<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Backup\BackupException;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;

/** Backups: listar, criar, baixar, restaurar, excluir e configurar (somente administradores). */
final class AdminBackupsController extends Controller
{
    public function index(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        $service = $this->app->backups();
        return $this->adminView('admin/backups', [
            'backups' => $service->list(),
            'retention' => (int) ($service->getSetting('backup_retention') ?? $this->app->config->int('backup_retention', 14)),
            'lastAuto' => $service->getSetting('last_auto_backup_at'),
            'cronConfigured' => ($service->getSetting('cron_secret_hash') ?? '') !== '',
            'cronUrl' => $this->absoluteUrl('/cron/backup'),
            'maxUpload' => $this->maxUploadBytes(),
        ], $admin);
    }

    public function create(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        try {
            $name = $this->app->backups()->create('manual');
        } catch (BackupException $ex) {
            $this->flash('error', $ex->getMessage());
            return $this->redirect('/admin/backups');
        }
        $this->audit($admin, 'backup.criado', $name);
        $this->flash('success', 'Backup criado: ' . $name);
        return $this->redirect('/admin/backups');
    }

    public function settings(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        $service = $this->app->backups();
        $retention = (int) $request->str('retention');
        if ($retention < 1 || $retention > 365) {
            $this->flash('error', 'A retenção deve ser entre 1 e 365 backups.');
            return $this->redirect('/admin/backups');
        }
        $service->setSetting('backup_retention', (string) $retention);
        $details = ['retencao' => $retention];

        $secret = trim($request->str('cron_secret'));
        if ($request->str('cron_remove') === '1') {
            $service->setSetting('cron_secret_hash', '');
            $details['cron'] = 'removido';
        } elseif ($secret !== '') {
            if (mb_strlen($secret) < 16 || mb_strlen($secret) > 200) {
                $this->flash('error', 'O código de autenticação do cron deve ter entre 16 e 200 caracteres.');
                return $this->redirect('/admin/backups');
            }
            // Guarda apenas o HMAC do código (nunca o código em texto).
            $service->setSetting('cron_secret_hash', $this->app->keys()->hmac('cron', $secret));
            $details['cron'] = 'configurado';
        }
        $service->prune($retention);
        $this->audit($admin, 'backup.configuracao', null, $details);
        $this->flash('success', 'Configurações de backup salvas.');
        return $this->redirect('/admin/backups');
    }

    public function download(Request $request, array $params): Response
    {
        $admin = $this->requireAdmin($request);
        $path = $this->app->backups()->path($params['name']);
        if ($path === null) {
            throw HttpException::notFound();
        }
        $this->audit($admin, 'backup.baixado', $params['name']);
        return Response::file($path, 'application/zip', [
            'Content-Disposition' => 'attachment; filename="' . $params['name'] . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        $name = $request->str('name');
        if ($this->app->backups()->delete($name)) {
            $this->audit($admin, 'backup.excluido', $name);
            $this->flash('success', 'Backup excluído.');
        } else {
            $this->flash('error', 'Backup não encontrado.');
        }
        return $this->redirect('/admin/backups');
    }

    public function restore(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        if ($request->str('confirmacao') !== 'RESTAURAR') {
            $this->flash('error', 'Digite RESTAURAR para confirmar a restauração.');
            return $this->redirect('/admin/backups');
        }
        $service = $this->app->backups();
        $tmpUpload = null;
        $name = $request->str('name');
        if ($name !== '') {
            $zip = $service->path($name);
            if ($zip === null) {
                throw HttpException::notFound();
            }
            $label = $name;
        } else {
            $file = $request->files['arquivo'] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
                $this->flash('error', 'Selecione um arquivo de backup (.zip) ou escolha um backup da lista.');
                return $this->redirect('/admin/backups');
            }
            if ((int) $file['size'] > $this->app->config->int('backup_max_upload_bytes')) {
                $this->flash('error', 'O arquivo é grande demais.');
                return $this->redirect('/admin/backups');
            }
            $tmpUpload = $this->app->paths->tmp() . '/upload-' . bin2hex(random_bytes(6)) . '.zip';
            if (!move_uploaded_file((string) $file['tmp_name'], $tmpUpload)) {
                $this->flash('error', 'Não foi possível receber o arquivo.');
                return $this->redirect('/admin/backups');
            }
            $zip = $tmpUpload;
            $label = 'arquivo enviado';
        }

        try {
            $safety = $service->restore($zip, fn () => $this->app->closeDb(), fn () => $this->app->db());
        } catch (BackupException $ex) {
            $this->audit($admin, 'backup.restauracao_recusada', $label, ['motivo' => $ex->getMessage()]);
            $this->flash('error', 'Restauração recusada: ' . $ex->getMessage());
            return $this->redirect('/admin/backups');
        } finally {
            if ($tmpUpload !== null) {
                @unlink($tmpUpload);
            }
        }
        // Registra no banco restaurado.
        $this->app->audit()->log($admin, 'backup.restaurado', $label, ['backup_seguranca' => $safety], $this->app->clientIp());
        $this->app->session()->flash('success', 'Backup restaurado. Um backup de segurança do estado anterior foi criado: ' . $safety . '. Se sua sessão for encerrada, entre novamente.');
        return $this->redirect('/admin/backups');
    }

    /**
     * Backup diário via Cronjob da KingHost (chamada HTTP com cabeçalho X-Cron-Auth).
     * Responde 404 enquanto o código não for configurado no painel.
     */
    public function cron(Request $request): Response
    {
        if (!$this->app->isInstalled()) {
            throw HttpException::notFound();
        }
        $service = $this->app->backups();
        $expected = (string) ($service->getSetting('cron_secret_hash') ?? '');
        $sent = (string) ($request->header('X-Cron-Auth') ?? '');
        if ($expected === '') {
            throw HttpException::notFound();
        }
        $limiter = $this->app->limiter();
        $ipKey = 'cron:ip:' . $this->app->clientIp();
        if ($limiter->tooMany($ipKey, 10, 3600)) {
            throw new HttpException(429, 'Muitas tentativas.');
        }
        if ($sent === '' || !hash_equals($expected, $this->app->keys()->hmac('cron', $sent))) {
            $limiter->hit($ipKey, 3600);
            throw HttpException::forbidden();
        }
        $retention = (int) ($service->getSetting('backup_retention') ?? $this->app->config->int('backup_retention', 14));
        try {
            $name = $service->runIfDue(max(1, $this->app->config->int('backup_interval_hours', 24) - 1), $retention);
        } catch (\Throwable $ex) {
            $this->app->logger->error('Falha no backup via cron', ['erro' => $ex->getMessage()]);
            return Response::json(['ok' => false], 500);
        }
        return Response::json(['ok' => true, 'backup' => $name !== null]);
    }

    private function absoluteUrl(string $path): string
    {
        $scheme = $this->app->https() ? 'https' : 'http';
        return $scheme . '://' . $this->app->request()->host() . url($path);
    }

    private function maxUploadBytes(): int
    {
        $parse = static function (string $v): int {
            $v = trim($v);
            $n = (int) $v;
            return match (strtolower(substr($v, -1))) {
                'g' => $n * 1073741824,
                'm' => $n * 1048576,
                'k' => $n * 1024,
                default => $n,
            };
        };
        return min($parse((string) ini_get('upload_max_filesize')), $parse((string) ini_get('post_max_size')));
    }
}
