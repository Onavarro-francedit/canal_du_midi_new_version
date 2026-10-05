# Calcul de distance 2026 (TASK-057) — diseño

**Fecha:** 2026-10-05 · **Estado:** aprobado en el chat sobre la maqueta `docs/mockups/calcul-distance-2026.html`
(iteraciones: con mapa, sin frise, con esclusas enlazadas y fotos, sin bloque salida/llegada, ventanita sin scroll)
· **Base:** `docs/calcul-distance-benchmark-2026-10-05.md`

## Objetivo

Sustituir la herramienta de la página nº 1 del sitio (`/calcul-de-distance-canal-du-midi/`, 20 % de las vistas) por
una versión 2026. Debe calcular bien el tiempo en barco, porque la actual no cuenta las esclusas y se queda un tercio
corta. Además debe permitir elegir ciudades, puertos o esclusas, dibujar el trayecto en Google Maps y enlazar las
fichas de las esclusas.

**Restricciones:**
- PHP 7.4 y solo añadir: no se toca la página ni la plantilla actuales.
- Privada hasta publicar; ruta `/calcul-de-distance-canal-du-midi-2026/`.
- Sin conexión a la base `pimcore` (SEC-001).
- Se verifica en el navegador a 1440 y 390 px.

## Alcance

**Entra:**
- Canal du Midi principal, de Toulouse (port de l'Embouchure, PK 0) a Les Onglous (PK 240,5).
- Las 63 esclusas: las 62 de la herramienta actual más Fonseranes. Cada una con sus sas, su ficha y su foto.
- 22 ciudades y puertos, con los PK calculados sobre el trazado OSM recalado en las esclusas.
- Barco, bici y a pie, con días; enlace para compartir; mapa; lista de esclusas; « Que faire à… »; tabla de
  distancias; « Avant de partir ».

**No entra (después):**
- El ramal de la Robine (8 fichas de esclusas ya localizadas).
- Hora de llegada según el horario de las esclusas.
- GPX.
- Crear las 5 fichas de esclusas que faltan.

## Arquitectura

| Archivo | Responsabilidad |
|---|---|
| `includes/calcul-core.php` (nuevo) | Datos (`CANAL_CALCUL_LOCKS`, `CANAL_CALCUL_TOWNS`, `CANAL_CALCUL_MATRIX`) y funciones puras: nombres en francés, cálculo, textos de duración y días, frase del resultado, ciudad más cercana. Test en `tests/test-calcul.php` |
| `includes/calcul-route.php` (nuevo) | Ruta (`parse_request`), estado, plantilla, fotos de las fichas (tamaño `medium`, con caché), estilos y script, `<head>` SEO |
| `template-calcul.php` (nuevo) | Marcado. El resultado inicial lo pinta el servidor (GET `?de=&a=`, o Castelnaudary → Trèbes), así funciona sin JS y es rastreable |
| `assets/calcul.css` (nuevo, a mano, en línea) | Estilos `.cdm-calcul`, copiados de la maqueta |
| `assets/calcul.js` (nuevo) | Recalcula al escribir (misma fórmula, datos inyectados por `wp_localize_script`), ⇄, copiar enlace, lista y « Que faire » |
| `assets/calcul-map.js` (nuevo) | Mapa: carga diferida (escritorio tras `load`; móvil con un botón), trazado, tramo, esclusas con ventanita |
| `assets/calcul/canal-du-midi-trace.json` (nuevo, 24 KB) | Trazado OSM `[lat,lng,PK]` (relación 302044, ODbL), solo se descarga con el mapa |
| `canal-home.php`, `header.php`, `head-fix.php`, `fiche-route.php` | `canal_calcul_is_page()` en los mismos puntos que el contenido 2026 |

### Modelo de cálculo (constantes con comentario)

- **Barco:** `km ÷ 7 km/h + 10 min por sas`. Calibrado con Le Boat: Castelnaudary → Trèbes da 13,2 h (publicado:
  13 h). Días: hasta 6 h, « dans la journée »; si no, « ≈ N jours de navigation » (`ceil(h / 6)`).
- **Bici:** 15 km/h (referencia de France Vélo Tourisme). Hasta 60 km, en el día; si no, `ceil(km / 55)` días.
- **A pie:** 4 km/h; `ceil(km / 20)` días.
- **Esclusas del tramo:** las que tienen `lo < PK ≤ hi` (con una tolerancia de 0,01). El punto de salida no cuenta.
  Se muestran « N écluses · M sas ».
- Duración redondeada a 10 min: « 13 h 20 », « 50 min ».

### Ruta

- `parse_request` (prioridad 5): si `canal_contenu_strip_suffix($wp->request, CANAL_CONTENU_SUFFIX)` es
  `calcul-de-distance-canal-du-midi`, la petición pasa a `query_vars = ['canal_calcul' => '1']`.
- `template_redirect` (prioridad 0): sin permiso, 404. El permiso es `read_private_pages` o la opción
  `canal_calcul_public = '1'`. Con permiso, se pinta la plantilla, como el contenido 2026.

### SEO / AEO

- `<title>`: « Calcul de distance sur le Canal du Midi : km, écluses, temps de trajet ».
- Meta description con los modos y las esclusas.
- `noindex` mientras haya sufijo; canonical a la URL vigente.
- JSON-LD: `WebApplication` (`applicationCategory: TravelApplication`, gratuita, `inLanguage: fr-FR`, sobre el Canal
  du Midi) y `BreadcrumbList`.
- La frase del resultado (« De Castelnaudary à Trèbes : 53 km par le Canal du Midi et 23 écluses à franchir. ») y la
  tabla de distancias van en el HTML del servidor.

## Errores y casos límite

- `?de=` / `?a=` desconocidos: se usa el trayecto por defecto. El texto se busca por nombre exacto, sin mayúsculas ni
  acentos.
- Mismo punto de salida y llegada: 0 km, « Aucune écluse », tiempos « 0 min ».
- Inversión (B antes que A): mismos km y esclusas; la lista sale en el orden de paso.
- Sin clave de Maps en el tema, o Maps sin cargar: se oculta el bloque del mapa y el resto funciona.
- Esclusa sin ficha: texto sin enlace. Sin foto: hueco neutro.

## Pruebas

- `tests/test-calcul.php` (CLI puro), que entra en `remote.sh test`:
  - Castelnaudary → Trèbes: 53 km, 23 esclusas, 31 sas, 12 h 40;
  - Toulouse → Béziers: 208 km, 55 esclusas;
  - simetría A↔B;
  - el mismo punto;
  - nombres y artículos en francés (de l'écluse, des écluses, d'Homps);
  - textos de días;
  - búsqueda por nombre sin acentos;
  - tabla simétrica.
- Smoke en producción: con sesión 200, sin sesión 404; HTML del servidor con la frase y la tabla.
- Navegador a 1440 y 390 px: cálculo en vivo, ⇄, mapa (escritorio automático, móvil con botón), ventanita sin scroll,
  sin scroll horizontal.

## Criterios de éxito

1. Los mismos números que la maqueta.
2. Ninguna petición a Pimcore y ninguna credencial.
3. Google Maps solo se carga con el mapa visible; en móvil, solo al pulsar.
4. Tests y lint de PHP 7.4 en verde.
