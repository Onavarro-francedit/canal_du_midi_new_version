<?php
// SEO/AEO de la home: meta, Open Graph, título y JSON-LD. WP cargado, plugin SIN activar.
// Uso: wp-plugin/remote.sh run tests/smoke-seo.php
$src = $args[0] ?? '';
require_once $src . '/canal-home/canal-home.php';

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};

$title = canal_home_seo_title();
$check(mb_strlen($title) >= 40 && mb_strlen($title) <= 60 && strpos($title, 'Canal du Midi') !== false && stripos($title, 'carte') !== false && stripos($title, 'tracé') !== false, "título ($title)");
$desc = canal_home_seo_description();
$check(stripos($desc, 'carte détaillée') !== false, 'meta con « carte détaillée »');
$check(mb_strlen($desc) >= 120 && mb_strlen($desc) <= 160, 'meta description 120–160 car. (' . mb_strlen($desc) . ')');

$sejours = canal_home_sejours();
$html = canal_home_seo_head('https://www.plan-canal-du-midi.com/', $sejours, '2026-09-29T10:00:00+00:00');
$check(strpos($html, '<meta name="description" content="') !== false, 'meta description emitida');
foreach (['og:title', 'og:description', 'og:image', 'og:url', 'og:type', 'og:locale', 'og:site_name'] as $p) {
    $check(strpos($html, 'property="' . $p . '"') !== false, "Open Graph $p");
}
$check(strpos($html, 'name="twitter:card" content="summary_large_image"') !== false, 'Twitter Card');

preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
$check(count($m[1]) === 1, 'un único bloque JSON-LD');
$data = json_decode($m[1][0] ?? '', true);
$check(is_array($data) && ($data['@context'] ?? '') === 'https://schema.org' && isset($data['@graph']), 'JSON-LD válido con @graph');
$types = [];
foreach (($data['@graph'] ?? []) as $node) {
    $types[$node['@type']] = $node;
}
foreach (['Organization', 'WebSite', 'WebPage', 'TouristDestination', 'ItemList', 'FAQPage'] as $t) {
    $check(isset($types[$t]), "schema $t");
}
$map = $types['Map'] ?? [];
$check(($map['isAccessibleForFree'] ?? null) === true && substr((string) ($map['url'] ?? ''), -strlen('/plan-canal-du-midi.pdf')) === '/plan-canal-du-midi.pdf', 'schema Map: gratuito + url al PDF');
$check(!empty($map['@id']) && ($types['TouristDestination']['hasMap']['@id'] ?? '') === ($map['@id'] ?? 'x'), 'hasMap.@id == Map.@id');
$org = $types['Organization'] ?? [];
$check(($org['parentOrganization']['name'] ?? '') === 'Azur Communications' && in_array('https://www.facebook.com/canaldumidi.officiel/', (array) ($org['sameAs'] ?? []), true), 'Organization: editor Azur Communications + sameAs Facebook');
$target = $types['WebSite']['potentialAction']['target']['urlTemplate'] ?? '';
$check(strpos($target, 'search_keywords={search_term_string}') !== false, 'WebSite SearchAction');
$check(count($types['ItemList']['itemListElement'] ?? []) === count($sejours) && count($sejours) > 0, 'ItemList = séjours mostrados (' . count($sejours) . ')');
$faq = $types['FAQPage']['mainEntity'] ?? [];
$check(count($faq) === count(CANAL_HOME_FAQ), 'FAQPage = preguntas visibles (' . count($faq) . ')');
$plain = true;
foreach ($faq as $q) {
    $plain = $plain && strpos($q['acceptedAnswer']['text'] ?? '<', '<') === false;
}
$check($plain, 'respuestas FAQ en texto plano');
$check(stripos($m[1][0] ?? '', '</script') === false, 'JSON-LD sin </script');
$check(strpos($html, 'og-canal-du-midi-1200x630.jpg') !== false && strpos($html, 'property="og:image:width" content="1200"') !== false && strpos($html, 'property="og:image:height" content="630"') !== false, 'og:image 1200×630');
$check(strpos($html, 'rel="alternate" type="text/markdown"') !== false && strpos($html, '/llms.txt') !== false, '<link> a llms.txt en <head>');
$check(in_array('https://www.instagram.com/lofficielducanaldumidi/', (array) ($org['sameAs'] ?? []), true) && !empty($org['knowsAbout']), 'Organization: Instagram + knowsAbout');
$dest = (array) ($types['TouristDestination']['sameAs'] ?? []);
$check(in_array('https://www.wikidata.org/wiki/Q202494', $dest, true) && in_array('https://whc.unesco.org/fr/list/770/', $dest, true), 'TouristDestination: Wikidata Q202494 + UNESCO 770');
$wp = $types['WebPage'] ?? [];
$check(($wp['dateModified'] ?? '') === '2026-09-29T10:00:00+00:00', 'WebPage dateModified');
$check(($wp['speakable']['@type'] ?? '') === 'SpeakableSpecification' && !empty($wp['speakable']['cssSelector']), 'WebPage speakable');
// Sitemaps: sin autores ni biblioteca de Elementor; cabeceras de seguridad y sin generator.
// En eval-file los providers ya están registrados antes de cargar el plugin: se prueba el filtro
// (el efecto real se verifica por HTTP en /wp-sitemap.xml tras desplegar).
$check(apply_filters('wp_sitemaps_add_provider', new stdClass(), 'users') === false && apply_filters('wp_sitemaps_add_provider', new stdClass(), 'posts') !== false, 'filtro: sitemap sin provider users');
$check(!isset(apply_filters('wp_sitemaps_post_types', ['page' => 1, 'elementor_library' => 1])['elementor_library']), 'sitemap sin elementor_library');
$check(has_action('wp_head', 'wp_generator') === false, 'wp_generator retirado');
WP_CLI::log($fails === 0 ? 'TODO OK' : "$fails FALLO(S)");
