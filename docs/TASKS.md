# TASKS.md — Canal du Midi

Convención de IDs: `TASK-NNN` tareas · `BUG-NNN` bugs · `SEC-NNN` seguridad.

## 🔴 En curso

_(nada en curso — Incremento 2: TASK-013 ✅ se mantiene; TASK-011 (ligne d'eau)
**eliminada** por decisión del usuario (2026-06-23, ver 🚫). Siguiente candidato:
Incremento 3 → TASK-012 "Les étapes du canal", o la deuda de i18n/contenido
TASK-004/005/006/007. Ver 🟡 Pendiente.)_

## 🟡 Pendiente

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
