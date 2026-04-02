<main class="poi-page">
    <!-- 1. HERO POI -->
    <section class="poi-hero" style="background-image: linear-gradient(180deg, rgba(0,0,0,0.1), rgba(0,0,0,0.8)), url('<?= $poi->imageUrl ?>');">
        <div class="container">
            <div class="poi-hero-content">
                <span class="pill"><i class="bi bi-geo-fill"></i> Patrimoine</span>
                <h1><?= htmlspecialchars($poi->name) ?></h1>
                <div class="poi-meta-row">
                    <span class="badge"><i class="bi bi-water"></i> Canal du Midi</span>
                    <span class="badge"><i class="bi <?= $poi->getIcon() ?>"></i> <?= ucfirst($poi->type) ?></span>
                </div>
            </div>
        </div>
    </section>

    <div class="container poi-grid-layout">
        <!-- 2. CONTENIDO EDITORIAL -->
        <div class="poi-main-content">
            <section class="section-card">
                <div class="section-heading-inline">
                    <span class="section-kicker">Histoire & Infos</span>
                    <h2>Découvrez ce lieu d'exception</h2>
                </div>
                <p class="description-text">
                    <?= nl2br(htmlspecialchars($poi->description ?? 'Informations en cours de rédaction.')) ?>
                </p>
                <div id="map" class="map-container-small" 
                     data-lat="<?= $poi->lat ?>" 
                     data-lng="<?= $poi->lng ?>" 
                     data-title="<?= htmlspecialchars($poi->name) ?>"></div>
            </section>
        </div>

        <!-- 3. BLOQUE COMERCIAL: DÓNDE DORMIR CERCA -->
        <aside class="poi-sidebar">
            <div class="sticky-sidebar">
                <div class="nearby-services-card">
                    <h3><i class="bi bi-house-heart"></i> Dormir à proximité</h3>
                    <p>Sélection d'établissements proches de ce lieu.</p>
                    
                    <div class="mini-service-list">
                        <?php foreach ($nearbyServices as $s): ?>
                            <a href="<?= BASE_URL . $lang ?>/service/<?= $s->id ?>" class="mini-service-item">
                                <img src="<?= $s->imageUrl ?>" alt="<?= $s->translations['title'] ?>">
                                <div class="mini-info">
                                    <strong><?= htmlspecialchars($s->translations['title']) ?></strong>
                                    <span>À partir de <?= $s->getFormattedPrice() ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="<?= BASE_URL . $lang ?>/home#destinations" class="button button-full button-ghost">
                        Voir tout le catalogue
                    </a>
                </div>
            </div>
        </aside>
    </div>
</main>