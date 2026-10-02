# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE 2026-10-02 — TASK-044 Planificateur 2026 desplegado en privado y verificado · Siguiente: desplegar 16628df y decidir

**Agente activo al cerrar:** sesión principal (executing-plans + rediseños aprobados con mockup). **Handoff pendiente
de esta tarea:** ninguno (la otra sesión tiene su propio handoff de TASK-050 más abajo).
**Dónde quedamos:** `/planificateur-2026/` (página 18505, privada) funciona de punta a punta en producción; revisión final
hecha (sin críticos) y sus 3 correcciones en el commit 16628df, **pendiente de desplegar** (`remote.sh deploy` + smoke).

**Archivos:** `wp-plugin/canal-home/includes/planner-core.php`, `includes/planner.php`, `template-planner.php`,
`assets/planner.css`, `assets/planner.js` (nuevos); `includes/ai-core.php`, `includes/ai.php` (factorizados),
`canal-home.php`, `includes/header.php` (CTA → planificador); `tests/test-planner-core.php`, `tests/smoke-planner.php`,
`remote.sh`; mockup `docs/mockups/planificateur-2026-plan.html`; spec con la sección « Cambios aprobados… ».

**Decisiones que no están en ARCHITECTURE.md:**
- Correos seguros por defecto: sin `CANAL_PLANNER_LIVE = true` en `canal-ai-config.php` todo va a onavarro@ con
  `[TEST → destinatario]`. Fichas sin e-mail → buzón FE mbauwens@.
- Vista plan (chat izq./plan der.), modal de demanda con Google Maps (URL del tema, carga diferida), IA en el idioma
  del visitante que pregunta antes de proponer. Tarjeta de preguntas con opciones: descartada por el usuario.
- Google Maps no pinta en el Chrome de Claude: verificar el mapa en Chrome normal.
- Menores aplazados de la revisión: ver `.superpowers/sdd/2026-10-01-planificateur-2026/progress.md` (líneas « minor »).

**Próxima acción:**
```
! wp-plugin/remote.sh deploy
wp-plugin/remote.sh run tests/smoke-planner.php     # esperado: Success, incl. « la 2.ª demanda anula el enlace de la 1.ª »
```
Después: fusionar `feat/wp-planner-2026` en `main` (finishing-a-development-branch). Publicar (TASK-044b con 028/029b/030b)
SOLO con orden explícita.

---

## 2026-10-02 — coder TASK-050 implementada y activa en producción · Handoff pendiente: security

**Dónde quedamos:** caché de página WPFC activa en plan-canal-du-midi.com (script `wp-plugin/ops/wpfc-enable.php`, idempotente,
copia `.htaccess.bak-2026-10-02-wpfc`); TTFB ~0,11 s en `/`, `/navigation/`, `/explorer/`, ficha. Verificado por curl (ver TASKS 🔴).
**Archivos:** `wp-plugin/ops/wpfc-enable.php` (nuevo, activación + rollback en docblock) · `wp-plugin/canal-home/includes/fiche-route.php`
(purga `wpfc_clear_all_cache` al cambiar `canal_fiche_public`; comentario DONOTCACHEPAGE) · `docs/TASKS.md`.
**Próxima acción:** `/agent security` TASK-050; luego verificación visual (CMP/pubs en `/` y `/histoire/`, `/explorer/` con listados,
sesión real sin caché, `/accueil-2026/` en ventana pública).

---

## CIERRE 2026-10-02 — TASK-048 rendimiento móvil ⚠️ (listo con mejoras menores, privadas) · Siguiente: decidir

**Agente activo al cerrar:** product (pipeline architect → coder → security → product cerrado). **Handoff pendiente:** ninguno.
**Dónde quedamos:** las páginas 2026 (home, carte, ficha, planificador) usan fuentes autoalojadas, iconos como máscaras SVG,
CSS en línea, GA4 G-R0M81JSWP0 directo diferido a `load` y hero visible desde el primer pintado en móvil (8a); desplegado y
de nuevo PRIVADO. Home móvil 72 → 76–81 (LCP 5,7 → 4,6 s) en DataForSEO; objetivo 2,5 s no alcanzado → TASK-049.

**Git:** commits locales 32c0805, f2ce819, efe2927 en `feat/wp-planner-2026` (sin push). Sin commitear: docs de este cierre
(TASKS, LESSONS PRD-015, ERROR_LOG, SESSION) + ajenos (`header.php`, `styles.css`, `.claude/skills/seo-geo/`, `.playwright-mcp/`).

**Archivos de TASK-048:** `wp-plugin/build/build-css.mjs`, `build/home-extra.css` (8a), `build/package.json`;
`canal-home/assets/icons.css`, `assets/fonts/*`; `canal-home/canal-home.php`; `includes/fiche-core.php` (swap_gtag),
`fiche-route.php`, `seo.php`, `data.php` (tallas de tarjetas, CANAL_HOME_CARD_FALLBACK); `template-home.php`; tests.

**Decisiones que no están en ARCHITECTURE.md:**
- GA4 en páginas 2026: snippet propio (no el UA del tema), cargado tras `load`; `/` sigue con UA. El regex de
  `canal_home_swap_gtag` falla en silencio (deja el UA) si el tema cambia su snippet.
- Entrada animada del hero: solo > 768 px (PRD-015). Iconos nuevos solo en BD → `EXTRA_ICONS` de `build-css.mjs`.

**Próxima acción:**
```
Publicar home + carte + ficha (TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita; incluir llms-full.txt (PRD-013).
Después: TASK-049 (re-medir con caché de página, 3 pasadas, mediana; 8b si LCP móvil > 3,5 s; page_view GA4 en Tiempo real).
```
