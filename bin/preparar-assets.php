<?php

declare(strict_types=1);

/*
 * Ferramenta de DESENVOLVIMENTO (não roda em produção).
 * Gera os assets otimizados em public/assets a partir dos originais exportados do Figma
 * (docs/figma/assets-brutos/, pasta local fora do Git):
 *   - fotos → WebP + fallback JPG, no tamanho 2x do bloco do layout;
 *   - SVGs → sanitizados (sem script, foreignObject, eventos, referências externas ou estilos inline).
 *
 * Uso: bin/php.sh bin/preparar-assets.php
 */

$root = dirname(__DIR__);
$src = $root . '/docs/figma/assets-brutos';
$img = $root . '/public/assets/images';
$icons = $root . '/public/assets/icons';
@mkdir($img, 0755, true);
@mkdir($icons, 0755, true);

if (!is_dir($src)) {
    fwrite(STDERR, "Pasta $src não encontrada. Exporte os assets do Figma primeiro (docs/INVENTARIO_FIGMA.md).\n");
    exit(1);
}

/**
 * [arquivo de origem, nome de saída, largura final, altura final, recorte opcional em frações [x, y, w, h]]
 * O recorte da foto do coworking reproduz o enquadramento do Figma (imagem a 116,5% × 107,75%,
 * deslocada −10,41% / −3,59% dentro do bloco de 543 × 392).
 */
$photos = [
    ['foto-coworking.jpg', 'foto-coworking', 1086, 784, [0.1041 / 1.165, 0.0359 / 1.0775, 1 / 1.165, 1 / 1.0775]],
    ['foto-podcast.jpg', 'foto-podcast', 1086, 784, null],
    ['foto-fotografia.jpg', 'foto-fotografia', 1086, 784, null],
    ['foto-musica.png', 'foto-musica', 1086, 784, null],
    ['banner-fundo.jpg', 'banner-fundo', 2880, 1030, null],
    ['mapa-fundo.jpg', 'mapa-fundo', 2880, 1030, null],
];

foreach ($photos as [$file, $out, $w, $h, $crop]) {
    $path = "$src/$file";
    $info = getimagesize($path);
    $im = $info[2] === IMAGETYPE_PNG ? imagecreatefrompng($path) : imagecreatefromjpeg($path);
    $sw = imagesx($im);
    $sh = imagesy($im);
    if ($crop !== null) {
        [$cx, $cy, $cw, $ch] = [$crop[0] * $sw, $crop[1] * $sh, $crop[2] * $sw, $crop[3] * $sh];
    } else {
        // object-fit: cover centralizado.
        $scale = max($w / $sw, $h / $sh);
        $cw = $w / $scale;
        $ch = $h / $scale;
        $cx = ($sw - $cw) / 2;
        $cy = ($sh - $ch) / 2;
    }
    // Não amplia além do original (foto-musica tem 1024 × 576).
    $factor = min(1, $cw / $w, $ch / $h);
    $tw = (int) round($w * $factor);
    $th = (int) round($h * $factor);
    $dst = imagecreatetruecolor($tw, $th);
    imagecopyresampled($dst, $im, 0, 0, (int) round($cx), (int) round($cy), $tw, $th, (int) round($cw), (int) round($ch));
    imageinterlace($dst, true);
    imagejpeg($dst, "$img/$out.jpg", 82);
    imagewebp($dst, "$img/$out.webp", 80);
    printf("%-18s %4dx%-4d  jpg %4d KB  webp %4d KB\n", $out, $tw, $th, filesize("$img/$out.jpg") / 1024, filesize("$img/$out.webp") / 1024);
}

$svgs = [
    'logo-adesampa-header' => 'logo-adesampa',
    'logo-adesampa-banner' => 'logo-adesampa-branco',
    'logo-adesampa-rodape-topo' => 'logo-adesampa-rodape',
    'logo-adesampa-rodape' => 'logo-adesampa-rodape-assinatura',
    'logo-prefeitura-banner' => 'logo-prefeitura-branco',
    'logo-prefeitura-rodape' => 'logo-prefeitura-rodape',
    'logo-teia-submenu' => 'logo-teia',
    'logo-teia-branco' => 'logo-teia-branco',
    'icone-seta-menu' => 'seta-menu',
    'caret-circle-fechado' => 'caret-circle-down',
    'icone-file-pdf' => 'file-pdf',
    'rede-instagram' => 'rede-instagram',
    'rede-linkedin' => 'rede-linkedin',
    'rede-youtube' => 'rede-youtube',
    'rede-facebook' => 'rede-facebook',
];

foreach ($svgs as $from => $to) {
    $clean = sanitize_svg((string) file_get_contents("$src/$from.svg"));
    file_put_contents("$icons/$to.svg", $clean);
    printf("%-34s %6d bytes\n", "$to.svg", strlen($clean));
}

function sanitize_svg(string $svg): string
{
    $dom = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    // LIBXML_NONET: nunca busca recursos externos. Sem LIBXML_NOENT: entidades não são expandidas.
    if (!$dom->loadXML($svg, LIBXML_NONET)) {
        throw new RuntimeException('SVG inválido');
    }
    libxml_use_internal_errors($prev);
    if ($dom->doctype !== null) {
        $dom->removeChild($dom->doctype);
    }
    $xpath = new DOMXPath($dom);
    foreach (iterator_to_array($xpath->query('//*[local-name()="script" or local-name()="foreignObject" or local-name()="iframe" or local-name()="embed" or local-name()="object"]')) as $node) {
        $node->parentNode->removeChild($node);
    }
    foreach (iterator_to_array($xpath->query('//@*')) as $attr) {
        $name = strtolower($attr->nodeName);
        $value = trim($attr->nodeValue);
        $external = in_array($name, ['href', 'xlink:href', 'src'], true) && !str_starts_with($value, '#');
        if (str_starts_with($name, 'on') || $name === 'style' || $name === 'id' || $external || stripos($value, 'javascript:') !== false) {
            $attr->ownerElement->removeAttributeNode($attr);
        }
    }
    $root = $dom->documentElement;
    $root->removeAttribute('overflow');
    return $dom->saveXML($root) . "\n";
}
