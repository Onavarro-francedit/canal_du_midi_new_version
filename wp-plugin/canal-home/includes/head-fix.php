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

if (function_exists('add_action')) {
    // Prioridad -1: antes del template_redirect de la ficha (prioridad 0, que incluye la plantilla y sale).
    add_action('template_redirect', function () {
        if (get_query_var('canal_fiche') !== '' || canal_carte_is_page() || canal_home_is_page()) {
            ob_start(function (string $html): string { return canal_fiche_lighten_head(canal_home_fix_head($html)); });
        }
    }, -1);
}
