<?php
// Tests de live.php (interruptor de publicación) y redirects-2026.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-live.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/live.php';
require __DIR__ . '/../canal-home/includes/redirects-2026.php';

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

// Rutas en los dos modos.
$off = canal_2026_paths(false);
$on = canal_2026_paths(true);
check($off['CANAL_HOME_PATH'] === '/accueil-2026/' && $off['CANAL_CARTE_PATH'] === '/explorer-2026/' && $off['CANAL_FICHE_PATH'] === '/fiche-2026/' && $off['CANAL_CONTENU_SUFFIX'] === '-2026', 'privado: rutas -2026');
check($on['CANAL_HOME_PATH'] === '/' && $on['CANAL_CARTE_PATH'] === '/explorer/' && $on['CANAL_FICHE_PATH'] === '/fiche/' && $on['CANAL_CONTENU_SUFFIX'] === '', 'publicado: rutas originales');
check($on['CANAL_ETAPE_PATH'] === '/etape/' && $on['CANAL_ETAPES_PATH'] === '/etapes/' && $on['CANAL_PLANNER_PATH'] === '/planificateur/', 'publicado: étapes y planificador');
check(array_keys($on) === array_keys($off), 'mismas constantes en los dos modos');

// ¿Modo publicado? Opción, o vista previa de un administrador (cookie + permiso).
check(canal_2026_live_mode('1', false, false) === true, 'opción activada → publicado');
check(canal_2026_live_mode('', true, true) === true, 'vista previa de un administrador → publicado solo para él');
check(canal_2026_live_mode('', true, false) === false, 'cookie sin permiso → nada');
check(canal_2026_live_mode('', false, true) === false && canal_2026_live_mode(false, false, false) === false, 'sin opción ni vista previa → privado');

// Redirecciones 301 (solo se aplican en modo publicado).
$r = function (string $path) { return canal_2026_redirect_target($path); };
check($r('/accueil-2026/') === '/', '301: accueil-2026 → /');
check($r('/explorer-2026/') === '/explorer/' && $r('/planificateur-2026/') === '/planificateur/', '301: carte y planificador');
check($r('/fiche-2026/port-de-sete/') === '/fiche/port-de-sete/', '301: ficha');
check($r('/etape-2026/le-somail/') === '/etape/le-somail/' && $r('/etapes-2026/') === '/etapes/', '301: étapes');
check($r('/post-category/actualites-2026/page/3/') === '/post-category/actualites/page/3/', '301: archivo paginado');
check($r('/navigation/regles-de-navigation-2026/') === '/navigation/regles-de-navigation/' && $r('/canal-de-la-robine-2026') === '/canal-de-la-robine/', '301: contenido -2026');
check($r('/organiser-votre-sejour/') === '/planificateur/' && $r('/demande-de-location-de-bateau/') === '/planificateur/', '301: sustituidas por el planificador');
check($r('/agenda/') === '/manifestations-et-fetes-canal-du-midi/', '301: agenda vacío');
check($r('/hotels/') === '/categorie/hotel/' && $r('/se-restaurer/') === '/categorie/restauration/' && $r('/details-presta/') === '/explorer/', '301: páginas antiguas → su categoría (carte 2026)');
check($r('/les-ecluses-du-canal-du-midi/') === '/les-ecluses-du-canal-du-midi-2/', '301: lista antigua de esclusas → guía');
check(count(CANAL_2026_LEGACY_REDIRECTS) >= 50, 'mapa: ≥ 50 páginas antiguas (' . count(CANAL_2026_LEGACY_REDIRECTS) . ')');
foreach (['/', '/explorer/', '/fiche/port-de-sete/', '/canal-de-la-robine/', '/calcul-de-distance-canal-du-midi/', '/wp-admin/', '/plan-canal-du-midi.pdf', '/categorie/hotel/', '/2026/'] as $keep) {
    check($r($keep) === null, "sin redirección: $keep");
}
check($r('/zone/homps-capestang/') === '/etapes/' && $r('/zone/toulouse-castelnaudary') === '/etapes/', '301: zonas → étapes');
check(canal_2026_expired_fiche_target(['site-et-monument' => 5, 'musees' => 7, 'produits-regionaux' => 19]) === '/categorie/site-et-monument/', 'ficha caducada → su categoría principal (Abbaye de Fontcaude → site et monument)');
check(canal_2026_expired_fiche_target(['bateau-promenade' => 0, 'location-bateau' => 12]) === '/categorie/location-bateau/', 'ficha caducada: salta la categoría sin fichas');
check(canal_2026_expired_fiche_target(['vide' => 0]) === '/explorer/' && canal_2026_expired_fiche_target([]) === '/explorer/', 'ficha caducada sin categoría con fichas → explorer (nunca una carte vacía)');
check(canal_2026_redirect_url('/hotels/', 'utm_source=x') === '/categorie/hotel/?utm_source=x' && canal_2026_redirect_url('/fiche-2026/a/', 'x=1') === '/fiche/a/?x=1', 'la query se conserva');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
