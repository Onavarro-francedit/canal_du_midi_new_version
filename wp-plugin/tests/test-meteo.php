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
    foreach (['tmin', 'tmax', 'rain', 'hot', 'heat', 'record'] as $k) {
        check(count($s[$k]) === 12, "datos: {$s['name']} $k con 12 meses");
    }
}
check(count(CANAL_METEO_NAV) === 12, 'navegación: 12 meses');
check(CANAL_METEO_STATIONS[1]['tmax'][7] === 31.4 && CANAL_METEO_STATIONS[1]['hot'][7] === 18.4 && CANAL_METEO_STATIONS[1]['record'][7] === [43.2, 2023], 'datos: Carcassonne agosto 2021–2025 (31,4 °C, 18,4 j ≥ 30 °C, 43,2 °C en 2023)');

check(canal_meteo_best_months() === [5, 9, 10], 'mejor época 2021–2025: mayo, septiembre, octubre (≤ 7 días ≥ 30 °C y ≥ 20 °C en las 3 ciudades + navegación)');
check(canal_meteo_range('tmax', 4, ' °C') === '22,6 à 23,3 °C' && canal_meteo_range('hot', 7, ' jours') === '16 à 19 jours', 'rangos de las 3 ciudades');
check(canal_meteo_months_label([5, 6, 9]) === 'mai, juin et septembre', 'meses en francés');
check(canal_meteo_num(28.8) === '28,8' && canal_meteo_num(10.0) === '10', 'números con coma, sin ,0');

$rows = canal_meteo_rows();
check(count($rows) === 12 && $rows[0]['month'] === 'Janvier' && count($rows[0]['temps']) === 3, 'tabla: 12 filas, 3 estaciones');
check($rows[4]['best'] && !$rows[6]['best'] && $rows[7]['record'] === '43,2 °C (2023)', 'tabla: mayo marcado, julio no; máxima de agosto');

$faq = canal_meteo_faq();
check(count($faq) === 4, 'faq: 4 preguntas');
check(strpos($faq[0]['a'], 'mai, septembre et octobre') !== false && strpos($faq[0]['a'], 'Toulouse, Carcassonne et Béziers') !== false && strpos($faq[0]['a'], '43,2 °C à Carcassonne en août 2023') !== false, 'faq: mejor época con las 3 ciudades, el motivo y la máxima');
check(strpos($faq[1]['a'], '31,4') !== false && strpos($faq[1]['a'], '35 °C') !== false, 'faq: verano con las cifras 2021–2025');
check(strpos($faq[3]['a'], '1er janvier') !== false && strpos($faq[3]['a'], '25 décembre') !== false, 'faq: cierres de nuestra página de navegación');
foreach ($faq as $qa) {
    check(substr($qa['q'], -2) === ' ?', 'faq: « ' . $qa['q'] . ' »');
}

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
