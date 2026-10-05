# Plantilla de contenido 2026 (TASK-055) — diseño

**Fecha:** 2026-10-05 · **Estado:** aprobado en el chat (« todas las páginas pasan a la nueva versión, artículos
incluidos; mejorar SEO, AEO y GEO de todo ») · **Base:** `docs/inventario-paginas-2026-10-05.md`

## Objetivo

Cualquier página o artículo de WordPress de producción se ve con el diseño 2026 en `/<ruta>-2026/`. Las URLs se
generan con un router en el plugin `canal-home`; no se hace página por página. La plantilla aplica a cada URL todas
las mejoras de SEO, AEO y GEO que son automáticas. Además, lee el enriquecimiento editorial de la fase 2 (TASK-056)
cuando existe.

**Restricciones:** producción WordPress con PHP 7.4, sin tocar nada existente (solo añadir), privado hasta que se
publique y verificación visual en el navegador a 1440 y 390 px.

## Alcance

**Entra:** los 1 936 artículos publicados, sin excluir ninguno por tráfico. También todas las páginas publicadas con
plantilla de contenido: por defecto, `templates/content-sidebar.php` o Elementor.

**No entra** (estas URLs dan 404 en `-2026`):
- páginas con otra plantilla: las antiguas `rechercher-presta*`/`details_*`/`liste-*`/`resultat.php`/`prestataire.php`,
  `calcul_distance_canal.php` (tendrá plantilla propia), `formulaire_organiser_sejour.php` (la sustituye el
  planificador), `liens_reseaux_sociaux_canal.php`, `verif_facebook.php`, `page_accueil_test.php` y las 3 plantillas
  2026;
- las páginas de WooCommerce (`boutique`, `panier`, `paiement`, `mon-compte`) y `claim-list`.

**Fase 2 (TASK-056), fuera de esta tarea:** generar con IA el título, la descripción, el resumen y la FAQ de cada URL.
Las copias de prensa (~800 artículos de La Dépêche y Midi Libre) se reescriben como texto propio que cita la fuente.
Esta tarea solo define dónde se guardan esos datos y cómo se pintan.

## Arquitectura

| Archivo | Responsabilidad |
|---|---|
| `includes/contenu-core.php` (nuevo) | Funciones puras con test: sufijo/ruta, elegibilidad, limpieza del HTML, aviso de antigüedad, grafo JSON-LD |
| `includes/contenu-route.php` (nuevo) | Integración con WP: resolver la petición, estado, `template_redirect`, estilos, `<head>` SEO y purga de la caché |
| `template-contenu.php` (nuevo) | Marcado de la página: cabecera y pie del tema con nuestra cabecera, como la ficha |
| `assets/contenu.css` (nuevo, escrito a mano) | Estilos `.cdm-contenu` |
| `canal-home.php`, `includes/header.php`, `includes/head-fix.php` | Incluir `canal_contenu_is_page()` donde hoy se listan home, carte, ficha y planificador (cabecera propia, quitar assets sin uso, limpieza del `<head>`, etiquetas de iconos del pie) |
| `tests/test-contenu.php` (nuevo), `tests/smoke-contenu.php` (nuevo), `remote.sh` | Tests |

### Ruta

- Constante `CANAL_CONTENU_SUFFIX = '-2026'`. Al publicar pasa a ser `''`: es el único cambio para recuperar las URLs
  originales.
- `parse_request`: si `$wp->request` termina en el sufijo y **no** existe un post real con esa ruta (así
  `/accueil-2026/` y `/explorer-2026/` siguen siendo las suyas), se quita el sufijo y se busca con
  `get_page_by_path($ruta, OBJECT, ['page', 'post'])`.
- El post encontrado debe estar publicado y ser elegible. Si lo es, la petición pasa a `query_vars =
  ['canal_contenu' => ID]`. No hay reglas de reescritura ni `flush`.
- `template_redirect` (prioridad 0): si no tiene permiso, 404. El permiso es `read_private_pages` o la opción
  `canal_contenu_public = '1'`, con el mismo `canal_fiche_can_view()` de la ficha. Si lo tiene: estado, plantilla y
  `exit`. Como la ficha, además `DONOTCACHEPAGE` y `nocache_headers()`.
- La caché WPFC se purga al crear, cambiar o borrar `canal_contenu_public`.

### Contenido

Este es el pipeline. Es el de `the_content` del núcleo, sin los filtros de plugins: así Elementor no pinta su
constructor y su CSS y JS no hacen falta.

`do_blocks → wptexturize → wpautop → shortcode_unautop → do_shortcode → wp_filter_content_tags → canal_contenu_clean_html`

`canal_contenu_clean_html()` (pura, con test):
1. Quita `[Zoomer]` / `[/Zoomer]`, un shortcode que ya no existe y que hoy aparece como texto en 29 artículos.
2. `<h1>` → `<h2>`, porque la página ya tiene su H1.
3. Un párrafo cuyo único contenido es `<strong>`/`<b>` de 3 a 90 caracteres pasa a `<h2>`. Son los pseudo-títulos
   de 42 contenidos y dan estructura para AEO.
4. Quita los párrafos vacíos (`<p></p>`, `<p>&nbsp;</p>`).

### Página (`template-contenu.php`)

- `get_header()` / `get_footer()`, con nuestra cabecera y pie como en la ficha.
- Migas de pan:
  - páginas: Accueil › página madre (si existe) › título;
  - artículos: Accueil › primera categoría › título.
- H1: si el título está en MAYÚSCULAS, se pasa por `canal_fiche_display_title()`, la misma regla que en las fichas.
  Si existe, `_canal_2026_title` sustituye al título.
- Línea de fechas:
  - artículos: « Publié le 20 avril 2017 » (+ « · mis à jour le … » si la fecha de modificación es posterior y
    distinta);
  - páginas: solo « Mis à jour le … ».
- Aviso de antigüedad: en artículos publicados hace más de 3 años, « Article publié en 2017 : certaines informations
  ont pu changer. »
- « L'essentiel »: un recuadro tras el H1, solo si existe `_canal_2026_summary`.
- Imagen destacada, solo si el post la tiene (no se inventa ninguna).
- Cuerpo: columna de lectura de ~720 px, con tablas que se desplazan en horizontal en móvil e imágenes al 100 %.
- FAQ: solo si existe `_canal_2026_faq` (JSON `[{"q","a"}]`), con `<details>` y `FAQPage`.
- Columna lateral (debajo del texto en móvil):
  - las 3 herramientas más usadas: Carte interactive (`CANAL_CARTE_PATH`), Calcul de distance y Plan PDF
    (`CANAL_PLAN_PDF_PATH`);
  - en páginas con página madre, sus páginas hermanas publicadas y elegibles, enlazadas en versión 2026.

### SEO / AEO / GEO automático

- `<title>`: título mostrado + « | Plan Canal du Midi ».
- Meta description, en este orden: `_canal_2026_description`, extracto manual o `canal_fiche_excerpt()` del texto
  limpio (155 caracteres).
- `robots`: `noindex,nofollow` mientras el sufijo no esté vacío, como en la ficha.
- Canonical: la URL de la página con el sufijo vigente (al publicar, la original).
- OG/Twitter con `canal_home_seo_social()`. La imagen es la destacada, si no la primera `<img>` del contenido y, si no,
  `CANAL_HOME_HERO_IMAGE`.
- JSON-LD con `canal_home_seo_jsonld()`:
  - `Article` (artículos) o `WebPage` (páginas): `headline`, `description`, `datePublished`, `dateModified`, `author`
    y `publisher` = la Organization del sitio (`#org` de la home), `image`, `inLanguage: fr-FR`, `mainEntityOfPage`,
    `about` = Canal du Midi (Wikidata Q202494);
  - `BreadcrumbList`;
  - `FAQPage` si hay FAQ.

### Contrato con la fase 2

Son metadatos de post nuevos y opcionales: si no existen, la plantilla funciona igual.

| Meta | Tipo | Uso |
|---|---|---|
| `_canal_2026_title` | string ≤ 70 | H1, `<title>`, `headline` |
| `_canal_2026_description` | string ≤ 160 | meta description, OG, `description` |
| `_canal_2026_summary` | string (2–3 frases) | recuadro « L'essentiel » |
| `_canal_2026_faq` | JSON `[{"q":"…","a":"…"}]` | sección FAQ + `FAQPage` |

## Errores y casos límite

- Ruta con sufijo que no corresponde a ningún post elegible: 404 del tema.
- Post sin contenido tras la limpieza: se pinta igual (título, fechas, columna lateral).
- `_canal_2026_faq` con JSON inválido: se ignora.
- Contenido con `<script>` de widgets (por ejemplo la météo de booked.net): se conserva, porque es contenido del editor.

## Pruebas

- `tests/test-contenu.php` (PHP puro, se añade a `remote.sh test`) cubre:
  - sufijo y ruta;
  - elegibilidad por tipo y plantilla;
  - las 4 reglas de limpieza;
  - el aviso de antigüedad;
  - el grafo JSON-LD: tipos, fechas, FAQ solo si existe.
- `tests/smoke-contenu.php`, con WP cargado: resolución y HTML de *règles de navigation* (texto largo + tabla), *canal
  de la Robine* (Elementor), *péniches à vendre* (artículo), *météo* (widgets) y *foire de printemps* (artículo de 2017
  sin tráfico); 404 de una página excluida (*calcul de distance*); `/accueil-2026/` sin cambios.
- Navegador a 1440 y 390 px en esas 5 URLs: captura tras hacer scroll.

## Criterios de éxito

1. Las 5 URLs de prueba responden 200 con sesión y 404 sin ella; `/accueil-2026/` y `/explorer-2026/` no cambian.
2. Un solo H1 por página y JSON-LD válido (Article/WebPage + BreadcrumbList).
3. Sin CSS ni JS de Elementor, Woo o CF7 en estas páginas.
4. Tests y lint de PHP 7.4 en verde (`remote.sh test`).
