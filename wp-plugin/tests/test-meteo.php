<?php
// Tests de meteo-core.php (clima mes a mes de la página météo, TASK-066 M1) — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-meteo.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/meteo-core.php';

$fails = 0;
function check(bool $cond, string $label): void
{
    global $fails;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        echo "FAIL - $label\n";
        $fails++;
    }
}

foreach (CANAL_METEO_STATIONS as $s) {
    foreach (['tmin', 'tmax', 'rain', 'hot'] as $k) {
        check(count($s[$k]) === 12, "datos: {$s['name']} $k con 12 meses");
    }
    check(strpos($s['url'], 'FICHECLIM_') !== false, "datos: {$s['name']} enlaza su ficha Météo-France");
}
check(count(CANAL_METEO_NAV) === 12, 'navegación: 12 meses');
check(CANAL_METEO_STATIONS[1]['tmax'][6] === 28.8 && CANAL_METEO_STATIONS[1]['rain'][6] === 4.6, 'datos: Carcassonne julio = ficha (28,8 °C, 4,6 j)');

check(canal_meteo_best_months() === [5, 6, 9], 'mejor época: mayo, junio, septiembre (regla 20–27 °C + navegación en temporada)');
check(canal_meteo_months_label([5, 6, 9]) === 'mai, juin et septembre', 'meses en francés');
check(canal_meteo_num(28.8) === '28,8' && canal_meteo_num(10.0) === '10', 'números con coma, sin ,0');

$rows = canal_meteo_rows();
check(count($rows) === 12 && $rows[0]['month'] === 'Janvier' && count($rows[0]['temps']) === 3, 'tabla: 12 filas, 3 estaciones');
check($rows[4]['best'] && !$rows[6]['best'], 'tabla: mayo marcado, julio no');

$faq = canal_meteo_faq();
check(count($faq) === 4, 'faq: 4 preguntas');
check(strpos($faq[0]['a'], 'Mai, juin et septembre') === 0 && strpos($faq[0]['a'], 'Carcassonne') !== false, 'faq: mejor época con la regla');
check(strpos($faq[1]['a'], '28,8') !== false, 'faq: verano con la cifra de la ficha');
check(strpos($faq[3]['a'], '1er janvier') !== false && strpos($faq[3]['a'], '25 décembre') !== false, 'faq: cierres de nuestra página de navegación');
foreach ($faq as $qa) {
    check(substr($qa['q'], -2) === ' ?', 'faq: « ' . $qa['q'] . ' »');
}

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
