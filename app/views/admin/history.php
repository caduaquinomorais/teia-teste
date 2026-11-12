<?php
/**
 * @var \Teia\Core\View $view
 * @var array $page
 * @var array $revisions
 * @var int $currentVersion
 * @var string $csrf
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Histórico — ' . $page['title'], 'active' => 'inicio']);
?>
<div class="page-head">
    <div>
        <p class="breadcrumb-admin"><a href="<?= e(url('/admin')) ?>">Início</a> / <a href="<?= e(url('/admin/paginas/' . $page['slug'])) ?>"><?= e($page['title']) ?></a> / Histórico</p>
        <h1>Histórico de versões</h1>
        <p class="muted">Restaurar uma versão cria uma nova versão com aquele conteúdo. Nada é apagado.</p>
    </div>
</div>
<?php if ($revisions === []): ?>
    <p>Esta página ainda não foi editada: o site mostra o conteúdo original do Figma.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Versão</th><th scope="col">Data</th><th scope="col">Quem publicou</th><th scope="col">Observação</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
            <tbody>
            <?php foreach ($revisions as $rev): ?>
                <tr>
                    <td><?= (int) $rev['version'] ?><?= (int) $rev['version'] === $currentVersion ? ' <span class="badge">atual</span>' : '' ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $rev['created_at']))) ?></td>
                    <td><?= e($rev['author'] ?? '—') ?></td>
                    <td><?= e($rev['note'] ?? '') ?></td>
                    <td>
                        <?php if ((int) $rev['version'] !== $currentVersion): ?>
                            <form method="post" action="<?= e(url('/admin/paginas/' . $page['slug'] . '/restaurar/' . (int) $rev['version'])) ?>" data-confirm="Restaurar a versão <?= (int) $rev['version'] ?>? O conteúdo atual continuará disponível no histórico.">
                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="current_version" value="<?= (int) $currentVersion ?>">
                                <button class="btn btn--small btn--secondary" type="submit">Restaurar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
