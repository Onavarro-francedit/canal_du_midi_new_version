<?php
/**
 * Archivos del blog 2026 (TASK-059): /post-category/<ruta>-2026/[page/N/] con las páginas y artículos de la categoría
 * (las del menú mezclan ambos: « Villes à visiter » son páginas, « Actualités » artículos). Misma estética y
 * privacidad que la plantilla de contenido (canal_contenu_public abre las dos).
 */
defined('ABSPATH') || exit;

function canal_archive_state(?array $set = null): array
{
    static $state = [];
    if ($set !== null) {
        $state = $set;
    }
    return $state;
}

function canal_archive_is_page(): bool
{
    return canal_archive_state() !== [];
}

/** URL de cada elemento: su versión 2026 si la tiene, si no la de siempre. */
function canal_archive_item_url(WP_Post $p): string
{
    return canal_contenu_post_eligible($p) ? canal_contenu_url($p) : (string) get_permalink($p);
}

function canal_archive_item(WP_Post $p): array
{
    $image = (string) get_the_post_thumbnail_url($p, 'medium_large');
    if ($image === '' && preg_match('~<img[^>]+src="([^"]+)"~i', $p->post_content, $m)) {
        $image = canal_fiche_https($m[1]);
    }
    $text = $p->post_excerpt !== '' ? $p->post_excerpt : strip_shortcodes(preg_replace('~\[/?Zoomer\]~i', '', $p->post_content));
    return [
        'url'     => canal_archive_item_url($p),
        'title'   => canal_fiche_display_title(wp_strip_all_tags($p->post_title)),
        'date'    => $p->post_type === 'post' ? canal_contenu_date_fr($p->post_date) : '',
        'iso'     => (string) get_post_time('c', false, $p),
        'excerpt' => canal_fiche_excerpt(canal_contenu_plain($text), 150),
        'image'   => $image,
    ];
}

function canal_archive_data(WP_Term $term, int $page): ?array
{
    $q = new WP_Query([
        'post_type'      => ['post', 'page'],
        'post_status'    => 'publish',
        'cat'            => $term->term_id,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => CANAL_ARCHIVE_PER_PAGE,
        'paged'          => $page,
        'ignore_sticky_posts' => true,
    ]);
    $pages = canal_archive_pages((int) $q->found_posts, CANAL_ARCHIVE_PER_PAGE);
    if (!$q->posts || $page > $pages) {
        return null;
    }
    $path = trim((string) get_category_parents($term->term_id, false, '/', true), '/');
    $url = function (WP_Term $t, int $n = 1): string {
        return home_url(canal_archive_path(trim((string) get_category_parents($t->term_id, false, '/', true), '/'), $n, CANAL_CONTENU_SUFFIX));
    };
    $crumbs = [[home_url(CANAL_HOME_PATH), 'Accueil']];
    if ($term->parent && ($parent = get_term($term->parent, 'category')) instanceof WP_Term) {
        $crumbs[] = [$url($parent), html_entity_decode($parent->name, ENT_QUOTES, 'UTF-8')];
    }
    $name = html_entity_decode($term->name, ENT_QUOTES, 'UTF-8');
    $crumbs[] = [$url($term), $name];
    $children = [];
    foreach (get_terms(['taxonomy' => 'category', 'parent' => $term->term_id, 'hide_empty' => true]) as $child) {
        $children[] = [$url($child), html_entity_decode($child->name, ENT_QUOTES, 'UTF-8'), (int) $child->count];
    }
    return [
        'name'     => $name,
        'path'     => $path,
        'page'     => $page,
        'pages'    => $pages,
        'total'    => (int) $q->found_posts,
        'items'    => array_map('canal_archive_item', $q->posts),
        'children' => $children,
        'crumbs'   => $crumbs,
        'url'      => $url($term, $page),
        'pageUrl'  => function (int $n) use ($url, $term): string { return $url($term, $n); },
    ];
}

add_filter('query_vars', function (array $vars) {
    $vars[] = 'canal_archive';
    $vars[] = 'canal_archive_page';
    return $vars;
});

// Antes que el router de contenido: una categoría del blog no es una página.
add_action('parse_request', function (WP $wp) {
    $a = canal_archive_parse((string) $wp->request, CANAL_CONTENU_SUFFIX);
    $term = $a ? get_category_by_path($a['path'], true) : null;
    if ($term instanceof WP_Term && (int) $term->count > 0) {
        $wp->query_vars = ['canal_archive' => (string) $term->term_id, 'canal_archive_page' => (string) $a['page']];
    }
}, 6);

add_action('template_redirect', function () {
    $id = (int) get_query_var('canal_archive');
    if ($id === 0) {
        return;
    }
    global $wp_query;
    $term = get_term($id, 'category');
    $data = $term instanceof WP_Term && canal_fiche_can_view(current_user_can('read_private_pages'), get_option('canal_contenu_public'))
        ? canal_archive_data($term, max(1, (int) get_query_var('canal_archive_page')))
        : null;
    if (!$data) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    canal_archive_state($data);
    $wp_query->is_home = false;
    $wp_query->is_404  = false;
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();
    status_header(200);
    include CANAL_HOME_DIR . 'template-archive.php';
    exit;
}, 0);

add_action('wp_enqueue_scripts', function () {
    if (!canal_archive_is_page()) {
        return;
    }
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    canal_home_inline_file('canal-contenu', 'assets/contenu.css', ['canal-home-base']);
    canal_home_inline_file('canal-home-header', 'assets/header.css', ['canal-home-base']);
    wp_enqueue_style('canal-fiche-theme', CANAL_HOME_URL . 'assets/fiche-theme.css', ['canal-contenu', 'canal-home-header'], (string) filemtime(CANAL_HOME_DIR . 'assets/fiche-theme.css'));
    wp_add_inline_style('canal-fiche-theme', CANAL_THEME_FIX_CSS);
}, 20);

function canal_archive_title(array $a): string
{
    return canal_contenu_seo_title($a['name']) . ($a['page'] > 1 ? ' — page ' . $a['page'] : '');
}

add_filter('pre_get_document_title', function ($title) {
    return canal_archive_is_page() ? canal_archive_title(canal_archive_state()) : $title;
}, 20);

add_filter('wp_robots', function (array $robots) {
    if (canal_archive_is_page() && CANAL_CONTENU_SUFFIX !== '') {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['follow'], $robots['index']);
    }
    return $robots;
});

add_action('wp_head', function () {
    if (!canal_archive_is_page()) {
        return;
    }
    $a = canal_archive_state();
    $home = home_url('/');
    $desc = sprintf('« %s » sur le Canal du Midi : %d articles et guides, de Toulouse à l’étang de Thau.', $a['name'], $a['total']);
    echo '<link rel="canonical" href="' . esc_url($a['url']) . '">' . "\n";
    if ($a['page'] > 1) {
        echo '<link rel="prev" href="' . esc_url(($a['pageUrl'])($a['page'] - 1)) . '">' . "\n";
    }
    if ($a['page'] < $a['pages']) {
        echo '<link rel="next" href="' . esc_url(($a['pageUrl'])($a['page'] + 1)) . '">' . "\n";
    }
    echo canal_home_seo_social(canal_archive_title($a), $desc, $a['url'], $a['name'], $a['items'][0]['image'] ?: home_url(CANAL_HOME_HERO_IMAGE)); // phpcs:ignore — escapado dentro.
    $crumbs = [];
    foreach ($a['crumbs'] as $i => $c) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[1], 'item' => $c[0]];
    }
    echo canal_home_seo_jsonld(['@context' => 'https://schema.org', '@graph' => [ // phpcs:ignore — JSON_HEX_TAG.
        [
            '@type'       => 'CollectionPage',
            '@id'         => $a['url'] . '#collection',
            'url'         => $a['url'],
            'name'        => canal_archive_title($a),
            'description' => $desc,
            'inLanguage'  => 'fr-FR',
            'isPartOf'    => ['@type' => 'WebSite', '@id' => $home . '#website', 'name' => CANAL_HOME_SITE_NAME, 'url' => $home],
            'about'       => ['@id' => $home . '#canal-du-midi'],
            'mainEntity'  => ['@type' => 'ItemList', 'itemListElement' => array_map(function ($item, $i) {
                return ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $item['url'], 'name' => $item['title']];
            }, $a['items'], array_keys($a['items']))],
        ],
        ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs],
    ]]);
}, 5);
