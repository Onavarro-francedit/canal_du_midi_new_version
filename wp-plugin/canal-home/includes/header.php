<?php
/**
 * Cabecera propia (la de la app local, layout/header.php) en home, carte y ficha 2026.
 * El tema deja de pintar la suya con su filtro mylisting/header-config y la nuestra se imprime
 * en su hook mylisting/body/start. El resto del sitio no cambia; desactivar el plugin la devuelve.
 */
defined('ABSPATH') || exit;

function canal_header_is_page(): bool
{
    return canal_home_is_page() || canal_carte_is_page() || canal_fiche_is_page();
}

// Prioridad 99: la integración Elementor del tema usa el mismo filtro (a 10) y vuelve a poner show=true.
add_filter('mylisting/header-config', function ($config) {
    if (canal_header_is_page()) {
        $config['header']['show'] = false;
    }
    return $config;
}, 99);

/**
 * Menú principal del sitio (menú WP « Principale », 16) reorganizado por intención y modo de viaje,
 * ordenado por vistas GA4 (oct 2025 – sep 2026; Calcul de distance = nº 1 del sitio). Sin duplicados,
 * 2 niveles. Rutas relativas a home_url(); las absolutas son externas. Estas páginas no leen el menú
 * de WP: si cambia allí, actualizar aquí. 'carte' = slug de categoría para « Voir sur la carte ».
 */
function canal_header_menu(): array
{
    return [
        'En bateau' => [
            'cols' => [
                'Louer & naviguer' => [
                    'Location de bateau' => '/categorie/location-bateau/',
                    'Croisière en bateau' => '/categorie/croisiere-bateau/',
                    'Activités nautiques' => '/categorie/nautique/',
                    'Péniches à vendre' => '/peniches-a-vendre-sur-le-canal-tout-savoir-avant-dacheter/',
                ],
                'Ports & écluses' => [
                    'Les ports' => '/categorie/ports/',
                    'Ports fluviaux' => '/categorie/ports-fluviaux/',
                    'Haltes nautiques' => '/categorie/halte-nautique/',
                    'Les écluses du canal' => '/les-ecluses-du-canal-du-midi-2/',
                    'Toutes les écluses' => '/categorie/ecluses/',
                    'Dimensions des écluses' => '/longeur-largeur-des-ecluses-tirant-deau-tirant-dair-sur-le-canal-du-midi/',
                ],
                'Règles de navigation' => [
                    'Période de navigation' => '/navigation/periode-de-navigation/',
                    'Règles de navigation' => '/navigation/regles-de-navigation/',
                    'Permis de conduire' => '/navigation/permis-de-conduire/',
                    'Les panneaux' => '/navigation/les-panneaux/',
                    'Passer une écluse' => '/navigation/passer-une-ecluse/',
                    "Le canal en bateau : s'y préparer" => '/canal-du-midi-en-bateau-comment-sy-preparer/',
                    "Calcul d'itinéraire fluvial (VNF)" => 'http://www.vnf.fr/calculitinerairefluvial/app/Main.html',
                ],
            ],
            'foot' => [],
            'carte' => 'nautique',
        ],
        'Vélo & balades' => [
            'cols' => [
                'À vélo' => [
                    'Voie verte et véloroute' => '/voie-verte-et-veloroute/',
                    'Le canal à vélo' => '/categorie/velo/',
                    'Voyage organisé à vélo' => '/categorie/organisation-de-voyage-a-velo/',
                    'Location de vélo' => '/categorie/location-de-velo/',
                    'Balade à vélo sur le canal' => '/balade-a-velo-sur-le-canal-du-midi/',
                    'Chemins de halage : conditions' => '/conditions-dutilisation-des-chemins-de-halage-le-long-du-canal-du-midi-a-velo/',
                ],
                'Autrement' => [
                    'En roller' => '/balade-en-roller-2/',
                    'Balade à pied / Randonnée' => '/balade-a-pied-randonnee/',
                    'Canoë-kayak' => '/categorie/location-de-canoe-kayak/',
                    'Loisirs de plein air' => '/categorie/loisir-de-plein-air/',
                    'Toutes les activités' => '/categorie/activites-loisirs/',
                ],
            ],
            'foot' => [],
            'carte' => 'velo',
        ],
        'Découvrir' => [
            'cols' => [
                'Le canal' => [
                    'Le Canal de la Robine' => '/canal-de-la-robine/',
                    'Histoire' => '/le-canal/histoire/',
                    'Alimentation en eau' => '/alimentation-en-eau-du-canal/',
                    'La faune et la flore' => '/la-faune-et-la-flore/',
                    'Ouvrages' => '/le-canal/ouvrages/',
                    'Construction' => '/le-canal/construction/',
                    'Associations' => '/post-category/associations/',
                ],
                'À voir' => [
                    'Vignobles' => '/post-category/vignobles/',
                    'Circuits près du canal' => '/post-category/circuits-pres-du-canal-du-midi/',
                    'Lieux remarquables' => '/post-category/lieux-remarquables/',
                    'Sites et monuments' => '/categorie/site-et-monument/',
                    'Villes à visiter' => '/post-category/villes-a-visiter/',
                    'Musées' => '/categorie/musees/',
                    'Moulins' => '/categorie/moulins/',
                    'Excursions' => '/categorie/excursions/',
                    'Œnotourisme' => '/categorie/oenotourisme/',
                ],
                'Médias' => [
                    'Vidéos du canal' => '/video-de-presentation-du-canal-du-midi/',
                    'Photos du canal' => '/photos-canal-du-midi/',
                    'Archives : films et vidéos' => '/films-et-videos/',
                    'Archives : ouvrages, cartes et images' => '/ouvrages-cartes-et-images/',
                ],
            ],
            'foot' => [],
            'carte' => 'voir-visiter',
        ],
        'Se loger' => [
            'cols' => [
                'Hôtels & chambres' => [
                    'Camping' => '/categorie/camping/',
                    'Hôtel' => '/categorie/hotel/',
                    'Chambre à louer' => '/categorie/chambre-a-louer/',
                    "Chambre d'hôtes" => '/categorie/chambre-dhotes/',
                    'Appartement hôtel' => '/categorie/appartement-hotel/',
                ],
                'Locations' => [
                    'Gîte' => '/categorie/gites/',
                    'Location saisonnière' => '/categorie/location-saisonniere/',
                    'Appartement / Maison' => '/categorie/appartement-maison-a-louer/',
                ],
                'Insolite & auberges' => [
                    'Insolite' => '/categorie/insolite/',
                    'Péniche' => '/categorie/peniche/',
                    'Roulotte' => '/categorie/roulotte/',
                    'Auberge collective' => '/categorie/auberge-collective/',
                    'Auberge de jeunesse' => '/categorie/auberge-de-jeunesse/',
                    'Hostel' => '/categorie/hostel/',
                ],
            ],
            'foot' => ['Tous les hébergements' => '/categorie/hebergement/'],
            'carte' => 'hebergement',
        ],
        'Manger & Boire' => [
            'cols' => [
                'Restaurants' => [
                    'Tous les restaurants' => '/categorie/restauration-2/',
                    'Restaurant' => '/categorie/restaurant/',
                    'Brasserie / Snack' => '/categorie/brasserie-snack/',
                    'Bateau restaurant' => '/categorie/bateau-restaurant/',
                    'Bar' => '/categorie/bar/',
                ],
                'Commerces alimentaires' => [
                    'Tous les commerces' => '/categorie/commerce-alimentaire/',
                    'Vente de vins' => '/categorie/vente-de-vins/',
                    'Produits régionaux' => '/categorie/produits-regionaux/',
                    'Boulangerie / Pâtisserie' => '/categorie/boulangerie-patisserie/',
                    'Supermarché / Épicerie' => '/categorie/supermarche-epicerie/',
                ],
                'Shopping & services' => [
                    'Shopping' => '/categorie/shopping/',
                    'Librairie' => '/categorie/librairie/',
                    'Artisanat' => '/categorie/artisanat/',
                    'Commerce' => '/categorie/commerce/',
                    'Services' => '/categorie/services/',
                ],
            ],
            'foot' => ['Tout Manger & Boire' => '/categorie/restauration/'],
            'carte' => 'restauration',
        ],
        'Préparer' => [
            'cols' => [
                'Outils' => [
                    'Calcul de distance' => '/calcul-de-distance-canal-du-midi/',
                    'Météo du Canal du Midi' => '/meteo-du-canal-du-midi/',
                    'Recevoir le plan du canal' => '/recevoir-le-plan-du-canal-du-midi-2/',
                    'Organiser votre séjour' => '/organiser-votre-sejour/',
                ],
                'Agenda' => [
                    'Fêtes et manifestations' => '/manifestations-et-fetes-canal-du-midi/',
                    'Jours de marchés' => '/jours-de-marches-proche-du-canal-du-midi/',
                    'Actualités' => '/post-category/actualites/',
                ],
                'Aide' => [
                    'Foire aux questions' => '/foire-aux-question-faq-canal-du-midi/',
                    "Lieux d'informations" => '/categorie/lieux-dinformations/',
                ],
            ],
            'foot' => [],
        ],
    ];
}

/** Página más vista del sitio: acceso directo en la barra además de en « Préparer ». */
const CANAL_HEADER_DISTANCE_PATH = '/calcul-de-distance-canal-du-midi/';

function canal_header_url(string $path): string
{
    return $path[0] === '/' ? home_url($path) : $path;
}

add_action('mylisting/body/start', function () {
    if (!canal_header_is_page()) {
        return;
    }
    $home    = home_url(CANAL_HOME_PATH);
    $carte   = home_url(CANAL_CARTE_PATH);
    $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
    $i = 0;
    ?>
<header class="cdm-header">
    <div class="cdm-header__row">
        <a class="cdm-header__brand" href="<?php echo esc_url($home); ?>">
            <span class="cdm-header__mark" aria-hidden="true"></span>
            <span>Canal du Midi</span>
        </a>
        <button class="cdm-header__toggle" type="button" aria-expanded="false" aria-controls="cdm-header-nav" aria-label="Menu">
            <span></span><span></span>
        </button>
        <nav id="cdm-header-nav" class="cdm-header__nav" aria-label="Navigation principale">
            <?php foreach (canal_header_menu() as $label => $panel) : $id = 'cdm-mega-' . (++$i); ?>
            <div class="cdm-mega">
                <button class="cdm-mega__btn" type="button" aria-expanded="false" aria-controls="<?php echo $id; ?>"><?php echo esc_html($label); ?><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                <div class="cdm-mega__panel" id="<?php echo $id; ?>">
                    <div class="cdm-mega__cols">
                        <?php foreach ($panel['cols'] as $title => $items) : ?>
                        <div class="cdm-mega__col">
                            <p class="cdm-mega__title"><?php echo esc_html($title); ?></p>
                            <ul>
                                <?php foreach ($items as $text => $path) : $ext = $path[0] !== '/'; ?>
                                <li><a href="<?php echo esc_url(canal_header_url($path)); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($text); ?><?php echo $ext ? ' <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>' : ''; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($panel['foot'] || !empty($panel['carte'])) : ?>
                    <div class="cdm-mega__foot">
                        <?php foreach ($panel['foot'] as $text => $path) : ?>
                        <a href="<?php echo esc_url(canal_header_url($path)); ?>"><?php echo esc_html($text); ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                        <?php if (!empty($panel['carte'])) : ?>
                        <a href="<?php echo esc_url(add_query_arg('type', $panel['carte'], $carte)); ?>"><i class="bi bi-map" aria-hidden="true"></i> Voir sur la carte</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <a class="cdm-header__link" href="<?php echo esc_url(home_url(CANAL_HEADER_DISTANCE_PATH)); ?>"><i class="bi bi-rulers" aria-hidden="true"></i> Distances</a>
            <a class="cdm-header__link cdm-header__link--carte" href="<?php echo esc_url($carte); ?>"><i class="bi bi-map" aria-hidden="true"></i> Carte</a>
            <a class="cdm-header__cta" href="<?php echo esc_url($home . '#plan'); ?>"><i class="bi bi-magic" aria-hidden="true"></i> Planifier mon voyage</a>
            <a class="cdm-header__account" href="<?php echo esc_url($account); ?>" title="<?php echo is_user_logged_in() ? 'Mon compte' : 'Se connecter'; ?>"><i class="bi bi-person" aria-hidden="true"></i><span><?php echo is_user_logged_in() ? 'Mon compte' : 'Se connecter'; ?></span></a>
        </nav>
    </div>
</header>
<button type="button" class="cdm-header__backdrop" aria-label="Fermer le menu" tabindex="-1"></button>
<script>
(function () {
    var toggle = document.querySelector('.cdm-header__toggle');
    var nav = document.getElementById('cdm-header-nav');
    var megas = nav.querySelectorAll('.cdm-mega');
    var openMega = function (mega, open) {
        mega.classList.toggle('is-open', open);
        mega.querySelector('.cdm-mega__btn').setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    var closeMegas = function (except) { megas.forEach(function (m) { if (m !== except) openMega(m, false); }); };
    var set = function (open) {
        nav.classList.toggle('is-open', open);
        document.body.classList.toggle('cdm-nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open) closeMegas();
    };
    toggle.addEventListener('click', function () { set(!nav.classList.contains('is-open')); });
    document.querySelector('.cdm-header__backdrop').addEventListener('click', function () { set(false); });
    megas.forEach(function (m) {
        m.querySelector('.cdm-mega__btn').addEventListener('click', function () {
            closeMegas(m);
            openMega(m, !m.classList.contains('is-open'));
        });
    });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) set(false); });
    document.addEventListener('click', function (e) { if (!e.target.closest('.cdm-header')) closeMegas(); });
    // Escape cierra y devuelve el foco al botón del panel abierto (patrón disclosure del W3C).
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var open = nav.querySelector('.cdm-mega.is-open');
        set(false);
        if (open) open.querySelector('.cdm-mega__btn').focus();
    });
})();
</script>
    <?php
});
