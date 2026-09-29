<?php
/**
 * SEO de la carte (CANAL_CARTE_PATH): <title>, meta description, Open Graph/Twitter, JSON-LD
 * (CollectionPage + BreadcrumbList + ItemList) y noindex,follow en las URLs con filtros.
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
    static $registered = ['results' => [], 'total' => 0];
    if ($state !== null) {
        $registered = $state;
    }
    return $registered;
}

function canal_carte_seo_graph(string $url, array $results, int $total): array
{
    $home  = home_url('/');
    $items = [];
    foreach (array_slice(array_values($results), 0, CANAL_CARTE_SEO_LIST_MAX) as $i => $r) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $r['url'], 'name' => $r['title']];
    }
    return [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'CollectionPage',
                '@id'         => $url . '#webpage',
                'url'         => $url,
                'name'        => canal_carte_seo_title(),
                'description' => canal_carte_seo_description($total),
                'inLanguage'  => 'fr-FR',
                // @id definidos en el JSON-LD de la home (Organization, WebSite, TouristDestination).
                'isPartOf'    => ['@id' => $home . '#website'],
                'publisher'   => ['@id' => $home . '#organization'],
                'about'       => ['@id' => $home . '#canal-du-midi'],
                'breadcrumb'  => ['@id' => $url . '#breadcrumb'],
                'mainEntity'  => ['@id' => $url . '#list'],
            ],
            [
                '@type'           => 'BreadcrumbList',
                '@id'             => $url . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $home],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Carte interactive', 'item' => $url],
                ],
            ],
            [
                '@type'           => 'ItemList',
                '@id'             => $url . '#list',
                'name'            => 'Prestataires du Canal du Midi',
                'numberOfItems'   => $total,
                'itemListElement' => $items,
            ],
        ],
    ];
}

add_filter('pre_get_document_title', function ($title) {
    return canal_carte_is_page() ? canal_carte_seo_title() : $title;
}, 20);

add_action('wp_head', function () {
    if (!canal_carte_is_page()) {
        return;
    }
    $url   = (string) get_permalink();
    $state = canal_carte_seo_state();
    echo canal_home_seo_social(canal_carte_seo_title(), canal_carte_seo_description($state['total']), $url, 'Le Canal du Midi : péniches, chemin de halage et vélo'); // phpcs:ignore — escapado dentro.
    echo canal_home_seo_jsonld(canal_carte_seo_graph($url, $state['results'], $state['total'])); // phpcs:ignore — JSON_HEX_TAG.
}, 5);

// Variantes filtradas (?q=, ?type=, « Autour de moi »…): no se indexan, pero sus enlaces sí se siguen.
add_filter('wp_robots', function (array $robots) {
    if (canal_carte_is_page() && canal_carte_has_filters(wp_unslash($_GET))) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
});
