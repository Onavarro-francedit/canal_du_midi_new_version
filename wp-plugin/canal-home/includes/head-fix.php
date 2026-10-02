<?php
/**
 * El header.php del tema (my-listing) imprime <div id="fb-root"></div> ANTES de wp_head(): el parser
 * HTML cierra el <head> en ese <div> y el title, la meta description, el canonical, los robots y el
 * JSON-LD acaban en el <body> (Google ignora el canonical fuera del <head>; Lighthouse: « sin
 * metadescripción »). Sin tocar el tema: en NUESTRAS páginas se mueve el div justo después de <body>.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

function canal_home_fix_head(string $html): string
{
    $headEnd = strpos($html, '</head>');
    $div = '<div id="fb-root"></div>';
    $pos = strpos($html, $div);
    if ($headEnd === false || $pos === false || $pos > $headEnd) {
        return $html;
    }
    $html = substr_replace($html, '', $pos, strlen($div));
    return (string) preg_replace('/<body[^>]*>/', "$0\n$div", $html, 1);
}

// WebP en el <body>: cada JPG/PNG propio (uploads o assets del plugin) en src, srcset o url() pasa a su
// hermano « archivo.jpg.webp » si $exists('/wp-content/…') lo confirma. El <head> (og:image) no se toca.
function canal_home_webp_html(string $html, callable $exists, string $host = 'https://www.plan-canal-du-midi.com'): string
{
    $body = stripos($html, '<body');
    if ($body === false) {
        return $html;
    }
    $re = '#' . preg_quote($host, '#') . '(/wp-content/(?:uploads|plugins/canal-home/assets)/[^"\'\s?)&]+?\.(?:jpe?g|png))(?=[?"\'\s)&])#i'; // & : url(&quot;…&quot;) de canal_home_css_url
    $out = preg_replace_callback($re, function (array $m) use ($exists, $host): string {
        return $exists($m[1]) ? $host . $m[1] . '.webp' : $m[0];
    }, substr($html, $body));
    return substr($html, 0, $body) . ($out ?? substr($html, $body));
}

// Hermano .webp de un archivo propio: existe, o se genera con el editor de imágenes de WP (GD) al vuelo.
// ponytail: como mucho 8 por petición; si quedan pendientes, esa respuesta no entra en la caché de página
// (DONOTCACHEPAGE) y las siguientes completan el resto. Con miles de imágenes nuevas: script de generación previa.
function canal_home_webp_exists(string $rel): bool
{
    static $made = 0;
    $file = ABSPATH . ltrim($rel, '/');
    if (is_file($file . '.webp')) {
        return true;
    }
    if (!is_file($file) || !is_writable(dirname($file))) {
        return false;
    }
    if ($made >= 8) {
        $GLOBALS['canal_home_webp_pending'] = true;
        return false;
    }
    $made++;
    $editor = wp_get_image_editor($file);
    if (is_wp_error($editor)) {
        return false;
    }
    $editor->set_quality(78);
    $saved = $editor->save($file . '.webp', 'image/webp');
    return !is_wp_error($saved) && is_file($file . '.webp');
}

if (function_exists('add_action')) {
    // Prioridad -1: antes del template_redirect de la ficha (prioridad 0, que incluye la plantilla y sale).
    add_action('template_redirect', function () {
        if (get_query_var('canal_fiche') !== '' || canal_carte_is_page() || canal_home_is_page()) {
            ob_start(function (string $html): string {
                $html = canal_home_webp_html(canal_fiche_lighten_head(canal_home_fix_head($html)), 'canal_home_webp_exists', untrailingslashit(home_url()));
                if (!empty($GLOBALS['canal_home_webp_pending']) && !defined('DONOTCACHEPAGE')) {
                    define('DONOTCACHEPAGE', true); // WP Fastest Cache no guarda una versión con WebP a medias
                }
                return $html;
            });
        }
    }, -1);
}
