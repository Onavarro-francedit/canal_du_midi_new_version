<?php
// Tests de velo-core.php (Le Canal du Midi à vélo en bref, TASK-066 T4) — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-velo.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/live.php';
canal_2026_define_paths(false);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/fiche-core.php';
require __DIR__ . '/../canal-home/includes/calcul-core.php';
require __DIR__ . '/../canal-home/includes/etape-core.php';
require __DIR__ . '/../canal-home/includes/meteo-core.php';
require __DIR__ . '/../canal-home/includes/velo-core.php';

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

$f = canal_velo_facts();
check($f['km'] === '240,5 km' && $f['time'] === '16 h' && $f['days'] === '≈ 5 jours' && $f['locks'] === 63, 'cifras: 240,5 km, 16 h, ≈ 5 días, 63 esclusas (calcul)');

$plans = canal_velo_plans();
check(array_keys($plans) === [5, 4, 3], 'reparto en 5, 4 y 3 días');
check($plans[5][0][0] === 'Toulouse' && end($plans[5])[1] === 'Marseillan' && count($plans[5]) === 5, 'reparto: de Toulouse a Marseillan');
check($plans[5][0][2] === '50 km' && $plans[5][0][3] === '3 h 20', 'reparto: km y tiempo de cada jornada');

check(canal_velo_months() === [4, 5, 9, 10], 'meses en bici: abril, mayo, septiembre, octubre (météo)');

$faq = canal_velo_faq(null);
check(count($faq) === 4, 'faq: 4 preguntas sin alquiler');
check(strpos($faq[0]['a'], '240,5 km de Toulouse à l’étang de Thau') === 0, 'faq: km');
check(strpos($faq[1]['a'], 'Comptez 5 jours') === 0 && strpos($faq[1]['a'], 'En 4 jours : Toulouse → Castelnaudary (65 km)') !== false, 'faq: días y etapas');
check(strpos($faq[2]['a'], 'Avril, mai, septembre et octobre') === 0, 'faq: cuándo');
check(count(canal_velo_faq(['q' => 'Où louer un vélo le long du Canal du Midi ?', 'a' => 'x'])) === 5, 'faq: con la pregunta de alquiler de la carte');
foreach ($faq as $qa) {
    check(substr($qa['q'], -2) === ' ?', 'faq: « ' . $qa['q'] . ' »');
}

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
