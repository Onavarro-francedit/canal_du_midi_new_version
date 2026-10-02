<?php
/**
 * Lecturas de datos de WordPress para la home (solo lectura).
 */
defined('ABSPATH') || exit;

const CANAL_HOME_HERO_IMAGE = '/wp-content/uploads/2025/04/img_couv_site_2025_v2.jpg';
const CANAL_HOME_TYPE_WHITELIST = [
    'hotel', 'chambre-a-louer', 'appartement-maison-a-louer', 'camping', 'restaurant', 'nautique',
    'peniche', 'location-de-velo', 'excursions', 'chateaux', 'musees', 'oenotourisme',
];
// slug de la taxonomía region ⇒ etiqueta (los términos están en MAYÚSCULAS y sin algunos acentos).
const CANAL_HOME_STAGES = [
    'toulouse' => 'Toulouse', 'castelnaudary' => 'Castelnaudary', 'carcassonne' => 'Carcassonne',
    'trebes' => 'Trèbes', 'homps' => 'Homps', 'argens-minervois' => 'Argens-Minervois',
    'beziers' => 'Béziers', 'agde' => 'Agde', 'sete' => 'Sète',
];
// Categorías de « Croisières, balades et excursions ». NO usar 'nautique': sus hijas incluyen
// ecluses (72) y ports (21), que llenaban la sección de esclusas.
const CANAL_HOME_SEJOUR_CATS = [
    'croisiere-bateau', 'location-bateau', 'peniche', 'excursions', 'location-de-velo', 'location-de-canoe-kayak',
];

function canal_home_plain(string $s): string
{
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function canal_home_terms_by_slugs(array $slugs, string $taxonomy): array
{
    $out = [];
    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term && !is_wp_error($term)) {
            $out[$term->slug] = canal_home_plain($term->name);
        }
    }
    return $out;
}

function canal_home_hero_types(): array
{
    return canal_home_terms_by_slugs(CANAL_HOME_TYPE_WHITELIST, 'job_listing_category');
}

function canal_home_stages(): array
{
    // Solo etapas que existen como término region (el filtro de /explorer/ usa el slug).
    return array_intersect_key(CANAL_HOME_STAGES, canal_home_terms_by_slugs(array_keys(CANAL_HOME_STAGES), 'region'));
}

// Selección fija (SEO: la home no debe cambiar de contenido en cada petición ni chocar con la
// caché de página). Categorías de intención turística alta, todas con imagen.
const CANAL_HOME_CATEGORY_SLUGS = [
    'location-bateau', 'croisiere-bateau', 'location-de-velo', 'hotel', 'chambre-dhotes', 'restaurant',
];

function canal_home_categories(int $limit = 6): array
{
    $terms = [];
    foreach (CANAL_HOME_CATEGORY_SLUGS as $slug) {
        $term = get_term_by('slug', $slug, 'job_listing_category');
        if ($term && !is_wp_error($term)) {
            $terms[] = $term;
        }
    }
    $out = [];
    foreach ($terms as $term) {
        // hide_empty conserva padres sin fichas propias (count 0) si tienen hijas con fichas.
        if ((int) $term->count === 0) {
            continue;
        }
        $link = get_term_link($term);
        if (is_wp_error($link)) {
            continue;
        }
        $imageId = (int) get_term_meta($term->term_id, 'image', true);
        $image = $imageId ? (string) wp_get_attachment_image_url($imageId, 'medium_large') : '';
        $out[] = [
            'name'  => canal_home_plain($term->name),
            'url'   => $link,
            'image' => $image !== '' ? $image : home_url(CANAL_HOME_HERO_IMAGE),
            'count' => (int) $term->count,
        ];
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/**
 * Valor CSS url("…") seguro para un atributo style: las comillas y paréntesis se codifican
 * (esc_url deja ' como &#039;, que el navegador decodifica dentro del atributo).
 */
function canal_home_css_url(string $url): string
{
    $safe = str_replace(["'", '"', '(', ')', ' '], ['%27', '%22', '%28', '%29', '%20'], esc_url_raw($url));
    return 'url(&quot;' . esc_attr($safe) . '&quot;)';
}

function canal_home_cover(int $postId): string
{
    // _job_cover es un array serializado de URLs.
    $cover = get_post_meta($postId, '_job_cover', true);
    if (is_array($cover)) {
        $cover = reset($cover);
    }
    return is_string($cover) ? $cover : '';
}

// Las tarjetas se ven a ~350 px: usar la talla de WP « medium_large » (768) en vez del original
// (hasta 280 KB). Si la URL no es de la mediateca, se devuelve tal cual.
function canal_home_cover_card(int $postId): string
{
    $cover = canal_home_cover($postId);
    $id    = $cover !== '' ? attachment_url_to_postid($cover) : 0;
    $small = $id ? wp_get_attachment_image_url($id, 'medium_large') : false;
    return $small ?: $cover;
}

// Términos region sin acentos ni guiones en la BD (no se modifican): grafía correcta solo al mostrar.
const CANAL_HOME_CITY_FIX = [
    'Beziers' => 'Béziers', 'Montreal' => 'Montréal', 'Avignonet Lauragais' => 'Avignonet-Lauragais',
    'Nissan-Lez-Enserune' => 'Nissan-lez-Ensérune', "Saint-Nazaire-D'aude" => "Saint-Nazaire-d'Aude",
];

function canal_home_city(int $postId): string
{
    $regions = get_the_terms($postId, 'region');
    if (!$regions || is_wp_error($regions)) {
        return '';
    }
    // Los términos region están en MAYÚSCULAS («TOULOUSE») → «Toulouse».
    $city = mb_convert_case(canal_home_plain($regions[0]->name), MB_CASE_TITLE, 'UTF-8');
    return CANAL_HOME_CITY_FIX[$city] ?? $city;
}

function canal_home_category_label(int $postId, array $preferred = []): string
{
    $terms = get_the_terms($postId, 'job_listing_category');
    if (!$terms || is_wp_error($terms)) {
        return '';
    }
    foreach ($terms as $term) {
        if (in_array($term->slug, $preferred, true)) {
            return canal_home_plain($term->name);
        }
    }
    return canal_home_plain($terms[0]->name);
}

function canal_home_card(WP_Post $post, array $preferred = []): array
{
    $cover = canal_home_cover_card($post->ID);
    return [
        'title'    => canal_home_plain(get_the_title($post)),
        'slug'     => $post->post_name,
        'url'      => canal_fiche_url($post->post_name),
        'image'    => $cover !== '' ? $cover : home_url(CANAL_HOME_HERO_IMAGE),
        'category' => canal_home_category_label($post->ID, $preferred),
        'city'     => canal_home_city($post->ID),
    ];
}

// 8 fichas deterministas con rotación diaria: estables durante el día (SEO, caché de página)
// y distintas de un día a otro para repartir la visibilidad entre prestatarios.
function canal_home_sejours(int $limit = 8): array
{
    $ids = get_posts([
        'post_type'      => 'job_listing',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'tax_query'      => [[
            'taxonomy' => 'job_listing_category',
            'field'    => 'slug',
            'terms'    => CANAL_HOME_SEJOUR_CATS,
            'include_children' => false,
        ]],
    ]);
    $count = count($ids);
    if ($count === 0) {
        return [];
    }
    $offset = ((int) gmdate('z') * $limit) % $count;
    $picked = array_slice(array_merge(array_slice($ids, $offset), array_slice($ids, 0, $offset)), 0, min($limit, $count));
    return array_map(function ($id) {
        return canal_home_card(get_post($id), CANAL_HOME_SEJOUR_CATS);
    }, $picked);
}

function canal_home_card_by_slug(string $slug): ?array
{
    $post = get_page_by_path($slug, OBJECT, 'job_listing');
    return ($post instanceof WP_Post && $post->post_status === 'publish') ? canal_home_card($post) : null;
}

function canal_home_ai_catalog(): array
{
    $cached = get_transient('canal_home_ai_catalog');
    if (is_array($cached)) {
        return $cached;
    }
    // Orden por ID → texto determinista → prefijo cacheable estable.
    $posts = get_posts([
        'post_type'   => 'job_listing',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'ID',
        'order'       => 'ASC',
    ]);
    $items = [];
    foreach ($posts as $post) {
        $cats = get_the_terms($post->ID, 'job_listing_category');
        $desc = (string) get_post_meta($post->ID, '_job_description', true);
        if ($desc === '') {
            $desc = $post->post_content;
        }
        $items[] = [
            'slug'       => $post->post_name,
            'title'      => canal_home_plain(get_the_title($post)),
            'categories' => ($cats && !is_wp_error($cats)) ? array_map('canal_home_plain', wp_list_pluck($cats, 'name')) : [],
            'city'       => canal_home_city($post->ID),
            'excerpt'    => mb_substr(trim(canal_home_plain(wp_strip_all_tags($desc))), 0, 200, 'UTF-8'),
        ];
    }
    set_transient('canal_home_ai_catalog', $items, 12 * HOUR_IN_SECONDS);
    return $items;
}
