<?php
/**
 * @var \Teia\Core\View $view
 * @var array $user        administrador logado
 * @var ?array $target     usuário editado (null = novo)
 * @var array $input
 * @var array $errors
 * @var array $pages
 * @var bool $isLastAdmin
 * @var string $csrf
 */
defined('TEIA_ROOT') || exit;
$isNew = $target === null;
$view->layout('admin/layout', ['title' => $isNew ? 'Novo usuário' : 'Gerenciar usuário', 'active' => 'usuarios']);
$action = $isNew ? '/admin/usuarios/novo' : '/admin/usuarios/' . (int) $target['id'];
$self = !$isNew && (int) $target['id'] === (int) $user['id'];
?>
<div class="narrow">
    <p class="breadcrumb-admin"><a href="<?= e(url('/admin/usuarios')) ?>">Usuários</a> / <?= $isNew ? 'Novo' : e($target['name']) ?></p>
    <h1><?= $isNew ? 'Novo usuário' : e($target['name']) ?></h1>

    <?php if ($errors !== []): ?>
        <div class="alert alert--error" role="alert"><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url($action)) ?>" class="stack">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="field">
            <label class="field__label" for="username">Usuário (nome de acesso)</label>
            <?php if ($isNew): ?>
                <p class="field__help" id="username-ajuda">Letras minúsculas, números, ponto, hífen ou sublinhado. Ex.: maria.silva</p>
                <input id="username" name="username" value="<?= e($input['username']) ?>" required maxlength="40" pattern="[a-z0-9][a-z0-9._\-]{2,39}" autocapitalize="none" spellcheck="false" aria-describedby="username-ajuda">
            <?php else: ?>
                <input id="username" value="<?= e($input['username']) ?>" disabled>
            <?php endif; ?>
        </div>
        <div class="field">
            <label class="field__label" for="name">Nome</label>
            <input id="name" name="name" value="<?= e($input['name']) ?>" required maxlength="100">
        </div>
        <div class="field">
            <label class="field__label" for="email">E-mail (opcional)</label>
            <input id="email" name="email" type="email" value="<?= e($input['email']) ?>" maxlength="150">
        </div>
        <fieldset class="field-group">
            <legend>Papel</legend>
            <label class="check"><input type="radio" name="role" value="editor"<?= $input['role'] === 'editor' ? ' checked' : '' ?><?= $isLastAdmin ? ' disabled' : '' ?>> Editor — edita apenas as páginas marcadas abaixo</label>
            <label class="check"><input type="radio" name="role" value="admin"<?= $input['role'] === 'admin' ? ' checked' : '' ?>> Administrador — edita tudo e gerencia usuários e backups</label>
            <?php if ($isLastAdmin): ?><p class="field__help">Esta é a última conta de administrador ativa: o papel não pode ser alterado.</p><?php endif; ?>
        </fieldset>
        <fieldset class="field-group">
            <legend>Páginas que pode editar (apenas para editores)</legend>
            <?php foreach ($pages as $slug => $page): ?>
                <label class="check"><input type="checkbox" name="permissions[]" value="<?= e($slug) ?>"<?= in_array($slug, $input['permissions'], true) ? ' checked' : '' ?>> <?= e($page['title']) ?></label>
            <?php endforeach; ?>
            <p class="field__help">Ao retirar uma permissão, as sessões abertas dessa pessoa são encerradas.</p>
        </fieldset>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= $isNew ? 'Criar usuário' : 'Salvar alterações' ?></button>
            <a class="btn btn--secondary" href="<?= e(url('/admin/usuarios')) ?>">Cancelar</a>
        </div>
        <?php if ($isNew): ?><p class="muted">Uma senha temporária será gerada e exibida uma única vez. A pessoa terá de trocá-la no primeiro acesso.</p><?php endif; ?>
    </form>

    <?php if (!$isNew && !$self): ?>
        <hr>
        <h2>Acesso</h2>
        <div class="stack">
            <form method="post" action="<?= e(url('/admin/usuarios/' . (int) $target['id'] . '/senha')) ?>" data-confirm="Gerar uma nova senha temporária para <?= e($target['name']) ?>? As sessões abertas serão encerradas.">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button class="btn btn--secondary" type="submit">Redefinir senha</button>
            </form>
            <form method="post" action="<?= e(url('/admin/usuarios/' . (int) $target['id'] . '/status')) ?>" data-confirm="<?= (int) $target['active'] === 1 ? 'Desativar a conta de ' . e($target['name']) . '? A pessoa não conseguirá mais entrar.' : 'Reativar a conta de ' . e($target['name']) . '?' ?>">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="ativo" value="<?= (int) $target['active'] === 1 ? '0' : '1' ?>">
                <?php if ((int) $target['active'] === 1): ?>
                    <button class="btn btn--danger" type="submit"<?= $isLastAdmin ? ' disabled' : '' ?>>Desativar conta</button>
                    <?php if ($isLastAdmin): ?><p class="field__help">A última conta de administrador ativa não pode ser desativada.</p><?php endif; ?>
                <?php else: ?>
                    <button class="btn btn--secondary" type="submit">Reativar conta</button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>
