<?php

declare(strict_types=1);

/*
 * Prepara uma instância TEMPORÁRIA para o teste visual, com conteúdo de TESTE que reproduz
 * o estado do frame do Figma (resposta da 1ª dúvida e PDFs presentes). Esse conteúdo existe
 * apenas no diretório temporário e nunca é publicado.
 *
 * Uso: bin/php.sh tests/visual/preparar.php <diretório de dados temporário>
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use Teia\Auth\UserRepository;
use Teia\Cms\ContentRepository;
use Teia\Cms\MediaService;
use Teia\Cms\PageRegistry;
use Teia\Cms\Schema;
use Teia\Core\Paths;
use Teia\Database\Database;
use Teia\Install\Installer;

$dataDir = $argv[1] ?? '';
if ($dataDir === '') {
    fwrite(STDERR, "Informe o diretório de dados temporário.\n");
    exit(1);
}
@mkdir($dataDir, 0700, true);

$paths = new Paths(TEIA_ROOT, $dataDir);
$installer = new Installer($paths, '');
$installer->prepare(TEIA_ROOT . '/app/migrations');
$db = new Database($paths->database());
(new UserRepository($db))->create('visual', 'Teste visual', null, bin2hex(random_bytes(16)), 'admin', false);
$installer->finish($db);

$media = new MediaService($db, $paths->uploads(), TEIA_ROOT . '/public/assets', 8 << 20, 15 << 20, 2400, 40_000_000);
$pdf = $paths->tmp() . '/teste.pdf';
file_put_contents($pdf, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");

$page = (new PageRegistry(TEIA_ROOT . '/app/content'))->get('teia');
$data = Schema::defaults($page);
// Texto do Figma (placeholder de layout do item 1) — usado SOMENTE para medir a geometria.
$figmaPlaceholder = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ornare id leo sit amet bibendum. Phasellus blandit luctus molestie. Quisque et urna vel risus feugiat convallis. Cras pharetra rutrum elit eu aliquet. Fusce rhoncus neque non rhoncus dictum. Maecenas mattis maximus enim. Praesent eu dui quis risus viverra convallis. Morbi id lobortis elit, a faucibus tellus.';
foreach ($data['duvidas']['itens'] as $i => &$item) {
    $item['resposta'] = $i === 0 ? $figmaPlaceholder : 'Resposta de teste.';
}
unset($item);
foreach ($data['normas']['documentos'] as &$doc) {
    $doc['arquivo'] = $media->storePdf($pdf, 'teste.pdf', null);
}
unset($doc);
(new ContentRepository($db))->save('teia', $data, 0, null, 'Conteúdo de teste visual');
echo "ok\n";
