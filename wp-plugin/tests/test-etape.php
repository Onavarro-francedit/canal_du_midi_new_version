<?php
// Tests de etape-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-etape.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/live.php';
canal_2026_define_paths(false);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/fiche-core.php';
require __DIR__ . '/../canal-home/includes/calcul-core.php';
require __DIR__ . '/../canal-home/includes/etape-core.php';
require __DIR__ . '/../canal-home/includes/guide-core.php';

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

// M5 (TASK-066): « À voir » por etapa, solo con datos.
check(canal_etape_locks_notable(206.0, 2.0) === ['Écluses de Fonseranes (8 sas)'], 'esclusas notables: Fonseranes cerca de Béziers');
check(canal_etape_locks_notable(145.2, 3.0) === [], 'esclusas notables: ninguna de 3 sas o más cerca de Homps');
check(canal_etape_locks_notable(null, 3.0) === [], 'esclusas notables: Robine sin PK');
$voirGroups = ['voir' => ['items' => [
    ['title' => 'Château et Remparts de la Cité', 'cat_slugs' => ['chateaux', 'voir-visiter']],
    ['title' => 'Mairie de Trèbes', 'cat_slugs' => ['lieux-dinformations']],
    ['title' => 'Moulin', 'cat_slugs' => ['moulins']],
]]];
$h = canal_etape_highlights(canal_etape_find('trebes'), $voirGroups);
check($h === ['Château et Remparts de la Cité', 'Moulin', 'Écluse de Trèbes (3 sas)'], 'à voir: monumentos (sin mairies) y esclusas notables');
check(canal_etape_highlights(canal_etape_find('homps'), []) === [], 'à voir: sin datos → vacío');

// Parcours (rediseño de /etapes/): menú de wp-admin → parcours, cifras del calcul.
$items = [
    ['title' => 'Aller simple', 'url' => 'https://x.fr/calcul/?de=Castelnaudary&a=Homps', 'classes' => ['bateau', 'semaine']],
    ['title' => 'Aller-retour', 'url' => 'https://x.fr/calcul/?de=homps&a=le%20somail', 'classes' => ['bateau', 'weekend', 'aller-retour']],
    ['title' => 'Mal', 'url' => 'https://x.fr/calcul/?de=Paris&a=Homps', 'classes' => ['bateau', 'jour']],
    ['title' => 'Sin modo', 'url' => 'https://x.fr/calcul/?de=Homps&a=Agde', 'classes' => ['semaine']],
];
$parsed = canal_parcours_parse($items);
check(count($parsed) === 2, 'parcours: menú → 2 válidos (ciudad desconocida y sin modo fuera)');
check($parsed[1]['de'] === 'Homps' && $parsed[1]['a'] === 'Le Somail' && $parsed[1]['retour'] === true, 'parcours: nombres del calcul y aller-retour');
check(count(canal_parcours_parse(CANAL_PARCOURS_DEFAULT)) === count(CANAL_PARCOURS_DEFAULT), 'parcours: los de por defecto son válidos');
$c = canal_parcours_card($parsed[0]);
check($c['title'] === 'Castelnaudary → Homps' && $c['chips'] === ['80 km', '30 écluses · 45 sas', '19 h', '≈ 4 jours de navigation'], 'parcours: barco, cifras del calcul');
check(array_column($c['via'], 'name') === ['Bram', 'Carcassonne', 'Trèbes'], 'parcours: etapas intermedias en orden');
check($c['from']['slug'] === 'castelnaudary', 'parcours: etapa de salida');
$r = canal_parcours_card($parsed[1]);
check($r['title'] === 'Homps → Le Somail et retour' && $r['chips'][0] === '41 km' && $r['chips'][2] === '7 h 50', 'parcours: aller-retour dobla km y tiempo');
$v = canal_parcours_card(canal_parcours_parse([['title' => 'x', 'url' => '?de=Toulouse&a=Castelnaudary', 'classes' => ['velo', 'weekend']]])[0]);
check($v['chips'] === ['65 km', '4 h 20 à vélo', '≈ 2 jours'], 'parcours: bici, km y días');

// Parcours calculados (todas las salidas) y modo « à pied ».
$pied = canal_parcours_card(canal_parcours_parse([['title' => 'x', 'url' => '?de=Carcassonne&a=Trèbes', 'classes' => ['pied', 'jour']]])[0]);
check($pied['chips'] === ['12 km', '3 h 10 à pied', 'dans la journée'], 'parcours: à pied, km y días');
check(count(array_filter(canal_parcours_parse(CANAL_PARCOURS_DEFAULT), function ($p) { return $p['mode'] === 'pied'; })) >= 3, 'parcours: hay propuestas à pied por defecto');
check(canal_parcours_duree('bateau', canal_calcul_compute(165.6, 188.4)) === 'jour', 'duración: Le Somail → Capestang en barco, un día');
check(canal_parcours_duree('velo', canal_calcul_compute(105.1, 165.6)) === 'weekend', 'duración: Carcassonne → Le Somail en bici, fin de semana');
check(canal_parcours_duree('velo', canal_calcul_compute(0.0, 240.5)) === 'semaine', 'duración: el canal entero en bici, semana');
check(canal_parcours_duree('pied', canal_calcul_compute(0.0, 12.3)) === 'jour' && canal_parcours_duree('pied', canal_calcul_compute(0.0, 240.5)) === null, 'duración: a pie, 12 km un día; 240 km no cabe');
$all = canal_parcours_all();
$fromCarca = array_filter($all, function ($r) { return ($r['a'] === 5 || $r['b'] === 5) && $r['mode'] === 'velo' && $r['duree'] === 'weekend'; });
check(count($fromCarca) >= 2, 'todos: desde Carcassonne en bici un fin de semana hay varios (' . count($fromCarca) . ')');
$one = array_values($all)[0];
check(isset($one['a'], $one['b'], $one['mode'], $one['duree'], $one['chips'], $one['km']) && $one['a'] < $one['b'], 'todos: entrada compacta, una por par y modo');

// Botón de la carte: con el número real de fichas; sin fichas, sin botón.
check(canal_parcours_carte_label('bateau', 1, 'à Castelnaudary') === '1 loueur à Castelnaudary', 'botón: 1 loueur');
check(canal_parcours_carte_label('velo', 3, 'au Somail') === '3 loueurs de vélos au Somail', 'botón: vélos en plural');
check(canal_parcours_carte_label('pied', 2, 'à Homps') === '2 hébergements à Homps', 'botón: a pie → hébergements');
check(canal_parcours_carte_label('bateau', 0, 'à Toulouse') === null, 'botón: sin fichas → sin botón');

// FAQ de /etapes/: preguntas de Search Console (12 meses), respuestas con las cifras del calcul.
$split = canal_etapes_split(5);
check(count($split) === 5 && $split[0]['from'] === 'Toulouse' && end($split)['to'] === 'Marseillan', 'reparto: 5 días de Toulouse a Marseillan');
check(abs(array_sum(array_column($split, 'km')) - 240.5) < 0.6, 'reparto: suma 240,5 km');
check(max(array_column(canal_etapes_split(3), 'km')) <= 100, 'reparto: 3 días sin jornadas de más de 100 km');
$faq = canal_etapes_search_faq();
$qs = array_column($faq, 'q');
check(count($faq) === 6, 'faq: 6 preguntas');
check(in_array('Combien de temps pour faire le Canal du Midi en bateau ?', $qs, true), 'faq: pregunta literal de Search Console (bateau)');
$velo = $faq[array_search('Faire le Canal du Midi à vélo en 3, 4 ou 5 jours : quelles étapes ?', $qs, true)]['a'];
check(strpos($velo, 'En 5 jours') !== false && strpos($velo, 'Toulouse → ') !== false && strpos($velo, ' km') !== false, 'faq: étapes en bici calculadas');
$permis = $faq[array_search('Peut-on louer un bateau sans permis sur le Canal du Midi ?', $qs, true)]['a'];
check(strpos($permis, 'Oui. ') === 0 && strpos($permis, '8 km/h') !== false, 'faq: sans permis → « Oui » + la regla de la fuente');
foreach ($faq as $qa) {
    check(substr($qa['q'], -2) === ' ?' && strlen($qa['a']) > 60, 'faq: « ' . $qa['q'] . ' » con respuesta');
}

// T2 Sallèles-d'Aude: canal de jonction (7 écluses), « Que voir », enlace ficha → etapa.
$sal = canal_etape_find('salleles-daude');
check(strpos(canal_etape_lead($sal), 'canal de jonction') !== false && strpos(canal_etape_lead($sal), '7 écluses') !== false, 'Sallèles: cabecera en el canal de jonction, 7 esclusas');
check(strpos(canal_etape_lead(canal_etape_find('narbonne')), 'canal de la Robine') !== false, 'Narbonne: sigue en la Robine');
$lock = function (string $slug, string $title) { return ['slug' => $slug, 'title' => $title, 'url' => '/fiche/' . $slug . '/', 'distance_km' => 1.0, 'cat_slugs' => ['ecluses']]; };
$salGroups = [
    'eau'  => ['items' => [$lock('ecluse-de-gailhousty', 'Écluse de Gailhousty'), $lock('port-de-salleles-daude', 'Port de Sallèles d’Aude'), $lock('ecluse-de-cesse', 'Écluse de Cesse'),
        $lock('ecluse-de-salleles', 'Écluse de Sallèles-d’Aude'), $lock('ecluse-de-truilhas', 'Écluse de Truilhas'), $lock('ecluse-dempare', 'Écluse d’Empare'),
        $lock('ecluse-dargelliers', 'Écluse d’Argelliers'), $lock('ecluse-de-saint-cyr', 'Écluse de Saint-Cyr'), $lock('ecluse-de-moussoulens', 'Écluse de Moussoulens')]],
    'voir' => ['items' => [['title' => 'Vélorail', 'distance_km' => 0.4, 'cat_slugs' => ['loisir-de-plein-air']], ['title' => 'Office de Tourisme', 'distance_km' => 3.7, 'cat_slugs' => ['lieux-dinformations']]]],
];
$j = canal_etape_jonction($salGroups);
check(array_column($j, 'slug') === CANAL_ETAPE_JONCTION && count($j) === 7, 'jonction: las 7 esclusas en orden de paso, sin Moussoulens (Robine)');
check(canal_etape_jonction(['eau' => ['items' => [$lock('ecluse-de-cesse', 'Écluse de Cesse')]]]) === [], 'jonction: incompleta → nada');
$salFaq = canal_etape_faq($sal, $salGroups);
$voir = array_values(array_filter($salFaq, function ($qa) { return strpos($qa['q'], 'Que voir') === 0; }));
check(count($voir) === 1 && strpos($voir[0]['a'], 'Cesse, Truilhas, Empare, Argelliers, Saint-Cyr, Sallèles et Gailhousty') !== false && strpos($voir[0]['a'], 'Vélorail') !== false && strpos($voir[0]['a'], 'Office') === false, 'Sallèles: « Que voir » con las 7 esclusas y el Vélorail, sin la oficina de turismo');
check(canal_etape_nearest(43.2573, 2.9498)['slug'] === 'salleles-daude' && canal_etape_nearest(43.6, 1.45)['slug'] === 'toulouse' && canal_etape_nearest(48.85, 2.35) === null, 'ficha → etapa más cercana dentro de su radio; París → ninguna');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
