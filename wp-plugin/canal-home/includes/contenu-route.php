<?php
/**
 * Ruta /<ruta>-2026/ de la plantilla de contenido (TASK-055): cualquier página o artículo elegible
 * (contenu-core.php) con el diseño 2026. Sin regla de reescritura: parse_request quita el sufijo si no existe un
 * post real con esa ruta (/accueil-2026/, /explorer-2026/ siguen siendo los suyos). Privada como la ficha.
 */
defined('ABSPATH') || exit;

function canal_contenu_url(WP_Post $p): string
{
    return home_url('/' . get_page_uri($p) . CANAL_CONTENU_SUFFIX . '/');
}

// Estado de la petición (contenido resuelto); vacío fuera de la plantilla.
function canal_contenu_state(?array $set = null): array
{
    static $state = [];
    if ($set !== null) {
        $state = $set;
    }
    return $state;
}

function canal_contenu_is_page(): bool
{
    return canal_contenu_state() !== [];
}

function canal_contenu_post_eligible(WP_Post $p): bool
{
    return canal_contenu_is_eligible($p->post_type, $p->post_status, (string) get_page_template_slug($p), $p->post_name);
}

/** Páginas de la misma rúbrica: hermanas (o hijas si no tiene madre), elegibles, enlazadas en versión 2026. */
function canal_contenu_siblings(WP_Post $p): array
{
    if ($p->post_type !== 'page') {
        return [];
    }
    $out = [];
    foreach (get_pages(['parent' => $p->post_parent ?: $p->ID, 'sort_column' => 'menu_order,post_title']) as $s) {
        if ($s->ID !== $p->ID && canal_contenu_post_eligible($s)) {
            $out[] = [canal_contenu_url($s), canal_fiche_display_title(wp_strip_all_tags($s->post_title))];
        }
    }
    return $out;
}

function canal_contenu_data(WP_Post $p): array
{
    $GLOBALS['post'] = $p;
    setup_postdata($p);
    // the_content del núcleo sin los filtros de plugins: Elementor no pinta su constructor (su CSS/JS no se carga);
    // las páginas Elementor guardan en post_content su versión HTML.
    $html = canal_contenu_clean_html(wp_filter_content_tags(do_shortcode(shortcode_unautop(wpautop(wptexturize(do_blocks($p->post_content)))))));
    $meta = function (string $key) use ($p): string {
        return trim((string) get_post_meta($p->ID, $key, true));
    };
    $title = $meta('_canal_2026_title');
    if ($title === '') {
        $title = canal_fiche_display_title(wp_strip_all_tags($p->post_title));
    }
    $description = $meta('_canal_2026_description');
    if ($description === '') {
        $description = canal_fiche_excerpt(canal_contenu_plain($p->post_excerpt !== '' ? $p->post_excerpt : $html));
    }
    $image = (string) get_the_post_thumbnail_url($p, 'large');
    if ($image === '' && preg_match('~<img[^>]+src="([^"]+)"~i', $html, $m)) {
        $image = $m[1];
    }
    $url = canal_contenu_url($p);
    $crumbs = [[home_url(CANAL_HOME_PATH), 'Accueil']];
    if ($p->post_type === 'page' && $p->post_parent && ($parent = get_post($p->post_parent)) && canal_contenu_post_eligible($parent)) {
        $crumbs[] = [canal_contenu_url($parent), canal_fiche_display_title(wp_strip_all_tags($parent->post_title))];
    }
    if ($p->post_type === 'post') {
        foreach (get_the_category($p->ID) as $cat) {
            if ($cat->slug !== 'non-classe') {
                $crumbs[] = [get_category_link($cat), $cat->name];
                break;
            }
        }
    }
    $crumbs[] = [$url, $title];
    return [
        'id'          => $p->ID,
        'plugins'     => canal_contenu_keeps_plugins($p->post_name),
        'type'        => $p->post_type,
        'title'       => $title,
        'description' => $description,
        'summary'     => $meta('_canal_2026_summary'),
        'faq'         => canal_contenu_faq($meta('_canal_2026_faq')),
        'html'        => $html,
        'published'   => (string) get_post_time('c', false, $p),
        'modified'    => (string) get_post_modified_time('c', false, $p),
        'image'       => $image !== '' ? $image : home_url(CANAL_HOME_HERO_IMAGE),
        'thumb'       => (string) get_the_post_thumbnail($p, 'large', ['class' => 'contenu-cover', 'loading' => 'eager', 'fetchpriority' => 'high']),
        'crumbs'      => $crumbs,
        'siblings'    => canal_contenu_siblings($p),
        'notice'      => canal_contenu_old_notice($p->post_type, $p->post_date, current_time('Y-m-d')),
        'url'         => $url,
    ];
}

add_filter('query_vars', function (array $vars) {
    $vars[] = 'canal_contenu';
    return $vars;
});

add_action('parse_request', function (WP $wp) {
    $path = canal_contenu_strip_suffix((string) $wp->request, CANAL_CONTENU_SUFFIX);
    if ($path === null || get_page_by_path($wp->request, OBJECT, ['page', 'post'])) {
        return;
    }
    $post = get_page_by_path($path, OBJECT, ['page', 'post']);
    if ($post instanceof WP_Post && canal_contenu_post_eligible($post)) {
        $wp->query_vars = ['canal_contenu' => (string) $post->ID];
    }
});

add_action('template_redirect', function () {
    $id = (int) get_query_var('canal_contenu');
    if ($id === 0) {
        return;
    }
    global $wp_query;
    // Una query var sola deja is_home = true: sin set_404 se vería la portada del blog.
    if (!canal_fiche_can_view(current_user_can('read_private_pages'), get_option('canal_contenu_public'))) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    canal_contenu_state(canal_contenu_data(get_post($id)));
    $wp_query->is_home = false;
    $wp_query->is_404  = false;
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();
    status_header(200);
    header('Link: <' . esc_url_raw(home_url('/llms.txt')) . '>; rel="llms-txt"', false);
    include CANAL_HOME_DIR . 'template-contenu.php';
    exit;
}, 0);

// WPFC no respeta DONOTCACHEPAGE: al abrir o cerrar las páginas de contenido 2026 se vacía la caché de página.
foreach (['add_option_', 'update_option_', 'delete_option_'] as $canal_contenu_prefix) {
    add_action($canal_contenu_prefix . 'canal_contenu_public', function () {
        do_action('wpfc_clear_all_cache');
    });
}
unset($canal_contenu_prefix);

add_action('wp_enqueue_scripts', function () {
    if (!canal_contenu_is_page()) {
        return;
    }
    canal_home_inline_style('canal-home-base', canal_home_base_css());
    // Todo en línea (contenu.css es pequeño): cero peticiones de CSS antes del primer render.
    canal_home_inline_file('canal-contenu', 'assets/contenu.css', ['canal-home-base']);
    canal_home_inline_file('canal-home-header', 'assets/header.css', ['canal-home-base']);
    // Base del tema (Bootstrap, pie) que usa la ficha; nuestras páginas comparten cabecera y pie.
    wp_enqueue_style('canal-fiche-theme', CANAL_HOME_URL . 'assets/fiche-theme.css', ['canal-contenu', 'canal-home-header'], (string) filemtime(CANAL_HOME_DIR . 'assets/fiche-theme.css'));
    wp_add_inline_style('canal-fiche-theme', CANAL_THEME_FIX_CSS);
}, 20);

add_filter('pre_get_document_title', function ($title) {
    return canal_contenu_is_page() ? canal_contenu_seo_title(canal_contenu_state()['title']) : $title;
}, 20);

add_filter('wp_robots', function (array $robots) {
    if (canal_contenu_is_page() && CANAL_CONTENU_SUFFIX !== '') {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset($robots['follow'], $robots['index']);
    }
    return $robots;
});

add_action('wp_head', function () {
    if (!canal_contenu_is_page()) {
        return;
    }
    $c = canal_contenu_state();
    echo '<link rel="canonical" href="' . esc_url($c['url']) . '">' . "\n";
    echo '<link rel="alternate" type="text/markdown" title="llms.txt" href="' . esc_url(home_url('/llms.txt')) . '">' . "\n";
    echo canal_home_seo_social(canal_contenu_seo_title($c['title']), $c['description'], $c['url'], $c['title'], $c['image']); // phpcs:ignore — escapado dentro.
    echo canal_home_seo_jsonld(canal_contenu_seo_graph($c, $c['url'], home_url('/'), CANAL_HOME_SITE_NAME)); // phpcs:ignore — JSON_HEX_TAG.
}, 5);
