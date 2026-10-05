<?php
/**
 * Clima del Canal du Midi mes a mes (TASK-066 M1, GEO-IA 05/10): bloque « Quand venir ? » de la página météo 2026.
 * Datos: fichas climatológicas de Météo-France (normales 1991–2020; Béziers-Vias 1994–2020), Licence Ouverte Etalab 2.0,
 * y temporadas de navegación de nuestra página « Période de navigation ». Funciones puras, test en tests/test-meteo.php.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_METEO_SLUG = 'meteo-du-canal-du-midi';
const CANAL_METEO_NAV_URL = '/navigation/periode-de-navigation/';
const CANAL_METEO_MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

// tmin/tmax: temperatura mínima/máxima media (°C); rain: días con ≥ 1 mm; hot: días con máxima ≥ 30 °C.
const CANAL_METEO_STATIONS = [
    [
        'name' => 'Toulouse', 'station' => 'Toulouse-Blagnac', 'period' => '1991–2020',
        'url' => 'https://object.files.data.gouv.fr/meteofrance/data/synchro_ftp/REF_STATION/FICHECLIM_31069001.pdf',
        'tmin' => [2.9, 3.1, 5.5, 7.9, 11.4, 15.0, 17.0, 17.1, 13.9, 10.9, 6.3, 3.6],
        'tmax' => [9.7, 11.2, 15.0, 17.6, 21.4, 25.7, 28.2, 28.5, 24.8, 19.7, 13.5, 10.4],
        'rain' => [9.2, 7.8, 8.2, 9.3, 9.9, 7.1, 5.7, 5.9, 6.6, 7.5, 10.0, 8.7],
        'hot'  => [0, 0, 0, 0, 0.6, 6.0, 11.5, 11.3, 3.5, 0.1, 0, 0],
    ],
    [
        'name' => 'Carcassonne', 'station' => 'Carcassonne', 'period' => '1991–2020',
        'url' => 'https://object.files.data.gouv.fr/meteofrance/data/synchro_ftp/REF_STATION/FICHECLIM_11069001.pdf',
        'tmin' => [3.5, 3.5, 5.9, 8.1, 11.6, 15.1, 17.3, 17.3, 14.1, 11.3, 6.9, 4.2],
        'tmax' => [10.0, 11.4, 14.9, 17.7, 21.4, 25.9, 28.8, 28.9, 24.8, 19.7, 13.9, 10.7],
        'rain' => [9.5, 7.6, 7.8, 9.3, 7.8, 5.6, 4.6, 5.1, 5.6, 7.2, 9.0, 8.5],
        'hot'  => [0, 0, 0, 0, 0.6, 5.9, 13.0, 12.3, 2.5, 0.1, 0, 0],
    ],
    [
        'name' => 'Béziers', 'station' => 'Béziers-Vias', 'period' => '1994–2020',
        'url' => 'https://object.files.data.gouv.fr/meteofrance/data/synchro_ftp/REF_STATION/FICHECLIM_34209002.pdf',
        'tmin' => [3.6, 3.5, 6.0, 8.7, 12.2, 15.9, 18.3, 18.0, 14.6, 12.0, 7.3, 4.0],
        'tmax' => [12.1, 13.1, 16.3, 18.6, 22.3, 27.0, 29.7, 29.3, 25.3, 21.0, 15.9, 12.6],
        'rain' => [5.7, 4.0, 4.4, 5.6, 5.5, 3.4, 2.2, 3.6, 4.3, 5.6, 5.9, 4.9],
        'hot'  => [0, 0, 0, 0, 0.7, 5.6, 14.4, 11.6, 2.2, 0.2, 0, 0],
    ],
];

// Temporadas de navegación por mes (página « Période de navigation »): [temporada, amplitud horaria, en temporada].
const CANAL_METEO_NAV = [
    ['Basse saison', '8 h 30 – 16 h 30, à la demande', false],
    ['Basse saison', '8 h 30 – 16 h 30, à la demande', false],
    ['Basse puis moyenne (17 mars)', '8 h 30 – 16 h 30, puis 8 h – 19 h', false],
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

// « Meilleure période »: máxima media de Carcassonne (centro del canal) entre 20 y 27 °C y navegación en temporada.
const CANAL_METEO_BEST = ['station' => 1, 'min' => 20.0, 'max' => 27.0];

function canal_meteo_num(float $v): string
{
    return str_replace('.', ',', (string) round($v, 1));
}

/** Meses (1–12) que cumplen la regla de CANAL_METEO_BEST. */
function canal_meteo_best_months(): array
{
    $s = CANAL_METEO_STATIONS[CANAL_METEO_BEST['station']];
    $out = [];
    foreach ($s['tmax'] as $i => $t) {
        if ($t >= CANAL_METEO_BEST['min'] && $t <= CANAL_METEO_BEST['max'] && CANAL_METEO_NAV[$i][2]) {
            $out[] = $i + 1;
        }
    }
    return $out;
}

function canal_meteo_months_label(array $months): string
{
    $names = array_map(function ($m) { return CANAL_METEO_MONTHS[$m - 1]; }, $months);
    $last = array_pop($names);
    return $names ? implode(', ', $names) . ' et ' . $last : (string) $last;
}

/** Filas de la tabla: mes, mín/máx por estación, días de lluvia (Carcassonne), navegación, ¿mejor época? */
function canal_meteo_rows(): array
{
    $best = canal_meteo_best_months();
    $rows = [];
    for ($i = 0; $i < 12; $i++) {
        $rows[] = [
            'month' => ucfirst(CANAL_METEO_MONTHS[$i]),
            'temps' => array_map(function ($s) use ($i) {
                return canal_meteo_num($s['tmin'][$i]) . ' / ' . canal_meteo_num($s['tmax'][$i]) . ' °C';
            }, CANAL_METEO_STATIONS),
            'rain'  => canal_meteo_num(CANAL_METEO_STATIONS[1]['rain'][$i]) . ' j',
            'nav'   => CANAL_METEO_NAV[$i][0],
            'best'  => in_array($i + 1, $best, true),
        ];
    }
    return $rows;
}

/** Preguntas de la página (GEO-IA « meilleure période »; Search Console « fermeture canal du midi », « température hiver »). */
function canal_meteo_faq(): array
{
    [$tls, $car, $bez] = CANAL_METEO_STATIONS;
    $best = canal_meteo_best_months();
    $bestT = array_map(function ($m) use ($car) { return $car['tmax'][$m - 1]; }, $best);
    $summer = function (array $s): string {
        return $s['name'] . ' ' . canal_meteo_num($s['tmax'][6]) . ' °C en juillet (' . canal_meteo_num($s['hot'][6]) . ' jours à 30 °C ou plus, '
            . canal_meteo_num($s['rain'][6]) . ' jours de pluie)';
    };
    $winter = function (array $s): string {
        return $s['name'] . ' ' . canal_meteo_num($s['tmin'][0]) . ' à ' . canal_meteo_num($s['tmax'][0]) . ' °C';
    };
    return [
        [
            'q' => 'Quelle est la meilleure période pour faire le Canal du Midi ?',
            'a' => ucfirst(canal_meteo_months_label($best)) . ' : à Carcassonne, au centre du canal, la température maximale moyenne y va de '
                . canal_meteo_num(min($bestT)) . ' à ' . canal_meteo_num(max($bestT)) . ' °C et la navigation est en haute saison (8 h – 19 h 30). '
                . 'En juillet et août il fait plus chaud : ' . canal_meteo_num($car['tmax'][6]) . ' °C de maximale moyenne à Carcassonne en juillet et '
                . canal_meteo_num($car['hot'][6]) . ' jours à 30 °C ou plus.',
        ],
        [
            'q' => 'Quel temps fait-il sur le Canal du Midi en été ?',
            'a' => 'Températures maximales moyennes : ' . $summer($tls) . ' ; ' . $summer($car) . ' ; ' . $summer($bez) . '.',
        ],
        [
            'q' => 'Quel temps fait-il sur le Canal du Midi en hiver ?',
            'a' => 'En janvier, minimales et maximales moyennes : ' . $winter($tls) . ', ' . $winter($car) . ', ' . $winter($bez) . '.',
        ],
        [
            'q' => 'Le Canal du Midi est-il fermé en hiver ?',
            'a' => 'Non, mais la navigation est en basse saison du 1er novembre au 16 mars : de 8 h 30 à 16 h 30, à la demande. Elle est fermée sur '
                . 'tout le canal le 1er janvier, le 1er mai, le 11 novembre et le 25 décembre. Les fermetures pour travaux (chômages) sont annoncées '
                . 'chaque année par Voies navigables de France.',
        ],
    ];
}
