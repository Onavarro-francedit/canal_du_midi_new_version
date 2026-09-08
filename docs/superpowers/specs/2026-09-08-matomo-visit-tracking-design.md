# Diseño — Tracking de visitas con Matomo autoalojado

## Contexto
El sitio (francés, RGPD aplica) no tiene ningún sistema de estadísticas de
visitas. Se evaluaron 3 opciones:
- **Google Analytics (GA4)**: descartado — comparte datos con Google fuera de
  la UE, la CNIL no lo reconoce como exento de consentimiento, obligaría a
  mostrar un banner que el usuario no quiere.
- **Solución propia (tabla MySQL + PHP)**: descartada — el usuario no quiere
  mantener él mismo el filtrado de bots.
- **Matomo autoalojado** (elegida): analítica completa, filtrado de bots de
  fábrica, y configurable en modo exento de banner según los criterios de
  exención de la CNIL (justo la ventaja que se buscaba frente a GA4).

## Alcance
Instalar y configurar Matomo para medir visitas del sitio público. No incluye
widgets embebidos en el dashboard admin propio (`/fr/backoffice`) ni tracking
de las páginas del backoffice — se usa la interfaz propia de Matomo para ver
las estadísticas.

## Instalación
- Matomo (última versión estable) se descarga en `public/matomo/`, como
  aplicación de terceros vendorizada — fuera del autoload PSR-4 del proyecto
  (`App\...`), con su propio `.htaccess` y punto de entrada
  (`public/matomo/index.php`).
- Nueva base de datos MySQL **separada**: `canal_du_midi_matomo` (mismo host
  y usuario que la BD principal en XAMPP local). Aislada de las tablas de
  negocio (`fiches`, `bookings`, `users`, `translations`, etc.).
- El asistente de instalación de Matomo crea el primer "site" (Canal du Midi)
  y genera el `idSite` a usar en el snippet de tracking.

## Configuración de privacidad (exención de banner, criterios CNIL)
En Matomo → Administración → Privacidad, activar:
- **Anonimizar IP**: últimos 2 bytes (o más) enmascarados antes de guardar.
- **Respetar DoNotTrack** del navegador.
- **Desactivar** el uso de cookies de seguimiento persistente entre sitios /
  fingerprinting cross-site — usar solo medición de sesión.
- **Retención de datos en bruto**: máximo 13 meses (límite CNIL para medición
  de audiencia), purga automática configurada.
- No se activan integraciones que compartan datos con terceros (Matomo
  autoalojado no lo hace por defecto).

Con esta configuración el sitio no necesita mostrar un banner de
consentimiento para esta medición, según la lista de exención de la CNIL para
herramientas de medición de audiencia.

## Integración con el sitio
- **Snippet de tracking**: el código JS estándar de Matomo (`matomo.js` +
  `matomo.php` como endpoint de tracking) se inserta en
  `src/Infrastructure/Views/layout/footer.php`, siguiendo el mismo patrón
  condicional que ya usan los bloques de scripts por página (`$page ===
  'search'`, `$page === 'home'`, etc. — ver líneas finales de ese archivo):

  ```php
  <?php if (isset($page) && $page !== 'backoffice'): ?>
      <!-- Matomo -->
      <script>
          var _paq = window._paq = window._paq || [];
          _paq.push(['trackPageView']);
          _paq.push(['enableLinkTracking']);
          (function() {
              var u = "<?= MATOMO_URL ?>";
              _paq.push(['setTrackerUrl', u + 'matomo.php']);
              _paq.push(['setSiteId', <?= (int) MATOMO_SITE_ID ?>]);
              var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
              g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
          })();
      </script>
  <?php endif; ?>
  ```

  Condición `$page !== 'backoffice'` en vez de una lista de páginas incluidas
  — mismo criterio de "todo el público, nada del admin" acordado, y evita
  tener que actualizar esta condición cada vez que se añada una página nueva.

- **Nuevas constantes** en `src/Config/config.php`, siguiendo el patrón ya
  usado para `GOOGLE_MAPS_API_KEY` (env var con fallback):

  ```php
  define('MATOMO_URL', BASE_URL . 'public/matomo/');
  define('MATOMO_SITE_ID', (int) ($_ENV['MATOMO_SITE_ID'] ?? 1));
  ```

  `MATOMO_SITE_ID` se define en `.env` tras crear el "site" en el asistente
  de instalación de Matomo.

## Seguridad
- En local (XAMPP) no se restringe el acceso a `/matomo/` — Matomo tiene su
  propio login independiente del backoffice del proyecto.
- Nota para producción (fuera de alcance de esta implementación, se deja
  documentado): restringir `/matomo/` por IP o autenticación adicional a
  nivel de servidor (`.htaccess`), y mantener Matomo actualizado como
  cualquier dependencia externa con superficie de ataque propia.

## Fuera de alcance
- Widgets o stat cards de Matomo embebidos en `/fr/backoffice`.
- Tracking de páginas del backoffice (login, dashboard, edición de fichas).
- Migración de datos históricos (no existen).
- Configuración de producción (subdominio dedicado, hardening de acceso) —
  se instala en subcarpeta, solo para el entorno local por ahora.

## Archivos afectados
- `public/matomo/` — nueva instalación vendorizada (no se versiona el código
  de Matomo en sí; sí su presencia y configuración local).
- `src/Config/config.php` — nuevas constantes `MATOMO_URL`, `MATOMO_SITE_ID`.
- `src/Infrastructure/Views/layout/footer.php` — snippet de tracking
  condicional.
- `.env` / `.env.example` — `MATOMO_SITE_ID`.
