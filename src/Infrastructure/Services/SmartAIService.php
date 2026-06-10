<?php
namespace App\Infrastructure\Services;

use App\Domain\Services\AIServiceInterface;

class SmartAIService implements AIServiceInterface {
    public function analyzeRequest(string $prompt, array $availableServices): array {
        $prompt = strtolower($prompt);
        $bestMatch = null;
        $reason = "";
        $rankedMatches = [];

        $normalizeService = function ($service): array {
            return [
                'id' => $service->id,
                'title' => $service->translations['title'] ?? '',
                'type' => $service->type ?? '',
                'price' => method_exists($service, 'getFormattedPrice') ? $service->getFormattedPrice() : '',
                'text' => $service->translations['description'] ?? '',
                'address' => method_exists($service, 'getFullAddress') ? $service->getFullAddress() : '',
                'lat' => $service->lat ?? 0,
                'lng' => $service->lng ?? 0,
                'image' => $service->imageUrl ?? '',
                'gallery' => array_values(array_filter(array_map('trim', (array)($service->gallery ?? [])))),
                'url' => BASE_URL . 'fiche/' . ($service->slug ?? ''),
                'roomsCount' => (int)($service->features['rooms_count'] ?? 0),
                'hybrid' => method_exists($service, 'isHybrid') ? $service->isHybrid() : false,
                'priceValue' => (float)($service->price ?? 0),
            ];
        };

        $buildRankedMatches = function (array $services) use ($normalizeService): array {
            return array_map($normalizeService, array_slice($services, 0, 5));
        };

        // Helper: detect if a prompt word matches any of the given needles
        $promptContainsAny = function (array $needles) use ($prompt): bool {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($prompt, $needle)) {
                    return true;
                }
            }
            return false;
        };

        // ── ESTRATEGIA: Precio más competitivo ──────────────────────────────
        if ($promptContainsAny(['prix', 'abordable', 'moins cher', 'pas cher', 'economique', 'budget', 'economique'])) {
            usort($availableServices, fn($a, $b) => $a->price <=> $b->price);
            $bestMatch = $availableServices[0];
            $rankedMatches = $buildRankedMatches($availableServices);
            $reason = "D'après votre demande, cet établissement est le plus compétitif avec un tarif de " . $bestMatch->getFormattedPrice() . ".";
        }
        // ── ESTRATEGIA: Espacioso / grupos ───────────────────────────────────
        elseif ($promptContainsAny(['spacieux', 'grand', 'groupe', 'famille', 'capacite', 'capacité', 'nombreux'])) {
            usort($availableServices, fn($a, $b) => ($b->features['rooms_count'] ?? 0) <=> ($a->features['rooms_count'] ?? 0));
            $bestMatch = $availableServices[0];
            $rankedMatches = $buildRankedMatches($availableServices);
            $reason = "Pour un maximum d'espace, je vous suggère cet établissement qui dispose de " . ($bestMatch->features['rooms_count'] ?? 0) . " chambres.";
        }
        // ── ESTRATEGIA: Restaurant gastronomique ─────────────────────────────
        elseif ($promptContainsAny(['gastronomie', 'gastronomique', 'chef', 'etoile', 'gourmet', 'degustation', 'bistronomie', 'raffine', 'raffiné'])) {
            $gastroMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->translations['tag'] ?? '',
                    $s->type ?? '',
                ]));
                return str_contains($text, 'restaurant') || str_contains($text, 'gastronomie')
                    || str_contains($text, 'gastronomique') || str_contains($text, 'chef')
                    || str_contains($text, 'table') || str_contains($text, 'cuisine');
            });
            $gastroMatches = array_values($gastroMatches);
            $bestMatch = !empty($gastroMatches) ? $gastroMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($gastroMatches) ? $gastroMatches : $availableServices);
            $reason = "Pour une expérience gastronomique, j'ai sélectionné cette adresse reconnue pour la qualité de sa cuisine sur le Canal du Midi.";
        }
        // ── ESTRATEGIA: Restaurant terroir / local ───────────────────────────
        elseif ($promptContainsAny(['terroir', 'regional', 'régional', 'occitan', 'traditionnel', 'local']) && $promptContainsAny(['manger', 'restaurant', 'table', 'cuisine', 'repas', 'dejeuner', 'diner'])) {
            $localMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                ]));
                return str_contains($text, 'restaurant') || str_contains($text, 'table')
                    || str_contains($text, 'cuisine') || str_contains($text, 'terroir')
                    || str_contains($text, 'regional') || str_contains($text, 'auberge');
            });
            $localMatches = array_values($localMatches);
            $bestMatch = !empty($localMatches) ? $localMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($localMatches) ? $localMatches : $availableServices);
            $reason = "Pour découvrir les saveurs du terroir occitan, cette adresse propose une cuisine authentique et locale.";
        }
        // ── ESTRATEGIA: Restaurant (générique + terrasse/vue) ────────────────
        elseif ($promptContainsAny(['restaurant', 'manger', 'gastronomie', 'table', 'repas', 'dejeuner', 'diner', 'cuisine', 'bistrot', 'brasserie'])) {
            $restMatches = [];
            $hybridMatches = [];
            foreach ($availableServices as $s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));
                if (method_exists($s, 'isHybrid') && $s->isHybrid()) {
                    $hybridMatches[] = $s;
                    if (!$bestMatch) {
                        $bestMatch = $s;
                    }
                } elseif (str_contains($text, 'restaurant') || str_contains($text, 'restauration')
                    || str_contains($text, 'bistrot') || str_contains($text, 'brasserie')
                    || str_contains($text, 'table') || str_contains($text, 'cuisine')) {
                    $restMatches[] = $s;
                    if (!$bestMatch) {
                        $bestMatch = $s;
                    }
                }
            }
            $pool = !empty($restMatches) ? $restMatches : (!empty($hybridMatches) ? $hybridMatches : $availableServices);
            $rankedMatches = $buildRankedMatches($pool);
            $reason = "J'ai sélectionné cette adresse car elle propose une offre de restauration sur place, idéale pour profiter du Canal du Midi.";
        }
        // ── ESTRATEGIA: Hébergement de luxe / charme ─────────────────────────
        elseif ($promptContainsAny(['luxe', 'luxueux', 'prestige', 'charme', 'boutique', 'spa', 'piscine', 'suite', 'premium']) && $promptContainsAny(['hotel', 'hebergement', 'hébergement', 'chambre', 'nuit', 'dormir', 'sejour'])) {
            $luxeMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));
                return str_contains($text, 'luxe') || str_contains($text, 'charme')
                    || str_contains($text, 'prestige') || str_contains($text, 'boutique')
                    || str_contains($text, 'spa') || str_contains($text, 'piscine')
                    || str_contains($text, 'hotel') || str_contains($text, 'suite');
            });
            usort($luxeMatches, fn($a, $b) => $b->price <=> $a->price);
            $luxeMatches = array_values($luxeMatches);
            $bestMatch = !empty($luxeMatches) ? $luxeMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($luxeMatches) ? $luxeMatches : $availableServices);
            $reason = "Pour un séjour d'exception, j'ai sélectionné cet hébergement haut de gamme au bord du Canal du Midi.";
        }
        // ── ESTRATEGIA: Gîte / chambre d'hôtes / rural ───────────────────────
        elseif ($promptContainsAny(['gite', 'gîte', 'chambre d\'hote', 'chambre d\'hôte', 'rural', 'campagne', 'ferme', 'domaine', 'bastide'])) {
            $giteMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));
                return str_contains($text, 'gite') || str_contains($text, 'gites')
                    || str_contains($text, 'chambre') || str_contains($text, 'rural')
                    || str_contains($text, 'campagne') || str_contains($text, 'ferme')
                    || str_contains($text, 'domaine') || str_contains($text, 'bastide')
                    || str_contains($text, 'maison') || str_contains($text, 'villa');
            });
            $giteMatches = array_values($giteMatches);
            $bestMatch = !empty($giteMatches) ? $giteMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($giteMatches) ? $giteMatches : $availableServices);
            $reason = "Pour un séjour authentique à la campagne, ce gîte ou cette maison d'hôtes vous accueille dans un cadre typique de la région.";
        }
        // ── ESTRATEGIA: Hébergement (générique) ──────────────────────────────
        elseif ($promptContainsAny(['hotel', 'hebergement', 'hébergement', 'hostel', 'guesthouse', 'lodging', 'chambre', 'dormir', 'nuit', 'sejour', 'séjour'])) {
            $hotelMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));
                return str_contains($text, 'hotel') || str_contains($text, 'hebergement')
                    || str_contains($text, 'chambre') || str_contains($text, 'gite')
                    || str_contains($text, 'hostel') || str_contains($text, 'guesthouse')
                    || str_contains($text, 'pension') || str_contains($text, 'residence');
            });
            $hotelMatches = array_values($hotelMatches);
            $bestMatch = !empty($hotelMatches) ? $hotelMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($hotelMatches) ? $hotelMatches : $availableServices);
            $reason = "Pour votre séjour sur le Canal du Midi, j'ai sélectionné cet hébergement qui combine confort et situation idéale.";
        }
        // ── ESTRATEGIA: Location de vélos ────────────────────────────────────
        elseif ($promptContainsAny(['louer', 'location', 'rent']) && $promptContainsAny(['velo', 'vélo', 'bike', 'bicycle', 'bicyclette'])) {
            $bikeRentalMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                ]));
                return (str_contains($text, 'velo') || str_contains($text, 'bike') || str_contains($text, 'bicycle') || str_contains($text, 'cyclo'))
                    && (str_contains($text, 'location') || str_contains($text, 'louer') || str_contains($text, 'rent'));
            });
            $bikeRentalMatches = array_values($bikeRentalMatches);
            usort($bikeRentalMatches, fn($a, $b) => $a->price <=> $b->price);
            $bestMatch = !empty($bikeRentalMatches) ? $bikeRentalMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($bikeRentalMatches) ? $bikeRentalMatches : $availableServices);
            $reason = "Pour louer un vélo et explorer le Canal du Midi à votre rythme, cet établissement propose des tarifs de location adaptés.";
        }
        // ── ESTRATEGIA: Vélo électrique ───────────────────────────────────────
        elseif ($promptContainsAny(['electrique', 'électrique', 'vae', 'e-bike']) && $promptContainsAny(['velo', 'vélo', 'bike'])) {
            $eBikeMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                ]));
                return (str_contains($text, 'electrique') || str_contains($text, 'vae') || str_contains($text, 'e-bike') || str_contains($text, 'assistance'))
                    && (str_contains($text, 'velo') || str_contains($text, 'bike') || str_contains($text, 'cyclo'));
            });
            $eBikeMatches = array_values($eBikeMatches);
            $bestMatch = !empty($eBikeMatches) ? $eBikeMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($eBikeMatches) ? $eBikeMatches : $availableServices);
            $reason = "Pour un parcours sans effort le long du Canal du Midi, ce prestataire propose des vélos à assistance électrique.";
        }
        // ── ESTRATEGIA: Circuit / balade à vélo ──────────────────────────────
        elseif ($promptContainsAny(['velo', 'vélo', 'bike', 'bicycle', 'bicyclette', 'cyclo', 'cycling', 'cyclotourisme', 'cyclable', 'voie verte', 'deux roues'])) {
            $bikeMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));
                return str_contains($text, 'velo') || str_contains($text, 'bike')
                    || str_contains($text, 'bicycle') || str_contains($text, 'cyclo')
                    || str_contains($text, 'cyclotourisme') || str_contains($text, 'cyclable')
                    || str_contains($text, 'voie verte') || str_contains($text, 'deux roues');
            });
            $bikeMatches = array_values($bikeMatches);
            $bestMatch = !empty($bikeMatches) ? $bikeMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($bikeMatches) ? $bikeMatches : $availableServices);
            $reason = "Pour explorer le Canal du Midi à vélo, j'ai sélectionné ce prestataire spécialisé en cyclotourisme.";
        }
        // ── ESTRATEGIA: Glamping / hébergement insolite ───────────────────────
        elseif ($promptContainsAny(['glamping', 'lodge', 'cabane', 'insolite', 'bulle', 'tipi', 'yourte', 'treehouse'])) {
            $glampingMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                ]));
                return str_contains($text, 'glamping') || str_contains($text, 'lodge')
                    || str_contains($text, 'cabane') || str_contains($text, 'insolite')
                    || str_contains($text, 'bulle') || str_contains($text, 'tipi')
                    || str_contains($text, 'yourte') || str_contains($text, 'treehouse')
                    || str_contains($text, 'tente lodge');
            });
            $glampingMatches = array_values($glampingMatches);
            $bestMatch = !empty($glampingMatches) ? $glampingMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($glampingMatches) ? $glampingMatches : $availableServices);
            $reason = "Pour une nuit insolite au cœur de la nature, j'ai sélectionné cet hébergement atypique sur le Canal du Midi.";
        }
        // ── ESTRATEGIA: Mobile home / camping équipé ─────────────────────────
        elseif ($promptContainsAny(['mobil home', 'mobilhome', 'mobile home', 'caravane', 'bungalow', 'chalet'])) {
            $mobilhomeMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                ]));
                return str_contains($text, 'mobil home') || str_contains($text, 'mobilhome')
                    || str_contains($text, 'mobile home') || str_contains($text, 'caravane')
                    || str_contains($text, 'bungalow') || str_contains($text, 'chalet')
                    || str_contains($text, 'camping');
            });
            $mobilhomeMatches = array_values($mobilhomeMatches);
            usort($mobilhomeMatches, fn($a, $b) => $a->price <=> $b->price);
            $bestMatch = !empty($mobilhomeMatches) ? $mobilhomeMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($mobilhomeMatches) ? $mobilhomeMatches : $availableServices);
            $reason = "Pour un séjour en camping avec tout le confort, cet établissement propose des mobil-homes et bungalows bien équipés.";
        }
        // ── ESTRATEGIA: Camping (générique) ──────────────────────────────────
        elseif ($promptContainsAny(['camping', 'tente', 'emplacement', 'bivouac', 'plein air', 'caravane', 'camp'])) {
            $campingMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));
                return str_contains($text, 'camping') || str_contains($text, 'tente')
                    || str_contains($text, 'emplacement') || str_contains($text, 'caravane')
                    || str_contains($text, 'bivouac') || str_contains($text, 'plein air')
                    || str_contains($text, 'glamping') || str_contains($text, 'lodge');
            });
            $campingMatches = array_values($campingMatches);
            usort($campingMatches, fn($a, $b) => $a->price <=> $b->price);
            $bestMatch = !empty($campingMatches) ? $campingMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($campingMatches) ? $campingMatches : $availableServices);
            $reason = "Pour profiter de la nature en camping sur le Canal du Midi, j'ai sélectionné ce camping bien situé et bien équipé.";
        }
        // ── ESTRATEGIA: Bateau / navigation ─────────────────────────────────
        elseif ($promptContainsAny(['bateau', 'bateaux', 'boat', 'peniche', 'péniche', 'croisiere', 'croisière', 'navigation', 'fluvial', 'canal', 'location bateau', 'louer bateau'])) {
            $boatMatches = array_filter($availableServices, function ($s) {
                $text = strtolower(implode(' ', [
                    $s->translations['title'] ?? '',
                    $s->translations['description'] ?? '',
                    $s->translations['tag'] ?? '',
                    $s->type ?? '',
                    $s->label ?? '',
                ]));

                return str_contains($text, 'bateau') || str_contains($text, 'boat')
                    || str_contains($text, 'peniche') || str_contains($text, 'péniche')
                    || str_contains($text, 'croisiere') || str_contains($text, 'croisière')
                    || str_contains($text, 'navigation') || str_contains($text, 'fluvial')
                    || str_contains($text, 'location') || str_contains($text, 'louer');
            });
            $boatMatches = array_values($boatMatches);
            $bestMatch = !empty($boatMatches) ? $boatMatches[0] : ($availableServices[0] ?? null);
            $rankedMatches = $buildRankedMatches(!empty($boatMatches) ? $boatMatches : $availableServices);
            $reason = "Pour une demande liée aux bateaux, j'ai sélectionné les services les plus proches de la navigation, de la croisière ou de la location sur le Canal du Midi.";
        }
        // ── FALLBACK RÉEL: services hybrides ou première proposition ─────────
        else {
            $hybridMatches = [];
            foreach ($availableServices as $s) {
                if (method_exists($s, 'isHybrid') && $s->isHybrid()) {
                    $hybridMatches[] = $s;
                    if (!$bestMatch) {
                        $bestMatch = $s;
                    }
                }
            }
            $rankedMatches = $buildRankedMatches(!empty($hybridMatches) ? $hybridMatches : $availableServices);
            $reason = "J'ai sélectionné cette adresse car elle propose une offre complète de séjour et de restauration sur place.";
        }

        // ── FALLBACK ─────────────────────────────────────────────────────────
        if (!$bestMatch && !empty($availableServices)) {
            $bestMatch = $availableServices[0];
            $rankedMatches = $buildRankedMatches($availableServices);
            $reason = "Voici notre meilleure recommandation actuelle pour découvrir le Canal du Midi.";
        }

        if (empty($rankedMatches) && !empty($bestMatch)) {
            $rankedMatches = [$normalizeService($bestMatch)];
        }

        return [
            'id'      => $bestMatch ? $bestMatch->id : null,
            'title'   => $bestMatch ? $bestMatch->translations['title'] : '',
            'text'    => $reason,
            'type'    => $bestMatch ? ucfirst($bestMatch->type) : '',
            'price'   => $bestMatch ? $bestMatch->getFormattedPrice() : '',
            'results' => $rankedMatches,
            'count'   => count($rankedMatches),
        ];
    }
}