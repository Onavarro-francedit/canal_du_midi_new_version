# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE 2026-10-02 — TASK-046 FAQ accueil-2026 ⚠️ (listo con mejoras menores, privada) · Siguiente: decidir

**Agente activo al cerrar:** product (pipeline architect → coder → security → product cerrado). **Handoff pendiente:** ninguno.
**Dónde quedamos:** la FAQ de la home 2026 (página 18500, PRIVADA) tiene 8 preguntas alineadas con Search Console
(precio sin cifras, permis, días, horaires des écluses, péage, vélo, longitud, carte); desplegada y verificada a 1440/390.
**Git:** commit local b2642e1 en `feat/wp-planner-2026` (sin push). Sin commitear, ajenos: `header.php`, `styles.css`,
`.claude/skills/seo-geo/`, `.playwright-mcp/` + docs de este cierre (TASKS, LESSONS, ERROR_LOG, SESSION).

**Archivos de TASK-046:** `wp-plugin/canal-home/includes/content.php` (CANAL_HOME_FAQ), `wp-plugin/tests/smoke-render.php`.

**Pendiente del usuario:** smoke-render y smoke-seo (requieren desactivar el plugin; paso agrupado).

**Decisiones que no están en ARCHITECTURE.md:**
- Precios de alquiler: SIN cifras en la home (decisión del usuario 2026-10-02).
- Contenido fechado (horarios de écluses, año del plan) → revisión anual en enero (TASK-047, PRD-014).
- `wp-plugin/llms-full.txt` sigue con la FAQ antigua (« ouvrages »): regenerar CON el .md de categorías al publicar (PRD-013).

**Próxima acción:**
```
Opcional: /agent coder TASK-047 (copy FAQ: devis + écluses « en été » + cierres + año).
Publicar home + carte + ficha (TASK-028 / TASK-029b / TASK-030b) SOLO con orden explícita; incluir llms-full.txt (PRD-013).
```
