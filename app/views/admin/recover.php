<?php
/**
 * Recuperação de acesso de administrador por token (arquivo no diretório de dados, obtido via FTP).
 * @var string $csrf
 * @var array $errors
 * @var bool $generated
 * @var bool $done
 * @var string $username
 */
defined('TEIA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar acesso | Painel do Teia</title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--centered">
<main class="auth-card auth-card--wide" id="conteudo">
    <img class="auth-card__logo" src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="94" height="28">
    <h1>Recuperar acesso de administrador</h1>

    <?php if ($done): ?>
        <div class="alert alert--success" role="status">Senha redefinida e conta reativada. O token foi apagado.</div>
        <p><a class="btn btn--primary" href="<?= e(url('/admin/login')) ?>">Ir para o login</a></p>
    <?php else: ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if ($generated): ?>
            <div class="alert alert--info" role="status">Se não havia um token válido, um novo foi criado. Ele vale por 30 minutos.</div>
        <?php endif; ?>

        <ol class="steps">
            <li>Clique em “Gerar token”. O site cria o arquivo <code>recovery-token.txt</code> no diretório de dados (fora da pasta pública).</li>
            <li>Acesse o servidor por FTP, abra esse arquivo e copie o código de 32 caracteres.</li>
            <li>Preencha o formulário abaixo. O token só funciona uma vez.</li>
        </ol>

        <form method="post" action="<?= e(url('/admin/recuperar')) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="etapa" value="gerar">
            <button class="btn btn--secondary" type="submit">Gerar token</button>
        </form>

        <form method="post" action="<?= e(url('/admin/recuperar')) ?>" class="stack">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="etapa" value="redefinir">
            <div class="field">
                <label class="field__label" for="token">Token</label>
                <input id="token" name="token" required maxlength="64" autocomplete="off" spellcheck="false">
            </div>
            <div class="field">
                <label class="field__label" for="username">Usuário administrador</label>
                <input id="username" name="username" value="<?= e($username) ?>" required maxlength="40" autocomplete="username" autocapitalize="none">
            </div>
            <div class="field">
                <label class="field__label" for="password">Nova senha</label>
                <p class="field__help" id="password-ajuda">Pelo menos 12 caracteres, sem o nome de usuário.</p>
                <input id="password" name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" aria-describedby="password-ajuda">
            </div>
            <div class="field">
                <label class="field__label" for="password_confirm">Repita a nova senha</label>
                <input id="password_confirm" name="password_confirm" type="password" required minlength="12" maxlength="128" autocomplete="new-password">
            </div>
            <button class="btn btn--primary btn--block" type="submit">Redefinir senha</button>
        </form>
        <p class="muted small"><a href="<?= e(url('/admin/login')) ?>">Voltar ao login</a></p>
    <?php endif; ?>
</main>
</body>
</html>
