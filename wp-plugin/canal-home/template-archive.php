<?php
/**
 * Archivo del blog 2026 (TASK-059): tarjetas de las páginas y artículos de una categoría, paginadas.
 * Misma estructura y estilos que template-contenu.php (contenu.css).
 */
defined('ABSPATH') || exit;

$a = canal_archive_state();
$last = count($a['crumbs']) - 1;
$tools = [
    ['bi-map', 'Carte interactive', 'Ports, écluses, hébergements et activités', home_url(CANAL_CARTE_PATH)],
    ['bi-rulers', 'Calcul de distance', 'Distances et temps en bateau ou à vélo', home_url('/' . CANAL_CALCUL_SLUG . CANAL_CONTENU_SUFFIX . '/')],
    ['bi-file-earmark-arrow-down', 'Plan du canal (PDF)', 'Le plan officiel, gratuit', home_url(CANAL_PLAN_PDF_PATH)],
];

get_header();
?>
<div class="cdm-contenu">
<main class="contenu">
    <div class="contenu-wrap">
        <nav class="contenu-crumbs" aria-label="Fil d'Ariane">
            <ol>
                <?php foreach ($a['crumbs'] as $i => $crumb): ?>
                    <li><?php if ($i < $last): ?><a href="<?= esc_url($crumb[0]) ?>"><?= esc_html($crumb[1]) ?></a><?php else: ?><span aria-current="page"><?= esc_html($crumb[1]) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <header class="contenu-head">
            <h1><?= esc_html($a['name']) ?></h1>
            <p class="contenu-dates"><?= (int) $a['total'] ?> article<?= $a['total'] > 1 ? 's' : '' ?> et guide<?= $a['total'] > 1 ? 's' : '' ?><?= $a['page'] > 1 ? ' · page ' . (int) $a['page'] . ' sur ' . (int) $a['pages'] : '' ?></p>
            <?php if ($a['children']): ?>
                <ul class="contenu-chips" aria-label="Sous-rubriques">
                    <?php foreach ($a['children'] as $child): ?>
                        <li><a href="<?= esc_url($child[0]) ?>"><?= esc_html($child[1]) ?> <span><?= (int) $child[2] ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </header>

        <div class="contenu-grid">
            <div class="contenu-list">
                <ul class="contenu-cards">
                    <?php foreach ($a['items'] as $n => $item): ?>
                        <li class="contenu-item">
                            <a href="<?= esc_url($item['url']) ?>">
                                <?php if ($item['image'] !== ''): ?>
                                    <?php // WP: las 2 primeras sin lazy (la primera es el LCP en móvil). ?><img src="<?= esc_url($item['image']) ?>" alt="" <?= $n === 0 ? 'fetchpriority="high"' : ($n > 1 ? 'loading="lazy"' : '') ?> width="384" height="216">
                                <?php else: ?>
                                    <span class="contenu-item-noimg" aria-hidden="true"></span>
                                <?php endif; ?>
                                <span class="contenu-item-body">
                                    <?php if ($item['date'] !== ''): ?><time datetime="<?= esc_attr($item['iso']) ?>"><?= esc_html($item['date']) ?></time><?php endif; ?>
                                    <strong><?= esc_html($item['title']) ?></strong>
                                    <span class="contenu-item-excerpt"><?= esc_html($item['excerpt']) ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($a['pages'] > 1): ?>
                    <nav class="contenu-pager" aria-label="Pagination">
                        <?php if ($a['page'] > 1): ?><a class="contenu-pager-step" href="<?= esc_url(($a['pageUrl'])($a['page'] - 1)) ?>" rel="prev">← Précédent</a><?php endif; ?>
                        <?php $prev = 0; foreach (canal_archive_window($a['page'], $a['pages']) as $n): ?>
                            <?php if ($prev && $n > $prev + 1): ?><span class="contenu-pager-gap">…</span><?php endif; ?>
                            <?php if ($n === $a['page']): ?><span aria-current="page"><?= (int) $n ?></span><?php else: ?><a href="<?= esc_url(($a['pageUrl'])($n)) ?>"><?= (int) $n ?></a><?php endif; ?>
                            <?php $prev = $n; ?>
                        <?php endforeach; ?>
                        <?php if ($a['page'] < $a['pages']): ?><a class="contenu-pager-step" href="<?= esc_url(($a['pageUrl'])($a['page'] + 1)) ?>" rel="next">Suivant →</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>

            <aside class="contenu-aside">
                <section class="contenu-card">
                    <h2>Préparer votre séjour</h2>
                    <ul class="contenu-tools">
                        <?php foreach ($tools as $t): ?>
                            <li>
                                <a href="<?= esc_url($t[3]) ?>">
                                    <i class="bi <?= esc_attr($t[0]) ?>" aria-hidden="true"></i>
                                    <span><strong><?= esc_html($t[1]) ?></strong><small><?= esc_html($t[2]) ?></small></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</main>
</div>
<?php
get_footer();
