# LESSONS.md — Canal du Midi

Memoria persistente de errores aprendidos. La mantienen automáticamente los
agentes **security** (SEC-NNN) y **product** (PRD-NNN). Los agentes **architect**
y **coder** DEBEN leerla antes de empezar cualquier tarea.

Para más contexto de una lección, consultar `docs/ERROR_LOG.md`.

---

## Capa de datos (PDO / MySQL)

_(sin lecciones todavía)_

## Backend / Servicios (PHP, OpenAI, Mail)

_(sin lecciones todavía)_

## Vistas / Frontend (PHP, CSS, JS)

_(sin lecciones todavía)_

## Seguridad (SEC-NNN)

- **SEC-004: rawurlencode() y htmlspecialchars() son capas distintas en URLs dinámicas.**
  Al construir un `href` con valores de BD en el query-string, aplicar DOS capas independientes:
  (1) `rawurlencode()` sobre cada valor de segmento/parámetro (codifica `+`, `&`, `#`, espacios, etc.),
  (2) `htmlspecialchars($url, ENT_QUOTES, 'UTF-8')` sobre el atributo HTML completo.
  Saltarse rawurlencode permite inyectar parámetros adicionales (`slug=hotel&admin=1`) o romper la semántica de la URL. Detectado en `home.php:175` con `$cat['slug']`. Corregido en TASK-011/013.

- **SEC-005: BASE_URL obligatorio en hrefs; IDs numéricos de BD con cast (int) en output.**
  No hardcodear el subpath de instalación (`/canal_du_midi/`) en ningún `href` — usar siempre `BASE_URL`. Los IDs que el modelo declara `?int` (nullable) deben castearse con `(int)` antes del echo: convierte NULL a 0 (ruta inexistente, inocuo) y elimina toda superficie de inyección. Detectado en `home.php:263` tour card. Corregido en TASK-011/013.

- **SEC-003: CDN de terceros sin SRI.** Todo `<script>`/`<link>` que cargue un
  recurso de un CDN externo (jsDelivr, unpkg, etc.) debe llevar **versión fija**
  (nunca `@latest`) + `integrity="sha384-…"` (SRI) + `crossorigin="anonymous"`.
  Sin SRI, un CDN comprometido o un MITM ejecuta código arbitrario en la página
  con plenos privilegios. Detectado en TASK-008 (GSAP/ScrollTrigger en home).
  **CERRADA del todo (2026-06-24):** SEC-004 añadió SRI a los `<script>` de Leaflet
  1.9.4 + markercluster 1.5.3 (footer) y a bootstrap-icons (header); el cierre final
  añadió SRI a los `<link>` CSS de leaflet.css (service/search/poi) +
  MarkerCluster.css + MarkerCluster.Default.css. **Ya no queda ningún recurso CDN
  (JS ni CSS) sin `integrity` en el proyecto.** Lección operativa: al añadir SRI,
  cubrir JS *y* CSS del mismo paquete, y todas las páginas que lo cargan, no solo una.

- **SEC-007 (CORREGIDO 2026-06-24): htmlspecialchars() sin ENT_QUOTES en vistas — deuda pre-existente en search_results.php.**
  `search_results.php` llamaba a `htmlspecialchars($valor)` sin `ENT_QUOTES, 'UTF-8'`
  en 11 puntos. Detectado en L383/L404 (texto puro) en la revisión security de
  PRD-004/PRD-005, pero el grep reveló el mismo patrón en contexto de ATRIBUTO
  (`value=`, `data-value=`, `data-label=`, `data-selected=`) donde sí es relevante.
  En PHP 8.2 el flag por defecto ya incluye `ENT_QUOTES`, así que no era explotable,
  pero violaba SEC-006. CORREGIDO: las 11 instancias ahora usan `ENT_QUOTES, 'UTF-8'`;
  `grep` confirma 0 restantes, `php -l` OK. Lección operativa: al cerrar una deuda de
  escape, hacer `grep htmlspecialchars(... sin ENT_QUOTES` en TODO el archivo, no solo
  en las líneas reportadas.

- **SEC-009: todo `<iframe>` de tercero debe llevar `sandbox` con permisos mínimos.**
  Sin `sandbox`, el iframe puede acceder a `window.top`, navegar el frame principal o lanzar popups. El mínimo para un visor de documentos: `sandbox="allow-scripts allow-same-origin allow-popups allow-forms"`. Añadir `allow-popups` solo si el visor necesita abrir ventanas. Verificar que el visor funcione con el sandbox antes de desplegar. Detectado en `home.php:323` (iframe Calaméo). Pendiente de corregir (mejora no bloqueante).

- **SEC-008 (CORREGIDO 2026-06-24): al auditar escape en un archivo, hacer `grep htmlspecialchars( | grep -v ENT_QUOTES` sobre TODO el archivo, incluyendo archivos adyacentes en scope.**
  `EmailTemplates.php` contenía 21 llamadas a `htmlspecialchars()` sin `ENT_QUOTES, 'UTF-8'`, detectadas en la revisión del cluster BUG-003 por extensión de SEC-007. La mayoría texto de nodo (inocuo en PHP 8.2), pero varias en contexto de atributo `href="mailto:…"` y `href="tel:…"`. CORREGIDO en pase de limpieza con `perl` (lookahead para no doble-escapar las ya correctas): las 21 ahora usan `ENT_QUOTES, 'UTF-8'`; `grep` confirma 0 restantes, `php -l` OK, hrefs verificados intactos.

- **SEC-010: nunca emitir un `<iframe>` cuyo `src` viene de BD sin validar el host.**
  `strip_tags($html, '<iframe>')` NO es un sanitizador de atributos: preserva el `src` íntegro, permitiendo iframes de hosts arbitrarios (phishing, contenido inapropiado). Patrón seguro obligatorio: (1) extraer el `src` del iframe con `preg_match`, (2) validar el host contra una allowlist explícita (`youtube.com`, `youtu.be`, `vimeo.com`, etc.), (3) rechazar (skip) cualquier host no listado, (4) reconstruir el `<iframe>` con `htmlspecialchars($src, ENT_QUOTES, 'UTF-8')` + `sandbox` mínimo. Detectado en `service_detail.php:299`. Corregido en TASK-001 (2026-06-24).

- **SEC-011: al auditar una vista, buscar todos los ecos de IDs de modelo sin cast (int).**
  SEC-005 establece el cast `(int)` para IDs nullable. Al cerrar una tarea, ejecutar `grep "\$[a-z]*->id\b"` sobre cada vista modificada y confirmar que cada eco lleva `(int)`. Un ID nullable echado sin cast en `data-*` o `value=` emite el atributo vacío si el modelo devuelve NULL, lo que puede silenciar errores o alterar la lógica JS/AJAX downstream. Detectado en `service_detail.php:481` (`data-sid`) y `service_detail.php:634` (`value=service_id`). Corregido en TASK-001 (2026-06-24).

- **SEC-012 (CORREGIDO 2026-06-24): el catch de PDOException nunca debe imprimir `$e->getMessage()` al usuario.**
  Un fallo de conexión a BD en producción expone host, usuario y nombre de BD al navegador si el catch hace `die($e->getMessage())` (ej. `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`). Patrón seguro obligatorio: `error_log($e->getMessage())` (server-side) + `die("Erreur de connexion à la base de données.")` (mensaje genérico al usuario). Detectado en `src/Config/Database.php:27`. Corregido en SEC-001 (2026-06-24). Corolario: revisar todo `catch` que haga `die`/`echo` con el mensaje crudo de la excepción en capas de infraestructura.

- **SEC-006: htmlspecialchars() SIEMPRE con ENT_QUOTES, 'UTF-8' — nunca confiar en los flags por defecto.**
  PHP usa `ENT_COMPAT` por defecto: escapa `"` pero NO `'`. En atributos HTML delimitados por comillas simples (o en parsers tolerantes), un valor que contenga `'` sin escapar puede romper el atributo o permitir inyección. Además, omitir `'UTF-8'` puede generar comportamientos inesperados con caracteres multibyte. Regla fija del proyecto: `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')` en cada punto de salida, sin excepción. Detectado en `header.php` (5 llamadas) donde `$seo['title']` podía contener comillas simples vía el patrón "Résultats pour '{query}'" (BUG-009). Corregido en cluster buscador hero (2026-06-23).

- **SEC-013: en servicios IA, el check `empty($apiKey)` debe ser el primer guard, antes de cualquier procesamiento del prompt.**
  En `VacationPlannerService::generatePlan` la llamada a `sanitizeUserPrompt()` se hace antes del check de API key (líneas 19 y 36 respectivamente). No es un bug de seguridad (la sanitización es barata y el resultado no se filtra al exterior en la ruta de fallback), pero viola el principio de "fail fast": si no hay key, no hay razón para procesar el input. En `ClaudeAIService` el orden es correcto (key primero, sanitización después). Regla: en cualquier servicio que llame a una API externa, el guard `if (empty($apiKey)) { return fallback; }` va al inicio del método, antes de construir catálogos, sanitizar prompts o instanciar el cliente. Detectado en TASK-018 (2026-07-06). Mejora de calidad, no bloqueante.

- **SEC-015: un valor interpolado en un template string de CSS inline (`style.backgroundImage = \`url('${valor}')\``) puede romper el atributo igual que un `href`/`src` sin escapar, aunque no sea `innerHTML`.**
  En `backoffice-edit.js`, `setHeroPreview(url)` y `renderPhotos()` construyen
  `style.backgroundImage` con un template string sin escapar la comilla simple.
  El valor viene de `imageUrl`/`data.imageUrl` (URL de foto ya subida por el propio
  owner de esa fiche — no es input arbitrario de un tercero en este flujo), así que
  NO es explotable en el vector actual, pero es el mismo patrón de raíz que un XSS
  de atributo: si `imageUrl` alguna vez llegase de una fuente menos controlada
  (ej. nombre de archivo original del upload en vez de un hash aleatorio), una
  comilla simple cerraría `url('...')` y permitiría inyectar CSS/`expression()`
  arbitrario. Detectado en la revisión security de TASK-026 (2026-09-03). Regla:
  al fijar una URL en un `style` vía JS, usar `el.style.backgroundImage =
  \`url("${CSS.escape ? url.replace(/["\\]/g, '\\$&') : url}")\`` o, mejor,
  fijar la propiedad con un objeto CSSStyleValue si el navegador lo soporta;
  como mínimo escapar comillas dobles/simples y backslashes antes de interpolar
  en cualquier valor de `style`. **Pendiente de corregir** (mejora no bloqueante,
  no es una regresión de TASK-026 — el patrón `setHeroPreview` ya existía antes).

- **SEC-014: todo endpoint POST que llame a una API de pago externa o escriba en BD/envíe email necesita CSRF + rate-limit — no asumir que "ya está registrado" sin comprobarlo.**
  `ai-plan-generate` (llama a Anthropic, coste por token) y `ai-plan-submit`
  (crea filas en `vacation_plans` + dispara N emails a prestadores reales) no
  tienen token CSRF ni límite de tasa. El handoff de TASK-019 afirmó que esto
  "ya estaba registrado en TASKS.md", pero `grep -i "csrf\|rate.limit"` sobre
  `docs/TASKS.md`/`docs/LESSONS.md` dio 0 resultados: era una suposición no
  verificada. Regla operativa doble: (1) cualquier endpoint que dispare una
  llamada de pago o efectos secundarios costosos (email masivo, escritura BD)
  debe protegerse con CSRF + throttling básico por IP/sesión; (2) si un handoff
  dice "ya registrado en X", el siguiente agente debe verificarlo con `grep`
  antes de darlo por bueno, no repetir la afirmación sin comprobar. Detectado en
  la revisión security de TASK-019 (2026-07-06). Pendiente de corregir (ticket
  de backlog dedicado, no bloqueante para TASK-019: no es una regresión de esta
  tarea).

## Producto / UX (PRD-NNN)

- **PRD-013: un texto que también vive en un archivo DERIVADO (llms-full.txt, sitemap, feed) no está cambiado hasta regenerar y subir el derivado.**
  TASK-045 cambió la FAQ n.º 6 en `content.php` pero `wp-plugin/llms-full.txt` (generado por
  `build/build-llms-full.php` y ya público en httpdocs) sigue con la pregunta antigua: los motores IA leen otra
  versión que la página. Ojo: el script sin el argumento `<categorias.md>` vacía la lista de categorías. Regla: al
  tocar `CANAL_HOME_FAQ`/`CANAL_HOME_ETAPES`, añadir al plan « regenerar llms-full.txt (con categorías) y subirlo ».

- **PRD-009: al localizar strings NUEVOS en una página cuyo "chrome" ya está hardcodeado en un idioma, verificar la coherencia de idioma de TODA la pantalla, no solo del string nuevo.**
  TASK-019 añadió `DATE_I18N` (fr/es/en) para el hint de fecha aproximada y los 3
  errores de fecha del planner, y quedaron CORRECTOS en los 3 idiomas (verificado
  en navegador: `/es/vacation-planner` muestra el hint "Fechas estimadas a partir de
  su solicitud…" y el error "Indique sus fechas de llegada y salida."). Pero el
  resto de `vacation_planner.php` está **hardcodeado en francés**: títulos, labels
  ("Arrivée prévue *", "Départ prévu *", "Vos coordonnées"), intro, botón de envío,
  pantalla de confirmación, Y los OTROS errores JS del mismo formulario
  (nombre/email vacío → "Veuillez renseigner les champs obligatoires.", fallo de
  envío, prompt vacío, fallo de generación). Resultado verificado en navegador: en
  el MISMO formulario en `/es/`, dejar fechas vacías da error en español pero dejar
  el nombre vacío da error en francés; y el hint español aparece incrustado entre
  labels francesas. Además TASK-018 hace que la IA responda en el idioma del usuario,
  así que el CONTENIDO del plan llega en es/en dentro de un marco francés. Regla:
  cuando localices un string nuevo en una vista, (1) audita TODA la pantalla que el
  usuario ve en ese idioma (labels, intro, botones, TODOS los mensajes de error del
  mismo handler JS, confirmación), (2) o localizas el conjunto coherente o registras
  la deuda explícita — nunca dejar dos idiomas mezclados en la misma pantalla o el
  mismo formulario (corolario de PRD-004/PRD-006: coherencia de cara al usuario).
  Detectado en la revisión product de TASK-019 (2026-07-06). NO es regresión de
  TASK-019 (el chrome francés es pre-existente); TASK-019 hizo bien SU parte, pero
  su localización dejó visible la costura. Pendiente: ver TASK-025 en 🟡.

- **PRD-001: Decoración anti-FOUC debe ser visible por defecto, no opacity:0.**
  Cualquier elemento puramente decorativo cuya animación dependa de un JS de CDN
  (GSAP, etc.) NO debe tener `opacity:0` como estado base esperando que el JS
  lo revele. Si el CDN o el JS fallan, queda invisible para siempre. El estado
  base en CSS debe ser **visible**; el "ocultar para animar" debe hacerlo el
  propio JS (o, como aquí, un mecanismo que no afecte la versión estática:
  `stroke-dashoffset` dibuja la línea sin tocar la opacidad). Detectado en
  TASK-008 (`.canal-line`, styles.css ~1294: `opacity:0` sin `html.gsap-ready`);
  corregido a `opacity:1` por defecto. Criterio del architect: "si el CDN de
  GSAP falla, nada queda en opacity:0 permanente".
  (Nota: el código de TASK-008 fue revertido; la lección sigue siendo válida.)

- **PRD-003: Gradiente SVG `objectBoundingBox` sobre línea recta = stroke invisible.**
  Un `<linearGradient>` sin `gradientUnits` usa por defecto `objectBoundingBox`.
  Si el elemento pintado es un path de **altura o anchura cero** (una línea recta
  horizontal/vertical, como la hairline "ligne d'eau" `M0 12 H1000`), su bounding
  box es degenerado y el gradiente colapsa: el `stroke: url(#grad)` se pinta como
  **transparente/nada**. Solución: declarar `gradientUnits="userSpaceOnUse"` con
  `x1/x2/y1/y2` en coordenadas del viewBox. Corolario de verificación: que el
  computed style del `stroke` sea `url("#grad")` y el `<defs>` exista NO prueba que
  la línea se pinte — hay que **muestrear el color real del píxel en navegador**
  (refuerza PRD-002). Detectado en TASK-011 (3 divisores + ligne del hero: solo se
  veían los 4 puntos-écluse, sin línea que los una). **CORREGIDO en BUG-005
  (2026-06-23):** `gradientUnits="userSpaceOnUse"` con `x1/x2/y1/y2` del viewBox en
  `home.php` L110-114. Verificado con muestreo de píxeles real (no computed style):
  hero izquierda rgb(43,181,196)=teal exacto → derecha rgb(84,77,190)=violeta exacto,
  los 4 divisores pintan el degradado, los 16 puntos-écluse visibles, igual en
  reduced-motion. La regla queda como referencia permanente.

- **PRD-004: Nunca imprimir un slug/clave técnica en texto de cara al usuario.**
  Si un valor llega como slug de BD desde un `<select>`/query-string
  (`location-de-velo`, `nautique`), NO echarlo crudo en `<title>`, `<h1>` ni copy:
  mapearlo a la etiqueta traducida (`name`) que el propio `<select>` ya muestra al
  usuario. El select y el título deben hablar el mismo idioma humano. Detectado en
  el título SEO condicional del buscador hero (`PageController.php:227`,
  `$seoTitle = "… — " . $types[0]`): `?type=location-de-velo` renderizaba
  `<title>Séjours et activités — location-de-velo</title>` en vez de "Location de
  vélo". El controlador ya tenía `$categories` cargado (mapa slug→name disponible).
  Verificación: inspeccionar el `<title>` REAL en navegador para cada rama del
  título, no asumir. **Pendiente de corregir** (vuelve a architect/coder).

- **PRD-005: Expandir una categoría padre a todos sus hijos puede romper la UX aunque sea correcto en datos.**
  La expansión jerárquica padre→hijos en un filtro es válida a nivel de modelo,
  pero una categoría padre puede mezclar servicios reservables con POIs de
  infraestructura/patrimonio. Resultado: un filtro que el usuario entiende como
  "X reservable" devuelve mayormente "no-X". Detectado en el buscador hero:
  Type="Le Canal en Bateau" (slug `nautique`, padre) devuelve 136/253, de los
  cuales 93 (68%) son `ecluses`(72)+`ports`(21) — esclusas y puertos, no barcos.
  El turista que busca una croisière ve una lista de esclusas. Regla: antes de
  exponer un filtro, CONTAR y MIRAR la muestra real de resultados en navegador
  (refuerza PRD-002) y juzgar si responde a lo que el label promete; "filtra algo
  y es < total" NO basta. Opciones de fix: excluir hijos no-reservables, apuntar el
  option a las hijas correctas, o dar a écluses/ports su propia entrada de filtro.
  **Pendiente de corregir** (decisión de producto + architect/coder).

- **PRD-002: El motion/scroll se verifica en NAVEGADOR, no a nivel de código.**
  El pipeline aprobó TASK-008 (✅/⚠️) revisando código y métricas (overflow,
  conteo de triggers, opacidades), pero NO detectó que dos pins de sección
  completa se solapaban visualmente ni que la línea del canal era un trazo
  tosco. Solo `/verify` en navegador (captura real) lo destapó, y el usuario
  acabó revirtiendo. Para cualquier tarea de animación/scroll: capturar la
  página renderizada y JUZGAR cómo se ve antes de aprobar; un PASS "mecánico"
  (sin errores, sin overflow) NO es evidencia de que se ve bien. Además: el
  **pin de sección completa múltiple en ScrollTrigger es frágil** (secciones
  contiguas con `start:'top top'` se solapan); preferir parallax sutil +
  reveals + progreso sin pins de sección.

- **PRD-007: un `<div id="map">` con `data-lat/lng` correctos NO implica un mapa — verifica que la librería se cargue para ESE `$page`.**
  El `layout/footer.php` carga Leaflet condicionado por `$page` (`service`/`fiche`/`search`/`home`).
  La ruta `poi` emite su contenedor de mapa (`poi_detail.php`) con `data-lat`/`data-lng`/`data-title`
  bien casteados, pero el footer NO tiene rama para `$page === 'poi'`, así que `window.L` queda
  `undefined` y el mapa nunca inicializa: el turista ve un hueco gris, sin error de consola (falla
  silenciosa). Detectado en la verificación de TASK-001/002 sobre `/fr/poi/3` (Écluse de Bayard).
  Es un bug pre-existente de gating del footer (commit `b554b6f`), NO una regresión del escape.
  Regla: al añadir/tocar una ruta que pinta un mapa, comprobar EN NAVEGADOR que existe
  `.leaflet-container` + marcador real para ese `$page` (refuerza PRD-002); el `<div>` con
  `data-*` correcto no basta. Fix: añadir `poi` a la condición de carga de Leaflet en `footer.php`
  (CSS+JS con el SRI ya existente, SEC-004) + init que lea `#map[data-lat][data-lng]`.
  **Pendiente de corregir** (seguimiento, fuera del scope de TASK-001/002).

- **PRD-006: PRD-004 (no slugs al usuario) no se limita al `<title>` — audita cards, meta y badges.**
  Corolario de PRD-004. Tras corregir el slug crudo en el título de search, el mismo
  patrón reapareció en la HOME: la meta de las `.tour-card` (BUG-001) imprime
  `prestataires-touristiques` (el `$tour->type` crudo, igual para 253/253 listings) en
  vez de la categoría legible. Es jerga interna con guiones bajo un título de
  experiencia real. Regla: al exponer CUALQUIER componente con datos de BD (card,
  badge, meta, chip, breadcrumb), verificar en navegador el TEXTO REAL renderizado y
  mapear todo slug/`type`/clave a su `name` traducido; nunca echar `type`/`slug` crudo
  ni conformarse con un fallback genérico ("Durée flexible") que oculta el problema.
  Detectado en `home.php` (meta de tour-card) en la verificación del cluster
  BUG-001/002/003. **CORREGIDO 2026-06-24** (PRD-006/BUG-012, pipeline architect→coder,
  verdict PASS en navegador): `PageController` calcula `$tourMetaLabels[(int)$tour->id]`
  vía helper `resolveTourMetaLabel` (prioriza categoría experiencial, excluye
  `prestataires-touristiques`/`ecluses`/`ports`, fallback a 1ª categoría real, luego
  `$tour->type` legible, nunca slug crudo); `home.php` lo imprime con
  `htmlspecialchars(...,ENT_QUOTES,'UTF-8')`. Verificado: las 4 tour-cards muestran
  "Location de bateau"/"Location de vélo"/"Croisière en bateau", 0 slugs crudos.

- **PRD-010: un mensaje de error no está "mostrado" por tener texto y `hidden=false` — hay que comprobar que su rect cae DENTRO del área visible del contenedor scrolleable, en un viewport realista.**
  En `#modal-photos` (TASK-026) la validación cliente de subida funciona: rechaza el archivo,
  vacía el input y NO dispara ninguna petición de red. Pero el `.ficha-modal-error` vive en la
  cabecera del formulario y los dropzones están al final: con el modal desplazado (lo normal
  para llegar a ellos) el mensaje se pinta 368 px por encima del área visible en un viewport
  1440×800 → el prestador elige un PDF o una foto de 7 Mo y **no ve absolutamente nada**, cree
  que la foto se añadió y guarda sin ella. Regla: (1) al revelar un error, desplazarlo a la
  vista (`scrollIntoView({block:'nearest'})`) o pintarlo junto al control que lo provocó;
  (2) al verificar, medir `getBoundingClientRect()` del mensaje contra el rect del contenedor
  scrolleable en el momento del fallo y capturar el viewport — nunca conformarse con
  `hidden === false` ni con una captura en un viewport artificialmente alto (refuerza PRD-002).
  Detectado en la revisión product de TASK-026 (2026-09-03). **CORREGIDO el mismo día**
  (helper `showModalError()` + `scrollIntoView`), con un segundo hallazgo en la
  re-verificación que es la parte más valiosa de la lección: **`block: 'nearest'` NO basta
  cuando hay una cabecera collant**. Deja el mensaje justo en el borde superior de la zona
  desplazable, es decir DEBAJO del `<h2>Photos</h2>` (y=60→163) — rect "dentro" del formulario,
  `hidden=false`, y aun así invisible. Se detectó con
  `document.elementFromPoint(centro del mensaje)`, que devolvía el `H2` en vez de la caja de
  error. Regla final de verificación: la prueba de que un mensaje se ve no es su rect, es que
  `elementFromPoint()` en su centro devuelva el propio mensaje (más la captura). Fix aplicado:
  `block: 'center'` (alternativa equivalente: `scroll-margin-top` igual a la altura de la
  cabecera collant).

- **PRD-011: al repintar por AJAX, la rama "cero elementos" debe limpiar TODOS los reflejos del dato anterior (fondos CSS incluidos) y decir algo al usuario.**
  `renderPhotos()` escribe el hero con `if (hero && imageUrl) hero.style.backgroundImage = …`:
  cuando el usuario borra la ÚLTIMA foto, `imageUrl` llega vacío, el `if` no entra y la pantalla
  se queda con el fondo de **la foto recién borrada** — la acción parece no haber tenido efecto
  (la BD sí quedó correcta). Además el picker de portada se vacía sin mensaje, bajo un label que
  sigue diciendo "cliquez une photo pour la choisir" y sin nada que clicar. Regla: un `if (valor)`
  alrededor de una escritura de estado convierte el vaciado en un no-op; tratar el caso vacío de
  forma explícita (limpiar/placeholder) y dar el mismo tipo de mensaje de estado vacío que ya
  tienen las listas hermanas ("Aucune photo pour le moment."). Corolario de verificación: probar
  siempre el borrado hasta 0 elementos, no solo el de "uno de varios". Detectado en la revisión
  product de TASK-026 (2026-09-03). **CORREGIDO y re-verificado en navegador el mismo día**
  (`setHeroPreview`/`renderPhotos` limpian `backgroundImage` a `''` y el picker pinta
  "Aucune photo pour le moment." con `photos.length === 0`).

- **PRD-012: una interacción sin affordance no existe para el usuario.**
  El reordenado por drag & drop de TASK-026 funciona (verificado: arrastrar la 6ª vignette a la
  1ª posición reordena el DOM y el orden persiste en BD y en el carrusel público tras guardar y
  recargar), pero nada lo anuncia: el label del picker dice solo "(cliquez une photo pour la
  choisir)", el `cursor` es `pointer` (no `grab`/`move`) y no hay icono de arrastre. Un prestador
  no descubrirá la función. Además el drag nativo HTML5 (`draggable="true"`) **no funciona en
  táctil**, así que en tablet/móvil el reordenado es sencillamente inaccesible, sin fallback.
  Regla: toda interacción nueva necesita (1) una pista textual o visual explícita en el idioma del
  usuario, (2) `cursor` coherente, y (3) una decisión consciente y documentada sobre táctil
  (fallback con flechas ↑↓, o "el backoffice es desktop-only"). Detectado en la revisión product
  de TASK-026 (2026-09-03). **Pendiente de decidir/corregir** (BUG-020).
