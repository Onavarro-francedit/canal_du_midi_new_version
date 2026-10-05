<?php
/**
 * Villes & étapes 2026 (TASK-060): datos y funciones puras, con test en tests/test-etape.php.
 * Todo sale de datos del sitio (PK y tiempos del calcul, prestatarios de la carte): sin textos redactados.
 * Spec: docs/superpowers/specs/2026-10-05-etapes-2026-design.md
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

// CANAL_ETAPE_PATH / CANAL_ETAPES_PATH ('/etape-2026/' en privado, '/etape/' publicado) los define live.php.

// Etapas: 'calcul' = lugar de CANAL_CALCUL_TOWNS (solo Canal du Midi); lat/lng = ficha del puerto (o centro); radius en km;
// 'de' / 'a' = formas con artículo si no son « de X » / « à X »; 'pages' = páginas del sitio sobre la ciudad.
const CANAL_ETAPES = [
    ['slug' => 'toulouse', 'name' => 'Toulouse', 'canal' => 'midi', 'calcul' => 'Toulouse', 'lat' => 43.59609, 'lng' => 1.45568, 'radius' => 6, 'search' => 'Toulouse', 'dept' => 'Haute-Garonne', 'pages' => ['visiter-toulouse']],
    ['slug' => 'ramonville', 'name' => 'Ramonville-Saint-Agne', 'canal' => 'midi', 'calcul' => 'Ramonville', 'lat' => 43.54166, 'lng' => 1.49029, 'radius' => 4, 'search' => 'Ramonville', 'dept' => 'Haute-Garonne', 'pages' => []],
    ['slug' => 'port-lauragais', 'name' => 'Port-Lauragais', 'canal' => 'midi', 'calcul' => 'Port-Lauragais', 'lat' => 43.35382, 'lng' => 1.80408, 'radius' => 6, 'search' => 'Avignonet', 'dept' => 'Haute-Garonne', 'pages' => []],
    ['slug' => 'castelnaudary', 'name' => 'Castelnaudary', 'canal' => 'midi', 'calcul' => 'Castelnaudary', 'lat' => 43.31557, 'lng' => 1.95404, 'radius' => 5, 'search' => 'Castelnaudary', 'dept' => 'Aude', 'pages' => ['visiter-castelnaudary']],
    ['slug' => 'bram', 'name' => 'Bram', 'canal' => 'midi', 'calcul' => 'Bram', 'lat' => 43.25530, 'lng' => 2.12701, 'radius' => 5, 'search' => 'Bram', 'dept' => 'Aude', 'pages' => []],
    ['slug' => 'carcassonne', 'name' => 'Carcassonne', 'canal' => 'midi', 'calcul' => 'Carcassonne', 'lat' => 43.21742, 'lng' => 2.35070, 'radius' => 6, 'search' => 'Carcassonne', 'dept' => 'Aude', 'pages' => ['visiter-carcassonne']],
    ['slug' => 'trebes', 'name' => 'Trèbes', 'canal' => 'midi', 'calcul' => 'Trèbes', 'lat' => 43.20980, 'lng' => 2.44460, 'radius' => 5, 'search' => 'Trèbes', 'dept' => 'Aude', 'pages' => []],
    ['slug' => 'homps', 'name' => 'Homps', 'canal' => 'midi', 'calcul' => 'Homps', 'lat' => 43.26839, 'lng' => 2.71582, 'radius' => 5, 'search' => 'Homps', 'dept' => 'Aude', 'de' => "d'Homps", 'pages' => []],
    ['slug' => 'argens-minervois', 'name' => 'Argens-Minervois', 'canal' => 'midi', 'calcul' => 'Argens-Minervois', 'lat' => 43.24126, 'lng' => 2.76839, 'radius' => 4, 'search' => 'Argens', 'dept' => 'Aude', 'pages' => []],
    ['slug' => 'le-somail', 'name' => 'Le Somail', 'canal' => 'midi', 'calcul' => 'Le Somail', 'lat' => 43.26642, 'lng' => 2.90414, 'radius' => 5, 'search' => 'Somail', 'dept' => 'Aude', 'de' => 'du Somail', 'a' => 'au Somail', 'pages' => []],
    ['slug' => 'capestang', 'name' => 'Capestang', 'canal' => 'midi', 'calcul' => 'Capestang', 'lat' => 43.33159, 'lng' => 3.04325, 'radius' => 5, 'search' => 'Capestang', 'dept' => 'Hérault', 'pages' => ['capestang']],
    ['slug' => 'poilhes', 'name' => 'Poilhes', 'canal' => 'midi', 'calcul' => 'Poilhes', 'lat' => 43.30813, 'lng' => 3.07891, 'radius' => 4, 'search' => 'Poilhes', 'dept' => 'Hérault', 'pages' => ['poilhes']],
    ['slug' => 'colombiers', 'name' => 'Colombiers', 'canal' => 'midi', 'calcul' => 'Colombiers', 'lat' => 43.31407, 'lng' => 3.14273, 'radius' => 4, 'search' => 'Colombiers', 'dept' => 'Hérault', 'pages' => ['colombiers', 'tunnel-du-malpas']],
    ['slug' => 'beziers', 'name' => 'Béziers', 'canal' => 'midi', 'calcul' => 'Béziers', 'lat' => 43.33381, 'lng' => 3.22107, 'radius' => 6, 'search' => 'Béziers', 'dept' => 'Hérault', 'pages' => ['visiter-beziers', 'villeneuve-les-beziers']],
    ['slug' => 'portiragnes', 'name' => 'Portiragnes', 'canal' => 'midi', 'calcul' => 'Portiragnes (Cassafières)', 'lat' => 43.29220, 'lng' => 3.37106, 'radius' => 5, 'search' => 'Portiragnes', 'dept' => 'Hérault', 'pages' => []],
    ['slug' => 'agde', 'name' => 'Agde', 'canal' => 'midi', 'calcul' => 'Agde', 'lat' => 43.32007, 'lng' => 3.46471, 'radius' => 6, 'search' => 'Agde', 'dept' => 'Hérault', 'pages' => ['visiter-cap-dagde', 'ouvrage-sur-le-libron']],
    ['slug' => 'marseillan', 'name' => 'Marseillan', 'canal' => 'midi', 'calcul' => 'Étang de Thau (Les Onglous)', 'lat' => 43.34017, 'lng' => 3.53862, 'radius' => 7, 'search' => 'Marseillan', 'dept' => 'Hérault', 'pages' => ['visiter-marseillan', 'bassin-de-thau', 'visiter-sete']],
    ['slug' => 'salleles-daude', 'name' => "Sallèles-d'Aude", 'canal' => 'robine', 'calcul' => '', 'lat' => 43.25840, 'lng' => 2.94921, 'radius' => 4, 'search' => 'Sallèles', 'dept' => 'Aude', 'pages' => ['canal-de-la-robine']],
    ['slug' => 'narbonne', 'name' => 'Narbonne', 'canal' => 'robine', 'calcul' => '', 'lat' => 43.18440, 'lng' => 3.00390, 'radius' => 6, 'search' => 'Narbonne', 'dept' => 'Aude', 'pages' => ['visiter-narbonne', 'canal-de-la-robine']],
    ['slug' => 'port-la-nouvelle', 'name' => 'Port-la-Nouvelle', 'canal' => 'robine', 'calcul' => '', 'lat' => 43.01930, 'lng' => 3.04530, 'radius' => 6, 'search' => 'Port-la-Nouvelle', 'dept' => 'Aude', 'pages' => ['canal-de-la-robine']],
];

// Grupos de prestatarios por categoría de la carte (slugs de job_listing_category). 'eau' = esclusas y puertos.
const CANAL_ETAPE_GROUPS = [
    'bateau' => ['Louer un bateau', ['location-bateau', 'croisiere-bateau', 'nautique', 'location-de-canoe-kayak', 'peniche', 'bateau-restaurant'], 'location-bateau'],
    'dormir' => ['Où dormir', ['hotel', 'camping', 'gites', 'chambre-dhotes', 'location-saisonniere', 'appartement-hotel', 'appartement-maison-a-louer', 'chambre-a-louer', 'auberge-collective', 'auberge-de-jeunesse', 'hostel', 'insolite', 'roulotte'], 'hebergement'],
    'manger' => ['Où manger', ['restaurant', 'restauration-2', 'brasserie-snack', 'bar', 'table-dhote', 'commerce-alimentaire', 'boulangerie-patisserie', 'produits-regionaux', 'supermarche-epicerie', 'vente-de-vins'], 'restauration'],
    'velo'   => ['À vélo', ['location-de-velo', 'organisation-de-voyage-a-velo', 'velo', 'transfert-de-bagages'], 'location-de-velo'],
    'voir'   => ['À voir, à faire', ['site-et-monument', 'musees', 'moulins', 'excursions', 'oenotourisme', 'chateaux', 'loisir-de-plein-air', 'lieux-dinformations', 'shopping', 'artisanat', 'librairie', 'commerce', 'services'], 'voir-visiter'],
    'eau'    => ['Écluses et ports', ['ecluses', 'ports', 'ports-fluviaux', 'halte-nautique'], ''],
];
const CANAL_ETAPE_GROUP_MAX = 4;

function canal_etape_find(string $slug): ?array
{
    foreach (CANAL_ETAPES as $e) {
        if ($e['slug'] === $slug) {
            return $e;
        }
    }
    return null;
}

/** Etapa anterior y siguiente sobre el Canal du Midi (la Robine no tiene orden en el calcul). */
function canal_etape_neighbors(string $slug): array
{
    $midi = array_values(array_filter(CANAL_ETAPES, function ($e) { return $e['canal'] === 'midi'; }));
    foreach ($midi as $i => $e) {
        if ($e['slug'] === $slug) {
            return ['prev' => $midi[$i - 1] ?? null, 'next' => $midi[$i + 1] ?? null];
        }
    }
    return ['prev' => null, 'next' => null];
}

function canal_etape_de(array $e): string
{
    return $e['de'] ?? ((preg_match('/^[AEIOUYÉÈ]/u', $e['name']) ? "d'" : 'de ') . $e['name']);
}

function canal_etape_a(array $e): string
{
    return $e['a'] ?? ('à ' . $e['name']);
}

function canal_etape_pk(array $e): ?float
{
    return $e['calcul'] !== '' ? canal_calcul_pk($e['calcul']) : null;
}

function canal_etape_pk_label(float $pk): string
{
    return 'PK ' . (fmod($pk, 1.0) === 0.0 ? (string) (int) $pk : number_format($pk, 1, ',', ''));
}

/** Frase de cabecera, solo con datos. */
function canal_etape_lead(array $e): string
{
    if ($e['canal'] === 'robine') {
        return 'Sur le canal de la Robine, la branche du Canal du Midi qui rejoint la Méditerranée par Narbonne et Port-la-Nouvelle.';
    }
    $n = canal_etape_neighbors($e['slug']);
    $pk = canal_etape_pk_label((float) canal_etape_pk($e)) . ' du Canal du Midi, ';
    if ($n['prev'] && $n['next']) {
        return $pk . 'entre ' . $n['prev']['name'] . ' et ' . $n['next']['name'] . '.';
    }
    return $n['next'] ? $pk . 'point de départ du canal, avant ' . $n['next']['name'] . '.' : $pk . "arrivée du canal sur l'étang de Thau, après " . $n['prev']['name'] . '.';
}

/** Prestatarios del radio agrupados por tipo (cada uno en su primer grupo), los más cercanos primero. */
function canal_etape_groups(array $listings, float $lat, float $lng, float $radius): array
{
    $out = [];
    foreach ($listings as $item) {
        if (!canal_fiche_has_coords($item['lat'] ?? null, $item['lng'] ?? null)) {
            continue;
        }
        $km = canal_fiche_distance_km($lat, $lng, (float) $item['lat'], (float) $item['lng']);
        if ($km > $radius) {
            continue;
        }
        // Esclusas y puertos primero: cat_slugs incluye las categorías madre y la suya es náutica.
        foreach (['eau' => CANAL_ETAPE_GROUPS['eau']] + CANAL_ETAPE_GROUPS as $key => $g) {
            if (array_intersect($item['cat_slugs'] ?? [], $g[1])) {
                $item['distance_km'] = $km;
                $out[$key][] = $item;
                break;
            }
        }
    }
    $groups = [];
    foreach (CANAL_ETAPE_GROUPS as $key => $g) {
        if (empty($out[$key])) {
            continue;
        }
        usort($out[$key], function ($a, $b) { return $a['distance_km'] <=> $b['distance_km']; });
        $groups[$key] = ['label' => $g[0], 'type' => $g[2], 'count' => count($out[$key]), 'items' => $key === 'eau' ? $out[$key] : array_slice($out[$key], 0, CANAL_ETAPE_GROUP_MAX)];
    }
    return $groups;
}

/** Prestatarios (sin esclusas ni puertos). */
function canal_etape_count(array $groups): int
{
    $n = 0;
    foreach ($groups as $key => $g) {
        $n += $key === 'eau' ? 0 : $g['count'];
    }
    return $n;
}

function canal_etape_list(array $names): string
{
    $last = array_pop($names);
    return $names ? implode(', ', $names) . ' et ' . $last : (string) $last;
}

/** Preguntas frecuentes con respuestas calculadas; sin datos, no hay pregunta. */
function canal_etape_faq(array $e, array $groups): array
{
    $at = canal_etape_a($e);
    $faq = [];
    if ($e['canal'] === 'midi') {
        $pk = (float) canal_etape_pk($e);
        $n = canal_etape_neighbors($e['slug']);
        $where = canal_etape_pk_label($pk) . ', à ' . (int) round($pk) . ' km de Toulouse par le canal';
        $between = $n['prev'] && $n['next'] ? ', entre ' . $n['prev']['name'] . ' et ' . $n['next']['name'] : '';
        $faq[] = ['q' => 'Où se trouve ' . $e['name'] . ' sur le Canal du Midi ?', 'a' => $e['name'] . ' se trouve au ' . $where . $between . ', dans le département ' . (in_array($e['dept'], ['Aude', 'Hérault'], true) ? 'de l’' : 'de la ') . $e['dept'] . '.'];
        if ($n['next']) {
            $r = canal_calcul_compute($pk, (float) canal_etape_pk($n['next']));
            $faq[] = [
                'q' => 'Combien de temps pour aller ' . canal_etape_de($e) . ' à ' . $n['next']['name'] . ' en bateau ?',
                'a' => 'Comptez environ ' . canal_calcul_duration($r['boat']) . ' de navigation pour les ' . (int) round($r['km']) . ' km'
                    . ($r['sites'] ? ' et ' . $r['sites'] . ' écluse' . ($r['sites'] > 1 ? 's' : '') : '') . ' jusqu’à ' . $n['next']['name']
                    . ', et ' . canal_calcul_duration($r['bike']) . ' à vélo par le chemin de halage.',
            ];
        }
    } else {
        $faq[] = ['q' => 'Où se trouve ' . $e['name'] . ' ?', 'a' => $e['name'] . ' se trouve sur le canal de la Robine, la branche du Canal du Midi qui rejoint la Méditerranée par Narbonne et Port-la-Nouvelle, dans le département de l’Aude.'];
    }
    if (!empty($groups['bateau'])) {
        $names = array_slice(array_column($groups['bateau']['items'], 'title'), 0, 3);
        $faq[] = ['q' => 'Où louer un bateau ' . $at . ' ?', 'a' => $groups['bateau']['count'] . ' loueur' . ($groups['bateau']['count'] > 1 ? 's et compagnies' : ' ou compagnie') . ' de croisière ' . ($groups['bateau']['count'] > 1 ? 'sont référencés' : 'est référencé') . ' à proximité : ' . canal_etape_list($names) . '.'];
    }
    if (!empty($groups['dormir'])) {
        $c = $groups['dormir']['count'];
        $faq[] = ['q' => 'Où dormir ' . $at . ' ?', 'a' => $c . ' hébergement' . ($c > 1 ? 's sont référencés' : ' est référencé') . ' à moins de ' . $e['radius'] . ' km, dont ' . canal_etape_list(array_slice(array_column($groups['dormir']['items'], 'title'), 0, 3)) . '.'];
    }
    return $faq;
}

function canal_etape_title(array $e): string
{
    return $e['name'] . ($e['canal'] === 'midi' ? ' — étape du Canal du Midi' : ' — canal de la Robine') . ' : que faire, où dormir, distances';
}
