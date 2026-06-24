# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## PRD-007 / BUG-014 — Cargar Leaflet en la ficha de POI + fix ancla #newsletter — IMPLEMENTADO por coder — 2026-06-24

**Agente activo al cerrar:** coder.
**Handoff pendiente:** coder → security (ver abajo).

**Dónde quedamos:** implementación completa en 3 archivos; `php -l` OK; pendiente
verificación en navegador por el agente security (`.leaflet-container` en `/fr/poi/3`).

**Archivos modificados en esta sesión:**
- `src/Infrastructure/Views/layout/header.php` — añadido `<link>` Leaflet CSS 1.9.4
  al bloque `$page === 'poi'` (tras `poi_detail.css`); ancla `#newsletter` → `BASE_URL.$lang/home#plan`.
- `src/Infrastructure/Views/layout/footer.php` — añadido bloque `$page === 'poi'` entre
  `search` y `home`: Leaflet JS 1.9.4 (SRI idéntico al auditado) + `map.js`.
- `src/Infrastructure/Views/home.php` — ancla `href="#newsletter"` del botón "Voir les offres" → `href="#plan"`.
- `docs/TASKS.md` — PRD-007/BUG-014 movido de 🔴 a 🟢 (pendiente verif. navegador).
- `docs/SESSION.md` — este archivo.

**Próxima acción:**
```
/agent security — verificar PRD-007/BUG-014: mapa Leaflet en /fr/poi/3 (.leaflet-container + marcador), anclas corregidas, regresión service/search, 0 errores integrity.
```

---

## TASK-001 + TASK-002 — Hardening escape de salida + prompts OpenAI — PIPELINE CERRADO ✅ product ⚠️ — 2026-06-24

**Agente activo al cerrar:** product (cierre de pipeline).
**Handoff pendiente:** ninguno. No hay tarea en curso.

**Veredicto product:** ⚠️ listo con mejoras menores. Pipeline completo
(architect → coder → security → product). Verificado en navegador con render real
y muestra (PRD-002, Playwright headless). Los criterios de éxito del handoff
security→product están CUMPLIDOS; las únicas excepciones son por realidad de datos
(0 vídeos, 0 servicios con paginación de reviews) o por un bug pre-existente ajeno
al scope (mapa de POI).

**Qué quedó verificado (evidencia de navegador):**
- Ficha `/fr/fiche/a-labordage-moussaillon`: HTTP 200; título/h1 `À L'ABORDAGE
  MOUSSAILLON !` con acento + apóstrofe correctos (0 mojibake, 0 doble-escape);
  mapa Leaflet con **1 marcador + 12 tiles** (el cast `(float)` de lat/lng NO rompió
  el marcador); galería, reviews, formulario de reserva + modal de doble
  confirmación; **0 errores de consola**.
- `name="service_id" value="246"` entero correcto; `data-sid` usa `(int)$service->id`.
- SEC-010 vídeo: allowlist correcta; **0/253 servicios tienen vídeo** → no se
  renderiza iframe (realidad de datos, no bug).
- "Voir plus d'avis": botón condicional a `reviewCount > mostrados`; **ningún
  servicio del catálogo lo dispara** → no observado en vivo, atributo `(int)` OK
  estático.
- POI `/fr/poi/3`: HTTP 200; título/h1 `Écluse de Bayard (Gare)` + alts con acentos
  intactos (0 mojibake/doble-escape) → escape de poi_detail.php correcto.
- Vacation planner `/fr/vacation-planner`: HTTP 200, asistente de 4 pasos, 0
  errores de consola.
- Vacation PDF: referencia inválida → **404 + "introuvable"** (manejo elegante).
  No se probó un PDF real (sin referencia de prueba; lectura de BD bloqueada por la
  restricción "no tocar BD"). Escape de la vista confirmado estático (16
  `htmlspecialchars`, todas ENT_QUOTES).
- Auditoría final: `grep htmlspecialchars | grep -v ENT_QUOTES` → **0** en los 6
  archivos de TASK-001. Sin regresiones de render.

**Capturas (scratchpad):** `PROD-service.png`, `PROD-poi.png`, `PROD-vacplanner.png`.

**Seguimiento NUEVO abierto (🟡, no bloqueante):**
- **PRD-007 / BUG-014** — El mapa de la ficha de POI nunca renderiza:
  `poi_detail.php` emite `<div id="map">` con `data-lat/lng` correctos, pero
  `footer.php:32` solo carga Leaflet para `$page`=`service`/`fiche`/`search`/`home`;
  falta la rama `poi`. `window.L` undefined, hueco gris, 0 errores de consola. Bug
  PRE-EXISTENTE de gating (commit `b554b6f`), NO regresión de TASK-001. Fix: añadir
  `poi` a la carga de Leaflet en `footer.php` (CSS+JS con SRI ya existente, SEC-004)
  + init `#map[data-lat][data-lng]`. Ver LESSONS.md PRD-007 / ERROR_LOG.md.

**Próxima acción candidata:**
```
/agent architect — corregir PRD-007/BUG-014 (cargar Leaflet en la ficha de POI), o
/agent architect — Incremento 3 visual: TASK-012 "Les étapes du canal"
   (Toulouse → Castelnaudary → Carcassonne → Béziers → Étang de Thau)
```
