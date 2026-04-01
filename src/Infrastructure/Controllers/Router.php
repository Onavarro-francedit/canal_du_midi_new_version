<?php
namespace App\Infrastructure\Controllers;

class Router {
    private string $lang = 'fr';
    private string $page = 'home';
    private ?string $params = null;

    public function handleRequest() {
        $uri = $_SERVER['REQUEST_URI'];
        $uriPath = explode('?', $uri)[0];

        // Detectar la carpeta base de forma idéntica a config.php
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $basePath = rtrim(str_replace('/public', '', str_replace('\\', '/', dirname($scriptName))), '/');

        // Extraer la ruta relativa
        // Si entras a localhost/canal_du_midi/, el path será "" o "/"
        $path = str_replace($basePath, '', $uriPath);
        $path = trim($path, '/');

        // Valores por defecto
        $this->lang = 'fr';
        $this->page = 'home';
        $this->params = null;

        if (!empty($path)) {
            $parts = explode('/', $path);

            // Caso 1: La URL empieza por idioma (ej: /es/home)
            if (strlen($parts[0]) === 2) {
                $this->lang = $parts[0];
                $this->page = (!empty($parts[1])) ? $parts[1] : 'home';
                $this->params = $parts[2] ?? null;
            } 
            // Caso 2: La URL no tiene idioma (ej: /home o /index.php)
            else {
                if ($parts[0] !== 'index.php') {
                    $this->page = $parts[0];
                    $this->params = $parts[1] ?? null;
                }
            }
        }

        // Limpieza final por si el path fue "index.php"
        if ($this->page === 'index.php' || empty($this->page)) {
            $this->page = 'home';
        }

        return [
            'lang' => $this->lang,
            'page' => $this->page,
            'params' => $this->params
        ];
    }
}