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
    $L(['id' => 4, 'title' => 'Adresse sin GPS', 'city' => 'Agde', 'cat_slugs' => ['bar', 'restauration']]),
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
