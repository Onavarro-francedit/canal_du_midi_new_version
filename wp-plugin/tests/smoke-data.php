<?php
// Smoke de data.php con WordPress cargado (solo lectura + transient propio).
// Uso (servidor): wp eval-file smoke-data.php <dir-src>   → lo lanza remote.sh smoke
$src = $args[0] ?? '';
require_once $src . '/canal-home/includes/data.php';

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};

$types = canal_home_hero_types();
$check(count($types) === 12, 'hero types: 12 (' . count($types) . ')');
$check(array_keys($types)[0] === 'hotel', 'hero types: orden de la whitelist');

$stages = canal_home_stages();
$check(count($stages) === 9, 'stages: 9 (' . implode(', ', $stages) . ')');
$check(($stages['beziers'] ?? '') === 'Béziers' && ($stages['argens-minervois'] ?? '') === 'Argens-Minervois', 'stages: etiquetas legibles con acentos');

$cats = canal_home_categories(6);
$check(array_column($cats, 'name') === array_column(canal_home_categories(6), 'name'), 'categories: deterministas (misma selección y orden)');
$check(count(array_intersect(array_column($cats, 'url'), array_map(function ($s) { return get_term_link($s, 'job_listing_category'); }, CANAL_HOME_CATEGORY_SLUGS))) === 6, 'categories: la selección curada');
$check(count($cats) === 6, 'categories: 6');
foreach ($cats as $c) {
    $check(strpos($c['url'], '/categorie/') !== false && $c['image'] !== '' && $c['count'] > 0, "category {$c['name']}: url+imagen+count");
}

$sejours = canal_home_sejours();
$check(count($sejours) === 8, 'sejours: 8');
$check(array_column($sejours, 'url') === array_column(canal_home_sejours(), 'url'), 'sejours: deterministas en el mismo día');
foreach ($sejours as $s) {
    $check(strpos($s['url'], '/fiche/') !== false && $s['image'] !== '' && $s['category'] !== '', "sejour {$s['title']}: url+imagen+categoría");
    $check(strpos($s['title'], '&#') === false, "sejour {$s['title']}: título sin entidades");
    $check(!in_array($s['category'], ['Ecluses', 'Écluses', 'Ports', 'Ports fluviaux'], true), "sejour {$s['title']}: no es esclusa/puerto ({$s['category']})");
}
// Muestreo amplio: 40 séjours seguidos sin ninguna esclusa ni puerto.
$bad = 0;
for ($i = 0; $i < 10; $i++) {
    foreach (canal_home_sejours() as $s) {
        if (in_array($s['category'], ['Ecluses', 'Écluses', 'Ports', 'Ports fluviaux'], true)) {
            $bad++;
        }
    }
}
$check($bad === 0, "sejours: 0 esclusas/puertos en 40 tarjetas aleatorias ($bad)");

$card = canal_home_card_by_slug('hotel-de-bordeaux');
$check($card !== null && substr($card['url'], -strlen('/fiche/hotel-de-bordeaux/')) === '/fiche/hotel-de-bordeaux/', 'card_by_slug: permalink /fiche/');
$check($card !== null && $card['city'] === 'Toulouse', 'card_by_slug: ciudad desde region');
$check(canal_home_card_by_slug('no-existe-xyz') === null, 'card_by_slug: inexistente → null');

delete_transient('canal_home_ai_catalog');
$catalog = canal_home_ai_catalog();
$check(count($catalog) >= 250, 'catalog: >= 250 fichas (' . count($catalog) . ')');
$check(count(array_filter($catalog, function ($i) { return $i['slug'] === ''; })) === 0, 'catalog: sin slugs vacíos');
$check(is_array(get_transient('canal_home_ai_catalog')), 'catalog: guardado en transient');
$chars = 0;
foreach ($catalog as $i) {
    $chars += strlen(implode(' | ', [$i['slug'], $i['title'], implode(', ', $i['categories']), $i['city'], $i['excerpt']]));
}
WP_CLI::log('catalog: ~' . (int) ($chars / 3.5) . ' tokens estimados');
delete_transient('canal_home_ai_catalog');

WP_CLI::log($fails === 0 ? 'TODO OK' : "$fails FALLO(S)");
