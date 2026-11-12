<?php

declare(strict_types=1);

namespace Teia\Cms;

defined('TEIA_ROOT') || exit;

use Teia\Security\UrlValidator;

/**
 * Tipos de campo do CMS e regras de validação (sempre no servidor).
 *
 * Tipos: text, textarea, image, link, email, phone, list, group,
 *        e as extensões boolean (liga/desliga) e file (PDF).
 *
 * Estrutura dos dados: seções → campos → valores. Listas são arrays de itens.
 * Somente chaves declaradas na definição são aceitas (lista branca).
 */
final class Schema
{
    public const TYPES = ['text', 'textarea', 'image', 'link', 'email', 'phone', 'list', 'group', 'boolean', 'file'];

    // ------------------------------------------------------------------
    // Validação da própria definição (erro de programação = exceção).
    // ------------------------------------------------------------------

    public static function assertDefinition(array $page): void
    {
        foreach ($page['sections'] as $key => $section) {
            self::assertKey($key);
            if (!isset($section['label'], $section['fields']) || !is_array($section['fields'])) {
                throw new \RuntimeException("Seção '$key' sem label/fields.");
            }
            self::assertFields($section['fields'], $key);
        }
    }

    private static function assertFields(array $fields, string $where): void
    {
        foreach ($fields as $key => $field) {
            self::assertKey($key);
            $type = $field['type'] ?? null;
            if (!in_array($type, self::TYPES, true)) {
                throw new \RuntimeException("Campo '$where.$key' com tipo inválido.");
            }
            foreach (['label'] as $required) {
                if (!isset($field[$required])) {
                    throw new \RuntimeException("Campo '$where.$key' sem $required.");
                }
            }
            if (in_array($type, ['list', 'group'], true)) {
                if (empty($field['fields']) || !is_array($field['fields'])) {
                    throw new \RuntimeException("Campo '$where.$key' sem subcampos.");
                }
                self::assertFields($field['fields'], "$where.$key");
            }
        }
    }

    private static function assertKey(mixed $key): void
    {
        if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_]{0,39}$/', $key)) {
            throw new \RuntimeException('Chave inválida na definição: ' . var_export($key, true));
        }
    }

    // ------------------------------------------------------------------
    // Padrões e mesclagem (valores do banco sobrescrevem os padrões).
    // ------------------------------------------------------------------

    public static function defaults(array $page): array
    {
        $out = [];
        foreach ($page['sections'] as $sKey => $section) {
            $out[$sKey] = self::fieldsDefaults($section['fields']);
        }
        return $out;
    }

    private static function fieldsDefaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $key => $field) {
            $out[$key] = self::fieldDefault($field);
        }
        return $out;
    }

    private static function fieldDefault(array $field): mixed
    {
        $type = $field['type'];
        if ($type === 'group') {
            $base = self::fieldsDefaults($field['fields']);
            return is_array($field['default'] ?? null) ? self::mergeFields($field['fields'], $base, $field['default']) : $base;
        }
        if ($type === 'list') {
            $items = [];
            foreach (($field['default'] ?? []) as $item) {
                $items[] = self::mergeFields($field['fields'], self::fieldsDefaults($field['fields']), is_array($item) ? $item : []);
            }
            return $items;
        }
        if ($type === 'boolean') {
            return (bool) ($field['default'] ?? false);
        }
        return (string) ($field['default'] ?? '');
    }

    /** Conteúdo final: padrões + valores salvos (somente chaves conhecidas). */
    public static function resolve(array $page, ?array $stored): array
    {
        $defaults = self::defaults($page);
        if ($stored === null) {
            return $defaults;
        }
        $out = [];
        foreach ($page['sections'] as $sKey => $section) {
            $out[$sKey] = self::mergeFields($section['fields'], $defaults[$sKey], is_array($stored[$sKey] ?? null) ? $stored[$sKey] : []);
        }
        return $out;
    }

    private static function mergeFields(array $fields, array $defaults, array $stored): array
    {
        $out = [];
        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $stored)) {
                // Campo novo (sem valor salvo): usa o padrão. Não exige migração.
                $out[$key] = $defaults[$key];
                continue;
            }
            $value = $stored[$key];
            switch ($field['type']) {
                case 'group':
                    $out[$key] = self::mergeFields($field['fields'], $defaults[$key], is_array($value) ? $value : []);
                    break;
                case 'list':
                    $items = [];
                    foreach (is_array($value) ? $value : [] as $item) {
                        if (is_array($item)) {
                            $items[] = self::mergeFields($field['fields'], self::fieldsDefaults($field['fields']), $item);
                        }
                    }
                    $out[$key] = $items;
                    break;
                case 'boolean':
                    $out[$key] = (bool) $value;
                    break;
                default:
                    $out[$key] = is_scalar($value) ? (string) $value : $defaults[$key];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Normalização e validação do formulário enviado.
    // ------------------------------------------------------------------

    /**
     * @param array $input   $_POST['c'] (estrutura seção/campo)
     * @param array $uploads caminho => 'media:ID' para arquivos recém-enviados e já processados
     * @param array $removes caminho => true para imagens/arquivos marcados para remoção
     * @param callable(string):bool $refExists verifica se 'asset:..'/'media:..' existe
     * @return array{0:array,1:array<string,string>} [dados normalizados, erros por caminho]
     */
    public static function normalize(array $page, array $input, array $uploads, array $removes, callable $refExists): array
    {
        $errors = [];
        $data = [];
        foreach ($page['sections'] as $sKey => $section) {
            $sectionInput = is_array($input[$sKey] ?? null) ? $input[$sKey] : [];
            $data[$sKey] = self::normalizeFields($section['fields'], $sectionInput, $sKey, $uploads, $removes, $refExists, $errors);
        }
        return [$data, $errors];
    }

    private static function normalizeFields(array $fields, array $input, string $path, array $uploads, array $removes, callable $refExists, array &$errors): array
    {
        $out = [];
        foreach ($fields as $key => $field) {
            $fieldPath = $path . '.' . $key;
            $out[$key] = self::normalizeField($field, $input[$key] ?? null, $fieldPath, $uploads, $removes, $refExists, $errors);
        }
        return $out;
    }

    private static function normalizeField(array $field, mixed $raw, string $path, array $uploads, array $removes, callable $refExists, array &$errors): mixed
    {
        $type = $field['type'];
        $label = $field['label'];
        $required = (bool) ($field['required'] ?? false);

        if ($type === 'group') {
            return self::normalizeFields($field['fields'], is_array($raw) ? $raw : [], $path, $uploads, $removes, $refExists, $errors);
        }

        if ($type === 'list') {
            $items = [];
            $rawItems = is_array($raw) ? $raw : [];
            foreach ($rawItems as $itemKey => $itemRaw) {
                if (!is_array($itemRaw) || !preg_match('/^[a-z0-9_]{1,24}$/', (string) $itemKey)) {
                    continue;
                }
                $itemPath = $path . '.' . $itemKey;
                $item = self::normalizeFields($field['fields'], $itemRaw, $itemPath, $uploads, $removes, $refExists, $errors);
                $items[] = $item;
            }
            $min = (int) ($field['min_items'] ?? ($required ? 1 : 0));
            $max = (int) ($field['max_items'] ?? 50);
            if (count($items) < $min) {
                $errors[$path] = "“{$label}”: adicione pelo menos {$min} item(ns).";
            } elseif (count($items) > $max) {
                $errors[$path] = "“{$label}”: no máximo {$max} itens.";
            }
            return $items;
        }

        if ($type === 'boolean') {
            return $raw === '1' || $raw === 1 || $raw === true || $raw === 'on';
        }

        if ($type === 'image' || $type === 'file') {
            if (isset($uploads[$path])) {
                return $uploads[$path];
            }
            if (!empty($removes[$path])) {
                $value = '';
            } else {
                $value = is_string($raw) ? trim($raw) : '';
            }
            if ($value !== '') {
                $pattern = $type === 'image'
                    ? '/^(asset:[a-z0-9-]{1,60}|media:[a-f0-9]{32})$/'
                    : '/^(asset:[a-z0-9-]{1,60}\.pdf|media:[a-f0-9]{32})$/';
                if (!preg_match($pattern, $value) || !$refExists($value)) {
                    $errors[$path] = "“{$label}”: arquivo inválido.";
                    return '';
                }
            }
            if ($value === '' && $required) {
                $errors[$path] = "“{$label}” é obrigatório.";
            }
            return $value;
        }

        // Tipos de texto.
        $value = is_string($raw) ? $raw : '';
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        // Remove caracteres de controle (exceto quebra de linha em textarea).
        $value = preg_replace($type === 'textarea' ? '/[\x00-\x09\x0B-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', '', $value) ?? '';
        if (!mb_check_encoding($value, 'UTF-8')) {
            $errors[$path] = "“{$label}”: texto inválido.";
            return '';
        }
        $value = trim($value);
        if ($type !== 'textarea') {
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        }

        if ($value === '') {
            if ($required) {
                $errors[$path] = "“{$label}” é obrigatório.";
            }
            return '';
        }

        $len = mb_strlen($value, 'UTF-8');
        $max = (int) ($field['max'] ?? ($type === 'textarea' ? 2000 : 200));
        $min = (int) ($field['min'] ?? 0);
        if ($len > $max) {
            $errors[$path] = "“{$label}”: máximo de {$max} caracteres (atual: {$len}).";
            return $value;
        }
        if ($len < $min) {
            $errors[$path] = "“{$label}”: mínimo de {$min} caracteres.";
            return $value;
        }

        switch ($type) {
            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[$path] = "“{$label}”: e-mail inválido.";
                }
                return mb_strtolower($value, 'UTF-8');
            case 'phone':
                if (!preg_match('/^\+?[0-9()\s.\-]{8,20}$/', $value)) {
                    $errors[$path] = "“{$label}”: telefone inválido. Use apenas números, espaços, parênteses e hífen.";
                }
                return $value;
            case 'link':
                $embed = $field['embed'] ?? null;
                if ($embed === 'google_maps') {
                    if (!UrlValidator::isGoogleMapsEmbed($value)) {
                        $errors[$path] = "“{$label}”: use um link de incorporação do Google Maps (https://www.google.com/maps/...).";
                    }
                } elseif (!UrlValidator::isAllowed($value)) {
                    $errors[$path] = "“{$label}”: link inválido. Use http://, https://, mailto:, tel: ou uma âncora (#secao).";
                }
                return $value;
            case 'text':
                if (isset($field['pattern']) && !preg_match($field['pattern'], $value)) {
                    $errors[$path] = "“{$label}”: " . ($field['pattern_help'] ?? 'formato inválido.');
                }
                return $value;
            default:
                return $value;
        }
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Encontra a definição do campo por caminho "secao.campo.itemKey.subcampo". */
    public static function fieldAt(array $page, string $path): ?array
    {
        $parts = explode('.', $path);
        $section = $page['sections'][array_shift($parts)] ?? null;
        if ($section === null) {
            return null;
        }
        $fields = $section['fields'];
        $field = null;
        while ($parts !== []) {
            $key = array_shift($parts);
            $field = $fields[$key] ?? null;
            if ($field === null) {
                return null;
            }
            if ($field['type'] === 'list') {
                if ($parts === []) {
                    return $field;
                }
                $itemKey = array_shift($parts);
                if (!preg_match('/^[a-z0-9_]{1,24}$/', $itemKey)) {
                    return null;
                }
                $fields = $field['fields'];
            } elseif ($field['type'] === 'group') {
                $fields = $field['fields'];
            } elseif ($parts !== []) {
                return null;
            }
        }
        return $field;
    }

    /** Lista todas as referências de mídia ('media:ID') usadas nos dados. */
    public static function mediaRefs(array $data): array
    {
        $refs = [];
        array_walk_recursive($data, static function ($v) use (&$refs): void {
            if (is_string($v) && preg_match('/^media:[a-f0-9]{32}$/', $v)) {
                $refs[$v] = true;
            }
        });
        return array_keys($refs);
    }
}
