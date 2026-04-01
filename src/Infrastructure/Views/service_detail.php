<?php
/**
 * Vista: service_detail.php
 * Recibe: 
 * - $service (Objeto de la clase Service)
 * - $lang (Idioma actual)
 */
?>

<main class="service-page">
    <!-- 1. HERO DEL SERVICIO -->
    <section class="service-hero" style="background-image: linear-gradient(180deg, rgba(0,0,0,0.1), rgba(0,0,0,0.7)), url('<?= htmlspecialchars($service->imageUrl) ?>');">
        <div class="container">
            <div class="service-hero-content">
                <span class="pill"><?= htmlspecialchars($service->translations['tag'] ?? $service->type) ?></span>
                <h1><?= htmlspecialchars($service->translations['title'] ?? 'Titre non disponible') ?></h1>
                <div class="service-location">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Expérience au fil du Canal du Midi
                </div>
            </div>
        </div>
    </section>

    <div class="container service-grid">
        <!-- 2. COLUMNA IZQUIERDA: INFORMACIÓN -->
        <div class="service-main-content">
            <section class="description-block">
                <h2>À propos de cette expérience</h2>
                <p class="description-text">
                    <?= nl2br(htmlspecialchars($service->translations['description'] ?? 'Aucune description disponible pour le moment.')) ?>
                </p>
                
                <div class="amenities-grid">
                    <div class="amenity"><span>✓</span> Support client 24/7</div>
                    <div class="amenity"><span>✓</span> Annulation flexible</div>
                    <div class="amenity"><span>✓</span> Guide local inclus</div>
                    <div class="amenity"><span>✓</span> Équipement premium</div>
                </div>
            </section>

            <section class="gallery-placeholder">
                <h3>Galerie de photos</h3>
                <div class="simple-gallery">
                    <img src="<?= htmlspecialchars($service->imageUrl ?? "") ?>" alt="Gallery 1">
                    <img src="https://images.unsplash.com/photo-1510798831971-661eb04b3739?auto=format&fit=crop&w=400&q=80" alt="Gallery 2">
                </div>
            </section>
        </div>

        <!-- 3. COLUMNA DERECHA: FORMULARIO DE RESERVA (STICKY) -->
        <aside class="service-sidebar">
            <div class="booking-card">
                <div class="booking-header">
                    <span class="price-big"><?= $service->getFormattedPrice() ?></span>
                    <span class="price-label">par séjour</span>
                </div>

                <form id="booking-form" action="/canal_du_midi/<?= $lang ?>/reserve" method="POST">
                    <input type="hidden" name="service_id" value="<?= $service->id ?>">
                    
                    <div class="form-group">
                        <label>Date de départ</label>
                        <input type="date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre de personnes</label>
                        <select name="guests">
                            <option value="1">1 Personne</option>
                            <option value="2">2 Personnes</option>
                            <option value="4">4 Personnes</option>
                            <option value="6">Plus de 6</option>
                        </select>
                    </div>

                    <button type="submit" class="button button-full">Réserver maintenant</button>
                    
                    <p class="booking-note">Vous ne serez pas débité immédiatement. Le propriétaire doit confirmer la disponibilité.</p>
                </form>

                <div class="provider-info">
                    <div class="provider-avatar"></div>
                    <div>
                        <strong>Géré par Canal du Midi</strong>
                        <span>Hôte vérifié</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</main>