# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE 2026-09-29 (noche) — Fiche 2026 `/fiche-2026/<slug>/` terminada (privada) · Siguiente: decidir publicación

**Agente activo al cerrar:** sesión principal (brainstorming → spec → plan → executing-plans).
**Handoff pendiente:** ninguno. **Git:** rama `feat/wp-fiche-2026` (sin fusionar ni push).
**Dónde quedamos:** las 254 fichas tienen versión nueva en `/fiche-2026/<slug>/` (solo con sesión;
sin sesión → 404), enlazadas desde la home y la carte privadas. Nada publicado.

**Archivos:** `wp-plugin/canal-home/includes/fiche-core.php` (puras), `fiche-data.php`,
`fiche-route.php`, `seo-fiche.php`, `template-fiche.php`, `assets/fiche/fiche.js`,
`assets/fiche.css` (generado; fuente `build/fiche-extra.css`), `canal-home.php`, `data.php` y
`carte-data.php` (url → `canal_fiche_url`), `seo.php` (`$image` opcional en `canal_home_seo_social`),
tests `test-fiche.php`, `smoke-fiche.php`, `smoke-carte-data.php`, `remote.sh`.

**Decisiones que no están en ARCHITECTURE.md:**
- Ruta propia por regla de reescritura (`CANAL_FICHE_PATH`); una query var sola deja `is_home` →
  hay que `set_404()` explícitamente.
- El tema ya reserva el hueco de su cabecera fija; la barra sticky sigue su borde (`--fiche-bar-top`).
- En producción hay fichas `expired` (p. ej. Nicols): no se muestran.

**Próxima acción:**
```
Revisar /fiche-2026/<slug>/ (con sesión) y decidir: fusionar feat/wp-fiche-2026 en main + push;
publicar home + carte + ficha juntas = TASK-028 / TASK-029b / TASK-030b, SOLO con orden explícita.
```

---

### Histórico

## CIERRE 2026-09-29 (tarde) — Carte `/explorer-2026/` terminada (privada) · Siguiente: TASK-030 ficha

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** la carte (`/explorer-2026/`, página WP **18502**, **privada**) está terminada:
copia de `/search` local + ajustes del usuario, SEO/GEO/AEO (auditoría seo-geo 32 → **80/100**) y
rendimiento. La home privada (`/accueil-2026/`, 18500) ya enlaza a la carte. Nada publicado.
**Git:** todo en `main` y en `origin/main`. Sin commitear, ajenos: `header.php`, `styles.css` (solo
un comentario) y `.claude/skills/seo-geo/`.

**Hecho desde el cierre anterior (detalle en `docs/TASKS.md` 🟢 TASK-029):**
- Skeleton visible (se oculta el cargador del tema solo en la carte), imágenes de tarjeta a 200 px,
  sin pestaña « Catégories ».
- Convención **`-2026`** para páginas nuevas (al publicar se quita el sufijo); ruta en
  `CANAL_CARTE_PATH`. Home y botón « Carte interactive » del menú (solo en home y carte) → carte.
- IA: respaldo sin `json_schema` cuando la API devuelve 503 « Grammar compilation… » (se recuerda
  10 min); estado de espera visible en la home (botón bloqueado, spinner, mensaje).
- SEO: `includes/seo-carte.php` (título, meta, OG/Twitter, JSON-LD CollectionPage + ItemList +
  BreadcrumbList + FAQPage, `dateModified`, `speakable`, `Link` llms.txt, `noindex,follow` con
  filtros), FAQ con datos reales (`includes/carte-faq.php`), H1 + intro + editor al pie de la
  columna de filtros, enlaces UNESCO/VNF, communes con acentos al mostrar (`CANAL_HOME_CITY_FIX`).
- Rendimiento: portadas 768 px + `srcset`, `content-visibility` en tarjetas, fuera Elementor/
  WooCommerce/CF7/TablePress/PayPal solo en la carte (107 → 79 peticiones).

**Decisiones que no están en ARCHITECTURE.md:**
- Páginas nuevas con sufijo `-2026`; al publicar, retirar/renombrar antes la página actual.
- Home y carte se publican JUNTAS (TASK-028 + TASK-029b): la home enlaza a la carte.
- Límite IA: 10 peticiones/IP cada 10 min (las pruebas desde la oficina lo agotan; se puede
  resetear borrando el transient `canal_home_ai_ip_<md5(ip)>`).

**Próxima acción (nueva sesión):**
```
TASK-030 — nueva versión de la ficha (/fiche/<slug>/ → página nueva con sufijo -2026):
leer docs/SESSION.md y docs/TASKS.md, analizar una ficha de producción (solo lectura) y la vista
local de ficha (src/Infrastructure/Views/…), y proponer el enfoque (brainstorming) antes de código.
```
(Publicar home/carte = TASK-028 / TASK-029b, SOLO con orden explícita.)

### CIERRE 2026-09-29 (mañana) — Home « Accueil 2026 » TERMINADA (privada, NO publicar) · Siguiente: la carte interactive

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Decisión del usuario:** la home queda terminada pero **privada** (página 18500, portada
sigue siendo la 15269). No publicarla ni cambiar la portada sin orden explícita.
**Git:** todo en `main` y en `origin/main` (push hecho); sin commitear solo: cambios de
« Se connecter » de la app local (`header.php`, `styles.css`) y la skill `.claude/skills/seo-geo/`.

**Hecho en la sesión:** home completa (diseño, IA, envío del plan por e-mail, móvil, navbar
restilizada solo en esa página, SEO/AEO: meta/OG/JSON-LD/FAQ/étapes/llms.txt/llms-full.txt),
`robots.txt` y `.htaccess` modificados con autorización (copias: `robots.txt.orig`,
`htaccess.bak-2026-09-29` en `/var/www/vhosts/plan-canal-du-midi.com/`), sitemap enviado a
Google Search Console y a Bing (Googlebot y Bingbot lo leyeron con 200 el 29/09;
`BingSiteAuth.xml` en la raíz, no borrarlo). Detalle en `docs/TASKS.md` 🟢 TASK-027.

**Siguiente tarea — TASK-029 « Carte interactive »:** el botón « Carte interactive » y todos
los enlaces de la home apuntan a **`/explorer/`** (página WP **10154**, plantilla Elementor
`elementor_header_footer` con el widget de exploración del tema my-listing: filtros
search_keywords / category[] / search_location+lat/lng/proximity, lista + Google Maps).
`/carte/` no existe (404). Misma regla que la home: no modificar nada existente; rediseñar
AÑADIENDO desde el plugin `canal-home` (página nueva o CSS/plantilla propios), fuente de
diseño = vista `/search` de la app local (`src/Infrastructure/Views/search_results.php`,
`public/assets/js/search-map.js`, `public/assets/css/search.css`).

**Próxima acción:**
```
Leer docs/SESSION.md y docs/TASKS.md (TASK-029), analizar /explorer/ en producción
(solo lectura) y la vista /search local, y proponer el enfoque antes de escribir código.
```

### TASK-027 — Home « Accueil 2026 » en el WordPress de producción — DESPLEGADA EN PRIVADO ✅ — 2026-09-29

**Agente activo al cerrar:** sesión principal (brainstorming → spec → plan → ejecución
directa con executing-plans).
**Handoff pendiente:** ninguno. Rama `feat/wp-home-accueil-2026` **fusionada en `main`**
(fast-forward, 2026-09-29) y borrada. `main` va por delante de `origin/main` (sin push).
**Dónde quedamos:** plugin `canal-home` activo en producción, página « Accueil 2026 »
(ID 18500) **privada** y verificada; la portada sigue siendo la home Elementor (15269).

**Archivos de la sesión (repo):**
- `wp-plugin/canal-home/` — plugin (bootstrap, plantilla, `includes/ai-core.php` puro,
  `includes/data.php`, `includes/ai.php` endpoint, `assets/home.css` generado, `assets/home.js`).
- `wp-plugin/build/` — build del CSS (postcss, solo local) + `home-extra.css`.
- `wp-plugin/tests/` — `test-ai-core.php`, `smoke-data.php`, `smoke-render.php`,
  `smoke-endpoint.php`, `check-cache.php`.
- `wp-plugin/remote.sh` — test / smoke / deploy / run / wp.
- Spec y plan en `docs/superpowers/`.

**En el servidor (añadido, nada existente modificado):**
`wp-content/plugins/canal-home/`, `/var/www/vhosts/plan-canal-du-midi.com/canal-ai-config.php`
(clave del `.env` local, modelo `claude-opus-5`, tope 300/día), página 18500 privada,
`active_plugins`, transients `canal_home_*` y la fila `wp_options`
`canal_home_ai_daily_YYYYMMDD` (tope diario atómico). **Rollback completo:** desactivar el
plugin, papelera de la página 18500, borrar transients `canal_home_*` y filas
`canal_home_ai_daily_%` / `canal_home_plan_daily_%`, borrar `httpdocs/llms.txt` y
`llms-full.txt`, restaurar `robots.txt` desde `/var/www/vhosts/plan-canal-du-midi.com/robots.txt.orig` y, si se
quiere, el `.htaccess` desde `htaccess.bak-2026-09-29` (mismo directorio).

**Decisiones que no están en ARCHITECTURE.md:**
- Producción = WordPress; la app local es solo fuente de diseño.
- El explorador de my-listing filtra por `search_location` (texto que geocodifica, 10 km),
  no por la taxonomía `region`.
- El tema fija `html{font-size:10px}` → el CSS portado se convierte rem→px en el build.

**Observación para el usuario:** `httpdocs/wp-config.php` se reescribió el 29/09 a las
01:24:47 UTC (usuario del sitio, sin cron propio → tarea de Plesk/WP Toolkit). No fuimos
nosotros; no se ha tocado.

**Iteraciones de diseño del 29/09 (en producción, página privada):** parallax de la banda,
iconos en « Nos atouts », bloque del plan como en local con envío real por e-mail, foto vélo,
tarjetas de séjours rediseñadas (sin esclusas), 6 arreglos móviles + carruseles, hero móvil,
libro del plan recortado, cabecera del tema restilizada solo en esta página
(`assets/header.css`). Detalle en `docs/TASKS.md` 🟢 TASK-027.

**SEO/AEO (29/09):** meta/OG/título/JSON-LD, FAQ, étapes, selección determinista, hero
optimizado y `llms.txt` en la raíz (archivo nuevo). Ver TASKS.md 🟢 TASK-027.

**Antes de publicar:** revisar `pm.max_children` del pool PHP-FPM (cada llamada IA ocupa un
worker ~7–10 s).

**Próxima acción (solo con orden explícita del usuario):**
```
wp-plugin/remote.sh wp post update 18500 --post_status=publish
wp-plugin/remote.sh wp option update page_on_front 18500     # rollback: 15269
```
