# Carte interactive (`/carte/`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Copiar la página del mapa local (`/search`) en el WordPress de producción como página nueva y privada `/carte/`, servida por el plugin `canal-home`.

**Architecture:** Plantilla de página del plugin (mismo patrón que la home). Las 254 fichas `job_listing` se leen una vez en un transient; los filtros GET se aplican en PHP con funciones puras y testeables; el markup, el CSS y el JS son copias de los de `/search` con cambios mínimos marcados con `// WP:`. Google Maps lo carga el tema.

**Tech Stack:** PHP 7.4 (servidor) sin frameworks, WordPress 7.0.6 + tema my-listing, Google Maps JS (del tema) + `@googlemaps/markerclusterer@2.5.3`, JS vanilla, postcss (build local).

**Spec:** `docs/superpowers/specs/2026-09-29-carte-interactive-design.md`

## Global Constraints

- **Producción: no se modifica nada existente, solo se añade.** `/explorer/` (página 10154) no se toca. La página nueva se crea **privada**. Nada de publicar ni cambiar enlaces de la home sin orden explícita.
- PHP del servidor **7.4**: prohibidos `str_contains`, `str_starts_with`, `match`, `?->`, tipos union, `readonly`. Las funciones flecha `fn` sí están permitidas. `remote.sh test` hace lint con 7.4.
- `assets/home.css` y `assets/carte.css` son **generados**: nunca se editan a mano (`node wp-plugin/build/build-css.mjs`).
- Toda salida PHP escapada (`esc_html`, `esc_attr`, `esc_url`). JSON inline con `wp_json_encode(..., JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`.
- Entrada GET solo a través de `canal_carte_params()`. `lat`/`lng` redondeados a 2 decimales (~1 km).
- Textos visibles en francés. Comentarios de código en español, como en el resto del plugin.
- Shell: `head` es la herramienta HEAD de LWP, no coreutils → no usar `| head` (usar `sed -n 1,20p`).
- Tras cualquier cambio visual: verificación en navegador con captura, haciendo scroll antes de la captura de página completa.

## Review Focus

1. **Arranque lento con 254 fichas:** el `skeleton-controler.js` local descarga TODAS las imágenes antes de mostrar la página. Se espera que la página se muestre rápido → solo las 6 primeras cuentan, el resto con `loading="lazy"` (Task 4, paso de `skeleton-controler.js`; se verifica en Task 5 con la pestaña Red).
2. **Página bloqueada en el esqueleto** si Maps falla, o si `wp-fastest-cache` combina o difiere el JS y los eventos `search:*-ready` no llegan. Se espera que el contenido aparezca igualmente → temporizador de 5 s en el script de « reveal » (Task 4; prueba en Task 5 bloqueando `maps.googleapis.com`).
3. **Enlaces con parámetros de `/explorer/`** (`search_keywords`, `category[]`, `search_location`) y `?type=slug` como string: se espera el mismo resultado que con los nombres propios (Task 1 tests + Task 2 smoke con `search_location=Toulouse`).
4. **IA con filtros activos o con límite alcanzado** (429 `rate`/`daily`): se espera volver a la lista completa antes de preguntar (comportamiento de local), y ver el mensaje del endpoint sin perder la lista (Task 4, `ai-search.js`; se verifica en Task 5).
5. **Geolocalización denegada o no disponible:** se espera un aviso en línea, sin `alert`, y el botón usable de nuevo (Task 4, `search-tabs.js`; se verifica en Task 5 denegando el permiso).

---

### Task 1: Filtro puro (`carte-filter.php`) con tests

**Files:**
- Create: `wp-plugin/canal-home/includes/carte-filter.php`
- Create: `wp-plugin/tests/test-carte-filter.php`
- Modify: `wp-plugin/remote.sh` (función `run_test`)

**Interfaces:**
- Consumes: nada (PHP puro).
- Produces:
  - `canal_carte_fold(string $s): string` — minúsculas sin acentos.
  - `canal_carte_params(array $get, array $validCatSlugs): array` → `['q'=>string,'type'=>string[],'location'=>string,'lat'=>?float,'lng'=>?float]`.
  - `canal_carte_filter(array $listings, array $params): array` — cada ficha necesita las claves `title, description, city, address, cat_names[], cat_slugs[], lat, lng`; devuelve las que pasan, con `distance_km` (float) si hay posición y la ficha tiene GPS. Orden: distancia ascendente (sin GPS al final) y luego título sin acentos.
  - `canal_carte_distance(float $lat1, float $lng1, float $lat2, float $lng2): float` — km (haversine).

- [ ] **Step 1: Escribir el test que falla**

`wp-plugin/tests/test-carte-filter.php`:

```php
<?php
// Tests de carte-filter.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-carte-filter.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/carte-filter.php';

$fails = 0;
function check(bool $cond, string $label): void
{
    global $fails;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        $fails++;
        echo "FAIL - $label\n";
    }
}

$L = function (array $o): array {
    return array_merge([
        'id' => 0, 'title' => '', 'description' => '', 'city' => '', 'address' => '',
        'cat_names' => [], 'cat_slugs' => [], 'lat' => null, 'lng' => null,
    ], $o);
};
$listings = [
    $L(['id' => 1, 'title' => 'Écluses de Fonserannes', 'city' => 'Béziers', 'address' => 'Rue du Canal, 34500 Béziers',
        'cat_names' => ['Ecluses'], 'cat_slugs' => ['ecluses', 'nautique', 'activites-loisirs'], 'lat' => 43.3527, 'lng' => 3.1985]),
    $L(['id' => 2, 'title' => 'Hôtel du Capitole', 'city' => 'Toulouse', 'address' => '1 place du Capitole, 31000 Toulouse',
        'description' => 'Chambres face au canal', 'cat_names' => ['Hôtel'], 'cat_slugs' => ['hotel', 'hebergement'], 'lat' => 43.6045, 'lng' => 1.4440]),
    $L(['id' => 3, 'title' => 'Location vélo', 'city' => 'Carcassonne', 'address' => 'Port de Carcassonne',
        'cat_names' => ['Location de vélo'], 'cat_slugs' => ['location-de-velo', 'velo', 'activites-loisirs'], 'lat' => 43.2130, 'lng' => 2.3491]),
    $L(['id' => 4, 'title' => 'Adresse sans GPS', 'city' => 'Agde', 'cat_slugs' => ['bar', 'restauration']]),
];
$valid = ['ecluses', 'nautique', 'activites-loisirs', 'hotel', 'hebergement', 'location-de-velo', 'velo', 'bar', 'restauration'];
$ids = function (array $r): array { return array_column($r, 'id'); };
$f = function (array $get) use ($listings, $valid): array {
    return canal_carte_filter($listings, canal_carte_params($get, $valid));
};

// ── canal_carte_fold ────────────────────────────────────────────────────
check(canal_carte_fold('Écluse À BÉZIERS œuf') === 'ecluse a beziers oeuf', 'fold: minúsculas sin acentos + ligaduras');

// ── canal_carte_params ──────────────────────────────────────────────────
check(canal_carte_params([], $valid) === ['q' => '', 'type' => [], 'location' => '', 'lat' => null, 'lng' => null], 'params: vacío');
$p = canal_carte_params(['search_keywords' => 'vélo', 'category' => ['hotel'], 'search_location' => 'Toulouse'], $valid);
check($p['q'] === 'vélo' && $p['type'] === ['hotel'] && $p['location'] === 'Toulouse', 'params: alias de /explorer/');
check(canal_carte_params(['q' => 'a', 'search_keywords' => 'b'], $valid)['q'] === 'a', 'params: el nombre propio gana al alias');
check(canal_carte_params(['type' => 'hotel'], $valid)['type'] === ['hotel'], 'params: type como string (enlaces ?type=slug)');
check(canal_carte_params(['type' => ['hotel', 'inexistant', ['x'], 'hotel']], $valid)['type'] === ['hotel'], 'params: fuera slugs inválidos, arrays anidados y duplicados');
check(strpbrk(canal_carte_params(['q' => '<script>alert(1)</script>'], $valid)['q'], '<>') === false, 'params: sin < ni >');
check(mb_strlen(canal_carte_params(['q' => str_repeat('é', 300)], $valid)['q'], 'UTF-8') === 100, 'params: q truncada a 100 (multibyte)');
check(canal_carte_params(['q' => "\xC3\x28"], $valid)['q'] === '', 'params: UTF-8 inválido → vacío');
check(canal_carte_params(['q' => ['x']], $valid)['q'] === '', 'params: q array → vacío');
$p = canal_carte_params(['lat' => '43.60456', 'lng' => '1.44401'], $valid);
check($p['lat'] === 43.6 && $p['lng'] === 1.44, 'params: lat/lng redondeados a 2 decimales');
check(canal_carte_params(['lat' => '95', 'lng' => '1'], $valid)['lat'] === null, 'params: lat fuera de rango → ignorada');
check(canal_carte_params(['lat' => '43.6'], $valid)['lat'] === null, 'params: lat sin lng → ignoradas');
check(canal_carte_params(['lat' => 'abc', 'lng' => '1'], $valid)['lng'] === null, 'params: no numérico → ambas ignoradas');

// ── canal_carte_filter ──────────────────────────────────────────────────
check($ids($f([])) === [4, 1, 2, 3], 'filter: sin filtros, orden alfabético sin acentos');
check($ids($f(['q' => 'ecluse'])) === [1], 'filter: « ecluse » encuentra « Écluses »');
check($ids($f(['q' => 'HÔTEL canal'])) === [2], 'filter: varias palabras, todas deben aparecer (título + descripción)');
check($ids($f(['q' => 'hôtel à Toulouse'])) === [2], 'filter: palabras de menos de 3 letras ignoradas');
check($ids($f(['q' => 'béziers'])) === [1], 'filter: q busca en la commune');
check($ids($f(['q' => 'ecluses fonserannes zzzz'])) === [], 'filter: una palabra que falta → fuera');
check($ids($f(['type' => ['activites-loisirs']])) === [1, 3], 'filter: categoría padre incluye descendientes');
check($ids($f(['type' => ['hotel', 'bar']])) === [4, 2], 'filter: varias categorías = O');
check($ids($f(['search_location' => 'toulouse'])) === [2], 'filter: location (alias) en commune');
check($ids($f(['location' => '31000'])) === [2], 'filter: location en dirección');
$r = $f(['lat' => '43.60', 'lng' => '1.44']);
check($ids($r) === [2, 3, 1, 4], 'filter: con posición, orden por distancia y sin GPS al final');
check($r[0]['distance_km'] < 1, 'filter: distancia al hotel < 1 km');
check($r[1]['distance_km'] > 80 && $r[1]['distance_km'] < 90, 'filter: Toulouse → Carcassonne ≈ 85 km');
check(!isset($r[3]['distance_km']), 'filter: sin GPS → sin distance_km');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
```

- [ ] **Step 2: Ejecutar y ver que falla**

Run: `/Applications/XAMPP/xamppfiles/bin/php wp-plugin/tests/test-carte-filter.php`
Expected: `Failed opening required '.../includes/carte-filter.php'` (fatal).

- [ ] **Step 3: Implementar `carte-filter.php`**

```php
<?php
/**
 * Filtro de la carte — funciones puras (sin WordPress), testeables con PHP CLI.
 * Entrada: $_GET sin barras (wp_unslash). Acepta los parámetros de /explorer/ como alias.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_CARTE_MAX_TEXT = 100;

function canal_carte_fold(string $s): string
{
    return strtr(mb_strtolower($s, 'UTF-8'), [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae', '’' => "'",
    ]);
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
```

- [ ] **Step 4: Ejecutar y ver que pasa**

Run: `/Applications/XAMPP/xamppfiles/bin/php wp-plugin/tests/test-carte-filter.php`
Expected: todas las líneas `ok`, `TODO OK`, exit 0.

- [ ] **Step 5: Añadir el test a `remote.sh test` y ejecutarlo en el servidor (PHP 7.4)**

En `wp-plugin/remote.sh`, función `run_test`, después de la línea `$PHP74 $TMP/tests/test-ai-core.php"`, cambiar el final del bloque para que quede:

```bash
        $PHP74 $TMP/tests/test-ai-core.php
        $PHP74 $TMP/tests/test-carte-filter.php"
```

Run: `wp-plugin/remote.sh test`
Expected: `No syntax errors detected` para cada archivo, ambos tests con `TODO OK` (o la salida final de test-ai-core) y exit 0.

- [ ] **Step 6: Commit**

```bash
git add wp-plugin/canal-home/includes/carte-filter.php wp-plugin/tests/test-carte-filter.php wp-plugin/remote.sh
git commit -m "feat(wp-carte): filtro puro de la carte (q, type, location, distancia) con tests"
```

---

### Task 2: Datos de WordPress y caché (`carte-data.php`) + smoke

**Files:**
- Create: `wp-plugin/canal-home/includes/carte-data.php`
- Create: `wp-plugin/tests/smoke-carte-data.php`
- Modify: `wp-plugin/canal-home/canal-home.php` (bloque de `require_once`, líneas 16-22)

**Interfaces:**
- Consumes: `canal_home_plain()`, `canal_home_cover()`, `canal_home_city()`, `canal_home_category_label()` de `includes/data.php` (ya existen); `canal_carte_filter`, `canal_carte_params` (Task 1).
- Produces:
  - `const CANAL_CARTE_CACHE = 'canal_carte_listings'`
  - `canal_carte_listings(): array` — fichas con claves `id:int, slug, title, url, lat:?float, lng:?float, image, gallery:string[] (máx. 8), address, description (200 car.), type (nombre de categoría), cat_names:string[], cat_slugs:string[] (propias + ancestros), city, phone, email`.
  - `canal_carte_categories(array $listings): array` — `[['id'=>int,'slug','name','parent_id'=>int,'offers_count'=>int], …]`, solo categorías con fichas, por nombre.
  - `canal_carte_public(array $item): array` — claves del JSON de local: `id, slug, lat, lng, title, image, gallery, address, description, type, label, phone, email, url, distance_km`.
  - `canal_carte_flush(): void`

- [ ] **Step 1: Escribir el smoke que falla**

`wp-plugin/tests/smoke-carte-data.php`:

```php
<?php
// Smoke de carte-data.php con WordPress cargado (lectura + transient propio).
// Se ejecuta DESPUÉS de desplegar: prueba el código desplegado (el plugin activo ya lo ha cargado).
// Uso: wp-plugin/remote.sh run tests/smoke-carte-data.php
$src = $args[0] ?? '';
if (!function_exists('canal_carte_listings')) {
    require_once $src . '/canal-home/includes/data.php';
    require_once $src . '/canal-home/includes/carte-filter.php';
    require_once $src . '/canal-home/includes/carte-data.php';
}

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};

canal_carte_flush();
$published = (int) wp_count_posts('job_listing')->publish;
$listings = canal_carte_listings();
$check(count($listings) === $published, "listings: todas las publicadas ($published)");
$check(is_array(get_transient(CANAL_CARTE_CACHE)), 'cache: transient creado');
$check(count(array_filter($listings, fn($l) => strpos($l['url'], '/fiche/') === false)) === 0, 'listings: todas con url /fiche/');
$check(count(array_filter($listings, fn($l) => !is_float($l['lat']) || !is_float($l['lng']))) === 0, 'listings: todas con lat/lng float');
$check(count(array_filter($listings, fn($l) => $l['image'] !== '')) > 200, 'listings: >200 con imagen de portada');
$check(count(array_filter($listings, fn($l) => strpos($l['title'] . $l['description'], '&#') !== false)) === 0, 'listings: sin entidades HTML');
$check(count(array_filter($listings, fn($l) => count($l['gallery']) > 8)) === 0, 'listings: galería limitada a 8');

$cats = canal_carte_categories($listings);
$bySlug = array_column($cats, null, 'slug');
$check(count(array_filter($cats, fn($c) => $c['parent_id'] === 0)) >= 3, 'categories: al menos 3 raíces con fichas');
$check(($bySlug['hebergement']['offers_count'] ?? 0) > 50, 'categories: « hebergement » cuenta sus descendientes (' . ($bySlug['hebergement']['offers_count'] ?? 0) . ')');
$check(count(array_filter($cats, fn($c) => $c['offers_count'] === 0)) === 0, 'categories: ninguna vacía');

$slugs = array_column($cats, 'slug');
$toulouse = canal_carte_filter($listings, canal_carte_params(['search_location' => 'Toulouse'], $slugs));
$check(count($toulouse) >= 10, 'filtro real: search_location=Toulouse (' . count($toulouse) . ')');
$ecluses = canal_carte_filter($listings, canal_carte_params(['type' => 'ecluses'], $slugs));
$check(count($ecluses) >= 50, 'filtro real: ?type=ecluses (' . count($ecluses) . ')');

$public = canal_carte_public($listings[0]);
$expected = ['id', 'slug', 'lat', 'lng', 'title', 'image', 'gallery', 'address', 'description', 'type', 'label', 'phone', 'email', 'url', 'distance_km'];
$check(array_keys($public) === $expected, 'public: claves del JSON de local');

$check(has_action('save_post_job_listing', 'canal_carte_flush') !== false, 'invalidación: save_post_job_listing');
$check(has_action('edited_job_listing_category', 'canal_carte_flush') !== false, 'invalidación: edited_job_listing_category');
canal_carte_flush();
$check(get_transient(CANAL_CARTE_CACHE) === false, 'invalidación: flush borra el transient');
canal_carte_listings(); // se deja la caché caliente

WP_CLI::log($fails ? "$fails FALLO(S)" : 'TODO OK');
if ($fails) {
    exit(1);
}
```

- [ ] **Step 2: Ejecutarlo y ver que falla**

Run: `wp-plugin/remote.sh run tests/smoke-carte-data.php`
Expected: fatal `Failed opening required '.../includes/carte-data.php'`.

- [ ] **Step 3: Implementar `carte-data.php`**

```php
<?php
/**
 * Datos de la carte (solo lectura): todas las fichas publicadas en un transient de 12 h.
 * Reutiliza los helpers de data.php (portada, commune, categoría principal).
 */
defined('ABSPATH') || exit;

const CANAL_CARTE_CACHE = 'canal_carte_listings';
const CANAL_CARTE_GALLERY_MAX = 8;

function canal_carte_listings(): array
{
    $cached = get_transient(CANAL_CARTE_CACHE);
    if (is_array($cached)) {
        return $cached;
    }
    // get_posts precarga metas y términos de todas las fichas (sin N+1).
    $posts = get_posts([
        'post_type'   => 'job_listing',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);
    $items = [];
    foreach ($posts as $post) {
        $terms = get_the_terms($post->ID, 'job_listing_category');
        $terms = ($terms && !is_wp_error($terms)) ? $terms : [];
        $slugs = [];
        foreach ($terms as $term) {
            $slugs[] = $term->slug;
            foreach (get_ancestors($term->term_id, 'job_listing_category', 'taxonomy') as $ancestorId) {
                $ancestor = get_term($ancestorId, 'job_listing_category');
                if ($ancestor instanceof WP_Term) {
                    $slugs[] = $ancestor->slug;
                }
            }
        }
        $lat = get_post_meta($post->ID, 'geolocation_lat', true);
        $lng = get_post_meta($post->ID, 'geolocation_long', true);
        $gallery = get_post_meta($post->ID, '_job_gallery', true);
        $desc = (string) get_post_meta($post->ID, '_job_description', true);
        if ($desc === '') {
            $desc = $post->post_content;
        }
        $items[] = [
            'id'          => (int) $post->ID,
            'slug'        => $post->post_name,
            'title'       => canal_home_plain(get_the_title($post)),
            'url'         => (string) get_permalink($post),
            'lat'         => is_numeric($lat) ? (float) $lat : null,
            'lng'         => is_numeric($lng) ? (float) $lng : null,
            'image'       => canal_home_cover($post->ID),
            'gallery'     => is_array($gallery) ? array_slice(array_values(array_filter($gallery, 'is_string')), 0, CANAL_CARTE_GALLERY_MAX) : [],
            'address'     => canal_home_plain((string) get_post_meta($post->ID, '_job_location', true)),
            'description' => mb_substr(trim((string) preg_replace('/\s+/u', ' ', canal_home_plain(wp_strip_all_tags($desc)))), 0, 200, 'UTF-8'),
            'type'        => canal_home_category_label($post->ID),
            'cat_names'   => array_map('canal_home_plain', wp_list_pluck($terms, 'name')),
            'cat_slugs'   => array_values(array_unique($slugs)),
            'city'        => canal_home_city($post->ID),
            'phone'       => trim((string) get_post_meta($post->ID, '_job_phone', true)),
            'email'       => sanitize_email((string) get_post_meta($post->ID, '_job_email', true)),
        ];
    }
    set_transient(CANAL_CARTE_CACHE, $items, 12 * HOUR_IN_SECONDS);
    return $items;
}

// Categorías con al menos una ficha; offers_count incluye las fichas de las subcategorías.
function canal_carte_categories(array $listings): array
{
    $terms = get_terms(['taxonomy' => 'job_listing_category', 'hide_empty' => false, 'orderby' => 'name']);
    if (is_wp_error($terms)) {
        return [];
    }
    $counts = [];
    foreach ($listings as $item) {
        foreach ($item['cat_slugs'] as $slug) {
            $counts[$slug] = ($counts[$slug] ?? 0) + 1;
        }
    }
    $out = [];
    foreach ($terms as $term) {
        if (empty($counts[$term->slug])) {
            continue;
        }
        $out[] = [
            'id'           => (int) $term->term_id,
            'slug'         => $term->slug,
            'name'         => canal_home_plain($term->name),
            'parent_id'    => (int) $term->parent,
            'offers_count' => $counts[$term->slug],
        ];
    }
    return $out;
}

// Mismas claves que searchResults en search_results.php (local): search-map.js no cambia.
function canal_carte_public(array $item): array
{
    return [
        'id'          => $item['id'],
        'slug'        => $item['slug'],
        'lat'         => $item['lat'],
        'lng'         => $item['lng'],
        'title'       => $item['title'],
        'image'       => $item['image'],
        'gallery'     => $item['gallery'],
        'address'     => $item['address'],
        'description' => $item['description'],
        'type'        => $item['type'],
        'label'       => '',
        'phone'       => $item['phone'],
        'email'       => $item['email'],
        'url'         => $item['url'],
        'distance_km' => $item['distance_km'] ?? null,
    ];
}

function canal_carte_flush(): void
{
    delete_transient(CANAL_CARTE_CACHE);
}

// Solo borran nuestro transient: no tocan nada existente.
add_action('save_post_job_listing', 'canal_carte_flush');
add_action('edited_job_listing_category', 'canal_carte_flush');
add_action('deleted_post', function ($postId, $post = null) {
    if ($post instanceof WP_Post && $post->post_type === 'job_listing') {
        canal_carte_flush();
    }
}, 10, 2);
```

- [ ] **Step 4: Cargarlo desde el plugin**

En `wp-plugin/canal-home/canal-home.php`, después de `require_once CANAL_HOME_DIR . 'includes/seo.php';`:

```php
require_once CANAL_HOME_DIR . 'includes/carte-filter.php';
require_once CANAL_HOME_DIR . 'includes/carte-data.php';
```

- [ ] **Step 5: Desplegar y ejecutar el smoke**

Desplegar es seguro: solo se añaden funciones y hooks que borran un transient propio; ninguna página usa todavía la carte.

Run: `wp-plugin/remote.sh deploy && wp-plugin/remote.sh run tests/smoke-carte-data.php`
Expected: tests + lint OK; smoke con todas las líneas `ok` y `TODO OK`. Si un umbral falla con datos reales (p. ej. imágenes ≤ 200), comprobar el dato con `remote.sh wp eval` antes de tocar el umbral y anotar el motivo.

- [ ] **Step 6: Commit**

```bash
git add wp-plugin/canal-home/includes/carte-data.php wp-plugin/canal-home/canal-home.php wp-plugin/tests/smoke-carte-data.php
git commit -m "feat(wp-carte): datos de las fichas en transient con invalidación + smoke"
```

---

### Task 3: CSS de la carte (build generalizado)

**Files:**
- Modify: `wp-plugin/build/build-css.mjs` (reescritura completa, abajo)
- Create: `wp-plugin/build/carte-extra.css`
- Create (generado): `wp-plugin/canal-home/assets/carte.css`

**Interfaces:**
- Consumes: `public/assets/css/styles.css`, `public/assets/css/search.css`, `wp-plugin/build/home-extra.css`.
- Produces: `assets/carte.css` con todas las reglas bajo `.cdm-carte`, alto de cabecera en `var(--cdm-header-h, 82px)` (la variable la fija el JS de Task 4). `assets/home.css` **idéntico** al actual.

- [ ] **Step 1: Crear `wp-plugin/build/carte-extra.css`**

```css
/* Ajustes propios de la carte WordPress. Fuente: se prefija con .cdm-carte en el build. */

/* Compatibilidad con el tema my-listing (Bootstrap) — mismos ajustes que la home. */
.container::before, .container::after { content: none; display: none; }
h1, h2, h3, h4, h5, h6 { color: inherit; }
p { color: inherit; }

/* WP: « Autour de moi » (no existe en local). */
.search-nearby-button { width: 100%; justify-content: center; margin-top: 12px; }
.search-nearby-status { margin: 8px 0 0; font-size: 13px; line-height: 1.4; color: #64748b; }
.search-nearby-status:empty { display: none; }

/* WP: distancia en la tarjeta cuando hay posición. */
.card-distance { margin-left: 4px; font-weight: 600; white-space: nowrap; }

/* WP: mapa sin Google Maps. */
.map-unavailable { display: flex; align-items: center; justify-content: center; height: 100%; min-height: 240px; color: #64748b; font-weight: 600; }
```

- [ ] **Step 2: Reescribir `wp-plugin/build/build-css.mjs`**

```js
// Genera canal-home/assets/home.css (.cdm-home) y canal-home/assets/carte.css (.cdm-carte)
// a partir del CSS de la app local. Uso: node wp-plugin/build/build-css.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import postcss from 'postcss';
import prefixer from 'postcss-prefix-selector';

const read = (rel) => readFileSync(new URL(rel, import.meta.url), 'utf8');

// El tema fija html{font-size:10px}; la app local se diseñó con 16px → rem a px fijos.
const remToPx = {
    postcssPlugin: 'rem-to-px',
    Declaration(decl) {
        decl.value = decl.value.replace(/(\d*\.?\d+)rem\b/g, (_, n) => `${+(parseFloat(n) * 16).toFixed(2)}px`);
    },
};

// search.css asume una cabecera de 82px (y 82+61 = 143px); en WP la mide el JS (--cdm-header-h).
const headerHeight = {
    postcssPlugin: 'header-height',
    Declaration(decl) {
        decl.value = decl.value
            .replace(/\b143px\b/g, 'calc(var(--cdm-header-h, 82px) + 61px)')
            .replace(/\b82px\b/g, 'var(--cdm-header-h, 82px)');
    },
};

async function build({ prefix, sources, out, plugins = [], needles }) {
    const source = sources.map(read).join('\n');
    const result = await postcss([
        prefixer({
            prefix,
            transform(p, selector, prefixed) {
                if (selector === ':root' || selector === 'html' || selector === 'body') return p;
                if (selector.startsWith(prefix)) return selector;
                return prefixed;
            },
        }),
        ...plugins,
        remToPx,
    ]).process(source, { from: undefined });

    // Verificación: ninguna regla fuera de @keyframes puede escapar del contenedor.
    const leaks = [];
    postcss.parse(result.css).walkRules((rule) => {
        if (rule.parent?.type === 'atrule' && /keyframes$/.test(rule.parent.name)) return;
        for (const sel of rule.selectors) {
            if (!sel.startsWith(prefix)) leaks.push(sel);
        }
    });
    if (leaks.length) {
        console.error(`${out}: selectores sin prefijo:`, leaks);
        process.exit(1);
    }

    // Verificación: el tema fija html{font-size:10px} → ningún valor puede depender de rem.
    const remDecls = [];
    postcss.parse(result.css).walkDecls((decl) => {
        if (/\d(\.\d+)?rem\b/.test(decl.value)) remDecls.push(`${decl.parent.selector} { ${decl.prop}: ${decl.value} }`);
    });
    if (remDecls.length) {
        console.error(`${out}: valores en rem:`, remDecls.slice(0, 5), `(${remDecls.length})`);
        process.exit(1);
    }

    // Verificación: ajustes de compatibilidad con el tema presentes.
    for (const needle of needles) {
        if (!result.css.includes(needle)) {
            console.error(`${out}: falta el ajuste de compatibilidad con el tema:`, needle);
            process.exit(1);
        }
    }

    writeFileSync(
        new URL(`../canal-home/assets/${out}`, import.meta.url),
        '/* GENERADO por wp-plugin/build/build-css.mjs — no editar a mano */\n' + result.css,
    );
    console.log(`${out} OK`);
}

await build({
    prefix: '.cdm-home',
    sources: ['../../public/assets/css/styles.css', './home-extra.css'],
    out: 'home.css',
    needles: ['.cdm-home .container::before', '.cdm-home h1', '.cdm-home p', '.cdm-home:not(.js-reveal) [data-reveal]'],
});

await build({
    prefix: '.cdm-carte',
    sources: ['../../public/assets/css/styles.css', '../../public/assets/css/search.css', './carte-extra.css'],
    out: 'carte.css',
    plugins: [headerHeight],
    needles: ['.cdm-carte .container::before', '.cdm-carte h1', '.cdm-carte p', 'var(--cdm-header-h, 82px)', '.cdm-carte .search-workspace'],
});
```

Nota: `headerHeight` va antes de `remToPx` para no convertir en variable un `82px` que venga de `5.125rem`.

- [ ] **Step 3: Ejecutar el build y verificar**

Run: `node wp-plugin/build/build-css.mjs && git diff --stat -- wp-plugin/canal-home/assets/home.css && grep -c "cdm-header-h" wp-plugin/canal-home/assets/carte.css`
Expected: `home.css OK`, `carte.css OK`; **ningún cambio** en `home.css` (diff vacío); un recuento de `cdm-header-h` ≥ 14 (las 14 apariciones de 82px/143px en `search.css`).

- [ ] **Step 4: Commit**

```bash
git add wp-plugin/build/build-css.mjs wp-plugin/build/carte-extra.css wp-plugin/canal-home/assets/carte.css
git commit -m "feat(wp-carte): carte.css generado desde styles.css + search.css bajo .cdm-carte"
```

---

### Task 4: Plantilla, registro y JS de la carte

**Files:**
- Create: `wp-plugin/canal-home/template-carte.php` (copia adaptada de `src/Infrastructure/Views/search_results.php`)
- Create: `wp-plugin/canal-home/assets/carte/search-map.js`, `search-tabs.js`, `ai-search.js`, `skeleton-controler.js` (copias adaptadas de `public/assets/js/`)
- Modify: `wp-plugin/canal-home/canal-home.php`
- Modify: `wp-plugin/canal-home/includes/data.php:133-143` (`canal_home_card` añade `slug`)

**Interfaces:**
- Consumes: `canal_carte_listings`, `canal_carte_categories`, `canal_carte_public` (Task 2); `canal_carte_params`, `canal_carte_filter` (Task 1); `assets/carte.css` (Task 3); endpoint `POST /wp-json/canal-home/v1/ai` `{prompt}` → `200 {results:[{title,url,image,category,city,slug,reason}]}` o `4xx/5xx {error,message,results:[]}`.
- Produces: plantilla `canal-home/template-carte.php` (nombre visible « Carte interactive »); objeto JS `CDM_CARTE = {aiUrl, pageUrl}`; `canal_carte_is_page(): bool`.

- [ ] **Step 1: `slug` en las tarjetas de la IA**

En `includes/data.php`, dentro de `canal_home_card()`, añadir tras `'title' => …,`:

```php
        'slug'     => $post->post_name,
```

- [ ] **Step 2: Registrar la plantilla y los assets en `canal-home.php`**

a) Tras `define('CANAL_HOME_TEMPLATE', 'canal-home/template-home.php');`:

```php
define('CANAL_CARTE_TEMPLATE', 'canal-home/template-carte.php');
const CANAL_HOME_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,700&family=Sora:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap';
```

b) En el filtro `theme_page_templates`, tras la línea de la home:

```php
    $templates[CANAL_CARTE_TEMPLATE] = 'Carte interactive';
```

c) Tras `canal_home_is_page()`:

```php
function canal_carte_is_page(): bool
{
    return is_page() && get_page_template_slug(get_queried_object_id()) === CANAL_CARTE_TEMPLATE;
}
```

d) Sustituir el filtro `template_include` por:

```php
add_filter('template_include', function ($template) {
    if (canal_home_is_page()) {
        return CANAL_HOME_DIR . 'template-home.php';
    }
    return canal_carte_is_page() ? CANAL_HOME_DIR . 'template-carte.php' : $template;
});
```

e) En el `wp_enqueue_scripts` de la home, sustituir la URL literal de las fuentes por `CANAL_HOME_FONTS_URL` y, a continuación de ese `add_action(...)`, añadir:

```php
// Carte: Google Maps lo carga ya el tema en todas las páginas (footer, síncrono): no se carga otra vez.
// Nuestros scripts van en el footer y search-map.js arranca en DOMContentLoaded, cuando Maps ya existe.
add_action('wp_enqueue_scripts', function () {
    if (!canal_carte_is_page()) {
        return;
    }
    $ver = function (string $rel): string { return (string) filemtime(CANAL_HOME_DIR . $rel); };
    wp_enqueue_style('canal-home-fonts', CANAL_HOME_FONTS_URL, [], null);
    wp_enqueue_style('canal-home-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css', [], '1.11.1');
    wp_enqueue_style('canal-carte', CANAL_HOME_URL . 'assets/carte.css', [], $ver('assets/carte.css'));
    // body.page-template-canal-home también se aplica a esta plantilla (misma carpeta).
    wp_enqueue_style('canal-home-header', CANAL_HOME_URL . 'assets/header.css', [], $ver('assets/header.css'));
    wp_enqueue_script('canal-carte-clusterer', 'https://unpkg.com/@googlemaps/markerclusterer@2.5.3/dist/index.min.js', [], '2.5.3', true);
    foreach (['search-map', 'search-tabs', 'ai-search', 'skeleton-controler'] as $name) {
        wp_enqueue_script("canal-carte-$name", CANAL_HOME_URL . "assets/carte/$name.js", ['canal-carte-clusterer'], $ver("assets/carte/$name.js"), true);
    }
    wp_localize_script('canal-carte-ai-search', 'CDM_CARTE', [
        'aiUrl'   => rest_url('canal-home/v1/ai'),
        'pageUrl' => get_permalink(),
    ]);
}, 20);
```

- [ ] **Step 3: Crear `template-carte.php` copiando la vista local**

Run: `cp src/Infrastructure/Views/search_results.php wp-plugin/canal-home/template-carte.php`

Después aplicar exactamente estos cambios (números de línea del original):

**3a. Líneas 1-49 (cabecera PHP) → sustituir por:**

```php
<?php
/**
 * Plantilla « Carte interactive » — copia de src/Infrastructure/Views/search_results.php (app local).
 * Cambios respecto al original marcados con « WP: ». PHP 7.4: nada de str_contains/match.
 */
defined('ABSPATH') || exit;

// WP: datos de WordPress en lugar de los repositorios de la app local.
$resetUrl      = (string) get_permalink();
$listings      = canal_carte_listings();
$categories    = canal_carte_categories($listings);
$params        = canal_carte_params(wp_unslash($_GET), array_column($categories, 'slug'));
$results       = canal_carte_filter($listings, $params);
$query         = $params['q'];
$city          = $params['location'];
$selectedTypes = $params['type'];

$resultsCount = count($results);
$categoryOptionCount = count(array_filter($categories, fn($cat) => trim((string)($cat['slug'] ?? '')) !== ''));
$selectedTypeLabels = [];
foreach ($selectedTypes as $st) {
    foreach ($categories as $cat) {
        $slug = trim((string)($cat['slug'] ?? ''));
        if ($slug !== '' && $slug === $st) {
            $selectedTypeLabels[$st] = (string)($cat['name'] ?? ucfirst($slug));
            break;
        }
    }
}
$selectedTypeDisplayText = count($selectedTypes) === 0
    ? 'Tous les types'
    : (count($selectedTypes) === 1
        ? (reset($selectedTypeLabels) ?: $selectedTypes[0])
        : count($selectedTypes) . ' type(s) sélectionné(s)');
$activeFilters = array_filter(array_merge(
    [$query !== '' ? $query : null, $city !== '' ? $city : null, $params['lat'] !== null ? 'Autour de moi' : null],
    array_values($selectedTypeLabels)
));

$rootCategories = [];
$childCategoriesByParent = [];
foreach ($categories as $cat) {
    $parentId = (int)($cat['parent_id'] ?? 0);
    if ($parentId > 0) {
        $childCategoriesByParent[$parentId][] = $cat;
        continue;
    }
    $rootCategories[] = $cat;
}

get_header();
?>
<div class="cdm-carte">
```

**3b. Línea 124 (texto del tooltip) → sustituir por:**

```php
                                    Recherchez par nom d'établissement, type de service ou commune (ex : « vélo Carcassonne », « chambre d'hôtes Homps »). Essayez différents mots-clés pour affiner vos résultats !
```

**3c. Entre la línea 207 (`</div>` que cierra `filter-block--type-modern`) y la 208 (`<br>`) → insertar:**

```php
                    <?php // WP: se conservan lugar y posición al reenviar; lat/lng desactivados si están vacíos (no van en la URL). ?>
                    <?php if ($city !== ''): ?>
                        <input type="hidden" name="location" value="<?= esc_attr($city) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="lat" value="<?= esc_attr((string) $params['lat']) ?>"<?= $params['lat'] === null ? ' disabled' : '' ?>>
                    <input type="hidden" name="lng" value="<?= esc_attr((string) $params['lng']) ?>"<?= $params['lng'] === null ? ' disabled' : '' ?>>
                    <button type="button" class="button button-small search-nearby-button" id="search-nearby-button">
                        <i class="bi bi-crosshair"></i> Autour de moi
                    </button>
                    <p class="search-nearby-status" id="search-nearby-status" role="status" aria-live="polite"></p>
```

**3d. Línea 226 → `use ($lang, $selectedTypes)` pasa a `use ($resetUrl, $selectedTypes)`.**

**3e. Líneas 254 y 258 (`str_contains`, PHP 8) → sustituir por:**

```php
                                if ($categorySlugRaw !== '' && strpos($categorySlugRaw, $needle) !== false) {
```
```php
                                if ($categoryNameRaw !== '' && strpos(mb_strtolower($categoryNameRaw), $needle) !== false) {
```

**3f. Línea 277 → sustituir el `href` por:**

```php
                            return '<a href="' . esc_url(add_query_arg('type', $categorySlugRaw, $resetUrl)) . '"'
```

**3g. Líneas 316-327 (botones de estrategia) → sustituir por** (sugerencias del canal; `ai-search.js` envía `data-ai-prompt`):

```php
                            <button type="button" class="ai-prompt-button" data-ai-prompt="Une balade à vélo en famille le long du canal">
                                <i class="bi bi-bicycle"></i>
                                Une balade à vélo en famille
                            </button>
                            <button type="button" class="ai-prompt-button" data-ai-prompt="Dormir au bord du canal dans un lieu de charme">
                                <i class="bi bi-house-door"></i>
                                Dormir au bord du canal
                            </button>
                            <button type="button" class="ai-prompt-button" data-ai-prompt="Déguster les vins du Minervois chez un vigneron">
                                <i class="bi bi-cup-hot"></i>
                                Déguster les vins du Minervois
                            </button>
```

**3h. Línea 344 → placeholder:** `placeholder="Exemple : une balade en bateau sans permis au départ de Castelnaudary, un restaurant au bord de l'eau…"`

**3i. Líneas 402-465 (bucle de tarjetas) → sustituir por:**

```php
                    <?php foreach ($results as $s): ?>
                        <?php
                        // WP: $s es un array de canal_carte_listings() (antes, un objeto Service).
                        $serviceTitle = $s['title'] !== '' ? $s['title'] : 'Adresse Canal du Midi';
                        $serviceDesc  = mb_substr($s['description'], 0, 120, 'UTF-8');
                        $serviceImage = $s['image'];
                        $ficheUrl     = $s['url'];
                        ?>
                        <article
                            class="explore-card"
                            data-id="<?= (int) $s['id'] ?>"
                            data-lat="<?= esc_attr((string) $s['lat']) ?>"
                            data-lng="<?= esc_attr((string) $s['lng']) ?>"
                            onmouseenter="window.highlightMarker && window.highlightMarker(<?= (int) $s['id'] ?>)"
                            onmouseleave="window.resetMarker && window.resetMarker(<?= (int) $s['id'] ?>)"
                        >
                            <a class="explore-card-link" href="<?= esc_url($ficheUrl) ?>">
                                <div class="card-image<?= $serviceImage ? '' : ' card-image--placeholder' ?>">
                                    <?php if ($serviceImage): ?>
                                        <img
                                            data-src="<?= esc_url($serviceImage) ?>"
                                            alt="<?= esc_attr($serviceTitle) ?>"
                                            width="400"
                                            height="260"
                                            decoding="async"
                                        >
                                    <?php else: ?>
                                        <div class="card-image-icon"><i class="bi bi-building"></i></div>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="card-body">
                                <h3 class="card-title"><?= esc_html($serviceTitle) ?></h3>
                                <div class="card-location">
                                    <i class="bi bi-geo-alt"></i>
                                    <span><?= esc_html($s['address']) ?></span>
                                    <?php if (isset($s['distance_km'])): ?>
                                        <span class="card-distance">· à <?= esc_html(number_format($s['distance_km'], $s['distance_km'] < 10 ? 1 : 0, ',', ' ')) ?> km</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($serviceDesc !== ''): ?>
                                    <p class="card-tagline"><?= esc_html($serviceDesc) ?>…</p>
                                <?php endif; ?>
                            </div>

                            <div class="<?= $s['phone'] === '' ? 'card-footer-row--right-aligned' : 'card-footer-row' ?>">
                                <?php if ($s['phone'] !== ''): ?>
                                    <span class="card-phone">
                                        <i class="bi bi-telephone"></i>
                                        <?= esc_html($s['phone']) ?>
                                    </span>
                                <?php endif; ?>
                                <a href="<?= esc_url($ficheUrl) ?>" class="card-detail-trigger">
                                    <span>Voir la fiche</span>
                                    <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
```

**3j. Líneas 537-588 (los dos `<script>`) → sustituir por:**

```php
<!-- Pasamos los datos a JS de forma segura -->
<script>
    const searchResults = <?= wp_json_encode(array_map('canal_carte_public', $results), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.searchResults = searchResults;
</script>
<script>
    (function () {
        const page = document.getElementById('search-page');
        if (!page) return;

        // WP: alto real de la cabecera fija del tema (search.css asumía 82px).
        const header = document.querySelector('.c27-main-header');
        const shell = page.closest('.cdm-carte');
        const setHeaderHeight = () => {
            if (header && shell) shell.style.setProperty('--cdm-header-h', header.offsetHeight + 'px');
        };
        setHeaderHeight();
        window.addEventListener('resize', setHeaderHeight);

        document.body.classList.add('search-page-loading');

        const state = {
            mapReady: false,
            imagesReady: <?= $resultsCount === 0 ? 'true' : 'false' ?>,
        };

        const revealIfReady = () => {
            if (!state.mapReady || !state.imagesReady) {
                return;
            }

            page.classList.remove('is-loading');
            page.classList.add('is-ready');
            document.body.classList.remove('search-page-loading');
        };

        window.addEventListener('search:map-ready', () => {
            state.mapReady = true;
            revealIfReady();
        }, { once: true });

        window.addEventListener('search:images-ready', () => {
            state.imagesReady = true;
            revealIfReady();
        }, { once: true });

        // WP: si Maps o las imágenes no avisan (JS combinado por la caché, red lenta), se muestra igual a los 5 s.
        window.setTimeout(() => {
            state.mapReady = true;
            state.imagesReady = true;
            revealIfReady();
        }, 5000);
    })();
</script>
</div>
<?php
get_footer();
```

Comprobación rápida de que no queda nada de la app local:
Run: `grep -n "BASE_URL\|\$lang\|str_contains\|htmlspecialchars(\$s->\|->translations" wp-plugin/canal-home/template-carte.php`
Expected: sin resultados (los `htmlspecialchars` restantes sobre variables propias ya escapadas son válidos).

- [ ] **Step 4: Copiar los JS y aplicar los cambios**

Run:
```bash
mkdir -p wp-plugin/canal-home/assets/carte
cp public/assets/js/{search-map,search-tabs,ai-search,skeleton-controler}.js wp-plugin/canal-home/assets/carte/
```

**4a. `assets/carte/search-map.js`** — sustituir:

```js
    if (!mapElement || typeof google === 'undefined' || !google.maps) {
        signalMapReady();
        return;
    }
```
por:
```js
    if (!mapElement || typeof google === 'undefined' || !google.maps) {
        // WP: sin Google Maps (clave ausente o bloqueada) → aviso en lugar de un panel vacío.
        if (mapElement) {
            const notice = document.createElement('div');
            notice.className = 'map-unavailable';
            notice.textContent = 'Carte indisponible';
            mapElement.appendChild(notice);
        }
        signalMapReady();
        return;
    }
```

**4b. `assets/carte/search-tabs.js`** — sustituir el `});` final del archivo (el que cierra `DOMContentLoaded`) por:

```js

    // WP: « Autour de moi » — posición redondeada a 2 decimales (~1 km) y reenvío del formulario.
    const nearbyButton = document.getElementById('search-nearby-button');
    const nearbyStatus = document.getElementById('search-nearby-status');
    if (nearbyButton && nearbyStatus) {
        nearbyButton.addEventListener('click', () => {
            const form = nearbyButton.closest('form');
            if (!form || !navigator.geolocation) {
                nearbyStatus.textContent = 'La géolocalisation n’est pas disponible sur cet appareil.';
                return;
            }
            nearbyButton.disabled = true;
            nearbyStatus.textContent = 'Localisation en cours…';
            navigator.geolocation.getCurrentPosition((position) => {
                form.elements.lat.disabled = false;
                form.elements.lng.disabled = false;
                form.elements.lat.value = position.coords.latitude.toFixed(2);
                form.elements.lng.value = position.coords.longitude.toFixed(2);
                form.submit();
            }, () => {
                nearbyButton.disabled = false;
                nearbyStatus.textContent = 'Position refusée ou introuvable. Autorisez la localisation dans votre navigateur puis réessayez.';
            }, { timeout: 10000, maximumAge: 600000 });
        });
    }
});
```

**4c. `assets/carte/ai-search.js`** — cuatro sustituciones:

1. `const baseSearchUrl = \`${BASE_URL}${lang}/search\`;` →
```js
    const baseSearchUrl = CDM_CARTE.pageUrl; // WP: sin BASE_URL/lang globales
```
2. `const keys = ['q', 'city', 'type', 'type[]'];` →
```js
        const keys = ['q', 'city', 'type', 'type[]', 'location', 'lat', 'search_keywords', 'search_location', 'category[]']; // WP: + lugar, posición y alias
```
3. El cuerpo del `strategyBtns.forEach(...)` →
```js
    strategyBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.aiPrompt || ''; // WP: sugerencias del canal en data-ai-prompt
            aiPrompt.value = text;
            submitAiSearch(text);
        });
    });
```
4. El bloque `try { … } catch (error) { … }` de `executeAiSearch` →
```js
        try {
            // WP: endpoint del plugin (JSON {prompt}); responde fichas con slug + reason.
            const response = await fetch(CDM_CARTE.aiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: text }),
            });
            const payload = await response.json();

            if (!response.ok || payload.error) {
                // Límite o indisponible: se muestra el mensaje y la lista no se toca.
                responseLoading?.classList.add('is-hidden');
                responseEmpty?.classList.remove('is-hidden');
                responseBody?.classList.remove('is-hidden');
                responseLabel.textContent = 'Assistant IA';
                responseTitle.textContent = 'Réponse indisponible';
                responseText.textContent = payload.message || 'L’assistant IA est momentanément indisponible.';
                if (responseMeta) responseMeta.textContent = '';
                return;
            }

            // Sin filtros aplicados, window.searchResults contiene todas las fichas: se completan por slug.
            const all = getAvailableResults();
            const results = (Array.isArray(payload.results) ? payload.results : [])
                .map((row) => {
                    const base = all.find((item) => item.slug === row.slug);
                    return base ? { ...base, text: row.reason } : null;
                })
                .filter(Boolean);

            applyAiResults({
                count: results.length,
                results,
                title: results.length === 1 ? results[0].title : 'Sélection de l’assistant',
                text: results.length === 1 ? results[0].text : undefined,
            });
        } catch (error) {
            console.error('AI Error:', error);
            responseLoading?.classList.add('is-hidden');
            responseEmpty?.classList.remove('is-hidden');
            responseBody?.classList.add('is-hidden');
        } finally {
```
(el `finally { … }` original se conserva tal cual).

Comprobar: `grep -n "BASE_URL\|\blang\b\|ai-analyze\|aiStrategy" wp-plugin/canal-home/assets/carte/ai-search.js` → sin resultados.

**4d. `assets/carte/skeleton-controler.js`** — con 254 fichas no se puede esperar a todas las imágenes. Sustituir desde `const images = …` hasta el final del `images.forEach(...)` por:

```js
    // WP: hasta 254 fichas → solo las 6 primeras imágenes deciden cuándo se muestra la página;
    // el resto se carga en diferido (loading="lazy").
    const FIRST_IMAGES = 6;
    const images = Array.from(document.querySelectorAll('.search-layout-page .card-image img[data-src]'));

    if (images.length === 0) {
        signalImagesReady();
        return;
    }

    const target = Math.min(images.length, FIRST_IMAGES);
    let settledImages = 0;
    const markSettled = (index) => {
        if (index >= FIRST_IMAGES) return;
        settledImages += 1;
        if (settledImages >= target) {
            signalImagesReady();
        }
    };

    images.forEach((img, index) => {
        const src = img.dataset.src;
        if (!src) {
            markSettled(index);
            return;
        }

        if (index >= FIRST_IMAGES) img.loading = 'lazy';

        const wrapper = img.closest('.card-image');

        img.addEventListener('load', () => {
            wrapper?.classList.add('is-loaded');
            markSettled(index);
        }, { once: true });

        img.addEventListener('error', () => {
            if (wrapper) {
                wrapper.classList.add('is-loaded', 'card-image--placeholder');
                img.remove();
                const icon = document.createElement('div');
                icon.className = 'card-image-icon';
                icon.innerHTML = '<i class="bi bi-building"></i>';
                wrapper.appendChild(icon);
            }
            markSettled(index);
        }, { once: true });

        img.src = src;
    });
```

- [ ] **Step 5: Lint + tests y despliegue**

Run: `wp-plugin/remote.sh deploy`
Expected: lint 7.4 sin errores en `template-carte.php` y demás, tests OK, rsync hecho.

- [ ] **Step 6: Crear la página privada**

Run:
```bash
wp-plugin/remote.sh wp post create --post_type=page --post_title='Carte interactive' --post_name=carte --post_status=private --meta_input='{"_wp_page_template":"canal-home/template-carte.php"}' --porcelain
```
Expected: un ID numérico (anotarlo: **ID_CARTE**). Comprobar: `wp-plugin/remote.sh wp post get <ID_CARTE> --fields=ID,post_name,post_status` → `carte`, `private`.

- [ ] **Step 7: Comprobación rápida en navegador**

En Chrome (sesión de admin; la página es privada) abrir `https://www.plan-canal-du-midi.com/carte/` y `…/carte/?search_location=Toulouse`.
Expected: la página sale del esqueleto, el contador muestra el total de fichas publicadas (254 a 2026-09-29) y ≥ 10 con Toulouse, los pines aparecen en el mapa y la consola no tiene errores. Si algo falla, corregir antes del commit (la comparación visual detallada es la Task 5).

- [ ] **Step 8: Commit**

```bash
git add wp-plugin/canal-home/template-carte.php wp-plugin/canal-home/assets/carte wp-plugin/canal-home/canal-home.php wp-plugin/canal-home/includes/data.php
git commit -m "feat(wp-carte): plantilla « Carte interactive » copiada de /search + JS adaptado"
```

---

### Task 5: Verificación en navegador y documentación

**Files:**
- Modify: `docs/TASKS.md`, `docs/SESSION.md`
- Modify (solo si la verificación lo exige): `wp-plugin/build/carte-extra.css` → rebuild

**Interfaces:**
- Consumes: página privada `https://www.plan-canal-du-midi.com/carte/` (sesión de admin en Chrome) y `http://localhost/canal_du_midi/fr/search` como referencia.

- [ ] **Step 1: Comparación visual lado a lado**

Con Chrome (claude-in-chrome), a **1440**, **1024** y **390** px de ancho: capturar `/fr/search` local y `/carte/`, haciendo scroll por la lista antes de la captura de página completa. Criterios: mismas tres columnas (1440), mapa bajo la lista (1024), barra inferior filtros/lista/mapa (390); tipografía y tarjetas iguales; la cabecera del tema no tapa la sidebar ni el mapa (`--cdm-header-h` correcto); sin scroll horizontal.

- [ ] **Step 2: Recorrido funcional**

Comprobar y anotar el resultado de cada punto:
1. `?q=ecluse` → solo esclusas; chip « ecluse »; « Effacer » vuelve a 254.
2. Pestaña Catégories → « Hébergement » → `?type=hebergement` con sus subcategorías.
3. `/carte/?search_location=Toulouse&search_keywords=&category[]=` → resultados de Toulouse, chip « Toulouse ».
4. Pin → InfoWindow con carrusel, « Itinéraire »; tarjeta → hover resalta el pin.
5. « Autour de moi » aceptando → URL con `lat`/`lng` de 2 decimales, tarjetas con « à X km » ordenadas; **denegando** (Review Focus 5) → aviso en línea, botón usable.
6. IA: sugerencia « Une balade à vélo en famille » → tarjetas y pines de la selección; con `?q=x` aplicado → recarga la página sin filtros y lanza la pregunta (Review Focus 4).
7. Pestaña Red: al cargar se piden ≈ 6 imágenes y el resto al hacer scroll (Review Focus 1); una sola carga de `maps.googleapis.com` (sin aviso « included multiple times » en consola).
8. Bloquear `maps.googleapis.com` (DevTools → Network request blocking) → la lista aparece en ≤ 5 s y el panel dice « Carte indisponible » (Review Focus 2).
9. Consola sin errores JS en todos los casos anteriores.

- [ ] **Step 3: Corregir diferencias (si las hay)**

Cada diferencia visual se corrige **solo** en `wp-plugin/build/carte-extra.css`, luego `node wp-plugin/build/build-css.mjs` y `wp-plugin/remote.sh deploy`, y se vuelve a capturar. Commit por corrección: `fix(wp-carte): <qué>`.

- [ ] **Step 4: Documentar**

`docs/TASKS.md`: mover TASK-029 a 🟢 con: página privada (ID_CARTE), archivos, comandos de verificación, rollback (`wp post delete ID_CARTE`, `wp transient delete canal_carte_listings`) y lo pendiente (enlaces de la home y redirección de `/explorer/` solo con orden explícita).
`docs/SESSION.md`: nuevo bloque de cierre arriba con fecha, dónde quedamos, archivos, decisiones (filtrado en PHP por GET, Maps del tema, 6 imágenes iniciales) y próxima acción.

- [ ] **Step 5: Commit**

```bash
git add docs/TASKS.md docs/SESSION.md
git commit -m "docs: TASK-029 carte interactive desplegada en privado"
```
