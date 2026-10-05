# GEO-IA — Visibilidad de plan-canal-du-midi.com en los motores de IA y mejoras propuestas

**Fecha:** 2026-10-05 · **Autor:** otra sesión de Claude Code (la del proyecto GEO-IA), a petición del usuario.
**Para:** la sesión de Claude que trabaja en `canal_du_midi`. **Cuándo aplicarlo:** cuando termines lo que tienes en curso
(TASK-053). Está registrado como **TASK-064** en `docs/TASKS.md` (🟡 Pendiente).

> Resumen en 5 líneas
> 1. Hemos montado una herramienta propia, **GEO-IA**, que cada semana pregunta en ChatGPT, Gemini, Claude, Perplexity
>    (y Copilot cuando el usuario inicie sesión) y en el modo IA de Google si citan plan-canal-du-midi.com.
> 2. Primera medición (05/10): **nos citan en el 20 % de las respuestas** (10 de 50). Google IA 5/10, Perplexity 3/10,
>    Gemini 2/10, **ChatGPT 0/10, Claude 0/10**.
> 3. Ganamos en **écluses / période de navigation**, Robine, distances. Perdemos del todo en **météo, campings,
>    location de bateau y « que voir »**: en esos temas el ganador es **canal-du-midi.com** (55 citas).
> 4. Proponemos 5 mejoras (+ 4 técnicas pequeñas). Una ya está hecha (TASK-054, PDF). Las editoriales necesitan el **OK
>    del usuario** porque el 05/10 se decidió no generar contenido para artículos (ver §6).
> 5. La medición se repite sola cada semana: después de aplicar una mejora, márcala como « Hecha » en el panel
>    `http://localhost/geo-ia/` para ver si sube la visibilidad de ese tema.

---

## 1. Qué es GEO-IA y por qué existe

El usuario quería saber **cómo aparece el sitio cuando un viajero pregunta a una IA** (no a Google clásico) y mejorar esa
referencia (SEO + GEO + AEO). Las herramientas de pago (Peec AI, Profound, DataForSEO) cuestan dinero; DataForSEO exige una
recarga mínima de 50 $. Se decidió un experimento propio y gratuito: si funciona y las citas suben, el usuario propondrá a su
jefe una herramienta de pago que respete las condiciones de uso.

**Dónde vive (fuera de este repo, a propósito — servirá para otros sitios de France Édition):**

| Pieza | Ubicación |
|---|---|
| Repo | `/Applications/XAMPP/xamppfiles/htdocs/geo-ia/` (git propio) |
| Diseño y plan | `geo-ia/docs/superpowers/specs/2026-10-05-geo-ia-design.md`, `…/plans/2026-10-05-geo-ia.md` |
| Base de datos | MySQL local `geo_ia` (XAMPP) |
| Panel visual | **http://localhost/geo-ia/?site=canal-du-midi** |
| Skill | `geo-ia` (`~/.claude/skills/geo-ia` → `geo-ia/skill/SKILL.md`) |
| Programación | launchd `com.francedit.geo-ia`, lunes–viernes 9:00; corre 1 vez por semana (`cli.php toca-hoy`) |
| Informe de cada semana | `geo-ia/sites/canal-du-midi/runs/<fecha>/informe.md` + `respuestas.md` (texto íntegro) |
| Config del sitio | `geo-ia/sites/canal-du-midi/site.json` (dominio, patrones de marca, páginas clave) y `panel.json` (preguntas) |

**No tienes que tocar GEO-IA** para aplicar las mejoras. Solo, opcionalmente, leer el panel o los informes.

## 2. Cómo mide (metodología — para que sepas cuánto fiarte de los números)

Investigación previa (cómo lo hacen los servicios de pago y los estudios):
- [Peec AI](https://peec.ai/blog/the-key-to-prompt-tracking) y Profound siguen un **panel fijo** de preguntas para que la
  tendencia sea comparable; cambiar las preguntas cada semana rompe la serie.
- Estudios 2026 ([arXiv 2604.07585](https://arxiv.org/pdf/2604.07585), [2607.13304](https://arxiv.org/pdf/2607.13304)):
  repetir la misma pregunta >5 veces no aporta; la fiabilidad viene de **muchas preguntas distintas, varias formulaciones
  y varios motores**.
- [Profound](https://tryprofound.com/features/prompt-volumes) elige las preguntas a partir de conversaciones reales.

Lo que hacemos:
- **10 temas fijos** sacados de las consultas reales de Search Console (04/09–02/10): carte, distances, vélo, bateau,
  Robine, écluses, hébergement, météo, à voir, histoire. Cada tema tiene **4 formulaciones** (FR/EN, perfiles: familia,
  ciclista, pareja, primera vez). Cada semana se usa, por tema, la formulación más antigua → la frase cambia, el tema no.
- **3–4 preguntas exploratorias** por semana a partir de Search Console (fuera de la métrica principal).
- **Un chat temporal nuevo por pregunta** (ChatGPT chat temporal, Gemini discusión temporal, Claude incógnito, Perplexity
  incógnito, Google modo IA `udm=50`), con la cuenta del usuario, motor por motor, 20–40 s entre preguntas.
- **Métrica principal:** % de respuestas del panel que **enlazan** nuestro dominio como fuente. La «mención» sin enlace se
  cuenta aparte.

**Límites a tener en cuenta:**
- n pequeño: cada celda tema×motor es **1 respuesta por semana**. Un 0 % o un 100 % aislado no es concluyente; mira
  tendencias de 4 semanas.
- **ChatGPT está sesgado**: su chat temporal sigue usando la memoria de la cuenta del usuario (respondió en español y sabía
  del proyecto). Hasta que el usuario desactive la memoria, sus datos no son fiables.
- Copilot aún no se mide (exige iniciar sesión; la extensión no puede abrir el login de Microsoft).

## 3. Resultados de la primera medición (2026-10-05)

**Global:** 20 % (10/50 respuestas del panel). Menciones de marca: 2 % — y se nos nombra como **« L'Officiel du Canal du
Midi »** (Gemini, Google IA), no como « Plan Canal du Midi ». *(Dato útil para el schema `Organization.alternateName`, §5.)*

**Por motor:** Google IA 5/10 · Perplexity 3/10 · Gemini 2/10 · ChatGPT 0/10 · Claude 0/10 (Claude casi nunca enlaza
fuentes).

**Por tema (motores que nos citan / 5):**

| Tema | Pregunta de esta semana | Citas | Nuestra página citada |
|---|---|---|---|
| écluses | « Quels sont les horaires d'ouverture des écluses… » | **3/5** (Perplexity y Google IA en **posición 1**) | `/navigation/periode-de-navigation/`, `/navigation/`, `/navigation/regles-de-navigation/` |
| carte | « Où trouver une carte détaillée… avec les écluses et les villes ? » | 2/5 | home, `Plan-Canal-du-Midi-2023.pdf`, `/les-ecluses-du-canal-du-midi-2/` |
| robine | « Qu'est-ce que le canal de la Robine… » | 2/5 | `/canal-de-la-robine/` (pos. 7 y 9) |
| distances | « Quelle est la distance… combien de temps… » | 1/5 | `/calcul-de-distance-canal-du-midi/` (pos. 6) |
| vélo | « Faire le canal du Midi à vélo : quelles étapes… » | 1/5 | `/voie-verte-et-veloroute/` (pos. 11) |
| histoire | « Comment le canal du Midi est-il alimenté en eau ? » | 1/5 | `/alimentation-en-eau-du-canal/` (pos. 8) |
| **météo** | « Quel temps fait-il… meilleure période… » | **0/5** | — |
| **hébergement** | « Quels campings se trouvent au bord du canal… » | **0/5** | — |
| **bateau** | « Comment louer un bateau sans permis… » | **0/5** | — |
| **à voir** | « Quels sont les plus beaux villages et sites… » | **0/5** | — |

**Exploratorias:** « péniche à vendre » → nos citan Perplexity (pos. 9) y Google IA (pos. 8):
`/peniches-a-vendre-sur-le-canal-tout-savoir-avant-dacheter/` funciona. « Que voir à Sallèles-d'Aude » → solo Perplexity
(pos. 10, `/fiche/port-de-salleles-daude/`); en GSC esa consulta tiene 1 542 impresiones/mes, posición 10, CTR 0,7 %.
« Vignobles et caves cet automne » → nadie nos cita (no tenemos página).

**Quién sale en nuestro lugar (citas en todas las respuestas):** canal-du-midi.com **55** · vnf.fr 12 ·
tourisme-occitanie.com 11 · audetourisme.com 10 · cotedumidi.com 9 · tourismecanaldumidi.fr 9 ·
le-canal-du-midi-a-velo.fr 8 · canaldes2mersavelo.com 7 · riverly.com 6 · crisboat.com 5.

**Páginas competidoras más citadas por tema** (lo que la IA prefiere — estúdialas antes de escribir):

| Tema | URLs citadas (≥2 veces) |
|---|---|
| météo | canal-du-midi.com/organiser-sa-visite/quand-venir/ · levelovoyageur.com/…/quelle-est-la-meilleure-periode-pour-faire-le-canal-du-midi-a-velo/ · belle-allure.voyage/blog/quand-faire-le-canal-du-midi-a-velo · profil-voiles.fr/quelle-est-la-meilleure-periode… |
| hébergement | canal-du-midi.com/organiser-sa-visite/dormir/campings/ (×3) · camping-colombiers.com · campingvaldecesse.com/camping-proche-canal-du-midi/ |
| bateau | leboat.com/fr/croisiere-fluviale/france/canal-du-midi · filovent.com/location-bateau-France/… |
| à voir | canal-du-midi.com/decouvrir/incontournables/ (×3) · audetourisme.com/…/canal-du-midi/spots/ (×3) · riverly.com/croisieres-france-canal-du-midi-villages/ · toploc.com/blog/france/visiter-le-canal-du-midi |
| carte | canal-du-midi.com/organiser-sa-visite/cartes-canal-du-midi/ (×4) · fluviacarte.com/…/voie-canal-du-midi-64 · canalmidi.com/tracedetaille.html |
| distances | canal-du-midi.com/…/faire-du-velo-le-long-du-canal/ · …/combien-de-temps/ |
| vélo | dodocyclo.org · francevelotourisme.com/…/le-canal-du-midi-a-velo · globe-trotting.com · canaldes2mersavelo.com |
| robine | canal-du-midi.com/…/narbonne/ · tourisme-occitanie.com/…/narbonne-balade-canal-robine/ · cotedumidi.com · narbonne.fr/canal-robine |
| écluses | canalmidi.com/bateau.html · canal-du-midi.com/…/tout-savoir-pour-naviguer/ · ladepeche.fr (actualidad horarios 2026) |
| histoire | vnf.fr/…/saint-ferreol-aux-sources-du-canal-du-midi/ |

Texto íntegro de las 65 respuestas: `geo-ia/sites/canal-du-midi/runs/2026-10-05/respuestas.md`.

## 4. Qué tienen en común las páginas que la IA cita (patrón observado)

Comparando las páginas ganadoras con las nuestras:
1. **Respuesta directa en las 2–3 primeras frases**, con la pregunta casi literal en el H1/H2 (« Quand venir sur le canal
   du Midi ? »). Nuestras páginas de categoría (`/categorie/camping/`, `/categorie/location-bateau/`) son **directorios
   sin texto**: la IA no tiene nada que citar.
2. **Una página por intención** (canal-du-midi.com tiene « quand venir », « campings », « incontournables », « cartes »,
   « combien de temps »). No hace falta que sean más ricas que las nuestras — su ventaja es que **existen y responden**.
3. **Datos concretos y comparables:** tablas (mes a mes, base → itinerario → horas), cifras (PK, nº de esclusas, km).
4. Donde ganamos (écluses/navigation) es justo donde tenemos **páginas de referencia con datos** (horarios, periodos,
   reglas). Es la prueba de que el formato funciona para nosotros.

## 5. Mejoras propuestas — detalle para aplicarlas

> Encaje con las reglas del proyecto (léelas en `CLAUDE.md`): **en producción solo se añade**; páginas nuevas con sufijo
> `-2026` y privadas hasta publicar; textos **editables desde wp-admin** (memoria `wp-admin-editable`); UI sencilla
> y propia (`ui-simple-identidad-propia`). La plantilla de contenido 2026 (TASK-055) y los listados `/categorie-2026/<slug>/`
> del inventario (`docs/inventario-paginas-2026-10-05.md` §plantillas) son el sitio natural para casi todo lo de abajo.

### M1 · [alta] Météo: sección « Meilleure période » con tabla mes a mes — tema `meteo` (0/5)
- **Página:** `/meteo-du-canal-du-midi/` (#8 del inventario: 953 vistas, 726 clics GSC, posición 7,8). Hoy ≈450 palabras
  + widget de previsión; **no responde « ¿cuándo ir? »**.
- **Qué añadir (en su versión -2026):**
  - 2–3 frases de respuesta directa arriba: mayo–junio y septiembre–octubre como mejores meses; julio–agosto calor y
    afluencia; navegación cerrada/limitada en invierno (chômage, nov.–marzo — **comprobar fechas exactas con
    `/navigation/periode-de-navigation/`**, que es nuestra página más citada).
  - Tabla mes a mes: T° media mín/máx, días de lluvia, viento (tramontane / autan), estado de la navegación, afluencia.
    Por Toulouse / Carcassonne / Béziers si se puede. **Fuente de datos citada** (Météo-France / climatologías), no inventada.
  - FAQ corta con `FAQPage` (« Quelle est la meilleure période… ? », « Peut-on naviguer en hiver ? », « Le canal à vélo en
    été ? »).
- **Por qué:** 0/5 motores, mientras ganan canal-du-midi.com/quand-venir, levelovoyageur, belle-allure, que sí responden
  la pregunta estacional.

### M2 · [alta] Guía « Campings au bord du canal du Midi » con distancia a la orilla y PK — tema `hebergement` (0/5)
- **Página:** `/categorie/camping/` es solo directorio. Opción 1: texto introductorio + tabla en el **listado
  `/categorie-2026/camping/`** (plantilla de listado del inventario). Opción 2: página-guía propia `-2026`.
- **Contenido:** respuesta directa + tabla `camping · commune · PK · distancia a la orilla (bord immédiat / < 1 km /
  > 1 km) · tramo · Accueil Vélo · apertura`, generada **desde los datos de las fichas** (Pimcore/WP) — no a mano.
- **Ventaja única:** ChatGPT y Perplexity insisten en la distinción « au bord » vs « à proximité » y **nadie la publica
  con datos**. Nosotros tenemos las coordenadas de las fichas y el trazado (calcul de distance, TASK-057).
- **Ganan hoy:** canal-du-midi.com/…/dormir/campings/ (137 campings, sin distancias), campingvaldecesse, camping-colombiers.

### M3 · [alta → ya hecha en gran parte] PDF del plan
- **Hallazgo:** Google IA recomienda « télécharger le Plan du Canal du Midi en PDF » pero enlaza
  `/wp-content/uploads/pdf/Plan-Canal-du-Midi-2023.pdf`.
- **Estado:** **TASK-054 (05/10) ya lo resolvió**: las ediciones 2022–2026 hacen 301 a `/plan-canal-du-midi.pdf`
  (comprobado: `…2023.pdf` → 301 → `/plan-canal-du-midi.pdf` → 200). Google IA tardará en refrescar su índice.
- **Lo que queda:** en la carte (`/explorer/` y `/explorer-2026/`) y en la home, un bloque de respuesta directa encima
  del enlace: « Carte détaillée gratuite du canal du Midi : 63 écluses avec PK, villes et ports — PDF à télécharger (édition
  2026) », con la **fecha de edición visible**. Perplexity y Claude citan canal-du-midi.com/cartes, IGN y Fluviacarte, no a
  nosotros. *(Nota: `/carte/` da 404; la carte es `/explorer/`.)*

### M4 · [media] Guía « Louer un bateau sans permis » con tabla de bases — tema `bateau` (0/5)
- **Página:** `/categorie/location-bateau/` es un directorio. Igual que M2: listado 2026 con introducción o guía `-2026`.
- **Contenido:** respuesta directa (sin permiso, carte de plaisance temporaire, 30–60 min de formación, 8 km/h máx. —
  **verificar** con `/navigation/regles-de-navigation/`); tabla `base de salida → itinerario → nº de esclusas → horas de
  navegación → duración recomendada` (Castelnaudary, Trèbes, Homps, Argens, Le Somail, Capestang, Colombiers,
  Port-Cassafières, Agde) usando el **calcul de distance** para km/esclusas/horas; presupuesto orientativo solo si hay
  fuente; FAQ; enlaces a nuestras fichas de loueurs (son clientes: no favorecer a ninguno).
- **Ganan hoy:** leboat, filovent, nicols, canalous, alpha-croisière, canal-du-midi.com.

### M5 · [media] « Les plus beaux villages et sites du canal du Midi » ordenados por PK — tema `a_voir` (0/5)
- **Contexto:** ya existen las **20 etapas `/etape-2026/<slug>/` + índice `/etapes-2026/`** (TASK-060) y
  `/post-category/villes-a-visiter/` (la única categoría del blog con algo de Google, 30 clics).
- **Propuesta:** que el **índice `/etapes-2026/`** (o una página de síntesis) responda la pregunta: top 15 Toulouse → Thau
  con H2 por sitio, PK, qué ver en 2 líneas, **una cifra concreta** (Fonseranes 8 bassins / 21 m, tunnel du Malpas 1679,
  Grand Bassin…), enlace a la etapa/ficha y al mapa; schema `ItemList` de `TouristAttraction`; fecha de actualización.
- **Ganan hoy:** canal-du-midi.com/decouvrir/incontournables/ (10 sitios, un H2 por sitio), audetourisme (spots),
  cotedumidi, grandsitecanaldumidi.

### Mejoras técnicas pequeñas (sin contenido nuevo)
- **T1 · Marca « L'Officiel du Canal du Midi »**: así nos llaman Gemini y Google IA. Añadirlo como `alternateName` en el
  schema `Organization`/`WebSite` de las páginas 2026 (ver `includes/seo.php`) y en `llms.txt` / `llms-full.txt`
  (PRD-013: regenerar al publicar).
- **T2 · Sallèles-d'Aude** (exploratoria): 1 542 impresiones/mes, posición 10, CTR 0,7 %. Reforzar la ficha del puerto /
  la etapa con un bloque « Que voir à Sallèles-d'Aude » (Amphoralis, écluse, canal de jonction, Gailhousty). Ganan
  sallelesdaude.fr/patrimoine, cotedumidi (canal de jonction), canal-du-midi.com (Gailhousty).
- **T3 · Péniches à vendre**: la guía ya nos trae citas de Perplexity y Google IA. No tocar el fondo; solo asegurar que su
  versión 2026 conserva la estructura (es un ejemplo de lo que funciona).
- **T4 · Vélo / distances**: nos citan en posición 6–11. `/voie-verte-et-veloroute/` y el calcul ganarían con una
  respuesta directa arriba (« 240 km de Toulouse à Sète, 5–7 jours à vélo, X km goudronnés… ») — **cifras a verificar**
  con nuestros propios datos del calcul (TASK-057).

## 6. Decisiones del usuario que afectan a estas mejoras (¡léelo antes de escribir!)

- **TASK-056 (enriquecimiento editorial IA) fue DESCARTADA el 05/10** y en SESSION.md consta: « no generar contenido para
  artículos (los publican los clientes) ». **M1, M2, M4 y M5 son guías editoriales nuevas**, no artículos de clientes,
  pero **pide confirmación al usuario** antes de redactarlas. Alternativa compatible: construirlas **a partir de datos**
  (fichas, PK, calcul de distance, periodos de navigación) con poco texto, como el calcul (TASK-057).
- **No inventar cifras**: toda temperatura, horario o precio con fuente. La IA cita precisamente lo verificable.
- **Producción: solo añadir** (`-2026`, privado, publicar solo con orden explícita — TASK-063).

## 7. Cómo saber si funcionó

1. Al desplegar una mejora, abre **http://localhost/geo-ia/?site=canal-du-midi** → sección « Mejoras propuestas » → botón
   **« Hecha »**. Queda la fecha y el gráfico del tema la marca con una línea discontinua.
2. GEO-IA mide cada semana (lunes 9:00). Ojo: mientras las páginas sean **privadas (`-2026`) la IA no puede verlas**; el
   efecto solo se medirá **después de publicar** (TASK-063) y de que los motores re-indexen (días–semanas).
3. Mira la tendencia del tema a 4 semanas, no un dato suelto (n = 1 por celda y semana).

## 8. Pendientes que son del usuario (no de esta sesión)

- Desactivar memoria / instrucciones personalizadas de ChatGPT (sesgo).
- Iniciar sesión en Copilot en el Chrome de la extensión.
- Bing Webmaster: ChatGPT busca sobre el índice de Bing → al publicar las páginas 2026, enviarlas con SubmitUrl
  (memoria `open-seo-local`, clave `BING_WMT_KEY`).
