<?php
/**
 * Layout do site público.
 * @var \Teia\Core\View $view
 * @var \Teia\Cms\Render $h
 * @var array $layout conteúdo de cabeçalho/submenu/rodapé
 * @var string $content
 * @var string $title
 * @var string $description
 */
defined('TEIA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:locale" content="pt_BR">
    <meta name="theme-color" content="#14171F">
    <link rel="preload" href="<?= e(url('/assets/fonts/geist-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset('js/site.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<?= $view->partial('site/_header', ['layout' => $layout, 'h' => $h]) ?>
<main id="conteudo" tabindex="-1">
<?= $content ?>
</main>
<?= $view->partial('site/_footer', ['layout' => $layout, 'h' => $h]) ?>
</body>
</html>
