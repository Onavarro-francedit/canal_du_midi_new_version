<?php
/**
 * Bloque de la página météo 2026 (TASK-066 M1): lo incluye template-contenu.php cuando $c['block'] === 'meteo'.
 * Datos y textos: includes/meteo-core.php. Sin JS se ven los 12 meses.
 */
defined('ABSPATH') || exit;
$meteoMonths = canal_meteo_months();
?>
                    <?php // Météo (TASK-066 M1, maqueta docs/mockups/meteo-2026.html): sin JS se ven los 12 meses. ?>
                    <section class="meteo-block" aria-labelledby="meteo-year-title">
                        <p class="meteo-eyebrow">L’année en un coup d’œil</p>
                        <h2 id="meteo-year-title">Chaque mois sur le canal</h2>
                        <p class="meteo-intro">Température de l’après-midi de l’étape la plus fraîche à la plus chaude, et nombre de jours à 30 °C ou plus. Touchez un mois pour le détail par étape.</p>
                        <ul class="meteo-legend">
                            <?php foreach (CANAL_METEO_KINDS as $k => [$label, $rule]): ?><li class="is-<?= esc_attr($k) ?>"><b><?= esc_html($label) ?></b> <?= esc_html($rule) ?></li><?php endforeach; ?>
                        </ul>
                        <div class="meteo-year">
                            <?php foreach ($meteoMonths as $i => $mo): ?>
                                <button type="button" class="meteo-month is-<?= esc_attr($mo['kind']) ?>" data-i="<?= (int) $i ?>" aria-pressed="false" aria-controls="meteo-month-<?= (int) $i ?>">
                                    <b><?= esc_html($mo['short']) ?></b><span class="meteo-tag"><?= esc_html($mo['label']) ?></span>
                                    <span class="meteo-temp"><?= esc_html($mo['temp']) ?></span><small><?= esc_html($mo['hot']) ?></small>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <?php foreach ($meteoMonths as $i => $mo): ?>
                            <div class="meteo-detail" id="meteo-month-<?= (int) $i ?>">
                                <h3><?= esc_html(ucfirst($mo['name'])) ?> sur le Canal du Midi — <?= esc_html(mb_strtolower($mo['label'], 'UTF-8')) ?></h3>
                                <p><?= esc_html($mo['text']) ?></p>
                                <ul class="meteo-places">
                                    <?php foreach (CANAL_METEO_STATIONS as $st): ?>
                                        <li><b><?= esc_html($st['name']) ?></b><span><?= esc_html(canal_meteo_num($st['tmin'][$i]) . ' → ' . canal_meteo_num($st['tmax'][$i]) . ' °C · ' . canal_meteo_days($st['hot'][$i]) . ' j à 30 °C+ · ' . canal_meteo_days($st['rain'][$i]) . ' j de pluie') ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <section class="meteo-block" aria-labelledby="meteo-ways-title">
                        <p class="meteo-eyebrow">Selon votre façon de voyager</p>
                        <h2 id="meteo-ways-title">La bonne période pour vous</h2>
                        <div class="meteo-ways">
                            <?php foreach (canal_meteo_ways() as $w): ?>
                                <article class="meteo-way">
                                    <span class="meteo-way-icon" aria-hidden="true"><?= file_get_contents(CANAL_HOME_DIR . 'assets/icons/' . $w['icon'] . '.svg') // phpcs:ignore — SVG propio del plugin. ?></span>
                                    <h3><?= esc_html($w['title']) ?></h3>
                                    <span class="meteo-best"><?= esc_html($w['best']) ?></span>
                                    <ul><?php foreach ($w['points'] as $pt): ?><li><?= esc_html($pt) ?></li><?php endforeach; ?></ul>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="meteo-block" aria-labelledby="meteo-seasons-title">
                        <p class="meteo-eyebrow">Les saisons</p>
                        <h2 id="meteo-seasons-title">À quoi s’attendre, saison par saison</h2>
                        <div class="meteo-seasons">
                            <?php foreach (canal_meteo_seasons() as $se): ?>
                                <article class="meteo-season">
                                    <h3><?= esc_html($se['name']) ?></h3>
                                    <p><?= esc_html($se['months']) ?></p>
                                    <dl><?php foreach ($se['rows'] as [$dt, $dd]): ?><dt><?= esc_html($dt) ?></dt><dd><?= esc_html($dd) ?></dd><?php endforeach; ?></dl>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <p class="meteo-source">Moyennes <?= esc_html(CANAL_METEO_YEARS) ?> (5 dernières années complètes) de
                            <a href="<?= esc_url(CANAL_METEO_SOURCE_URL) ?>" target="_blank" rel="noopener">Météo-France, données climatologiques mensuelles</a>
                            (Licence Ouverte), stations <?= esc_html(implode(', ', array_column(CANAL_METEO_STATIONS, 'station'))) ?>.
                            Saisons de navigation : <a href="<?= esc_url(home_url(CANAL_METEO_NAV_URL)) ?>">Période de navigation</a>.</p>
                    </section>

                    <h2 class="meteo-forecast-title">La météo des 7 prochains jours</h2>
                    <script>
                    (function () {
                        var year = document.querySelector('.meteo-year');
                        if (!year) return;
                        var details = document.querySelectorAll('.meteo-detail');
                        function show(i) {
                            year.querySelectorAll('.meteo-month').forEach(function (b, k) { b.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
                            details.forEach(function (d, k) { d.hidden = k !== i; });
                        }
                        year.addEventListener('click', function (e) {
                            var b = e.target.closest('.meteo-month');
                            if (b) show(+b.dataset.i);
                        });
                        show(new Date().getMonth());
                    })();
                    </script>
