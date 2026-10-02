# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE 2026-10-02 (noche) — Medición SEO/rendimiento gratuita montada · TASK-045/046 ✅, TASK-048 ⚠️, TASK-050 ⚠️ · Siguiente: TASK-050b

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** caché de página activa en todo el sitio (TTFB ~0,11 s); herramientas de medición gratuitas configuradas y
documentadas (abajo); todo commiteado y subido a `origin/feat/wp-planner-2026`.

**Herramientas (fuera del repo, en `~/open-seo`, Docker http://localhost:3001):**
- OpenSEO (proyecto « Canal du Midi 2026 » `162b75bf-0351-4b6c-b67a-38b6ba702973`): auditorías vía `~/open-seo/mcp.sh` —
  lanzarlas SIN `runLighthouse` (el rastreo es gratis; Lighthouse cuesta 0,005 $/página). Search Console + GA4 conectados.
- `~/open-seo/.env`: `DATAFORSEO_API_KEY` (saldo ~0,57 $ del crédito gratis, solo consultas puntuales), `PSI_API_KEY`
  (PageSpeed Insights gratis → usar los datos CrUX de usuarios reales; el LCP de laboratorio no sirve con caché, PRD-016),
  `BING_WMT_KEY` (API de Bing Webmaster; estadísticas aún vacías: sitio dado de alta ~29/09).
- Línea base CrUX de `/` (28 días antes de la caché, p75): móvil LCP 2,94 s · TTFB 1,26 s; escritorio LCP 2,63 s · TTFB 0,96 s.

**Archivos de la sesión:** `docs/auditoria-accueil-2026-2026-10-02.md` (auditoría 84/100); plugin: title/H1/#etapes/FAQ/schema Map
(TASK-045), FAQ de 8 preguntas (TASK-046), iconos SVG/fuentes propias/CSS en línea/GA4 diferido/imágenes (TASK-048),
`wp-plugin/ops/wpfc-enable.php` + purga en `fiche-route.php` (TASK-050). Producción: `wp-content/uploads/.htaccess` (bloque
canal-cache) y `.htaccess` (bloques WpFastestCache + canal-headers), copias `.bak-2026-10-02*`.

**Decisiones que no están en ARCHITECTURE.md:** ver el cierre de TASK-050 de abajo + medir siempre con navegador real/CrUX en
páginas cacheadas; avisos de negocio pendientes de transmitir: conversión Google Ads AW-986499205 no se registra en ninguna
página (preexistente); visitas MyListing ya no cuentan las servidas desde caché; GA4 diferido en páginas 2026.

**Próximas acciones:**
1. **Hoy ≥ 20:45 CEST — TASK-050b:**
```
wp-plugin/remote.sh wp cron event list --fields=hook,next_run_relative | grep fastest
curl -s -A "Mozilla/5.0 Chrome/130" https://www.plan-canal-du-midi.com/explorer/ | tail -c 200
```
   + `/explorer/` en ventana anónima con listados.
2. **~06/10:** consultas y backlinks de Bing (API) vs Google.
3. **~30/10:** CrUX con `PSI_API_KEY` para ver el efecto real de la caché (TTFB/LCP de usuarios).
4. Publicación conjunta home + carte + ficha (TASK-028/029b/030b) solo con orden explícita; al publicar: regenerar `llms-full.txt`
   (PRD-013), enviar las URLs a Bing, re-medir (TASK-049 con criterio PRD-016).

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

## CIERRE 2026-10-02 (tarde) — TASK-044 Planificateur 2026 desplegado en privado, revisión final y menores corregidos · ⚠️ API sin crédito

**Agente activo al cerrar:** sesión principal. **Handoff pendiente de esta tarea:** ninguno.
**Dónde quedamos:** `/planificateur-2026/` (página 18505, privada) desplegado con todo, incluidos cb50c1a (menores de la
revisión) y 87983ed (`window.history`; sin él la página `?confirmer=` no mostraba el resumen) — respuesta a la nota de
product: sí, ambos están desplegados. `smoke-planner.php` 29/29, confirmación por enlace y foco del modal verificados en
navegador.
**⚠️ La cuenta de la API de Anthropic no tiene crédito** (HTTP 400 « credit balance is too low », 02/10 ~13:10): el
planificador y la búsqueda IA de la home responden « momentanément indisponible » hasta recargar crédito.

**Decisiones que no están en ARCHITECTURE.md:** correos seguros por defecto (sin `CANAL_PLANNER_LIVE = true` todo a
onavarro@); una demanda viva por e-mail; correo de confirmación solo con datos del catálogo; en `planner.js` la variable
`history` es el historial del chat → usar `window.history`; Google Maps no pinta en el Chrome de Claude.

**Próxima acción:**
```
1. Recargar crédito de la API de Anthropic y comprobar /planificateur-2026/ con una conversación real.
2. Fusionar feat/wp-planner-2026 en main (lleva también TASK-045…050) — con el visto bueno del usuario.
3. Publicar (TASK-044b con 028/029b/030b) SOLO con orden explícita.
```

---

