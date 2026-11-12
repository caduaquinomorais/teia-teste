<?php

declare(strict_types=1);

namespace Teia\Auth;

defined('TEIA_ROOT') || exit;

use Teia\Database\Database;
use Teia\Security\Keys;

/** Registro de auditoria. O IP é guardado apenas como HMAC (permite correlacionar, não identificar). */
final class Audit
{
    public function __construct(
        private readonly Database $db,
        private readonly ?Keys $keys,
    ) {
    }

    public function log(?array $user, string $action, ?string $target = null, array $details = [], ?string $ip = null): void
    {
        $this->db->execute(
            'INSERT INTO audit_log (user_id, username, action, target, details, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $user['id'] ?? null,
                $user['username'] ?? null,
                $action,
                $target,
                $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ($ip !== null && $this->keys !== null) ? substr($this->keys->hmac('audit-ip', $ip), 0, 16) : null,
                gmdate('c'),
            ]
        );
    }

    public function recent(int $limit = 200, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM audit_log ORDER BY id DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }
}
