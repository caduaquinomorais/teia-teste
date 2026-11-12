<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Auth\Auth;
use Teia\Core\App;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\Security\Csrf;

/** Base dos controladores, com os controles de acesso do painel. */
abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html($this->app->view->render($template, $data), $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    // ------------------------------------------------------------------
    // Painel: sessão, CSRF e autorização (validados no backend em TODA rota).
    // ------------------------------------------------------------------

    /** Painel exige instalação concluída. */
    protected function requireInstalled(): void
    {
        if (!$this->app->isInstalled()) {
            if ($this->app->request()->method === 'GET') {
                throw new RedirectException(url('/instalar'));
            }
            throw new HttpException(503, 'O site ainda não foi instalado.');
        }
    }

    /** Inicia a sessão e, em POST, valida Origin e token CSRF. */
    protected function adminRequest(Request $request): void
    {
        $this->requireInstalled();
        $this->app->session()->start();
        if ($request->isPost()) {
            if (!Csrf::sameOrigin($request) || !Csrf::validSessionToken($request)) {
                throw new HttpException(403, 'Sessão expirada ou formulário inválido. Recarregue a página e tente novamente.');
            }
        }
    }

    /**
     * Exige usuário autenticado. GET sem sessão → redireciona ao login; POST → 403.
     * Senha temporária obriga a troca antes de qualquer outra ação.
     */
    protected function requireUser(Request $request, bool $allowPasswordChange = false): array
    {
        $this->adminRequest($request);
        $user = $this->app->auth()->user();
        if ($user === null) {
            if ($request->isPost()) {
                throw HttpException::forbidden('Faça login para continuar.');
            }
            if (!empty($_SESSION['_expired'])) {
                unset($_SESSION['_expired']);
                $this->app->session()->flash('info', 'Sua sessão expirou. Entre novamente.');
            }
            throw new RedirectException(url('/admin/login'));
        }
        if ((int) $user['must_change_password'] === 1 && !$allowPasswordChange) {
            if ($request->isPost()) {
                throw HttpException::forbidden('Troque sua senha temporária antes de continuar.');
            }
            throw new RedirectException(url('/admin/senha'));
        }
        return $user;
    }

    protected function requireAdmin(Request $request): array
    {
        $user = $this->requireUser($request);
        if (!Auth::isAdmin($user)) {
            throw HttpException::forbidden('Somente administradores podem acessar esta área.');
        }
        return $user;
    }

    protected function adminView(string $template, array $data, ?array $user, int $status = 200): Response
    {
        $data += [
            'user' => $user,
            'csrf' => Csrf::sessionToken(),
            'flashes' => $this->app->session()->takeFlashes(),
            'pages' => $this->app->pages()->all(),
        ];
        return $this->render($template, $data, $status);
    }

    protected function flash(string $type, string $message): void
    {
        $this->app->session()->flash($type, $message);
    }

    protected function audit(?array $user, string $action, ?string $target = null, array $details = []): void
    {
        $this->app->audit()->log($user, $action, $target, $details, $this->app->clientIp());
    }
}
