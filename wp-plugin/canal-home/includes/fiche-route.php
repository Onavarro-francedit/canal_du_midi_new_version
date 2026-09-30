<?php
/**
 * Ruta /fiche-2026/<slug>/: regla de reescritura propia (no toca las /fiche/ del tema).
 * Privada: sin sesión con read_private_pages → 404 del tema.
 */
defined('ABSPATH') || exit;

function canal_fiche_url(string $slug): string
{
    return home_url(CANAL_FICHE_PATH . $slug . '/'); // post_name ya viene codificado
}

// Estado de la petición (ficha resuelta); vacío fuera de la ficha.
function canal_fiche_state(?array $set = null): array
{
    static $state = [];
    if ($set !== null) {
        $state = $set;
    }
    return $state;
}

function canal_fiche_is_page(): bool
{
    return canal_fiche_state() !== [];
}

add_action('init', function () {
    add_rewrite_tag('%canal_fiche%', '([^/]+)');
    add_rewrite_rule('^' . trim(CANAL_FICHE_PATH, '/') . '/([^/]+)/?$', 'index.php?canal_fiche=$matches[1]', 'top');
    // Flush una sola vez por ruta: rewrite_rules es una caché derivada; añadir la regla no altera las demás.
    if (get_option('canal_fiche_rewrite') !== CANAL_FICHE_PATH . '1') {
        flush_rewrite_rules(false);
        update_option('canal_fiche_rewrite', CANAL_FICHE_PATH . '1', false);
    }
});

add_action('template_redirect', function () {
    $slug = get_query_var('canal_fiche');
    if (!is_string($slug) || $slug === '') {
        return;
    }
    global $wp_query;
    $post = canal_fiche_post($slug);
    // Una query var sola deja is_home = true: sin set_404 se vería la portada del blog.
    if (!$post || !current_user_can('read_private_pages')) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    canal_fiche_state(canal_fiche_data($post));
    $wp_query->is_home = false;
    $wp_query->is_404  = false;
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true); // WP Fastest Cache: página privada
    }
    nocache_headers();
    status_header(200);
    header('Link: <' . esc_url_raw(home_url('/llms.txt')) . '>; rel="llms-txt"', false); // como la home y la carte
    include CANAL_HOME_DIR . 'template-fiche.php';
    exit;
}, 0);

// header.css se aplica a body.page-template-canal-home (cabecera del tema restilizada, como home y carte).
add_filter('body_class', function (array $classes) {
    if (canal_fiche_is_page()) {
        $classes[] = 'page-template-canal-home';
        $classes[] = 'cdm-fiche-page';
    }
    return $classes;
});

add_action('wp_enqueue_scripts', function () {
    if (!canal_fiche_is_page()) {
        return;
    }
    $ver = function (string $rel): string { return (string) filemtime(CANAL_HOME_DIR . $rel); };
    wp_enqueue_style('canal-home-fonts', CANAL_HOME_FONTS_URL, [], null);
    wp_enqueue_style('canal-home-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css', [], '1.11.1');
    wp_enqueue_style('canal-fiche', CANAL_HOME_URL . 'assets/fiche.css', [], $ver('assets/fiche.css'));
    wp_add_inline_style('canal-fiche', '.loader-bg.main-loader{display:none!important}');
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], $ver('assets/header.css'));
    // Google Maps lo carga ya el tema (footer, síncrono); fiche.js arranca en DOMContentLoaded.
    wp_enqueue_script('canal-fiche', CANAL_HOME_URL . 'assets/fiche/fiche.js', [], $ver('assets/fiche/fiche.js'), true);
    wp_add_inline_script('canal-fiche', canal_home_carte_menu_js());
}, 20);
