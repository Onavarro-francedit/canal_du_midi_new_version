# Inventario de cobertura 2026 — plan-canal-du-midi.com

**Fecha:** 2026-10-06 · **Pregunta:** al publicar el sitio 2026, ¿qué sirve cada URL publicada, y qué se queda sin versión 2026?

**Cómo:** `wp-plugin/tests/inventario-2026.php`, solo lectura, ejecutado en producción con
`remote.sh run tests/inventario-2026.php`. Aplica las mismas reglas que el plugin al publicar (`canal_contenu_post_eligible`,
301 de `redirects-2026.php`, taxonomías de la carte). Resultado por URL: `docs/data/cobertura-2026.tsv` (2 553 URLs),
cruzado con los clics de Google de 12 meses de `docs/data/paginas-trafico-2025-10_2026-09.csv`.

## 1. Resumen

**El 95 % de los clics de Google llegan a URLs que ya tienen versión 2026.** Del resto, la mitad son PDF, que ya se
resuelven: el plan antiguo redirige a `/plan-canal-du-midi.pdf`. Lo que queda sin cubrir suma **unos 770 clics al año (1,4 %)**:

| Al publicar, la URL se sirve con… | URLs | Clics Google / año | % clics |
|---|---|---|---|
| Plantilla de contenido 2026 (páginas y artículos) | 1 530 con tráfico (2 005 publicadas) | 29 300 | 51,8 % |
| Ficha 2026 | 254 | 10 919 | 19,3 % |
| Calcul de distance 2026 | 1 | 7 751 | 13,7 % |
| Home 2026 | 1 | 4 756 | 8,4 % |
| Carte 2026 (`/explorer/`, `/categorie/`, `/region/`, `/mot-cle/`) | 186 | 1 200 | 2,1 % |
| 301 hacia su equivalente (páginas vacías 2013–2018, « demande… ») | 59 | 136 | 0,2 % |
| Archivo del blog 2026 (`/post-category/`) | 27 | 45 | 0,1 % |
| PDF y archivos (no son páginas; el plan antiguo → 301 al vigente) | 181 | 1 693 | 3,0 % |
| **Fichas caducadas o borradas** (30 caducadas dan 404) | 43 | **440** | 0,8 % |
| **Artículos borrados: hoy 404** | 57 | **212** | 0,4 % |
| **Sin versión 2026** (zonas + 9 páginas) | 17 | **116** | 0,2 % |

## 2. Lo que no tiene versión 2026

### 2.1 Fichas caducadas: 404 con clics de Google (30 caducadas)

Las fichas caducadas (`expired`, el cliente no renovó) dan **404**, pero Google las sigue enviando:
**Locaboat** (109 clics al año), **Nicols Port-Lauragais** (59), **Click and Boat** (56), Guiraud Conciergerie (32),
Hôtel-restaurant O Fil de l'Ô (23), Nicols « en bateau » (21, con 146 vistas), Hostel Toulouse Wilson, Auberge de la
Croisade, Le Fournil de Labastide, Abbaye de Fontcaude… Hoy son visitantes perdidos.
→ **Propuesta:** 301 de cada ficha caducada a su categoría en la carte 2026 (`/categorie/location-bateau/`…). Va en el
plugin, sin tocar las fichas. Si la ficha se renueva, vuelve a estar publicada y la 301 deja de aplicarse.

### 2.2 Artículos borrados: 404 con clics (57)

El principal es « Marché de Noël au château les Carrasses » (167 clics, 116 vistas); los demás tienen menos de 35 clics.
Son noticias borradas: un 404 es correcto. Dar una 301 a un artículo parecido sería inventar la relación. → No se hace nada.

### 2.3 Zonas `/zone/<tramo>/` (8 términos, 113 clics)

`homps-capestang` 74 clics, `carcassonne-homps` 19, `toulouse-castelnaudary` 12; el resto, menos de 5. Hoy las sirve el
tema (listado my-listing) y la carte 2026 no filtra por zona.
→ **Propuesta:** 301 de cada zona a `/etapes/`, que responde a la misma intención (tramos del canal) con datos.

### 2.4 Páginas sin plantilla 2026 (9, unos 3 clics)

`liens-reseaux-sociaux`, `verif-page-facebook` y `accueil-demo` usan plantillas propias del tema o de Elementor. Las otras 6
(boutique, panier, paiement, claim-list, site-web-en-maintenance, votre-demande-de-guide-est-valide) están excluidas a
propósito (WooCommerce muerto, páginas técnicas). Sin tráfico. → Nada.

## 3. Cubiertas, pero hay que revisarlas

La plantilla de contenido 2026 pinta **todas** las páginas y artículos, incluidas las 25 páginas Elementor (con el HTML que
Elementor guarda en `post_content`). Comprobado en el navegador (vista privada `-2026`):

- **Le Canal de la Robine** (Elementor, 2 291 clics, la página de contenido nº 2): bien, con texto completo, 2 imágenes y
  secciones h2.
- **Fêtes et manifestations** (Elementor): bien.
- **Photos du Canal du Midi** (Elementor, galería): bien (21 fotos en columna), pero quedaban sueltos los textos
  « Précédent / Suivant » del carrusel de Elementor. → **Corregido** (`canal_contenu_clean_html`, para todas las páginas).

Señales del inventario (columna `nota` del TSV), para revisar por tráfico:

| Señal | Páginas | Artículos | Comentario |
|---|---|---|---|
| Elementor | 25 | — | Las de más tráfico: Robine ✅, Histoire (200 clics), Le Grand Bief (119), Villeneuve-lès-Béziers (96), Photos ✅ |
| iframe (vídeo o mapa) | 6 | 15 | Se muestran tal cual |
| Texto corto (< 300 caracteres) | 2 | 401 | Noticias 2014–2017 que son sobre todo una foto o un enlace; sin tráfico relevante |
| Shortcodes | `caption` (se pinta), `Zoomer` (ya se quita), CF7 y TablePress (formularios que se conservan) | | |

## 4. Siguientes pasos

1. ✅ **301 de las fichas caducadas a su categoría** (06/10, activa ya: solo sustituye el 404). Categoría principal según el
   orden del editor que tenga fichas publicadas; si no hay ninguna, `/explorer/`.
2. ✅ **301 de `/zone/<tramo>/` → `/etapes/`** (06/10), solo al publicar (hoy esas páginas funcionan con el tema).
3. ✅ Revisadas en el navegador (06/10) Histoire, Le Grand Bief, Villeneuve-lès-Béziers, Bassin de Thau (vídeo y mapa bien),
   Colombiers y Poilhes. Dos fallos corregidos para todo el sitio: la portada se repetía como primera imagen del texto
   (`canal_contenu_drop_cover`), y en el contenido de bloques (93 páginas y artículos) se aplicaba `wpautop`, que cortaba
   el texto a media frase con `<br>`; ahora se hace como el núcleo de WordPress.
4. Volver a ejecutar el inventario antes de publicar (TASK-063): `remote.sh run tests/inventario-2026.php`.
