<?php
$resultsCount = count($results);
$resetUrl = BASE_URL . $lang . '/search';
$categoryVisuals = [
    'info' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1200&q=80',
    'hotel' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
    'restaurant' => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=1200&q=80',
    'boat' => 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=1200&q=80',
    'activity' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80',
    'tour' => 'https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=1200&q=80',
    'destination' => 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?auto=format&fit=crop&w=1200&q=80',
];
$activeFilters = array_filter([
    $query !== '' ? $query : null,
    $city !== '' ? $city : null,
    $type !== '' ? ucfirst($type) : null,
]);
?>

<main class="search-layout-page">
    <div class="search-workspace">
        <aside class="search-sidebar" id="search-sidebar-panel">
            <div class="search-sidebar-tabs">
                <div class="search-sidebar-tab is-active" data-tab-target="filters-content"><i class="bi bi-sliders2"></i> Filtres</div>
                <div class="search-sidebar-tab" data-tab-target="categories-content"><i class="bi bi-bookmark"></i> Catégories</div>
                <div class="search-sidebar-tab" data-tab-target="ai-content"><i class="bi bi-stars"></i> Ai</div>
            </div>

            <div id="filters-content" class="search-sidebar-content is-active">
                <form action="<?= BASE_URL . $lang ?>/search" method="GET" class="search-sidebar-form">
                    <div class="filter-block">
                        <label for="search-keywords" class="filter-block-label">Recherche par mot clé</label>
                        <input id="search-keywords" type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Que cherchez-vous ?">
                    </div>

                    <div class="filter-block">
                        <label for="search-city" class="filter-block-label">Localisation</label>
                        <input id="search-city" type="text" name="city" value="<?= htmlspecialchars($city) ?>" placeholder="Saisir une ville ou adresse...">
                    </div>

                    <div class="filter-block">
                        <label for="search-type" class="filter-block-label">Service(s) souhaité(s)</label>
                        <select id="search-type" name="type">
                            <option value="">Tous les types</option>
                            <option value="hotel" <?= $type === 'hotel' ? 'selected' : '' ?>>Hôtel</option>
                            <option value="boat" <?= $type === 'boat' ? 'selected' : '' ?>>Bateau</option>
                        </select>
                    </div>
                    <br>
                    <div class="search-sidebar-actions">
                        <button type="submit" class="button search-sidebar-submit">
                            <i class="bi bi-search"></i>
                            Rechercher
                        </button>
                        <br>
                        <a href="<?= $resetUrl ?>" class="search-reset-link">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Réinitialiser les filtres
                        </a>
                    </div>
                </form>
            </div>

            <div id="categories-content" class="search-sidebar-content">
                <div class="category-explorer-list">
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $categoryImage = $categoryVisuals[$cat['slug']] ?? 'https://images.unsplash.com/photo-1517760444937-f6397edcbbcd?auto=format&fit=crop&w=1200&q=80';
                        $categoryName = htmlspecialchars($cat['name'] ?? ucfirst($cat['slug']));
                        $offersCount = (int)($cat['offers_count'] ?? 0);
                        ?>
                        <a href="<?= BASE_URL . $lang ?>/search?type=<?= $cat['slug'] ?>" class="category-item<?= $type === $cat['slug'] ? ' is-active' : '' ?>" style="background-image: linear-gradient(180deg, rgba(11, 18, 32, 0.08), rgba(11, 18, 32, 0.62)), url('<?= htmlspecialchars($categoryImage) ?>');">
                            <div class="cat-card-top">
                                <div class="cat-icon-box">
                                    <i class="bi <?= $cat['icon_class'] ?>"></i>
                                </div>
                            </div>
                            <div class="cat-card-bottom">
                                <span class="cat-name"><?= $categoryName ?></span>
                                <span class="cat-hint"><?= $offersCount ?> offre<?= $offersCount > 1 ? 's' : '' ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="ai-content" class="search-sidebar-content">
                <div class="ai-panel">
                    

                    <div class="ai-actions-block">
                        <span class="ai-section-label">Demander à l'assistant</span>
                        <div class="ai-action-list">
                            <button type="button" class="ai-prompt-button" data-ai-strategy="best-value">
                                <i class="bi bi-stars"></i>
                                Trouver le meilleur rapport qualité / prix
                            </button>
                            <button type="button" class="ai-prompt-button" data-ai-strategy="hybrid">
                                <i class="bi bi-cup-hot"></i>
                                Prioriser les adresses avec restauration
                            </button>
                            <button type="button" class="ai-prompt-button" data-ai-strategy="spacious">
                                <i class="bi bi-door-open"></i>
                                Voir les établissements les plus spacieux
                            </button>
                        </div>
                    </div>

                    <div class="ai-response-card" id="ai-response-card">
                        <div class="ai-response-empty" id="ai-response-empty">
                            <div class="ai-prompt-shell">
                                <div class="ai-prompt-head">
                                    <div class="ai-prompt-icon">
                                        <i class="bi bi-stars"></i>
                                    </div>
                                    <div class="ai-prompt-copy">
                                        <strong>Décrivez votre besoin</strong>
                                    </div>
                                </div>

                               
                                <textarea name="ai-prompt" id="ai-prompt" rows="4" placeholder="Exemple : je veux un hôtel abordable, un lieu avec restaurant ou un établissement spacieux...."></textarea>

                                <div class="ai-prompt-footer">
                                    <button type="button" class="button button-small ai-submit-button" id="ai-submit-button">
                                        Analyser ma demande
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="ai-response-body is-hidden" id="ai-response-body">
                            <span class="ai-response-label" id="ai-response-label"></span>
                            <h4 id="ai-response-title"></h4>
                            <p id="ai-response-text"></p>
                            <div class="ai-response-meta" id="ai-response-meta"></div>
                            <div class="ai-response-actions">
                                <button type="button" class="button button-small ai-highlight-button" id="ai-highlight-button">
                                    <i class="bi bi-cursor-fill"></i>
                                    Mettre en avant dans la liste
                                </button>
                                <a href="#" class="ai-open-link" id="ai-open-link">Ouvrir la fiche</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="search-results-column" id="results-list">
            <header class="search-results-toolbar">
                <div class="search-toolbar-chip"><i class="bi bi-filter-left"></i> <?= !empty($activeFilters) ? 'Filtres actifs' : 'Aléatoire' ?></div>
                <div class="search-results-count"><?= $resultsCount ?> résultats</div>
                
            </header>

            <?php if (empty($results)): ?>
                <div class="no-results-card">
                    <i class="bi bi-binoculars"></i>
                    <h2>Aucun résultat pour cette recherche</h2>
                    <p>Essayez un autre mot-clé, élargissez la ville recherchée ou retirez un filtre pour découvrir davantage d'adresses.</p>
                </div>
            <?php else: ?>
                <div class="explore-list">
                    <?php foreach ($results as $s): ?>
                        <?php
                        $serviceTitle = htmlspecialchars($s->translations['title'] ?? 'Adresse Canal du Midi');
                        $serviceTag = htmlspecialchars($s->translations['tag'] ?? 'Une halte bien située pour découvrir le Canal du Midi.');
                        $serviceDescription = htmlspecialchars($s->translations['description'] ?? 'Une halte sélectionnée pour découvrir le Canal du Midi dans de bonnes conditions.');
                        $serviceAddress = htmlspecialchars($s->contact['address'] ?? 'Canal du Midi, Occitanie');
                        $roomsCount = (int)($s->features['rooms_count'] ?? 0);
                        ?>
                        <article class="explore-card"
                            data-id="<?= $s->id ?>"
                            data-lat="<?= $s->lat ?>"
                            data-lng="<?= $s->lng ?>"
                            onmouseenter="highlightMarker(<?= $s->id ?>)"
                            onmouseleave="resetMarker(<?= $s->id ?>)">
                            <a class="explore-card-link" href="<?= BASE_URL . $lang ?>/service/<?= $s->id ?>">
                                <div class="card-image">
                                    <img src="<?= htmlspecialchars($s->imageUrl) ?>" alt="<?= $serviceTitle ?>">
                                    <span class="card-favorite-badge"><i class="bi bi-lightning-charge"></i></span>
                                    <div class="card-image-overlay"></div>
                                    <div class="card-image-caption">
                                        <h3><?= $serviceTitle ?></h3>
                                    </div>
                                </div>
                            </a>

                                <div class="card-footer-row">
                                    <button
                                        type="button"
                                        class="card-detail-trigger"
                                        aria-label="Afficher les détails de <?= $serviceTitle ?>"
                                        data-service-id="<?= $s->id ?>"
                                    >
                                        <i class="bi bi-info-circle"></i>
                                        <span>Voir les détails</span>
                                    </button>
                                </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="explore-map-wrapper" id="search-map-panel">
            <div class="map-panel-shell">
                <div class="map-panel-header">
                    <span class="section-kicker">Carte</span>
                    <div class="map-panel-summary">
                        <strong><?= $resultsCount ?></strong>
                        <span>adresses visibles sur la carte</span>
                    </div>
                </div>
                <div id="explore-map"></div>
            </div>
        </aside>
    </div>

    <div class="search-mobile-backdrop" id="search-mobile-backdrop"></div>

    <div class="listing-detail-modal" id="listing-detail-modal" aria-hidden="true">
        <div class="listing-detail-backdrop" data-close-listing-modal></div>
        <div class="listing-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="listing-detail-title">
            <button type="button" class="listing-detail-close" aria-label="Fermer" data-close-listing-modal>
                <i class="bi bi-x-lg"></i>
            </button>

            <div class="listing-detail-panel">
                <div class="listing-detail-media">
                    <img id="listing-detail-image" src="" alt="">
                    <div class="listing-detail-media-overlay"></div>
                    <div class="listing-detail-media-caption">
                        <span id="listing-detail-type" class="listing-detail-type"></span>
                        <h2 id="listing-detail-title"></h2>
                    </div>
                </div>

                <div class="listing-detail-content">
                    <div class="listing-detail-section">
                        <h3><i class="bi bi-card-text"></i> Description</h3>
                        <p id="listing-detail-description"></p>
                    </div>

                    <div class="listing-detail-section">
                        <h3><i class="bi bi-grid"></i> Catégories</h3>
                        <div class="listing-detail-tags" id="listing-detail-tags"></div>
                    </div>

                    <div class="listing-detail-footer">
                        <span id="listing-detail-price" class="listing-detail-price"></span>
                        <a id="listing-detail-link" class="button button-small" href="">Voir la fiche</a>
                    </div>
                </div>
            </div>

            <div class="listing-detail-map-shell">
                <div id="listing-detail-map"></div>
            </div>
        </div>
    </div>

    <nav class="search-mobile-nav" aria-label="Navigation mobile des résultats">
        <button type="button" class="search-mobile-nav-item mobile-view-trigger" data-mobile-target="filters">
            <i class="bi bi-search"></i>
        </button>
        <button type="button" class="search-mobile-nav-item is-active mobile-view-trigger" data-mobile-target="list">
            <i class="bi bi-list-ul"></i>
        </button>
        <button type="button" class="search-mobile-nav-item mobile-view-trigger" data-mobile-target="map">
            <i class="bi bi-map"></i>
        </button>
    </nav>
</main>

<!-- Pasamos los datos a JS de forma segura -->
<script>
    const searchResults = <?= json_encode(array_map(fn($s) => [
        'id' => $s->id,
        'lat' => $s->lat,
        'lng' => $s->lng,
        'title' => $s->translations['title'],
        'price' => $s->getFormattedPrice(),
        'priceValue' => (float)$s->price,
        'image' => $s->imageUrl,
        'address' => $s->contact['address'] ?? '',
        'tag' => $s->translations['tag'] ?? '',
        'description' => $s->translations['description'] ?? '',
        'type' => ucfirst($s->type),
        'hybrid' => $s->isHybrid(),
        'roomsCount' => (int)($s->features['rooms_count'] ?? 0),
        'url' => BASE_URL . $lang . '/service/' . $s->id,
    ], $results)) ?>;
</script>