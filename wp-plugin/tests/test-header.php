<?php
// Tests de includes/header.php (menú y sección activa) — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-header.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
const CANAL_CARTE_PATH = '/explorer-2026/'; // en WP la define canal-home.php
function add_filter(...$a) {}
function add_action(...$a) {}
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
check(canal_header_section($menu, ['restaurant'], '') === 'Manger & Boire', 'sección: ficha de restaurante');
check(canal_header_section($menu, ['location-bateau'], '') === 'En bateau', 'sección: ficha de location de bateau');
check(canal_header_section($menu, ['xx', 'gites'], '') === 'Se loger', 'sección: segunda categoría');
check(canal_header_section($menu, ['shopping'], '') === 'Préparer', 'sección: shopping está en Préparer');
check(canal_header_section($menu, [], 'hebergement') === 'Se loger', 'sección: carte ?type=hebergement');
check(canal_header_section($menu, [], 'velo') === 'Vélo & balades', 'sección: carte ?type=velo');
check(canal_header_section($menu, ['inconnue'], 'inconnu') === '', 'sección: sin coincidencia');
check(canal_header_section($menu, [], '') === '', 'sección: home');

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
