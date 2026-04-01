<?php
// public/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Config/config.php';
require_once __DIR__ . '/../autoload.php';

use App\Infrastructure\Controllers\Router;
use App\Infrastructure\Controllers\PageController;

// 1. Detectar ruta e idioma (El Router hace su magia)
$router = new Router();
$request = $router->handleRequest();

// 2. Ejecutar el controlador (Él se encarga de los datos y las vistas)
$controller = new PageController();
$controller->render($request['page'], $request['lang'], $request['params']);

// FIN DEL ARCHIVO. No debe haber nada más aquí.