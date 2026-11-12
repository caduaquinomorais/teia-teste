<?php

declare(strict_types=1);

namespace Teia\Auth;

defined('TEIA_ROOT') || exit;

use Teia\Security\Csrf;
use Teia\Security\PasswordPolicy;
use Teia\Security\RateLimiter;

/**
 * Autenticação do painel.
 * Mensagens de erro sempre genéricas; limites por usuário e por IP; versão de sessão
 * para encerrar sessões após desativação, troca de senha ou revogação de permissão.
 */
final class Auth
{
    public const GENERIC_ERROR = 'Usuário ou senha inválidos.';
    public const LOCKED_ERROR = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';

    private ?array $user = null;
    private bool $resolved = false;

    public function __construct(
        private readonly UserRepository $users,
        private readonly SessionManager $session,
        private readonly RateLimiter $limiter,
        private readonly Audit $audit,
        private readonly int $maxPerUser,
        private readonly int $maxPerIp,
        private readonly int $window,
    ) {
    }

    /** @return array{ok:bool,error?:string,user?:array} */
    public function attempt(string $username, string $password, string $ip): array
    {
        $username = mb_strtolower(trim($username), 'UTF-8');
        $userKey = 'login:user:' . $username;
        $ipKey = 'login:ip:' . $ip;

        if ($this->limiter->tooMany($ipKey, $this->maxPerIp, $this->window)
            || $this->limiter->tooMany($userKey, $this->maxPerUser, $this->window)) {
            // Registra mesmo bloqueado, para manter a janela.
            $this->limiter->hit($ipKey, $this->window);
            $this->audit->log(null, 'login.bloqueado', $username, [], $ip);
            return ['ok' => false, 'error' => self::LOCKED_ERROR];
        }

        $user = $username !== '' && mb_strlen($password) <= PasswordPolicy::MAX ? $this->users->findByUsername($username) : null;
        // Verificação com hash fictício quando o usuário não existe (tempo de resposta uniforme).
        $hash = $user['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$YVVVWlBQNjZIN2p1T1V0MA$4VlG9hhOXYGHaj/+R6XGvLXPe/T5jAp1WFkI7kbVu6I';
        $valid = password_verify($password, $hash);

        if ($user === null || !$valid || (int) $user['active'] !== 1) {
            $this->limiter->hit($ipKey, $this->window);
            $this->limiter->hit($userKey, $this->window);
            $this->audit->log(null, 'login.falha', $username, [], $ip);
            return ['ok' => false, 'error' => self::GENERIC_ERROR];
        }

        if (PasswordPolicy::needsRehash($user['password_hash'])) {
            $this->users->rehash((int) $user['id'], $password);
        }
        $this->limiter->clear($userKey);
        $this->session->regenerate();
        Csrf::rotateSessionToken();
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['sv'] = (int) $user['session_version'];
        $this->users->touchLogin((int) $user['id']);
        $this->audit->log($user, 'login.sucesso', null, [], $ip);
        $this->user = $user;
        $this->resolved = true;
        return ['ok' => true, 'user' => $user];
    }

    /** Usuário autenticado da sessão atual, revalidado no banco a cada requisição. */
    public function user(): ?array
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;
        $uid = (int) ($_SESSION['uid'] ?? 0);
        if ($uid <= 0) {
            return null;
        }
        $user = $this->users->find($uid);
        if ($user === null || (int) $user['active'] !== 1 || (int) $user['session_version'] !== (int) ($_SESSION['sv'] ?? -1)) {
            // Conta desativada, senha trocada ou permissão revogada: encerra a sessão.
            $this->session->reset();
            $_SESSION['_expired'] = true;
            return null;
        }
        $this->user = $user;
        return $user;
    }

    /** Atualiza a versão de sessão guardada (após o próprio usuário trocar a senha). */
    public function refreshSessionVersion(): void
    {
        $uid = (int) ($_SESSION['uid'] ?? 0);
        $user = $uid ? $this->users->find($uid) : null;
        if ($user) {
            $this->session->regenerate();
            Csrf::rotateSessionToken();
            $_SESSION['sv'] = (int) $user['session_version'];
            $this->user = $user;
        }
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
        $this->resolved = true;
    }

    public static function isAdmin(?array $user): bool
    {
        return $user !== null && $user['role'] === 'admin';
    }

    public function canEditPage(?array $user, string $slug): bool
    {
        if ($user === null) {
            return false;
        }
        if (self::isAdmin($user)) {
            return true;
        }
        return in_array($slug, $this->users->pagePermissions((int) $user['id']), true);
    }
}
