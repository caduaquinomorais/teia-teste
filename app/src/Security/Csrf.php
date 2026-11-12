<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

use Teia\Http\Request;

/**
 * Proteção CSRF.
 * - Painel: token sincronizador guardado na sessão (campo _csrf).
 * - Telas sem sessão (instalador, recuperação): token HMAC com carimbo de tempo e finalidade.
 * - Em ambos: validação de Origin/Referer quando presentes.
 */
final class Csrf
{
    public const FIELD = '_csrf';

    public function __construct(private readonly ?Keys $keys = null)
    {
    }

    // ---------- Token sincronizador (sessão) ----------

    public static function sessionToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function rotateSessionToken(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function validSessionToken(Request $request): bool
    {
        $sent = $request->post[self::FIELD] ?? $request->header('X-CSRF-Token');
        $expected = $_SESSION['_csrf'] ?? null;
        return is_string($sent) && is_string($expected) && $expected !== '' && hash_equals($expected, $sent);
    }

    // ---------- Token HMAC sem estado ----------

    public function issueStateless(string $purpose, ?int $now = null): string
    {
        $time = (string) ($now ?? time());
        $nonce = bin2hex(random_bytes(8));
        $mac = $this->requireKeys()->hmac('csrf', $purpose . '|' . $time . '|' . $nonce);
        return $time . '.' . $nonce . '.' . $mac;
    }

    public function validStateless(string $purpose, mixed $token, int $maxAge = 7200, ?int $now = null): bool
    {
        if (!is_string($token) || !preg_match('/^(\d{1,12})\.([a-f0-9]{16})\.([a-f0-9]{64})$/', $token, $m)) {
            return false;
        }
        $now ??= time();
        $time = (int) $m[1];
        if ($time > $now + 60 || $now - $time > $maxAge) {
            return false;
        }
        $expected = $this->requireKeys()->hmac('csrf', $purpose . '|' . $m[1] . '|' . $m[2]);
        return hash_equals($expected, $m[3]);
    }

    // ---------- Origem ----------

    /**
     * Rejeita requisições cuja Origin (ou Referer) aponta para outro host.
     * Sem Origin e sem Referer: aceita (navegadores antigos), pois o token continua obrigatório.
     */
    public static function sameOrigin(Request $request): bool
    {
        $host = $request->host();
        $origin = $request->header('Origin');
        if ($origin !== null && $origin !== '') {
            if ($origin === 'null') {
                return false;
            }
            return strtolower((string) parse_url($origin, PHP_URL_HOST)) . self::port($origin) === self::hostWithoutDefaultPort($host);
        }
        $referer = $request->header('Referer');
        if ($referer !== null && $referer !== '') {
            return strtolower((string) parse_url($referer, PHP_URL_HOST)) . self::port($referer) === self::hostWithoutDefaultPort($host);
        }
        return true;
    }

    private static function port(string $url): string
    {
        $port = parse_url($url, PHP_URL_PORT);
        return $port ? ':' . $port : '';
    }

    private static function hostWithoutDefaultPort(string $host): string
    {
        return preg_replace('/:(80|443)$/', '', $host) ?? $host;
    }

    private function requireKeys(): Keys
    {
        if ($this->keys === null) {
            throw new \RuntimeException('Chave da aplicação indisponível.');
        }
        return $this->keys;
    }
}
