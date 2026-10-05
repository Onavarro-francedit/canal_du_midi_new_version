<?php
/**
 * Villes & étapes 2026 (TASK-060): /etape-2026/<slug>/ y el índice /etapes-2026/. Privadas como el resto
 * (opción canal_etape_public). Datos: etape-core.php + prestatarios de la carte (canal_carte_listings, en caché).
 */
defined('ABSPATH') || exit;

function canal_etape_state(?array $set = null): array
{
    static $state = [];
    if ($set !== null) {
        $state = $set;
    }
    return $state;
}

function canal_etape_is_page(): bool
{
    return canal_etape_state() !== [];
}

function canal_etape_url(string $slug): string
{
    return home_url(CANAL_ETAPE_PATH . $slug . '/');
}

/** Foto en talla de tarjeta (medium_large) del prestatario. */
function canal_etape_card(array $item): array
{
    $item['image'] = canal_home_cover_card((int) $item['id']) ?: (string) ($item['image'] ?? '');
    return $item;
}

function canal_etape_groups_for(array $e): array
{
    // Nombres guardados en MAYÚSCULAS → « Aur Blan » (misma regla que la ficha 2026), en tarjetas y FAQ.
    $listings = array_map(function ($item) {
        $item['title'] = canal_fiche_display_title((string) $item['title']);
        return $item;
    }, canal_carte_listings());
    return canal_etape_groups($listings, $e['lat'], $e['lng'], (float) $e['radius']);
}

/** Foto de cabecera: la del puerto más cercano; si no, la del primer prestatario con foto. */
function canal_etape_hero(array $groups): string
{
    $pool = array_merge($groups['eau']['items'] ?? [], ...array_values(array_map(function ($g) { return $g['items']; }, $groups)));
    usort($pool, function ($a, $b) {
        $pa = array_intersect($a['cat_slugs'] ?? [], ['ports', 'ports-fluviaux', 'halte-nautique']) ? 0 : 1;
        $pb = array_intersect($b['cat_slugs'] ?? [], ['ports', 'ports-fluviaux', 'halte-nautique']) ? 0 : 1;
        return [$pa, $a['distance_km']] <=> [$pb, $b['distance_km']];
    });
    foreach ($pool as $item) {
        $img = canal_home_cover_card((int) $item['id']);
        if ($img !== '') {
            return $img;
        }
    }
    return home_url(CANAL_HOME_HERO_IMAGE);
}

function canal_etape_data(array $e): array
{
    global $wpdb;
    $groups = canal_etape_groups_for($e);
    foreach ($groups as $key => $g) {
        $groups[$key]['items'] = array_map('canal_etape_card', $g['items']);
    }
    $marks = [];
    if ($e['canal'] === 'midi') {
        $pk = (float) canal_etape_pk($e);
        $n = canal_etape_neighbors($e['slug']);
        $calc = function (string $from, string $to): string {
            return add_query_arg(['de' => $from, 'a' => $to], home_url('/' . CANAL_CALCUL_SLUG . CANAL_CONTENU_SUFFIX . '/'));
        };
        $marks[] = ['Depuis Toulouse', canal_calcul_compute(0.0, $pk), $calc('Toulouse', $e['calcul'])];
        $marks[] = ["Jusqu'à l'étang de Thau", canal_calcul_compute($pk, 240.5), $calc($e['calcul'], 'Étang de Thau (Les Onglous)')];
        foreach (['prev' => 'Étape précédente', 'next' => 'Étape suivante'] as $k => $label) {
            if ($n[$k]) {
                $marks[] = [$label . ' : ' . $n[$k]['name'], canal_calcul_compute($pk, (float) canal_etape_pk($n[$k])), $calc($e['calcul'], $n[$k]['calcul']), canal_etape_url($n[$k]['slug'])];
            }
        }
    }
    $read = canal_etape_pages($e);
    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_title LIKE %s ORDER BY post_date DESC LIMIT 3",
        '%' . $wpdb->esc_like($e['search']) . '%'
    ));
    foreach ($ids as $id) {
        $p = get_post((int) $id);
        $read[] = [canal_archive_item_url($p), canal_fiche_display_title(wp_strip_all_tags($p->post_title)), canal_contenu_date_fr($p->post_date)];
    }
    return [
        'e'      => $e,
        'lead'   => canal_etape_lead($e),
        'groups' => $groups,
        'count'  => canal_etape_count($groups),
        'hero'   => canal_etape_hero($groups),
        'marks'  => $marks,
        'read'   => $read,
        'faq'    => canal_etape_faq($e, $groups),
        'url'    => canal_etape_url($e['slug']),
        // '' si la carte no daría ninguna ficha (cuenta por municipio, los grupos por radio): sin enlace vacío.
        'carte'  => function (string $type) use ($e): string {
            $query = array_filter(['type' => $type, 'search_location' => $e['search']]);
            return canal_carte_count($query) ? add_query_arg($query, home_url(CANAL_CARTE_PATH)) : '';
        },
    ];
}

/** Páginas del sitio sobre la ciudad: [[url, título], …]. */
function canal_etape_pages(array $e): array
{
    $read = [];
    foreach ($e['pages'] as $path) {
        $p = get_page_by_path($path, OBJECT, ['page', 'post']);
        if ($p instanceof WP_Post && $p->post_status === 'publish') {
            $read[] = [canal_archive_item_url($p), canal_fiche_display_title(wp_strip_all_tags($p->post_title))];
        }
    }
    return $read;
}

function canal_etapes_index(): array
{
    $out = ['midi' => [], 'robine' => []];
    foreach (CANAL_ETAPES as $e) {
        $groups = canal_etape_groups_for($e);
        $out[$e['canal']][] = [
            'e' => $e, 'url' => canal_etape_url($e['slug']), 'lead' => canal_etape_lead($e), 'count' => canal_etape_count($groups),
            'image' => canal_etape_hero($groups), 'voir' => canal_etape_highlights($e, $groups), 'read' => canal_etape_pages($e),
        ];
    }
    $out['faq'] = canal_etapes_search_faq();
    $out['parcours'] = array_map('canal_parcours_card', canal_parcours_parse(canal_parcours_items()));
    return $out;
}

// Parcours editables en Apariencia → Menús (ubicación propia; no toca los menús existentes).
add_action('after_setup_theme', function () {
    register_nav_menus(['canal_parcours' => 'Parcours (page Étapes 2026)']);
});

function canal_parcours_items(): array
{
    $locations = get_nav_menu_locations();
    $items = !empty($locations['canal_parcours']) ? wp_get_nav_menu_items($locations['canal_parcours']) : [];
    if (!$items) {
        return CANAL_PARCOURS_DEFAULT;
    }
    return array_map(function ($i) {
        return ['title' => $i->title, 'url' => $i->url, 'classes' => (array) $i->classes];
    }, $items);
}

/** Enlace al calcul con el parcours ya rellenado. */
function canal_parcours_url(array $c): string
{
    return add_query_arg(['de' => $c['de'], 'a' => $c['a']], canal_calcul_url());
}

// Segundo botón de la tarjeta: lo que se busca en la etapa de salida según el modo (barco, bici, a pie → dormir).
const CANAL_PARCOURS_CARTE_TYPE = ['bateau' => 'location-bateau', 'velo' => 'location-de-velo', 'pied' => 'hebergement'];

function canal_parcours_carte_query(array $c): array
{
    return ['type' => CANAL_PARCOURS_CARTE_TYPE[$c['mode']], 'search_location' => $c['from']['search']];
}

function canal_parcours_loueurs_url(array $c): string
{
    return add_query_arg(canal_parcours_carte_query($c), home_url(CANAL_CARTE_PATH));
}

/** Etiqueta del botón, o null si la carte no daría ninguna ficha con esa búsqueda. */
function canal_parcours_loueurs_label(array $c): ?string
{
    return canal_parcours_carte_label($c['mode'], canal_carte_count(canal_parcours_carte_query($c)), canal_etape_a($c['from']));
}

// Sitemap: las étapes no son posts; proveedor propio solo con el sitio publicado (TASK-063).
add_action('init', function () {
    if (!CANAL_2026_LIVE || canal_2026_is_preview() || !class_exists('WP_Sitemaps_Provider')) {
        return;
    }
    wp_register_sitemap_provider('etapes', new class ('etapes', 'etapes') extends WP_Sitemaps_Provider {
        public function __construct(string $name, string $type)
        {
            $this->name = $name;
            $this->object_type = $type;
        }
        public function get_url_list($page_num, $object_subtype = '')
        {
            $urls = [['loc' => home_url(CANAL_ETAPES_PATH)]];
            foreach (CANAL_ETAPES as $e) {
                $urls[] = ['loc' => canal_etape_url($e['slug'])];
            }
            return $urls;
        }
        public function get_max_num_pages($object_subtype = '')
        {
            return 1;
        }
    });
});

add_filter('query_vars', function (array $vars) {
    $vars[] = 'canal_etape';
    return $vars;
});

add_action('parse_request', function (WP $wp) {
    $req = trim((string) $wp->request, '/');
    if ($req === trim(CANAL_ETAPES_PATH, '/')) {
        $wp->query_vars = ['canal_etape' => '_index'];
    } elseif (preg_match('#^' . preg_quote(trim(CANAL_ETAPE_PATH, '/'), '#') . '/([a-z0-9-]+)$#', $req, $m) && canal_etape_find($m[1])) {
        $wp->query_vars = ['canal_etape' => $m[1]];
    }
}, 6);

add_action('template_redirect', function () {
    $slug = (string) get_query_var('canal_etape');
    if ($slug === '') {
        return;
    }
    global $wp_query;
    if (!canal_fiche_can_view(current_user_can('read_private_pages'), get_option('canal_etape_public'))) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    canal_etape_state($slug === '_index' ? ['index' => canal_etapes_index(), 'url' => home_url(CANAL_ETAPES_PATH)] : canal_etape_data(canal_etape_find($slug)));
    $wp_query->is_home = false;
    $wp_query->is_404  = false;
    if (!CANAL_2026_LIVE) {
        defined('DONOTCACHEPAGE') || define('DONOTCACHEPAGE', true);
        nocache_headers();
    }
    status_header(200);
    include CANAL_HOME_DIR . ($slug === '_index' ? 'template-etapes.php' : 'template-etape.php');
    exit;
}, 0);

foreach (['add_option_', 'update_option_', 'delete_option_'] as $canal_etape_prefix) {
    add_action($canal_etape_prefix . 'canal_etape_public', function () {
        do_action('wpfc_clear_all_cache');
    });
}
unset($canal_etape_prefix);

add_action('wp_enqueue_scripts', function () {
    if (!canal_etape_is_page()) {
        return;
    }
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    canal_home_inline_file('canal-contenu', 'assets/contenu.css', ['canal-home-base']);
    canal_home_inline_file('canal-etape', 'assets/etape.css', ['canal-contenu']);
    canal_home_inline_file('canal-home-header', 'assets/header.css', ['canal-home-base']);
    wp_enqueue_style('canal-fiche-theme', CANAL_HOME_URL . 'assets/fiche-theme.css', ['canal-etape', 'canal-home-header'], (string) filemtime(CANAL_HOME_DIR . 'assets/fiche-theme.css'));
    wp_add_inline_style('canal-fiche-theme', CANAL_THEME_FIX_CSS);
    if (isset(canal_etape_state()['index'])) {
        wp_enqueue_script('canal-etapes', CANAL_HOME_URL . 'assets/etapes.js', [], (string) filemtime(CANAL_HOME_DIR . 'assets/etapes.js'), ['in_footer' => true, 'strategy' => 'defer']);
        $midi = canal_etape_state()['index']['midi'];
        wp_localize_script('canal-etapes', 'CDM_ETAPES', [
            'etapes'   => array_map(function ($it) {
                $c = ['from' => $it['e']];
                return ['name' => $it['e']['name'], 'calcul' => $it['e']['calcul'], 'url' => $it['url'], 'img' => $it['image'], 'pk' => (float) canal_etape_pk($it['e']),
                    'links' => array_combine(CANAL_PARCOURS_MODES, array_map(function ($m) use ($c) { return canal_parcours_loueurs_url($c + ['mode' => $m]); }, CANAL_PARCOURS_MODES)),
                    'labels' => array_combine(CANAL_PARCOURS_MODES, array_map(function ($m) use ($c) { return canal_parcours_loueurs_label($c + ['mode' => $m]); }, CANAL_PARCOURS_MODES))];
            }, $midi),
            'routes'   => canal_parcours_all(),
            'calcul'   => canal_calcul_url(),
            'traceUrl' => CANAL_HOME_URL . 'assets/calcul/canal-du-midi-trace.json?ver=' . filemtime(CANAL_HOME_DIR . 'assets/calcul/canal-du-midi-trace.json'),
        ]);
    }
}, 20);

// Google Maps: como en el calcul, se quita de la cola y etapes.js lo carga al acercarse el mapa (misma URL y clave).
add_action('wp_print_footer_scripts', function () {
    if (!canal_etape_is_page() || !isset(canal_etape_state()['index'])) {
        return;
    }
    $maps = wp_scripts()->registered['google-maps'] ?? null;
    wp_add_inline_script('canal-etapes', 'window.CDM_ETAPES_MAPS = ' . wp_json_encode($maps ? (string) $maps->src : '') . ';', 'before');
}, 1);

const CANAL_ETAPES_TITLE = 'Quel parcours faire sur le Canal du Midi ? Étapes, distances et durées en bateau ou à vélo';

add_filter('pre_get_document_title', function ($title) {
    if (!canal_etape_is_page()) {
        return $title;
    }
    $s = canal_etape_state();
    return isset($s['index']) ? CANAL_ETAPES_TITLE : canal_etape_title($s['e']);
}, 20);

add_filter('wp_robots', function (array $robots) {
    if (canal_etape_is_page() && CANAL_CONTENU_SUFFIX !== '') {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['follow'], $robots['index']);
    }
    return $robots;
});

add_action('wp_head', function () {
    if (!canal_etape_is_page()) {
        return;
    }
    $s = canal_etape_state();
    $home = home_url('/');
    $canal = ['@type' => 'TouristDestination', '@id' => $home . '#canal-du-midi', 'name' => 'Canal du Midi', 'sameAs' => ['https://www.wikidata.org/wiki/Q202494', CANAL_FICHE_UNESCO_URL]];
    $index = home_url(CANAL_ETAPES_PATH);
    echo '<link rel="canonical" href="' . esc_url($s['url']) . '">' . "\n";
    if (isset($s['index'])) {
        $desc = 'Parcours en bateau ou à vélo sur le Canal du Midi pour une journée, un week-end ou une semaine, et les étapes de Toulouse à l’étang de Thau avec distances, écluses et temps.';
        echo canal_home_seo_social(CANAL_ETAPES_TITLE, $desc, $s['url'], 'Le Canal du Midi', home_url(CANAL_HOME_HERO_IMAGE)); // phpcs:ignore
        $items = [];
        foreach (array_merge($s['index']['midi'], $s['index']['robine']) as $i => $it) {
            $place = ['@type' => 'TouristDestination', 'name' => $it['e']['name'], 'url' => $it['url'], 'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $it['e']['lat'], 'longitude' => $it['e']['lng']]];
            if ($it['voir']) {
                $place['includesAttraction'] = array_map(function ($name) { return ['@type' => 'TouristAttraction', 'name' => $name]; }, $it['voir']);
            }
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $place];
        }
        $graph = [['@type' => 'CollectionPage', 'url' => $s['url'], 'name' => CANAL_ETAPES_TITLE, 'description' => $desc, 'inLanguage' => 'fr-FR', 'about' => $canal, 'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $items]]];
        if ($s['index']['faq']) {
            $graph[] = ['@type' => 'FAQPage', 'mainEntity' => array_map(function ($qa) {
                return ['@type' => 'Question', 'name' => $qa['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']]];
            }, $s['index']['faq'])];
        }
        echo canal_home_seo_jsonld(['@context' => 'https://schema.org', '@graph' => $graph]); // phpcs:ignore
        return;
    }
    $e = $s['e'];
    $desc = canal_fiche_excerpt($s['lead'] . ' ' . ($s['count'] ? $s['count'] . ' prestataires référencés : location de bateaux, hébergements, restaurants et visites.' : 'Écluses, ports et distances.'));
    echo canal_home_seo_social(canal_etape_title($e), $desc, $s['url'], $e['name'], $s['hero']); // phpcs:ignore
    $graph = [
        [
            '@type'            => 'TouristDestination',
            '@id'              => $s['url'] . '#destination',
            'name'             => $e['name'],
            'url'              => $s['url'],
            'description'      => $desc,
            'image'            => $s['hero'],
            'geo'              => ['@type' => 'GeoCoordinates', 'latitude' => $e['lat'], 'longitude' => $e['lng']],
            'containedInPlace' => $canal,
        ],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => home_url(CANAL_HOME_PATH)],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Étapes du Canal du Midi', 'item' => $index],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $e['name'], 'item' => $s['url']],
        ]],
    ];
    if ($s['faq']) {
        $graph[] = ['@type' => 'FAQPage', 'mainEntity' => array_map(function ($qa) {
            return ['@type' => 'Question', 'name' => $qa['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']]];
        }, $s['faq'])];
    }
    echo canal_home_seo_jsonld(['@context' => 'https://schema.org', '@graph' => $graph]); // phpcs:ignore
}, 5);
