<?php
/**
 * Layout do painel administrativo.
 * @var \Teia\Core\View $view
 * @var ?array $user
 * @var string $csrf
 * @var array $flashes
 * @var array $pages
 * @var string $content
 * @var string $title
 * @var string $active
 */
defined('TEIA_ROOT') || exit;
$isAdmin = $user !== null && $user['role'] === 'admin';
$active ??= '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> | Painel do Teia</title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<?php if ($user !== null): ?>
<header class="topbar">
    <a class="topbar__brand" href="<?= e(url('/admin')) ?>">
        <img src="<?= e(url('/assets/icons/logo-teia-branco.svg')) ?>" alt="" width="60" height="18">
        <span>Painel</span>
    </a>
    <nav class="topbar__nav" aria-label="Menu do painel">
        <a href="<?= e(url('/admin')) ?>"<?= $active === 'inicio' ? ' aria-current="page"' : '' ?>>Início</a>
        <?php if ($isAdmin): ?>
            <a href="<?= e(url('/admin/usuarios')) ?>"<?= $active === 'usuarios' ? ' aria-current="page"' : '' ?>>Usuários</a>
            <a href="<?= e(url('/admin/backups')) ?>"<?= $active === 'backups' ? ' aria-current="page"' : '' ?>>Backups</a>
            <a href="<?= e(url('/admin/auditoria')) ?>"<?= $active === 'auditoria' ? ' aria-current="page"' : '' ?>>Auditoria</a>
        <?php endif; ?>
        <a href="<?= e(url('/admin/senha')) ?>"<?= $active === 'senha' ? ' aria-current="page"' : '' ?>>Minha senha</a>
        <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Ver site ↗</a>
    </nav>
    <form class="topbar__logout" method="post" action="<?= e(url('/admin/sair')) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <span class="topbar__user"><?= e($user['name']) ?></span>
        <button class="btn btn--small btn--ghost" type="submit">Sair</button>
    </form>
</header>
<?php endif; ?>
<main class="admin-main" id="conteudo" tabindex="-1">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
