<?php
/**
 * Calcul de distance 2026 (TASK-057): datos y funciones puras (sin WordPress), con test en tests/test-calcul.php.
 * Spec: docs/superpowers/specs/2026-10-05-calcul-distance-2026-design.md · benchmark: docs/calcul-distance-benchmark-2026-10-05.md
 * Sin conexión a Pimcore: los PK de las esclusas son los del calculador actual (pimcore.object_store_65, copiados el
 * 2026-10-05) + Fonseranes; los de ciudades y puertos, del trazado OSM (relación 302044) recalado en las 56 esclusas
 * con ficha (desfase ≤ 0,5 km; 1,5 km tras el Grand Bief).
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

// Página actual del calculador; la versión 2026 es /<slug>-2026/ (calcul-route.php).
const CANAL_CALCUL_SLUG = 'calcul-de-distance-canal-du-midi';

// Modelo de tiempos (CanalPlanAC / loueurs; calibrado con Le Boat: Castelnaudary → Trèbes 13 h publicadas).
const CANAL_CALCUL_BOAT_KMH = 7;
const CANAL_CALCUL_MIN_PER_SAS = 10;
const CANAL_CALCUL_BOAT_HOURS_DAY = 6;   // horario de esclusas 9 h–19 h con pausa
const CANAL_CALCUL_BIKE_KMH = 15;        // referencia de France Vélo Tourisme
const CANAL_CALCUL_BIKE_KM_ONE_DAY = 60;
const CANAL_CALCUL_BIKE_KM_DAY = 55;
const CANAL_CALCUL_WALK_KMH = 4;
const CANAL_CALCUL_WALK_KM_DAY = 20;

// Ciudades y puertos: 'search' = texto de búsqueda de la carte; 'de' / 'a' = formas con artículo si no son « de X » / « à X ».
const CANAL_CALCUL_TOWNS = [
    ['name' => 'Toulouse', 'pk' => 0.0, 'search' => 'Toulouse'],
    ['name' => 'Toulouse Port Saint-Sauveur', 'pk' => 5.4, 'search' => 'Toulouse'],
    ['name' => 'Ramonville', 'pk' => 12.3, 'search' => 'Ramonville'],
    ['name' => 'Castanet-Tolosan', 'pk' => 15.7, 'search' => 'Castanet'],
    ['name' => 'Montgiscard', 'pk' => 24.9, 'search' => 'Montgiscard'],
    ['name' => 'Port-Lauragais', 'pk' => 50.2, 'search' => 'Avignonet'],
    ['name' => 'Seuil de Naurouze', 'pk' => 51.6, 'search' => 'Montferrand', 'de' => 'du Seuil de Naurouze', 'a' => 'au Seuil de Naurouze'],
    ['name' => 'Castelnaudary', 'pk' => 64.9, 'search' => 'Castelnaudary'],
    ['name' => 'Bram', 'pk' => 80.9, 'search' => 'Bram'],
    ['name' => 'Carcassonne', 'pk' => 105.1, 'search' => 'Carcassonne'],
    ['name' => 'Trèbes', 'pk' => 117.5, 'search' => 'Trèbes'],
    ['name' => 'Marseillette', 'pk' => 127.2, 'search' => 'Marseillette'],
    ['name' => 'Puichéric', 'pk' => 136.4, 'search' => 'Puichéric'],
    ['name' => 'Homps', 'pk' => 145.2, 'search' => 'Homps', 'de' => "d'Homps"],
    ['name' => 'Argens-Minervois', 'pk' => 151.4, 'search' => 'Argens'],
    ['name' => 'Le Somail', 'pk' => 165.6, 'search' => 'Somail', 'de' => 'du Somail', 'a' => 'au Somail'],
    ['name' => 'Capestang', 'pk' => 188.4, 'search' => 'Capestang'],
    ['name' => 'Poilhes', 'pk' => 194.0, 'search' => 'Poilhes'],
    ['name' => 'Colombiers', 'pk' => 200.6, 'search' => 'Colombiers'],
    ['name' => 'Béziers', 'pk' => 208.4, 'search' => 'Béziers'],
    ['name' => 'Portiragnes (Cassafières)', 'pk' => 222.4, 'search' => 'Portiragnes'],
    ['name' => 'Agde', 'pk' => 231.2, 'search' => 'Agde'],
    ['name' => 'Étang de Thau (Les Onglous)', 'pk' => 240.5, 'search' => 'Marseillan', 'de' => "de l'étang de Thau (Les Onglous)", 'a' => "à l'étang de Thau (Les Onglous)"],
];

// Tabla de distancias: ciudades principales (nombres de CANAL_CALCUL_TOWNS).
const CANAL_CALCUL_MATRIX = ['Toulouse', 'Castelnaudary', 'Carcassonne', 'Trèbes', 'Homps', 'Le Somail', 'Capestang', 'Béziers', 'Agde', 'Étang de Thau (Les Onglous)'];

// 63 esclusas: PK, sas (cámaras: una escala de 3 = 3), nombre (título de la ficha) y slug de la ficha. Todas tienen ficha
// (los nombres de Pimcore « Laplanque », « Guilhermin », « Ladouce »… son los de las fichas: la Planque, Guillermin, la Douce…).
const CANAL_CALCUL_LOCKS = [
    ['pk' => 1.11, 'sas' => 1, 'name' => 'Écluse du Béarnais', 'slug' => 'ecluse-du-bearnais'],
    ['pk' => 2.05, 'sas' => 1, 'name' => 'Écluse des Minimes', 'slug' => 'ecluse-des-minimes'],
    ['pk' => 3.72, 'sas' => 1, 'name' => 'Écluse Bayard', 'slug' => 'ecluse-bayard'],
    ['pk' => 15.73, 'sas' => 1, 'name' => 'Écluse de Castanet', 'slug' => 'ecluse-de-castanet'],
    ['pk' => 17.45, 'sas' => 1, 'name' => 'Écluse de Vic', 'slug' => 'ecluse-de-vic'],
    ['pk' => 24.94, 'sas' => 2, 'name' => 'Écluse de Montgiscard', 'slug' => 'ecluse-de-montgiscard'],
    ['pk' => 28.11, 'sas' => 1, 'name' => 'Écluse d\'Ayguesvives', 'slug' => 'ecluse-dayguesvives'],
    ['pk' => 29.66, 'sas' => 2, 'name' => 'Écluse du Sanglier', 'slug' => 'ecluse-du-sanglier'],
    ['pk' => 33.3, 'sas' => 1, 'name' => 'Écluse de Négra', 'slug' => 'ecluse-de-negra'],
    ['pk' => 37.51, 'sas' => 2, 'name' => 'Écluse de Laval', 'slug' => 'ecluse-de-laval'],
    ['pk' => 38.96, 'sas' => 1, 'name' => 'Écluse de Gardouch', 'slug' => 'ecluse-de-gardouch'],
    ['pk' => 43.05, 'sas' => 1, 'name' => 'Écluse de Renneville', 'slug' => 'ecluse-de-renneville'],
    ['pk' => 45.92, 'sas' => 2, 'name' => 'Écluse d\'Encassan', 'slug' => 'ecluse-dencassan'],
    ['pk' => 47.51, 'sas' => 1, 'name' => 'Écluse d\'Emborrel', 'slug' => 'ecluse-demborrel'],
    ['pk' => 51.632, 'sas' => 1, 'name' => 'Écluse de l\'Océan', 'slug' => 'ecluse-de-locean'],
    ['pk' => 56.631, 'sas' => 1, 'name' => 'Écluse de la Méditerranée', 'slug' => 'ecluse-de-la-mediterranee'],
    ['pk' => 57.503, 'sas' => 2, 'name' => 'Écluse du Roc', 'slug' => 'ecluse-du-roc'],
    ['pk' => 58.741, 'sas' => 3, 'name' => 'Écluse de Laurens', 'slug' => 'ecluse-de-laurens'],
    ['pk' => 59.701, 'sas' => 1, 'name' => 'Écluse de la Domergue', 'slug' => 'ecluse-de-la-domergue'],
    ['pk' => 60.918, 'sas' => 1, 'name' => 'Écluse de la Planque', 'slug' => 'ecluse-de-la-planque'],
    ['pk' => 65.597, 'sas' => 4, 'name' => 'Écluses de Saint-Roch', 'slug' => 'ecluses-de-saint-roch'],
    ['pk' => 67.068, 'sas' => 2, 'name' => 'Écluse de Gay', 'slug' => 'ecluse-de-gay'],
    ['pk' => 68.716, 'sas' => 3, 'name' => 'Écluse du Vivier', 'slug' => 'ecluse-du-vivier'],
    ['pk' => 69.135, 'sas' => 1, 'name' => 'Écluse de Guillermin', 'slug' => 'ecluse-de-guillermin'],
    ['pk' => 69.672, 'sas' => 1, 'name' => 'Écluse de Saint-Sernin', 'slug' => 'ecluse-de-saint-sernin'],
    ['pk' => 70.564, 'sas' => 1, 'name' => 'Écluse de Guerre', 'slug' => 'ecluse-de-guerre'],
    ['pk' => 71.659, 'sas' => 1, 'name' => 'Écluse de la Peyruque', 'slug' => 'ecluse-de-la-peyruque'],
    ['pk' => 72.161, 'sas' => 1, 'name' => 'Écluse de la Criminelle', 'slug' => 'ecluse-de-la-criminelle'],
    ['pk' => 73.55, 'sas' => 1, 'name' => 'Écluse de Tréboul', 'slug' => 'ecluse-de-treboul'],
    ['pk' => 77.372, 'sas' => 1, 'name' => 'Écluse de Villepinte', 'slug' => 'ecluse-de-villepinte'],
    ['pk' => 79.046, 'sas' => 1, 'name' => 'Écluse de Sauzens', 'slug' => 'ecluse-de-sauzens'],
    ['pk' => 80.256, 'sas' => 1, 'name' => 'Écluse de Bram', 'slug' => 'ecluse-de-bram'],
    ['pk' => 85.874, 'sas' => 1, 'name' => 'Écluse de Béteille', 'slug' => 'ecluse-de-beteille'],
    ['pk' => 93.392, 'sas' => 1, 'name' => 'Écluse de Villeséque', 'slug' => 'ecluse-de-villeseque'],
    ['pk' => 98.215, 'sas' => 2, 'name' => 'Écluse de Lalande', 'slug' => 'ecluse-de-lalande'],
    ['pk' => 98.53, 'sas' => 1, 'name' => 'Écluse d\'Herminis', 'slug' => 'ecluse-dherminis'],
    ['pk' => 99.901, 'sas' => 1, 'name' => 'Écluse de la Douce', 'slug' => 'ecluse-de-la-douce'],
    ['pk' => 105.26, 'sas' => 1, 'name' => 'Écluse de Carcassonne', 'slug' => 'ecluse-de-carcassonne'],
    ['pk' => 107.97, 'sas' => 1, 'name' => 'Écluse de Saint-Jean', 'slug' => 'ecluse-de-saint-jean'],
    ['pk' => 108.75, 'sas' => 2, 'name' => 'Écluse double de Fresquel', 'slug' => 'ecluse-double-de-fresquel'],
    ['pk' => 109.0, 'sas' => 1, 'name' => 'Écluse simple de Fresquel', 'slug' => 'ecluse-simple-de-fresquel'],
    ['pk' => 112.61, 'sas' => 1, 'name' => 'Écluse de l\'Évêque', 'slug' => 'ecluse-de-leveque'],
    ['pk' => 113.4, 'sas' => 1, 'name' => 'Écluse de Villedubert', 'slug' => 'ecluse-de-villedubert'],
    ['pk' => 118.01, 'sas' => 3, 'name' => 'Écluse de Trèbes', 'slug' => 'ecluse-de-trebes'],
    ['pk' => 127.2, 'sas' => 1, 'name' => 'Écluse de Marseillette', 'slug' => 'ecluse-de-marseillette'],
    ['pk' => 130.352, 'sas' => 3, 'name' => 'Écluse de Fontfile', 'slug' => 'ecluse-de-fontfile'],
    ['pk' => 131.594, 'sas' => 2, 'name' => 'Écluse de Saint-Martin', 'slug' => 'ecluse-de-saint-martin'],
    ['pk' => 133.359, 'sas' => 2, 'name' => 'Écluse de l\'Aiguille', 'slug' => 'ecluse-de-laiguille'],
    ['pk' => 136.396, 'sas' => 2, 'name' => 'Écluse de Puichéric', 'slug' => 'ecluse-de-puicheric'],
    ['pk' => 142.7, 'sas' => 1, 'name' => 'Écluse de Jouarres', 'slug' => 'ecluse-de-jouarres'],
    ['pk' => 146.39, 'sas' => 1, 'name' => 'Écluse d\'Homps', 'slug' => 'ecluse-dhomps'],
    ['pk' => 147.08, 'sas' => 2, 'name' => 'Écluse d\'Ognon', 'slug' => 'ecluse-dognon'],
    ['pk' => 149.81, 'sas' => 2, 'name' => 'Écluse de Pechlaurier', 'slug' => 'ecluse-de-pechlaurier'],
    ['pk' => 152.29, 'sas' => 1, 'name' => 'Écluse d\'Argens', 'slug' => 'ecluse-dargens'],
    ['pk' => 206.6, 'sas' => 8, 'name' => 'Écluses de Fonseranes', 'slug' => 'ecluses-de-fonseranes'],
    ['pk' => 208.012, 'sas' => 1, 'name' => 'Écluse de l\'Orb', 'slug' => 'ecluse-de-lorb'],
    ['pk' => 208.39, 'sas' => 1, 'name' => 'Écluse de Béziers', 'slug' => 'ecluse-de-beziers'],
    ['pk' => 212.452, 'sas' => 1, 'name' => 'Écluse d\'Arièges', 'slug' => 'ecluse-darieges'],
    ['pk' => 213.796, 'sas' => 1, 'name' => 'Écluse de Villeneuve', 'slug' => 'ecluse-de-villeneuve'],
    ['pk' => 218.262, 'sas' => 1, 'name' => 'Écluse de Portiragnes', 'slug' => 'ecluse-de-portiragnes'],
    ['pk' => 231.42, 'sas' => 1, 'name' => 'Écluse ronde d\'Agde', 'slug' => 'ecluse-ronde-dagde'],
    ['pk' => 232.04, 'sas' => 1, 'name' => 'Écluse de Prades', 'slug' => 'ecluse-de-prades'],
    ['pk' => 235.3, 'sas' => 1, 'name' => 'Écluse de Bagnas', 'slug' => 'ecluse-de-bagnas'],
];

/** Lugares elegibles: nombre → PK (ciudades primero, luego esclusas). */
function canal_calcul_places(): array
{
    static $places = null;
    if ($places === null) {
        $places = [];
        foreach (CANAL_CALCUL_TOWNS as $t) {
            $places[$t['name']] = $t['pk'];
        }
        foreach (CANAL_CALCUL_LOCKS as $l) {
            $places[$l['name']] = $l['pk'];
        }
    }
    return $places;
}

/** Nombre canónico del lugar escrito por el visitante (sin mayúsculas ni acentos), o null. */
function canal_calcul_find(string $q): ?string
{
    $q = canal_carte_fold(trim($q));
    if ($q === '') {
        return null;
    }
    foreach (array_keys(canal_calcul_places()) as $name) {
        if (canal_carte_fold($name) === $q) {
            return $name;
        }
    }
    return null;
}

function canal_calcul_pk(string $name): float
{
    return (float) (canal_calcul_places()[$name] ?? 0.0);
}

/** Esclusas entre dos PK en el orden de paso: la de salida no cuenta, la de llegada sí. */
function canal_calcul_locks_between(float $a, float $b): array
{
    $lo = min($a, $b);
    $hi = max($a, $b);
    $out = array_values(array_filter(CANAL_CALCUL_LOCKS, function ($l) use ($lo, $hi) {
        return $l['pk'] > $lo + 0.01 && $l['pk'] <= $hi + 0.01;
    }));
    return $a > $b ? array_reverse($out) : $out;
}

function canal_calcul_compute(float $a, float $b): array
{
    $km = abs($b - $a);
    $locks = canal_calcul_locks_between($a, $b);
    $sas = (int) array_sum(array_column($locks, 'sas'));
    return [
        'km'    => $km,
        'sites' => count($locks),
        'sas'   => $sas,
        'boat'  => $km / CANAL_CALCUL_BOAT_KMH + $sas * CANAL_CALCUL_MIN_PER_SAS / 60,
        'bike'  => $km / CANAL_CALCUL_BIKE_KMH,
        'walk'  => $km / CANAL_CALCUL_WALK_KMH,
    ];
}

/** Horas → « 12 h 40 », « 2 h », « 50 min » (redondeo a 10 min). */
function canal_calcul_duration(float $hours): string
{
    $min = (int) round($hours * 6) * 10;
    $h = intdiv($min, 60);
    $m = $min % 60;
    if ($h === 0) {
        return $m . ' min';
    }
    return $h . ' h' . ($m ? ' ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) : '');
}

function canal_calcul_days_boat(float $hours): string
{
    return $hours <= CANAL_CALCUL_BOAT_HOURS_DAY ? 'dans la journée' : '≈ ' . (int) ceil($hours / CANAL_CALCUL_BOAT_HOURS_DAY) . ' jours de navigation';
}

function canal_calcul_days_bike(float $km): string
{
    return $km <= CANAL_CALCUL_BIKE_KM_ONE_DAY ? 'dans la journée' : '≈ ' . (int) ceil($km / CANAL_CALCUL_BIKE_KM_DAY) . ' jours';
}

function canal_calcul_days_walk(float $km): string
{
    $days = (int) ceil($km / CANAL_CALCUL_WALK_KM_DAY);
    return $days <= 1 ? 'dans la journée' : '≈ ' . $days . ' jours';
}

function canal_calcul_locks_label(int $sites, int $sas): string
{
    if ($sites === 0) {
        return 'Aucune écluse';
    }
    return $sites . ' écluse' . ($sites > 1 ? 's' : '') . ($sas > $sites ? ' · ' . $sas . ' sas' : '');
}

function canal_calcul_town(string $name): ?array
{
    foreach (CANAL_CALCUL_TOWNS as $t) {
        if ($t['name'] === $name) {
            return $t;
        }
    }
    return null;
}

// « Écluse de Bram » → « l'écluse de Bram »; « Écluses de Fonseranes » → « les écluses de Fonseranes ».
function canal_calcul_lock_lower(string $name): string
{
    return (string) preg_replace_callback('/^Écluses? /u', function ($m) {
        return mb_strtolower($m[0], 'UTF-8');
    }, $name);
}

/** « de Trèbes », « d'Agde », « du Somail », « de l'écluse de Bram », « des écluses de Fonseranes ». */
function canal_calcul_de(string $name): string
{
    $t = canal_calcul_town($name);
    if ($t && isset($t['de'])) {
        return $t['de'];
    }
    if (strpos($name, 'Écluses ') === 0) {
        return 'des ' . canal_calcul_lock_lower($name);
    }
    if (strpos($name, 'Écluse ') === 0) {
        return "de l'" . canal_calcul_lock_lower($name);
    }
    return (preg_match('/^[AEIOUYÉÈ]/u', $name) ? "d'" : 'de ') . $name;
}

/** « à Trèbes », « au Somail », « à l'écluse de Bram », « aux écluses de Fonseranes ». */
function canal_calcul_a(string $name): string
{
    $t = canal_calcul_town($name);
    if ($t && isset($t['a'])) {
        return $t['a'];
    }
    if (strpos($name, 'Écluses ') === 0) {
        return 'aux ' . canal_calcul_lock_lower($name);
    }
    if (strpos($name, 'Écluse ') === 0) {
        return "à l'" . canal_calcul_lock_lower($name);
    }
    return 'à ' . $name;
}

function canal_calcul_ucfirst(string $s): string
{
    return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
}

/** Frase del resultado (visible y citable): « De Castelnaudary à Trèbes : 53 km par le Canal du Midi et 23 écluses à franchir. » */
function canal_calcul_sentence(string $from, string $to, array $r): string
{
    $locks = $r['sites'] ? ' et ' . $r['sites'] . ' écluse' . ($r['sites'] > 1 ? 's' : '') . ' à franchir' : '';
    return canal_calcul_ucfirst(canal_calcul_de($from)) . ' ' . canal_calcul_a($to) . ' : ' . (int) round($r['km']) . ' km par le Canal du Midi' . $locks . '.';
}

/** Bloque « Que faire à … » de la llegada: título y búsqueda de la carte (ciudad o la más cercana a la esclusa). */
function canal_calcul_arrival(string $to): array
{
    $t = canal_calcul_town($to);
    if ($t) {
        return ['title' => 'Que faire ' . canal_calcul_a($to) . ' ?', 'search' => $t['search']];
    }
    $pk = canal_calcul_pk($to);
    $near = CANAL_CALCUL_TOWNS[0];
    foreach (CANAL_CALCUL_TOWNS as $c) {
        if (abs($c['pk'] - $pk) < abs($near['pk'] - $pk)) {
            $near = $c;
        }
    }
    return ['title' => 'Que faire près ' . canal_calcul_de($to) . ' ?', 'search' => $near['search']];
}

/** Tabla de distancias (km enteros) entre las ciudades de CANAL_CALCUL_MATRIX, con nombres cortos. */
function canal_calcul_matrix(): array
{
    $short = function (string $n): string { return (string) preg_replace('/ \(.*\)$/', '', $n); };
    $m = [];
    foreach (CANAL_CALCUL_MATRIX as $r) {
        foreach (CANAL_CALCUL_MATRIX as $c) {
            $m[$short($r)][$short($c)] = (int) round(abs(canal_calcul_pk($r) - canal_calcul_pk($c)));
        }
    }
    return $m;
}
