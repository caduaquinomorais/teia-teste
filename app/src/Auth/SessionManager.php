<?php

declare(strict_types=1);

namespace Teia\Auth;

defined('TEIA_ROOT') || exit;

/**
 * Sessão do painel administrativo.
 * - Só é iniciada em rotas /admin (visitantes do site nunca recebem cookie).
 * - Arquivos de sessão em <dados>/sessions (fora da área pública).
 * - Cookie HttpOnly, SameSite=Lax, Secure em HTTPS, restrito ao caminho /admin.
 * - Expiração por inatividade e expiração absoluta.
 */
final class SessionManager
{
    public const COOKIE = 'teia_admin';

    private bool $started = false;

    public function __construct(
        private readonly string $savePath,
        private readonly string $cookiePath,
        private readonly bool $secure,
        private readonly int $idleTimeout,
        private readonly int $absoluteTimeout,
    ) {
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }
        if (!is_dir($this->savePath)) {
            throw new \RuntimeException('Diretório de sessões inexistente.');
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) $this->absoluteTimeout);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '50');
        session_save_path($this->savePath);
        session_name(self::COOKIE);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $this->cookiePath,
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_cache_limiter('nocache');
        session_start();
        $this->started = true;

        $now = time();
        $created = (int) ($_SESSION['_created'] ?? 0);
        $last = (int) ($_SESSION['_last'] ?? 0);
        if ($created > 0 && ($now - $created > $this->absoluteTimeout || $now - $last > $this->idleTimeout)) {
            $this->reset();
            $_SESSION['_expired'] = true;
        }
        if (empty($_SESSION['_created'])) {
            $_SESSION['_created'] = $now;
        }
        $_SESSION['_last'] = $now;
    }

    /** Novo ID de sessão (após login / troca de privilégio). */
    public function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
        $_SESSION['_last'] = time();
    }

    /** Limpa os dados e gera novo ID, mantendo a sessão ativa. */
    public function reset(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
        $_SESSION['_last'] = time();
    }

    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
        $this->started = false;
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int,array{type:string,message:string}> */
    public function takeFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($flashes) ? $flashes : [];
    }

    public function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
