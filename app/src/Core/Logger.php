<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

/** Log em arquivo (um por dia) dentro de <dados>/logs. Nunca exibido ao visitante. */
final class Logger
{
    public function __construct(private readonly string $dir)
    {
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        if (!is_dir($this->dir)) {
            // Instalação incompleta: usa o log do PHP.
            error_log("[TEIA][$level] $message " . ($context ? json_encode($context) : ''));
            return;
        }
        $line = sprintf(
            "[%s] %s %s %s\n",
            date('c'),
            $level,
            str_replace(["\r", "\n"], ' ', $message),
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR) : ''
        );
        @file_put_contents($this->dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
