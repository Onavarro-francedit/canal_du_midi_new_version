<?php
// Tests de links-2026.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-links.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
const CANAL_HOME_PATH = '/accueil-2026/';
const CANAL_CARTE_PATH = '/explorer-2026/';
const CANAL_FICHE_PATH = '/fiche-2026/';
const CANAL_PLANNER_PATH = '/planificateur-2026/';
require __DIR__ . '/../canal-home/includes/contenu-core.php';
require __DIR__ . '/../canal-home/includes/calcul-core.php';
require __DIR__ . '/../canal-home/includes/links-2026.php';

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

// Contenido elegible simulado (en WP: get_page_by_path + canal_contenu_post_eligible).
$isContent = function (string $path): bool {
    return in_array($path, ['canal-de-la-robine', 'navigation/regles-de-navigation', 'nous-contacter', 'peniches-a-vendre'], true);
};
$p = function (string $path) use ($isContent) { return canal_2026_path($path, $isContent); };

// Rutas fijas.
check($p('/') === '/accueil-2026/', 'home → accueil-2026');
check($p('/explorer/') === '/explorer-2026/', 'carte antigua → explorer-2026');
check($p('/fiche/port-de-sete/') === '/fiche-2026/port-de-sete/', 'ficha → fiche-2026');
check($p('/categorie/location-bateau/') === '/explorer-2026/?type=location-bateau', 'categoría → carte filtrada');
check($p('/categorie/ports/page/2/') === '/explorer-2026/?type=ports', 'categoría paginada → carte filtrada');
check($p('/calcul-de-distance-canal-du-midi/') === '/calcul-de-distance-canal-du-midi-2026/', 'calcul → calcul 2026');
check($p('/organiser-votre-sejour/') === '/planificateur-2026/', 'organiser → planificateur');

// Contenido: solo si es elegible.
check($p('/canal-de-la-robine/') === '/canal-de-la-robine-2026/', 'página de contenido → -2026');
check($p('/navigation/regles-de-navigation/') === '/navigation/regles-de-navigation-2026/', 'página hija → -2026');
check($p('/peniches-a-vendre') === '/peniches-a-vendre-2026/', 'sin barra final');
check($p('/recevoir-le-plan-du-canal-du-midi-2/') === '/recevoir-le-plan-du-canal-du-midi-2/', 'no elegible → intacta');

// Lo que nunca se toca.
foreach (['/wp-content/uploads/a.jpg', '/wp-admin/admin-post.php', '/plan-canal-du-midi.pdf', '/feed/', '/canal-de-la-robine-2026/', '/fiche-2026/x/', '/accueil-2026/', '/region/homps/', '/zone/homps-capestang/', '/post-category/actualites/', '/mot-cle/velo/'] as $keep) {
    check($p($keep) === $keep, "intacta: $keep");
}

// HTML: solo los href del <body>, del propio sitio; conserva host, query y ancla.
$host = 'https://www.plan-canal-du-midi.com';
$html = '<html><head><link rel="canonical" href="https://www.plan-canal-du-midi.com/fiche/x/"></head><body>'
    . '<a href="https://www.plan-canal-du-midi.com/canal-de-la-robine/#carte">a</a>'
    . '<a href="/fiche/port-de-sete/">b</a>'
    . '<a href="https://www.plan-canal-du-midi.com/categorie/hotel/?search_location=Homps">c</a>'
    . '<a class="x" href="https://example.com/canal-de-la-robine/">d</a>'
    . '<a href="https://www.plan-canal-du-midi.com/?s=velo">e</a>'
    . '<a href="tel:+33600000000">f</a>'
    . '</body></html>';
$out = canal_2026_rewrite_html($html, $host, $isContent);
check(strpos($out, '<link rel="canonical" href="https://www.plan-canal-du-midi.com/fiche/x/">') !== false, 'html: <head> intacto');
check(strpos($out, 'href="https://www.plan-canal-du-midi.com/canal-de-la-robine-2026/#carte"') !== false, 'html: host + ancla');
check(strpos($out, 'href="/fiche-2026/port-de-sete/"') !== false, 'html: ruta relativa');
check(strpos($out, 'href="https://www.plan-canal-du-midi.com/explorer-2026/?type=hotel&amp;search_location=Homps"') !== false, 'html: categoría con query fusionada');
check(strpos($out, 'href="https://example.com/canal-de-la-robine/"') !== false, 'html: otro dominio intacto');
check(strpos($out, 'href="https://www.plan-canal-du-midi.com/?s=velo"') !== false, 'html: búsqueda en la home intacta');
check(strpos($out, 'href="tel:+33600000000"') !== false, 'html: tel intacto');
check(canal_2026_rewrite_html($out, $host, $isContent) === $out, 'html: idempotente');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
