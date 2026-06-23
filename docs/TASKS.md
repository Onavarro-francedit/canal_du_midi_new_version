# TASKS.md — Canal du Midi

Convención de IDs: `TASK-NNN` tareas · `BUG-NNN` bugs · `SEC-NNN` seguridad.

## 🔴 En curso

_(vacío — Incremento 1 completado; siguiente: Incremento 2, TASK-011 + TASK-013)_

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

- **TASK-011** — Elemento firma "la ligne d'eau": hairline horizontal
  (degradado teal→violeta) bajo el hero y como **divisor entre secciones**, con
  marcas-écluse como hitos. SVG/border estático, se dibuja una vez al cargar
  (robusto, NO ligado a scroll).
- **TASK-012** — "Les étapes du canal": tira secuenciada real
  Toulouse → Castelnaudary → Carcassonne → Béziers → Étang de Thau (secuencia
  geográfica real → marcadores numerados/écluse justificados). Punto de entrada
  a destinos/búsqueda.
- **TASK-013** — Micro-interacciones premium (150–250 ms, solo `transform`/
  `opacity`): cards de destinos y tours con *lift* + sombra suave + zoom de la
  imagen dentro del marco (`overflow:hidden` + `scale(1.06)`); nudge del icono
  en botones al hover.
- **TASK-014** — Limpieza de contenido off-brand de la home (parte del pase de
  pulido visual): quitar eyebrows en inglés → ver **TASK-004**; reemplazar fotos
  off-topic (montaña/mochilera) por imágenes de canal → ver **TASK-007**;
  newsletter funcional → ver **BUG-003**; bloque inmersivo "Lire la vidéo" →
  ver **BUG-004**; copy "qui se vend bien" → hablar al viajero.

## 🟢 Completadas

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
  (SEC-003). NOTA: la *ligne d'eau* reutilizable adelanta parte de TASK-011.
- **TASK-000** — Inicialización del pipeline de agentes y estructura `docs/`.

## 🚫 Descartadas / en pausa

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
