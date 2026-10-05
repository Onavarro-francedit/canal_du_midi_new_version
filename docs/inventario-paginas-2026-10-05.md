# Inventario y análisis de páginas — plan-canal-du-midi.com

**Fecha:** 2026-10-05 · **Período:** oct 2025 – sep 2026 (12 meses) · **Objeto:** decidir qué páginas del WordPress de
producción pasan a la versión 2026, con qué plantillas, y cómo queda el navbar.

**Fuentes:** WP-CLI en producción (solo lectura), GA4 y Search Console (vía OpenSEO). El conector Matomo no sirve
para este sitio, porque está medido con GA4. Datos por URL en `docs/data/paginas-trafico-2025-10_2026-09.csv`
(2 318 URLs, con la decisión propuesta para cada una).

---

## 1. Resumen

1. **El sitio se apoya en muy pocas páginas.** La página nº 1 (*Calcul de distance*) suma el 20 % de las vistas.
   Las 10 primeras suman el 42 % y las 100 primeras, el 72 %.
2. **Con unas 330 URLs basta.** El sitio tiene unas 2 400 URLs publicadas: 1 936 artículos, 143 páginas, 254 fichas y
   59 categorías. Unas **260 páginas o artículos reúnen el 94 % de los clics de Google** en contenido. A esto se suman
   las fichas (ya resueltas) y unas 35 categorías con uso real.
3. **Con 4 plantillas genéricas se cubre todo.** Ya existen *ficha*, *home* y *carte*. Faltan:
   - **Contenido** (páginas + artículos): 59 % de las vistas.
   - **Listado de categoría**: 9 %.
   - **Herramienta *Calcul de distance***: el 20 %, en una sola página.
   - **Ville / étape** (nueva): es la mayor demanda de Google sin cubrir.
4. **Tendencia preocupante.** Los clics de Google bajan un **44 % en julio y un 35 % en agosto respecto a 2025**, y las
   impresiones bajan en proporción similar. Es un motivo más para modernizar ahora y concentrarse en lo que funciona.
5. **El navbar 2026 tiene 103 enlaces.** Unos 15 apuntan a categorías con 0–2 fichas. Se puede adelgazar y añadir una
   entrada **Villes & étapes**.

---

## 2. Inventario (producción, 05/10/2026)

| Tipo | Publicados | Plantilla actual | Nota |
|---|---|---|---|
| Artículos (`post`) | 1 936 | tema my-listing | El 90 % son de 2014–2017 (noticias); solo 1 usa Elementor |
| Páginas (`page`) | 143 + 2 privadas | `content-sidebar.php` (~70), Elementor (~25), plantillas antiguas | Ver §5 |
| Fichas (`job_listing`, `/fiche/`) | 254 | my-listing | **Ya hay versión 2026** (`/fiche-2026/<slug>/`) |
| Categorías de fichas (`/categorie/`) | 61 términos | my-listing (explore) | 14 tienen 0 fichas; muchas tienen 1 o 2 |
| `region` (`/region/`) | 110 términos | my-listing | 88 están vacíos |
| `zone` (`/zone/`) | 8 tramos del canal | my-listing | Toulouse → Castelnaudary → … → Frontignan |
| Categorías de artículos (`/post-category/`) | 27 | tema | El 98 % de los artículos están en « actualites » |
| WooCommerce (`/boutique/`, `/panier/`…) | 0 productos útiles | — | Tienda muerta |
| Menú « Principale » | 111 elementos | — | Con duplicados (Ports, Écluses, Calcul de distance ×3) |

---

## 3. Tráfico por tipo de página

| Tipo | URLs con datos | Vistas GA4 | % vistas | Clics Google | Impresiones | URLs con ≥10 vistas/mes |
|---|---|---|---|---|---|---|
| Página de contenido | 82 | 25 238 | **40,1 %** | 19 993 | 813 k | 23 |
| Ficha | 297 | 13 254 | 21,0 % | 11 359 | 859 k | 28 |
| Artículo de blog | 1 517 | 11 865 | 18,8 % | 17 214 | 694 k | 16 |
| Listado de categoría | 59 | 5 498 | 8,7 % | 975 | 106 k | 13 |
| Home `/` | 1 | 4 161 | 6,6 % | 4 756 | 435 k | 1 |
| Carte `/explorer/` | 1 | 1 928 | 3,1 % | 61 | 17 k | 1 |
| Categorías de artículos | 40 | 550 | 0,9 % | 45 | 2 k | 1 |
| zone / region | 66 | 216 | 0,3 % | 258 | 13 k | 0 |
| Páginas antiguas 2013–2018 | 54 | 178 | 0,3 % | 136 | 6 k | 0 |
| Tienda / cuenta | 6 | 48 | 0,1 % | 59 | 24 k | 0 |
| PDFs y otros | 193 | — | — | **1 712** | 44 k | — |

**Lecturas:**
- **Los PDF del plan reciben ~1 100 clics de Google al año.** El más clicado es el **plan de 2023** (841), por delante
  del de 2025 (178) y del de 2026 (36). Google sigue mandando gente a un plan desactualizado.
- **GA4 se queda corto.** En artículos hay más clics de Google (17 214) que vistas en GA4 (11 865), lo que apunta al
  consentimiento de cookies. El canal « Direct » tiene un 20 % de engagement: probablemente bots. **Para decidir, mejor
  los clics de Google que las vistas de GA4.**
- Los *key events* de GA4 son 3 eventos antiguos (« visite_la_catégorie_… », 18 en un año) y la búsqueda interna no
  se mide. **Hoy no se mide ninguna conversión.**
- Móvil: 64 % de los usuarios.

### Tendencia (clics de Google por mes)

| | jun | jul | ago | sep |
|---|---|---|---|---|
| 2025 | 8 145 | 10 183 | 8 870 | 6 314 |
| 2026 | 6 043 | 5 678 | 5 776 | 3 997 |
| Variación | −26 % | **−44 %** | **−35 %** | −37 % |

---

## 4. Lo más y lo menos visitado

### Top 15 (clics de Google · vistas GA4)

| # | Página | Clics | Vistas | Posición |
|---|---|---|---|---|
| 1 | /calcul-de-distance-canal-du-midi/ | 7 751 | 12 671 | 7,3 |
| 2 | / (home) | 4 756 | 4 161 | 9,1 |
| 3 | /canal-de-la-robine/ | 2 291 | 1 800 | 7,2 |
| 4 | /longeur-largeur-des-ecluses-… (dimensions des écluses) | 1 702 | 1 262 | 7,4 |
| 5 | /voie-verte-et-veloroute/ | 1 524 | 1 930 | 18,1 |
| 6 | /peniches-a-vendre-… (artículo) | 1 431 | 866 | 10,7 |
| 7 | /navigation/periode-de-navigation/ | 1 037 | 748 | 7,4 |
| 8 | /meteo-du-canal-du-midi/ | 953 | 726 | 7,8 |
| 9 | Plan-Canal-du-Midi-2023.pdf | 841 | — | 13,6 |
| 10 | /la-librairie-du-somail-… (artículo) | 570 | 335 | 9,2 |
| 11 | /les-ecluses-du-canal-du-midi-2/ | 543 | 338 | 10,3 |
| 12 | /beziers-la-future-gare-tgv-… (artículo) | 538 | 350 | 9,8 |
| 13 | /navigation/regles-de-navigation/ | 502 | 423 | 11,5 |
| 14 | /le-lac-de-saint-ferreol-sera-bientot-vide-… (artículo) | 479 | 296 | 7,1 |
| 15 | /auberge-de-la-croisade-… (artículo) | 462 | 248 | 7,3 |

Las fichas más fuertes son las de **ports** (Cassafières, Sète, Homps, Port Neuf, Ramonville…) y **écluses**.

### Lo que casi no se visita

- **Artículos:** de 1 936, **unos 1 130 no tuvieron ningún clic de Google en 12 meses** (713 con tráfico nulo o
  residual y unos 420 que ni aparecen). Son noticias de 2014–2016.
- **54 páginas antiguas de 2013–2018** (`rechercher-presta.php`, `details-*`, `/loisirs/…`, `/se-restaurer/…`,
  `/services-utiles/…`): 178 vistas al año entre todas y sin contenido. Las sustituyó my-listing.
- **Páginas con 0–6 vistas:** `/agenda/`, `/demande-de-promenade-en-bateaux/`, `/demande-dhebergement-…/`,
  `/site-web-en-maintenance/`, `/accueil-demo/`, `/verif-page-facebook/`, `/liens-reseaux-sociaux/`,
  `/votre-demande-de-guide-est-valide/`, `/infos-legales/` (normal), `/visiter-toulouse/` (6 vistas).
- **`/region/` y `/zone/`:** 216 vistas entre las 66 URLs.
- **`/post-category/`:** 550 vistas; solo *villes-a-visiter* tiene algo de Google (30 clics).
- **Tienda WooCommerce:** prácticamente cero.

---

## 5. Demanda de Google por intención (5 000 consultas, 1,17 M impresiones)

| Intención | Impresiones | Clics | CTR | Posición | Lectura |
|---|---|---|---|---|---|
| « canal du midi » (genérica) | 316 k | 2 237 | 0,7 % | 9,2 | La home está en el borde de la 1.ª página |
| **Villes / villages** | **200 k** | 1 785 | 0,9 % | 11,3 | **El mayor hueco**: Sallèles-d'Aude (21 k), Poilhes, Le Somail, Port-la-Nouvelle… sin página de destino |
| Carte / plan / tracé | 120 k | 2 219 | 1,8 % | 7,4 | Cubierta por home y carte 2026 (TASK-045) y por el PDF |
| Ports / capitainerie | 99 k | 1 605 | 1,6 % | 8,4 | Las fichas de puerto funcionan |
| Camping / hébergement | 45 k | 361 | 0,8 % | 15,4 | Las categorías posicionan mal |
| Navigation / bateau | 40 k | 331 | 0,8 % | **25,7** | Contenido disperso en 6 páginas cortas de `/navigation/` |
| Vélo / voie verte | 39 k | 923 | 2,4 % | **22,5** | *voie-verte-et-veloroute* en la posición 18 con 60 k impresiones |
| Restaurant | 33 k | 561 | 1,7 % | 10,2 | Sobre todo nombres de restaurantes concretos (fichas y artículos) |
| Écluses | 28 k | 668 | 2,4 % | 9,9 | Fuerte |
| Robine / Narbonne | 19 k | 1 588 | 8,6 % | 5,4 | Fuerte |
| Distance / km | 14 k | 894 | 6,4 % | 5,8 | Fuerte |
| Lacs / Saint-Ferréol | 13 k | 424 | 3,2 % | 8,6 | Artículos |
| Histoire / Riquet | 13 k | 94 | 0,7 % | 9,7 | Débil para ser un tema central |
| Péniche (comprar o vivir) | 10 k | 508 | 5,2 % | 8,1 | Un artículo sostiene todo el tema |
| Météo | 3 k | 541 | 16,9 % | 3,4 | Fuerte |

---

## 6. ¿Son útiles las páginas actuales? Qué hacer con cada grupo

| Grupo | Decisión | Por qué |
|---|---|---|
| Páginas de contenido con tráfico (54) | **Migrar** con la plantilla *contenido* | 40 % de las vistas |
| Artículos con ≥12 clics/año o ≥120 vistas (206) | **Migrar** con la plantilla *contenido* (variante artículo) | Son el long tail que funciona |
| Artículos con tráfico residual (598) | **Revisar**: migrar sin promoción | Poco coste si la plantilla es genérica |
| Artículos sin tráfico (~1 130) | **No migrar**; al publicar, quedan en el tema antiguo o en `noindex` (decisión aparte) | Noticias caducadas de 2014–2016 |
| Categorías con uso (35) | **Listado 2026** = carte 2026 filtrada + texto introductorio | La carte ya filtra por `?type=` |
| Categorías vacías o con 1–2 fichas (24) | **Fusionar** en la categoría madre (no se tocan las existentes) | No justifican una página |
| `calcul-de-distance` | **Plantilla propia** (herramienta) | Página nº 1, 20 % de las vistas |
| `organiser-votre-sejour` | Sustituida por el **planificador 2026** | 352 vistas, 0 de Google |
| `recevoir-le-plan` (×2) | Unificar en una sola página 2026 que también enlace al PDF vigente | Duplicada, y el PDF de 2023 se lleva el tráfico |
| Páginas antiguas 2013–2018 (54), tienda, `agenda`, `demande-*`, `accueil-demo`, `verif-*`, `maintenance` | **No migrar** | Sin tráfico ni contenido |
| `/region/`, `/zone/`, `/post-category/` | **No migrar tal cual**; `/zone/` y `/region/` alimentan la nueva plantilla *ville/étape* | 0,3 % de tráfico; la demanda está en §5 |

**Duplicados detectados:**
- `recevoir-le-plan-du-canal-du-midi` y `-2`.
- `/les-ecluses-du-canal-du-midi/` (antigua) y `-2`.
- `/le-canal/ouvrages/` y `/ouvrages-d-art/`.
- `/balade-a-velo-sur-le-canal-du-midi/` (posición 67, 0 clics) y `/voie-verte-et-veloroute/`.
- `/categorie/restaurant/` y `/categorie/restauration-2/`.
- `/categorie/ports/` y `/categorie/ports-fluviaux/`.

---

## 7. Navbar 2026: qué quitar y qué añadir

Base: `canal_header_menu()` (103 enlaces) y el análisis del 01/10 (`docs/navbar-analisis-2026-10-01.md`). Ahora,
con datos de Google y el número de fichas por categoría:

**Quitar** (0–2 fichas, ≤35 vistas al año; quedan accesibles desde « Tous les … » y la carte):
- Hostels (2)
- Auberges collectives (1)
- Roulottes (2)
- Appartements / Maisons (1)
- Chambres à louer (2)
- Hébergements insolites (0)
- Restaurants `/categorie/restaurant/` (duplicado de *Tous les restaurants*)
- Shopping (1)
- Commerces (14 vistas)
- Artisanat (13 vistas)
- Services (0 fichas)
- Loisirs de plein air (4 fichas, 44 vistas)
- Canoë-kayak (1 ficha)
- Balade à vélo sur le canal (posición 67, duplicado de Voie verte)
- Archives : films et vidéos / ouvrages, cartes et images (~45 vistas cada una)

Son **unos 16 enlaces menos** y el panel « Préparer › Sur place » desaparece.

**Añadir:**
1. **Villes & étapes**, en Découvrir o como entrada propia. Es la mayor demanda (200 k impresiones) y apunta a la
   nueva plantilla. Empezar por los 8 tramos de `zone` y las 10–15 villas con más fichas o búsquedas: Toulouse,
   Castelnaudary, Carcassonne, Homps, Le Somail, Sallèles-d'Aude, Capestang, Béziers, Agde, Sète, Narbonne.
2. **Plan PDF 2026** visible en « Préparer » (ya está *Recevoir le plan*): descarga directa de la versión vigente.
3. **Planificateur** como CTA (ya está), cuando se publique.

**Mantener tal cual:** En bateau (navegación + ports + écluses: el núcleo del tráfico), los accesos directos Distances
y Carte, y Météo.

**Estructura resultante (6 entradas → 5):**

```
En bateau | Vélo & balades | Villes & étapes | Se loger & manger | Préparer
```

« Manger & Boire » tiene poco uso (todas sus categorías juntas suman ~450 vistas al año), así que se fusiona con
« Se loger » en un solo panel de dos columnas. « Découvrir » (histoire, ouvrages, faune, Robine, vignobles) pasa a una
columna dentro de « Villes & étapes ».

---

## 8. Páginas que debería tener un sitio así (buenas prácticas)

Referentes: tourisme-occitanie.com, canal-du-midi.com, france.fr y visitscotland (ver el análisis del 01/10, §2). Un
sitio de destino con directorio tiene, por este orden:

| Tipo de página | ¿Existe? | Estado |
|---|---|---|
| Home con mapa y búsqueda | ✅ accueil-2026 | Hecha |
| Mapa interactivo filtrable | ✅ explorer-2026 | Hecha |
| Ficha de prestatario con JSON-LD | ✅ fiche-2026 | Hecha |
| **Páginas de destino (villes / étapes)** con qué ver, dónde dormir y comer, puerto y esclusas, distancias | ❌ | **Falta. Es la mayor oportunidad** |
| **Guías pilar por modo**: « Le canal en bateau » y « Le canal à vélo » | ⚠️ dispersas | 6 páginas cortas en `/navigation/` (posición 25); vélo en la posición 22 |
| Herramientas: distancias, météo, plan PDF, planificador | ✅ (antiguas) / ✅ planner | Distancias y météo en plantilla antigua |
| Agenda / eventos vivos | ❌ | `/agenda/` vacía; *manifestations* es estática |
| Guía práctica / FAQ | ⚠️ | FAQ de 511 palabras de 2023; la de la home 2026 es mejor |
| Listados por categoría | ⚠️ | Son útiles para navegar pero posicionan mal (posición 12–70) |
| Blog / actualidad | ⚠️ | 1 936 entradas, casi todas caducadas; la sección no tiene salida editorial |
| Contacto, aviso legal, sobre nosotros (E-E-A-T) | ⚠️ | Sin página « qui sommes-nous » |

---

## 9. Consecuencia para el generador de páginas 2026

En lugar de una página por URL, un **router genérico en el plugin**, como `fiche-route.php`:

| Plantilla | Cubre | Fuente de datos | Ruta de prueba |
|---|---|---|---|
| **Contenido** | 54 páginas + ~800 artículos | `post_content` filtrado: shortcodes y Elementor renderizados, limpieza de clases | `/<slug>-2026/` |
| **Listado** | 35 categorías | Reutiliza carte 2026 con `?type=` + descripción del término | `/categorie-2026/<slug>/` |
| **Herramienta distancias** | 1 página (20 % del tráfico) | Datos de `calcul_distance_canal.php` | `/calcul-de-distance-2026/` |
| **Ville / étape** (nueva) | 8 zonas + ~15 villas | `zone`/`region` + fichas + artículos relacionados | `/etape-2026/<slug>/` |

Al publicar, el mismo router sirve la URL original (sin `-2026`). Las URLs « no migrar » siguen en el tema antiguo.

**Orden propuesto:**
1. Plantilla *contenido*: cubre el 59 % de las vistas y es casi todo texto.
2. *Calcul de distance* 2026.
3. Listado de categoría.
4. Ville / étape: lleva trabajo editorial, porque hay que escribir textos de etapa.

---

## 10. Avisos

- GA4 no mide conversiones: no hay *key events* útiles ni búsqueda interna. Conviene definir eventos en las páginas
  2026, por ejemplo clic en teléfono o web de la ficha, descarga del plan y demanda del planificador.
- El PDF de 2023 recibe más clics que el de 2026. Al publicar, convendría redirigirlo al vigente. Eso es tocar
  `.htaccess`, así que requiere autorización.
- La caída interanual de Google (−35/−44 % en verano) no se explica solo con este análisis. Hay que mirar si es por
  posición o por CTR (AI Overviews) antes de achacarla al diseño.
