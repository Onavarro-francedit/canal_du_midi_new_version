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
