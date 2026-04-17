<?php
/**
 * Vista: service_detail.php — Compatible Pimcore object_query_60 + Services originales
 */
$isPimcore = !empty($service->categories);
$activeCategories = $isPimcore ? $service->getActiveCategories() : [];
$activeEquipments = $isPimcore ? $service->getActiveEquipments() : [];
$fullAddress = $isPimcore ? $service->getFullAddress() : ($service->contact['address'] ?? 'Occitanie, France');

// Icons mapping for categories
$categoryIcons = [
    'Hébergement' => 'bi-house-door', 'Résidence de tourisme' => 'bi-building', 'Hôtellerie plein air' => 'bi-tree',
    'Chambre d\'hôtes' => 'bi-door-open', 'Gîtes ruraux' => 'bi-house-heart', 'Hôtel' => 'bi-building',
    'Location saisonnière' => 'bi-key', 'Bar / Salon de thé' => 'bi-cup-hot', 'Restaurant' => 'bi-egg-fried',
    'Table d\'hôtes' => 'bi-palette', 'Bateau restaurant' => 'bi-water', 'Snack' => 'bi-bag',
    'Brasserie' => 'bi-cup-straw', 'Alimentation' => 'bi-basket3', 'Boulangerie' => 'bi-basket2',
    'Produits régionaux' => 'bi-box-seam', 'Épicerie' => 'bi-cart4', 'Vente de vins' => 'bi-droplet',
    'Caviste' => 'bi-droplet-half', 'Domaine' => 'bi-globe-europe-africa', 'Loisirs' => 'bi-controller',
    'Croisière avec chambres' => 'bi-water', 'Promenade en bateau' => 'bi-water', 'Croisière de luxe' => 'bi-gem',
    'Location bateaux' => 'bi-life-preserver', 'Location vélos' => 'bi-bicycle', 'Voyage à vélo' => 'bi-bicycle',
    'Excursions' => 'bi-compass', 'Musée' => 'bi-bank', 'Parc de loisirs' => 'bi-tree',
    'Lieux à voir' => 'bi-eye', 'Artisanat' => 'bi-brush', 'Commerce' => 'bi-shop',
    'Taxi' => 'bi-taxi-front', 'Office de tourisme' => 'bi-info-circle', 'Librairie' => 'bi-book',
    'Location kayak' => 'bi-water', 'Location voiture' => 'bi-car-front',
];

$equipmentIcons = [
    'Animaux acceptés' => 'bi-emoji-smile', 'Piscine' => 'bi-water', 'Parking' => 'bi-p-square', 'Accessible PMR' => 'bi-person-wheelchair',
];
?>
<?php if (isset($_GET['review_success'])): ?>
    <div class="alert alert-success">
        <i class="bi bi-stars"></i> Merci ! Votre avis a été publié avec succès.
    </div>
<?php endif; ?>
<main class="service-page">
    <!-- 1. HERO -->
    <section class="service-hero" style="background-image: linear-gradient(180deg, rgba(0,0,0,0.1), rgba(0,0,0,0.78)), url('<?= htmlspecialchars($service->imageUrl ?: 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=1600&q=80') ?>');">
        <div class="container">
            <div class="service-hero-content">
                <div class="taxonomy-tags">
                    <span class="pill"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($service->type) ?></span>
                    <?php if (!empty($service->label)): ?>
                        <span class="pill pill-alt"><i class="bi bi-award-fill"></i> <?= htmlspecialchars($service->label) ?></span>
                    <?php elseif ($service->isHybrid()): ?>
                        <span class="pill pill-alt"><i class="bi bi-cup-hot-fill"></i> Restaurant</span>
                    <?php endif; ?>
                </div>
                <h1><?= htmlspecialchars($service->translations['title'] ?? 'Établissement Canal du Midi') ?></h1>
                <?php if (!empty($service->raison) && $service->raison !== $service->translations['title']): ?>
                    <p class="service-raison"><?= htmlspecialchars($service->raison) ?></p>
                <?php endif; ?>
                <div class="service-location-row">
                    <div class="service-location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?= htmlspecialchars($fullAddress) ?>
                    </div>
                    <?php if (!empty($service->zone)): ?>
                        <div class="service-rating-pill">
                            <i class="bi bi-map"></i> Zone <?= htmlspecialchars($service->zone) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($isPimcore && !empty($activeCategories)): ?>
                    <div class="hero-facts">
                        <?php $shown = 0; foreach ($activeCategories as $catName => $v): if ($shown >= 3) break; ?>
                            <div class="hero-fact">
                                <i class="bi <?= $categoryIcons[$catName] ?? 'bi-check2' ?>"></i>
                                <strong><?= htmlspecialchars($catName) ?></strong>
                            </div>
                        <?php $shown++; endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="hero-facts">
                        <div class="hero-fact">
                            <span class="hero-fact-label">À partir de</span>
                            <strong><?= $service->getFormattedPrice() ?></strong>
                        </div>
                        <div class="hero-fact">
                            <span class="hero-fact-label">Capacité</span>
                            <strong><?= $service->features['rooms_count'] ?? '—' ?> chambres</strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 2. ACTION BAR -->
    <div class="action-bar-wrapper">
        <div class="container">
            <div class="action-bar">
                <?php if (!empty($service->contact['phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($service->contact['phone']) ?>" class="action-link">
                        <i class="bi bi-telephone-fill"></i> <span>Appeler</span>
                    </a>
                <?php endif; ?>
                <?php if ($service->lat && $service->lng): ?>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $service->lat ?>,<?= $service->lng ?>" target="_blank" class="action-link">
                        <i class="bi bi-map-fill"></i> <span>Itinéraire</span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($service->contact['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($service->contact['email']) ?>" class="action-link">
                        <i class="bi bi-envelope-fill"></i> <span>Email</span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($service->contact['website'])): ?>
                    <a href="<?= htmlspecialchars($service->contact['website']) ?>" target="_blank" rel="noopener" class="action-link">
                        <i class="bi bi-globe"></i> <span>Site Web</span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($service->socials['facebook'])): ?>
                    <a href="<?= htmlspecialchars($service->socials['facebook']) ?>" target="_blank" rel="noopener" class="action-link">
                        <i class="bi bi-facebook"></i> <span>Facebook</span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($service->socials['instagram'])): ?>
                    <a href="<?= htmlspecialchars($service->socials['instagram']) ?>" target="_blank" rel="noopener" class="action-link">
                        <i class="bi bi-instagram"></i> <span>Instagram</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container service-grid">
        <!-- 3. COLUMNA IZQUIERDA -->
        <div class="service-main-content">
            <!-- 3. PRÉSENTATION -->
            <section class="section-card section-card-intro info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Présentation</span>
                    <h2><?= htmlspecialchars($service->translations['title'] ?? '') ?></h2>
                </div>
                <?php if (!empty($service->translations['description'])): ?>
                    <div class="description-text">
                        <?= nl2br(htmlspecialchars($service->translations['description'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($service->responsable)): ?>
                    <div class="service-signals">
                        <div class="signal-card">
                            <i class="bi bi-person-badge"></i>
                            <div>
                                <strong>Responsable</strong>
                                <span><?= htmlspecialchars($service->responsable) ?></span>
                            </div>
                        </div>
                        <?php if (!empty($service->label)): ?>
                        <div class="signal-card">
                            <i class="bi bi-award"></i>
                            <div>
                                <strong>Label qualité</strong>
                                <span><?= htmlspecialchars($service->label) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 4. CATÉGORIES -->
            <?php if (!empty($activeCategories)): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Activités</span>
                    <h3>Catégories & Services</h3>
                </div>
                <div class="categories-grid">
                    <?php foreach ($activeCategories as $catName => $v): ?>
                        <div class="category-tag">
                            <i class="bi <?= $categoryIcons[$catName] ?? 'bi-check2' ?>"></i>
                            <?= htmlspecialchars($catName) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 5. ÉQUIPEMENTS -->
            <?php if (!empty($activeEquipments)): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Confort</span>
                    <h3>Équipements</h3>
                </div>
                <div class="amenities-grid-pro">
                    <?php foreach ($activeEquipments as $eqName => $v): ?>
                        <div class="amenity-pro">
                            <i class="bi <?= $equipmentIcons[$eqName] ?? 'bi-check-circle' ?>"></i>
                            <?= htmlspecialchars($eqName) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php elseif (!$isPimcore && !empty($service->amenities)): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Confort</span>
                    <h3>Services & Équipements</h3>
                </div>
                <div class="amenities-grid-pro">
                    <?php foreach ($service->amenities as $amenity): ?>
                        <div class="amenity-pro">
                            <i class="bi <?= htmlspecialchars($amenity['icon_name']) ?>"></i> 
                            <?= ucfirst(htmlspecialchars($amenity['slug'])) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 6. ACTUALITÉ -->
            <?php if (!empty($service->actualite)): ?>
            <section class="section-card info-block actualite-section">
                <div class="section-heading-inline">
                    <span class="section-kicker">Actualité</span>
                    <h3>Dernières nouvelles</h3>
                </div>
                <div class="description-text actualite-text">
                    <?= nl2br(htmlspecialchars($service->actualite)) ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 7. VIDÉOS -->
            <?php if (!empty($service->videos)): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Vidéo</span>
                    <h3>Découvrir en images</h3>
                </div>
                <div class="videos-grid">
                    <?php foreach ($service->videos as $video): ?>
                        <div class="video-embed">
                            <?= $video ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            </section>

            <!-- 8. GALERIE -->
            <?php if (!empty($service->gallery)): ?>
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Ambiance</span>
                    <h3>Galerie Photos</h3>
                </div>
                <div class="masonry-gallery">
                    <?php foreach ($service->gallery as $index => $photoUrl): ?>
                        <div class="gallery-item">
                            <img src="<?= htmlspecialchars($photoUrl) ?>" 
                                alt="Photo <?= $index + 1 ?>" 
                                class="lightbox-trigger" 
                                data-index="<?= $index ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

           

            <!-- MOVER ESTO AL FINAL DEL ARCHIVO (antes del </main>) -->
            <div id="lightbox" class="lightbox">
                <span class="lightbox-close"><i class="bi bi-x-lg"></i></span>
                
                <button class="lightbox-prev" aria-label="Précédent">
                    <i class="bi bi-chevron-left"></i>
                </button>
                
                <div class="lightbox-content">
                    <img id="lightbox-img" src="" alt="Vue agrandie">
                </div>
                
                <button class="lightbox-next" aria-label="Suivant">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <!-- Localización -->
            <section class="section-card info-block map-section">
                <div class="location-hero">
                    <div class="section-heading-inline location-heading">
                        <span class="section-kicker">Accès</span>
                        <h3>Localisation</h3>
                        <p class="location-intro">Repérez l'établissement en un coup d'oeil et identifiez les étapes, haltes et services accessibles à proximité du Canal du Midi.</p>
                    </div>

                    <a
                        href="https://www.google.com/maps/dir/?api=1&destination=<?= $service->lat ?? '' ?>,<?= $service->lng ?? '' ?>"
                        target="_blank"
                        rel="noreferrer"
                        class="location-route-link"
                    >
                        <i class="bi bi-sign-turn-right-fill"></i>
                        Ouvrir l'itinéraire
                    </a>
                </div>

                <div class="location-layout">
                    <div class="location-map-shell">
                        <div id="map" 
                            class="map-container" 
                            data-lat="<?= $service->lat ?>" 
                            data-lng="<?= $service->lng ?>" 
                            data-title="<?= htmlspecialchars($service->translations['title']) ?>">
                        </div>

                        <div class="location-address-card">
                            <span class="location-address-label">Adresse de l'établissement</span>
                            <p class="address-footer">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($fullAddress) ?>
                            </p>
                        </div>
                    </div>

                    <div class="location-nearby-panel">
                        <div class="location-nearby-header">
                            <span class="section-kicker">Emplacement</span>
                            <h4>À proximité du Canal</h4>
                            <p>Les points d'intérêt accessibles rapidement depuis votre séjour.</p>
                        </div>

                        <div class="poi-grid">
                            <?php if (!empty($service->nearbyPOIs)): ?>
                                <?php foreach ($service->nearbyPOIs as $poi): ?>
                                    <a href="<?= BASE_URL . $lang ?>/poi/<?= $poi->id ?>" class="poi-link">
                                        <div class="poi-item poi-hover-trigger" 
                                            data-lat="<?= $poi->lat ?>" 
                                            data-lng="<?= $poi->lng ?>" 
                                            data-name="<?= htmlspecialchars($poi->name) ?>">
                                            
                                            <div class="poi-image-container">
                                                <?php if ($poi->imageUrl): ?>
                                                    <img src="<?= $poi->imageUrl ?>" alt="<?= $poi->name ?>" class="poi-thumb">
                                                <?php else: ?>
                                                    <div class="poi-icon-fallback"><i class="bi <?= $poi->getIcon() ?>"></i></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="poi-info">
                                                <strong><?= htmlspecialchars($poi->name) ?></strong>
                                                <span><?= ucfirst($poi->type) ?></span>
                                            </div>
                                            
                                            <div class="poi-distance"><?= $poi->getFormattedDistance() ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="poi-empty-state">
                                    <i class="bi bi-bicycle"></i>
                                    <p>Découvrez les merveilles du Canal du Midi à quelques minutes en vélo ou en bateau.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>


             <!-- SECCIÓN DE RESEÑAS -->
            <section class="section-card info-block reviews-container">
                <?php $initialReviews = array_slice($service->reviews, 0, 3); ?>
                <div class="reviews-hero">
                    <div class="section-heading-inline reviews-heading">
                        <span class="section-kicker">Avis clients</span>
                        <h3>Ce que disent les voyageurs</h3>
                        <p class="reviews-intro">Des retours utiles pour se projeter avant de réserver, basés sur des séjours réellement effectués.</p>
                    </div>

                    <div class="reviews-summary-card reviews-summary-card-wide">
                        <div class="reviews-summary-score">
                            <span class="reviews-summary-label">Note moyenne</span>
                            <div class="avg-number"><?= number_format((float) $service->avgRating, 1) ?></div>
                        </div>
                        <div class="reviews-summary-meta">
                            <div class="avg-stars">
                                <?php 
                                for($i=1; $i<=5; $i++) echo ($i <= round($service->avgRating)) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                ?>
                            </div>
                            <div class="reviews-summary-copy">
                                <strong><?= (int) $service->reviewCount ?> avis vérifiés</strong>
                                <span>Expérience globale saluée pour le confort, l'accueil et l'emplacement.</span>
                            </div>
                        </div>
                        <div class="reviews-summary-highlights">
                            <span class="reviews-highlight-pill"><i class="bi bi-patch-check"></i> Avis publiés après validation</span>
                            <span class="reviews-highlight-pill"><i class="bi bi-chat-quote"></i> Derniers retours affichés en priorité</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($service->reviews)): ?>
                    <div id="reviews-items-wrapper" class="reviews-list">
                        <?php foreach ($initialReviews as $review): ?>
                            <?php include __DIR__ . '/components/review_item.php'; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="reviews-footer">
                        <p class="reviews-progress">
                            <span id="reviews-loaded-count"><?= count($initialReviews) ?></span>
                            sur
                            <span><?= (int) $service->reviewCount ?></span>
                            avis affichés
                        </p>

                        <?php if ($service->reviewCount > count($initialReviews)): ?>
                            <button
                                id="load-more-reviews"
                                class="button button-ghost reviews-load-more"
                                data-page="1"
                                data-sid="<?= $service->id ?>"
                                data-total="<?= (int) $service->reviewCount ?>"
                            >
                                <i class="bi bi-plus-circle"></i> Voir plus d'avis
                            </button>
                        <?php endif; ?>

                        <p id="reviews-feedback" class="reviews-feedback" aria-live="polite"></p>
                    </div>
                <?php else: ?>
                    <div class="reviews-empty-state">
                        <i class="bi bi-chat-heart"></i>
                        <strong>Aucun avis publié pour le moment</strong>
                        <span>Les premiers retours apparaîtront ici dès qu'un voyageur partagera son expérience.</span>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- 4. COLUMNA DERECHA -->
        <aside class="service-sidebar">
            <div class="contact-card">
                <span class="section-kicker">Informations utiles</span>
                <h3 class="sidebar-card-title">Coordonnées</h3>
                <p class="sidebar-card-copy">Contactez directement l'établissement pour toute demande d'information ou de réservation.</p>
                <div class="contact-actions">
                    <?php if (!empty($service->contact['phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($service->contact['phone']) ?>" class="contact-action-pill">
                        <i class="bi bi-telephone-fill"></i>
                        <span>Appeler</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($service->contact['email']) ?>" class="contact-action-pill">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Écrire</span>
                    </a>
                    <?php endif; ?>
                </div>
                <ul class="contact-list">
                    <?php if (!empty($service->contact['phone'])): ?>
                    <li>
                        <i class="bi bi-telephone"></i>
                        <div>
                            <strong>Téléphone</strong>
                            <span><?= htmlspecialchars($service->contact['phone']) ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['phone2'])): ?>
                    <li>
                        <i class="bi bi-telephone"></i>
                        <div>
                            <strong>Tél. secondaire</strong>
                            <span><?= htmlspecialchars($service->contact['phone2']) ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['mobile'])): ?>
                    <li>
                        <i class="bi bi-phone"></i>
                        <div>
                            <strong>Mobile</strong>
                            <span><?= htmlspecialchars($service->contact['mobile']) ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['fax'])): ?>
                    <li>
                        <i class="bi bi-printer"></i>
                        <div>
                            <strong>Fax</strong>
                            <span><?= htmlspecialchars($service->contact['fax']) ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['email'])): ?>
                    <li>
                        <i class="bi bi-envelope"></i>
                        <div>
                            <strong>Email</strong>
                            <span><?= htmlspecialchars($service->contact['email']) ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['email2'])): ?>
                    <li>
                        <i class="bi bi-envelope"></i>
                        <div>
                            <strong>Email secondaire</strong>
                            <span><?= htmlspecialchars($service->contact['email2']) ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <li>
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <strong>Adresse</strong>
                            <span><?= htmlspecialchars($fullAddress) ?></span>
                        </div>
                    </li>
                    <?php if (!empty($service->contact['website'])): ?>
                    <li>
                        <i class="bi bi-globe"></i>
                        <div>
                            <strong>Site web</strong>
                            <a href="<?= htmlspecialchars($service->contact['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(parse_url($service->contact['website'], PHP_URL_HOST) ?: $service->contact['website']) ?></a>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($service->contact['website2'])): ?>
                    <li>
                        <i class="bi bi-globe2"></i>
                        <div>
                            <strong>Site alternatif</strong>
                            <a href="<?= htmlspecialchars($service->contact['website2']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(parse_url($service->contact['website2'], PHP_URL_HOST) ?: $service->contact['website2']) ?></a>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($service->socials['facebook']) || !empty($service->socials['instagram'])): ?>
                <div class="social-links">
                    <?php if (!empty($service->socials['facebook'])): ?>
                    <a href="<?= htmlspecialchars($service->socials['facebook']) ?>" target="_blank" rel="noopener" class="social-link">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($service->socials['instagram'])): ?>
                    <a href="<?= htmlspecialchars($service->socials['instagram']) ?>" target="_blank" rel="noopener" class="social-link">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="booking-card">
                <div class="booking-header">
                    <span class="booking-kicker">Réserver un séjour</span>
                    <h3 class="booking-title">Composez votre demande en moins d'une minute</h3>
                    <span class="price-big"><?= $service->getFormattedPrice() ?></span>
                    <span class="price-label">prix moyen / nuit</span>
                </div>

                <form id="booking-form" action="<?= BASE_URL . $lang ?>/reserve" method="POST">

                    
                    
                    <div class="form-group">
                        <label><i class="bi bi-calendar-range"></i> Dates du séjour</label>
                        <button type="button" id="select-dates-trigger" class="button button-ghost button-full">
                            <i class="bi bi-calendar-range"></i>Sélectionner les dates
                        </button>
                        <!-- Inputs ocultos que el calendario llenará -->
                        <input type="hidden" name="service_id" value="<?= $service->id ?>">
                        <input type="hidden" name="checkin" required>
                        <input type="hidden" name="checkout" required>
                    </div>

                    <!-- SECCIÓN: PASAJEROS -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="bi bi-person-fill"></i> Adultes</label>
                            <input type="number" name="adults" value="1" min="1" aria-label="Nombre d'adultes">
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-children"></i> Enfants</label>
                            <input type="number" name="children" value="0" min="0" aria-label="Nombre d'enfants">
                        </div>
                    </div>

                    <!-- SECCIÓN: ACCESIBILIDAD / CONDICIONES ESPECIALES -->
                    <div class="form-group special-conditions">
                        <label>Besoins spécifiques</label>
                        <p class="form-helper">Indiquez tout besoin particulier pour recevoir une réponse plus précise.</p>
                        <div class="checkbox-group-grid">
                            <label class="checkbox-card">
                                <input type="checkbox" name="has_disabled" value="1">
                                <i class="bi bi-person-wheelchair"></i>
                                <span>PMR</span>
                            </label>
                            <label class="checkbox-card">
                                <input type="checkbox" name="is_pregnant" value="1">
                                <i class="bi bi-person-arms-up"></i>
                                <span>Enceinte</span>
                            </label>
                        </div>
                    </div>

                    <!-- SECCIÓN: CÓDIGO PROMO -->
                    <div class="promo-section">
                        <!-- Enlace inicial oculto -->
                        <button type="button" id="show-promo-link" class="link-button">
                            <i class="bi bi-tag"></i> Vous avez un code promo ?
                        </button>

                        <!-- Contenedor del input (oculto por CSS inicialmente) -->
                        <div id="promo-input-wrapper" class="promo-input-group is-hidden">
                            <input type="text" id="promo-code-input" name="promo_code_temp" placeholder="Entrez votre code">
                            <button type="button" id="apply-promo-btn" class="button button-small">Appliquer</button>
                        </div>

                        <!-- Feedback del código (Éxito o Error) -->
                        <div id="promo-message" class="promo-feedback"></div>
                        
                        <!-- Input oculto final para enviar en el formulario de reserva -->
                        <input type="hidden" name="promo_code" id="final-promo-code" value="">
                    </div>
                    <br>

                    <div class="form-group">
                        <label><i class="bi bi-envelope"></i> Votre Email</label>
                        <input type="email" name="customer_email" placeholder="email@exemple.com" required>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-plus-circle"></i> Options souhaitées</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="opt_rest" class="opt_rest"> Restaurant</label>
                            <label><input type="checkbox" name="opt_parking" class="opt_parking"> Parking</label>
                        </div>
                    </div>

                    <button type="submit" class="button button-full">
                        <i class="bi bi-calendar-check"></i> Réserver mon séjour
                    </button>
                    <p class="booking-disclaimer">En envoyant ce formulaire, vous demandez une prise de contact directe avec l'établissement, sans paiement immédiat.</p>
                </form>

                <div class="booking-trust-list">
                    <span><i class="bi bi-check2-circle"></i> Réponse rapide de l'établissement</span>
                    <span><i class="bi bi-check2-circle"></i> Contact direct sans intermédiaire</span>
                </div>

                <div class="provider-info">
                    <div class="provider-avatar"><i class="bi bi-person-vcard"></i></div>
                    <div>
                        <strong><?= htmlspecialchars($service->translations['title']) ?></strong>
                        <span>Établissement Partenaire</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
    
    <?php include __DIR__ . '/modals/calendar_modal.php'; ?>
    <?php include __DIR__ . '/modals/booking_summary_modal.php'; ?>
    <?php include __DIR__ . '/modals/booking_acepted_modal.php'; ?>
</main>