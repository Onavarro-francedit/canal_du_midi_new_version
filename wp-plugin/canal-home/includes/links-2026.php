<?php
/**
 * Enlaces internos de las páginas 2026 → su versión 2026 (navbar, pie, fichas, textos de contenido, home…).
 * Se aplica una vez a todo el <body> en el búfer de salida (head-fix.php), así ningún enlace se escapa aunque venga
 * del editor de WP. Al publicar, los sufijos quedan vacíos y las rutas coinciden con las originales: no cambia nada.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

// Archivos que siguen en el tema (sin versión 2026) o que no son páginas.
const CANAL_2026_KEEP = '#^/(wp-|feed/|region/|zone/|mot-cle/|tag/|author/|fiche-2026/)|\.[a-z0-9]{2,5}$#i';

/**
 * Ruta interna (sin host ni query) → su versión 2026, o la misma si no tiene.
 * $isContent: ¿página/artículo elegible? · $isArchive: ¿categoría del blog con contenido?
 */
function canal_2026_path(string $path, callable $isContent, ?callable $isArchive = null): string
{
    $trim = trim($path, '/');
    if ($trim === '') {
        return CANAL_HOME_PATH;
    }
    $is2026 = CANAL_CONTENU_SUFFIX !== '' && substr($trim, -strlen(CANAL_CONTENU_SUFFIX)) === CANAL_CONTENU_SUFFIX;
    if ($is2026 || preg_match(CANAL_2026_KEEP, $path)) {
        return $path;
    }
    if ($trim === 'explorer') {
        return CANAL_CARTE_PATH;
    }
    // Las « demande… » de antes: el planificador las sustituye (sin versión 2026).
    if ($trim === 'organiser-votre-sejour' || in_array($trim, ['demande-de-location-de-bateau', 'demande-de-promenade-en-bateaux', 'demande-dhebergement-le-long-du-canal-du-midi'], true)) {
        return CANAL_PLANNER_PATH;
    }
    if ($trim === CANAL_CALCUL_SLUG) {
        return '/' . CANAL_CALCUL_SLUG . CANAL_CONTENU_SUFFIX . '/';
    }
    if (preg_match('#^fiche/([^/]+)$#', $trim, $m)) {
        return CANAL_FICHE_PATH . $m[1] . '/';
    }
    if (preg_match('#^post-category/(.+?)(?:/page/(\d+))?$#', $trim, $m)) {
        return $isArchive && $isArchive($m[1]) ? canal_archive_path($m[1], (int) ($m[2] ?? 1), CANAL_CONTENU_SUFFIX) : $path;
    }
    if (preg_match('#^categorie/([^/]+)(/page/\d+)?$#', $trim, $m)) {
        return CANAL_CARTE_PATH . '?type=' . $m[1];
    }
    return $isContent($trim) ? '/' . $trim . CANAL_CONTENU_SUFFIX . '/' : $path;
}

/** Reescribe los href del <body> que apuntan al propio sitio ($host); conserva el host usado, la query y el ancla. */
function canal_2026_rewrite_html(string $html, string $host, callable $isContent, ?callable $isArchive = null): string
{
    $body = stripos($html, '<body');
    if ($body === false) {
        return $html;
    }
    $re = '#\bhref="(' . preg_quote($host, '#') . ')?(/[^"?\#]*)(\?[^"\#]*)?(\#[^"]*)?"#';
    $rest = preg_replace_callback($re, function (array $m) use ($isContent, $isArchive): string {
        $query = $m[3] ?? '';
        if ($m[2] === '/' && $query !== '') {
            return $m[0]; // búsquedas y ?page_id= de la home
        }
        $new = canal_2026_path($m[2], $isContent, $isArchive);
        if ($new === $m[2]) {
            return $m[0];
        }
        if ($query !== '') {
            $new .= (strpos($new, '?') === false ? '?' : '&amp;') . ltrim($query, '?'); // la query ya viene escapada
        } elseif (strpos($new, '?') !== false) {
            $new = str_replace('&', '&amp;', $new);
        }
        return 'href="' . $m[1] . $new . ($m[4] ?? '') . '"';
    }, substr($html, $body));
    return substr($html, 0, $body) . (string) $rest;
}

if (function_exists('add_action')) {
    /** ¿La ruta es una página o artículo con versión 2026? Caché en transient (12 h) para no consultar en cada visita. */
    function canal_2026_is_content(string $path): bool
    {
        static $map = null, $dirty = false;
        if ($map === null) {
            $map = get_transient('canal_2026_content_paths_v1');
            $map = is_array($map) ? $map : [];
            add_action('shutdown', function () use (&$map, &$dirty) {
                if ($dirty) {
                    set_transient('canal_2026_content_paths_v1', $map, 12 * HOUR_IN_SECONDS);
                }
            });
        }
        if (!array_key_exists($path, $map)) {
            $post = get_page_by_path($path, OBJECT, ['page', 'post']);
            $map[$path] = $post instanceof WP_Post && canal_contenu_post_eligible($post);
            $dirty = true;
        }
        return $map[$path];
    }

    /** ¿Categoría del blog con páginas o artículos publicados? (archivo 2026, TASK-059) */
    function canal_2026_is_archive(string $path): bool
    {
        $term = get_category_by_path($path, true);
        return $term instanceof WP_Term && (int) $term->count > 0;
    }

    function canal_2026_links_html(string $html): string
    {
        return canal_2026_rewrite_html($html, untrailingslashit(home_url()), 'canal_2026_is_content', 'canal_2026_is_archive');
    }
}
