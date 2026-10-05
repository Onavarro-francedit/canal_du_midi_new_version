<?php
// Tests de meteo-core.php (página météo 2026, TASK-066 M1) — PHP CLI puro (7.4+), sin WordPress.
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

// Datos: 8 estaciones en orden de PK, 12 meses por serie (salida de build/build-meteo.php).
check(count(CANAL_METEO_STATIONS) === 8, 'datos: 8 estaciones');
check(array_column(CANAL_METEO_STATIONS, 'pk') === [0, 33, 64, 100, 151, 172, 202, 240], 'datos: orden de PK de Toulouse a l’étang de Thau');
foreach (CANAL_METEO_STATIONS as $s) {
    foreach (['tmin', 'tmax', 'rain', 'hot'] as $k) {
        check(count($s[$k]) === 12, "datos: {$s['name']} $k con 12 meses");
    }
}
check(CANAL_METEO_STATIONS[3]['tmax'][7] === 31.4 && CANAL_METEO_STATIONS[5]['hot'][6] === 24.0, 'datos: Carcassonne agosto 31,4 °C; Le Somail julio 24 días ≥ 30 °C');

// Etiquetas (mismo resultado que la maqueta).
check(array_map('canal_meteo_kind', range(0, 11)) === ['off', 'off', 'off', 'mild', 'ideal', 'hot', 'vhot', 'vhot', 'ideal', 'ideal', 'off', 'off'], 'etiquetas de los 12 meses');
check(canal_meteo_best_months() === [5, 9, 10], 'meses ideales: mayo, septiembre, octubre');
check(canal_meteo_months_label([5, 9, 10]) === 'mai, septembre et octobre', 'meses en francés');
check(canal_meteo_span(22.6, 25.0, 'canal_meteo_days') === '23 à 25' && canal_meteo_span(0.0, 0.4, 'canal_meteo_days') === '0', 'rangos');

$months = canal_meteo_months();
check($months[7]['temp'] === '30–33 °C' && $months[7]['hot'] === '13–24 j à 30 °C+' && $months[0]['hot'] === 'jamais 30 °C', 'tarjetas: agosto 30–33 °C, 13–24 días; enero jamás 30 °C');
check(strpos($months[7]['text'], 'Le plus chaud : Le Somail (33,1 °C') !== false && strpos($months[7]['text'], 'le plus frais : Étang de Thau (29,6 °C)') !== false, 'detalle: más caluroso y más fresco en agosto');
check(strpos($months[2]['text'], 'basse puis moyenne saison') !== false, 'detalle: marzo cambia de temporada el 17');

$ways = canal_meteo_ways();
check(array_column($ways, 'icon') === ['directions_boat', 'directions_bike', 'directions_walk'], 'modos: iconos de Material Symbols');
check($ways[0]['best'] === 'Mai, septembre et octobre' && strpos($ways[1]['points'][1], 'vers Le Somail (48 jours') !== false && strpos($ways[1]['points'][1], 'vers l’étang de Thau (26)') !== false, 'modos: mejores meses y extremos de verano');
foreach ($ways as $w) {
    check(file_exists(__DIR__ . '/../canal-home/assets/icons/' . $w['icon'] . '.svg'), "icono {$w['icon']}.svg presente");
}

$seasons = canal_meteo_seasons();
check(array_column($seasons, 'name') === ['Printemps', 'Été', 'Automne', 'Hiver'], 'estaciones');
check($seasons[2]['rows'][3][1] === 'haute puis moyenne puis basse saison' && $seasons[0]['rows'][3][1] === 'basse puis moyenne puis haute saison', 'estaciones: navegación en el orden de los meses');
check($seasons[3]['rows'][1][1] === 'aucun', 'invierno: ningún día a 30 °C');

$faq = canal_meteo_faq();
check(count($faq) === 4, 'faq: 4 preguntas');
check(strpos($faq[0]['a'], 'Mai, septembre et octobre : l’après-midi, 23 à 25 °C en mai') === 0, 'faq: mejor época con todo el canal');
check(strpos($faq[1]['a'], 'Le plus chaud vers Le Somail, le plus frais vers l’étang de Thau') !== false, 'faq: verano, extremos');
check(strpos($faq[3]['a'], '1er janvier') !== false && strpos($faq[3]['a'], '25 décembre') !== false, 'faq: cierres de nuestra página de navegación');
foreach ($faq as $qa) {
    check(substr($qa['q'], -2) === ' ?', 'faq: « ' . $qa['q'] . ' »');
}

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
