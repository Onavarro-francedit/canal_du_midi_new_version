<?php
// Tests de calcul-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-calcul.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/live.php';
canal_2026_define_paths(false);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/calcul-core.php';

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

// Datos: 63 esclusas ordenadas por PK, Fonseranes con 8 sas, ciudades de 0 a 240,5.
$pks = array_column(CANAL_CALCUL_LOCKS, 'pk');
$sorted = $pks;
sort($sorted);
check(count(CANAL_CALCUL_LOCKS) === 63 && $pks === $sorted, 'datos: 63 esclusas ordenadas');
$fons = array_values(array_filter(CANAL_CALCUL_LOCKS, function ($l) { return strpos($l['name'], 'Fonseranes') !== false; }));
check(count($fons) === 1 && $fons[0]['sas'] === 8 && $fons[0]['slug'] === 'ecluses-de-fonseranes', 'datos: Fonseranes 8 sas con ficha');
check(!in_array('', array_column(CANAL_CALCUL_LOCKS, 'slug'), true), 'datos: las 63 esclusas tienen ficha');
$towns = CANAL_CALCUL_TOWNS;
check($towns[0]['pk'] === 0.0 && end($towns)['pk'] === 240.5, 'datos: Toulouse PK 0 → Les Onglous PK 240,5');

// Búsqueda por nombre sin mayúsculas ni acentos.
check(canal_calcul_find('trebes') === 'Trèbes' && canal_calcul_find('  CASTELNAUDARY ') === 'Castelnaudary', 'buscar: sin acentos ni mayúsculas');
check(canal_calcul_find("ÉCLUSE D'HOMPS") === "Écluse d'Homps", 'buscar: esclusa');
check(canal_calcul_find('Narbonne') === null && canal_calcul_find('') === null, 'buscar: desconocido → null');

// Cálculo (calibrado con Le Boat: Castelnaudary → Trèbes ≈ 13 h).
$r = canal_calcul_compute(canal_calcul_pk('Castelnaudary'), canal_calcul_pk('Trèbes'));
check(round($r['km']) === 53.0 && $r['sites'] === 23 && $r['sas'] === 31, 'Castelnaudary → Trèbes: 53 km, 23 esclusas, 31 sas');
check(canal_calcul_duration($r['boat']) === '12 h 40' && canal_calcul_duration($r['bike']) === '3 h 30', 'Castelnaudary → Trèbes: 12 h 40 en barco, 3 h 30 en bici');
$back = canal_calcul_compute(canal_calcul_pk('Trèbes'), canal_calcul_pk('Castelnaudary'));
check($back['km'] === $r['km'] && $back['sas'] === $r['sas'], 'simetría A ↔ B');
$tb = canal_calcul_compute(canal_calcul_pk('Toulouse'), canal_calcul_pk('Béziers'));
check(round($tb['km']) === 208.0 && $tb['sites'] === 57, 'Toulouse → Béziers: 208 km, 57 esclusas');
$same = canal_calcul_compute(105.1, 105.1);
check($same['km'] === 0.0 && $same['sites'] === 0 && canal_calcul_duration($same['boat']) === '0 min', 'mismo punto: 0 km, sin esclusas');
// La esclusa de salida no cuenta; la de llegada sí.
$fp = canal_calcul_compute(canal_calcul_pk('Écluses de Fonseranes'), canal_calcul_pk('Écluse de Portiragnes'));
check($fp['sites'] === 5 && $fp['sas'] === 5, 'Fonseranes → Portiragnes: 5 esclusas (salida excluida, llegada incluida)');

// Esclusas del tramo en el orden de paso.
$names = array_column(canal_calcul_locks_between(canal_calcul_pk('Trèbes'), canal_calcul_pk('Castelnaudary')), 'name');
check($names[0] === 'Écluse de Villedubert' && end($names) === 'Écluses de Saint-Roch', 'lista: orden de paso (Trèbes → Castelnaudary)');

// Textos.
check(canal_calcul_duration(0.84) === '50 min' && canal_calcul_duration(13.2) === '13 h 10' && canal_calcul_duration(2.0) === '2 h', 'duración: redondeo a 10 min');
check(canal_calcul_days_boat(5.9) === 'dans la journée' && canal_calcul_days_boat(12.7) === '≈ 3 jours de navigation', 'días: barco');
check(canal_calcul_days_bike(60) === 'dans la journée' && canal_calcul_days_bike(208) === '≈ 4 jours', 'días: bici');
check(canal_calcul_days_walk(12) === 'dans la journée' && canal_calcul_days_walk(53) === '≈ 3 jours', 'días: a pie');
check(canal_calcul_sentence('Castelnaudary', 'Trèbes', $r) === 'De Castelnaudary à Trèbes : 53 km par le Canal du Midi et 23 écluses à franchir.', 'frase: ciudades');
check(canal_calcul_sentence('Écluses de Fonseranes', 'Écluse de Portiragnes', $fp) === "Des écluses de Fonseranes à l'écluse de Portiragnes : 12 km par le Canal du Midi et 5 écluses à franchir.", 'frase: esclusas (des / à l\')');
check(canal_calcul_sentence("Écluse d'Homps", 'Agde', canal_calcul_compute(146.39, 231.2)) === "De l'écluse d'Homps à Agde : 85 km par le Canal du Midi et 9 écluses à franchir.", 'frase: d\'Homps');
check(canal_calcul_sentence('Agde', 'Agde', $same) === 'De Agde à Agde : 0 km par le Canal du Midi.' || canal_calcul_sentence('Agde', 'Agde', $same) === "D'Agde à Agde : 0 km par le Canal du Midi.", 'frase: sin esclusas');
check(canal_calcul_locks_label(1, 1) === '1 écluse' && canal_calcul_locks_label(23, 31) === '23 écluses · 31 sas' && canal_calcul_locks_label(0, 0) === 'Aucune écluse', 'etiqueta de esclusas');

// « Que faire à … » y búsqueda en la carte: ciudad o ciudad más cercana a la esclusa.
check(canal_calcul_arrival('Trèbes') === ['title' => 'Que faire à Trèbes ?', 'search' => 'Trèbes'], 'llegada: ciudad');
check(canal_calcul_arrival('Écluse de Bram') === ['title' => "Que faire près de l'écluse de Bram ?", 'search' => 'Bram'], 'llegada: esclusa → ciudad cercana');

// Tabla de distancias simétrica, con la diagonal a 0.
$m = canal_calcul_matrix();
check(count($m) === count(CANAL_CALCUL_MATRIX) && $m['Toulouse']['Castelnaudary'] === $m['Castelnaudary']['Toulouse'] && $m['Agde']['Agde'] === 0, 'tabla: simétrica');
check($m['Toulouse']['Béziers'] === 208 && $m['Le Somail']['Capestang'] === 23, 'tabla: valores');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
