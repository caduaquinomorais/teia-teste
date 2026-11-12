<?php

declare(strict_types=1);

namespace Teia\Database;

defined('TEIA_ROOT') || exit;

/**
 * Migrações numeradas em app/migrations (NNN_descricao.sql), controladas por PRAGMA user_version.
 * Antes de aplicar migrações em um banco que já tem estrutura, chama $beforeMigrate
 * (usado para criar um backup de segurança).
 */
final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly string $dir,
    ) {
    }

    /** @return array<int,string> número => caminho do arquivo */
    public function available(): array
    {
        $files = [];
        foreach (glob($this->dir . '/*.sql') ?: [] as $file) {
            if (preg_match('/^(\d{3})_[a-z0-9_]+\.sql$/', basename($file), $m)) {
                $files[(int) $m[1]] = $file;
            }
        }
        ksort($files);
        return $files;
    }

    public function latestVersion(): int
    {
        $files = $this->available();
        return $files === [] ? 0 : (int) array_key_last($files);
    }

    public function pending(): array
    {
        $current = $this->db->userVersion();
        return array_filter($this->available(), static fn (int $n) => $n > $current, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Aplica as migrações pendentes. Retorna a lista de versões aplicadas.
     * @param callable(int $from, int $to):void|null $beforeMigrate
     */
    public function migrate(?callable $beforeMigrate = null): array
    {
        $pending = $this->pending();
        if ($pending === []) {
            return [];
        }
        $current = $this->db->userVersion();
        if ($current > $this->latestVersion()) {
            throw new \RuntimeException('O banco é de uma versão mais nova do que este código.');
        }
        if ($current > 0 && $beforeMigrate !== null) {
            $beforeMigrate($current, $this->latestVersion());
        }
        $applied = [];
        foreach ($pending as $version => $file) {
            $sql = (string) file_get_contents($file);
            $this->db->transaction(function (Database $db) use ($sql, $version): void {
                $db->pdo()->exec($sql);
                $db->setUserVersion($version);
            });
            $applied[] = $version;
        }
        return $applied;
    }
}
