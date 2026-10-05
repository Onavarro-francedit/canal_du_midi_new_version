# Calcul de distance — benchmark y propuestas de mejora (TASK-057)

**Fecha:** 2026-10-05 · **Página:** `/calcul-de-distance-canal-du-midi/`, la nº 1 del sitio: 12 671 vistas, 4 146
usuarios y 7 751 clics de Google en 12 meses (20 % del tráfico del sitio). El 76 % de los clics llegan desde el móvil.

---

## 1. Qué hace hoy

- Un formulario con dos `<select>` (62 esclusas, de Toulouse a Agde) que envía un POST y recarga la página. El
  resultado es la distancia y el tiempo en barco (distancia ÷ 6 km/h) y en bici (distancia ÷ 13 km/h).
- Debajo, el texto de la página: una lista de ciudades con km y tiempos en barco y en bici, con 28 imágenes de iconos.
- Los datos (nombre de la esclusa + PK) se leen en cada visita de la base `pimcore` (`object_store_65`).
- ⚠️ El código tiene credenciales en claro e inyección SQL (SEC-001, `docs/para-direccion.md` §2).

### Problemas medidos

| # | Problema | Evidencia |
|---|---|---|
| P1 | **El tiempo en barco no cuenta las esclusas** | Castelnaudary → Trèbes: la herramienta da 8,7 h; Le Boat publica 13 h (31 esclusas). Subestima ~⅓ |
| P2 | **Solo se puede elegir una esclusa**, no una ciudad ni un puerto | La gente busca « distance Toulouse Castelnaudary », « Carcassonne Narbonne », « Toulouse Béziers » |
| P3 | **Faltan tramos y puntos** | No están Fonseranes (9 esclusas, Béziers), el Grand Bief (Argens → Béziers, 54 km sin esclusas: Le Somail, Capestang), el final en el étang de Thau, ni el canal de la Robine (Narbonne, Port-la-Nouvelle: página nº 3 del sitio) |
| P4 | No dice **cuántas esclusas** hay entre los dos puntos | Es el dato que más cambia el tiempo en barco y el que da Le Boat |
| P5 | No traduce el tiempo a **días ni etapas** | « combien de temps pour faire le canal du midi en bateau » (109 impresiones, posición 10,9) |
| P6 | No hay modo **a pie** | « canal du midi à pied », « itinéraire canal du midi à pied » |
| P7 | La tabla de distancias es texto con iconos, no una tabla | « tableau distance canal du midi »: 2 143 impresiones; Google y las IA leen mejor una `<table>` |
| P8 | No hay mapa del tramo ni enlace a qué hay en el destino | Los usuarios eligen y se van; no hay paso hacia fichas (loueurs, alojamiento) |
| P9 | Resultado no compartible (POST); recarga en cada cálculo; `select` de 15 px de alto en móvil | UX móvil |

---

## 2. Qué existe en internet (validado y probado)

| Producto | Qué hace bien | Límites frente a lo nuestro |
|---|---|---|
| **VNF Navi** — app oficial de Voies navigables de France ([App Store](https://apps.apple.com/app/id1550922830)) | Cálculo de ruta fluvial con **hora de llegada estimada**, avisos a la navegación y cierres en tiempo real, disponibilidad de esclusas, modo sin conexión y POI de datatourisme | Solo app (no web), solo barco y para toda Francia: no está pensada para preparar un viaje en el Canal du Midi ni para la bici |
| **France Vélo Tourisme** — sitio oficial de las rutas ciclistas ([etapa Toulouse → Montgiscard](https://www.francevelotourisme.com/itineraire/le-canal-des-2-mers-a-velo/toulouse-montgiscard)) | Etapas con **km, duración (~15 km/h), dificultad, desnivel, firme, GPX**, servicios « Accueil Vélo », **estaciones de tren** y « Créer mon voyage » | Solo bici y por etapas fijas: no calcula entre dos puntos cualesquiera ni da tiempos en barco |
| **Le Boat / Locaboat / Nicols** — loueurs ([Le Boat, base de Trèbes](https://www.leboat.com/en/boating-holidays/france/canal-du-midi/trebes)) | **Tablas de horas de navegación entre bases contando las esclusas** (Trèbes → Castelnaudary: 13 h, 31 esclusas), días por itinerario | Solo entre sus propias bases; dentro de su embudo de venta |
| **CanalPlanAC** y planificadores británicos ([método](https://waterways.org.uk/?p=440)) | El modelo de referencia: **tiempo = km ÷ velocidad + minutos por esclusa** (15–20 min), plan por días | No confirmo que cubra el Canal du Midi; en inglés |
| **Komoot / Bikemap** | Trazado, GPX y desnivel para bici | Genéricos, sin barco ni esclusas |

**Conclusión:** no existe una herramienta web, gratuita y en francés que calcule **entre dos puntos cualesquiera del
Canal du Midi** el tiempo en barco con las esclusas, en bici y a pie. VNF es solo barco y solo app; France Vélo Tourisme,
solo bici por etapas; los loueurs, solo entre sus bases. **Nuestro nicho es válido** y es la página nº 1 del sitio: no
conviene sustituirla, sino mejorarla con lo que estos productos ya validaron y enlazar a VNF y FVT como complementos.

---

## 3. Propuestas de mejora (por prioridad)

### Imprescindibles (versión 2026)

1. **Tiempo en barco con las esclusas** (P1, P4). Modelo de CanalPlanAC y los loueurs: `km ÷ 7 km/h + 10 min por cámara`.
   Las escalas cuentan cada cámara: *Saint-Roch* son 4 y *Fonseranes*, 9. Calibrado con Le Boat: Castelnaudary →
   Trèbes da 13,2 h (publicado: 13 h). Se muestra el número de esclusas del tramo. Los parámetros son constantes con
   comentario, para ajustarlos si hace falta.
2. **Elegir ciudades y puertos, no solo esclusas** (P2, P3). Toulouse (Port de l'Embouchure), Ramonville, Castelnaudary
   (Grand Bassin), Bram, Carcassonne, Trèbes, Homps, Le Somail, Capestang, Béziers, Portiragnes, Agde y Marseillan /
   étang de Thau, además de las 63 esclusas con Fonseranes. Un buscador único con autocompletado
   (`<input list>` + `<datalist>`, nativo).
3. **Canal de la Robine** (P3): Sallèles-d'Aude → Narbonne → Port-la-Nouvelle (jonction + Robine), como ramal.
4. **Barco, bici y a pie a la vez** (P6): barco (con esclusas), bici a 15 km/h (la referencia de France Vélo Tourisme)
   y a pie a 4 km/h. **Días** (P5): barco, ~6 h de navegación al día (horario de esclusas 9 h–19 h con pausa); bici,
   40–60 km al día (lo que ya dice la FAQ de la home).
5. **Cálculo instantáneo y compartible** (P9): JS sin recargar, botón ⇄ para invertir, URL `?de=…&a=…` para compartir y
   enlazar. Sin JS, el formulario sigue funcionando (GET).
6. **Tabla de distancias real** (P7): una `<table>` HTML entre las ~12 ciudades principales (km · esclusas · h en barco
   · h en bici), generada de los mismos datos. Es la respuesta a « tableau distance canal du midi » y la que pueden citar
   Google y las IA. JSON-LD: `WebApplication` + `FAQPage` con 4–5 trayectos reales (Toulouse → Castelnaudary,
   Carcassonne → Béziers…).
7. **Datos en el plugin**, no en Pimcore: un array PHP con nombre, PK, cámaras, municipio y tipo
   (esclusa/puerto/ciudad), con test. Sin conexión a la base `pimcore` ni credenciales (resuelve SEC-001 en esta página).

### Recomendadas (misma tarea si cabe; si no, 057b)

8. **« Sur le trajet » / « À l'arrivée »** (P8): enlaces a la carte 2026 filtrada cerca del destino (loueurs de bateau,
   location de vélo, hébergements) y a las fichas de los puertos y esclusas del tramo, que ya existen con coordenadas.
9. **Avisos útiles**: horario de las esclusas por temporada (ya está en la FAQ de la home) y un enlace a
   **Avisbat (VNF)** para los cierres, más un enlace a **VNF Navi** y a **France Vélo Tourisme** (GPX, trenes).

### Después (no ahora)

10. Mapa con el tramo resaltado: necesita la geometría del canal (OSM). La carte 2026 ya tiene mapa.
11. Hora de llegada estimada según la hora de salida y el horario de las esclusas, como VNF Navi.
12. GPX del tramo para bici (como France Vélo Tourisme).

---

## 4. Datos

- PK de las 62 esclusas actuales: el `<select>` de la página (origen `pimcore.object_store_65`), copiados al plugin.
  Faltan Fonseranes (~PK 206, 9 cámaras: 8 esclusas + 1 de salida) y los puertos y ciudades: se toman del plan oficial y
  de las fichas (puertos con coordenadas). **Verificar cada PK añadido con una segunda fuente (VNF / plan oficial)
  antes de publicar.**
- Robine: PK propio del ramal (jonction de la Robine desde el Canal du Midi en ~PK 168, Sallèles), a documentar con
  fuente.
