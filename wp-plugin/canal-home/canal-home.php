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

require_once CANAL_HOME_DIR . 'includes/ai-core.php';
require_once CANAL_HOME_DIR . 'includes/data.php';
require_once CANAL_HOME_DIR . 'includes/ai.php';

add_filter('theme_page_templates', function ($templates) {
    $templates[CANAL_HOME_TEMPLATE] = 'Accueil 2026';
    return $templates;
});

function canal_home_is_page(): bool
{
    return is_page() && get_page_template_slug(get_queried_object_id()) === CANAL_HOME_TEMPLATE;
}

add_filter('template_include', function ($template) {
    return canal_home_is_page() ? CANAL_HOME_DIR . 'template-home.php' : $template;
});

// Prioridad 20: después de los estilos del tema, para ganar a igual especificidad.
add_action('wp_enqueue_scripts', function () {
    if (!canal_home_is_page()) {
        return;
    }
    wp_enqueue_style(
        'canal-home-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,700&family=Sora:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style('canal-home-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css', [], '1.11.1');
    // Versión = fecha del archivo: cada despliegue invalida la caché del navegador.
    wp_enqueue_style('canal-home', CANAL_HOME_URL . 'assets/home.css', [], (string) filemtime(CANAL_HOME_DIR . 'assets/home.css'));
    wp_enqueue_script('canal-home', CANAL_HOME_URL . 'assets/home.js', [], (string) filemtime(CANAL_HOME_DIR . 'assets/home.js'), true);
    wp_localize_script('canal-home', 'CDM_HOME', [
        'aiUrl'       => rest_url('canal-home/v1/ai'),
        'explorerUrl' => home_url('/explorer/'),
    ]);
}, 20);
