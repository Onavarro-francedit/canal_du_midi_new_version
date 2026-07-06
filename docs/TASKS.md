# TASKS.md — Canal du Midi

Convención de IDs: `TASK-NNN` tareas · `BUG-NNN` bugs · `SEC-NNN` seguridad.

## 🔴 En curso

_(ninguna — TASK-018 migración IA a Claude completada, pasa a security)_

## 🟡 Pendiente

- **PRD-006 / BUG-012** — _(✅ CORREGIDO 2026-06-24 — ver 🟢 Completadas)_

- **BUG-004** — _(✅ RESUELTO 2026-06-24 — botón "Lire la vidéo" eliminado en la tanda
  autónoma; ver 🟢 Completadas)_

- **TASK-016b** (datos, largo plazo) — Backfill de una columna `commune` limpia en
  `listings`, extraída de `address`/`postal_code` (la columna `city` actual contiene
  el nombre del establecimiento, no la localidad). Permite un dropdown de
  "Destination" exacto y joins por comuna sin depender de `LIKE` sobre `address`.
  Sustituye la lista curada de TASK-016 (a) cuando esté lista. FUERA del cluster del
  buscador del hero.

- **SEC-001** — _(✅ → ver 🟢)_
- **TASK-001** — _(✅ → ver 🟢)_
- **TASK-002** — _(✅ → ver 🟢)_
- **SEC-004** — Añadir SRI (`integrity` + `crossorigin="anonymous"`) a los
  `<script>` de Leaflet 1.9.4 y leaflet.markercluster 1.5.3 (unpkg) cargados en
  `footer.php` para las páginas service/search. Misma deuda que SEC-003 (GSAP),
  ya resuelta en home. Lección aplicable: SEC-003.

- **SEC-008** — _(✅ CORREGIDA 2026-06-24 — ver 🟢 Completadas)_

- **SEC-009** — _(✅ ya implementado/reconciliado 2026-06-24 — ver 🟢 tanda backlog)_

- **SEC-004 (corregido in-situ, 2026-06-23)** — `home.php:175`: slug de BD en query-string
  sin `rawurlencode()`. Corregido en la revisión security de TASK-011/013. Ver LESSONS.md.
- **SEC-005 (corregido in-situ, 2026-06-23)** — `home.php:263`: URL hardcodeada con
  `/canal_du_midi/` y `$tour->id` sin cast `(int)`. Corregidos en la revisión security.
  Ver LESSONS.md.

### Home page (de la revisión de `home.php` + `PageController::render`)

- **SEC-002** — XSS/inyección en atributos en el grid de destinos
  (`home.php:156-157`): escapar `$url` (href) y `$bgImage` (inline `style`,
  viene de `categories.image_url`) con `htmlspecialchars(ENT_QUOTES,'UTF-8')`.
- **BUG-001** — `$tours` mal filtrado (`PageController.php:25`): toma los 4
  primeros servicios cualesquiera en vez de tipos tour/boat. Restaurar el filtro
  por tipo (línea 24 comentada) y dar fallback si no hay tours.
- **BUG-002** — URL hardcodeada en la tarjeta de tour (`home.php:219`): usa
  `/canal_du_midi/...` en vez de `BASE_URL`; se rompe en otro path/dominio.
- **BUG-003** — Formulario de newsletter no funcional (`home.php:298-301`):
  sin `action`, `method` ni `name` en el input email; no envía nada.
- **BUG-004** — Botón "Lire la vidéo" del bloque inmersivo (`home.php:239`) sin
  lógica asociada; cablear a un vídeo o quitarlo.
- **TASK-003** — Limpiar trabajo desperdiciado en `PageController` (home):
  `$features` y `$articles` se consultan a BD y se descartan; `$destinations`
  se calcula y no se usa. Eliminar o cablear a la vista. Reduce 2 queries/carga.
- **TASK-004** — i18n de la home: traducir al francés los textos en inglés
  (`Top destinations`, `Popular tours`, `Why choose us?`, `Weekly flash deals`,
  `Summer escapes`, `Sign up for our newsletter`, `Submit`,
  `Where would you like to go?`).
- **TASK-005** — Reemplazar contenido placeholder de la home: stats falsas del
  hero (`12K+/48/4.9`), contacto `tel:+33500000000` y `bonjour@canaldumidi.local`,
  features estáticas de "Why choose us" (existe `getActiveFeatures()`).
- **TASK-006** — Poblar dinámicamente el buscador de la home: ciudades y tipos
  están hardcodeados (`home.php:64-84`); usar `getCities()` del repositorio.
- **TASK-007** — Sustituir imágenes Unsplash externas hardcodeadas del hero y la
  sección editorial (`home.php:16,180-186`) por imágenes gestionables/locales.

### Buscador del hero — análisis Product Owner (2026-06-23, con evidencia de BD)

Diagnóstico funcional del componente `.hero-search` (`home.php:47-102` →
`PageController::render` case `search` → `MySQLServiceRepository::searchListings`).
Datos verificados en BD local (253 listings publicados, 26 categorías top-level).

- **BUG-007** — Filtro "Type" parcialmente roto. La opción `boat` del `<select
  name="type">` (`home.php:88`) **no corresponde a ningún slug de categoría**; las
  reales son `nautique` ("Le Canal en Bateau"), `bateau-restaurant`, `peniche`.
  `resolveCategoryIdsForSearch()` no encuentra match → devuelve `[]` → la búsqueda
  ignora el filtro y **retorna TODOS los listings sin filtrar**. El usuario cree que
  filtró por barco y ve todo. (Severidad: alta.)
- **TASK-015** — Poblar "Type" dinámicamente desde las categorías reales. Solo se
  exponen 2 tipos (`hotel`, `boat`) de **26 categorías top-level** existentes
  (hôtel, camping, restaurant, location-de-velo, peniche, nautique, musees,
  chateaux, oenotourisme, bar, brasserie-snack, table-dhote, excursions…). Usar
  `getCategories()` filtrando `parent_id IS NULL/0`, con `slug` real como `value` y
  `name` (traducido) como etiqueta. Absorbe y cierra BUG-007. (Supersede la parte
  "tipos" de TASK-006.) (Severidad: alta — desbloquea el valor real del buscador.)
- **BUG-008 / TASK-016** — Filtro "Destination" no fiable por datos sucios. La
  columna `listings.city` **NO contiene la ciudad**, contiene el NOMBRE del
  establecimiento (ej. city="LE GRAND BASSIN" para "SAS C. CASTEL"). La localidad
  real está embebida en `address` ("11400 Castelnaudary, France") y `postal_code`.
  Por eso `getCities()` devuelve 250 nombres de negocio (inservible) y el dropdown
  está hardcodeado a Toulouse/Carcassonne, que solo aciertan "de rebote" por el
  `address LIKE :city`. Opciones: (a) **corto plazo** — lista curada de etapas
  reales del canal (Toulouse, Castelnaudary, Carcassonne, Homps, Le Somail, Béziers,
  Agde, Sète…) que casan con el contenido de `address`; (b) **largo plazo** —
  backfill de una columna `commune` limpia extrayéndola de `address`/`postal_code`.
  Decisión de producto pendiente: empezar por (a), dejar (b) como tarea de datos
  aparte. (Severidad: alta.)
- **BUG-009** — Búsqueda vacía sin guía. Enviar el form sin `q`/`city`/`type`
  devuelve el catálogo completo y el `<title>` queda `Résultats pour '' | Canal du
  Midi` (comillas vacías, `PageController.php:175`). Mostrar un título sensato cuando
  no hay query y/o un estado "Affinez votre recherche". (Severidad: media.)
- **TASK-017** — Robustez/feedback del buscador. `home-ai.js` no comprueba
  `response.ok` antes de `response.json()` (una respuesta 500/HTML lanza y cae al
  fallback silenciosamente, ver `home-ai.js:80`); sin estado de carga en el botón
  "Rechercher" del flujo clásico. Añadir chequeo de `response.ok`, mensajes de error
  más claros y feedback de carga en submit clásico. (Severidad: media.)

### Motion / animaciones

_(TASK-008 revertida — ver 🚫 Descartadas / en pausa)_

### Rediseño visual de la home — efecto WOW (iniciativa)

Dirección de arte: **mantener** violeta `#544DBE` + teal `#2BB6C4` + Playfair
Display; **añadir** una luz cálida de Occitanie (terracota/ville rose) para que
deje de leer como SaaS frío. Tesis: "el canal es una línea de agua horizontal;
la home debe sentirse como deslizarse por ella". WOW desde **carga del hero +
reveals + micro-interacciones**, NUNCA scroll-jacking ni pins de sección
(lección de TASK-008). Verificar en navegador cada incremento.

_(TASK-009 y TASK-010 movidas a 🔴 En curso — Incremento 1)_

- **TASK-011** — _(ELIMINADA 2026-06-23 por decisión del usuario — ver 🚫
  Descartadas)_ Elemento firma "la ligne d'eau".
- **TASK-012** — "Les étapes du canal": tira secuenciada real
  Toulouse → Castelnaudary → Carcassonne → Béziers → Étang de Thau (secuencia
  geográfica real → marcadores numerados/écluse justificados). Punto de entrada
  a destinos/búsqueda.
- **TASK-013** — _(movida a 🔴 En curso — Incremento 2)_ Micro-interacciones
  premium (150–250 ms, solo `transform`/`opacity`): cards de destinos y tours con
  *lift* + sombra suave + zoom de la imagen dentro del marco (`overflow:hidden`
  + `scale(1.06)`); nudge del icono en botones al hover.
- **TASK-014** — Limpieza de contenido off-brand de la home (parte del pase de
  pulido visual): quitar eyebrows en inglés → ver **TASK-004**; reemplazar fotos
  off-topic (montaña/mochilera) por imágenes de canal → ver **TASK-007**;
  newsletter funcional → ver **BUG-003**; bloque inmersivo "Lire la vidéo" →
  ver **BUG-004**; copy "qui se vend bien" → hablar al viajero.

## 🟢 Completadas

- **TASK-018 — Migración IA: OpenAI → Claude Sonnet 4.6 ✅ coder completo, pasa a security (2026-07-06)**
  - Búsqueda IA (`ClaudeAIService`) y planificateur (`VacationPlannerService`) migrados a `anthropic-ai/sdk` v0.36.0 (Composer). Modelo: `claude-sonnet-4-6`. Constantes `ANTHROPIC_API_KEY`/`ANTHROPIC_MODEL` en `config.php` (vía `.env`); `OPENAI_*` eliminadas. `OpenAIService.php` borrado (git rm).
  - `SanitizesPrompts` (nuevo trait): `sanitizeUserPrompt()` extraído de los dos servicios; hardening SEC-002/SEC-010 intacto (trim, colapso de saltos, elimina delimitador, `mb_substr`). Test CLI 4/4 PASS.
  - Prompt caching: catálogo en bloque `system` con `cacheControl: ephemeral`; instrucciones en bloque separado (byte-estable). `outputConfig` con `json_schema` structured outputs; prefill `assistant` eliminado (no compatible con Sonnet 4.6 → era causa de 400).
  - Fallback: sin API key o ante `\Throwable` → `SmartAIService::analyzeRequest` (búsqueda) / `::fallback()` (planner).
  - Verificado: `php -l` OK en todos los archivos; `grep -rniE "openai|gpt-|api\.openai" src/` → **0**; `/fr/search` y `/fr/vacation-planner` HTTP 200, **0 errores de consola** (Playwright). Smoke de caching (`scripts/smoke_claude_cache.php`) y verificación con API key real pendientes de que el usuario inserte su `ANTHROPIC_API_KEY` en `.env`.
  - Archivos: `composer.json`, `src/config/config.php`, `src/Infrastructure/Services/SanitizesPrompts.php` (nuevo), `src/Infrastructure/Services/ClaudeAIService.php` (nuevo), `src/Infrastructure/Services/VacationPlannerService.php` (reescritura parcial), `src/Infrastructure/Controllers/PageController.php:577`, `scripts/test_sanitize.php` (nuevo), `scripts/smoke_claude_cache.php` (nuevo). `src/Infrastructure/Services/OpenAIService.php` eliminado.

- **SEC-001 + SEC-012 — Credenciales de BD vía `.env` + fix leak en catch PDO ✅ pipeline completo + security ⚠️ verificado en navegador (2026-06-24)**
  - **SEC-001:** `src/Config/Database.php`: las 4 credenciales hardcodeadas (`localhost`/`canal_du_midi`/`root`/`""`) → `$_ENV['DB_HOST'|'DB_NAME'|'DB_USER'|'DB_PASS'] ?? fallback` (mismo valor local, XAMPP sigue conectando). DSN como `$dsn`, charset=utf8mb4 fijo. Sin tocar BD ni `.env`/`.env.example`.
  - **SEC-012 (nuevo, corregido por security):** el `catch(PDOException)` hacía `die("…" . $e->getMessage())` → fugaba host/usuario/BD al navegador (ej. `Access denied for user 'root'@'localhost'`). Corregido: `error_log($e->getMessage())` (server-side) + `die("Erreur de connexion à la base de données.")` (genérico). Lección SEC-012 registrada.
  - Verificado (security, Playwright): `php -l` OK; `grep '"root"|canal_du_midi|localhost'` solo en fallbacks `??`; `.env` en `.gitignore`; home HTTP 200 con datos de BD ("ABORDAGE MOUSSAILLON" ×12); `/fr/fiche/a-labordage-moussaillon` HTTP 200; 0 error de conexión en pantalla. Veredicto ⚠️ aprobado.
  - Deuda menor (no bloqueante): `.env.example` sección DB usa valores XAMPP locales en vez de placeholders `your-db-host` (cosmético, sin riesgo).
    de `??` (líneas 15-16), no como credenciales sueltas. Verificación navegador: pendiente del agente fork.

- **PRD-007 / BUG-014 — Cargar Leaflet en la ficha de POI + fix ancla #newsletter ✅ pipeline completo + security ⚠️ verificado en navegador (2026-06-24)**
  - Causa raíz: `footer.php` no tenía rama `$page === 'poi'` → `window.L` undefined → hueco gris silencioso.
  - Fix (3 archivos, sin BD): (1) `header.php` — `<link>` Leaflet CSS 1.9.4 al bloque `$page === 'poi'`. (2) `footer.php` — bloque `$page === 'poi'` con Leaflet JS 1.9.4 (SRI auditado) + `map.js` (genérico, reusado; orden Leaflet→map.js). Sin markercluster. (3) `home.php` + `header.php` — anclas muertas `#newsletter` → `#plan` (relativa en home; `BASE_URL.$lang/home#plan` absoluta en header). `grep 'href="#newsletter"' src/` → 0.
  - **Verificado en navegador (security, Playwright, PRD-002):** `/fr/poi/3` → HTTP 200, `.leaflet-container` + 8 tiles CartoDB + 1 marcador, `window.L.version=1.9.4`, coords `43.601/1.455`; `/fr/poi/1` → coords distintas `43.216/2.35` (leídas de `data-*`, no hardcodeadas); service/fiche y search sin regresión; anclas correctas; 0 errores consola/integrity. Veredicto ⚠️ aprobado.

- **SEC-003 (CERRADA del todo) — SRI en los CSS de Leaflet/markercluster ✅ (2026-06-24)**
  - Última deuda de SRI: los 3 `<link>` de `leaflet.css` (service/search/poi) + `MarkerCluster.css` + `MarkerCluster.Default.css` en `header.php` iban sin `integrity`. Añadido SRI sha384 + `crossorigin="anonymous"` a los 5 (hashes calculados de unpkg). Verificado en navegador: search/poi/fiche renderizan el mapa con 0 errores de integrity, 0 de consola. **Ya NO queda ningún recurso CDN (JS ni CSS) sin SRI en todo el proyecto.** Cierra SEC-003 y SEC-004 por completo.

- **TASK-001 + TASK-002 — Hardening escape de salida + prompts OpenAI ✅ pipeline COMPLETO + product ⚠️ verificado en navegador (2026-06-24)**
  - **Veredicto product:** ⚠️ listo con mejoras menores. Verificado en navegador (Playwright headless, render real + muestra, PRD-002). Capturas en scratchpad: `PROD-service.png`, `PROD-poi.png`, `PROD-vacplanner.png`.
  - **Criterios de éxito CUMPLIDOS:** (1) Ficha `/fr/fiche/a-labordage-moussaillon` → HTTP 200, título/h1 `À L'ABORDAGE MOUSSAILLON !` (acento `À` + apóstrofe correctos, **0 mojibake, 0 doble-escape**), mapa Leaflet con **1 marcador + 12 tiles** (el cast `(float)` de lat/lng NO rompió el marcador), galería, reviews, formulario de reserva con modal de doble confirmación (summary-card), **0 errores de consola**. (4) `name="service_id" value="246"` entero correcto. (3) `data-sid` usa `(int)$service->id` (confirmado estático); el botón "Voir plus d'avis" es condicional a `reviewCount > mostrados` y NINGÚN servicio del catálogo lo dispara, así que no se observó en vivo (sin servicio cualificable). (2) **SEC-010 vídeo:** lógica de allowlist correcta, pero **0/253 servicios tienen vídeo** poblado → ningún iframe se renderiza (realidad de datos, no bug). (5) **POI** `/fr/poi/3` → HTTP 200, título/h1 `Écluse de Bayard (Gare)` + alts (`HÔTEL DE BORDEAUX`, `LA CHARTREUSE Hôtel`) con acentos intactos, **0 mojibake/doble-escape** (escape de poi_detail.php correcto). (6) **Vacation planner** `/fr/vacation-planner` → HTTP 200, asistente de 4 pasos renderiza, 0 errores de consola. (7) **Vacation PDF:** referencia inválida → **404 + "introuvable"** (manejo elegante, sin fuga de error PHP); no se pudo probar un PDF real (sin referencia de prueba y lectura de BD bloqueada por la restricción "no tocar BD"); escape de la vista confirmado estático (16 `htmlspecialchars`, todas con ENT_QUOTES).
  - **Auditoría de escape final:** `grep htmlspecialchars | grep -v ENT_QUOTES` → **0** en los 6 archivos de TASK-001. Sin regresiones de render (mapa con marcador OK, sin doble-escape visible, sin atributos rotos).
  - **Seguimiento NUEVO abierto (🟡, no bloqueante):** PRD-007/BUG-014 — el mapa de la ficha de POI nunca renderiza porque `footer.php` no carga Leaflet para `$page==='poi'`. Bug PRE-EXISTENTE de gating, NO regresión de TASK-001. Registrado en LESSONS.md (PRD-007) y ERROR_LOG.md.

- **TASK-001 + TASK-002 — (entrada security) Hardening escape de salida + prompts OpenAI ✅ pipeline security ⚠️ (2026-06-24)**
  - **TASK-001 (escape de salida):** pase de flag `ENT_QUOTES, 'UTF-8'` en los 6 archivos de vistas: `service_detail.php`, `poi_detail.php`, `vacation_pdf.php`, `review_item.php`, `header.php`, `booking_summary_modal.php`. Verificado: `grep htmlspecialchars | grep -v ENT_QUOTES` → 0 en los 6 archivos; `php -l` OK en todos. Security corrigió adicionalmente: (1) SEC-010 — `strip_tags('<iframe>')` reemplazado por extractor seguro con allowlist de hosts (youtube/vimeo) + reconstrucción del iframe con `htmlspecialchars` + `sandbox`; (2) SEC-011 — `$service->id` sin cast `(int)` en `data-sid` (L481) y `value=service_id` (L634) corregidos.
  - **TASK-002 (hardening prompts OpenAI):** `sanitizeUserPrompt()` en `OpenAIService.php` y `VacationPlannerService.php`. Input delimitado + cláusula anti-override en system. Sistema (no contiene input de usuario), IDs de BD validados como `(int)` en `buildResultsFromIds`. `php -l` OK en ambos.
  - **Aviso vacation_pdf.php (evaluado por security):** `$plan` en `renderVacationPDF` se lee de BD (`vacation_plans.plan_data`) por referencia (`reference` en URL), NO de `$_POST['plan']` directamente. El controlador busca la fila por `reference` (PDO preparado). La vista `vacation_pdf.php` escapa todos los campos con `ENT_QUOTES`. Riesgo de data disclosure entre usuarios: inexistente — cada visitante solo puede acceder a su propio plan si conoce la referencia (token opaco `CDM-YYYY-XXXXXXXX`). Veredicto: no hay vulnerabilidad de ownership explotable.
  - **Nuevas lecciones registradas:** SEC-010 (iframe de BD sin validar host), SEC-011 (IDs sin cast en vistas).

- **Tanda autónoma de backlog (`/loop` 30 min) — PRD-006, TASK-003, TASK-004, BUG-004, SEC-004, SEC-009, SEC-002 ✅ (2026-06-24)**
  - **PRD-006/BUG-012** ✅ (pipeline architect→coder, verdict PASS en navegador): meta de las 4 tour-cards muestra categoría legible ("Location de bateau", "Location de vélo", "Croisière en bateau"), 0 slugs crudos. `PageController` `$tourMetaLabels` + helper `resolveTourMetaLabel`; `home.php` imprime escapado (SEC-006). 2 archivos, sin BD.
  - **TASK-003** ✅: eliminadas queries/cálculos muertos del case home (`$destinations`, `$features=getActiveFeatures`, `$articles=getLatestArticles`); −2 queries/carga. `php -l` OK, home 200 sin warnings.
  - **TASK-004** ✅: 6 cadenas EN→FR en `home.php` (Destinations phares, Séjours populaires, Où souhaitez-vous aller ?, Pourquoi nous choisir ?, Offres flash de la semaine, Escapades d'été). Verificado: 0 inglés en la home. (Las cadenas "Sign up for our newsletter"/"Submit" ya no existían.)
  - **BUG-004** ✅: eliminado el `<button class="play-button">` "Lire la vidéo" muerto (sin handler; default autónomo = quitar). Banda inmersiva intacta.
  - **SEC-004** ✅ (COMPLETA): SRI sha384 + `crossorigin="anonymous"` en Leaflet 1.9.4 y leaflet.markercluster 1.5.3 (`footer.php`) Y bootstrap-icons 1.11.1 CSS (`header.php`). Verificado en navegador: Leaflet 1.9.4 carga en /search (0 errores integrity, mapa renderiza) y la fuente bootstrap-icons carga en la home (0 errores integrity, iconos renderizan). Ya no queda ningún CDN externo sin SRI.
  - **SEC-006 tour-card** ✅: corregidas 3 llamadas `htmlspecialchars()` preexistentes sin `ENT_QUOTES,'UTF-8'` en la tour-card de `home.php` (img src, alt, h3 title), detectadas por security al revisar el bloque PRD-006. `grep` → 0 restantes en home.php.
  - **SEC-009** ✅ (ya estaba en código; docs reconciliados): iframe Calaméo con `sandbox` mínimo.
  - **SEC-002** ✅ (ya estaba en código; docs reconciliados): grid de destinos escapa `$url`/`$bgImage` con `ENT_QUOTES,'UTF-8'`.
  - Restricción respetada: ninguna acción tocó la BD. Verificación Playwright (PRD-002). Plan: `~/.claude/plans/optimized-toasting-pearl.md`.

- **SEC-008 — `htmlspecialchars()` sin `ENT_QUOTES, 'UTF-8'` en `EmailTemplates.php` ✅ corregido (2026-06-24)**
  - Pase de limpieza (1 archivo) de las 21 instancias sin el flag (texto de nodo + varias en `href="mailto:"`/`href="tel:"`). Aplicado con `perl` + lookahead (no doble-escapa las correctas). Verificado: `grep` → 0 restantes, `php -l` OK, hrefs intactos. No explotable en PHP 8.2 pero cumple SEC-006. Lección: SEC-007/SEC-008.

- **BUG-013 — Embed Calaméo de la sección `#plan` se veía mal ✅ corregido (2026-06-24)**
  - Reportado por el usuario con captura. Causa raíz: se usaba la página de lectura completa (`www.calameo.com/read/...`) en un contenedor 16:9, en vez del visor embebible oficial. El sitio live (plan-canal-du-midi.com) usa el embed mini `//v.calameo.com/?bkcode=...&mode=mini` (480×400).
  - Fix in-situ (1 archivo, `home.php` sección `#plan`, supersede la descripción del iframe en BUG-003): `src` → `https://v.calameo.com/?bkcode=003331405edc35288442a&mode=mini`; tamaño nativo 480×400 como columna izquierda fija (`flex:0 0 480px;max-width:100%`); eliminado el hack `padding-bottom:56.25%`. `sandbox` (SEC-009) intacto, re-verificado con el host `v.calameo.com`.
  - Verificado en navegador (Playwright, PRD-002): desktop 1280 renderiza la portada "L'Officiel du Canal du Midi 2026" + 2 columnas como la referencia; móvil 390 apila centrado con form full-width; frame `v.calameo.com` cargado en ambos, **0 errores de consola**. Capturas `PLAN-FIX-desktop.png`, `PLAN-FIX-mobile.png`. `php -l` OK.

- **Cluster "Bugs funcionales de la home" — BUG-001, BUG-002, BUG-003 — ✅ pipeline completo + product ⚠️ verificado en navegador (2026-06-24)**
  - **Veredicto product:** ⚠️ listo con mejoras menores. Verificado en navegador (curl + Chrome headless, conteo Y muestra real, PRD-002). Capturas en scratchpad: `DESK3-plan.png`, `DESK3-form.png`, `DESK3-fb.png`, `PROD-mfull-b.png`. Los 8 criterios de éxito del handoff CUMPLIDOS. Único seguimiento abierto: PRD-006/BUG-012 (meta de tour-card con slug crudo), no bloqueante, movido a 🟡.
  - **Verificación en navegador (evidencia real):** Home HTTP 200, log PHP limpio (sin fatal/warning de canal_du_midi durante las pruebas). **BUG-001:** las 4 tour-cards son experiencias reales — `service/246` À L'ABORDAGE MOUSSAILLON (location-bateau), `service/232` CAMPING DE MONTOLIEU (camping + location-de-velo → entra por velo), `service/200` CRIS'BOAT (location-bateau), `service/244` CROISIERES DU MIDI HOMPS (croisiere-bateau). Categorías confirmadas en BD vía `listing_categories`. **0 écluses, 0 ports** (PRD-005 respetado). NO es el fallback genérico. **BUG-002:** href = `http://localhost/canal_du_midi/fr/service/NNN` (BASE_URL correcto, no hardcodeado). **BUG-003 iframe:** Calaméo `003331405edc35288442a`, contenedor responsive 16:9 (`padding-bottom:56.25%`), `title` descriptivo, `loading=lazy`, no desborda en móvil. **BUG-003 form:** `method=POST`, `action=.../fr/plan-request`, `name=email`, `required`. Email válido → 302 → `?plan=ok#plan` + banner verde "Vérifiez votre boîte mail — votre plan est en route !" (SMTP envió, desviado a MAIL_TEST_ADDRESS). Email inválido y vacío → `?plan=invalid#plan` + "Adresse e-mail invalide…", sin fatal. GET a plan-request → redirige limpio a `/home#plan`. `?plan=error` → "Une erreur est survenue…". Flag fuera de whitelist (`?plan=zzz`) → 0 alertas (whitelist OK). Feedback anclado a `#plan`, `role="alert"`, colores semánticos (verde/rojo/ámbar), copy FR correcto. Regresión: hero, buscador, destinos, why-us intactos.
  - **BUG-001** — `$tours` filtrado por categorías experienciales (`excursions`, `location-de-velo`, `peniche`, `nautique`) vía `resolveCategoryIdsForSearch()` + `searchListings()`. Fallback a `array_slice($allServices,0,4)` si vacío. `$allCatsRaw` movido antes del cálculo de `$tours`. Archivos: `PageController.php` case `home`.
  - **BUG-002** — YA estaba corregido (`home.php:244` usa `BASE_URL + (int)$tour->id`). Sin cambio de código; solo verificación.
  - **BUG-003** — Sección `id="plan"` en `home.php` reemplaza la newsletter genérica: iframe Calaméo responsive (ratio 16:9 con `padding-bottom:56.25%`), form POST hacia `/plan-request`, feedback `$planFeedback` (ok/invalid/error) con mensajes FR escapados. Nuevo `case 'plan-request'` en `PageController.php`: valida con `FILTER_VALIDATE_EMAIL`, envía vía `MailService::send()` con subject fijo, redirige a `?plan=ok|invalid|error#plan`. Sin persistencia (decisión firme del usuario). Nuevo `EmailTemplates::planByEmail()` con heroHeader teal→violeta, CTA "📑 Télécharger le plan (PDF)", enlace "Lire en ligne (Calaméo)" opcional, noticeBox y firma.
  - Deuda no bloqueante registrada: SEC-008 (EmailTemplates sin ENT_QUOTES), SEC-009 (iframe sin sandbox), PRD-006/BUG-012 (meta tour-card con slug crudo). BUG-004 aplazado (no tocado).

- **SEC-007 — `htmlspecialchars()` sin `ENT_QUOTES, 'UTF-8'` en `search_results.php` ✅ corregido (2026-06-24)**
  - Deuda pre-existente cerrada in-situ (fix de 1 archivo, por debajo del umbral del pipeline). No XSS explotable en PHP 8.2 (el flag por defecto ya incluye `ENT_QUOTES`), pero violaba la convención SEC-006.
  - Alcance ampliado más allá de las dos líneas reportadas (L383/L404): se corrigieron **las 11 instancias** del archivo, varias en contexto de atributo HTML (`value=`, `data-value=`, `data-label=`, `data-selected=`) donde el flag sí importa. Verificado: `grep` → **0 `htmlspecialchars(` sin `ENT_QUOTES`** restantes; `php -l` OK.
  - Archivo: `src/Infrastructure/Views/search_results.php`. Lección: SEC-006/SEC-007.

- **PRD-004 / BUG-010 + PRD-005 / BUG-011 — "coherencia del buscador hero" ✅ pipeline completo + product ✅ verificado en navegador (2026-06-24)**
  - **Veredicto product:** ✅ listo. Verificado en navegador (Chrome headless + curl, conteo Y muestra real, PRD-002). Captura: scratchpad `PRD-nautique.png`, `PRD-velo.png`.
  - **PRD-004 (título con slug crudo → nombre legible):** RESUELTO. `?type=location-de-velo` → `<title>Séjours et activités — Location de vélo …`; `?type=nautique` → `… — Le Canal en Bateau …` (antes imprimía el slug crudo). `$catNameBySlug` (slug→name) construido desde `$categories` ya cargado, sin query extra.
  - **PRD-005 (filtro náutico contaminado por écluses/ports):** RESUELTO. `?type=nautique` pasó de **136 → 17** resultados. Muestra real: 100% barcos/croisières/péniches/locations (CRIS'BOAT, CROISIERES DU MIDI, LES BATEAUX DU MIDI, LES CANALOUS, PÉNICHE SURCOUF, PURA VIDA CRUISE, NAVICANAL…). **0 écluses, 0 ports** dentro de los `card-title`. La única coincidencia "port" es "NAVICANAL - Port Lauragais", un operador de barcos (no un POI de puerto). El BFS excluye los slugs `ecluses`/`ports` solo cuando el padre seleccionado es `nautique`.
  - **Regresiones verificadas:** `?type=hotel` → 18 (solo alojamientos, intacto). Sin filtros → "Tous nos séjours et activités", 253. `?type=hotel&city=Homps` → 0 + "Aucun résultat trouvé". `?q=l'hotel` → 6, apóstrofe íntegro `&#039;` en `<title>` (SEC-006 intacto). Slug inexistente/manipulado (`?type=zzz-no-existe-123`) → NO rompe la página: fallback al slug crudo, 253 resultados, 0 warnings/notices/fatal PHP.
  - Archivos: `src/Infrastructure/Controllers/PageController.php` (L218-243 PRD-004, L1148-1199 PRD-005). `php -l` OK. Deuda pre-existente SEC-007 (search_results.php L383/L404 sin ENT_QUOTES) sigue abierta como tarea aparte, no bloqueante.

- **Cluster "Buscador del hero" — BUG-007/TASK-015 + BUG-008/TASK-016 + BUG-009 + TASK-017 ✅ pipeline completo + product ⚠️ verificado en navegador (2026-06-23)**
  - **Veredicto product:** ⚠️ listo con mejoras menores. Verificado en Chrome headless (CDP) sobre http://localhost/canal_du_midi/. Capturas en scratchpad: `VS-1-home-selects.png`, `VS-2-empty.png`, `VS-3-boat.png`, `VS-4-carcassonne.png`. Criterios principales del cluster CUMPLIDOS; quedan 2 seguimientos abiertos (PRD-004/BUG-010 título con slug crudo, PRD-005/BUG-011 filtro náutico contaminado por écluses/ports) movidos a 🟡 Pendiente, no bloquean el cierre del cluster.
  - **Conteos reales observados:** sin filtros/vacío → `Tous nos séjours et activités`, 253. type=hotel → 18 (solo hoteles ✅). type=nautique → 136 (⚠️ 93 son écluses+ports, ver PRD-005). city=Carcassonne → 10 (todos con Carcassonne en address ✅). combo Carcassonne+hotel → 0 ("Aucun" ✅). q=l'hotel → 6, `<title>` y meta íntegros (SEC-006 ✅). Botón "Rechercher" pasa a `disabled` + spinner "Recherche…" al submit (✅, verificado disparando el evento).
  - Selects: Type lista 12 tipos reales (Hôtel…Oenotourisme, sin "boat" fantasma ✅); Destination lista 9 etapas (✅).
  - BUG-007 / TASK-015: `<select name="type">` del hero poblado desde `getCategories()` filtrado por whitelist de 12 slugs turísticos top-level. Valor = slug real de BD, etiqueta = name de BD. Elimina la opción fantasma `boat`.
  - BUG-008 / TASK-016: `<select name="city">` del hero con `CANAL_STAGES` (constante en `config.php`, 9 etapas verificadas contra `listings.address`; "Le Somail" excluido por 0 coincidencias).
  - BUG-009: título SEO de búsqueda condicional — q no vacío → "Résultats pour…"; city → "Séjours et activités à {city}"; type → "Séjours et activités — {type}"; sin filtros → "Tous nos séjours et activités".
  - TASK-017: `home-ai.js` — `response.ok` check antes de `json()`, mensajes de fallback claros, feedback de carga en submit clásico (deshabilita botón + spinner).
  Archivos: `src/Config/config.php`, `src/Infrastructure/Controllers/PageController.php`, `src/Infrastructure/Views/home.php`, `public/assets/js/home-ai.js`. `php -l` OK en los 3 PHP. HTTP 200 en home y todos los casos de título de search verificados.

- **BUG-006 ✅ (verificado en navegador a 390px, 2026-06-23)** — Hero roto en vista
  móvil. **Causa raíz:** `.hero-card` es `display:flex` (fila) con dos hijos:
  `.hero-card-content` y el `<form class="hero-search">`. En escritorio el buscador
  es `position:absolute` (fuera del flujo); en móvil (≤820px) vuelve a
  `position:relative`, así que se convertía en **columna flex al lado** del contenido
  → contenido aplastado a la izquierda (desbordaba a x=-15, w=239) y buscador
  comprimido a la derecha (w=181), tal como en la captura del usuario. **Fix
  (1 línea efectiva):** en el `@media (max-width:820px)`, `.hero-card` →
  `flex-direction:column; align-items:stretch; justify-content:flex-start`, para que
  contenido y buscador se apilen. Verificado a 390px: contenido a ancho completo
  (w=358), stats en 2×2, buscador apilado debajo a ancho completo (w=358), eyebrow
  centrado y completo, 0 overflow horizontal, 0 errores de consola. Revisadas además
  las 7 secciones + footer en móvil: todas se adaptan a 1 columna correctamente (ya
  eran responsive vía los breakpoints existentes; no requirieron cambios). NOTA: el
  texto en inglés que aún aparece en móvil (Why choose us?, Weekly flash deals, etc.)
  es deuda de i18n (TASK-004), no de layout.

- **TASK-013 ✅ (pipeline completo + verificación navegador ✅, 2026-06-23)** —
  Micro-interacciones premium verificadas en navegador (PRD-002): `.destination-card`
  (vía hover del `<a>` envolvente) y `.tour-card` hacen lift `translateY(-5px)` +
  sombra profunda; la imagen escala `scale(1.06)` dentro del marco con
  `overflow:hidden` (0 desbordamiento); el icono de `.button` hace nudge
  `translateX(2px)` al hover sin romper el lift del botón. Transiciones 180–200 ms,
  solo transform/box-shadow. `prefers-reduced-motion` anula todo (transition 0s,
  sin transform al hover). 0 overflow, 0 errores de consola. Archivos: `home.php`
  (refactor capa `.destination-card-media`), `styles.css`.
  Capturas: scratchpad (`P2`, `Q1`, datos de hover).

- **TASK-009 ✅ (architect→coder→security ✅→verif. navegador ✅, 2026-06-23)** — Tokens de arte en `:root`
  (`styles.css`): `--terracotta`, `--sand-warm`, `--ink`, `--violet` (solo CTAs),
  `--water`, sin renombrar los existentes. Playfair cargado en peso recto
  (`0,600;0,700`) además de itálico (`1,700`) en `header.php`; h1 del hero con
  `font-family: Playfair Display` y última palabra en `<em>`. Decisión registrada
  en `docs/ARCHITECTURE.md`.
- **TASK-010 ✅ (pipeline completo + verificación navegador ✅, 2026-06-23)** —
  Verificado en navegador (PRD-002): parallax real (translate −5px→22.8px),
  Ken Burns activo, ligne d'eau dibujada, h1 Playfair+itálica, 4 stats reales,
  reduced-motion estático, 0 overflow/errores. Captura `H1-hero-loaded.png`.
  Hero orquestado: entrada escalonada bajo `html.hero-ready` (eyebrow → h1 →
  ligne d'eau → buscador → datos), Ken Burns lento (`scale`) + parallax sutil
  (`translate`) SOLO de `.hero-card-img` (`home-hero.js`, vanilla, early-return en
  reduced-motion). Stats reales `240 km · 63 écluses · Toulouse → Méditerranée ·
  UNESCO`. *Ligne d'eau* SVG (degradado teal→violeta + marcas-écluse) dibujada por
  `stroke-dashoffset` (fallback visible). `prefers-reduced-motion` anula todo. Sin
  pins ni scroll-jacking (PRD-002). Estado base visible (PRD-001). Sin CDN nuevo
  (SEC-003). NOTA (2026-06-23): la *ligne d'eau* del hero fue **eliminada** después
  por decisión del usuario (ver TASK-011 en 🚫); el resto del hero (entrada
  escalonada, Ken Burns, parallax, stats) se mantiene.
- **TASK-000** — Inicialización del pipeline de agentes y estructura `docs/`.

## 🚫 Descartadas / en pausa

- **TASK-011 + BUG-005** — Elemento firma "la ligne d'eau" (hairline SVG degradado
  teal→violeta del hero + 3 divisores entre secciones con marcas-écluse).
  **ELIMINADA (2026-06-23)** por decisión del usuario: "se ve muy feo y no tiene
  ninguna utilidad". Aunque pasó todo el pipeline y se verificó en navegador
  (degradado pintado correctamente tras BUG-005), el resultado visual no convenció.
  Eliminadas las 4 instancias SVG (hero + 3 divisores) de `home.php`, todo el bloque
  CSS `.ligne-eau*` / `.ligne-eau--divider` / `@keyframes ligne-eau-draw` y la regla
  reduced-motion asociada de `styles.css`, y los comentarios obsoletos de
  `home-hero.js`. Verificado: home render limpio (HTTP 200), 0 referencias `ligne-eau`
  en HTML servido, 0 errores PHP, `php -l` OK. El hero (entrada escalonada, Ken Burns,
  parallax, stats) y las micro-interacciones de TASK-013 quedan intactos.
  **Nota de dirección de arte:** la tesis "el canal es una línea de agua horizontal"
  se conserva como concepto, pero el hairline literal queda descartado; si se retoma
  la idea, buscar otra expresión visual (no la hairline + écluse).

- **TASK-008** — Capa de motion narrativo (GSAP + ScrollTrigger) en la home.
  **REVERTIDA (2026-06-23)** por decisión del usuario tras verificación visual.
  El pipeline (architect → coder → security → product) la dio por ✅/⚠️ a nivel
  de código, pero la verificación en navegador (`/verify`) destapó un fallo
  visual real que el pipeline no detectó: los **dos pins de sección completa**
  (`#destinations` track horizontal y `#experiences` editorial) **se solapaban**
  —el `<h2>` editorial montándose sobre las cards— y la **línea del canal** era
  un trazo diagonal tosco. Causa: pinear dos secciones contiguas con
  `start:'top top'` + viewport del track sin altura de pantalla.
  Revertidos quirúrgicamente: `footer.php` (a HEAD), `gsap-story.js` (borrado),
  y quitados los hooks `data-story`/bloque CSS "GSAP Story layer" de `home.php`
  y `styles.css` **preservando los cambios previos** (contenido editorial,
  layout de tour-cards). Verificado: home vuelve a render limpio, `gsap`
  undefined, 0 `[data-story]`, sin overflow, anclas y reveals intactos.
  **Lección (LESSON pendiente de registrar):** el pin de sección completa
  múltiple es frágil; verificar SIEMPRE en navegador antes de aprobar motion,
  no solo a nivel de código. Si se retoma, ir por la opción "simplificar"
  (parallax sutil + reveals + progreso, sin pins de sección).
