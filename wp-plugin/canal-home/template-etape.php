<?php
/**
 * Étape 2026 (TASK-060): ciudad o pueblo del canal, todo con datos (etape-route.php). Estructura .cdm-contenu + etape.css.
 */
defined('ABSPATH') || exit;

$s = canal_etape_state();
$e = $s['e'];
$km = function (array $r): string { return (int) round($r['km']) . ' km'; };

get_header();
?>
<div class="cdm-contenu cdm-etape">
<main class="contenu">
    <div class="contenu-wrap">
        <nav class="contenu-crumbs" aria-label="Fil d'Ariane">
            <ol>
                <li><a href="<?= esc_url(home_url(CANAL_HOME_PATH)) ?>">Accueil</a></li>
                <li><a href="<?= esc_url(home_url(CANAL_ETAPES_PATH)) ?>">Étapes du Canal du Midi</a></li>
                <li><span aria-current="page"><?= esc_html($e['name']) ?></span></li>
            </ol>
        </nav>

        <header class="etape-hero">
            <div class="etape-hero-text">
                <p class="etape-eyebrow"><?= $e['canal'] === 'midi' ? 'Étape du Canal du Midi' : 'Canal de la Robine' ?> · <?= esc_html($e['dept']) ?></p>
                <h1><?= esc_html($e['name']) ?></h1>
                <p class="etape-lead"><?= esc_html($s['lead']) ?></p>
                <div class="etape-actions">
                    <?php if ($carteUrl = ($s['carte'])('')): ?><a class="etape-btn" href="<?= esc_url($carteUrl) ?>"><i class="bi bi-map" aria-hidden="true"></i> Voir sur la carte</a><?php endif; ?>
                    <?php if ($e['canal'] === 'midi'): ?>
                        <a class="etape-btn etape-btn--soft" href="<?= esc_url(add_query_arg('de', $e['calcul'], home_url('/' . CANAL_CALCUL_SLUG . CANAL_CONTENU_SUFFIX . '/'))) ?>"><i class="bi bi-rulers" aria-hidden="true"></i> Calculer un trajet</a>
                    <?php endif; ?>
                </div>
            </div>
            <img class="etape-hero-img" src="<?= esc_url($s['hero']) ?>" alt="" width="768" height="512" fetchpriority="high">
        </header>

        <?php if ($s['marks']): ?>
            <section class="etape-section" aria-labelledby="etape-reperes">
                <h2 id="etape-reperes">Repères sur le canal</h2>
                <ul class="etape-marks">
                    <?php foreach ($s['marks'] as $m): ?>
                        <li>
                            <span class="etape-mark-label"><?php if (!empty($m[3])): ?><a href="<?= esc_url($m[3]) ?>"><?= esc_html($m[0]) ?></a><?php else: ?><?= esc_html($m[0]) ?><?php endif; ?></span>
                            <strong><?= esc_html($km($m[1])) ?></strong>
                            <span><?= esc_html(canal_calcul_locks_label($m[1]['sites'], $m[1]['sas'])) ?> · <?= esc_html(canal_calcul_duration($m[1]['boat'])) ?> en bateau</span>
                            <a class="etape-mark-link" href="<?= esc_url($m[2]) ?>">Détail du trajet →</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php foreach ($s['groups'] as $key => $g): if ($key === 'eau') { continue; } ?>
            <section class="etape-section" aria-labelledby="etape-<?= esc_attr($key) ?>">
                <div class="etape-section-head">
                    <h2 id="etape-<?= esc_attr($key) ?>"><?= esc_html($g['label']) ?> <span><?= (int) $g['count'] ?></span></h2>
                    <?php if ($carteUrl = ($s['carte'])($g['type'])): ?><a href="<?= esc_url($carteUrl) ?>">Tout voir sur la carte →</a><?php endif; ?>
                </div>
                <ul class="etape-cards">
                    <?php foreach ($g['items'] as $it): ?>
                        <li>
                            <a href="<?= esc_url($it['url']) ?>">
                                <?php if ($it['image'] !== ''): ?><img src="<?= esc_url($it['image']) ?>" alt="" loading="lazy" width="384" height="256"><?php else: ?><span class="etape-noimg" aria-hidden="true"></span><?php endif; ?>
                                <span class="etape-card-body">
                                    <strong><?= esc_html($it['title']) ?></strong>
                                    <small><?= esc_html(trim(($it['type'] ?? '') . ' · ' . canal_fiche_km_label($it['distance_km']), ' ·')) ?></small>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>

        <?php if (!empty($s['groups']['eau'])): ?>
            <section class="etape-section" aria-labelledby="etape-eau">
                <h2 id="etape-eau">Écluses et ports</h2>
                <ul class="etape-water">
                    <?php foreach ($s['groups']['eau']['items'] as $it): ?>
                        <li><a href="<?= esc_url($it['url']) ?>"><?= esc_html(canal_fiche_display_title($it['title'])) ?></a> <span><?= esc_html(canal_fiche_km_label($it['distance_km'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($s['read']): ?>
            <section class="etape-section" aria-labelledby="etape-lire">
                <h2 id="etape-lire">À lire</h2>
                <ul class="etape-read">
                    <?php foreach ($s['read'] as $r): ?>
                        <li><a href="<?= esc_url($r[0]) ?>"><?= esc_html($r[1]) ?></a><?php if (!empty($r[2])): ?> <span><?= esc_html($r[2]) ?></span><?php endif; ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($s['faq']): ?>
            <section class="contenu-faq etape-section" aria-labelledby="etape-faq">
                <h2 id="etape-faq">Questions fréquentes</h2>
                <?php foreach ($s['faq'] as $qa): ?>
                    <details>
                        <summary><?= esc_html($qa['q']) ?></summary>
                        <p><?= esc_html($qa['a']) ?></p>
                    </details>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <p class="etape-back"><a href="<?= esc_url(home_url(CANAL_ETAPES_PATH)) ?>">← Toutes les étapes du Canal du Midi</a></p>
    </div>
</main>
</div>
<?php
get_footer();
