<?php
/**
 * Météo du Canal du Midi (TASK-066 M1): « Quand partir ? » — el año de un vistazo, según cómo se viaja, las estaciones y la
 * FAQ (maqueta docs/mockups/meteo-2026.html, estructura de WeatherSpark / Weather2Travel / canal-du-midi.com).
 * Datos: Météo-France, « Données climatologiques de base – mensuelles » (meteo.data.gouv.fr, Licence Ouverte v2), media
 * de los 5 últimos años completos en 8 estaciones a lo largo del canal (el usuario descartó las normales 1991–2020).
 * Cada enero: `php wp-plugin/build/build-meteo.php` → pegar en CANAL_METEO_STATIONS, cambiar CANAL_METEO_YEARS y
 * CANAL_METEO_UPDATED, ajustar tests/test-meteo.php. Navegación: nuestra página « Période de navigation ».
 * Funciones puras (todo el texto sale de los datos), test en tests/test-meteo.php.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_METEO_SLUG = 'meteo-du-canal-du-midi';
const CANAL_METEO_NAV_URL = '/navigation/periode-de-navigation/';
const CANAL_METEO_SOURCE_URL = 'https://meteo.data.gouv.fr/datasets/donnees-climatologiques-de-base-mensuelles';
const CANAL_METEO_YEARS = '2021–2025';
const CANAL_METEO_UPDATED = '2026-10-05T12:00:00+02:00';
const CANAL_METEO_MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const CANAL_METEO_SHORT = ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];

// Media mensual por estación (orden de PK). tmin/tmax: media de las mínimas/máximas diarias (°C); rain: días con ≥ 1 mm;
// hot: días con máxima ≥ 30 °C. 'name' = lugar del canal que se muestra; 'station' = estación de Météo-France.
const CANAL_METEO_STATIONS = [
    [
        'name' => 'Toulouse', 'station' => 'Toulouse-Blagnac', 'id' => '31069001', 'pk' => 0,
        'tmin' => [2.4, 5.0, 6.8, 8.3, 12.0, 16.8, 17.8, 18.3, 15.2, 12.1, 7.2, 4.7],
        'tmax' => [9.7, 13.5, 15.7, 18.2, 22.6, 28.0, 29.7, 30.7, 25.6, 22.3, 14.8, 12.0],
        'rain' => [6.8, 4.2, 7.4, 8.2, 8.4, 8.4, 4.6, 4.2, 7.0, 6.4, 9.8, 8.0],
        'hot' => [0.0, 0.0, 0.0, 0.0, 1.8, 8.4, 15.2, 15.6, 6.2, 1.2, 0.0, 0.0],
    ],
    [
        'name' => 'Lauragais', 'station' => 'Montesquieu-Lauragais', 'id' => '31374001', 'pk' => 33,
        'tmin' => [3.4, 5.3, 7.0, 8.1, 11.5, 16.2, 17.3, 17.8, 14.9, 12.3, 7.3, 5.2],
        'tmax' => [10.2, 13.3, 15.8, 18.5, 22.9, 28.2, 30.3, 31.0, 25.6, 22.2, 14.5, 11.8],
        'rain' => [9.0, 5.6, 8.6, 9.0, 9.2, 9.2, 4.8, 5.0, 7.6, 5.8, 12.0, 9.8],
        'hot' => [0.0, 0.0, 0.0, 0.0, 2.4, 9.0, 16.6, 17.4, 6.4, 1.6, 0.0, 0.0],
    ],
    [
        'name' => 'Castelnaudary', 'station' => 'Castelnaudary', 'id' => '11076001', 'pk' => 64,
        'tmin' => [2.8, 5.2, 6.7, 7.8, 11.4, 16.5, 17.4, 17.8, 14.9, 12.0, 7.3, 5.1],
        'tmax' => [10.0, 13.5, 15.6, 18.6, 22.8, 28.6, 30.3, 31.3, 25.8, 22.3, 14.8, 12.2],
        'rain' => [8.2, 6.4, 8.8, 8.2, 9.2, 7.4, 4.8, 4.2, 7.8, 6.6, 12.0, 8.4],
        'hot' => [0.0, 0.0, 0.0, 0.4, 1.4, 9.8, 16.8, 18.6, 5.0, 1.2, 0.0, 0.0],
    ],
    [
        'name' => 'Carcassonne', 'station' => 'Carcassonne', 'id' => '11069001', 'pk' => 100,
        'tmin' => [3.0, 5.4, 6.9, 8.1, 11.6, 16.7, 17.6, 18.2, 15.0, 12.2, 7.4, 5.0],
        'tmax' => [10.2, 13.8, 15.7, 18.7, 22.8, 28.5, 30.4, 31.4, 26.0, 22.4, 15.0, 12.4],
        'rain' => [8.4, 5.6, 8.6, 8.0, 8.0, 5.4, 4.8, 3.0, 7.6, 5.0, 10.8, 9.0],
        'hot' => [0.0, 0.0, 0.0, 0.4, 0.8, 9.8, 16.8, 18.4, 4.8, 1.0, 0.0, 0.0],
    ],
    [
        'name' => 'Homps–Lézignan', 'station' => 'Lézignan-Corbières', 'id' => '11203004', 'pk' => 151,
        'tmin' => [3.8, 6.1, 7.1, 8.9, 12.6, 18.1, 19.2, 19.5, 15.9, 12.9, 8.1, 5.8],
        'tmax' => [11.4, 14.3, 16.0, 19.0, 23.3, 29.3, 31.1, 31.7, 26.1, 22.7, 15.7, 13.2],
        'rain' => [5.8, 5.0, 7.0, 6.6, 4.8, 4.2, 3.8, 2.2, 5.0, 4.0, 7.2, 5.8],
        'hot' => [0.0, 0.0, 0.0, 0.2, 1.2, 11.4, 18.0, 19.4, 4.2, 0.2, 0.0, 0.0],
    ],
    [
        'name' => 'Le Somail', 'station' => 'Argeliers', 'id' => '11012001', 'pk' => 172,
        'tmin' => [3.5, 5.8, 6.9, 8.7, 12.5, 17.8, 18.8, 18.9, 15.4, 12.5, 8.0, 5.3],
        'tmax' => [12.4, 15.2, 16.9, 20.4, 24.7, 30.6, 32.5, 33.1, 27.5, 23.8, 16.9, 14.2],
        'rain' => [4.6, 5.8, 6.4, 5.6, 4.2, 3.6, 3.6, 2.8, 4.6, 4.6, 5.8, 5.2],
        'hot' => [0.0, 0.0, 0.0, 0.2, 2.6, 14.2, 24.0, 24.0, 7.4, 0.6, 0.0, 0.0],
    ],
    [
        'name' => 'Béziers', 'station' => 'Béziers-Courtade', 'id' => '34032002', 'pk' => 202,
        'tmin' => [2.1, 4.7, 5.8, 7.6, 11.8, 16.9, 18.0, 17.7, 14.3, 11.3, 6.9, 4.2],
        'tmax' => [12.8, 15.3, 16.7, 20.0, 24.2, 29.8, 31.7, 32.1, 27.0, 23.3, 17.2, 14.2],
        'rain' => [3.8, 3.6, 6.2, 6.0, 4.8, 3.2, 4.0, 2.6, 3.8, 3.6, 5.2, 4.4],
        'hot' => [0.0, 0.0, 0.0, 0.0, 1.4, 12.0, 22.6, 21.6, 4.0, 0.4, 0.0, 0.0],
    ],
    [
        'name' => 'Étang de Thau', 'station' => 'Marseillan', 'id' => '34150001', 'pk' => 240,
        'tmin' => [4.1, 6.6, 8.1, 9.9, 13.7, 18.8, 19.9, 19.4, 16.3, 14.1, 9.0, 5.9],
        'tmax' => [12.6, 14.4, 15.9, 18.7, 22.5, 27.5, 29.8, 29.6, 25.2, 22.1, 16.9, 13.7],
        'rain' => [3.6, 4.0, 5.8, 4.6, 4.8, 3.6, 2.8, 2.8, 3.8, 4.2, 5.2, 4.8],
        'hot' => [0.0, 0.0, 0.0, 0.0, 0.8, 5.6, 13.0, 13.0, 0.2, 0.0, 0.0, 0.0],
    ],
];

// Temporada de navegación por mes (página « Période de navigation ») y horario de las esclusas.
const CANAL_METEO_NAV = ['basse', 'basse', 'basse puis moyenne', 'moyenne', 'haute', 'haute', 'haute', 'haute', 'haute', 'moyenne', 'basse', 'basse'];
const CANAL_METEO_NAV_HOURS = [
    'basse'              => 'écluses à la demande, 8 h 30 – 16 h 30',
    'moyenne'            => 'écluses de 8 h à 19 h',
    'haute'              => 'écluses de 8 h à 19 h 30',
    'basse puis moyenne' => 'à la demande jusqu’au 16, puis écluses de 8 h à 19 h',
];

// Etiqueta de cada mes: navegación en basse saison → « off »; si no, según los días ≥ 30 °C (máximo de las 8 estaciones)
// y la máxima media (mínimo de las 8). [texto, regla mostrada en la leyenda].
const CANAL_METEO_KINDS = [
    'ideal' => ['Idéal', '20 °C ou plus, au plus 8 jours à 30 °C+'],
    'mild'  => ['Doux', 'moins de 20 °C l’après-midi'],
    'hot'   => ['Chaud', '9 à 15 jours à 30 °C+'],
    'vhot'  => ['Très chaud', 'plus de 15 jours à 30 °C+'],
    'off'   => ['Navigation réduite', 'basse saison de navigation'],
];

function canal_meteo_num(float $v): string
{
    return str_replace('.', ',', (string) round($v, 1));
}

/** « Étang de Thau » → « étang de Thau » (lcfirst no sabe de multibyte). */
function canal_meteo_lcfirst(string $s): string
{
    return mb_strtolower(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
}

function canal_meteo_days(float $v): string
{
    return (string) (int) round($v);
}

/** « 22 à 25 » (o « 22 » si coinciden) con el formateador $f. */
function canal_meteo_span(float $a, float $b, callable $f): string
{
    return $f($a) === $f($b) ? $f($a) : $f($a) . ' à ' . $f($b);
}

/** Valores del mes $i (0–11) de la serie $key en las 8 estaciones. */
function canal_meteo_col(string $key, int $i): array
{
    return array_map(function ($s) use ($key, $i) { return $s[$key][$i]; }, CANAL_METEO_STATIONS);
}

/** « 22 à 25 » de la serie $key en el mes $i, de la estación más baja a la más alta. */
function canal_meteo_month_span(string $key, int $i, callable $f): string
{
    $v = canal_meteo_col($key, $i);
    return canal_meteo_span(min($v), max($v), $f);
}

function canal_meteo_kind(int $i): string
{
    if (strpos(CANAL_METEO_NAV[$i], 'basse') === 0) {
        return 'off';
    }
    $hot = max(canal_meteo_col('hot', $i));
    if ($hot > 15) {
        return 'vhot';
    }
    if ($hot > 8) {
        return 'hot';
    }
    return min(canal_meteo_col('tmax', $i)) < 20 ? 'mild' : 'ideal';
}

/** Meses (1–12) « Idéal ». */
function canal_meteo_best_months(): array
{
    $out = [];
    for ($i = 0; $i < 12; $i++) {
        if (canal_meteo_kind($i) === 'ideal') {
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

/** [estación, valor] con el valor más alto (o más bajo) de $key sumado en los meses $idx (0–11). */
function canal_meteo_extreme(string $key, array $idx, bool $max): array
{
    $best = null;
    foreach (CANAL_METEO_STATIONS as $s) {
        $v = array_sum(array_map(function ($i) use ($s, $key) { return $s[$key][$i]; }, $idx));
        if ($best === null || ($max ? $v > $best[1] : $v < $best[1])) {
            $best = [$s, $v];
        }
    }
    return $best;
}

/** Las 12 tarjetas del año: etiqueta, rangos y texto del detalle. */
function canal_meteo_months(): array
{
    $out = [];
    for ($i = 0; $i < 12; $i++) {
        $hot = canal_meteo_col('hot', $i);
        $hotText = max($hot) < 0.5 ? 'jamais 30 °C en moyenne' : canal_meteo_month_span('hot', $i, 'canal_meteo_days') . ' jours à 30 °C ou plus';
        $text = 'L’après-midi, ' . canal_meteo_month_span('tmax', $i, 'canal_meteo_days') . ' °C selon l’étape ; ' . $hotText . ' ; '
            . canal_meteo_month_span('rain', $i, 'canal_meteo_days') . ' jours de pluie. Navigation : ' . CANAL_METEO_NAV[$i] . ' saison, '
            . CANAL_METEO_NAV_HOURS[CANAL_METEO_NAV[$i]] . '.';
        if (max($hot) > 8) {
            [$hi] = canal_meteo_extreme('tmax', [$i], true);
            [$lo] = canal_meteo_extreme('tmax', [$i], false);
            $text .= ' Le plus chaud : ' . $hi['name'] . ' (' . canal_meteo_num($hi['tmax'][$i]) . ' °C, ' . canal_meteo_days($hi['hot'][$i])
                . ' jours à 30 °C+) ; le plus frais : ' . $lo['name'] . ' (' . canal_meteo_num($lo['tmax'][$i]) . ' °C).';
        }
        $kind = canal_meteo_kind($i);
        $out[] = [
            'name'  => CANAL_METEO_MONTHS[$i],
            'short' => CANAL_METEO_SHORT[$i],
            'kind'  => $kind,
            'label' => CANAL_METEO_KINDS[$kind][0],
            'temp'  => str_replace(' à ', '–', canal_meteo_month_span('tmax', $i, 'canal_meteo_days')) . ' °C',
            'hot'   => max($hot) < 0.5 ? 'jamais 30 °C' : str_replace(' à ', '–', canal_meteo_month_span('hot', $i, 'canal_meteo_days')) . ' j à 30 °C+',
            'text'  => $text,
        ];
    }
    return $out;
}

/** Tarjetas « Selon votre façon de voyager »; 'icon' = archivo de assets/icons (Material Symbols de Google). */
function canal_meteo_ways(): array
{
    $best = canal_meteo_best_months();
    [$hi, $hiV] = canal_meteo_extreme('hot', [6, 7], true);
    [$lo, $loV] = canal_meteo_extreme('hot', [6, 7], false);
    return [
        [
            'icon' => 'directions_boat', 'title' => 'En bateau', 'best' => ucfirst(canal_meteo_months_label($best)),
            'points' => [
                'Haute saison de navigation de mai à septembre (écluses de 8 h à 19 h 30), moyenne saison en avril et octobre (8 h – 19 h).',
                'En juillet, ' . canal_meteo_month_span('hot', 6, 'canal_meteo_days') . ' jours à 30 °C ou plus selon l’étape : naviguez tôt le matin.',
                'Du 1er novembre au 16 mars, écluses à la demande de 8 h 30 à 16 h 30 ; navigation fermée les 1er janvier, 1er mai, 11 novembre et 25 décembre.',
            ],
        ],
        [
            'icon' => 'directions_bike', 'title' => 'À vélo', 'best' => 'Avril, ' . canal_meteo_months_label($best),
            'points' => [
                'En avril, ' . canal_meteo_month_span('tmax', 3, 'canal_meteo_days') . ' °C l’après-midi et presque jamais 30 °C : idéal pour enchaîner les étapes.',
                'En été, le plus chaud : vers ' . $hi['name'] . ' (' . canal_meteo_days($hiV) . ' jours à 30 °C+ en juillet-août) ; le plus frais : vers l’'
                    . canal_meteo_lcfirst($lo['name']) . ' (' . canal_meteo_days($loV) . ').',
                'En juillet-août, roulez le matin et emportez de l’eau.',
            ],
        ],
        [
            'icon' => 'directions_walk', 'title' => 'À pied', 'best' => 'Toute l’année hors juillet-août',
            'points' => [
                'En hiver, ' . canal_meteo_month_span('tmax', 0, 'canal_meteo_days') . ' °C l’après-midi en janvier, plus doux vers la Méditerranée.',
                'Le plus pluvieux : novembre, ' . canal_meteo_month_span('rain', 10, 'canal_meteo_days') . ' jours de pluie selon l’étape.',
                'En juillet-août, marchez tôt et évitez les heures chaudes.',
            ],
        ],
    ];
}

/** Las 4 estaciones: tarde, días de calor y de lluvia (suma de la estación), navegación en el orden de los meses. */
function canal_meteo_seasons(): array
{
    $defs = [['Printemps', [2, 3, 4]], ['Été', [5, 6, 7]], ['Automne', [8, 9, 10]], ['Hiver', [11, 0, 1]]];
    $out = [];
    foreach ($defs as [$name, $idx]) {
        $tx = array_merge(...array_map(function ($i) { return canal_meteo_col('tmax', $i); }, $idx));
        $sum = function (string $key) use ($idx): array {
            return array_map(function ($s) use ($key, $idx) {
                return array_sum(array_map(function ($i) use ($s, $key) { return $s[$key][$i]; }, $idx));
            }, CANAL_METEO_STATIONS);
        };
        $hot = $sum('hot');
        $rain = $sum('rain');
        $nav = [];
        foreach ($idx as $i) {
            foreach (explode(' puis ', CANAL_METEO_NAV[$i]) as $part) {
                if (end($nav) !== $part) {
                    $nav[] = $part;
                }
            }
        }
        $out[] = [
            'name'   => $name,
            'months' => implode(', ', array_map(function ($i) { return CANAL_METEO_MONTHS[$i]; }, $idx)),
            'rows'   => [
                ['L’après-midi', canal_meteo_span(min($tx), max($tx), 'canal_meteo_days') . ' °C selon le mois et l’étape'],
                ['Jours à 30 °C ou plus', max($hot) < 0.5 ? 'aucun' : canal_meteo_span(min($hot), max($hot), 'canal_meteo_days') . ' sur la saison selon l’étape'],
                ['Jours de pluie', canal_meteo_span(min($rain), max($rain), 'canal_meteo_days') . ' sur la saison'],
                ['Navigation', implode(' puis ', $nav) . ' saison'],
            ],
        ];
    }
    return $out;
}

/** Preguntas de la página (GEO-IA « meilleure période »; Search Console « fermeture canal du midi », « température hiver »). */
function canal_meteo_faq(): array
{
    $best = canal_meteo_best_months();
    [$hi] = canal_meteo_extreme('hot', [6, 7], true);
    [$lo] = canal_meteo_extreme('hot', [6, 7], false);
    $bestT = array_map(function ($m) {
        return canal_meteo_month_span('tmax', $m - 1, 'canal_meteo_days') . ' °C en ' . CANAL_METEO_MONTHS[$m - 1];
    }, $best);
    $bestHot = max(array_map(function ($m) { return max(canal_meteo_col('hot', $m - 1)); }, $best));
    return [
        [
            'q' => 'Quelle est la meilleure période pour faire le Canal du Midi ?',
            'a' => ucfirst(canal_meteo_months_label($best)) . ' : l’après-midi, ' . implode(' ; ', $bestT) . ' selon l’étape, au plus '
                . canal_meteo_days($bestHot) . ' jours à 30 °C ou plus, et le canal est ouvert à la navigation. Juillet et août sont les mois les plus chauds.',
        ],
        [
            'q' => 'Quel temps fait-il sur le Canal du Midi en été ?',
            'a' => 'En juillet, ' . canal_meteo_month_span('tmax', 6, 'canal_meteo_num') . ' °C de maximale moyenne et '
                . canal_meteo_month_span('hot', 6, 'canal_meteo_days') . ' jours à 30 °C ou plus selon l’étape ; en août, '
                . canal_meteo_month_span('tmax', 7, 'canal_meteo_num') . ' °C et ' . canal_meteo_month_span('hot', 7, 'canal_meteo_days')
                . ' jours. Le plus chaud vers ' . $hi['name'] . ', le plus frais vers l’' . canal_meteo_lcfirst($lo['name']) . '.',
        ],
        [
            'q' => 'Quel temps fait-il sur le Canal du Midi en hiver ?',
            'a' => 'En janvier, ' . canal_meteo_month_span('tmin', 0, 'canal_meteo_num') . ' °C le matin et '
                . canal_meteo_month_span('tmax', 0, 'canal_meteo_num') . ' °C l’après-midi selon l’étape, plus doux vers la Méditerranée.',
        ],
        [
            'q' => 'Le Canal du Midi est-il fermé en hiver ?',
            'a' => 'Non, mais la navigation est en basse saison du 1er novembre au 16 mars : de 8 h 30 à 16 h 30, à la demande. Elle est fermée sur '
                . 'tout le canal le 1er janvier, le 1er mai, le 11 novembre et le 25 décembre. Les fermetures pour travaux (chômages) sont annoncées '
                . 'chaque année par Voies navigables de France.',
        ],
    ];
}
