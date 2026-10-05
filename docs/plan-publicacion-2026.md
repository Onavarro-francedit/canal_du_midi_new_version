# Plan de publicación del sitio 2026 — plan-canal-du-midi.com

**Fecha:** 2026-10-05 · **Estado:** fase 0 HECHA (2026-10-05, ver §9); publicación pendiente. **Nada se ejecuta sin la orden explícita del usuario** (regla del
proyecto: en producción no se modifica lo existente salvo orden expresa).
**Sustituye y agrupa:** TASK-028 (home), TASK-029b (carte), TASK-030b (ficha), TASK-044b (planificador) y la
publicación de TASK-055/057/059/060/061/062.

---

## 1. Qué se publica

| Sección | Hoy (privado) | Tras publicar | Visitas/año (GA4) |
|---|---|---|---|
| Home | `/accueil-2026/` (⚠️ pública desde el 02/10) | `/` | 4 161 |
| Carte | `/explorer-2026/` | `/explorer/` | 1 928 |
| Fichas (254) | `/fiche-2026/<slug>/` | `/fiche/<slug>/` | 13 254 |
| Páginas y artículos (~2 000) | `/<ruta>-2026/` | `/<ruta>/` | 37 103 |
| Calcul de distance | `/calcul-de-distance-canal-du-midi-2026/` | `/calcul-de-distance-canal-du-midi/` | 12 671 |
| Archivos del blog | `/post-category/<x>-2026/` | `/post-category/<x>/` | 550 |
| Étapes (20 + índice) | `/etape-2026/<slug>/`, `/etapes-2026/` | `/etape/<slug>/`, `/etapes/` (**URLs nuevas**) | — |
| Planificador | `/planificateur-2026/` | `/planificateur/` | (sustituye a organiser: 352) |
| Navbar y pie 2026 | en las páginas 2026 | en todas las páginas anteriores | — |

**También pasan a 2026 (TASK-064):**
- `/categorie/<x>/`, `/region/<x>/` y `/mot-cle/<x>/`, en su misma URL: la carte 2026 filtrada por su término, con su
  H1, título, descripción y canonical propios (el tema las sirve con la página `/explorer/` y query vars `explore_*`);
- la página 404 (cabecera 2026, buscador y accesos) y la búsqueda `?s=` (301 a la búsqueda de la carte).

**No cambian:** la tienda Woo y `/mon-compte/`, `wp-admin`, las páginas de autor y de fecha.

---

## 2. Cómo: un solo interruptor (opción `canal_2026_live` → constante `CANAL_2026_LIVE`)

Las páginas 2026 se enlazan entre sí. **Publicar por partes dejaría a los visitantes con enlaces a páginas privadas
(404).** Por eso se publica todo a la vez, con una constante del plugin.

| `CANAL_2026_LIVE` | `false` (hoy) | `true` (publicado) |
|---|---|---|
| Rutas | `-2026` privadas (`parse_request`) | URLs originales (`template_redirect`, con `is_front_page()`, `is_page('explorer')`, `is_singular('job_listing')`, `is_singular()` elegible, `is_category()`, página del calcul) |
| Sufijos / prefijos | `-2026`, `/fiche-2026/`, `/etape-2026/` | `''`, `/fiche/`, `/etape/` |
| Privacidad | `read_private_pages` o la opción `*_public` | pública |
| `robots` | `noindex,nofollow` | indexable |
| Redirecciones 301 (§4) | apagadas | activas |

- **Ajustes de WordPress:** no se tocan, salvo el planificador (página 18505 → `publish`, slug `planificateur`).
  La portada sigue siendo la página 15269: el plugin pinta encima la plantilla 2026, así que no hace falta cambiar
  *Réglages → Lecture*.
- **Encender:** `wp-plugin/remote.sh wp option update canal_2026_live 1`. **Vuelta atrás:**
  `wp-plugin/remote.sh wp option delete canal_2026_live`. Son segundos, sin desplegar. Al cambiar la opción se vacía
  sola la caché WPFC. Las redirecciones se apagan con el mismo interruptor.
- **Vista previa (solo administradores):** con sesión, visitar `/?canal_2026_preview=1` y se ve TODO el sitio como
  publicado (cookie de 1 día); `/?canal_2026_preview=0` la quita. Nadie más lo nota y WPFC no cachea a usuarios con
  sesión. En la vista previa no se tocan las reglas de URL globales (las fichas siguen en `/fiche-2026/` para los demás).

**Código pendiente (fase 0, en privado y con tests):**
- el modo `LIVE` de cada ruta;
- el mapa de redirecciones;
- las étapes en el sitemap;
- los eventos de GA4 (§5).

Todo detrás de la constante, así que desplegarlo no cambia nada público.

---

## 3. Fases

### Fase 0 — Preparación (sin cambios visibles) · ~1 día de trabajo

1. Código del modo `LIVE` y de las redirecciones (§4), con tests. El smoke se ejecuta en modo `LIVE` simulado por
   código, nunca en producción.
2. Eventos de GA4 (§5) desplegados ya en las páginas 2026, para comprobarlos antes.
3. **Copia de seguridad:** `wp db export` fuera de `httpdocs` + tag git `pre-publicacion-2026` del plugin.
4. **Línea base de medición:**
   - exportar de Search Console los clics, impresiones y posición de las 100 URLs principales (12 meses y 28 días);
   - CrUX de `/`, `/calcul-de-distance-canal-du-midi/` y `/explorer/` con la clave PSI;
   - GA4: las vistas de las 30 primeras.
5. **Comprobaciones que dependen del usuario o de terceros (bloqueantes):**
   - [ ] **pedido real del plan en papel** (o en el modo sandbox de PayPal) desde la página 2026: llega el correo a
     agomes@ y la redirección a PayPal funciona;
   - [ ] **crédito en la API de Anthropic** (el planificador y la búsqueda IA de la home responden « indisponible »);
   - [ ] **planificador:** `CANAL_PLANNER_LIVE = true` (correos reales a los prestatarios) y confirmar los destinatarios;
   - [ ] `pm.max_children` del pool PHP-FPM (cada llamada IA ocupa un worker hasta 30 s);
   - [ ] decidir qué pasa con `/accueil-2026/`, que es pública hoy: 301 a `/` al publicar;
   - [ ] informar a dirección de `docs/para-direccion.md` (prensa, contraseña de Pimcore, fotos de Wikimedia).
6. Regenerar `llms.txt` y `llms-full.txt` con las URLs definitivas (`build/build-llms-full.php`, PRD-013).

### Fase 1 — Publicación (con la orden del usuario) · 30–45 min

Momento: un día laborable por la mañana. Octubre es temporada baja (el tráfico es la mitad que en julio), un buen
momento para corregir antes de la temporada 2027.

1. `CANAL_2026_LIVE = true` → `remote.sh deploy` (los tests y el lint de PHP 7.4 se ejecutan antes).
2. Planificador: `remote.sh wp post update 18505 --post_status=publish --post_title="Planificateur" --post_name=planificateur`
   (`/planificateur/` ya lo sirve el plugin en modo publicado aunque no se cambie el slug; el título quita el « 2026 » de la pestaña).
3. Vaciar la caché WPFC (`wpfc_clear_all_cache`) y `canal_carte_listings`.
4. Subir `llms.txt` y `llms-full.txt`.
5. **Smoke en ventana anónima** (lista de §6): 200 + diseño 2026 en las 15 URLs tipo; 301 en las del §4.
6. Search Console: reenviar `wp-sitemap.xml` y pedir la indexación de `/`, el calcul, `/etapes/` y 5 étapes.
7. Bing Webmaster API: `SubmitUrlBatch` de las URLs principales y del sitemap (ChatGPT busca sobre el índice de Bing).

### Fase 2 — Vigilancia · días 1–30

| Cuándo | Qué | Umbral para actuar |
|---|---|---|
| +1 h | 404 en los logs y en GA4 (`page_not_found`), consola, formularios | cualquier 404 sobre una URL con tráfico |
| +1 día | GA4 Tiempo real: page_view y eventos §5; pedidos del plan | sin eventos → revisar el consentimiento de Sirdata |
| +3 días | Search Console: cobertura (404, « explorada, no indexada »), canonicals | > 20 URLs nuevas con error |
| +7 días | clics y posición de las 100 URLs frente a la línea base | caída > 30 % en el top 10 → analizar URL por URL |
| +28 días | CrUX (LCP/INP/CLS de campo), TASK-049 | LCP móvil p75 > 2,5 s → TASK-049 8b |

**Criterio de vuelta atrás inmediata:** errores 500, formulario de pago roto o caída general de páginas vistas > 50 %
en 24 h (descontando el día de la semana).

---

## 4. Redirecciones 301 (solo con `LIVE`, en el plugin, sin tocar `.htaccess`)

| Desde | Hacia | Por qué |
|---|---|---|
| `/accueil-2026/`, `/explorer-2026/`, `/planificateur-2026/` | `/`, `/explorer/`, `/planificateur/` | `/accueil-2026/` ha sido pública; las otras, por si se compartieron |
| `/fiche-2026/<slug>/`, `/etape-2026/…`, `/etapes-2026/` | `/fiche/<slug>/`, `/etape/…`, `/etapes/` | ídem |
| `/<ruta>-2026/`, `/post-category/<x>-2026/`, calcul `-2026` | sin el sufijo | ídem |
| `/organiser-votre-sejour/`, las 3 `/demande-…/` | `/planificateur/` | las sustituye el planificador |
| `/agenda/` (vacía) | `/manifestations-et-fetes-canal-du-midi/` | página vacía |
| 54 páginas de 2013–2018 (`rechercher-presta`, `details-*`, `/loisirs/…`, `/se-restaurer/…`, `/services-utiles/…`) | categoría equivalente en la carte (p. ej. `/hotels/` → `/explorer/?type=hotel`, `/se-restaurer/` → `?type=restauration`) o `/explorer/` | sin contenido; tabla completa en el código, una línea por URL, con test |
| El PDF del plan (ya hecho, TASK-054) | `/plan-canal-du-midi.pdf` | — |

---

## 5. Medición: eventos de GA4 (hoy GA4 no mide ninguna conversión)

| Evento | Cuándo | Parámetros | ¿Key event? |
|---|---|---|---|
| `fiche_contact` | clic en llamar, e-mail, web, itinerario o redes de una ficha | `method`, `fiche` | ✅ |
| `plan_commande` | CF7 `wpcf7mailsent` del formulario 12976 (pedido en papel) | — | ✅ |
| `plan_pdf` | clic en `/plan-canal-du-midi.pdf` | `source` (página) | ✅ |
| `planner_request` (ya existía en planner.js) | demanda confirmada, uno por prestatario | `listing_slug` | ✅ |
| `calcul_trajet` | trayecto calculado (1 por trayecto distinto) | `de`, `a`, `km` | — |
| `etape_clic` | clic en un prestatario desde una étape | `etape`, `groupe` | — |

Se envían con `gtag('event', …)` sobre el GA4 diferido que ya está en las páginas 2026 (respeta el consentimiento de
Sirdata). Se marcan como key events en GA4 (lo hace el usuario o yo vía la API de administración, si se autoriza).

---

## 6. Smoke de publicación (ventana anónima)

1. `/` · `/explorer/` · `/explorer/?type=hotel` · `/fiche/port-de-sete/` · `/fiche/ecluse-de-bram/`
2. `/canal-de-la-robine/` · `/navigation/regles-de-navigation/` · `/peniches-a-vendre-sur-le-canal-tout-savoir-avant-dacheter/`
3. `/calcul-de-distance-canal-du-midi/?de=Toulouse&a=Agde` · `/post-category/actualites/page/2/` · `/etapes/` · `/etape/le-somail/`
4. `/planificateur/` · `/recevoir-le-plan-du-canal-du-midi-2/` (formulario cargado, sin enviar) · `/meteo-du-canal-du-midi/`
5. 301: `/accueil-2026/` · `/fiche-2026/port-de-sete/` · `/organiser-votre-sejour/` · `/hotels/`
6. Para cada una: 200 o 301, un solo H1, `robots` indexable, canonical a sí misma, sin errores en la consola, a 390 px
   sin scroll horizontal y con la caché WPFC sirviendo (2.ª visita con `X-Cache`/HTML en caché).

---

## 7. Riesgos

| Riesgo | Mitigación |
|---|---|
| Caída de posiciones por el cambio de plantilla en 2 000 URLs | Las URLs, los títulos y el contenido se mantienen; se añaden un H1 único, datos estructurados y enlaces internos. Vigilancia +7/+28 días; vuelta atrás en 2 min |
| El pedido en papel (PayPal) falla | Prueba real bloqueante en la fase 0; vuelta atrás inmediata si falla |
| Carga de PHP por la IA (planificador, búsqueda) | `pm.max_children` + límites por IP y diarios que ya existen |
| Caché WPFC sirviendo HTML antiguo o mezclado | Vaciado tras el deploy; las páginas con `?de=` (calcul) y `?type=` (carte) no se cachean |
| Estadísticas de visitas de MyListing para los prestatarios (TASK-050b) | Ya afectadas por la caché; la referencia es Matomo/GA4 |
| `<head>` roto del tema (TASK-031) en lo que no se migra | Las páginas 2026 ya lo corrigen; el resto (`/categorie/`, `/region/`) sigue igual |

---

## 8. Después de publicar (no bloquea)

- `/categorie/<x>/`: plantilla 2026 propia o 301 a la carte filtrada (las categorías reciben 106 000 impresiones;
  decidir con datos de +28 días).
- TASK-043: navbar, pie y textos editables desde wp-admin (cuando el cliente apruebe el diseño).
- Robine en el calcul; mapa en las étapes; eventos de GA4 adicionales; TASK-031 en el resto del sitio.

---

## 9. Fase 0 — hecha el 2026-10-05

| Paso | Estado |
|---|---|
| Interruptor `canal_2026_live` + rutas por modo (`includes/live.php`, test `tests/test-live.php`) | ✅ desplegado, **apagado** |
| Modo publicado en home, carte, ficha, contenido, calcul, archivos y étapes (con test de rutas) | ✅ verificado en la vista previa: 16 URLs → 200, plantilla 2026, un H1, indexables, canonical correcto |
| Redirecciones 301 (`includes/redirects-2026.php`, 59 páginas antiguas + rutas -2026) | ✅ verificado en la vista previa: 8 casos |
| Sitemap de las étapes (solo publicado) | ✅ |
| Eventos de GA4 (`includes/events-2026.php`, `calcul_trajet` en calcul.js) | ✅ activos ya en las páginas 2026; verificados `fiche_contact` y `plan_pdf` |
| Copia de la BD | ✅ `/var/www/vhosts/plan-canal-du-midi.com/backups-canal/db-pre-publicacion-2026-20261005-1145-completa.sql.gz` (101 tablas + 32 vistas, 8,5 MB) |
| Tag git del plugin | ✅ `pre-publicacion-2026` |
| Línea base | ✅ `docs/data/baseline-gsc-28d-2026-10-05.csv`, `docs/data/baseline-crux-2026-10-05.txt` (CrUX de origen: móvil LCP 2,97 s · INP 114 ms · TTFB 1,25 s; escritorio LCP 2,70 s) |
| `/planificateur/` en modo publicado (sirve la página por su plantilla; sin bucle con la redirección canónica de WP) | ✅ verificado en la vista previa (también organiser y demandes → planificador) |
| Al encender (fase 1): página 18505 → `publish` + título « Planificateur »; `llms.txt` / `llms-full.txt` con las URLs definitivas | pendiente |
| Bloqueantes del usuario (§3 fase 0, punto 5): pedido real del plan, crédito Anthropic, `CANAL_PLANNER_LIVE`, `pm.max_children`, dirección | pendiente |
| `/categorie/`, `/region/`, `/mot-cle/`, 404 y `?s=` en 2026 (TASK-064) | ✅ verificado en la vista previa: hotel 18, camping 14, location-bateau 10, Homps 11, etiqueta 52; canonical propio; títulos sobre el filtro del tema (prioridad 10001) |
| Corregido gracias a la vista previa | Elementor imponía su plantilla en `/` y `/explorer/` (la nuestra va ahora a prioridad 99); la caché de la carte compartía URLs de fichas entre modos (ahora una clave por modo) |
