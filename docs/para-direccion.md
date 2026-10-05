# Puntos para informar a dirección

Hallazgos que debo comunicar a mi jefe. No son decisiones técnicas: necesitan una decisión de la empresa. Añadir los
nuevos arriba.

---

## 3. Fotos de las fichas de esclusas tomadas de Wikipedia (detectado el 2026-10-05)

De las 71 fotos de las fichas de esclusas, **46 tienen nombres de archivo de Wikipedia/Wikimedia Commons** (p. ej.
`260px-Ecluse_de_Bram.jpeg`, `Portiragnes_Lock_1.jpg`) y 16 están en baja resolución (260 px). Las fotos de Wikimedia
suelen estar bajo licencias como CC BY-SA, que **obligan a citar al autor y la licencia** junto a la imagen; hoy no se
cita. Opciones: añadir el crédito en cada ficha, sustituirlas por fotos propias o de los clientes, o confirmar que
tenemos otro permiso. No se ha tocado nada; la calculadora 2026 las mostraría tal como están en las fichas.

---

## 2. Seguridad: contraseña de la base de datos de Pimcore visible en el tema (detectado el 2026-10-02 y el 2026-10-05)

Hay dos archivos del tema my-listing que contienen **el usuario y la contraseña de la base de datos `pimcore`**
(servidor 51.38.234.212) escritos en claro: `affiche_pub_940.php` y `templates/calcul_distance_canal.php`. Además, los
dos construyen consultas SQL con datos que manda el visitante (Referer y URL) sin escapar, lo que permite **inyección
SQL**. Es la base de la publicidad y de las fichas de Pimcore.

**Acción recomendada:** quien gestione Pimcore y la publicidad debería cambiar esa contraseña, sacarla del código y usar
consultas preparadas. No es código del plugin 2026; no se ha tocado. Detalle técnico en `docs/TASKS.md` → SEC-001.

---

## 1. Artículos del blog que reproducen noticias de prensa (detectado el 2026-10-05)

**Qué pasa.** De los 1 936 artículos publicados en plan-canal-du-midi.com, unos **800 (41 %)** reproducen noticias de
periódicos regionales. Las cifras salen de buscar las firmas en el texto de los artículos:

| Fuente | Artículos |
|---|---|
| La Dépêche du Midi (« Photo DDM », ladepeche.fr) | 579 |
| Midi Libre | 212 |
| L'Indépendant | 23 |
| Con enlace a la noticia original | 179 |

Ejemplos: `/peniches-a-vendre-sur-le-canal-tout-savoir-avant-dacheter/` (el 6.º contenido más visitado del sitio,
firmado « Cyril Doumergue, www.ladepeche.fr ») y `/carcassonne-labattage-des-arbres-va-reprendre/` (« Photo DDM »).
La mayoría son de 2014–2016.

**Contexto.** Son los propios clientes quienes nos piden publicar estos artículos (información del 05/10).

**Por qué importa:**
- **SEO:** Google no posiciona dos veces el mismo texto. Muestra el original del periódico y descarta la copia, así que
  estos artículos casi no traen visitas. Entre oct 2025 y sep 2026, ~1 130 artículos no tuvieron ningún clic desde
  Google.
- **Derechos:** que un cliente pida publicar una noticia que habla de él no implica que tenga los derechos del texto o
  de la foto. Esos derechos son del periódico o del fotógrafo. Habría que confirmar si existe un acuerdo con La Dépêche
  o Midi Libre, o si es un riesgo aceptado.

**Opciones a valorar (no se ha hecho nada):**
1. Dejarlos como están y asumir el riesgo.
2. Mantenerlos, pero con un resumen breve y un enlace a la noticia original, en lugar del texto completo.
3. Pedir a los clientes un texto propio cuando quieran publicar algo.
4. Retirar de los buscadores (`noindex`) los que no aportan nada.

**Estado:** pendiente de comunicar. Por decisión del 05/10 no se modifica ni se genera contenido en estos artículos.
En la versión 2026 se muestran tal cual (TASK-055).
