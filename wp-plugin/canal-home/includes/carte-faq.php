<?php
/**
 * FAQ de la carte — funciones puras (sin WordPress), testeables con PHP CLI.
 * Las respuestas se construyen con los datos reales (recuentos y communes por categoría):
 * siempre ciertas y al día. Se muestran bajo la lista y se marcan como FAQPage.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

/** Communes con más fichas de una categoría (o de sus hijas, vía cat_slugs); empate → alfabético. */
function canal_carte_top_cities(array $listings, string $slug, int $limit): array
{
    $counts = [];
    foreach ($listings as $item) {
        if ($item['city'] !== '' && in_array($slug, $item['cat_slugs'], true)) {
            $counts[$item['city']] = ($counts[$item['city']] ?? 0) + 1;
        }
    }
    uksort($counts, function ($a, $b) use ($counts) {
        return ($counts[$b] <=> $counts[$a]) ?: strcmp(canal_carte_fold($a), canal_carte_fold($b));
    });
    return array_slice(array_keys($counts), 0, $limit);
}

function canal_carte_join_fr(array $items): string
{
    if (count($items) < 2) {
        return (string) ($items[0] ?? '');
    }
    return implode(', ', array_slice($items, 0, -1)) . ' et ' . end($items);
}

/** @return array<int, array{q: string, a: string}> */
function canal_carte_faq(array $listings): array
{
    $count = function (string $slug) use ($listings): int {
        return count(array_filter($listings, function ($item) use ($slug) {
            return in_array($slug, $item['cat_slugs'], true);
        }));
    };
    $plural = function (int $n, string $word): string {
        return $n . ' ' . $word . ($n > 1 ? 's' : '');
    };
    $where = function (string $slug) use ($listings): string {
        $cities = canal_carte_top_cities($listings, $slug, 4);
        return $cities ? ', notamment à ' . canal_carte_join_fr($cities) : '';
    };

    $faq = [];
    if ($n = $count('location-bateau')) {
        $faq[] = [
            'q' => 'Où louer un bateau sans permis sur le Canal du Midi ?',
            'a' => 'La carte recense ' . $plural($n, 'loueur') . ' de bateaux' . $where('location-bateau')
                . '. Choisissez « Location de bateau » dans les filtres pour les afficher sur la carte.',
        ];
    }
    if ($n = $count('location-de-velo')) {
        $faq[] = [
            'q' => 'Où louer un vélo le long du Canal du Midi ?',
            'a' => $plural($n, 'loueur') . ' de vélos ' . ($n > 1 ? 'sont référencés' : 'est référencé') . ' au bord du canal' . $where('location-de-velo')
                . '. La piste le long du chemin de halage relie Toulouse à la Méditerranée.',
        ];
    }
    if ($n = $count('hebergement')) {
        $faq[] = [
            'q' => 'Comment trouver un hébergement au bord du canal ?',
            'a' => 'Filtrez par « Se loger » : ' . $plural($n, 'hébergement')
                . ' (hôtels, chambres d’hôtes, gîtes, campings et hébergements insolites)' . $where('hebergement') . '.',
        ];
    }
    $faq[] = [
        'q' => 'Comment utiliser la carte interactive ?',
        'a' => 'Cherchez par mot-clé ou par type de service, activez « Autour de moi » pour trier les adresses par distance, '
            . 'ou décrivez votre séjour à l’assistant IA. Chaque fiche indique l’adresse, le téléphone et l’itinéraire.',
    ];
    return $faq;
}
