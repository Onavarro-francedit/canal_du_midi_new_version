# Diseño — Backoffice para que los comercios editen su ficha

- **Fecha:** 2026-09-02
- **Estado:** aprobado (diseño), pendiente de plan de implementación
- **Autor:** pipeline de brainstorming (Claude)

## Objetivo

Dar a cada comercio (listing) una cuenta propia para entrar a un panel y editar el
contenido de su ficha (`/fiche/{slug}`) sin depender de que el equipo interno lo haga
manualmente. Un rol `admin` interno puede editar/gestionar cualquier ficha y crear las
cuentas de los comercios.

## Decisiones (confirmadas con el usuario)

| Decisión | Elección |
|---|---|
| Quién accede | `owner` (dueño de una ficha) **y** `admin` (equipo interno, cualquier ficha) |
| Qué edita el owner | Todo lo visible en la ficha: texto, contacto, categorías, portada + galería |
| Alta de cuentas | El admin crea la cuenta (email + password temporal) y la asocia a la ficha — sin autorregistro público |
| Fotos nuevas | Subida de archivo real al servidor (`public/uploads/listings/{id}/`), no solo pegar URL |
| Relación cuenta↔ficha | 1 owner = 1 ficha (`listings.owner_user_id`), ampliable a N:N después si hace falta |

## Contexto actual (verificado en código)

- **Cero infraestructura de auth/sesión/CSRF/uploads** en el proyecto — greenfield total
  (confirmado: sin `password_hash`, sin `session_start()`, sin tabla `users`, sin
  `$_FILES`/`move_uploaded_file` en ningún sitio).
- `listings.claimed` (tinyint) existe en la BD pero **no lo lee/escribe nada** en el
  código — es un stub pensado, aparentemente, para justo este caso. Lo reaprovechamos.
- `src/Infrastructure/Controllers/Router.php` parsea `lang/page/params` — **solo un
  segmento de params** (`/fiche/{slug}` → params=`slug`; una ruta como
  `/backoffice/edit/246` perdería el `246`). Los datos adicionales van por
  querystring (`?id=246`), como ya hace el resto del sitio (ver `search_results.php`
  con sus filtros en `$_GET`).
- `public/index.php` es el único punto de entrada: `Router` → `PageController::render()`.
  `render()` es un switch gigante (~1500 líneas) que hace `require_once` de
  `Views/layout/header.php` → `Views/{vista}.php` → `Views/layout/footer.php`, con las
  variables locales del método visibles dentro de los `require` (scope compartido).
- `SEC-014` (backlog, `docs/LESSONS.md`): los endpoints de IA no tienen CSRF ni
  rate-limit. El helper CSRF que se construye aquí queda disponible para tapar ese
  hueco después (fuera de este alcance, pero mismo mecanismo reutilizable).

## Arquitectura objetivo

```
Router → PageController::render()
              │
              ├── page === 'backoffice' ──► BackofficeController::handle($lang, $params)
              │         (nuevo, clase propia — NO se mete en el switch de 1500 líneas)
              │
              │   BackofficeController delega en:
              │     - AuthService        (login/logout, password_hash/verify, sesión)
              │     - Csrf                (helper estático: token(), check())
              │     - MySQLServiceRepository (ya existe: lectura/escritura de listings)
              │     - ListingUploader     (nuevo: valida y guarda archivos subidos)
              │
              └── resto de páginas ─────► (sin cambios)
```

### Unidades

1. **Tabla `users`** (migración SQL nueva, no existe archivo de migraciones en el
   repo — se documenta el SQL aquí y se aplica a mano, como el resto de tablas del
   proyecto):
   ```sql
   CREATE TABLE users (
       id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       email VARCHAR(190) NOT NULL UNIQUE,
       password_hash VARCHAR(255) NOT NULL,
       role ENUM('admin','owner') NOT NULL DEFAULT 'owner',
       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
   );

   CREATE TABLE login_attempts (
       id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       ip VARCHAR(45) NOT NULL,
       attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_ip_time (ip, attempted_at)
   );

   ALTER TABLE listings
       ADD COLUMN owner_user_id INT UNSIGNED NULL,
       ADD CONSTRAINT fk_listings_owner FOREIGN KEY (owner_user_id)
           REFERENCES users(id) ON DELETE SET NULL;
   ```
   `claimed` se pone a `1` cada vez que `owner_user_id` se asigna (mantenido en sync,
   no se elimina la columna). `ON DELETE SET NULL`: si se borra un `user`, la ficha
   vuelve a quedar sin dueño en vez de romper el `DELETE` por la FK.

   **Bootstrap del primer admin:** no hay UI para crear el primer `admin` (el admin
   crea owners, pero nadie crea al primer admin desde el panel — hueco real, sin esto
   no hay forma de entrar la primera vez). Se resuelve con un `INSERT` manual único,
   documentado en el plan de implementación:
   ```
   php -r "echo password_hash('cambia-esto', PASSWORD_DEFAULT), PHP_EOL;"
   INSERT INTO users (email, password_hash, role) VALUES ('admin@...', '<hash de arriba>', 'admin');
   ```

2. **`App\Infrastructure\Services\AuthService`** (nuevo,
   `src/Infrastructure/Services/AuthService.php`):
   - `attempt(string $email, string $password): bool` — busca en `users`,
     `password_verify()`, si OK llama `session_regenerate_id(true)` (evita session
     fixation) y guarda `$_SESSION['user_id']`, `$_SESSION['role']`,
     `$_SESSION['owner_listing_id']` (si `role==='owner'`, resuelto vía
     `SELECT id FROM listings WHERE owner_user_id = ?`).
   - `logout(): void` — `session_destroy()`.
   - `currentUser(): ?array` / `isAdmin(): bool` / `ownerListingId(): ?int`.
   - `resetOwnerPassword(int $listingId, string $newPassword): void` — el admin
     genera una nueva password temporal para un owner bloqueado (única vía de
     recuperación, ya que el reset por email queda fuera de alcance).
   - `session_start()` se llama una vez, al principio de `BackofficeController::handle()`,
     con `session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => APP_ENV === 'prod'])`
     antes de arrancarla (único punto del sitio que usa sesiones — el resto sigue sin estado).

3. **`App\Infrastructure\Services\Csrf`** (nuevo, helper estático simple):
   - `token(): string` — genera/reutiliza `$_SESSION['csrf_token']`.
   - `check(?string $submitted): bool` — comparación `hash_equals()`.
   - Cada `<form>` del backoffice lleva `<input type="hidden" name="csrf" value="<?= Csrf::token() ?>">`;
     cada handler POST llama `Csrf::check($_POST['csrf'] ?? null)` antes de tocar la BD.

4. **`App\Infrastructure\Services\ListingUploader`** (nuevo):
   - `store(int $listingId, array $file): string` — valida `$file['type']` contra
     whitelist (`image/jpeg`, `image/png`, `image/webp`), tamaño máx. (5MB), genera
     nombre único (`uniqid()` + extensión saneada), mueve a
     `public/uploads/listings/{id}/`, devuelve la ruta relativa a guardar en BD
     (`cover` o dentro del array JSON de `gallery`).
   - Rechaza con excepción si el MIME real (via `finfo`) no coincide con la whitelist
     (no confiar en la extensión ni en el `Content-Type` del cliente).
   - **`public/uploads/` lleva un `.htaccess` propio** (`php_flag engine off` +
     `<FilesMatch "\.(php|phtml|php\d?)$"> Require all denied </FilesMatch>`) para que,
     aunque algún día un archivo malicioso pase la validación de MIME, Apache nunca lo
     ejecute como PHP — es la capa de defensa que no depende de que la validación sea
     perfecta.

5. **`App\Infrastructure\Controllers\BackofficeController`** (nuevo):
   - `handle(string $lang, ?string $params): void` — arranca sesión, resuelve la
     "subruta" desde `$params` (`login` | `logout` | `null`=dashboard) y el rol.
   - Rutas:
     - `GET/POST /backoffice/login` — formulario + intento de login (CSRF + rate
       limit por **IP** — no por sesión: una rate-limit atada a `$_SESSION` no sirve
       de nada porque el atacante simplemente no manda la cookie y cada intento
       arranca sesión nueva. Máx. 5 intentos/15 min por IP, guardado en una tabla
       ligera `login_attempts (ip, attempted_at)` con `DELETE` de filas viejas en
       cada intento — no hace falta nada más sofisticado).
     - `/backoffice/logout` — cierra sesión, redirige a login.
     - `GET/POST /backoffice` (requiere sesión):
       - `owner` → formulario de edición de **su** ficha (`ownerListingId()`).
       - `admin` sin `?id=` → listado de todas las fichas (buscador simple por
         título) + formulario de alta de cuenta owner (email, password temporal,
         selector de ficha a asociar).
       - `admin` con `?id=123` → formulario de edición de esa ficha (mismo template
         que usa el owner, reutilizado).
   - Si no hay sesión válida → redirect a `/backoffice/login` (equivalente a un
     guard, ya que no existe capa de middleware en el proyecto).

6. **Vistas nuevas** — `src/Infrastructure/Views/backoffice/`:
   - `login.php` — formulario email/password, mensaje de error genérico ("credenciales
     incorrectas", nunca "email no existe" para no filtrar qué cuentas existen).
   - `edit_listing.php` — formulario reutilizado por owner y admin: título, descripción,
     teléfono/mobile/email/website/facebook, dirección, categorías (checkboxes sobre
     `getCategories()` ya existente), portada + galería (inputs `type="file"` +
     miniaturas de lo ya subido con opción de quitar), guardar.
   - `admin_dashboard.php` — tabla de fichas con buscador + form de alta de cuenta +
     acción "resetear password" por owner ya existente (única vía de recuperación
     de acceso, dado que no hay reset por email en este alcance).
   - Reutilizan `Views/layout/header.php`/`footer.php` con `$page = 'backoffice'`; CSS
     nuevo `public/assets/css/backoffice.css`, cargado condicionalmente en `header.php`
     igual que las demás secciones (`if ($page === 'backoffice')`).

7. **Escritura en `MySQLServiceRepository`** — añadir `updateListing(int $id, array $fields): void`
   y `setListingCategories(int $id, array $categoryIds): void` (borra+inserta en
   `listing_categories`, reutilizando `rebuildCategoryCountsCache()` ya existente tras
   el cambio para que los contadores de la home sigan correctos).

## Flujo de datos (edición de ficha)

```
Owner/Admin ── GET /backoffice ──► BackofficeController
                                        │ AuthService::currentUser() (sesión)
                                        ▼
                          MySQLServiceRepository::findById($listingId)
                                        │
                                        ▼
                          Views/backoffice/edit_listing.php (formulario prellenado)

Owner/Admin ── POST /backoffice (csrf + campos + files) ──► BackofficeController
                                        │ Csrf::check()
                                        │ validar campos (trim, longitudes, email/url válidos)
                                        │ ListingUploader::store() por cada archivo nuevo
                                        ▼
                          MySQLServiceRepository::updateListing() + setListingCategories()
                                        │
                                        ▼
                          redirect 303 a /backoffice?saved=1 (evita reenvío de formulario)
```

## Manejo de errores

- Login fallido → mensaje genérico + no revela si el email existe.
- CSRF inválido → 403 simple. Solo existen `Views/errors/404.php` y
  `service_not_found.php` hoy — se crea `Views/errors/403.php` nuevo, mismo patrón.
- Sesión ausente/expirada en cualquier ruta protegida → redirect a login (no 500).
- Subida de archivo inválida (tipo/tamaño) → el formulario se re-renderiza con los
  datos ya tecleados intactos + mensaje de error específico del campo.
- `owner` intentando `?id=` de una ficha que no es la suya → 403 (nunca se resuelve
  el `$id` de la URL para un owner; siempre se usa `ownerListingId()` de sesión).

## Testing / verificación

- Sin framework de tests en el proyecto (verificado en sesiones anteriores) → verificación
  manual en navegador, igual que el resto de features de este proyecto:
  1. Admin crea cuenta owner para un listing real → login como ese owner → solo ve/edita
     su ficha (confirmar que `?id=` de otra ficha da 403).
  2. Editar texto/contacto → guardar → recargar `/fiche/{slug}` y confirmar el cambio.
  3. Subir foto de portada y de galería → confirmar que aparecen en `/fiche/{slug}` y
     en el archivo físico dentro de `public/uploads/listings/{id}/`.
  4. Cambiar categorías → confirmar que los contadores de "Destinations phares" en home
     y el filtro de `/search` reflejan el cambio (cache de categorías recalculada).
  5. CSRF: enviar el form sin token (via curl) → debe rechazar.
  6. Intentar login con password incorrecta 6 veces seguidas desde la misma IP → debe
     bloquear temporalmente (y confirmar que cambiar de sesión/borrar cookies NO lo
     resetea, ya que el límite es por IP).
  7. Subir un archivo `.php` renombrado a `.jpg` → debe rechazarlo por MIME real; y
     aunque se guardara, pedirlo directo por URL bajo `/uploads/` no debe ejecutarse.
  8. Admin resetea la password de un owner → el owner puede entrar con la nueva.

## Fuera de alcance (YAGNI, anotado para después)

- Recuperación de contraseña por email (no hay envío de mail transaccional propio del
  backoffice todavía; el proyecto ya tiene PHPMailer configurado para otra cosa, se
  puede reutilizar cuando se pida).
- Roles intermedios (editor, moderador).
- Un owner con varias fichas (N:N).
- Historial de cambios / auditoría de ediciones.
- Notificaciones (email al admin cuando un owner edita, etc.).
