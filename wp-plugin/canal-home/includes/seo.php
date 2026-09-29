<?php
/**
 * SEO/AEO de « Accueil 2026 »: <title>, meta description, Open Graph/Twitter y JSON-LD.
 * Solo se emite en la página con la plantilla del plugin (canal_home_is_page()); no hay
 * plugin SEO en el sitio, así que no hay duplicados. Nada de lo existente se modifica.
 */
defined('ABSPATH') || exit;

const CANAL_HOME_SITE_NAME = "L'Officiel du Canal du Midi";
const CANAL_HOME_LOGO      = '/wp-content/uploads/2020/04/logo_canal_nouveau_2020_v6.png';
const CANAL_HOME_FACEBOOK  = 'https://www.facebook.com/canaldumidi.officiel/';
const CANAL_HOME_INSTAGRAM = 'https://www.instagram.com/lofficielducanaldumidi/';
const CANAL_HOME_OG_IMAGE  = 'assets/og-canal-du-midi-1200x630.jpg';

function canal_home_seo_title(): string
{
    return "Canal du Midi : bateaux, vélos, hébergements | L'Officiel"; // ≤ 60 car.
}

function canal_home_seo_description(): string
{
    return 'Préparez votre séjour sur le Canal du Midi : bateaux sans permis, vélos, hébergements, restaurants et visites de Toulouse à Sète. Plan officiel 2026 gratuit.';
}

/** Séjours mostrados en la plantilla (la plantilla los registra antes de get_header()). */
function canal_home_seo_items(?array $items = null): array
{
    static $registered = [];
    if ($items !== null) {
        $registered = $items;
    }
    return $registered;
}

function canal_home_seo_graph(string $url, array $sejours, string $modified = ''): array
{
    $home  = home_url('/');
    $hero  = home_url(CANAL_HOME_HERO_IMAGE);
    $org   = $home . '#organization';
    $site  = $home . '#website';
    $canal = $home . '#canal-du-midi';

    $faq = [];
    foreach (CANAL_HOME_FAQ as $item) {
        $faq[] = [
            '@type'          => 'Question',
            'name'           => $item['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
        ];
    }
    $list = [];
    foreach (array_values($sejours) as $i => $s) {
        $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $s['url'], 'name' => $s['title']];
    }

    return [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'              => 'Organization',
                '@id'                => $org,
                'name'               => CANAL_HOME_SITE_NAME,
                'url'                => $home,
                'logo'               => home_url(CANAL_HOME_LOGO),
                'sameAs'             => [CANAL_HOME_FACEBOOK, CANAL_HOME_INSTAGRAM],
                'description'        => "Guide pratique et plan officiel du Canal du Midi, édité chaque année par Azur Communications.",
                'knowsAbout'         => ['Canal du Midi', 'Tourisme fluvial', 'Cyclotourisme', 'Navigation de plaisance', "Patrimoine mondial de l'UNESCO"],
                'parentOrganization' => [
                    '@type'     => 'Organization',
                    'name'      => 'Azur Communications',
                    'email'     => 'contact@azur-communications.fr',
                    'telephone' => '+33 4 68 62 31 62',
                ],
            ],
            [
                '@type'           => 'WebSite',
                '@id'             => $site,
                'url'             => $home,
                'name'            => CANAL_HOME_SITE_NAME,
                'inLanguage'      => 'fr-FR',
                'publisher'       => ['@id' => $org],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => home_url(CANAL_CARTE_PATH) . '?search_keywords={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type'              => 'WebPage',
                '@id'                => $url . '#webpage',
                'url'                => $url,
                'name'               => canal_home_seo_title(),
                'description'        => canal_home_seo_description(),
                'inLanguage'         => 'fr-FR',
                'isPartOf'           => ['@id' => $site],
                'about'              => ['@id' => $canal],
                'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $hero, 'width' => 1898, 'height' => 682],
                'dateModified'       => $modified,
                // Fragmentos aptos para lectura en voz alta (asistentes de voz / AEO).
                'speakable'          => [
                    '@type'       => 'SpeakableSpecification',
                    'cssSelector' => ['#etapes .section-heading p', '#faq .faq-item p'],
                ],
            ],
            [
                '@type'       => 'TouristDestination',
                '@id'         => $canal,
                'name'        => 'Canal du Midi',
                'description' => "Voie navigable de 240 km et 63 écluses reliant Toulouse à l'étang de Thau, construite par Pierre-Paul Riquet, inaugurée en 1681 et inscrite au patrimoine mondial de l'UNESCO depuis 1996.",
                'sameAs'      => [
                    'https://fr.wikipedia.org/wiki/Canal_du_Midi',
                    'https://www.wikidata.org/wiki/Q202494',
                    'https://whc.unesco.org/fr/list/770/',
                ],
                'touristType' => ['Tourisme fluvial', 'Cyclotourisme', 'Tourisme culturel'],
            ],
            [
                '@type'           => 'ItemList',
                'name'            => 'Croisières, balades et excursions',
                'itemListElement' => $list,
            ],
            [
                '@type'      => 'FAQPage',
                'mainEntity' => $faq,
            ],
        ],
    ];
}

// Meta description + Open Graph + Twitter card. Compartido por la home y la carte.
function canal_home_seo_social(string $title, string $desc, string $url, string $imageAlt): string
{
    $image = CANAL_HOME_URL . CANAL_HOME_OG_IMAGE;
    $meta  = function (string $attr, string $key, string $value): string {
        return '<meta ' . $attr . '="' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n";
    };
    $out  = $meta('name', 'description', $desc);
    $out .= $meta('property', 'og:type', 'website');
    $out .= $meta('property', 'og:locale', 'fr_FR');
    $out .= $meta('property', 'og:site_name', CANAL_HOME_SITE_NAME);
    $out .= $meta('property', 'og:title', $title);
    $out .= $meta('property', 'og:description', $desc);
    $out .= $meta('property', 'og:url', $url);
    $out .= $meta('property', 'og:image', $image);
    $out .= $meta('property', 'og:image:width', '1200');
    $out .= $meta('property', 'og:image:height', '630');
    $out .= $meta('property', 'og:image:alt', $imageAlt);
    $out .= $meta('name', 'twitter:card', 'summary_large_image');
    $out .= $meta('name', 'twitter:title', $title);
    $out .= $meta('name', 'twitter:description', $desc);
    $out .= $meta('name', 'twitter:image', $image);
    return $out;
}

// JSON-LD en un <script>. JSON_HEX_TAG: '<' y '>' como \u003C/\u003E → imposible cerrar el <script> desde los datos.
function canal_home_seo_jsonld(array $graph): string
{
    return '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . "\n";
}

function canal_home_seo_head(string $url, array $sejours, string $modified = ''): string
{
    $out  = canal_home_seo_social(canal_home_seo_title(), canal_home_seo_description(), $url, 'Le Canal du Midi : péniches, chemin de halage et vélo');
    $out .= '<link rel="alternate" type="text/markdown" title="llms.txt" href="' . esc_url(home_url('/llms.txt')) . '">' . "\n";
    $out .= canal_home_seo_jsonld(canal_home_seo_graph($url, $sejours, $modified));
    return $out;
}

add_filter('pre_get_document_title', function ($title) {
    return canal_home_is_page() ? canal_home_seo_title() : $title;
}, 20);

add_action('wp_head', function () {
    if (!canal_home_is_page()) {
        return;
    }
    $url = is_front_page() ? home_url('/') : (string) get_permalink();
    echo canal_home_seo_head($url, canal_home_seo_items(), (string) get_the_modified_date('c', get_queried_object_id())); // phpcs:ignore — escapado dentro.
}, 5);

// Rendimiento: conexión anticipada a Google Fonts (solo en esta página).
add_filter('wp_resource_hints', function ($urls, $relation) {
    if ($relation === 'preconnect' && canal_home_is_page()) {
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin'];
        $urls[] = 'https://fonts.googleapis.com';
    }
    return $urls;
}, 10, 2);

// Cabecera Link hacia llms.txt en la home (descubrimiento por agentes de IA).
add_action('template_redirect', function () {
    if (canal_home_is_page() && !headers_sent()) {
        header('Link: <' . esc_url_raw(home_url('/llms.txt')) . '>; rel="llms-txt"', false);
    }
});

// ── Ajustes de todo el sitio autorizados por el usuario (2026-09-29, auditoría seo-geo) ──
// Sitemaps: fuera el listado de autores (expone slugs de cuentas, uno derivado de un e-mail) y
// la biblioteca de plantillas de Elementor (no es contenido público).
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);
add_filter('wp_sitemaps_post_types', function ($types) {
    unset($types['elementor_library']);
    return $types;
});
// No publicar la versión de WordPress ni la de PHP.
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
// Cabeceras de seguridad. HSTS sin includeSubDomains/preload (no se controla cada subdominio).
add_action('send_headers', function () {
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
});
