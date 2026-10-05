<?php
/**
 * SEO de la carte (CANAL_CARTE_PATH): <title>, meta description, Open Graph/Twitter, JSON-LD
 * (CollectionPage + BreadcrumbList + ItemList + FAQPage), cabecera Link a llms.txt y
 * noindex,follow en las URLs con filtros.
 * La canonical la emite WordPress al publicar la página (siempre la URL sin parámetros).
 */
defined('ABSPATH') || exit;

// El ItemList solo recoge fichas visibles en el HTML, y no todas: el HTML ya es pesado.
const CANAL_CARTE_SEO_LIST_MAX = 30;

function canal_carte_seo_title(): string
{
    return "Carte des prestataires du Canal du Midi | L'Officiel"; // ≤ 60 car.
}

function canal_carte_seo_description(int $total): string
{
    return "Carte interactive des $total prestataires du Canal du Midi : hébergements, location de bateaux et de vélos, restaurants et visites, de Toulouse à l'étang de Thau.";
}

/** Resultados y total que pinta la plantilla (los registra antes de get_header()). */
function canal_carte_seo_state(?array $state = null): array
{
    static $registered = ['results' => [], 'total' => 0, 'faq' => [], 'modified' => '', 'term' => null, 'termSeo' => null];
    if ($state !== null) {
        $registered = $state;
    }
    return $registered;
}

/** Fecha (ISO 8601) de la ficha publicada modificada más recientemente: la carte cambia con ellas. */
function canal_carte_last_modified(): string
{
    global $wpdb;
    $gmt = $wpdb->get_var("SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_type = 'job_listing' AND post_status = 'publish'");
    return $gmt ? gmdate('c', strtotime($gmt . ' UTC')) : '';
}

function canal_carte_seo_graph(string $url, array $results, int $total, array $faq = [], string $modified = '', ?array $termSeo = null): array
{
    $home  = home_url('/');
    $carte = home_url(CANAL_CARTE_PATH);
    $items = [];
    foreach (array_slice(array_values($results), 0, CANAL_CARTE_SEO_LIST_MAX) as $i => $r) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $r['url'], 'name' => $r['title']];
    }
    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'CollectionPage',
                '@id'         => $url . '#webpage',
                'url'         => $url,
                'name'        => $termSeo ? $termSeo['title'] : canal_carte_seo_title(),
                'description' => $termSeo ? $termSeo['description'] : canal_carte_seo_description($total),
                'inLanguage'  => 'fr-FR',
                // @id definidos en el JSON-LD de la home (Organization, WebSite, TouristDestination).
                'isPartOf'    => ['@id' => $home . '#website'],
                'publisher'   => ['@id' => $home . '#organization'],
                'about'       => ['@id' => $home . '#canal-du-midi'],
                'breadcrumb'  => ['@id' => $url . '#breadcrumb'],
                'mainEntity'  => ['@id' => $url . '#list'],
                'dateModified' => $modified,
                // Fragmentos aptos para lectura en voz alta (asistentes de voz / AEO).
                'speakable'   => [
                    '@type'       => 'SpeakableSpecification',
                    'cssSelector' => ['.search-results-title', '.search-results-intro'],
                ],
            ],
            [
                '@type'           => 'BreadcrumbList',
                '@id'             => $url . '#breadcrumb',
                'itemListElement' => array_merge([
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $home],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Carte interactive', 'item' => $carte],
                ], $termSeo ? [['@type' => 'ListItem', 'position' => 3, 'name' => $termSeo['h1'], 'item' => $url]] : []),
            ],
            [
                '@type'           => 'ItemList',
                '@id'             => $url . '#list',
                'name'            => 'Prestataires du Canal du Midi',
                'numberOfItems'   => $termSeo ? count($results) : $total,
                'itemListElement' => $items,
            ],
        ],
    ];
    // Solo si la FAQ se muestra en la página (el schema debe reflejar lo visible).
    if ($faq) {
        $graph['@graph'][] = [
            '@type'      => 'FAQPage',
            '@id'        => $url . '#faq',
            'mainEntity' => array_map(function ($item) {
                return ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]];
            }, $faq),
        ];
    }
    return $graph;
}

add_filter('pre_get_document_title', function ($title) {
    if (!canal_carte_is_page()) {
        return $title;
    }
    $state = canal_carte_seo_state();
    return $state['termSeo'] ? $state['termSeo']['title'] : canal_carte_seo_title();
}, 10001); // el tema fija el título de /categorie/ y /region/ con prioridad 10000 (my-listing explore.php)

// /categorie/<x>/ (y región, etiqueta) es su propia página: canonical a sí misma, no a /explorer/ (la página que
// la sirve). Sin esto Google las trataría como duplicados de la carte.
add_filter('get_canonical_url', function ($url) {
    $term = canal_carte_is_page() ? canal_carte_term() : null;
    return $term && $term['url'] !== '' ? $term['url'] : $url;
});

add_action('wp_head', function () {
    if (!canal_carte_is_page()) {
        return;
    }
    $state = canal_carte_seo_state();
    $ts    = $state['termSeo'];
    $url   = $state['term'] && $state['term']['url'] !== '' ? $state['term']['url'] : (string) get_permalink();
    echo canal_home_seo_social($ts ? $ts['title'] : canal_carte_seo_title(), $ts ? $ts['description'] : canal_carte_seo_description($state['total']), $url, 'Le Canal du Midi : péniches, chemin de halage et vélo'); // phpcs:ignore — escapado dentro.
    echo canal_home_seo_jsonld(canal_carte_seo_graph($url, $state['results'], $state['total'], $state['faq'], $state['modified'], $ts)); // phpcs:ignore — JSON_HEX_TAG.
}, 5);

// Cabecera Link hacia llms.txt (descubrimiento por agentes de IA), como en la home.
add_action('template_redirect', function () {
    if (canal_carte_is_page() && !headers_sent()) {
        header('Link: <' . esc_url_raw(home_url('/llms.txt')) . '>; rel="llms-txt"', false);
    }
});

// Variantes filtradas (?q=, ?type=, « Autour de moi »…): no se indexan, pero sus enlaces sí se siguen.
add_filter('wp_robots', function (array $robots) {
    if (canal_carte_is_page() && canal_carte_has_filters(wp_unslash($_GET))) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
});
