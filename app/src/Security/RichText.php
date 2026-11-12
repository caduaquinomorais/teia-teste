<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

/**
 * Formatador seguro de texto rico. NUNCA aceita HTML: todo o texto é escapado
 * e só depois as marcações abaixo são convertidas.
 *
 *   **negrito**            → <strong>
 *   *itálico*              → <em>
 *   [texto](https://…)     → <a href>  (somente URLs aceitas pelo UrlValidator)
 *   quebra de linha        → <br>
 *   linha em branco        → novo parágrafo
 */
final class RichText
{
    public static function toHtml(string $text, bool $paragraphs = true): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($text === '') {
            return '';
        }
        $blocks = $paragraphs ? preg_split("/\n{2,}/", $text) : [$text];
        $out = [];
        foreach ($blocks as $block) {
            $html = self::inline(trim((string) $block));
            $out[] = $paragraphs ? '<p>' . $html . '</p>' : $html;
        }
        return implode("\n", $out);
    }

    public static function inline(string $text): string
    {
        // 0) Remove caracteres de controle (inclusive os usados como marcadores internos).
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';

        // 1) Extrai links antes de escapar, substituindo por marcadores.
        $links = [];
        $text = preg_replace_callback(
            '/\[([^\[\]\n]{1,200})\]\(([^()\s]{1,2048})\)/u',
            static function (array $m) use (&$links): string {
                $url = $m[2];
                if (!UrlValidator::isAllowed($url)) {
                    return $m[0]; // Mantém como texto; será escapado.
                }
                $key = "\x01" . count($links) . "\x02";
                $links[$key] = [$m[1], $url];
                return $key;
            },
            $text
        ) ?? $text;

        // 2) Escapa tudo.
        $html = e($text);

        // 3) Negrito e itálico sobre o texto já escapado.
        $html = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/us', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/(?<![*\w])\*(?=\S)([^*\n]+?)(?<=\S)\*(?![*\w])/u', '<em>$1</em>', $html) ?? $html;

        // 4) Reinsere links (texto do link também escapado e formatado).
        foreach ($links as $key => [$label, $url]) {
            $labelHtml = e($label);
            $labelHtml = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/us', '<strong>$1</strong>', $labelHtml) ?? $labelHtml;
            $attrs = 'href="' . e($url) . '"';
            if (UrlValidator::isExternal($url)) {
                $attrs .= ' rel="noopener noreferrer" target="_blank"';
            }
            $html = str_replace(e($key), '<a ' . $attrs . '>' . $labelHtml . '</a>', $html);
        }

        // 5) Quebras de linha simples.
        return str_replace("\n", "<br>\n", $html);
    }

    /** Texto puro (sem marcações), útil para meta description e alt. */
    public static function toPlain(string $text): string
    {
        $text = preg_replace('/\[([^\[\]\n]+)\]\([^()\s]+\)/u', '$1', $text) ?? $text;
        $text = str_replace(['**', '*'], '', $text);
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
