<?php
/**
 * Redirecciones 301 del sitio publicado (TASK-063 §4): solo con CANAL_2026_LIVE. Sin tocar .htaccess.
 * Las rutas privadas -2026 pasan a las definitivas; lo que sustituye el planificador va a él; las 54 páginas vacías de
 * 2013–2018 (plantillas rechercher-presta, details_*, liste-*) van a su equivalente en la carte.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_2026_LEGACY_REDIRECTS = [
    'organiser-votre-sejour' => '/planificateur/',
    'demande-de-location-de-bateau' => '/planificateur/',
    'demande-de-promenade-en-bateaux' => '/planificateur/',
    'demande-dhebergement-le-long-du-canal-du-midi' => '/planificateur/',
    'agenda' => '/manifestations-et-fetes-canal-du-midi/',
    'les-ecluses-du-canal-du-midi' => '/les-ecluses-du-canal-du-midi-2/',
    'details-ecluse' => '/explorer/?type=ecluses',
    'les-ports-du-canal-du-midi' => '/explorer/?type=ports',
    'details-ports' => '/explorer/?type=ports',
    'detail-halte-nautique' => '/explorer/?type=halte-nautique',
    'details-presta' => '/explorer/',
    'prestataire' => '/explorer/',
    'resultat' => '/explorer/',
    'hotels' => '/explorer/?type=hotel',
    'chambres-dhotes' => '/explorer/?type=chambre-dhotes',
    'gites-ruraux' => '/explorer/?type=gites',
    'locations-saisonnieres' => '/explorer/?type=location-saisonniere',
    'residence-de-tourisme' => '/explorer/?type=hebergement',
    'rechercher-un-hebergement' => '/explorer/?type=hebergement',
    'hotellerie-de-plein-air' => '/explorer/?type=camping',
    'camping-a-la-ferme' => '/explorer/?type=camping',
    'location-de-bateaux-gites' => '/explorer/?type=location-bateau',
    'transport-de-bagages' => '/explorer/?type=transfert-de-bagages',
    'organisation-de-voyages-a-velo' => '/explorer/?type=organisation-de-voyage-a-velo',
    'promenade-en-bateaux' => '/explorer/?type=croisiere-bateau',
    'location-de-canoe-kawak' => '/explorer/?type=location-de-canoe-kayak',
    'produits-regionaux' => '/explorer/?type=produits-regionaux',
    'artisanat' => '/explorer/?type=artisanat',
    'cours-de-cuisine' => '/explorer/',
    'se-restaurer' => '/explorer/?type=restauration',
    'se-restaurer/rechercher-restaurants' => '/explorer/?type=restaurant',
    'se-restaurer/snacks' => '/explorer/?type=brasserie-snack',
    'se-restaurer/bateau-restaurant' => '/explorer/?type=bateau-restaurant',
    'se-restaurer/bars-salons-de-the' => '/explorer/?type=bar',
    'se-restaurer/alimentation' => '/explorer/?type=commerce-alimentaire',
    'se-restaurer/vins' => '/explorer/?type=vente-de-vins',
    'se-restaurer/tables-dhote' => '/explorer/?type=table-dhote',
    'loisirs' => '/explorer/?type=activites-loisirs',
    'loisirs/rechercher-des-loisirs' => '/explorer/?type=activites-loisirs',
    'loisirs/croisieres' => '/explorer/?type=croisiere-bateau',
    'loisirs/croisieres-de-luxe' => '/explorer/?type=croisiere-bateau',
    'loisirs/locations-de-bateaux' => '/explorer/?type=location-bateau',
    'loisirs/locations-de-velos' => '/explorer/?type=location-de-velo',
    'loisirs/locations-de-voitures' => '/explorer/',
    'loisirs/parcs-de-loisirs' => '/explorer/?type=loisir-de-plein-air',
    'loisirs/musees' => '/explorer/?type=musees',
    'loisirs/lieux-a-voir' => '/explorer/?type=site-et-monument',
    'loisirs/excursions' => '/explorer/?type=excursions',
    'services-utiles' => '/explorer/?type=services',
    'services-utiles/offices-de-tourisme' => '/explorer/?type=lieux-dinformations',
    'services-utiles/collectivites' => '/explorer/?type=lieux-dinformations',
    'services-utiles/librairies' => '/explorer/?type=librairie',
    'services-utiles/centre-commercial' => '/explorer/?type=commerce',
    'services-utiles/taxis' => '/explorer/',
    'services-utiles/voiture-de-transport-avec-chauffeur' => '/explorer/',
    'services-utiles/chantiers-navals' => '/explorer/',
    'services-utiles/laveries' => '/explorer/',
    'services-utiles/pharmacies' => '/explorer/',
    'services-utiles/immobilier' => '/explorer/',
];

/** Destino 301 de una ruta (sin query) o null. Las rutas -2026 se reconocen por el sufijo, sea cual sea el modo. */
function canal_2026_redirect_target(string $path): ?string
{
    $trim = trim($path, '/');
    if ($trim === '' || strpos($trim, 'wp-') === 0) {
        return null;
    }
    if (isset(CANAL_2026_LEGACY_REDIRECTS[$trim])) {
        return CANAL_2026_LEGACY_REDIRECTS[$trim];
    }
    $fixed = ['accueil-2026' => '/', 'explorer-2026' => '/explorer/', 'planificateur-2026' => '/planificateur/', 'etapes-2026' => '/etapes/'];
    if (isset($fixed[$trim])) {
        return $fixed[$trim];
    }
    if (preg_match('#^(fiche|etape)-2026/([^/]+)$#', $trim, $m)) {
        return '/' . $m[1] . '/' . $m[2] . '/';
    }
    if (preg_match('#^(post-category/.+?)-2026(/page/\d+)?$#', $trim, $m)) {
        return '/' . $m[1] . ($m[2] ?? '') . '/';
    }
    if (preg_match('#^(.+?)-2026$#', $trim, $m) && strpos($m[1], '/') !== 0) {
        return '/' . $m[1] . '/';
    }
    return null;
}

/** Destino con la query original (utm, etc.) añadida. */
function canal_2026_redirect_url(string $path, string $query): ?string
{
    $target = canal_2026_redirect_target($path);
    if ($target === null || $query === '') {
        return $target;
    }
    return $target . (strpos($target, '?') === false ? '?' : '&') . $query;
}

if (function_exists('add_action')) {
    add_action('template_redirect', function () {
        if (!CANAL_2026_LIVE) {
            return;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $target = canal_2026_redirect_url((string) parse_url($uri, PHP_URL_PATH), (string) parse_url($uri, PHP_URL_QUERY));
        if ($target !== null) {
            wp_safe_redirect(home_url($target), 301, 'canal-home 2026');
            exit;
        }
    }, -20);
}
