# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## TASK-027 — Home « Accueil 2026 » en el WordPress de producción — DESPLEGADA EN PRIVADO ✅ — 2026-09-29

**Agente activo al cerrar:** sesión principal (brainstorming → spec → plan → ejecución
directa con executing-plans).
**Handoff pendiente:** ninguno. Rama `feat/wp-home-accueil-2026` sin mergear.
**Dónde quedamos:** plugin `canal-home` activo en producción, página « Accueil 2026 »
(ID 18500) **privada** y verificada; la portada sigue siendo la home Elementor (15269).

**Archivos de la sesión (repo):**
- `wp-plugin/canal-home/` — plugin (bootstrap, plantilla, `includes/ai-core.php` puro,
  `includes/data.php`, `includes/ai.php` endpoint, `assets/home.css` generado, `assets/home.js`).
- `wp-plugin/build/` — build del CSS (postcss, solo local) + `home-extra.css`.
- `wp-plugin/tests/` — `test-ai-core.php`, `smoke-data.php`, `smoke-render.php`,
  `smoke-endpoint.php`, `check-cache.php`.
- `wp-plugin/remote.sh` — test / smoke / deploy / run / wp.
- Spec y plan en `docs/superpowers/`.

**En el servidor (añadido, nada existente modificado):**
`wp-content/plugins/canal-home/`, `/var/www/vhosts/plan-canal-du-midi.com/canal-ai-config.php`
(clave del `.env` local, modelo `claude-opus-5`, tope 300/día), página 18500 privada,
`active_plugins`, transients `canal_home_*` y la fila `wp_options`
`canal_home_ai_daily_YYYYMMDD` (tope diario atómico). **Rollback completo:** desactivar el
plugin, papelera de la página 18500, borrar transients `canal_home_*` y filas
`canal_home_ai_daily_%`.

**Decisiones que no están en ARCHITECTURE.md:**
- Producción = WordPress; la app local es solo fuente de diseño.
- El explorador de my-listing filtra por `search_location` (texto que geocodifica, 10 km),
  no por la taxonomía `region`.
- El tema fija `html{font-size:10px}` → el CSS portado se convierte rem→px en el build.

**Observación para el usuario:** `httpdocs/wp-config.php` se reescribió el 29/09 a las
01:24:47 UTC (usuario del sitio, sin cron propio → tarea de Plesk/WP Toolkit). No fuimos
nosotros; no se ha tocado.

**Próxima acción (solo con orden explícita del usuario):**
```
wp-plugin/remote.sh wp post update 18500 --post_status=publish
wp-plugin/remote.sh wp option update page_on_front 18500     # rollback: 15269
```
