<?php
/**
 * Edição de uma página do CMS.
 * @var \Teia\Core\View $view
 * @var array $page
 * @var array $values
 * @var int $version
 * @var array $errors
 * @var ?int $conflictVersion
 * @var string $csrf
 * @var \Teia\Cms\Render $h
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => $page['title'], 'active' => 'inicio']);
?>
<div class="page-head">
    <div>
        <p class="breadcrumb-admin"><a href="<?= e(url('/admin')) ?>">Início</a> / <?= e($page['title']) ?></p>
        <h1><?= e($page['title']) ?></h1>
        <?php if (!empty($page['description'])): ?><p class="muted"><?= e($page['description']) ?></p><?php endif; ?>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(url('/admin/paginas/' . $page['slug'] . '/historico')) ?>">Histórico de versões</a>
        <?php if ($page['slug'] !== 'erro-404'): ?><a class="btn btn--secondary" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Ver no site ↗</a><?php endif; ?>
    </div>
</div>

<?php if ($conflictVersion !== null): ?>
    <div class="alert alert--error" role="alert">
        <strong>Conflito de edição:</strong> outra pessoa publicou esta página (versão <?= (int) $conflictVersion ?>) enquanto você editava.
        Suas alterações <strong>não foram salvas</strong> e continuam no formulário abaixo.
        <a href="<?= e(url('/admin/paginas/' . $page['slug'])) ?>" target="_blank" rel="noopener">Abra a versão atual em outra aba</a>, compare e,
        se quiser manter as suas alterações por cima, clique em “Publicar mesmo assim”.
    </div>
<?php elseif ($errors !== []): ?>
    <div class="alert alert--error" role="alert">
        <strong>Nada foi publicado.</strong> Corrija <?= count($errors) === 1 ? 'o item abaixo' : 'os ' . count($errors) . ' itens abaixo' ?>:
        <ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="cms-form" method="post" action="<?= e(url('/admin/paginas/' . $page['slug'])) ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="version" value="<?= (int) ($conflictVersion ?? $version) ?>">
    <?php foreach ($page['sections'] as $sKey => $section): ?>
        <section class="cms-section" aria-labelledby="sec-<?= e($sKey) ?>">
            <h2 id="sec-<?= e($sKey) ?>"><?= e($section['label']) ?></h2>
            <?php if (!empty($section['help'])): ?><p class="field__help"><?= e($section['help']) ?></p><?php endif; ?>
            <?php foreach ($section['fields'] as $fKey => $field): ?>
                <?= $view->partial('admin/_field', [
                    'field' => $field, 'key' => $fKey, 'value' => $values[$sKey][$fKey] ?? null,
                    'path' => $sKey . '.' . $fKey, 'name' => 'c[' . $sKey . '][' . $fKey . ']',
                    'errors' => $errors, 'depth' => 0, 'h' => $h,
                ]) ?>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= $conflictVersion !== null ? 'Publicar mesmo assim' : 'Publicar alterações' ?></button>
        <span class="muted">As alterações aparecem no site imediatamente. Versões anteriores ficam no histórico.</span>
    </div>
</form>
