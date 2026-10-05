<?php
/**
 * Índice /etapes/ (rediseño 05/10, maqueta docs/mockups/etapes-2026.html): « Quel parcours faire ? » — parcours por modo y
 * duración (menú de wp-admin, cifras del calcul) y el canal en línea con los tramos entre etapas. Sin JS se ven todos.
 */
defined('ABSPATH') || exit;

$s = canal_etape_state();
$idx = $s['index'];
$images = [];
foreach (array_merge($idx['midi'], $idx['robine']) as $it) {
    $images[$it['e']['slug']] = $it['image'];
}
$label = ['bateau' => 'en bateau', 'velo' => 'à vélo', 'jour' => '1 journée', 'weekend' => 'un week-end', 'semaine' => 'une semaine'];

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

        <?php if ($idx['parcours']): ?>
        <div class="etapes-ask" hidden>
            <fieldset>
                <legend>Comment ?</legend>
                <div class="etapes-seg" data-q="mode">
                    <button type="button" data-v="bateau" aria-pressed="true">En bateau</button>
                    <button type="button" data-v="velo" aria-pressed="false">À vélo</button>
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
        </div>

        <section class="etapes-props" aria-labelledby="etapes-props-title" aria-live="polite">
            <div class="etapes-props-head">
                <h2 id="etapes-props-title">Parcours en bateau et à vélo</h2>
                <p>Calcul : 7 km/h et 10 min par sas d’écluse, 6 h de navigation par jour · vélo 15 km/h.</p>
            </div>
            <div class="etapes-prop-grid">
                <?php foreach ($idx['parcours'] as $c): ?>
                    <article class="etapes-prop" data-mode="<?= esc_attr($c['mode']) ?>" data-duree="<?= esc_attr($c['duree']) ?>">
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
                                <a class="etapes-btn" href="<?= esc_url(canal_parcours_loueurs_url($c)) ?>"><?= $c['mode'] === 'bateau' ? 'Loueurs ' . esc_html(canal_etape_a($c['from'])) : 'Louer un vélo' ?></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="etapes-line" aria-labelledby="etapes-line-title">
            <p class="etapes-eyebrow">Le canal étape par étape</p>
            <h2 id="etapes-line-title">De Toulouse à l’étang de Thau</h2>
            <p class="etapes-line-intro"><?= count($idx['midi']) ?> étapes dans l’ordre, avec la distance et le temps entre chacune. Touchez une étape pour voir ses ports, hébergements et loueurs.</p>
            <ol class="etapes-route">
                <?php foreach ($idx['midi'] as $i => $it): ?>
                    <li class="etapes-stop">
                        <img src="<?= esc_url($it['image']) ?>" alt="" loading="lazy" width="82" height="82">
                        <a class="etapes-stop-body" href="<?= esc_url($it['url']) ?>">
                            <strong><?= esc_html($it['e']['name']) ?></strong>
                            <span class="etapes-pk"><?= esc_html(canal_etape_pk_label((float) canal_etape_pk($it['e']))) ?></span>
                            <?php if ($it['count']): ?><span class="etapes-n"><?= (int) $it['count'] ?> prestataires</span><?php endif; ?>
                            <?php if ($it['voir']): ?><span class="etapes-voir"><b>À voir :</b> <?= esc_html(canal_etape_list($it['voir'])) ?></span><?php endif; ?>
                        </a>
                    </li>
                    <?php if ($it['e']['slug'] === 'le-somail' && $idx['robine']): ?>
                        <li class="etapes-branch">↘ Embranchement : canal de la Robine vers
                            <?php foreach ($idx['robine'] as $k => $rb): ?><?= $k ? ($k === count($idx['robine']) - 1 ? ' et ' : ', ') : '' ?><a href="<?= esc_url($rb['url']) ?>"><?= esc_html($rb['e']['name']) ?></a><?php endforeach; ?>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($idx['legs'][$i])): $l = $idx['legs'][$i]; ?>
                        <li class="etapes-leg"><span><b><?= (int) round($l['km']) ?> km</b> · <?= esc_html(mb_strtolower(canal_calcul_locks_label($l['sites'], $l['sas']), 'UTF-8')) ?> · <?= esc_html(canal_calcul_duration($l['boat'])) ?> en bateau · <?= esc_html(canal_calcul_duration($l['bike'])) ?> à vélo</span></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php foreach ($idx['faq'] as $i => $qa): ?>
            <section class="etape-section etapes-faq" aria-labelledby="etapes-faq-<?= (int) $i ?>">
                <h2 id="etapes-faq-<?= (int) $i ?>"><?= esc_html($qa['q']) ?></h2>
                <p><?= esc_html($qa['a']) ?></p>
            </section>
        <?php endforeach; ?>
    </div>
</main>
</div>
<script>
(function () {
    var ask = document.querySelector('.etapes-ask');
    if (!ask) return;
    var state = {mode: 'bateau', duree: 'semaine'};
    var label = <?= wp_json_encode($label) ?>;
    var title = document.getElementById('etapes-props-title');
    function apply() {
        var n = 0;
        document.querySelectorAll('.etapes-prop').forEach(function (el) {
            var on = el.dataset.mode === state.mode && el.dataset.duree === state.duree;
            el.hidden = !on;
            n += on ? 1 : 0;
        });
        title.textContent = n + ' parcours ' + label[state.mode] + ' pour ' + label[state.duree];
    }
    ask.hidden = false;
    ask.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) return;
        var seg = b.parentNode;
        seg.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
        state[seg.dataset.q] = b.dataset.v;
        apply();
    });
    apply();
})();
</script>
<?php
get_footer();
