<?php
// Tests de fiche-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-fiche.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/carte-filter.php';
require __DIR__ . '/../canal-home/includes/fiche-core.php';
require __DIR__ . '/../canal-home/includes/head-fix.php';

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

// Vídeo: solo YouTube/Vimeo, id validado.
check(canal_fiche_video_embed('https://www.youtube.com/watch?v=V6bsAQRZyAg') === 'https://www.youtube-nocookie.com/embed/V6bsAQRZyAg', 'video: youtube watch');
check(canal_fiche_video_embed('https://youtu.be/V6bsAQRZyAg?t=3') === 'https://www.youtube-nocookie.com/embed/V6bsAQRZyAg', 'video: youtu.be');
check(canal_fiche_video_embed('https://www.youtube.com/embed/V6bsAQRZyAg') === 'https://www.youtube-nocookie.com/embed/V6bsAQRZyAg', 'video: youtube embed');
check(canal_fiche_video_embed('https://vimeo.com/123456789') === 'https://player.vimeo.com/video/123456789', 'video: vimeo');
check(canal_fiche_video_embed('https://evil.example/watch?v=V6bsAQRZyAg') === '', 'video: host no permitido');
check(canal_fiche_video_embed('https://www.youtube.com/watch?v=abc"><script>') === '', 'video: id inválido');
check(canal_fiche_video_embed('https://www.youtube.com/watch?v[]=x') === '', 'video: v como array');
check(canal_fiche_video_embed('') === '' && canal_fiche_video_embed('pas une url') === '', 'video: vacío / basura');

// Teléfono: el tel: solo lleva el número.
check(canal_fiche_tel('04 68 91 59 30') === '0468915930', 'tel: con espacios');
check(canal_fiche_tel('Tél Atelier : 07 68 13 87 23') === '0768138723', 'tel: con texto delante');
check(canal_fiche_tel('+33 4.68.91.59.30') === '+33468915930', 'tel: internacional con puntos');
check(canal_fiche_tel('04 68 12 34 56 - 06 12 34 56 78') === '0468123456', 'tel: dos números → solo el primero');
check(canal_fiche_tel('0468123456 0612345678') === '0468123456', 'tel: dos números pegados → solo el primero');
check(canal_fiche_tel('+33 (0)4 68 12 34 56') === '+33468123456', 'tel: +33 (0) conserva el prefijo');
check(canal_fiche_tel('fermé') === '' && canal_fiche_tel('') === '', 'tel: sin número');

// Redes: _facebook + _links, solo http(s) y dominio exacto.
$links = [
    ['network' => 'Facebook', 'url' => 'https://www.facebook.com/otra/'],
    ['network' => 'Instagram', 'url' => 'https://www.instagram.com/chambre_lamarelle/'],
    ['network' => 'X', 'url' => 'https://notfacebook.com/x'],
    'basura',
];
$s = canal_fiche_social('https://www.facebook.com/maisonlamarelle/', $links);
check(($s['facebook'] ?? '') === 'https://www.facebook.com/maisonlamarelle/', 'social: _facebook tiene prioridad');
check(($s['instagram'] ?? '') === 'https://www.instagram.com/chambre_lamarelle/', 'social: instagram desde _links');
check(count($s) === 2, 'social: dominio falso descartado');
check(canal_fiche_social('javascript:alert(1)//facebook.com', '') === [], 'social: esquema no http descartado');

// Coordenadas y distancia.
check(canal_fiche_has_coords(43.21, 2.35) && !canal_fiche_has_coords(null, 2.35) && !canal_fiche_has_coords(0.0, 0.0), 'coords: null y 0,0 no valen');
$d = canal_fiche_distance_km(43.6045, 1.4440, 43.2130, 2.3491); // Toulouse → Carcassonne ≈ 84 km
check($d > 80 && $d < 88, "distancia: Toulouse–Carcassonne ≈ 84 km ($d)");
check(canal_fiche_km_label(0.85) === '850 m' && canal_fiche_km_label(3.24) === '3,2 km', 'km_label: m y km con coma');

// Dirección que en realidad son coordenadas (écluses: _job_location = « lat, lng »).
check(canal_fiche_is_coords_text('43.265531891736615, 2.109718510697836'), 'coords_text: « lat, lng »');
check(canal_fiche_is_coords_text(' -1.5,43.2 '), 'coords_text: sin espacio y negativa');
check(!canal_fiche_is_coords_text('20, rue du Pech 11170 Sainte-Eulalie'), 'coords_text: dirección real');
check(!canal_fiche_is_coords_text(''), 'coords_text: vacío');

// Cercanas.
$L = function (int $id, $lat, $lng): array { return ['id' => $id, 'title' => "F$id", 'lat' => $lat, 'lng' => $lng]; };
$all = [$L(1, 43.60, 1.44), $L(2, 43.61, 1.45), $L(3, 43.21, 2.35), $L(4, null, null), $L(5, 0.0, 0.0), $L(6, 43.605, 1.445)];
$near = canal_fiche_nearby($all, 1, 43.60, 1.44, 2);
check(array_column($near, 'id') === [6, 2], 'nearby: orden por distancia, excluye la propia, n=2');
check(isset($near[0]['distance_km']) && $near[0]['distance_km'] < 1, 'nearby: añade distance_km');
check(!in_array(4, array_column(canal_fiche_nearby($all, 1, 43.60, 1.44, 10), 'id'), true), 'nearby: sin coords excluida');
check(!in_array(5, array_column(canal_fiche_nearby($all, 1, 43.60, 1.44, 10), 'id'), true), 'nearby: 0,0 excluida');
check(canal_fiche_nearby($all, 1, null, null) === [] && canal_fiche_nearby($all, 1, 0.0, 0.0) === [], 'nearby: ficha sin coords → []');

// Extracto.
$long = str_repeat('Le canal du Midi ', 20);
$ex = canal_fiche_excerpt($long);
check(mb_strlen($ex, 'UTF-8') <= 155 && substr($ex, -3) === '…', 'excerpt: ≤155 y termina en …');
check(canal_fiche_excerpt("  Écluse   de\n Sauzens  ") === 'Écluse de Sauzens', 'excerpt: espacios colapsados, corto sin …');

// Título legible: solo se tocan los nombres enteramente en mayúsculas.
check(canal_fiche_display_title('MAISON RASSIER') === 'Maison Rassier', 'display_title: mayúsculas → Maison Rassier');
check(canal_fiche_display_title('LE RELAIS DE SULLY') === 'Le Relais de Sully', 'display_title: « de » en minúscula salvo al inicio');
check(canal_fiche_display_title("L'ESCALE OCCITANE - CAMPING ***") === "L'Escale Occitane - Camping ***", "display_title: tras apóstrofo, mayúscula");
check(canal_fiche_display_title('Écluse de Sauzens') === 'Écluse de Sauzens', 'display_title: mixto sin cambios');
check(canal_fiche_display_title('LES CANALOUS - CARCASSONNE') === 'Les Canalous - Carcassonne', 'display_title: artículo inicial en mayúscula');

// FAQ con datos reales.
$base = ['title' => 'Maison Rassier', 'address' => '20, rue du Pech 11170 Sainte-Eulalie', 'city' => 'Sainte-Eulalie', 'zones' => ['Carcassonne - Homps'],
    'phone' => '06 80 88 00 99', 'mobile' => '', 'email' => 'a@b.fr', 'website' => 'https://www.maisons-delmas-rassier.com/',
    'nearby' => [['title' => 'Le Relais de Sully', 'type' => 'Boulangerie', 'distance_km' => 1.5], ['title' => 'Écluse de Béteille', 'type' => 'Ecluses', 'distance_km' => 2.8]]];
$faq = canal_fiche_faq($base);
check(count($faq) === 3, 'faq: 3 preguntas con datos completos');
check($faq[0]['q'] === 'Où se trouve Maison Rassier ?' && strpos($faq[0]['a'], '20, rue du Pech 11170 Sainte-Eulalie') !== false && strpos($faq[0]['a'], 'Carcassonne - Homps') !== false, 'faq: dónde (dirección + tramo)');
check(strpos($faq[1]['a'], '06 80 88 00 99') !== false && strpos($faq[1]['a'], 'a@b.fr') !== false && strpos($faq[1]['a'], 'maisons-delmas-rassier.com') !== false, 'faq: contacto (tel, e-mail, web)');
check(strpos($faq[2]['a'], 'Le Relais de Sully (Boulangerie, à 1,5 km)') !== false, 'faq: cercanas con tipo y distancia');
$el = canal_fiche_faq(['title' => 'Écluse de Sauzens', 'address' => '', 'city' => 'Bram', 'zones' => [], 'phone' => '', 'mobile' => '', 'email' => '', 'website' => '',
    'nearby' => [['title' => 'Port de Bram', 'type' => '', 'distance_km' => 1.8]]]);
check($el[1]['q'] === "Que trouve-t-on autour d'Écluse de Sauzens ?" && strpos($el[1]['a'], "À proximité d'Écluse de Sauzens : Port de Bram (à 1,8 km)") === 0, 'faq: elisión « d\' » ante vocal');
$min = canal_fiche_faq(['title' => 'Écluse', 'address' => '', 'city' => 'Bram', 'zones' => [], 'phone' => '', 'mobile' => '', 'email' => '', 'website' => '', 'nearby' => []]);
check(count($min) === 1 && strpos($min[0]['a'], 'Bram') !== false, 'faq: sin contacto ni cercanas → solo « où », con la commune');
check(canal_fiche_faq(['title' => 'X', 'address' => '', 'city' => '', 'zones' => [], 'phone' => '', 'mobile' => '', 'email' => '', 'website' => '', 'nearby' => []]) === [], 'faq: sin datos → []');

// Tipo de schema según la categoría (gana la prioridad del mapa: alojamiento > restauración > …).
check(canal_fiche_schema_type(['gites'], true) === 'LodgingBusiness', 'schema_type: gîte → LodgingBusiness');
check(canal_fiche_schema_type(['hotel'], true) === 'Hotel', 'schema_type: hotel → Hotel');
check(canal_fiche_schema_type(['camping'], true) === 'Campground', 'schema_type: camping → Campground');
check(canal_fiche_schema_type(['chambre-dhotes'], true) === 'BedAndBreakfast', "schema_type: chambre d'hôtes → BedAndBreakfast");
check(canal_fiche_schema_type(['bar', 'restaurant'], true) === 'Restaurant', 'schema_type: prioridad del mapa, no orden alfabético de términos');
check(canal_fiche_schema_type(['bar', 'camping'], true) === 'Campground', 'schema_type: camping con bar → Campground');
check(canal_fiche_schema_type(['ecluses'], true) === 'TouristAttraction', 'schema_type: écluse con contacto sigue siendo TouristAttraction');
check(canal_fiche_schema_type(['commerce', 'librairie', 'site-et-monument'], true) === 'TouristAttraction', 'schema_type: monumento con librería → TouristAttraction');
check(canal_fiche_schema_type(['librairie'], true) === 'BookStore', 'schema_type: librería sola → BookStore');
check(canal_fiche_seo_title_text('Abbaye-Cathedrale de Saint-Papoul', 'Saint-Papoul') === 'Abbaye-Cathedrale de Saint-Papoul — Canal du Midi', 'seo_title: sin repetir la commune');
check(canal_fiche_seo_title_text('Écluse de Béziers', 'BEZIERS') === 'Écluse de Béziers — Canal du Midi', 'seo_title: comparación sin acentos ni mayúsculas');
check(canal_fiche_seo_title_text('Maison Rassier', 'Sainte-Eulalie') === 'Maison Rassier à Sainte-Eulalie — Canal du Midi', 'seo_title: con commune');
check(canal_fiche_seo_title_text('Maison Rassier', '') === 'Maison Rassier — Canal du Midi', 'seo_title: sin commune');
check(canal_fiche_street('5 Pl. Mgr de Langle, Saint-Papoul, France', 'Saint-Papoul') === '5 Pl. Mgr de Langle', 'street: sin commune ni país');
check(canal_fiche_street('20 rue du Pech, 11170 Sainte-Eulalie', 'Sainte-Eulalie') === '20 rue du Pech', 'street: sin CP + commune');
check(canal_fiche_street('Saint-Papoul, France', 'Saint-Papoul') === 'Saint-Papoul', 'street: solo commune → sin el país');
check(canal_fiche_street('Quai du Port', '') === 'Quai du Port', 'street: sin commune');
check(canal_fiche_schema_type(['location-de-velo'], true) === 'LocalBusiness', 'schema_type: sin mapeo + contacto → LocalBusiness');
check(canal_fiche_schema_type([], false) === 'TouristAttraction', 'schema_type: sin mapeo ni contacto → TouristAttraction');

// Teléfono internacional para el JSON-LD.
check(canal_fiche_tel_intl('06 80 88 00 99') === '+33680880099', 'tel_intl: nacional → +33');
check(canal_fiche_tel_intl('Tél Atelier : 07 68 13 87 23') === '+33768138723', 'tel_intl: con texto');
check(canal_fiche_tel_intl('+44 20 7946 0958') === '+442079460958', 'tel_intl: extranjero sin cambios');
check(canal_fiche_tel_intl('fermé') === '', 'tel_intl: sin número');

// Acceso: privada salvo sesión con read_private_pages u opción canal_fiche_public = '1' (pruebas públicas).
check(canal_fiche_can_view(true, false) && canal_fiche_can_view(false, '1'), 'can_view: sesión o apertura temporal');
check(!canal_fiche_can_view(false, false) && !canal_fiche_can_view(false, '0') && !canal_fiche_can_view(false, 'yes'), 'can_view: sin sesión ni opción exacta → privada');

// <head>: el tema imprime <div id="fb-root"> antes de wp_head(); el parser cierra el <head> ahí y
// title/meta/canonical/JSON-LD acaban en el <body>. Se mueve el div justo después de <body>.
$html = "<html><head>\n<script src=x></script>\n<div id=\"fb-root\"></div>\n<title>T</title><meta name=\"description\" content=\"d\">\n</head>\n<body class=\"a\">\n<p>x</p></body></html>";
$fixed = canal_home_fix_head($html);
$headPart = substr($fixed, 0, strpos($fixed, '</head>'));
check(strpos($headPart, 'fb-root') === false && strpos($headPart, '<title>T</title>') !== false, 'fix_head: fb-root fuera del <head>');
check(strpos($fixed, "<body class=\"a\">\n<div id=\"fb-root\"></div>") !== false, 'fix_head: fb-root justo después de <body>');
check(substr_count($fixed, 'fb-root') === 1, 'fix_head: un solo fb-root');
check(canal_home_fix_head('<html><head><title>T</title></head><body><div id="fb-root"></div></body></html>') === '<html><head><title>T</title></head><body><div id="fb-root"></div></body></html>', 'fix_head: sin cambios si ya está en el body');
check(canal_home_fix_head('parcial') === 'parcial', 'fix_head: HTML sin </head> intacto');

// Recursos del tema/plugins que la ficha no usa (móvil): se quitan solo en la ficha.
foreach (['stripe-js', 'google-maps', 'mylisting-maps'] as $h) {
    check(canal_fiche_is_unused_asset($h), "unused_asset: $h fuera");
}
// Desde la cabecera propia (TASK-032) el CSS/JS del tema tampoco se carga en la ficha (subconjunto en fiche-theme.css).
foreach (['moment', 'moment-locale-fr', 'select2', 'jquery', 'jquery-core', 'jquery-migrate', 'jquery-ui-core', 'jquery-ui-sortable', 'c27-main', 'mylisting-vendor', 'mylisting-frontend', 'mylisting-icons', 'mylisting-dynamic-styles', 'font-awesome-5-all', 'font-awesome-4-shim', 'theme-styles-default'] as $h) {
    check(canal_fiche_is_unused_asset($h), "unused_asset: $h fuera");
}
foreach (['canal-fiche', 'canal-fiche-theme', 'canal-home-header', 'google-maps-extra', 'jquery-ui-datepicker', 'mylisting-frontend-extra'] as $h) {
    check(!canal_fiche_is_unused_asset($h), "unused_asset: $h se mantiene");
}

// Carte: mismo tema fuera, pero conserva Google Maps (search-map.js).
check(!canal_theme_is_unused_asset('google-maps') && canal_fiche_is_unused_asset('google-maps'), 'theme_unused: google-maps solo fuera en la ficha');
check(canal_theme_is_unused_asset('mylisting-frontend') && canal_theme_is_unused_asset('stripe-js') && !canal_theme_is_unused_asset('canal-carte-theme'), 'theme_unused: tema y Stripe fuera, lo nuestro no');
check(canal_theme_is_unused_asset('google-fonts-1') && !canal_theme_is_unused_asset('canal-home-base'), 'theme_unused: Roboto de Elementor fuera, nuestra base (fuentes e iconos) no');
check(strpos(CANAL_THEME_FIX_CSS, 'footer.footer{position:static}') === 0, 'theme_fix_css: pie estático');
check(strpos(CANAL_THEME_FIX_CSS, '.loader-bg.main-loader{display:none!important}') !== false, 'theme_fix_css: sin el cargador del tema (lo quitaba frontend.js)');

// <head> del tema aligerado en la ficha.
$h = "<html><head><script src=\"https://www.google.com/recaptcha/api.js\" async defer></script>\n<script async defer crossorigin=\"anonymous\" src=\"https://connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v15.0\" nonce=\"x\"></script>\n"
    . "<link href=\"https://fonts.googleapis.com/css2?family=Quicksand&display=swap\" rel=\"stylesheet\">\n<link rel='stylesheet' id='style-pub'  href='/style-pub.css' type='text/css' />\n<script src=\"https://www.googletagmanager.com/gtag/js\" async></script></head><body><script src=\"https://www.google.com/recaptcha/api.js\"></script></body></html>";
$l = canal_fiche_lighten_head($h);
$lh = substr($l, 0, strpos($l, '</head>'));
check(strpos($lh, 'recaptcha') === false && strpos($lh, 'connect.facebook.net') === false && strpos($lh, 'style-pub') === false, 'lighten_head: sin reCAPTCHA, SDK de Facebook ni style-pub');
check(strpos($lh, 'media="print" onload="this.media=\'all\'"') !== false && strpos($lh, '<noscript><link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet"></noscript>') !== false, 'lighten_head: Google Fonts sin bloquear + noscript');
check(strpos($lh, 'googletagmanager') !== false && strpos($l, '<body><script src="https://www.google.com/recaptcha/api.js"></script>') !== false, 'lighten_head: GTM intacto y el <body> sin tocar');
check(canal_fiche_lighten_head('parcial') === 'parcial', 'lighten_head: HTML sin </head> intacto');

// Nombre accesible de los enlaces-icono del menú.
check(canal_fiche_icon_link_label('<i class="fa fa-facebook-f"></i>', 'https://www.facebook.com/canaldumidi.officiel/?ref=hl') === 'Facebook', 'icon_link_label: Facebook');
check(canal_fiche_icon_link_label('<i class="fab fa-instagram"></i>', 'https://www.instagram.com/x/') === 'Instagram', 'icon_link_label: Instagram');
check(canal_fiche_icon_link_label('', 'https://exemple.fr/') === 'exemple.fr', 'icon_link_label: dominio desconocido');
check(canal_fiche_icon_link_label('[27-icon icon="fab fa-instagram"]', 'https://www.instagram.com/x/') === 'Instagram', 'icon_link_label: título = shortcode de icono del tema');
check(canal_fiche_icon_link_label('Contact', 'https://www.facebook.com/') === '', 'icon_link_label: con texto → sin cambios');

// TASK-048: GA4 directo y diferido en lugar del contenedor UA (snippet real del header.php del tema).
$ua = "<!-- Global site tag (gtag.js) - Google Analytics -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=UA-641851-4\"></script>\n<script>\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n\n  gtag('config', 'UA-641851-4');\n</script>\n"
    . "\t<title>x</title>\n<script>\nfunction gtag_report_conversion(url) { gtag('event', 'conversion', {'send_to': 'AW-986499205/uiW5CPDUq9kCEIWRs9YD'}); return false; }\n</script>\n";
$g = canal_home_swap_gtag($ua);
check(strpos($g, 'UA-641851-4') === false, 'gtag: el contenedor UA desaparece');
check(substr_count($g, "gtag('config','G-R0M81JSWP0')") === 1 && substr_count($g, 'gtag/js?id=G-R0M81JSWP0') === 1, 'gtag: GA4 configurado una vez y cargado una vez');
check(strpos($g, 'addEventListener(\'load\'') !== false && strpos($g, '<script async src') === false, 'gtag: el loader va en load, sin <script async> en el head');
check(strpos($g, 'function gtag()') !== false && strpos($g, 'function gtag()') < strpos($g, 'function gtag_report_conversion'), 'gtag: el stub va antes de gtag_report_conversion');
check(strpos($g, 'AW-986499205/uiW5CPDUq9kCEIWRs9YD') !== false && strpos($g, '<title>x</title>') !== false, 'gtag: la conversión de Ads y el resto del head intactos');
$noSnippet = "<head><title>x</title></head>";
check(canal_home_swap_gtag($noSnippet) === $noSnippet, 'gtag: head sin el snippet UA → intacto');

// Hojas en línea: un « </style> » dentro de un archivo cerraría la etiqueta antes de tiempo (wp_add_inline_style lo recorta).
foreach (['home.css', 'header.css', 'home-theme.css', 'icons.css'] as $css) {
    $src = (string) file_get_contents(__DIR__ . '/../canal-home/assets/' . $css);
    check($src !== '' && stripos($src, '</style') === false, "inline css: $css sin </style>");
}
check(strpos((string) file_get_contents(__DIR__ . '/../canal-home/assets/icons.css'), 'cdn.jsdelivr') === false, 'icons.css: sin CDN');

// Grafo JSON-LD.
$f = [
    'title' => 'Hôtel de Bordeaux', 'excerpt' => 'Hôtel au bord du canal.', 'cover' => 'https://x/c.jpg', 'gallery' => ['https://x/c.jpg', 'https://x/g.jpg'],
    'address' => '4 Boulevard Bonrepos, 31000 Toulouse, France', 'city' => 'Toulouse', 'postcode' => '31000',
    'lat' => 43.6, 'lng' => 1.45, 'phone' => 'Tél : 05 61 62 41 09', 'mobile' => '', 'email' => 'a@b.fr', 'categories' => [['name' => 'Hôtel', 'slug' => 'hotel']], 'website' => 'https://www.hoteldebordeaux31.fr/',
    'social' => ['facebook' => 'https://www.facebook.com/h/'],
    'modified' => '2026-09-12T08:00:00+00:00', 'faq' => [['q' => 'Où se trouve H ?', 'a' => 'Ici.']],
];
$g = canal_fiche_seo_graph($f, 'https://s/fiche-2026/h/', 'https://s/', 'https://s/explorer-2026/', 'Site');
$place = $g['@graph'][0];
$types = array_column($g['@graph'], '@type');
$page = $g['@graph'][array_search('WebPage', $types, true)];
check($page['dateModified'] === '2026-09-12T08:00:00+00:00' && $page['mainEntity']['@id'] === 'https://s/fiche-2026/h/#place', 'graph: WebPage con dateModified y mainEntity');
check($page['publisher']['@id'] === 'https://s/#organization' && $page['publisher']['name'] === 'Site', 'graph: publisher = organización del sitio');
check(in_array('.fiche-faq', $page['speakable']['cssSelector'], true), 'graph: speakable sobre la FAQ');
check($page['isPartOf']['@type'] === 'WebSite' && $page['isPartOf']['name'] === 'Site' && $page['isPartOf']['url'] === 'https://s/', 'graph: isPartOf WebSite tipado (no CreativeWork vacío en el validador)');
$faqNode = $g['@graph'][array_search('FAQPage', $types, true)];
check($faqNode['mainEntity'][0]['name'] === 'Où se trouve H ?' && $faqNode['mainEntity'][0]['acceptedAnswer']['text'] === 'Ici.', 'graph: FAQPage con las preguntas');
check($g['@context'] === 'https://schema.org', 'graph: @context');
check($place['@type'] === 'Hotel', 'graph: tipo según categoría (Hotel)');
check($place['telephone'] === '+33561624109', 'graph: telephone en formato internacional');
check($place['containedInPlace']['@id'] === 'https://s/#canal-du-midi' && in_array('https://www.wikidata.org/wiki/Q202494', $place['containedInPlace']['sameAs'], true), 'graph: containedInPlace Canal du Midi con Wikidata');
check($page['about']['@id'] === 'https://s/#canal-du-midi', 'graph: WebPage about Canal du Midi');
check($place['image'] === ['https://x/c.jpg', 'https://x/g.jpg'], 'graph: imágenes sin duplicar');
check($place['sameAs'] === ['https://www.hoteldebordeaux31.fr/', 'https://www.facebook.com/h/'], 'graph: sameAs web + redes');
check($place['address']['postalCode'] === '31000' && $place['geo']['latitude'] === 43.6, 'graph: address y geo');
check($place['address']['streetAddress'] === '4 Boulevard Bonrepos' && $place['email'] === 'a@b.fr', 'graph: streetAddress sin CP/commune/país; email en LocalBusiness');
$mon = canal_fiche_seo_graph(array_merge($f, ['categories' => [['name' => 'Librairie', 'slug' => 'librairie'], ['name' => 'Site', 'slug' => 'site-et-monument']]]), 'https://s/f/', 'https://s/', 'https://s/c/', 'Site')['@graph'][0];
check($mon['@type'] === 'TouristAttraction' && !isset($mon['email']) && $mon['telephone'] === '+33561624109', 'graph: monumento sin email (no existe en Place), con teléfono');
check(array_column($g['@graph'][array_search('BreadcrumbList', $types, true)]['itemListElement'], 'position') === [1, 2, 3], 'graph: breadcrumb de 3 niveles');
$min = canal_fiche_seo_graph(['title' => 'Écluse', 'excerpt' => '', 'cover' => '', 'gallery' => [], 'address' => '', 'city' => '', 'postcode' => '',
    'lat' => null, 'lng' => null, 'phone' => '', 'mobile' => '', 'email' => '', 'website' => '', 'social' => [], 'modified' => '', 'faq' => [], 'categories' => []], 'https://s/f/', 'https://s/', 'https://s/c/', 'Site');
$mp = $min['@graph'][0];
check(!in_array('FAQPage', array_column($min['@graph'], '@type'), true), 'graph: sin FAQ → sin FAQPage');
check($mp['@type'] === 'TouristAttraction', 'graph: sin contacto → TouristAttraction');
check(!isset($mp['geo']) && !isset($mp['address']) && !isset($mp['image']) && !isset($mp['telephone']) && !isset($mp['description']), 'graph: campos vacíos omitidos');


// ── WebP en el <body> de las páginas 2026 ──
$H = 'https://www.plan-canal-du-midi.com';
$page = '<html><head><meta property="og:image" content="' . $H . '/wp-content/uploads/a/og.jpg"></head><body>'
    . '<img src="' . $H . '/wp-content/uploads/2020/01/img_8404_1.jpeg" srcset="' . $H . '/wp-content/uploads/x-300x200.jpg 300w, ' . $H . '/wp-content/uploads/x.jpg 708w">'
    . '<span style="background-image:url(\'' . $H . '/wp-content/uploads/2024/b.JPG\')"></span>'
    . '<img src="' . $H . '/wp-content/plugins/canal-home/assets/peniche-toulouse-800.jpg?ver=12">'
    . '<img src="' . $H . '/wp-content/uploads/sin-webp.png">'
    . '<img src="https://otro.example/wp-content/uploads/z.jpg">'
    . '</body></html>';
$exists = function (string $rel): bool { return strpos($rel, 'sin-webp') === false; };
$w = canal_home_webp_html($page, $exists);
check(strpos($w, 'img_8404_1.jpeg.webp"') !== false, 'webp: src de uploads');
check(strpos($w, 'x-300x200.jpg.webp 300w') !== false && strpos($w, 'x.jpg.webp 708w') !== false, 'webp: cada candidato del srcset');
check(strpos($w, "b.JPG.webp')") !== false, 'webp: url() de un style, extensión en mayúsculas');
check(strpos($w, 'peniche-toulouse-800.jpg.webp?ver=12') !== false, 'webp: assets del plugin, conserva ?ver');
check(strpos($w, 'sin-webp.png"') !== false && strpos($w, 'sin-webp.png.webp') === false, 'webp: sin archivo .webp → original');
check(strpos($w, 'otro.example/wp-content/uploads/z.jpg"') !== false, 'webp: otro dominio intacto');
check(strpos($w, 'og.jpg"') !== false && strpos($w, 'og.jpg.webp') === false, 'webp: el <head> (og:image) no se toca');
check(canal_home_webp_html('<p>sin body</p>', $exists) === '<p>sin body</p>', 'webp: HTML sin <body> intacto');


// ── CSS en línea minificado ──
$css = "/* GENERADO */\n.cdm-home .a :hover {\n    color: red;\n    content: \"a  /* b */  c\";\n}\n\n@media (max-width: 760px) {\n  .b > .c { margin : 0 }\n}\n";
$min = canal_home_minify_css($css);
check(strpos($min, 'GENERADO') === false, 'minify: sin comentarios');
check(strpos($min, '.cdm-home .a :hover{') !== false, 'minify: conserva el espacio descendiente antes de :hover');
check(strpos($min, '"a  /* b */  c"') !== false, 'minify: cadenas intactas');
check(strpos($min, "\n") === false && strlen($min) < strlen($css) * 0.8, 'minify: sin saltos y más corto');
check(strpos($min, '@media (max-width: 760px){.b > .c{margin : 0}}') !== false, 'minify: llaves y bloques @media');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
