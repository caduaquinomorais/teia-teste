<?php
defined('TEIA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Instalação concluída | Teia</title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--centered">
<main class="auth-card" id="conteudo">
    <img class="auth-card__logo" src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="94" height="28">
    <h1>Instalação concluída</h1>
    <div class="alert alert--success" role="status">A conta de administrador foi criada e o token de instalação foi apagado. Este endereço de instalação não existe mais.</div>
    <p><a class="btn btn--primary" href="<?= e(url('/admin/login')) ?>">Entrar no painel</a></p>
</main>
</body>
</html>
