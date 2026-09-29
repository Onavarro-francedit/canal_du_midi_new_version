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
const CANAL_HOME_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,700&family=Sora:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap';

require_once CANAL_HOME_DIR . 'includes/ai-core.php';
require_once CANAL_HOME_DIR . 'includes/data.php';
require_once CANAL_HOME_DIR . 'includes/content.php';
require_once CANAL_HOME_DIR . 'includes/limits.php';
require_once CANAL_HOME_DIR . 'includes/ai.php';
require_once CANAL_HOME_DIR . 'includes/plan.php';
require_once CANAL_HOME_DIR . 'includes/seo.php';
require_once CANAL_HOME_DIR . 'includes/carte-filter.php';
require_once CANAL_HOME_DIR . 'includes/carte-data.php';

add_filter('theme_page_templates', function ($templates) {
    $templates[CANAL_HOME_TEMPLATE] = 'Accueil 2026';
    $templates[CANAL_CARTE_TEMPLATE] = 'Carte interactive';
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

// El botón « Carte interactive » del menú del tema apunta a /explorer/ en todo el sitio; en nuestras
// páginas (home y carte) se redirige a /carte/ sin tocar el menú existente.
function canal_home_carte_menu_js(): string
{
    return 'document.querySelectorAll(\'.c27-main-header a[href$="/explorer/"], #main-menu a[href$="/explorer/"]\').forEach(function (a) { a.href = '
        . wp_json_encode(home_url('/carte/')) . '; });';
}

add_filter('template_include', function ($template) {
    if (canal_home_is_page()) {
        return CANAL_HOME_DIR . 'template-home.php';
    }
    return canal_carte_is_page() ? CANAL_HOME_DIR . 'template-carte.php' : $template;
});

// Prioridad 20: después de los estilos del tema, para ganar a igual especificidad.
add_action('wp_enqueue_scripts', function () {
    if (!canal_home_is_page()) {
        return;
    }
    wp_enqueue_style('canal-home-fonts', CANAL_HOME_FONTS_URL, [], null);
    wp_enqueue_style('canal-home-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css', [], '1.11.1');
    // Versión = fecha del archivo: cada despliegue invalida la caché del navegador.
    wp_enqueue_style('canal-home', CANAL_HOME_URL . 'assets/home.css', [], (string) filemtime(CANAL_HOME_DIR . 'assets/home.css'));
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], (string) filemtime(CANAL_HOME_DIR . 'assets/header.css'));
    wp_enqueue_script('canal-home', CANAL_HOME_URL . 'assets/home.js', [], (string) filemtime(CANAL_HOME_DIR . 'assets/home.js'), true);
    wp_add_inline_script('canal-home', canal_home_carte_menu_js());
    wp_localize_script('canal-home', 'CDM_HOME', [
        'aiUrl'       => rest_url('canal-home/v1/ai'),
        'carteUrl'    => home_url('/carte/'),
    ]);
}, 20);

// Carte: Google Maps lo carga ya el tema en todas las páginas (footer, síncrono): no se carga otra vez.
// Nuestros scripts van en el footer y search-map.js arranca en DOMContentLoaded, cuando Maps ya existe.
add_action('wp_enqueue_scripts', function () {
    if (!canal_carte_is_page()) {
        return;
    }
    $ver = function (string $rel): string { return (string) filemtime(CANAL_HOME_DIR . $rel); };
    wp_enqueue_style('canal-home-fonts', CANAL_HOME_FONTS_URL, [], null);
    wp_enqueue_style('canal-home-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css', [], '1.11.1');
    wp_enqueue_style('canal-carte', CANAL_HOME_URL . 'assets/carte.css', [], $ver('assets/carte.css'));
    // El cargador a pantalla completa del tema tapa el skeleton de la carte hasta window.load: solo en esta plantilla.
    wp_add_inline_style('canal-carte', 'body.page-template-template-carte .loader-bg.main-loader{display:none!important}');
    // body.page-template-canal-home también se aplica a esta plantilla (misma carpeta).
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], $ver('assets/header.css'));
    wp_enqueue_script('canal-carte-clusterer', 'https://unpkg.com/@googlemaps/markerclusterer@2.5.3/dist/index.min.js', [], '2.5.3', true);
    foreach (['search-map', 'search-tabs', 'ai-search', 'skeleton-controler'] as $name) {
        wp_enqueue_script("canal-carte-$name", CANAL_HOME_URL . "assets/carte/$name.js", ['canal-carte-clusterer'], $ver("assets/carte/$name.js"), true);
    }
    wp_add_inline_script('canal-carte-search-tabs', canal_home_carte_menu_js());
    wp_localize_script('canal-carte-ai-search', 'CDM_CARTE', [
        'aiUrl'   => rest_url('canal-home/v1/ai'),
        'pageUrl' => get_permalink(),
    ]);
}, 20);
