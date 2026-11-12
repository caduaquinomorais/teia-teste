<?php

declare(strict_types=1);

namespace Teia\Cms;

defined('TEIA_ROOT') || exit;

use Teia\Database\Database;

/**
 * Persistência do conteúdo das páginas com histórico de revisões e
 * controle otimista de concorrência (versão esperada).
 */
final class ContentRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array{version:int,data:?array,updated_at:?string,updated_by:?int} */
    public function current(string $slug): array
    {
        $row = $this->db->fetch('SELECT version, data, updated_at, updated_by FROM pages WHERE slug = ?', [$slug]);
        if ($row === null) {
            return ['version' => 0, 'data' => null, 'updated_at' => null, 'updated_by' => null];
        }
        $data = json_decode((string) $row['data'], true);
        return [
            'version' => (int) $row['version'],
            'data' => is_array($data) ? $data : null,
            'updated_at' => $row['updated_at'],
            'updated_by' => $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
        ];
    }

    /**
     * Publica imediatamente uma nova versão.
     * @throws ConflictException se a versão atual for diferente da esperada (edição concorrente)
     */
    public function save(string $slug, array $data, int $expectedVersion, ?int $userId, ?string $note = null): int
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return (int) $this->db->transaction(function (Database $db) use ($slug, $json, $expectedVersion, $userId, $note): int {
            $current = (int) ($db->fetchValue('SELECT version FROM pages WHERE slug = ?', [$slug]) ?? 0);
            if ($current !== $expectedVersion) {
                throw new ConflictException($current);
            }
            $new = $current + 1;
            $now = gmdate('c');
            if ($current === 0) {
                $db->execute('INSERT INTO pages (slug, version, data, updated_at, updated_by) VALUES (?, ?, ?, ?, ?)', [$slug, $new, $json, $now, $userId]);
            } else {
                $db->execute('UPDATE pages SET version = ?, data = ?, updated_at = ?, updated_by = ? WHERE slug = ?', [$new, $json, $now, $userId, $slug]);
            }
            $db->execute(
                'INSERT INTO page_revisions (page_slug, version, data, created_at, created_by, note) VALUES (?, ?, ?, ?, ?, ?)',
                [$slug, $new, $json, $now, $userId, $note]
            );
            return $new;
        });
    }

    public function revisions(string $slug, int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT r.version, r.created_at, r.note, u.name AS author, u.username
               FROM page_revisions r LEFT JOIN users u ON u.id = r.created_by
              WHERE r.page_slug = ? ORDER BY r.version DESC LIMIT ?',
            [$slug, $limit]
        );
    }

    public function revision(string $slug, int $version): ?array
    {
        $row = $this->db->fetch('SELECT version, data, created_at FROM page_revisions WHERE page_slug = ? AND version = ?', [$slug, $version]);
        if ($row === null) {
            return null;
        }
        $data = json_decode((string) $row['data'], true);
        return ['version' => (int) $row['version'], 'data' => is_array($data) ? $data : [], 'created_at' => $row['created_at']];
    }

    /** Restaura uma revisão criando uma NOVA versão (o histórico nunca é apagado). */
    public function restore(string $slug, int $version, int $expectedVersion, ?int $userId): int
    {
        $rev = $this->revision($slug, $version);
        if ($rev === null) {
            throw new \InvalidArgumentException('Revisão inexistente.');
        }
        return $this->save($slug, $rev['data'], $expectedVersion, $userId, 'Restauração da versão ' . $version);
    }
}
