# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## CIERRE PARCIAL 2026-10-05 (noche) — TASK-058/059/060: enlaces 2026, archivos del blog, Villes & étapes · Siguiente: navbar de 5 entradas

**Agente activo:** sesión principal. **Handoff pendiente:** ninguno. Todo desplegado en privado y commiteado.
**Hecho:** enlaces internos de las páginas 2026 → 2026 (links-2026.php); archivos `/post-category/<x>-2026/`; 20 etapas
`/etape-2026/<slug>/` + índice `/etapes-2026/`; las 63 esclusas del calcul enlazan a su ficha (los nombres de Pimcore difieren).
**Abrir para medir:** opciones `canal_contenu_public`, `canal_calcul_public`, `canal_etape_public` (= '1'); `/accueil-2026/` sigue publicada.
**Próxima acción:**
```
1. ✅ Navbar de 5 entradas hecho (TASK-061).
2. Formularios (recevoir-le-plan-2 CF7, demandes) o retirarlos; luego plan de publicación (orden explícita).
```

---

## CIERRE PARCIAL 2026-10-05 (tarde) — TASK-057 Calcul de distance 2026 desplegado (privado) · Siguiente: listados de categoría o Robine

**Agente activo:** sesión principal (skills superpowers, sin agentes del pipeline). **Handoff pendiente:** ninguno.
**Dónde quedamos:** `/calcul-de-distance-canal-du-midi-2026/` funciona en prod con sesión; todo commiteado.
**Decisiones nuevas:** no generar contenido para artículos (los publican los clientes); hallazgos de empresa en
`docs/para-direccion.md` (prensa copiada, contraseña Pimcore en el tema, fotos Wikimedia sin crédito). Mapa = Google Maps
(URL del tema) + trazado OSM propio; sin frise de PK.
**Próxima acción:**
```
1. Informar a dirección: docs/para-direccion.md (3 puntos).
2. Siguiente plantilla del generador: listados de categoría (reutilizar carte 2026) o ramal de la Robine en el calcul.
```

---

## CIERRE 2026-10-05 — Inventario completo + plan PDF fijo + plantilla de contenido 2026 · Siguiente: TASK-056 (IA) o calcul de distance 2026

**Agente activo al cerrar:** sesión principal (sin agentes del pipeline: decisión del usuario 05/10, se usan skills superpowers).
**Handoff pendiente:** ninguno.
**Dónde quedamos:** cualquier página o artículo de producción se ve en versión 2026 en `/<ruta>-2026/` (privado, TASK-055).

**Archivos de la sesión:**
- `docs/inventario-paginas-2026-10-05.md` + `docs/data/paginas-trafico-2025-10_2026-09.csv` — inventario, tráfico GA4+GSC, intención, navbar.
- `wp-plugin/canal-home/includes/plan.php` — URL fija `/plan-canal-du-midi.pdf` (última edición, nginx X-Accel-Redirect) (TASK-054).
- Producción `.htaccess` raíz: bloque `canal-plan-pdf` (301 de todas las ediciones; copia `.htaccess.bak-2026-10-05`).
- `includes/contenu-core.php`, `includes/contenu-route.php`, `template-contenu.php`, `assets/contenu.css`, `tests/test-contenu.php`,
  `tests/smoke-contenu.php` (TASK-055); integraciones en `canal-home.php`, `header.php`, `head-fix.php`, `fiche-route.php`.

**Decisiones que no están en ARCHITECTURE.md:**
- Todas las páginas y artículos pasan a 2026, también sin tráfico; mejorar SEO/AEO/GEO de cada URL (memoria migrar-todo-seo-geo).
- Router de contenido sin reglas de reescritura (`parse_request`): si existe un post real con la ruta -2026 se respeta.
- Enriquecimiento IA en metadatos nuevos `_canal_2026_{title,description,summary,faq}`; la plantilla ya los lee.
- ~800 artículos son copias de prensa (La Dépêche/Midi Libre): reescribir con fuente citada en TASK-056.
- `smoke-contenu.php` se ejecuta con el plugin YA desplegado (no requiere archivos).

**Próxima acción:**
```
1. TASK-056: recargar crédito Anthropic y generar title/description/summary/faq de las ~260 URLs con tráfico.
2. O bien: plantilla Calcul de distance 2026 (página nº 1, 20 % de las vistas).
3. Pendientes de antes: /accueil-2026/ sigue publicada · SEC-001 (credenciales en el tema).
```

---

## CIERRE 2026-10-02 (fin de semana) — TASK-044 planificador terminado en privado + TASK-051 rendimiento 2026 · ⚠️ /accueil-2026/ PUBLICADA · Siguiente: decidir

**Agente activo al cerrar:** sesión principal. **Handoff pendiente:** ninguno.
**Dónde quedamos:** planificador 2026 (página 18505, privada) completo y fusionado en `main`; TASK-051 (rendimiento de
las páginas 2026) desplegada: Lighthouse móvil local 81–89 estable (antes alternaba 91/66), accesibilidad 100.
**⚠️ `/accueil-2026/` (18500) está PUBLICADA** desde el 02/10 a petición del usuario para medir en PageSpeed: volver a
`private` cuando lo diga (`wp-plugin/remote.sh wp post update 18500 --post_status=private`).
**⚠️ API de Anthropic sin crédito** (planificador y búsqueda IA de la home responden « indisponible »).

**Archivos (TASK-051):** `includes/head-fix.php` (WebP en el body, `canal_home_move_consent_to_body`),
`includes/fiche-core.php` (`canal_home_minify_css`), `canal-home.php` (CSS en línea minificado), `template-home.php`
(srcset, fondos `data-bg`), `assets/home.js` (rAF, fondos diferidos), `assets/header.css` (contraste Sirdata),
`assets/.htaccess` (caché 1 año), `assets/peniche-toulouse-480.jpg`, `tests/test-fiche.php`.

**Decisiones que no están en ARCHITECTURE.md:**
- WebP: hermano `archivo.jpg.webp` generado al vuelo con GD (8/petición; si faltan, DONOTCACHEPAGE). `rsync --delete`
  del deploy borra los `.webp` de `canal-home/assets/` → se regeneran solos.
- Causa del 66/91 en PageSpeed: el `stub` síncrono de Sirdata en el `<head>` → Chrome sin pantalla no presenta frames
  hasta ~2,3 s. En páginas 2026 stub → cmp → pcm.js se mueven, en el mismo orden, al principio del `<body>`.
- Tras cada deploy que toque HTML: `remote.sh wp eval 'do_action("wpfc_clear_all_cache");'` (caché WPFC).
- Lighthouse local: `npx lighthouse@12 <url> --only-categories=performance` varias pasadas; mirar el FCP *observado*.

**Próxima acción:**
```
1. Decidir si /accueil-2026/ vuelve a privada.
2. Recargar crédito de la API de Anthropic y probar el planificador con una conversación real.
3. ⚠️ Avisar a quien gestiona Pimcore/publicidad: themes/my-listing/affiche_pub_940.php tiene credenciales de BD en
   claro e inyección SQL vía Referer (ver TASKS 🟡 SEC-001).
4. Publicar home + carte + ficha + planificador (TASK-028/029b/030b/044b) SOLO con orden explícita.
```

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

