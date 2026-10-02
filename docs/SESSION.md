# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE 2026-10-02 — TASK-050 caché de página WPFC ⚠️ (listo con mejoras menores, ACTIVA en producción) · Siguiente: TASK-050b a +6 h

**Agente activo al cerrar:** product (pipeline coder → security → product cerrado). **Handoff pendiente:** ninguno.
**Dónde quedamos:** WP Fastest Cache sirve todo el sitio público a anónimos (TTFB ~0,11 s, antes 0,5–1,2 s); verificado en
navegador (`/` con CMP/GAM/pubs, `/explorer/` con listados, `/le-canal/histoire/` cacheada, sesión real sin caché).

**Archivos de la sesión:** `wp-plugin/ops/wpfc-enable.php` (activación idempotente + rollback en el docblock),
`wp-plugin/canal-home/includes/fiche-route.php` (purga al cambiar `canal_fiche_public`) — commit c26f136; docs de este cierre
sin commitear: `docs/TASKS.md` (TASK-050 → 🟢 ⚠️, TASK-050b nueva, TASK-049 con criterio PRD-016), `docs/LESSONS.md` (PRD-016),
`docs/ERROR_LOG.md`, `docs/SESSION.md` (CLAUDE.md sin tocar: veredicto ⚠️).

**Decisiones que no están en ARCHITECTURE.md:**
- WPFC ignora `DONOTCACHEPAGE`: toda página privada que se abra temporalmente (2026, ficha pública) depende de la purga al
  cambiar de estado/opción. Caché vaciada entera cada 6 h por los nonces de anónimo (`c27_ajax_nonce`).
- Producción: excepción autorizada a « solo añadir » (.htaccess + opción WpFastestCache). Rollback: docblock de `wpfc-enable.php`.
- Medición: con caché, Lighthouse simulado/DataForSEO no sirven solos para decidir (PRD-016).

**Próxima acción (a partir de ~20:45 CEST del 02/10):**
```
wp-plugin/remote.sh wp cron event list --fields=hook,next_run_relative | grep fastest   # siguiente en ~6 h
curl -s -A "Mozilla/5.0 Chrome/130" https://www.plan-canal-du-midi.com/explorer/ | tail -c 200   # firma WPFC reciente
wp-plugin/remote.sh wp db query "SELECT HOUR(time) h, COUNT(*) FROM wp_mylisting_visits GROUP BY h"
```
+ abrir `/explorer/` en ventana anónima y comprobar que cargan listados (TASK-050b).

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
(Nota de product, 02/10 15:05: después hay commits 531cbe9 « desplegada y verificada » y cb50c1a « menores de la revisión »;
confirmar en esa sesión si cb50c1a está desplegado.)

