<?php

declare(strict_types=1);

namespace Teia\Cms;

defined('TEIA_ROOT') || exit;

use Teia\Security\RichText;
use Teia\Security\UrlValidator;

/** Helpers de saída usados pelos templates do site. Toda saída é escapada. */
final class Render
{
    public function __construct(private readonly ?MediaService $media)
    {
    }

    /**
     * <picture> com WebP + fallback JPG/PNG.
     * Referências 'asset:' funcionam mesmo sem banco (antes da instalação).
     */
    public function picture(string $ref, string $alt, array $attrs = []): string
    {
        $sources = $this->sources($ref);
        if ($sources === null) {
            return '';
        }
        $imgAttrs = ['src' => url($sources['fallback']), 'alt' => $alt] + $attrs;
        if ($sources['width'] && $sources['height'] && !isset($attrs['width'])) {
            $imgAttrs['width'] = (string) $sources['width'];
            $imgAttrs['height'] = (string) $sources['height'];
        }
        $html = '<picture>';
        if ($sources['webp']) {
            $html .= '<source srcset="' . e(url($sources['webp'])) . '" type="image/webp">';
        }
        $html .= '<img' . $this->attrs($imgAttrs) . '></picture>';
        return $html;
    }

    /** URLs para uso em CSS (image-set) — retorna [webp|null, fallback]. */
    public function backgroundUrls(string $ref): ?array
    {
        $sources = $this->sources($ref);
        return $sources === null ? null : [$sources['webp'] ? url($sources['webp']) : null, url($sources['fallback'])];
    }

    public function fileUrl(string $ref): ?string
    {
        if ($ref === '' || $this->media === null) {
            return null;
        }
        $path = $this->media->fileUrl($ref);
        return $path === null ? null : url($path);
    }

    /**
     * Atributos de um link editável. URL vazia (TODO-LINK) ou inválida → sem href
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

    public function rich(string $text): string
    {
        return RichText::toHtml($text);
    }

    public function richInline(string $text): string
    {
        return RichText::inline($text);
    }

    public function plain(string $text): string
    {
        return RichText::toPlain($text);
    }

    private function sources(string $ref): ?array
    {
        if (preg_match('/^asset:([a-z0-9-]{1,60})$/', $ref, $m)) {
            $dir = TEIA_ROOT . '/public/assets/images/';
            foreach (['jpg', 'png'] as $ext) {
                if (is_file($dir . $m[1] . '.' . $ext)) {
                    $dims = @getimagesize($dir . $m[1] . '.' . $ext);
                    return [
                        'webp' => is_file($dir . $m[1] . '.webp') ? '/assets/images/' . $m[1] . '.webp' : null,
                        'fallback' => '/assets/images/' . $m[1] . '.' . $ext,
                        'width' => $dims[0] ?? null,
                        'height' => $dims[1] ?? null,
                    ];
                }
            }
            return null;
        }
        if ($this->media === null) {
            return null;
        }
        return $this->media->imageSources($ref);
    }

    private function attrs(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if (!preg_match('/^[a-z][a-z0-9-]*$/', (string) $name)) {
                continue;
            }
            $out .= $value === true ? ' ' . $name : ' ' . $name . '="' . e((string) $value) . '"';
        }
        return $out;
    }
}
