<?php
// Smoke de la plantilla de contenido 2026 con WordPress cargado y el plugin YA desplegado y activo (no se
// requiere nada: sin « Cannot redeclare »). Uso: wp-plugin/remote.sh run tests/smoke-contenu.php
$fails = 0;
$check = function (bool $ok, string $label) use (&$fails) {
    echo ($ok ? 'ok   - ' : 'FAIL - ') . $label . "\n";
    $fails += $ok ? 0 : 1;
};

$resolve = function (string $request) {
    $path = canal_contenu_strip_suffix($request, CANAL_CONTENU_SUFFIX);
    if ($path === null || get_page_by_path($request, OBJECT, ['page', 'post'])) {
        return null;
    }
    $p = get_page_by_path($path, OBJECT, ['page', 'post']);
    return $p instanceof WP_Post && canal_contenu_post_eligible($p) ? $p : null;
};

$cases = [
    'navigation/regles-de-navigation-2026'      => ['page', '<table'],
    'canal-de-la-robine-2026'                   => ['page', '<img'],
    'peniches-a-vendre-sur-le-canal-tout-savoir-avant-dacheter-2026' => ['post', '<p'],
    'meteo-du-canal-du-midi-2026'               => ['page', 'booked'],
    'foire-de-printemps-du-grand-narbonne-2026' => ['post', '<p'],
];
foreach ($cases as $request => [$type, $needle]) {
    $p = $resolve($request);
    $check($p instanceof WP_Post && $p->post_type === $type, "$request: resuelve ($type)");
    if (!$p) {
        continue;
    }
    $c = canal_contenu_data($p);
    $check(stripos($c['html'], '<h1') === false, "$request: sin <h1> en el cuerpo");
    $check(stripos($c['html'], $needle) !== false, "$request: contiene $needle");
    $check(stripos($c['html'], '[Zoomer') === false && strpos($c['html'], 'elementor-') === false, "$request: sin [Zoomer] ni marcado de Elementor");
    $check($c['url'] === home_url('/' . $request . '/'), "$request: url 2026");
    $check($c['description'] !== '' && mb_strlen($c['description']) <= 160, "$request: meta description (" . mb_strlen($c['description']) . ')');
    $graph = canal_contenu_seo_graph($c, $c['url'], home_url('/'), CANAL_HOME_SITE_NAME)['@graph'];
    $check($graph[0]['@type'] === ($type === 'post' ? 'Article' : 'WebPage'), "$request: JSON-LD {$graph[0]['@type']}");
    $json = canal_home_seo_jsonld(canal_contenu_seo_graph($c, $c['url'], home_url('/'), CANAL_HOME_SITE_NAME));
    $check(strpos($json, '</script>') === strrpos($json, '</script>'), "$request: JSON-LD sin </script> interno");
    echo "     título: {$c['title']} · migas: " . implode(' › ', array_column($c['crumbs'], 1)) . ' · hermanas: ' . count($c['siblings']) . ($c['notice'] ? " · aviso: {$c['notice']}" : '') . "\n";
}
$check(strpos(canal_contenu_data($resolve('foire-de-printemps-du-grand-narbonne-2026'))['notice'], '2017') !== false, 'foire 2017: aviso de antigüedad');

// Excluidas y páginas reales con sufijo.
$check($resolve('calcul-de-distance-canal-du-midi-2026') === null, 'calcul de distance: no elegible');
$check($resolve('hotels-2026') === null, 'plantilla antigua: no elegible');
$check($resolve('accueil-2026') === null && get_page_by_path('accueil-2026') instanceof WP_Post, 'accueil-2026: página real, sin router');
$check($resolve('explorer-2026') === null, 'explorer-2026: página real, sin router');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
