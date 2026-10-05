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
    'details-ecluse' => '/categorie/ecluses/',
    'les-ports-du-canal-du-midi' => '/categorie/ports/',
    'details-ports' => '/categorie/ports/',
    'detail-halte-nautique' => '/categorie/halte-nautique/',
    'details-presta' => '/explorer/',
    'prestataire' => '/explorer/',
    'resultat' => '/explorer/',
    'hotels' => '/categorie/hotel/',
    'chambres-dhotes' => '/categorie/chambre-dhotes/',
    'gites-ruraux' => '/categorie/gites/',
    'locations-saisonnieres' => '/categorie/location-saisonniere/',
    'residence-de-tourisme' => '/categorie/hebergement/',
    'rechercher-un-hebergement' => '/categorie/hebergement/',
    'hotellerie-de-plein-air' => '/categorie/camping/',
    'camping-a-la-ferme' => '/categorie/camping/',
    'location-de-bateaux-gites' => '/categorie/location-bateau/',
    'transport-de-bagages' => '/categorie/transfert-de-bagages/',
    'organisation-de-voyages-a-velo' => '/categorie/organisation-de-voyage-a-velo/',
    'promenade-en-bateaux' => '/categorie/croisiere-bateau/',
    'location-de-canoe-kawak' => '/categorie/location-de-canoe-kayak/',
    'produits-regionaux' => '/categorie/produits-regionaux/',
    'artisanat' => '/categorie/artisanat/',
    'cours-de-cuisine' => '/explorer/',
    'se-restaurer' => '/categorie/restauration/',
    'se-restaurer/rechercher-restaurants' => '/categorie/restaurant/',
    'se-restaurer/snacks' => '/categorie/brasserie-snack/',
    'se-restaurer/bateau-restaurant' => '/categorie/bateau-restaurant/',
    'se-restaurer/bars-salons-de-the' => '/categorie/bar/',
    'se-restaurer/alimentation' => '/categorie/commerce-alimentaire/',
    'se-restaurer/vins' => '/categorie/vente-de-vins/',
    'se-restaurer/tables-dhote' => '/categorie/table-dhote/',
    'loisirs' => '/categorie/activites-loisirs/',
    'loisirs/rechercher-des-loisirs' => '/categorie/activites-loisirs/',
    'loisirs/croisieres' => '/categorie/croisiere-bateau/',
    'loisirs/croisieres-de-luxe' => '/categorie/croisiere-bateau/',
    'loisirs/locations-de-bateaux' => '/categorie/location-bateau/',
    'loisirs/locations-de-velos' => '/categorie/location-de-velo/',
    'loisirs/locations-de-voitures' => '/explorer/',
    'loisirs/parcs-de-loisirs' => '/categorie/loisir-de-plein-air/',
    'loisirs/musees' => '/categorie/musees/',
    'loisirs/lieux-a-voir' => '/categorie/site-et-monument/',
    'loisirs/excursions' => '/categorie/excursions/',
    'services-utiles' => '/categorie/services/',
    'services-utiles/offices-de-tourisme' => '/categorie/lieux-dinformations/',
    'services-utiles/collectivites' => '/categorie/lieux-dinformations/',
    'services-utiles/librairies' => '/categorie/librairie/',
    'services-utiles/centre-commercial' => '/categorie/commerce/',
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
        if (is_search()) { // ?s= del tema → buscador de la carte (la lupa 2026 ya va allí)
            wp_safe_redirect(add_query_arg('q', rawurlencode(get_search_query(false)), home_url(CANAL_CARTE_PATH)), 301, 'canal-home 2026');
            exit;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $target = canal_2026_redirect_url((string) parse_url($uri, PHP_URL_PATH), (string) parse_url($uri, PHP_URL_QUERY));
        if ($target !== null) {
            wp_safe_redirect(home_url($target), 301, 'canal-home 2026');
            exit;
        }
    }, -20);
}
