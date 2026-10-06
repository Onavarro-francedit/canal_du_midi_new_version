<?php
/**
 * Plantilla de contenido 2026 (TASK-055): páginas y artículos de WordPress en /<ruta>-2026/.
 * Datos: canal_contenu_state() (contenu-route.php). El cuerpo es el post_content del editor (sin escapar, como
 * the_content); el resto se escapa.
 */
defined('ABSPATH') || exit;

$c = canal_contenu_state();
$lastCrumb = count($c['crumbs']) - 1;
$published = canal_contenu_date_fr($c['published']);
$modified = canal_contenu_date_fr($c['modified']);
$showModified = $modified !== '' && substr($c['modified'], 0, 10) > substr($c['published'], 0, 10);
$tools = [
    ['bi-map', 'Carte interactive', 'Ports, écluses, hébergements et activités', home_url(CANAL_CARTE_PATH)],
    ['bi-rulers', 'Calcul de distance', 'Distances et temps en bateau ou à vélo', home_url('/calcul-de-distance-canal-du-midi/')],
    ['bi-file-earmark-arrow-down', 'Plan du canal (PDF)', 'Le plan officiel, gratuit', home_url(CANAL_PLAN_PDF_PATH)],
];

get_header();
?>
<div class="cdm-contenu">
<main class="contenu">
    <div class="contenu-wrap">
        <nav class="contenu-crumbs" aria-label="Fil d'Ariane">
            <ol>
                <?php foreach ($c['crumbs'] as $i => $crumb): ?>
                    <li><?php if ($i < $lastCrumb): ?><a href="<?= esc_url($crumb[0]) ?>"><?= esc_html($crumb[1]) ?></a><?php else: ?><span aria-current="page"><?= esc_html($crumb[1]) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <header class="contenu-head">
            <h1><?= esc_html($c['title']) ?></h1>
            <p class="contenu-dates">
                <?php if ($c['type'] === 'post'): ?>
                    Publié le <time datetime="<?= esc_attr($c['published']) ?>"><?= esc_html($published) ?></time><?php if ($showModified): ?> · mis à jour le <time datetime="<?= esc_attr($c['modified']) ?>"><?= esc_html($modified) ?></time><?php endif; ?>
                <?php else: ?>
                    Mis à jour le <time datetime="<?= esc_attr($c['modified']) ?>"><?= esc_html($modified) ?></time>
                <?php endif; ?>
            </p>
            <?php if ($c['notice'] !== ''): ?>
                <p class="contenu-notice" role="note"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= esc_html($c['notice']) ?></p>
            <?php endif; ?>
            <?php if ($c['summary'] !== ''): ?>
                <div class="contenu-essentiel">
                    <p class="contenu-essentiel__label">L'essentiel</p>
                    <p><?= esc_html($c['summary']) ?></p>
                </div>
            <?php endif; ?>
        </header>

        <div class="contenu-grid">
            <article class="contenu-body">
                <?= $c['thumb'] // phpcs:ignore — HTML de WordPress (get_the_post_thumbnail). ?>
                <?php if ($c['block'] !== ''): ?>
                    <?php include CANAL_HOME_DIR . 'parts/contenu-' . $c['block'] . '.php'; ?>
                <?php endif; ?>
                <?= $c['html'] // phpcs:ignore — contenido del editor, como the_content. ?>

                <?php if ($c['faq']): ?>
                    <section class="contenu-faq" aria-labelledby="contenu-faq-title">
                        <h2 id="contenu-faq-title">Questions fréquentes</h2>
                        <?php // Visibles sin clic: los bots no despliegan acordeones. ?>
                        <?php foreach ($c['faq'] as $qa): ?>
                            <div class="contenu-faq-item">
                                <h3><?= esc_html($qa['q']) ?></h3>
                                <p><?= esc_html($qa['a']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </article>

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
                <?php if ($c['siblings']): ?>
                    <section class="contenu-card">
                        <h2>Dans la même rubrique</h2>
                        <ul class="contenu-links">
                            <?php foreach ($c['siblings'] as $s): ?>
                                <li><a href="<?= esc_url($s[0]) ?>"><?= esc_html($s[1]) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>
</div>
<?php
get_footer();
