# TASK-029 — Carte interactive (`/carte/`) — Diseño

**Fecha:** 2026-09-29 · **Estado:** spec pendiente de revisión del usuario

## Objetivo

Copiar la página del mapa de la app local (`/search`) en el WordPress de producción
(`plan-canal-du-midi.com`) como página nueva **`/carte/`**, adaptando solo lo imprescindible
a los datos de WordPress. Concepto: **copia fiel**, no rediseño.

Regla dura de producción: **no se modifica nada existente, solo se añade.** `/explorer/`
(página 10154, widget my-listing) queda intacta.

## Decisiones tomadas (brainstorming 2026-09-29)

1. Página WP nueva propia, **privada** hasta orden explícita de publicar.
2. Enfoque de datos A: las fichas se leen de un array cacheado y se filtran **en PHP**; el
   formulario se envía por **GET** y la página se vuelve a pintar (igual que en local).
   Sin AJAX para la lista.
3. Funciones: filtros + lista + mapa, modal de detalle, asistente IA, « Autour de moi ».
4. Layout, markup, CSS y JS = los de `/search` local.

## Fuente de diseño (local)

- `src/Infrastructure/Views/search_results.php` — markup (sidebar con pestañas
  Filtres / Catégories / Ai, lista `explore-card`, panel de mapa, modal de detalle).
- `public/assets/css/search.css` — estilos y breakpoints (1400 / 1280 / 1180 / 900 / 720 / 640).
- `public/assets/js/search-map.js` — Google Maps, pines SVG, clusters propios, InfoWindow
  con carrusel, modal de detalle con mini-mapa, hover tarjeta ↔ pin.

## Arquitectura (plugin `canal-home`, todo añadido)

| Archivo nuevo | Responsabilidad |
|---|---|
| `wp-plugin/canal-home/template-carte.php` | Markup portado de `search_results.php` |
| `wp-plugin/canal-home/includes/carte-data.php` | `canal_carte_listings()` (lectura + caché) e invalidación |
| `wp-plugin/canal-home/includes/carte-filter.php` | `canal_carte_params()` y `canal_carte_filter()` — **puras**, sin WP |
| `wp-plugin/canal-home/assets/carte.js` | Port de `search-map.js` + IA + « Autour de moi » |
| `wp-plugin/canal-home/assets/carte.css` | **Generado** desde `search.css` con el build rem→px existente |
| `wp-plugin/build/` | Entrada nueva para `carte.css` (nunca editar el generado a mano) |
| `wp-plugin/tests/test-carte-filter.php` | Tests puros del filtro |
| `wp-plugin/tests/smoke-carte-data.php` | Smoke con WP cargado |

Cambios en archivos propios del plugin (no de producción ajena):
- `canal-home.php`: registrar la plantilla `canal-carte` (`theme_page_templates` +
  `template_include`, mismo patrón que la home) y encolar `carte.css`, `carte.js`,
  `header.css` y Google Maps solo en esa plantilla.
- `includes/data.php`: añadir `slug` a `canal_home_card()` (campo extra, compatible).
- `remote.sh test`: ejecutar también `test-carte-filter.php`.

## Datos

`canal_carte_listings(): array` — fichas `job_listing` publicadas (254 a 2026-09-29, todas
con geolocalización). Una sola `get_posts` + `update_meta_cache` +
`update_object_term_cache`. Cada ficha:

```
id, slug, title, url (permalink → /fiche/<slug>/), lat, lng (float|null),
cover (_job_cover[0] o imagen por defecto de la home), gallery (_job_gallery, array de URLs),
excerpt (_job_description o post_content, sin tags, 200 car.), phone (_job_phone),
website (_job_website), address (_job_location), city (término region, Title Case),
cats: [{slug, name, parent_slug}]
```

- Fichas sin lat/lng: en la lista, no en el mapa.
- Categorías: taxonomía `job_listing_category` (5 padres: hebergement, restauration,
  activites-loisirs, lieux-dinformations, services + subcategorías). Contadores calculados del
  mismo array.
- **Caché:** transient `canal_carte_listings`, 12 h. Se borra en `save_post_job_listing`,
  `deleted_post` (si es job_listing) y `edited_job_listing_category`.

## Filtrado (`includes/carte-filter.php`, puro)

`canal_carte_params(array $get, array $validCatSlugs): array` normaliza la entrada:

| Parámetro | Alias de `/explorer/` | Saneado |
|---|---|---|
| `q` | `search_keywords` | texto, 100 car. máx. |
| `type[]` | `category[]` | solo slugs presentes en `$validCatSlugs` |
| `location` | `search_location` | texto, 100 car. máx. |
| `lat`, `lng` | — | float, lat ∈ [-90,90], lng ∈ [-180,180]; si no, se ignoran |

`canal_carte_filter(array $listings, array $params): array`:
- `q`: todas las palabras deben aparecer en título + categorías + commune + extracto;
  sin distinguir acentos ni mayúsculas.
- `type[]`: una ficha pasa si tiene alguna de las categorías o una hija de ellas (padre ⇒ hijas).
- `location`: coincidencia sin acentos en commune o dirección.
- `lat`/`lng`: añade `distance_km` (haversine) y ordena por distancia ascendente;
  si no, orden alfabético por título.

## Página y UI

- Markup, clases y comportamiento de `/search`: tres columnas en escritorio, mapa debajo
  a ≤ 1180 px, sidebar lateral deslizante y conmutación `data-mobile-view` a ≤ 900 px.
- **Filtres:** `q` + selector de categorías de local, con las categorías de WP. Añadido:
  botón **« Autour de moi »** (geolocalización del navegador → reenvía el GET con `lat`/`lng`;
  si se deniega: aviso en línea, sin `alert`). Con distancia, la tarjeta muestra « à X km ».
- **Lugar:** sin campo en la sidebar (local no lo tiene). Si llega `location` por URL, se
  muestra como chip activo « 📍 <texto> » que se puede quitar.
- **Catégories:** tarjetas de las categorías padre con contador (enlazan a `?type[]=<slug>`).
- **Ai:** textarea + sugerencias adaptadas al canal (los botones « best-value / spacious »
  de local son de hoteles y se sustituyen). Llama a `POST /wp-json/canal-home/v1/ai`, pinta los
  resultados con su `reason` en la `ai-response-card` y resalta sus pines por `slug`. Errores
  y límites: se muestra el `message` del endpoint.
- **Modal de detalle:** el de local (foto/carrusel, categoría, descripción, teléfono, web,
  dirección, mini-mapa, « Itinéraire », « Voir la fiche » → permalink).
- Variables JS (`BASE_URL`, clave de Maps, endpoint IA) vía `wp_localize_script`. JSON de las
  fichas filtradas inline con `wp_json_encode(..., JSON_HEX_TAG | JSON_HEX_AMP)`.
- Cabecera y pie del tema; `assets/header.css` de la home para la navbar.
- Textos en francés. Imágenes de tarjetas con `loading="lazy"`.

## Google Maps

- Clave: opción `options_general_google_maps_api_key` (la que ya usa my-listing en
  `/explorer/`), idioma `fr`. Carga asíncrona con `callback`.
- Sin clave o si falla la carga: la lista funciona y el panel del mapa muestra
  « Carte indisponible ».

## Seguridad

- Toda salida escapada (`esc_html`, `esc_attr`, `esc_url`); URLs de web con esquema http(s).
- Entrada GET solo a través de `canal_carte_params` (lista blanca de slugs, floats acotados).
- La IA reutiliza el endpoint existente con sus límites por IP y diario; no se añaden endpoints.
- El JS construye el DOM con `textContent` / `createElement`, nunca `innerHTML` con datos.

## Tests y verificación

1. `test-carte-filter.php` (en `remote.sh test`): palabra con acentos (« ecluse » encuentra
   « Écluse »), varias palabras, padre ⇒ hijas, slug inválido descartado, alias de
   `/explorer/`, `location`, orden por distancia, lat/lng fuera de rango ignorados,
   entrada con HTML/script neutralizada.
2. `smoke-carte-data.php` (`remote.sh run`, plugin desactivado): nº de fichas = nº de
   `job_listing` publicados, cada una con url `/fiche/` y lat/lng numéricos; el transient se
   crea y se borra tras `save_post_job_listing`.
3. Visual en navegador, lado a lado con `/search` local, a 1440, 1024 y 390 px; con scroll
   antes de las capturas de página completa. Probar filtros, modal, IA, « Autour de moi » y
   los alias (`/carte/?search_location=Toulouse`).

## Despliegue y rollback

- `wp-plugin/remote.sh deploy`, luego crear la página « Carte interactive » (slug `carte`,
  plantilla `canal-carte`, **privada**).
- Rollback: papelera de la página y `wp transient delete canal_carte_listings`; volver a
  desplegar el plugin sin los archivos de la carte.

## Fuera de alcance

- Cambiar los enlaces de la home de `/explorer/` a `/carte/` y redirigir `/explorer/`:
  con la publicación (TASK-028/029), solo con orden explícita.
- Diseño de la ficha (TASK-030).
