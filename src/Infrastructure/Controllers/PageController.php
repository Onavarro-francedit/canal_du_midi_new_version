<?php
namespace App\Infrastructure\Controllers;

use App\Infrastructure\Persistence\MySQLServiceRepository;

class PageController {


    public function render(string $page, string $lang = 'fr', ?string $params = null) {
        // 1. Configuración de datos comunes
        $repository = new MySQLServiceRepository();


        // 1. VALORES POR DEFECTO (Para la Home o páginas genéricas)
        $seo = [
            'title' => "Canal du Midi | Voyages et Escapades Premium",
            'description' => "Découvrez le Canal du Midi : croisières en péniche, circuits à vélo et hôtels de charme. Planifiez votre voyage sur mesure.",
            'keywords' => "Canal du Midi, voyage, tourisme, Occitanie, péniche, vélo, hôtel"
        ];

        // 2. Lógica según la página solicitada
        if ($page === 'home') {
            // Obtenemos todos los servicios desde la DB usando el idioma detectado
            $allServices = $repository->findAll($lang);

            // Filtramos los datos para la vista home
            $destinations = array_filter($allServices, fn($s) => $s->type === 'destination');
            $tours = array_filter($allServices, fn($s) => $s->type === 'tour' || $s->type === 'boat');
            
            // 1. Obtener todas las categorías con sus contadores reales
            $allCategories = $repository->getCategoriesWithCount($lang);

            if(!empty($allCategories)){
                // 2. Mezclar el array aleatoriamente
                shuffle($allCategories);
            }

            // 3. Tomar solo los primeros 6 para la Home
            $randomCategories = array_slice($allCategories, 0, 6);

            if (empty($destinations)) {
                $destinations = array_slice($allServices, 0, 3);
            }

            $features = $repository->getActiveFeatures($lang);
            $articles = $repository->getLatestArticles($lang);

            // 3. Renderizado de Vistas (MVC)
            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/home.php';
            require_once __DIR__ . '/../Views/layout/footer.php';
            
        } else if ($page === 'service') {
            $id = (int)$params;
            $service = $repository->findById($id, $lang);
            // 2. SEO DINÁMICO PARA LA FICHA DEL CLIENTE
            $seo['title'] = $service->translations['title'] . " | Canal du Midi";
            $rawDesc = strip_tags($service->translations['description']);
            $seo['description'] = mb_substr($rawDesc, 0, 155) . "...";

            


            if (!$service) {
                $pageTitle = "Service introuvable | Canal du Midi";
                require_once __DIR__ . '/../Views/layout/header.php';
                require_once __DIR__ . '/../Views/errors/service_not_found.php'; // <---
                require_once __DIR__ . '/../Views/layout/footer.php';
                return;
            }

            $service->nearbyPOIs = $repository->getNearbyPOIs($service->lat, $service->lng, $lang);
            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/service_detail.php'; // Crearemos esta vista
            require_once __DIR__ . '/../Views/layout/footer.php';

        } else if ($page === 'reserve' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->handleBooking($lang);

            return;

        } else if ($page === 'validate-promo' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->validatePromo();
            exit;

        }else if ($page === 'check-availability' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->apiCheckAvailability();
            exit;

        }else if ($page === 'get-booked-dates' && $_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->apiGetBookedDates();
            exit;

        }else if ($page === 'save-review' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->handleSaveReview($lang);
            return;

        }else if ($page === 'get-more-reviews') {

            $this->apiGetMoreReviews();
            return;
        }elseif ($page === 'poi') {
            $poiId = (int)$params;
            $poi = $repository->findPoiById($poiId, $lang);

            if ($poi) {
                $nearbyServices = $repository->getServicesNearPoi($poi->lat, $poi->lng, $lang);
                $rawDescription = $poi->description ?? ''; 
                $seo = [
                    'title' => $poi->name . " | Canal du Midi",
                    'description' => mb_substr(strip_tags($rawDescription), 0, 160),
                    'keywords' => $poi->name . ", patrimoine, Canal du Midi"
                ];
                
                require_once __DIR__ . '/../Views/layout/header.php';
                require_once __DIR__ . '/../Views/poi_detail.php';
                require_once __DIR__ . '/../Views/layout/footer.php';
            } else {
                // Cargar 404...
            }
        }elseif ($page === 'search') {
            $query = $_GET['q'] ?? '';
            $city = $_GET['city'] ?? '';
            $type = $_GET['type'] ?? '';
            $page = 'search';

            $results = $repository->search($query, $city, $type, $lang);
            $categories = $repository->getCategoriesWithCount($lang); 

            $seo = [
                'title' => "Résultats pour '$query' | Canal du Midi",
                'description' => "Découvrez les meilleurs séjours et activités correspondant a votre recherche.",
                'keywords' => "recherche, voyage, canal du midi"
            ];

            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/search_results.php'; // Nueva vista
            require_once __DIR__ . '/../Views/layout/footer.php';

        }elseif ($page === 'ai-analyze' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $this->handleAIRequest($lang);
            return;

        }else {

            // Manejo de error 404
            http_response_code(404);
            $pageTitle = "404 - Page non trouvée";
            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/errors/404.php'; // <---
            require_once __DIR__ . '/../Views/layout/footer.php';
        }
    }


    private function handleBooking(string $lang) {
        // 1. Definir variables para el Header (Evita el Warning)
        $page = 'reserve';
        $seo = ['title' => 'Confirmation', 'description' => '', 'keywords' => ''];

        // Recoger todos los campos nuevos
        $data = [
            'sid'      => (int)$_POST['service_id'],
            'email'    => $_POST['customer_email'] ?? '',
            'checkin'  => $_POST['checkin'] ?? null,
            'checkout' => $_POST['checkout'] ?? null,
            'adults'   => (int)($_POST['adults'] ?? 1),
            'children' => (int)($_POST['children'] ?? 0),
            'disabled' => isset($_POST['has_disabled']) ? 1 : 0,
            'pregnant' => isset($_POST['is_pregnant']) ? 1 : 0,
            'promo'    => $_POST['promo_code'] ?? null
        ];

        $db = \App\Config\Database::getConnection();
        $sql = "INSERT INTO bookings 
                (service_id, customer_email, checkin_date, checkout_date, adults, children, has_disabled, is_pregnant, promo_code) 
                VALUES (:sid, :email, :checkin, :checkout, :adults, :children, :disabled, :pregnant, :promo)";
        
        $stmt = $db->prepare($sql);
        $success = $stmt->execute($data);

        // 4. RESPUESTA PARA AJAX (Importante)
        // Si la petición viene por JS, devolvemos JSON en lugar de cargar el header/footer
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Es AJAX: Solo devolvemos JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        } else {
            // No es AJAX (Fallback): Cargamos la página completa de éxito
            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/booking_success.php';
            require_once __DIR__ . '/../Views/layout/footer.php';
        }
    }

    private function validatePromo() {
        $code = $_POST['code'] ?? '';
        $db = \App\Config\Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM promo_codes WHERE code = :code AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
        $stmt->execute(['code' => $code]);
        $promo = $stmt->fetch(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        if ($promo) {
            echo json_encode([
                'success' => true,
                'type' => $promo['discount_type'],
                'value' => (float)$promo['discount_value'],
                'message' => 'Code promo appliqué !'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Code non valide o expiré']);
        }
    }

    private function apiCheckAvailability() {
        $sid = (int)$_POST['service_id'];
        $start = $_POST['checkin'];
        $end = $_POST['checkout'];

        $bookingRepo = new \App\Infrastructure\Persistence\MySQLBookingRepository();
        $available = $bookingRepo->isAvailable($sid, $start, $end);

        header('Content-Type: application/json');
        echo json_encode(['available' => $available]);
    }

    private function apiGetBookedDates() {
        $sid = (int)($_GET['service_id'] ?? 0);
        $db = \App\Config\Database::getConnection();
        
        // 1. Añadimos IS NOT NULL en el SQL para limpiar desde el origen
        $stmt = $db->prepare("SELECT checkin_date, checkout_date FROM bookings 
                            WHERE service_id = :sid 
                            AND status != 'cancelled' 
                            AND checkin_date IS NOT NULL 
                            AND checkout_date IS NOT NULL");
        $stmt->execute(['sid' => $sid]);
        $bookings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $occupiedDates = [];
        foreach ($bookings as $b) {
            // 2. Doble verificación en PHP para evitar el Deprecated Warning
            if (empty($b['checkin_date']) || empty($b['checkout_date'])) {
                continue; 
            }

            try {
                $startDate = new \DateTime($b['checkin_date']);
                $endDate = new \DateTime($b['checkout_date']);

                // El intervalo es de 1 día (P1D)
                // Añadimos 1 día al final porque DatePeriod no incluye el último día por defecto
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

        // Limpiar el buffer de salida por si hubo algún eco previo que ensucie el JSON
        if (ob_get_length()) ob_clean();

        header('Content-Type: application/json');
        echo json_encode(array_values(array_unique($occupiedDates))); // Reindexar evita que JSON salga como objeto
    }

    private function handleSaveReview(string $lang) {
        $repository = new \App\Infrastructure\Persistence\MySQLServiceRepository();
        
        $success = $repository->saveReview([
            'service_id'    => (int)$_POST['service_id'],
            'booking_id'    => (int)$_POST['booking_id'],
            'customer_name' => $_POST['customer_name'],
            'rating'        => (int)$_POST['rating'],
            'comment'       => $_POST['comment']
        ]);

        if ($success) {
            // Redirigir a una página de agradecimiento o a la ficha del hotel
            header("Location: " . BASE_URL . $lang . "/service/" . $_POST['service_id'] . "?review_success=1");
        } else {
            echo "Erreur lors de la publication.";
        }
    }

    private function apiGetMoreReviews() {
        $sid = (int)($_GET['service_id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 3;
        $offset = $page * $limit;

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
            'total' => $totalReviews,
        ]);
    }

    private function handleAIRequest(string $lang) {
        $prompt = $_POST['prompt'] ?? '';
        $repository = new \App\Infrastructure\Persistence\MySQLServiceRepository();
        
        // Es vital obtener todos los detalles de los servicios para que la IA los "conozca"
        $allServices = $repository->findAll($lang, true); // Pasar true para cargar amenities y features

        $aiService = new \App\Infrastructure\Services\OpenAIService(); // <--- Aquí el cambio
        $result = $aiService->analyzeRequest($prompt, $allServices);

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}