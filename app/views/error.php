<?php
/**
 * Página de erro genérica. Nunca mostra caminhos, stack traces ou versões.
 * @var int $status
 * @var string $title
 * @var string $message
 * @var ?string $ref
 * @var bool $isAdmin
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
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--centered">
<main class="auth-card" id="conteudo">
    <img class="auth-card__logo" src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="94" height="28">
    <p class="muted">Erro <?= (int) $status ?></p>
    <h1><?= e($title) ?></h1>
    <?php if ($message !== ''): ?><p><?= e($message) ?></p><?php endif; ?>
    <?php if ($ref): ?>
        <p class="muted">Código de referência: <strong><?= e($ref) ?></strong>. Informe este código ao suporte.</p>
    <?php endif; ?>
    <p><a class="btn btn--secondary" href="<?= e(url($isAdmin ? '/admin' : '/')) ?>"><?= $isAdmin ? 'Voltar ao painel' : 'Voltar ao início' ?></a></p>
</main>
</body>
</html>
