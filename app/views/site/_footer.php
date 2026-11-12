<?php
/**
 * Rodapé institucional ("Rodapé/Redapé V1").
 * As cores das barras das colunas são identidade visual (fixas aqui, não editáveis).
 * Links vazios = TODO-LINK.
 * @var array $layout
 * @var \Teia\Cms\Render $h
 */
defined('TEIA_ROOT') || exit;
$r = $layout['rodape'];
$colunas = [
    'col_adesampa' => 'empreende',
    'col_empreende' => 'empreende',
    'col_tech' => 'tech',
    'col_games' => 'games',
    'col_verde' => 'verde',
    'col_criativa' => 'criativa',
    'col_outros' => 'empreende',
    'col_comunicacao' => 'empreende',
];
$redes = [
    'instagram' => 'Instagram',
    'linkedin' => 'LinkedIn',
    'youtube' => 'YouTube',
    'facebook' => 'Facebook',
];
?>
<footer class="site-footer">
    <div class="site-footer__top container">
        <div class="site-footer__about">
            <a class="site-footer__logo"<?= $h->href($r['logo_link']) ?> aria-label="ADE SAMPA">
                <img src="<?= e(url('/assets/icons/logo-adesampa-rodape.svg')) ?>" alt="ADE SAMPA" width="199" height="34" loading="lazy">
            </a>
            <p class="site-footer__text"><?= e($r['texto']) ?></p>
        </div>
        <div class="site-footer__social">
            <p class="site-footer__heading"><?= e($r['redes_titulo']) ?></p>
            <ul class="site-footer__social-list">
                <?php foreach ($redes as $key => $nome): ?>
                    <li>
                        <a class="social-btn"<?= $h->href($r['redes'][$key]) ?> aria-label="<?= e($nome) ?>">
                            <img src="<?= e(url('/assets/icons/rede-' . $key . '.svg')) ?>" alt="" width="15" height="15" loading="lazy">
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <nav class="site-footer__columns container" aria-label="Mapa do site ADE SAMPA">
        <?php foreach ($colunas as $key => $cor): $col = $r[$key]; ?>
            <div class="site-footer__col">
                <p class="site-footer__col-title"><span class="site-footer__bar site-footer__bar--<?= e($cor) ?>" aria-hidden="true"></span><?= e($col['titulo']) ?></p>
                <ul class="site-footer__links">
                    <?php foreach ($col['links'] as $link): ?>
                        <li><a class="site-footer__link"<?= $h->href($link['link']) ?>><?= e($link['rotulo']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="site-footer__bottom container">
        <div class="site-footer__legal">
            <ul class="site-footer__legal-links">
                <?php foreach ($r['legais'] as $i => $link): ?>
                    <?php if ($i > 0): ?><li class="site-footer__dot" aria-hidden="true">•</li><?php endif; ?>
                    <li><a class="site-footer__link"<?= $h->href($link['link']) ?>><?= e($link['rotulo']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <p class="site-footer__info">
                <strong><?= e($r['empresa']) ?></strong> <span>| <?= e($r['cnpj']) ?></span><br>
                <?= e($r['endereco']) ?><br>
                <?= e($r['copyright']) ?>
            </p>
        </div>
        <div class="site-footer__logos">
            <img class="site-footer__logo-ade" src="<?= e(url('/assets/icons/logo-adesampa-rodape-assinatura.svg')) ?>" alt="ADE SAMPA" width="133" height="23" loading="lazy">
            <img class="site-footer__logo-pref" src="<?= e(url('/assets/icons/logo-prefeitura-rodape.svg')) ?>" alt="Prefeitura de São Paulo" width="161" height="51" loading="lazy">
        </div>
    </div>
</footer>
