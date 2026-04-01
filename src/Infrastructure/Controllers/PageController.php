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
            $tours = array_filter($allServices, fn($s) => $s->type === 'tour');

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

            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/service_detail.php'; // Crearemos esta vista
            require_once __DIR__ . '/../Views/layout/footer.php';

        } else if ($page === 'reserve' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->handleBooking($lang);

            return;

        } else {
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
}