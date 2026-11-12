<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Auth\Auth;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\Security\Csrf;
use Teia\Security\PasswordPolicy;

final class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->requireUser($request);
        $this->autoBackup();
        $pages = [];
        foreach ($this->app->pages()->all() as $slug => $page) {
            if ($this->app->auth()->canEditPage($user, $slug)) {
                $current = $this->app->content()->current($slug);
                $pages[$slug] = $page + ['version' => $current['version'], 'updated_at' => $current['updated_at']];
            }
        }
        return $this->adminView('admin/dashboard', ['editable' => $pages], $user);
    }

    public function loginForm(Request $request): Response
    {
        $this->adminRequest($request);
        if ($this->app->auth()->user() !== null) {
            return $this->redirect('/admin');
        }
        return $this->adminView('admin/login', ['error' => null, 'username' => ''], null);
    }

    public function login(Request $request): Response
    {
        $this->adminRequest($request);
        $username = $request->str('username');
        $result = $this->app->auth()->attempt($username, $request->str('password'), $this->app->clientIp());
        if (!$result['ok']) {
            $status = $result['error'] === Auth::LOCKED_ERROR ? 429 : 401;
            return $this->adminView('admin/login', ['error' => $result['error'], 'username' => mb_substr($username, 0, 40)], null, $status);
        }
        $user = $result['user'];
        $this->autoBackup();
        return $this->redirect((int) $user['must_change_password'] === 1 ? '/admin/senha' : '/admin');
    }

    public function logout(Request $request): Response
    {
        $this->adminRequest($request);
        $user = $this->app->auth()->user();
        if ($user !== null) {
            $this->audit($user, 'logout');
        }
        $this->app->auth()->logout();
        return $this->redirect('/admin/login');
    }

    public function passwordForm(Request $request): Response
    {
        $user = $this->requireUser($request, true);
        return $this->adminView('admin/password', ['errors' => []], $user);
    }

    public function passwordSave(Request $request): Response
    {
        $user = $this->requireUser($request, true);
        $current = $request->str('current_password');
        $new = $request->str('password');
        $errors = [];
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'A senha atual está incorreta.';
        }
        if ($new !== $request->str('password_confirm')) {
            $errors[] = 'A confirmação da nova senha não confere.';
        }
        if ($current !== '' && hash_equals($current, $new)) {
            $errors[] = 'A nova senha deve ser diferente da atual.';
        }
        $errors = array_merge($errors, PasswordPolicy::validate($new, $user['username'], [$user['name'], 'teia', 'adesampa']));
        if ($errors !== []) {
            return $this->adminView('admin/password', ['errors' => $errors], $user, 422);
        }
        $this->app->users()->setPassword((int) $user['id'], $new, false);
        $this->app->auth()->refreshSessionVersion();
        $this->audit($user, 'senha.alterada', 'usuario:' . $user['id']);
        $this->flash('success', 'Senha alterada com sucesso. Outras sessões abertas foram encerradas.');
        return $this->redirect('/admin');
    }

    // ------------------------------------------------------------------
    // Recuperação de acesso (token no diretório de dados, obtido via FTP)
    // ------------------------------------------------------------------

    public function recoverForm(Request $request): Response
    {
        $this->requireInstalled();
        return $this->recoverView([]);
    }

    public function recover(Request $request): Response
    {
        $this->requireInstalled();
        if (!Csrf::sameOrigin($request)) {
            throw HttpException::forbidden('Origem da requisição inválida.');
        }
        $csrf = new Csrf($this->app->keys());
        if (!$csrf->validStateless('recuperar', $request->str(Csrf::FIELD))) {
            return $this->recoverView(['errors' => ['O formulário expirou. Tente novamente.']], 403);
        }
        $installer = $this->app->installer();
        $limiter = $this->app->limiter();
        $ttl = $this->app->config->int('recovery_token_ttl', 1800);
        $max = $this->app->config->int('token_max_attempts', 5);
        $window = $this->app->config->int('token_window', 900);
        $ipKey = 'recover:ip:' . $this->app->clientIp();

        if ($limiter->tooMany($ipKey, $max, $window) || $limiter->tooMany('recover:global', $max * 4, $window)) {
            return $this->recoverView(['errors' => ['Muitas tentativas. Aguarde 15 minutos e tente novamente.']], 429);
        }

        if ($request->str('etapa') === 'gerar') {
            $limiter->hit($ipKey, $window);
            $created = $installer->issueRecoveryToken($ttl);
            $this->app->audit()->log(null, 'recuperacao.token_gerado', null, ['novo' => $created], $this->app->clientIp());
            return $this->recoverView(['generated' => true]);
        }

        $username = mb_strtolower(trim($request->str('username')), 'UTF-8');
        if (!$installer->verifyRecoveryToken($request->str('token'), $ttl)) {
            $limiter->hit($ipKey, $window);
            $limiter->hit('recover:global', $window);
            $this->app->audit()->log(null, 'recuperacao.token_invalido', $username, [], $this->app->clientIp());
            return $this->recoverView(['errors' => ['Token inválido ou expirado.'], 'username' => $username], 403);
        }
        $users = $this->app->users();
        $target = $users->findByUsername($username);
        if ($target === null || $target['role'] !== 'admin') {
            $limiter->hit($ipKey, $window);
            return $this->recoverView(['errors' => ['Informe o usuário de um administrador.'], 'username' => $username], 422);
        }
        $password = $request->str('password');
        $errors = $password !== $request->str('password_confirm') ? ['A confirmação da senha não confere.'] : [];
        $errors = array_merge($errors, PasswordPolicy::validate($password, $username, [$target['name'], 'teia', 'adesampa']));
        if ($errors !== []) {
            return $this->recoverView(['errors' => $errors, 'username' => $username], 422);
        }
        $this->app->db()->transaction(function () use ($users, $target, $password): void {
            $users->setPassword((int) $target['id'], $password, false);
            $users->setActive((int) $target['id'], true);
        });
        $installer->consumeRecoveryToken();
        $limiter->clear($ipKey);
        $limiter->clear('login:user:' . $username);
        $this->app->audit()->log($target, 'recuperacao.concluida', 'usuario:' . $target['id'], [], $this->app->clientIp());
        return $this->recoverView(['done' => true]);
    }

    public function auditLog(Request $request): Response
    {
        $user = $this->requireAdmin($request);
        $page = max(1, (int) $request->queryStr('p', '1'));
        $rows = $this->app->audit()->recent(101, ($page - 1) * 100);
        return $this->adminView('admin/audit', [
            'rows' => array_slice($rows, 0, 100),
            'page' => $page,
            'hasNext' => count($rows) > 100,
        ], $user);
    }

    private function recoverView(array $data, int $status = 200): Response
    {
        $data += [
            'csrf' => (new Csrf($this->app->keys()))->issueStateless('recuperar'),
            'errors' => [],
            'generated' => false,
            'done' => false,
            'username' => '',
        ];
        return $this->render('admin/recover', $data, $status);
    }

    /** Backup diário automático disparado pela atividade no painel (únicos momentos em que há mudanças). */
    private function autoBackup(): void
    {
        try {
            $service = $this->app->backups();
            $retention = (int) ($service->getSetting('backup_retention') ?? $this->app->config->int('backup_retention', 14));
            $name = $service->runIfDue($this->app->config->int('backup_interval_hours', 24), $retention);
            if ($name !== null) {
                $this->app->logger->info('Backup automático criado', ['arquivo' => $name]);
            }
        } catch (\Throwable $ex) {
            $this->app->logger->error('Falha no backup automático', ['erro' => $ex->getMessage()]);
        }
    }
}
