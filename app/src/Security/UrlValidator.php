<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

/**
 * Valida URLs editáveis pelo CMS. Permitido somente:
 *  - http:// e https:// (com host);
 *  - mailto: e tel:;
 *  - âncoras (#secao).
 * Qualquer outro esquema (javascript:, data:, vbscript:, file: …) é recusado.
 */
final class UrlValidator
{
    public static function isAllowed(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048) {
            return false;
        }
        // Caracteres de controle e espaços internos não são aceitos (evita ofuscação de esquema).
        if (preg_match('/[\x00-\x20\x7F]/', $url)) {
            return false;
        }
        if ($url[0] === '#') {
            return (bool) preg_match('/^#[A-Za-z][A-Za-z0-9_-]{0,63}$/', $url);
        }
        $lower = strtolower($url);
        if (str_starts_with($lower, 'mailto:')) {
            $address = substr($url, 7);
            $address = explode('?', $address, 2)[0];
            return filter_var(rawurldecode($address), FILTER_VALIDATE_EMAIL) !== false;
        }
        if (str_starts_with($lower, 'tel:')) {
            return (bool) preg_match('/^tel:\+?[0-9().\-]{3,20}$/i', $url);
        }
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function isExternal(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', trim($url));
    }

    /** URL de incorporação do Google Maps (iframe do mapa). */
    public static function isGoogleMapsEmbed(string $url): bool
    {
        if (!self::isAllowed($url) || !str_starts_with(strtolower($url), 'https://')) {
            return false;
        }
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        return in_array($host, ['www.google.com', 'maps.google.com'], true)
            && str_starts_with($path, '/maps');
    }
}
