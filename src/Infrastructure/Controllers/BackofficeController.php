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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            $this->handleAjaxBlockUpdate($listingId, $repo);
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
                'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
                'city'        => trim((string) ($_POST['city'] ?? '')),
            ];

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
        }

        $service = $repo->findByIdForEdit($listingId);
        if ($service === null) {
            require __DIR__ . '/../Views/layout/header.php';
            require __DIR__ . '/../Views/errors/403.php';
            require __DIR__ . '/../Views/layout/footer.php';
            return;
        }

        $allCategories = $repo->getCategories();

        if ($formError !== '' && isset($fields)) {
            $service->translations['title'] = $fields['title'];
            $service->translations['description'] = $fields['description'];
            $service->contact['phone'] = $fields['phone'];
            $service->contact['mobile'] = $fields['mobile'];
            $service->contact['email'] = $fields['email'];
            $service->contact['website'] = $fields['website'];
            $service->contact['facebook'] = $fields['facebook'];
            $service->contact['address_raw'] = $fields['address'];
            $service->contact['cp'] = $fields['postal_code'];
            $service->contact['ville'] = $fields['city'];

            $submittedCategoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));
            $service->categories = array_values(array_filter(
                $allCategories,
                fn($cat) => in_array((int) $cat['id'], $submittedCategoryIds, true)
            ));
        }

        $saved = isset($_GET['saved']);
        $isAdmin = $this->auth->isAdmin();
        $currentUser = $this->auth->currentUser();

        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/backoffice/edit_listing.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }

    private function handleAjaxBlockUpdate(int $listingId, MySQLServiceRepository $repo): void
    {
        header('Content-Type: application/json');

        if (!Csrf::check($_POST['csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Session invalide, rechargez la page.']);
            return;
        }

        try {
            switch ((string) ($_POST['block'] ?? '')) {
                case 'info':
                    $fields = [
                        'title'       => trim((string) ($_POST['title'] ?? '')),
                        'description' => trim((string) ($_POST['description'] ?? '')),
                    ];
                    if ($fields['title'] === '') {
                        throw new \RuntimeException('Le titre est obligatoire.');
                    }
                    $repo->updateListing($listingId, $fields);
                    echo json_encode(['success' => true]);
                    return;

                case 'contact':
                    $fields = [
                        'phone'    => trim((string) ($_POST['phone'] ?? '')),
                        'mobile'   => trim((string) ($_POST['mobile'] ?? '')),
                        'email'    => trim((string) ($_POST['email'] ?? '')),
                        'website'  => trim((string) ($_POST['website'] ?? '')),
                        'facebook' => trim((string) ($_POST['facebook'] ?? '')),
                    ];
                    if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException("L'email n'est pas valide.");
                    }
                    $repo->updateListing($listingId, $fields);
                    echo json_encode(['success' => true]);
                    return;

                case 'address':
                    $fields = [
                        'address'     => trim((string) ($_POST['address'] ?? '')),
                        'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
                        'city'        => trim((string) ($_POST['city'] ?? '')),
                    ];

                    $lat = trim((string) ($_POST['lat'] ?? ''));
                    $lng = trim((string) ($_POST['lng'] ?? ''));
                    if ($lat !== '' && $lng !== '') {
                        if (!is_numeric($lat) || !is_numeric($lng) || (float) $lat < -90 || (float) $lat > 90 || (float) $lng < -180 || (float) $lng > 180) {
                            throw new \RuntimeException('Position invalide sur la carte.');
                        }
                        $fields['lat'] = (float) $lat;
                        $fields['lng'] = (float) $lng;
                    }

                    $repo->updateListing($listingId, $fields);
                    echo json_encode(['success' => true]);
                    return;

                case 'categories':
                    $categoryIds = array_map('intval', (array) ($_POST['categories'] ?? []));
                    $repo->setListingCategories($listingId, $categoryIds);
                    echo json_encode(['success' => true]);
                    return;

                case 'delete_photo':
                    $photoUrl = trim((string) ($_POST['photo_url'] ?? ''));
                    if ($photoUrl === '') {
                        throw new \RuntimeException('Photo introuvable.');
                    }

                    $result = $repo->deleteListingPhoto($listingId, $photoUrl);
                    if (!$result['deleted']) {
                        throw new \RuntimeException("Cette photo n'appartient pas à la fiche.");
                    }

                    if ($result['orphanPath'] !== null) {
                        (new \App\Infrastructure\Services\ListingUploader())->delete($listingId, $result['orphanPath']);
                    }

                    $updated = $repo->findByIdForEdit($listingId);
                    echo json_encode(['success' => true, 'imageUrl' => $updated->imageUrl, 'gallery' => $updated->gallery]);
                    return;

                case 'photos':
                    $uploader = new \App\Infrastructure\Services\ListingUploader();
                    $current = $repo->findByIdForEdit($listingId);

                    $heroMode = (string) ($_POST['hero_mode'] ?? 'carousel') === 'single' ? 'single' : 'carousel';
                    $repo->updateListing($listingId, ['hero_mode' => $heroMode]);

                    // Le client peut choisir l'ordre/la portada AVANT d'envoyer une photo tout juste
                    // ajoutée (pas encore d'URL serveur) : il référence alors ce fichier par un jeton
                    // "new:<index dans gallery_files[]>". On stocke donc les fichiers en premier, pour
                    // pouvoir résoudre ces jetons en chemins réels avant de traiter ordre et portada.
                    $newPhotos = [];
                    if (!empty($_FILES['gallery_files']['tmp_name'][0])) {
                        foreach ($_FILES['gallery_files']['tmp_name'] as $i => $tmpName) {
                            if ($tmpName === '') {
                                continue;
                            }
                            $newPhotos[] = $uploader->store($listingId, [
                                'tmp_name' => $tmpName,
                                'size'     => $_FILES['gallery_files']['size'][$i],
                                'name'     => $_FILES['gallery_files']['name'][$i],
                            ]);
                        }
                        if ($newPhotos) {
                            $repo->updateListing($listingId, [
                                'gallery' => json_encode(array_values(array_merge($current->gallery ?? [], $newPhotos))),
                            ]);
                        }
                    }

                    $resolveToken = static function (string $token) use ($newPhotos): string {
                        if (str_starts_with($token, 'new:')) {
                            return $newPhotos[(int) substr($token, 4)] ?? '';
                        }
                        return $token;
                    };

                    $order = array_values(array_filter(array_map(
                        $resolveToken,
                        array_map('strval', (array) ($_POST['gallery_order'] ?? []))
                    ), static fn (string $url): bool => $url !== ''));
                    if ($order) {
                        $repo->reorderListingGallery($listingId, $order);
                    }

                    if (!empty($_FILES['cover_file']['tmp_name'])) {
                        $repo->updateListing($listingId, ['cover' => $uploader->store($listingId, $_FILES['cover_file'])]);
                    } else {
                        $coverSource = $resolveToken(trim((string) ($_POST['cover_source'] ?? '')));
                        $allowedCovers = array_merge([$current->imageUrl], $current->gallery, $newPhotos);
                        if ($coverSource !== '' && in_array($coverSource, $allowedCovers, true)) {
                            $repo->updateListing($listingId, ['cover' => $coverSource]);
                        }
                    }

                    $updated = $repo->findByIdForEdit($listingId);
                    echo json_encode(['success' => true, 'imageUrl' => $updated->imageUrl, 'gallery' => $updated->gallery]);
                    return;

                default:
                    throw new \RuntimeException('Bloc inconnu.');
            }
        } catch (\RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function handleAdminDashboard(string $lang, string $page, array $seo, MySQLServiceRepository $repo): void
    {
        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['csrf'] ?? null)) {
                http_response_code(403);
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
                    } catch (\RuntimeException $e) {
                        $error = $e->getMessage();
                    }
                }
            } elseif ($action === 'reset_password') {
                $listingId = (int) ($_POST['listing_id'] ?? 0);
                $newPassword = (string) ($_POST['new_password'] ?? '');

                if (strlen($newPassword) < 8) {
                    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                } elseif ($this->auth->resetOwnerPassword($listingId, $newPassword)) {
                    $message = 'Mot de passe réinitialisé.';
                } else {
                    $error = 'Aucun compte associé à cette fiche.';
                }
            }
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $allListings = $repo->searchListingsForAdmin($search);
        $owners = $this->auth->listOwnersWithListing();
        $currentUser = $this->auth->currentUser();

        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/backoffice/admin_dashboard.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }
}
