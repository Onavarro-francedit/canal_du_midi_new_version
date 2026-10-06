<?php
/**
 * Introducción por datos de /categorie/camping/ y /categorie/location-bateau/ (TASK-066 M2/M4, GEO-IA 05/10):
 * distancia a la orilla de cada ficha sobre el trazado OSM del calcul. Solo la frase de la columna de filtros:
 * la tabla por PK y la FAQ al pie de la lista se quitaron (06/10, nadie las lee y hacían scroll en la lista).
 * Funciones puras (sin WordPress), testeables con PHP CLI. Solo datos: nada de texto generado.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_GUIDE_BANK_M  = 300;    // « au bord du canal »
const CANAL_GUIDE_MAX_M   = 15000;  // más lejos: no está « le long du canal »
const CANAL_GUIDE_BASE_KM = 3;      // una base toma el nombre de la ciudad del calcul si está a menos de 3 km
// Fuente: nuestra página « Règles de navigation » (la usa la FAQ de /etapes/).
// Sin « Oui/Non » delante: depende de cómo se formula la pregunta.
const CANAL_GUIDE_PERMIS_ANSWER = 'Pour un bateau loué à une société de location, vous êtes dispensé du permis : le loueur vous délivre une carte de plaisance '
    . 'à la signature du contrat. La vitesse est limitée à 8 km/h sur le canal.';
const CANAL_GUIDE_EXCLUDE = 'lieux-dinformations'; // offices de tourisme clasificadas también como camping o loueur

/** @return array<int, array{0: float, 1: float, 2: float}> [lat, lng, pk] */
function canal_guide_trace(string $file): array
{
    static $cache = [];
    if (!isset($cache[$file])) {
        $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
        $cache[$file] = is_array($data) ? $data : [];
    }
    return $cache[$file];
}

/** PK del punto más cercano del trazado y distancia en metros (proyección sobre cada tramo, plano local). */
function canal_guide_project(array $trace, float $lat, float $lng): array
{
    $kx = 111320 * cos(deg2rad(43.3));
    $ky = 110570;
    $best = INF;
    $pk = 0.0;
    for ($i = 1, $n = count($trace); $i < $n; $i++) {
        [$la, $oa, $ka] = $trace[$i - 1];
        [$lb, $ob, $kb] = $trace[$i];
        $dx = ($ob - $oa) * $kx;
        $dy = ($lb - $la) * $ky;
        $px = ($lng - $oa) * $kx;
        $py = ($lat - $la) * $ky;
        $len2 = $dx * $dx + $dy * $dy;
        $t = $len2 > 0 ? max(0.0, min(1.0, ($px * $dx + $py * $dy) / $len2)) : 0.0;
        $d = sqrt(($px - $t * $dx) ** 2 + ($py - $t * $dy) ** 2);
        if ($d < $best) {
            $best = $d;
            $pk = $ka + $t * ($kb - $ka);
        }
    }
    return ['pk' => round($pk, 1), 'm' => (int) round($best)];
}

/** Fichas de una categoría a menos de 15 km del canal, ordenadas por PK. */
function canal_guide_rows(array $listings, string $slug, array $trace): array
{
    $rows = [];
    foreach ($listings as $item) {
        if ($item['lat'] === null || $item['lng'] === null || !in_array($slug, $item['cat_slugs'], true)
            || in_array(CANAL_GUIDE_EXCLUDE, $item['cat_slugs'], true)) {
            continue;
        }
        $p = canal_guide_project($trace, $item['lat'], $item['lng']);
        if ($p['m'] <= CANAL_GUIDE_MAX_M) {
            $title = canal_fiche_display_title((string) preg_replace('/\s+/u', ' ', $item['title']));
            $rows[] = ['title' => $title, 'url' => $item['url'], 'city' => $item['city']] + $p;
        }
    }
    usort($rows, function ($a, $b) {
        return $a['pk'] <=> $b['pk'];
    });
    return $rows;
}

function canal_guide_plural(int $n, string $word): string
{
    return $n . ' ' . $word . ($n > 1 ? 's' : '');
}

/** Introducción de la categoría (camping, location-bateau), o null si no tiene. */
function canal_guide_for(string $slug, array $listings, array $trace): ?string
{
    if ($slug === 'camping') {
        return canal_guide_camping(canal_guide_rows($listings, 'camping', $trace));
    }
    if ($slug === 'location-bateau') {
        return canal_guide_bateau(canal_guide_rows($listings, 'location-bateau', $trace));
    }
    return null;
}

function canal_guide_camping(array $rows): ?string
{
    if (!$rows) {
        return null;
    }
    $bank = count(array_filter($rows, function ($r) {
        return $r['m'] < CANAL_GUIDE_BANK_M;
    }));
    $near = count(array_filter($rows, function ($r) {
        return $r['m'] >= CANAL_GUIDE_BANK_M && $r['m'] < 1000;
    }));
    return canal_guide_plural(count($rows), 'camping') . ' référencés le long du Canal du Midi : '
        . $bank . ' au bord du canal (moins de 300 m), ' . $near . ' à moins de 1 km, les autres à quelques kilomètres.';
}

/** Ciudad del calcul más cercana por PK (a menos de 3 km), o la commune de la ficha. */
function canal_guide_base(float $pk, string $city): string
{
    $best = null;
    foreach (CANAL_CALCUL_TOWNS as $t) {
        if (abs($t['pk'] - $pk) <= CANAL_GUIDE_BASE_KM && ($best === null || abs($t['pk'] - $pk) < abs($best['pk'] - $pk))) {
            $best = $t;
        }
    }
    return $best ? $best['name'] : $city;
}

/** Fuente de la regla: nuestra página « Règles de navigation ». */
function canal_guide_bateau(array $rows): ?string
{
    $bases = [];
    foreach ($rows as $r) {
        $name = canal_guide_base($r['pk'], $r['city']);
        if ($name !== '') {
            $bases[$name] = true;
        }
    }
    if (!$bases) {
        return null;
    }
    $bases = array_keys($bases);
    return 'Pas besoin de permis : pour un bateau loué, le loueur vous délivre une carte de plaisance à la signature du contrat, '
        . 'et la vitesse est limitée à 8 km/h. ' . canal_guide_plural(count($rows), 'loueur') . ' dans ' . canal_guide_plural(count($bases), 'base')
        . ', de ' . $bases[0] . ' à ' . end($bases) . '.';
}
