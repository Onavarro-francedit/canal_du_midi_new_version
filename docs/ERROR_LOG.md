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
