<?php
/**
 * Senha temporária exibida UMA única vez.
 * @var \Teia\Core\View $view
 * @var array $target
 * @var string $temporary
 * @var string $reason
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Senha temporária', 'active' => 'usuarios']);
?>
<div class="narrow">
    <h1><?= e($reason) ?></h1>
    <div class="alert alert--info" role="status">
        Envie os dados abaixo para <strong><?= e($target['name']) ?></strong> por um canal seguro.
        Esta senha <strong>não será exibida novamente</strong> e deverá ser trocada no primeiro acesso.
    </div>
    <dl class="credentials">
        <dt>Endereço do painel</dt><dd><code><?= e(url('/admin')) ?></code></dd>
        <dt>Usuário</dt><dd><code><?= e($target['username']) ?></code></dd>
        <dt>Senha temporária</dt><dd><code class="credentials__secret"><?= e($temporary) ?></code></dd>
    </dl>
    <p><a class="btn btn--primary" href="<?= e(url('/admin/usuarios')) ?>">Voltar para usuários</a></p>
</div>
