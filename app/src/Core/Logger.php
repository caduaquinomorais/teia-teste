<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

/** Registra erros no log do PHP do servidor (error_log). Nunca exibido ao visitante. */
final class Logger
{
    public function error(string $message, array $context = []): void
    {
        error_log('[TEIA] ' . str_replace(["\r", "\n"], ' ', $message) . ($context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR) : ''));
    }
}
