<?php

declare(strict_types=1);

/*
 * Prepara uma pasta de conteúdo TEMPORÁRIA para o teste visual, reproduzindo o estado do frame
 * do Figma (1ª dúvida respondida com o texto de layout do Figma e PDFs presentes).
 * Esse conteúdo existe só na pasta temporária e nunca é publicado.
 *
 * Uso: bin/php.sh tests/visual/preparar.php <pasta temporária>
 */

$dir = $argv[1] ?? '';
if ($dir === '') {
    fwrite(STDERR, "Informe a pasta temporária.\n");
    exit(1);
}
@mkdir($dir, 0700, true);
$src = dirname(__DIR__, 2) . '/app/content';
foreach (['layout', 'erro-404'] as $name) {
    copy("$src/$name.php", "$dir/$name.php");
}

// Texto do Figma (placeholder de layout do item 1) — usado SOMENTE para medir a geometria.
$placeholder = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ornare id leo sit amet bibendum. Phasellus blandit luctus molestie. Quisque et urna vel risus feugiat convallis. Cras pharetra rutrum elit eu aliquet. Fusce rhoncus neque non rhoncus dictum. Maecenas mattis maximus enim. Praesent eu dui quis risus viverra convallis. Morbi id lobortis elit, a faucibus tellus.';

$override = <<<PHP
<?php
\$c = require '$src/teia.php';
foreach (\$c['duvidas']['itens'] as \$i => &\$item) {
    \$item['resposta'] = \$i === 0 ? '$placeholder' : 'Resposta de teste.';
}
unset(\$item);
foreach (\$c['normas']['documentos'] as &\$doc) {
    \$doc['arquivo'] = 'https://exemplo.invalid/teste.pdf';
}
unset(\$doc);
return \$c;
PHP;
file_put_contents("$dir/teia.php", $override);
echo "ok\n";
