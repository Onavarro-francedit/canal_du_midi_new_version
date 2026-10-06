<?php
/**
 * Le Canal du Midi à vélo en bref (TASK-066 T4, GEO-IA 05/10): bloque de respuesta directa en /voie-verte-et-veloroute/.
 * Search Console (12 meses): « canal du midi à vélo / en vélo / vélo » ≈ 8 000 impresiones en posición 26–33; « plan piste
 * cyclable canal du midi » 3 437 en posición 7. Todo sale del calcul (distancias, tiempos), de las étapes (reparto en días)
 * y de la página météo (meses): nada redactado. Funciones puras, test en tests/test-velo.php.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_VELO_SLUG = 'voie-verte-et-veloroute';
const CANAL_VELO_END = 'Étang de Thau (Les Onglous)';

/** Cifras del canal entero en bici: km, horas de pedaleo, días (modelo del calcul: 15 km/h, 55 km al día). */
function canal_velo_facts(): array
{
    $r = canal_calcul_compute(0.0, canal_calcul_pk(CANAL_VELO_END));
    return [
        'km'    => number_format($r['km'], 1, ',', '') . ' km',
        'time'  => canal_calcul_duration($r['bike']),
        'days'  => canal_calcul_days_bike($r['km']),
        'locks' => $r['sites'],
    ];
}

/** Reparto en 5, 4 y 3 días: [días => [[desde, hasta, km, tiempo], …]]. */
function canal_velo_plans(): array
{
    $out = [];
    foreach ([5, 4, 3] as $d) {
        $out[$d] = array_map(function ($s) {
            return [$s['from'], $s['to'], (int) round($s['km']) . ' km', canal_calcul_duration($s['km'] / CANAL_CALCUL_BIKE_KMH)];
        }, canal_etapes_split($d));
    }
    return $out;
}

/** Meses para ir en bici: los « Idéal » de la página météo más los « Doux » (abril: menos de 20 °C, nunca 30 °C). */
function canal_velo_months(): array
{
    $out = [];
    for ($i = 0; $i < 12; $i++) {
        if (in_array(canal_meteo_kind($i), ['ideal', 'mild'], true)) {
            $out[] = $i + 1;
        }
    }
    return $out;
}

/** Preguntas de Search Console con respuesta calculada. $rental: pregunta « Où louer un vélo… » de la carte (o null). */
function canal_velo_faq(?array $rental): array
{
    $f = canal_velo_facts();
    $plans = canal_velo_plans();
    $plan = function (int $d) use ($plans): string {
        return implode(' ; ', array_map(function ($l) { return $l[0] . ' → ' . $l[1] . ' (' . $l[2] . ')'; }, $plans[$d]));
    };
    $hot = canal_meteo_col('hot', 6);
    $faq = [
        [
            'q' => 'Combien de kilomètres fait le Canal du Midi à vélo ?',
            'a' => $f['km'] . ' de Toulouse à l’étang de Thau par le chemin de halage, soit ' . $f['time'] . ' de selle à 15 km/h. Le long du chemin, '
                . $f['locks'] . ' écluses jalonnent le parcours.',
        ],
        [
            'q' => 'Combien de jours pour faire le Canal du Midi à vélo ?',
            'a' => 'Comptez ' . str_replace('≈ ', '', $f['days']) . ' à 55 km par jour. En 5 jours : ' . $plan(5) . '. En 4 jours : ' . $plan(4) . '.',
        ],
        [
            'q' => 'Quand faire le Canal du Midi à vélo ?',
            'a' => ucfirst(canal_meteo_months_label(canal_velo_months())) . ' : moins de 9 jours à 30 °C ou plus par mois sur tout le canal (moyennes '
                . CANAL_METEO_YEARS . ', Météo-France). En juillet, ' . canal_meteo_span(min($hot), max($hot), 'canal_meteo_days')
                . ' jours à 30 °C ou plus selon l’étape : roulez le matin.',
        ],
        [
            'q' => 'Quelles étapes à vélo en famille sur le Canal du Midi ?',
            'a' => 'Des tronçons de 10 à 25 km entre deux étapes : ' . canal_etape_list(canal_etapes_short_legs()) . '.',
        ],
    ];
    if ($rental) {
        $faq[] = $rental;
    }
    return $faq;
}
