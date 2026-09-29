<?php
// Renderiza template-home.php con WP cargado, SIN activar el plugin (ejecutar antes de activar).
// Uso: wp-plugin/remote.sh run tests/smoke-render.php
$src = $args[0] ?? '';
require_once $src . '/canal-home/canal-home.php';
$errors = [];
set_error_handler(function ($no, $str, $file, $line) use (&$errors, $src) {
    if (strpos($file, $src) === 0) {
        $errors[] = "$str ($file:$line)";
    }
    return false;
});
ob_start();
include $src . '/canal-home/template-home.php';
$html = ob_get_clean();
restore_error_handler();

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};
$check($errors === [], 'sin warnings/notices del plugin' . ($errors ? ': ' . implode(' | ', $errors) : ''));
$check(substr_count($html, '<section') === 7, 'secciones: 7 (' . substr_count($html, '<section') . ')');
$check(strpos($html, 'class="cdm-home"') !== false, 'contenedor .cdm-home');
$check(substr_count($html, 'class="destination-card-link"') === 6, 'destinos: 6');
$check(substr_count($html, 'class="tour-card"') === 4, 'séjours: 4');
$form = (string) strstr((string) strstr($html, 'id="home-search-form"'), '</form>', true);
$options = preg_match_all('/<option value="[^"]+"/', $form);
$check(strpos($form, 'name="search_location"') !== false && strpos($form, 'name="region"') === false, '/explorer/ filtra por search_location (texto geocodificado), no por region');
$check(strpos($form, '<option value="Béziers">') !== false, 'etapa enviada como nombre legible (Béziers)');
$check($options === 21, 'selects: 9 etapas + 12 tipos (' . $options . ')');
$check(strpos($html, 'src=""') === false && strpos($html, "url('')") === false, 'sin imágenes vacías');
$check(strpos($html, 'Plan-Canal-du-Midi-2026.pdf') !== false, 'enlace PDF');
preg_match_all('/style="[^"]*\d(\.\d+)?rem[^"]*"/', $html, $remInline);
$check(count($remInline[0]) === 0, 'sin rem en estilos en línea (el tema fija html{font-size:10px}): ' . count($remInline[0]));
$check(strpos($html, 'admin-post.php') !== false && strpos($html, 'name="action" value="canal_home_plan"') !== false && strpos($html, 'type="email" name="email"') !== false, 'plan: formulario e-mail hacia admin-post');
$check(strpos($html, 'name="website"') !== false, 'plan: campo honeypot');
$check(strpos($html, 'Recevez le plan du Canal du Midi 2026') !== false && strpos($html, 'btn-pdf__size') !== false, 'plan: textos y botón PDF como en local');
$check(strpos($html, 'canal-home/assets/plan-canal-du-midi-2026.jpg') !== false && strpos($html, 'couv_canal_du_midi_2026.png') === false, 'plan: libro en perspectiva (no la portada plana)');
$check(preg_match_all('/class="feature-icon"[^>]*><i class="bi bi-(shop|map|compass)" aria-hidden="true">/', $html) === 3, 'atouts: 3 iconos (shop, map, compass)');
$check(preg_match_all('/<i class="bi bi-(water|bicycle) offer-watermark" aria-hidden="true">/', $html) === 2, 'ofertas: 2 iconos de marca de agua');

$check(strpos($html, 'fixedban') !== false || strpos($html, 'googletag') !== false, 'cabecera del tema con pubs');
WP_CLI::log($fails === 0 ? 'TODO OK' : "$fails FALLO(S)");
