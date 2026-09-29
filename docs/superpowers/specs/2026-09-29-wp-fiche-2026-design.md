# TASK-030 — Fiche prestataire 2026 (`/fiche-2026/<slug>/`) — Diseño

Fecha: 2026-09-29 · Estado: aprobado (el usuario delegó la revisión del diseño)

## Objetivo

Nueva versión de la ficha de prestatario del WordPress de producción, con el diseño de la vista
local `src/Infrastructure/Views/service_detail.php` + `public/assets/css/service_detail.css`,
servida por el plugin `canal-home` **sin modificar nada existente**. Privada hasta orden explícita.

Éxito = un usuario con sesión ve `/fiche-2026/<slug>/` para cualquiera de las 254 fichas con el
diseño local (escritorio y móvil), un visitante sin sesión recibe 404, la carte y la home privadas
enlazan a la ficha nueva, y las `/fiche/<slug>/` actuales no cambian en nada.

## Decisiones del usuario

- URL: regla de reescritura propia `/fiche-2026/<slug>/` (opción A).
- Se quitan los bloques sin datos en WP: reserva/calendario/promo, avis, equipamientos, modales.
- Carte y home enlazan a `/fiche-2026/` mientras todo sea privado (constante `CANAL_FICHE_PATH`).
- Se añade « Autour de ce lieu »: 6 fichas más cercanas.

## Datos en producción (29/09, 254 fichas `job_listing`, tipo único `prestataires-touristiques`)

Cobertura de metas no vacías: `_job_description` 245, `_job_cover`/`_job_gallery` 253,
`_job_location` + `geolocation_lat/long` 254, `_job_phone` 146, `_job_website` 137,
`_job_email` 132, `_facebook` 112, `_links` 121, `_zone` 64, `_job_video_url` 18,
`_telephone-portable` 17, `_fax` 12, `_work_hours` 6. Avis: 0. Sin plugin SEO. Caché:
WP Fastest Cache + redis-cache. Permalinks `/%postname%/`; el CPT usa `fiche/` y `fiches/`.

## 1. Rutas y visibilidad (`includes/fiche-route.php`)

- `const CANAL_FICHE_PATH = '/fiche-2026/';` en `canal-home.php`, junto a `CANAL_CARTE_PATH`.
- `canal_fiche_url(string $slug): string` → `home_url(CANAL_FICHE_PATH . $slug . '/')`.
  Sustituye a `get_permalink()` en `canal_home_card()` (data.php) y `canal_carte_listings()`
  (carte-data.php). Tras desplegar: `canal_carte_flush()` y borrar transients de la home.
- `init`: `add_rewrite_tag('%canal_fiche%', '([^/]+)')` y
  `add_rewrite_rule('^fiche-2026/([^/]+)/?$', 'index.php?canal_fiche=$matches[1]', 'top')`.
  Flush único: si la opción `canal_fiche_rewrite_ver` ≠ versión de la regla →
  `flush_rewrite_rules(false)` y se actualiza la opción (la tabla de reglas es una caché derivada;
  añadir una regla no altera las existentes).
- `template_redirect` (prioridad 0), solo si `get_query_var('canal_fiche')` no está vacío:
  1. Busca `job_listing` publicado por `name` (sanitize_title). No existe → deja seguir (404 del tema).
  2. Sin `current_user_can('read_private_pages')` → deja seguir (404): equivale a « privada ».
  3. Si no: `define('DONOTCACHEPAGE', true)`, `nocache_headers()`, `status_header(200)`,
     `$wp_query->is_404 = false`, guarda el post en `$GLOBALS['canal_fiche_post']`,
     `include template-fiche.php; exit;`.
- `canal_fiche_is_page(): bool` → true cuando hay post resuelto; lo usan enqueue, dequeue, SEO y
  `pre_get_document_title`.
- Publicación futura (TASK-030b, solo con orden): `CANAL_FICHE_PATH` → `/fiche/`, plantilla
  aplicada a `is_singular('job_listing')`, 301 de `/fiche-2026/…` a `/fiche/…`, quitar `noindex`
  y el control de sesión.

## 2. Datos (`includes/fiche-data.php`)

`canal_fiche_data(WP_Post $post): array` (una sola ficha, metas completas):

| clave | origen |
|---|---|
| `title` | `get_the_title` → `canal_home_plain` |
| `description` | `_job_description` o `post_content`, `strip_shortcodes`, `wp_kses_post` + `wpautop` |
| `cover`, `gallery[]` | `canal_home_cover()`, `_job_gallery` (strings, sin límite de 8) |
| `address`, `city`, `zone` | `_job_location`, `canal_home_city()`, `_zone` |
| `lat`, `lng` | `geolocation_lat/long` (float o null) |
| `phone`, `mobile`, `fax`, `email`, `website` | `_job_phone`, `_telephone-portable`, `_fax`, `_job_email`, `_job_website` |
| `social` | `_facebook` + entradas de `_links` cuyo host sea facebook/instagram/youtube |
| `video` | `_job_video_url` pasado por `canal_fiche_video_embed()` |
| `categories[]` | términos `job_listing_category` (nombre + enlace a la carte filtrada) |
| `nearby[]` | `canal_fiche_nearby()` |

Funciones puras (sin WP, testeables en `tests/test-fiche.php`):
- `canal_fiche_video_embed(string $url): string` → URL de embed YouTube/Vimeo o `''`
  (allowlist de hosts, como SEC-010 en local).
- `canal_fiche_nearby(array $listings, int $selfId, float $lat, float $lng, int $n = 6): array`
  → haversine sobre los items de `canal_carte_listings()`, excluye la propia ficha y los sin coords.

## 3. Plantilla y assets

- `template-fiche.php`: `get_header()` / `get_footer()` del tema, contenedor `.cdm-fiche`.
  Bloques (cada uno solo si tiene datos), en el orden de la vista local: hero con galería
  (fondo cruzado) + H1 + dirección + zona + 3 categorías; barra de acciones (Appeler,
  Itinéraire, Email, Site web, redes); présentation; catégories; vídeo (iframe `loading=lazy`,
  `sandbox` como en local); galería + lightbox; mapa; « Autour de ce lieu » (tarjetas como las de
  la carte). Lateral: « Coordonnées » (pills + lista + redes). Todo escapado con `esc_html`,
  `esc_url`, `esc_attr`; `tel:` con el número limpiado.
- CSS: `build-css.mjs` genera `assets/fiche.css` desde `service_detail.css` (+ `styles.css` si
  hace falta) con prefijo `.cdm-fiche` y rem→px; ajustes WP en `build/fiche-extra.css`.
  Nunca editar `assets/fiche.css` a mano.
- JS: `assets/fiche/fiche.js` (carrusel del hero + lightbox portados de `hero-carousel.js` /
  `lightbox.js`, mapa con Google Maps que ya carga el tema, un marcador).
  `canal_home_carte_menu_js()` también aquí.
- Enqueue: fuentes, bootstrap-icons, `fiche.css`, `header.css`, `fiche.js`; mismo dequeue de
  plugins no usados que la carte (`canal_carte_dequeue_unused` generalizado a
  `canal_carte_is_page() || canal_fiche_is_page()`), y ocultar el cargador del tema.

## 4. SEO (`includes/seo-fiche.php`)

- `<title>`: « {Titre} à {Commune} — Canal du Midi »; meta description: 155 caracteres de la
  descripción (o fallback con categoría + commune).
- `canonical` = `canal_fiche_url(slug)`; OG/Twitter con la portada (reutiliza
  `canal_home_seo_social`).
- JSON-LD (`canal_home_seo_jsonld`): `TouristAttraction` + `LocalBusiness` con name, description,
  image[], address (PostalAddress con commune/CP si existen), geo, telephone, email, url,
  sameAs[]; `BreadcrumbList` Accueil → Carte → Ficha.
- Mientras la ruta sea `-2026`: `<meta name="robots" content="noindex,nofollow">`.
- `canal_fiche_seo_graph()` es pura → test en `tests/test-fiche.php`.

## 5. Tests y verificación

- `tests/test-fiche.php` (PHP puro, en `remote.sh test`): video embed (YouTube largo/corto,
  Vimeo, host no permitido, basura), nearby (orden, exclusión, sin coords, n), graph (claves y
  omisión de campos vacíos).
- `tests/smoke-fiche.php` (WP cargado, plugin desactivado como los otros smokes):
  `canal_fiche_data` sobre una ficha completa, una mínima y una con vídeo.
- Navegador (con sesión): escritorio + móvil 375 px, scroll antes de la captura completa, consola
  sin errores; sin sesión (`curl`): 404; `/fiche/<slug>/` actual idéntica (200, mismo tamaño aprox.).
- Enlaces: una tarjeta de la carte y un séjour de la home abren `/fiche-2026/…`.

## Fuera de alcance

Horarios (`_work_hours` existe en 6 fichas pero sin horas: todo « enter-hours »), reservas, avis, formulario de contacto, equipamientos, traducciones, publicación (TASK-030b).

## Riesgos

- Algún bloque del tema puede leer `get_queried_object()` en la cabecera (null en esta ruta) →
  verificar en el smoke de render y en navegador.
- `wpautop` sobre descripciones con HTML de Elementor/editor: verificar 3 fichas reales.
