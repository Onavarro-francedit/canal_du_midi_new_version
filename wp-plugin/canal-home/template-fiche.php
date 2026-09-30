<?php
/**
 * Plantilla « Fiche 2026 » — port de src/Infrastructure/Views/service_detail.php (app local).
 * WP: sin reserva, avis, equipamientos ni modales (no hay datos en WordPress). PHP 7.4.
 */
defined('ABSPATH') || exit;

$f          = canal_fiche_state();
$heroImage  = $f['hero'];
$heroSlides = array_values(array_unique(array_filter(array_merge([$heroImage], $f['gallery']))));
$hasGeo     = $f['lat'] !== null;
$latLng     = $hasGeo ? sprintf('%.6F,%.6F', $f['lat'], $f['lng']) : '';
$routeUrl   = $hasGeo ? 'https://www.google.com/maps/dir/?api=1&destination=' . $latLng : '';
$tel        = canal_fiche_tel($f['phone'] !== '' ? $f['phone'] : $f['mobile']);
$carteUrl   = home_url(CANAL_CARTE_PATH);
$address    = $f['address'] !== '' ? $f['address'] : ($f['city'] !== '' ? $f['city'] . ' — Canal du Midi' : 'Canal du Midi, Occitanie');
$phones     = array_filter(['Téléphone' => $f['phone'], 'Mobile' => $f['mobile'], 'Fax' => $f['fax']]);
$socialIcon = ['facebook' => 'bi-facebook', 'instagram' => 'bi-instagram', 'youtube' => 'bi-youtube'];
$socialName = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'];

get_header();
?>
<div class="cdm-fiche">
<main class="service-page">
    <!-- 1. HERO -->
    <section class="service-hero" id="service-hero" data-gallery="<?= esc_attr(wp_json_encode($heroSlides)) ?>">
        <div class="service-hero-bg service-hero-bg--a is-visible" style="background-image: <?= canal_home_css_url($heroImage) ?>;"></div>
        <div class="service-hero-bg service-hero-bg--b"></div>
        <div class="container">
            <div class="service-hero-content">
                <h1><?= esc_html($f['title']) ?></h1>
                <div class="service-location-row">
                    <div class="service-location"><i class="bi bi-geo-alt-fill"></i> <?= esc_html($address) ?></div>
                    <?php if ($f['zones']): ?>
                        <div class="service-rating-pill"><i class="bi bi-map"></i> <?= esc_html(implode(' · ', $f['zones'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($f['categories']): ?>
                    <div class="hero-facts">
                        <?php foreach (array_slice($f['categories'], 0, 3) as $cat): ?>
                            <div class="hero-fact"><i class="bi bi-check2"></i> <strong><?= esc_html($cat['name']) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 2. ACTION BAR -->
    <div class="action-bar-wrapper">
        <div class="container">
            <div class="action-bar">
                <?php if ($tel !== ''): ?>
                    <a href="<?= esc_url('tel:' . $tel) ?>" class="action-link"><i class="bi bi-telephone-fill"></i> <span>Appeler</span></a>
                <?php endif; ?>
                <?php if ($hasGeo): ?>
                    <a href="<?= esc_url($routeUrl) ?>" target="_blank" rel="noopener" class="action-link"><i class="bi bi-map-fill"></i> <span>Itinéraire</span></a>
                <?php endif; ?>
                <?php if ($f['email'] !== ''): ?>
                    <a href="<?= esc_url('mailto:' . $f['email']) ?>" class="action-link"><i class="bi bi-envelope-fill"></i> <span>Email</span></a>
                <?php endif; ?>
                <?php if ($f['website'] !== ''): ?>
                    <a href="<?= esc_url($f['website']) ?>" target="_blank" rel="noopener" class="action-link"><i class="bi bi-globe"></i> <span>Site Web</span></a>
                <?php endif; ?>
                <?php foreach ($f['social'] as $net => $url): ?>
                    <a href="<?= esc_url($url) ?>" target="_blank" rel="noopener" class="action-link"><i class="bi <?= esc_attr($socialIcon[$net]) ?>"></i> <span><?= esc_html($socialName[$net]) ?></span></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container service-grid">
        <div class="service-main-content">
            <!-- 3. PRÉSENTATION -->
            <section class="section-card section-card-intro info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Présentation</span>
                    <h2>À propos de <?= esc_html($f['title']) ?></h2>
                </div>
                <?php if (trim(wp_strip_all_tags($f['description_html'])) !== ''): ?>
                    <div class="description-text"><?= $f['description_html'] // WP: ya pasado por wp_kses_post ?></div>
                <?php endif; ?>
                <?php if ($f['modified'] !== ''): ?>
                    <p class="fiche-publisher">Fiche éditée par <?= esc_html(CANAL_HOME_SITE_NAME) ?> · mise à jour le <time datetime="<?= esc_attr($f['modified']) ?>"><?= esc_html(date_i18n('j F Y', strtotime($f['modified']))) ?></time></p>
                <?php endif; ?>
            </section>

            <!-- 4. CATÉGORIES (WP: enlazan a la carte filtrada) -->
            <?php if ($f['categories']): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Activités</span>
                    <h3>Catégories &amp; Services</h3>
                </div>
                <div class="categories-grid">
                    <?php foreach ($f['categories'] as $cat): ?>
                        <a class="category-tag" href="<?= esc_url(add_query_arg('type', $cat['slug'], $carteUrl)) ?>"><i class="bi bi-check2"></i> <?= esc_html($cat['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 5. VIDÉO (WP: URL ya validada contra la allowlist en canal_fiche_video_embed) -->
            <?php if ($f['video'] !== ''): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Vidéo</span>
                    <h3>Découvrir en images</h3>
                </div>
                <div class="videos-grid">
                    <div class="video-embed">
                        <iframe src="<?= esc_url($f['video']) ?>" title="<?= esc_attr('Vidéo — ' . $f['title']) ?>" allowfullscreen loading="lazy"
                            sandbox="allow-scripts allow-same-origin allow-presentation"></iframe>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 6. GALERIE -->
            <?php if ($f['gallery']): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Ambiance</span>
                    <h3>Galerie Photos</h3>
                </div>
                <div class="masonry-gallery">
                    <?php foreach ($f['gallery_imgs'] as $i => $photo): ?>
                        <div class="gallery-item">
                            <img src="<?= esc_url($photo['image']) ?>"<?php if ($photo['image_srcset'] !== ''): ?> srcset="<?= esc_attr($photo['image_srcset']) ?>" sizes="(max-width: 640px) 100vw, 460px"<?php endif; ?><?php if (!empty($photo['image_w'])): ?> width="<?= (int) $photo['image_w'] ?>" height="<?= (int) $photo['image_h'] ?>"<?php endif; ?>
                                alt="<?= esc_attr($f['title'] . ' — photo ' . ($i + 1)) ?>" class="lightbox-trigger" data-index="<?= (int) $i ?>" data-full="<?= esc_url($f['gallery'][$i]) ?>" loading="lazy" decoding="async">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 7. LOCALISATION + « Autour de ce lieu » (WP: fichas cercanas en lugar de POIs) -->
            <?php if ($hasGeo): ?>
            <section class="section-card info-block map-section">
                <div class="location-hero">
                    <div class="section-heading-inline location-heading">
                        <span class="section-kicker">Accès</span>
                        <h3>Localisation</h3>
                        <p class="location-intro">Repérez l'établissement en un coup d'œil et découvrez les prestataires voisins le long du Canal du Midi, <a href="<?= esc_url(CANAL_FICHE_UNESCO_URL) ?>" target="_blank" rel="noopener">inscrit au patrimoine mondial de l'UNESCO</a> et géré par <a href="<?= esc_url(CANAL_FICHE_VNF_URL) ?>" target="_blank" rel="noopener">Voies Navigables de France</a>.</p>
                    </div>
                    <a href="<?= esc_url($routeUrl) ?>" target="_blank" rel="noopener" class="location-route-link"><i class="bi bi-sign-turn-right-fill"></i> Ouvrir l'itinéraire</a>
                </div>
                <div class="location-layout">
                    <div class="location-map-shell">
                        <div id="map" class="map-container" data-lat="<?= esc_attr(sprintf('%.6F', $f['lat'])) ?>" data-lng="<?= esc_attr(sprintf('%.6F', $f['lng'])) ?>" data-title="<?= esc_attr($f['title']) ?>"></div>
                        <div class="location-address-card">
                            <span class="location-address-label">Adresse de l'établissement</span>
                            <p class="address-footer"><i class="bi bi-geo-alt-fill"></i> <?= esc_html($address) ?></p>
                        </div>
                    </div>
                    <?php if ($f['nearby']): ?>
                    <div class="location-nearby-panel">
                        <div class="location-nearby-header">
                            <span class="section-kicker">À proximité</span>
                            <h4>Autour de ce lieu</h4>
                            <p>Les prestataires les plus proches, à pied, à vélo ou en bateau.</p>
                        </div>
                        <div class="poi-grid">
                            <?php foreach ($f['nearby'] as $n): ?>
                                <a href="<?= esc_url(canal_fiche_url($n['slug'])) ?>" class="poi-link">
                                    <div class="poi-item poi-hover-trigger" data-lat="<?= esc_attr(sprintf('%.6F', $n['lat'])) ?>" data-lng="<?= esc_attr(sprintf('%.6F', $n['lng'])) ?>" data-name="<?= esc_attr($n['title']) ?>">
                                        <div class="poi-image-container">
                                            <?php if (($n['image'] ?? '') !== ''): ?>
                                                <img src="<?= esc_url(canal_fiche_https($n['image'])) ?>"<?php if (($n['image_srcset'] ?? '') !== ''): ?> srcset="<?= esc_attr($n['image_srcset']) ?>" sizes="56px"<?php endif; ?> width="56" height="56" alt="" class="poi-thumb" loading="lazy" decoding="async">
                                            <?php else: ?>
                                                <div class="poi-icon-fallback"><i class="bi bi-geo-alt"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="poi-info">
                                            <strong><?= esc_html($n['title']) ?></strong>
                                            <span><?= esc_html($n['type'] ?? '') ?></span>
                                        </div>
                                        <div class="poi-distance"><?= esc_html(canal_fiche_km_label($n['distance_km'])) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 8. QUESTIONS FRÉQUENTES (WP: datos reales de la ficha, AEO + FAQPage) -->
            <?php if ($f['faq']): ?>
            <section class="section-card info-block fiche-faq">
                <div class="section-heading-inline">
                    <span class="section-kicker">En bref</span>
                    <h2>Questions fréquentes</h2>
                </div>
                <?php foreach ($f['faq'] as $qa): ?>
                    <div class="fiche-faq-item">
                        <h3><?= esc_html($qa['q']) ?></h3>
                        <p><?= esc_html($qa['a']) ?></p>
                    </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        </div>

        <!-- COLUMNA DERECHA: coordonnées (WP: sin tarjeta de reserva) -->
        <aside class="service-sidebar">
            <div class="contact-card">
                <span class="section-kicker">Informations utiles</span>
                <h3 class="sidebar-card-title">Coordonnées</h3>
                <p class="sidebar-card-copy">Contactez directement l'établissement pour toute demande d'information ou de réservation.</p>
                <?php if ($tel !== '' || $f['email'] !== ''): ?>
                <div class="contact-actions">
                    <?php if ($tel !== ''): ?>
                        <a href="<?= esc_url('tel:' . $tel) ?>" class="contact-action-pill"><i class="bi bi-telephone-fill"></i> <span>Appeler</span></a>
                    <?php endif; ?>
                    <?php if ($f['email'] !== ''): ?>
                        <a href="<?= esc_url('mailto:' . $f['email']) ?>" class="contact-action-pill"><i class="bi bi-envelope-fill"></i> <span>Écrire</span></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <ul class="contact-list">
                    <?php foreach ($phones as $label => $number): ?>
                        <li><i class="bi <?= $label === 'Fax' ? 'bi-printer' : ($label === 'Mobile' ? 'bi-phone' : 'bi-telephone') ?>"></i>
                            <div><strong><?= esc_html($label) ?></strong><span><?= esc_html($number) ?></span></div></li>
                    <?php endforeach; ?>
                    <?php if ($f['email'] !== ''): ?>
                        <li><i class="bi bi-envelope"></i><div><strong>Email</strong><span><?= esc_html($f['email']) ?></span></div></li>
                    <?php endif; ?>
                    <li><i class="bi bi-geo-alt"></i><div><strong>Adresse</strong><span><?= esc_html($address) ?></span></div></li>
                    <?php if ($f['website'] !== ''): ?>
                        <li><i class="bi bi-globe"></i><div><strong>Site web</strong>
                            <a href="<?= esc_url($f['website']) ?>" target="_blank" rel="noopener"><?= esc_html((string) (wp_parse_url($f['website'], PHP_URL_HOST) ?: $f['website'])) ?></a></div></li>
                    <?php endif; ?>
                </ul>
                <?php if ($f['social']): ?>
                <div class="social-links">
                    <?php foreach ($f['social'] as $net => $url): ?>
                        <a href="<?= esc_url($url) ?>" target="_blank" rel="noopener" class="social-link" aria-label="<?= esc_attr($socialName[$net]) ?>"><i class="bi <?= esc_attr($socialIcon[$net]) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <a class="button button-ghost button-full fiche-back-carte" href="<?= esc_url($carteUrl) ?>"><i class="bi bi-map"></i> Voir tous les prestataires sur la carte</a>
        </aside>
    </div>

    <?php if ($f['gallery']): ?>
    <div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Galerie photos">
        <button type="button" class="lightbox-close" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
        <button type="button" class="lightbox-prev" aria-label="Précédent"><i class="bi bi-chevron-left"></i></button>
        <div class="lightbox-content"><img id="lightbox-img" src="" alt=""></div>
        <button type="button" class="lightbox-next" aria-label="Suivant"><i class="bi bi-chevron-right"></i></button>
    </div>
    <?php endif; ?>
</main>
</div>
<?php
get_footer();
