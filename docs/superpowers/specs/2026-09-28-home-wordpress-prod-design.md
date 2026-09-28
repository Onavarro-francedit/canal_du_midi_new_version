# Diseño — Nueva home "Accueil 2026" en el WordPress de producción

## Contexto
Producción (`https://www.plan-canal-du-midi.com`, servidor `plesk-prod`,
`/var/www/vhosts/plan-canal-du-midi.com/httpdocs`) es un WordPress 7.0.6 con el
tema **my-listing**, Elementor 3.0.16, WooCommerce y WP Fastest Cache. El
pool PHP-FPM del vhost corre **PHP 7.4**. Funciona y genera ingresos (pub 940 +
Google Ad Manager cargados en `my-listing/header.php`).

La aplicación PHP local (este repo) deja de ser un sitio propio: se usa como
**fuente de diseño**. Fases acordadas: 1) home (esta spec) → 2) `/carte` →
3) diseño de la ficha.

## Restricción absoluta
**No se modifica nada existente en producción**, aunque esté mal hecho: ni
archivos del tema, ni `wp-config.php`, ni opciones, páginas, plugins o menús.
Solo se **añade**: un plugin nuevo, un archivo de config fuera de `httpdocs` y
una página nueva. El cambio de portada (*Réglages → Lecture*) es un paso
aparte que solo se hace con confirmación explícita del usuario.

## Enfoque
Plugin nuevo e independiente `wp-content/plugins/canal-home/`. Descartados:
child theme (cambia el tema activo = modificar lo existente) y Elementor
(3.0.16 obsoleto, la IA necesita PHP igualmente).

Desarrollo y prueba **directamente en producción** con la página en estado
**privado** (decisión del usuario). Mitigación: todo el código del plugin es
pasivo fuera de su plantilla y su ruta REST; cada archivo pasa
`/opt/plesk/php/7.4/bin/php -l` en el servidor antes de subirse; rollback =
desactivar el plugin.

## Estructura del plugin

| Archivo | Responsabilidad |
|---|---|
| `canal-home.php` | Cabecera de plugin. Registra la plantilla "Accueil 2026" (`theme_page_templates` + `template_include`), encola assets **solo** si la página usa esa plantilla, registra la ruta REST. |
| `template-home.php` | `get_header()` del tema → markup de la home dentro de `<div class="cdm-home">` → `get_footer()` del tema. Conserva menú, pubs y pie. |
| `data.php` | Funciones de lectura (categorías, séjours, catálogo IA). Solo lectura. |
| `ai.php` | Endpoint REST de la IA. |
| `assets/home.css` | CSS de la home local portado y **acotado bajo `.cdm-home`** (sin reglas globales sobre `body`, `a`, `h1`… fuera del contenedor). |
| `assets/home.js` | Modal IA + modal plan (Calaméo) + scroll-reveal, en un solo archivo, sin dependencias. |

Fuentes (Playfair Display, Sora, Manrope — Google Fonts) y Bootstrap Icons
(jsDelivr) se encolan solo en esa página. Todo el PHP compatible con **7.4**
(sin `str_contains`, `match`, argumentos con nombre, tipos union, etc.).

## Contenido por sección (textos validados por el usuario el 2026-09-28)

1. **Hero** — imagen `wp-content/uploads/2025/04/img_couv_site_2025_v2.jpg`.
   Eyebrow « L'Officiel du Canal du Midi » · H1 « Explorez le Canal du Midi,
   de Toulouse à la Méditerranée » · sous-titre « Hébergements, location de
   bateaux et de vélos, restaurants, visites : trouvez les meilleures adresses
   le long du canal et préparez votre séjour en toute liberté. » · stats 240 km
   / 63 écluses / 1681 / UNESCO.
   - **Buscador clásico** → GET `/explorer/` con
     `type=prestataires-touristiques`, `search_keywords`, `category`, `region`.
     Select "Destination": slugs `region` toulouse, castelnaudary, carcassonne,
     trebes, homps, argens-minervois, beziers, agde, sete (verificados en BD).
     Select "Type": whitelist de 12 slugs `job_listing_category` del
     `PageController` local (todos existen en prod), etiqueta = nombre del término.
   - **Botón « Assistant IA »** → modal (ver sección IA).
2. **Destinations** — Eyebrow « À découvrir » · H2 « Que faire le long du
   canal ? » · texto « Plus de 250 prestataires sélectionnés, classés par
   activité, pour composer votre séjour. » · 6 términos `job_listing_category`
   con `count > 0` al azar, imagen = term meta `image` (fallback: imagen del
   hero), enlace `get_term_link()` (`/categorie/<slug>/`) · botón « Voir tous
   les prestataires sur la carte » → `/explorer/`.
3. **Expériences** — Eyebrow « Votre séjour » · H2 « Préparer et profiter de
   votre séjour » · texto y lista de 4 puntos validados · fotos
   `2024/04/Dominique_VIET_CRTLOccitanie_0017338_MD_RET3-1.jpg` (crédito
   « © D. Viet / CRTL Occitanie »), `2022/03/peniche_toulouse.jpg`,
   `2020/04/rando-velo_2.webp` · botones « Organiser votre séjour » →
   `/organiser-votre-sejour/`, « Calculer une distance » →
   `/calcul-de-distance-canal-du-midi/`.
4. **Séjours** — Eyebrow « Sur l'eau et à vélo » · H2 « Croisières, balades et
   excursions » · 4 `job_listing` publicados en las categorías excursions,
   location-de-velo, peniche, nautique (`orderby=rand`), imagen `_job_cover`,
   etiqueta = primera categoría legible, enlace `get_permalink()`. **Sin precio.**
5. **Banda inmersiva** — H2 « Votre séjour sur mesure, sans chercher » · texto
   validado · botón « Envoyer ma demande » → `/organiser-votre-sejour/`.
6. **Pourquoi « L'Officiel du Canal du Midi » ?** — 3 cartas (Des prestataires
   locaux / Le plan officiel / Des outils pratiques) + 2 cartas destacadas
   « Naviguer sur le canal » → `/navigation/regles-de-navigation/` y « Voie
   verte et véloroute » → `/voie-verte-et-veloroute/`.
7. **Plan** — imagen `2026/05/couv_canal_du_midi_2026.png` (clic → modal
   Calaméo `bkcode=003331405edc35288442a`) · Eyebrow « Plan officiel 2026 » ·
   H2 « Le plan du Canal du Midi 2026 » · botones « Télécharger le plan
   gratuit (PDF) » → `/wp-content/uploads/pdf/Plan-Canal-du-Midi-2026.pdf` y
   « Recevoir le plan par courrier » → `/recevoir-le-plan-du-canal-du-midi-2/`.
   **Sin formulario de e-mail** (el plan no se envía por e-mail en prod).

Fuera de la nueva home (decisión validada): widgets Facebook y bloque
« Prestataire à la une ». La home Elementor actual (página 15269) queda intacta.

Todas las URLs internas se construyen con `home_url()`; todo texto/atributo
salido de BD se escapa con `esc_html()` / `esc_attr()` / `esc_url()`.

## Asistente IA

**Flujo:** textarea del modal → `POST /wp-json/canal-home/v1/ai` `{prompt}` →
servidor llama a Claude → responde `{results:[{title, url, image, category,
city, reason}]}` → el modal pinta 3–5 tarjetas con enlace a la ficha.

**Catálogo:** todos los `job_listing` publicados (254) en formato compacto, una
línea por ficha: `slug | título | categorías | región | extracto ~200 car. de
_job_description sin HTML`. Cacheado en el transient `canal_home_ai_catalog`
(12 h). Serialización determinista (orden por ID) para que el prefijo del
prompt sea estable.

**Llamada:** HTTP directo (`wp_remote_post`, timeout 30 s) a
`https://api.anthropic.com/v1/messages`. El SDK oficial PHP exige PHP ≥ 8.1 y
Composer; el vhost corre 7.4 → HTTP crudo es la única opción viable.
- `model`: `claude-opus-5` (valor por defecto; cambiar a `claude-haiku-4-5`
  es decisión del usuario por coste/latencia — constante en el archivo de config).
  **Ojo:** Haiku 4.5 rechaza `effort` (400) y no usa `fallbacks`; el código
  solo envía `effort`, `fallbacks` y la cabecera beta si el modelo **no** es
  `claude-haiku-*`. `thinking` no se envía (en Opus 5 es adaptativo por defecto).
- `output_config`: `{effort: "low", format: {type: "json_schema", schema}}` con
  schema `{results: [{slug: string, reason: string}]}`. La API exige
  `additionalProperties: false` y `required` en **cada** objeto (raíz e ítems
  de `results`); `maxLength`/`maxItems` no se admiten → los límites (3–5
  resultados, `reason` ≤ 140 car.) van en las instrucciones y se **recortan en
  servidor** (`array_slice`, `mb_substr`). Tarea de selección simple → effort `low`.
- `system`: [instrucciones (FR, 3–5 resultados, solo slugs del catálogo, raison
  ≤ 140 car. en francés), catálogo] con `cache_control: {type: "ephemeral"}` en
  el bloque del catálogo. El prompt del usuario va en `messages`, fuera del
  prefijo cacheado.
- `fallbacks: "default"` + cabecera `anthropic-beta:
  server-side-fallback-2026-07-01`; si `stop_reason === "refusal"` (toda la
  cadena rechazó) → error controlado.
- `max_tokens`: 8000 (el pensamiento adaptativo también consume de este tope;
  2000 podía truncar el JSON). Si `stop_reason === "max_tokens"` → error
  controlado, nunca `json_decode` de una salida cortada.
- Se lee el primer bloque `type === "text"` de `content` (puede haber bloques
  `thinking` antes), no `content[0]`.

**Validación de la respuesta:** se descartan slugs que no estén en el catálogo;
título, imagen, categoría, ciudad y URL se construyen **en servidor** desde WP
(`get_permalink`), nunca desde el texto del modelo. `reason` se devuelve como
texto plano y el JS lo inserta con `textContent`.

**Seguridad:**
- Clave API y modelo en `/var/www/vhosts/plan-canal-du-midi.com/canal-ai-config.php`
  (fuera de `httpdocs`, archivo nuevo, `chmod 640`), que define
  `CANAL_AI_API_KEY` y `CANAL_AI_MODEL`. Si no existe → la IA responde error
  controlado y la home sigue funcionando.
- Rate limit por IP con transients: 10 peticiones / 10 min → HTTP 429 con
  mensaje francés.
- **Tope global** diario (transient `canal_home_ai_daily`, p. ej. 300
  llamadas/día, constante en el config): el endpoint es público y cada llamada
  cuesta dinero; el límite por IP no para un abuso desde muchas IPs. Superado →
  mismo mensaje de error + enlace al explorador.
- Prompt: `trim`, máx. 500 caracteres, se eliminan etiquetas y caracteres de
  control (misma lógica que `SanitizesPrompts` local); vacío → 400.
- Sin nonce (la home pública pasará por WP Fastest Cache; un nonce cacheado
  caducaría). La ruta es pública (`permission_callback => '__return_true'`)
  y se protege con los dos límites. La URL del endpoint se inyecta en el JS con
  `rest_url('canal-home/v1/ai')`, no se escribe `/wp-json/` a mano.

**Errores (UX):** timeout / error API / 0 resultados → mensaje francés en el
modal + enlace « Voir les résultats dans l'explorateur » →
`/explorer/?type=prestataires-touristiques&search_keywords=<prompt>` (prompt
codificado con `encodeURIComponent`).
Durante la llamada: botón deshabilitado + « Recherche en cours… ».

## Puesta en marcha
1. Crear `canal-ai-config.php` fuera de `httpdocs` (la clave la aporta el usuario).
2. Subir el plugin, `php -l` 7.4 de cada archivo, activar.
3. Crear la página « Accueil 2026 » en estado **privé** con la plantilla.
4. Verificación en navegador (Playwright), logueado como admin: las 7 secciones
   con datos reales, 3–4 búsquedas IA reales (p. ej. « week-end romantique près
   de Carcassonne », « louer un bateau sans permis », « balade à vélo en
   famille »), búsqueda clásica → `/explorer/` filtrado, modal plan, móvil
   (375 px), 0 errores de consola, publicidad 940 visible, resto del sitio
   sin cambios (home actual, una ficha, `/explorer/`).
5. Cambio de portada: solo cuando el usuario lo pida explícitamente. Requiere
   **publicar** antes la página (el selector de *Réglages → Lecture* solo
   lista páginas publicadas); en cuanto se publica es accesible por su slug,
   así que publicar y cambiar la portada se hacen en el mismo momento.

## Criterios de éxito
- La página privada muestra las 7 secciones con datos reales de producción y
  los textos validados.
- La IA devuelve 3–5 fichas reales y pertinentes, con enlaces que funcionan.
- 0 archivos existentes modificados: un `find httpdocs -newer <marca>`
  tomado antes de empezar solo lista `plugins/canal-home/`, caché y uploads.
  Única escritura en opciones: la entrada del plugin en `active_plugins`
  (inherente a activar un plugin) + transients propios `canal_home_*`.
- Desactivar el plugin deja el sitio como antes en todo lo visible. Rollback
  completo = desactivar + enviar a la papelera la página « Accueil 2026 »
  (sin plugin cae a la plantilla por defecto) + borrar transients `canal_home_*`.

## Fuera de alcance
Cambio de portada, `/carte`, diseño de fichas, actualizar PHP/Elementor,
corregir código existente (incl. credenciales en claro de `page_accueil_test.php`),
i18n (el sitio es solo francés).
