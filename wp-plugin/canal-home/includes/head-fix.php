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

// Banner de cookies Sirdata (stub + cmp) y pcm.js de publicidad: el tema los pone en el <head>, donde el stub síncrono
// retrasa toda la pintura (02/10: FCP 0,2 → 2,2 s) y en móvil el texto del banner llega a ser el LCP (06/10, météo).
// Se sustituyen por un cargador al principio del <body>: a la primera interacción (scroll, toque, tecla, ratón) carga el
// stub y, cuando ha cargado, cmp, pcm.js y GA4 (window.canalGtag, fiche-core.php), en ese orden: nada de publicidad ni
// medición antes del stub de consentimiento. Quien no interactúa no ve el banner y no se le mide (TASK-070).
function canal_home_move_consent_to_body(string $html): string
{
    $headEnd = stripos($html, '</head>');
    if ($headEnd === false) {
        return $html;
    }
    $head = substr($html, 0, $headEnd);
    $re = '#<script\b[^>]*\bsrc="(https://(?:cache\.consentframework\.com|choices\.consentframework\.com|a\.rltd\.net)/[^"]*)"[^>]*>\s*</script>#i';
    if (!preg_match_all($re, $head, $m)) {
        return $html;
    }
    $srcs = array_map('html_entity_decode', $m[1]);
    $stub = null;
    foreach ($srcs as $i => $src) {
        if (strpos($src, '/stub') !== false) {
            $stub = $src;
            unset($srcs[$i]);
        }
    }
    $rest = substr($html, $headEnd);
    $loader = '<script>' . canal_home_consent_loader($stub, array_values($srcs)) . '</script>';
    $moved = preg_replace('#<body\b[^>]*>#i', '$0' . "\n" . str_replace(['\\', '$'], ['\\\\', '\\$'], $loader), $rest, 1, $count);
    return $count ? str_replace($m[0], '', $head) . $moved : $html;
}

/** JS del cargador diferido: $stub primero (si lo hay) y después $then, en orden. */
function canal_home_consent_loader(?string $stub, array $then): string
{
    $json = function ($v) {
        return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    };
    return '(function(w,d){w.canalConsentDeferred=1;var ev=["pointerdown","keydown","touchstart","scroll","wheel","mousemove"],done=0;'
        . 'function add(src,cb){var s=d.createElement("script");s.src=src;s.async=true;s.setAttribute("referrerpolicy","unsafe-url");s.charset="utf-8";'
        . 'if(cb){s.onload=s.onerror=cb;}d.body.appendChild(s);}'
        . 'function rest(){' . $json($then) . '.forEach(function(src){add(src);});if(w.canalGtag){add(w.canalGtag);}}'
        . 'function go(){if(done)return;done=1;ev.forEach(function(e){w.removeEventListener(e,go,true);});'
        . ($stub !== null ? 'add(' . $json($stub) . ',rest);' : 'rest();') . '}'
        . 'ev.forEach(function(e){w.addEventListener(e,go,{capture:true,passive:true});});})(window,document);';
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

/** ¿Esta petición la pinta una plantilla 2026? (rutas -2026 privadas o, publicado, las URLs de siempre) */
function canal_2026_is_template_request(): bool
{
    foreach (['canal_fiche', 'canal_contenu', 'canal_calcul', 'canal_archive', 'canal_etape'] as $var) {
        if (get_query_var($var) !== '') {
            return true;
        }
    }
    if (canal_carte_is_page() || canal_home_is_page() || canal_404_is_page()) {
        return true;
    }
    if (!CANAL_2026_LIVE) {
        return false;
    }
    $q = get_queried_object();
    return is_singular('job_listing') || is_category() || is_page(CANAL_CALCUL_SLUG)
        || (is_singular(['post', 'page']) && $q instanceof WP_Post && canal_contenu_post_eligible($q));
}

if (function_exists('add_action')) {
    // Prioridad -1: antes del template_redirect de la ficha (prioridad 0, que incluye la plantilla y sale).
    add_action('template_redirect', function () {
        if (canal_2026_is_template_request()) {
            ob_start(function (string $html): string {
                $html = canal_home_webp_html(canal_home_move_consent_to_body(canal_fiche_lighten_head(canal_home_fix_head($html))), 'canal_home_webp_exists', untrailingslashit(home_url()));
                $html = canal_2026_links_html($html); // enlaces internos → versión 2026 (links-2026.php)
                if (!empty($GLOBALS['canal_home_webp_pending']) && !defined('DONOTCACHEPAGE')) {
                    define('DONOTCACHEPAGE', true); // WP Fastest Cache no guarda una versión con WebP a medias
                }
                return $html;
            });
        } elseif (canal_planner_is_page() || canal_account_is_page()) {
            ob_start('canal_2026_links_html'); // el planificador conserva su <head> (formulario): solo los enlaces
        }
    }, -1);
}
