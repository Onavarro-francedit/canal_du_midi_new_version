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
$check($options === 21, 'selects: 9 etapas + 12 tipos (' . $options . ')');
$check(strpos($html, 'src=""') === false && strpos($html, "url('')") === false, 'sin imágenes vacías');
$check(strpos($html, 'Plan-Canal-du-Midi-2026.pdf') !== false, 'enlace PDF');
$check(strpos($html, 'fixedban') !== false || strpos($html, 'googletag') !== false, 'cabecera del tema con pubs');
WP_CLI::log($fails === 0 ? 'TODO OK' : "$fails FALLO(S)");
