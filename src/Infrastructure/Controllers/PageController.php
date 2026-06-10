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
        $relevantServices = $this->rankServicesForAI($prompt, $allServices);

        $aiService = new \App\Infrastructure\Services\OpenAIService();
        $result = $aiService->analyzeRequest($prompt, $relevantServices);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    private function rankServicesForAI(string $prompt, array $services): array {
        if (empty($services)) {
            return [];
        }

        $normalizedPrompt = $this->normalizeAiText($prompt);
        $promptTerms = $this->expandAiPromptTerms($normalizedPrompt);
        $intent = $this->detectAiIntent($normalizedPrompt);

        if ($intent !== null) {
            $filteredServices = array_values(array_filter($services, function ($service) use ($intent): bool {
                return $this->serviceMatchesAiIntent($service, $intent);
            }));

            if (!empty($filteredServices)) {
                $services = $filteredServices;
            }
        }

        if (empty($promptTerms)) {
            return array_slice($services, 0, min(20, count($services)));
        }

        $scoredServices = [];
        foreach ($services as $service) {
            $score = $this->scoreAiService($service, $promptTerms);
            $scoredServices[] = ['service' => $service, 'score' => $score];
        }

        usort($scoredServices, function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                $leftTitle = strtolower((string)($left['service']->translations['title'] ?? ''));
                $rightTitle = strtolower((string)($right['service']->translations['title'] ?? ''));
                return $leftTitle <=> $rightTitle;
            }

            return $right['score'] <=> $left['score'];
        });

        $topServices = array_slice(array_column($scoredServices, 'service'), 0, 20);

        return array_values(array_filter($topServices, function ($service) {
            return $service !== null;
        }));
    }

    private function detectAiIntent(string $prompt): ?string {
        // ── BOAT sub-intents (existing logic, untouched) ────────────────────
        if (str_contains($prompt, 'louer') || str_contains($prompt, 'location') || str_contains($prompt, 'rent')) {
            if (str_contains($prompt, 'bateau') || str_contains($prompt, 'bateaux') || str_contains($prompt, 'boat') || str_contains($prompt, 'peniche') || str_contains($prompt, 'péniche')) {
                return 'boat_rental';
            }
        }

        if (str_contains($prompt, 'croisiere') || str_contains($prompt, 'croisieres') || str_contains($prompt, 'croisière') || str_contains($prompt, 'croisières')) {
            return 'boat_cruise';
        }

        $mentionsBoatContext = str_contains($prompt, 'bateau') || str_contains($prompt, 'bateaux') || str_contains($prompt, 'boat') || str_contains($prompt, 'peniche') || str_contains($prompt, 'péniche') || str_contains($prompt, 'navigation') || str_contains($prompt, 'fluvial') || str_contains($prompt, 'croisiere') || str_contains($prompt, 'croisière');
        if (str_contains($prompt, 'canal') && $mentionsBoatContext) {
            return 'boat_navigation';
        }

        // ── RESTAURANT sub-intents ───────────────────────────────────────────
        // Gastronomic / fine dining
        if (str_contains($prompt, 'gastronomie') || str_contains($prompt, 'gastronomique') || str_contains($prompt, 'chef') || str_contains($prompt, 'etoile') || str_contains($prompt, 'étoile') || str_contains($prompt, 'bistronomie')) {
            return 'restaurant_gastro';
        }
        // Local / traditional cuisine
        if (str_contains($prompt, 'terroir') || str_contains($prompt, 'regional') || str_contains($prompt, 'régional') || str_contains($prompt, 'local') || str_contains($prompt, 'occitan') || str_contains($prompt, 'traditionnel')) {
            if (str_contains($prompt, 'manger') || str_contains($prompt, 'restaurant') || str_contains($prompt, 'table') || str_contains($prompt, 'cuisine') || str_contains($prompt, 'repas')) {
                return 'restaurant_local';
            }
        }
        // Waterside / terrace dining
        if ((str_contains($prompt, 'bord') || str_contains($prompt, 'terrasse') || str_contains($prompt, 'vue')) && (str_contains($prompt, 'manger') || str_contains($prompt, 'restaurant') || str_contains($prompt, 'dejeuner') || str_contains($prompt, 'déjeuner') || str_contains($prompt, 'diner') || str_contains($prompt, 'dîner'))) {
            return 'restaurant_terrasse';
        }

        // ── HOTEL sub-intents ────────────────────────────────────────────────
        // Luxury / charme
        if ((str_contains($prompt, 'luxe') || str_contains($prompt, 'luxueux') || str_contains($prompt, 'prestige') || str_contains($prompt, 'charme') || str_contains($prompt, 'boutique') || str_contains($prompt, 'etoile') || str_contains($prompt, 'étoile')) && (str_contains($prompt, 'hotel') || str_contains($prompt, 'hébergement') || str_contains($prompt, 'hebergement') || str_contains($prompt, 'chambre') || str_contains($prompt, 'nuit') || str_contains($prompt, 'dormir') || str_contains($prompt, 'sejour') || str_contains($prompt, 'séjour'))) {
            return 'hotel_luxe';
        }
        // Budget / affordable lodging
        if ((str_contains($prompt, 'pas cher') || str_contains($prompt, 'economique') || str_contains($prompt, 'économique') || str_contains($prompt, 'abordable') || str_contains($prompt, 'budget')) && (str_contains($prompt, 'hotel') || str_contains($prompt, 'chambre') || str_contains($prompt, 'nuit') || str_contains($prompt, 'dormir') || str_contains($prompt, 'hebergement') || str_contains($prompt, 'hébergement'))) {
            return 'hotel_budget';
        }
        // Gîte / rural / maison d'hôtes
        if (str_contains($prompt, 'gite') || str_contains($prompt, 'gîte') || str_contains($prompt, 'maison') || str_contains($prompt, 'chambre d\'hote') || str_contains($prompt, 'chambre d\'hôte') || str_contains($prompt, 'rural') || str_contains($prompt, 'campagne')) {
            if (str_contains($prompt, 'dormir') || str_contains($prompt, 'nuit') || str_contains($prompt, 'sejour') || str_contains($prompt, 'séjour') || str_contains($prompt, 'hebergement') || str_contains($prompt, 'hébergement') || str_contains($prompt, 'gite') || str_contains($prompt, 'gîte')) {
                return 'hotel_gite';
            }
        }

        // ── BIKE sub-intents ─────────────────────────────────────────────────
        // Bike rental
        if ((str_contains($prompt, 'louer') || str_contains($prompt, 'location') || str_contains($prompt, 'rent')) && (str_contains($prompt, 'velo') || str_contains($prompt, 'vélo') || str_contains($prompt, 'bike') || str_contains($prompt, 'bicyclette') || str_contains($prompt, 'bicycle'))) {
            return 'bike_rental';
        }
        // Guided bike tour / circuit
        if ((str_contains($prompt, 'circuit') || str_contains($prompt, 'tour') || str_contains($prompt, 'balade') || str_contains($prompt, 'itineraire') || str_contains($prompt, 'itinéraire') || str_contains($prompt, 'guide')) && (str_contains($prompt, 'velo') || str_contains($prompt, 'vélo') || str_contains($prompt, 'bike') || str_contains($prompt, 'cyclo') || str_contains($prompt, 'cyclotourisme'))) {
            return 'bike_circuit';
        }
        // Electric bike
        if ((str_contains($prompt, 'electrique') || str_contains($prompt, 'électrique') || str_contains($prompt, 'vae') || str_contains($prompt, 'e-bike')) && (str_contains($prompt, 'velo') || str_contains($prompt, 'vélo') || str_contains($prompt, 'bike'))) {
            return 'bike_electric';
        }

        // ── CAMPING sub-intents ──────────────────────────────────────────────
        // Glamping / premium camping
        if (str_contains($prompt, 'glamping') || str_contains($prompt, 'lodgetente') || str_contains($prompt, 'lodge') || str_contains($prompt, 'cabane') || str_contains($prompt, 'insolite') || str_contains($prompt, 'treehouse') || str_contains($prompt, 'bulle')) {
            return 'camping_glamping';
        }
        // Mobile home / caravane
        if (str_contains($prompt, 'mobil') || str_contains($prompt, 'mobile home') || str_contains($prompt, 'mobilhome') || str_contains($prompt, 'caravane') || str_contains($prompt, 'caravan')) {
            return 'camping_mobilhome';
        }
        // Tent / emplacement
        if ((str_contains($prompt, 'tente') || str_contains($prompt, 'emplacement') || str_contains($prompt, 'pitch')) && (str_contains($prompt, 'camping') || str_contains($prompt, 'camp') || str_contains($prompt, 'bivouac'))) {
            return 'camping_tente';
        }

        // ── Generic fallback intent map ──────────────────────────────────────
        $intentMap = [
            'boat'       => ['bateau', 'bateaux', 'boat'],
            'restaurant' => ['restaurant', 'restauration', 'manger', 'gastronomie', 'table', 'cuisine', 'repas', 'dejeuner', 'diner'],
            'hotel'      => ['hotel', 'hebergement', 'hébergement', 'hostel', 'guesthouse', 'gite', 'gîte', 'lodging', 'chambre', 'dormir', 'nuit'],
            'bike'       => ['velo', 'vélo', 'bike', 'bicycle', 'cyclo', 'cycling', 'cyclotourisme', 'bicyclette', 'deux roues'],
            'activity'   => ['activite', 'activité', 'loisir', 'loisirs', 'tour', 'visite', 'experience', 'expérience'],
            'camping'    => ['camping', 'caravane', 'mobil home', 'emplacement', 'tente', 'bivouac', 'glamping'],
            'transport'  => ['transport', 'navette', 'bus', 'train', 'taxi'],
        ];

        foreach ($intentMap as $intent => $needles) {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($prompt, $needle)) {
                    return $intent;
                }
            }
        }

        return null;
    }

    private function serviceMatchesAiIntent(object $service, string $intent): bool {
        $serviceText = $this->normalizeAiText(implode(' ', array_filter([
            (string)($service->translations['title'] ?? ''),
            (string)($service->translations['description'] ?? ''),
            (string)($service->translations['tag'] ?? ''),
            (string)($service->type ?? ''),
            (string)($service->label ?? ''),
            (string)($service->zone ?? ''),
            (string)($service->contact['ville'] ?? ''),
            (string)($service->contact['address'] ?? ''),
            (string)($service->getFullAddress() ?? ''),
            implode(' ', array_map(fn($category) => (string)($category['name'] ?? ''), (array)$service->getActiveCategories())),
            implode(' ', array_map('strval', (array)$service->getActiveEquipments())),
            implode(' ', array_map(fn($amenity) => (string)($amenity['slug'] ?? ''), (array)($service->amenities ?? []))),
        ])));

        $intentNeedles = [
            // ── Boat (existing, untouched) ───────────────────────────────────
            'boat_rental'     => ['location', 'louer', 'rent', 'bateau', 'bateaux', 'boat', 'peniche', 'peniches'],
            'boat_cruise'     => ['croisiere', 'croisieres', 'cruise', 'bateau', 'bateaux', 'boat', 'peniche', 'peniches'],
            'boat_navigation' => ['navigation', 'fluvial', 'canal', 'bateau', 'bateaux', 'boat', 'peniche', 'peniches'],
            'boat'            => ['bateau', 'bateaux', 'boat', 'peniche', 'peniches', 'croisiere', 'croisieres', 'navigation', 'water', 'canal'],

            // ── Restaurant sub-intents ───────────────────────────────────────
            'restaurant_gastro'   => ['restaurant', 'gastronomie', 'gastronomique', 'chef', 'etoile', 'bistronomie', 'table', 'cuisine', 'gourmet', 'gourmand', 'menu', 'degustation'],
            'restaurant_local'    => ['restaurant', 'terroir', 'regional', 'occitan', 'traditionnel', 'local', 'cuisine', 'table', 'repas', 'bistrot', 'auberge'],
            'restaurant_terrasse' => ['restaurant', 'terrasse', 'bord', 'vue', 'exterieur', 'table', 'dejeuner', 'diner', 'canal', 'eau'],
            'restaurant'          => ['restaurant', 'restauration', 'manger', 'gastronomie', 'table', 'cuisine', 'repas', 'bistrot', 'brasserie', 'auberge', 'menu', 'carte'],

            // ── Hotel sub-intents ────────────────────────────────────────────
            'hotel_luxe'   => ['hotel', 'hebergement', 'chambre', 'luxe', 'luxueux', 'charme', 'prestige', 'boutique', 'etoile', 'suite', 'spa', 'piscine', 'bien-etre', 'premium'],
            'hotel_budget' => ['hotel', 'hebergement', 'chambre', 'auberge', 'hostel', 'economique', 'budget', 'simple', 'pension', 'logement'],
            'hotel_gite'   => ['gite', 'gites', 'chambre d hote', 'maison', 'ferme', 'rural', 'campagne', 'hebergement', 'domaine', 'propriete', 'villa', 'bastide'],
            'hotel'        => ['hotel', 'hebergement', 'hostel', 'guesthouse', 'gite', 'gites', 'lodging', 'chambre', 'dormir', 'nuit', 'sejour', 'pension', 'residence', 'appart', 'auberge'],

            // ── Bike sub-intents ─────────────────────────────────────────────
            'bike_rental'   => ['location', 'louer', 'rent', 'velo', 'velos', 'bike', 'bicycle', 'bicyclette', 'cyclo', 'deux roues'],
            'bike_circuit'  => ['circuit', 'itineraire', 'balade', 'tour', 'velo', 'bike', 'cyclo', 'cyclotourisme', 'route', 'piste', 'voie verte', 'canal'],
            'bike_electric' => ['velo electrique', 'vae', 'e-bike', 'electrique', 'assistance', 'velo', 'bike', 'bicyclette'],
            'bike'          => ['velo', 'velos', 'bike', 'bicycle', 'bicyclette', 'cyclo', 'cycling', 'cyclotourisme', 'deux roues', 'cyclable', 'voie verte', 'piste cyclable'],

            // ── Camping sub-intents ──────────────────────────────────────────
            'camping_glamping'  => ['glamping', 'lodge', 'cabane', 'insolite', 'bulle', 'treehouse', 'tipi', 'yourte', 'tente lodge', 'cabane arbre', 'hebergement insolite'],
            'camping_mobilhome' => ['mobil home', 'mobilhome', 'mobile home', 'caravane', 'caravan', 'chalet', 'bungalow', 'camping'],
            'camping_tente'     => ['camping', 'camp', 'tente', 'emplacement', 'pitch', 'bivouac', 'toile', 'nature', 'plein air'],
            'camping'           => ['camping', 'campings', 'caravane', 'mobil home', 'mobilhome', 'emplacement', 'tente', 'bivouac', 'glamping', 'lodge', 'cabane', 'plein air', 'nature'],

            // ── Other (existing, untouched) ──────────────────────────────────
            'activity'  => ['activite', 'activite', 'loisir', 'loisirs', 'tour', 'visite', 'experience', 'experience'],
            'transport' => ['transport', 'navette', 'bus', 'train', 'taxi'],
        ];

        $needles = $intentNeedles[$intent] ?? [];
        if (empty($needles)) {
            return true;
        }

        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($serviceText, $needle)) {
                return true;
            }
        }

        // Hybrid services (restaurant + hotel) match restaurant and hotel intents
        if (method_exists($service, 'isHybrid') && $service->isHybrid()) {
            if (in_array($intent, ['boat', 'boat_rental', 'boat_cruise', 'boat_navigation'], true)) {
                return true;
            }
            if (in_array($intent, ['restaurant', 'restaurant_gastro', 'restaurant_local', 'restaurant_terrasse'], true)) {
                return true;
            }
            if (in_array($intent, ['hotel', 'hotel_luxe', 'hotel_budget', 'hotel_gite'], true)) {
                return true;
            }
        }

        return false;
    }

    private function scoreAiService(object $service, array $promptTerms): int {
        $textParts = [
            strtolower((string)($service->translations['title'] ?? '')),
            strtolower((string)($service->translations['description'] ?? '')),
            strtolower((string)($service->translations['tag'] ?? '')),
            strtolower((string)($service->type ?? '')),
            strtolower((string)($service->label ?? '')),
            strtolower((string)($service->zone ?? '')),
            strtolower((string)($service->contact['ville'] ?? '')),
            strtolower((string)($service->contact['address'] ?? '')),
            strtolower((string)($service->getFullAddress() ?? '')),
        ];

        foreach ($service->getActiveCategories() as $category) {
            if (!is_array($category)) {
                continue;
            }

            $textParts[] = strtolower((string)($category['name'] ?? ''));
            $textParts[] = strtolower((string)($category['slug'] ?? ''));
        }

        foreach ($service->getActiveEquipments() as $equipment) {
            $textParts[] = strtolower((string)$equipment);
        }

        foreach ((array)($service->amenities ?? []) as $amenity) {
            if (is_array($amenity)) {
                $textParts[] = strtolower((string)($amenity['slug'] ?? ''));
                $textParts[] = strtolower((string)($amenity['name'] ?? ''));
            }
        }

        $haystack = implode(' | ', array_filter($textParts));
        $score = 0;

        // ── BOAT domain signals (existing, untouched) ────────────────────────
        $hasBoatSignals = str_contains($haystack, 'bateau')
            || str_contains($haystack, 'bateaux')
            || str_contains($haystack, 'boat')
            || str_contains($haystack, 'peniche')
            || str_contains($haystack, 'péniche')
            || str_contains($haystack, 'croisiere')
            || str_contains($haystack, 'croisière')
            || str_contains($haystack, 'navigation')
            || str_contains($haystack, 'fluvial')
            || str_contains($haystack, 'canal');

        $hasRentalSignals = str_contains($haystack, 'location')
            || str_contains($haystack, 'louer')
            || str_contains($haystack, 'rent');

        if ($hasBoatSignals) {
            $score += 2;
        }

        if ($hasRentalSignals && $hasBoatSignals) {
            $score += 4;
        }

        if ($service->isHybrid() && $hasBoatSignals) {
            $score += 2;
        }

        // ── RESTAURANT domain signals ────────────────────────────────────────
        $hasRestaurantSignals = str_contains($haystack, 'restaurant')
            || str_contains($haystack, 'restauration')
            || str_contains($haystack, 'brasserie')
            || str_contains($haystack, 'bistrot')
            || str_contains($haystack, 'auberge')
            || str_contains($haystack, 'table')
            || str_contains($haystack, 'cuisine')
            || str_contains($haystack, 'menu')
            || str_contains($haystack, 'gastronomie')
            || str_contains($haystack, 'gastronomique');

        $hasGastroSignals = str_contains($haystack, 'gastronomie')
            || str_contains($haystack, 'gastronomique')
            || str_contains($haystack, 'chef')
            || str_contains($haystack, 'etoile')
            || str_contains($haystack, 'gourmet')
            || str_contains($haystack, 'degustation')
            || str_contains($haystack, 'bistronomie');

        if ($hasRestaurantSignals) {
            $score += 2;
        }

        if ($hasGastroSignals && $hasRestaurantSignals) {
            $score += 4;
        }

        if ($service->isHybrid() && $hasRestaurantSignals) {
            $score += 3;
        }

        // ── HOTEL domain signals ─────────────────────────────────────────────
        $hasHotelSignals = str_contains($haystack, 'hotel')
            || str_contains($haystack, 'hebergement')
            || str_contains($haystack, 'chambre')
            || str_contains($haystack, 'gite')
            || str_contains($haystack, 'hostel')
            || str_contains($haystack, 'guesthouse')
            || str_contains($haystack, 'residence')
            || str_contains($haystack, 'pension')
            || str_contains($haystack, 'auberge')
            || str_contains($haystack, 'logement');

        $hasLuxeSignals = str_contains($haystack, 'luxe')
            || str_contains($haystack, 'luxueux')
            || str_contains($haystack, 'charme')
            || str_contains($haystack, 'prestige')
            || str_contains($haystack, 'boutique')
            || str_contains($haystack, 'spa')
            || str_contains($haystack, 'piscine')
            || str_contains($haystack, 'suite')
            || str_contains($haystack, 'premium');

        $hasGiteSignals = str_contains($haystack, 'gite')
            || str_contains($haystack, 'gites')
            || str_contains($haystack, 'rural')
            || str_contains($haystack, 'campagne')
            || str_contains($haystack, 'ferme')
            || str_contains($haystack, 'domaine')
            || str_contains($haystack, 'bastide')
            || str_contains($haystack, 'villa');

        if ($hasHotelSignals) {
            $score += 2;
        }

        if ($hasLuxeSignals && $hasHotelSignals) {
            $score += 4;
        }

        if ($hasGiteSignals && $hasHotelSignals) {
            $score += 3;
        }

        if ($service->isHybrid() && $hasHotelSignals) {
            $score += 2;
        }

        // ── BIKE domain signals ──────────────────────────────────────────────
        $hasBikeSignals = str_contains($haystack, 'velo')
            || str_contains($haystack, 'velos')
            || str_contains($haystack, 'bike')
            || str_contains($haystack, 'bicycle')
            || str_contains($haystack, 'bicyclette')
            || str_contains($haystack, 'cyclo')
            || str_contains($haystack, 'cyclotourisme')
            || str_contains($haystack, 'cycling')
            || str_contains($haystack, 'cyclable')
            || str_contains($haystack, 'voie verte')
            || str_contains($haystack, 'piste cyclable')
            || str_contains($haystack, 'deux roues');

        $hasBikeRentalSignals = $hasRentalSignals && $hasBikeSignals;

        $hasElectricBikeSignals = (str_contains($haystack, 'electrique') || str_contains($haystack, 'vae') || str_contains($haystack, 'e-bike') || str_contains($haystack, 'assistance'))
            && $hasBikeSignals;

        if ($hasBikeSignals) {
            $score += 2;
        }

        if ($hasBikeRentalSignals) {
            $score += 4;
        }

        if ($hasElectricBikeSignals) {
            $score += 3;
        }

        // ── CAMPING domain signals ───────────────────────────────────────────
        $hasCampingSignals = str_contains($haystack, 'camping')
            || str_contains($haystack, 'campings')
            || str_contains($haystack, 'caravane')
            || str_contains($haystack, 'tente')
            || str_contains($haystack, 'emplacement')
            || str_contains($haystack, 'bivouac')
            || str_contains($haystack, 'plein air')
            || str_contains($haystack, 'nature');

        $hasGlampingSignals = str_contains($haystack, 'glamping')
            || str_contains($haystack, 'lodge')
            || str_contains($haystack, 'cabane')
            || str_contains($haystack, 'insolite')
            || str_contains($haystack, 'bulle')
            || str_contains($haystack, 'tipi')
            || str_contains($haystack, 'yourte')
            || str_contains($haystack, 'treehouse');

        $hasMobilhomeSignals = str_contains($haystack, 'mobil home')
            || str_contains($haystack, 'mobilhome')
            || str_contains($haystack, 'mobile home')
            || str_contains($haystack, 'bungalow')
            || str_contains($haystack, 'chalet');

        if ($hasCampingSignals) {
            $score += 2;
        }

        if ($hasGlampingSignals && $hasCampingSignals) {
            $score += 4;
        }

        if ($hasMobilhomeSignals && $hasCampingSignals) {
            $score += 3;
        }

        // ── Per-term scoring (existing logic, untouched) ─────────────────────
        foreach ($promptTerms as $term) {
            if ($term === '') {
                continue;
            }

            if (str_contains($haystack, $term)) {
                $score += 3;
            }

            foreach ($this->aiSynonymsForTerm($term) as $synonym) {
                if ($synonym !== '' && str_contains($haystack, $synonym)) {
                    $score += 2;
                }
            }
        }

        if ($service->isHybrid()) {
            if (str_contains($haystack, 'restaurant') || str_contains($haystack, 'restauration')) {
                $score += 4;
            }
        }

        if ((float)$service->price > 0) {
            $score += 1;
        }

        return $score;
    }

    private function normalizeAiText(string $text): string {
        $text = strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9\s]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string)$text);
    }

    private function expandAiPromptTerms(string $prompt): array {
        if ($prompt === '') {
            return [];
        }

        $terms = preg_split('/\s+/', $prompt) ?: [];
        $expanded = [];

        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '') {
                continue;
            }

            $expanded[] = $term;
            foreach ($this->aiSynonymsForTerm($term) as $synonym) {
                $expanded[] = $synonym;
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    private function aiSynonymsForTerm(string $term): array {
        $synonyms = [
            // Boat (existing, untouched)
            'bateau'     => ['boat', 'bateaux', 'peniche', 'peniches', 'croisiere', 'croisieres', 'navigation', 'canal'],
            'bateaux'    => ['boat', 'bateau', 'peniche', 'peniches', 'croisiere', 'croisieres', 'navigation', 'canal'],
            'boat'       => ['bateau', 'bateaux', 'peniche', 'peniches', 'croisiere', 'croisieres', 'navigation', 'canal'],
            'peniche'    => ['bateau', 'bateaux', 'boat', 'peniches', 'croisiere', 'croisieres', 'navigation', 'canal'],
            'peniches'   => ['bateau', 'bateaux', 'boat', 'peniche', 'croisiere', 'croisieres', 'navigation', 'canal'],
            'croisiere'  => ['bateau', 'bateaux', 'boat', 'navigation', 'fluvial', 'canal', 'croisieres'],
            'navigation' => ['bateau', 'bateaux', 'boat', 'peniche', 'fluvial', 'canal', 'croisiere'],
            'canal'      => ['bateau', 'navigation', 'fluvial', 'peniche', 'croisiere'],

            // Restaurant
            'restaurant'   => ['restauration', 'manger', 'table', 'cuisine', 'repas', 'bistrot', 'brasserie', 'auberge', 'gastronomie', 'menu'],
            'restauration' => ['restaurant', 'manger', 'table', 'cuisine', 'repas', 'gastronomie', 'menu'],
            'manger'       => ['restaurant', 'restauration', 'table', 'cuisine', 'repas', 'dejeuner', 'diner'],
            'gastronomie'  => ['restaurant', 'gastronomique', 'chef', 'etoile', 'gourmet', 'degustation', 'bistronomie'],
            'table'        => ['restaurant', 'cuisine', 'repas', 'gastronomie', 'menu', 'carte'],
            'cuisine'      => ['restaurant', 'restauration', 'table', 'gastronomie', 'terroir', 'chef'],
            'terroir'      => ['cuisine', 'local', 'regional', 'traditionnel', 'occitan', 'restaurant', 'table'],
            'bistrot'      => ['restaurant', 'brasserie', 'auberge', 'cuisine', 'table', 'repas'],
            'brasserie'    => ['restaurant', 'bistrot', 'cuisine', 'table', 'repas'],
            'auberge'      => ['restaurant', 'bistrot', 'cuisine', 'table', 'hebergement'],
            'terrasse'     => ['restaurant', 'table', 'exterieur', 'vue', 'bord', 'dejeuner', 'diner'],
            'gourmet'      => ['gastronomie', 'gastronomique', 'chef', 'restaurant', 'degustation'],

            // Hotel / hébergement
            'hotel'        => ['hebergement', 'chambre', 'hostel', 'guesthouse', 'gite', 'lodging', 'pension', 'residence', 'logement', 'dormir', 'nuit'],
            'hebergement'  => ['hotel', 'chambre', 'hostel', 'guesthouse', 'gite', 'lodging', 'pension', 'residence', 'logement'],
            'chambre'      => ['hotel', 'hebergement', 'gite', 'guesthouse', 'nuit', 'dormir', 'sejour'],
            'dormir'       => ['hotel', 'hebergement', 'chambre', 'gite', 'nuit', 'sejour', 'logement'],
            'nuit'         => ['hotel', 'hebergement', 'chambre', 'gite', 'dormir', 'sejour'],
            'sejour'       => ['hotel', 'hebergement', 'chambre', 'gite', 'dormir', 'nuit', 'logement'],
            'gite'         => ['hotel', 'hebergement', 'chambre', 'guesthouse', 'rural', 'campagne', 'ferme', 'domaine'],
            'gites'        => ['gite', 'hotel', 'hebergement', 'rural', 'campagne', 'ferme'],
            'charme'       => ['hotel', 'hebergement', 'luxe', 'prestige', 'boutique', 'etoile', 'gite'],
            'luxe'         => ['hotel', 'hebergement', 'prestige', 'charme', 'boutique', 'spa', 'piscine', 'suite', 'premium'],
            'rural'        => ['gite', 'campagne', 'ferme', 'domaine', 'hebergement', 'nature'],
            'campagne'     => ['gite', 'rural', 'ferme', 'domaine', 'hebergement', 'nature', 'bastide'],
            'hostel'       => ['hotel', 'hebergement', 'auberge', 'logement', 'chambre', 'pension'],

            // Bike
            'velo'           => ['bike', 'bicycle', 'bicyclette', 'cyclo', 'cyclotourisme', 'cycling', 'cyclable', 'deux roues', 'piste'],
            'velos'          => ['velo', 'bike', 'bicycle', 'bicyclette', 'cyclo', 'cyclotourisme'],
            'bike'           => ['velo', 'velos', 'bicycle', 'bicyclette', 'cyclo', 'cycling', 'cyclotourisme', 'cyclable'],
            'bicycle'        => ['velo', 'bike', 'bicyclette', 'cyclo', 'cycling'],
            'bicyclette'     => ['velo', 'bike', 'bicycle', 'cyclo'],
            'cyclo'          => ['velo', 'bike', 'bicycle', 'cyclotourisme', 'cycling', 'cyclable'],
            'cyclotourisme'  => ['velo', 'bike', 'bicycle', 'cyclo', 'cycling', 'circuit', 'itineraire'],
            'cycling'        => ['velo', 'bike', 'bicycle', 'cyclo', 'cyclotourisme', 'cyclable'],
            'cyclable'       => ['velo', 'bike', 'cyclotourisme', 'voie verte', 'piste'],
            'electrique'     => ['velo electrique', 'vae', 'e-bike', 'assistance', 'velo', 'bike'],
            'vae'            => ['velo electrique', 'electrique', 'e-bike', 'assistance', 'velo'],
            'circuit'        => ['itineraire', 'balade', 'tour', 'route', 'velo', 'cyclo', 'cyclotourisme'],
            'balade'         => ['circuit', 'itineraire', 'tour', 'promenade', 'velo', 'cyclo'],
            'itineraire'     => ['circuit', 'balade', 'tour', 'route', 'velo', 'cyclo'],

            // Camping
            'camping'     => ['campings', 'caravane', 'tente', 'emplacement', 'bivouac', 'plein air', 'nature', 'glamping', 'lodge', 'mobil home'],
            'campings'    => ['camping', 'caravane', 'tente', 'emplacement', 'bivouac', 'plein air'],
            'caravane'    => ['camping', 'mobil home', 'mobilhome', 'emplacement', 'caravan'],
            'tente'       => ['camping', 'emplacement', 'bivouac', 'plein air', 'nature'],
            'emplacement' => ['camping', 'tente', 'caravane', 'pitch', 'bivouac'],
            'bivouac'     => ['camping', 'tente', 'emplacement', 'plein air', 'nature'],
            'glamping'    => ['lodge', 'cabane', 'insolite', 'bulle', 'tipi', 'yourte', 'treehouse', 'camping'],
            'lodge'       => ['glamping', 'cabane', 'insolite', 'tente lodge', 'camping'],
            'cabane'      => ['glamping', 'lodge', 'insolite', 'treehouse', 'arbre', 'camping'],
            'insolite'    => ['glamping', 'lodge', 'cabane', 'bulle', 'tipi', 'yourte', 'treehouse'],
            'mobil'       => ['mobil home', 'mobilhome', 'caravane', 'bungalow', 'chalet', 'camping'],
            'mobilhome'   => ['mobil home', 'caravane', 'bungalow', 'chalet', 'camping'],
            'bungalow'    => ['mobil home', 'mobilhome', 'chalet', 'camping', 'hebergement'],
            'chalet'      => ['mobil home', 'mobilhome', 'bungalow', 'camping', 'hebergement'],
        ];

        return $synonyms[$term] ?? [];
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