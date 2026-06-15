<?php
namespace App\Infrastructure\Services;

use App\Domain\Services\AIServiceInterface;

class OpenAIService implements AIServiceInterface {
    private string $apiKey;
    private string $model;
    private string $apiUrl = "https://api.openai.com/v1/chat/completions";

    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
    }

    public function analyzeRequest(string $prompt, array $availableServices): array {
        $fallbackService = new SmartAIService();

        $normalizeService = function ($service): array {
            $categories = array_map(
                fn($category) => [
                    'id' => (int)($category['id'] ?? 0),
                    'name' => (string)($category['name'] ?? ''),
                    'slug' => (string)($category['slug'] ?? ''),
                ],
                $service->getActiveCategories()
            );

            $equipments = array_values(array_filter(array_map('strval', $service->getActiveEquipments())));
            $keywords = array_values(array_filter([
                trim((string)($service->translations['title'] ?? '')),
                trim((string)($service->translations['tag'] ?? '')),
                trim((string)($service->type ?? '')),
                trim((string)($service->label ?? '')),
                trim((string)($service->zone ?? '')),
                trim((string)($service->contact['ville'] ?? '')),
                trim(implode(' ', array_map(fn($category) => $category['name'] ?? '', $categories))),
                trim(implode(' ', $equipments)),
                trim(implode(' ', array_map(fn($amenity) => (string)($amenity['slug'] ?? ''), (array)($service->amenities ?? [])))),
            ]));

            return [
                'id' => $service->id,
                'title' => $service->translations['title'] ?? '',
                'type' => $service->type ?? '',
                'price' => method_exists($service, 'getFormattedPrice') ? $service->getFormattedPrice() : '',
                'text' => $service->translations['description'] ?? '',
                'address' => method_exists($service, 'getFullAddress') ? $service->getFullAddress() : '',
                'city' => trim((string)($service->contact['ville'] ?? '')),
                'zone' => trim((string)($service->zone ?? '')),
                'label' => trim((string)($service->label ?? '')),
                'lat' => $service->lat ?? 0,
                'lng' => $service->lng ?? 0,
                'image' => $service->imageUrl ?? '',
                'gallery' => array_values(array_filter(array_map('trim', (array)($service->gallery ?? [])))),
                'url' => BASE_URL . 'fiche/' . ($service->slug ?? ''),
                'roomsCount' => (int)($service->features['rooms_count'] ?? 0),
                'hybrid' => method_exists($service, 'isHybrid') ? $service->isHybrid() : false,
                'priceValue' => (float)($service->price ?? 0),
                'categories' => $categories,
                'equipments' => $equipments,
                'keywords' => $keywords,
            ];
        };

        $buildResultsFromIds = function (array $ids) use ($availableServices, $normalizeService): array {
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
            $results = [];

            foreach ($ids as $id) {
                foreach ($availableServices as $service) {
                    if ((int)$service->id !== $id) {
                        continue;
                    }

                    $results[] = $normalizeService($service);
                    break;
                }
            }

            return $results;
        };

        // 1. Preparamos el contexto para la IA
        // Le damos a ChatGPT todos los servicios disponibles para que "conozca" tu catálogo.
        $serviceData = [];
        foreach ($availableServices as $service) {
            $serviceCategories = array_values(array_map(fn($category) => [
                'id' => (int)($category['id'] ?? 0),
                'name' => (string)($category['name'] ?? ''),
                'slug' => (string)($category['slug'] ?? ''),
            ], $service->getActiveCategories()));

            $serviceEquipments = array_values(array_filter(array_map('strval', $service->getActiveEquipments())));
            $serviceKeywords = array_values(array_filter([
                trim((string)($service->translations['title'] ?? '')),
                trim((string)($service->translations['tag'] ?? '')),
                trim((string)($service->type ?? '')),
                trim((string)($service->label ?? '')),
                trim((string)($service->zone ?? '')),
                trim((string)($service->contact['ville'] ?? '')),
                trim(implode(' ', array_map(fn($category) => $category['name'] ?? '', $serviceCategories))),
                trim(implode(' ', $serviceEquipments)),
                trim(implode(' ', array_map(fn($a) => (string)($a['slug'] ?? ''), $service->amenities))),
            ]));

            $serviceData[] = [
                'id' => $service->id,
                'title' => $service->translations['title'],
                'type' => $service->type,
                'price' => $service->price,
                'isHybrid' => $service->isHybrid(),
                'roomsCount' => $service->features['rooms_count'],
                'city' => $service->contact['ville'] ?? '',
                'label' => $service->label ?? '',
                'zone' => $service->zone ?? '',
                'address' => $service->getFullAddress(),
                'amenities' => array_map(fn($a) => $a['slug'], $service->amenities),
                'categories' => $serviceCategories,
                'equipments' => $serviceEquipments,
                'keywords' => $serviceKeywords,
            ];
        }

        // 2. Instrucción a ChatGPT
        $messages = [
            [
                "role" => "system",
                "content" => "Tu es un assistant de voyage expert pour le Canal du Midi. La liste fournie est déjà filtrée et classée selon l'intention détectée par l'application. Ton rôle est d'analyser uniquement cette liste et de recommander les services réellement pertinents pour cette intention, sans élargir à d'autres familles. Utilise en priorité les champs title, type, categories, equipments, amenities, city, address, zone, label, price, roomsCount et keywords. Si la demande est liée aux bateaux, ne garde que les services liés à la location, à la croisière, à la péniche ou à la navigation. Si elle est liée à restaurant, hotel, bike ou camping, reste strictement dans cette famille et ses sous-intentions. Réponds uniquement avec un JSON structuré. Voici les services disponibles: " . json_encode($serviceData, JSON_UNESCAPED_UNICODE)
            ],
            [
                "role" => "user",
                "content" => "L'utilisateur demande: '" . $prompt . "'. Recommande uniquement les IDs de services pertinents présents dans la liste fournie, avec leurs titres, leurs types, leurs prix et une explication DÉTAILLÉE (max 100 mots) en français. Ne propose aucun service qui n'appartient pas à l'intention détectée. S'il y a plusieurs services vraiment pertinents dans cette même intention, inclue-les aussi."
            ],
            [
                "role" => "assistant",
                "content" => '{"recommended_id": int, "title": "string", "type": "string", "price": "string", "explanation": "string", "recommendations": [{"id": int, "title": "string", "type": "string", "price": "string", "explanation": "string"}]}'
            ]
        ];

        // 3. Llamada a la API de OpenAI
        if (empty($this->apiKey)) {
            return $fallbackService->analyzeRequest($prompt, $availableServices);
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7, // Creatividad de la IA
            'max_tokens' => 1500
        ]));

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log('OpenAI API Error: HTTP ' . $httpCode . ' | ' . ($curlError ?: $response));
            return $fallbackService->analyzeRequest($prompt, $availableServices);
        }

        $decodedResponse = json_decode($response, true);
        $rawContent = $decodedResponse['choices'][0]['message']['content'] ?? '';
        $aiContent = json_decode($rawContent, true);

        if (!is_array($decodedResponse) || !is_string($rawContent) || !is_array($aiContent)) {
            error_log('OpenAI API Error: malformed response payload');
            return $fallbackService->analyzeRequest($prompt, $availableServices);
        }

        $recommendationIds = [];
        if (!empty($aiContent['recommendations']) && is_array($aiContent['recommendations'])) {
            foreach ($aiContent['recommendations'] as $recommendation) {
                if (is_array($recommendation) && isset($recommendation['id'])) {
                    $recommendationIds[] = (int)$recommendation['id'];
                }
            }
        }

        if (empty($recommendationIds) && isset($aiContent['recommended_id'])) {
            $recommendationIds[] = (int)$aiContent['recommended_id'];
        }

        $results = $buildResultsFromIds($recommendationIds);

        if (empty($results)) {
            return $fallbackService->analyzeRequest($prompt, $availableServices);
        }

        // 4. Mapear la respuesta de la IA a nuestro formato
        return [
            'id' => $results[0]['id'] ?? null,
            'title' => $results[0]['title'] ?? ($aiContent['title'] ?? 'Erreur AI'),
            'type' => $results[0]['type'] ?? ($aiContent['type'] ?? 'Problème de connexion'),
            'price' => $results[0]['price'] ?? ($aiContent['price'] ?? ''),
            'text' => $aiContent['explanation'] ?? "L'assistant n'a pas pu analyser votre demande.",
            'results' => $results,
            'count' => count($results),
        ];
    }
}