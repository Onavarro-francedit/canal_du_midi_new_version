<?php
// Tests de includes/header.php (menú y sección activa) — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-header.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
const CANAL_CARTE_PATH = '/explorer-2026/'; // en WP la define canal-home.php
function add_filter(...$a) {}
function add_action(...$a) {}
require __DIR__ . '/../canal-home/includes/etape-core.php';
require __DIR__ . '/../canal-home/includes/header.php';

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

$menu = canal_header_menu();

// Sección activa: por categoría de la ficha o por ?type= de la carte.
check(canal_header_section($menu, ['restaurant'], '') === 'Se loger & manger', 'sección: ficha de restaurante');
check(canal_header_section($menu, ['location-bateau'], '') === 'En bateau', 'sección: ficha de location de bateau');
check(canal_header_section($menu, ['xx', 'gites'], '') === 'Se loger & manger', 'sección: segunda categoría');
check(canal_header_section($menu, ['moulins'], '') === 'Villes & étapes', 'sección: moulins en Villes & étapes');
check(canal_header_section($menu, [], 'hebergement') === 'Se loger & manger', 'sección: carte ?type=hebergement');
check(canal_header_section($menu, [], 'velo') === 'Vélo & balades', 'sección: carte ?type=velo');
check(canal_header_section($menu, ['inconnue'], 'inconnu') === '', 'sección: sin coincidencia');
check(canal_header_section($menu, [], '') === '', 'sección: home');

// Estructura (TASK-061, docs/inventario-paginas-2026-10-05.md §7): 5 paneles, sin categorías casi vacías.
check(array_keys($menu) === ['En bateau', 'Vélo & balades', 'Villes & étapes', 'Se loger & manger', 'Préparer'], 'menú: 5 paneles en orden');
$all = [];
foreach ($menu as $panel) {
    foreach ($panel['cols'] as $items) {
        $all = array_merge($all, array_values($items));
    }
}
check(count($all) <= 75, 'menú: como mucho 75 enlaces (' . count($all) . ')');
$vides = ['hostel', 'auberge-collective', 'roulotte', 'appartement-maison-a-louer', 'chambre-a-louer', 'insolite', 'shopping', 'commerce', 'artisanat', 'services', 'loisir-de-plein-air', 'location-de-canoe-kayak', 'nautique', 'restauration-2', 'peniche', 'bateau-restaurant'];
check(array_filter($all, function ($p) use ($vides) { return preg_match('~^/categorie/([^/]+)/$~', $p, $m) && in_array($m[1], $vides, true); }) === [], 'menú: sin categorías vacías o duplicadas');
check(in_array(CANAL_ETAPE_PATH . 'le-somail/', $all, true) && in_array(CANAL_ETAPES_PATH, array_values($menu['Villes & étapes']['foot']), true), 'menú: etapas e índice de etapas');

// Invariantes del menú (NN/g: cada opción una sola vez; enlaces salientes en https).
$paths = $labels = [];
foreach ($menu as $panel) {
    foreach ($panel['cols'] as $items) {
        foreach ($items as $text => $path) {
            $paths[] = $path;
            $labels[] = $text;
        }
    }
}
check(count($paths) === count(array_unique($paths)), 'menú: ninguna URL repetida');
check(count($labels) === count(array_unique($labels)), 'menú: ninguna etiqueta repetida');
check(array_filter($paths, function ($p) { return $p[0] !== '/' && strpos($p, 'https://') !== 0; }) === [], 'menú: externos en https');
check(!in_array('http://www.vnf.fr/calculitinerairefluvial/app/Main.html', $paths, true), 'menú: sin el CIFL cerrado');
$titles = [];
foreach ($menu as $panel) {
    $titles = array_merge($titles, array_keys($panel['cols']));
}
check(array_intersect($titles, $labels) === [], 'menú: ningún título de columna repetido como enlace');

// Footer: rutas internas o https.
$foot = [];
foreach (canal_footer_menu() as $items) {
    $foot = array_merge($foot, array_values($items));
}
check($foot !== [] && array_filter($foot, function ($p) { return $p[0] !== '/' && strpos($p, 'https://') !== 0 && strpos($p, 'tel:') !== 0 && strpos($p, 'mailto:') !== 0; }) === [], 'footer: rutas internas, https, tel o mailto');

exit($fails ? 1 : 0);
