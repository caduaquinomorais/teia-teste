<?php

declare(strict_types=1);

defined('TEIA_ROOT') || exit;

use Teia\Core\App;

/**
 * Escapa texto para HTML (conteúdo e atributos). Use SEMPRE ao imprimir dados.
 */
function e(mixed $value): string
{
    if ($value === null || is_array($value) || is_object($value)) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL interna considerando o base path (ex.: /teia).
 * Arquivos estáticos (/assets/…) recebem o prefixo /public quando o servidor não encaminha
 * a requisição para public/ (ver Request::detectPublicPrefix e o index.php da raiz).
 */
function url(string $path = '/'): string
{
    $app = App::instance();
    $base = $app?->basePath() ?? '';
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    if (str_starts_with($path, '/assets/')) {
        $base .= $app?->publicPrefix() ?? '';
    }
    return $base . $path;
}

/** URL de arquivo estático em public/assets, com versão para cache. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = TEIA_ROOT . '/public/assets/' . $path;
    $version = is_file($file) ? substr((string) filemtime($file), -6) : '0';
    return url('/assets/' . $path) . '?v=' . $version;
}

/** Nonce CSP da requisição atual (para <script> e <style>). */
function csp_nonce(): string
{
    return App::instance()?->cspNonce() ?? '';
}
