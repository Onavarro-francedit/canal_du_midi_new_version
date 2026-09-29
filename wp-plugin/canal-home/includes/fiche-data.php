<?php
/**
 * Datos de una ficha (solo lectura). Metas vistas en producción el 29/09 (ver spec).
 */
defined('ABSPATH') || exit;

// get_page_by_path respeta los slugs codificados (%c3%a9…), a diferencia de sanitize_title.
function canal_fiche_post(string $slug): ?WP_Post
{
    if ($slug === '') {
        return null;
    }
    $post = get_page_by_path($slug, OBJECT, 'job_listing');
    return ($post instanceof WP_Post && $post->post_status === 'publish') ? $post : null;
}

// Hay galerías guardadas en http:// → contenido mixto en una página https.
function canal_fiche_https(string $url): string
{
    return $url === '' ? '' : set_url_scheme($url, 'https');
}

function canal_fiche_data(WP_Post $post): array
{
    $id = $post->ID;
    $meta = function (string $key) use ($id): string {
        $v = get_post_meta($id, $key, true);
        return is_string($v) ? trim($v) : '';
    };
    $desc = strip_shortcodes($meta('_job_description') !== '' ? $meta('_job_description') : $post->post_content);
    $gallery = get_post_meta($id, '_job_gallery', true);
    $gallery = array_values(array_unique(array_map('canal_fiche_https', array_filter(is_array($gallery) ? $gallery : [], 'is_string'))));
    $zones = get_post_meta($id, '_zone', true);
    $zones = is_array($zones) ? array_values(array_filter($zones, 'is_string')) : ($zones ? [(string) $zones] : []);
    $terms = get_the_terms($id, 'job_listing_category');
    $categories = [];
    foreach (($terms && !is_wp_error($terms)) ? $terms : [] as $term) {
        $categories[] = ['name' => canal_home_plain($term->name), 'slug' => $term->slug];
    }
    $lat = get_post_meta($id, 'geolocation_lat', true);
    $lng = get_post_meta($id, 'geolocation_long', true);
    $lat = canal_fiche_has_coords($lat, $lng) ? (float) $lat : null;
    $lng = $lat !== null ? (float) $lng : null;
    $website = $meta('_job_website');

    return [
        'id'               => $id,
        'slug'             => $post->post_name,
        'title'            => canal_home_plain(get_the_title($post)),
        'description_html' => wpautop(wp_kses_post($desc)),
        'excerpt'          => canal_fiche_excerpt(canal_home_plain(wp_strip_all_tags($desc))),
        'cover'            => canal_fiche_https(canal_home_cover($id)),
        'gallery'          => $gallery,
        'address'          => canal_home_plain($meta('_job_location')),
        'city'             => canal_home_city($id),
        'postcode'         => $meta('_code-postal'),
        'zones'            => $zones,
        'lat'              => $lat,
        'lng'              => $lng,
        'phone'            => $meta('_job_phone'),
        'mobile'           => $meta('_telephone-portable'),
        'fax'              => $meta('_fax'),
        'email'            => sanitize_email($meta('_job_email')),
        'website'          => $website !== '' ? esc_url_raw($website) : '',
        'social'           => canal_fiche_social($meta('_facebook'), get_post_meta($id, '_links', true)),
        'video'            => canal_fiche_video_embed($meta('_job_video_url')),
        'categories'       => $categories,
        'nearby'           => canal_fiche_nearby(canal_carte_listings(), $id, $lat, $lng),
    ];
}
