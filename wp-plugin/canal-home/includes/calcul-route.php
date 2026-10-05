<?php
/**
 * Calcul de distance 2026 (TASK-057): ruta /calcul-de-distance-canal-du-midi-2026/ (la página actual, con plantilla
 * propia, no es « contenido »). Privada como la ficha. El resultado inicial lo pinta el servidor (?de=&a=); calcul.js
 * recalcula en vivo y carga el mapa (Google Maps + trazado OSM) en diferido.
 */
defined('ABSPATH') || exit;

const CANAL_CALCUL_DEFAULT = ['Castelnaudary', 'Trèbes'];

function canal_calcul_url(): string
{
    return home_url('/' . CANAL_CALCUL_SLUG . CANAL_CONTENU_SUFFIX . '/');
}

function canal_calcul_state(?array $set = null): array
{
    static $state = [];
    if ($set !== null) {
        $state = $set;
    }
    return $state;
}

function canal_calcul_is_page(): bool
{
    return canal_calcul_state() !== [];
}

/** Foto de cada ficha de esclusa (talla « medium » de la mediateca si existe), en caché un día. */
function canal_calcul_lock_photos(): array
{
    $photos = get_transient('canal_calcul_photos_v1');
    if (is_array($photos)) {
        return $photos;
    }
    $photos = [];
    foreach (CANAL_CALCUL_LOCKS as $l) {
        $post = $l['slug'] !== '' ? get_page_by_path($l['slug'], OBJECT, 'job_listing') : null;
        $cover = $post ? canal_home_cover($post->ID) : '';
        if ($cover === '') {
            continue;
        }
        $id = attachment_url_to_postid($cover);
        $medium = $id ? wp_get_attachment_image_url($id, 'medium') : false;
        $photos[$l['slug']] = canal_fiche_https($medium ?: $cover);
    }
    set_transient('canal_calcul_photos_v1', $photos, DAY_IN_SECONDS);
    return $photos;
}

/** Esclusas listas para la plantilla y el JS: PK, sas, nombre, URL de la ficha 2026 y foto. */
function canal_calcul_locks_view(): array
{
    $photos = canal_calcul_lock_photos();
    return array_map(function ($l) use ($photos) {
        return [
            'pk'    => $l['pk'],
            'sas'   => $l['sas'],
            'name'  => $l['name'],
            'url'   => $l['slug'] !== '' ? canal_fiche_url($l['slug']) : '',
            'photo' => $photos[$l['slug']] ?? '',
        ];
    }, CANAL_CALCUL_LOCKS);
}

function canal_calcul_data(string $from, string $to): array
{
    $r = canal_calcul_compute(canal_calcul_pk($from), canal_calcul_pk($to));
    $locks = canal_calcul_locks_view();
    $byName = array_column($locks, null, 'name');
    return [
        'from'     => $from,
        'to'       => $to,
        'r'        => $r,
        'sentence' => canal_calcul_sentence($from, $to, $r),
        'locks'    => $locks,
        'route'    => array_map(function ($l) use ($byName) { return $byName[$l['name']]; }, canal_calcul_locks_between(canal_calcul_pk($from), canal_calcul_pk($to))),
        'arrival'  => canal_calcul_arrival($to),
        'matrix'   => canal_calcul_matrix(),
        'url'      => canal_calcul_url(),
    ];
}

add_filter('query_vars', function (array $vars) {
    $vars[] = 'canal_calcul';
    return $vars;
});

// Antes que el router de contenido (prioridad 10): esta página tiene plantilla propia y no es elegible allí.
add_action('parse_request', function (WP $wp) {
    if (canal_contenu_strip_suffix((string) $wp->request, CANAL_CONTENU_SUFFIX) === CANAL_CALCUL_SLUG) {
        $wp->query_vars = ['canal_calcul' => '1'];
    }
}, 5);

add_action('template_redirect', function () {
    if (get_query_var('canal_calcul') !== '1') {
        return;
    }
    global $wp_query;
    if (!canal_fiche_can_view(current_user_can('read_private_pages'), get_option('canal_calcul_public'))) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    $from = canal_calcul_find((string) wp_unslash($_GET['de'] ?? '')) ?? CANAL_CALCUL_DEFAULT[0];
    $to = canal_calcul_find((string) wp_unslash($_GET['a'] ?? '')) ?? CANAL_CALCUL_DEFAULT[1];
    canal_calcul_state(canal_calcul_data($from, $to));
    $wp_query->is_home = false;
    $wp_query->is_404  = false;
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();
    status_header(200);
    header('Link: <' . esc_url_raw(home_url('/llms.txt')) . '>; rel="llms-txt"', false);
    include CANAL_HOME_DIR . 'template-calcul.php';
    exit;
}, 0);

// WPFC no respeta DONOTCACHEPAGE: al abrir o cerrar la página se vacía la caché de página.
foreach (['add_option_', 'update_option_', 'delete_option_'] as $canal_calcul_prefix) {
    add_action($canal_calcul_prefix . 'canal_calcul_public', function () {
        do_action('wpfc_clear_all_cache');
    });
}
unset($canal_calcul_prefix);

add_action('wp_enqueue_scripts', function () {
    if (!canal_calcul_is_page()) {
        return;
    }
    $c = canal_calcul_state();
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    canal_home_inline_file('canal-calcul', 'assets/calcul.css', ['canal-home-base']);
    canal_home_inline_file('canal-home-header', 'assets/header.css', ['canal-home-base']);
    wp_enqueue_style('canal-fiche-theme', CANAL_HOME_URL . 'assets/fiche-theme.css', ['canal-calcul', 'canal-home-header'], (string) filemtime(CANAL_HOME_DIR . 'assets/fiche-theme.css'));
    wp_add_inline_style('canal-fiche-theme', CANAL_THEME_FIX_CSS);
    wp_enqueue_script('canal-calcul', CANAL_HOME_URL . 'assets/calcul.js', [], (string) filemtime(CANAL_HOME_DIR . 'assets/calcul.js'), ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('canal-calcul', 'CDM_CALCUL', [
        'locks'    => $c['locks'],
        'towns'    => CANAL_CALCUL_TOWNS,
        'model'    => [
            'boatKmh' => CANAL_CALCUL_BOAT_KMH, 'minPerSas' => CANAL_CALCUL_MIN_PER_SAS, 'boatHoursDay' => CANAL_CALCUL_BOAT_HOURS_DAY,
            'bikeKmh' => CANAL_CALCUL_BIKE_KMH, 'bikeKmOneDay' => CANAL_CALCUL_BIKE_KM_ONE_DAY, 'bikeKmDay' => CANAL_CALCUL_BIKE_KM_DAY,
            'walkKmh' => CANAL_CALCUL_WALK_KMH, 'walkKmDay' => CANAL_CALCUL_WALK_KM_DAY,
        ],
        'carteUrl' => home_url(CANAL_CARTE_PATH),
        'traceUrl' => CANAL_HOME_URL . 'assets/calcul/canal-du-midi-trace.json?ver=' . filemtime(CANAL_HOME_DIR . 'assets/calcul/canal-du-midi-trace.json'),
    ]);
}, 20);

// Google Maps: el tema lo registra tarde y se quita de la cola en esta página (canal_fiche_is_unused_asset);
// calcul.js lo carga en diferido con la misma URL (clave del sitio).
add_action('wp_print_footer_scripts', function () {
    if (!canal_calcul_is_page()) {
        return;
    }
    $maps = wp_scripts()->registered['google-maps'] ?? null;
    wp_add_inline_script('canal-calcul', 'window.CDM_CALCUL_MAPS = ' . wp_json_encode($maps ? (string) $maps->src : '') . ';', 'before');
}, 1);

const CANAL_CALCUL_TITLE = 'Calcul de distance sur le Canal du Midi : km, écluses, temps de trajet';
const CANAL_CALCUL_DESCRIPTION = 'Distance, nombre d’écluses et temps de trajet en bateau, à vélo ou à pied entre deux villes, ports ou écluses du Canal du Midi, de Toulouse à l’étang de Thau.';

add_filter('pre_get_document_title', function ($title) {
    return canal_calcul_is_page() ? CANAL_CALCUL_TITLE : $title;
}, 20);

add_filter('wp_robots', function (array $robots) {
    if (canal_calcul_is_page() && CANAL_CONTENU_SUFFIX !== '') {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['follow'], $robots['index']);
    }
    return $robots;
});

add_action('wp_head', function () {
    if (!canal_calcul_is_page()) {
        return;
    }
    $url = canal_calcul_url();
    $home = home_url('/');
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    echo canal_home_seo_social(CANAL_CALCUL_TITLE, CANAL_CALCUL_DESCRIPTION, $url, 'Le Canal du Midi', home_url(CANAL_HOME_HERO_IMAGE)); // phpcs:ignore — escapado dentro.
    echo canal_home_seo_jsonld(['@context' => 'https://schema.org', '@graph' => [ // phpcs:ignore — JSON_HEX_TAG.
        [
            '@type'               => 'WebApplication',
            '@id'                 => $url . '#app',
            'name'                => 'Calcul de distance sur le Canal du Midi',
            'url'                 => $url,
            'description'         => CANAL_CALCUL_DESCRIPTION,
            'applicationCategory' => 'TravelApplication',
            'operatingSystem'     => 'Tous',
            'isAccessibleForFree' => true,
            'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
            'inLanguage'          => 'fr-FR',
            'about'               => ['@type' => 'TouristDestination', '@id' => $home . '#canal-du-midi', 'name' => 'Canal du Midi', 'sameAs' => ['https://www.wikidata.org/wiki/Q202494', CANAL_FICHE_UNESCO_URL]],
            'publisher'           => ['@type' => 'Organization', '@id' => $home . '#organization', 'name' => CANAL_HOME_SITE_NAME],
        ],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => home_url(CANAL_HOME_PATH)],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Calcul de distance', 'item' => $url],
        ]],
    ]]);
}, 5);
