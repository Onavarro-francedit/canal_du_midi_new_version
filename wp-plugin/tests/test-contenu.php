<?php
// Tests de contenu-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-contenu.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/fiche-core.php';
require __DIR__ . '/../canal-home/includes/contenu-core.php';

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

// Ruta: solo las que terminan en el sufijo; la ruta original sin él.
check(canal_contenu_strip_suffix('canal-de-la-robine-2026', '-2026') === 'canal-de-la-robine', 'ruta: página simple');
check(canal_contenu_strip_suffix('navigation/regles-de-navigation-2026/', '-2026') === 'navigation/regles-de-navigation', 'ruta: página hija');
check(canal_contenu_strip_suffix('canal-de-la-robine', '-2026') === null, 'ruta: sin sufijo');
check(canal_contenu_strip_suffix('-2026', '-2026') === null && canal_contenu_strip_suffix('', '-2026') === null, 'ruta: solo sufijo / vacía');
check(canal_contenu_strip_suffix('fiche-2026/port-de-sete', '-2026') === null, 'ruta: la ficha 2026 no es contenido');
check(canal_contenu_strip_suffix('navigation/-2026', '-2026') === null, 'ruta: segmento vacío');
check(canal_contenu_strip_suffix('canal-de-la-robine-2026', '') === null, 'ruta: sufijo vacío (publicado) → sin router');

// Elegibilidad: todos los artículos publicados; páginas solo con plantilla de contenido.
check(canal_contenu_is_eligible('post', 'publish', '', 'foire-de-printemps-du-grand-narbonne'), 'elegible: artículo sin tráfico');
check(canal_contenu_is_eligible('page', 'publish', 'templates/content-sidebar.php', 'canal-de-la-robine'), 'elegible: página content-sidebar');
check(canal_contenu_is_eligible('page', 'publish', '', 'gastronomie-canal-du-midi'), 'elegible: página sin plantilla');
check(!canal_contenu_is_eligible('page', 'publish', 'templates/calcul_distance_canal.php', 'calcul-de-distance-canal-du-midi'), 'no elegible: calcul de distance');
check(!canal_contenu_is_eligible('page', 'publish', 'rechercher-presta.php', 'hotels'), 'no elegible: plantilla antigua');
check(!canal_contenu_is_eligible('page', 'publish', 'canal-home/template-home.php', 'accueil-2026'), 'no elegible: página 2026');
check(!canal_contenu_is_eligible('page', 'publish', 'elementor_header_footer', 'explorer'), 'no elegible: explorer Elementor');
check(!canal_contenu_is_eligible('page', 'publish', '', 'panier'), 'no elegible: tienda');
check(canal_contenu_is_eligible('page', 'publish', 'templates/content-sidebar.php', 'recevoir-le-plan-du-canal-du-midi-2') && canal_contenu_keeps_plugins('recevoir-le-plan-du-canal-du-midi-2'), 'elegible con sus plugins: pedido del plan (CF7 + PayPal)');
check(canal_contenu_is_eligible('page', 'publish', 'templates/content-sidebar.php', 'boutique-canal-du-midi') && canal_contenu_keeps_plugins('boutique-canal-du-midi'), 'elegible con sus plugins: boutique (TablePress)');
check(!canal_contenu_keeps_plugins('canal-de-la-robine'), 'página normal: sin scripts de plugins');
check(!canal_contenu_is_eligible('page', 'publish', 'templates/content-sidebar.php', 'demande-dhebergement-le-long-du-canal-du-midi') && !canal_contenu_is_eligible('page', 'publish', '', 'agenda'), 'no elegibles: demandes (→ planificador) y agenda vacío');
check(!canal_contenu_is_eligible('post', 'private', '', 'x') && !canal_contenu_is_eligible('post', 'draft', '', 'x'), 'no elegible: no publicado');
check(!canal_contenu_is_eligible('job_listing', 'publish', '', 'port-de-sete'), 'no elegible: ficha');

// Limpieza del HTML.
check(canal_contenu_clean_html('a [Zoomer] b [/Zoomer]') === 'a  b ', 'limpieza: [Zoomer] fuera');
check(canal_contenu_clean_html('<h1 class="x">Titre</h1>') === '<h2 class="x">Titre</h2>', 'limpieza: h1 → h2');
check(canal_contenu_clean_html("<p><strong>LES RÈGLES DE ROUTE</strong></p>") === '<h2>LES RÈGLES DE ROUTE</h2>', 'limpieza: pseudo-título solo');
check(canal_contenu_clean_html("<p><strong>LES RÈGLES DE ROUTE</strong><br />\nIl est conseillé.</p>") === "<h2>LES RÈGLES DE ROUTE</h2>\n<p>Il est conseillé.</p>", 'limpieza: pseudo-título + texto');
check(canal_contenu_clean_html('<p><strong>Adresse :</strong><br />1 rue</p>') === '<p><strong>Adresse :</strong><br />1 rue</p>', 'limpieza: etiqueta « X : » intacta');
check(canal_contenu_clean_html('<p>Un <strong>mot</strong> en gras.</p>') === '<p>Un <strong>mot</strong> en gras.</p>', 'limpieza: negrita dentro del texto intacta');
check(canal_contenu_clean_html("<p>&nbsp;</p><p> </p><p><br /></p><p>x</p>") === '<p>x</p>', 'limpieza: párrafos vacíos fuera');

check(canal_contenu_plain('<p>Lien&nbsp;suivant &amp; <b>plus</b></p><script>x()</script>') === 'Lien suivant & plus', 'texto plano: entidades y scripts');

// <title>: sin repetir « Canal du Midi ».
check(canal_contenu_seo_title('Le Canal de la Robine') === 'Le Canal de la Robine — Canal du Midi', 'title: con sufijo');
check(canal_contenu_seo_title('Météo du Canal du Midi') === 'Météo du Canal du Midi', 'title: ya lo lleva');

// Fechas.
check(canal_contenu_date_fr('2017-04-20 09:33:14') === '20 avril 2017', 'fecha: francés');
check(canal_contenu_date_fr('2026-08-01') === '1er août 2026', 'fecha: 1er');
check(canal_contenu_old_notice('post', '2017-04-20 09:33:14', '2026-10-05') === 'Article publié en 2017 : certaines informations ont pu changer.', 'antigüedad: artículo viejo');
check(canal_contenu_old_notice('post', '2023-10-05 10:00:00', '2026-10-05') === '', 'antigüedad: 3 años justos → sin aviso');
check(canal_contenu_old_notice('post', '2023-10-04 10:00:00', '2026-10-05') !== '', 'antigüedad: más de 3 años → aviso');
check(canal_contenu_old_notice('page', '2014-01-01 00:00:00', '2026-10-05') === '', 'antigüedad: páginas nunca');

// FAQ de la fase 2: JSON validado.
check(canal_contenu_faq('[{"q":"Faut-il un permis ?","a":"Non."}]') === [['q' => 'Faut-il un permis ?', 'a' => 'Non.']], 'faq: válida');
check(canal_contenu_faq('{rotten') === [] && canal_contenu_faq('') === [] && canal_contenu_faq('"x"') === [], 'faq: JSON roto / vacío / escalar');
check(canal_contenu_faq('[{"q":"","a":"x"},{"q":"y"},"z",{"q":" Q ","a":" A "}]') === [['q' => 'Q', 'a' => 'A']], 'faq: entradas incompletas fuera');

// JSON-LD.
$c = [
    'type' => 'post', 'title' => 'Foire de printemps', 'description' => 'Desc', 'published' => '2017-04-20T09:33:14+02:00',
    'modified' => '2017-04-21T10:00:00+02:00', 'image' => 'https://x/img.jpg',
    'crumbs' => [['https://x/', 'Accueil'], ['https://x/c/', 'Actualités'], ['https://x/foire-2026/', 'Foire de printemps']], 'faq' => [],
];
$g = canal_contenu_seo_graph($c, 'https://x/foire-2026/', 'https://x/', 'Site')['@graph'];
$types = array_column($g, '@type');
check($types === ['Article', 'TouristDestination', 'BreadcrumbList'], 'jsonld: Article + canal + migas');
check($g[0]['datePublished'] === $c['published'] && $g[0]['dateModified'] === $c['modified'] && $g[0]['headline'] === 'Foire de printemps', 'jsonld: fechas y headline');
check($g[0]['author']['@id'] === 'https://x/#organization' && $g[0]['about']['@id'] === 'https://x/#canal-du-midi', 'jsonld: autor y about');
check(count($g[2]['itemListElement']) === 3 && $g[2]['itemListElement'][2]['position'] === 3, 'jsonld: migas ordenadas');
$c['type'] = 'page';
$c['faq'] = [['q' => 'Q ?', 'a' => 'R.']];
$c['image'] = '';
$g = canal_contenu_seo_graph($c, 'https://x/p-2026/', 'https://x/', 'Site')['@graph'];
check(array_column($g, '@type') === ['WebPage', 'TouristDestination', 'BreadcrumbList', 'FAQPage'], 'jsonld: WebPage + FAQPage');
check(!isset($g[0]['image']) && !isset($g[0]['headline']), 'jsonld: sin campos vacíos ni headline en WebPage');

// Archivos del blog: /post-category/<ruta>-2026/[page/N/]
check(canal_archive_parse('post-category/vignobles-2026', '-2026') === ['path' => 'vignobles', 'page' => 1], 'archivo: ruta simple');
check(canal_archive_parse('post-category/actualites/divers-2026/page/3/', '-2026') === ['path' => 'actualites/divers', 'page' => 3], 'archivo: subcategoría paginada');
check(canal_archive_parse('post-category/vignobles', '-2026') === null && canal_archive_parse('vignobles-2026', '-2026') === null, 'archivo: sin sufijo o fuera de post-category');
check(canal_archive_parse('post-category/vignobles-2026/page/0', '-2026') === null && canal_archive_parse('post-category/vignobles-2026', '') === null, 'archivo: página 0 / sufijo vacío');
check(canal_archive_path('actualites', 1, '-2026') === '/post-category/actualites-2026/' && canal_archive_path('actualites', 2, '-2026') === '/post-category/actualites-2026/page/2/', 'archivo: URL de página');
check(canal_archive_pages(1900, 12) === 159 && canal_archive_pages(0, 12) === 0 && canal_archive_pages(12, 12) === 1, 'archivo: número de páginas');
check(canal_archive_window(1, 159) === [1, 2, 159] && canal_archive_window(80, 159) === [1, 79, 80, 81, 159] && canal_archive_window(2, 3) === [1, 2, 3], 'archivo: paginación compacta');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
