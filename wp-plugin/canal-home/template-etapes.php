<?php
/**
 * Índice /etapes/ (rediseño 05/10, maqueta docs/mockups/etapes-2026.html): « Quel parcours faire ? » — parcours por modo y
 * duración y, si se elige, salida (assets/etapes.js con canal_parcours_all); el mapa dibuja el parcours seleccionado.
 * Sin JS: los parcours propuestos (menú de wp-admin) y la lista de etapas como enlaces.
 */
defined('ABSPATH') || exit;

$s = canal_etape_state();
$idx = $s['index'];
$images = [];
foreach (array_merge($idx['midi'], $idx['robine']) as $it) {
    $images[$it['e']['slug']] = $it['image'];
}
$midiIdx = array_flip(array_map(function ($it) { return $it['e']['slug']; }, $idx['midi']));

get_header();
?>
<div class="cdm-contenu cdm-etape">
<main class="contenu etapes-page">
    <div class="contenu-wrap">
        <nav class="contenu-crumbs" aria-label="Fil d'Ariane">
            <ol>
                <li><a href="<?= esc_url(home_url(CANAL_HOME_PATH)) ?>">Accueil</a></li>
                <li><span aria-current="page">Villes &amp; étapes</span></li>
            </ol>
        </nav>
        <header class="contenu-head">
            <p class="etapes-eyebrow">Villes &amp; étapes</p>
            <h1>Quel parcours faire sur le Canal du Midi ?</h1>
            <p class="etapes-lead">Dites-nous comment vous voyagez et combien de temps vous avez : voici les parcours qui tiennent, avec les kilomètres, les écluses et le temps réel.</p>
        </header>

        <div class="etapes-ask" hidden>
            <fieldset>
                <legend>Comment ?</legend>
                <div class="etapes-seg" data-q="mode">
                    <button type="button" data-v="bateau" aria-pressed="true">En bateau</button>
                    <button type="button" data-v="velo" aria-pressed="false">À vélo</button>
                    <button type="button" data-v="pied" aria-pressed="false">À pied</button>
                </div>
            </fieldset>
            <fieldset>
                <legend>Combien de temps ?</legend>
                <div class="etapes-seg" data-q="duree">
                    <button type="button" data-v="jour" aria-pressed="false">1 journée</button>
                    <button type="button" data-v="weekend" aria-pressed="false">Un week-end</button>
                    <button type="button" data-v="semaine" aria-pressed="true">Une semaine</button>
                </div>
            </fieldset>
            <label class="etapes-depart">
                <span>Au départ de</span>
                <select id="etapes-depart">
                    <option value="">Les parcours conseillés</option>
                    <?php foreach ($idx['midi'] as $i => $it): ?><option value="<?= (int) $i ?>"><?= esc_html($it['e']['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>

        <section class="etapes-props" aria-labelledby="etapes-props-title">
            <div class="etapes-props-head">
                <h2 id="etapes-props-title" aria-live="polite">Parcours en bateau, à vélo et à pied</h2>
                <p>Calcul : bateau 7 km/h et 10 min par sas d’écluse, 6 h de navigation par jour · vélo 15 km/h · à pied 4 km/h.</p>
            </div>
            <div class="etapes-results">
                <div class="etapes-list" id="etapes-list">
                    <?php foreach ($idx['parcours'] as $c): ?>
                        <article class="etapes-prop" data-mode="<?= esc_attr($c['mode']) ?>" data-duree="<?= esc_attr($c['duree']) ?>" data-a="<?= (int) ($midiIdx[$c['from']['slug']] ?? 0) ?>" data-b="<?= (int) ($midiIdx[$c['to']['slug']] ?? 0) ?>">
                            <img src="<?= esc_url($images[$c['from']['slug']] ?? '') ?>" alt="" loading="lazy" width="384" height="256">
                            <div class="etapes-prop-body">
                                <?php if ($c['tag'] !== ''): ?><span class="etapes-prop-tag"><?= esc_html($c['tag']) ?></span><?php endif; ?>
                                <h3><?= esc_html($c['title']) ?></h3>
                                <ul class="etapes-chips">
                                    <?php foreach ($c['chips'] as $i => $chip): ?><li<?= $i === count($c['chips']) - 1 ? ' class="is-key"' : '' ?>><?= esc_html($chip) ?></li><?php endforeach; ?>
                                </ul>
                                <?php if ($c['via']): ?>
                                    <p><b>Vous passez par :</b> <?php foreach ($c['via'] as $k => $e): ?><?= $k ? ' · ' : '' ?><a href="<?= esc_url(canal_etape_url($e['slug'])) ?>"><?= esc_html($e['name']) ?></a><?php endforeach; ?></p>
                                <?php endif; ?>
                                <div class="etapes-prop-actions">
                                    <a class="etapes-btn etapes-btn--primary" href="<?= esc_url(canal_parcours_url($c)) ?>">Voir le détail</a>
                                    <?php if ($carte = canal_parcours_loueurs_label($c)): ?><a class="etapes-btn" href="<?= esc_url(canal_parcours_loueurs_url($c)) ?>"><?= esc_html($carte) ?></a><?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <p class="etapes-empty" hidden>Aucun parcours de cette durée au départ de cette étape : essayez une autre durée ou une autre étape.</p>
                </div>
                <div class="etapes-map-box is-unavailable" id="etapes-map-box">
                    <div id="etapes-map" role="img" aria-label="Carte du parcours sélectionné"></div>
                    <span class="etapes-map-credit">Tracé du canal © OpenStreetMap</span>
                </div>
            </div>
        </section>

        <section class="etapes-all" aria-labelledby="etapes-all-title">
            <h2 id="etapes-all-title">Toutes les étapes, de Toulouse à la Méditerranée</h2>
            <ol class="etapes-chips-list">
                <?php foreach (array_merge($idx['midi'], $idx['robine']) as $it): ?>
                    <li><a class="etapes-chip<?= $it['e']['canal'] === 'robine' ? ' is-robine' : '' ?>" href="<?= esc_url($it['url']) ?>"><?= esc_html($it['e']['name']) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php // Preguntas de Search Console con respuesta visible: sin acordeón (los bots no hacen clic). ?>
        <section class="etape-section etapes-faq" aria-labelledby="etapes-faq-title">
            <h2 id="etapes-faq-title">Questions fréquentes sur le parcours</h2>
            <?php foreach ($idx['faq'] as $qa): ?>
                <div class="etapes-faq-item">
                    <h3><?= esc_html($qa['q']) ?></h3>
                    <p><?= esc_html($qa['a']) ?></p>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</main>
</div>
<?php
get_footer();
