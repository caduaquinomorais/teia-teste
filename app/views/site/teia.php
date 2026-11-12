<?php
/**
 * Página do TEIA (frame Figma 212:2903). A ORDEM e o LAYOUT das seções são fixos aqui;
 * o CMS altera apenas o conteúdo ($c).
 * @var \Teia\Core\View $view
 * @var \Teia\Cms\Render $h
 * @var array $c conteúdo da página "teia"
 * @var array $layout
 */
defined('TEIA_ROOT') || exit;

$view->layout('site/layout', [
    'title' => $c['seo']['titulo'],
    'description' => $c['seo']['descricao'],
]);

$agendamento = $c['geral']['link_agendamento'];
$banner = $c['banner'];
$trilha = $banner['trilha'];
$mapa = $c['mapa'];
$unidades = $mapa['unidades'];

/** URL do mapa do Google para um endereço (iframe sem chave de API). */
$mapUrl = static fn (string $endereco): string => 'https://www.google.com/maps?q=' . rawurlencode($endereco) . '&z=15&hl=pt-BR&output=embed';

$faq = array_values(array_filter($c['duvidas']['itens'], static fn ($i) => trim($i['resposta']) !== ''));
?>
<section class="hero" aria-labelledby="hero-titulo">
    <div class="hero__bg" aria-hidden="true"><?= $h->picture($banner['fundo'], '', ['class' => 'hero__bg-img', 'fetchpriority' => 'high']) ?></div>
    <div class="hero__inner container">
        <div class="hero__content">
            <nav class="breadcrumb" aria-label="Trilha de navegação">
                <ol>
                    <li><a<?= $h->href($trilha['inicio_link']) ?>><?= e($trilha['inicio_texto']) ?></a></li>
                    <li><a<?= $h->href($trilha['programas_link']) ?>><?= e($trilha['programas_texto']) ?></a></li>
                    <li aria-current="page"><?= e($trilha['atual']) ?></li>
                </ol>
            </nav>
            <div class="hero__text">
                <p class="tag tag--medio"><?= e($banner['tag']) ?></p>
                <h1 class="hero__title" id="hero-titulo"><?= e($banner['titulo']) ?></h1>
                <p class="hero__lead"><?= e($banner['texto']) ?></p>
            </div>
        </div>
        <div class="hero__logos">
            <img src="<?= e(url('/assets/icons/logo-adesampa-branco.svg')) ?>" alt="ADE SAMPA" width="137" height="24">
            <img src="<?= e(url('/assets/icons/logo-prefeitura-branco.svg')) ?>" alt="Prefeitura de São Paulo" width="166" height="52">
        </div>
    </div>
</section>

<?php foreach ($c['espacos']['itens'] as $i => $espaco): $alt = $i % 2 === 1; $idTitulo = 'espaco-' . $espaco['ancora']; ?>
<section class="space<?= $alt ? ' space--alt' : '' ?>" id="<?= e($espaco['ancora']) ?>" aria-labelledby="<?= e($idTitulo) ?>">
    <div class="space__inner container">
        <div class="space__text">
            <div class="space__copy">
                <p class="tag tag--clara"><?= e($espaco['tag']) ?></p>
                <h2 class="h2" id="<?= e($idTitulo) ?>"><?= e($espaco['titulo']) ?></h2>
                <div class="body1 rich"><?= $h->rich($espaco['texto']) ?></div>
            </div>
            <div class="btn-row">
                <a class="btn btn--secondary" href="#mapa"><?= e($espaco['botao_mapa']) ?></a>
                <a class="btn btn--primary"<?= $h->href($agendamento) ?>><?= e($espaco['botao_agendamento']) ?></a>
            </div>
        </div>
        <div class="gallery" data-gallery>
            <div class="gallery__frame">
                <?php foreach ($espaco['fotos'] as $f => $foto): ?>
                    <figure class="gallery__slide"<?= $f > 0 ? ' hidden' : '' ?> data-slide>
                        <?= $h->picture($foto['imagem'], $foto['alt'], ['class' => 'gallery__img', 'loading' => $i === 0 && $f === 0 ? 'eager' : 'lazy', 'decoding' => 'async']) ?>
                    </figure>
                <?php endforeach; ?>
            </div>
            <div class="gallery__dots" role="group" aria-label="Fotos de <?= e($espaco['titulo']) ?>">
                <?php if (count($espaco['fotos']) > 1): ?>
                    <?php foreach ($espaco['fotos'] as $f => $foto): ?>
                        <button class="gallery__dot" type="button" data-dot="<?= (int) $f ?>" aria-label="Foto <?= $f + 1 ?> de <?= count($espaco['fotos']) ?>" aria-pressed="<?= $f === 0 ? 'true' : 'false' ?>"></button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endforeach; ?>

<section class="map-section" id="mapa" aria-labelledby="mapa-titulo">
    <div class="map-section__bg" aria-hidden="true"><?= $h->picture('asset:mapa-fundo', '', ['class' => 'map-section__bg-img', 'loading' => 'lazy']) ?></div>
    <div class="map-section__inner container">
        <div class="map-section__text">
            <h2 class="h2 map-section__title" id="mapa-titulo"><?= e($mapa['titulo']) ?></h2>
            <p class="body1"><?= e($mapa['texto']) ?></p>
            <a class="btn btn--secondary"<?= $h->href($agendamento) ?>><?= e($mapa['botao']) ?></a>
        </div>
        <?php if ($mapa['mapa_personalizado'] !== ''): ?>
            <div class="map-card map-card--full">
                <iframe class="map-card__frame" src="<?= e($mapa['mapa_personalizado']) ?>" title="Mapa das unidades do Teia" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        <?php else: $first = $unidades[0] ?? null; ?>
            <div class="map-card" data-map>
                <div class="map-card__panel">
                    <p class="map-card__header" data-map-title><?= e($first['nome'] ?? '') ?></p>
                    <?php if (count($unidades) > 1): ?>
                        <label class="map-card__select-label" for="mapa-unidade">Escolha a unidade</label>
                        <select class="map-card__select" id="mapa-unidade" data-map-select>
                            <?php foreach ($unidades as $u => $unidade): ?>
                                <option value="<?= (int) $u ?>"><?= e($unidade['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <?php foreach ($unidades as $u => $unidade): ?>
                        <dl class="map-card__details" data-map-unit="<?= (int) $u ?>" data-map-src="<?= e($mapUrl($unidade['endereco'])) ?>" data-map-name="<?= e($unidade['nome']) ?>"<?= $u > 0 ? ' hidden' : '' ?>>
                            <div><dt><?= e($mapa['rotulos']['unidade']) ?></dt><dd><?= e($unidade['nome']) ?></dd></div>
                            <?php if ($unidade['zona'] !== ''): ?><div><dt><?= e($mapa['rotulos']['zona']) ?></dt><dd><?= e($unidade['zona']) ?></dd></div><?php endif; ?>
                            <?php if ($unidade['classificacao'] !== ''): ?><div><dt><?= e($mapa['rotulos']['classificacao']) ?></dt><dd><?= e($unidade['classificacao']) ?></dd></div><?php endif; ?>
                            <?php if ($unidade['status'] !== ''): ?><div><dt><?= e($mapa['rotulos']['status']) ?></dt><dd><?= e($unidade['status']) ?></dd></div><?php endif; ?>
                            <div><dt><?= e($mapa['rotulos']['endereco']) ?></dt><dd><?= e($unidade['endereco']) ?></dd></div>
                        </dl>
                    <?php endforeach; ?>
                </div>
                <?php if ($first !== null): ?>
                    <iframe class="map-card__frame" src="<?= e($mapUrl($first['endereco'])) ?>" title="Mapa: <?= e($first['nome']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" data-map-frame></iframe>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="faq" id="duvidas" aria-labelledby="duvidas-titulo">
    <div class="faq__inner container">
        <header class="section-head">
            <h2 class="h2" id="duvidas-titulo"><?= e($c['duvidas']['titulo']) ?></h2>
            <?php if ($c['duvidas']['subtitulo'] !== ''): ?><p class="body1"><?= e($c['duvidas']['subtitulo']) ?></p><?php endif; ?>
        </header>
        <?php if ($faq !== []): ?>
            <div class="faq__list">
                <?php foreach ($faq as $n => $item): ?>
                    <details class="faq__item">
                        <summary class="faq__question">
                            <span class="h5"><?= $n + 1 ?>. <?= e($item['pergunta']) ?></span>
                            <img class="faq__icon" src="<?= e(url('/assets/icons/caret-circle-down.svg')) ?>" alt="" width="32" height="32">
                        </summary>
                        <div class="faq__answer body1 rich"><?= $h->rich($item['resposta']) ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="rules" id="normas" aria-labelledby="normas-titulo">
    <div class="rules__inner container">
        <div class="rules__box">
            <header class="rules__head">
                <h2 class="rules__title" id="normas-titulo"><?= e($c['normas']['titulo']) ?></h2>
                <?php if ($c['normas']['texto'] !== ''): ?><p class="rules__lead"><?= e($c['normas']['texto']) ?></p><?php endif; ?>
            </header>
            <ul class="rules__cards">
                <?php foreach ($c['normas']['documentos'] as $doc): $fileUrl = $h->fileUrl($doc['arquivo']); ?>
                    <li class="doc-card">
                        <span class="doc-card__icon" aria-hidden="true"><img src="<?= e(url('/assets/icons/file-pdf.svg')) ?>" alt="" width="32" height="32"></span>
                        <div class="doc-card__text">
                            <h3 class="h5"><?= e($doc['titulo']) ?></h3>
                            <?php if ($doc['descricao'] !== ''): ?><p class="body2"><?= e($doc['descricao']) ?></p><?php endif; ?>
                        </div>
                        <?php if ($fileUrl !== null): ?>
                            <a class="btn btn--secondary" href="<?= e($fileUrl) ?>" download><?= e($doc['botao']) ?><span class="sr-only"> — <?= e($doc['titulo']) ?> (PDF)</span></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
