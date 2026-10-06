<?php
/**
 * Guías por datos en /categorie/camping/ y /categorie/location-bateau/ (TASK-066 M2/M4, GEO-IA 05/10):
 * PK y distancia a la orilla de cada ficha sobre el trazado OSM del calcul; tramos entre bases con calcul-core.
 * Funciones puras (sin WordPress), testeables con PHP CLI. Solo datos: nada de texto generado.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_GUIDE_BANK_M  = 300;    // « au bord du canal »
const CANAL_GUIDE_MAX_M   = 15000;  // más lejos: no está « le long du canal »
const CANAL_GUIDE_BASE_KM = 3;      // una base toma el nombre de la ciudad del calcul si está a menos de 3 km
const CANAL_GUIDE_RULES_URL = '/navigation/regles-de-navigation/';
// Fuente: nuestra página « Règles de navigation » (también la usa la FAQ de /etapes/).
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

function canal_guide_distance_label(int $m): string
{
    if ($m < CANAL_GUIDE_BANK_M) {
        return 'Au bord du canal';
    }
    return $m < 1000 ? 'À ' . (int) (round($m / 100) * 100) . ' m' : 'À ' . number_format($m / 1000, 1, ',', '') . ' km';
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

/**
 * Bloque de la guía para una categoría, o null si no tiene.
 * @return array{title: string, intro: string, columns: string[], rows: array, links: array, faq: array{q: string, a: string}, source: string}|null
 */
function canal_guide_for(string $slug, array $listings, array $trace): ?array
{
    if ($slug === 'camping') {
        return canal_guide_camping(canal_guide_rows($listings, 'camping', $trace));
    }
    if ($slug === 'location-bateau') {
        return canal_guide_bateau(canal_guide_rows($listings, 'location-bateau', $trace));
    }
    return null;
}

function canal_guide_camping(array $rows): ?array
{
    if (!$rows) {
        return null;
    }
    $bank = array_filter($rows, function ($r) {
        return $r['m'] < CANAL_GUIDE_BANK_M;
    });
    $near = array_filter($rows, function ($r) {
        return $r['m'] >= CANAL_GUIDE_BANK_M && $r['m'] < 1000;
    });
    $name = function ($r) {
        return $r['title'] . ' (' . ($r['city'] !== '' ? $r['city'] . ', ' : '') . canal_etape_pk_label($r['pk']) . ')';
    };
    $a = $bank
        ? 'Au bord du canal (moins de 300 m) : ' . implode(', ', array_map($name, $bank)) . '.'
        : 'Aucun camping référencé n’est à moins de 300 m du canal.';
    if ($near) {
        $a .= ' À moins de 1 km : ' . implode(', ', array_map($name, $near)) . '.';
    }
    $a .= ' Les autres sont à quelques kilomètres du canal.';
    return [
        'title'   => 'Campings le long du canal',
        'intro'   => canal_guide_plural(count($rows), 'camping') . ' référencés le long du Canal du Midi : '
            . count($bank) . ' au bord du canal (moins de 300 m), ' . count($near) . ' à moins de 1 km, les autres à quelques kilomètres.',
        'columns' => ['Camping', 'Commune', 'PK', 'Distance du canal'],
        'rows'    => array_map(function ($r) {
            return [$r['title'], $r['city'], canal_etape_pk_label($r['pk']), canal_guide_distance_label($r['m'])];
        }, $rows),
        'links'   => array_column($rows, 'url'),
        'faq'     => ['q' => 'Quels campings sont au bord du Canal du Midi ?', 'a' => $a],
        'source'  => 'PK depuis Toulouse et distance à vol d’oiseau entre l’adresse du camping et le tracé du canal (OpenStreetMap). Le canal de jonction et le canal de la Robine ne sont pas comptés.',
    ];
}

/** Ciudad del calcul más cercana por PK (a menos de 3 km) con su PK, o la commune de la ficha. */
function canal_guide_base(float $pk, string $city): array
{
    $best = null;
    foreach (CANAL_CALCUL_TOWNS as $t) {
        if (abs($t['pk'] - $pk) <= CANAL_GUIDE_BASE_KM && ($best === null || abs($t['pk'] - $pk) < abs($best['pk'] - $pk))) {
            $best = $t;
        }
    }
    return $best ? ['name' => $best['name'], 'pk' => $best['pk']] : ['name' => $city, 'pk' => $pk];
}

function canal_guide_bateau(array $rows): ?array
{
    $bases = [];
    foreach ($rows as $r) {
        $base = canal_guide_base($r['pk'], $r['city']);
        $name = $base['name'];
        if ($name === '') {
            continue;
        }
        $bases[$name] = $bases[$name] ?? $base + ['n' => 0];
        $bases[$name]['n']++;
    }
    $bases = array_values($bases);
    if (!$bases) {
        return null;
    }
    $out = [];
    foreach ($bases as $i => $base) {
        $leg = '—';
        if (isset($bases[$i + 1])) {
            $next = $bases[$i + 1];
            $c = canal_calcul_compute($base['pk'], $next['pk']);
            $leg = 'jusqu’à ' . $next['name'] . ' : ' . number_format($c['km'], 0, ',', '') . ' km, '
                . mb_strtolower(canal_calcul_locks_label($c['sites'], $c['sas']), 'UTF-8') . ', ' . canal_calcul_duration($c['boat']);
        }
        $out[] = [$base['name'], canal_etape_pk_label($base['pk']), canal_guide_plural($base['n'], 'loueur'), $leg];
    }
    $first = $bases[0]['name'];
    $last = end($bases)['name'];
    return [
        'title'   => 'Bases de location, de Toulouse à la mer',
        'intro'   => 'Pas besoin de permis : pour un bateau loué, le loueur vous délivre une carte de plaisance à la signature du contrat, '
            . 'et la vitesse est limitée à 8 km/h. ' . canal_guide_plural(count($rows), 'loueur') . ' dans ' . canal_guide_plural(count($bases), 'base')
            . ', de ' . $first . ' à ' . $last . '.',
        'columns' => ['Base', 'PK', 'Loueurs', 'Jusqu’à la base suivante'],
        'rows'    => $out,
        'links'   => [],
        'faq'     => [
            'q' => 'Faut-il un permis pour louer un bateau sur le Canal du Midi ?',
            'a' => 'Non. ' . CANAL_GUIDE_PERMIS_ANSWER,
        ],
        'source'  => 'Règles : notre page « Règles de navigation ». Temps de navigation : 7 km/h et 10 minutes par sas d’écluse, comme le calcul de distance.',
    ];
}
