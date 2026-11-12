<?php

declare(strict_types=1);

namespace Teia\View;

defined('TEIA_ROOT') || exit;

use Teia\Security\UrlValidator;

/** Helpers de saída usados pelos templates do site. Toda saída é escapada. */
final class Render
{
    public function __construct(private readonly string $assetsDir)
    {
    }

    /** <picture> com WebP + fallback JPG/PNG de public/assets/images/<nome>. */
    public function picture(string $name, string $alt, array $attrs = []): string
    {
        if (!preg_match('/^[a-z0-9-]{1,60}$/', $name)) {
            return '';
        }
        $dir = $this->assetsDir . '/images/';
        $fallback = null;
        foreach (['jpg', 'png'] as $ext) {
            if (is_file($dir . $name . '.' . $ext)) {
                $fallback = $name . '.' . $ext;
                break;
            }
        }
        if ($fallback === null) {
            return '';
        }
        $imgAttrs = ['src' => url('/assets/images/' . $fallback), 'alt' => $alt] + $attrs;
        $dims = @getimagesize($dir . $fallback);
        if ($dims && !isset($attrs['width'])) {
            $imgAttrs['width'] = (string) $dims[0];
            $imgAttrs['height'] = (string) $dims[1];
        }
        $html = '<picture>';
        if (is_file($dir . $name . '.webp')) {
            $html .= '<source srcset="' . e(url('/assets/images/' . $name . '.webp')) . '" type="image/webp">';
        }
        return $html . '<img' . $this->attrs($imgAttrs) . '></picture>';
    }

    /** URL de um PDF: arquivo existente em public/assets/docs ou URL https. Null = sem botão. */
    public function fileUrl(string $file): ?string
    {
        $file = trim($file);
        if ($file === '') {
            return null;
        }
        if (preg_match('/^[a-z0-9][a-z0-9._-]{0,100}\.pdf$/i', $file) && !str_contains($file, '..')) {
            return is_file($this->assetsDir . '/docs/' . $file) ? url('/assets/docs/' . $file) : null;
        }
        return str_starts_with(strtolower($file), 'https://') && UrlValidator::isAllowed($file) ? $file : null;
    }

    /**
     * Atributos de um link. URL vazia (TODO-LINK) ou inválida → sem href
     * (o item aparece, mas não navega), marcado com data-link-pendente.
     */
    public function href(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !UrlValidator::isAllowed($url)) {
            return ' data-link-pendente="true"';
        }
        $out = ' href="' . e($url) . '"';
        if (UrlValidator::isExternal($url)) {
            $out .= ' rel="noopener noreferrer"';
        }
        return $out;
    }

    /** Texto em parágrafos: linha em branco = novo parágrafo; quebra simples = <br>. Sempre escapado. */
    public function paragraphs(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }
        $out = [];
        foreach (preg_split("/\n{2,}/", $text) ?: [] as $block) {
            $out[] = '<p>' . nl2br(e(trim($block)), false) . '</p>';
        }
        return implode("\n", $out);
    }

    private function attrs(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $name => $value) {
            if ($value === null || $value === false || !preg_match('/^[a-z][a-z0-9-]*$/', (string) $name)) {
                continue;
            }
            $out .= $value === true ? ' ' . $name : ' ' . $name . '="' . e((string) $value) . '"';
        }
        return $out;
    }
}
