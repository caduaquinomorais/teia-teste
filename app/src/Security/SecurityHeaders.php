<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

use Teia\Http\Response;

/** Cabeçalhos de segurança aplicados a todas as respostas. */
final class SecurityHeaders
{
    /** Origens permitidas em iframes do site público (mapa do Google). */
    public const MAP_FRAME_SOURCES = ['https://www.google.com', 'https://maps.google.com'];

    public static function nonce(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    public static function apply(Response $response, string $nonce, bool $https, bool $allowMapFrames = false): void
    {
        if (!$response->hasHeader('Content-Security-Policy')) {
            $frameSrc = $allowMapFrames ? implode(' ', self::MAP_FRAME_SOURCES) : "'none'";
            $response->header('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'nonce-{$nonce}'",
                "img-src 'self' data:",
                "font-src 'self'",
                "connect-src 'self'",
                "frame-src {$frameSrc}",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]));
        }
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('Cross-Origin-Opener-Policy', 'same-origin');
        if ($https) {
            // Sem includeSubDomains: o domínio é compartilhado com outros projetos da ADE SAMPA.
            $response->header('Strict-Transport-Security', 'max-age=31536000');
        }
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
    }
}
