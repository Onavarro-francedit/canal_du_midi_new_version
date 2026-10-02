# TASKS.md — Canal du Midi

Convención de IDs: `TASK-NNN` tareas · `BUG-NNN` bugs · `SEC-NNN` seguridad.

## 🔴 En curso

### TASK-046 — accueil-2026: FAQ alineada con las búsquedas reales — IMPLEMENTADO Y DESPLEGADO (coder) 2026-10-02, pendiente security + smokes/visual
Sustituye la FAQ n.º 5 (« ouvrages », sin demanda) por « Combien coûte la location d’un bateau… » (sin cifras) y
añade « horaires des écluses » (= /navigation/periode-de-navigation/) y « combien de jours ». 8 preguntas.
Solo `wp-plugin/canal-home/includes/content.php` (+ aserción en `tests/smoke-render.php`). Página 18500 PRIVADA.
Al publicar: regenerar y subir `llms-full.txt` con categorías (PRD-013). Coder: `remote.sh test` Todo OK, deploy hecho; smoke-render/smoke-seo y visual los hace la sesión principal. Siguiente: security.

_(Publicar home + carte + ficha: SOLO con orden explícita, TASK-028 / TASK-029b / TASK-030b en 🟡.)_

## 🟡 Pendiente

### Navbar 2026 — mejoras SEO/AEO/GEO (aprobadas 2026-10-01, ver `docs/navbar-analisis-2026-10-01.md`)
**Estado 2026-10-01: TASK-037 a 042 DESPLEGADAS (privadas) Y VERIFICADAS EN PRODUCCIÓN** (carte `?type=hebergement`,
ficha, home; 1876 / 390 / 360 px). Fuente Manrope en los `<p>` del pie y del mega-menú (el tema fuerza Quicksand con
!important) corregida y verificada en producción. Archivos: `canal-home/includes/header.php` (menú §7, Avisbat,
`canal_header_section()`, accesos rápidos + lupa, `canal_footer_menu()` y pie en `mylisting/get-footer`),
`canal-home/assets/header.css` (sección actual, iconos móvil, panel de búsqueda, pie; oculta `footer.footer` del tema
solo en las 3 páginas; botones del menú 9→7 px para que quepa la lupa sin tocar los 100 px ni los 1360 px),
`tests/test-header.php` (nuevo, 14 checks). Pendiente: añadir `test-header.php` a `remote.sh test` y redesplegar.
- **TASK-037** — Sustituir el enlace « Calcul d'itinéraire fluvial (VNF) » (CIFL cerrado, redirige a la nota de
  cierre) por Avisbat `https://avisbat.vnf.fr/`. Alta · 5 min.
- **TASK-038** — Móvil (≤1180 px): Carte y Distances como iconos en la barra; CTA y tools arriba del cajón. Alta · S.
- **TASK-039** — `aria-current="page"` + marca visual de la sección activa (PHP + CSS). Alta · S.
- **TASK-040** — Etiquetas: renombrar solapes (écluses, ports, restaurant), Shopping → Préparer, reubicar
  Associations, separar guías de listados en Bateau/Vélo (§7 del informe). Media · S.
- **TASK-041** — Footer rico propio en las páginas 2026 (aditivo, sin tocar el del tema). Media · M.
- **TASK-042** — Lupa en la cabecera → `/explorer-2026/?search_keywords=` (coherente con el `SearchAction`). Media · S.

### Migración visual del WordPress de producción (nuevo rumbo, 2026-09-28)
- **TASK-044 — Planificateur de séjour 2026 (`/planificateur-2026/`, privada)** — spec
  `docs/superpowers/specs/2026-10-01-planificateur-2026-design.md`, mockup aprobado `docs/mockups/planificateur-2026.html`.
  Asistente conversacional (orbe IA, chat) + demanda a prestatarios con confirmación por e-mail; sin e-mail →
  mbauwens@francedit.com; en desarrollo todo → onavarro@francedit.com. Siguiente: revisión de la spec → writing-plans.
  Fase 2 (fuera): « Comment venir » con enlaces Omio.
- **TASK-043 — Contenido de las páginas 2026 gestionable desde el backoffice de WP** — SOLO tras la aprobación del
  diseño 2026 por el cliente (decisión del usuario 2026-10-01: no invertir antes). Navbar y pie → menús nativos nuevos
  (`register_nav_menus` « Navbar 2026 » / « Pie 2026 »; 3 niveles: panel → título de columna → enlace; pie de panel con
  clase CSS `cdm-foot`), sin tocar « Principale »; sustituye `canal_header_menu()` / `canal_footer_menu()`. Después:
  `CANAL_HOME_FAQ`, `CANAL_HOME_ETAPES`, `CANAL_HOME_STAGES`, `CANAL_HOME_SEJOUR_CATS` → campos de la página.
- **TASK-028 — Publicar « Accueil 2026 » como portada** — SOLO con orden explícita del
  usuario. **Decisión 2026-09-29: se deja privada por ahora.** `wp-plugin/remote.sh wp post update 18500 --post_status=publish` y *Réglages →
  Lecture* → página de inicio = « Accueil 2026 » (hoy `page_on_front` = 15269, la home
  Elementor). Publicar y cambiar portada en el mismo momento (al publicarse ya es
  accesible en `/accueil-2026/`). Rollback: volver a poner 15269.
- **TASK-029b — Publicar la carte (`/explorer-2026/` → `/explorer/`)** — SOLO con orden explícita. **Publicar JUNTO con
  TASK-028:** desde el 29/09 la home privada enlaza a la carte (`CANAL_CARTE_PATH` = `/explorer-2026/`) (buscador, étapes, CTA, IA,
  SearchAction del JSON-LD) y, solo en home y carte, el botón « Carte interactive » del menú del
  tema se reescribe a la carte por JS (`canal_home_carte_menu_js`). Si se publica la home sin la
  carte, esos enlaces dan 404 a los visitantes. Al publicar: página 18502 → publish; quitar el sufijo -2026 (la página 10154 `/explorer/` actual se retira o renombra antes) y
  `CANAL_CARTE_PATH` → `/explorer/`; con eso el botón del menú del tema ya apunta bien. Revisar `wp-plugin/llms.txt` y `build/build-llms-full.php`
  (hoy públicos, se dejaron en `/explorer/`). Revisar noindex/SEO y peso (~0,6–0,8 MB).
- **TASK-030b — Publicar la ficha 2026** — SOLO con orden explícita, JUNTO con TASK-028/029b.
  `CANAL_FICHE_PATH` → `/fiche/`; aplicar la plantilla a `is_singular('job_listing')` (sin regla
  propia); 301 de `/fiche-2026/…` a `/fiche/…`; quitar el control de sesión y el noindex (la
  condición `CANAL_FICHE_PATH !== '/fiche/'` en seo-fiche.php ya lo hace); vaciar
  `canal_carte_listings`. Rollback de TASK-030: quitar los archivos fiche-* del plugin y
  `wp option delete canal_fiche_rewrite && wp rewrite flush`.
- **TASK-031 — `<head>` roto en TODO el sitio (dejar para el final, decisión del usuario 30/09)** — el
  `header.php` del tema imprime `<div id="fb-root"></div>` antes de `wp_head()`: el navegador cierra el
  `<head>` ahí y title/meta description/canonical/robots/OG/JSON-LD de las páginas actuales quedan en el
  `<body>` (Google puede ignorarlos; PageSpeed: « sin metadescripción »). El sitio NO se cae: solo SEO.
  Ya corregido en nuestras páginas (`includes/head-fix.php`). Plan: ampliar el mismo buffer primero a
  `/fiche/…` (comprobar 3-4 fichas + consola + PageSpeed), luego al resto; vaciar WP Fastest Cache.
  Rollback: quitar la condición/el archivo y vaciar la caché. Toca lo existente → SOLO con orden explícita.
- **Minors de TASK-027 (diferidos, revisión final):** prompt no-string → llamada facturada
  (falta `args` type=string en la ruta REST); delimitador del prompt reconstruible por
  anidado; límite por IP eludible rotando IPv6 /64; `replaceChildren` (Safari ≥ 14);
  `@keyframes` sin prefijo; `url('…')` inline con `esc_url`; `remote.sh` con `/tmp` fijo y
  despliegue no atómico (`--delay-updates`); a 375 px « DE GÉNIE HYDRAULIQUE » roza el
  borde; los smokes exigen el plugin desactivado. (Foto `rando-velo_2.webp` sustituida por
  `2020/01/img_8404_1.jpeg`, la imagen propia de la categoría « Le Canal à Vélo », 2026-09-29.)
- **Antes de publicar (TASK-028):** revisar `pm.max_children` del pool PHP-FPM (cada
  llamada IA ocupa un worker ~7 s, hasta 30 s).
- **Observación (no nuestra, no tocada):** `httpdocs/wp-config.php` se reescribe de
  madrugada (29/09 01:24:47 UTC, usuario del sitio, 600) — probable tarea de
  Plesk/WP Toolkit. Contraseña de BD en claro en `my-listing/page_accueil_test.php`
  (regla: no se modifica nada existente).

- **BUG-020 / PRD-012 (medio — product, TASK-026, 2026-09-03)** — El reordenado
  por drag & drop funciona y persiste, pero es **indescubrible**: ninguna pista
  textual, `cursor: pointer` en vez de `grab`/`move`, ningún icono de arrastre;
  el label solo menciona elegir la portada. Además el drag nativo HTML5 no
  funciona en táctil → en tablet/móvil el reordenado es inaccesible sin fallback.
  Decidir: añadir pista + `cursor: grab` (+ flechas ↑↓ como fallback táctil), o
  documentar que el backoffice es desktop-only. Nota relacionada: el picker mezcla
  portada y galería y, tras recargar, la portada vuelve siempre al primer hueco,
  así que la disposición exacta que hizo el usuario puede no coincidir con lo que
  ve al volver (el ORDEN de la galería sí se respeta, en el picker y en el
  carrusel público).

- **SEC-015** (backlog, no bloqueante) — `backoffice-edit.js` interpola URLs sin
  escapar comillas en `style.backgroundImage` (`setHeroPreview`/`renderPhotos`).
  No explotable HOY (URL siempre generada server-side con hash aleatorio), pero
  es deuda de patrón. Ver LESSONS.md / ERROR_LOG.md.

- **SEC-014** (backlog, no bloqueante) — `ai-plan-generate`/`ai-plan-submit` sin
  token CSRF ni rate-limit por IP/sesión. Pre-existente (no es regresión de
  TASK-019). Ver LESSONS.md / ERROR_LOG.md.

- **TASK-025 / PRD-009** (i18n, UX — abierta por product en el cierre de TASK-019,
  2026-07-06) — El planificateur mezcla idiomas de cara al usuario. `vacation_planner.php`
  tiene todo el chrome hardcodeado en francés (títulos, labels "Arrivée prévue"/"Départ
  prévu"/"Vos coordonnées", intro "Nous allons transmettre votre demande…", botón
  "Envoyer ma demande d'intérêt", pantalla de confirmación), y `vacation-planner.js`
  solo localizó los 4 strings de fecha de TASK-019 (`DATE_I18N` fr/es/en); los demás
  errores JS (`input-error` prompt vacío/fallo de generación; `submit-error`
  nombre/email vacío → "Veuillez renseigner les champs obligatoires."/fallo de envío)
  siguen en francés fijo. Verificado en navegador (`/es/vacation-planner`, flujo real):
  en el MISMO formulario, fechas vacías → error en español, nombre vacío → error en
  francés; hint español entre labels francesas. Con TASK-018 (IA responde en el idioma
  del usuario) la mezcla es más evidente: plan en es/en dentro de marco francés. Fix:
  extraer todos los textos del planner (vista + strings JS restantes) a un mapa i18n
  fr/es/en coherente, o decidir explícitamente que el planner es monolingüe francés y
  no exponerlo en `/es/`·`/en/`. Verificar en navegador los 3 idiomas. Lección: PRD-009,
  corolario de PRD-004/PRD-006. (Severidad: media — no bloquea la conversión, pero
  rompe la coherencia para el turista hispano/anglófono.)

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

### Buscador `/search` — filtro por tipo del sidebar (verificado en navegador 2026-07-06)

- **BUG-015** — En `/search`, un `type[]` inválido/desconocido (reproducido con
  `?q=&type[]=portsc`) **no se ignora ni da 404**; ensucia toda la página:
  (1) aparece como chip de servicio seleccionado en el dropdown "Service(s)
  souhaité(s)" (se ve literalmente "portsc" con un badge numérico "47");
  (2) el filtro no casa ningún slug real → efecto BUG-007: **devuelve los 253
  listings sin filtrar** mientras el usuario cree que filtró; (3) el slug crudo se
  imprime en el `<title>` → "Séjours et activités — portsc"; (4) el selector de
  idioma **propaga el `type[]` roto** a ES/EN (`.../es/search?…&type[]=portsc`).
  Mismo patrón que BUG-007/PRD-004 pero en la página de resultados, no en el hero.
  Fix: validar cada `type[]` contra los slugs reales de categoría; descartar los
  inválidos (o 404 explícito); no imprimir slug crudo en título ni chip; no arrastrar
  tipos inválidos al cambiar de idioma. Lección aplicable: BUG-007, PRD-004.
  (Severidad: alta — es lo primero que toca el usuario y rompe el filtro.)

### Planificateur IA — conversión del lead (iniciativa de negocio, 2026-07-06)

Contexto: el flujo del planificador (`/fr/vacation-planner`) funciona de punta a
punta (petición NL → itinerario IA con catálogo real → edición → captura de lead →
email por prestador, branded). Análisis de negocio del usuario ("como Steve Jobs"):
**el producto está construido pero regala el lead y la relación** — manda el email
del viajero a cada prestador y se sale del bucle, sin fechas firmes, sin seguimiento
ni monetización. Tesis: pasar de "buzón de leads" a "conserje que cierra el viaje" —
la plataforma se queda en medio, mide conversión y habilita el cobro. Verificado en
navegador (Brave, extensión Claude) el flujo completo + el email al prestador (solo
lleva email del viajero; `Séjour: dates à confirmer`; "contactez directement").

- **TASK-019** _(✅ pipeline coder + security ⚠️ aprobado con observaciones
  2026-07-06 — ver 🟢 Completadas; pendiente handoff a product)_
  (conversión, barata, alto impacto) — Fechas estructuradas en el
  planner. La IA ya interpreta "début septembre" para el itinerario, pero los campos
  Arrivée/Départ del paso 3 quedan vacíos y el email al prestador sale
  "dates à confirmer". Extraer/normalizar las fechas del texto libre → (a) prerrellenar
  Arrivée/Départ, (b) hacerlas obligatorias antes del envío, (c) incluirlas en el email
  al prestador. Un lead con fechas convierte mucho más que "dates à confirmer".
- **TASK-020** (conversión) — Contacto obligatorio: exigir **teléfono O email** (hoy
  ambos opcionales; el turismo cierra por teléfono). Además `autocomplete` seguro en el
  campo email del viajero: la autofill de Brave metió un correo personal ajeno en la
  prueba — usar `autocomplete="off"`/`"new-password"` para que el viajero no envíe sin
  querer datos guardados.
- **TASK-021** (ESTRATÉGICA, mayor — pasar por architect) — "Répondre via Canal du
  Midi". Sustituir en el email del prestador el "aquí tienes su email, contáctalo
  directement" por un CTA que lleve a una página propia donde el prestador hace
  **Disponible / Proposer une autre date / Non**. La plataforma queda en medio:
  ID único de lead, estados (envoyé/vu/répondu/confirmé), página de respuesta del
  prestador y vista de seguimiento del viajero. Desbloquea la métrica de conversión
  vendible al prestador y la monetización (pay-per-lead / pay-per-réservation). Toca
  BD (nueva persistencia de leads/estados), email, controladores y vistas → architect.
- **TASK-022** (conversión + viralidad) — Plan guardable/compartible: URL única por
  itinerario (token opaco tipo el de `vacation_pdf`, `CDM-YYYY-XXXXXXXX`) + "guardar/
  partager mon plan". Habilita re-engagement (nudge a las 48 h) y planificación en
  pareja. Hoy el plan solo vive en el PDF/email, no se puede volver a él.
- **TASK-023** (conversión, UX) — Respuesta consolidada al viajero: en vez de que 12
  negocios le escriban por separado, la plataforma le da UNA actualización
  ("3 de 5 paradas confirmadas para el 1–4 sept"). Depende de TASK-021. Reduce carga
  mental y drop-off del viajero.
- **PRD-008** — Poner el planificador al frente. Evaluar que la home lleve al
  planificador como acción principal (no el buscador tipo directorio, que es el plan B
  para quien ya sabe qué quiere). Verificar el cambio en navegador.
- **TASK-024** — Contenido real en las páginas del planner (extiende TASK-005): footer
  con `+33 5 00 00 00 00`, `bonjour@canaldumidi.local` y el texto placeholder
  "Landing inspirée de votre capture, prête à servir de base pour un site touristique."
  Sustituir por datos reales antes de exponerlo.

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

### TASK-045 — accueil-2026: intención « carte / plan / tracé » — DESPLEGADO EN PRIVADO ✅ — 2026-10-02

- Title « Canal du Midi : carte, tracé, bateaux et vélos | L'Officiel » (59), meta 153 car., H1 « Canal du Midi : carte
  et tracé / de Toulouse à la Méditerranée » (2 líneas a 1440, no tapa las cifras), `#etapes` → « Quel est le tracé du
  Canal du Midi ? » + CTA carte interactive / plan (#plan), FAQ n.º 6 « Où trouver une carte détaillée et gratuite… »,
  JSON-LD `Map` + `hasMap`. Página 18500 sigue PRIVADA. Commits dec61a1, f561d01 (sin push).
- Pipeline: security ✅ sin hallazgos; product ✅ (2026-10-02). Visual 1440/390/320 OK (sesión principal).
- **Pendiente al publicar (TASK-028/029b):** regenerar `wp-plugin/llms-full.txt` (FAQ n.º 6 aún con la pregunta
  antigua; `build-llms-full.php` necesita el .md de categorías como argumento, si no borra la lista → PRD-013) y
  subirlo a httpdocs. Opcional: enlace a la carte en la respuesta de la FAQ n.º 6; check `</script` en smoke-seo.

### TASK-036 — Carte: mapa bajo demanda en ≤1180 px — DESPLEGADO EN PRIVADO ✅ — 2026-10-01

- `search-map.js`: `ensureMap()` crea el mapa al abrir la vista Mapa (o al pasar a ≥1181 px); antes de eso
  `setSearchMapResults` (IA) solo guarda los resultados. Escritorio sin cambios.
- **Lighthouse móvil:** 63 → **69 / 66** (TBT 320 → 230–300 ms, hilo principal 4,5 → 3,4 s, arranque de Maps
  745 → 275 ms); 0 teselas pedidas en móvil. Escritorio 83, mapa con clusters (Chrome limpio). Una tercera pasada
  móvil dio 44 con todo el JS (también el de terceros) al doble: ruido de CPU local.
- **Lección:** en la pestaña del Chrome de Claude, Google Maps puede no pintar (tampoco la /explorer/ del tema);
  verificar el mapa con un Chrome limpio (captura final de Lighthouse) antes de buscar un bug.


### TASK-035 — Carte: LCP sin esperar a Google Maps — DESPLEGADO EN PRIVADO ✅ — 2026-10-01

- Diagnóstico (Lighthouse local, la API de PageSpeed sin cuota): el LCP era la imagen de la 1.ª tarjeta, que
  no estaba en el HTML (`data-src` puesto por JS tras el Maps síncrono del tema) y la página entera quedaba oculta
  tras el skeleton hasta el `idle` del mapa. El HTML (1,2 MB) pesa 130 KB en gzip: no era el problema.
- `src`/`srcset` en el HTML, `fetchpriority="high"` en la 1.ª, las 6 primeras sin fundido
  (`CANAL_CARTE_EAGER_IMAGES`), el resto `loading="lazy"` nativo; se revela al cargar las 6 primeras (respaldo
  5 s), el mapa aparece después en su panel con skeleton (shimmer en `#explore-map`, Maps con
  `backgroundColor: 'transparent'`, se apaga en `tilesloaded`); clusterer y JS de la carte en `defer`, `skeleton-controler.js` sin
  dependencias.
- **Lighthouse móvil:** 48 → **63** (FCP 6,8 → 2,6 s, LCP 13,4 → 7,1 s, TBT 440 → 320 ms); escritorio **83**
  (LCP 2,7 s). Verificado con sesión en escritorio: lista, imágenes diferidas, mapa con clusters, filtro `?q=`.


### TASK-034 — Home y carte 2026 sin CSS/JS del tema — DESPLEGADO EN PRIVADO ✅ — 2026-10-01

- Mismo método que TASK-033: `assets/carte-theme.css` y `assets/home-theme.css` (subconjunto extraído con
  `build/extract-theme-css.js`), `CANAL_THEME_UNUSED_ASSETS` (tema + Stripe + Roboto de Elementor; la carte
  conserva Google Maps, ficha y home no), `CANAL_THEME_FIX_CSS` común (pie estático, sin el cargador del tema,
  barra de admin oculta < 1200 px), `canal_fiche_lighten_head` en las tres páginas. En la home además fuera
  WooCommerce, CF7 (+ PayPal), Elementor, TablePress, medidor de contraseñas (`canal_carte_dequeue_unused`).
- **PageSpeed (laboratorio, páginas publicadas unos minutos y vueltas a private):** home móvil 43 → **78**,
  escritorio 55 → **92** (TBT 1000 → 140 ms); carte móvil 39 → 36, escritorio 49 → 58 (su cuello de botella es
  propio → TASK-035).
- **Verificado con sesión:** carte (búsqueda, IA, filtros, mapa, móvil) y home (buscador → carte, modal IA, móvil).
  Newsletter no enviada (alta real).
- **Lección:** el cargador a pantalla completa del tema lo quitaba frontend.js; sin él tapaba la home (solo con
  sesión). Al quitar el JS del tema, revisar en navegador real con captura, no solo posiciones en Playwright.

### TASK-033 — Ficha 2026: diseño móvil, auditoría SEO y aligerado del tema — DESPLEGADO EN PRIVADO ✅ — 2026-09-30

- **Diseño móvil (≤640 px, maqueta `maison-rassier-mockup.html`):** hero foto 300 px + tarjeta de identidad
  que lo solapa (chips, H1, dirección), CTA Appeler/Itinéraire + iconos, secciones blancas a sangre, « Lire la
  suite », categorías en 2 columnas, galería en mosaico con « +N », mapa compacto, « Autour » en carrusel,
  FAQ en bloques, coordonnées en lista. Barra fija abajo (Appeler + iconos) que solo aparece cuando los CTA de
  arriba salen de pantalla (IntersectionObserver) y deja 76 px a la derecha para el botón del CMP. Paleta y
  tipografía del sitio (violeta/Sora), no el azul de la maqueta. Escritorio sin cambios. Sin carrusel del hero
  en móvil.
- **Auditoría seo-geo:** schema « monumento antes que comercio » (la abadía salía `BookStore`), sin `email`
  en `TouristAttraction`/`Museum` (no existe en Place), `streetAddress` sin commune/país, `<title>` sin repetir
  la commune (`canal_fiche_seo_title_text`), contraste AA de « Fiche éditée par… » (#64748b).
- **Tema fuera de la ficha:** `CANAL_FICHE_UNUSED_ASSETS` quita el CSS (vendor/frontend/icons/FA/select2/
  dynamic-styles…) y el JS (jQuery, jquery-ui, moment, select2, vendor.js, frontend.js) del tema; lo que la
  ficha usa del CSS del tema (~3 % de las reglas, 32 KB) va en `assets/fiche-theme.css`, generado con
  `build/extract-theme-css.js` (regenerar si cambia el tema/pie). `canal_fiche_lighten_head` quita del
  `<head>` fijo del tema reCAPTCHA (~830 KB), SDK de Facebook y style-pub.css, y carga Google Fonts sin
  bloquear. Pie en flujo normal (`position:static`: sin el JS del tema nadie reserva su hueco) y barra de admin
  oculta bajo 1200 px como hacía el tema. `aria-label` en los enlaces-icono del menú (título = shortcode
  `[27-icon …]`) en home, carte y ficha.
- **PageSpeed (laboratorio, ficha abierta unos minutos y cerrada):** móvil 69 → **87** (LCP 5,6 → 3,8 s,
  TBT 240 → 80 ms), escritorio 84 → **98**, accesibilidad 93 → 97, navegación agéntica 3/3.
- **Límites:** `fiche-theme.css` se extrajo de UNA ficha (una con vídeo podría necesitar alguna regla más);
  el CMP y su botón flotante son de terceros.

### TASK-032 — Navbar nuevo en home, carte y ficha 2026 — DESPLEGADO EN PRIVADO ✅ — 2026-09-30

- **Qué:** la cabecera del tema my-listing se sustituye, SOLO en nuestras 3 páginas, por una propia con el
  estilo de la app local + mega-menú. `includes/header.php`: filtro del tema `mylisting/header-config`
  (prioridad **99**: la integración Elementor del tema lo usa a 10 y vuelve a poner `show=true`) y marcado en
  `mylisting/body/start`. `assets/header.css` reescrito. Borrado `canal_home_carte_menu_js()` y el
  `body_class` de la ficha; carte y `fiche.js` miden `.cdm-header`. Nueva constante `CANAL_HOME_PATH`
  (`/accueil-2026/` → `/` al publicar).
- **Estructura (investigación NN/g/Baymard/W3C + vistas GA4 oct 2025–sep 2026):** por intención y modo de
  viaje, ordenada por frecuencia: En bateau · Vélo & balades · Découvrir · Se loger · Manger & Boire ·
  Préparer + accesos directos « Distances » (calcul de distance = nº 1 del sitio, 14.136 vistas) y « Carte »,
  CTA « Planifier mon voyage » (→ home `#plan`) e icono de cuenta. 87 URLs únicas del menú WP « Principale »
  (16) + páginas muy vistas que no estaban (péniches à vendre, /categorie/nautique/, /categorie/velo/…),
  todas 200. Categorías → páginas actuales `/categorie/…` (decisión del usuario); « Voir sur la carte » →
  `/explorer-2026/?type=<slug>`. Asociaciones: un solo enlace.
- **UX:** patrón *disclosure* del W3C (botón `aria-expanded`, clic, no hover; Escape devuelve el foco);
  hamburguesa + acordeones ≤1180 px; bajo 1200 px la cabecera va a `top:0` (el tema oculta `#wpadminbar`).
- **Verificado en navegador** (1440 px y 390 px): home, carte, ficha, paneles, filtros de la carte.
- **Ajustes de diseño (usuario, 30/09):** barra máx. 1360 px; logo + paneles a la izquierda y accesos directos a
  la derecha (se probó el menú centrado con subgrid y se descartó: hueco grande tras el logo); 100 px logo→menú
  y « Planifier mon voyage » con icono desde 1440 px; 32 px en 1181–1439; « Planifier » en 1181–1365; Distances
  y Carte solo icono en 1181–1279. Indicador violeta bajo el panel abierto; sombra al hacer scroll.
- **Límites conocidos:** el menú está en un array PHP (si cambia el menú de WP, actualizar a mano); no se
  marca la sección actual.

### TASK-030 — Fiche 2026 (`/fiche-2026/<slug>/`) — DESPLEGADA EN PRIVADO ✅ — 2026-09-29
- Spec `docs/superpowers/specs/2026-09-29-wp-fiche-2026-design.md`, plan
  `docs/superpowers/plans/2026-09-29-wp-fiche-2026.md`. Rama `feat/wp-fiche-2026`.
- Regla de reescritura propia (opción `canal_fiche_rewrite`); sin sesión `read_private_pages` → 404
  del tema; `DONOTCACHEPAGE`; `/fiche/<slug>/` actuales sin cambios.
- Port de `service_detail.php` sin reserva/avis/equipamientos: hero con galería, barra de acciones
  (sticky bajo la cabecera del tema, estática en móvil), présentation, catégories (→ carte
  filtrada), vídeo (allowlist YouTube/Vimeo, youtube-nocookie), galería + lightbox, mapa +
  « Autour de ce lieu » (6 fichas más cercanas), coordonnées.
- Datos: imágenes forzadas a https; `tel:` limpio (« Tél Atelier : 07… »); `_job_location` con
  coordenadas (écluses) → « Commune — Canal du Midi »; `_work_hours` vacío en prod → sin horarios.
- SEO: título « X à Commune — Canal du Midi », meta, OG con la portada, canonical, JSON-LD
  LocalBusiness/TouristAttraction + BreadcrumbList, `noindex,nofollow`.
- Home y carte enlazan a `CANAL_FICHE_PATH`. Tests: `test-fiche.php` (en `remote.sh test`),
  `smoke-fiche.php`, `smoke-carte-data.php`.
- **Auditoría seo-geo (30/09): 66/100 → ~82 estimado.** Hecho: precarga del hero + galería y miniaturas
  en tamaño reducido con srcset y ancho/alto (imágenes 1,67 MB → 178 KB); WebPage con `dateModified`
  y editor + línea visible « Fiche éditée par … mise à jour le … »; FAQ de 3 preguntas con datos reales
  (dónde / contacto / alrededores) + FAQPage + speakable; nombres en MAYÚSCULAS mostrados legibles
  (`canal_fiche_display_title`, BD intacta); H2 « À propos de … »; enlace/cabecera llms.txt.
  Menores hechos (30/09): tipo schema por categoría con prioridad (Hotel, Campground, BedAndBreakfast,
  LodgingBusiness, Hostel, Restaurant, BarOrPub, Bakery, Museum, TouristInformationCenter,
  TouristAttraction, Store…); `telephone` E.164 (+33…); `containedInPlace`/`about` = Canal du Midi
  (Wikidata Q202494, UNESCO); enlaces visibles UNESCO y VNF en « Localisation ».
- **Prueba pública (30/09, 10:18–10:32 UTC, opción `canal_fiche_public`, ya borrada):** PageSpeed móvil
  ficha 2026 44 (LCP 7,1 s, CLS 0) vs ficha actual 28 (LCP 21,5 s, CLS 0,197); escritorio 64 (LCP 1,6 s).
  Validador schema.org 0 errores; Rich Results: migas, LocalBusiness (solo falta priceRange, facultativo) y
  Organisation válidos. **Hallazgo:** `header.php` del tema imprime `<div id="fb-root">` antes de
  `wp_head()` → en TODO el sitio title/meta/canonical/JSON-LD quedan en el `<body>`. Corregido solo en
  nuestras páginas (`includes/head-fix.php`, buffer que mueve el div tras `<body>`).
  Pendiente rendimiento móvil (tema/terceros): CSS/JS bloqueantes del tema (~4,4 s), reCAPTCHA, Stripe,
  SDK Facebook, GTM, Google Maps cargado aunque el mapa esté abajo.
- **Optimización móvil (30/09):** en la ficha fuera Stripe (264 KB); Google Maps (~400 KB) en diferido desde
  fiche.js al acercarse el mapa (misma URL/clave que el tema, leída en `wp_print_footer_scripts`); Google
  Fonts y Bootstrap Icons sin bloquear (media=print + onload + noscript). **No quitar** moment, select2 ni
  jquery-ui: el `frontend.js` del tema falla sin ellos y la cabecera (`hide-until-load`) queda invisible
  (comprobado). **Medido (30/09, 12:19–12:24 UTC abierta):** móvil 48 y 69 en dos pasadas (antes 44),
  LCP 5,1/5,0 s (antes 7,1 s), FCP ~4,1 s; escritorio 84 (antes 64), LCP 1,4 s, TBT 220 ms (antes 620).
  El resto del TBT/FCP móvil es del tema y terceros (scripts en línea, consentimiento, GTM, vendor.js).

### BUG — IA « momentanément indisponible » (home y carte) — CORREGIDO ✅ — 2026-09-29
- **Causa:** incidente de la API de Anthropic: `503 overloaded_error « Grammar compilation is
  temporarily unavailable »` — cae el servicio que aplica el `json_schema` de la respuesta; las
  peticiones sin schema funcionan. No era un fallo nuestro.
- **Arreglo (resiliencia):** `canal_home_is_grammar_outage()` + `canal_home_request_without_schema()`
  (`ai-core.php`, con tests): ante ese 503 se reintenta una vez sin schema (formato pedido en las
  instrucciones; `canal_home_parse_response` valida igual) y se recuerda la caída 10 min
  (transient `canal_home_ai_no_schema`) para no pagar los 10-17 s del 503 en cada petición.
  Medido: 1ª petición 29 s, siguientes 8,7 s, 5 resultados válidos. Log: `IA: json_schema no disponible`.

### TASK-029 — « Carte interactive » en el WordPress de producción — DESPLEGADA EN PRIVADO ✅ — 2026-09-29
- **Qué:** copia fiel de `/search` local como página nueva **privada** `/explorer-2026/` (ID **18502**,
  plantilla `canal-home/template-carte.php`). `/explorer/` intacta. Spec y plan en
  `docs/superpowers/specs|plans/2026-09-29-carte-interactive*`.
- **Archivos (plugin `canal-home`):** `includes/carte-filter.php` (filtro puro: q, type[]
  padre⇒hijas, location, lat/lng redondeados, distancia; alias de `/explorer/`),
  `includes/carte-data.php` (254 fichas en transient `canal_carte_listings` 12 h, invalidación
  en save/delete de fichas y edición de categorías), `template-carte.php`, `assets/carte/*.js`
  (copias de local marcadas `WP:`), `assets/carte.css` (generado: `styles.css` + `search.css` +
  `build/carte-extra.css` bajo `.cdm-carte`), `canal-home.php` (registro + assets),
  `data.php` (`slug` en `canal_home_card`). Tests: `tests/test-carte-filter.php` (en
  `remote.sh test`), `tests/smoke-carte-data.php` (`remote.sh run`, con el plugin activo).
- **Decisiones:** filtros por GET y filtrado en PHP (como local); Google Maps lo carga el tema
  (no se duplica); solo 6 imágenes iniciales, resto lazy; reveal forzado a los 5 s; cabecera
  fija del tema compensada por JS (`--cdm-header-h` + padding); IA = endpoint de la home, los
  resultados se resuelven por `slug` contra una copia de todas las fichas.
- **Verificado en Chrome (admin):** escritorio/1024/390 iguales a local; filtros, alias,
  categorías, « Autour de moi » (denegado y OK), IA 2 consultas seguidas, popup con carrusel,
  vista mapa móvil, 0 errores de consola. No probado: Maps bloqueado (camino de código revisado).
- **Ajustes del usuario (29/09, tarde):** pestaña « Catégories » eliminada; imagen de tarjeta a
  200 px fijos (`carte-extra.css`); el cargador del tema (`.loader-bg.main-loader`) se oculta
  solo en esta plantilla porque tapaba el skeleton de local hasta `window.load`.
- **SEO (auditoría seo-geo 29/09, 32/100 → fixes):** `includes/seo-carte.php` (título 52 car., meta
  159 car., Open Graph/Twitter, JSON-LD CollectionPage + BreadcrumbList + ItemList de 30 fichas
  visibles con numberOfItems total, `noindex,follow` si hay filtros — `canal_carte_has_filters`,
  con tests); H1 + introducción en la columna de resultados; `seo.php` comparte
  `canal_home_seo_social()`/`canal_home_seo_jsonld()`. Canonical: la emite WordPress al publicar.
  2ª pasada seo-geo (60/100 → ~80): `dateModified` (ficha modificada más reciente), `speakable`
  (H1 + intro), línea visible « Guide édité par… mis à jour le… », cabecera `Link` a llms.txt, FAQ de 4
  preguntas construida con los datos reales (`includes/carte-faq.php`, con tests) + `FAQPage` solo
  si se ve, enlaces a UNESCO y VNF, `<h2>` del modal con texto y sin `src=""`. Grafía de communes
  corregida al mostrar (`CANAL_HOME_CITY_FIX`: Béziers, Montréal…; la taxonomía region no se toca).
  Pendiente: orden del HTML (filtros antes que el H1), `sameAs` de la organización (solo FB/IG).
- **Peso (29/09):** el HTML pesa 1,1 MB pero 123 KB con gzip; el peso real era el del tema y las
  imágenes. Hecho: (1) portadas en `medium_large` 768 px + `srcset` (`canal_carte_resized_images`,
  una sola consulta; 209/253 con versión reducida); (2) `content-visibility: auto` en las tarjetas
  (5.138 nodos en el DOM); (3) fuera Elementor, WooCommerce, CF7, TablePress y PayPal SOLO en esta
  plantilla (`CANAL_CARTE_UNUSED_ASSETS`; se conservan Font Awesome y select2, que usa el tema).
  Medido: 107 → 79 peticiones, ~4,2 → ~3,6 MB descomprimidos, imágenes iniciales 811 → 574 KB.
  Fase 2 posible: cargar Google Maps solo al abrir el mapa en móvil (~830 KB).
- **Rollback:** `wp-plugin/remote.sh wp post delete 18502 --force` y
  `wp-plugin/remote.sh wp transient delete canal_carte_listings`.
- **Diferido (menor):** sin invalidación en `edited_region`; sin quitar un solo filtro (solo
  « Effacer »); fichas sin portada muestran el icono de local; peso de página (ver TASK-029b).

- **TASK-027 — Home « Accueil 2026 » en el WordPress de producción (plugin
  `canal-home`) ✅ desplegada en PRIVADO (2026-09-28/29)**
  - Spec `docs/superpowers/specs/2026-09-28-home-wordpress-prod-design.md`, plan
    `docs/superpowers/plans/2026-09-28-home-wordpress-prod.md`, rama `feat/wp-home-accueil-2026`.
  - Plugin nuevo (fuente en `wp-plugin/`, despliegue `wp-plugin/remote.sh deploy`):
    plantilla de página con las 7 secciones (textos validados), datos reales de WP,
    asistente IA `POST /wp-json/canal-home/v1/ai` (claude-opus-5, catálogo de 254 fichas
    con prompt caching ~34k tokens → ~0,025 $/búsqueda con caché caliente, límite
    10/10 min por IP + 300/día; el tope diario es un contador atómico en `wp_options`
    `canal_home_ai_daily_YYYYMMDD` — fail-closed, independiente de Redis). Clave en `/var/www/vhosts/plan-canal-du-midi.com/canal-ai-config.php`
    (fuera de httpdocs, 640). Página ID **18500**, privada; portada intacta (15269).
  - Tests: 42 de `ai-core` (PHP 7.4 del servidor) + smokes de datos, render y endpoint
    (API simulada) + verificación en navegador 1440/375 px.
  - Hallazgos de compatibilidad con el tema corregidos: `html{font-size:10px}` (rem→px en
    build), clearfix Bootstrap en `.container`, color de h1–h6, z-index de la cabecera
    (500), caché de assets (versión = filemtime), y el explorador filtra por
    `search_location` (texto geocodificado, 10 km), no por la taxonomía `region`.
  - Parallax en la banda inmersiva (petición del usuario, 2026-09-29): la foto se desplaza
    de 0 % a 100 % de `background-position` mientras la banda cruza la pantalla; desactivado
    con `prefers-reduced-motion`.
  - « Nos atouts » con iconos Bootstrap Icons (shop / map / compass) y color de acento por
    tarjeta (violeta / agua / terracota), filete superior y hover; tarjetas « En bateau » /
    « À vélo » con icono de marca de agua (water / bicycle).
  - Bloque del plan idéntico al local (petición del usuario, 2026-09-29): libro en 3D
    (`assets/plan-canal-du-midi-2026.jpg`, JPEG 960 px desde el PNG local) y formulario
    « Recevoir le plan par e-mail » real: POST `admin-post.php?action=canal_home_plan` →
    `wp_mail()` (sale por Easy WP SMTP, `noreply@plan-canal-du-midi.com`) con el HTML fijo
    generado desde `EmailTemplates::planByEmail()` local (`emails/plan.html`; pie corregido
    a www.plan-canal-du-midi.com). No guarda e-mails. Honeypot `website`, 3 envíos/h por IP,
    tope 200/día (contador atómico `canal_home_plan_daily_YYYYMMDD` en `wp_options`).
    Envío real de prueba a onavarro@francedit.com → `?plan=ok`.
  - Tarjetas « Croisières, balades et excursions » (2026-09-29): foto como `background-image`
    (4:3, `cover`, nunca deformada), categoría en etiqueta / nombre / ciudad con icono, alineadas.
    Categorías explícitas sin hijas (`croisiere-bateau`, `location-bateau`, `peniche`,
    `excursions`, `location-de-velo`, `location-de-canoe-kayak`): antes `nautique` arrastraba
    `ecluses` (72) y `ports` (21) → 30 de 40 tarjetas eran esclusas. `canal_home_css_url()`
    codifica comillas/paréntesis en los `background-image` en línea (cierra el minor de
    inyección CSS de las tarjetas de destinos).
  - Revisión móvil con Claude in Chrome + simulador iPhone del usuario (390 px, 2026-09-29) y
    6 arreglos verificados con sonda a 390 px y regresión a 1440 px: campos a 16px en ≤820px
    (Safari iOS hacía zoom al enfocar), campos ≥44px de alto, el teclado se cierra al enviar a
    la IA y la vista baja a los resultados, destinos y séjours en carrusel horizontal con
    scroll-snap de borde a borde en ≤560px, bloque del plan sin caja fija en móvil (estilos en
    línea movidos a CSS; el flex-basis de 480px se volvía 480px de alto en columna), etiquetas
    ≥12px. Página en móvil: 9 845 → 7 095 px.
  - Hero en móvil (≤560px): párrafo corto (« Hébergements, bateaux, vélos et visites : les
    meilleures adresses du canal. », 75 car. frente a 161), eyebrow 12px, cifras 2×2 con
    etiquetas 13px sin mayúsculas en una línea. `.cdm-home { overflow-x: clip }`: las
    animaciones de entrada laterales dejaban la página arrastrable hacia los lados (422/390)
    hasta llegar a « Expériences ». Libro del plan recortado al contenido (sin ~25 % de blanco).
  - Cabecera del tema restilizada SOLO en esta página (2026-09-29): `assets/header.css`
    (escrito a mano, fuera del build) bajo `body.page-template-canal-home` — fondo blanco
    translúcido con blur y sombra, menú y submenús en Manrope con hover violeta, « Carte
    interactive » en píldora violeta, en escritorio (≥1201px, corte del tema) menú compacto y
    zona de usuario reducida al avatar (el nombre pisaba « Manger & Boire »), botón del panel
    móvil con margen lateral. Hueco sobre el hero 120 → 12px. La home actual y el resto del
    sitio conservan la cabecera original (verificado). Al rediseñar /carte y las fichas, el
    mismo CSS puede extenderse a todo el sitio desde el plugin.
  - SEO/AEO (auditoría con agente 2026-09-29: SEO 5,5/10 · AEO 3,5/10 → implementadas todas
    las acciones, solo añadiendo): `includes/seo.php` — `<title>` propio, meta description,
    Open Graph + Twitter, JSON-LD `@graph` (Organization con Azur Communications y Facebook,
    WebSite + SearchAction, WebPage, TouristDestination, ItemList de séjours, FAQPage) y
    preconnect a Google Fonts; `includes/content.php` — FAQ (6 Q/R) y étapes (8 con PK de la
    calculadora del sitio), fuente única para la plantilla y el schema; secciones nuevas
    « Les étapes du canal » y « Questions fréquentes »; frase E-E-A-T (Azur Communications);
    categorías curadas (6) y 8 séjours deterministas con rotación diaria (antes al azar); hero
    con fetchpriority/dimensiones/srcset y sin animación de entrada; péniche 2 MB → 190 KB
    (copia en el plugin); títulos de modales fuera del esquema de encabezados; `llms.txt` nuevo
    en la raíz (desplegado por `remote.sh deploy`). Test nuevo `tests/smoke-seo.php`.
  - **Pendiente del usuario / al publicar:** enviar `wp-sitemap.xml` en Search Console y Bing
    Webmaster; verificar canonical `/`, redirección de `/accueil-2026/`, ausencia de `noindex`,
    Rich Results Test y PageSpeed móvil en anónimo.
  - Auditoría con la skill `seo-geo` (--audit-only, 2026-09-29): 58/100 → correcciones aplicadas:
    título 57 car., og:image 1200×630 propia, `sameAs` Instagram + Wikidata Q202494 + UNESCO 770,
    `knowsAbout`, `dateModified`, `speakable`, cabecera `Link: rel="llms-txt"` + `<link>`,
    fuentes VNF/UNESCO en la FAQ, 2 H2 en forma de pregunta, `llms-full.txt` (generado desde
    content.php por `build/build-llms-full.php`). Con autorización del usuario, cambios de todo el
    sitio desde el plugin: sitemaps sin autores ni `elementor_library`, sin `wp_generator`, sin
    `X-Powered-By: PHP`, HSTS (6 meses, sin subdominios) y `nosniff`; `robots.txt` sustituido
    (original guardado en `/var/www/vhosts/plan-canal-du-midi.com/robots.txt.orig`): bots de
    búsqueda IA permitidos, entrenamiento no (`Content-Signal: ai-train=no`), `Sitemap:`.
    `.htaccess` (autorizado 2026-09-29, copia en `/var/www/vhosts/plan-canal-du-midi.com/htaccess.bak-2026-09-29`):
    la regla de bloqueo 403 sale del bloque « BEGIN WordPress » (WP podía borrarla al regenerarlo) y
    `developers.facebook` → `meta-externalagent` (solo el robot de entrenamiento de Meta; quedan
    permitidos meta-externalfetcher y facebookexternalhit). Verificado: GPTBot/ClaudeBot/Amazonbot/
    ImagesiftBot/meta-externalagent → 403; buscadores IA, Googlebot y aperçus Facebook → 200.
  - Pendiente del usuario: datos del editor para E-E-A-T (año de inicio del plan, tirada,
    responsable) — no se inventan.

- **BUG-018 / PRD-010 — Error de validación del modal Photos invisible
  ✅ corregido y verificado (2026-09-03)**
  - **Síntoma:** `.ficha-modal-error` vive en la cabecera del formulario y los
    dropzones al final; con el modal desplazado (lo normal) el mensaje quedaba
    368 px por encima del área visible a 1440×800 → el prestador elegía un PDF o
    una foto de 7 Mo y no veía nada.
  - **Fix:** helper `showModalError(errorBox, message)` en `backoffice-edit.js`
    (textContent + `hidden = false` + `scrollIntoView`), usado en los 4 puntos de
    error (validación de dropzone, borrado fallido/red, submit fallido/red).
  - **Corrección adicional de product en la re-verificación:** la primera versión
    usaba `block: 'nearest'`, que deja el mensaje justo en el borde superior de la
    zona desplazable — **debajo del `<h2>Photos</h2> collant**, que va de y=60 a
    y=163. Medido en navegador: `elementFromPoint()` en el centro del mensaje
    devolvía el `H2`, o sea seguía invisible pese a `hidden=false` y a un rect
    "dentro" del formulario. Cambiado a `block: 'center'`.
  - **Verificado en navegador (1440×800, modal desplazado al máximo):** elegir
    `doc.pdf` → mensaje « doc.pdf » : format non pris en charge (JPG, PNG ou
    WEBP). visible sin scroll manual (rect 169→215, `elementFromPoint` devuelve
    la propia caja), input vaciado, **0 peticiones de red**, 0 errores de consola.

- **BUG-019 / PRD-011 — Estado sucio tras borrar la última foto
  ✅ corregido y verificado (2026-09-03)**
  - **Fix:** `setHeroPreview()` y `renderPhotos()` limpian `backgroundImage` a `''`
    cuando no hay portada (antes el `if (imageUrl)` hacía que el vaciado fuese un
    no-op y quedaba pegada la foto recién borrada), y `.ficha-cover-picker` pinta
    "Aucune photo pour le moment." cuando `photos.length === 0`.
  - **Verificado en navegador:** borradas las 8 fotos una a una desde el modal →
    `bo-hero-preview` y `ficha-hero` con `backgroundImage` vacío (bloque neutro,
    sin la foto borrada), picker con el mensaje, galería con el suyo, 0 errores de
    consola. La ficha pública sigue degradando a la imagen genérica del canal.
  - **Nit cosmético pendiente (no bloqueante):** el `<p>` del picker hereda las
    columnas del grid y el mensaje se parte en 3 líneas en una columna estrecha;
    un `grid-column: 1 / -1` en `backoffice.css` lo dejaría en una línea.

- **TASK-026 — Modal "Photos" del backoffice: borrar, reordenar y validar
  ✅ CERRADA (2026-09-03)**
  - **Pipeline:** architect ✅ → coder ✅ → security ⚠️ (0 críticos, SEC-015 no
    bloqueante) → product ⚠️ → **fix de BUG-018/BUG-019 + re-verificación en
    navegador → ✅ limpio**. Único seguimiento vivo: BUG-020 (🟡, decisión de UX).
    Spec: `docs/superpowers/specs/2026-09-03-backoffice-photos-modal-design.md`.
  - **Qué se implementó:** (1) borrado de fotos (portada y galería) desde
    `#modal-photos` con botón "×", `confirm()` y AJAX (`case 'delete_photo'` en
    `handleAjaxBlockUpdate`), con reasignación automática de portada; (2)
    reordenado de la galería por drag & drop nativo sobre `.ficha-cover-picker`,
    persistido como `gallery_order[]` al guardar; (3) validación cliente 5 Mo +
    MIME y estado "Envoi en cours…" con spinner y botón deshabilitado.
    Archivos: `MySQLServiceRepository.php`, `ListingUploader.php`,
    `BackofficeController.php`, `edit_listing.php`, `backoffice-edit.js`,
    `backoffice.css`.
  - **Verificación en navegador (Playwright, owner de prueba sobre la fiche 226,
    datos restaurados byte a byte al terminar):** borrado de foto de galería →
    desaparece sin recargar y sigue ausente tras F5 (BD confirmada); "Annuler" en
    el `confirm()` no dispara ninguna petición; borrado de la portada activa →
    se reasigna sola a la siguiente y el hero del modal + la ficha pública la
    reflejan, sin hueco gris; drag de la 6ª vignette a la 1ª + "Enregistrer" +
    F5 → el nuevo orden persiste en BD, en el picker y en el carrusel público
    (comparado antes/después, no asumido); archivo de 7 Mo y `doc.pdf` →
    rechazados en cliente con texto francés correcto y **cero peticiones de red**;
    durante el envío el botón muestra "Envoi en cours…" + spinner y queda
    deshabilitado (capturado en vuelo ralentizando `fetch`), luego vuelve a
    "Enregistrer" y sale "Modifications enregistrées."; **0 errores de consola**
    en toda la sesión (solo warnings preexistentes de Google Maps); todos los
    textos nuevos en francés y coherentes con el resto del backoffice.
  - **Seguimientos:** BUG-018/PRD-010 y BUG-019/PRD-011 → **corregidos y
    re-verificados en navegador el mismo día** (ver sus entradas arriba en 🟢).
    Queda abierto solo **BUG-020/PRD-012** en 🟡 (el drag & drop es indescubrible
    y no funciona en táctil — decisión de UX, no defecto de implementación).

- **BUG-013 — Galería del backoffice sin normalizar, fotos subidas daban 404
  ✅ corregido (2026-09-02)**
  - **Origen:** encontrada en Task 12 (verificación end-to-end del backoffice de
    fichas, 2026-09-02). Las fotos de galería subidas desde el backoffice daban
    **404** al mostrarse tanto en `/backoffice?id=X` (preview de edición) como en
    la página pública `/fr/fiche/{slug}` (thumbnail bajo la portada).
  - **Causa raíz:** `MySQLServiceRepository::rowToService()` normaliza `cover` con
    `normalizeMediaUrl()` (antepone `BASE_URL` si la ruta no es `http(s)://`), pero
    el array `$gallery` (línea ~462-468, decodificado directo del JSON de BD) se
    usaba TAL CUAL, sin pasar por esa misma función — con lo que las rutas nuevas
    (`public/uploads/listings/{id}/xxx.jpg`, formato del `ListingUploader` de
    Task 9) quedaban relativas. Bajo una URL con prefijo de idioma (`/fr/...`, el
    caso normal) el navegador resolvía `public/uploads/...` contra el path
    actual → pedía `/fr/public/uploads/...` → 404. Las fotos WordPress antiguas
    (URL absoluta `https://...`) no se veían afectadas, por eso no se detectó en
    fiches sin subidas nuevas.
  - **Fix:** en `rowToService()`, tras decodificar y filtrar `$gallery`, se mapea
    `normalizeMediaUrl()` sobre cada elemento (mismo patrón que ya usaba `cover`):
    `$gallery = array_map(fn($url) => $this->normalizeMediaUrl((string) $url), $gallery);`.
    Los otros dos usos de `gallery` en el archivo (`resolveGalleryFirst()`, usado
    por `getServicesNearPoi()`, y `getMediaFromDisk()`) ya normalizaban
    correctamente por elemento — no necesitaron cambios.
  - **Verificación:** (1) smoke check aislado (`assert`, fixture `draft` insertada
    y borrada por SQL directo) confirmó que una ruta relativa en el JSON de
    `gallery` sale de `findByIdForEdit()` con `BASE_URL` antepuesto y que una URL
    ya absoluta queda intacta. (2) Re-ejecución del repro real de Task 12 sobre la
    fiche id=1 (cuentas admin/owner throwaway creadas y borradas por SQL/flujo
    real, fiche respaldada y restaurada byte a byte): subida real de una foto de
    galería vía `curl` multipart autenticado como owner → el `<img src="...">`
    devuelto tanto por `/backoffice?saved=1` como por `/fr/fiche/
    la-marelle-chambres-dhotes` ahora es
    `http://localhost/canal_du_midi/public/uploads/listings/1/xxx.jpg` (antes
    relativo) → `curl -o /dev/null -w "%{http_code}"` sobre esa URL exacta →
    **200** (antes 404). Archivo de test, cuentas throwaway, categorías y fila de
    `listings` restaurados al estado previo tras la prueba.
  - Archivos: `src/Infrastructure/Persistence/MySQLServiceRepository.php`.
    Reporte completo: `.superpowers/sdd/task-12-report.md` (sección "Fix: BUG-013
    — gallery URL normalization").

- **Backoffice de fichas (owner/admin) — Tasks 1-12 ✅ FEATURE COMPLETA — verificación end-to-end (Task 12, 2026-09-02)**
  - **Qué es:** espacio `/backoffice` con login (email+password, CSRF, rate-limit
    5 intentos/IP/15 min), rol `owner` (edita solo su fiche, `?id=` ignorado) y rol
    `admin` (busca/lista fiches, crea cuentas owner, resetea passwords), edición de
    texto/contacto, subida de foto de portada + galería (validación MIME real vía
    `finfo`, no por extensión) y gestión de categorías. Plan:
    `docs/superpowers/plans/2026-09-02-backoffice-fichas.md`. Ledger de las 12 tareas:
    `.superpowers/sdd/progress.md` (Tasks 1-11, cada una con su propio review/commit) +
    este cierre (Task 12).
  - **Task 12 — verificación end-to-end de las 8 piezas juntas (checklist del spec),
    HTTP/curl real + Playwright, cuentas throwaway creadas/borradas por SQL (NUNCA se
    tocó `admin@canaldumidi.local`), sobre la fiche id=1 (sin owner previo,
    respaldada y restaurada byte a byte al terminar):**
    1. ✅ Admin (throwaway) crea cuenta owner para fiche 1 vía POST real a
       `/backoffice` (`action=create_owner`) → owner logueado ve/edita fiche 1
       incluso pidiendo `/backoffice?id=2` (título de fiche 2 NUNCA aparece).
    2. ✅ Editar descripción/ciudad → guardar (302 a `?saved=1`) → `/fr/fiche/
       la-marelle-chambres-dhotes` recargado muestra el texto nuevo y "Carcassonne".
    3. ✅ Subida de portada + 1 foto de galería (JPG real) → `cover`/`gallery` en BD
       actualizados, archivos físicos en `public/uploads/listings/1/`. **Hallazgo:**
       el thumbnail de galería NO se renderiza en el navegador (404) porque el array
       `gallery` se usa sin pasar por `normalizeMediaUrl()` (a diferencia de `cover`,
       que sí la usa) — ver BUG-013 más abajo en 🟡. La foto de portada sí se ve bien.
    4. ✅ Cambiar categorías (id 22 → id 10 "nautique") → `/fr/search?type=nautique`
       pasó de 16 a 17 resultados tras guardar.
    5. ✅ CSRF: POST a `/backoffice/login` sin `csrf` → `HTTP 403` + página
       "Accès refusé", 0 intento de login procesado.
    6. ✅ Rate-limit: 4 intentos fallidos → "Identifiants incorrects"; a partir del
       5º (incluso más estricto que el "6º" del checklist) → "Trop de tentatives.
       Réessayez dans 15 minutes.", con cookies nuevas en cada intento (bloqueo por
       IP en `login_attempts`, no por sesión).
    7. ✅ `.php` renombrado a `.jpg` (contenido real `<?php ... ?>`, MIME real
       detectado por `finfo`) → rechazado con "Formato de imagen no permitido
       (solo JPG, PNG o WEBP)", 0 archivo escrito a disco. Defensa adicional
       confirmada: `public/uploads/.htaccess` (`php_flag engine off` +
       `Require all denied` sobre `.php*`), aunque no llegó a ejercitarse porque el
       upload ya fue rechazado en capa de aplicación.
    8. ✅ Admin resetea password del owner (`action=reset_password`) → owner
       inicia sesión con la password nueva (`HTTP 302` a `/backoffice`).
  - **Consola del navegador (Playwright):** `/backoffice/login` → 0 errores.
    `/backoffice` como owner → 1 error (el 404 del thumbnail de galería, ver
    BUG-013). `/backoffice` como admin (listado) → 0 errores. `/backoffice?id=1`
    como admin → mismo 1 error de galería (reproducible, no es un fluke).
  - **Limitaciones conocidas, ya revisadas y aceptadas como no bloqueantes**
    (heredadas de los reviews de Tasks 9-10, documentadas aquí para no perderlas —
    ver también nuevo ítem 🟡 más abajo):
    1. Upload huérfano: si portada/galería se sube con éxito pero título/email
       fallan validación en la MISMA request, el archivo queda escrito en disco
       sin referencia en BD (Task 9).
    2. Fallo silencioso de upload a nivel PHP (ej. excede `upload_max_filesize`)
       en un slot de `gallery_files[]` da `tmp_name` vacío, indistinguible de "no
       elegí archivo" — sin mensaje de error para ese slot concreto (Task 9).
    3. Los campos de password en `admin_dashboard.php` (password temporal al crear
       cuenta y el de resetear) son `type="text"`, no `type="password"` —
       credenciales visibles en pantalla. Coincide con el código de referencia del
       plan (Task 10).
    4. Este entorno local necesitó `chmod 777 public/uploads/listings/` (Apache
       corre como `daemon`, el directorio se creó con dueño `imac:staff`) — fix de
       entorno, no de código; anotado por si un nuevo setup local lo repite (Task 9).
  - **Limpieza de artefactos de test confirmada por consulta a BD:** `SELECT COUNT(*)
    FROM users` → 1 (solo `admin@canaldumidi.local`); fiche 1 con `owner_user_id`
    NULL, `claimed=0`, `title`/`city`/`cover` restaurados al valor original;
    categorías de fiche 1 restauradas a `22,45,47`; `login_attempts` → 0 filas;
    `public/uploads/listings/` vacío (archivos huérfanos borrados vía script PHP
    temporal ejecutado por Apache/`daemon`, ya que los archivos subidos quedan con
    dueño `daemon` y el shell local no tiene permiso de borrado directo — mismo
    fix de entorno del punto 4).
  - Archivos: ninguno de código (verificación pura). Reporte completo:
    `.superpowers/sdd/task-12-report.md`.

- **TASK-019 — Fechas estructuradas y obligatorias en el planificateur IA ✅ PIPELINE COMPLETO — architect → coder → security ⚠️ → product ⚠️ listo con mejoras menores, verificado en navegador (2026-07-06)**
  - **Revisión product (2026-07-06):** ⚠️ listo con mejoras menores. Los 6 criterios
    de éxito del architect CUMPLIDOS. El objetivo de NEGOCIO (el corazón de la tarea)
    se cumple: las fechas son obligatorias (cliente + servidor autoritativo) y el lead
    al prestador ya NO puede salir "dates à confirmer" — sale con fechas firmes en el
    formato francés inequívoco "du vendredi 4 septembre 2026 au lundi 7 septembre 2026"
    (frDate: día-semana + día + mes en letra + año, sin ambigüedad día/mes). El viajero
    edita las fechas en un `<input type=date>` nativo (picker localizado, tampoco
    ambiguo). Verificado EN NAVEGADOR el flujo ES real (Playwright, PRD-002): prompt
    "Viajamos 4 días en pareja a principios de septiembre…" → plan generado (0 errores
    de consola), dates prerrellenadas 2026-09-07→2026-09-10 (approx), `min`=hoy, hint
    en español "Fechas estimadas a partir de su solicitud — ajústelas si es necesario.",
    submit con fechas vacías → bloqueado con "Indique sus fechas de llegada y salida.".
    Strings de fecha correctos e idiomáticos en fr/es/en (inspección + ES verificado en
    vivo; EN por cableado idéntico + inspección). La copy de confirmación-antes-de-
    -acción-irreversible existe y es clara ("Nous allons transmettre votre demande à
    chaque prestataire… Ils vous contacteront directement") + se reitera en la pantalla
    de confirmación → el usuario sabe que al enviar se contacta a los prestadores.
  - **Mejora menor / seguimiento abierto (no bloqueante):** PRD-009 / TASK-025 (🟡) —
    idiomas mezclados en el planner: TASK-019 localizó bien SUS strings (hint + errores
    de fecha), pero el resto de la página (labels, intro, botón, confirmación, y los
    OTROS errores JS del mismo formulario: nombre/email vacío → francés) sigue
    hardcodeado en francés. En `/es/` el mismo formulario da error de fecha en español
    y error de nombre en francés. Pre-existente, NO regresión de TASK-019. Registrado en
    LESSONS.md (PRD-009) / ERROR_LOG.md, elevado a 🟡 (TASK-025). Como el objetivo de
    negocio y los 6 criterios se cumplen, no bloquea el cierre.
  - **Revisión security (2026-07-06):** ⚠️ aprobado con observaciones. Verificado
    en NAVEGADOR real (Playwright, PRD-002), no solo curl:
    - Prompt "Nous partons 4 jours en couple début septembre…" → plan generado,
      paso 3 (`#f-checkin`/`#f-checkout`) prerrellenado `2026-09-04`→`2026-09-07`,
      `min` de ambos inputs correcto, `#date-hint` visible con el texto i18n
      "Dates estimées à partir de votre demande…" (criterios (a) y (b) del aviso
      del coder, CONFIRMADOS).
    - Submit con fecha borrada por JS → bloqueado client-side con mensaje i18n
      "Veuillez indiquer vos dates d'arrivée et de départ.", **0 requests** a
      `ai-plan-submit` (criterio (c) CONFIRMADO).
    - **GOTCHA de verificación (no es bug de producto):** el primer intento de
      render mostró los inputs vacíos y el hint oculto pese a que la respuesta de
      `ai-plan-generate` sí traía `date_debut`/`date_fin`/`date_precision=approx`
      correctos (confirmado inspeccionando el `response-body` real de la request).
      Causa: el Chrome reutilizado por Playwright MCP tenía en caché de disco una
      copia VIEJA de `vacation-planner.js` (12660 bytes, sin `prefillDates`) de una
      sesión de verificación anterior a los cambios de TASK-019; `curl` al mismo
      tiempo servía los 17786 bytes correctos. Se vació la caché de disco del
      perfil de Chrome del MCP y se repitió el flujo completo → prefill y hint
      funcionan correctamente. **No hay ningún bug de caché en el servidor ni en
      el código**: Apache no envía cache-busting en `<script src>` de assets
      estáticos (mejora de calidad no bloqueante, ver abajo).
    - `isValidIsoDate()` server-side re-confirmado de forma independiente (curl,
      sin depender del test previo del coder): sin fechas, `checkout<checkin`,
      `2026-02-30`, `'; DROP TABLE…` y `<script>alert(1)</script>` en `checkin` →
      los 5 casos `{success:false,error:'Dates requises'}`, **0 filas nuevas**
      (`SELECT COUNT(*)`=0 confirmado por SQL). Envío válido → `{success:true,...}`
      + fila con `checkin_date`/`checkout_date` exactos; `EmailTemplates::
      actorNotification()` invocada con esos datos NO contiene "dates à confirmer"
      y sí "du vendredi 4 septembre 2026 au lundi 7 septembre 2026" (criterio (d)
      CONFIRMADO a nivel de plantilla; no se pudo inspeccionar la bandeja real de
      `MAIL_TEST_ADDRESS`, pero `MailService::send()` no lanzó excepción). Fila de
      prueba eliminada tras verificar.
    - `php -l` OK en los 3 PHP; `node --check` OK en el JS; `grep htmlspecialchars
      | grep -v ENT_QUOTES` → 0 en `vacation_planner.php`; `git diff --stat` de
      `EmailTemplates.php` → 0 (confirmado sin cambios). home/search/planner HTTP
      200 sin regresión. 0 errores de consola en todo el flujo (Playwright).
    - Sin inyección SQL (PDO preparado en INSERT/SELECT), sin XSS nuevo (fechas
      viajan por `.value=`/JSON, nunca por `echo` PHP), sin CRLF en cabeceras de
      email (`sanitizeText()` retira `\x00-\x1F`/`\x7F` de name/email/phone/
      checkin/checkout antes de usarse en subject/headers), sin prompt injection
      nuevo (la fecha de hoy va FUERA de `<<<DEMANDE_UTILISATEUR>>>`, el bloque
      `system` sigue cacheable), sin secretos en cliente, SEC-013 intacto (guard
      `empty($apiKey)` sigue siendo el primer statement).
    - **SEC-014 (nuevo, registrado esta revisión):** `ai-plan-generate`/
      `ai-plan-submit` sin CSRF ni rate-limit. El coder afirmó en su handoff que
      esto "ya estaba registrado en TASKS.md", pero no había ninguna entrada
      previa (`grep -i csrf` → 0). Pre-existente, NO regresión de TASK-019.
      Registrado en LESSONS.md/ERROR_LOG.md, elevado a 🟡 Pendiente.
    - Riesgo de robustez (no de seguridad, señalado por el coder): la distinción
      "duración vs. fecha real" vive solo en el prompt del modelo, sin test
      automatizado — podría degradarse si el modelo cambia. Anotado, no bloqueante.
    - Mejora de calidad no bloqueante: los `<script>`/`<link>` de assets propios
      (`vacation-planner.js`, `.css`) no llevan versión/hash de cache-busting; un
      deploy que cambie JS/CSS puede servir una copia cacheada obsoleta a
      visitantes recurrentes hasta que expire la caché HTTP del navegador.
      Recomendación: `?v=<hash o timestamp de build>` en los `<script src>`/
      `<link href>` propios.
  - **(entrada original coder, sin cambios) implementación coder lista
    (2026-07-06):**
  - `VacationPlannerService::generatePlan()`: schema structured-outputs +3 campos
    (`date_debut`/`date_fin`/`date_precision` enum exact|approx|none, todos
    `required`). Reglas de determinación en `systemInstructions` (bloque no
    cacheado): exact/approx/none, `date_fin = date_debut + (duration_days-1)`,
    nunca fecha pasada, y regla explícita para que "week-end"/"séjour"/"X jours"
    (palabras de DURACIÓN sin mes/estación/fecha) cuenten como `none`, no `approx`.
    Fecha de hoy inyectada en `messages[0].content` ("Date du jour : AAAA-MM-JJ.")
    ANTES de `<<<DEMANDE_UTILISATEUR>>>`, fuera de los bloques `system` (prompt
    caching de TASK-018 intacto: catálogo cacheado sin tocar).
  - `normalizePlanDates(array $plan): array` (nuevo, privado): valida ISO real vía
    `DateTime::createFromFormat('Y-m-d', $v)` + round-trip `format()==$v` (rechaza
    "2026-02-30"); fuerza enum a `none` si no es exact/approx/none; deriva
    `date_fin` si falta/inválida/`< date_debut`; blanquea TODO si `date_debut` no es
    válida. Se llama sobre `hydrate()` antes del `return` de `generatePlan()`.
    **Nota de implementación:** se usó el idiom "createFromFormat + round-trip
    format()" en vez de `DateTime::getLastErrors()` (mencionado en el plan) porque
    la semántica de retorno de `getLastErrors()` (array vs `false`) cambió entre
    versiones de PHP; el round-trip es equivalente y estable en PHP 8.2+.
  - `fallback()`: añadidos `date_debut:''`, `date_fin:''`, `date_precision:'none'`.
    Guard `empty($this->apiKey)` sigue siendo el PRIMER statement (SEC-013 intacto).
  - `vacation_planner.php`: labels Arrivée/Départ con `<span class="req">*</span>`,
    inputs `required`; nuevo `<p class="planner-date-hint" id="date-hint" style="display:none;">`.
    (Estilo `.planner-date-hint` añadido en `vacation-planner.css`, archivo no
    listado en el plan pero necesario para que el hint no se vea sin estilo —
    adición de bajo riesgo, solo CSS.)
  - `vacation-planner.js`: `DATE_I18N` (fr/es/en) con `dateRequired`/`datePast`/
    `dateOrder`/`approxHint`; `setDateMinToday()` (min=hoy en ambos inputs, llamado
    en init y en prefill); `prefillDates(plan)` (nuevo, llamado en el `.then` de
    `generatePlan()` tras `renderPlan()`): rellena `#f-checkin`/`#f-checkout` con
    `date_debut`/`date_fin`, ajusta `min`, muestra/oculta `#date-hint` si
    `date_precision==='approx'`. Validación en el submit de `#contact-form`:
    checkin/checkout no vacíos, checkin ≥ hoy, checkout ≥ checkin, con mensajes
    i18n vía `#submit-error` — SIN tocar la validación existente de name/email.
  - `PageController::handleAIPlanSubmit()`: nuevo guard AUTORITATIVO server-side
    ANTES de `CREATE TABLE`/`INSERT`/emails — `isValidIsoDate($checkin)` +
    `isValidIsoDate($checkout)` + `$checkout >= $checkin`; si falla,
    `{success:false,error:'Dates requises'}` y no se ejecuta nada más. Nuevo
    helper privado `isValidIsoDate(string): bool` (mismo idiom round-trip que en
    el servicio). `handleAIPlanGenerate()` sin cambios de contrato.
    `EmailTemplates.php` **sin cambios** (confirmado con `git diff --stat` → 0
    líneas): al llegar `checkin`/`checkout` siempre reales y validados,
    `actorNotification()`/`buildSlotsHtml()` dejan de disparar "dates à confirmer"
    de forma automática, sin tocar la plantilla — tal como preveía el plan.
  - **Verificado en local (curl + navegador, servidor XAMPP real, API key real):**
    (1) `php -l` OK en los 3 PHP tocados + `EmailTemplates.php` sin diff;
    `node --check` OK en el JS. (2) Prompt real "Nous partons 4 jours en couple
    début septembre…" → `date_debut=2026-09-01`, `date_fin=2026-09-04`,
    `date_precision=approx`, `duration_days=4` (hoy=2026-07-06) — coincide con el
    criterio de éxito #1. (3) Prompt "Un week-end romantique…" (sin mes/estación) →
    `date_debut=''`, `date_fin=''`, `date_precision=none` — coincide con el
    criterio #2 (tras ajustar la regla del prompt para distinguir "duración" de
    "fecha real"; la 1ª versión del prompt clasificaba "week-end" como `approx`,
    corregido y re-verificado). (4) `curl` directo a `ai-plan-submit` evadiendo el
    cliente: sin fechas → `{success:false,error:'Dates requises'}`, 0 filas nuevas
    en `vacation_plans`; `checkout<checkin` → mismo rechazo; fecha calendario
    inválida `2026-02-30` → mismo rechazo. Confirmado con `SELECT COUNT(*)` en
    BD = 0 para los 3 casos. (5) `curl` con fechas válidas (`2026-09-01`/`09-04`) →
    `{success:true,reference:...}`, fila creada con esas fechas exactas en
    `checkin_date`/`checkout_date` (verificado por SQL), emails enviados sin error
    (`MailService::send()` retornó sin excepción). Filas de prueba eliminadas tras
    verificar (no quedan datos de test en `vacation_plans`). Home/search/planner
    HTTP 200 sin regresión.
  - **Pendiente para security:** ver "AVISOS PARA SECURITY" en el handoff de
    SESSION.md — confirmar en navegador (no solo curl) el flujo completo de UI
    (paso 3 prerrellenado, hint visible, bloqueo de submit) y el contenido real
    del email al prestador ("Séjour: du X au Y", no "dates à confirmer").
  - Archivos: `src/Infrastructure/Services/VacationPlannerService.php`,
    `public/assets/js/vacation-planner.js`, `src/Infrastructure/Views/vacation_planner.php`,
    `public/assets/css/vacation-planner.css` (nuevo, no listado en el plan),
    `src/Infrastructure/Controllers/PageController.php`.

- **TASK-018 — Migración IA: OpenAI → Claude Sonnet 4.6 ✅ pipeline completo + security ⚠️ aprobado con observaciones (2026-07-06)**
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
