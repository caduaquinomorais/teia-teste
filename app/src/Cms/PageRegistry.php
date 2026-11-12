<?php

declare(strict_types=1);

namespace Teia\Cms;

defined('TEIA_ROOT') || exit;

/**
 * Carrega as definições de páginas de app/content/*.php.
 * As definições ficam no código; o conteúdo editado fica no SQLite.
 */
final class PageRegistry
{
    /** @var array<string,array>|null */
    private ?array $pages = null;

    public function __construct(private readonly string $dir)
    {
    }

    /** @return array<string,array> slug => definição, na ordem de 'order' */
    public function all(): array
    {
        if ($this->pages !== null) {
            return $this->pages;
        }
        $pages = [];
        foreach (glob($this->dir . '/*.php') ?: [] as $file) {
            $def = require $file;
            if (!is_array($def) || !isset($def['slug'], $def['title'], $def['sections'])) {
                throw new \RuntimeException('Definição de página inválida: ' . basename($file));
            }
            if (!preg_match('/^[a-z0-9-]{1,40}$/', $def['slug'])) {
                throw new \RuntimeException('Slug inválido: ' . basename($file));
            }
            Schema::assertDefinition($def);
            $pages[$def['slug']] = $def;
        }
        uasort($pages, static fn ($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
        return $this->pages = $pages;
    }

    public function get(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    public function has(string $slug): bool
    {
        return isset($this->all()[$slug]);
    }

    /** @return string[] */
    public function slugs(): array
    {
        return array_keys($this->all());
    }
}
