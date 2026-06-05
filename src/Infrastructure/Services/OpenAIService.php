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

        // 1. Preparamos el contexto para la IA
        // Le damos a ChatGPT todos los servicios disponibles para que "conozca" tu catálogo.
        $serviceData = array_map(fn($s) => [
            'id' => $s->id,
            'title' => $s->translations['title'],
            'type' => $s->type,
            'price' => $s->price,
            'isHybrid' => $s->isHybrid(),
            'roomsCount' => $s->features['rooms_count'],
            'amenities' => array_map(fn($a) => $a['slug'], $s->amenities),
            'description' => $s->translations['description']
        ], $availableServices);

        // 2. Instrucción a ChatGPT
        $messages = [
            [
                "role" => "system",
                "content" => "Tu es un assistant de voyage expert pour le Canal du Midi. Ton rôle est d'analyser les requêtes des utilisateurs et de recommander le MEILLEUR service (hôtel, bateau, activité) de la liste fournie. Tu dois justifier ta recommandation en citant des détails du service. Réponds uniquement avec un JSON structuré. Voici les services disponibles: " . json_encode($serviceData, JSON_UNESCAPED_UNICODE)
            ],
            [
                "role" => "user",
                "content" => "L'utilisateur demande: '" . $prompt . "'. Recommande un ID de service, son titre, son type, son prix, et une explication DÉTAILLÉE (max 100 mots) de ta recommandation en français. Ne propose que des IDs qui existent dans la liste."
            ],
            [
                "role" => "assistant",
                "content" => '{"recommended_id": int, "title": "string", "type": "string", "price": "string", "explanation": "string"}'
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
            'max_tokens' => 200 // Limitar la longitud de la respuesta
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

        // 4. Mapear la respuesta de la IA a nuestro formato
        return [
            'id' => $aiContent['recommended_id'] ?? null,
            'title' => $aiContent['title'] ?? 'Erreur AI',
            'type' => $aiContent['type'] ?? 'Problème de connexion',
            'price' => $aiContent['price'] ?? '',
            'text' => $aiContent['explanation'] ?? "L'assistant n'a pas pu analyser votre demande.",
        ];
    }
}