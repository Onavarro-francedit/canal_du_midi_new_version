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
    if (!$post || !canal_fiche_can_view(current_user_can('read_private_pages'), get_option('canal_fiche_public'))) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    canal_fiche_state(canal_fiche_data($post));
    $wp_query->is_home = false;
    $wp_query->is_404  = false;
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true); // otros plugins de caché; WPFC no lo respeta (ver purga más abajo)
    }
    nocache_headers();
    status_header(200);
    header('Link: <' . esc_url_raw(home_url('/llms.txt')) . '>; rel="llms-txt"', false); // como la home y la carte
    include CANAL_HOME_DIR . 'template-fiche.php';
    exit;
}, 0);

// WPFC no respeta DONOTCACHEPAGE (solo en 403/503): al abrir o cerrar la ficha 2026 se vacía la caché de página.
foreach (['add_option_', 'update_option_', 'delete_option_'] as $canal_fiche_prefix) {
    add_action($canal_fiche_prefix . 'canal_fiche_public', function () {
        do_action('wpfc_clear_all_cache');
    });
}
unset($canal_fiche_prefix);

add_action('wp_enqueue_scripts', function () {
    if (!canal_fiche_is_page()) {
        return;
    }
    $ver = function (string $rel): string { return (string) filemtime(CANAL_HOME_DIR . $rel); };
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    wp_enqueue_style('canal-fiche', CANAL_HOME_URL . 'assets/fiche.css', [], $ver('assets/fiche.css'));
    wp_add_inline_style('canal-fiche', '.loader-bg.main-loader{display:none!important}');
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], $ver('assets/header.css'));
    // Lo que la ficha usa del CSS del tema (base Bootstrap, pie, iconos del pie), después de lo nuestro como antes.
    wp_enqueue_style('canal-fiche-theme', CANAL_HOME_URL . 'assets/fiche-theme.css', ['canal-fiche', 'canal-home-header'], $ver('assets/fiche-theme.css'));
    wp_add_inline_style('canal-fiche-theme', CANAL_THEME_FIX_CSS);
    // fiche.js arranca en DOMContentLoaded; el mapa (Google Maps) se carga en diferido desde él.
    wp_enqueue_script('canal-fiche', CANAL_HOME_URL . 'assets/fiche/fiche.js', [], $ver('assets/fiche/fiche.js'), true);
}, 20);

// Google Maps se quita de la cola en la ficha (canal_fiche_is_unused_asset) y fiche.js lo carga en diferido
// con la misma URL (clave y bibliotecas) que registra el tema. El tema lo registra tarde: se lee justo antes
// de imprimir los scripts del pie.
add_action('wp_print_footer_scripts', function () {
    if (!canal_fiche_is_page()) {
        return;
    }
    $maps = wp_scripts()->registered['google-maps'] ?? null;
    wp_add_inline_script('canal-fiche', 'window.CDM_FICHE = ' . wp_json_encode(['mapsSrc' => $maps ? (string) $maps->src : '']) . ';', 'before');
}, 1);

// Pie del tema: los enlaces de redes son solo un icono (Lighthouse: « enlaces sin nombre »). Solo en nuestras páginas.
add_filter('nav_menu_link_attributes', function ($atts, $item) {
    if (canal_fiche_is_page() || canal_home_is_page() || canal_carte_is_page() || canal_contenu_is_page() || canal_calcul_is_page() || canal_archive_is_page()) {
        $label = canal_fiche_icon_link_label((string) $item->title, (string) ($atts['href'] ?? ''));
        if ($label !== '' && empty($atts['aria-label'])) {
            $atts['aria-label'] = $label;
        }
    }
    return $atts;
}, 10, 2);
