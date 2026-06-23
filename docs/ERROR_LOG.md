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
