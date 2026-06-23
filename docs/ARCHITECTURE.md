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
