# TASKS.md — Canal du Midi

Convención de IDs: `TASK-NNN` tareas · `BUG-NNN` bugs · `SEC-NNN` seguridad.

## 🔴 En curso

_(ninguna — pendiente revisión security del cluster buscador del hero)_

## 🟡 Pendiente

- **PRD-004 / BUG-010** (copy, severidad media) — El título SEO de búsqueda imprime
  el SLUG crudo del tipo, no el nombre legible. `PageController.php:227`:
  `$seoTitle = "Séjours et activités — " . $types[0]`. Verificado en navegador:
  `?type=location-de-velo` → `<title>… — location-de-velo</title>`; `?type=nautique`
  → `… — nautique`. Debe mostrar "Location de vélo" / "Le Canal en Bateau". El
  controlador ya carga `$categories` (mapa slug→name). Fix: índice slug→name +
  fallback al slug. Re-verificar las 4 ramas del título en navegador. Lección PRD-004.

- **PRD-005 / BUG-011** (coherencia de filtro, severidad media-alta) — Type="Le Canal
  en Bateau" (`nautique`) devuelve 136/253, de los cuales 93 (68%) son écluses(72) +
  ports(21) — infraestructura no reservable, no barcos. `resolveCategoryIdsForSearch`
  expande el padre `nautique` a TODAS sus hijas (incluidas `ecluses` y `ports`). El
  turista que busca una croisière ve una lista de esclusas. Decisión de producto +
  fix: excluir hijos no-reservables del genérico náutico, o apuntar el option a las
  hijas reservables (croisiere-bateau/location-bateau/location-de-canoe-kayak), o
  separar écluses/ports a su propia entrada. Re-verificar conteo+muestra en navegador.
  Lección PRD-005.

- **TASK-016b** (datos, largo plazo) — Backfill de una columna `commune` limpia en
  `listings`, extraída de `address`/`postal_code` (la columna `city` actual contiene
  el nombre del establecimiento, no la localidad). Permite un dropdown de
  "Destination" exacto y joins por comuna sin depender de `LIKE` sobre `address`.
  Sustituye la lista curada de TASK-016 (a) cuando esté lista. FUERA del cluster del
  buscador del hero.

- **SEC-001** — Unificar credenciales de BD: `src/config/Database.php` hardcodea
  `root`/password vacío; debe leer `DB_HOST/DB_NAME/DB_USER/DB_PASS` del `.env`.
- **TASK-001** — Revisar escape de salida (`htmlspecialchars`) en las vistas que
  imprimen contenido de servicios/POIs y resultados de IA.
- **TASK-002** — Endurecer entradas de usuario que alimentan prompts de OpenAI
  (`SmartAIService`, `VacationPlannerService`) frente a prompt injection.
- **SEC-004** — Añadir SRI (`integrity` + `crossorigin="anonymous"`) a los
  `<script>` de Leaflet 1.9.4 y leaflet.markercluster 1.5.3 (unpkg) cargados en
  `footer.php` para las páginas service/search. Misma deuda que SEC-003 (GSAP),
  ya resuelta en home. Lección aplicable: SEC-003.

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
