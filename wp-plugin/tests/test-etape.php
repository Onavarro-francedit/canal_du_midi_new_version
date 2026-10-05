<?php
// Tests de etape-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-etape.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/fiche-core.php';
require __DIR__ . '/../canal-home/includes/calcul-core.php';
require __DIR__ . '/../canal-home/includes/etape-core.php';

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

// Datos: 20 etapas, slugs únicos, las del Canal du Midi con su lugar en el calcul y en orden de PK.
$slugs = array_column(CANAL_ETAPES, 'slug');
check(count(CANAL_ETAPES) === 20 && count(array_unique($slugs)) === 20, 'datos: 20 etapas con slug único');
$midi = array_values(array_filter(CANAL_ETAPES, function ($e) { return $e['canal'] === 'midi'; }));
$pks = array_map(function ($e) { return canal_calcul_pk($e['calcul']); }, $midi);
$sorted = $pks;
sort($sorted);
check(count($midi) === 17 && $pks === $sorted && !in_array(null, array_map('canal_calcul_find', array_column($midi, 'calcul')), true), 'datos: 17 etapas del Canal du Midi en orden de PK, todas en el calcul');
check(canal_calcul_pk('Port-Lauragais') === 50.2, 'calcul: Port-Lauragais PK 50,2');

// Vecinos.
$n = canal_etape_neighbors('le-somail');
check($n['prev']['slug'] === 'argens-minervois' && $n['next']['slug'] === 'capestang', 'vecinos: Le Somail entre Argens y Capestang');
check(canal_etape_neighbors('toulouse')['prev'] === null && canal_etape_neighbors('marseillan')['next'] === null, 'vecinos: extremos');
check(canal_etape_neighbors('narbonne') === ['prev' => null, 'next' => null], 'vecinos: Robine sin vecinos');
check(canal_etape_find('nope') === null && canal_etape_find('agde')['name'] === 'Agde', 'buscar etapa');

// Frase de cabecera.
check(canal_etape_lead(canal_etape_find('le-somail')) === 'PK 165,6 du Canal du Midi, entre Argens-Minervois et Capestang.', 'frase: entre dos etapas');
check(canal_etape_lead(canal_etape_find('toulouse')) === 'PK 0 du Canal du Midi, point de départ du canal, avant Ramonville-Saint-Agne.', 'frase: inicio');
check(canal_etape_lead(canal_etape_find('marseillan')) === "PK 240,5 du Canal du Midi, arrivée du canal sur l'étang de Thau, après Agde.", 'frase: final');
check(strpos(canal_etape_lead(canal_etape_find('narbonne')), 'canal de la Robine') !== false, 'frase: Robine');

// Agrupación por radio y tipo.
$L = function ($id, $title, $cats, $lat, $lng) { return ['id' => $id, 'title' => $title, 'cat_slugs' => $cats, 'lat' => $lat, 'lng' => $lng, 'url' => "/f/$id/", 'image' => '', 'type' => '']; };
$listings = [
    $L(1, 'Loueur A', ['location-bateau'], 43.2665, 2.9045),
    $L(2, 'Loueur B', ['croisiere-bateau'], 43.2700, 2.9100),
    $L(3, 'Hôtel C', ['hotel'], 43.2664, 2.9041),
    $L(4, 'Camping D', ['camping'], 43.2680, 2.9070),
    $L(5, 'Écluse E', ['ecluses'], 43.2600, 2.9000),
    $L(6, 'Loin F', ['hotel'], 43.6000, 1.4500),
    $L(7, 'Sans coords', ['hotel'], null, null),
    $L(8, 'Mairie G', ['lieux-dinformations'], 43.2662, 2.9040),
    $L(9, 'Port H', ['ports', 'nautique'], 43.2650, 2.9030), // cat_slugs incluye las categorías madre
];
$g = canal_etape_groups($listings, 43.26642, 2.90414, 5.0);
check($g['bateau']['count'] === 2 && $g['dormir']['count'] === 2 && $g['voir']['count'] === 1, 'grupos: conteos por tipo dentro del radio');
check(!isset($g['manger']) && !isset($g['velo']), 'grupos: sin grupos vacíos');
check($g['dormir']['items'][0]['title'] === 'Hôtel C' && $g['dormir']['items'][0]['distance_km'] < 0.1, 'grupos: ordenados por distancia');
check(count($g['eau']['items']) === 2 && $g['eau']['items'][0]['title'] === 'Port H', 'grupos: esclusas y puertos aparte, aunque su categoría madre sea náutica');
check(canal_etape_count($g) === 5, 'grupos: total de prestatarios');

// FAQ con datos (sin datos, sin pregunta).
$faq = canal_etape_faq(canal_etape_find('le-somail'), $g);
$qs = array_column($faq, 'q');
check($qs[0] === 'Où se trouve Le Somail sur le Canal du Midi ?' && strpos($faq[0]['a'], '166 km de Toulouse') !== false, 'faq: ubicación');
check(in_array('Combien de temps pour aller du Somail à Capestang en bateau ?', $qs, true), 'faq: tiempo hasta la etapa siguiente (« du Somail »)');
check(in_array('Où louer un bateau au Somail ?', $qs, true) && strpos($faq[2]['a'], 'Loueur A et Loueur B') !== false, 'faq: loueurs por nombre');
check(in_array('Où dormir au Somail ?', $qs, true), 'faq: alojamiento');
$faqRobine = canal_etape_faq(canal_etape_find('narbonne'), []);
check(count($faqRobine) === 1, 'faq: sin prestatarios ni vecinos → solo la ubicación');

check(canal_etape_title(canal_etape_find('capestang')) === 'Capestang — étape du Canal du Midi : que faire, où dormir, distances', 'title');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
