<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

use Teia\Database\Database;

/**
 * Limitador de tentativas por janela fixa, persistido no SQLite.
 * A chave do balde é um HMAC (ex.: "login:ip:203.0.113.5") — IP e usuário nunca ficam em texto puro.
 */
final class RateLimiter
{
    public function __construct(
        private readonly Database $db,
        private readonly Keys $keys,
    ) {
    }

    public function bucket(string $key): string
    {
        return $this->keys->hmac('ratelimit', $key);
    }

    /** Já excedeu o limite? (não registra tentativa) */
    public function tooMany(string $key, int $max, int $window, ?int $now = null): bool
    {
        $now ??= time();
        $row = $this->db->fetch('SELECT count, window_start FROM rate_limits WHERE bucket = ?', [$this->bucket($key)]);
        if ($row === null || $now - (int) $row['window_start'] >= $window) {
            return false;
        }
        return (int) $row['count'] >= $max;
    }

    /** Registra uma tentativa (falha). Retorna o total na janela atual. */
    public function hit(string $key, int $window, ?int $now = null): int
    {
        $now ??= time();
        $bucket = $this->bucket($key);
        return (int) $this->db->transaction(function (Database $db) use ($bucket, $window, $now): int {
            $row = $db->fetch('SELECT count, window_start FROM rate_limits WHERE bucket = ?', [$bucket]);
            if ($row === null || $now - (int) $row['window_start'] >= $window) {
                $db->execute(
                    'INSERT INTO rate_limits (bucket, count, window_start) VALUES (?, 1, ?)
                     ON CONFLICT (bucket) DO UPDATE SET count = 1, window_start = excluded.window_start',
                    [$bucket, $now]
                );
                return 1;
            }
            $db->execute('UPDATE rate_limits SET count = count + 1 WHERE bucket = ?', [$bucket]);
            return (int) $row['count'] + 1;
        });
    }

    public function clear(string $key): void
    {
        $this->db->execute('DELETE FROM rate_limits WHERE bucket = ?', [$this->bucket($key)]);
    }

    /** Remove baldes antigos (manutenção). */
    public function prune(int $olderThanSeconds = 86400, ?int $now = null): void
    {
        $now ??= time();
        $this->db->execute('DELETE FROM rate_limits WHERE window_start < ?', [$now - $olderThanSeconds]);
    }
}
