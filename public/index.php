<?php
// public/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../autoload.php';

use App\Infrastructure\Persistence\MySQLServiceRepository;

// 1. Configuración inicial
$lang = $_GET['lang'] ?? 'fr'; // El idioma viene de la URL
$pageTitle = "Canal du Midi | Version 2.0";

// 2. Obtención de Datos (Capa de Aplicación / Infraestructura)
$repository = new MySQLServiceRepository();
$allServices = $repository->findAll($lang);

// Filtramos los datos para la vista
$destinations = array_filter($allServices, fn($s) => $s->type === 'destination');
$tours = array_filter($allServices, fn($s) => $s->type === 'tour');

// 3. Renderizado de Vistas
// Pasamos los datos a las vistas simplemente incluyéndolas
require_once __DIR__ . '/../src/Infrastructure/Views/layout/header.php';
require_once __DIR__ . '/../src/Infrastructure/Views/home.php';
require_once __DIR__ . '/../src/Infrastructure/Views/layout/footer.php';