# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## 2026-10-02 — TASK-046 FAQ accueil-2026 (coder) · Handoff pendiente: security

**Dónde quedamos:** FAQ de la home con 8 preguntas (alquiler, permiso, días, horarios de écluses, péage, vélo, longitud, mapa); « ouvrages » eliminada. `remote.sh test` Todo OK y deploy hecho; commit local sin push.
**Archivos:** `wp-plugin/canal-home/includes/content.php` (CANAL_HOME_FAQ + docblock), `wp-plugin/tests/smoke-render.php` (+1 aserción).
**Pendiente (sesión principal):** smoke-render/smoke-seo con plugin desactivado, verificación visual, llms-full.txt con categorías al publicar (PRD-013).
**Próxima acción:** `/agent security` sobre TASK-046.

---

## CIERRE 2026-10-02 — TASK-045 accueil-2026 « carte / plan / tracé » ✅ (privada) · Siguiente: decidir

**Agente activo al cerrar:** product (pipeline architect → coder → security → product cerrado). **Handoff pendiente:** ninguno.
**Dónde quedamos:** la home 2026 (página 18500, PRIVADA) cubre la intención de búsqueda de mapa (title, meta, H1,
#etapes « Quel est le tracé… » + CTA, FAQ n.º 6, JSON-LD Map); desplegada y verificada a 1440/390/320.
**Git:** commits locales dec61a1, f561d01 en `feat/wp-planner-2026` (sin push). Sin commitear, ajenos: `header.php`,
`styles.css`, `.claude/skills/seo-geo/`, `.playwright-mcp/`, `docs/auditoria-accueil-2026-2026-10-02.md` + docs de este cierre.

**Archivos (wp-plugin/):** `canal-home/includes/seo.php`, `template-home.php`, `includes/content.php`
(`CANAL_HOME_PLAN_PDF`, FAQ 6), `build/home-extra.css` (+ `assets/home.css`), `tests/smoke-render.php`, `tests/smoke-seo.php`.

**Decisiones que no están en ARCHITECTURE.md:**
- `wp-plugin/llms-full.txt` es derivado de `content.php`: al publicar, regenerarlo CON el .md de categorías
  (`php wp-plugin/build/build-llms-full.php <categorias.md>`; sin argumento vacía la lista) y subirlo (PRD-013).
- TASK-044 (planificateur) sigue en la rama; « Planifier mon voyage » de la cabecera ya apunta a él.

**Próxima acción:**
```
Publicar home + carte + ficha (TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita;
incluir: regenerar y subir llms-full.txt (PRD-013). Opcional: push de la rama.
```
