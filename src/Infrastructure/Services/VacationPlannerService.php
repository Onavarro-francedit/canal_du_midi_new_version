<?php
namespace App\Infrastructure\Services;

class VacationPlannerService {
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
        $this->model  = OPENAI_MODEL;
    }

    public function generatePlan(string $userPrompt, array $allServices): array {
        $catalog = $this->buildCatalog($allServices);

        $system = 'Tu es un expert en planification de voyages sur le Canal du Midi (Occitanie, France). '
            . 'Ta mission : créer un itinéraire personnalisé jour par jour, en utilisant UNIQUEMENT les services présents dans le catalogue fourni. '
            . "Réponds UNIQUEMENT avec un JSON valide, sans texte en dehors du JSON.\n\n"
            . "FORMAT JSON REQUIS :\n"
            . '{"duration_days":3,"summary":"Résumé du séjour en 1-2 phrases","days":[{"day":1,"label":"Titre du jour","activities":[{"slot":"matin","service_id":42,"title":"Nom du service","note":"Conseil pratique court"}]}]}'
            . "\n\nRÈGLES :\n"
            . "- Utilise uniquement des service_id présents dans le catalogue\n"
            . "- Maximum 3 activités par jour réparties sur : matin / après-midi / soir\n"
            . "- Inclure un hébergement le soir si le séjour dure plusieurs jours\n"
            . "- Adapter le contenu au profil (famille, couple, aventure, luxe, etc.)\n"
            . "- Ne jamais inventer de services absents du catalogue\n\n"
            . 'CATALOGUE : ' . json_encode($catalog, JSON_UNESCAPED_UNICODE);

        if (empty($this->apiKey)) {
            return $this->fallback($allServices);
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'           => $this->model,
                'messages'        => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => 'Demande du voyageur : "' . $userPrompt . '"'],
                ],
                'temperature'     => 0.7,
                'max_tokens'      => 2000,
                'response_format' => ['type' => 'json_object'],
            ]),
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log('[VacationPlanner] OpenAI error HTTP ' . $httpCode . ': ' . $curlError);
            return $this->fallback($allServices);
        }

        $decoded = json_decode($response, true);
        $raw     = $decoded['choices'][0]['message']['content'] ?? '';
        $plan    = json_decode($raw, true);

        if (!is_array($plan) || empty($plan['days'])) {
            error_log('[VacationPlanner] Invalid plan JSON: ' . substr($raw, 0, 300));
            return $this->fallback($allServices);
        }

        return $this->hydrate($plan, $allServices);
    }

    private function buildCatalog(array $services): array {
        $catalog = [];
        foreach ($services as $s) {
            $categories = array_values(array_filter(
                array_map(fn($c) => $c['name'] ?? '', $s->getActiveCategories())
            ));
            $catalog[] = [
                'id'         => (int)$s->id,
                'title'      => $s->translations['title'] ?? '',
                'type'       => $s->type ?? '',
                'city'       => $s->contact['ville'] ?? '',
                'zone'       => $s->zone ?? '',
                'price'      => (float)($s->price ?? 0),
                'categories' => $categories,
            ];
        }
        return $catalog;
    }

    private function hydrate(array $plan, array $services): array {
        $map = [];
        foreach ($services as $s) {
            $map[(int)$s->id] = $s;
        }

        foreach ($plan['days'] as &$day) {
            foreach ($day['activities'] as &$act) {
                $sid = (int)($act['service_id'] ?? 0);
                $s   = $map[$sid] ?? null;
                if (!$s) {
                    continue;
                }
                $act['type']    = $s->type ?? '';
                $act['image']   = $s->imageUrl ?? '';
                $act['url']     = BASE_URL . 'fiche/' . ($s->slug ?? '');
                $act['price']   = $s->getFormattedPrice();
                $act['city']    = $s->contact['ville'] ?? '';
                $act['address'] = $s->getFullAddress();
                $act['email']   = trim($s->contact['email'] ?? '');
                $act['phone']   = trim($s->contact['phone'] ?: ($s->contact['mobile'] ?? ''));
            }
            unset($act);
        }
        unset($day);

        return $plan;
    }

    private function fallback(array $services): array {
        $slots    = ['matin', 'après-midi', 'soir'];
        $slice    = array_slice($services, 0, 9);
        $days     = [];
        $dayCount = min(3, (int)ceil(count($slice) / 3));

        for ($d = 0; $d < $dayCount; $d++) {
            $chunk = array_slice($slice, $d * 3, 3);
            $acts  = [];
            foreach ($chunk as $i => $s) {
                $acts[] = [
                    'slot'       => $slots[$i] ?? 'matin',
                    'service_id' => (int)$s->id,
                    'title'      => $s->translations['title'] ?? '',
                    'note'       => '',
                    'type'       => $s->type ?? '',
                    'image'      => $s->imageUrl ?? '',
                    'url'        => BASE_URL . 'fiche/' . ($s->slug ?? ''),
                    'price'      => $s->getFormattedPrice(),
                    'city'       => $s->contact['ville'] ?? '',
                    'address'    => $s->getFullAddress(),
                    'email'      => trim($s->contact['email'] ?? ''),
                    'phone'      => trim($s->contact['phone'] ?: ($s->contact['mobile'] ?? '')),
                ];
            }
            $days[] = ['day' => $d + 1, 'label' => 'Jour ' . ($d + 1), 'activities' => $acts];
        }

        return [
            'duration_days' => count($days),
            'summary'       => 'Voici une sélection de prestataires pour votre séjour sur le Canal du Midi.',
            'days'          => $days,
        ];
    }
}
