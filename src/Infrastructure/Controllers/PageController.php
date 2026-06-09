<?php
namespace App\Infrastructure\Controllers;

use App\Infrastructure\Persistence\MySQLServiceRepository;

class PageController {


    public function render(string $page, string $lang = 'fr', ?string $params = null) {
        $repository = new MySQLServiceRepository();
        $lang = $this->normalizeLanguage($lang);
        $page = $this->normalizePage($page);

        $seo = [
            'title' => "Canal du Midi | Voyages et Escapades Premium",
            'description' => "Découvrez le Canal du Midi : croisières en péniche, circuits à vélo et hôtels de charme. Planifiez votre voyage sur mesure.",
            'keywords' => "Canal du Midi, voyage, tourisme, Occitanie, péniche, vélo, hôtel"
        ];

        switch ($page) {
            case 'home':
                $allServices = $repository->findAll($lang);
                $destinations = array_filter($allServices, fn($service) => $service->type === 'destination');
                $tours = array_filter($allServices, fn($service) => $service->type === 'tour' || $service->type === 'boat');
                $allCategories = $repository->getCategoriesWithCount($lang);

                if (!empty($allCategories)) {
                    shuffle($allCategories);
                }

                $randomCategories = array_slice($allCategories, 0, 6);

                if (empty($destinations)) {
                    $destinations = array_slice($allServices, 0, 3);
                }

                $features = $repository->getActiveFeatures($lang);
                $articles = $repository->getLatestArticles($lang);

                require_once __DIR__ . '/../Views/layout/header.php';
                require_once __DIR__ . '/../Views/home.php';
                require_once __DIR__ . '/../Views/layout/footer.php';
                return;

            case 'fiche':
                $serviceSlug = $params ?? '';
                $service = $repository->findBySlug($serviceSlug);

                if (!$service) {
                    http_response_code(404);
                    $pageTitle = 'Service introuvable | Canal du Midi';
                    require_once __DIR__ . '/../Views/layout/header.php';
                    require_once __DIR__ . '/../Views/errors/service_not_found.php';
                    require_once __DIR__ . '/../Views/layout/footer.php';
                    return;
                } else {
                    $serviceTitle = $this->sanitizeText($service->translations['title'] ?? 'Service');
                    $serviceDescription = $this->buildMetaDescription($service->translations['description'] ?? '', 155);
                    $seo = [
                        'title' => $serviceTitle . ' | Canal du Midi',
                        'description' => $serviceDescription,
                        'keywords' => $serviceTitle . ', Canal du Midi'
                    ];

                    $service->nearbyPOIs = $repository->getNearbyPOIs($service->lat, $service->lng, $lang);
                    require_once __DIR__ . '/../Views/layout/header.php';
                    require_once __DIR__ . '/../Views/service_detail.php';
                    require_once __DIR__ . '/../Views/layout/footer.php';
                    return;
                }

            case 'reserve':
                switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                    case 'POST':
                        $this->handleBooking($lang);
                        return;

                    default:
                        break;
                }
                break;

            case 'validate-promo':
                switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                    case 'POST':
                        $this->validatePromo();
                        return;

                    default:
                        break;
                }
                break;

            case 'check-availability':
                switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                    case 'POST':
                        $this->apiCheckAvailability();
                        return;

                    default:
                        break;
                }
                break;

            case 'get-booked-dates':
                switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                    case 'GET':
                        $this->apiGetBookedDates();
                        return;

                    default:
                        break;
                }
                break;

            case 'save-review':
                switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                    case 'POST':
                        $this->handleSaveReview($lang);
                        return;

                    default:
                        break;
                }
                break;

            case 'get-more-reviews':
                $this->apiGetMoreReviews();
                return;

            case 'poi':
                $poiId = (int)($params ?? 0);
                $poi = $repository->findPoiById($poiId, $lang);

                switch (true) {
                    case !$poi:
                        http_response_code(404);
                        $pageTitle = 'Point d’intérêt introuvable | Canal du Midi';
                        require_once __DIR__ . '/../Views/layout/header.php';
                        require_once __DIR__ . '/../Views/errors/404.php';
                        require_once __DIR__ . '/../Views/layout/footer.php';
                        return;

                    default:
                        $nearbyServices = $repository->getServicesNearPoi($poi->lat, $poi->lng, $lang);
                        $rawDescription = $poi->description ?? '';
                        $poiName = $this->sanitizeText($poi->name ?? 'Point d’intérêt');
                        $seo = [
                            'title' => $poiName . ' | Canal du Midi',
                            'description' => $this->buildMetaDescription($rawDescription, 160),
                            'keywords' => $poiName . ', patrimoine, Canal du Midi'
                        ];

                        require_once __DIR__ . '/../Views/layout/header.php';
                        require_once __DIR__ . '/../Views/poi_detail.php';
                        require_once __DIR__ . '/../Views/layout/footer.php';
                        return;
                }

            case 'search':
                $query = $this->sanitizeText($_GET['q'] ?? '');
                $city = $this->sanitizeText($_GET['city'] ?? '');
                $typeRaw = $_GET['type'] ?? [];
                $types = array_values(array_filter(array_map(fn($type) => $this->sanitizeText((string)$type), (array)$typeRaw)));
                $type = $types;

                $categories = $repository->getCategories();
                $categoryIds = $this->resolveCategoryIdsForSearch($types, $categories);
                $results = $repository->searchListings($query, $city, [], $categoryIds);
                $cities = $repository->getCities();


                $seo = [
                    'title' => "Résultats pour '" . $query . "' | Canal du Midi",
                    'description' => 'Découvrez les meilleurs séjours et activités correspondant a votre recherche.',
                    'keywords' => 'recherche, voyage, canal du midi'
                ];

                require_once __DIR__ . '/../Views/layout/header.php';
                require_once __DIR__ . '/../Views/search_results.php';
                require_once __DIR__ . '/../Views/layout/footer.php';
                return;

            case 'ai-analyze':
                switch ($_SERVER['REQUEST_METHOD'] ?? 'GET') {
                    case 'POST':
                        $this->handleAIRequest($lang);
                        return;

                    default:
                        break;
                }
                break;

            default:
                http_response_code(404);
                $pageTitle = '404 - Page non trouvée';
                require_once __DIR__ . '/../Views/layout/header.php';
                require_once __DIR__ . '/../Views/errors/404.php';
                require_once __DIR__ . '/../Views/layout/footer.php';
                return;
        }
    }


    private function handleBooking(string $lang) {
        $page = 'reserve';
        $seo = ['title' => 'Confirmation', 'description' => '', 'keywords' => ''];

        $serviceId = (int)($_POST['service_id'] ?? 0);
        $email = $this->sanitizeText($_POST['customer_email'] ?? '');
        $checkin = $this->sanitizeText($_POST['checkin'] ?? '');
        $checkout = $this->sanitizeText($_POST['checkout'] ?? '');
        $adults = max(1, (int)($_POST['adults'] ?? 1));
        $children = max(0, (int)($_POST['children'] ?? 0));
        $promo = $this->sanitizeText($_POST['promo_code'] ?? '');

        if ($serviceId <= 0 || $email === '' || $checkin === '' || $checkout === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données de réservation invalides.']);
            return;
        }

        $data = [
            'sid'      => $serviceId,
            'email'    => $email,
            'checkin'  => $checkin,
            'checkout' => $checkout,
            'adults'   => $adults,
            'children' => $children,
            'disabled' => isset($_POST['has_disabled']) ? 1 : 0,
            'pregnant' => isset($_POST['is_pregnant']) ? 1 : 0,
            'promo'    => $promo !== '' ? $promo : null
        ];

        $db = \App\Config\Database::getConnection();
        $sql = "INSERT INTO bookings 
                (service_id, customer_email, checkin_date, checkout_date, adults, children, has_disabled, is_pregnant, promo_code) 
                VALUES (:sid, :email, :checkin, :checkout, :adults, :children, :disabled, :pregnant, :promo)";
        
        $stmt = $db->prepare($sql);
        $success = $stmt->execute($data);

        switch (true) {
            case $this->isAjaxRequest():
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
                return;

            default:
                require_once __DIR__ . '/../Views/layout/header.php';
                require_once __DIR__ . '/../Views/booking_success.php';
                require_once __DIR__ . '/../Views/layout/footer.php';
        }
    }

    private function validatePromo() {
        $code = $this->sanitizeText($_POST['code'] ?? '');
        $db = \App\Config\Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM promo_codes WHERE code = :code AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
        $stmt->execute(['code' => $code]);
        $promo = $stmt->fetch(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        switch (true) {
            case (bool)$promo:
                echo json_encode([
                    'success' => true,
                    'type' => $promo['discount_type'],
                    'value' => (float)$promo['discount_value'],
                    'message' => 'Code promo appliqué !'
                ]);
                return;

            default:
                echo json_encode(['success' => false, 'message' => 'Code non valide o expiré']);
                return;
        }
    }

    private function apiCheckAvailability() {
        $sid = (int)($_POST['service_id'] ?? 0);
        $start = $this->sanitizeText($_POST['checkin'] ?? '');
        $end = $this->sanitizeText($_POST['checkout'] ?? '');

        $bookingRepo = new \App\Infrastructure\Persistence\MySQLBookingRepository();
        $available = $bookingRepo->isAvailable($sid, $start, $end);

        header('Content-Type: application/json');
        echo json_encode(['available' => $available]);
    }

    private function apiGetBookedDates() {
        $sid = (int)($_GET['service_id'] ?? 0);
        $db = \App\Config\Database::getConnection();

        $stmt = $db->prepare("SELECT checkin_date, checkout_date FROM bookings 
                            WHERE service_id = :sid 
                            AND status != 'cancelled' 
                            AND checkin_date IS NOT NULL 
                            AND checkout_date IS NOT NULL");
        $stmt->execute(['sid' => $sid]);
        $bookings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $occupiedDates = [];
        foreach ($bookings as $b) {
            if (empty($b['checkin_date']) || empty($b['checkout_date'])) {
                continue; 
            }

            try {
                $startDate = new \DateTime($b['checkin_date']);
                $endDate = new \DateTime($b['checkout_date']);

                $endDate->modify('+1 day'); 

                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);
                
                foreach ($period as $date) {
                    $occupiedDates[] = $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Si alguna fecha tiene un formato inválido, la saltamos silenciosamente
                continue;
            }
        }

        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json');
        echo json_encode(array_values(array_unique($occupiedDates)));
    }

    private function handleSaveReview(string $lang) {
        $repository = new \App\Infrastructure\Persistence\MySQLServiceRepository();

        $serviceId = (int)($_POST['service_id'] ?? 0);
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $customerName = $this->sanitizeText($_POST['customer_name'] ?? '');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        $comment = $this->sanitizeText($_POST['comment'] ?? '');

        if ($serviceId <= 0 || $bookingId <= 0 || $customerName === '') {
            http_response_code(400);
            echo 'Données de commentaire invalides.';
            return;
        }
        
        $success = $repository->saveReview([
            'service_id'    => $serviceId,
            'booking_id'    => $bookingId,
            'customer_name' => $customerName,
            'rating'        => $rating,
            'comment'       => $comment
        ]);

        switch (true) {
            case $success:
                header('Location: ' . BASE_URL . $this->normalizeLanguage($lang) . '/service/' . $serviceId . '?review_success=1');
                return;

            default:
                echo 'Erreur lors de la publication.';
                return;
        }
    }

    private function apiGetMoreReviews() {
        $sid = (int)($_GET['service_id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 3;
        $offset = ($page - 1) * $limit;

        $db = \App\Config\Database::getConnection();
        $countStmt = $db->prepare("SELECT COUNT(*) FROM service_reviews WHERE service_id = :sid AND is_approved = 1");
        $countStmt->execute(['sid' => $sid]);
        $totalReviews = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT * FROM service_reviews WHERE service_id = :sid AND is_approved = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':sid', $sid, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $reviewsData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();

        foreach ($reviewsData as $r) {
            $review = new \App\Domain\Models\Review($r['id'], $r['customer_name'], $r['rating'], $r['comment'], $r['created_at']);
            include __DIR__ . '/../Views/components/review_item.php';
        }

        header('Content-Type: application/json');
        echo json_encode([
            'html' => ob_get_clean(),
            'loadedCount' => count($reviewsData),
            'hasMore' => ($offset + count($reviewsData)) < $totalReviews,
            'nextPage' => $page + 1,
        ]);
    }

    private function handleAIRequest(string $lang) {
        $prompt = $this->sanitizeText($_POST['prompt'] ?? '');
        $repository = new \App\Infrastructure\Persistence\MySQLServiceRepository();
        
        $allServices = $repository->findAll($this->normalizeLanguage($lang), true);

        $aiService = new \App\Infrastructure\Services\OpenAIService();
        $result = $aiService->analyzeRequest($prompt, $allServices);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    private function normalizeLanguage(string $lang): string {
        $normalized = strtolower(trim($lang));

        if (!preg_match('/^[a-z]{2}$/', $normalized)) {
            return 'fr';
        }

        return $normalized;
    }

    private function normalizePage(string $page): string {
        $normalized = trim(strtolower($page));
        $normalized = preg_replace('/[^a-z0-9\-]/', '', $normalized);

        return $normalized ?: 'home';
    }

    private function sanitizeText(?string $value): string {
        $cleanValue = trim((string)$value);
        $cleanValue = strip_tags($cleanValue);
        $cleanValue = preg_replace('/[\x00-\x1F\x7F]+/u', '', $cleanValue);

        return $cleanValue ?? '';
    }

    private function buildMetaDescription(?string $text, int $length = 155): string {
        $cleanText = $this->sanitizeText($text);

        if ($cleanText === '') {
            return '';
        }

        if (mb_strlen($cleanText) <= $length) {
            return $cleanText;
        }

        return mb_substr($cleanText, 0, $length) . '...';
    }

    private function resolveCategoryIdsForSearch(array $selectedTypes, array $categories): array {
        $selectedSlugs = [];
        foreach ($selectedTypes as $selectedType) {
            $slug = strtolower(trim((string)$selectedType));
            if ($slug !== '') {
                $selectedSlugs[$slug] = true;
            }
        }

        if (empty($selectedSlugs)) {
            return [];
        }

        $categoryBySlug = [];
        $childrenByParent = [];
        foreach ($categories as $category) {
            $slug = strtolower(trim((string)($category['slug'] ?? '')));
            $categoryId = (int)($category['id'] ?? 0);
            $parentId = isset($category['parent_id']) && $category['parent_id'] !== null ? (int)$category['parent_id'] : 0;

            if ($slug !== '' && $categoryId > 0) {
                $categoryBySlug[$slug] = $categoryId;
            }

            if ($categoryId > 0 && $parentId > 0) {
                $childrenByParent[$parentId][] = $categoryId;
            }
        }

        $selectedIds = [];
        foreach (array_keys($selectedSlugs) as $selectedSlug) {
            if (isset($categoryBySlug[$selectedSlug])) {
                $selectedIds[] = $categoryBySlug[$selectedSlug];
            }
        }

        $selectedIds = array_values(array_unique(array_filter($selectedIds)));
        $expandedIds = $selectedIds;
        $stack = $selectedIds;

        while (!empty($stack)) {
            $currentId = array_pop($stack);
            foreach ($childrenByParent[$currentId] ?? [] as $childId) {
                if (in_array($childId, $expandedIds, true)) {
                    continue;
                }

                $expandedIds[] = $childId;
                $stack[] = $childId;
            }
        }

        return array_values(array_unique($expandedIds));
    }

    private function isAjaxRequest(): bool {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}