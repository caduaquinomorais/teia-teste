<?php

declare(strict_types=1);

namespace Teia\Auth;

defined('TEIA_ROOT') || exit;

use Teia\Database\Database;
use Teia\Security\PasswordPolicy;

/** Acesso à tabela users e às permissões por página. */
final class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch('SELECT * FROM users WHERE username = ?', [trim($username)]);
    }

    public function all(): array
    {
        return $this->db->fetchAll('SELECT * FROM users ORDER BY active DESC, role, name COLLATE NOCASE');
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1");
    }

    public static function validUsername(string $username): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9._-]{2,39}$/', $username);
    }

    public function create(string $username, string $name, ?string $email, string $password, string $role, bool $mustChange): int
    {
        $now = gmdate('c');
        $this->db->execute(
            'INSERT INTO users (username, name, email, password_hash, role, active, must_change_password, session_version, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, 1, ?, ?)',
            [$username, $name, $email ?: null, PasswordPolicy::hash($password), $role, $mustChange ? 1 : 0, $now, $now]
        );
        return $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $name, ?string $email, string $role): void
    {
        $this->db->execute(
            'UPDATE users SET name = ?, email = ?, role = ?, updated_at = ? WHERE id = ?',
            [$name, $email ?: null, $role, gmdate('c'), $id]
        );
    }

    public function setPassword(int $id, string $password, bool $mustChange): void
    {
        $this->db->execute(
            'UPDATE users SET password_hash = ?, must_change_password = ?, session_version = session_version + 1, updated_at = ? WHERE id = ?',
            [PasswordPolicy::hash($password), $mustChange ? 1 : 0, gmdate('c'), $id]
        );
    }

    public function rehash(int $id, string $password): void
    {
        $this->db->execute('UPDATE users SET password_hash = ? WHERE id = ?', [PasswordPolicy::hash($password), $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->db->execute(
            'UPDATE users SET active = ?, session_version = session_version + 1, updated_at = ? WHERE id = ?',
            [$active ? 1 : 0, gmdate('c'), $id]
        );
    }

    /** Invalida todas as sessões abertas do usuário. */
    public function bumpSessionVersion(int $id): void
    {
        $this->db->execute('UPDATE users SET session_version = session_version + 1 WHERE id = ?', [$id]);
    }

    public function touchLogin(int $id): void
    {
        $this->db->execute('UPDATE users SET last_login_at = ? WHERE id = ?', [gmdate('c'), $id]);
    }

    // ---------- Permissões por página ----------

    /** @return string[] slugs permitidos */
    public function pagePermissions(int $userId): array
    {
        $rows = $this->db->fetchAll('SELECT page_slug FROM page_permissions WHERE user_id = ? ORDER BY page_slug', [$userId]);
        return array_column($rows, 'page_slug');
    }

    /**
     * Substitui as permissões do usuário. Retorna [concedidas, revogadas].
     * @param string[] $slugs
     */
    public function setPagePermissions(int $userId, array $slugs, int $grantedBy): array
    {
        $current = $this->pagePermissions($userId);
        $slugs = array_values(array_unique($slugs));
        $grant = array_values(array_diff($slugs, $current));
        $revoke = array_values(array_diff($current, $slugs));
        foreach ($grant as $slug) {
            $this->db->execute(
                'INSERT INTO page_permissions (user_id, page_slug, granted_by, granted_at) VALUES (?, ?, ?, ?)',
                [$userId, $slug, $grantedBy, gmdate('c')]
            );
        }
        foreach ($revoke as $slug) {
            $this->db->execute('DELETE FROM page_permissions WHERE user_id = ? AND page_slug = ?', [$userId, $slug]);
        }
        if ($revoke !== []) {
            // Revogação encerra as sessões abertas do usuário.
            $this->bumpSessionVersion($userId);
        }
        return [$grant, $revoke];
    }
}
