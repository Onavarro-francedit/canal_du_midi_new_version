<?php
/**
 * TASK-050 — Activa la caché de página de WP Fastest Cache (1.4.9) sin pasar por el « Submit » de wp-admin
 * (bloqueado por falsos positivos de Elementor 3.0.16). Reutiliza getHtaccess() del propio plugin.
 *
 * Uso (WP cargado):  wp-plugin/remote.sh run ops/wpfc-enable.php        (idempotente)
 *
 * ROLLBACK de un comando (como el usuario del sitio; FECHA = día de la copia, ver la salida del script):
 *   ssh plesk-prod 'S=/var/www/vhosts/plan-canal-du-midi.com/httpdocs; W="sudo -u ga241453_canal /opt/plesk/php/7.4/bin/php /usr/local/bin/wp --path=$S"; \
 *     cp -p $S/.htaccess.bak-FECHA-wpfc $S/.htaccess && $W option delete WpFastestCache && $W option delete WpFastestCacheExclude \
 *     && $W cron event delete wp_fastest_cache_0 && rm -rf $S/wp-content/cache/all'
 *
 * No toca el bloque « WordPress » ni el anti-bots del .htaccess: solo añade (antes de « # BEGIN WordPress »)
 * el bloque WpFastestCache y el bloque canal-headers.
 */
if (!defined('ABSPATH')) {
    exit;
}

$ht   = ABSPATH . '.htaccess';
$bak  = ABSPATH . '.htaccess.bak-' . gmdate('Y-m-d') . '-wpfc';
$rm   = '~#\s?BEGIN\s?WpFastestCache.*?#\s?END\s?WpFastestCache\s*|# BEGIN canal-headers.*?# END canal-headers\s*~s';
$mark = '# BEGIN WordPress';

// a) Guardas
if (!class_exists('WpFastestCache')) {
    WP_CLI::error('WP Fastest Cache no está activo.');
}
if (!is_writable($ht)) {
    WP_CLI::error('.htaccess no es escribible.');
}
$orig = (string) file_get_contents($ht);
if (strpos($orig, $mark) === false) {
    WP_CLI::error('.htaccess no contiene « # BEGIN WordPress ».');
}
// WPFC registra sus intervalos vía cron_schedules; si en CLI no está, se añade aquí.
if (!isset(wp_get_schedules()['everysixhours'])) {
    add_filter('cron_schedules', function (array $s): array {
        $s['everysixhours'] = ['interval' => 6 * HOUR_IN_SECONDS, 'display' => 'Once Every 6 Hours'];
        return $s;
    });
}
if (!isset(wp_get_schedules()['everysixhours'])) {
    WP_CLI::error('El intervalo everysixhours no está disponible.');
}

// b) Copia (una por día, no se pisa)
if (!file_exists($bak) && !copy($ht, $bak)) {
    WP_CLI::error('No se pudo crear la copia ' . $bak);
}

// c) Ajustes y exclusiones (carrito/sesión WooCommerce)
$opts = [
    'wpFastestCacheStatus' => 'on', 'wpFastestCacheLoggedInUser' => 'on',
    'wpFastestCacheNewPost' => 'on', 'wpFastestCacheNewPost_type' => 'all',
    'wpFastestCacheUpdatePost' => 'on', 'wpFastestCacheUpdatePost_type' => 'post',
    'wpFastestCacheLanguage' => 'eng',
];
$exclude = [
    ['prefix' => 'contain', 'content' => 'wp_woocommerce_session_', 'type' => 'cookie'],
    ['prefix' => 'contain', 'content' => 'woocommerce_items_in_cart', 'type' => 'cookie'],
];

// d) Las exclusiones se leen de la opción al generar el bloque
update_option('WpFastestCacheExclude', wp_json_encode($exclude));

// e) Bloque oficial del plugin
$_SERVER['HTTP_HOST'] = (string) wp_parse_url(home_url(), PHP_URL_HOST);
$_POST = $opts;
require_once WP_PLUGIN_DIR . '/wp-fastest-cache/inc/admin.php';
$admin = new WpFastestCacheAdmin();
$block = $admin->getHtaccess();
if (!is_string($block)) {
    WP_CLI::error('getHtaccess() no devolvió texto.');
}
foreach (['# BEGIN WpFastestCache', '# END WpFastestCache', 'wordpress_logged_in', 'woocommerce_items_in_cart'] as $need) {
    if (strpos($block, $need) === false) {
        WP_CLI::error("El bloque generado no contiene « $need »; .htaccess sin tocar.");
    }
}

// f) Reescritura del .htaccess
$headers = "# BEGIN canal-headers (TASK-050): las páginas de la caché WPFC no pasan por PHP (seo.php send_headers)\n"
    . "<IfModule mod_headers.c>\n<FilesMatch \"index\\.html\$\">\n"
    . "Header set X-Content-Type-Options \"nosniff\"\n"
    . "Header set Strict-Transport-Security \"max-age=15552000\" env=HTTPS\n"
    . "</FilesMatch>\n</IfModule>\n# END canal-headers\n";
$stripped = preg_replace($rm, '', $orig);
$pos      = strpos($stripped, $mark);
if ($stripped === null || $pos === false) {
    WP_CLI::error('No se pudo preparar el nuevo .htaccess; sin tocar.');
}
$new = substr($stripped, 0, $pos) . rtrim($block, "\n") . "\n" . $headers . substr($stripped, $pos);
if (file_put_contents($ht, $new) === false) {
    WP_CLI::error('No se pudo escribir .htaccess.');
}
$read = (string) file_get_contents($ht);
if ($read !== $new || preg_replace($rm, '', $read) !== $stripped
    || substr_count($read, '# BEGIN WpFastestCache') !== 1 || substr_count($read, '# BEGIN canal-headers') !== 1
    || strpos($read, $mark) < strpos($read, '# END canal-headers')) {
    copy($bak, $ht);
    WP_CLI::error('Verificación del .htaccess fallida: restaurada la copia.');
}

// g) Directorio de caché
$w = $admin->checkCachePathWriteable();
if ($w !== true) {
    WP_CLI::error('Caché no escribible: ' . wp_strip_all_tags(is_array($w) ? (string) $w[0] : (string) $w));
}

// h) Opción del plugin
update_option('WpFastestCache', wp_json_encode($opts));

// i) Timeout 6 h (nonces de anónimo): sustituye cualquier evento wp_fastest_cache existente
foreach ((array) _get_cron_array() as $ts => $hooks) {
    foreach ((array) $hooks as $hook => $events) {
        if (preg_match('/^wp_fastest_cache(_\d+)?$/', (string) $hook)) {
            foreach ((array) $events as $ev) {
                wp_unschedule_event($ts, $hook, $ev['args']);
            }
        }
    }
}
$ok = wp_schedule_event(time() + 6 * HOUR_IN_SECONDS, 'everysixhours', 'wp_fastest_cache_0', [json_encode(['prefix' => 'all', 'content' => 'all'])]);
if ($ok === false || is_wp_error($ok)) {
    WP_CLI::error('No se pudo programar wp_fastest_cache_0.');
}

// j) Caché limpia desde cero
do_action('wpfc_clear_all_cache');
WP_CLI::log($block . $headers);
WP_CLI::log('Copia: ' . $bak);
WP_CLI::success('WP Fastest Cache activado.');
