<?php
/**
 * Página 404 (sem referência no Figma — decisão D-10).
 * @var \Teia\Core\View $view
 * @var array $c conteúdo de "erro-404"
 * @var array $teia conteúdo da página "teia" (fundo do banner)
 * @var \Teia\View\Render $h
 */
defined('TEIA_ROOT') || exit;
$view->layout('site/layout', ['title' => $c['titulo'] . ' | Teia', 'description' => $c['texto']]);
?>
<section class="hero hero--error" aria-labelledby="erro-titulo">
    <div class="hero__bg" aria-hidden="true"><?= $h->picture($teia['banner']['fundo'], '', ['class' => 'hero__bg-img']) ?></div>
    <div class="hero__inner container">
        <div class="hero__content">
            <div class="hero__text">
                <p class="tag tag--medio">Erro 404</p>
                <h1 class="hero__title" id="erro-titulo"><?= e($c['titulo']) ?></h1>
                <p class="hero__lead"><?= e($c['texto']) ?></p>
            </div>
            <a class="btn btn--secondary" href="<?= e(url('/')) ?>"><?= e($c['botao']) ?></a>
        </div>
    </div>
</section>
