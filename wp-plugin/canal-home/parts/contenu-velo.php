<?php
/**
 * « Le Canal du Midi à vélo en bref » (TASK-066 T4): lo incluye template-contenu.php en /voie-verte-et-veloroute/
 * ($c['block'] === 'velo'), encima del texto de la página. Datos: includes/velo-core.php (calcul, étapes, météo).
 */
defined('ABSPATH') || exit;
$velo = canal_velo_facts();
$veloRent = canal_carte_count(['type' => 'location-de-velo']);
?>
                    <section class="meteo-block velo-block" aria-labelledby="velo-title">
                        <p class="meteo-eyebrow">Le Canal du Midi à vélo en bref</p>
                        <h2 id="velo-title">De Toulouse à l’étang de Thau par le chemin de halage</h2>
                        <ul class="velo-stats">
                            <li><b><?= esc_html($velo['km']) ?></b><span>de Toulouse à l’étang de Thau</span></li>
                            <li><b><?= esc_html($velo['time']) ?></b><span>de selle à 15 km/h</span></li>
                            <li><b><?= esc_html(str_replace('≈ ', '', $velo['days'])) ?></b><span>à 55 km par jour</span></li>
                        </ul>

                        <h3>Vos étapes en 5, 4 ou 3 jours</h3>
                        <div class="velo-plans">
                            <?php foreach (canal_velo_plans() as $days => $legs): ?>
                                <article class="meteo-season">
                                    <h3><?= (int) $days ?> jours</h3>
                                    <ol>
                                        <?php foreach ($legs as [$from, $to, $km, $time]): ?>
                                            <li><b><?= esc_html($from . ' → ' . $to) ?></b> <span><?= esc_html($km . ' · ' . $time) ?></span></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <h3>En famille : des tronçons de 10 à 25 km</h3>
                        <ul class="velo-short">
                            <?php foreach (canal_etapes_short_legs() as $leg): ?><li><?= esc_html($leg) ?></li><?php endforeach; ?>
                        </ul>

                        <h3>Quand partir ?</h3>
                        <p class="meteo-intro"><?= esc_html(canal_velo_faq(null)[2]['a']) ?> <a href="<?= esc_url(home_url('/' . CANAL_METEO_SLUG . '/')) ?>">Le climat mois par mois</a>.</p>

                        <div class="velo-actions">
                            <a class="velo-btn velo-btn--primary" href="<?= esc_url(canal_calcul_url()) ?>">Calculer une distance</a>
                            <a class="velo-btn" href="<?= esc_url(home_url(CANAL_ETAPES_PATH)) ?>">Parcours à vélo par étape</a>
                            <?php if ($veloRent): ?><a class="velo-btn" href="<?= esc_url(add_query_arg('type', 'location-de-velo', home_url(CANAL_CARTE_PATH))) ?>"><?= (int) $veloRent ?> loueur<?= $veloRent > 1 ? "s" : "" ?> de vélos</a><?php endif; ?>
                        </div>
                        <p class="meteo-source">Distances et temps : notre <a href="<?= esc_url(canal_calcul_url()) ?>">calcul de distance</a> (15 km/h, 55 km par jour) ; saisons : moyennes <?= esc_html(CANAL_METEO_YEARS) ?> de Météo-France.</p>
                    </section>
