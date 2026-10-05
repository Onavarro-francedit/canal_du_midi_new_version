# Plantilla de contenido 2026 — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** que cualquier página o artículo de WordPress de producción se vea con el diseño 2026 en `/<ruta>-2026/`, con
SEO, AEO y GEO automáticos y la lectura de los metadatos de la fase 2.

**Architecture:**
- Funciones puras en `includes/contenu-core.php`, con test CLI.
- Integración con WordPress en `includes/contenu-route.php`: `parse_request` resuelve el sufijo y `template_redirect`
  pinta la página.
- Marcado en `template-contenu.php` y estilos en `assets/contenu.css`.
- Las páginas 2026 existentes se reconocen con `canal_contenu_is_page()` en los mismos puntos que la ficha.

**Tech Stack:** WordPress (PHP 7.4 en producción), CSS a mano, tests en PHP CLI puro, `remote.sh`.

**Spec:** `docs/superpowers/specs/2026-10-05-plantilla-contenido-2026-design.md`

## Global Constraints

- PHP 7.4: sin `match`, sin tipos unión, sin `str_ends_with`, sin funciones flecha con `use` implícito sobre referencias.
- Solo añadir: no se modifica ningún post, opción ni archivo existente de WordPress.
- Ruta: `CANAL_CONTENU_SUFFIX = '-2026'`. Privada: `read_private_pages` o la opción `canal_contenu_public = '1'`.
- `noindex,nofollow` mientras el sufijo no esté vacío.
- Metadatos de la fase 2: `_canal_2026_title`, `_canal_2026_description`, `_canal_2026_summary`, `_canal_2026_faq`
  (JSON `[{"q","a"}]`).
- Textos visibles en francés. Comentarios de código en español, como el resto del plugin.

## Review Focus

1. `/accueil-2026/` y `/explorer-2026/` (páginas reales con sufijo) → deben seguir sirviendo su plantilla, no el contenido de « accueil » ni de « explorer ».
2. Un artículo y una página con el mismo slug → `get_page_by_path` con `['page','post']` devuelve la página; es aceptable (el mismo comportamiento que WP), pero no debe dar error.
3. Un párrafo en negrita que es una etiqueta (« <strong>Adresse :</strong><br> ») → no debe convertirse en `<h2>`.
4. `_canal_2026_faq` con JSON roto o con entradas vacías → se ignora, sin aviso de PHP.
5. Un artículo de hace exactamente 3 años o menos → sin aviso de antigüedad; uno de hace más → con aviso.

---

### Task 1: Núcleo puro (`contenu-core.php`) con tests

**Files:**
- Create: `wp-plugin/canal-home/includes/contenu-core.php`
- Create: `wp-plugin/tests/test-contenu.php`
- Modify: `wp-plugin/remote.sh` (en `run_test`, añadir el test)

**Interfaces:**
- Consumes: `canal_fiche_display_title(string): string`, `canal_fiche_excerpt(string, int): string`
  (`fiche-core.php`) y `canal_carte_fold(string): string` (`carte-filter.php`).
- Produces:
  - `CANAL_CONTENU_SUFFIX`;
  - `canal_contenu_strip_suffix(string $request, string $suffix): ?string`;
  - `canal_contenu_is_eligible(string $type, string $status, string $template, string $slug): bool`;
  - `canal_contenu_clean_html(string $html): string`;
  - `canal_contenu_seo_title(string $title): string`;
  - `canal_contenu_date_fr(string $ymd): string`;
  - `canal_contenu_old_notice(string $type, string $published, string $today): string`;
  - `canal_contenu_faq(string $json): array`;
  - `canal_contenu_seo_graph(array $c, string $url, string $homeUrl, string $siteName): array`, donde
    `$c = ['type','title','description','published','modified','image','crumbs' => [[url,name],…],'faq']`.

- [ ] **Step 1: test que falla** — `wp-plugin/tests/test-contenu.php` con los casos de abajo (ver el código en el repositorio tras el commit; cubre los 5 puntos de Review Focus que son puros: 1, 3, 4 y 5).
- [ ] **Step 2:** `php wp-plugin/tests/test-contenu.php` → falla (no existe `contenu-core.php`).
- [ ] **Step 3:** implementar `contenu-core.php`.
- [ ] **Step 4:** `php wp-plugin/tests/test-contenu.php` → `TODO OK`; añadirlo a `run_test` de `remote.sh`.
- [ ] **Step 5:** commit `feat(contenu): núcleo puro de la plantilla de contenido 2026`.

Código de referencia (núcleo):

```php
const CANAL_CONTENU_SUFFIX = '-2026';
const CANAL_CONTENU_PAGE_TEMPLATES = ['', 'default', 'templates/content-sidebar.php'];
const CANAL_CONTENU_EXCLUDED_SLUGS = ['boutique', 'panier', 'paiement', 'mon-compte', 'claim-list', 'site-web-en-maintenance', 'votre-demande-de-guide-est-valide'];

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

function canal_contenu_clean_html(string $html): string
{
    $html = (string) preg_replace('~\[/?Zoomer\]~i', '', $html);
    $html = (string) preg_replace('~<h1(\s[^>]*)?>(.*?)</h1>~is', '<h2$1>$2</h2>', $html);
    // Pseudo-títulos: <p><strong>Título</strong></p> o <p><strong>Título</strong><br> texto…; no las etiquetas « X : ».
    $html = (string) preg_replace('~<p>\s*<(strong|b)>([^<:]{3,90})</\1>\s*</p>~i', '<h2>$2</h2>', $html);
    $html = (string) preg_replace('~<p>\s*<(strong|b)>([^<:]{3,90})</\1>\s*<br\s*/?>\s*~i', "<h2>$2</h2>\n<p>", $html);
    return (string) preg_replace('~<p>(?:\s|&nbsp;|\xC2\xA0|<br\s*/?>)*</p>~i', '', $html);
}
```

### Task 2: Ruta, plantilla, estilos y SEO (`contenu-route.php`)

**Files:**
- Create: `wp-plugin/canal-home/includes/contenu-route.php`
- Create: `wp-plugin/canal-home/template-contenu.php`
- Create: `wp-plugin/canal-home/assets/contenu.css`
- Modify: `wp-plugin/canal-home/canal-home.php` (`require_once` de los dos archivos; `canal_carte_dequeue_unused` incluye `canal_contenu_is_page()`)
- Modify: `wp-plugin/canal-home/includes/header.php:10-14` (`canal_header_is_page`)
- Modify: `wp-plugin/canal-home/includes/head-fix.php:86-88` (`get_query_var('canal_contenu')`)
- Modify: `wp-plugin/canal-home/includes/fiche-route.php` (filtro `nav_menu_link_attributes`)
- Create: `wp-plugin/tests/smoke-contenu.php`

**Interfaces:**
- Consumes: todo lo de la Task 1; `canal_fiche_can_view()`, `canal_home_seo_social()`, `canal_home_seo_jsonld()`,
  `canal_home_inline_style()`, `canal_home_base_css()`, `CANAL_THEME_FIX_CSS`, `CANAL_CARTE_PATH`,
  `CANAL_PLAN_PDF_PATH`, `CANAL_HOME_PATH`, `CANAL_HOME_HERO_IMAGE` y `CANAL_HOME_SITE_NAME`.
- Produces:
  - `canal_contenu_url(WP_Post $p): string`;
  - `canal_contenu_state(?array $set = null): array`;
  - `canal_contenu_is_page(): bool`;
  - `canal_contenu_data(WP_Post $p): array`, con las claves `id`, `type`, `title`, `description`, `summary`, `faq`,
    `html`, `published`, `modified`, `image`, `thumb`, `crumbs`, `siblings`, `notice` y `url`.

- [ ] **Step 1:** `tests/smoke-contenu.php` (WP cargado): resuelve las 5 URLs de prueba con `canal_contenu_strip_suffix` + `get_page_by_path` + `canal_contenu_is_eligible`; comprueba que `calcul-de-distance-canal-du-midi` no es elegible y que `accueil-2026` existe como página real; renderiza `canal_contenu_data()` de cada una (un solo `<h1>` en la plantilla, sin `<h1` en `html`, JSON-LD con `Article`/`WebPage`).
- [ ] **Step 2:** implementar `contenu-route.php`, `template-contenu.php`, `contenu.css` y las 4 integraciones.
- [ ] **Step 3:** `remote.sh test` (lint 7.4 + tests) y luego `remote.sh run tests/smoke-contenu.php`. Ojo: el smoke
  necesita el plugin desactivado («Cannot redeclare»). Hay que hacer `deploy` primero y lanzar el smoke con `wp eval`
  sobre las funciones ya cargadas, o con el plugin desactivado. Se usa la variante con el plugin activo:
  `remote.sh wp eval-file` del smoke **sin** `require` del plugin.
- [ ] **Step 4:** `remote.sh deploy` y purgar WPFC.
- [ ] **Step 5:** commit `feat(contenu): router /<ruta>-2026/ y plantilla de contenido 2026`.

### Task 3: Verificación en navegador y docs

- [ ] **Step 1:** con sesión en Chrome: las 5 URLs a 1440 y 390 px, haciendo scroll antes de capturar. Comprobar un
  solo H1, migas, fechas, aviso de antigüedad en el artículo de 2017, tabla desplazable y columna lateral.
- [ ] **Step 2:** sin sesión (`curl`): 404 en las 5; `/accueil-2026/` 200 y sin cambios.
- [ ] **Step 3:** si los JSON-LD tienen el `@type` esperado, validarlos con `canal_home_seo_jsonld` en el smoke.
- [ ] **Step 4:** actualizar `docs/TASKS.md` (TASK-055 ✅, TASK-056 en 🟡), `docs/SESSION.md` y la sección « Estado
  actual » de `CLAUDE.md`. Commit.
