<?php
// Tests de guide-core.php (introducción de campings y location de bateau) — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-guide.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/calcul-core.php';
require __DIR__ . '/../canal-home/includes/etape-core.php';
require __DIR__ . '/../canal-home/includes/fiche-core.php';
require __DIR__ . '/../canal-home/includes/guide-core.php';

$fails = 0;
function check(bool $cond, string $label): void
{
    global $fails;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        echo "FAIL - $label\n";
        $fails++;
    }
}

$trace = canal_guide_trace(__DIR__ . '/../canal-home/assets/calcul/canal-du-midi-trace.json');
check(count($trace) > 900, 'trazado: puntos cargados');
check(canal_guide_rows([['title' => 'CAMPING  LE  PIN', 'url' => '', 'city' => '', 'lat' => 43.316259, 'lng' => 3.286161, 'cat_slugs' => ['camping']]], 'camping', $trace)[0]['title'] === 'Camping le Pin', 'filas: título legible (mayúsculas y espacios dobles)');

// Coordenadas reales de fichas (05/10).
$p = canal_guide_project($trace, 43.316259, 3.286161); // Les Berges du Canal, Villeneuve-lès-Béziers
check($p['m'] < 300 && $p['pk'] > 210 && $p['pk'] < 216, 'proyección: camping en la orilla (' . $p['m'] . ' m, PK ' . $p['pk'] . ')');
$p = canal_guide_project($trace, 43.29783, 2.222197); // Montolieu
check($p['m'] > 3000, 'proyección: Montolieu lejos del canal (' . $p['m'] . ' m)');
$p = canal_guide_project($trace, 43.3126509, 1.9553227); // Cris'Boat, Castelnaudary
check(abs($p['pk'] - 64.9) < 2, 'proyección: Castelnaudary ≈ PK 65 (' . $p['pk'] . ')');

$L = function (string $title, string $city, ?float $lat, ?float $lng, array $cats) {
    return ['title' => $title, 'url' => '/fiche/' . strtolower($title) . '/', 'city' => $city, 'lat' => $lat, 'lng' => $lng, 'cat_slugs' => $cats];
};
$listings = [
    $L('Montolieu', 'Montolieu', 43.29783, 2.222197, ['camping']),
    $L('Berges', 'Villeneuve-Lès-Béziers', 43.316259, 3.286161, ['camping']),
    $L('Sans GPS', 'Agde', null, null, ['camping']),
    $L('Paris', 'Paris', 48.8566, 2.3522, ['camping']),
    $L('OFFICE  DE TOURISME', 'Homps', 43.266118, 2.720717, ['camping', 'location-bateau', 'lieux-dinformations']),
    $L('Hotel', 'Homps', 43.266118, 2.720717, ['hotel']),
    $L('Cris', 'Castelnaudary', 43.3126509, 1.9553227, ['location-bateau']),
    $L('Canalous Homps', 'Homps', 43.266118, 2.720717, ['location-bateau']),
    $L('Canalous Agde', 'Agde', 43.3202611, 3.4679195, ['location-bateau']),
    $L('Haricot', 'Colombiers', 43.3148346, 3.1383661, ['location-bateau']),
    $L('Canalous Colombiers', 'Colombiers', 43.3148346, 3.1383661, ['location-bateau']),
];

$rows = canal_guide_rows($listings, 'camping', $trace);
check(array_column($rows, 'title') === ['Montolieu', 'Berges'], 'filas: por PK, sin GPS, sin lejanas, sin offices de tourisme ni otras categorías');

check(canal_guide_for('hotel', $listings, $trace) === null, 'guía: solo camping y location-bateau');

$g = canal_guide_for('camping', $listings, $trace);
check(strpos($g, '2 campings') === 0 && strpos($g, '1 au bord du canal') !== false, 'camping: intro con recuentos reales');

$b = canal_guide_for('location-bateau', $listings, $trace);
check(strpos($b, '5 loueurs dans 4 bases, de Castelnaudary à Agde.') !== false, 'bateau: loueurs y bases por PK con nombre del calcul');
check(strpos($b, '8 km/h') !== false && strpos($b, 'carte de plaisance') !== false, 'bateau: intro con la regla de la fuente');
check(canal_guide_for('location-bateau', [], $trace) === null, 'bateau: sin loueurs, sin intro');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
