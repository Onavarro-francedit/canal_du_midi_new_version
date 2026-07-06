# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## TASK-018 — Migración IA OpenAI → Claude Sonnet 4.6 — CODER COMPLETO ✅ — 2026-07-06

**Agente activo al cerrar:** coder.
**Handoff pendiente:** coder → security (ver handoff al final).

**Qué se hizo:**
- SDK `anthropic-ai/sdk` v0.36.0 instalado vía Composer (5 paquetes nuevos).
- `config.php`: `OPENAI_*` → `ANTHROPIC_API_KEY`/`ANTHROPIC_MODEL` (default `claude-sonnet-4-6`).
- `.env`: líneas `ANTHROPIC_API_KEY=sk-ant-PEGA_TU_CLAVE_AQUI` / `ANTHROPIC_MODEL=claude-sonnet-4-6` añadidas. **El usuario debe pegar su clave real.**
- `SanitizesPrompts.php` (nuevo trait): `sanitizeUserPrompt()` compartido; test CLI 4/4 PASS.
- `ClaudeAIService.php` (nuevo): implementa `AIServiceInterface`; usa SDK + prompt caching + structured outputs; fallback a `SmartAIService`.
- `VacationPlannerService.php`: reescritura parcial (encabezado + `generatePlan()`); `buildCatalog`/`hydrate`/`fallback` intactos.
- `PageController.php:577`: `new OpenAIService()` → `new ClaudeAIService()`.
- `OpenAIService.php`: eliminado (git rm).
- `scripts/test_sanitize.php` y `scripts/smoke_claude_cache.php`: nuevos scripts de verificación.

**Verificado (sin API key real):**
- `php -l` OK en todos los archivos nuevos/modificados.
- `grep -rniE "openai|gpt-|api\.openai" src/` → 0 coincidencias.
- `/fr/search` HTTP 200, 0 errores de consola (Playwright).
- `/fr/vacation-planner` HTTP 200, 0 errores de consola (Playwright).

**Pendiente (requiere usuario):**
- Insertar la `ANTHROPIC_API_KEY` real en `.env` y ejecutar `php scripts/smoke_claude_cache.php`.
- Verificar con Playwright búsqueda IA real y plan real una vez la clave esté activa.

**Archivos modificados en esta sesión:**
- `composer.json` — añade `anthropic-ai/sdk ^0.36.0`
- `src/config/config.php` — `OPENAI_*` → `ANTHROPIC_*`
- `.env` — añade líneas `ANTHROPIC_*` (no en git)
- `src/Infrastructure/Services/SanitizesPrompts.php` — nuevo trait de sanitización
- `src/Infrastructure/Services/ClaudeAIService.php` — nuevo servicio Claude (búsqueda IA)
- `src/Infrastructure/Services/VacationPlannerService.php` — reescritura parcial a Claude
- `src/Infrastructure/Controllers/PageController.php` — línea 577: ClaudeAIService
- `src/Infrastructure/Services/OpenAIService.php` — ELIMINADO
- `scripts/test_sanitize.php` — test CLI del trait (4/4 PASS)
- `scripts/smoke_claude_cache.php` — smoke de prompt caching (requiere API key real)

**Próxima acción:**
```
# 1. El usuario pega su ANTHROPIC_API_KEY real en .env
# 2. Ejecutar smoke de caching:
ANTHROPIC_API_KEY=sk-ant-... php scripts/smoke_claude_cache.php
# 3. Pasar handoff al agente security:
/agent security [handoff coder → security de TASK-018]
```

---

## SEC-001 + SEC-012 — CERRADOS ✅ — 2026-06-24 — BACKLOG AUTÓNOMO AGOTADO

**Pipeline:** architect → coder → security (⚠️ aprobado, verificado en navegador).

**SEC-001:** `src/Config/Database.php` — las 4 credenciales PDO hardcodeadas →
`$_ENV['DB_HOST'|'DB_NAME'|'DB_USER'|'DB_PASS'] ?? fallback` (mismo valor local, XAMPP
sigue conectando). DSN como `$dsn`, charset utf8mb4 fijo. Sin tocar BD ni `.env`.

**SEC-012 (nuevo, corregido por security):** el `catch(PDOException)` hacía
`die("…".$e->getMessage())` → fugaba host/usuario/BD al navegador. Corregido:
`error_log($e->getMessage())` + `die("Erreur de connexion à la base de données.")`.

**Verificado (security, Playwright):** home HTTP 200 con datos de BD, ficha HTTP 200,
0 error de conexión visible, `php -l` OK, credenciales solo en fallback `??`, `.env` en
`.gitignore`.

**⛔ BACKLOG AUTÓNOMO AGOTADO.** Todo lo resoluble sin input del usuario y sin tocar la BD
está hecho. PENDIENTE (requiere al usuario): **TASK-005** (stats reales del hero `12K+/48/4.9`
+ contacto real, hoy `tel:+33500000000`/`bonjour@canaldumidi.local`), **TASK-007** (imágenes
locales en vez de Unsplash). EXCLUIDO: **TASK-016b** (toca BD). Deuda menor cosmética:
`.env.example` sección DB con valores locales en vez de placeholders.

**Loop autónomo:** detenido tras agotar el backlog (cron `d090ba17` eliminado). Para
reanudar: re-lanzar `/loop` o pedir una tarea concreta.

---

## PRD-007/BUG-014 + cierre SEC-003 (CSS SRI) — CERRADOS ✅ — 2026-06-24

**Pipeline:** architect → coder → security (⚠️ aprobado, verificado en navegador). El
cierre de SEC-003 (CSS) lo hizo el coordinador directo + verificación navegador.

**PRD-007/BUG-014 (mapa POI):** `footer.php` no cargaba Leaflet para `$page==='poi'` →
hueco gris. Fix (3 archivos, sin BD): Leaflet CSS al bloque poi de `header.php`; bloque
poi en `footer.php` con Leaflet JS (SRI) + `map.js` genérico; anclas muertas
`#newsletter` → `#plan` en home/header. **Verificado (security, Playwright):** `/fr/poi/3`
y `/fr/poi/1` renderizan `.leaflet-container` + marcador con coords reales distintas
(43.601/1.455 y 43.216/2.35), service/fiche/search sin regresión, 0 errores integrity/consola.

**SEC-003 cierre final (CSS SRI):** añadido `integrity` sha384 + `crossorigin="anonymous"`
a los 5 `<link>` CSS de unpkg que faltaban (leaflet.css ×3 en service/search/poi +
MarkerCluster.css + MarkerCluster.Default.css) en `header.php`. Verificado en navegador:
search/poi/fiche con mapa OK, 0 errores de integrity. **Ya no queda NINGÚN recurso CDN
(JS ni CSS) sin SRI en el proyecto.** SEC-003 y SEC-004 cerradas del todo.

**Estado del backlog autónomo:** AGOTADO. Lo único pendiente requiere input del usuario
(TASK-005 stats/contacto reales, TASK-007 imágenes locales) o está excluido por tocar BD
(TASK-016b). BUG-004 ya resuelto (botón quitado). Ver 🟢 en TASKS.md.

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
