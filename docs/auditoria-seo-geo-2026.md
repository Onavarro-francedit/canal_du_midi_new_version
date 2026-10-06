# Auditoría SEO / AEO / GEO de las páginas 2026 — seguimiento

**Creado:** 2026-10-06 (TASK-070) · **Medido:** el sitio tal como quedará publicado (vista previa de publicación, URLs
definitivas), 2 495 páginas · **Rúbrica:** skill `seo-geo` (100 puntos: técnico, on-page, schema, GEO, AEO, E-E-A-T),
solo lo medible página a página.

## Cómo se usa

- Cada línea con ⬜ es una tarea. Al analizarla: 🔍 + nota corta. Al terminarla y verificarla en producción: ✅ + fecha.
- Orden de trabajo: **§2 sitio entero → §3 plantillas** (un cambio arregla cientos de URLs) **→ §4 páginas una a una**
  por clics, que es donde va la mejora de contenido.
- Volver a medir (solo lectura, ~15 min):
  `wp-plugin/remote.sh run tests/auditoria-2026.php > docs/data/auditoria-2026.tsv` y después
  `python3 wp-plugin/build/auditoria-informe.py docs/data/auditoria-2026.tsv 20` (pinta las tablas de §3 y §4).
- Datos por URL (todas las columnas): `docs/data/auditoria-2026.tsv`. Clics = Google, oct. 2025 – sep. 2026.
- Reglas que mandan sobre la rúbrica: los artículos los escriben los clientes (**nada de texto generado** en sus títulos,
  resúmenes o FAQ); datos recientes y con fuente; nada de UIs cargadas; FAQ siempre visibles.

## 1. Resumen

1. **La base técnica está bien en todas las plantillas:** un H1, canonical, OG/Twitter y BreadcrumbList al 100 %.
2. **Lo que más pesa está en dos páginas:** el calcul (7 751 clics) y la home (4 756). La home pasa todo; al calcul le
   falta title corto, FAQPage y dateModified.
3. **Titles:** fallan en la mitad de artículos (992 de más de 65 car., la mayoría por el sufijo « — Canal du Midi » sobre
   títulos largos) y en el 81 % de las páginas (son **cortos**, menos de 39 car.). Se arregla en la plantilla.
4. **Contenido fino:** 968 de 1 933 artículos tienen menos de 300 palabras (381 menos de 100). No se reescriben (son de
   los clientes): hay que decidir qué hacer con ellos (§3.3).
5. **AEO casi a cero fuera de home y cartes:** casi ningún H2 en forma de pregunta y pocas FAQ en las páginas guía
   propias (Robine, écluses, période de navigation…), que son las que más clics traen después del calcul.
6. **GEO de entidad:** la Organization declara 2 perfiles (Facebook, Instagram) y no tiene ficha Wikidata propia.

## 2. Sitio entero

- ✅ HTTPS + HSTS, `X-Content-Type-Options: nosniff`.
- ✅ robots.txt: bots de respuesta permitidos (OAI-SearchBot, ChatGPT-User, Claude-*, Perplexity-*, DuckAssist, Mistral),
  `Content-Signal: search=yes, ai-input=yes, ai-train=no`, entrenamiento bloqueado (decisión del 29/09).
- ⬜ **Revisar `Google-Extended` bloqueado:** además del entrenamiento, controla el uso en las respuestas de Gemini
  (grounding), justo lo que mide el panel GEO-IA. Decidir si se abre (solo ese bot).
- ✅ `llms.txt` y `llms-full.txt` en la raíz. ⬜ Regenerar `llms-full.txt` al publicar (PRD-013).
- ✅ Sitemap `wp-sitemap.xml` en robots.txt. ⬜ Al publicar: comprobar que lista las URLs 2026 (étapes incluidas) y
  enviarlo a Search Console y Bing Webmaster.
- ✅ Gemelo markdown anunciado en el `<head>` (`rel="alternate" type="text/markdown"` → llms.txt).
- ⬜ **Organization `sameAs`:** 2 perfiles; la rúbrica pide 4+ con Wikidata. Crear la entidad en Wikidata y sumar
  perfiles reales (LinkedIn de Azur Communications, Google Business Profile si existe) → tema para dirección
  (`docs/para-direccion.md`), es publicar fuera del sitio.
- ⬜ Organization sin `contactPoint`: añadir el contacto público que ya figura en el sitio.
- ⬜ Core Web Vitals: volver a medir con CrUX tras publicar (TASK-049; no decidir con el LCP de laboratorio, PRD-016).
- ⬜ 4 URLs no responden 200: `/marches-de-produits-locaux-a-port-lauragais-20-juin-2026/` (301, correcto: es la página
  privada de prueba) y `/post-category/croisiere-bateau/`, `/histoire/`, `/lieux-dinformations/` (404 en vista previa:
  comprobar si es porque están vacías y si alguien las enlaza).

## 2b. Lighthouse y OpenSEO (06/10, medidas con las páginas 2026 abiertas)

**Objetivo: 100 en las cuatro categorías.** Lighthouse 12 local, una página por plantilla, móvil y escritorio
(`/explorer-2026/?type=location-bateau` = categoría). El SEO de 66–69 es solo el `noindex` de las rutas privadas `-2026`:
al publicar pasa a 100 (las páginas ya publicadas en su URL, home y carte, dan 100).

Pasada 4 (tras los arreglos del 06/10):

| Página | Perf | Acc | BP | SEO | LCP s | TBT ms | CLS |
|---|---|---|---|---|---|---|---|
| archive-desktop | 99 | 100 | 100 | 69 | 0.9 | 0 | 0.0 |
| archive-mobile | 92 | 100 | 100 | 69 | 3.3 | 0 | 0.0 |
| article-desktop | 100 | 100 | 100 | 69 | 0.7 | 0 | 0.001 |
| article-mobile | 95 | 100 | 100 | 69 | 3.0 | 0 | 0.023 |
| calcul-desktop | 100 | 100 | 96 | 69 | 0.6 | 0 | 0.004 |
| calcul-mobile | 98 | 100 | 100 | 66 | 1.9 | 0 | 0.004 |
| carte-desktop | 98 | 100 | 96 | 100 | 1.0 | 4 | 0.001 |
| carte-mobile | 83 | 100 | 100 | 100 | 4.2 | 115 | 0.002 |
| categorie-desktop | 91 | 100 | 96 | 69 | 1.7 | 9 | 0.004 |
| categorie-mobile | 88 | 100 | 100 | 69 | 3.9 | 0 | 0 |
| etape-desktop | 100 | 100 | 100 | 69 | 0.5 | 0 | 0.0 |
| etape-mobile | 99 | 100 | 100 | 69 | 2.1 | 36 | 0.0 |
| etapes-desktop | 99 | 100 | 96 | 69 | 0.8 | 0 | 0.005 |
| etapes-mobile | 96 | 100 | 100 | 69 | 2.1 | 81 | 0.04 |
| fiche-desktop | 100 | 100 | 100 | 69 | 0.8 | 0 | 0.001 |
| fiche-mobile | 95 | 100 | 100 | 69 | 2.7 | 22 | 0 |
| home-desktop | 99 | 100 | 100 | 100 | 1.0 | 0 | 0.013 |
| home-mobile | 91 | 100 | 100 | 100 | 3.2 | 0 | 0.007 |
| meteo-desktop | 100 | 100 | 100 | 66 | 0.5 | 0 | 0.003 |
| meteo-mobile | 99 | 100 | 100 | 66 | 1.9 | 0 | 0.005 |
| page-desktop | 100 | 100 | 100 | 69 | 0.6 | 0 | 0.0 |
| page-mobile | 95 | 100 | 100 | 69 | 2.9 | 0 | 0.0 |
| planificateur-desktop | 100 | 100 | 100 | 66 | 0.5 | 0 | 0.01 |
| planificateur-mobile | 97 | 100 | 100 | 66 | 2.6 | 0 | 0.018 |

Hecho el 06/10 (de 50–91 → 83–99 en móvil; accesibilidad 92 → 100):
- ✅ robots.txt válido (sin `Content-Signal`, directiva desconocida para Lighthouse).
- ✅ Contraste AA y nombres accesibles (carte, contenido, étapes, météo, marcadores del mapa); títulos del contenido sin saltos.
- ✅ Sirdata, pcm.js y GA4 a la primera interacción, en cadena tras el stub de consentimiento (el banner era el LCP).
- ✅ CLS: calcul 0,13 → 0 y `/etapes/` 0,46 → 0,04 (huecos de mapa reservados, estado inicial antes de pintar).
- ✅ Carte: sin esqueleto, Google Maps bajo demanda (móvil: al abrir la vista Carte), 2 imágenes sin lazy.
- ✅ Ficha: precarga de la cabecera en WebP (antes precargaba la JPG). Archivo: primera imagen sin lazy.
- ✅ Météo: enlaces a las previsiones de Météo-France en lugar de los widgets de booked.net.
- ✅ `remote.sh deploy` vacía la caché de WP Fastest Cache (servía el HTML de antes del despliegue).

Pendiente para el 100:
- 🔍 Carte (06/10, 2.ª ronda): CSS en línea, sin `description` en el JSON (−19 KB comprimidos), precarga WebP de la
  primera tarjeta, una sola imagen sin lazy → móvil 83 → 89–91, escritorio 98–99; categoría móvil 83–88 (ruido entre
  pasadas). Techo actual: fotos de tarjeta de 90–120 KB (768 px WebP q78) y fuentes Sora+Manrope (57 KB) en 4G simulado.
  Siguiente palanca: tarjetas a 480–600 px o calidad 65 (afecta a todas las imágenes del plugin).
- ⬜ Rendimiento móvil < 95: home (91), archivo (92); carte/categoría ver arriba.
- ✅ Fotos de tarjeta de la carte: variante `archivo.jpg.c640.webp` (640 px, calidad 65), generadas para las 251 fichas
  (`tests/gen-card-images.php`, solo añade archivos): 75 KB → 30 KB de media.
- ⬜ **Los `.webp` generales del plugin (TASK-048) salen a calidad 86, no 78:** al convertir JPG → WebP WordPress
  restablece la calidad por defecto del formato e ignora `set_quality()`; solo el filtro `wp_editor_set_quality` la
  fija. Por eso pesan casi lo mismo que los JPG (91 KB vs 91 KB). Rehacerlos con el filtro (sobrescribe solo archivos
  generados por el plugin) aligeraría fichas, artículos y home. LCP 3–4 s en 4G simulado: HTML de la carte
  (140 KB, 254 tarjetas), fuentes precargadas en la home, imágenes de 90–120 KB en las tarjetas.
- ⬜ Buenas prácticas 96 en escritorio con mapa (calcul, carte, categoría, étapes): `image-size-responsive` de una imagen
  interna de Google Maps (`transparent.png`). Es de Google: no se arregla desde el sitio sin quitar el mapa.
- ⬜ Volver a medir tras publicar (TASK-049) con CrUX: datos reales, no laboratorio (PRD-016).

**OpenSEO** (crawl de 3 000 páginas desde `/accueil-2026/`; solo cuenta lo de las páginas 2026, el resto son páginas
antiguas del tema a las que llega por enlaces):
- ⬜ Enlaces internos rotos en el texto de 2 artículos: `/le-canal/histoire-2026/Pinpin` (enlace relativo mal escrito) y
  `/occitanie/toulouse_31555/` (404); un enlace con `%20http:` pegado (`…visiter-castelnaudary…`) y otro con código PHP en
  el href (`nouveau-catalogue-2014-les-canalous`). Se corrigen en el editor de WordPress o filtrando en la plantilla.
- ⬜ 100 fichas con meta description corta (33–60 car.): el texto del cliente es corto → completar con categoría y étape.
- ⬜ 18 artículos sin meta description y 2 sin H1 (contenido vacío o solo imagen).
- ⬜ 426 titles largos (coincide con §3.3) y 794 páginas con salto de nivel de títulos (H1 → H3 en tarjetas y pies de
  plantilla; revisar los `<h3>` de las tarjetas de la carte y del pie).
- ⬜ 564 páginas a profundidad 5+ desde la home (artículos antiguos): enlazarlos desde sus categorías y étapes.
- ⬜ 37 fichas con respuesta lenta (1,7–2,4 s sin caché): medir de nuevo con la caché activa.
- ℹ️ 290 « canonicalizadas »: son las variantes `?type=` de la carte privada, correcto (al publicar son `/categorie/…`).

## 3. Plantillas

% de páginas de cada tipo que pasan cada comprobación. FAQPage no aplica a artículos ni archivos del blog (texto de los
clientes). « H2 en forma de pregunta » no ve las preguntas en H3 (la FAQ de la columna « À savoir » de las cartes usa H3).

| Tipo | Páginas | Clics/año | Title 39–65 car. | Meta description 120–165 car. | Un solo H1 | Canonical | OG + Twitter card | BreadcrumbList | FAQPage | dateModified | ≥ 300 palabras | H2 en forma de pregunta | ≥ 3 enlaces internos | Enlace a fuente externa | Tabla o lista |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| home | 1 | 4756 | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % |
| carte | 1 | 61 | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ❌ 0 % |
| categorie | 61 | 975 | ❌ 49 % | ⚠️ 93 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ⚠️ 90 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ⚠️ 90 % | ❌ 3 % |
| region | 110 | 145 | ⚠️ 90 % | ⚠️ 99 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ⚠️ 81 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ⚠️ 81 % | ❌ 0 % |
| mot-cle | 14 | 19 | ⚠️ 71 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ⚠️ 93 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ⚠️ 93 % | ❌ 0 % |
| fiche | 254 | 10919 | ⚠️ 73 % | ⚠️ 94 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ⚠️ 76 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % |
| etapes | 1 | 0 | ❌ 0 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ✅ 100 % |
| etape | 20 | 0 | ❌ 25 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ⚠️ 75 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ✅ 100 % |
| calcul | 1 | 7751 | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % |
| planificateur | 1 | 0 | ✅ 100 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ❌ 0 % | ❌ 0 % | ❌ 0 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % |
| contenu-page | 70 | 12298 | ❌ 19 % | ⚠️ 99 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ❌ 3 % | ✅ 100 % | ⚠️ 61 % | ❌ 1 % | ✅ 100 % | ⚠️ 70 % | ✅ 100 % |
| contenu-article | 1933 | 17002 | ❌ 44 % | ⚠️ 82 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ⚠️ 50 % | ❌ 0 % | ✅ 100 % | ❌ 7 % | ✅ 100 % |
| archive | 24 | 45 | ❌ 12 % | ❌ 0 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ✅ 100 % | ❌ 0 % | ❌ 29 % | ❌ 0 % | ✅ 100 % | ❌ 0 % | ✅ 100 % |

No responden 200: 4 — /marches-de-produits-locaux-a-port-lauragais-20-juin-2026/ (301), /post-category/croisiere-bateau/ (404), /post-category/histoire/ (404), /post-category/lieux-dinformations/ (404)

### 3.1 Calcul de distance (1 página, 7 751 clics)
- ⬜ Title de 70 car. → ≤ 60 sin perder « calcul de distance » ni « écluses ».
- ⬜ FAQPage: preguntas reales de Search Console sobre distancias y tiempos, respondidas con el propio cálculo.
- ⬜ dateModified en el schema (fecha de los datos de esclusas/trazado).

### 3.2 Páginas de contenido propias (70 páginas, 12 298 clics)
- ⬜ Titles cortos (81 % con menos de 39 car.): regla de plantilla, si el título es corto el sufijo pasa a
  « | L'Officiel du Canal du Midi ».
- ⬜ FAQ visible + FAQPage en las páginas guía, con datos y fuente (VNF, nuestras páginas de reglas): se hace página a
  página en §4, empezando por Robine, écluses, période de navigation, règles de navigation.
- ⬜ 30 % sin ningún enlace a fuente externa: añadir la fuente oficial donde haya datos (VNF, Météo-France, UNESCO).

### 3.3 Artículos (1 933, 17 002 clics)
- ⬜ Titles > 65 car. (992): quitar el sufijo « — Canal du Midi » cuando el título ya es largo (sin tocar el título del
  cliente).
- ⬜ Meta description < 120 car. (347, 41 vacías): completar con el comienzo del texto cuando el extracto es corto.
- ⬜ **Decisión:** 381 artículos con menos de 100 palabras (noticias antiguas, anuncios): ¿siguen indexables, pasan a
  `noindex` o se agrupan en su archivo? Mirar antes sus clics en `docs/data/auditoria-2026.tsv`.
- ⬜ dateModified correcto en Article (está; comprobar que es la fecha real de modificación y no la de hoy).

### 3.4 Fichas (254, 10 919 clics)
- ⬜ Titles: 16 de más de 65 car. y el resto de los que fallan, cortos (« Port de Sète — Canal du Midi »): añadir la
  commune o la categoría cuando el título es corto.
- ⬜ 62 fichas con menos de 300 palabras: el texto es del cliente; reforzar con datos propios (étape, PK, distancia al
  canal, esclusa más cercana), que ya existen en el plugin.

### 3.5 Cartes: categorías, regiones y etiquetas (185 páginas, 1 139 clics)
- ⬜ Titles > 65 car. en 31 categorías (« … au bord du Canal du Midi : N adresses | L'Officiel »): acortar el patrón.
- ⬜ Meta description corta en etiquetas (0 % en rango).
- ⬜ Páginas sin FAQPage (10 % de categorías, 19 % de regiones): comprobar si es porque tienen muy pocas fichas.
- ⬜ **Decisión:** 110 regiones con 145 clics en total: ¿`noindex` para las que tienen 1–2 fichas (contenido fino)?

### 3.6 Étapes (`/etapes/` y 20 `/etape/<x>/`, nuevas)
- ⬜ `/etapes/`: title de 90 car. y description de 173 → acortar; añadir BreadcrumbList, dateModified y fuentes externas.
- ⬜ `/etape/<x>/`: titles > 65 en 15 de 20 (el patrón « — étape du Canal du Midi : que faire, où dormir, distances »);
  dateModified; enlace a la fuente de los datos (OT, VNF).
- ⬜ 5 étapes con menos de 300 palabras: Narbonne (154), Port-la-Nouvelle (125), Marseillan, Trèbes, Portiragnes.

### 3.7 Planificateur (1 página)
- ✅ 06/10: title, description, canonical, OG y JSON-LD (WebPage + BreadcrumbList); `<head>` del tema aligerado.

### 3.8 Archivo del blog (24 páginas, 45 clics)
- ⬜ Titles cortos y descriptions de 93 car.: patrón de plantilla con el nombre de la categoría + « actualités du Canal du Midi ».

## 4. Páginas una a una (por clics)

Columna « Falla » (claves de la tabla de §3): title, desc, h1, canon, social, crumb, faq, date, words (menos de 300),
h2q (ningún H2 en pregunta), links, ext (sin fuente externa), struct (sin tabla ni lista). Lo que se arregla en la
plantilla (§3) desaparecerá de aquí al volver a medir; esta lista es para **revisar el contenido** de cada página.

### Páginas con tráfico (305 URLs, ≥ 20 clics/año: 49318 clics, 91 % del total)

| Estado | URL | Tipo | Clics | Pos. | Palabras | Falla |
|---|---|---|---|---|---|---|
| ⬜ | `/calcul-de-distance-canal-du-midi/` | calcul | 7751 | 7.3 | 546 | title, faq, date |
| ⬜ | `/` | home | 4756 | 9.1 | 1264 |  |
| ⬜ | `/canal-de-la-robine/` | contenu-page | 2291 | 7.2 | 387 | title, faq, h2q, ext |
| ⬜ | `/longeur-largeur-des-ecluses-tirant-deau-tirant-dair-sur-le-canal-du-midi/` | contenu-page | 1702 | 7.4 | 403 | title, faq, h2q |
| ⬜ | `/voie-verte-et-veloroute/` | contenu-page | 1524 | 18.1 | 1344 | h2q |
| ⬜ | `/peniches-a-vendre-sur-le-canal-tout-savoir-avant-dacheter/` | contenu-article | 1431 | 10.7 | 574 | title, h2q, ext |
| ⬜ | `/navigation/periode-de-navigation/` | contenu-page | 1037 | 7.4 | 233 | title, faq, words, h2q, ext |
| ⬜ | `/meteo-du-canal-du-midi/` | contenu-page | 953 | 7.8 | 3601 | h2q |
| ⬜ | `/la-librairie-du-somail-pantheonisee-par-la-television/` | contenu-article | 570 | 9.2 | 469 | title, h2q, ext |
| ⬜ | `/les-ecluses-du-canal-du-midi-2/` | contenu-page | 543 | 10.3 | 1110 | title, faq, h2q |
| ⬜ | `/beziers-la-future-gare-tgv-au-centre-des-debats/` | contenu-article | 538 | 9.8 | 768 | h2q, ext |
| ⬜ | `/fiche/port-de-sete/` | fiche | 515 | 9.2 | 558 | title, h2q |
| ⬜ | `/navigation/regles-de-navigation/` | contenu-page | 502 | 11.5 | 4238 | title, faq, h2q, ext |
| ⬜ | `/le-lac-de-saint-ferreol-sera-bientot-vide-un-spectacle-impressionnant/` | contenu-article | 479 | 7.1 | 427 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-portiragnes/` | fiche | 468 | 5.8 | 295 | title, words, h2q |
| ⬜ | `/auberge-de-la-croisade-a-cruzy-34310-nouveaux-menus/` | contenu-article | 462 | 7.3 | 176 | title, words, h2q, ext |
| ⬜ | `/3-de-bordeaux-a-sete-780-km-sur-les-rives-des-canaux-de-lentre-deux-mers/` | contenu-article | 452 | 8.9 | 360 | title, h2q, ext |
| ⬜ | `/fiche/port-cassafieres-portiragne/` | fiche | 452 | 5.9 | 324 | h2q |
| ⬜ | `/fiche/mairie-de-salleles-daude/` | fiche | 432 | 9.8 | 386 | h2q |
| ⬜ | `/canal-du-midi-en-bateau-comment-sy-preparer/` | contenu-article | 424 | 10.3 | 792 | h2q |
| ⬜ | `/balade-en-bateau-sur-le-canal-de-la-robine/` | contenu-article | 408 | 16.3 | 161 | words, h2q, ext |
| ⬜ | `/les-meilleurs-lieux-pour-courir-a-toulouse/` | contenu-article | 390 | 10.0 | 1240 | h2q, ext |
| ⬜ | `/categorie/camping/` | categorie | 378 | 12.3 | 1408 | h2q |
| ⬜ | `/carcassonne-decouvrez-lepanchoir-de-foucaud-maison-eclusiere/` | contenu-article | 376 | 5.0 | 214 | title, words, h2q, ext |
| ⬜ | `/fiche/port-sud-ramonville-st-agne/` | fiche | 354 | 8.9 | 609 | title, h2q |
| ⬜ | `/conditions-dutilisation-des-chemins-de-halage-le-long-du-canal-du-midi-a-velo/` | contenu-page | 350 | 9.7 | 335 | title, faq, h2q |
| ⬜ | `/tarn-et-garonne-vnf-loue-ses-maisons-eclusieres/` | contenu-article | 343 | 7.7 | 471 | h2q, ext |
| ⬜ | `/fiche/port-neuf-beziers/` | fiche | 324 | 7.6 | 314 | title, h2q |
| ⬜ | `/balade-en-roller-2/` | contenu-page | 317 | 8.0 | 768 | title, faq, h2q |
| ⬜ | `/beziers-et-environs-agenda-du-jour-et-du-week-end-a-venir/` | contenu-article | 306 | 11.7 | 880 | title, h2q, ext |
| ⬜ | `/fiche/port-la-robine/` | fiche | 293 | 13.1 | 387 | h2q |
| ⬜ | `/fiche/port-de-homps/` | fiche | 291 | 11.4 | 389 | title, h2q |
| ⬜ | `/alimentation-en-eau-du-canal/` | contenu-page | 276 | 8.5 | 983 | faq, h2q |
| ⬜ | `/saint-felix-lauragais-les-pecheurs-obliges-de-quitter-le-lac-de-lenclas/` | contenu-article | 262 | 7.4 | 573 | title, h2q, ext |
| ⬜ | `/vnf-recrute-des-eclusiers-saisonniers/` | contenu-article | 242 | 8.7 | 619 | h2q, ext |
| ⬜ | `/la-faune-et-la-flore/` | contenu-page | 236 | 5.7 | 805 | title, faq, h2q |
| ⬜ | `/fiche/port-saint-sauveur-toulouse/` | fiche | 228 | 7.0 | 516 | h2q |
| ⬜ | `/fiche/aux-petits-oignons/` | fiche | 218 | 7.0 | 333 | h2q |
| ⬜ | `/voici-le-nouveau-parcours-du-marathon-de-toulouse-qui-fete-son-dixieme-anniversaire/` | contenu-article | 204 | 4.6 | 486 | title, h2q, ext |
| ⬜ | `/le-canal/histoire/` | contenu-page | 200 | 10.0 | 1160 | title, faq, h2q |
| ⬜ | `/fiche/port-du-somail/` | fiche | 197 | 7.6 | 367 | h2q |
| ⬜ | `/castelnaudary-donadery-un-lieu-de-vie-et-de-partage-en-plus-du-futur-musee/` | contenu-article | 195 | 4.2 | 882 | title, h2q, ext |
| ⬜ | `/fiche/le-trouve-tout-du-livre/` | fiche | 185 | 9.0 | 414 | h2q |
| ⬜ | `/categorie/activites-loisirs/` | categorie | 178 | 20.2 | 6417 | title, h2q, struct |
| ⬜ | `/fiche/camping-de-pepieux/` | fiche | 176 | 15.8 | 432 | title, h2q |
| ⬜ | `/fiche/port-de-bram/` | fiche | 171 | 7.0 | 363 | title, h2q |
| ⬜ | `/television-lequipe-de-tournage-de-la-serie-candice-renoir-est-a-agde-et-au-bord-du-canal-du-midi/` | contenu-article | 167 | 5.6 | 484 | title, h2q, ext |
| ⬜ | `/castanet-tolosan-31-le-lac-un-lieu-de-promenade-le-week-end/` | contenu-article | 161 | 5.6 | 300 | title, h2q, ext |
| ⬜ | `/une-belle-vie-au-bord-du-canal/` | contenu-article | 161 | 9.3 | 694 | h2q, ext |
| ⬜ | `/video-de-presentation-du-canal-du-midi/` | contenu-page | 159 | 10.2 | 161 | title, faq, words, h2q, ext |
| ⬜ | `/fiche/port-de-trebes/` | fiche | 157 | 8.2 | 478 | title, h2q |
| ⬜ | `/le-tour-du-seuil-de-naurouze/` | contenu-article | 155 | 9.4 | 448 | h2q, ext |
| ⬜ | `/les-marches-a-port-la-nouvelle/` | contenu-article | 155 | 9.4 | 155 | words, h2q, ext |
| ⬜ | `/fiche/moulin-bladier-de-bessan/` | fiche | 154 | 6.3 | 386 | h2q |
| ⬜ | `/fiche/camping-municipal-de-tounel/` | fiche | 152 | 18.0 | 499 | h2q |
| ⬜ | `/le-chancre-colore-du-platane/` | contenu-article | 150 | 9.5 | 699 | h2q, ext |
| ⬜ | `/jours-de-marches-proche-du-canal-du-midi/` | contenu-page | 148 | 9.8 | 170 | faq, words, h2q, ext |
| ⬜ | `/fiche/port-des-onglous/` | fiche | 146 | 7.9 | 323 | h2q |
| ⬜ | `/fiche/cave-cooperative-du-chateau-de-ventenac/` | fiche | 142 | 9.9 | 997 | title, h2q |
| ⬜ | `/toulouse-pourquoi-les-arbres-qui-bordent-le-canal-du-midi-ont-ils-tous-un-numero/` | contenu-article | 139 | 7.2 | 387 | title, h2q, ext |
| ⬜ | `/categorie/ports/` | categorie | 136 | 9.0 | 1810 | h2q, struct |
| ⬜ | `/la-saint-valentin-a-l-auberge-de-la-croisade-34-cruzy/` | contenu-article | 135 | 13.8 | 327 | title, h2q, ext |
| ⬜ | `/fiche/camping-municipal-de-salleles-daude/` | fiche | 134 | 9.4 | 568 | h2q |
| ⬜ | `/narbonne-a-la-decouverte-du-quartier-de-bourg/` | contenu-article | 133 | 7.2 | 264 | words, h2q, ext |
| ⬜ | `/informations-croisiere/` | contenu-page | 131 | 26.1 | 1486 | title, faq, h2q |
| ⬜ | `/vignoble-de-picpoul-de-pinet/` | contenu-page | 126 | 7.5 | 666 | faq, h2q |
| ⬜ | `/le-canal-en-famille-une-appli-mobile-pour-partir-a-laventure-sur-le-canal-du-midi/` | contenu-article | 125 | 7.7 | 522 | title, h2q |
| ⬜ | `/on-a-teste-pour-vous-le-lac-de-saint-ferreol-a-velo-depuis-toulouse-via-le-canal-du-midi/` | contenu-article | 124 | 8.8 | 1073 | title, h2q, ext |
| ⬜ | `/fiche/maison-de-la-haute-garonne/` | fiche | 121 | 11.5 | 553 | h2q |
| ⬜ | `/le-grand-bief/` | contenu-page | 119 | 9.0 | 352 | title, faq, h2q |
| ⬜ | `/toulouse-meteo-france-lannonce-a-toulouse-le-mois-de-janvier-a-ete-lun-des-plus-froids-de-ces-25-dernieres-annees-seul-le-mois-de-janvier-2012-avait-ete-plus-glacial/` | contenu-article | 119 | 8.2 | 408 | title, h2q, ext |
| ⬜ | `/fiche/port-de-lembouchure-toulouse/` | fiche | 119 | 8.0 | 306 | h2q |
| ⬜ | `/la-creation-du-lac-de-marciac-le-long-voyage-de-la-peniche/` | contenu-article | 117 | 8.6 | 445 | title, h2q, ext |
| ⬜ | `/fiche/halte-plaisance-fluviale-de-frontignan/` | fiche | 117 | 8.2 | 392 | h2q |
| ⬜ | `/ramonville-saint-agne-joies-du-barbecue-au-bord-du-canal/` | contenu-article | 116 | 9.7 | 429 | title, h2q, ext |
| ⬜ | `/fiche/port-de-salleles-daude/` | fiche | 116 | 10.7 | 421 | h2q |
| ⬜ | `/voie-verte-du-lido/` | contenu-article | 109 | 10.3 | 298 | title, words, h2q, ext |
| ⬜ | `/visite-du-chateau-de-bonrepos-riquet/` | contenu-article | 109 | 10.1 | 260 | words, h2q, ext |
| ⬜ | `/fiche/port-de-colombiers/` | fiche | 109 | 8.7 | 357 | title, h2q |
| ⬜ | `/vignoble-du-cabardes/` | contenu-page | 107 | 6.5 | 496 | title, faq, h2q |
| ⬜ | `/scenes-de-meurtres-a-carcassonne/` | contenu-article | 107 | 11.1 | 595 | h2q, ext |
| ⬜ | `/fiche/office-de-tourisme-de-castelnaudary/` | fiche | 107 | 8.4 | 453 | h2q |
| ⬜ | `/villefranche-de-lauragais-rosalie-et-canoe-sur-le-canal-du-midi/` | contenu-article | 106 | 9.4 | 404 | h2q, ext |
| ⬜ | `/fiche/la-guinguette-restaurant/` | fiche | 106 | 10.4 | 383 | h2q |
| ⬜ | `/beziers-un-telepherique-entre-canal-du-midi-et-centre-ville-devrait-voir-le-jour/` | contenu-article | 105 | 4.6 | 314 | title, h2q, ext |
| ⬜ | `/le-paddle-sur-le-canal-du-midi/` | contenu-article | 104 | 5.4 | 181 | title, words, h2q, ext |
| ⬜ | `/des-velos-a-louer-dans-le-vieux-village-a-trebes/` | contenu-article | 104 | 6.4 | 303 | h2q, ext |
| ⬜ | `/fiche/port-dargens-minervois/` | fiche | 104 | 8.6 | 235 | desc, words, h2q |
| ⬜ | `/navigation/les-panneaux/` | contenu-page | 102 | 10.0 | 209 | title, faq, words, h2q, ext |
| ⬜ | `/fiche/port-de-segala/` | fiche | 100 | 6.2 | 276 | words, h2q |
| ⬜ | `/fiche/ecluse-dhomps/` | fiche | 99 | 8.5 | 273 | title, words, h2q |
| ⬜ | `/fiche/port-de-lauragais-avignonet-lauragais/` | fiche | 98 | 9.1 | 278 | title, words, h2q |
| ⬜ | `/villeneuve-les-beziers/` | contenu-page | 96 | 11.5 | 299 | title, faq, words, h2q |
| ⬜ | `/photos-canal-du-midi/` | contenu-page | 96 | 7.9 | 44 | title, desc, faq, words, h2q, ext |
| ⬜ | `/fiche/port-de-serignan/` | fiche | 94 | 8.5 | 374 | title, h2q |
| ⬜ | `/narbonne-le-pont-des-marchands-sur-la-robine/` | contenu-article | 92 | 6.8 | 189 | words, h2q, ext |
| ⬜ | `/des-chenes-pour-remplacer-les-platanes-malades-du-canal-du-midi/` | contenu-article | 90 | 7.8 | 309 | h2q, ext |
| ⬜ | `/fiche/chez-fabienne/` | fiche | 90 | 5.6 | 330 | title, h2q |
| ⬜ | `/fiche/hotel-ibis-budget/` | fiche | 89 | 11.1 | 443 | h2q |
| ⬜ | `/fiche/port-du-agde/` | fiche | 88 | 10.5 | 262 | title, desc, words, h2q |
| ⬜ | `/toulouse-transforme-les-allees-jean-jaures-en-rambla/` | contenu-article | 87 | 8.0 | 538 | title, h2q, ext |
| ⬜ | `/navigation/passer-une-ecluse/` | contenu-page | 87 | 18.3 | 112 | title, faq, words, h2q, ext |
| ⬜ | `/le-somail-decouvrez-la-maison-bonnal/` | contenu-article | 86 | 8.1 | 160 | words, h2q, ext |
| ⬜ | `/carcassonne-presentation-officielle-dune-application-mobile-sur-le-canal-du-midi/` | contenu-article | 86 | 6.2 | 188 | title, words, h2q, ext |
| ⬜ | `/fiche/port-de-marseillan/` | fiche | 86 | 11.7 | 345 | title, h2q |
| ⬜ | `/bassin-de-thau/` | contenu-page | 85 | 11.9 | 649 | title, faq, h2q |
| ⬜ | `/colombiers/` | contenu-page | 84 | 13.0 | 319 | title, faq, h2q |
| ⬜ | `/navigation/permis-de-conduire/` | contenu-page | 84 | 8.6 | 279 | title, faq, words, h2q, ext |
| ⬜ | `/fiche/port-de-capestang/` | fiche | 83 | 7.3 | 385 | title, h2q |
| ⬜ | `/poilhes/` | contenu-page | 82 | 10.0 | 356 | title, faq, h2q |
| ⬜ | `/fiche/port-de-valras-plage/` | fiche | 82 | 11.1 | 431 | h2q |
| ⬜ | `/la-barque-de-poste-le-bateau-du-canal-du-midi/` | contenu-article | 80 | 7.6 | 613 | h2q, ext |
| ⬜ | `/le-fret-une-solution-pour-renflouer-le-canal-du-midi/` | contenu-article | 79 | 18.3 | 1408 | h2q, ext |
| ⬜ | `/ou-trouver-du-carburant-pres-du-canal-du-midi/` | contenu-article | 79 | 8.4 | 225 | words, h2q, ext |
| ⬜ | `/fiche/ecluses-de-fonseranes/` | fiche | 79 | 12.3 | 944 | h2q |
| ⬜ | `/fiche/port-de-carcassonne/` | fiche | 79 | 8.7 | 388 | title, h2q |
| ⬜ | `/fiche/ecluse-dargens/` | fiche | 78 | 7.5 | 273 | words, h2q |
| ⬜ | `/fiche/crisboat/` | fiche | 77 | 8.2 | 339 | h2q |
| ⬜ | `/fetes-et-festivals-salleles-daude/` | contenu-article | 76 | 9.6 | 343 | h2q, ext |
| ⬜ | `/fiche/office-de-tourisme-port-la-nouvelle-cote-du-midi/` | fiche | 76 | 11.0 | 402 | title, h2q |
| ⬜ | `/en-croisiere-a-bord-de-la-peniche-surcouf-vous-allez-decouvrir-le-vrai-canal-du-midi/` | contenu-article | 75 | 10.9 | 544 | title, h2q, ext |
| ⬜ | `/eclusier-sur-le-canal-du-midi/` | contenu-article | 73 | 22.8 | 577 | title, h2q, ext |
| ⬜ | `/fiche/site-de-negra/` | fiche | 73 | 6.9 | 407 | h2q |
| ⬜ | `/fiche/port-dagde/` | fiche | 71 | 8.4 | 396 | title, h2q |
| ⬜ | `/lac-de-saint-ferreol-pres-de-toulouse-derniere-ligne-droite-avant-le-remplissage/` | contenu-article | 70 | 11.9 | 596 | title, h2q, ext |
| ⬜ | `/lezignan-corbieres-le-marche-de-noel-revient-enchanter-le-grand-moulin/` | contenu-article | 69 | 8.4 | 406 | title, h2q, ext |
| ⬜ | `/fiche/la-petite-maison-du-velo/` | fiche | 68 | 8.8 | 423 | h2q |
| ⬜ | `/fiche/ecluse-de-bagnas/` | fiche | 67 | 5.9 | 294 | words, h2q |
| ⬜ | `/argens-minervois-charmant-village-traverse-par-le-canal-du-midi/` | contenu-article | 66 | 9.9 | 185 | words, h2q, ext |
| ⬜ | `/un-roi-a-la-place-de-riquet-pourquoi-la-statue-des-allees-jean-jaures-a-bien-failli-ne-jamais-exister/` | contenu-article | 65 | 7.5 | 417 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-renneville/` | fiche | 65 | 6.5 | 279 | title, words, h2q |
| ⬜ | `/toulouse-les-soirees-endiablees-de-la-peniche-le-samsara/` | contenu-article | 64 | 11.4 | 421 | title, h2q, ext |
| ⬜ | `/vignoble-du-minervois/` | contenu-page | 63 | 15.9 | 618 | title, faq, h2q |
| ⬜ | `/les-peniches-la-meilleure-solution-pour-decouvrir-le-canal-du-midi/` | contenu-article | 63 | 19.7 | 497 | title, h2q |
| ⬜ | `/en-balade-sur-le-canal-du-midi/` | contenu-article | 63 | 13.2 | 1117 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-marseillette/` | fiche | 63 | 10.8 | 287 | title, words, h2q |
| ⬜ | `/explorer/` | carte | 61 | 10.3 | 9422 | h2q, struct |
| ⬜ | `/gardouch-un-nouveau-bistrot-sinstalle-dans-la-maison-eclusiere/` | contenu-article | 61 | 9.3 | 650 | title, h2q, ext |
| ⬜ | `/le-canal-du-midi-classe-mais-en-danger/` | contenu-article | 60 | 5.5 | 1378 | h2q, ext |
| ⬜ | `/fiche/office-de-tourisme-le-somail-cote-du-midi/` | fiche | 60 | 14.0 | 465 | h2q |
| ⬜ | `/boutique-canal-du-midi/` | contenu-page | 59 | 8.4 | 90 | title, faq, words, h2q |
| ⬜ | `/le-canal-du-midi-a-lemission-des-racines-et-des-ailes-et-exposition/` | contenu-article | 58 | 7.7 | 317 | title, h2q |
| ⬜ | `/noel-au-chateau-les-carrasses-34/` | contenu-article | 57 | 6.0 | 186 | words, h2q, ext |
| ⬜ | `/le-manuscrit-signe-par-louis-xiv-expose-a-toulouse/` | contenu-article | 57 | 6.7 | 702 | title, h2q, ext |
| ⬜ | `/castanet-tolosan-davantage-de-brochets-seront-laches-dans-le-canal/` | contenu-article | 56 | 11.9 | 664 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-montgiscard/` | fiche | 54 | 7.8 | 280 | title, words, h2q |
| ⬜ | `/toulouse-la-maison-eclusiere-de-saint-pierre-enfin-un-lieu-culturel/` | contenu-article | 53 | 8.9 | 560 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-villepinte/` | fiche | 53 | 6.5 | 283 | title, words, h2q |
| ⬜ | `/castelnaudary-coup-de-coeur-pour-la-minoterie-de-naurouze/` | contenu-article | 51 | 8.5 | 328 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-ronde-dagde/` | fiche | 51 | 9.3 | 435 | title, h2q |
| ⬜ | `/fiche/ecluse-dayguesvives/` | fiche | 51 | 7.2 | 278 | title, words, h2q |
| ⬜ | `/categorie/commerce-alimentaire/` | categorie | 51 | 9.0 | 1745 | title, h2q, struct |
| ⬜ | `/grace-a-vauban-la-rigole-a-pu-alimenter-le-canal-du-midi/` | contenu-article | 50 | 8.5 | 563 | h2q, ext |
| ⬜ | `/restaurant-chez-leclusier-a-colombiers/` | contenu-article | 50 | 8.1 | 210 | words, h2q, ext |
| ⬜ | `/fiche/ecluse-bayard/` | fiche | 50 | 9.6 | 285 | words, h2q |
| ⬜ | `/fiche/la-roue-qui-tourne/` | fiche | 50 | 9.4 | 498 | h2q |
| ⬜ | `/le-role-de-leclusier/` | contenu-article | 49 | 10.2 | 565 | title, h2q, ext |
| ⬜ | `/fiche/hotel-premiere-classe-toulouse-nord-sesquieres/` | fiche | 49 | 10.5 | 394 | h2q |
| ⬜ | `/pont-canal-du-repudre/` | contenu-page | 48 | 10.2 | 310 | title, faq, h2q, ext |
| ⬜ | `/revel-deux-jours-de-visites-gratuites-journees-du-patrimoine-34e-journees-du-patrimoine/` | contenu-article | 48 | 16.0 | 649 | title, h2q, ext |
| ⬜ | `/fiche/port-la-fabrique-la-redorte/` | fiche | 48 | 6.6 | 320 | h2q |
| ⬜ | `/fiche/port-de-palavas-les-flots/` | fiche | 48 | 10.0 | 410 | h2q |
| ⬜ | `/ouvrage-sur-le-libron/` | contenu-page | 46 | 9.8 | 452 | title, faq, h2q |
| ⬜ | `/toulouse-peniches-epaves-le-cote-sombre-du-canal-du-midi/` | contenu-article | 46 | 8.3 | 454 | h2q, ext |
| ⬜ | `/le-menu-d-ete-de-l-auberge-de-la-croisade-a-cruzy-34/` | contenu-article | 46 | 12.5 | 197 | title, words, h2q, ext |
| ⬜ | `/fiche/poterie-de-naurouze/` | fiche | 46 | 8.6 | 455 | h2q |
| ⬜ | `/retour-sur-le-voyage-extraordinaire-de-bruno-sananes-laventurier-du-quotidien/` | contenu-article | 45 | 6.8 | 635 | title, h2q, ext |
| ⬜ | `/bize-minervois-8eme-grand-show-freestyle-motocross/` | contenu-article | 45 | 6.3 | 66 | title, desc, words, h2q, ext |
| ⬜ | `/a-toulouse-un-marche-au-jardin/` | contenu-article | 44 | 13.1 | 345 | h2q, ext |
| ⬜ | `/narbonne-st-pierre-la-mer-premiere-plage-non-fumeur-de-la-region/` | contenu-article | 44 | 6.4 | 474 | title, h2q, ext |
| ⬜ | `/castanet-tolosan-balades-sur-le-canal-du-midi/` | contenu-article | 44 | 9.6 | 310 | h2q, ext |
| ⬜ | `/manifestations-et-fetes-canal-du-midi/` | contenu-page | 44 | 14.6 | 325 | faq, h2q |
| ⬜ | `/ouvrages-d-art/` | contenu-page | 44 | 13.3 | 861 | title, faq, h2q, ext |
| ⬜ | `/fiche/mairie-de-montferrand/` | fiche | 44 | 10.2 | 361 | title, h2q |
| ⬜ | `/films-et-videos/` | contenu-page | 43 | 7.4 | 517 | title, faq, h2q |
| ⬜ | `/marseillan-le-promenoir-un-autre-regard-sur-le-bord-de-mer-la-rehabilitation-de-ce-cordon-pietonnier-a-ete-inauguree/` | contenu-article | 43 | 9.8 | 398 | title, h2q, ext |
| ⬜ | `/limoux-un-sandre-de-72-kg-a-ete-peche-samedi-dans-le-canal-du-midi/` | contenu-article | 42 | 12.4 | 360 | title, h2q, ext |
| ⬜ | `/herault-a-sete-reprise-explosive-pour-le-tournage-de-la-serie-candice-renoir/` | contenu-article | 42 | 10.3 | 194 | title, words, h2q, ext |
| ⬜ | `/beziers-longer-les-neuf-ecluses-a-pied-nest-plus-possible/` | contenu-article | 41 | 12.6 | 910 | title, h2q, ext |
| ⬜ | `/castelnaudary-la-ville-sanime-pour-les-fetes/` | contenu-article | 41 | 8.7 | 414 | h2q, ext |
| ⬜ | `/fiche/navicanal-port-lauragais/` | fiche | 41 | 9.9 | 481 | h2q |
| ⬜ | `/crue-de-laude-voies-navigables-de-france-mobilise-pour-louverture-de-la-saison-touristique/` | contenu-article | 40 | 8.2 | 671 | title, h2q, ext |
| ⬜ | `/la-mairie-communique-alerte-travaux-routiers-a-trebes/` | contenu-article | 40 | 9.2 | 177 | title, words, h2q, ext |
| ⬜ | `/vias-au-programme-des-journees-europeennes-du-patrimoine/` | contenu-article | 40 | 5.7 | 388 | title, h2q, ext |
| ⬜ | `/nous-contacter/` | contenu-page | 40 | 4.9 | 129 | title, faq, words, ext |
| ⬜ | `/fiche/ecluse-de-trebes/` | fiche | 40 | 8.5 | 301 | title, h2q |
| ⬜ | `/fiche/ecluse-du-bearnais/` | fiche | 40 | 10.7 | 319 | h2q |
| ⬜ | `/fiche/vacanceole-port-minervois/` | fiche | 40 | 12.0 | 464 | title, h2q |
| ⬜ | `/decouvrir-la-perle-noire-et-ses-tresors-a-velo-agde-u-34-km-de-pistes-cyclables-et-plusieurs-balades-en-vue/` | contenu-article | 39 | 8.1 | 590 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-vic/` | fiche | 39 | 7.3 | 292 | words, h2q |
| ⬜ | `/offre-d-emploi-sur-le-canal/` | contenu-article | 38 | 8.4 | 296 | words, h2q, ext |
| ⬜ | `/fiche/ecluse-de-beteille/` | fiche | 38 | 6.2 | 292 | words, h2q |
| ⬜ | `/capestang/` | contenu-page | 37 | 15.0 | 633 | title, faq, h2q |
| ⬜ | `/toulouse-l-occitania-un-lieu-unique/` | contenu-article | 37 | 13.6 | 331 | h2q |
| ⬜ | `/toulouse-il-y-a-deux-cents-ans-la-bataille-oubliee/` | contenu-article | 37 | 8.7 | 660 | title, h2q, ext |
| ⬜ | `/chateaux-dits-cathares/` | contenu-page | 35 | 33.8 | 549 | title, faq, h2q |
| ⬜ | `/vignoble-de-faugeres/` | contenu-page | 35 | 24.0 | 620 | title, faq, h2q |
| ⬜ | `/auterive-31-agenda-des-manifestations-du-15-au-31-mai/` | contenu-article | 35 | 15.1 | 2568 | title, h2q, ext |
| ⬜ | `/le-canal/ouvrages/` | contenu-page | 35 | 12.9 | 1039 | title, faq, h2q |
| ⬜ | `/fiche/mairie-de-argens-minervois/` | fiche | 35 | 11.9 | 530 | h2q |
| ⬜ | `/piste-cyclable-du-canal-du-midi-travaux-de-renovation/` | contenu-article | 34 | 7.2 | 234 | words, h2q, ext |
| ⬜ | `/toulouse-peniches-retour-du-tenace-et-de-la-belle-chaurienne/` | contenu-article | 34 | 8.7 | 369 | title, h2q, ext |
| ⬜ | `/feru-de-litterature-lecrivain-carcassonnais-bernard-vaissiere-publie-un-roman-les-champs-phlegreens-il-le-presentera-a-250-invites-le-samedi-15-novembre-a-lhotel-de/` | contenu-article | 34 | 6.2 | 628 | title, h2q, ext |
| ⬜ | `/fiche/moulin-de-saint-tibery/` | fiche | 34 | 5.6 | 379 | title, h2q |
| ⬜ | `/fiche/ecluse-de-lorb/` | fiche | 34 | 11.0 | 322 | h2q |
| ⬜ | `/fiche/ecluse-de-jouarres/` | fiche | 34 | 5.8 | 302 | h2q |
| ⬜ | `/fiche/ecluse-de-negra/` | fiche | 34 | 7.8 | 330 | h2q |
| ⬜ | `/tunnel-du-malpas/` | contenu-page | 33 | 10.2 | 347 | title, faq, h2q |
| ⬜ | `/une-base-de-canoe-a-ouvert-ses-portes-sur-le-canal-du-midi/` | contenu-article | 32 | 9.0 | 623 | h2q, ext |
| ⬜ | `/renneville-on-a-teste-pour-vous-la-balade-en-rosalie-sur-le-canal-du-midi/` | contenu-article | 32 | 7.6 | 1099 | title, h2q, ext |
| ⬜ | `/fiche/restaurant-association-les-cedres/` | fiche | 32 | 9.5 | 430 | h2q |
| ⬜ | `/fiche/ecluse-de-castanet/` | fiche | 32 | 10.0 | 345 | h2q |
| ⬜ | `/brax-la-voie-verte-traverse-la-commune/` | contenu-article | 31 | 6.2 | 278 | words, h2q, ext |
| ⬜ | `/fiche/camping-de-la-capelle/` | fiche | 31 | 9.4 | 419 | h2q |
| ⬜ | `/fiche/ecluse-de-laiguille/` | fiche | 31 | 7.6 | 286 | words, h2q |
| ⬜ | `/fiche/residence-chateau-de-jouarres/` | fiche | 31 | 11.9 | 478 | h2q |
| ⬜ | `/fiche/paulette-location-velo-narbonne/` | fiche | 31 | 15.5 | 361 | h2q |
| ⬜ | `/achetez-votre-vignette-plaisance/` | contenu-article | 30 | 10.8 | 199 | words |
| ⬜ | `/ouvrages-cartes-et-images/` | contenu-page | 30 | 7.8 | 413 | faq, h2q |
| ⬜ | `/vols-au-dessus-du-biterrois/` | contenu-article | 30 | 6.6 | 623 | h2q, ext |
| ⬜ | `/quatre-mois-de-travaux-au-bassin-de-lampy-les-cammazes-81/` | contenu-article | 30 | 10.2 | 231 | title, words, h2q, ext |
| ⬜ | `/toulouse-fete-le-canal-du-midi/` | contenu-article | 30 | 5.8 | 291 | title, words, h2q, ext |
| ⬜ | `/fiche/ports-du-somail-et-de-la-robine/` | fiche | 30 | 9.5 | 423 | title, h2q |
| ⬜ | `/fiche/ecluse-de-treboul/` | fiche | 30 | 5.9 | 282 | words, h2q |
| ⬜ | `/fiche/ecluse-de-locean/` | fiche | 30 | 9.4 | 325 | h2q |
| ⬜ | `/fiche/gite-aude/` | fiche | 30 | 7.9 | 366 | h2q |
| ⬜ | `/post-category/villes-a-visiter/` | archive | 30 | 9.0 | 348 | title, desc, date, h2q, ext |
| ⬜ | `/agenda-activites-autour-de-capestang-34310/` | contenu-article | 29 | 16.0 | 292 | words, h2q, ext |
| ⬜ | `/fiche/camping-le-val-de-cesse/` | fiche | 29 | 11.8 | 423 | h2q |
| ⬜ | `/fiche/moulin-den-cos/` | fiche | 29 | 6.7 | 256 | title, words, h2q |
| ⬜ | `/fiche/ecluse-de-raonel/` | fiche | 29 | 7.3 | 225 | desc, words, h2q |
| ⬜ | `/fiche/ecluse-de-puicheric/` | fiche | 29 | 7.3 | 296 | title, words, h2q |
| ⬜ | `/fiche/ecluse-de-carcassonne/` | fiche | 29 | 11.3 | 298 | title, words, h2q |
| ⬜ | `/fiche/ecluse-demborrel/` | fiche | 29 | 6.5 | 284 | words, h2q |
| ⬜ | `/fiche/la-bonne-planque/` | fiche | 29 | 8.5 | 377 | h2q |
| ⬜ | `/beziers-balades-sur-les-flots-de-lorb/` | contenu-article | 28 | 14.6 | 732 | h2q, ext |
| ⬜ | `/au-fil-de-leau-croisiere-sur-le-canal-du-midi/` | contenu-article | 28 | 36.0 | 1049 | h2q, ext |
| ⬜ | `/fiche/hotel-restaurant-du-lauragais/` | fiche | 28 | 15.7 | 359 | title, h2q |
| ⬜ | `/fiche/ecluse-dencassan/` | fiche | 28 | 7.7 | 342 | h2q |
| ⬜ | `/fiche/lereservoir/` | fiche | 28 | 8.0 | 741 | title, h2q |
| ⬜ | `/toulouse-dessine-moi-le-nom-des-stations-du-metro/` | contenu-article | 27 | 17.1 | 776 | title, h2q, ext |
| ⬜ | `/les-350-ans-du-canal-du-midi-en-quelques-dates-le-canal-du-midi-fete-cette-annee-ses-350-ans-retour-en-quelques-dates-sur-louvrage-de-pierre-paul-riquet-et-sur-une-longue-histoire-de-navigation-en/` | contenu-article | 27 | 18.8 | 812 | title, h2q, ext |
| ⬜ | `/narbonne-au-chantier-dinsertion-du-pnr-lesperance-revit/` | contenu-article | 27 | 9.3 | 451 | title, h2q, ext |
| ⬜ | `/il-y-a-350-ans-riquet-testait-la-rigole/` | contenu-article | 27 | 12.5 | 523 | h2q, ext |
| ⬜ | `/castelnaudary-la-poterie-not-entreprise-du-patrimoine-vivant/` | contenu-article | 27 | 14.6 | 911 | title, h2q, ext |
| ⬜ | `/fiche/le-relais-de-sully/` | fiche | 27 | 8.8 | 366 | h2q |
| ⬜ | `/fiche/ecluse-de-laurens/` | fiche | 27 | 10.1 | 306 | h2q |
| ⬜ | `/categorie/chambre-dhotes/` | categorie | 27 | 24.8 | 1085 | title, h2q, struct |
| ⬜ | `/carcassonne-le-restaurant-quick-du-pont-rouge-ferme-aujourdhui-le-temps-de-se-transformer-en-burger-king/` | contenu-article | 26 | 10.4 | 358 | title, h2q, ext |
| ⬜ | `/toulouse-nouveau-quartier-matabiau-la-rue-bayard-livree-mi-2017-le-nouveau-parvis-en-2019/` | contenu-article | 26 | 10.7 | 882 | title, h2q, ext |
| ⬜ | `/carcassonne-le-plus-grand-train-du-pere-noel-de-france-attendu-le-22-decembre/` | contenu-article | 26 | 10.7 | 245 | title, words, h2q, ext |
| ⬜ | `/expo-lillustrateur-pierre-samson-dynamite-lhistoire-du-canal-du-midi/` | contenu-article | 25 | 6.4 | 352 | title, h2q, ext |
| ⬜ | `/beziers-le-port-neuf-reprend-vie/` | contenu-article | 25 | 10.7 | 488 | h2q, ext |
| ⬜ | `/narbonne-quels-changements-pour-la-robine-en-coeur-de-ville/` | contenu-article | 25 | 14.1 | 781 | title, h2q, ext |
| ⬜ | `/je-moppose-aux-abattages-dans-la-traversee-de-la-commune-yves-bastie-maire-de-salleles-daude/` | contenu-article | 25 | 9.4 | 81 | title, desc, words, h2q, ext |
| ⬜ | `/balade-a-pied-randonnee/` | contenu-page | 25 | 51.6 | 187 | faq, words, h2q, ext |
| ⬜ | `/trebes-la-brocante-du-canal-exemple-a-suivre/` | contenu-article | 25 | 4.8 | 303 | h2q, ext |
| ⬜ | `/agenda-des-animations-du-haut-minervois/` | contenu-article | 25 | 9.8 | 1309 | h2q, ext |
| ⬜ | `/castelnaudary-culture-helene-giral-prend-deux-engagements/` | contenu-article | 25 | 5.4 | 618 | title, h2q, ext |
| ⬜ | `/fiche/ecluse-de-prades/` | fiche | 25 | 8.8 | 297 | words, h2q |
| ⬜ | `/fiche/ecluse-de-lalande/` | fiche | 25 | 8.0 | 289 | words, h2q |
| ⬜ | `/fiche/ecluse-du-roc/` | fiche | 25 | 7.4 | 283 | words, h2q |
| ⬜ | `/association-replantons-le-canal-du-midi/` | contenu-page | 24 | 6.3 | 145 | faq, words, h2q |
| ⬜ | `/la-voie-verte-sur-de-bons-rails-salleles-daude/` | contenu-article | 24 | 9.4 | 425 | h2q, ext |
| ⬜ | `/marseillette-decouvrez-les-dernieres-rizieres-audoises/` | contenu-article | 24 | 10.3 | 249 | title, words, h2q, ext |
| ⬜ | `/toulouse-le-projet-de-tour-de-voies-navigables-de-france-fait-polemique/` | contenu-article | 24 | 10.3 | 779 | title, h2q, ext |
| ⬜ | `/braderie-d-hiver-au-polygone-a-beziers/` | contenu-article | 24 | 7.2 | 104 | words, h2q, ext |
| ⬜ | `/fiche/ecluse-de-moussoulens/` | fiche | 24 | 9.9 | 223 | desc, words, h2q |
| ⬜ | `/fiche/ecluse-de-salleles/` | fiche | 24 | 10.0 | 225 | desc, words, h2q |
| ⬜ | `/fiche/ecluse-dargelliers/` | fiche | 24 | 8.4 | 213 | desc, words, h2q |
| ⬜ | `/fiche/ecluse-darieges/` | fiche | 24 | 7.1 | 294 | words, h2q |
| ⬜ | `/fiche/ecluse-de-la-planque/` | fiche | 24 | 9.1 | 304 | h2q |
| ⬜ | `/fiche/port-de-castelnaudary/` | fiche | 24 | 23.9 | 401 | title, h2q |
| ⬜ | `/fiche/le-relais-de-riquet-restaurant/` | fiche | 24 | 7.8 | 362 | h2q |
| ⬜ | `/narbonne-la-passerelle-sur-le-canal-est-a-nouveau-ouverte/` | contenu-article | 23 | 7.5 | 303 | title, h2q, ext |
| ⬜ | `/festival-musique-et-vin-a-assignan/` | contenu-article | 23 | 7.8 | 1150 | h2q, ext |
| ⬜ | `/le-somail-pied-a-terre-ideal/` | contenu-article | 23 | 18.9 | 554 | h2q, ext |
| ⬜ | `/agenda-des-animations-du-haut-minervois-2/` | contenu-article | 23 | 16.8 | 879 | h2q, ext |
| ⬜ | `/fiche/ecluse-de-beziers/` | fiche | 23 | 11.5 | 317 | title, h2q |
| ⬜ | `/fiche/ecluse-de-bram/` | fiche | 23 | 6.9 | 289 | title, words, h2q |
| ⬜ | `/association-riquet-et-son-canal/` | contenu-page | 22 | 9.2 | 175 | faq, words, h2q |
| ⬜ | `/les-feeries-de-noel-a-narbonne/` | contenu-article | 22 | 15.4 | 161 | words, h2q, ext |
| ⬜ | `/les-metiers-du-canal-cecile-veille-sur-les-ecluses/` | contenu-article | 22 | 7.0 | 420 | title, h2q, ext |
| ⬜ | `/ramonville-saint-agne-le-bmx-sinstalle-de-long-du-canal/` | contenu-article | 22 | 7.9 | 305 | title, h2q, ext |
| ⬜ | `/carcassonne-une-pizza-au-cassoulet/` | contenu-article | 22 | 7.0 | 182 | words, h2q, ext |
| ⬜ | `/fiche/grotte-de-limousis/` | fiche | 22 | 9.1 | 460 | title, h2q |
| ⬜ | `/fiche/mairie-de-homps/` | fiche | 22 | 18.0 | 431 | title, h2q |
| ⬜ | `/fiche/ecluse-de-saint-jean/` | fiche | 22 | 9.7 | 289 | words, h2q |
| ⬜ | `/fiche/ecluse-de-laval/` | fiche | 22 | 8.8 | 292 | words, h2q |
| ⬜ | `/vignoble-des-corbieres/` | contenu-page | 21 | 29.5 | 553 | title, faq, h2q |
| ⬜ | `/toulouse-parvis-matabiau-canal-souterrain-esplanade-pietonne-et-tour-de-verre/` | contenu-article | 21 | 11.5 | 826 | title, h2q, ext |
| ⬜ | `/castelnaudary-en-marche-pour-un-tour-du-grand-bassin-a-pied/` | contenu-article | 21 | 11.5 | 347 | title, h2q, ext |
| ⬜ | `/idee-cadeau-originale/` | contenu-article | 21 | 9.0 | 107 | words, h2q, ext |
| ⬜ | `/canal-du-midi-une-nouvelle-campagne-dabattage-de-platanes-dans-lherault/` | contenu-article | 21 | 8.4 | 632 | title, h2q, ext |
| ⬜ | `/m6-zoome-sur-le-patrimoine-de-carcassonne/` | contenu-article | 21 | 7.2 | 381 | h2q, ext |
| ⬜ | `/fiche/ecluse-de-saint-martin/` | fiche | 21 | 11.4 | 286 | words, h2q |
| ⬜ | `/revel-un-14-juillet-tres-anime/` | contenu-article | 20 | 9.5 | 352 | h2q, ext |
| ⬜ | `/la-metamorphose-du-marathon-de-toulouse-metropole-marathon-de-toulouse-course-a-pied/` | contenu-article | 20 | 10.7 | 490 | title, h2q, ext |
| ⬜ | `/toulouse-vingt-six-ans-de-canal-pour-loccitania/` | contenu-article | 20 | 10.6 | 458 | title, h2q, ext |
| ⬜ | `/canal-du-midi-christian-lapalu-obtient-la-sauvegarde-des-arbres-sains/` | contenu-article | 20 | 7.9 | 607 | title, h2q |
| ⬜ | `/fiche/reve-de-velo-travel/` | fiche | 20 | 9.7 | 471 | h2q |
| ⬜ | `/fiche/port-de-narbonne/` | fiche | 20 | 12.2 | 433 | title, h2q |
| ⬜ | `/fiche/syndicat-mixte-d-amenagement-de-jouarres/` | fiche | 20 | 6.9 | 453 | title, h2q |
| ⬜ | `/fiche/residence-le-domaine-denserune/` | fiche | 20 | 11.7 | 495 | h2q |
| ⬜ | `/categorie/appartement-maison-a-louer/` | categorie | 20 | 39.9 | 1468 | title, h2q, struct |

## 5. Lo que el robot no mide (revisar a mano en cada página de §4)

- Si el primer párrafo responde a la búsqueda principal de la URL (Search Console → consultas de esa página).
- Datos concretos con fuente y fecha (cifras, nombres, PK), recientes (5 últimos años).
- Imágenes: las miniaturas llevan `alt=""` a propósito; las fotos principales necesitan alt descriptivo.
- Enlaces internos útiles hacia la página (no solo desde ella) y su texto de enlace.
- Core Web Vitals por página (PageSpeed/CrUX).
