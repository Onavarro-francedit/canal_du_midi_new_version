# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

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
