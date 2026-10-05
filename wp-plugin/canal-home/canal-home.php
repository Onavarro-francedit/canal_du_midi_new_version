<?php
/**
 * Plugin Name: Canal Home 2026
 * Description: Plantilla de página « Accueil 2026 » (nuevo diseño de la home) + asistente IA. No modifica ninguna página existente.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: France Édition
 */
defined('ABSPATH') || exit;

define('CANAL_HOME_DIR', plugin_dir_path(__FILE__));
define('CANAL_HOME_URL', plugin_dir_url(__FILE__));
define('CANAL_HOME_VERSION', '1.0.0');
define('CANAL_HOME_TEMPLATE', 'canal-home/template-home.php');
define('CANAL_CARTE_TEMPLATE', 'canal-home/template-carte.php');
// Las páginas nuevas llevan el sufijo -2026 (home: /accueil-2026/); al publicar se quita el sufijo.
const CANAL_CARTE_PATH = '/explorer-2026/';
// Home nueva (página 18500): la cabecera propia enlaza a sus secciones. Al publicar → '/' (TASK-028).
const CANAL_HOME_PATH = '/accueil-2026/';
// Ficha nueva: /fiche-2026/<slug>/ (regla propia). Al publicar → '/fiche/' (TASK-030b).
const CANAL_FICHE_PATH = '/fiche-2026/';
// Planificateur 2026 (TASK-044): página privada. Al publicar → '/planificateur/' o la que se decida.
const CANAL_PLANNER_PATH = '/planificateur-2026/';
define('CANAL_PLANNER_TEMPLATE', 'canal-home/template-planner.php');

// TASK-048: fuentes e iconos propios, sin CDN ni hojas externas. Fuentes woff2 latin autoalojadas (Sora y Manrope
// variables; Playfair Display solo 700 normal e itálica: es el h1 de la home) + máscaras SVG de icons.css
// (generado por build-css.mjs). Todo en línea: cero peticiones antes del primer render.
function canal_home_base_css(): string
{
    $font = function (string $family, string $file, string $weight, string $style = 'normal'): string {
        return '@font-face{font-family:"' . $family . '";font-style:' . $style . ';font-weight:' . $weight . ';font-display:swap;'
            . 'src:url("' . esc_url_raw(CANAL_HOME_URL . 'assets/fonts/' . $file) . '") format("woff2");'
            . 'unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}';
    };
    return $font('Sora', 'sora.woff2', '100 800')
        . $font('Manrope', 'manrope.woff2', '200 800')
        . $font('Playfair Display', 'playfair-display-700.woff2', '700')
        . $font('Playfair Display', 'playfair-display-700-italic.woff2', '700', 'italic')
        . (string) file_get_contents(CANAL_HOME_DIR . 'assets/icons.css');
}

// Hoja en línea (sin petición): handle + dependencias como una hoja normal, así se conserva el orden de impresión.
// Los archivos son nuestros (build), sin « </style> »: lo comprueba tests/test-fiche.php.
function canal_home_inline_style(string $handle, string $css, array $deps = []): void
{
    wp_register_style($handle, false, $deps);
    wp_enqueue_style($handle);
    wp_add_inline_style($handle, canal_home_minify_css($css));
}

function canal_home_inline_file(string $handle, string $rel, array $deps = []): void
{
    canal_home_inline_style($handle, (string) file_get_contents(CANAL_HOME_DIR . $rel), $deps);
}

require_once CANAL_HOME_DIR . 'includes/ai-core.php';
require_once CANAL_HOME_DIR . 'includes/data.php';
require_once CANAL_HOME_DIR . 'includes/content.php';
require_once CANAL_HOME_DIR . 'includes/limits.php';
require_once CANAL_HOME_DIR . 'includes/ai.php';
require_once CANAL_HOME_DIR . 'includes/plan.php';
require_once CANAL_HOME_DIR . 'includes/planner-core.php';
require_once CANAL_HOME_DIR . 'includes/planner.php';
require_once CANAL_HOME_DIR . 'includes/seo.php';
require_once CANAL_HOME_DIR . 'includes/carte-filter.php';
require_once CANAL_HOME_DIR . 'includes/carte-data.php';
require_once CANAL_HOME_DIR . 'includes/carte-faq.php';
require_once CANAL_HOME_DIR . 'includes/seo-carte.php';
require_once CANAL_HOME_DIR . 'includes/fiche-core.php';
require_once CANAL_HOME_DIR . 'includes/fiche-data.php';
require_once CANAL_HOME_DIR . 'includes/fiche-route.php';
require_once CANAL_HOME_DIR . 'includes/seo-fiche.php';
require_once CANAL_HOME_DIR . 'includes/contenu-core.php';
require_once CANAL_HOME_DIR . 'includes/contenu-route.php';
require_once CANAL_HOME_DIR . 'includes/calcul-core.php';
require_once CANAL_HOME_DIR . 'includes/calcul-route.php';
require_once CANAL_HOME_DIR . 'includes/head-fix.php';
require_once CANAL_HOME_DIR . 'includes/header.php';

add_filter('theme_page_templates', function ($templates) {
    $templates[CANAL_HOME_TEMPLATE] = 'Accueil 2026';
    $templates[CANAL_CARTE_TEMPLATE] = 'Carte interactive';
    $templates[CANAL_PLANNER_TEMPLATE] = 'Planificateur 2026';
    return $templates;
});

function canal_home_is_page(): bool
{
    return is_page() && get_page_template_slug(get_queried_object_id()) === CANAL_HOME_TEMPLATE;
}

function canal_carte_is_page(): bool
{
    return is_page() && get_page_template_slug(get_queried_object_id()) === CANAL_CARTE_TEMPLATE;
}

function canal_planner_is_page(): bool
{
    return is_page() && get_page_template_slug(get_queried_object_id()) === CANAL_PLANNER_TEMPLATE;
}

add_filter('template_include', function ($template) {
    if (canal_home_is_page()) {
        return CANAL_HOME_DIR . 'template-home.php';
    }
    if (canal_planner_is_page()) {
        return CANAL_HOME_DIR . 'template-planner.php';
    }
    return canal_carte_is_page() ? CANAL_HOME_DIR . 'template-carte.php' : $template;
});

// Prioridad 20: después de los estilos del tema, para ganar a igual especificidad.
add_action('wp_enqueue_scripts', function () {
    if (!canal_home_is_page()) {
        return;
    }
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    // CSS de la home en línea (TASK-048): sin la petición que bloqueaba el primer render. Carte y ficha no:
    // su CSS (~0,2 MB) pesaría más en cada HTML que lo que ahorra.
    canal_home_inline_file('canal-home', 'assets/home.css', ['canal-home-base']);
    canal_home_inline_file('canal-home-header', 'assets/header.css', ['canal-home-base']);
    // Lo que la home usa del CSS del tema y de los plugins (TASK-034), después de lo nuestro como antes.
    canal_home_inline_file('canal-home-theme', 'assets/home-theme.css', ['canal-home', 'canal-home-header']);
    wp_add_inline_style('canal-home-theme', CANAL_THEME_FIX_CSS);
    wp_enqueue_script('canal-home', CANAL_HOME_URL . 'assets/home.js', [], (string) filemtime(CANAL_HOME_DIR . 'assets/home.js'), true);
    wp_localize_script('canal-home', 'CDM_HOME', [
        'aiUrl'       => rest_url('canal-home/v1/ai'),
        'carteUrl'    => home_url(CANAL_CARTE_PATH),
    ]);
}, 20);

// Carte, ficha y home: plugins que se cargan en todo el sitio y estas páginas no usan (0 elementos Elementor,
// sin formularios de CF7, tablas ni tienda; el mini-carrito de WooCommerce del tema está vacío y oculto).
// Solo se quitan de la cola en esta plantilla; si algo que se mantiene depende de ellos, WordPress
// los vuelve a imprimir como dependencia.
const CANAL_CARTE_UNUSED_ASSETS = '/^(elementor|e-animation|swiper|wc-|woocommerce|contact-form-7|wpcf7|tablepress|wp-ecommerce-paypal|wpecpp|cf7pp|sourcebuster)/';

function canal_carte_dequeue_unused(): void
{
    if (!canal_carte_is_page() && !canal_fiche_is_page() && !canal_home_is_page() && !canal_planner_is_page() && !canal_contenu_is_page() && !canal_calcul_is_page()) {
        return;
    }
    foreach ([wp_scripts(), wp_styles()] as $deps) {
        foreach ($deps->queue as $handle) {
            // Carte: conserva Google Maps; ficha y home (sin mapa propio del tema): fuera.
            if (preg_match(CANAL_CARTE_UNUSED_ASSETS, $handle) || (canal_carte_is_page() ? canal_theme_is_unused_asset($handle) : canal_fiche_is_unused_asset($handle))) {
                $deps->dequeue($handle);
            }
        }
    }
}
// Varios plugins encolan tarde (en el pie): se limpia justo antes de imprimir cabecera y pie.
add_action('wp_enqueue_scripts', 'canal_carte_dequeue_unused', 9999);
add_action('wp_print_styles', 'canal_carte_dequeue_unused', 0);
add_action('wp_print_scripts', 'canal_carte_dequeue_unused', 0);
add_action('wp_print_footer_scripts', 'canal_carte_dequeue_unused', 0);

// Carte: Google Maps lo carga ya el tema en todas las páginas (footer, síncrono): no se carga otra vez.
// Nuestros scripts van en el footer y search-map.js arranca en DOMContentLoaded, cuando Maps ya existe.
add_action('wp_enqueue_scripts', function () {
    if (!canal_carte_is_page()) {
        return;
    }
    $ver = function (string $rel): string { return (string) filemtime(CANAL_HOME_DIR . $rel); };
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    wp_enqueue_style('canal-carte', CANAL_HOME_URL . 'assets/carte.css', [], $ver('assets/carte.css'));
    // El cargador a pantalla completa del tema tapa el skeleton de la carte hasta window.load: solo en esta plantilla.
    wp_add_inline_style('canal-carte', 'body.page-template-template-carte .loader-bg.main-loader{display:none!important}');
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], $ver('assets/header.css'));
    // Lo que la carte usa del CSS del tema (TASK-034), después de lo nuestro como antes.
    wp_enqueue_style('canal-carte-theme', CANAL_HOME_URL . 'assets/carte-theme.css', ['canal-carte', 'canal-home-header'], $ver('assets/carte-theme.css'));
    wp_add_inline_style('canal-carte-theme', CANAL_THEME_FIX_CSS);
    // TASK-035: skeleton-controler sin dependencias (corre en cuanto se lee: muestra la lista); el resto en
    // defer para no bloquear el análisis (search-map arranca en DOMContentLoaded, tras el Maps síncrono del tema).
    wp_enqueue_script('canal-carte-skeleton-controler', CANAL_HOME_URL . 'assets/carte/skeleton-controler.js', [], $ver('assets/carte/skeleton-controler.js'), true);
    wp_enqueue_script('canal-carte-clusterer', 'https://unpkg.com/@googlemaps/markerclusterer@2.5.3/dist/index.min.js', [], '2.5.3', ['in_footer' => true, 'strategy' => 'defer']);
    foreach (['search-map', 'search-tabs', 'ai-search'] as $name) {
        wp_enqueue_script("canal-carte-$name", CANAL_HOME_URL . "assets/carte/$name.js", ['canal-carte-clusterer'], $ver("assets/carte/$name.js"), ['in_footer' => true, 'strategy' => 'defer']);
    }
    wp_localize_script('canal-carte-ai-search', 'CDM_CARTE', [
        'aiUrl'   => rest_url('canal-home/v1/ai'),
        'pageUrl' => get_permalink(),
    ]);
}, 20);

// Planificateur 2026: pantalla única (sin pie ni bloque publicitario), CSS/JS propios.
add_filter('body_class', function ($classes) {
    if (canal_planner_is_page()) {
        $classes[] = 'cdm-planner-page';
    }
    return $classes;
});

add_action('wp_enqueue_scripts', function () {
    if (!canal_planner_is_page()) {
        return;
    }
    $ver = function (string $rel): string { return (string) filemtime(CANAL_HOME_DIR . $rel); };
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], $ver('assets/header.css'));
    // Base del tema que usa la cabecera (mismo subconjunto que la home, TASK-034).
    wp_enqueue_style('canal-home-theme', CANAL_HOME_URL . 'assets/home-theme.css', ['canal-home-header'], $ver('assets/home-theme.css'));
    wp_add_inline_style('canal-home-theme', CANAL_THEME_FIX_CSS);
    wp_enqueue_style('canal-planner', CANAL_HOME_URL . 'assets/planner.css', ['canal-home-theme'], $ver('assets/planner.css'));
    wp_enqueue_script('canal-planner', CANAL_HOME_URL . 'assets/planner.js', [], $ver('assets/planner.js'), ['in_footer' => true, 'strategy' => 'defer']);
    $token = isset($_GET['confirmer']) && is_string($_GET['confirmer']) ? sanitize_key(wp_unslash($_GET['confirmer'])) : '';
    wp_localize_script('canal-planner', 'CDM_PLANNER', [
        'planUrl'    => rest_url('canal-home/v1/plan'),
        'requestUrl' => rest_url('canal-home/v1/plan/request'),
        'confirmUrl' => rest_url('canal-home/v1/plan/confirm'),
        'carteUrl'   => home_url(CANAL_CARTE_PATH),
        'themes'     => CANAL_PLANNER_THEMES,
        'confirm'    => $token !== '' ? canal_planner_confirm_preview($token) : null,
    ]);
}, 20);

// Google Maps del modal de demanda: misma URL (clave y bibliotecas) que registra el tema, cargada por planner.js
// solo al abrir el modal (como la ficha). El tema lo registra tarde: se lee justo antes de los scripts del pie.
add_action('wp_print_footer_scripts', function () {
    if (!canal_planner_is_page()) {
        return;
    }
    $maps = wp_scripts()->registered['google-maps'] ?? null;
    wp_add_inline_script('canal-planner', 'window.CDM_PLANNER_MAPS = ' . wp_json_encode($maps ? (string) $maps->src : '') . ';', 'before');
}, 1);

