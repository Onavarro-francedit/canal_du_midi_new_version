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
