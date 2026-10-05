<?php
/**
 * Plantilla « Carte interactive » — copia de src/Infrastructure/Views/search_results.php (app local).
 * Cambios respecto al original marcados con « WP: ». PHP 7.4: nada de str_contains/match.
 */
defined('ABSPATH') || exit;

// WP: datos de WordPress en lugar de los repositorios de la app local.
$resetUrl      = home_url(CANAL_CARTE_PATH); // WP: también desde /categorie/<x>/: el formulario busca en toda la carte
$listings      = canal_carte_listings();
$categories    = canal_carte_categories($listings);
$term          = canal_carte_term(); // WP: /categorie/, /region/, /mot-cle/ publicados (TASK-064)
$params        = canal_carte_term_params(canal_carte_params(wp_unslash($_GET), array_column($categories, 'slug')), $term);
$results       = canal_carte_filter($listings, $params);
$query         = $params['q'];
$city          = $params['location'];
$selectedTypes = $params['type'];

$resultsCount = count($results);
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
    [$query !== '' ? $query : null, $city !== '' ? $city : null, $params['lat'] !== null ? 'Autour de moi' : null],
    array_values($selectedTypeLabels)
));

// WP: datos para el <head> (título, meta, JSON-LD), que get_header() imprime.
$faq      = $results ? canal_carte_faq($listings, canal_plan_pdf_year()) : []; // WP: solo se muestra (y se marca) si hay lista
$modified = canal_carte_last_modified();
$termSeo  = $term ? canal_carte_term_seo($term, $resultsCount) : null;
canal_carte_seo_state(['results' => $results, 'total' => count($listings), 'faq' => $faq, 'modified' => $modified, 'term' => $term, 'termSeo' => $termSeo]);

get_header();
?>
<div class="cdm-carte">

<main class="search-layout-page is-loading" id="search-page" data-results-count="<?= (int)$resultsCount ?>">
    <div class="search-page-skeleton" aria-hidden="true">
        <div class="search-page-skeleton__sidebar">
            <div class="search-page-skeleton__block search-page-skeleton__block--title"></div>
            <div class="search-page-skeleton__stack">
                <span class="search-page-skeleton__line search-page-skeleton__line--wide"></span>
                <span class="search-page-skeleton__line"></span>
                <span class="search-page-skeleton__line search-page-skeleton__line--short"></span>
            </div>
            <div class="search-page-skeleton__chip-row">
                <span class="search-page-skeleton__chip"></span>
                <span class="search-page-skeleton__chip"></span>
                <span class="search-page-skeleton__chip"></span>
            </div>
            <div class="search-page-skeleton__panel">
                <span class="search-page-skeleton__line search-page-skeleton__line--wide"></span>
                <span class="search-page-skeleton__line"></span>
                <span class="search-page-skeleton__line"></span>
                <span class="search-page-skeleton__line search-page-skeleton__line--short"></span>
            </div>
        </div>

        <div class="search-page-skeleton__results">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <article class="search-page-skeleton__card">
                    <div class="search-page-skeleton__media"></div>
                    <div class="search-page-skeleton__body">
                        <span class="search-page-skeleton__line search-page-skeleton__line--wide"></span>
                        <span class="search-page-skeleton__line"></span>
                        <span class="search-page-skeleton__line search-page-skeleton__line--short"></span>
                    </div>
                </article>
            <?php endfor; ?>
        </div>

        <div class="search-page-skeleton__map">
            <div class="search-page-skeleton__map-header">
                <span class="search-page-skeleton__line search-page-skeleton__line--wide"></span>
                <span class="search-page-skeleton__line search-page-skeleton__line--short"></span>
            </div>
            <div class="search-page-skeleton__map-canvas"></div>
            <div class="search-page-skeleton__map-footer">
                <span class="search-page-skeleton__chip search-page-skeleton__chip--wide"></span>
                <span class="search-page-skeleton__chip search-page-skeleton__chip--wide"></span>
            </div>
        </div>
    </div>

    <div class="search-workspace">
        <aside class="search-sidebar" id="search-sidebar-panel">
            <div class="search-sidebar-tabs">
                <div class="search-sidebar-tab is-active" data-tab-target="filters-content"><i class="bi bi-sliders2"></i> Filtres</div>
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
                                    Recherchez par nom d'établissement, type de service ou commune (ex : « vélo Carcassonne », « chambre d'hôtes Homps »). Essayez différents mots-clés pour affiner vos résultats !
                                </span>
                            </span>
                        </label>
                        <input id="search-keywords" type="text" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Que cherchez-vous ?">
                    </div>

                    
                    <div class="filter-block filter-block--type-modern">
                        <label for="search-type-trigger" class="filter-block-label">
                            Service(s) souhaité(s)
                            <span class="filter-block-count"><?= $categoryOptionCount ?></span>
                        </label>
                        <div id="search-type-hidden-container">
                            <?php foreach ($selectedTypes as $st): ?>
                                <input type="hidden" name="type[]" value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endforeach; ?>
                        </div>
                        <div class="modern-type-select" id="search-type-custom" data-selected="<?= htmlspecialchars(json_encode(array_values($selectedTypes)), ENT_QUOTES, 'UTF-8') ?>">
                            <button
                                type="button"
                                class="modern-type-select-trigger"
                                id="search-type-trigger"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                aria-controls="search-type-dropdown"
                            >
                                <span class="modern-type-select-value" id="search-type-value"><?= htmlspecialchars($selectedTypeDisplayText, ENT_QUOTES, 'UTF-8') ?></span>
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
                                            data-value="<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>"
                                            data-label="<?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>"
                                            role="option"
                                            aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                                        >
                                            <span class="modern-type-option-check"><i class="bi bi-check2"></i></span>
                                            <span class="modern-type-option-name"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></span>
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
                    <?php // WP: se conservan lugar y posición al reenviar; lat/lng desactivados si están vacíos (no van en la URL). ?>
                    <?php if ($city !== ''): ?>
                        <input type="hidden" name="location" value="<?= esc_attr($city) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="lat" value="<?= esc_attr((string) $params['lat']) ?>"<?= $params['lat'] === null ? ' disabled' : '' ?>>
                    <input type="hidden" name="lng" value="<?= esc_attr((string) $params['lng']) ?>"<?= $params['lng'] === null ? ' disabled' : '' ?>>
                    <button type="button" class="button button-small search-nearby-button" id="search-nearby-button">
                        <i class="bi bi-crosshair"></i> Autour de moi
                    </button>
                    <p class="search-nearby-status" id="search-nearby-status" role="status" aria-live="polite"></p>
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

            <?php // WP: pestaña « Catégories » de local eliminada a petición del usuario (2026-09-29). ?>
            <div id="ai-content" class="search-sidebar-content">
                <div class="ai-panel">
                    

                    <div class="ai-actions-block">
                        <span class="ai-section-label">Demander à l'assistant</span>
                        <div class="ai-action-list">
                            <button type="button" class="ai-prompt-button" data-ai-prompt="Une balade à vélo en famille le long du canal">
                                <i class="bi bi-bicycle"></i>
                                Une balade à vélo en famille
                            </button>
                            <button type="button" class="ai-prompt-button" data-ai-prompt="Dormir au bord du canal dans un lieu de charme">
                                <i class="bi bi-house-door"></i>
                                Dormir au bord du canal
                            </button>
                            <button type="button" class="ai-prompt-button" data-ai-prompt="Déguster les vins du Minervois chez un vigneron">
                                <i class="bi bi-cup-hot"></i>
                                Déguster les vins du Minervois
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

                               
                                <textarea name="ai-prompt" id="ai-prompt" rows="4" placeholder="Exemple : une balade en bateau sans permis au départ de Castelnaudary, un restaurant au bord de l'eau…"></textarea>

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
            <?php // WP: H1, introducción y editor (SEO/AEO/E-E-A-T), al pie de la columna de filtros en letra pequeña. ?>
            <div class="search-sidebar-about">
                <?php if ($termSeo): ?>
                <h1 class="search-results-title"><?= esc_html($termSeo['h1']) ?></h1>
                <p class="search-results-intro"><?= esc_html($termSeo['intro']) ?></p>
                <?php else: ?>
                <h1 class="search-results-title">Carte des prestataires du Canal du Midi</h1>
                <p class="search-results-intro"><?= (int) count($listings) ?> adresses le long des 240 km du canal, de Toulouse à l'étang de Thau : où dormir, louer un bateau ou un vélo, manger et visiter.</p>
                <?php endif; ?>
                <?php if ($modified !== ''): ?>
                    <p class="search-results-publisher">Guide édité par L'Officiel du Canal du Midi · mis à jour le <time datetime="<?= esc_attr($modified) ?>"><?= esc_html(date_i18n('j F Y', strtotime($modified))) ?></time></p>
                <?php endif; ?>
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
                                <?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>
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
                    <?php foreach (array_values($results) as $i => $s): ?>
                        <?php
                        // WP: $s es un array de canal_carte_listings() (antes, un objeto Service).
                        $serviceTitle = $s['title'] !== '' ? $s['title'] : 'Adresse Canal du Midi';
                        $serviceDesc  = mb_substr($s['description'], 0, 120, 'UTF-8');
                        $serviceImage = $s['image'];
                        $ficheUrl     = $s['url'];
                        ?>
                        <article
                            class="explore-card"
                            data-id="<?= (int) $s['id'] ?>"
                            data-lat="<?= esc_attr((string) $s['lat']) ?>"
                            data-lng="<?= esc_attr((string) $s['lng']) ?>"
                            onmouseenter="window.highlightMarker && window.highlightMarker(<?= (int) $s['id'] ?>)"
                            onmouseleave="window.resetMarker && window.resetMarker(<?= (int) $s['id'] ?>)"
                        >
                            <a class="explore-card-link" href="<?= esc_url($ficheUrl) ?>">
                                <?php // WP: las primeras imágenes van en el HTML sin fundido (LCP); el resto con lazy nativo. ?>
                                <div class="card-image<?= $serviceImage ? ($i < CANAL_CARTE_EAGER_IMAGES ? ' is-loaded' : '') : ' card-image--placeholder' ?>">
                                    <?php if ($serviceImage): ?>
                                        <?php // WP: portada en 768 px + srcset (la original llega a 1024 px). ?>
                                        <img
                                            src="<?= esc_url($serviceImage) ?>"
                                            <?php if ($s['image_srcset'] !== ''): ?>srcset="<?= esc_attr($s['image_srcset']) ?>" sizes="(max-width: 1180px) 100vw, 360px"<?php endif; ?>
                                            <?= $i === 0 ? 'fetchpriority="high"' : ($i >= CANAL_CARTE_EAGER_IMAGES ? 'loading="lazy"' : '') ?>
                                            alt="<?= esc_attr($serviceTitle) ?>"
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
                                <h3 class="card-title"><?= esc_html($serviceTitle) ?></h3>
                                <div class="card-location">
                                    <i class="bi bi-geo-alt"></i>
                                    <span><?= esc_html($s['address']) ?></span>
                                    <?php if (isset($s['distance_km'])): ?>
                                        <span class="card-distance">· à <?= esc_html(number_format($s['distance_km'], $s['distance_km'] < 10 ? 1 : 0, ',', ' ')) ?> km</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($serviceDesc !== ''): ?>
                                    <p class="card-tagline"><?= esc_html($serviceDesc) ?>…</p>
                                <?php endif; ?>
                            </div>

                            <div class="<?= $s['phone'] === '' ? 'card-footer-row--right-aligned' : 'card-footer-row' ?>">
                                <?php if ($s['phone'] !== ''): ?>
                                    <span class="card-phone">
                                        <i class="bi bi-telephone"></i>
                                        <?= esc_html($s['phone']) ?>
                                    </span>
                                <?php endif; ?>
                                <a href="<?= esc_url($ficheUrl) ?>" class="card-detail-trigger">
                                    <span>Voir la fiche</span>
                                    <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php // WP: preguntas frecuentes (AEO), dentro de la lista porque es ella la que hace scroll. ?>
                    <section class="carte-faq" aria-labelledby="carte-faq-title">
                        <p class="carte-faq-kicker" id="carte-faq-title">Questions fréquentes</p>
                        <?php foreach ($faq as $item): ?>
                            <div class="carte-faq-item">
                                <h2><?= esc_html($item['q']) ?></h2>
                                <p><?= esc_html($item['a']) ?></p>
                            </div>
                        <?php endforeach; ?>
                        <p class="carte-faq-sources">Le Canal du Midi est inscrit au <a href="https://whc.unesco.org/fr/list/770/" target="_blank" rel="noopener">patrimoine mondial de l'UNESCO</a> depuis 1996. Conditions de navigation et chômages : <a href="https://www.vnf.fr/" target="_blank" rel="noopener">Voies navigables de France</a>.</p>
                    </section>
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
                    <img id="listing-detail-image" alt=""><?php // WP: sin src="" (pediría la propia página) ?>
                    <div class="listing-detail-media-overlay"></div>
                    <div class="listing-detail-media-caption">
                        <span id="listing-detail-type" class="listing-detail-type"></span>
                        <h2 id="listing-detail-title">Détail de l'adresse</h2><?php // WP: sin encabezado vacío; el JS lo rellena ?>
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
    const searchResults = <?= wp_json_encode(array_map('canal_carte_public', $results), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.searchResults = searchResults;
</script>
<script>
    (function () {
        const page = document.getElementById('search-page');
        if (!page) return;

        // WP: alto real de la cabecera fija del tema (search.css asumía 82px).
        const header = document.querySelector('.cdm-header');
        const shell = page.closest('.cdm-carte');
        const setHeaderHeight = () => {
            if (!header || !shell) return;
            shell.style.setProperty('--cdm-header-h', header.offsetHeight + 'px');
            // WP: la cabecera fija del tema ocupa más alto del que reserva → se compensa el solapamiento.
            // Solo con la página arriba: al hacer scroll el tema oculta la cabecera y la medida no vale.
            if (window.scrollY !== 0) return;
            shell.style.paddingTop = '0px';
            const overlap = getComputedStyle(header).position === 'fixed'
                ? Math.round(header.getBoundingClientRect().bottom - shell.getBoundingClientRect().top)
                : 0;
            shell.style.paddingTop = overlap > 0 ? overlap + 'px' : '0px';
        };
        setHeaderHeight();
        window.addEventListener('resize', setHeaderHeight);

        document.body.classList.add('search-page-loading');

        // WP: se muestra en cuanto cargan las primeras imágenes, sin esperar a Google Maps (TASK-035: el mapa
        // retrasaba el LCP); el mapa aparece en su panel cuando esté listo. Respaldo a los 5 s.
        let revealed = false;
        const reveal = () => {
            if (revealed) return;
            revealed = true;
            page.classList.remove('is-loading');
            page.classList.add('is-ready');
            document.body.classList.remove('search-page-loading');
        };
        <?php if ($resultsCount === 0): ?>reveal();<?php endif; ?>
        window.addEventListener('search:images-ready', reveal, { once: true });
        window.setTimeout(reveal, 5000);
    })();
</script>
</div>
<?php
get_footer();