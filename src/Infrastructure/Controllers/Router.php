<?php
namespace App\Infrastructure\Controllers;

class Router {
    private string $lang = 'fr'; // Idioma por defecto
    private string $page = 'home'; // Página por defecto
    private ?string $params = null; // Parámetros adicionales


    public function handleRequest() {
    // Obtenemos la URI completa (ej: /canal_du_midi/fr/home)
    $uri = $_SERVER['REQUEST_URI'];
    
    // Eliminamos los parámetros de la URL si existen (?id=1...)
    $uri = explode('?', $uri)[0];

    // Eliminamos la carpeta base de la ruta
    $path = str_replace('/canal_du_midi/', '', $uri);
    $path = trim($path, '/');
    
    $parts = explode('/', $path);

    // Valores por defecto
    $this->lang = $parts[0] ?? 'fr';
    $this->page = $parts[1] ?? 'home';
    $this->params = $parts[2] ?? null;

    if (!empty($parts[0]) && strlen($parts[0]) === 2) {
        $this->lang = $parts[0];
        
        if (!empty($parts[1])) {
            $this->page = $parts[1];
        }
    }

    return [
        'lang' => $this->lang,
        'page' => $this->page,
        'params' => $this->params
    ];
}
}