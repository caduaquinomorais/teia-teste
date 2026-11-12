<?php

declare(strict_types=1);

namespace Teia\Http;

defined('TEIA_ROOT') || exit;

/** Requisição HTTP. O caminho ($path) já vem sem o base path (ex.: /teia). */
final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath,
        public readonly array $server,
    ) {
    }

    public static function fromGlobals(?string $configuredBasePath = null): self
    {
        $server = $_SERVER;
        $script = (string) ($server['SCRIPT_NAME'] ?? '');
        // O servidor embutido do PHP preenche SCRIPT_NAME com o caminho pedido quando ele tem extensão
        // (ex.: /x.webp). Só o front controller (…/index.php) indica o base path real.
        if (!str_ends_with($script, '/index.php')) {
            $script = '/index.php';
        }
        $basePath = $configuredBasePath ?? self::detectBasePath($script);
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?? '/'));

        return new self(
            strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            self::stripBasePath($path, $basePath),
            $basePath,
            $server,
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
        if ($dir === '/' || $dir === '.') {
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
        return preg_replace('#/{2,}#', '/', $path) ?? '/';
    }

    public function header(string $name): ?string
    {
        $value = $this->server['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null;
        return is_string($value) ? $value : null;
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
        $fromProxy = $trustedProxies !== [] && in_array($this->server['REMOTE_ADDR'] ?? '', $trustedProxies, true);
        return $fromProxy && strtolower((string) $this->header('X-Forwarded-Proto')) === 'https';
    }
}
