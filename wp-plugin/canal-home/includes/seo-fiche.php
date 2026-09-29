<?php
/**
 * SEO de la ficha 2026: <title>, meta description, OG/Twitter, canonical, JSON-LD y robots.
 * Mientras la ruta sea -2026: noindex,nofollow (página privada).
 */
defined('ABSPATH') || exit;

function canal_fiche_seo_title(array $f): string
{
    return $f['title'] . ($f['city'] !== '' ? ' à ' . $f['city'] : '') . ' — Canal du Midi';
}

function canal_fiche_seo_description(array $f): string
{
    if ($f['excerpt'] !== '') {
        return $f['excerpt'];
    }
    $cat = $f['categories'][0]['name'] ?? 'Prestataire touristique';
    return canal_fiche_excerpt($cat . ' ' . ($f['city'] !== '' ? 'à ' . $f['city'] . ' ' : '') . 'au bord du Canal du Midi : coordonnées, accès et prestataires à proximité.');
}

add_filter('pre_get_document_title', function ($title) {
    return canal_fiche_is_page() ? canal_fiche_seo_title(canal_fiche_state()) : $title;
}, 20);

add_filter('wp_robots', function (array $robots) {
    if (canal_fiche_is_page() && CANAL_FICHE_PATH !== '/fiche/') {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['follow'], $robots['index']);
    }
    return $robots;
});

add_action('wp_head', function () {
    if (!canal_fiche_is_page()) {
        return;
    }
    $f   = canal_fiche_state();
    $url = canal_fiche_url($f['slug']);
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    echo canal_home_seo_social(canal_fiche_seo_title($f), canal_fiche_seo_description($f), $url, $f['title'], $f['cover']); // phpcs:ignore — escapado dentro.
    echo canal_home_seo_jsonld(canal_fiche_seo_graph($f, $url, home_url('/'), home_url(CANAL_CARTE_PATH))); // phpcs:ignore — JSON_HEX_TAG.
}, 5);
