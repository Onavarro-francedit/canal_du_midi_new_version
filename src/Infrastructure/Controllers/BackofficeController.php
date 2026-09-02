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
                http_response_code(403);
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
