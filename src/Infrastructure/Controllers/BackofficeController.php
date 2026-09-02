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
                http_response_code(403);
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
}
