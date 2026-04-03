<?php
namespace App\Infrastructure\Services;

use App\Domain\Services\AIServiceInterface;

class SmartAIService implements AIServiceInterface {
    public function analyzeRequest(string $prompt, array $availableServices): array {
        $prompt = strtolower($prompt);
        $bestMatch = null;
        $reason = "";

        // ESTRATEGIA 1: Mejor relación Calidad/Precio (Lo más barato con buen rating)
        if (str_contains($prompt, 'prix') || str_contains($prompt, 'abordable') || str_contains($prompt, 'moins cher')) {
            usort($availableServices, fn($a, $b) => $a->price <=> $b->price);
            $bestMatch = $availableServices[0];
            $reason = "D'après votre demande, cet établissement est le plus compétitif avec un tarif de " . $bestMatch->getFormattedPrice() . ".";
        }
        // ESTRATEGIA 2: Espacioso (Más habitaciones)
        elseif (str_contains($prompt, 'spacieux') || str_contains($prompt, 'grand') || str_contains($prompt, 'groupe')) {
            usort($availableServices, fn($a, $b) => ($b->features['rooms_count'] ?? 0) <=> ($a->features['rooms_count'] ?? 0));
            $bestMatch = $availableServices[0];
            $reason = "Pour un maximum d'espace, je vous suggère cet établissement qui dispose de " . ($bestMatch->features['rooms_count'] ?? 0) . " chambres.";
        }
        // ESTRATEGIA 3: Híbrido (Con restaurante)
        elseif (str_contains($prompt, 'restaurant') || str_contains($prompt, 'manger') || str_contains($prompt, 'gastronomie')) {
            foreach ($availableServices as $s) {
                if ($s->isHybrid()) { $bestMatch = $s; break; }
            }
            $reason = "J'ai sélectionné cette adresse car elle propose une offre complète de séjour et de restauration sur place.";
        }

        // FALLBACK: Si no entiende nada, elige el primero
        if (!$bestMatch && !empty($availableServices)) {
            $bestMatch = $availableServices[0];
            $reason = "Voici notre meilleure recommandation actuelle pour découvrir le Canal du Midi.";
        }

        return [
            'id' => $bestMatch ? $bestMatch->id : null,
            'title' => $bestMatch ? $bestMatch->translations['title'] : '',
            'text' => $reason,
            'type' => $bestMatch ? ucfirst($bestMatch->type) : '',
            'price' => $bestMatch ? $bestMatch->getFormattedPrice() : ''
        ];
    }
}