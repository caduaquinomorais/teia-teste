<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

/** Configuração: padrões de app/config/app.php sobrescritos por <dados>/config.local.php. */
final class Config
{
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function load(Paths $paths): self
    {
        $values = require $paths->app . '/config/app.php';
        $local = $paths->localConfig();
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

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }
}
