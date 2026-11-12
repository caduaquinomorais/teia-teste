<?php
/**
 * Página de erro genérica (400/405/500). Nunca mostra caminhos, stack traces ou versões.
 * Não usa o layout completo do site, para continuar funcionando mesmo se o conteúdo falhar.
 * @var int $status
 * @var string $title
 * @var string $message
 * @var ?string $ref
 */
defined('TEIA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> | Teia</title>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<main class="error-page" id="conteudo">
    <img src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="94" height="28">
    <p class="error-page__status">Erro <?= (int) $status ?></p>
    <h1 class="h2"><?= e($title) ?></h1>
    <?php if ($message !== ''): ?><p class="body1"><?= e($message) ?></p><?php endif; ?>
    <?php if ($ref): ?><p class="body2">Código de referência: <strong><?= e($ref) ?></strong></p><?php endif; ?>
    <a class="btn btn--primary" href="<?= e(url('/')) ?>">Voltar ao início</a>
</main>
</body>
</html>
