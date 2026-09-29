<?php
/**
 * Filtro de la carte — funciones puras (sin WordPress), testeables con PHP CLI.
 * Entrada: $_GET sin barras (wp_unslash). Acepta los parámetros de /explorer/ como alias.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_CARTE_MAX_TEXT = 100;

function canal_carte_fold(string $s): string
{
    $map = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
    ];
    $map["\u{2019}"] = "'";
    return strtr(mb_strtolower($s, 'UTF-8'), $map);
}

function canal_carte_text($value): string
{
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
        return '';
    }
    $clean = (string) preg_replace('/[\x00-\x1F\x7F<>]+|\s+/u', ' ', $value);
    return trim(mb_substr(trim($clean), 0, CANAL_CARTE_MAX_TEXT, 'UTF-8'));
}

function canal_carte_coord($value, float $max): ?float
{
    if (!is_string($value) || !is_numeric($value)) {
        return null;
    }
    $coord = round((float) $value, 2);
    return ($coord >= -$max && $coord <= $max) ? $coord : null;
}

function canal_carte_params(array $get, array $validCatSlugs): array
{
    $pick = function (string $key, string $alias) use ($get) {
        return $get[$key] ?? $get[$alias] ?? null;
    };
    $types = $pick('type', 'category');
    $types = is_array($types) ? $types : ($types === null ? [] : [$types]);
    $types = array_values(array_unique(array_filter($types, function ($slug) use ($validCatSlugs) {
        return is_string($slug) && in_array($slug, $validCatSlugs, true);
    })));
    $lat = canal_carte_coord($get['lat'] ?? null, 90);
    $lng = canal_carte_coord($get['lng'] ?? null, 180);
    $hasGeo = $lat !== null && $lng !== null;
    return [
        'q'        => canal_carte_text($pick('q', 'search_keywords')),
        'type'     => $types,
        'location' => canal_carte_text($pick('location', 'search_location')),
        'lat'      => $hasGeo ? $lat : null,
        'lng'      => $hasGeo ? $lng : null,
    ];
}

// Parámetros que filtran la carte (propios + alias de /explorer/). Una URL con alguno relleno es una
// variante de la página: noindex,follow (la canonical de WordPress apunta a la URL limpia).
const CANAL_CARTE_FILTER_PARAMS = ['q', 'type', 'location', 'lat', 'lng', 'search_keywords', 'search_location', 'category'];

function canal_carte_has_filters(array $get): bool
{
    foreach (CANAL_CARTE_FILTER_PARAMS as $key) {
        $values = (array) ($get[$key] ?? []);
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }
    }
    return false;
}

function canal_carte_distance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function canal_carte_filter(array $listings, array $params): array
{
    $words = array_filter(preg_split('/\s+/u', canal_carte_fold($params['q'])) ?: [], function ($word) {
        return mb_strlen($word, 'UTF-8') >= 3;
    });
    $location = canal_carte_fold($params['location']);
    $hasGeo = $params['lat'] !== null && $params['lng'] !== null;
    $out = [];
    foreach ($listings as $item) {
        $haystack = canal_carte_fold($item['title'] . ' ' . implode(' ', $item['cat_names']) . ' ' . $item['city'] . ' ' . $item['description']);
        foreach ($words as $word) {
            if (strpos($haystack, $word) === false) {
                continue 2;
            }
        }
        if ($params['type'] && !array_intersect($params['type'], $item['cat_slugs'])) {
            continue;
        }
        if ($location !== '' && strpos(canal_carte_fold($item['city'] . ' ' . $item['address']), $location) === false) {
            continue;
        }
        if ($hasGeo && $item['lat'] !== null && $item['lng'] !== null) {
            $item['distance_km'] = canal_carte_distance($params['lat'], $params['lng'], $item['lat'], $item['lng']);
        }
        $out[] = $item;
    }
    usort($out, function ($a, $b) {
        return (($a['distance_km'] ?? INF) <=> ($b['distance_km'] ?? INF))
            ?: strcmp(canal_carte_fold($a['title']), canal_carte_fold($b['title']));
    });
    return $out;
}
