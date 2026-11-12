<?php
/**
 * @var \Teia\Core\View $view
 * @var ?string $error
 * @var string $username
 * @var string $csrf
 * @var array $flashes
 */
defined('TEIA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Entrar | Painel do Teia</title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--centered">
<main class="auth-card" id="conteudo">
    <img class="auth-card__logo" src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="94" height="28">
    <h1>Painel do Teia</h1>
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?php if ($error): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/admin/login')) ?>" class="stack">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="field">
            <label class="field__label" for="username">Usuário</label>
            <input id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required maxlength="40" autofocus>
        </div>
        <div class="field">
            <label class="field__label" for="password">Senha</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="128">
        </div>
        <button class="btn btn--primary btn--block" type="submit">Entrar</button>
    </form>
    <p class="muted small"><a href="<?= e(url('/admin/recuperar')) ?>">Perdeu o acesso de administrador?</a></p>
</main>
</body>
</html>
