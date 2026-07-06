# ERROR_LOG.md — Canal du Midi

Historial completo de errores. No leer en cada sesión; consultar solo si una
lección de `docs/LESSONS.md` necesita más contexto. Lo completan automáticamente
los agentes security y product.

Formato de entrada:

```
### [SEC-NNN | PRD-NNN] Título — fecha
- Síntoma:
- Causa raíz:
- Corrección aplicada:
- Cómo prevenirlo (→ LESSONS.md):
```

---

### [PRD-009] Planner con idiomas mezclados: hint/errores de fecha localizados dentro de un chrome hardcodeado en francés — 2026-07-06
- Síntoma: en `/es/vacation-planner` y `/en/vacation-planner`, TASK-019 añadió el
  hint de fecha aproximada y los errores de fecha correctamente en español/inglés,
  pero el resto de la página sigue en francés. Verificado en navegador (Playwright,
  flujo real con prompt español "Viajamos 4 días en pareja a principios de
  septiembre…" → plan generado, dates prerrellenadas 2026-09-07→2026-09-10):
  · Hint (ES, correcto): "Fechas estimadas a partir de su solicitud — ajústelas si
    es necesario."
  · Error fechas vacías (ES, correcto): "Indique sus fechas de llegada y salida."
  · Pero labels/heading/intro/botón en FRANCÉS: "Arrivée prévue *", "Départ prévu *",
    "Vos coordonnées", "Nous allons transmettre votre demande…", "Envoyer ma demande
    d'intérêt".
  · En el MISMO formulario, dejar el nombre vacío da error en FRANCÉS ("Veuillez
    renseigner les champs obligatoires.") mientras dejar las fechas vacías da error
    en español → dos idiomas en el mismo formulario.
- Causa raíz: `vacation_planner.php` tiene todo el texto hardcodeado en francés
  (deuda pre-existente, la página se construyó monolingüe). `vacation-planner.js`
  solo pasó a `DATE_I18N` (fr/es/en) los 4 strings NUEVOS de TASK-019; los demás
  mensajes JS (`input-error` prompt vacío/fallo de generación, `submit-error`
  nombre-email/fallo de envío) siguen como literales franceses. TASK-018 hizo que la
  IA responda en el idioma del usuario, así que el contenido del plan llega en es/en,
  acentuando la mezcla.
- Corrección aplicada: ninguna en esta revisión. NO es regresión de TASK-019 (su
  objetivo de negocio —fechas firmes en el lead— se cumple, y sus propios strings
  quedaron correctos en los 3 idiomas). Se abre TASK-025 (🟡) para localizar el
  chrome del planner + los mensajes JS restantes.
- Cómo prevenirlo (→ LESSONS.md PRD-009): al localizar un string nuevo, auditar TODA
  la pantalla/formulario en ese idioma (labels, intro, botones, TODOS los errores del
  mismo handler, confirmación); localizar el conjunto coherente o registrar la deuda,
  nunca dejar dos idiomas mezclados de cara al usuario (corolario PRD-004/PRD-006).

### [SEC-014] Endpoints ai-plan-generate / ai-plan-submit sin token CSRF ni rate-limit — 2026-07-06
- Síntoma: `PageController::handleAIPlanGenerate()` y `::handleAIPlanSubmit()` (case
  `ai-plan-generate`/`ai-plan-submit` del router) aceptan POST sin verificar ningún
  token CSRF ni aplicar límite de tasa por IP/sesión. El handoff coder→security de
  TASK-019 afirmaba que esto ya estaba "registrado en TASKS.md", pero al revisar
  `docs/TASKS.md` y `docs/LESSONS.md` no existe ninguna entrada previa sobre esto
  (`grep -i csrf\|rate.limit` → 0 coincidencias). Se registra aquí por primera vez.
- Causa raíz: el endpoint `ai-plan-generate` reenvía el prompt del visitante a la
  API de Anthropic (coste por token) y `ai-plan-submit` crea filas en BD y dispara
  hasta N emails (uno por prestador del plan + uno al usuario). Sin CSRF, un sitio
  malicioso puede forzar estas acciones desde el navegador de un visitante
  autenticado con cookies de sesión válidas (aunque aquí no hay sesión de usuario
  final, así que el impacto real es más de abuso/costo que de suplantación). Sin
  rate-limit, un atacante puede automatizar llamadas masivas: (a) agotar cuota/
  presupuesto de la API key de Anthropic, (b) inundar de emails a los prestadores
  reales y al buzón de contacto, (c) llenar `vacation_plans` de filas basura.
- Corrección aplicada: ninguna en esta revisión — NO es una regresión introducida
  por TASK-019 (el guard de fechas de TASK-019 no agrava ni mitiga este vector);
  es deuda pre-existente desde que se creó el planificateur IA. Se eleva como
  recomendación no bloqueante para un ticket de backlog dedicado.
- Cómo prevenirlo (→ LESSONS.md): todo endpoint POST que (1) llame a una API de
  pago externa o (2) escriba en BD/envíe email debe llevar como mínimo un token
  CSRF de sesión y algún throttling básico (por IP y/o por sesión) antes de
  ejecutar la acción cara. Verificar en cada nueva tarea de IA/formulario si el
  endpoint que se toca ya tiene esta protección; si no, señalarlo explícitamente
  en el handoff en vez de asumir que "ya está registrado".

---

### [SEC-013] Observación: orden sanitización vs. check de API key en VacationPlannerService — 2026-07-06
- Síntoma: en `VacationPlannerService::generatePlan`, `sanitizeUserPrompt()` se invoca en la línea 19 antes del `if (empty($this->apiKey))` de la línea 36. Esto significa que la sanitización se ejecuta incluso en la ruta del fallback (sin API key).
- Causa raíz: no es un bug de seguridad (la sanitización es CPU-barata y no filtra ni mutaciones del prompt al exterior en la ruta de fallback). Es una inconsistencia menor de orden respecto a `ClaudeAIService`, donde el check de key precede a la sanitización.
- Corrección aplicada: ninguna (no bloqueante). Observación registrada como mejora de calidad.
- Cómo prevenirlo: en servicios IA, el check de `empty($apiKey)` debe ser el primer guard, antes de cualquier procesamiento del prompt. Garantiza que no se consume CPU ni se modifica el input antes de confirmar que habrá una llamada real.

---

### [SEC-003] JS de terceros por CDN sin Subresource Integrity (SRI) — 2026-06-23
- Síntoma: en TASK-008 se cargaron `gsap@3.13.0/gsap.min.js` y
  `ScrollTrigger.min.js` desde jsDelivr con `<script defer src>` pero sin
  `integrity` ni `crossorigin`. Ese JS se ejecuta con plenos privilegios en la
  home (mismo origen del DOM, cookies de sesión, formularios de búsqueda/IA).
- Causa raíz: confianza implícita en el CDN. Si jsDelivr se ve comprometido o
  hay un ataque MITM/secuestro de la URL, se inyecta y ejecuta código arbitrario
  en cada visitante de la home sin que el navegador lo bloquee.
- Corrección aplicada: añadidos `integrity="sha384-…"` (hash calculado del
  contenido real de gsap@3.13.0) + `crossorigin="anonymous"` a ambos `<script>`
  en `src/Infrastructure/Views/layout/footer.php`. El navegador rechaza el
  recurso si el hash no coincide. La versión ya estaba fijada (no `@latest`).
- Cómo prevenirlo (→ LESSONS.md): todo `<script>`/`<link>` de un CDN externo
  debe llevar versión fija + `integrity` (SRI) + `crossorigin="anonymous"`.

### [SEC-004] slug de BD concatenado en URL sin rawurlencode — 2026-06-23 (TASK-011/013)
- Síntoma: `home.php:175` construía `$url = BASE_URL . $lang . '/search?type=' . $cat['slug']` sin codificar. Un slug con espacio, `+`, `&`, `#` o cualquier reservado RFC 3986 rompe el parámetro `?type=` o permite inyectar parámetros adicionales en la URL (`hotel&admin=1`).
- Causa raíz: `htmlspecialchars(ENT_QUOTES)` escapa las comillas para el contexto HTML pero NO codifica caracteres especiales de URL dentro del valor de un query-string. Son dos capas distintas de escape.
- Corrección aplicada: `rawurlencode((string)($cat['slug'] ?? ''))` sobre el valor del parámetro antes de concatenar; `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` se mantiene sobre el atributo `href` completo.
- Cómo prevenirlo (→ LESSONS.md): en URLs dinámicas separar siempre las dos capas: `rawurlencode()` para el valor de cada segmento/query-param; `htmlspecialchars(ENT_QUOTES,'UTF-8')` para el atributo HTML que envuelve la URL.

### [SEC-005] Tour card: URL hardcodeada y $tour->id sin cast — 2026-06-23 (TASK-011/013)
- Síntoma: `home.php:263` usaba `href="/canal_du_midi/<?= $lang ?>/service/<?= $tour->id ?>"`. Dos problemas: (1) path hardcodeado `/canal_du_midi/` — roto en cualquier otro subpath o dominio; (2) `$tour->id` (tipo `?int`) echado directamente sin escape ni cast — aunque la BD lo tipifica como INT, el type hint es nullable y un NULL daría `href="/canal_du_midi/fr/service/"`, forma inadvertida de bypass.
- Causa raíz: reutilización de un snippet anterior a la refactorización de BASE_URL + ausencia de cast explícito en el output.
- Corrección aplicada: `href="<?= BASE_URL . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>/service/<?= (int)$tour->id ?>"`. Cast `(int)` convierte NULL → 0 (inofensivo, la ruta no existirá) y elimina cualquier superficie de inyección.
- Cómo prevenirlo (→ LESSONS.md): nunca hardcodear el subpath de instalación en un href; usar siempre `BASE_URL`. IDs numéricos de BD deben castearse con `(int)` en el output aunque el modelo los declare `int`.

### [PRD-001] Elemento decorativo con opacity:0 invisible si el CDN de GSAP falla — 2026-06-23
- Síntoma: en TASK-008 la `.canal-line` (SVG decorativo, `aria-hidden`, sobre las
  fotos apiladas de la sección Expériences) tenía `opacity:0` como estado base en
  styles.css (~1294) y solo pasaba a `opacity:1` bajo `html.gsap-ready`. Esa clase
  la añade gsap-story.js únicamente si GSAP+ScrollTrigger cargan desde el CDN.
- Causa raíz: estado oculto por defecto delegando el "revelado" a un JS de terceros.
  Si jsDelivr no responde, el SRI rechaza el recurso, o el JS no corre, la clase
  `gsap-ready` no se añade y la línea queda invisible permanentemente. Viola el
  criterio del architect "si el CDN de GSAP falla, nada queda en opacity:0".
- Corrección aplicada: `.canal-line` base pasa a `opacity:1`. El efecto de
  "dibujado" en desktop+motion lo controla `stroke-dashoffset` (gsap-story.js),
  que no toca la opacidad, así la versión estática se ve completa y la animada
  sigue dibujándose. Se conserva la regla `html.gsap-ready .canal-line {opacity:1}`
  (inocua). styles.css.
- Cómo prevenirlo (→ LESSONS.md PRD-001): la decoración dependiente de CDN debe
  ser visible por defecto; ocultar-para-animar es responsabilidad del JS.

### [SEC-007] htmlspecialchars() sin ENT_QUOTES en nodos de texto de search_results.php — 2026-06-24 (revisión PRD-004/PRD-005)
- Síntoma: `search_results.php` L383 usa `htmlspecialchars($filter)` sin flags para
  imprimir cada chip de filtro activo (`<span class="active-filter-chip">`);
  L404 usa `htmlspecialchars($s->translations['title'] ?? ...)` sin flags para
  `$serviceTitle` (usado en `alt=` y `<h3>` dentro del bucle de resultados).
  El contexto primario es texto de nodo (no atributo), así que `ENT_COMPAT` previene
  XSS estricto, pero la incosistencia viola la regla SEC-006 del proyecto y el patrón
  puede copiarse a atributos donde sí sería insuficiente.
- Causa raíz: código pre-existente anterior a la lección SEC-006; no introducido
  por los cambios de PRD-004/PRD-005.
- Corrección aplicada: NINGUNA todavía — deuda menor. Añadir `ENT_QUOTES, 'UTF-8'`
  en L383 y L404 de `search_results.php`.
- Cómo prevenirlo (→ LESSONS.md SEC-007 / SEC-006): toda llamada a
  `htmlspecialchars()` en el proyecto debe incluir `ENT_QUOTES, 'UTF-8'`,
  sin excepción, independientemente del contexto de salida.

### [SEC-006] htmlspecialchars() sin ENT_QUOTES en atributos HTML — 2026-06-23 (cluster buscador hero)
- Síntoma: `header.php` llamaba a `htmlspecialchars($seo['title'])` etc. sin pasar `ENT_QUOTES, 'UTF-8'`. PHP usa por defecto `ENT_COMPAT` (solo escapa `"`, no `'`). En el elemento `<title>` es inocuo, pero en los atributos `content=` de los meta (`og:title`, `og:description`, `description`, `keywords`) una comilla simple en el valor — posible cuando `$query` contiene `'` y el patrón BUG-009 genera `"Résultats pour 'valor'"` — puede romper el atributo o dar pie a inyección en parsers que acepten comillas simples como delimitadores.
- Causa raíz: omisión de los flags `ENT_QUOTES, 'UTF-8'` en los cinco `htmlspecialchars()` del header. `sanitizeText()` (upstream) elimina tags y control chars pero NO escapa entidades, por lo que el escape queda únicamente en el output — y sin ENT_QUOTES deja pasar `'`.
- Corrección aplicada: añadidos `ENT_QUOTES, 'UTF-8'` a los cinco `htmlspecialchars()` de `header.php` (L6 `<title>`, L7 `meta description`, L8 `meta keywords`, L16 `og:title`, L17 `og:description`).
- Cómo prevenirlo (→ LESSONS.md SEC-006): toda llamada a `htmlspecialchars()` en este proyecto debe incluir `ENT_QUOTES, 'UTF-8'` explícitamente. Nunca confiar en los flags por defecto de PHP.

### [PRD-003] Gradiente SVG en objectBoundingBox sobre un trazo horizontal = stroke invisible — 2026-06-23 (TASK-011)
- Síntoma: los 3 divisores "ligne d'eau" entre secciones (y también la ligne del
  hero) renderizan SOLO los 4 puntos-écluse; la hairline de degradado que los
  une NO se pinta. Verificado en navegador (Chrome vía Playwright): muestreando
  40 puntos a lo largo del eje central de cada divisor → 0 píxeles pintados en el
  trazo (los marks son visibles porque usan `fill: var(--water)`, un color sólido,
  no el gradiente). Visualmente el divisor lee como "cuatro puntos sueltos
  flotando", no como una línea elegante. Falla el criterio de éxito principal de
  TASK-011 ("mismo lenguaje visual que el del hero: degradado teal→violeta").
- Causa raíz: `<linearGradient id="ligneEauGradient">` no declara `gradientUnits`,
  por lo que usa el valor por defecto `objectBoundingBox`. El trazo es una línea
  perfectamente horizontal (`d="M0 12 H1000"`), cuyo bounding box tiene **altura
  cero**. Con `objectBoundingBox` un bbox degenerado (área 0) colapsa el gradiente
  y el `stroke` se pinta como nada/transparente. NO es (solo) un problema de
  referencia cross-SVG: incluso la ligne del hero —que comparte el `<defs>` en su
  mismo SVG— sale invisible por la misma razón. El riesgo de cross-SVG que avisó
  el architect existe además, pero la causa primaria es la geometría + units del
  gradiente.
- Corrección aplicada: NINGUNA todavía — veredicto product ❌, vuelve a architect/
  coder. Direcciones de fix (a decidir en el plan): (a) `gradientUnits="userSpaceOnUse"`
  con `x1/x2/y1/y2` en coordenadas del viewBox (p.ej. `x1="0" x2="1000"`), que no
  depende del bbox; y/o (b) dar altura no nula al área pintada; y/o (c) un `<defs>`
  propio en cada SVG divisor para no depender de la referencia del hero. Debe
  RE-VERIFICARSE en navegador muestreando píxeles, no solo computed style.
- Cómo prevenirlo (→ LESSONS.md PRD-003): un gradiente con `objectBoundingBox`
  (default) sobre un path de altura o anchura cero (líneas rectas) no pinta;
  usar `gradientUnits="userSpaceOnUse"`. Y un `stroke: url(#id)` con computed
  style correcto NO prueba que se pinte: verificar el color REAL del píxel en
  navegador (PRD-002).

### [PRD-004] Título SEO imprime el slug crudo del tipo en vez del nombre legible — 2026-06-23 (cluster buscador hero)
- Síntoma: al filtrar por tipo sin texto ni ciudad, el `<title>` y `<h1>` de la
  página de resultados usan `$types[0]` literal — el SLUG de BD. Verificado en
  navegador: `…/search?type=location-de-velo` → `<title>Séjours et activités —
  location-de-velo | Canal du Midi</title>`; `…/search?type=nautique` →
  `…— nautique`. El usuario seleccionó "Location de vélo" / "Le Canal en Bateau"
  en el select, pero el título le devuelve "location-de-velo" / "nautique":
  jerga interna con guiones, no francés legible. Visible además en navegador
  (no solo en el HTML): es el título de la pestaña y el encabezado de la página.
- Causa raíz: `PageController.php:227` hace `$seoTitle = "Séjours et activités — "
  . $types[0] . " | Canal du Midi"`. `$types` viene de `$_GET['type']` saneado:
  son slugs, no nombres. El controlador YA carga `$categories =
  $repository->getCategories()` (L213) y resuelve `$categoryIds`, así que tiene a
  mano el mapa slug→name para traducir, pero no lo usa en el título.
- Corrección aplicada: NINGUNA todavía — vuelve a architect/coder. Dirección de
  fix: construir un índice `slug => name` desde `$categories` (igual que home hace
  con `$catsBySlug`) y usar el `name` del primer tipo seleccionado en el título;
  fallback al slug solo si no hay match. Re-verificar en navegador los 4 ramos del
  título. (Bonus de coherencia: si hay >1 tipo, el título solo nombra el primero;
  considerar "{name} et autres" o un texto genérico.)
- Cómo prevenirlo (→ LESSONS.md PRD-004): nunca imprimir un slug/clave técnica en
  texto de cara al usuario; mapear siempre a la etiqueta traducida (`name`) que ya
  se muestra en el `<select>`. El select y el título deben hablar el mismo idioma.

### [SEC-008] htmlspecialchars() sin ENT_QUOTES en múltiples puntos de EmailTemplates.php — 2026-06-24 (cluster BUG-001/002/003)
- Síntoma: `EmailTemplates.php` contiene 19 llamadas a `htmlspecialchars()` sin `ENT_QUOTES, 'UTF-8'`. Afecta a datos que provienen de BD o de inputs validados (nombre del cliente, email, teléfono, referencia, textos de slots). Aunque ninguno llega directamente de `$_POST`/`$_GET` sin validar, y en PHP 8.2 `ENT_QUOTES` es el default, la violación sistemática de SEC-006 es una deuda de convención que debe corregirse. Los puntos más relevantes son los atributos `href="mailto:…"` y `href="tel:…"` (L241, L249), donde el valor está dentro de un atributo HTML delimitado por comillas dobles.
- Causa raíz: el archivo `EmailTemplates.php` pre-existía a la lección SEC-006 y sus templates no fueron actualizados.
- Corrección aplicada: NINGUNA todavía — mejora de calidad, no bloqueante (PHP 8.2 aplica `ENT_QUOTES` por defecto). Se registra como deuda pendiente.
- Cómo prevenirlo (→ LESSONS.md SEC-008): al abrir cualquier archivo PHP con salida HTML, ejecutar `grep htmlspecialchars( | grep -v ENT_QUOTES` antes de cerrar la tarea. Si el archivo no estaba en el scope del cluster, registrar la deuda en TASKS.md.

### [SEC-009] iframe de tercero (Calaméo) sin atributo sandbox — 2026-06-24 (cluster BUG-003)
- Síntoma: `home.php:323-330` carga `https://www.calameo.com/read/003331405edc35288442a` en un `<iframe>` con `allowfullscreen` y `referrerpolicy="no-referrer"` pero sin `sandbox`. Sin `sandbox`, el iframe de Calaméo puede acceder a `window.top`, navegar el frame principal, o abrir popups. El `src` es literal hardcodeado (sin interpolación de usuario), lo que limita el riesgo, pero la ausencia de contención es una debilidad de defensa en profundidad.
- Causa raíz: omisión del atributo `sandbox`; la preocupación funcional (Calaméo necesita scripts para renderizar el visor) llevó a no ponerlo.
- Corrección aplicada: NINGUNA todavía — mejora de calidad. Añadir `sandbox="allow-scripts allow-same-origin allow-popups allow-forms"` para permitir el visor sin dejar al iframe navegar el top frame. Verificar que el visor de Calaméo funcione con ese sandbox antes de desplegar.
- Cómo prevenirlo (→ LESSONS.md SEC-009): todo `<iframe>` de tercero debe llevar `sandbox` con los permisos mínimos. `allow-scripts allow-same-origin` es el mínimo para iframes de visualización; añadir `allow-popups` solo si el visor necesita abrir ventanas.

### [PRD-005] Filtro "Le Canal en Bateau" (nautique) devuelve mayoría écluses/ports, no barcos — 2026-06-23 (cluster buscador hero)
- Síntoma: seleccionar Type="Le Canal en Bateau" (slug `nautique`) en el hero y
  buscar devuelve 136 de 253 listings (54% del catálogo). Verificado en navegador:
  la lista está dominada por "Écluse Bayard", "Écluse d'Argelliers", "Écluse
  d'Argens"… El turista que quiere un paseo/croisière en barco recibe sobre todo
  esclusas y puertos. Conteo real por categoría hija de `nautique` (id 10):
  ecluses=72, ports=21, croisiere-bateau=5, location-bateau=10,
  location-canoe-kayak=1. Es decir 93/136 (68%) son infraestructura NO reservable.
- Causa raíz: `resolveCategoryIdsForSearch()` expande la categoría padre `nautique`
  a TODAS sus hijas vía el recorrido `childrenByParent` (PageController L1157-1170).
  Entre las hijas están `ecluses` y `ports`, que en este dataset son POIs de
  patrimonio/infraestructura del canal, no servicios turísticos de barco. La
  expansión jerárquica es correcta en abstracto, pero el contenido de esas dos
  ramas no casa con la expectativa del label "Le Canal en Bateau".
- Corrección aplicada: NINGUNA todavía — decisión de producto. Direcciones: (a)
  excluir `ecluses` y `ports` de la expansión cuando el tipo elegido es el genérico
  `nautique` desde el hero (lista de slugs no-reservables a excluir); (b) o apuntar
  el value del option "Le Canal en Bateau" a las hijas reservables (croisiere-bateau
  + location-bateau + location-de-canoe-kayak) en vez del padre `nautique`; (c) o
  separar écluses/ports a su propia entrada del select ("Écluses & patrimoine") para
  que sea una elección consciente del usuario. Re-verificar conteo y muestra en
  navegador.
- Cómo prevenirlo (→ LESSONS.md PRD-005): la expansión padre→hijos de categorías es
  correcta a nivel de datos pero NO garantiza coherencia de UX: una categoría padre
  puede mezclar servicios reservables con POIs de infraestructura. Antes de exponer
  un filtro al usuario, contar y MIRAR la muestra real de resultados en navegador
  (PRD-002) y juzgar si responde a lo que el label promete, no solo si "filtra algo".

### [SEC-010] iframe de video de BD renderizado sin validar el host (strip_tags) — 2026-06-24 (TASK-001)
- Síntoma: `service_detail.php:299` usaba `strip_tags((string)$video, '<iframe>')` para emitir el contenido del campo `videos` de BD. El `strip_tags` elimina todo excepto el `<iframe>`, pero preserva TODOS sus atributos incluyendo `src`. Un dato corrompido o editado por un administrador CMS puede contener `<iframe src="https://attacker.com/phishing">`, que se renderiza tal cual en la ficha pública del servicio: iframe de host arbitrario visible para cualquier visitante.
- Causa raíz: confianza implícita en los datos de BD. El campo `videos` se almacena como HTML crudo (import WordPress/Pimcore). `strip_tags` no es un sanitizador de atributos: permite cualquier `src`, `onload`, etc. que el tag permitido lleve.
- Corrección aplicada: reemplazado por un extractor seguro en `service_detail.php:296-318`: extrae el `src` del primer `<iframe>` con `preg_match`, valida el host contra una allowlist (`youtube.com`, `youtu.be`, `vimeo.com` y sus variantes `www.`/`player.`), rechaza (skip) cualquier host no listado, y reconstruye el iframe con `htmlspecialchars($iframeSrc, ENT_QUOTES, 'UTF-8')` + `sandbox="allow-scripts allow-same-origin allow-presentation"`. `php -l` OK.
- Cómo prevenirlo (→ LESSONS.md SEC-010): nunca emitir un `<iframe>` cuyo `src` viene de BD sin validar el host contra una allowlist explícita. `strip_tags('<iframe>')` NO es seguro para este caso: preserva el atributo `src` íntegro. Reconstruir siempre el iframe con los atributos controlados + sandbox.

### [SEC-011] IDs numéricos de BD echados sin cast (int) en atributos HTML — 2026-06-24 (TASK-001)
- Síntoma: `service_detail.php:481` (`data-sid="<?= $service->id ?>"`) y `service_detail.php:634` (`value="<?= $service->id ?>"`) echaban el ID del servicio sin cast ni escape. `$service->id` está declarado como `?int` (nullable) en el modelo. Aunque la BD lo almacena como INT, el tipo PHP es nullable: un NULL emitiría el atributo vacío y la llamada AJAX de "Voir plus d'avis" haría `?sid=` (sin ID), potencial bypass de la lógica de paginación. Además, sin cast el valor no está acotado en superficie de salida.
- Causa raíz: omisión del cast `(int)` en estos dos puntos, a pesar de existir la lección SEC-005 del proyecto ("IDs numéricos de BD con cast (int) en output"). El coder aplicó SEC-005 en otros puntos del mismo archivo (lat/lng, poi id, reviewCount) pero pasó estos dos por alto.
- Corrección aplicada: `data-sid="<?= (int)$service->id ?>"` (L481) y `value="<?= (int)$service->id ?>"` (L634). `php -l` OK.
- Cómo prevenirlo (→ LESSONS.md SEC-005): al cerrar cualquier tarea que toque una vista con IDs de modelo, hacer `grep "\$[a-z]*->id\b" | grep -v "(int)"` sobre el archivo para localizar todos los ecos de IDs sin cast.

### [PRD-006] La meta de las tour-cards imprime el slug técnico del tipo (`prestataires-touristiques`) — 2026-06-24 (verificación product cluster BUG-001/002/003)
- Síntoma: las 4 `.tour-card` de la home (BUG-001, ahora con experiencias reales)
  muestran bajo el título una etiqueta de meta `prestataires-touristiques`.
  Verificado en navegador (captura móvil PROD-mfull-b.png y desktop): debajo de
  "À L'ABORDAGE MOUSSAILLON !", "CAMPING DE MONTOLIEU", "CRIS'BOAT", "CROISIERES DU
  MIDI HOMPS" aparece el texto literal `prestataires-touristiques` — el valor crudo
  de `$tour->type`, que en este dataset es `prestataires-touristiques` para 253/253
  listings. Es jerga interna con guiones, no francés legible: instancia de PRD-004
  reaparecida en la HOME (no solo en el título de search ya corregido).
- Causa raíz: la vista `home.php` (~L250) imprime
  `$tour->translations['tag'] ?? 'Durée flexible'`; cuando `tag` está vacío debería
  caer al fallback "Durée flexible", pero el render real muestra el slug de tipo, lo
  que sugiere que `tag` se está poblando con el `type` crudo o que el meta toma otra
  fuente. En cualquier caso el usuario ve un slug. El fallback "Durée flexible" no
  aporta info real de la experiencia y el slug es peor.
- Corrección aplicada: NINGUNA — observación de UX nueva, NO bloqueante (el cluster
  cumple todos sus criterios de éxito; BUG-001 pedía "experiencias reales con título
  e imagen", logrado). Registrada como seguimiento. Dirección de fix: en la
  tour-card, mapear el tipo/categoría a su `name` traducido (reusar el patrón
  `slug→name` ya disponible vía `$allCatsRaw`/`$catsBySlug` en el case home), o
  mostrar la categoría principal legible (location-de-bateau → "Location de bateau",
  croisiere-bateau → "Croisière en bateau") en vez del `type` o del fallback genérico.
- Cómo prevenirlo (→ LESSONS.md PRD-004/PRD-006): PRD-004 no se limita al `<title>` de
  search — auditar TODA superficie de cara al usuario (cards, meta, badges, breadcrumbs)
  por slugs crudos. Verificar en navegador el texto REAL renderizado de cada componente
  nuevo o tocado, no solo que "carga".

### [PRD-007] Mapa de la ficha de POI nunca se renderiza (Leaflet no se carga para page==='poi') — 2026-06-24 (verificación product TASK-001/002)
- Síntoma: en `/fr/poi/{id}` el `<div id="map" class="map-container-small" data-lat data-lng>`
  queda VACÍO: no hay `.leaflet-container`, `innerHTML` del div = 0, `window.L === undefined`.
  Verificado en navegador (Playwright) sobre `/fr/poi/3` (Écluse de Bayard): `data-lat="43.601"`
  `data-lng="1.455"` (floats limpios, el cast `(float)` está bien), pero ningún `<script>` de
  Leaflet presente en la página → el mapa nunca inicializa. El turista ve un hueco gris donde
  debería estar la ubicación del punto de interés. 0 errores de consola (falla silenciosa).
- Causa raíz: `layout/footer.php:32` solo carga el JS/CSS de Leaflet cuando
  `$page === 'service' || $page === 'fiche'` (L32) o `$page === 'search'` (L44). NO existe
  una rama para `$page === 'poi'`, aunque `poi_detail.php:27-30` SÍ emite un contenedor de
  mapa con sus `data-*`. Bug pre-existente de gating en el footer, anterior a TASK-001 (el
  commit que tocó footer.php fue `b554b6f New features`, no esta tanda). TASK-001 solo añadió
  escape/cast en `poi_detail.php` y NO introdujo ni agravó este fallo: el `(float)` del lat/lng
  es correcto, el problema es la ausencia del `<script>` de Leaflet.
- Corrección aplicada: NINGUNA — fuera del scope de TASK-001/TASK-002 (escape + prompts). Se
  registra como seguimiento. Dirección de fix: añadir `poi` a la condición de carga de Leaflet
  en `footer.php` (CSS + JS con su SRI ya existente, SEC-004) y asegurar que el init JS del POI
  lea `#map[data-lat][data-lng]` (mismo patrón que la ficha de servicio). Re-verificar en
  navegador que el marcador aparece en la lat/lng del POI.
- Cómo prevenirlo (→ LESSONS.md PRD-007): si una vista emite un contenedor de mapa con `data-*`,
  verificar EN NAVEGADOR que la librería del mapa se carga para ESE `$page` concreto (el gating
  por página del footer es fácil de olvidar al añadir una ruta nueva). Un `<div id="map">` con
  datos correctos NO implica un mapa: comprobar `.leaflet-container` y el marcador reales
  (refuerza PRD-002).
