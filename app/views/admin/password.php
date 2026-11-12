<?php
/**
 * @var \Teia\Core\View $view
 * @var array $user
 * @var array $errors
 * @var string $csrf
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Minha senha', 'active' => 'senha']);
$temporary = (int) $user['must_change_password'] === 1;
?>
<div class="narrow">
    <h1><?= $temporary ? 'Crie sua senha' : 'Minha senha' ?></h1>
    <?php if ($temporary): ?>
        <div class="alert alert--info" role="status">Você entrou com uma senha temporária. Para continuar, crie uma senha pessoal.</div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/admin/senha')) ?>" class="stack">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="text" name="username" value="<?= e($user['username']) ?>" autocomplete="username" hidden>
        <div class="field">
            <label class="field__label" for="current_password"><?= $temporary ? 'Senha temporária' : 'Senha atual' ?></label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required maxlength="128">
        </div>
        <div class="field">
            <label class="field__label" for="password">Nova senha</label>
            <p class="field__help" id="password-ajuda">Pelo menos 12 caracteres. Uma frase com algumas palavras é fácil de lembrar e difícil de adivinhar. Não use seu nome de usuário.</p>
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="128" aria-describedby="password-ajuda">
        </div>
        <div class="field">
            <label class="field__label" for="password_confirm">Repita a nova senha</label>
            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required minlength="12" maxlength="128">
        </div>
        <button class="btn btn--primary" type="submit">Salvar senha</button>
    </form>
</div>
