<?php
namespace App\Domain\Services;

interface AIServiceInterface {
    /**
     * Analiza un prompt y devuelve el ID del mejor servicio 
     * junto con una explicación de por qué lo eligió.
     */
    public function analyzeRequest(string $prompt, array $availableServices): array;
}