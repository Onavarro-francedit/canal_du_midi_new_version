# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE 2026-10-01 (mediodía) — TASK-036 mapa bajo demanda en móvil (privada) · Siguiente: decidir

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** la carte en ≤1180 px no crea el mapa hasta abrir la vista Mapa (Lighthouse móvil 66–69,
escritorio 83) y el panel del mapa muestra skeleton mientras carga; desplegada y privada (404 sin sesión).

**Archivos:** `assets/carte/search-map.js` (`ensureMap`, skeleton), `build/carte-extra.css` + `assets/carte.css`.

**Decisiones que no están en ARCHITECTURE.md:** medir con `npx lighthouse@12` (la API de PageSpeed agota la
cuota); el Chrome de Claude puede no pintar Google Maps → verificar el mapa con Chrome limpio.

**Próxima acción:**
```
Opcional: CANAL_CARTE_EAGER_IMAGES 6 → 3 (en pantalla caben ~2 tarjetas; menos competencia en 4G lento).
Si no: publicar home + carte + ficha (TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita.
```

---

## CIERRE 2026-10-01 (mañana) — TASK-035 carte: LCP sin esperar a Maps (privada) · Siguiente: TASK-036 o publicar

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** la carte pinta lista e imágenes sin esperar a Google Maps (Lighthouse móvil 48 → 63,
LCP 13,4 → 7,1 s; escritorio 83); desplegada y privada (404 sin sesión).

**Archivos:** `template-carte.php` (imágenes en el HTML, revelado sin mapa), `assets/carte/skeleton-controler.js`
(reescrito), `canal-home.php` (scripts en defer), `includes/carte-data.php` (`CANAL_CARTE_EAGER_IMAGES`).

**Decisiones que no están en ARCHITECTURE.md:** la API anónima de PageSpeed agota la cuota diaria → medir con
`npx lighthouse@12 <url> --only-categories=performance` (móvil por defecto, `--preset=desktop`).

**Próxima acción:**
```
TASK-036 — en móvil iniciar el mapa solo al abrir la pestaña Mapa, o publicar home + carte + ficha
(TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita.
```

---

## CIERRE 2026-10-01 — TASK-034 home y carte sin CSS/JS del tema (privadas) · Siguiente: TASK-035 o publicar

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** home, carte y ficha 2026 ya no cargan el CSS/JS del tema ni los plugins que no usan
(PageSpeed móvil: home 78, ficha 87, carte 36); las tres privadas (404 sin sesión).

**Archivos:** `assets/carte-theme.css`, `assets/home-theme.css` (nuevos, generados), `canal-home.php`,
`includes/fiche-core.php` (`CANAL_THEME_UNUSED_ASSETS`, `CANAL_THEME_FIX_CSS`), `fiche-route.php`,
`head-fix.php`, `build/extract-theme-css.js`, `tests/test-fiche.php`.

**Decisiones que no están en ARCHITECTURE.md:** carte y home se miden publicando la página unos minutos
(`wp post update 1850x --post_status=publish` → medir → `private`), autorizado por el usuario.

**Próxima acción:**
```
TASK-035 — aligerar el peso propio de la carte (tarjetas, datos incrustados, mapa), o publicar home + carte + ficha
(TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita.
```

---

## CIERRE 2026-09-30 (noche) — TASK-033 ficha 2026 móvil + SEO + aligerado del tema (privada) · Siguiente: TASK-034 o publicar

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** la ficha 2026 tiene diseño móvil tipo app, correcciones SEO y ya no carga el CSS/JS del
tema (PageSpeed móvil 87, escritorio 98); sigue privada (`canal_fiche_public` borrada, 404 sin sesión).

**Archivos:** `template-fiche.php`, `assets/fiche/fiche.js`, `build/fiche-extra.css` (+ `assets/fiche.css`),
`assets/fiche-theme.css` (nuevo, generado), `build/extract-theme-css.js` (nuevo), `includes/fiche-core.php`,
`fiche-route.php`, `head-fix.php`, `seo-fiche.php`, `tests/test-fiche.php`.

**Decisiones que no están en ARCHITECTURE.md:** en la ficha el CSS del tema se sustituye por un subconjunto
estático extraído del DOM (regenerar si cambia el tema); el `<head>` fijo del tema se limpia con el buffer de
`head-fix.php` solo en la ficha; `remote.sh deploy` lo lanza el usuario (el clasificador lo bloquea).

**Próxima acción:**
```
TASK-034 — aligerar el tema en home y carte (mismo método que TASK-033), o bien publicar home + carte + ficha
(TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita.
```

---

### Histórico

## CIERRE 2026-09-30 (tarde) — TASK-032 navbar nuevo desplegado (privado) · Siguiente: decidir publicación

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** home, carte y ficha 2026 (privadas) tienen cabecera propia con mega-menú; el resto del
sitio sigue con la del tema. Detalle en `docs/TASKS.md` 🟢 TASK-032.

**Archivos:** `wp-plugin/canal-home/includes/header.php` (nuevo), `assets/header.css`, `canal-home.php`,
`includes/fiche-route.php`, `template-carte.php`, `assets/fiche/fiche.js`.

**Decisiones que no están en ARCHITECTURE.md:** cabecera del tema desactivada con `mylisting/header-config`
a prioridad 99; menú definido en PHP (no lee el menú WP); `remote.sh deploy` permitido en
`.claude/settings.local.json` (lanzarlo desde la raíz del proyecto).

**Próxima acción:**
```
Publicar home + carte + ficha juntas = TASK-028 / TASK-029b / TASK-030b, SOLO con orden explícita
(al publicar: CANAL_HOME_PATH → '/', CANAL_CARTE_PATH y CANAL_FICHE_PATH sin -2026).
Al final: TASK-031 (<head> del tema en todo el sitio), también solo con orden explícita.
```

---

## CIERRE 2026-09-30 — Fiche 2026 terminada, optimizada y fusionada (privada) · Siguiente: decidir publicación

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Git:** todo en `main` y en `origin/main` (69ecd8e + este commit de docs). Rama `feat/wp-fiche-2026` borrada.
**Dónde quedamos:** las 254 fichas tienen versión nueva en `/fiche-2026/<slug>/` (solo con sesión; sin sesión →
404), enlazadas desde la home y la carte privadas. Auditoría seo-geo 66 → 84/100. PageSpeed (ficha abierta
unos minutos con `canal_fiche_public`): móvil 48–69 (antes 44; ficha actual 28), escritorio 84 (antes 64).
Nada publicado.

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

**Decisiones de 30/09:** no quitar moment/select2/jquery-ui en la ficha (el JS del tema falla y la cabecera
no aparece); `<head>` roto por `fb-root` del tema en todo el sitio = TASK-031, para el final; abrir la ficha
para medir con PageSpeed está autorizado (y cerrarla siempre después).

**Próxima acción (nueva sesión):**
```
TASK-032 — rediseño del navbar (docs/TASKS.md): leer docs/SESSION.md y TASKS.md, ver la cabecera actual en
home/carte/ficha 2026 (con sesión, escritorio y móvil) y wp-plugin/canal-home/assets/header.css, y hacer
brainstorming con el usuario (qué no le gusta) antes de tocar código.
```
Después:
```
Publicar home + carte + ficha juntas = TASK-028 / TASK-029b / TASK-030b, SOLO con orden explícita.
Al final: TASK-031 (<head> del tema en todo el sitio), también solo con orden explícita.
```

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
