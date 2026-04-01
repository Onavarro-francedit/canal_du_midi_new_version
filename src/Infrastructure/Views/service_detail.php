<?php
/**
 * Vista: service_detail.php (Versión con Bootstrap Icons)
 */
?>

<main class="service-page">
    <!-- 1. HERO & IDENTIDAD -->
    <section class="service-hero" style="background-image: linear-gradient(180deg, rgba(0,0,0,0.1), rgba(0,0,0,0.8)), url('<?= htmlspecialchars($service->imageUrl) ?>');">
        <div class="container">
            <div class="service-hero-content">
                <div class="taxonomy-tags">
                    <span class="pill"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($service->type) ?></span>
                    <?php if($service->isHybrid()): ?>
                        <span class="pill pill-alt"><i class="bi bi-cup-hot-fill"></i> Restaurant</span>
                    <?php endif; ?>
                </div>
                <h1><?= htmlspecialchars($service->translations['title'] ?? 'Établissement Canal du Midi') ?></h1>
                <p class="service-intro">
                    <?= htmlspecialchars($service->translations['tag'] ?: 'Une adresse pensée pour explorer le Canal du Midi avec plus de confort, de rythme et de caractère.') ?>
                </p>
                <div class="service-location-row">
                    <div class="service-location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?= htmlspecialchars($service->contact['address'] ?? 'Région Occitanie, France') ?>
                    </div>
                    <div class="service-rating-pill">
                        <i class="bi bi-stars"></i>
                        Sélection Canal du Midi
                    </div>
                </div>
                <div class="hero-facts">
                    <div class="hero-fact">
                        <span class="hero-fact-label">À partir de</span>
                        <strong><?= $service->getFormattedPrice() ?></strong>
                    </div>
                    <div class="hero-fact">
                        <span class="hero-fact-label">Capacité</span>
                        <strong><?= $service->features['rooms_count'] ?? '49' ?> chambres</strong>
                    </div>
                    <div class="hero-fact">
                        <span class="hero-fact-label">Ambiance</span>
                        <strong><?= $service->isHybrid() ? 'Séjour & restauration' : 'Escapade premium' ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. BARRA DE ACCIONES RÁPIDAS -->
    <div class="action-bar-wrapper">
        <div class="container">
            <div class="action-bar">
                <a href="tel:<?= $service->contact['phone'] ?? '' ?>" class="action-link">
                    <i class="bi bi-telephone-fill"></i>
                    <span>Appeler</span>
                </a>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $service->lat ?? '' ?>,<?= $service->lng ?? '' ?>" target="_blank" class="action-link">
                    <i class="bi bi-map-fill"></i>
                    <span>Itinéraire</span>
                </a>
                <a href="mailto:<?= $service->contact['email'] ?? '' ?>" class="action-link">
                    <i class="bi bi-envelope-fill"></i>
                    <span>Email</span>
                </a>
                <?php if(!empty($service->contact['website'])): ?>
                    <a href="<?= $service->contact['website'] ?>" target="_blank" class="action-link">
                        <i class="bi bi-globe"></i>
                        <span>Site Web</span>
                    </a>
                <?php endif; ?>
                <button onclick="window.print()" class="action-link btn-reset">
                    <i class="bi bi-share-fill"></i>
                    <span>Partager</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container service-grid">
        <!-- 3. COLUMNA IZQUIERDA -->
        <div class="service-main-content">
            <section class="section-card section-card-intro info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Présentation</span>
                    <h2>Une halte pensée pour ralentir et mieux profiter du parcours</h2>
                </div>
                <p class="description-text">
                    <?= nl2br(htmlspecialchars($service->translations['description'] ?? 'Bienvenue dans cet établissement situé sur le Canal du Midi.')) ?>
                </p>
                <div class="service-signals">
                    <div class="signal-card">
                        <i class="bi bi-water"></i>
                        <div>
                            <strong>Cadre inspirant</strong>
                            <span>Une base idéale pour rayonner le long du canal.</span>
                        </div>
                    </div>
                    <div class="signal-card">
                        <i class="bi bi-moon-stars-fill"></i>
                        <div>
                            <strong>Confort soigné</strong>
                            <span>Des services pensés pour un séjour simple et fluide.</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Oferta detallada -->
            <section class="section-card info-block offer-details">
                <div class="section-heading-inline">
                    <span class="section-kicker">L'offre</span>
                    <h3>Ce que propose l'établissement</h3>
                </div>
                <div class="offer-grid-detail">
                    <div class="offer-item">
                        <h3><i class="bi bi-door-open-fill"></i> Hébergement</h3>
                        <ul>
                            <li><i class="bi bi-info-circle"></i> <strong>Total:</strong> <?= $service->features['rooms_count'] ?? '49' ?> chambres</li>
                            <li><i class="bi bi-person-wheelchair"></i> <strong>Accessibilité:</strong> <?= $service->features['pmr_rooms'] ?? '2' ?> chambres PMR</li>
                            <li><i class="bi bi-check2-all"></i> Bureau, TV, Wifi, Plateau de courtoisie</li>
                        </ul>
                    </div>
                    <?php if($service->isHybrid()): ?>
                    <div class="offer-item">
                        <h3><i class="bi bi-egg-fried"></i> Restauration</h3>
                        <ul>
                            <li><i class="bi bi-clock"></i> Semaine (Dîners), Week-end (Snacking)</li>
                            <li><i class="bi bi-cup-straw"></i> Petit-déjeuner buffet complet</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Equipamientos -->
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Confort</span>
                    <h3>Services & Équipements</h3>
                </div>
                <div class="amenities-grid-pro">
                    <?php if (!empty($service->amenities)): ?>
                        <?php foreach ($service->amenities as $amenity): ?>
                            <div class="amenity-pro">
                                <i class="bi <?= htmlspecialchars($amenity['icon_name']) ?>"></i> 
                                <?= ucfirst(htmlspecialchars($amenity['slug'])) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Consultez l'établissement pour plus de détails sur les services.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Galería -->
           <!-- SECCIÓN DE GALERÍA -->
            <section class="section-card info-block">
                <div class="section-heading-inline">
                    <span class="section-kicker">Ambiance</span>
                    <h3>Galerie Photos</h3>
                </div>
                
                <div class="masonry-gallery">
                    <?php if (!empty($service->gallery)): ?>
                        <?php foreach ($service->gallery as $index => $photoUrl): ?>
                            <div class="gallery-item">
                                <img src="<?= htmlspecialchars($photoUrl) ?>" 
                                    alt="Photo <?= $index + 1 ?>" 
                                    class="lightbox-trigger" 
                                    data-index="<?= $index ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Si no hay fotos en la galería, mostramos la principal para que no quede vacío -->
                        <div class="gallery-item" style="grid-column: span 2; grid-row: span 2;">
                            <img src="<?= htmlspecialchars($service->imageUrl) ?>" 
                                alt="Photo principale" 
                                class="lightbox-trigger" 
                                data-index="0">
                        </div>
                    <?php endif; ?>
                </div>
            </section>

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
                <div class="section-heading-inline">
                    <span class="section-kicker">Accès</span>
                    <h3>Localisation</h3>
                </div>
                
                <!-- Contenedor del mapa con coordenadas de la DB -->
                <div id="map" 
                    class="map-container" 
                    data-lat="<?= $service->lat ?>" 
                    data-lng="<?= $service->lng ?>" 
                    data-title="<?= htmlspecialchars($service->translations['title']) ?>">
                </div>
                
                <p class="address-footer">
                    <i class="bi bi-geo-alt-fill"></i> 
                    <?= htmlspecialchars($service->contact['address'] ?? 'Adresse non spécifiée') ?>
                </p>
            </section>
        </div>

        <!-- 4. COLUMNA DERECHA -->
        <aside class="service-sidebar">
            <div class="contact-card">
                <span class="section-kicker">Informations utiles</span>
                <h3 class="sidebar-card-title">Préparez votre séjour</h3>
                <p class="sidebar-card-copy">Retrouvez les coordonnées essentielles et choisissez le canal de contact le plus rapide selon votre besoin.</p>
                <div class="contact-actions">
                    <a href="tel:<?= htmlspecialchars($service->contact['phone'] ?? '') ?>" class="contact-action-pill">
                        <i class="bi bi-telephone-fill"></i>
                        <span>Appeler</span>
                    </a>
                    <a href="mailto:<?= htmlspecialchars($service->contact['email'] ?? '') ?>" class="contact-action-pill">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Écrire</span>
                    </a>
                </div>
                <ul class="contact-list">
                    <li>
                        <i class="bi bi-telephone"></i>
                        <div>
                            <strong>Téléphone</strong>
                            <span><?= htmlspecialchars($service->contact['phone'] ?? 'Téléphone sur demande') ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-envelope"></i>
                        <div>
                            <strong>Email</strong>
                            <span><?= htmlspecialchars($service->contact['email'] ?? 'Email indisponible') ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <strong>Adresse</strong>
                            <span><?= htmlspecialchars($service->contact['address'] ?? 'Occitanie, France') ?></span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="booking-card">
                <div class="booking-header">
                    <span class="booking-kicker">Réserver un séjour</span>
                    <h3 class="booking-title">Composez votre demande en moins d'une minute</h3>
                    <span class="price-big"><?= $service->getFormattedPrice() ?></span>
                    <span class="price-label">prix moyen / nuit</span>
                </div>

                <form id="booking-form" action="<?= BASE_URL . $lang ?>/reserve" method="POST">
                    <input type="hidden" name="service_id" value="<?= $service->id ?>">
                    
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="bi bi-calendar-check"></i> Arrivée</label>
                            <input type="date" name="checkin" required>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-calendar-x"></i> Départ</label>
                            <input type="date" name="checkout" required>
                        </div>
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
                    <div class="form-group">
                        <label><i class="bi bi-tag"></i> Code Promo</label>
                        <input type="text" name="promo_code" placeholder="Saisissez votre code">
                    </div>

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
                        <i class="bi bi-send-fill"></i> Contacter l'établissement
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
    <?php include __DIR__ . '/modals/booking_acepted_modal.php'; ?>
</main>