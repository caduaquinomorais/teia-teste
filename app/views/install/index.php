<?php
/**
 * @var array $checks
 * @var bool $requirementsOk
 * @var bool $prepared
 * @var string $csrf
 * @var array $errors
 * @var array $old
 */
defined('TEIA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Instalação | Teia</title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--centered">
<main class="auth-card auth-card--wide" id="conteudo">
    <img class="auth-card__logo" src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="94" height="28">
    <h1>Instalação do site</h1>

    <?php if ($errors !== []): ?>
        <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <h2>1. Requisitos do servidor</h2>
    <ul class="checks">
        <?php foreach ($checks as $check): ?>
            <li class="checks__item <?= $check['ok'] ? 'is-ok' : ($check['required'] ? 'is-fail' : 'is-warn') ?>">
                <span class="checks__status"><?= $check['ok'] ? 'OK' : ($check['required'] ? 'FALHOU' : 'AVISO') ?></span>
                <span><strong><?= e($check['label']) ?></strong><br><span class="muted small"><?= e($check['detail']) ?></span></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (!$requirementsOk): ?>
        <div class="alert alert--error" role="alert">Corrija os itens marcados como FALHOU e recarregue esta página. Veja docs/INSTALACAO.md.</div>
    <?php elseif (!$prepared): ?>
        <h2>2. Preparar</h2>
        <p>Cria as pastas de dados, a chave de criptografia, o banco de dados e um arquivo com o token de instalação.</p>
        <form method="post" action="<?= e(url('/instalar')) ?>">
            <input type="hidden" name="etapa" value="preparar">
            <button class="btn btn--primary" type="submit">Preparar instalação</button>
        </form>
    <?php else: ?>
        <h2>2. Criar a conta de administrador</h2>
        <ol class="steps">
            <li>Acesse o servidor por FTP e abra o arquivo <code>install-token.txt</code> no diretório de dados (<code>teia-ade-sampa-data</code>).</li>
            <li>Copie o código de 32 caracteres e cole no campo “Token”.</li>
        </ol>
        <form method="post" action="<?= e(url('/instalar')) ?>" class="stack">
            <input type="hidden" name="etapa" value="concluir">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="field">
                <label class="field__label" for="token">Token de instalação</label>
                <input id="token" name="token" required maxlength="64" autocomplete="off" spellcheck="false">
            </div>
            <div class="field">
                <label class="field__label" for="name">Seu nome</label>
                <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required maxlength="100" autocomplete="name">
            </div>
            <div class="field">
                <label class="field__label" for="username">Usuário (nome de acesso)</label>
                <p class="field__help" id="username-ajuda">Letras minúsculas, números, ponto, hífen ou sublinhado.</p>
                <input id="username" name="username" value="<?= e($old['username'] ?? '') ?>" required maxlength="40" autocapitalize="none" spellcheck="false" autocomplete="username" aria-describedby="username-ajuda">
            </div>
            <div class="field">
                <label class="field__label" for="email">E-mail (opcional)</label>
                <input id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" maxlength="150" autocomplete="email">
            </div>
            <div class="field">
                <label class="field__label" for="password">Senha</label>
                <p class="field__help" id="password-ajuda">Pelo menos 12 caracteres, sem o nome de usuário. Não existe senha padrão.</p>
                <input id="password" name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" aria-describedby="password-ajuda">
            </div>
            <div class="field">
                <label class="field__label" for="password_confirm">Repita a senha</label>
                <input id="password_confirm" name="password_confirm" type="password" required minlength="12" maxlength="128" autocomplete="new-password">
            </div>
            <button class="btn btn--primary btn--block" type="submit">Concluir instalação</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
