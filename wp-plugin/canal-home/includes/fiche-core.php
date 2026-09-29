<?php
/**
 * Ficha 2026 — funciones puras (sin WordPress), testeables con PHP CLI (tests/test-fiche.php).
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_FICHE_VIDEO_HOSTS = ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];
const CANAL_FICHE_SOCIAL = ['facebook' => 'facebook.com', 'instagram' => 'instagram.com', 'youtube' => 'youtube.com'];

// Allowlist de hosts (como SEC-010 en la app local): nunca un iframe con src arbitrario.
function canal_fiche_video_embed(string $url): string
{
    $parts = parse_url(trim($url));
    if (!is_array($parts)) {
        return '';
    }
    $host = strtolower((string) ($parts['host'] ?? ''));
    if (!in_array($host, CANAL_FICHE_VIDEO_HOSTS, true)) {
        return '';
    }
    $path = (string) ($parts['path'] ?? '');
    if (strpos($host, 'vimeo') !== false) {
        return preg_match('#/(\d+)/?$#', $path, $m) ? 'https://player.vimeo.com/video/' . $m[1] : '';
    }
    parse_str((string) ($parts['query'] ?? ''), $query);
    $id = $host === 'youtu.be' ? ltrim($path, '/') : ($query['v'] ?? '');
    if ($id === '' && preg_match('#^/(?:embed|shorts)/([^/]+)#', $path, $m)) {
        $id = $m[1];
    }
    return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? 'https://www.youtube-nocookie.com/embed/' . $id : '';
}

// Primer número del texto (« Tél Atelier : 07 68 13 87 23 » → 0768138723), para el href tel:.
// El separador de dos números (« 04… - 06… ») entra en la clase de caracteres: se corta al primer
// número completo (10 cifras en nacional, prefijo + 9 en internacional). « (0) » se ignora.
function canal_fiche_tel(string $raw): string
{
    if (!preg_match('/\+?\d[\d .\-]{7,}\d/', str_replace('(0)', '', $raw), $m)) {
        return '';
    }
    $num = preg_replace('/[^\d+]/', '', $m[0]);
    $num = $num[0] === '+' ? substr($num, 0, 12) : substr($num, 0, 10);
    return strlen(ltrim($num, '+')) >= 9 ? $num : '';
}

// _facebook (texto) + _links (array de {network, url}) → una URL por red, _facebook primero.
function canal_fiche_social(string $facebook, $links): array
{
    $urls = [$facebook];
    foreach (is_array($links) ? $links : [] as $link) {
        if (is_array($link) && is_string($link['url'] ?? null)) {
            $urls[] = $link['url'];
        }
    }
    $out = [];
    foreach ($urls as $url) {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            continue;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (CANAL_FICHE_SOCIAL as $net => $domain) {
            $match = $host === $domain || substr($host, -strlen($domain) - 1) === '.' . $domain;
            if ($match && !isset($out[$net])) {
                $out[$net] = $url;
            }
        }
    }
    return $out;
}

// Algunas fichas (écluses) guardan « lat, lng » en _job_location en lugar de una dirección.
function canal_fiche_is_coords_text(string $s): bool
{
    return (bool) preg_match('/^\s*-?\d{1,3}\.\d+\s*,\s*-?\d{1,3}\.\d+\s*$/', $s);
}

// 0,0 = geocodificación fallida (golfo de Guinea), no una posición real.
function canal_fiche_has_coords($lat, $lng): bool
{
    return is_numeric($lat) && is_numeric($lng) && (abs((float) $lat) > 0.0001 || abs((float) $lng) > 0.0001);
}

function canal_fiche_distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function canal_fiche_km_label(float $km): string
{
    return $km < 1 ? (int) round($km * 1000) . ' m' : number_format($km, 1, ',', '') . ' km';
}

// Fichas más cercanas a partir de canal_carte_listings() (sin consultas nuevas). Recorrido O(n) sobre ~254.
function canal_fiche_nearby(array $listings, int $selfId, ?float $lat, ?float $lng, int $n = 6): array
{
    if (!canal_fiche_has_coords($lat, $lng)) {
        return [];
    }
    $out = [];
    foreach ($listings as $item) {
        if ((int) ($item['id'] ?? 0) === $selfId || !canal_fiche_has_coords($item['lat'] ?? null, $item['lng'] ?? null)) {
            continue;
        }
        $item['distance_km'] = canal_fiche_distance_km($lat, $lng, (float) $item['lat'], (float) $item['lng']);
        $out[] = $item;
    }
    usort($out, function ($a, $b) { return $a['distance_km'] <=> $b['distance_km']; });
    return array_slice($out, 0, $n);
}

function canal_fiche_excerpt(string $text, int $max = 155): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));
    if (mb_strlen($text, 'UTF-8') <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1, 'UTF-8');
    $space = mb_strrpos($cut, ' ', 0, 'UTF-8');
    return rtrim($space > $max / 2 ? mb_substr($cut, 0, $space, 'UTF-8') : $cut, " ,;:.") . '…';
}

// JSON-LD de la ficha: el lugar + migas (Accueil → Carte → Ficha). Campos vacíos omitidos.
function canal_fiche_seo_graph(array $f, string $url, string $homeUrl, string $carteUrl): array
{
    $hasContact = $f['phone'] !== '' || $f['email'] !== '' || $f['website'] !== '';
    $place = [
        '@type'       => $hasContact ? 'LocalBusiness' : 'TouristAttraction',
        '@id'         => $url . '#place',
        'name'        => $f['title'],
        'url'         => $url,
        'description' => $f['excerpt'],
        'image'       => array_values(array_unique(array_filter(array_merge([$f['cover']], $f['gallery'])))),
        'telephone'   => $f['phone'],
        'email'       => $f['email'],
        'sameAs'      => array_values(array_filter(array_merge([$f['website']], array_values($f['social'])))),
    ];
    if ($f['address'] !== '') {
        $place['address'] = array_filter([
            '@type' => 'PostalAddress', 'streetAddress' => $f['address'], 'addressLocality' => $f['city'],
            'postalCode' => $f['postcode'], 'addressCountry' => 'FR',
        ]);
    }
    if (canal_fiche_has_coords($f['lat'], $f['lng'])) {
        $place['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $f['lat'], 'longitude' => (float) $f['lng']];
    }
    $place = array_filter($place, function ($v) { return $v !== '' && $v !== []; });
    $crumbs = [];
    foreach ([[$homeUrl, 'Accueil'], [$carteUrl, 'Carte interactive'], [$url, $f['title']]] as $i => $c) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[1], 'item' => $c[0]];
    }
    return [
        '@context' => 'https://schema.org',
        '@graph'   => [$place, ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs]],
    ];
}
