<?php

declare(strict_types=1);

namespace Teia\Tests;

/** Asserções mínimas do runner próprio (sem PHPUnit). */
final class AssertionFailed extends \RuntimeException
{
}

final class Skipped extends \RuntimeException
{
}

final class T
{
    public static function true(mixed $value, string $message = 'esperado verdadeiro'): void
    {
        if ($value !== true) {
            throw new AssertionFailed($message . ' (obtido: ' . self::show($value) . ')');
        }
    }

    public static function false(mixed $value, string $message = 'esperado falso'): void
    {
        if ($value !== false) {
            throw new AssertionFailed($message . ' (obtido: ' . self::show($value) . ')');
        }
    }

    public static function same(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailed(trim($message . ' esperado ' . self::show($expected) . ', obtido ' . self::show($actual)));
        }
    }

    public static function contains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailed(trim($message . ' texto não contém ' . self::show($needle)));
        }
    }

    public static function notContains(string $needle, string $haystack, string $message = ''): void
    {
        if (str_contains($haystack, $needle)) {
            throw new AssertionFailed(trim($message . ' texto NÃO deveria conter ' . self::show($needle)));
        }
    }

    public static function matches(string $pattern, string $subject, string $message = ''): void
    {
        if (!preg_match($pattern, $subject)) {
            throw new AssertionFailed(trim($message . ' não corresponde a ' . $pattern));
        }
    }

    /** @param callable():void $fn */
    public static function throws(string $class, callable $fn, string $message = ''): \Throwable
    {
        try {
            $fn();
        } catch (\Throwable $ex) {
            if ($ex instanceof $class) {
                return $ex;
            }
            throw new AssertionFailed(trim($message . " esperada $class, lançada " . get_class($ex) . ': ' . $ex->getMessage()));
        }
        throw new AssertionFailed(trim($message . " esperada exceção $class, nenhuma lançada"));
    }

    public static function skip(string $reason): never
    {
        throw new Skipped($reason);
    }

    private static function show(mixed $v): string
    {
        $s = is_string($v) ? '"' . $v . '"' : var_export($v, true);
        return mb_strlen($s) > 160 ? mb_substr($s, 0, 160) . '…' : $s;
    }
}

/** Diretório temporário isolado por teste. */
function tmpdir(string $prefix = 'teia-teste-'): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(5));
    mkdir($dir, 0700, true);
    register_shutdown_function(static fn () => rmtree($dir));
    return $dir;
}

function rmtree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $p = $dir . DIRECTORY_SEPARATOR . $f;
        is_dir($p) && !is_link($p) ? rmtree($p) : @unlink($p);
    }
    @rmdir($dir);
}
