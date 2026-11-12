<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

/**
 * Resolve os caminhos do projeto e do diretório de dados persistentes.
 *
 * O diretório de dados fica FORA do repositório e da área pública. Ordem de resolução:
 *  1. variável de ambiente TEIA_DATA_DIR;
 *  2. arquivo app/config/data-path.local.php (fora do Git) que retorna o caminho absoluto;
 *  3. padrão: pasta irmã do projeto, "../teia-ade-sampa-data".
 */
final class Paths
{
    public readonly string $root;
    public readonly string $app;
    public readonly string $public;
    public readonly string $data;

    public function __construct(string $root, ?string $dataDir = null)
    {
        $this->root = rtrim($root, '/\\');
        $this->app = $this->root . DIRECTORY_SEPARATOR . 'app';
        $this->public = $this->root . DIRECTORY_SEPARATOR . 'public';
        $this->data = rtrim($dataDir ?? self::resolveDataDir($this->root), '/\\');
    }

    public static function resolveDataDir(string $root, ?string $documentRoot = null): string
    {
        $env = getenv('TEIA_DATA_DIR');
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }
        $local = $root . '/app/config/data-path.local.php';
        if (is_file($local)) {
            $value = require $local;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }
        $default = dirname($root) . DIRECTORY_SEPARATOR . 'teia-ade-sampa-data';
        // Projeto publicado dentro do document root (ex.: KingHost /home/site/www/teia):
        // a pasta irmã também seria pública, então sobe para o pai do document root
        // (/home/site/teia-ade-sampa-data). Regra determinística: o deploy não altera o resultado.
        $documentRoot ??= (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($documentRoot !== '' && is_dir($documentRoot) && self::isInside($default, $documentRoot)) {
            $default = dirname(rtrim($documentRoot, '/\\')) . DIRECTORY_SEPARATOR . 'teia-ade-sampa-data';
        }
        return $default;
    }

    public function data(string $relative = ''): string
    {
        return $relative === '' ? $this->data : $this->data . DIRECTORY_SEPARATOR . $relative;
    }

    public function database(): string
    {
        return $this->data('database' . DIRECTORY_SEPARATOR . 'teia.sqlite');
    }

    public function uploads(): string
    {
        return $this->data('uploads');
    }

    public function backups(): string
    {
        return $this->data('backups');
    }

    public function logs(): string
    {
        return $this->data('logs');
    }

    public function sessions(): string
    {
        return $this->data('sessions');
    }

    public function tmp(): string
    {
        return $this->data('tmp');
    }

    public function keyFile(): string
    {
        return $this->data('app.key');
    }

    public function localConfig(): string
    {
        return $this->data('config.local.php');
    }

    public function installToken(): string
    {
        return $this->data('install-token.txt');
    }

    public function recoveryToken(): string
    {
        return $this->data('recovery-token.txt');
    }

    /** Subpastas obrigatórias do diretório de dados. */
    public function requiredDataDirs(): array
    {
        return [
            $this->data('database'),
            $this->uploads(),
            $this->backups(),
            $this->logs(),
            $this->sessions(),
            $this->tmp(),
        ];
    }

    /**
     * Verifica se $path está dentro de $container (ambos resolvidos).
     * Usado para recusar o diretório de dados dentro do document root.
     */
    public static function isInside(string $path, string $container): bool
    {
        $p = self::normalize($path);
        $c = self::normalize($container);
        if ($p === '' || $c === '') {
            return false;
        }
        return $p === $c || str_starts_with($p, rtrim($c, '/') . '/');
    }

    public static function normalize(string $path): string
    {
        $real = realpath($path);
        if ($real === false) {
            // Caminho ainda não existe: normaliza o pai existente mais próximo.
            $parent = dirname($path);
            if ($parent === $path) {
                return '';
            }
            $base = self::normalize($parent);
            if ($base === '') {
                return '';
            }
            $real = $base . '/' . basename($path);
        }
        $real = str_replace('\\', '/', $real);
        if (DIRECTORY_SEPARATOR === '\\') {
            $real = strtolower($real);
        }
        return rtrim($real, '/');
    }
}
