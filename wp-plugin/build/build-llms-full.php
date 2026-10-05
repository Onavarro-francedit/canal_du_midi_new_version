<?php
// Genera wp-plugin/llms-full.txt desde includes/content.php (misma FAQ y étapes que la home)
// + la lista de categorías (markdown generado desde WordPress, pasado como argumento).
// Uso: php wp-plugin/build/build-llms-full.php <categorias.md>
define('ABSPATH', __DIR__);
require __DIR__ . '/../canal-home/includes/content.php';
$base = 'https://www.plan-canal-du-midi.com';
$cats = is_readable($argv[1] ?? '') ? trim(file_get_contents($argv[1])) : '';

$o  = "# L'Officiel du Canal du Midi — version complète\n\n";
$o .= "> Guide pratique et plan officiel du Canal du Midi (France), édité chaque année par Azur Communications. Plus de 250 prestataires touristiques référencés le long du canal, de Toulouse à l'étang de Thau. Version résumée : $base/llms.txt\n\n";
$o .= "## Le Canal du Midi en bref\n\n";
$o .= "- Longueur : 240 km, de Toulouse à l'étang de Thau (Les Onglous, vers Sète).\n";
$o .= "- Écluses : 63.\n- Construction : Pierre-Paul Riquet ; inauguration en 1681.\n";
$o .= "- Patrimoine mondial de l'UNESCO depuis 1996 (https://whc.unesco.org/fr/list/770/).\n";
$o .= "- Gestionnaire de la voie d'eau : Voies Navigables de France (https://www.vnf.fr/).\n\n";
$o .= "## Les étapes (points kilométriques depuis Toulouse)\n\n";
foreach (CANAL_HOME_ETAPES as $e) {
    $o .= "- km {$e['km']} — {$e['name']} : {$e['note']}\n";
}
$o .= "\n## Questions fréquentes\n\n";
foreach (CANAL_HOME_FAQ as $f) {
    $o .= "### {$f['q']}\n\n{$f['a']}\n";
    if (!empty($f['url'])) {
        $o .= "\nEn savoir plus : $base{$f['url']}\n";
    }
    if (!empty($f['source'])) {
        $o .= "Source : {$f['source'][0]} — {$f['source'][1]}\n";
    }
    $o .= "\n";
}
$o .= "## Prestataires par catégorie\n\n" . ($cats !== '' ? $cats . "\n" : '') . "\nRecherche sur la carte : $base/explorer/\n\n";
$o .= "## Pages utiles\n\n";
foreach ([
    'Organiser votre séjour (demande transmise aux prestataires)' => '/organiser-votre-sejour/',
    'Calcul de distance entre deux écluses' => '/calcul-de-distance-canal-du-midi/',
    'Permis de conduire' => '/navigation/permis-de-conduire/',
    'Règles de navigation' => '/navigation/regles-de-navigation/',
    'Passer une écluse' => '/navigation/passer-une-ecluse/',
    'Voies vertes et véloroutes' => '/voie-verte-et-veloroute/',
    'Les ouvrages du canal' => '/le-canal/ouvrages/',
    'Foire aux questions' => '/foire-aux-question-faq-canal-du-midi/',
    'Plan officiel (PDF, édition en cours)' => '/plan-canal-du-midi.pdf',
    'Recevoir le plan par courrier' => '/recevoir-le-plan-du-canal-du-midi-2/',
] as $label => $path) {
    $o .= "- [$label]($base$path)\n";
}
$o .= "\n## Contact\n\n- Éditeur : Azur Communications — contact@azur-communications.fr — +33 4 68 62 31 62\n";
file_put_contents(__DIR__ . '/../llms-full.txt', $o);
echo strlen($o) . " bytes\n";
