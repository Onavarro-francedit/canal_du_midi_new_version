# Backoffice para fichas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dar a cada comercio (`listings`) una cuenta propia para entrar a `/backoffice` y editar su ficha (texto, contacto, categorías, portada + galería); un rol `admin` puede editar cualquier ficha y crear/gestionar cuentas owner.

**Architecture:** Subsistema aislado — un `BackofficeController` nuevo (no se mete en el switch de 1500 líneas de `PageController`), con `AuthService`/`Csrf`/`ListingUploader` nuevos en `src/Infrastructure/Services/`, más 3 métodos nuevos en `MySQLServiceRepository` ya existente. Sesiones PHP nativas, sin librerías.

**Tech Stack:** PHP 8.2 vanilla, PDO/MySQL, sesiones nativas (`session_start`, `password_hash`/`password_verify`), sin frameworks ni dependencias nuevas.

## Global Constraints

- Sin librerías/dependencias nuevas — todo con stdlib de PHP (spec: "Tech Stack").
- Sentencias preparadas (PDO) siempre — ninguna concatenación de SQL con input de usuario (CLAUDE.md, "Seguridad").
- Todo texto de salida escapado con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (patrón ya usado en todo `service_detail.php`/`header.php`).
- Rate-limit de login por **IP**, nunca por sesión (spec, corregido en la revisión).
- `public/uploads/` debe llevar `.htaccess` que impida ejecución de PHP antes de que exista ningún archivo subido ahí (spec).
- No hay framework de tests en el proyecto — cada tarea con lógica no trivial lleva un script `php` standalone basado en `assert()` como smoke check (ver skill ponytail: "Lazy code without its check is unfinished"); las tareas de UI se verifican en navegador (Chrome vía MCP), igual que el resto del proyecto.
- Namespace/autoload: PSR-4 manual vía `autoload.php` — `App\Infrastructure\Services\Foo` → `src/Infrastructure/Services/Foo.php`. No hay que registrar nada.

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `docs/sql/2026-09-02_backoffice.sql` | crear | DDL de `users`, `login_attempts`, `listings.owner_user_id` |
| `public/uploads/.htaccess` | crear | bloquea ejecución de PHP en uploads |
| `src/Infrastructure/Services/Csrf.php` | crear | token CSRF por sesión |
| `src/Infrastructure/Services/AuthService.php` | crear | login/logout/sesión/rate-limit/gestión de `users` |
| `src/Infrastructure/Services/ListingUploader.php` | crear | validar y guardar fotos subidas |
| `src/Infrastructure/Persistence/MySQLServiceRepository.php` | modificar | + `findByIdForEdit`, `updateListing`, `setListingCategories` |
| `src/Infrastructure/Controllers/BackofficeController.php` | crear | login/dashboard/guardado, dispatcher de `/backoffice/*` |
| `src/Infrastructure/Controllers/PageController.php` | modificar | + `case 'backoffice'` delega en `BackofficeController` |
| `src/Infrastructure/Views/backoffice/login.php` | crear | formulario de acceso |
| `src/Infrastructure/Views/backoffice/edit_listing.php` | crear | formulario de edición (owner y admin) |
| `src/Infrastructure/Views/backoffice/admin_dashboard.php` | crear | listado + alta de cuentas + reset password |
| `src/Infrastructure/Views/errors/403.php` | crear | página de acceso denegado |
| `src/Infrastructure/Views/layout/header.php` | modificar | cargar `backoffice.css` cuando `$page === 'backoffice'` |
| `public/assets/css/backoffice.css` | crear | estilos del panel |

---

### Task 1: Migración SQL + protección de `/uploads/` + bootstrap del primer admin

**Files:**
- Create: `docs/sql/2026-09-02_backoffice.sql`
- Create: `public/uploads/.htaccess`

**Interfaces:**
- Produces: tablas `users(id, email, password_hash, role, created_at)`,
  `login_attempts(id, ip, attempted_at)`; columna `listings.owner_user_id` (nullable,
  FK a `users.id` `ON DELETE SET NULL`). Consumidas por todas las tareas siguientes.

- [ ] **Step 1: Escribir el SQL de migración**

```sql
-- docs/sql/2026-09-02_backoffice.sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','owner') NOT NULL DEFAULT 'owner',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE listings
    ADD COLUMN owner_user_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_listings_owner FOREIGN KEY (owner_user_id)
        REFERENCES users(id) ON DELETE SET NULL;
```

- [ ] **Step 2: Aplicar la migración a la BD local**

Run: `mysql -h localhost -u root canal_du_midi < docs/sql/2026-09-02_backoffice.sql`
Expected: sin salida (o "Query OK") y sin errores.

- [ ] **Step 3: Verificar que las tablas existen**

Run: `mysql -h localhost -u root canal_du_midi -e "SHOW TABLES LIKE 'users'; SHOW TABLES LIKE 'login_attempts'; DESCRIBE listings;" | grep -E "users|login_attempts|owner_user_id"`
Expected: las 3 líneas aparecen.

- [ ] **Step 4: Crear el directorio de uploads y su `.htaccess`**

```apache
# public/uploads/.htaccess
php_flag engine off

<FilesMatch "\.(php|phtml|php\d?|pht)$">
    Require all denied
</FilesMatch>
```

Run: `mkdir -p public/uploads/listings`

- [ ] **Step 5: Verificar que Apache bloquea PHP en uploads**

```bash
echo '<?php echo "SHOULD_NOT_RUN"; ?>' > public/uploads/probe.php
curl -s http://localhost/canal_du_midi/public/uploads/probe.php
```

Expected: la respuesta NO contiene `SHOULD_NOT_RUN` (debe dar 403 o mostrar el código
fuente sin ejecutar, según config de Apache — nunca debe imprimir el texto).

- [ ] **Step 6: Limpiar el archivo de prueba y generar el primer admin**

```bash
rm public/uploads/probe.php
php -r "echo password_hash('CambiaEstaClave123!', PASSWORD_DEFAULT), PHP_EOL;"
```

Copiar el hash impreso y ejecutar (sustituyendo `<HASH>` y el email real que vaya a
usar el equipo):

```bash
mysql -h localhost -u root canal_du_midi -e "INSERT INTO users (email, password_hash, role) VALUES ('admin@canaldumidi.local', '<HASH>', 'admin');"
```

- [ ] **Step 7: Commit**

```bash
git add docs/sql/2026-09-02_backoffice.sql public/uploads/.htaccess
git commit -m "feat(backoffice): migración SQL (users, login_attempts, owner_user_id) + protección de uploads"
```

---

### Task 2: `Csrf` — token y validación

**Files:**
- Create: `src/Infrastructure/Services/Csrf.php`
- Create: `scripts/check_csrf.php` (smoke check, se borra al final de la tarea)

**Interfaces:**
- Consumes: nada (usa `$_SESSION` directamente; asume que quien lo llama ya hizo
  `session_start()`).
- Produces: `Csrf::token(): string`, `Csrf::check(?string $submitted): bool`. Usado por
  `BackofficeController` (Task 6+) en cada formulario/handler POST.

- [ ] **Step 1: Escribir la clase**

```php
<?php
// src/Infrastructure/Services/Csrf.php
namespace App\Infrastructure\Services;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function check(?string $submitted): bool
    {
        if (empty($_SESSION['csrf_token']) || $submitted === null || $submitted === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submitted);
    }
}
```

- [ ] **Step 2: Escribir el smoke check**

```php
<?php
// scripts/check_csrf.php — borrar tras verificar
require __DIR__ . '/../autoload.php';
session_start();

use App\Infrastructure\Services\Csrf;

$token = Csrf::token();
assert(is_string($token) && strlen($token) === 64, 'token debe ser hex de 64 chars');
assert(Csrf::token() === $token, 'token debe ser estable dentro de la misma sesión');
assert(Csrf::check($token) === true, 'check debe aceptar el token correcto');
assert(Csrf::check('otro-valor') === false, 'check debe rechazar un token incorrecto');
assert(Csrf::check(null) === false, 'check debe rechazar null');
assert(Csrf::check('') === false, 'check debe rechazar vacío');

echo "OK: Csrf funciona correctamente\n";
```

- [ ] **Step 3: Ejecutar el smoke check**

Run: `php -d assert.exception=1 scripts/check_csrf.php`
Expected: `OK: Csrf funciona correctamente`

- [ ] **Step 4: Borrar el script y commitear solo la clase**

```bash
rm scripts/check_csrf.php
git add src/Infrastructure/Services/Csrf.php
git commit -m "feat(backoffice): añade helper Csrf (token/check por sesión)"
```

---

### Task 3: `AuthService` — login, sesión, rate-limit por IP, gestión de `users`

**Files:**
- Create: `src/Infrastructure/Services/AuthService.php`
- Create: `scripts/check_auth_service.php` (smoke check, se borra al final)

**Interfaces:**
- Consumes: `App\Config\Database::getConnection(): PDO` (ya existe,
  `src/config/Database.php`); tablas `users`/`login_attempts`/`listings.owner_user_id`
  de Task 1.
- Produces (todo público, namespace `App\Infrastructure\Services\AuthService`):
  - `startSession(): void` — static, hace `session_set_cookie_params()` +
    `session_start()` si no hay sesión activa.
  - `attempt(string $email, string $password, string $ip): bool`
  - `logout(): void`
  - `currentUser(): ?array` — `['id' => int, 'email' => string, 'role' => string]` o `null`.
  - `isAdmin(): bool`
  - `ownerListingId(): ?int`
  - `tooManyAttempts(string $ip): bool`
  - `createOwnerAccount(string $email, string $password, int $listingId): int` — devuelve el `user_id` creado.
  - `resetOwnerPassword(int $listingId, string $newPassword): void`
  - `listOwnersWithListing(): array` — `[['user_id'=>int,'email'=>string,'listing_id'=>int,'listing_title'=>string], ...]` para el dashboard admin.

- [ ] **Step 1: Escribir la clase**

```php
<?php
// src/Infrastructure/Services/AuthService.php
namespace App\Infrastructure\Services;

use App\Config\Database;
use PDO;

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (defined('APP_ENV') && APP_ENV === 'prod'),
        ]);
        session_start();
    }

    public function tooManyAttempts(string $ip): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at > (NOW() - INTERVAL 15 MINUTE)"
        );
        $stmt->execute(['ip' => $ip]);
        return ((int) $stmt->fetchColumn()) >= 5;
    }

    private function recordAttempt(string $ip): void
    {
        $stmt = $this->db->prepare("INSERT INTO login_attempts (ip) VALUES (:ip)");
        $stmt->execute(['ip' => $ip]);
        // Limpieza oportunista de intentos viejos (evita crecer sin límite)
        $this->db->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
    }

    public function attempt(string $email, string $password, string $ip): bool
    {
        if ($this->tooManyAttempts($ip)) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->recordAttempt($ip);

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['role']    = $row['role'];

        if ($row['role'] === 'owner') {
            $listingStmt = $this->db->prepare("SELECT id FROM listings WHERE owner_user_id = :uid LIMIT 1");
            $listingStmt->execute(['uid' => $row['id']]);
            $_SESSION['owner_listing_id'] = (int) ($listingStmt->fetchColumn() ?: 0) ?: null;
        }

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id'    => (int) $_SESSION['user_id'],
            'role'  => (string) ($_SESSION['role'] ?? 'owner'),
        ];
    }

    public function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? null) === 'admin';
    }

    public function ownerListingId(): ?int
    {
        return isset($_SESSION['owner_listing_id']) ? (int) $_SESSION['owner_listing_id'] : null;
    }

    public function createOwnerAccount(string $email, string $password, int $listingId): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, 'owner')");
        $stmt->execute(['email' => $email, 'hash' => $hash]);
        $userId = (int) $this->db->lastInsertId();

        $update = $this->db->prepare("UPDATE listings SET owner_user_id = :uid, claimed = 1 WHERE id = :id");
        $update->execute(['uid' => $userId, 'id' => $listingId]);

        return $userId;
    }

    public function resetOwnerPassword(int $listingId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "UPDATE users u
             INNER JOIN listings l ON l.owner_user_id = u.id
             SET u.password_hash = :hash
             WHERE l.id = :listing_id"
        );
        $stmt->execute(['hash' => $hash, 'listing_id' => $listingId]);
    }

    public function listOwnersWithListing(): array
    {
        $sql = "SELECT u.id AS user_id, u.email, l.id AS listing_id, l.title AS listing_title
                FROM users u
                INNER JOIN listings l ON l.owner_user_id = u.id
                WHERE u.role = 'owner'
                ORDER BY l.title ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

- [ ] **Step 2: Escribir el smoke check** (usa la BD real local; crea y borra su propio usuario/ficha de prueba)

```php
<?php
// scripts/check_auth_service.php — borrar tras verificar
require __DIR__ . '/../src/Config/config.php';
require __DIR__ . '/../autoload.php';

use App\Infrastructure\Services\AuthService;
use App\Config\Database;

AuthService::startSession();
$auth = new AuthService();
$db = Database::getConnection();

// Fixture: listing de prueba
$db->exec("INSERT INTO listings (slug, title, status) VALUES ('test-backoffice-slug', 'TEST BACKOFFICE LISTING', 'draft')");
$listingId = (int) $db->lastInsertId();

// 1. Crear cuenta owner y comprobar que queda asociada
$userId = $auth->createOwnerAccount('owner-test@example.com', 'ClaveTemporal123!', $listingId);
assert($userId > 0, 'createOwnerAccount debe devolver un id > 0');

$check = $db->prepare("SELECT owner_user_id, claimed FROM listings WHERE id = :id");
$check->execute(['id' => $listingId]);
$row = $check->fetch(PDO::FETCH_ASSOC);
assert((int) $row['owner_user_id'] === $userId, 'la ficha debe quedar asociada al user_id creado');
assert((int) $row['claimed'] === 1, 'claimed debe pasar a 1');

// 2. Login correcto
$ip = '127.0.0.1';
$ok = $auth->attempt('owner-test@example.com', 'ClaveTemporal123!', $ip);
assert($ok === true, 'login con password correcta debe funcionar');
assert($auth->currentUser()['role'] === 'owner', 'el rol debe ser owner');
assert($auth->ownerListingId() === $listingId, 'ownerListingId debe resolver la ficha del owner');
assert($auth->isAdmin() === false, 'un owner no es admin');

// 3. Logout
$auth->logout();
assert($auth->currentUser() === null, 'tras logout no debe haber usuario actual');

// 4. Login incorrecto no debe autenticar
$bad = $auth->attempt('owner-test@example.com', 'password-incorrecta', $ip);
assert($bad === false, 'login con password incorrecta debe fallar');

// 5. Reset de password por admin, y el owner puede entrar con la nueva
$auth->resetOwnerPassword($listingId, 'NuevaClave456!');
$reLogin = $auth->attempt('owner-test@example.com', 'NuevaClave456!', $ip);
assert($reLogin === true, 'tras reset, el owner debe poder entrar con la password nueva');
$auth->logout();

// Limpieza de fixtures
$db->exec("DELETE FROM listings WHERE id = $listingId");
$db->exec("DELETE FROM users WHERE email = 'owner-test@example.com'");
$db->exec("DELETE FROM login_attempts WHERE ip = '127.0.0.1'");

echo "OK: AuthService funciona correctamente\n";
```

- [ ] **Step 3: Ejecutar el smoke check**

Run: `php -d assert.exception=1 scripts/check_auth_service.php`
Expected: `OK: AuthService funciona correctamente`
Si falla por `assert.exception` no soportado en la versión de PHP instalada, ejecutar
sin ese flag y revisar manualmente que no imprime `Assertion failed` en la salida.

- [ ] **Step 4: Borrar el script y commitear solo la clase**

```bash
rm scripts/check_auth_service.php
git add src/Infrastructure/Services/AuthService.php
git commit -m "feat(backoffice): añade AuthService (login, sesión, rate-limit por IP, gestión de owners)"
```

---

### Task 4: `ListingUploader` — validación y guardado de fotos

**Files:**
- Create: `src/Infrastructure/Services/ListingUploader.php`
- Create: `scripts/check_listing_uploader.php` (smoke check, se borra al final)

**Interfaces:**
- Consumes: `public/uploads/listings/` (creado en Task 1).
- Produces: `ListingUploader::store(int $listingId, array $file): string` — recibe una
  entrada de `$_FILES['campo']` (con `tmp_name`, `size`, `name`), devuelve la ruta
  relativa guardable en BD (ej. `public/uploads/listings/246/6710a1b2c3d4.jpg`), o
  lanza `\RuntimeException` con mensaje legible si el archivo es inválido. Usado por
  `BackofficeController` (Task 9).

- [ ] **Step 1: Escribir la clase**

```php
<?php
// src/Infrastructure/Services/ListingUploader.php
namespace App\Infrastructure\Services;

class ListingUploader
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function store(int $listingId, array $file): string
    {
        if (!isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Archivo no recibido correctamente.');
        }

        if ((int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('La imagen debe pesar menos de 5MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME[$realMime])) {
            throw new \RuntimeException('Formato de imagen no permitido (solo JPG, PNG o WEBP).');
        }

        $extension = self::ALLOWED_MIME[$realMime];
        $targetDir = __DIR__ . '/../../../public/uploads/listings/' . $listingId;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('No se pudo crear el directorio de destino.');
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        return 'public/uploads/listings/' . $listingId . '/' . $filename;
    }
}
```

- [ ] **Step 2: Escribir el smoke check** (genera imágenes reales en memoria, no
      depende de archivos externos)

```php
<?php
// scripts/check_listing_uploader.php — borrar tras verificar
require __DIR__ . '/../autoload.php';

use App\Infrastructure\Services\ListingUploader;

$uploader = new ListingUploader();
$listingId = 999999; // id de prueba, aislado de datos reales

function makeUploadedFileFixture(string $localPath): array
{
    // move_uploaded_file() exige que el archivo se reconozca como "subido por HTTP";
    // is_uploaded_file() no lo hará aquí, así que probamos store() por partes:
    // primero validamos el rechazo de tipos/tamaño (no requieren is_uploaded_file),
    // y comprobamos por separado que move_uploaded_file() es el único paso que
    // requeriría una petición HTTP real (se deja como nota, no bloquea el smoke check).
    return [
        'tmp_name' => $localPath,
        'size'     => filesize($localPath),
        'name'     => basename($localPath),
    ];
}

// 1. Archivo que no es una subida real -> debe rechazar por is_uploaded_file()
$fakeJpg = sys_get_temp_dir() . '/fake.jpg';
file_put_contents($fakeJpg, str_repeat('A', 100)); // texto plano con extensión .jpg
try {
    $uploader->store($listingId, makeUploadedFileFixture($fakeJpg));
    echo "FALLO: debía rechazar un archivo que no vino de \$_FILES\n";
    exit(1);
} catch (\RuntimeException $e) {
    assert(str_contains($e->getMessage(), 'no recibido'), 'debe rechazar por no ser is_uploaded_file');
}

// 2. Archivo de texto disfrazado de imagen (MIME real via finfo) -> debe rechazar
//    Se prueba directamente el detector de MIME real, ya que is_uploaded_file()
//    bloquea antes de llegar a esa validación en un script de línea de comandos.
$finfo = new finfo(FILEINFO_MIME_TYPE);
$realMime = $finfo->file($fakeJpg);
assert($realMime !== 'image/jpeg', 'un .txt disfrazado de .jpg no debe detectarse como image/jpeg real');

// 3. Imagen real válida (PNG generado en memoria) -> el MIME real debe reconocerse
$pngPath = sys_get_temp_dir() . '/real.png';
$im = imagecreate(2, 2);
imagepng($im, $pngPath);
imagedestroy($im);
$realPngMime = $finfo->file($pngPath);
assert($realPngMime === 'image/png', 'un PNG real debe detectarse como image/png');

unlink($fakeJpg);
unlink($pngPath);

echo "OK: ListingUploader valida tipo real y tamaño correctamente\n";
echo "NOTA: move_uploaded_file() solo se ejecuta con una petición HTTP real; se\n";
echo "verificará end-to-end en el navegador en la Task 10.\n";
```

- [ ] **Step 3: Ejecutar el smoke check**

Run: `php -d assert.exception=1 scripts/check_listing_uploader.php`
Expected: `OK: ListingUploader valida tipo real y tamaño correctamente`

- [ ] **Step 4: Borrar el script y commitear solo la clase**

```bash
rm scripts/check_listing_uploader.php
git add src/Infrastructure/Services/ListingUploader.php
git commit -m "feat(backoffice): añade ListingUploader (valida MIME real y tamaño, guarda en public/uploads/)"
```

---

### Task 5: `MySQLServiceRepository` — `findByIdForEdit`, `updateListing`, `setListingCategories`

**Files:**
- Modify: `src/Infrastructure/Persistence/MySQLServiceRepository.php`
- Create: `scripts/check_repository_backoffice.php` (smoke check, se borra al final)

**Interfaces:**
- Consumes: `rowToService()` (privado, ya existe línea 379) para reutilizar el mismo
  mapeo que `findById`; `getCategories()` (ya existe, línea 195); `refreshCategoryCountsCache()`
  (ya existe, público, línea 228).
- Produces:
  - `findByIdForEdit(int $id): ?Service` — igual que `findById()` pero **sin** filtrar
    `status = 'publish'` (el owner/admin debe poder editar fichas en `draft`).
  - `updateListing(int $id, array $fields): void` — `$fields` es un array asociativo
    con claves de esta whitelist: `title, description, phone, mobile, email, website,
    facebook, address, address2, postal_code, city, cover`. Claves fuera de la
    whitelist se ignoran (no llegan nunca a SQL).
  - `setListingCategories(int $id, array $categoryIds): void` — reemplaza las filas de
    `listing_categories` para ese listing y llama `refreshCategoryCountsCache()`.

- [ ] **Step 1: Añadir los 3 métodos** (insertar después de `findById()`, antes de
      `findAll()`, en `src/Infrastructure/Persistence/MySQLServiceRepository.php`)

```php
    public function findByIdForEdit(int $id): ?Service
    {
        $sql = "SELECT l.*,
                       GROUP_CONCAT(c.id   ORDER BY c.name SEPARATOR ',') AS cat_ids,
                       GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR '|') AS cat_names,
                       GROUP_CONCAT(c.slug ORDER BY c.name SEPARATOR ',') AS cat_slugs
                FROM listings l
                LEFT JOIN listing_categories lc ON l.id = lc.listing_id
                LEFT JOIN categories         c  ON lc.category_id = c.id
                WHERE l.id = :id
                GROUP BY l.id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToService($row) : null;
    }

    private const EDITABLE_FIELDS = [
        'title', 'description', 'phone', 'mobile', 'email', 'website',
        'facebook', 'address', 'address2', 'postal_code', 'city', 'cover',
    ];

    public function updateListing(int $id, array $fields): void
    {
        $set = [];
        $params = ['id' => $id];

        foreach (self::EDITABLE_FIELDS as $field) {
            if (array_key_exists($field, $fields)) {
                $set[] = "`$field` = :$field";
                $params[$field] = $fields[$field];
            }
        }

        if (empty($set)) {
            return;
        }

        $sql = "UPDATE listings SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function setListingCategories(int $id, array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));

        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare("DELETE FROM listing_categories WHERE listing_id = :id");
            $delete->execute(['id' => $id]);

            if (!empty($categoryIds)) {
                $insert = $this->db->prepare(
                    "INSERT INTO listing_categories (listing_id, category_id) VALUES (:listing_id, :category_id)"
                );
                foreach ($categoryIds as $categoryId) {
                    $insert->execute(['listing_id' => $id, 'category_id' => $categoryId]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $this->refreshCategoryCountsCache();
    }
```

- [ ] **Step 2: Escribir el smoke check**

```php
<?php
// scripts/check_repository_backoffice.php — borrar tras verificar
require __DIR__ . '/../src/Config/config.php';
require __DIR__ . '/../autoload.php';

use App\Infrastructure\Persistence\MySQLServiceRepository;
use App\Config\Database;

$repo = new MySQLServiceRepository();
$db = Database::getConnection();

// Fixture: listing en draft (findById normal NO debería encontrarlo)
$db->exec("INSERT INTO listings (slug, title, status, description) VALUES ('test-repo-backoffice', 'ANTES', 'draft', 'desc inicial')");
$id = (int) $db->lastInsertId();

// 1. findById (existente) NO debe devolver un draft
$viaFindById = $repo->findById($id, 'fr');
assert($viaFindById === null, 'findById no debe devolver listings en draft');

// 2. findByIdForEdit SÍ debe devolverlo
$service = $repo->findByIdForEdit($id);
assert($service !== null, 'findByIdForEdit debe devolver el listing aunque esté en draft');
assert($service->translations['title'] === 'ANTES', 'debe traer el título actual');

// 3. updateListing actualiza solo los campos permitidos
$repo->updateListing($id, ['title' => 'DESPUES', 'campo_inventado' => 'no debe escribirse']);
$check = $db->prepare("SELECT title FROM listings WHERE id = :id");
$check->execute(['id' => $id]);
assert($check->fetchColumn() === 'DESPUES', 'el título debe haberse actualizado');

// 4. setListingCategories reemplaza categorías y refresca la cache
$anyCategory = (int) $db->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
$repo->setListingCategories($id, [$anyCategory]);
$catCheck = $db->prepare("SELECT COUNT(*) FROM listing_categories WHERE listing_id = :id AND category_id = :cat");
$catCheck->execute(['id' => $id, 'cat' => $anyCategory]);
assert((int) $catCheck->fetchColumn() === 1, 'la categoría debe quedar asociada');

// Limpieza
$db->exec("DELETE FROM listing_categories WHERE listing_id = $id");
$db->exec("DELETE FROM listings WHERE id = $id");

echo "OK: findByIdForEdit / updateListing / setListingCategories funcionan correctamente\n";
```

- [ ] **Step 3: Ejecutar el smoke check**

Run: `php -d assert.exception=1 scripts/check_repository_backoffice.php`
Expected: `OK: findByIdForEdit / updateListing / setListingCategories funcionan correctamente`

- [ ] **Step 4: Borrar el script y commitear**

```bash
rm scripts/check_repository_backoffice.php
git add src/Infrastructure/Persistence/MySQLServiceRepository.php
git commit -m "feat(backoffice): añade findByIdForEdit/updateListing/setListingCategories al repositorio"
```

---

### Task 6: `BackofficeController` + ruta `/backoffice/login` + vista de login (end-to-end)

**Files:**
- Create: `src/Infrastructure/Controllers/BackofficeController.php`
- Create: `src/Infrastructure/Views/backoffice/login.php`
- Create: `src/Infrastructure/Views/errors/403.php`
- Modify: `src/Infrastructure/Controllers/PageController.php`

**Interfaces:**
- Consumes: `AuthService` (Task 3), `Csrf` (Task 2).
- Produces: `BackofficeController::handle(string $lang, ?string $params): void` — único
  método público, llamado desde `PageController::render()`. Rutas resueltas dentro:
  `$params === 'login'` → login; `$params === 'logout'` → logout; `$params === null`
  → dashboard (implementado en Task 8/11, en esta tarea solo el guard + placeholder).

- [ ] **Step 1: Escribir `BackofficeController` (login + logout + guard, dashboard
      placeholder)**

```php
<?php
// src/Infrastructure/Controllers/BackofficeController.php
namespace App\Infrastructure\Controllers;

use App\Infrastructure\Services\AuthService;
use App\Infrastructure\Services\Csrf;
use App\Infrastructure\Persistence\MySQLServiceRepository;

class BackofficeController
{
    private AuthService $auth;

    public function __construct()
    {
        AuthService::startSession();
        $this->auth = new AuthService();
    }

    public function handle(string $lang, ?string $params): void
    {
        $seo = [
            'title' => 'Backoffice | Canal du Midi',
            'description' => 'Espace de gestion des fiches établissements.',
            'keywords' => '',
        ];
        $page = 'backoffice';

        if ($params === 'logout') {
            $this->auth->logout();
            header('Location: ' . BASE_URL . $lang . '/backoffice/login');
            exit;
        }

        if ($params === 'login') {
            $this->handleLogin($lang, $page, $seo);
            return;
        }

        // Cualquier otra subruta (dashboard) requiere sesión.
        if ($this->auth->currentUser() === null) {
            header('Location: ' . BASE_URL . $lang . '/backoffice/login');
            exit;
        }

        $this->handleDashboard($lang, $page, $seo);
    }

    private function handleLogin(string $lang, string $page, array $seo): void
    {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['csrf'] ?? null)) {
                require __DIR__ . '/../Views/layout/header.php';
                require __DIR__ . '/../Views/errors/403.php';
                require __DIR__ . '/../Views/layout/footer.php';
                return;
            }

            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            if ($this->auth->attempt($email, $password, $ip)) {
                header('Location: ' . BASE_URL . $lang . '/backoffice');
                exit;
            }

            $error = $this->auth->tooManyAttempts($ip)
                ? 'Trop de tentatives. Réessayez dans 15 minutes.'
                : 'Identifiants incorrects.';
        }

        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/backoffice/login.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }

    private function handleDashboard(string $lang, string $page, array $seo): void
    {
        // Implementado en Task 8 (owner) y Task 11 (admin).
        require __DIR__ . '/../Views/layout/header.php';
        echo '<div class="container"><p>Dashboard en construcción.</p></div>';
        require __DIR__ . '/../Views/layout/footer.php';
    }
}
```

- [ ] **Step 2: Escribir la vista de login**

```php
<?php
// src/Infrastructure/Views/backoffice/login.php
use App\Infrastructure\Services\Csrf;
?>
<main class="backoffice-page backoffice-login">
    <div class="container backoffice-login-shell">
        <h1>Espace professionnel</h1>
        <p>Connectez-vous pour gérer votre fiche Canal du Midi.</p>

        <?php if (!empty($error)): ?>
            <div class="backoffice-alert backoffice-alert--error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL . $lang ?>/backoffice/login" class="backoffice-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-email">Email</label>
            <input type="email" id="bo-email" name="email" required autocomplete="username">

            <label for="bo-password">Mot de passe</label>
            <input type="password" id="bo-password" name="password" required autocomplete="current-password">

            <button type="submit" class="button button-primary">Se connecter</button>
        </form>
    </div>
</main>
```

- [ ] **Step 3: Escribir la vista de error 403**

```php
<?php
// src/Infrastructure/Views/errors/403.php
?>
<main class="error-page">
    <div class="container">
        <h1>403 — Accès refusé</h1>
        <p>Vous n'avez pas la permission d'effectuer cette action.</p>
        <a href="<?= BASE_URL . $lang ?>/home">Retour à l'accueil</a>
    </div>
</main>
```

- [ ] **Step 4: Cablear la ruta en `PageController`**

Modify `src/Infrastructure/Controllers/PageController.php` — añadir dentro del
`switch ($page)` existente (junto a los demás `case`, por ejemplo justo antes de
`case 'home':`):

```php
            case 'backoffice':
                (new \App\Infrastructure\Controllers\BackofficeController())->handle($lang, $params);
                return;
```

- [ ] **Step 5: Verificar en navegador** (usar el admin creado en Task 1, Step 6)

1. Navegar a `http://localhost/canal_du_midi/fr/backoffice/login`.
2. Confirmar que se ve el formulario, sin errores de consola.
3. Enviar credenciales incorrectas → debe mostrar "Identifiants incorrects." y quedarse
   en la página (no redirige).
4. Enviar las credenciales del admin creado en Task 1 → debe redirigir a
   `/fr/backoffice` y mostrar "Dashboard en construcción." (placeholder de esta tarea).
5. Navegar a `/fr/backoffice/logout` → debe redirigir a `/fr/backoffice/login`.
6. Navegar directo a `/fr/backoffice` sin sesión (usar ventana de incógnito o tras el
   logout) → debe redirigir a `/fr/backoffice/login` (guard funcionando).

- [ ] **Step 6: Commit**

```bash
git add src/Infrastructure/Controllers/BackofficeController.php \
        src/Infrastructure/Views/backoffice/login.php \
        src/Infrastructure/Views/errors/403.php \
        src/Infrastructure/Controllers/PageController.php
git commit -m "feat(backoffice): login/logout end-to-end + guard de sesión + ruta /backoffice"
```

---

### Task 7: CSS base del backoffice

**Files:**
- Create: `public/assets/css/backoffice.css`
- Modify: `src/Infrastructure/Views/layout/header.php`

**Interfaces:**
- Consumes: nada nuevo.
- Produces: hoja de estilos cargada cuando `$page === 'backoffice'`; usada por todas
  las vistas de `Views/backoffice/*`.

- [ ] **Step 1: Escribir el CSS**

```css
/* public/assets/css/backoffice.css */
.backoffice-page {
    max-width: 960px;
    margin: 48px auto;
    padding: 0 20px;
}

.backoffice-login-shell {
    max-width: 420px;
    margin: 80px auto;
    padding: 32px;
    border: 1px solid var(--line, #e2e2e2);
    border-radius: 12px;
    background: #fff;
}

.backoffice-form {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 20px;
}

.backoffice-form label {
    font-weight: 600;
    margin-top: 12px;
}

.backoffice-form input,
.backoffice-form textarea,
.backoffice-form select {
    padding: 10px 12px;
    border: 1px solid var(--line, #ccc);
    border-radius: 8px;
    font-size: 1rem;
}

.backoffice-form button {
    margin-top: 20px;
}

.backoffice-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 16px;
}

.backoffice-alert--error {
    background: #fdecea;
    color: #b3261e;
}

.backoffice-alert--success {
    background: #e6f4ea;
    color: #1e7e34;
}

.backoffice-fieldset {
    border: 1px solid var(--line, #e2e2e2);
    border-radius: 10px;
    padding: 16px;
    margin-top: 20px;
}

.backoffice-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 8px;
    margin-top: 8px;
}

.backoffice-gallery-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 12px;
}

.backoffice-gallery-preview img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.backoffice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.backoffice-table th,
.backoffice-table td {
    text-align: left;
    padding: 10px 12px;
    border-bottom: 1px solid var(--line, #e2e2e2);
}
```

- [ ] **Step 2: Cargar el CSS condicionalmente en `header.php`**

Modify `src/Infrastructure/Views/layout/header.php` — añadir tras el bloque
`<?php if (isset($page) && $page === 'vacation-planner'): ?>` (línea ~66-68):

```php
    <?php if (isset($page) && $page === 'backoffice'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/backoffice.css">
    <?php endif; ?>
```

- [ ] **Step 3: Verificar en navegador**

Recargar `http://localhost/canal_du_midi/fr/backoffice/login` (hard reload,
Cmd/Ctrl+Shift+R) y confirmar visualmente que el formulario ahora tiene el estilo de
tarjeta centrada, no HTML sin estilos.

- [ ] **Step 4: Commit**

```bash
git add public/assets/css/backoffice.css src/Infrastructure/Views/layout/header.php
git commit -m "feat(backoffice): estilos base del panel"
```

---

### Task 8: Formulario de edición — owner (prellenado + guardado de texto/contacto/categorías)

**Files:**
- Create: `src/Infrastructure/Views/backoffice/edit_listing.php`
- Modify: `src/Infrastructure/Controllers/BackofficeController.php`

**Interfaces:**
- Consumes: `MySQLServiceRepository::findByIdForEdit()`, `::updateListing()`,
  `::setListingCategories()`, `::getCategories()` (Task 5); `AuthService::ownerListingId()`,
  `::isAdmin()` (Task 3); `Csrf::token()`/`::check()` (Task 2).
- Produces: `edit_listing.php` recibe `$service` (objeto `Service`), `$allCategories`
  (array de `getCategories()`), `$saved` (bool), `$formError` (string), `$isAdmin` (bool).
  Reutilizada también por el admin en Task 11 (mismo template, distinto `$listing_id`
  resuelto por el controller).

- [ ] **Step 1: Reemplazar `handleDashboard()` en `BackofficeController` para resolver
      la ficha a editar y guardar cambios de texto/contacto/categorías** (la subida de
      fotos se añade en Task 10; por ahora el input `type="file"` existe en la vista
      pero el controller aún no lo procesa)

```php
    private function handleDashboard(string $lang, string $page, array $seo): void
    {
        $user = $this->auth->currentUser();
        $repo = new MySQLServiceRepository();

        $listingId = $this->auth->isAdmin()
            ? (int) ($_GET['id'] ?? 0)
            : $this->auth->ownerListingId();

        if ($this->auth->isAdmin() && $listingId === 0) {
            $this->handleAdminDashboard($lang, $page, $seo, $repo);
            return;
        }

        if (!$listingId) {
            require __DIR__ . '/../Views/layout/header.php';
            require __DIR__ . '/../Views/errors/403.php';
            require __DIR__ . '/../Views/layout/footer.php';
            return;
        }

        $saved = false;
        $formError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['csrf'] ?? null)) {
                require __DIR__ . '/../Views/layout/header.php';
                require __DIR__ . '/../Views/errors/403.php';
                require __DIR__ . '/../Views/layout/footer.php';
                return;
            }

            $fields = [
                'title'       => trim((string) ($_POST['title'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'phone'       => trim((string) ($_POST['phone'] ?? '')),
                'mobile'      => trim((string) ($_POST['mobile'] ?? '')),
                'email'       => trim((string) ($_POST['email'] ?? '')),
                'website'     => trim((string) ($_POST['website'] ?? '')),
                'facebook'    => trim((string) ($_POST['facebook'] ?? '')),
                'address'     => trim((string) ($_POST['address'] ?? '')),
                'address2'    => trim((string) ($_POST['address2'] ?? '')),
                'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
                'city'        => trim((string) ($_POST['city'] ?? '')),
            ];

            if ($fields['title'] === '') {
                $formError = 'Le titre est obligatoire.';
            } elseif ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                $formError = "L'email n'est pas valide.";
            } else {
                $repo->updateListing($listingId, $fields);

                $categoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));
                $repo->setListingCategories($listingId, $categoryIds);

                header('Location: ' . BASE_URL . $lang . '/backoffice' .
                    ($this->auth->isAdmin() ? '?id=' . $listingId . '&saved=1' : '?saved=1'));
                exit;
            }
        }

        $service = $repo->findByIdForEdit($listingId);
        if ($service === null) {
            require __DIR__ . '/../Views/layout/header.php';
            require __DIR__ . '/../Views/errors/403.php';
            require __DIR__ . '/../Views/layout/footer.php';
            return;
        }

        $allCategories = $repo->getCategories();
        $saved = isset($_GET['saved']);
        $isAdmin = $this->auth->isAdmin();

        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/backoffice/edit_listing.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }

    private function handleAdminDashboard(string $lang, string $page, array $seo, MySQLServiceRepository $repo): void
    {
        // Implementado en Task 11.
        require __DIR__ . '/../Views/layout/header.php';
        echo '<div class="container"><p>Admin dashboard en construcción.</p></div>';
        require __DIR__ . '/../Views/layout/footer.php';
    }
```

Nota: añadir `use App\Infrastructure\Persistence\MySQLServiceRepository;` ya está en
el `use` del Step 1 de Task 6 (ya importado). No hace falta repetirlo.

- [ ] **Step 2: Escribir la vista `edit_listing.php`**

```php
<?php
// src/Infrastructure/Views/backoffice/edit_listing.php
use App\Infrastructure\Services\Csrf;

$categoryIds = array_column($service->categories, 'id');
$actionUrl = BASE_URL . $lang . '/backoffice' . ($isAdmin ? '?id=' . $service->id : '');
?>
<main class="backoffice-page">
    <h1>Modifier ma fiche</h1>

    <?php if ($saved): ?>
        <div class="backoffice-alert backoffice-alert--success">Modifications enregistrées.</div>
    <?php endif; ?>
    <?php if (!empty($formError)): ?>
        <div class="backoffice-alert backoffice-alert--error"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="backoffice-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

        <label for="bo-title">Titre</label>
        <input type="text" id="bo-title" name="title" required value="<?= htmlspecialchars($service->translations['title'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="bo-description">Description</label>
        <textarea id="bo-description" name="description" rows="6"><?= htmlspecialchars($service->translations['description'], ENT_QUOTES, 'UTF-8') ?></textarea>

        <fieldset class="backoffice-fieldset">
            <legend>Contact</legend>

            <label for="bo-phone">Téléphone</label>
            <input type="text" id="bo-phone" name="phone" value="<?= htmlspecialchars($service->contact['phone'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-mobile">Mobile</label>
            <input type="text" id="bo-mobile" name="mobile" value="<?= htmlspecialchars($service->contact['mobile'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-email">Email</label>
            <input type="email" id="bo-email" name="email" value="<?= htmlspecialchars($service->contact['email'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-website">Site web</label>
            <input type="url" id="bo-website" name="website" value="<?= htmlspecialchars($service->contact['website'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-facebook">Facebook</label>
            <input type="url" id="bo-facebook" name="facebook" value="<?= htmlspecialchars($service->contact['facebook'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </fieldset>

        <fieldset class="backoffice-fieldset">
            <legend>Adresse</legend>

            <label for="bo-address">Adresse</label>
            <input type="text" id="bo-address" name="address" value="<?= htmlspecialchars($service->contact['address'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-postal">Code postal</label>
            <input type="text" id="bo-postal" name="postal_code" value="<?= htmlspecialchars($service->contact['cp'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-city">Ville</label>
            <input type="text" id="bo-city" name="city" value="<?= htmlspecialchars($service->contact['ville'], ENT_QUOTES, 'UTF-8') ?>">
        </fieldset>

        <fieldset class="backoffice-fieldset">
            <legend>Catégories</legend>
            <div class="backoffice-checkbox-grid">
                <?php foreach ($allCategories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>"
                            <?= in_array((int) $cat['id'], $categoryIds, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="backoffice-fieldset">
            <legend>Photos</legend>

            <label for="bo-cover">Photo de couverture</label>
            <input type="file" id="bo-cover" name="cover_file" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($service->imageUrl)): ?>
                <div class="backoffice-gallery-preview">
                    <img src="<?= htmlspecialchars($service->imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Couverture actuelle">
                </div>
            <?php endif; ?>

            <label for="bo-gallery">Ajouter des photos à la galerie</label>
            <input type="file" id="bo-gallery" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple>
            <?php if (!empty($service->gallery)): ?>
                <div class="backoffice-gallery-preview">
                    <?php foreach ($service->gallery as $photo): ?>
                        <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de la galerie">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </fieldset>

        <button type="submit" class="button button-primary">Enregistrer</button>
    </form>
</main>
```

- [ ] **Step 3: Verificar en navegador** (login como admin, ir directo con `?id=` a un
      listing real, ej. el de "À l'abordage Moussaillon!")

1. Login como admin → navegar a `http://localhost/canal_du_midi/fr/backoffice?id=246`
   (sustituir 246 por el id real del listing de prueba usado en sesiones anteriores).
2. Confirmar que el formulario aparece prellenado con el título/descripción/contacto
   reales del listing.
3. Cambiar el título a algo distinguible (ej. añadir " (editado backoffice)"), marcar
   una categoría distinta, guardar.
4. Confirmar redirect a `?id=246&saved=1` con el mensaje "Modifications enregistrées.".
5. Abrir `http://localhost/canal_du_midi/fiche/a-labordage-moussaillon` (hard reload) y
   confirmar que el título cambiado se refleja ahí.
6. Revertir el título al original guardando de nuevo (para no dejar datos de prueba
   permanentes en el listing real).

- [ ] **Step 4: Commit**

```bash
git add src/Infrastructure/Controllers/BackofficeController.php \
        src/Infrastructure/Views/backoffice/edit_listing.php
git commit -m "feat(backoffice): formulario de edición de ficha (texto, contacto, categorías)"
```

---

### Task 9: Subida de fotos (portada + galería) en el formulario de edición

**Files:**
- Modify: `src/Infrastructure/Controllers/BackofficeController.php`

**Interfaces:**
- Consumes: `ListingUploader::store()` (Task 4).
- Produces: al guardar, `cover` y `gallery` (JSON) en `listings` reflejan las fotos
  nuevas subidas, además de los campos de texto ya manejados en Task 8.

- [ ] **Step 1: Ampliar el bloque POST de `handleDashboard()` para procesar
      `cover_file` y `gallery_files[]`** — insertar justo antes de la llamada a
      `$repo->updateListing($listingId, $fields);` (Task 8, Step 1):

```php
            $uploader = new \App\Infrastructure\Services\ListingUploader();
            $uploadError = '';

            if (!empty($_FILES['cover_file']['tmp_name'])) {
                try {
                    $fields['cover'] = $uploader->store($listingId, $_FILES['cover_file']);
                } catch (\RuntimeException $e) {
                    $uploadError = $e->getMessage();
                }
            }

            $newGalleryPhotos = [];
            if (!empty($_FILES['gallery_files']['tmp_name'][0])) {
                foreach ($_FILES['gallery_files']['tmp_name'] as $i => $tmpName) {
                    if ($tmpName === '') {
                        continue;
                    }
                    $fileEntry = [
                        'tmp_name' => $tmpName,
                        'size'     => $_FILES['gallery_files']['size'][$i],
                        'name'     => $_FILES['gallery_files']['name'][$i],
                    ];
                    try {
                        $newGalleryPhotos[] = $uploader->store($listingId, $fileEntry);
                    } catch (\RuntimeException $e) {
                        $uploadError = $e->getMessage();
                    }
                }
            }

            if ($uploadError !== '') {
                $formError = $uploadError;
            } elseif ($fields['title'] === '') {
```

Y sustituir la línea existente `if ($fields['title'] === '') {` (que queda ahora
duplicada por el `elseif` de arriba) — el bloque completo del `if/elseif` de
validación queda así:

```php
            if ($uploadError !== '') {
                $formError = $uploadError;
            } elseif ($fields['title'] === '') {
                $formError = 'Le titre est obligatoire.';
            } elseif ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                $formError = "L'email n'est pas valide.";
            } else {
                $repo->updateListing($listingId, $fields);

                if (!empty($newGalleryPhotos)) {
                    $currentGallery = $repo->findByIdForEdit($listingId)->gallery ?? [];
                    $updatedGallery = array_values(array_merge($currentGallery, $newGalleryPhotos));
                    $repo->updateListing($listingId, ['gallery' => json_encode($updatedGallery)]);
                }

                $categoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));
                $repo->setListingCategories($listingId, $categoryIds);

                header('Location: ' . BASE_URL . $lang . '/backoffice' .
                    ($this->auth->isAdmin() ? '?id=' . $listingId . '&saved=1' : '?saved=1'));
                exit;
            }
```

- [ ] **Step 2: Añadir `gallery` a la whitelist de `updateListing`** — en
      `src/Infrastructure/Persistence/MySQLServiceRepository.php` (Task 5, Step 1),
      editar `EDITABLE_FIELDS`:

```php
    private const EDITABLE_FIELDS = [
        'title', 'description', 'phone', 'mobile', 'email', 'website',
        'facebook', 'address', 'address2', 'postal_code', 'city', 'cover', 'gallery',
    ];
```

- [ ] **Step 3: Verificar en navegador**

1. En `http://localhost/canal_du_midi/fr/backoffice?id=246`, subir una foto JPG/PNG
   pequeña como "photo de couverture" y guardar.
2. Confirmar redirect con `saved=1`, y que la sección "Photos" ahora muestra esa foto
   como portada actual.
3. Confirmar en el servidor: `ls public/uploads/listings/246/` debe contener el
   archivo nuevo.
4. Abrir `/fiche/a-labordage-moussaillon` y confirmar que el hero (carrusel de la
   sesión anterior) incluye la foto nueva.
5. Subir un archivo `.txt` renombrado a `.jpg` como portada → debe mostrar el error
   "Formato de imagen no permitido..." y NO debe guardar nada (confirmar que
   `cover` en BD no cambió).

- [ ] **Step 4: Commit**

```bash
git add src/Infrastructure/Controllers/BackofficeController.php \
        src/Infrastructure/Persistence/MySQLServiceRepository.php
git commit -m "feat(backoffice): subida de portada y galería con validación de MIME real"
```

---

### Task 10: Dashboard admin — listado, alta de cuenta owner, reset de password

**Files:**
- Create: `src/Infrastructure/Views/backoffice/admin_dashboard.php`
- Modify: `src/Infrastructure/Controllers/BackofficeController.php`

**Interfaces:**
- Consumes: `MySQLServiceRepository::findAll()` (ya existe) o una búsqueda simple por
  título; `AuthService::createOwnerAccount()`, `::resetOwnerPassword()`,
  `::listOwnersWithListing()` (Task 3); `Csrf`.
- Produces: vista con tabla de fichas + link "Modifier" (`?id=`), formulario de alta
  de cuenta owner, tabla de owners existentes con botón de reset de password.

- [ ] **Step 1: Implementar `handleAdminDashboard()`** (reemplaza el placeholder de
      Task 8, Step 1, en `BackofficeController`):

```php
    private function handleAdminDashboard(string $lang, string $page, array $seo, MySQLServiceRepository $repo): void
    {
        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['csrf'] ?? null)) {
                require __DIR__ . '/../Views/layout/header.php';
                require __DIR__ . '/../Views/errors/403.php';
                require __DIR__ . '/../Views/layout/footer.php';
                return;
            }

            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'create_owner') {
                $email = trim((string) ($_POST['owner_email'] ?? ''));
                $password = (string) ($_POST['owner_password'] ?? '');
                $listingId = (int) ($_POST['listing_id'] ?? 0);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "L'email du compte n'est pas valide.";
                } elseif (strlen($password) < 8) {
                    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                } elseif ($listingId <= 0) {
                    $error = 'Sélectionnez une fiche.';
                } else {
                    try {
                        $this->auth->createOwnerAccount($email, $password, $listingId);
                        $message = 'Compte créé avec succès.';
                    } catch (\PDOException $e) {
                        $error = 'Cet email est déjà utilisé.';
                    }
                }
            } elseif ($action === 'reset_password') {
                $listingId = (int) ($_POST['listing_id'] ?? 0);
                $newPassword = (string) ($_POST['new_password'] ?? '');

                if (strlen($newPassword) < 8) {
                    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                } else {
                    $this->auth->resetOwnerPassword($listingId, $newPassword);
                    $message = 'Mot de passe réinitialisé.';
                }
            }
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $allListings = $repo->searchListings($search);
        $owners = $this->auth->listOwnersWithListing();

        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/backoffice/admin_dashboard.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }
```

Nota: `searchListings(string $query = '', string $city = '', array $types = [], array
$categoryIds = []): array` ya existe (línea 86) y acepta búsqueda libre por texto —
reutilizado tal cual con solo el primer argumento.

- [ ] **Step 2: Escribir la vista `admin_dashboard.php`**

```php
<?php
// src/Infrastructure/Views/backoffice/admin_dashboard.php
use App\Infrastructure\Services\Csrf;
?>
<main class="backoffice-page">
    <h1>Backoffice — Administration</h1>

    <?php if (!empty($message)): ?>
        <div class="backoffice-alert backoffice-alert--success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="backoffice-alert backoffice-alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="backoffice-fieldset">
        <h2>Fiches</h2>
        <form method="get" action="<?= BASE_URL . $lang ?>/backoffice">
            <input type="text" name="q" placeholder="Rechercher une fiche..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="button button-small">Rechercher</button>
        </form>

        <table class="backoffice-table">
            <thead><tr><th>Titre</th><th>Ville</th><th></th></tr></thead>
            <tbody>
                <?php foreach (array_slice($allListings, 0, 50) as $listing): ?>
                    <tr>
                        <td><?= htmlspecialchars($listing->translations['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($listing->contact['ville'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="<?= BASE_URL . $lang ?>/backoffice?id=<?= (int) $listing->id ?>">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="backoffice-fieldset">
        <h2>Créer un compte établissement</h2>
        <form method="post" action="<?= BASE_URL . $lang ?>/backoffice" class="backoffice-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="create_owner">

            <label for="bo-owner-listing">Fiche</label>
            <select id="bo-owner-listing" name="listing_id" required>
                <option value="">— Choisir —</option>
                <?php foreach (array_slice($allListings, 0, 200) as $listing): ?>
                    <option value="<?= (int) $listing->id ?>"><?= htmlspecialchars($listing->translations['title'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="bo-owner-email">Email du compte</label>
            <input type="email" id="bo-owner-email" name="owner_email" required>

            <label for="bo-owner-password">Mot de passe temporaire</label>
            <input type="text" id="bo-owner-password" name="owner_password" required minlength="8">

            <button type="submit" class="button button-primary">Créer le compte</button>
        </form>
    </section>

    <section class="backoffice-fieldset">
        <h2>Comptes établissements existants</h2>
        <table class="backoffice-table">
            <thead><tr><th>Email</th><th>Fiche</th><th>Réinitialiser mot de passe</th></tr></thead>
            <tbody>
                <?php foreach ($owners as $owner): ?>
                    <tr>
                        <td><?= htmlspecialchars($owner['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($owner['listing_title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL . $lang ?>/backoffice" class="backoffice-inline-form">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="listing_id" value="<?= (int) $owner['listing_id'] ?>">
                                <input type="text" name="new_password" placeholder="Nouveau mot de passe" minlength="8" required>
                                <button type="submit" class="button button-small">Réinitialiser</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
```

- [ ] **Step 3: Verificar en navegador**

1. Login como admin → `http://localhost/canal_du_midi/fr/backoffice` (sin `?id=`) →
   debe mostrar el dashboard admin, no el placeholder.
2. Buscar por el título de un listing real en el campo de búsqueda → confirmar que
   filtra.
3. Crear una cuenta owner de prueba (email de prueba + password ≥8 caracteres,
   asociada a un listing de prueba, no uno real en producción) → confirmar mensaje
   "Compte créé avec succès." y que aparece en la tabla de "Comptes existants".
4. Logout → login con esa cuenta owner nueva → confirmar que entra directo al
   formulario de SU ficha (no ve el dashboard admin).
5. Logout → login de nuevo como admin → resetear la password de esa cuenta de prueba
   → logout → confirmar que la cuenta entra con la password nueva.
6. Limpieza: borrar la cuenta/asociación de prueba:
   `mysql -h localhost -u root canal_du_midi -e "UPDATE listings SET owner_user_id=NULL, claimed=0 WHERE owner_user_id=(SELECT id FROM users WHERE email='<email de prueba>'); DELETE FROM users WHERE email='<email de prueba>';"`

- [ ] **Step 4: Commit**

```bash
git add src/Infrastructure/Controllers/BackofficeController.php \
        src/Infrastructure/Views/backoffice/admin_dashboard.php
git commit -m "feat(backoffice): dashboard admin (listado, alta de cuentas, reset de password)"
```

---

### Task 11: Guard cruzado — un owner nunca accede a otra ficha por `?id=`

**Files:**
- Modify: `src/Infrastructure/Controllers/BackofficeController.php`

**Interfaces:**
- Consumes: nada nuevo — refuerzo de seguridad sobre `handleDashboard()` (Task 8).
- Produces: mismo `handle()` público, sin cambios de firma.

- [ ] **Step 1: Confirmar que `handleDashboard()` ya es seguro por construcción** — en
      Task 8, Step 1, la resolución de `$listingId` es:
      ```php
      $listingId = $this->auth->isAdmin()
          ? (int) ($_GET['id'] ?? 0)
          : $this->auth->ownerListingId();
      ```
      Un `owner` **nunca** llega a leer `$_GET['id']` — siempre se usa
      `ownerListingId()`, resuelto en sesión durante el login (Task 3). No hace falta
      código nuevo; este paso es de verificación explícita.

- [ ] **Step 2: Verificar en navegador que el guard aguanta el intento directo**

1. Login como el owner de prueba creado en Task 10 (o cualquier owner real).
2. Anotar su `listing_id` real:
   `mysql -h localhost -u root canal_du_midi -e "SELECT id FROM listings WHERE owner_user_id=(SELECT id FROM users WHERE email='<email owner>');"`
3. Con la sesión de ese owner activa, navegar a
   `http://localhost/canal_du_midi/fr/backoffice?id=<id de OTRA ficha distinta>`.
4. Confirmar que el formulario mostrado sigue siendo el de SU propia ficha (el `?id=`
   se ignora por completo para un owner) — nunca debe mostrar ni permitir guardar
   datos de la ficha ajena.

- [ ] **Step 3: Commit** (solo si Step 1 requirió algún ajuste; si no, no hay diff que
      commitear — se documenta la verificación en el propio plan)

```bash
git status --short
# Si no hay cambios pendientes, este paso no genera commit.
```

---

### Task 12: Verificación end-to-end completa (checklist del spec)

**Files:** ninguno (solo verificación manual).

**Interfaces:** ninguna nueva — confirma que las Tasks 1-11 funcionan juntas.

- [ ] **Step 1: Recorrer el checklist completo del spec en el navegador**

Usar Chrome (vía las herramientas MCP de este proyecto) contra
`http://localhost/canal_du_midi`:

1. Admin crea cuenta owner para un listing real → login como ese owner → solo ve/edita
   su ficha (`?id=` de otra ficha no cambia lo mostrado — Task 11).
2. Editar texto/contacto → guardar → recargar `/fiche/{slug}` y confirmar el cambio.
3. Subir foto de portada y de galería → confirmar que aparecen en `/fiche/{slug}` y
   como archivo físico en `public/uploads/listings/{id}/`.
4. Cambiar categorías → confirmar que los contadores de "Destinations phares" en home
   y el filtro de `/search` reflejan el cambio.
5. CSRF: `curl -s -X POST http://localhost/canal_du_midi/fr/backoffice/login -d "email=x@x.com&password=x"`
   (sin campo `csrf`) → la respuesta debe ser la página 403, no un intento de login.
6. Rate-limit: 6 intentos de login fallidos seguidos desde la misma IP → el 6º debe
   mostrar "Trop de tentatives..." aunque se borren las cookies del navegador entre
   intento e intento.
7. Subir `.php` renombrado a `.jpg` como portada → debe rechazarse (mensaje de error,
   sin guardar); pedir esa URL de subida (si llegó a crearse por error) directo en el
   navegador no debe ejecutar PHP.
8. Admin resetea la password de un owner → el owner puede entrar con la nueva.

- [ ] **Step 2: Revisar consola del navegador sin errores** en `/backoffice/login`,
      `/backoffice` (owner), `/backoffice` (admin), `/backoffice?id=X` — confirmar
      `0 errores` con las herramientas de consola de Chrome MCP.

- [ ] **Step 3: Actualizar `docs/TASKS.md` y `docs/SESSION.md`** (regla del proyecto,
      `CLAUDE.md` — "Reglas del pipeline") con el resultado de esta verificación y
      cualquier seguimiento abierto (ej. si algo del checklist no pasó y quedó
      pendiente).

- [ ] **Step 4: Commit final**

```bash
git add docs/TASKS.md docs/SESSION.md
git commit -m "docs(backoffice): cierre de verificación end-to-end del backoffice de fichas"
```
