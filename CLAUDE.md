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

- **Último completado:** Cluster "Buscador del hero" (BUG-007/TASK-015 +
  BUG-008/TASK-016 + BUG-009 + TASK-017), pipeline completo + product ⚠️ verificado
  en navegador. `<select>` Type (12 tipos reales, sin "boat" fantasma) y Destination
  (9 etapas) poblados desde BD; título SEO condicional sin comillas vacías; feedback
  de carga en "Rechercher"; SEC-006 (ENT_QUOTES) cerrada. Ver 🟢 en TASKS.md.
- **Seguimientos abiertos (🟡):** PRD-004/BUG-010 (el título de search imprime el
  slug crudo del tipo, p.ej. "location-de-velo", en vez del nombre legible) y
  PRD-005/BUG-011 (Type="Le Canal en Bateau"/`nautique` devuelve 136/253, mayoría
  écluses+ports, no barcos). Ambos en `docs/TASKS.md`.
- **Siguiente candidato:** corregir PRD-004 + PRD-005, o Incremento 3 visual —
  TASK-012 "Les étapes du canal" (Toulouse → … → Étang de Thau).
- Recordatorio: el motion/render Y los filtros/resultados se verifican SIEMPRE en
  navegador con captura, muestreo de píxeles o conteo+muestra real
  (PRD-002/PRD-003/PRD-005); el computed style/“filtra algo” no basta. Nada de
  pins/scroll-jacking (TASK-008 revertida).