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

        if (!$row) {
            // Mitigación de timing side-channel: paga el mismo coste bcrypt
            // que la rama de "contraseña incorrecta" para no filtrar por
            // temporización si el email existe o no.
            password_verify($password, '$2y$10$usesomesillystringfortestingusesomesillystringforte');
            return false;
        }

        if (!password_verify($password, $row['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['email']   = $row['email'];
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
            'email' => (string) ($_SESSION['email'] ?? ''),
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
