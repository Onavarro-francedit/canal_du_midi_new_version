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

function canal_home_seo_title(): string
{
    return "Canal du Midi : bateaux sans permis, vélos, hébergements | L'Officiel";
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

function canal_home_seo_graph(string $url, array $sejours): array
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
                'sameAs'             => [CANAL_HOME_FACEBOOK],
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
                        'urlTemplate' => home_url('/explorer/') . '?type=prestataires-touristiques&search_keywords={search_term_string}',
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
            ],
            [
                '@type'       => 'TouristDestination',
                '@id'         => $canal,
                'name'        => 'Canal du Midi',
                'description' => "Voie navigable de 240 km et 63 écluses reliant Toulouse à l'étang de Thau, construite par Pierre-Paul Riquet, inaugurée en 1681 et inscrite au patrimoine mondial de l'UNESCO depuis 1996.",
                'sameAs'      => 'https://fr.wikipedia.org/wiki/Canal_du_Midi',
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

function canal_home_seo_head(string $url, array $sejours): string
{
    $title = canal_home_seo_title();
    $desc  = canal_home_seo_description();
    $image = home_url(CANAL_HOME_HERO_IMAGE);
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
    $out .= $meta('property', 'og:image:width', '1898');
    $out .= $meta('property', 'og:image:height', '682');
    $out .= $meta('name', 'twitter:card', 'summary_large_image');
    $out .= $meta('name', 'twitter:title', $title);
    $out .= $meta('name', 'twitter:description', $desc);
    $out .= $meta('name', 'twitter:image', $image);
    // JSON_HEX_TAG: '<' y '>' como </> → imposible cerrar el <script> desde los datos.
    $json = wp_json_encode(canal_home_seo_graph($url, $sejours), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $out .= '<script type="application/ld+json">' . $json . '</script>' . "\n";
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
    echo canal_home_seo_head($url, canal_home_seo_items()); // phpcs:ignore — escapado dentro.
}, 5);

// Rendimiento: conexión anticipada a Google Fonts (solo en esta página).
add_filter('wp_resource_hints', function ($urls, $relation) {
    if ($relation === 'preconnect' && canal_home_is_page()) {
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin'];
        $urls[] = 'https://fonts.googleapis.com';
    }
    return $urls;
}, 10, 2);
