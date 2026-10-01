# Análisis del navbar 2026 — UX, SEO, AEO y GEO (brainstorming)

**Fecha:** 2026-10-01 · **Objeto:** cabecera propia de `/accueil-2026/`, `/explorer-2026/` y `/fiche-2026/<slug>/`
(`wp-plugin/canal-home/includes/header.php`, `assets/header.css`, TASK-032) · **Estado:** análisis, nada implementado.

---

## 1. Resumen ejecutivo

**Veredicto:** la arquitectura es sólida y está alineada con la industria: mega-menú por intención, 6 entradas,
patrón *disclosure* del W3C, enlaces `<a href>` en el HTML del servidor y orden basado en datos. **No hace falta
rehacerlo.** Sí conviene pulirlo: hay un enlace muerto, faltan señales de ubicación, en móvil las dos páginas más
útiles quedan al fondo del menú, hay etiquetas que se solapan y algunas categorías mal colocadas. Además, casi todo
el enlazado interno recae en el navbar porque el footer apenas tiene 6 enlaces.

**Enfoque recomendado:** **A — mantener y pulir** ahora (cambios pequeños en un solo array PHP y algo de CSS) y,
en una segunda fase, añadir **un footer rico propio en las páginas 2026** (enfoque C parcial). Las *hub pages* por
sección se aplazan hasta que haya contenido que las justifique.

---

## 2. Marco de referencia (fuentes)

| Fuente | Qué aporta al análisis |
|---|---|
| NN/g, *Mega Menus Work Well* (2017, Nielsen & Li) — [nngroup.com/articles/mega-menus-work-well](https://www.nngroup.com/articles/mega-menus-work-well/) | Grupos de granularidad media, **cada opción una sola vez**, etiquetas diferenciadas que empiecen por la palabra con más información, panel sencillo **sin widgets ni buscadores dentro**, títulos de nivel 1 que lleven a una página real |
| NN/g, *Menu-Design Checklist* (07/06/2024) — [nngroup.com/articles/menu-design](https://www.nngroup.com/articles/menu-design/) | **Indicar la ubicación actual** («¿dónde estoy?»), vocabulario claro y familiar, menú visible en escritorio, submenús por clic, objetivos táctiles en móvil |
| Baymard, *Drop-Down / Mega Menu benchmark* — [baymard.com/…/drop-down-menu](https://baymard.com/homepage-and-category-usability/benchmark/page-types/drop-down-menu) | Los detalles de interacción y de etiquetado «aparentemente menores» llevan a los usuarios a entender mal la jerarquía; la interfaz importa tanto como la taxonomía |
| W3C APG, *Disclosure Navigation* — [w3.org/WAI/ARIA/apg/…/disclosure-navigation](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/examples/disclosure-navigation/) | Botones para abrir y cerrar, enlaces dentro; **`aria-current="page"` en el enlace de la página actual**; sin `role="menu"` |
| Google, *Link best practices* — [developers.google.com/search/docs/crawling-indexing/links-crawlable](https://developers.google.com/search/docs/crawling-indexing/links-crawlable) | Solo se rastrean `<a href>`; el texto del enlace debe ser descriptivo, conciso y relevante; toda página importante necesita al menos un enlace interno |
| Google, *SEO Starter Guide* — [developers.google.com/search/docs/fundamentals/seo-starter-guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide) | Agrupar por temas en directorios; buen texto de enlace |
| Google, *AI features and your website* (act. 10/12/2025) — [developers.google.com/search/docs/appearance/ai-features](https://developers.google.com/search/docs/appearance/ai-features) | Para AI Overviews / AI Mode no hay requisitos extra: contenido **fácil de encontrar mediante enlaces internos** y datos estructurados que coincidan con el texto visible |
| Google Search Central Blog, *Simplifying breadcrumbs* (01/2025) — [developers.google.com/search/blog/2025/01/simplifying-breadcrumbs](https://developers.google.com/search/blog/2025/01/simplifying-breadcrumbs) | En los resultados móviles ya no se muestran breadcrumbs; en escritorio y en el marcado siguen vigentes |
| Skill `seo-geo` (rúbrica, fases 4, 5 y 9) | El enlazado interno reparte autoridad; *hub-and-spoke*; textos de enlace descriptivos sin relleno de palabras clave; consistencia de entidades; BreadcrumbList en todas las páginas salvo la home |
| Referentes de turismo (navbars consultados el 01/10/2026): [tourisme-occitanie.com](https://www.tourisme-occitanie.com/), [canal-du-midi.com](https://www.canal-du-midi.com/), [france.fr](https://www.france.fr/fr/), [visitscotland.com](https://www.visitscotland.com/) | Patrón común: **5–6 entradas** (Destinos / Qué hacer / Alojamiento / Preparar / Mapa), **buscador en la cabecera en los cuatro**, mapa como acceso directo y **footer con varios grupos** (contacto, legal, plan del sitio, otros sitios) |

> **Advertencia sobre GEO:** no hay evidencia primaria de que los LLM den peso a la *estructura* de un menú. Lo que sí
> está documentado (Google) es que el enlazado interno ayuda a descubrir y elegir páginas, y que la claridad de
> entidades y textos ayuda a citar. Se descartaron cifras del tipo «−37 % de tiempo de navegación con mega-menús»
> porque solo aparecen en fuentes secundarias sin estudio original.

---

## 3. Estado actual (medido el 01/10/2026)

- 6 paneles (En bateau · Vélo & balades · Découvrir · Se loger · Manger & Boire · Préparer), 2 accesos directos
  (Distances, Carte), CTA « Planifier mon voyage » (→ home `#plan`) y cuenta.
- **98 enlaces** en la cabecera (87 en los paneles), todos `<a href>` en el HTML del servidor. Los paneles cerrados
  usan `display:none`, pero los enlaces siguen en el DOM y son rastreables.
- **Footer del tema: 6 enlaces.**
- Sin `aria-current` ni marca de sección. Breadcrumb y `BreadcrumbList`: sí en carte (`seo-carte.php`) y ficha
  (`fiche-core.php`); la home no lo necesita.
- `WebSite` + `SearchAction` apunta a `/explorer-2026/?search_keywords=…`, pero **no hay buscador en la cabecera**.
- Menú en un array PHP fijo (`canal_header_menu()`), desacoplado del menú « Principale » de WP.
- ≤1180 px: hamburguesa. Dentro, los 6 acordeones van primero y **Distances, Carte y el CTA quedan al final**
  (`header.css:300-345`).

---

## 4. Hallazgos priorizados

### 🔴 Alta

**H1 — El enlace « Calcul d'itinéraire fluvial (VNF) » lleva a un servicio cerrado.**
Evidencia: `curl https://www.vnf.fr/calculitinerairefluvial/app/Main.html` → 302 a una nota de VNF: el CIFL **cerró
el 1/6/2026** y lo sustituyen Avisbat (https://avisbat.vnf.fr/), la app Navi y EuRIS (https://www.eurisportal.eu/).
Además, el enlace está en `http://`.
Impacto: un enlace saliente roto en las 3 plantillas resta confianza (E-E-A-T) y frustra al usuario.
Fuente: Google *Link best practices* (los enlaces salientes a fuentes válidas aportan confianza).

**H2 — En móvil, las dos páginas más útiles quedan al fondo del menú.**
Evidencia: con ≤1180 px, Distances (nº 1 del sitio según GA4 en TASK-032, 14.136 vistas) y Carte solo aparecen
después de los 6 acordeones. No hay ningún acceso visible en la barra junto a la hamburguesa.
Fuente: NN/g Menu Checklist (visibilidad; las tareas frecuentes deben estar a mano). Benchmark: VisitScotland y
france.fr mantienen el mapa y la búsqueda accesibles.
*Pendiente de verificar en GA4: el porcentaje de tráfico móvil.*

**H3 — No se indica la ubicación actual.**
Evidencia: 0 `aria-current`. En carte y ficha nada señala la sección.
Fuente: NN/g 2024 («¿dónde estoy?» es fundamental) y W3C APG (`aria-current="page"`). Afecta a la accesibilidad
básica (WCAG 2.4.8 *Location*, nivel AAA, recomendable).

### 🟡 Media

**H4 — Etiquetas que se solapan o se repiten.**
- « Règles de navigation » es a la vez título de columna y enlace dentro de esa columna.
- « Les écluses du canal » (guía) frente a « Toutes les écluses » (directorio).
- « Les ports » frente a « Ports fluviaux » frente a « Haltes nautiques ».
- « Restaurant » frente a « Tous les restaurants »; « Commerce » frente a « Tous les commerces ».

Fuente: NN/g Mega Menus (etiquetas diferenciadas, cada opción una vez) y Baymard (los detalles de etiquetado
confunden la jerarquía). SEO: dos enlaces con anclas casi iguales hacia URLs distintas mandan señales confusas sobre
qué página responde a « écluses canal du midi ».

**H5 — Categorías mal colocadas.**
- « Shopping & services » (Librairie, Artisanat, Services…) dentro de *Manger & Boire*: el modelo mental del
  usuario no lo busca ahí.
- « Associations » dentro de *Découvrir › Le canal*.

Fuente: NN/g (agrupar según el modelo mental del usuario).

**H6 — Se mezclan directorio y guías sin distinguirlos.**
En la misma columna conviven fichas de directorio (`/categorie/…`, intención transaccional: «encontrar un
prestatario») y artículos (`/post-category/…` y páginas, intención informativa). Ejemplos: « À vélo » mezcla
« Location de vélo » (directorio) con « Balade à vélo sur le canal » (guía), y « Ports & écluses » mezcla igual.
Impacto AEO/GEO: las páginas-guía son las que los motores de respuesta citan, y que el usuario y el crawler distingan
«guía» de «listado» refuerza la intención de cada URL. Fuente: skill seo-geo, fase 7 (intención de búsqueda).

**H7 — El footer apenas enlaza: todo el enlazado interno recae en el navbar.**
Evidencia: 6 enlaces en el footer. Los 4 referentes consultados tienen footer de varios grupos (contacto, legal,
plan del sitio, otros sitios).
Impacto: E-E-A-T (contacto o editor visibles, el plan PDF) y una segunda vía de enlazado hacia las páginas clave con
anclas más descriptivas. Fuente: NN/g (*Footers Are Underrated*: los usuarios buscan en el footer información
importante) y skill seo-geo, fase 6 (trust signals en el footer).

**H8 — Hay un `SearchAction` declarado pero ningún buscador visible en la cabecera.**
Evidencia: `seo.php:83-86` declara búsqueda vía la carte. Los 4 referentes muestran un buscador en la cabecera.
Fuente: NN/g desaconseja buscadores *dentro* del mega-menú, no en la barra. Google pide que los datos estructurados
reflejen lo visible (en la home el buscador del hero lo cubre; en carte y ficha no hay buscador en la cabecera).

### 🟢 Baja

**H9 — Textos de enlace genéricos («Hôtel», «Bar», «Camping»).**
En el contexto visual del panel se entienden. Para Google el ancla es corta pero el destino (`/categorie/hotel/`) es
coherente.
**No** se recomienda añadir «Canal du Midi» a cada enlace: sería relleno de palabras clave, que la propia skill
desaconseja y que no mejora la citación. Basta con plurales específicos («Hôtels», «Campings») y con reforzar el
contexto en el footer y en las guías.

**H10 — Los títulos de nivel 1 son solo botones; no existe página «En bateau».**
NN/g sugiere que el nivel 1 lleve a una página real. Hoy no hay *hub pages* y el pie « Voir sur la carte » hace de
destino. Es aceptable mientras no existan hubs (ver enfoque C).

**H11 — El CTA « Planifier mon voyage » saca al usuario de carte y ficha hacia `#plan` de la home.**
Funciona, pero cambia de contexto. Es razonable mientras el planificador solo viva en la home.

**H12 — El menú en un array PHP se desincroniza del menú de WP.**
Es un riesgo de mantenimiento, no de SEO. Usar `register_nav_menu` (un menú nuevo, sin tocar el existente) sería
añadir. Hoy es YAGNI: el menú cambia poco.

**H13 — 98 enlaces de navegación preceden al contenido en el HTML.**
Los LLM que leen el HTML crudo ven primero mucho texto de navegación. Ya está mitigado: va dentro de `<nav>` y
`<header>` (los extractores lo descartan como *boilerplate*) y existen `llms.txt` y `llms-full.txt`. No requiere
acción.

---

## 5. Brainstorming por problema (alternativas → recomendación)

| # | Alternativas | Trade-offs | Recomendación |
|---|---|---|---|
| H1 VNF | a) Sustituir por Avisbat (https) · b) Sustituir por EuRIS · c) Quitarlo | a: oficial VNF, en francés y vigente · b: europeo, más complejo · c: se pierde un recurso útil para navegantes | **a)** « Avisbat — état du réseau (VNF) » → `https://avisbat.vnf.fr/` |
| H2 móvil | a) Iconos Carte y Distances visibles en la barra junto a la hamburguesa · b) Mover los tools al principio del cajón · c) Barra inferior fija | a: acceso con un toque, cabe en 390 px (logo + 2 iconos + hamburguesa) · b: barato, pero sigue tras un toque · c: complejo y compite con la UI de la carte | **a + b**: iconos en la barra y CTA arriba del cajón |
| H3 ubicación | a) `aria-current="page"` + estilo en el enlace actual y en el botón del panel que lo contiene · b) Solo estilo visual | a: accesible y estándar (APG) · b: no llega a lectores de pantalla | **a)**: comparar `home_url($path)` con la URL actual en PHP |
| H4 etiquetas | a) Renombrar (ver §7) · b) Fusionar destinos (no se puede: son URLs existentes) · c) Quitar los duplicados del menú | a: sin tocar producción · c: pierde enlaces internos a URLs con tráfico | **a)**, más **c)** solo en los pares «Restaurant / Tous les restaurants» y «Commerce / Tous les commerces» |
| H5 ubicación | a) «Shopping & services» → nueva columna «Sur place» en *Préparer* · b) Renombrar el panel a «Manger & Shopping» · c) Panel nuevo «Services» (7 entradas) | a: encaja con la tarea «preparar o resolver in situ» · b: el título se alarga y sigue mezclando · c: más carga en la barra (NN/g: menos opciones) | **a)**; « Associations » → *Préparer › Aide* |
| H6 guía/listado | a) Microetiqueta o icono por tipo (p. ej. icono de libro para guías) · b) Reordenar columnas: «Trouver un prestataire» frente a «Guides & conseils» · c) Nada | a: poco cambio y bastante señal · b: más claro, pero reescribe la IA de los paneles · c: — | **b)** solo en *En bateau* y *Vélo*, donde la mezcla es mayor; el resto queda igual |
| H7 footer | a) Footer rico propio en las páginas 2026 (hubs, herramientas, plan PDF, editor y contacto, legal) · b) Ampliar el footer del tema (tocaría lo existente) · c) Nada | a: aditivo y bajo el control del plugin; segunda vía de enlazado; E-E-A-T · b: viola la regla «solo añadir» | **a)**, dentro del plugin y solo en las 3 plantillas |
| H8 búsqueda | a) Icono de lupa en los tools que abre un campo y envía GET a la carte `?search_keywords=` · b) Campo siempre visible · c) Nada | a: coherente con el `SearchAction`, sin widget dentro del mega · b: ocupa sitio en 1181–1439 px · c: incoherencia con el JSON-LD | **a)** |
| H10 hubs | a) Crear páginas hub `-2026` por sección · b) El título del panel enlaza a la carte filtrada · c) Nada | a: GEO potente (pillar + cluster), pero es contenido nuevo que hay que escribir · b: rápido, aunque la carte no es contenido citable · c: — | **c)** ahora; **a)** como proyecto aparte si se busca autoridad temática |

---

## 6. Enfoques globales de estructura

**A — Mantener y pulir (RECOMENDADO).**
Las mismas 6 entradas y el mismo orden basado en GA4, con los cambios H1–H8.
Ventajas: el coste es bajo, el riesgo nulo para producción y conserva el trabajo de TASK-032 validado con datos.
Coincide en forma con los 4 referentes (5–6 entradas por intención y mapa como acceso directo).

**B — Reorganizar por fases del viaje** (S'inspirer · Préparer · Sur place · Après).
Es el patrón de france.fr y Occitanie («S'inspirer», «Infos pratiques»).
Desventajas: rompe el orden por frecuencia y diluye el modo de viaje (bateau o vélo), que **es** el diferenciador del
canal y lo que la gente busca. En este sitio de nicho, el modo de transporte manda sobre la fase del viaje.
**Descartado.**

**C — Navbar ligero + footer rico + hub pages.**
4 entradas en el navbar con enlace a hubs, y el detalle se mueve a hubs y footer.
Ventajas: el mejor modelo para GEO (*hub-and-spoke*, páginas pillar citables) y menos enlaces por página.
Desventajas: exige escribir 4–6 hubs nuevos con calidad editorial; hasta que existan, quitar enlaces del navbar
empeora el descubrimiento.
**Recomendado solo en parte**: el footer rico ya (TASK-041) y los hubs como proyecto editorial futuro.

---

## 7. Propuesta concreta del menú (enfoque A)

Solo cambian etiquetas, la ubicación de algunos grupos y un enlace. Todas las URLs de destino son las actuales,
salvo la de VNF.

```
En bateau
  Louer & naviguer:     Location de bateau · Croisière en bateau · Activités nautiques · Péniches à vendre
  Ports & écluses:      Ports de plaisance (/categorie/ports/) · Ports fluviaux · Haltes nautiques
                        · Les 63 écluses : guide (/les-ecluses-du-canal-du-midi-2/)
                        · Annuaire des écluses (/categorie/ecluses/) · Dimensions des écluses
  Naviguer, mode d'emploi:  Période de navigation · Règles de navigation · Permis de conduire
                        · Les panneaux · Passer une écluse · Se préparer au voyage en bateau
                        · Avisbat — état du réseau (VNF) ↗ (https://avisbat.vnf.fr/)
Vélo & balades
  À vélo — prestataires:    Location de vélo · Voyage organisé à vélo · Le canal à vélo (/categorie/velo/)
  À vélo — guides:          Voie verte et véloroute · Balade à vélo sur le canal · Chemins de halage : conditions
  Autrement:                (igual)
Découvrir
  Le canal:  Histoire · Construction · Ouvrages · Alimentation en eau · La faune et la flore · Le Canal de la Robine
  À voir / Médias:  (igual)
Se loger:  (igual; plurales: Campings, Hôtels, Gîtes…)
Manger & Boire
  Restaurants:  Tous les restaurants · Brasseries / Snacks · Bateaux-restaurants · Bars        (sin «Restaurant»)
  Produits & vins:  Tous les commerces alimentaires · Vente de vins · Produits régionaux
                    · Boulangeries / Pâtisseries · Supermarchés / Épiceries
Préparer
  Outils:     (igual)
  Agenda:     (igual)
  Sur place:  Shopping · Librairies · Artisanat · Commerces · Services
  Aide:       Foire aux questions · Lieux d'informations · Associations du canal
```

**Por verificar antes de renombrar** (sin datos hoy):
- Qué diferencia hay entre `/categorie/ports/` y `/categorie/ports-fluviaux/`, y si alguna está vacía. Si una está
  vacía, quitarla del menú.
- Si « Restaurant » (`/categorie/restaurant/`) tiene un volumen propio en GA4 que justifique mantenerlo.

**Barra:** añadir la lupa (H8). En móvil, mostrar Carte y Distances como iconos en la barra (H2). Aplicar
`aria-current` y el estado de sección (H3).

---

## 8. Tareas candidatas (no añadidas a TASKS.md)

| ID | Tarea | Prioridad | Esfuerzo |
|---|---|---|---|
| TASK-037 | Navbar: sustituir el enlace VNF/CIFL (cerrado el 1/6/2026) por Avisbat (https) | Alta | 5 min |
| TASK-038 | Navbar móvil: Carte y Distances visibles en la barra; tools y CTA arriba del cajón | Alta | S |
| TASK-039 | Navbar: `aria-current="page"` y marca de la sección activa (PHP + CSS) | Alta | S |
| TASK-040 | Navbar: renombrar etiquetas, reubicar Shopping/Associations y separar guías de directorio en Bateau/Vélo (§7) | Media | S |
| TASK-041 | Footer rico propio en las páginas 2026: hubs, herramientas, plan PDF, editor y contacto, legal (aditivo) | Media | M |
| TASK-042 | Lupa en la cabecera → carte `?search_keywords=` (coherente con el `SearchAction`) | Media | S |
| TASK-043 | (Futuro) Hub pages `-2026` por sección (En bateau, À vélo, Découvrir, Se loger) como pillars GEO | Baja | L |

**Restricciones para todas:** producción en WordPress con PHP 7.4; solo añadir, sin tocar lo existente (todo vive en
el plugin y solo en las 3 plantillas 2026); verificación visual en el navegador a 1440 y 390 px; desplegar con
`remote.sh deploy` y las páginas siguen privadas.
