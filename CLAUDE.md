# Canal du Midi - Guía del Proyecto

Plataforma Travel-Tech premium para la gestión de reservas, itinerarios e información turística del Canal du Midi, construida con arquitectura Hexagonal, MVC y PHP puro.

## 🚀 Comandos Rápidos (Entorno Local)

- **Servidor:** XAMPP / Apache (Ruta: `http://localhost/canal_du_midi/`)
- **Base de Datos:** MySQL (Acceso vía phpMyAdmin)
- **Migración de Datos:** `php scripts/migrate_wp_data.php` (pendiente de creación)
- **Depuración:** Activar `display_errors` en `public/index.php`.

## 🏗️ Arquitectura y Estructura

El proyecto sigue el patrón de **Arquitectura Hexagonal (Puertos y Adaptadores)** y **MVC**.

- `src/Domain/Models/`: Entidades puras de negocio (ej. `Service`, `POI`, `Review`). Sin dependencias externas.
- `src/Domain/Repositories/`: Interfaces (Puertos) que definen cómo se accede a los datos.
- `src/Application/`: Casos de uso (Lógica de orquestación).
- `src/Infrastructure/Persistence/`: Implementaciones reales (Adaptadores) como `MySQLServiceRepository`.
- `src/Infrastructure/Controllers/`: Controladores MVC que gestionan las peticiones HTTP.
- `src/Infrastructure/Views/`: Plantillas PHP/HTML fragmentadas (`layout/`, `components/`, `errors/`).
- `public/`: Único punto de entrada (`index.php`) y assets estáticos.

## 🛠️ Stack Tecnológico

- **Backend:** PHP 8.2+ (Vanilla, sin frameworks).
- **Frontend:** HTML5, CSS3 "Duro" (Variables CSS, Grid, Flexbox), Vanilla JS (ES6+).
- **IA:** Integración obligatoria con OpenAI API (GPT-4/GPT-3.5) para búsqueda semántica.
- **Mapas:** Leaflet.js con proveedores de teselas premium (CartoDB).
- **BD:** MySQL 8.0 (Motor InnoDB).

## 🎨 Estándares de Código

### PHP
- **Namespaces:** Seguir estándar PSR-4 (`App\...`).
- **Clases:** PascalCase (ej. `PageController`).
- **Métodos/Variables:** camelCase (ej. `getFormattedPrice`).
- **Seguridad:** Uso estricto de **Sentencias Preparadas (PDO)** para evitar SQL Injection.
- **Tipado:** Usar Type Hinting en parámetros y retornos siempre que sea posible.

### CSS
- **Metodología:** Nombres descriptivos con guiones (ej. `.explore-card`, `.price-marker`).
- **Modularidad:** Un archivo CSS por vista compleja (`service_detail.css`, `search.css`).
- **Variables:** Usar `:root` para colores y bordes definidos en `styles.css`.

### JavaScript
- **Módulos:** Separar por responsabilidad (`lightbox.js`, `booking-ui.js`, `search-map.js`).
- **AJAX:** Uso de `Fetch API` con headers `XMLHttpRequest` para que PHP identifique la petición.
- **Globales:** Variables de entorno (`BASE_URL`, `lang`) inyectadas vía script tag en el header.

## 🔍 Reglas de Negocio Críticas

1. **Multilenguaje:** Todo contenido textual (títulos, descripciones) debe consultarse en la tabla `translations` filtrando por `lang_code`.
2. **Reservas:** Deben pasar por un flujo de doble confirmación (Formulario -> Modal Resumen -> Envío AJAX).
3. **Disponibilidad:** El calendario visual debe bloquear fechas consultando el solapamiento en la tabla `bookings`.
4. **SEO/IA:** Cada ficha de cliente debe incluir datos estructurados JSON-LD (`Schema.org`) generados dinámicamente.

## 📂 Archivos Clave de Estructura
- `public/index.php`: Front Controller.
- `autoload.php`: Autocarga manual de clases.
- `src/Config/config.php`: Detección dinámica de BASE_URL y API Keys.
- `src/Infrastructure/Controllers/Router.php`: Cerebro del sistema de URLs amigables.

---

## 📖 Lectura obligatoria al inicio de cada sesión

Leer en este orden exacto antes de actuar:

1. `docs/SESSION.md` — estado exacto de la última sesión y handoff pendiente
2. `CLAUDE.md` (este archivo) — convenciones, stack y estado actual
3. `docs/TASKS.md` → sección 🔴 En curso
4. `docs/LESSONS.md` — errores aprendidos (obligatorio para architect y coder)

## 🤖 Reglas del pipeline

Flujo de agentes: **architect → coder → security → product**. Cada agente lee
`docs/SESSION.md` y `docs/LESSONS.md` al iniciar y actualiza los docs al cerrar.
Entre agentes se pasa solo el **handoff estructurado** (ver instrucciones
globales), nunca el output completo del agente anterior.

Modelos: architect y product → `opus`; coder y security → `sonnet`.

### Regla del /clear
Antes de cualquier `/clear`, completar la checklist de cierre de sesión: código y
plan guardados en disco, `docs/TASKS.md` y `docs/SESSION.md` actualizados, handoff
redactado si aplica. Los archivos son la memoria permanente, no el chat.

## 📌 Estado actual del proyecto

- **Nuevo rumbo (2026-09-28):** producción sigue siendo el **WordPress** de
  `https://www.plan-canal-du-midi.com` (servidor `plesk-prod`, tema my-listing, PHP-FPM
  **7.4**). Esta app PHP local ya no es el sitio: es la **fuente de diseño**. Regla dura:
  **en producción no se modifica nada existente, solo se añade.** Fases: home → `/carte` →
  diseño de la ficha.
- **Convención de URLs:** toda página nueva lleva el sufijo **`-2026`** (`/accueil-2026/`,
  `/explorer-2026/`, …) para distinguirla de la actual; al publicar se quita el sufijo y se
  recupera la misma URL. En el plugin la ruta va en una constante (`CANAL_CARTE_PATH`).
- **Último desplegado (privado):** **TASK-044 — Planificateur 2026** (`/planificateur-2026/`, página 18505): chat IA
  en el idioma del visitante + vista plan con fotos + modal de demanda con Google Maps; correos solo a onavarro@ hasta
  `CANAL_PLANNER_LIVE = true`. Rama `feat/wp-planner-2026` (sin fusionar). Antes: TASK-037…042 navbar + footer 2026.
- **Último completado (06/10):** **desarrollo de las páginas 2026 terminado** — TASK-066 GEO-IA completa (T2 Sallèles, T4 vélo,
  Fonseranes), inventario de cobertura (`docs/inventario-cobertura-2026-10-06.md`: 95 % de los clics con versión 2026),
  TASK-068 revisión semanal de las webs de las fichas (enlace oculto si cae, e-mail los lunes), 301 de fichas caducadas.
  Solo queda publicar (TASK-063).
- **Antes (05/10, noche):** **TASK-066 GEO-IA** M1 (página météo « quand partir », 8 estaciones Météo-France
  2021–2025, sin tabla), M2 campings, M3 PDF, M4 location bateau, M5 étapes, T1 alternateName; **TASK-067 `/etapes/`**
  « Quel parcours faire ? » (parcours por modo/duración/salida, mapa del parcours, FAQ de Search Console). Reglas nuevas: datos
  recientes (5 años), iconos de biblioteca, FAQ visibles, ningún enlace a una carte vacía (PRD-017).
- **Antes (05/10):** **TASK-057 — Calcul de distance 2026** (`/calcul-de-distance-canal-du-midi-2026/`, privado;
  esclusas y sas en el tiempo de barco, Google Maps + trazado OSM, fichas de esclusas enlazadas). Antes ese día: **TASK-055 — plantilla de contenido 2026**: cualquier página o artículo en `/<ruta>-2026/`
  (privado; `canal_contenu_public`). Antes: TASK-054 plan PDF en URL fija `/plan-canal-du-midi.pdf`; inventario de todo el
  sitio en `docs/inventario-paginas-2026-10-05.md`. TASK-056 (IA) descartada; temas para dirección en `docs/para-direccion.md`.
- **Completado el 02/10:** **TASK-051 — PageSpeed páginas 2026** (Lighthouse móvil 81–89 estable, accesibilidad 100;
  causa del 66: stub de Sirdata en el `<head>`). ⚠️ `/accueil-2026/` está **publicada** para medir; ⚠️ SEC-001 en el tema.
- **Completado antes:** **TASK-050 ⚠️ — caché de página WP Fastest Cache en todo el sitio** (TTFB 0,5–1,2 s →
  ~0,11 s; rollback en `wp-plugin/ops/wpfc-enable.php`; seguimiento TASK-050b). Antes: **TASK-048 ⚠️ — rendimiento móvil páginas 2026** (iconos SVG en CSS, fuentes propias, CSS en línea,
  GA4 directo diferido, hero sin re-ocultar en móvil; móvil 72 → 76–81, LCP 5,7 → 4,6 s; re-medir tras publicar → TASK-049).
  Antes: **TASK-046 — FAQ alineada con Search Console**. Antes: **TASK-045 — accueil-2026 recupera « carte / plan / tracé »** (title, H1, #etapes, FAQ 6,
  JSON-LD Map; privada, 2026-10-02). Antes: **TASK-036 — carte: mapa bajo demanda en móvil** (Lighthouse móvil 66–69).
  Antes: **TASK-035 — carte: LCP sin esperar a Google Maps** (Lighthouse móvil 48 → 63,
  escritorio 83). Antes: **TASK-034 — home y carte sin CSS/JS del tema** (PageSpeed móvil home 43 → 78, carte
  sin mejora: su peso es propio → TASK-035). Antes: **TASK-033 — ficha 2026: diseño móvil, SEO y aligerado del
  tema** (móvil 87 / escritorio 98). Antes: **TASK-032 — navbar nuevo** (cabecera propia + mega-menú, estructura por intención
  y vistas GA4) en home, carte y ficha 2026, privadas. Antes: TASK-030 ficha `/fiche-2026/<slug>/`, TASK-029
  carte `/explorer-2026/` (18502), TASK-027 home `/accueil-2026/` (18500). Detalle en `docs/TASKS.md` 🟢.
- **Comandos:** `wp-plugin/remote.sh test` (tests + lint 7.4 en el servidor) ·
  `remote.sh deploy` · `remote.sh run tests/<smoke>.php` (con el plugin desactivado) ·
  `remote.sh wp <args>` (WP-CLI como el usuario del sitio) · CSS:
  `node wp-plugin/build/build-css.mjs` (nunca editar `assets/home.css` a mano).
- **Siguiente:** decidir la publicación conjunta home +
  carte + ficha (TASK-028 + TASK-029b + TASK-030b) — esto último SOLO con orden explícita; al publicar,
  regenerar y subir `llms-full.txt` (PRD-013).
- **Medición (gratis):** OpenSEO en `~/open-seo` (sin Lighthouse), Search Console/GA4, PageSpeed Insights CrUX
  (`PSI_API_KEY`) y Bing Webmaster API (`BING_WMT_KEY`) en `~/open-seo/.env`. Con caché, no decidir por el LCP de laboratorio (PRD-016).
- Recordatorio: lo visual se verifica SIEMPRE en navegador con captura (y haciendo scroll
  antes de la captura de página completa: scroll-reveal + lazy-load).
