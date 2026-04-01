<?php
namespace App\Infrastructure\Controllers;

use App\Infrastructure\Persistence\MySQLServiceRepository;

class PageController {
    public function render(string $page, string $lang = 'fr', ?string $params = null) {
        // 1. Configuración de datos comunes
        $repository = new MySQLServiceRepository();
        $pageTitle = ($lang === 'fr') ? "Canal du Midi | Version 2.0" : "Canal del Midi | v2.0";

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
        } else {
            // Manejo de error 404
            http_response_code(404);
            $pageTitle = "404 - Page non trouvée";
            require_once __DIR__ . '/../Views/layout/header.php';
            require_once __DIR__ . '/../Views/errors/404.php'; // <---
            require_once __DIR__ . '/../Views/layout/footer.php';
        }
    }
}