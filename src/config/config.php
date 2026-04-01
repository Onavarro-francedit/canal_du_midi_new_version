<?php
// src/Config/config.php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Detectar la ruta del script actual (ej: /canal_du_midi/public/index.php)
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', dirname($scriptName));

// Limpiamos para obtener solo la carpeta del proyecto (quitando /public si existe)
$basePath = str_replace('/public', '', $scriptDir);
$basePath = rtrim($basePath, '/');

// Definir constante BASE_URL
define('BASE_URL', $protocol . $host . $basePath . '/');

define('APP_ENV', ($host === 'localhost') ? 'dev' : 'prod');