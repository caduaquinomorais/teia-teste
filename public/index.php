<?php

declare(strict_types=1);

/*
 * Front controller do TEIA. Toda requisição que não é um arquivo estático passa por aqui.
 */

// Servidor embutido do PHP (desenvolvimento/testes): arquivos estáticos de public/ são servidos direto.
if (PHP_SAPI === 'cli-server') {
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $file = realpath(__DIR__ . rawurldecode($path));
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
        && !str_ends_with($file, '.php') && !str_contains($path, '/.')) {
        return false;
    }
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
}

require dirname(__DIR__) . '/app/bootstrap.php';

// Avisos do PHP viram exceções (tratadas e registradas no log, nunca exibidas).
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$paths = new Teia\Core\Paths(TEIA_ROOT);
$app = new Teia\Core\App($paths);
$request = Teia\Http\Request::fromGlobals($app->config->get('base_path'));
$app->handle($request)->send();
