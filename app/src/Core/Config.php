<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

/** Configuração: padrões de app/config/app.php sobrescritos por app/config/app.local.php (fora do Git). */
final class Config
{
    public function __construct(private readonly array $values)
    {
    }

    public static function load(string $dir): self
    {
        $values = require $dir . '/app.php';
        $local = $dir . '/app.local.php';
        if (is_file($local)) {
            $override = require $local;
            if (is_array($override)) {
                $values = array_replace($values, $override);
            }
        }
        return new self($values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
}
