<?php
/**
 * Datos de la carte (solo lectura): todas las fichas publicadas en un transient de 12 h.
 * Reutiliza los helpers de data.php (portada, commune, categoría principal).
 */
defined('ABSPATH') || exit;

const CANAL_CARTE_CACHE = 'canal_carte_listings';
const CANAL_CARTE_GALLERY_MAX = 8;

function canal_carte_listings(): array
{
    $cached = get_transient(CANAL_CARTE_CACHE);
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
            'url'         => (string) get_permalink($post),
            'lat'         => is_numeric($lat) ? (float) $lat : null,
            'lng'         => is_numeric($lng) ? (float) $lng : null,
            'image'       => canal_home_cover($post->ID),
            'gallery'     => is_array($gallery) ? array_slice(array_values(array_filter($gallery, 'is_string')), 0, CANAL_CARTE_GALLERY_MAX) : [],
            'address'     => canal_home_plain((string) get_post_meta($post->ID, '_job_location', true)),
            'description' => mb_substr(trim((string) preg_replace('/\s+/u', ' ', canal_home_plain(wp_strip_all_tags($desc)))), 0, 200, 'UTF-8'),
            'type'        => canal_home_category_label($post->ID),
            'cat_names'   => array_map('canal_home_plain', wp_list_pluck($terms, 'name')),
            'cat_slugs'   => array_values(array_unique($slugs)),
            'city'        => canal_home_city($post->ID),
            'phone'       => trim((string) get_post_meta($post->ID, '_job_phone', true)),
            'email'       => sanitize_email((string) get_post_meta($post->ID, '_job_email', true)),
        ];
    }
    set_transient(CANAL_CARTE_CACHE, $items, 12 * HOUR_IN_SECONDS);
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

function canal_carte_flush(): void
{
    delete_transient(CANAL_CARTE_CACHE);
}

// Solo borran nuestro transient: no tocan nada existente.
add_action('save_post_job_listing', 'canal_carte_flush');
add_action('edited_job_listing_category', 'canal_carte_flush');
add_action('deleted_post', function ($postId, $post = null) {
    if ($post instanceof WP_Post && $post->post_type === 'job_listing') {
        canal_carte_flush();
    }
}, 10, 2);
