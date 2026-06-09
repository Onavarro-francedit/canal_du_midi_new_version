<?php
$results = $results ?? [];
$categories = $categories ?? [];
$cities = $cities ?? [];
$lang = $lang ?? 'fr';
$query = (string)($query ?? '');
$city = (string)($city ?? '');

$resultsCount = count($results);
$resetUrl = BASE_URL . $lang . '/search';
$allCities = $cities;
$selectedTypesSource = $types ?? ($type ?? []);
$selectedTypes = array_values(array_filter(array_map('trim', (array)$selectedTypesSource)));
$categoryOptionCount = count(array_filter($categories, fn($cat) => trim((string)($cat['slug'] ?? '')) !== ''));
$selectedTypeLabels = [];
foreach ($selectedTypes as $st) {
    foreach ($categories as $cat) {
        $slug = trim((string)($cat['slug'] ?? ''));
        if ($slug !== '' && $slug === $st) {
            $selectedTypeLabels[$st] = (string)($cat['name'] ?? ucfirst($slug));
            break;
        }
    }
}
$selectedTypeDisplayText = count($selectedTypes) === 0
    ? 'Tous les types'
    : (count($selectedTypes) === 1
        ? (reset($selectedTypeLabels) ?: $selectedTypes[0])
        : count($selectedTypes) . ' type(s) sélectionné(s)');
$activeFilters = array_filter(array_merge(
    [$query !== '' ? $query : null, $city !== '' ? $city : null],
    array_values($selectedTypeLabels)
));

$rootCategories = [];
$childCategoriesByParent = [];
foreach ($categories as $cat) {
    $categoryId = (int)($cat['id'] ?? 0);
    $parentId = isset($cat['parent_id']) && $cat['parent_id'] !== null ? (int)$cat['parent_id'] : 0;

    if ($parentId > 0) {
        $childCategoriesByParent[$parentId][] = $cat;
        continue;
    }

    $rootCategories[] = $cat;
}

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
                <form action="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" method="GET" class="search-sidebar-form">
                    <div class="filter-block">
                        <label for="search-keywords" class="filter-block-label">
                            Recherche par mot clé
                            <span class="filter-help" id="keywords-help">
                                <button
                                    type="button"
                                    class="filter-help-badge"
                                    id="keywords-help-badge"
                                    aria-label="Voir des exemples de mots clé"
                                    aria-expanded="false"
                                    aria-controls="keywords-help-tooltip"
                                >
                                    <i class="bi bi-info-circle"></i>
                                </button>
                                <span class="filter-help-tooltip" id="keywords-help-tooltip" role="tooltip">
                                    Vous pouvez rechercher par nom d'établissement, type de service, ville, ou même des équipements spécifiques (ex : "hôtel avec piscine à Toulouse"). Essayez différents mots-clés pour affiner vos résultats !
                                </span>
                            </span>
                        </label>
                        <input id="search-keywords" type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Que cherchez-vous ?">
                    </div>

                    
                    <div class="filter-block filter-block--type-modern">
                        <label for="search-type-trigger" class="filter-block-label">
                            Service(s) souhaité(s)
                            <span class="filter-block-count"><?= $categoryOptionCount ?></span>
                        </label>
                        <div id="search-type-hidden-container">
                            <?php foreach ($selectedTypes as $st): ?>
                                <input type="hidden" name="type[]" value="<?= htmlspecialchars($st) ?>">
                            <?php endforeach; ?>
                        </div>
                        <div class="modern-type-select" id="search-type-custom" data-selected="<?= htmlspecialchars(json_encode(array_values($selectedTypes))) ?>">
                            <button
                                type="button"
                                class="modern-type-select-trigger"
                                id="search-type-trigger"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                aria-controls="search-type-dropdown"
                            >
                                <span class="modern-type-select-value" id="search-type-value"><?= htmlspecialchars($selectedTypeDisplayText) ?></span>
                            </button>

                            <div class="modern-type-select-dropdown" id="search-type-dropdown" hidden>
                                <input
                                    type="text"
                                    class="modern-type-select-search"
                                    id="search-type-filter"
                                    placeholder="Rechercher une catégorie..."
                                    autocomplete="off"
                                >
                                <div class="modern-type-options" id="search-type-options" role="listbox" aria-label="Service souhaité" aria-multiselectable="true">
                                    <button
                                        type="button"
                                        class="modern-type-option<?= empty($selectedTypes) ? ' is-selected' : '' ?>"
                                        data-value=""
                                        data-label="Tous les types"
                                        role="option"
                                        aria-selected="<?= empty($selectedTypes) ? 'true' : 'false' ?>"
                                    >
                                        <span class="modern-type-option-check"><i class="bi bi-check2"></i></span>
                                        <span class="modern-type-option-name">Tous les types</span>
                                    </button>

                                    <?php $hasCategoryOptions = false; ?>
                                    <?php foreach (($categories ?? []) as $cat): ?>
                                        <?php
                                            $catSlug = trim((string)($cat['slug'] ?? ''));
                                            if ($catSlug === '') {
                                                continue;
                                            }
                                            $hasCategoryOptions = true;
                                            $catName = (string)($cat['name'] ?? ucfirst($catSlug));
                                            $catOffers = (int)($cat['offers_count'] ?? 0);
                                            $isSelected = in_array($catSlug, $selectedTypes);
                                        ?>
                                        <button
                                            type="button"
                                            class="modern-type-option<?= $isSelected ? ' is-selected' : '' ?>"
                                            data-value="<?= htmlspecialchars($catSlug) ?>"
                                            data-label="<?= htmlspecialchars($catName) ?>"
                                            role="option"
                                            aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                                        >
                                            <span class="modern-type-option-check"><i class="bi bi-check2"></i></span>
                                            <span class="modern-type-option-name"><?= htmlspecialchars($catName) ?></span>
                                            <span class="modern-type-option-count"><?= $catOffers ?> offre<?= $catOffers > 1 ? 's' : '' ?></span>
                                        </button>
                                    <?php endforeach; ?>

                                    <?php if (!$hasCategoryOptions): ?>
                                        <div class="modern-type-empty">Aucune catégorie disponible</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="search-sidebar-actions">
                        <button type="submit" class="button search-sidebar-submit">
                            <i class="bi bi-search"></i>
                            Rechercher
                        </button>
                        <br>
                        <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="search-reset-link">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Réinitialiser les filtres
                        </a>
                    </div>
                </form>
            </div>

            <div id="categories-content" class="search-sidebar-content">
                <div class="category-explorer-list">
                    <?php
                        $renderCategoryCard = function (array $cat, bool $isChild = false) use ($lang, $selectedTypes) {
                            $categorySlugRaw = trim((string)($cat['slug'] ?? ''));
                            $categoryNameRaw = trim((string)($cat['name'] ?? ''));
                            $categoryName = htmlspecialchars($categoryNameRaw !== '' ? $categoryNameRaw : ucfirst($categorySlugRaw));
                            $offersCount = (int)($cat['offers_count'] ?? 0);
                            $categorySlug = rawurlencode($categorySlugRaw);

                            $categoryIconMap = [
                                'restaurant' => 'bi-cup-hot',
                                'hebergement' => 'bi-house-door',
                                'hotel' => 'bi-house-door',
                                'camping' => 'bi-tree',
                                'gite' => 'bi-house-heart',
                                'location' => 'bi-key',
                                'activite' => 'bi-compass',
                                'activités' => 'bi-compass',
                                'loisirs' => 'bi-stars',
                                'service' => 'bi-gear',
                                'transport' => 'bi-bus-front',
                                'velo' => 'bi-bicycle',
                                'vélo' => 'bi-bicycle',
                                'bateau' => 'bi-water',
                                'peniche' => 'bi-water',
                                'péniche' => 'bi-water',
                            ];

                            $categoryIcon = 'bi-grid';
                            foreach ($categoryIconMap as $needle => $iconClass) {
                                if ($categorySlugRaw !== '' && str_contains($categorySlugRaw, $needle)) {
                                    $categoryIcon = $iconClass;
                                    break;
                                }
                                if ($categoryNameRaw !== '' && str_contains(mb_strtolower($categoryNameRaw), $needle)) {
                                    $categoryIcon = $iconClass;
                                    break;
                                }
                            }

                            $categoryBackgrounds = [
                                'linear-gradient(180deg, rgba(10, 18, 32, 0.08), rgba(10, 18, 32, 0.62)), linear-gradient(135deg, #dcecff 0%, #bfdaf8 100%)',
                                'linear-gradient(180deg, rgba(10, 18, 32, 0.08), rgba(10, 18, 32, 0.62)), linear-gradient(135deg, #e7f4ee 0%, #c9e9da 100%)',
                                'linear-gradient(180deg, rgba(10, 18, 32, 0.08), rgba(10, 18, 32, 0.62)), linear-gradient(135deg, #f8eadf 0%, #f0c9b1 100%)',
                                'linear-gradient(180deg, rgba(10, 18, 32, 0.08), rgba(10, 18, 32, 0.62)), linear-gradient(135deg, #efe5ff 0%, #d8c7ff 100%)',
                                'linear-gradient(180deg, rgba(10, 18, 32, 0.08), rgba(10, 18, 32, 0.62)), linear-gradient(135deg, #eef0f8 0%, #d8deef 100%)',
                            ];
                            $categoryBackground = $categoryBackgrounds[abs(crc32($categorySlugRaw)) % count($categoryBackgrounds)];
                            $categoryClasses = 'category-item' . ((string)($cat['slug'] ?? '') !== '' && in_array((string)($cat['slug'] ?? ''), $selectedTypes, true) ? ' is-active' : '');
                            if ($isChild) {
                                $categoryClasses .= ' category-item--child';
                            }

                            return '<a href="' . htmlspecialchars(BASE_URL . $lang . '/search?type=' . $categorySlug, ENT_QUOTES, 'UTF-8') . '"'
                                . ' class="' . htmlspecialchars($categoryClasses, ENT_QUOTES, 'UTF-8') . '"'
                                . ' style="background-image: ' . htmlspecialchars($categoryBackground, ENT_QUOTES, 'UTF-8') . ';">'
                                . '<div class="cat-card-top"><div class="cat-icon-box"><i class="bi ' . htmlspecialchars($categoryIcon, ENT_QUOTES, 'UTF-8') . '"></i></div></div>'
                                . '<div class="cat-card-bottom"><span class="cat-name">' . $categoryName . '</span><span class="cat-hint">' . $offersCount . ' offre' . ($offersCount > 1 ? 's' : '') . '</span></div>'
                                . '</a>';
                        };
                    ?>

                    <?php if (!empty($rootCategories)): ?>
                        <?php foreach ($rootCategories as $parentCat): ?>
                            <div class="category-group">
                                <?= $renderCategoryCard($parentCat, false) ?>

                                <?php $parentId = (int)($parentCat['id'] ?? 0); ?>
                                <?php if (!empty($childCategoriesByParent[$parentId])): ?>
                                    <div class="category-group-children">
                                        <?php foreach ($childCategoriesByParent[$parentId] as $childCat): ?>
                                            <?= $renderCategoryCard($childCat, true) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <?= $renderCategoryCard($cat, false) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

                        <div class="ai-response-loading is-hidden" id="ai-response-loading" aria-live="polite" aria-busy="true">
                            <div class="ai-response-loading-spinner" aria-hidden="true"></div>
                            <strong>Recherche en cours...</strong>
                            <p>L'assistant analyse votre demande et sélectionne la meilleure réponse.</p>
                        </div>

                        <div class="ai-response-body is-hidden" id="ai-response-body">
                            <span class="ai-response-label" id="ai-response-label"></span>
                            <h4 id="ai-response-title"></h4>
                            <p id="ai-response-text"></p>
                            <div class="ai-response-meta" id="ai-response-meta"></div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="search-results-column" id="results-list">
            <header class="search-results-toolbar">
                <div class="search-toolbar-left">
                    <h2 class="search-results-count">
                        <?= $resultsCount ?> résultat<?= $resultsCount > 1 ? 's' : '' ?>
                    </h2>
                </div>
                <?php if (!empty($activeFilters)): ?>
                    <div class="search-active-filters">
                        <?php foreach ($activeFilters as $filter): ?>
                            <span class="active-filter-chip">
                                <i class="bi bi-check2"></i>
                                <?= htmlspecialchars($filter) ?>
                            </span>
                        <?php endforeach; ?>
                        <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="clear-filters-link">
                            <i class="bi bi-x-lg"></i> Effacer
                        </a>
                    </div>
                <?php endif; ?>
            </header>
        
            <?php if (empty($results)): ?>
                <div class="no-results-card">
                    <div class="no-results-icon"><i class="bi bi-compass"></i></div>
                    <h2>Aucun résultat trouvé</h2>
                    <p>Essayez un autre mot-clé, élargissez la ville recherchée ou retirez un filtre pour découvrir davantage d'adresses.</p>
                    <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="button button-small">Réinitialiser</a>
                </div>
            <?php else: ?>
                <div class="explore-list">
                    <?php foreach ($results as $s): ?>
                        <?php
                        $serviceTitle        = htmlspecialchars($s->translations['title'] ?? 'Adresse Canal du Midi');
                        $serviceDesc         = mb_substr(htmlspecialchars($s->translations['description'] ?? ''), 0, 120);
                        $serviceAddress      = htmlspecialchars($s->getFullAddress());
                        $serviceImage        = $s->imageUrl ?: '';
                        $serviceImageEscaped = htmlspecialchars($serviceImage, ENT_QUOTES, 'UTF-8');
                        $ficheUrl            = htmlspecialchars(BASE_URL . 'fiche/' . rawurlencode((string)$s->slug), ENT_QUOTES, 'UTF-8');
                        ?>
                        <article
                            class="explore-card"
                            data-id="<?= (int)$s->id ?>"
                            data-lat="<?= htmlspecialchars((string)$s->lat, ENT_QUOTES, 'UTF-8') ?>"
                            data-lng="<?= htmlspecialchars((string)$s->lng, ENT_QUOTES, 'UTF-8') ?>"
                            onmouseenter="window.highlightMarker && window.highlightMarker(<?= (int)$s->id ?>)"
                            onmouseleave="window.resetMarker && window.resetMarker(<?= (int)$s->id ?>)"
                        >
                            <a class="explore-card-link" href="<?= $ficheUrl ?>">
                                <div class="card-image<?= $serviceImage ? '' : ' card-image--placeholder' ?>">
                                    <?php if ($serviceImage): ?>
                                        <!--
                                            No usamos loading="lazy" aquí porque queremos controlar
                                            nosotros mismos la transición visual.
                                            data-src en lugar de src: la imagen no se descarga
                                            hasta que el IntersectionObserver la activa.
                                        -->
                                        <img
                                            data-src="<?= $serviceImageEscaped ?>"
                                            alt="<?= $serviceTitle ?>"
                                            width="400"
                                            height="260"
                                            decoding="async"
                                        >
                                    <?php else: ?>
                                        <div class="card-image-icon"><i class="bi bi-building"></i></div>
                                    <?php endif; ?>
                                </div>
                            </a>
        
                            <div class="card-body">
                                <h3 class="card-title"><?= $serviceTitle ?></h3>
                                <div class="card-location">
                                    <i class="bi bi-geo-alt"></i>
                                    <span><?= $serviceAddress ?></span>
                                </div>
                                <?php if ($serviceDesc): ?>
                                    <p class="card-tagline"><?= $serviceDesc ?>…</p>
                                <?php endif; ?>
                            </div>
        
                            <div class="<?= empty($s->contact['phone']) ? 'card-footer-row--right-aligned' : 'card-footer-row' ?>">
                                <?php if (!empty($s->contact['phone'])): ?>
                                    <span class="card-phone">
                                        <i class="bi bi-telephone"></i>
                                        <?= htmlspecialchars($s->contact['phone']) ?>
                                    </span>
                                <?php endif; ?>
                                <a href="<?= $ficheUrl ?>" class="card-detail-trigger">
                                    <span>Voir la fiche</span>
                                    <i class="bi bi-arrow-right-short"></i>
                                </a>
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
        'image' => $s->imageUrl,
        'gallery' => array_values(array_filter(array_map('trim', (array)($s->gallery ?? [])))),
        'address' => $s->getFullAddress(),
        'description' => mb_substr($s->translations['description'] ?? '', 0, 200),
        'type' => $s->type,
        'label' => $s->label ?? '',
        'phone' => $s->contact['phone'] ?? '',
        'email' => $s->contact['email'] ?? '',
        'url' => BASE_URL . 'fiche/' . $s->slug,
    ], $results), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>