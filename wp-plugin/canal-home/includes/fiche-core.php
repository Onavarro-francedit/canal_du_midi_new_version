<?php
/**
 * Ficha 2026 — funciones puras (sin WordPress), testeables con PHP CLI (tests/test-fiche.php).
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_FICHE_VIDEO_HOSTS = ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];
const CANAL_FICHE_UNESCO_URL = 'https://whc.unesco.org/fr/list/770/';
const CANAL_FICHE_VNF_URL = 'https://www.vnf.fr/';
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
// número completo (10 cifras en nacional; en internacional, máximo E.164 = 15 cifras, y un segundo
// « + » ya corta la coincidencia). « (0) » se ignora.
function canal_fiche_tel(string $raw): string
{
    if (!preg_match('/\+?\d[\d .\-]{7,}\d/', str_replace('(0)', '', $raw), $m)) {
        return '';
    }
    $num = preg_replace('/[^\d+]/', '', $m[0]);
    $num = $num[0] === '+' ? substr($num, 0, 16) : substr($num, 0, 10);
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

// Nombres guardados en MAYÚSCULAS (« MAISON RASSIER ») → « Maison Rassier », solo al mostrar.
// Artículos y preposiciones en minúscula salvo al inicio (« Le Relais de Sully », « Saint-Nazaire-d'Aude »).
const CANAL_FICHE_LOWER_WORDS = ['de', 'du', 'des', 'la', 'le', 'les', 'et', 'à', 'au', 'aux', 'en', 'sur', 'd', 'l'];

function canal_fiche_display_title(string $s): string
{
    if (!preg_match('/\p{Lu}/u', $s) || mb_strtoupper($s, 'UTF-8') !== $s) {
        return $s;
    }
    $first = true;
    return (string) preg_replace_callback('/\p{L}+/u', function ($m) use (&$first) {
        $w = mb_strtolower($m[0], 'UTF-8');
        $keepLower = !$first && in_array($w, CANAL_FICHE_LOWER_WORDS, true);
        $first = false;
        return $keepLower ? $w : mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($w, 1, null, 'UTF-8');
    }, $s);
}

// Preguntas frecuentes a partir de datos reales de la ficha (nada inventado): dónde, contacto, alrededores.
// « de » + nombre con vocal inicial → « d'Écluse… » (elisión francesa).
function canal_fiche_de(string $name): string
{
    return (preg_match('/^[aeiouyàâäéèêëîïôöùûüœæh]/iu', $name) ? "d'" : 'de ') . $name;
}

function canal_fiche_faq(array $f): array
{
    $t = $f['title'];
    $faq = [];
    if ($f['address'] !== '' || $f['city'] !== '') {
        $a = $f['address'] !== '' ? "$t se trouve à l'adresse suivante : {$f['address']}." : "$t se trouve à {$f['city']}.";
        if ($f['zones']) {
            $a .= ' Secteur du Canal du Midi : ' . implode(', ', $f['zones']) . '.';
        }
        $faq[] = ['q' => "Où se trouve $t ?", 'a' => $a];
    }
    $ways = [];
    $phone = $f['phone'] !== '' ? $f['phone'] : $f['mobile'];
    if ($phone !== '') {
        $ways[] = "par téléphone au $phone";
    }
    if ($f['email'] !== '') {
        $ways[] = "par e-mail à {$f['email']}";
    }
    if ($f['website'] !== '') {
        $ways[] = 'via son site ' . (parse_url($f['website'], PHP_URL_HOST) ?: $f['website']);
    }
    if ($ways) {
        $last = array_pop($ways);
        $faq[] = ['q' => "Comment contacter $t ?", 'a' => "On peut contacter $t " . ($ways ? implode(', ', $ways) . ' ou ' : '') . "$last."];
    }
    if ($f['nearby']) {
        $names = array_map(function ($n) {
            $detail = array_filter([$n['type'] ?? '', 'à ' . canal_fiche_km_label((float) $n['distance_km'])]);
            return $n['title'] . ' (' . implode(', ', $detail) . ')';
        }, array_slice($f['nearby'], 0, 3));
        $faq[] = ['q' => 'Que trouve-t-on autour ' . canal_fiche_de($t) . ' ?', 'a' => 'À proximité ' . canal_fiche_de($t) . ' : ' . implode(', ', $names) . '.'];
    }
    return $faq;
}

// Tipo schema.org por slug de categoría (job_listing_category de producción, 30/09). El orden del mapa es
// la prioridad (alojamiento > restauración > comercio > visitas): un camping con bar es un Campground.
const CANAL_FICHE_SCHEMA_TYPES = [
    'hotel' => 'Hotel', 'appartement-hotel' => 'Hotel', 'camping' => 'Campground', 'chambre-dhotes' => 'BedAndBreakfast',
    'gites' => 'LodgingBusiness', 'location-saisonniere' => 'LodgingBusiness', 'appartement-maison-a-louer' => 'LodgingBusiness',
    'chambre-a-louer' => 'LodgingBusiness', 'roulotte' => 'LodgingBusiness', 'peniche' => 'LodgingBusiness',
    'auberge-de-jeunesse' => 'Hostel', 'hostel' => 'Hostel', 'auberge-collective' => 'Hostel',
    'restaurant' => 'Restaurant', 'table-dhote' => 'Restaurant', 'bateau-restaurant' => 'Restaurant', 'brasserie-snack' => 'Restaurant',
    'bar' => 'BarOrPub', 'boulangerie-patisserie' => 'Bakery', 'supermarche-epicerie' => 'GroceryStore', 'librairie' => 'BookStore',
    'vente-de-vins' => 'Store', 'produits-regionaux' => 'Store', 'commerce' => 'Store', 'commerce-alimentaire' => 'Store',
    'artisanat' => 'Store', 'boucherie-charcuterie-traiteur' => 'Store',
    'musees' => 'Museum', 'lieux-dinformations' => 'TouristInformationCenter',
    'ecluses' => 'TouristAttraction', 'moulins' => 'TouristAttraction', 'chateaux' => 'TouristAttraction', 'site-et-monument' => 'TouristAttraction',
];

function canal_fiche_schema_type(array $catSlugs, bool $hasContact): string
{
    foreach (CANAL_FICHE_SCHEMA_TYPES as $slug => $type) {
        if (in_array($slug, $catSlugs, true)) {
            return $type;
        }
    }
    return $hasContact ? 'LocalBusiness' : 'TouristAttraction';
}

// Teléfono E.164 para el JSON-LD: 0X XX XX XX XX → +33XXXXXXXXX; los internacionales se dejan igual.
function canal_fiche_tel_intl(string $raw): string
{
    $num = canal_fiche_tel($raw);
    return (strlen($num) === 10 && $num[0] === '0') ? '+33' . substr($num, 1) : $num;
}

// La ficha 2026 es privada; la opción canal_fiche_public = '1' la abre temporalmente (pruebas PageSpeed,
// validadores). Abrir: wp option update canal_fiche_public 1 · cerrar: wp option delete canal_fiche_public.
function canal_fiche_can_view(bool $canReadPrivate, $publicOption): bool
{
    return $canReadPrivate || $publicOption === '1';
}

// Móvil (PageSpeed 30/09): la ficha no tiene pagos (Stripe) y Google Maps se carga en diferido desde
// fiche.js cuando el mapa se acerca a la pantalla (misma URL/clave que el tema). moment, select2 y jquery-ui
// NO se quitan: el frontend.js del tema falla sin ellos y la cabecera (hide-until-load) no aparece.
const CANAL_FICHE_UNUSED_ASSETS = '/^(stripe-js|google-maps|mylisting-maps)$/';

function canal_fiche_is_unused_asset(string $handle): bool
{
    return (bool) preg_match(CANAL_FICHE_UNUSED_ASSETS, $handle);
}

// Hojas propias no críticas (Google Fonts, Bootstrap Icons): se cargan sin bloquear el render.
function canal_fiche_nonblocking_css(string $tag): string
{
    if (strpos($tag, "media='all'") === false) {
        return $tag;
    }
    $async = str_replace("media='all'", "media='print' onload=\"this.media='all'\"", trim($tag));
    return $async . '<noscript>' . trim($tag) . "</noscript>\n";
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

// JSON-LD de la ficha: el lugar, la página (fecha, editor, speakable), migas y FAQ. Campos vacíos omitidos.
function canal_fiche_seo_graph(array $f, string $url, string $homeUrl, string $carteUrl, string $siteName): array
{
    $notEmpty = function ($v) { return $v !== '' && $v !== []; };
    $hasContact = $f['phone'] !== '' || $f['email'] !== '' || $f['website'] !== '';
    // El lugar forma parte del Canal du Midi (mismo @id que en la home), anclado en Wikidata / UNESCO.
    $canal = [
        '@type'  => 'TouristDestination',
        '@id'    => $homeUrl . '#canal-du-midi',
        'name'   => 'Canal du Midi',
        'sameAs' => ['https://fr.wikipedia.org/wiki/Canal_du_Midi', 'https://www.wikidata.org/wiki/Q202494', CANAL_FICHE_UNESCO_URL],
    ];
    $place = [
        '@type'       => canal_fiche_schema_type(array_column($f['categories'], 'slug'), $hasContact),
        '@id'         => $url . '#place',
        'name'        => $f['title'],
        'url'         => $url,
        'description' => $f['excerpt'],
        'image'       => array_values(array_unique(array_filter(array_merge([$f['cover']], $f['gallery'])))),
        'telephone'   => canal_fiche_tel_intl($f['phone'] !== '' ? $f['phone'] : ($f['mobile'] ?? '')),
        'email'       => $f['email'],
        'sameAs'      => array_values(array_filter(array_merge([$f['website']], array_values($f['social'])))),
        'containedInPlace' => $canal,
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
    $page = array_filter([
        '@type'        => 'WebPage',
        '@id'          => $url . '#webpage',
        'url'          => $url,
        'name'         => $f['title'],
        'inLanguage'   => 'fr-FR',
        'isPartOf'     => ['@type' => 'WebSite', '@id' => $homeUrl . '#website', 'name' => $siteName, 'url' => $homeUrl],
        'publisher'    => ['@type' => 'Organization', '@id' => $homeUrl . '#organization', 'name' => $siteName],
        'mainEntity'   => ['@id' => $url . '#place'],
        'about'        => ['@id' => $canal['@id']],
        'breadcrumb'   => ['@id' => $url . '#breadcrumb'],
        'dateModified' => $f['modified'],
        'speakable'    => ['@type' => 'SpeakableSpecification', 'cssSelector' => array_merge(['.service-hero h1', '.description-text'], $f['faq'] ? ['.fiche-faq'] : [])],
    ], $notEmpty);
    $crumbs = [];
    foreach ([[$homeUrl, 'Accueil'], [$carteUrl, 'Carte interactive'], [$url, $f['title']]] as $i => $c) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[1], 'item' => $c[0]];
    }
    $graph = [array_filter($place, $notEmpty), $page, ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $crumbs]];
    if ($f['faq']) {
        $graph[] = ['@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => array_map(function ($qa) {
            return ['@type' => 'Question', 'name' => $qa['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']]];
        }, $f['faq'])];
    }
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}
