<?php

declare(strict_types=1);

/*
 * Bootstrap do TEIA.
 * Registra o autoloader (PSR-4, namespace Teia\ → app/src/) e os helpers globais.
 * Nenhuma dependência externa: não há Composer em produção.
 */

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    echo 'O TEIA exige PHP 8.1 ou superior.';
    exit(1);
}

define('TEIA_ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Teia\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/src/Core/helpers.php';
