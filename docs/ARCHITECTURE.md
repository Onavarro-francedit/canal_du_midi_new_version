# ARCHITECTURE.md — Canal du Midi

Decisiones técnicas del proyecto. No leer en cada sesión; consultar al diseñar
cambios que afecten estructura, datos o patrones transversales.

## Visión general

Sitio turístico del Canal du Midi en PHP puro con **arquitectura hexagonal +
MVC**. Doble público: turistas (búsqueda, POIs, reservas, planificador IA) y
prestadores (fichas de servicio, imágenes de cliente, reservas).

Flujo de una petición:

```
public/index.php (front controller)
  → autoload.php (PSR-4 manual, prefijo App\ → src/)
  → src/config/config.php (carga .env, define BASE_URL/APP_ENV/OpenAI/Mail)
  → Infrastructure/Controllers/Router.php (URLs amigables)
  → PageController (resuelve la página)
  → Domain (Models + interfaces Repository/Service)
  → Infrastructure/Persistence (MySQL*Repository, PDO)
  → Infrastructure/Services (OpenAI, SmartAI, VacationPlanner, Mail)
  → Infrastructure/Views (PHP planas: layout/, components/, modals/, Emails/)
```

## Decisiones de diseño

- **Dirección de arte de la home (TASK-009/010):** se añaden tokens de arte en
  `:root` sin renombrar los existentes — `--terracotta #C16B43`, `--sand-warm
  #F4EFE7`, `--ink #0E1424` aportan la calidez Occitanie (ville rose) frente al
  frío SaaS; `--water #2BB6C4` es el teal del canal; `--violet #544DBE` queda
  reservado **exclusivamente a CTAs**. El hero usa Playfair Display (peso recto
  para el h1, con una palabra en `<em>` itálico).
- **Motion WOW sin scroll-jacking:** el "efecto WOW" se logra **solo por carga
  del hero** (entrada escalonada disparada por `html.hero-ready` tras
  `window.load`, Ken Burns lento de la foto) **+ parallax sutil de la imagen del
  hero**, nunca con pins ni scroll-jacking ni scrub (lección PRD-002 de la
  TASK-008 revertida). La decoración es visible por defecto (PRD-001): la entrada
  solo se "esconde para animar" bajo `.hero-ready`, y la *ligne d'eau* se dibuja
  con `stroke-dashoffset` (estado base = dibujada). `prefers-reduced-motion`
  anula todo. El parallax escribe la propiedad individual `translate` mientras
  Ken Burns usa `scale`, para que compongan sin pisarse.
- **Hexagonal:** el dominio (`src/Domain`) no depende de infraestructura. Los
  repositorios se definen como interfaces en `Domain/Repositories` y se
  implementan en `Infrastructure/Persistence`. Mantener esa dirección de
  dependencia.
- **PHP vanilla sin framework:** routing propio (`Router.php`) para URLs
  amigables. No introducir frameworks sin discutirlo aquí.
- **Config por `.env`:** `config.php` carga `.env` a `$_ENV`/`putenv` y expone
  constantes. Todo secreto (DB, OpenAI, SMTP) vive en `.env`.
- **IA OpenAI:** `OpenAIService` (cliente base), `SmartAIService` (búsqueda
  semántica / recomendaciones), `VacationPlannerService` (planificador). El
  input del usuario llega a prompts: tratar siempre como no confiable.
- **Email:** PHPMailer vía `MailService`; plantillas en `Views/Emails/EmailTemplates.php`.
- **Frontend:** CSS/JS vanilla modular en `public/assets/`; variables de entorno
  (`BASE_URL`, `lang`) inyectadas vía script tag en el header.

## Estructura de datos principal

- MySQL `canal_du_midi` (InnoDB), acceso vía PDO singleton `App\Config\Database`.
- Entidades de dominio: `Service`, `POI`, `Review`, reservas (`bookings`).
- Multilenguaje: contenido textual en tabla `translations` filtrando por
  `lang_code`.
- Reservas: solapamiento de fechas consultando `bookings` para bloquear el
  calendario.

## Patrones que se repiten

- Consultas con **PDO preparado** siempre (placeholders, nunca interpolación).
- Salida HTML escapada con `htmlspecialchars()` en las vistas.
- Datos estructurados JSON-LD por ficha vía `Views/helpers/SchemaGenerator.php`.
- AJAX con `Fetch API` + header `X-Requested-With: XMLHttpRequest`.

## Lo que NO se debe hacer

- No interpolar variables en SQL (riesgo de inyección — usar prepared statements).
- No imprimir variables en HTML sin `htmlspecialchars()` (XSS).
- No hardcodear credenciales ni claves; usar `.env` / `config.php`.
  ⚠️ Deuda conocida: `src/config/Database.php` hardcodea `root` / password vacío
  en vez de usar `DB_*` del `.env`. Unificar cuando se toque la capa de datos.
- No exponer `OPENAI_API_KEY` ni secretos en JS de cliente.
- No concatenar input de usuario en cabeceras de correo (CRLF injection).
- No usar `basename()`-less paths al servir/leer `public/clients_images/`.
- No dejar `display_errors`/mensajes de excepción visibles en `APP_ENV = prod`.

---

## Sitio 2026 en el WordPress de producción (plugin `wp-plugin/canal-home`) — estado 2026-10-05

**Principio:** no se modifica nada existente del WordPress; el plugin añade plantillas que pintan el diseño 2026 encima
de los datos de siempre (páginas, artículos, fichas, términos).

**Interruptor de publicación** (`includes/live.php`, plan en `docs/plan-publicacion-2026.md`):
- Opción `canal_2026_live` = '1' → constante `CANAL_2026_LIVE` y rutas definitivas (`/`, `/explorer/`, `/fiche/`,
  `/etape/`, sufijo de contenido `''`). Apagado (hoy): rutas privadas `-2026`.
- Vista previa solo para administradores: `/?canal_2026_preview=1` (cookie) / `=0`. No toca reglas de URL globales.
- Las rutas se definen en `plugins_loaded` (no son `const`): nunca usarlas al cargar un archivo.

**Qué pinta cada plantilla** (detección en privado → publicado):

| Plantilla | Privado | Publicado |
|---|---|---|
| `template-home.php` | página 18500 `/accueil-2026/` | `is_front_page()` |
| `template-carte.php` | página 18502 | `is_page('explorer')`; también `/categorie/`, `/region/`, `/mot-cle/` (query vars `explore_*` → `canal_carte_term()`) |
| `template-fiche.php` | regla `/fiche-2026/<slug>/` | `is_singular('job_listing')` |
| `template-contenu.php` | `parse_request` `/<ruta>-2026/` | `is_singular()` elegible (`canal_contenu_post_eligible`) |
| `template-calcul.php`, `template-archive.php`, `template-etape(s).php` | rutas `-2026` | página del calcul, `is_category()`, `/etape/` |
| `template-planner.php` | página 18505 | `/planificateur/` → página por su plantilla |
| `template-404.php`, « Mon compte » (`compte.css` sobre el tema) | — | `is_404()`, `is_account_page()` |

**Transversal:**
- `includes/links-2026.php`: reescribe los `href` del `<body>` hacia la versión 2026.
- `includes/redirects-2026.php`: 301, solo en modo publicado.
- `includes/events-2026.php`: eventos de GA4.
- `includes/head-fix.php`: arregla el `<head>` del tema, mueve el consentimiento al body y aplica WebP.
- Caché de la carte por modo (`canal_carte_cache_key()`).

**Reglas aprendidas:**
- Elementor reasigna `template_include` → la nuestra va a prioridad 99.
- MyListing fija el `<title>` de las páginas explore a prioridad 10000 → la nuestra va a 10001.
- `cat_slugs` de la carte incluye las categorías madre.
- Las páginas con plugins (CF7 + PayPal, TablePress) conservan sus scripts (`CANAL_CONTENU_PLUGIN_SLUGS`).

**Lo que NO se hace:**
- generar o reescribir el contenido de los artículos (los publican los clientes);
- tocar `.htaccess` o los ajustes de WP sin orden expresa;
- publicar sin la orden explícita del usuario.
