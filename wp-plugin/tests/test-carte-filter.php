<?php
// Tests de carte-filter.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-carte-filter.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/carte-faq.php';

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
check(canal_carte_fold("l\u{2019}Écluse") === "l'ecluse", 'fold: apóstrofo tipográfico → recto');

// ── canal_carte_params ──────────────────────────────────────────────────
check(canal_carte_params([], $valid) === ['q' => '', 'type' => [], 'location' => '', 'lat' => null, 'lng' => null, 'tag' => ''], 'params: vacío');
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

// ── canal_carte_has_filters (noindex de las URLs filtradas) ─────────────
check(!canal_carte_has_filters([]), 'has_filters: URL limpia');
check(!canal_carte_has_filters(['search_keywords' => '', 'category' => [''], 'search_location' => '']), 'has_filters: parámetros vacíos (formulario de la home sin rellenar)');
check(canal_carte_has_filters(['q' => 'vélo']), 'has_filters: q');
check(canal_carte_has_filters(['type' => ['hotel']]), 'has_filters: type[]');
check(canal_carte_has_filters(['search_location' => 'Toulouse']), 'has_filters: alias de /explorer/');
check(canal_carte_has_filters(['lat' => '43.60', 'lng' => '1.44']), 'has_filters: posición');
check(!canal_carte_has_filters(['utm_source' => 'newsletter']), 'has_filters: parámetros ajenos no cuentan');

// ── canal_carte_faq (respuestas construidas con los datos reales) ──────
$F = function (string $city, array $slugs): array { return ['city' => $city, 'cat_slugs' => $slugs]; };
$faqData = [
    $F('Castelnaudary', ['location-bateau']), $F('Castelnaudary', ['location-bateau']), $F('Homps', ['location-bateau']),
    $F('Trèbes', ['location-bateau']), $F('Agde', ['location-bateau']), $F('', ['location-bateau']),
    $F('Carcassonne', ['location-de-velo', 'velo']), $F('Toulouse', ['hotel', 'hebergement']), $F('Béziers', ['camping', 'hebergement']),
];
check(canal_carte_top_cities($faqData, 'location-bateau', 3) === ['Castelnaudary', 'Agde', 'Homps'], 'faq: communes por nº de fichas y luego alfabético, sin vacías');
check(canal_carte_join_fr(['A', 'B', 'C']) === 'A, B et C' && canal_carte_join_fr(['A']) === 'A' && canal_carte_join_fr([]) === '', 'faq: enumeración en francés');
$faq = canal_carte_faq($faqData);
$qs = array_column($faq, 'q');
check(count($faq) === 4, 'faq: 4 preguntas (' . count($faq) . ')');
check(strpos($faq[0]['a'], '6 loueurs') !== false && strpos($faq[0]['a'], 'Castelnaudary') !== false, 'faq: bateau con recuento y communes reales');
check(strpos($faq[2]['a'], '2 hébergements') !== false, 'faq: hébergement cuenta las subcategorías (vía cat_slugs)');
check(count(canal_carte_faq([$F('Agde', ['bar'])])) === 1, 'faq: categorías sin fichas se omiten (queda la pregunta de uso)');
check(substr($qs[0], -2) === ' ?', 'faq: puntuación francesa (espacio antes de ?)');
check(strpos($faq[1]['a'], '1 loueur de vélos est référencé') === 0, 'faq: concordancia en singular');
$pdf = canal_carte_faq($faqData, '2026');
check(count($pdf) === 5 && strpos(end($pdf)['q'], 'PDF') !== false, 'faq: con edición del plan, pregunta « carte en PDF » al final');
check(strpos(end($pdf)['a'], 'édition 2026') !== false && strpos(end($pdf)['a'], '/plan-canal-du-midi.pdf') !== false, 'faq: PDF con año de edición y URL fija');

// Páginas de taxonomía en la carte (TASK-064): /categorie/, /region/, /mot-cle/ filtran la carte por su término.
$base = canal_carte_params([], ['hotel', 'camping']);
$cat = ['tax' => 'job_listing_category', 'slug' => 'hotel', 'name' => 'Hôtel', 'description' => ''];
$reg = ['tax' => 'region', 'slug' => 'homps', 'name' => 'HOMPS', 'description' => ''];
$tag = ['tax' => 'case27_job_listing_tags', 'slug' => 'animaux-acceptes', 'name' => 'Animaux acceptés', 'description' => ''];
check(canal_carte_term_params($base, $cat)['type'] === ['hotel'], 'término: categoría → type');
check(canal_carte_term_params(canal_carte_params(['type' => 'camping'], ['hotel', 'camping']), $cat)['type'] === ['camping'], 'término: el filtro elegido por el visitante manda');
check(canal_carte_term_params($base, $reg)['location'] === 'HOMPS', 'término: región → municipio');
check(canal_carte_term_params($base, $tag)['tag'] === 'animaux-acceptes' && canal_carte_term_params($base, null) === $base + ['tag' => ''], 'término: etiqueta → tag; sin término, sin cambios');
$tagged = [
    ['title' => 'A', 'cat_names' => [], 'cat_slugs' => ['hotel'], 'tag_slugs' => ['animaux-acceptes'], 'city' => '', 'address' => '', 'description' => '', 'lat' => null, 'lng' => null],
    ['title' => 'B', 'cat_names' => [], 'cat_slugs' => ['hotel'], 'tag_slugs' => [], 'city' => '', 'address' => '', 'description' => '', 'lat' => null, 'lng' => null],
];
check(array_column(canal_carte_filter($tagged, canal_carte_term_params($base, $tag)), 'title') === ['A'], 'filtro: por etiqueta');
check(count(canal_carte_filter($tagged, $base)) === 2, 'filtro: sin etiqueta, todos');
$seo = canal_carte_term_seo($cat, 18);
check($seo['h1'] === 'Hôtel au bord du Canal du Midi' && strpos($seo['title'], '18 adresses') !== false && strpos($seo['description'], '18 ') === 0, 'seo: categoría');
check(canal_carte_term_seo($reg, 11)['h1'] === 'Homps : prestataires au bord du Canal du Midi', 'seo: región (MAYÚSCULAS → nombre)');
check(canal_carte_term_seo(['description' => 'Texte du thème.'] + $cat, 3)['description'] === 'Texte du thème.', 'seo: usa la descripción del término si existe');
check(canal_carte_term_seo($cat, 1)['title'] === "Hôtel au bord du Canal du Midi : 1 adresse | L'Officiel", 'seo: singular');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
