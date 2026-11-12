<?php

declare(strict_types=1);

namespace Teia\Http;

defined('TEIA_ROOT') || exit;

/** Requisição HTTP imutável. O caminho ($path) já vem sem o base path (ex.: /teia). */
final class Request
{
    /** @var array<string,string> */
    public array $attributes = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $server,
        public readonly array $cookies,
    ) {
    }

    public static function fromGlobals(?string $configuredBasePath = null): self
    {
        $server = $_SERVER;
        $script = (string) ($server['SCRIPT_NAME'] ?? '');
        // O servidor embutido do PHP preenche SCRIPT_NAME com o caminho pedido quando ele tem extensão
        // (ex.: /midia/x.webp). Só o front controller (…/index.php) indica o base path real.
        if (!str_ends_with($script, '/index.php')) {
            $script = '/index.php';
        }
        $basePath = $configuredBasePath ?? self::detectBasePath($script);
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?? '/'));
        $path = self::stripBasePath($path, $basePath);

        return new self(
            strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $basePath,
            $_GET,
            $_POST,
            $_FILES,
            $server,
            $_COOKIE,
        );
    }

    /**
     * Detecta o base path a partir do SCRIPT_NAME do front controller.
     *  /index.php                → ''       (document root aponta para public/)
     *  /teia/public/index.php    → '/teia'  (repositório publicado em www/teia com .htaccess raiz)
     *  /teia/index.php           → '/teia'
     */
    public static function detectBasePath(string $scriptName): string
    {
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ($dir === '/' || $dir === '.' || $dir === '\\') {
            $dir = '';
        }
        if (str_ends_with($dir, '/public')) {
            $dir = substr($dir, 0, -strlen('/public'));
        }
        return rtrim($dir, '/');
    }

    public static function stripBasePath(string $path, string $basePath): string
    {
        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
        }
        if (str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public'));
        }
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        // Remove barras duplicadas.
        return preg_replace('#/{2,}#', '/', $path) ?? '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public function queryStr(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'application/json');
    }

    public function host(): string
    {
        return strtolower((string) ($this->server['HTTP_HOST'] ?? ''));
    }

    public function isHttps(array $trustedProxies = []): bool
    {
        $https = $this->server['HTTPS'] ?? '';
        if ($https !== '' && strtolower((string) $https) !== 'off') {
            return true;
        }
        if ((string) ($this->server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if ($this->fromTrustedProxy($trustedProxies)) {
            return strtolower((string) $this->header('X-Forwarded-Proto')) === 'https';
        }
        return false;
    }

    public function ip(array $trustedProxies = []): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        if ($this->fromTrustedProxy($trustedProxies)) {
            $forwarded = (string) $this->header('X-Forwarded-For');
            $first = trim(explode(',', $forwarded)[0] ?? '');
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return $remote;
    }

    private function fromTrustedProxy(array $trustedProxies): bool
    {
        return $trustedProxies !== [] && in_array($this->server['REMOTE_ADDR'] ?? '', $trustedProxies, true);
    }
}
