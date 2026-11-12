<?php

declare(strict_types=1);

/*
 * Runner de testes próprio (sem PHPUnit).
 *
 *   bin/php.sh tests/run.php              # todos
 *   bin/php.sh tests/run.php unit         # só testes unitários
 *   bin/php.sh tests/run.php http         # só testes HTTP (servidor embutido)
 *   bin/php.sh tests/run.php http/Seguranca   # um arquivo
 *
 * Cada arquivo em tests/unit e tests/http retorna um array ['nome do teste' => fn () => ...].
 */

use Teia\Tests\AssertionFailed;
use Teia\Tests\Skipped;

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/lib/Test.php';
require __DIR__ . '/lib/Server.php';
require __DIR__ . '/lib/Http.php';
require __DIR__ . '/lib/Fixtures.php';

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$filter = $argv[1] ?? '';
$files = array_merge(glob(__DIR__ . '/unit/*Test.php') ?: [], glob(__DIR__ . '/http/*Test.php') ?: []);
$files = array_values(array_filter($files, static function (string $f) use ($filter): bool {
    $rel = str_replace('\\', '/', substr($f, strlen(__DIR__) + 1));
    return $filter === '' || str_starts_with($rel, $filter);
}));

$color = stream_isatty(STDOUT);
$paint = static fn (string $code, string $text): string => $color ? "\033[{$code}m{$text}\033[0m" : $text;

$passed = $failed = $skipped = 0;
$failures = [];
$started = microtime(true);
echo 'TEIA — testes (PHP ' . PHP_VERSION . ', ' . PHP_OS_FAMILY . ')' . PHP_EOL;

foreach ($files as $file) {
    $rel = str_replace('\\', '/', substr($file, strlen(__DIR__) + 1));
    echo PHP_EOL . $paint('1', $rel) . PHP_EOL;
    $tests = require $file;
    foreach ($tests as $name => $fn) {
        $t0 = microtime(true);
        try {
            $fn();
            $passed++;
            printf("  %s %s %s\n", $paint('32', '✔'), $name, $paint('2', sprintf('(%.0f ms)', (microtime(true) - $t0) * 1000)));
        } catch (Skipped $ex) {
            $skipped++;
            printf("  %s %s — %s\n", $paint('33', '○'), $name, $ex->getMessage());
        } catch (\Throwable $ex) {
            $failed++;
            $where = $ex instanceof AssertionFailed ? '' : ' [' . get_class($ex) . ' em ' . basename($ex->getFile()) . ':' . $ex->getLine() . ']';
            $failures[] = "$rel › $name: " . $ex->getMessage() . $where;
            printf("  %s %s\n      %s%s\n", $paint('31', '✘'), $name, $ex->getMessage(), $where);
        }
    }
    gc_collect_cycles();
}

$total = $passed + $failed + $skipped;
echo PHP_EOL . str_repeat('─', 60) . PHP_EOL;
printf("Resultado: %d/%d aprovados, %d falhas, %d ignorados (%.1f s)\n", $passed, $total, $failed, $skipped, microtime(true) - $started);
if ($failures !== []) {
    echo PHP_EOL . 'Falhas:' . PHP_EOL . ' - ' . implode(PHP_EOL . ' - ', $failures) . PHP_EOL;
}
exit($failed > 0 ? 1 : 0);
