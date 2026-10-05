<?php
/**
 * Plantilla de contenido 2026 (TASK-055): funciones puras (sin WordPress), con test en tests/test-contenu.php.
 * Cualquier página o artículo se ve en /<ruta>-2026/ (contenu-route.php). Spec:
 * docs/superpowers/specs/2026-10-05-plantilla-contenido-2026-design.md
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

// Al publicar → '' (y el router pasa a servir las URLs originales: tarea de publicación, aún no hecha).
const CANAL_CONTENU_SUFFIX = '-2026';
// Páginas de contenido; las demás plantillas son herramientas, formularios o listados antiguos (404 en -2026).
const CANAL_CONTENU_PAGE_TEMPLATES = ['', 'default', 'templates/content-sidebar.php'];
// Fuera también las « demande… » (las sustituye el planificador: links-2026.php las enlaza allí) y el agenda vacío.
const CANAL_CONTENU_EXCLUDED_SLUGS = ['boutique', 'panier', 'paiement', 'mon-compte', 'claim-list', 'site-web-en-maintenance', 'votre-demande-de-guide-est-valide',
    'agenda', 'demande-de-location-de-bateau', 'demande-de-promenade-en-bateaux', 'demande-dhebergement-le-long-du-canal-du-midi'];
// Páginas cuyo contenido es un plugin que debe seguir funcionando igual: el pedido del plan en papel (CF7 + PayPal, 7 €)
// y la boutique (TablePress). En ellas no se quitan los scripts de esos plugins (canal_carte_dequeue_unused).
const CANAL_CONTENU_PLUGIN_SLUGS = ['recevoir-le-plan-du-canal-du-midi-2', 'boutique-canal-du-midi'];
const CANAL_CONTENU_PLUGIN_ASSETS = '/^(contact-form-7|wpcf7|swv|cf7pp|wpecpp|wp-ecommerce-paypal|tablepress)/';
const CANAL_CONTENU_MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

// « navigation/regles-de-navigation-2026 » → « navigation/regles-de-navigation »; null si no es una ruta -2026.
// ponytail: con sufijo vacío (publicado) no hay router; publicar = otra tarea (template_include en las URLs originales).
function canal_contenu_strip_suffix(string $request, string $suffix): ?string
{
    $request = trim($request, '/');
    $len = strlen($suffix);
    if ($len === 0 || strlen($request) <= $len || substr($request, -$len) !== $suffix) {
        return null;
    }
    $path = substr($request, 0, -$len);
    return substr($path, -1) === '/' ? null : $path;
}

// Todos los artículos publicados (decisión del usuario: también los que no tienen tráfico); páginas de contenido.
function canal_contenu_keeps_plugins(string $slug): bool
{
    return in_array($slug, CANAL_CONTENU_PLUGIN_SLUGS, true);
}

function canal_contenu_is_eligible(string $type, string $status, string $template, string $slug): bool
{
    if ($status !== 'publish' || in_array($slug, CANAL_CONTENU_EXCLUDED_SLUGS, true)) {
        return false;
    }
    return $type === 'post' || ($type === 'page' && in_array($template, CANAL_CONTENU_PAGE_TEMPLATES, true));
}

// Limpieza mínima tras the_content del núcleo: la página ya tiene su H1, y los « títulos » en negrita de los
// textos antiguos pasan a <h2> (estructura para buscadores y respuestas de IA). Las etiquetas « X : » no.
function canal_contenu_clean_html(string $html): string
{
    $html = (string) preg_replace('~\[/?Zoomer\]~i', '', $html); // shortcode de un plugin ya borrado
    $html = (string) preg_replace('~<h1(\s[^>]*)?>(.*?)</h1>~is', '<h2$1>$2</h2>', $html);
    $html = (string) preg_replace('~<p>\s*<(strong|b)>([^<:]{3,90})</\1>\s*</p>~i', '<h2>$2</h2>', $html);
    $html = (string) preg_replace('~<p>\s*<(strong|b)>([^<:]{3,90})</\1>\s*<br\s*/?>\s*~i', "<h2>$2</h2>\n<p>", $html);
    return (string) preg_replace('~<p>(?:\s|&nbsp;|\xC2\xA0|<br\s*/?>)*</p>~i', '', $html);
}

/** Texto plano para resúmenes: sin etiquetas ni entidades (&nbsp; cortado a medias por el recorte). */
function canal_contenu_plain(string $html): string
{
    $text = html_entity_decode(strip_tags((string) preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
}

function canal_contenu_seo_title(string $title): string
{
    return strpos(canal_carte_fold($title), 'canal du midi') !== false ? $title : $title . ' — Canal du Midi';
}

// « 2017-04-20 09:33:14 » → « 20 avril 2017 » (« 1er » el día 1).
function canal_contenu_date_fr(string $ymd): string
{
    $t = strtotime($ymd);
    if ($t === false) {
        return '';
    }
    $day = (int) date('j', $t);
    return ($day === 1 ? '1er' : (string) $day) . ' ' . CANAL_CONTENU_MONTHS[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

// Artículos de hace más de 3 años: aviso honesto de fecha (confianza / E-E-A-T). Las páginas son guías vivas.
function canal_contenu_old_notice(string $type, string $published, string $today): string
{
    $limit = date('Y-m-d', (int) strtotime('-3 years', (int) strtotime($today)));
    if ($type !== 'post' || substr($published, 0, 10) >= $limit) {
        return '';
    }
    return 'Article publié en ' . substr($published, 0, 4) . ' : certaines informations ont pu changer.';
}

// _canal_2026_faq (fase 2): [{"q","a"}]; lo que no encaja se ignora.
function canal_contenu_faq(string $json): array
{
    $data = json_decode($json, true);
    $out = [];
    foreach (is_array($data) ? $data : [] as $qa) {
        $q = is_array($qa) && is_string($qa['q'] ?? null) ? trim($qa['q']) : '';
        $a = is_array($qa) && is_string($qa['a'] ?? null) ? trim($qa['a']) : '';
        if ($q !== '' && $a !== '') {
            $out[] = ['q' => $q, 'a' => $a];
        }
    }
    return $out;
}

// JSON-LD: Article (artículos) o WebPage (páginas) sobre el Canal du Midi (mismo @id que home y ficha), migas y FAQ.
function canal_contenu_seo_graph(array $c, string $url, string $homeUrl, string $siteName): array
{
    $notEmpty = function ($v) { return $v !== '' && $v !== []; };
    $isPost = $c['type'] === 'post';
    $org = ['@type' => 'Organization', '@id' => $homeUrl . '#organization', 'name' => $siteName, 'url' => $homeUrl];
    $main = array_filter([
        '@type'            => $isPost ? 'Article' : 'WebPage',
        '@id'              => $url . ($isPost ? '#article' : '#webpage'),
        'url'              => $url,
        'headline'         => $isPost ? $c['title'] : '',
        'name'             => $c['title'],
        'description'      => $c['description'],
        'datePublished'    => $c['published'],
        'dateModified'     => $c['modified'],
        'image'            => $c['image'],
        'inLanguage'       => 'fr-FR',
        'isPartOf'         => ['@type' => 'WebSite', '@id' => $homeUrl . '#website', 'name' => $siteName, 'url' => $homeUrl],
        'author'           => $org,
        'publisher'        => $org,
        'mainEntityOfPage' => $isPost ? $url : '',
        'about'            => ['@id' => $homeUrl . '#canal-du-midi'],
        'breadcrumb'       => ['@id' => $url . '#breadcrumb'],
    ], $notEmpty);
    $canal = [
        '@type'  => 'TouristDestination',
        '@id'    => $homeUrl . '#canal-du-midi',
        'name'   => 'Canal du Midi',
        'sameAs' => ['https://fr.wikipedia.org/wiki/Canal_du_Midi', 'https://www.wikidata.org/wiki/Q202494', CANAL_FICHE_UNESCO_URL],
    ];
    $crumbs = [];
    foreach (array_values($c['crumbs']) as $i => $crumb) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[1], 'item' => $crumb[0]];
    }
    $graph = [$main, $canal, ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $crumbs]];
    if ($c['faq']) {
        $graph[] = ['@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => array_map(function ($qa) {
            return ['@type' => 'Question', 'name' => $qa['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']]];
        }, $c['faq'])];
    }
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

// ---- Archivos del blog (TASK-059): /post-category/<ruta>-2026/[page/N/] con páginas y artículos de la categoría ----

const CANAL_ARCHIVE_PER_PAGE = 12;

/** « post-category/actualites/divers-2026/page/3 » → ['path' => 'actualites/divers', 'page' => 3]; null si no es un archivo 2026. */
function canal_archive_parse(string $request, string $suffix): ?array
{
    if ($suffix === '' || !preg_match('#^post-category/(.+?)' . preg_quote($suffix, '#') . '(?:/page/(\d+))?/?$#', trim($request, '/'), $m)) {
        return null;
    }
    $page = isset($m[2]) ? (int) $m[2] : 1;
    return $page >= 1 ? ['path' => $m[1], 'page' => $page] : null;
}

function canal_archive_path(string $path, int $page, string $suffix): string
{
    return '/post-category/' . $path . $suffix . '/' . ($page > 1 ? 'page/' . $page . '/' : '');
}

function canal_archive_pages(int $total, int $perPage): int
{
    return (int) ceil($total / $perPage);
}

/** Páginas que se muestran en la paginación: primera, última y las vecinas de la actual. */
function canal_archive_window(int $page, int $pages): array
{
    $out = [];
    foreach ([1, $page - 1, $page, $page + 1, $pages] as $n) {
        if ($n >= 1 && $n <= $pages && !in_array($n, $out, true)) {
            $out[] = $n;
        }
    }
    sort($out);
    return $out;
}
