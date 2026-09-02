# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## Task 12 — Verificación end-to-end del backoffice de fichas — FEATURE CERRADA ✅ (con 1 hallazgo no bloqueante) — 2026-09-02

**Agente activo al cerrar:** verificación end-to-end (última de las 12 tareas del
plan `docs/superpowers/plans/2026-09-02-backoffice-fichas.md`).
**Handoff pendiente:** ninguno. Las 12 tareas del backoffice están completas
(Tasks 1-11 cada una con su propio commit + review, ver `.superpowers/sdd/
progress.md`; Task 12 = esta verificación conjunta, sin código nuevo).

**Qué se hizo:** recorrido de las 8 piezas del checklist del spec CONTRA el
servidor local real (`http://localhost/canal_du_midi`), con cuentas throwaway
creadas y borradas por SQL (nunca se tocó `admin@canaldumidi.local`) sobre la
fiche id=1 (respaldada completa antes de tocarla, restaurada byte a byte al
terminar). Las 8 piezas PASARON:
1. Scoping owner (`?id=` ignorado) ✅ — 2. Editar texto/contacto y verlo en
`/fiche/{slug}` ✅ — 3. Subir portada+galería, archivo físico + BD ✅ (ver
hallazgo abajo) — 4. Cambiar categorías → contador de `/search?type=` cambia
✅ (16→17 en `nautique`) — 5. CSRF (POST sin token → 403) ✅ — 6. Rate-limit (IP,
sobrevive a cookies nuevas) ✅ — 7. Upload `.php` renombrado `.jpg` (MIME real
vía `finfo`) → rechazado ✅ — 8. Admin resetea password de owner → owner entra
con la nueva ✅. Consola del navegador (Playwright) revisada en las 4 pantallas
del checklist (login, backoffice owner, backoffice admin, backoffice?id=X).

**Hallazgo nuevo (BUG-013, 🟡, no bloqueante):** las fotos de galería subidas
desde el backoffice dan 404 al mostrarse (tanto en el preview de edición como en
`/fiche/{slug}` público) porque `rowToService()` normaliza `cover` con
`normalizeMediaUrl()` pero NO hace lo mismo con el array `gallery` — las rutas
relativas nuevas (`public/uploads/listings/...`) se rompen bajo cualquier URL con
prefijo de idioma (`/fr/...`). La portada sí se ve bien (sí pasa por
`normalizeMediaUrl()`). Fix candidato de una línea en el mismo sitio. Detalle
completo en `docs/TASKS.md` 🟡 y en `.superpowers/sdd/task-12-report.md`.

**Limitaciones ya conocidas y aceptadas (Tasks 9-10, re-documentadas aquí para no
perderlas):** upload huérfano si portada/galería se sube con éxito pero
título/email fallan validación después en la misma request; fallo silencioso de
upload a nivel PHP (`upload_max_filesize`) indistinguible de "no elegí archivo";
campos de password en `admin_dashboard.php` con `type="text"` (credenciales
temporales visibles en pantalla); este entorno local necesitó
`chmod 777 public/uploads/listings/` (fix de entorno, no de código — Apache corre
como `daemon`).

**Limpieza de artefactos de test:** confirmada por consulta a BD — `users` → 1
fila (solo el admin real), fiche 1 con `owner_user_id` NULL / `claimed=0` /
título-ciudad-portada restaurados al original, categorías de fiche 1 restauradas
a `22,45,47`, `login_attempts` → 0 filas, `public/uploads/listings/` vacío
(archivos huérfanos de test borrados vía script PHP temporal servido por Apache,
ya que quedan con dueño `daemon` y el shell local no puede borrarlos
directamente — mismo fix de entorno de arriba; el script temporal fue borrado
tras ejecutarse, no quedó en el repo).

**Archivos:** ninguno de código (verificación pura). `docs/TASKS.md` y
`docs/SESSION.md` (este cierre). Reporte completo en
`.superpowers/sdd/task-12-report.md`.

**Próxima acción candidata:**
```
/agent architect — BUG-013: normalizar $gallery con normalizeMediaUrl() en
   rowToService() (MySQLServiceRepository.php) para que los thumbnails de galería
   subidos desde el backoffice se vean tanto en /backoffice como en /fiche/{slug}.
/agent architect — TASK-025/PRD-009: localizar el chrome del vacation planner.
```

---

## TASK-019 — Fechas estructuradas y obligatorias en el planificateur IA — PIPELINE CERRADO ✅ · product ⚠️ listo con mejoras menores — 2026-07-06

**Agente activo al cerrar:** product (cierre de pipeline).
**Handoff pendiente:** ninguno. Pipeline COMPLETO: architect ✅ → coder ✅ →
security ⚠️ → product ⚠️ (esta sesión). No hay tarea en curso.

**Veredicto product:** ⚠️ listo con mejoras menores. Los 6 criterios de éxito
del architect CUMPLIDOS y el objetivo de NEGOCIO se cumple: las fechas son
obligatorias (cliente + servidor autoritativo) y el lead al prestador ya NO sale
"dates à confirmer" — sale con fechas firmes en formato francés inequívoco
("du vendredi 4 septembre 2026 au lundi 7 septembre 2026"). Verificado EN
NAVEGADOR el flujo **español** real (Playwright, PRD-002), no solo el francés que
ya había cubierto security:
- Prompt ES "Viajamos 4 días en pareja a principios de septiembre…" → plan
  generado (0 errores de consola), paso 3 prerrellena `#f-checkin=2026-09-07` /
  `#f-checkout=2026-09-10` (approx), `min`=hoy 2026-07-06.
- Hint en español correcto: "Fechas estimadas a partir de su solicitud —
  ajústelas si es necesario."
- Submit con fechas vacías → bloqueado con "Indique sus fechas de llegada y
  salida." (0 avance a screen-confirmation).
- Strings de fecha idiomáticos y correctos en fr/es/en (ES verificado en vivo;
  EN por cableado idéntico `currentLang()`→`lang="en"` + inspección de strings).
- Email al prestador: `frDate()` produce día-semana + día + mes en letra + año
  (sin ambigüedad día/mes); el viajero usa `<input type=date>` nativo. Coherente
  para ambos lados.

**Único seguimiento nuevo (no bloqueante) — PRD-009 / TASK-025 (🟡):** el planner
mezcla idiomas de cara al usuario. TASK-019 localizó bien SUS strings (hint +
errores de fecha), pero el resto de `vacation_planner.php` (labels "Arrivée
prévue"/"Départ prévu"/"Vos coordonnées", intro, botón "Envoyer ma demande
d'intérêt", confirmación) y los OTROS errores JS del mismo formulario
(nombre/email vacío → "Veuillez renseigner les champs obligatoires.", fallo de
envío, prompt vacío) siguen hardcodeados en francés. Verificado en navegador: en
el MISMO formulario en `/es/`, fechas vacías → error en español, nombre vacío →
error en francés. Pre-existente (NO regresión de TASK-019). Registrado en
LESSONS.md (PRD-009) y ERROR_LOG.md; abierto como TASK-025 en 🟡.

**Nota de protocolo:** CLAUDE.md "Estado actual" NO se actualizó porque el
veredicto es ⚠️ (el protocolo solo lo actualiza si es ✅). Sigue mostrando
TASK-001/002 como último bloque; conviene refrescarlo en un próximo cierre ✅.

**Otras observaciones abiertas (contexto, no bloqueantes):**
- **SEC-014** (🟡) — `ai-plan-generate`/`ai-plan-submit` sin CSRF ni rate-limit
  (pre-existente).
- **TASK-020** (🟡) — contacto obligatorio (teléfono O email) + `autocomplete`
  seguro en el email del viajero.
- Robustez: la distinción "duración vs. fecha real" vive solo en el prompt de la
  IA, sin test automatizado (podría degradarse si cambia el modelo).
- Cache-busting (`?v=hash`) en `vacation-planner.js`/`.css` (mejora de deploy).

**Archivos de TASK-019 (ya en git, sin cambios de código en esta sesión de
product):** `src/Infrastructure/Services/VacationPlannerService.php`,
`public/assets/js/vacation-planner.js`,
`src/Infrastructure/Views/vacation_planner.php`,
`public/assets/css/vacation-planner.css`,
`src/Infrastructure/Controllers/PageController.php`.

**Próxima acción candidata:**
```
/agent architect — TASK-025/PRD-009: localizar el chrome del planner + los strings
   JS restantes a fr/es/en (o decidir que el planner es monolingüe fr y no exponerlo
   en /es//en). Verificar en navegador los 3 idiomas.
/agent architect — TASK-020: contacto obligatorio (téléphone O email) + autocomplete seguro.
/agent architect — TASK-021 (estratégica): "Répondre via Canal du Midi" (leads con estado).
```
