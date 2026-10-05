<?php
/**
 * Clima del Canal du Midi mes a mes (TASK-066 M1, GEO-IA 05/10): bloque « Quand venir ? » de la página météo 2026.
 * Datos: Météo-France, « Données climatologiques de base – mensuelles » (meteo.data.gouv.fr, Licence Ouverte v2), media de
 * los 5 últimos años completos (CANAL_METEO_YEARS). El usuario descartó las normales 1991–2020 (05/10: no reflejan los
 * veranos actuales). Se regenera cada enero con `php wp-plugin/build/build-meteo.php` (pega su salida en CANAL_METEO_STATIONS).
 * Temporadas de navegación: nuestra página « Période de navigation ». Funciones puras, test en tests/test-meteo.php.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_METEO_SLUG = 'meteo-du-canal-du-midi';
const CANAL_METEO_NAV_URL = '/navigation/periode-de-navigation/';
const CANAL_METEO_SOURCE_URL = 'https://meteo.data.gouv.fr/datasets/donnees-climatologiques-de-base-mensuelles';
const CANAL_METEO_YEARS = '2021–2025';
const CANAL_METEO_MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

// Media mensual 2021–2025. tmin/tmax: media de las mínimas/máximas diarias (°C); rain: días con ≥ 1 mm; hot: días con
// máxima ≥ 30 °C; heat: días con máxima ≥ 35 °C; record: máxima más alta del mes en esos años [°C, año].
const CANAL_METEO_STATIONS = [
    [
        'name' => 'Toulouse', 'station' => 'Toulouse-Blagnac', 'id' => '31069001',
        'tmin' => [2.4, 5.0, 6.8, 8.3, 12.0, 16.8, 17.8, 18.3, 15.2, 12.1, 7.2, 4.7],
        'tmax' => [9.7, 13.5, 15.7, 18.2, 22.6, 28.0, 29.7, 30.7, 25.6, 22.3, 14.8, 12.0],
        'rain' => [6.8, 4.2, 7.4, 8.2, 8.4, 8.4, 4.6, 4.2, 7.0, 6.4, 9.8, 8.0],
        'hot'  => [0.0, 0.0, 0.0, 0.0, 1.8, 8.4, 15.2, 15.6, 6.2, 1.2, 0.0, 0.0],
        'heat' => [0.0, 0.0, 0.0, 0.0, 0.0, 2.8, 4.2, 6.4, 0.0, 0.0, 0.0, 0.0],
        'record' => [[18.5, 2025], [20.5, 2024], [24.3, 2024], [29.2, 2024], [34.5, 2025], [38.9, 2022], [39.4, 2022], [42.4, 2023], [34.4, 2022], [33.0, 2023], [24.1, 2023], [19.7, 2022]],
    ],
    [
        'name' => 'Carcassonne', 'station' => 'Carcassonne', 'id' => '11069001',
        'tmin' => [3.0, 5.4, 6.9, 8.1, 11.6, 16.7, 17.6, 18.2, 15.0, 12.2, 7.4, 5.0],
        'tmax' => [10.2, 13.8, 15.7, 18.7, 22.8, 28.5, 30.4, 31.4, 26.0, 22.4, 15.0, 12.4],
        'rain' => [8.4, 5.6, 8.6, 8.0, 8.0, 5.4, 4.8, 3.0, 7.6, 5.0, 10.8, 9.0],
        'hot'  => [0.0, 0.0, 0.0, 0.4, 0.8, 9.8, 16.8, 18.4, 4.8, 1.0, 0.0, 0.0],
        'heat' => [0.0, 0.0, 0.0, 0.0, 0.0, 2.8, 4.8, 7.2, 0.0, 0.0, 0.0, 0.0],
        'record' => [[20.4, 2024], [21.2, 2024], [26.4, 2024], [31.3, 2024], [34.0, 2025], [40.7, 2022], [39.8, 2025], [43.2, 2023], [32.8, 2022], [31.9, 2023], [25.2, 2023], [21.0, 2022]],
    ],
    [
        'name' => 'Béziers', 'station' => 'Béziers-Vias', 'id' => '34209002',
        'tmin' => [2.8, 5.4, 6.6, 8.4, 12.4, 17.9, 19.1, 19.0, 15.6, 12.8, 7.6, 4.9],
        'tmax' => [12.5, 14.8, 16.2, 19.3, 23.3, 28.9, 31.1, 31.2, 26.2, 22.6, 17.0, 13.9],
        'rain' => [3.8, 3.6, 5.6, 4.4, 4.6, 2.4, 2.4, 2.6, 3.6, 3.2, 4.4, 5.0],
        'hot'  => [0.0, 0.0, 0.0, 0.0, 1.2, 10.6, 17.2, 19.0, 1.8, 0.0, 0.0, 0.0],
        'heat' => [0.0, 0.0, 0.0, 0.0, 0.0, 1.4, 3.2, 4.4, 0.0, 0.0, 0.0, 0.0],
        'record' => [[20.6, 2024], [23.8, 2023], [24.9, 2024], [28.6, 2022], [32.5, 2025], [37.6, 2025], [40.4, 2022], [40.0, 2025], [33.8, 2023], [29.0, 2023], [24.6, 2022], [21.6, 2023]],
    ],
];

// Temporadas de navegación por mes (página « Période de navigation »): [temporada, amplitud horaria, en temporada].
const CANAL_METEO_NAV = [
    ['Basse saison', '8 h 30 – 16 h 30, à la demande', false],
    ['Basse saison', '8 h 30 – 16 h 30, à la demande', false],
    ['Basse, moyenne le 17', '8 h 30 – 16 h 30, puis 8 h – 19 h', false],
    ['Moyenne saison', '8 h – 19 h', true],
    ['Haute saison', '8 h – 19 h 30', true],
    ['Haute saison', '8 h – 19 h 30', true],
    ['Haute saison', '8 h – 19 h 30', true],
    ['Haute saison', '8 h – 19 h 30', true],
    ['Haute saison', '8 h – 19 h 30', true],
    ['Moyenne saison', '8 h – 19 h', true],
    ['Basse saison', '8 h 30 – 16 h 30, à la demande', false],
    ['Basse saison', '8 h 30 – 16 h 30, à la demande', false],
];

// Meses aconsejados: en las tres ciudades, como mucho 7 días a 30 °C o más, máxima media de 20 °C o más, y navegación en
// temporada (medias del periodo de CANAL_METEO_YEARS).
const CANAL_METEO_BEST = ['hot_max' => 7.0, 'tmax_min' => 20.0];

function canal_meteo_num(float $v): string
{
    return str_replace('.', ',', (string) round($v, 1));
}

/** Días (media de 5 años) redondeados al entero en el texto: « 35 jours ». */
function canal_meteo_days(float $v): string
{
    return (string) (int) round($v);
}

/** Meses (1–12) que cumplen la regla de CANAL_METEO_BEST en las tres ciudades. */
function canal_meteo_best_months(): array
{
    $out = [];
    for ($i = 0; $i < 12; $i++) {
        $ok = CANAL_METEO_NAV[$i][2];
        foreach (CANAL_METEO_STATIONS as $s) {
            $ok = $ok && $s['hot'][$i] <= CANAL_METEO_BEST['hot_max'] && $s['tmax'][$i] >= CANAL_METEO_BEST['tmax_min'];
        }
        if ($ok) {
            $out[] = $i + 1;
        }
    }
    return $out;
}

/** « 22,6 à 23,3 °C » : de la máxima media más baja a la más alta de las tres ciudades en el mes $i (0–11). */
function canal_meteo_range(string $key, int $i, string $unit): string
{
    $v = array_map(function ($s) use ($key, $i) { return $s[$key][$i]; }, CANAL_METEO_STATIONS);
    $f = $unit === ' °C' ? 'canal_meteo_num' : 'canal_meteo_days';
    return $f(min($v)) === $f(max($v)) ? $f(min($v)) . $unit : $f(min($v)) . ' à ' . $f(max($v)) . $unit;
}

function canal_meteo_months_label(array $months): string
{
    $names = array_map(function ($m) { return CANAL_METEO_MONTHS[$m - 1]; }, $months);
    $last = array_pop($names);
    return $names ? implode(', ', $names) . ' et ' . $last : (string) $last;
}

/** Filas de la tabla: mes, máxima media por estación, días ≥ 30 °C, máxima registrada y lluvia (Carcassonne), navegación. */
function canal_meteo_rows(): array
{
    $best = canal_meteo_best_months();
    $car = CANAL_METEO_STATIONS[1];
    $rows = [];
    for ($i = 0; $i < 12; $i++) {
        $rows[] = [
            'month'  => ucfirst(CANAL_METEO_MONTHS[$i]),
            'temps'  => array_map(function ($s) use ($i) {
                return canal_meteo_num($s['tmax'][$i]) . ' °C';
            }, CANAL_METEO_STATIONS),
            'hot'    => canal_meteo_num($car['hot'][$i]) . ' j',
            'record' => canal_meteo_num($car['record'][$i][0]) . ' °C (' . $car['record'][$i][1] . ')',
            'rain'   => canal_meteo_num($car['rain'][$i]) . ' j',
            'nav'    => CANAL_METEO_NAV[$i][0],
            'best'   => in_array($i + 1, $best, true),
        ];
    }
    return $rows;
}

/** Preguntas de la página (GEO-IA « meilleure période »; Search Console « fermeture canal du midi », « température hiver »). */
function canal_meteo_faq(): array
{
    [$tls, $car, $bez] = CANAL_METEO_STATIONS;
    $y = CANAL_METEO_YEARS;
    $best = canal_meteo_best_months();
    $max = function (array $s, array $months): array {
        $r = [0.0, 0];
        foreach ($months as $m) {
            if ($s['record'][$m - 1][0] > $r[0]) {
                $r = $s['record'][$m - 1] + [2 => $m];
            }
        }
        return $r;
    };
    $peak = $max($car, [7, 8]);
    $summer = function (array $s): string {
        return $s['name'] . ' : ' . canal_meteo_num($s['tmax'][6]) . ' °C en juillet et ' . canal_meteo_num($s['tmax'][7]) . ' °C en août, '
            . canal_meteo_days($s['hot'][6] + $s['hot'][7]) . ' jours à 30 °C ou plus dont ' . canal_meteo_days($s['heat'][6] + $s['heat'][7])
            . ' à 35 °C ou plus';
    };
    $winter = function (array $s): string {
        return $s['name'] . ' ' . canal_meteo_num($s['tmin'][0]) . ' à ' . canal_meteo_num($s['tmax'][0]) . ' °C';
    };
    return [
        [
            'q' => 'Quelle est la meilleure période pour faire le Canal du Midi ?',
            'a' => 'Pour éviter les fortes chaleurs : ' . canal_meteo_months_label($best) . '. Sur ' . $y . ', ces mois comptent au plus '
                . canal_meteo_days(CANAL_METEO_BEST['hot_max']) . ' jours à 30 °C ou plus à Toulouse, Carcassonne et Béziers, pour une maximale moyenne de '
                . implode(' ; ', array_map(function ($m) { return canal_meteo_range('tmax', $m - 1, ' °C') . ' en ' . CANAL_METEO_MONTHS[$m - 1]; }, $best))
                . ', et le canal est ouvert à la navigation. En juillet, la maximale moyenne est de ' . canal_meteo_range('tmax', 6, ' °C') . ' avec '
                . canal_meteo_range('hot', 6, ' jours') . ' à 30 °C ou plus selon la ville ; en août, ' . canal_meteo_range('tmax', 7, ' °C') . ' et '
                . canal_meteo_range('hot', 7, ' jours') . '. La température a atteint ' . canal_meteo_num($peak[0]) . ' °C à Carcassonne en '
                . CANAL_METEO_MONTHS[$peak[2] - 1] . ' ' . $peak[1] . '.',
        ],
        [
            'q' => 'Quel temps fait-il sur le Canal du Midi en été ?',
            'a' => 'Moyennes ' . $y . ' des températures maximales — ' . $summer($tls) . ' ; ' . $summer($car) . ' ; ' . $summer($bez) . '.',
        ],
        [
            'q' => 'Quel temps fait-il sur le Canal du Midi en hiver ?',
            'a' => 'En janvier (moyennes ' . $y . ' des minimales et des maximales) : ' . $winter($tls) . ', ' . $winter($car) . ', ' . $winter($bez) . '.',
        ],
        [
            'q' => 'Le Canal du Midi est-il fermé en hiver ?',
            'a' => 'Non, mais la navigation est en basse saison du 1er novembre au 16 mars : de 8 h 30 à 16 h 30, à la demande. Elle est fermée sur '
                . 'tout le canal le 1er janvier, le 1er mai, le 11 novembre et le 25 décembre. Les fermetures pour travaux (chômages) sont annoncées '
                . 'chaque année par Voies navigables de France.',
        ],
    ];
}
