<?php
/**
 * Datos de la carte (solo lectura): todas las fichas publicadas en un transient de 12 h.
 * Reutiliza los helpers de data.php (portada, commune, categoría principal).
 */
defined('ABSPATH') || exit;

const CANAL_CARTE_CACHE = 'canal_carte_listings_v2'; // v2: tag_slugs (TASK-064)

/** Clave de caché por modo: las URLs de las fichas cambian al publicar (/fiche-2026/ → /fiche/). */
function canal_carte_cache_key(): string
{
    return CANAL_CARTE_CACHE . (CANAL_2026_LIVE ? '_live' : '');
}
const CANAL_CARTE_GALLERY_MAX = 8;
const CANAL_CARTE_EAGER_IMAGES = 2; // tarjetas sin lazy: las visibles al cargar (1–2 en la columna; con 6 competían con el LCP, 06/10)

function canal_carte_listings(): array
{
    $cached = get_transient(canal_carte_cache_key());
    if (is_array($cached)) {
        return $cached;
    }
    // get_posts precarga metas y términos de todas las fichas (sin N+1).
    $posts = get_posts([
        'post_type'   => 'job_listing',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);
    $items = [];
    foreach ($posts as $post) {
        $terms = get_the_terms($post->ID, 'job_listing_category');
        $terms = ($terms && !is_wp_error($terms)) ? $terms : [];
        $slugs = [];
        foreach ($terms as $term) {
            $slugs[] = $term->slug;
            foreach (get_ancestors($term->term_id, 'job_listing_category', 'taxonomy') as $ancestorId) {
                $ancestor = get_term($ancestorId, 'job_listing_category');
                if ($ancestor instanceof WP_Term) {
                    $slugs[] = $ancestor->slug;
                }
            }
        }
        $lat = get_post_meta($post->ID, 'geolocation_lat', true);
        $lng = get_post_meta($post->ID, 'geolocation_long', true);
        $gallery = get_post_meta($post->ID, '_job_gallery', true);
        $desc = (string) get_post_meta($post->ID, '_job_description', true);
        if ($desc === '') {
            $desc = $post->post_content;
        }
        $desc = strip_shortcodes($desc); // WP: los shortcodes no deben verse en tarjetas ni alimentar la búsqueda
        $items[] = [
            'id'          => (int) $post->ID,
            'slug'        => $post->post_name,
            'title'       => canal_home_plain(get_the_title($post)),
            'url'         => canal_fiche_url($post->post_name),
            'lat'         => is_numeric($lat) ? (float) $lat : null,
            'lng'         => is_numeric($lng) ? (float) $lng : null,
            'image'       => canal_home_cover($post->ID),
            'gallery'     => is_array($gallery) ? array_slice(array_values(array_filter($gallery, 'is_string')), 0, CANAL_CARTE_GALLERY_MAX) : [],
            'address'     => canal_home_plain((string) get_post_meta($post->ID, '_job_location', true)),
            'description' => mb_substr(trim((string) preg_replace('/\s+/u', ' ', canal_home_plain(wp_strip_all_tags($desc)))), 0, 200, 'UTF-8'),
            'type'        => canal_home_category_label($post->ID),
            'cat_names'   => array_map('canal_home_plain', wp_list_pluck($terms, 'name')),
            'cat_slugs'   => array_values(array_unique($slugs)),
            'tag_slugs'   => array_values(wp_list_pluck(($tags = get_the_terms($post->ID, 'case27_job_listing_tags')) && !is_wp_error($tags) ? $tags : [], 'slug')),
            'city'        => canal_home_city($post->ID),
            'phone'       => trim((string) get_post_meta($post->ID, '_job_phone', true)),
            'email'       => sanitize_email((string) get_post_meta($post->ID, '_job_email', true)),
        ];
    }
    $items = canal_carte_resized_images($items);
    set_transient(canal_carte_cache_key(), $items, 12 * HOUR_IN_SECONDS);
    return $items;
}

// Portadas: _job_cover guarda la URL del original (hasta 1024 px) y la tarjeta la muestra a ~360 px.
// Se sustituye por el tamaño medium_large (768 px) + srcset de WordPress. Una sola consulta para
// todas (attachment_url_to_postid haría una por imagen: ~5 s al regenerar la caché).
function canal_carte_resized_images(array $items): array
{
    global $wpdb;
    $base  = set_url_scheme(trailingslashit(wp_get_upload_dir()['baseurl']), 'https');
    $paths = [];
    foreach ($items as $i => $item) {
        $items[$i]['image_srcset'] = '';
        $url = set_url_scheme($item['image'], 'https');
        if ($url !== '' && strpos($url, $base) === 0) {
            $paths[$i] = substr($url, strlen($base));
        }
    }
    if (!$paths) {
        return $items;
    }
    $unique = array_values(array_unique($paths));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value IN ("
            . implode(',', array_fill(0, count($unique), '%s')) . ')',
        $unique
    ));
    $ids = [];
    foreach ($rows as $row) {
        $ids[$row->meta_value] = (int) $row->post_id;
    }
    update_meta_cache('post', array_values($ids));
    foreach ($paths as $i => $path) {
        if (!isset($ids[$path])) {
            continue; // sin adjunto conocido: se queda la URL original
        }
        $src = wp_get_attachment_image_src($ids[$path], 'medium_large');
        if ($src) {
            $items[$i]['image']        = $src[0];
            $items[$i]['image_w']      = (int) $src[1];
            $items[$i]['image_h']      = (int) $src[2];
            $items[$i]['image_srcset'] = (string) wp_get_attachment_image_srcset($ids[$path], 'medium_large');
        }
    }
    return $items;
}

// Categorías con al menos una ficha; offers_count incluye las fichas de las subcategorías.
function canal_carte_categories(array $listings): array
{
    $terms = get_terms(['taxonomy' => 'job_listing_category', 'hide_empty' => false, 'orderby' => 'name']);
    if (is_wp_error($terms)) {
        return [];
    }
    $counts = [];
    foreach ($listings as $item) {
        foreach ($item['cat_slugs'] as $slug) {
            $counts[$slug] = ($counts[$slug] ?? 0) + 1;
        }
    }
    $out = [];
    foreach ($terms as $term) {
        if (empty($counts[$term->slug])) {
            continue;
        }
        $out[] = [
            'id'           => (int) $term->term_id,
            'slug'         => $term->slug,
            'name'         => canal_home_plain($term->name),
            'parent_id'    => (int) $term->parent,
            'offers_count' => $counts[$term->slug],
        ];
    }
    return $out;
}

// Mismas claves que searchResults en search_results.php (local): search-map.js no cambia.
function canal_carte_public(array $item): array
{
    return [
        'id'          => $item['id'],
        'slug'        => $item['slug'],
        'lat'         => $item['lat'],
        'lng'         => $item['lng'],
        'title'       => $item['title'],
        'image'       => $item['image'],
        'gallery'     => $item['gallery'],
        'address'     => $item['address'],
        'description' => $item['description'],
        'type'        => $item['type'],
        'label'       => '',
        'phone'       => $item['phone'],
        'email'       => $item['email'],
        'url'         => $item['url'],
        'distance_km' => $item['distance_km'] ?? null,
    ];
}

/** Fichas que mostraría la carte con esta búsqueda (mismos parámetros y filtro que la carte): para no enlazar a una carte vacía. */
function canal_carte_count(array $query): int
{
    static $listings = null, $slugs = null;
    if ($listings === null) {
        $listings = canal_carte_listings();
        $slugs = array_column(canal_carte_categories($listings), 'slug');
    }
    return count(canal_carte_filter($listings, canal_carte_params($query, $slugs)));
}

function canal_carte_flush(): void
{
    delete_transient(CANAL_CARTE_CACHE);
    delete_transient(CANAL_CARTE_CACHE . '_live');
}

// Solo borran nuestro transient: no tocan nada existente.
add_action('save_post_job_listing', 'canal_carte_flush');
add_action('edited_job_listing_category', 'canal_carte_flush');
add_action('deleted_post', function ($postId, $post = null) {
    if ($post instanceof WP_Post && $post->post_type === 'job_listing') {
        canal_carte_flush();
    }
}, 10, 2);
