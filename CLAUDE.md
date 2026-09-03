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

- **Último completado:** **TASK-026 — modal "Photos" del backoffice: borrar, reordenar
  y validar ✅** (pipeline completo + re-verificación en navegador, 2026-09-03). Borrado
  de portada/galería vía AJAX con `confirm()` y reasignación automática de portada,
  reordenado por drag & drop nativo persistido como `gallery_order[]`, validación cliente
  (5 Mo + MIME) y estado "Envoi en cours…" con spinner. Verificado con Playwright sobre la
  fiche 226 (owner throwaway, datos restaurados byte a byte): borrado que persiste tras F5,
  orden que llega al carrusel público, rechazo de archivos inválidos sin ninguna petición de
  red, 0 errores de consola, textos en francés. Se abrieron 3 hallazgos de product; **BUG-018**
  (mensaje de error invisible — `showModalError()` + `scrollIntoView({block:'center'})`, ojo:
  `'nearest'` NO basta bajo el `<h2>` collant) y **BUG-019** (hero y picker sucios tras borrar
  la última foto) están corregidos y re-verificados. Queda **BUG-020** en 🟡 (el drag & drop es
  indescubrible y no funciona en táctil — decisión de UX).
- **Antes:** TASK-001 (escape de salida `ENT_QUOTES,'UTF-8'` en 6 vistas:
  service_detail, poi_detail, vacation_pdf, review_item, header, booking_summary_modal)
  + TASK-002 (hardening de prompts OpenAI: `sanitizeUserPrompt`, delimitadores,
  cláusula anti-override). Pipeline completo + product ⚠️ verificado en navegador
  (Playwright, PRD-002). Incluye SEC-010 (iframe de vídeo de BD con allowlist
  youtube/vimeo + sandbox) y SEC-011 (`$service->id` casteado a `(int)` en `data-sid` y
  `service_id`). Ficha de servicio renderiza con mapa Leaflet (1 marcador OK pese al
  cast float), acentos franceses intactos, 0 errores de consola; vacation-pdf con ref
  inválida → 404 "introuvable". Auditoría: `grep htmlspecialchars | grep -v ENT_QUOTES`
  → 0 en los 6 archivos. Ver 🟢 en TASKS.md.
- **Antes (resumen):** clusters home BUG-001/002/003 (tour-cards reales + sección plan
  Calaméo + form e-mail) y "buscador del hero" (selects reales, PRD-004/PRD-005
  resueltos: título legible + filtro náutico 136→17). SEC-007/008 y la tanda de
  backlog (PRD-006, TASK-003/004, BUG-004, SEC-004/009/002) cerradas. Detalle en
  `docs/TASKS.md` 🟢.
- **Seguimientos abiertos (🟡):** BUG-020/PRD-012 (el reordenado del modal Photos es
  indescubrible y el drag nativo no funciona en táctil); PRD-007/BUG-014 (el mapa de la ficha de POI nunca
  renderiza — `footer.php` no carga Leaflet para `$page==='poi'`; bug PRE-EXISTENTE,
  no regresión de TASK-001); deuda de fondo — SEC-001 (credenciales BD en
  `Database.php`), TASK-005/007 (stats/contacto/imágenes reales), TASK-016b (backfill
  `commune`), BUG-004 ("Lire la vidéo" aplazado, ya eliminado el botón). En
  `docs/TASKS.md`.
- **Siguiente candidato:** corregir PRD-007/BUG-014 (Leaflet en ficha de POI), o
  Incremento 3 visual — TASK-012 "Les étapes du canal" (Toulouse → … → Étang de Thau).
- Recordatorio: el motion/render Y los filtros/resultados se verifican SIEMPRE en
  navegador con captura, muestreo de píxeles o conteo+muestra real
  (PRD-002/PRD-003/PRD-005/PRD-007); el computed style/“filtra algo”/“el div existe”
  no basta. Nada de pins/scroll-jacking (TASK-008 revertida).