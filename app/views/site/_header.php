<?php
/**
 * Cabeçalho institucional ("menu header", variante "programas") + submenu do TEIA.
 * Links vazios = TODO-LINK (preencher no painel em "Cabeçalho e rodapé").
 * @var array $layout
 * @var \Teia\Cms\Render $h
 */
defined('TEIA_ROOT') || exit;
$cab = $layout['cabecalho'];
$sub = $layout['submenu'];
?>
<header class="site-header" id="topo">
    <div class="site-header__inner">
        <a class="site-header__logo"<?= $h->href($cab['logo_link']) ?> aria-label="ADE SAMPA — página inicial">
            <img src="<?= e(url('/assets/icons/logo-adesampa.svg')) ?>" alt="ADE SAMPA" width="123" height="21">
        </a>
        <button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="menu-principal">
            <span class="site-header__toggle-icon" aria-hidden="true"></span>
            <span>Menu</span>
        </button>
        <nav class="site-header__nav" id="menu-principal" aria-label="Menu principal">
            <ul class="site-header__menu">
                <?php foreach ($cab['menu'] as $item): ?>
                    <li class="site-header__item<?= $item['ativo'] ? ' is-active' : '' ?>">
                        <a class="site-header__link"<?= $h->href($item['link']) ?><?= $item['ativo'] ? ' aria-current="true"' : '' ?>>
                            <?= e($item['rotulo']) ?>
                            <?php if ($item['seta']): ?>
                                <img class="site-header__arrow" src="<?= e(url('/assets/icons/seta-menu.svg')) ?>" alt="" width="7" height="4">
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn--empreende site-header__cta"<?= $h->href($cab['botao_link']) ?>><?= e($cab['botao_texto']) ?></a>
        </nav>
    </div>
</header>
<nav class="submenu" aria-label="Seções do Teia">
    <div class="submenu__inner">
        <a class="submenu__logo" href="#topo" aria-label="Teia — topo da página">
            <img src="<?= e(url('/assets/icons/logo-teia.svg')) ?>" alt="Teia" width="47" height="14">
        </a>
        <ul class="submenu__list">
            <?php foreach ($sub['itens'] as $item): ?>
                <li><a class="submenu__link"<?= $h->href($item['link']) ?>><?= e($item['rotulo']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
