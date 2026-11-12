<?php

declare(strict_types=1);

namespace Teia\Database;

defined('TEIA_ROOT') || exit;

use PDO;

/**
 * Acesso ao SQLite via PDO.
 * - PRAGMA foreign_keys=ON e busy_timeout em toda conexão.
 * - Transações com BEGIN IMMEDIATE (trava de escrita já no início, evita deadlock de upgrade).
 * - Transações aninhadas viram SAVEPOINTs, com controle próprio de profundidade.
 */
final class Database
{
    private PDO $pdo;
    private int $depth = 0;

    public function __construct(private readonly string $path, int $busyTimeoutMs = 5000)
    {
        $this->pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => max(1, intdiv($busyTimeoutMs, 1000)),
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA busy_timeout = ' . (int) $busyTimeoutMs);
        // Journal padrão (DELETE): mais seguro em hospedagem compartilhada do que WAL.
        $this->pdo->exec('PRAGMA journal_mode = DELETE');
        $this->pdo->exec('PRAGMA synchronous = FULL');
    }

    public function path(): string
    {
        return $this->path;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchValue(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? null : $value;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Executa $fn dentro de uma transação. Aninhamento usa SAVEPOINT.
     * @template T
     * @param callable(Database):T $fn
     * @return T
     */
    public function transaction(callable $fn): mixed
    {
        $level = $this->depth;
        if ($level === 0) {
            $this->pdo->exec('BEGIN IMMEDIATE');
        } else {
            $this->pdo->exec('SAVEPOINT sp_' . $level);
        }
        $this->depth++;
        try {
            $result = $fn($this);
            $this->depth--;
            if ($level === 0) {
                $this->pdo->exec('COMMIT');
            } else {
                $this->pdo->exec('RELEASE SAVEPOINT sp_' . $level);
            }
            return $result;
        } catch (\Throwable $ex) {
            $this->depth--;
            try {
                if ($level === 0) {
                    $this->pdo->exec('ROLLBACK');
                } else {
                    $this->pdo->exec('ROLLBACK TO SAVEPOINT sp_' . $level);
                    $this->pdo->exec('RELEASE SAVEPOINT sp_' . $level);
                }
            } catch (\Throwable) {
                // A conexão pode já ter desfeito a transação; o erro original prevalece.
            }
            throw $ex;
        }
    }

    public function transactionDepth(): int
    {
        return $this->depth;
    }

    public function userVersion(): int
    {
        return (int) $this->pdo->query('PRAGMA user_version')->fetchColumn();
    }

    public function setUserVersion(int $version): void
    {
        $this->pdo->exec('PRAGMA user_version = ' . $version);
    }

    public static function sqliteVersion(): string
    {
        $pdo = new PDO('sqlite::memory:');
        return (string) $pdo->query('SELECT sqlite_version()')->fetchColumn();
    }
}
