<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Auth\UserRepository;
use Teia\Database\Database;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\Install\InstallException;
use Teia\Security\Csrf;
use Teia\Security\PasswordPolicy;

/**
 * /instalar — disponível apenas enquanto o site não está instalado.
 * Depois da instalação, responde 404 para sempre.
 */
final class InstallController extends Controller
{
    public function show(Request $request): Response
    {
        $this->guard();
        return $this->view([]);
    }

    public function submit(Request $request): Response
    {
        $this->guard();
        if (!Csrf::sameOrigin($request)) {
            throw HttpException::forbidden('Origem da requisição inválida.');
        }
        $installer = $this->app->installer();
        $step = $request->str('etapa');

        if ($step === 'preparar') {
            try {
                $installer->prepare($this->app->paths->app . '/migrations');
            } catch (InstallException $ex) {
                return $this->view(['errors' => [$ex->getMessage()]], 422);
            }
            return Response::redirect(url('/instalar'));
        }

        if ($step !== 'concluir' || !$installer->isPrepared()) {
            throw new HttpException(400, 'Etapa inválida.');
        }

        $csrf = new Csrf($this->app->keys());
        if (!$csrf->validStateless('instalar', $request->str(Csrf::FIELD))) {
            return $this->view(['errors' => ['O formulário expirou. Tente novamente.']], 403);
        }

        $db = new Database($this->app->paths->database());
        $limiter = new \Teia\Security\RateLimiter($db, $this->app->keys());
        $ipKey = 'install:ip:' . $this->app->clientIp();
        $max = $this->app->config->int('token_max_attempts', 5);
        $window = $this->app->config->int('token_window', 900);
        if ($limiter->tooMany($ipKey, $max, $window) || $limiter->tooMany('install:global', $max * 4, $window)) {
            return $this->view(['errors' => ['Muitas tentativas. Aguarde 15 minutos e tente novamente.']], 429);
        }

        $old = [
            'username' => mb_strtolower(trim($request->str('username')), 'UTF-8'),
            'name' => trim($request->str('name')),
            'email' => trim($request->str('email')),
        ];
        if (!$this->app->installer()->verifyToken($request->str('token'))) {
            $limiter->hit($ipKey, $window);
            $limiter->hit('install:global', $window);
            return $this->view(['errors' => ['Token inválido.'], 'old' => $old], 403);
        }

        $errors = $this->validateAccount($old, $request->str('password'), $request->str('password_confirm'));
        if ($errors !== []) {
            return $this->view(['errors' => $errors, 'old' => $old], 422);
        }

        $db->transaction(function (Database $db) use ($old, $request): void {
            if ((int) $db->fetchValue('SELECT COUNT(*) FROM users') > 0) {
                throw new HttpException(409, 'Já existe uma conta administrativa.');
            }
            $users = new UserRepository($db);
            $id = $users->create($old['username'], $old['name'], $old['email'] ?: null, $request->str('password'), 'admin', false);
            (new \Teia\Auth\Audit($db, $this->app->keys()))->log(['id' => $id, 'username' => $old['username']], 'instalacao.concluida', null, [], $this->app->clientIp());
            $this->app->installer()->finish($db);
        });
        $this->app->markInstalled();
        $this->app->logger->info('Instalação concluída', ['usuario' => $old['username']]);

        return $this->render('install/done', []);
    }

    /** Após a instalação, /instalar não existe mais (404). */
    private function guard(): void
    {
        if ($this->app->isInstalled()) {
            throw HttpException::notFound();
        }
    }

    private function view(array $data, int $status = 200): Response
    {
        $installer = $this->app->installer();
        $prepared = $installer->isPrepared();
        $data += [
            'checks' => $installer->requirements(),
            'requirementsOk' => $installer->requirementsOk(),
            'prepared' => $prepared,
            'csrf' => $prepared ? (new Csrf($this->app->keys()))->issueStateless('instalar') : '',
            'errors' => [],
            'old' => [],
        ];
        return $this->render('install/index', $data, $status);
    }

    private function validateAccount(array $old, string $password, string $confirm): array
    {
        $errors = [];
        if (!UserRepository::validUsername($old['username'])) {
            $errors[] = 'Usuário: use de 3 a 40 caracteres (letras minúsculas, números, ponto, hífen ou sublinhado).';
        }
        if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
            $errors[] = 'Informe o nome (até 100 caracteres).';
        }
        if ($old['email'] !== '' && filter_var($old['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'E-mail inválido.';
        }
        if ($password !== $confirm) {
            $errors[] = 'A confirmação da senha não confere.';
        }
        return array_merge($errors, PasswordPolicy::validate($password, $old['username'], ['teia', 'adesampa']));
    }
}
