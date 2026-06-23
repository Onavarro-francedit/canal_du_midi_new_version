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

- **SEC-003: CDN de terceros sin SRI.** Todo `<script>`/`<link>` que cargue un
  recurso de un CDN externo (jsDelivr, unpkg, etc.) debe llevar **versión fija**
  (nunca `@latest`) + `integrity="sha384-…"` (SRI) + `crossorigin="anonymous"`.
  Sin SRI, un CDN comprometido o un MITM ejecuta código arbitrario en la página
  con plenos privilegios. Detectado en TASK-008 (GSAP/ScrollTrigger en home),
  corregido en `footer.php`. Deuda relacionada aún abierta: los `<script>` de
  Leaflet/markercluster (unpkg) en service/search siguen sin SRI.

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
