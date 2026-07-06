# TASKS.md — Canal du Midi

Convención de IDs: `TASK-NNN` tareas · `BUG-NNN` bugs · `SEC-NNN` seguridad.

## 🔴 En curso

_(ninguna — TASK-019 pasó a security ⚠️ aprobada con observaciones, ver 🟢
Completadas; pendiente de handoff a product)_

## 🟡 Pendiente

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
