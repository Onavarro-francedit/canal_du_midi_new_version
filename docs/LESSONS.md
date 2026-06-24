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
  con plenos privilegios. Detectado en TASK-008 (GSAP/ScrollTrigger en home),
  corregido en `footer.php`. Deuda relacionada aún abierta: los `<script>` de
  Leaflet/markercluster (unpkg) en service/search siguen sin SRI.

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

- **SEC-006: htmlspecialchars() SIEMPRE con ENT_QUOTES, 'UTF-8' — nunca confiar en los flags por defecto.**
  PHP usa `ENT_COMPAT` por defecto: escapa `"` pero NO `'`. En atributos HTML delimitados por comillas simples (o en parsers tolerantes), un valor que contenga `'` sin escapar puede romper el atributo o permitir inyección. Además, omitir `'UTF-8'` puede generar comportamientos inesperados con caracteres multibyte. Regla fija del proyecto: `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')` en cada punto de salida, sin excepción. Detectado en `header.php` (5 llamadas) donde `$seo['title']` podía contener comillas simples vía el patrón "Résultats pour '{query}'" (BUG-009). Corregido en cluster buscador hero (2026-06-23).

## Producto / UX (PRD-NNN)

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
