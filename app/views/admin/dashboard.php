<?php
/**
 * @var \Teia\Core\View $view
 * @var array $user
 * @var array $editable
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Início', 'active' => 'inicio']);
?>
<div class="page-head">
    <div>
        <h1>Olá, <?= e($user['name']) ?></h1>
        <p class="muted">Escolha o que você quer editar. As alterações são publicadas assim que você clica em “Publicar alterações”.</p>
    </div>
</div>

<?php if ($editable === []): ?>
    <div class="alert alert--info" role="status">Você ainda não tem permissão para editar nenhuma página. Peça a um administrador para liberar o acesso.</div>
<?php else: ?>
    <ul class="cards">
        <?php foreach ($editable as $slug => $page): ?>
            <li class="card">
                <h2 class="card__title"><?= e($page['title']) ?></h2>
                <p class="muted"><?= e($page['description'] ?? '') ?></p>
                <p class="card__meta">
                    <?= $page['version'] > 0
                        ? 'Versão ' . (int) $page['version'] . ' · atualizada em ' . e(date('d/m/Y H:i', strtotime((string) $page['updated_at'])))
                        : 'Ainda com o conteúdo original do Figma' ?>
                </p>
                <a class="btn btn--primary" href="<?= e(url('/admin/paginas/' . $slug)) ?>">Editar</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
