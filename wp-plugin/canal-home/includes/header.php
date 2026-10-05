<?php
/**
 * Cabecera y pie propios (los de la app local, layout/header.php y footer.php) en home, carte y ficha 2026.
 * El tema deja de pintar su cabecera con su filtro mylisting/header-config y la nuestra se imprime en su
 * hook mylisting/body/start; su pie mínimo se oculta por CSS (header.css) y el nuestro va en
 * mylisting/get-footer. El resto del sitio no cambia; desactivar el plugin los devuelve.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

function canal_header_is_page(): bool
{
    return canal_home_is_page() || canal_carte_is_page() || canal_fiche_is_page()
        || (function_exists('canal_planner_is_page') && canal_planner_is_page())
        || (function_exists('canal_contenu_is_page') && canal_contenu_is_page())
        || (function_exists('canal_calcul_is_page') && canal_calcul_is_page())
        || (function_exists('canal_archive_is_page') && canal_archive_is_page());
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
    // Etiquetas (TASK-040, docs/navbar-analisis-2026-10-01.md §7): cada opción una sola vez, sin títulos
    // de columna repetidos como enlace, guías separadas de los listados de prestatarios.
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
                    'Ports de plaisance (canal et littoral)' => '/categorie/ports/',
                    'Ports fluviaux' => '/categorie/ports-fluviaux/',
                    'Haltes nautiques' => '/categorie/halte-nautique/',
                    'Les 63 écluses : guide' => '/les-ecluses-du-canal-du-midi-2/',
                    'Annuaire des écluses' => '/categorie/ecluses/',
                    'Dimensions des écluses' => '/longeur-largeur-des-ecluses-tirant-deau-tirant-dair-sur-le-canal-du-midi/',
                ],
                "Naviguer, mode d'emploi" => [
                    'Période de navigation' => '/navigation/periode-de-navigation/',
                    'Règles de navigation' => '/navigation/regles-de-navigation/',
                    'Permis de conduire' => '/navigation/permis-de-conduire/',
                    'Les panneaux' => '/navigation/les-panneaux/',
                    'Passer une écluse' => '/navigation/passer-une-ecluse/',
                    'Se préparer au voyage en bateau' => '/canal-du-midi-en-bateau-comment-sy-preparer/',
                    // Sustituye al calculador de itinerarios de VNF (CIFL), cerrado en 2026.
                    'Avisbat : état du réseau (VNF)' => 'https://avisbat.vnf.fr/',
                ],
            ],
            'foot' => [],
            'carte' => 'nautique',
        ],
        'Vélo & balades' => [
            'cols' => [
                'Prestataires vélo' => [
                    'Location de vélo' => '/categorie/location-de-velo/',
                    'Voyage organisé à vélo' => '/categorie/organisation-de-voyage-a-velo/',
                    'Le canal à vélo' => '/categorie/velo/',
                ],
                'Guides vélo' => [
                    'Voie verte et véloroute' => '/voie-verte-et-veloroute/',
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
                    'Histoire' => '/le-canal/histoire/',
                    'Construction' => '/le-canal/construction/',
                    'Ouvrages' => '/le-canal/ouvrages/',
                    'Alimentation en eau' => '/alimentation-en-eau-du-canal/',
                    'La faune et la flore' => '/la-faune-et-la-flore/',
                    'Le Canal de la Robine' => '/canal-de-la-robine/',
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
                    'Campings' => '/categorie/camping/',
                    'Hôtels' => '/categorie/hotel/',
                    'Chambres à louer' => '/categorie/chambre-a-louer/',
                    "Chambres d'hôtes" => '/categorie/chambre-dhotes/',
                    'Appart-hôtels' => '/categorie/appartement-hotel/',
                ],
                'Locations' => [
                    'Gîtes' => '/categorie/gites/',
                    'Locations saisonnières' => '/categorie/location-saisonniere/',
                    'Appartements / Maisons' => '/categorie/appartement-maison-a-louer/',
                ],
                'Insolite & auberges' => [
                    'Hébergements insolites' => '/categorie/insolite/',
                    'Péniches' => '/categorie/peniche/',
                    'Roulottes' => '/categorie/roulotte/',
                    'Auberges collectives' => '/categorie/auberge-collective/',
                    'Auberges de jeunesse' => '/categorie/auberge-de-jeunesse/',
                    'Hostels' => '/categorie/hostel/',
                ],
            ],
            'foot' => ['Tous les hébergements' => '/categorie/hebergement/'],
            'carte' => 'hebergement',
        ],
        'Manger & Boire' => [
            'cols' => [
                'Restaurants & bars' => [
                    'Tous les restaurants & bars' => '/categorie/restauration-2/',
                    'Restaurants' => '/categorie/restaurant/',
                    'Brasseries / Snacks' => '/categorie/brasserie-snack/',
                    'Bateaux-restaurants' => '/categorie/bateau-restaurant/',
                    'Bars' => '/categorie/bar/',
                ],
                'Produits & vins' => [
                    'Tous les commerces alimentaires' => '/categorie/commerce-alimentaire/',
                    'Vente de vins' => '/categorie/vente-de-vins/',
                    'Produits régionaux' => '/categorie/produits-regionaux/',
                    'Boulangeries / Pâtisseries' => '/categorie/boulangerie-patisserie/',
                    'Supermarchés / Épiceries' => '/categorie/supermarche-epicerie/',
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
                'Sur place' => [
                    'Shopping' => '/categorie/shopping/',
                    'Librairies' => '/categorie/librairie/',
                    'Artisanat' => '/categorie/artisanat/',
                    'Commerces' => '/categorie/commerce/',
                    'Services' => '/categorie/services/',
                ],
                'Aide' => [
                    'Foire aux questions' => '/foire-aux-question-faq-canal-du-midi/',
                    "Lieux d'informations" => '/categorie/lieux-dinformations/',
                    'Associations du canal' => '/post-category/associations/',
                ],
            ],
            'foot' => [],
        ],
    ];
}

/**
 * Panel de la sección actual: el que enlaza a una categoría de la ficha (/categorie/<slug>/) o el del
 * ?type= de la carte. Gana el primero en el orden del menú. Las páginas 2026 no son destino de ningún
 * enlace del menú, así que no hay aria-current por ruta; solo la marca de sección.
 * ponytail: solo categorías que están en el menú; una subcategoría ausente no marca su panel.
 */
function canal_header_section(array $menu, array $cats, string $type): string
{
    foreach ($menu as $label => $panel) {
        if ($type !== '' && ($panel['carte'] ?? '') === $type) {
            return $label;
        }
        foreach ($panel['cols'] as $items) {
            foreach ($items as $path) {
                if (preg_match('~^/categorie/([^/]+)/$~', $path, $m) && in_array($m[1], $cats, true)) {
                    return $label;
                }
            }
        }
    }
    return '';
}

/** Pie propio (TASK-041): enlaces clave con anclas descriptivas, editor y contacto (E-E-A-T). */
function canal_footer_menu(): array
{
    return [
        'Explorer le canal' => [
            'Location de bateaux' => '/categorie/location-bateau/',
            'Location de vélos' => '/categorie/location-de-velo/',
            'Hébergements' => '/categorie/hebergement/',
            'Restaurants & bars' => '/categorie/restauration-2/',
            'Sites et monuments' => '/categorie/site-et-monument/',
            'Carte interactive' => CANAL_CARTE_PATH,
        ],
        'Préparer son séjour' => [
            'Calcul de distance' => '/calcul-de-distance-canal-du-midi/',
            'Météo du Canal du Midi' => '/meteo-du-canal-du-midi/',
            'Les 63 écluses du canal' => '/les-ecluses-du-canal-du-midi-2/',
            'Règles de navigation' => '/navigation/regles-de-navigation/',
            'Fêtes et manifestations' => '/manifestations-et-fetes-canal-du-midi/',
            'Foire aux questions' => '/foire-aux-question-faq-canal-du-midi/',
        ],
        'Le plan officiel' => [
            'Télécharger le plan (PDF)' => '/plan-canal-du-midi.pdf',
            'Recevoir le plan par courrier' => '/recevoir-le-plan-du-canal-du-midi-2/',
            'Boutique' => '/boutique-canal-du-midi/',
        ],
        'Contact' => [
            'Nous contacter' => '/nous-contacter/',
            '+33 4 68 62 31 62' => 'tel:+33468623162',
            'contact@azur-communications.fr' => 'mailto:contact@azur-communications.fr',
            'Mentions légales' => '/infos-legales/',
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
    $isHome  = canal_home_is_page();
    $isCarte = canal_carte_is_page();
    $menu    = canal_header_menu();
    $cats    = canal_fiche_is_page() ? array_column(canal_fiche_state()['categories'], 'slug') : [];
    $type    = $isCarte && isset($_GET['type']) && is_string($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : '';
    $section = canal_header_section($menu, $cats, $type);
    $here    = function (bool $current) { return $current ? ' aria-current="page"' : ''; };
    $i = 0;
    ?>
<header class="cdm-header">
    <div class="cdm-header__row">
        <a class="cdm-header__brand" href="<?php echo esc_url($home); ?>"<?php echo $here($isHome); ?>>
            <span class="cdm-header__mark" aria-hidden="true"></span>
            <span class="cdm-header__name">Canal du Midi</span>
        </a>
        <button class="cdm-header__toggle" type="button" aria-expanded="false" aria-controls="cdm-header-nav" aria-label="Menu">
            <span></span><span></span>
        </button>
        <nav id="cdm-header-nav" class="cdm-header__nav" aria-label="Navigation principale">
            <div class="cdm-header__menu">
            <?php foreach ($menu as $label => $panel) : $id = 'cdm-mega-' . (++$i); ?>
            <div class="cdm-mega<?php echo $label === $section ? ' is-current' : ''; ?>">
                <button class="cdm-mega__btn" type="button" aria-expanded="false" aria-controls="<?php echo $id; ?>"><?php echo esc_html($label); ?><?php echo $label === $section ? '<span class="cdm-sr"> (rubrique actuelle)</span>' : ''; ?><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                <div class="cdm-mega__panel" id="<?php echo $id; ?>">
                    <div class="cdm-mega__cols">
                        <?php foreach ($panel['cols'] as $title => $items) : ?>
                        <div class="cdm-mega__col">
                            <p class="cdm-mega__title"><?php echo esc_html($title); ?></p>
                            <ul>
                                <?php foreach ($items as $text => $path) : $ext = $path[0] !== '/'; ?>
                                <li><a href="<?php echo esc_url(canal_header_url($path)); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($text); ?><?php echo $ext ? ' <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="cdm-sr"> (nouvel onglet)</span>' : ''; ?></a></li>
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
            </div>
            <div class="cdm-header__tools">
            <a class="cdm-header__link" href="<?php echo esc_url(home_url(CANAL_HEADER_DISTANCE_PATH)); ?>" title="Calcul de distance"><i class="bi bi-rulers" aria-hidden="true"></i><span class="cdm-header__label">Distances</span></a>
            <a class="cdm-header__link cdm-header__link--carte" href="<?php echo esc_url($carte); ?>" title="Carte interactive"<?php echo $here($isCarte); ?>><i class="bi bi-map" aria-hidden="true"></i><span class="cdm-header__label">Carte</span></a>
            <a class="cdm-header__cta" href="<?php echo esc_url(home_url(CANAL_PLANNER_PATH)); ?>"><i class="bi bi-magic" aria-hidden="true"></i><span>Planifier<span class="cdm-header__cta-more"> mon voyage</span></span></a>
            <a class="cdm-header__account" href="<?php echo esc_url($account); ?>" title="<?php echo is_user_logged_in() ? 'Mon compte' : 'Se connecter'; ?>"><i class="bi bi-person" aria-hidden="true"></i><span><?php echo is_user_logged_in() ? 'Mon compte' : 'Se connecter'; ?></span></a>
            </div>
        </nav>
        <?php /* Accesos rápidos: la lupa siempre; Distances y Carte solo en móvil (en escritorio están en la barra). */ ?>
        <div class="cdm-header__quick">
            <a class="cdm-header__icon cdm-header__icon--m" href="<?php echo esc_url(home_url(CANAL_HEADER_DISTANCE_PATH)); ?>" aria-label="Calcul de distance"><i class="bi bi-rulers" aria-hidden="true"></i></a>
            <a class="cdm-header__icon cdm-header__icon--m" href="<?php echo esc_url($carte); ?>" aria-label="Carte interactive"<?php echo $here($isCarte); ?>><i class="bi bi-map" aria-hidden="true"></i></a>
            <div class="cdm-search">
                <button class="cdm-header__icon" type="button" aria-expanded="false" aria-controls="cdm-search" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
                <div class="cdm-search__panel" id="cdm-search">
                    <form class="cdm-search__form" role="search" method="get" action="<?php echo esc_url($carte); ?>">
                        <label class="cdm-sr" for="cdm-search-q">Rechercher sur le Canal du Midi</label>
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="cdm-search-q" type="search" name="search_keywords" maxlength="100" required
                               placeholder="Un prestataire, une ville, une activité…" autocomplete="off" enterkeyhint="search">
                        <button type="submit">Rechercher</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<button type="button" class="cdm-header__backdrop" aria-label="Fermer le menu" tabindex="-1"></button>
<script>
(function () {
    var header = document.querySelector('.cdm-header');
    var toggle = header.querySelector('.cdm-header__toggle');
    var nav = document.getElementById('cdm-header-nav');
    // Paneles « disclosure » (W3C): los del mega-menú y el de la búsqueda; el botón es el primer hijo.
    var megas = header.querySelectorAll('.cdm-mega, .cdm-search');
    var openMega = function (mega, open) {
        mega.classList.toggle('is-open', open);
        mega.firstElementChild.setAttribute('aria-expanded', open ? 'true' : 'false');
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
        m.firstElementChild.addEventListener('click', function () {
            var open = !m.classList.contains('is-open');
            if (!nav.contains(m)) set(false); // la búsqueda cierra el cajón del móvil
            closeMegas(m);
            openMega(m, open);
            var input = open && m.querySelector('input');
            if (input) input.focus();
        });
    });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) set(false); });
    document.addEventListener('click', function (e) { if (!e.target.closest('.cdm-header')) closeMegas(); });
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    // Escape cierra y devuelve el foco al botón del panel abierto (patrón disclosure del W3C).
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var open = header.querySelector('.cdm-mega.is-open, .cdm-search.is-open');
        set(false);
        if (open) open.firstElementChild.focus();
    });
})();
</script>
    <?php
});

// Pie propio al final de la página (tras el pie mínimo del tema, que header.css oculta en estas páginas).
add_action('mylisting/get-footer', function () {
    if (!canal_header_is_page()) {
        return;
    }
    ?>
<footer class="cdm-footer">
    <div class="cdm-footer__grid">
        <div class="cdm-footer__about">
            <a class="cdm-header__brand" href="<?php echo esc_url(home_url(CANAL_HOME_PATH)); ?>">
                <span class="cdm-header__mark" aria-hidden="true"></span>
                <span><?php echo esc_html(CANAL_HOME_SITE_NAME); ?></span>
            </a>
            <p>Guide pratique et plan officiel du Canal du Midi, de Toulouse à l'étang de Thau : 240 km, 63 écluses,
                patrimoine mondial de l'UNESCO. Édité chaque année par Azur Communications.</p>
            <div class="cdm-footer__social">
                <a href="<?php echo esc_url(CANAL_HOME_FACEBOOK); ?>" target="_blank" rel="noopener me" aria-label="Facebook (nouvel onglet)"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                <a href="<?php echo esc_url(CANAL_HOME_INSTAGRAM); ?>" target="_blank" rel="noopener me" aria-label="Instagram (nouvel onglet)"><i class="bi bi-instagram" aria-hidden="true"></i></a>
            </div>
        </div>
        <?php foreach (canal_footer_menu() as $title => $items) : ?>
        <nav class="cdm-footer__col" aria-label="<?php echo esc_attr($title); ?>">
            <p class="cdm-footer__title"><?php echo esc_html($title); ?></p>
            <ul>
                <?php foreach ($items as $text => $path) : ?>
                <li><a href="<?php echo esc_url($path[0] === '/' ? home_url($path) : $path); ?>"><?php echo esc_html($text); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endforeach; ?>
    </div>
    <div class="cdm-footer__bottom">
        <span>© <?php echo esc_html(gmdate('Y') . ' ' . CANAL_HOME_SITE_NAME); ?></span>
        <span>Le Canal du Midi est inscrit au patrimoine mondial de l'UNESCO depuis 1996.</span>
    </div>
</footer>
    <?php
}, 1);
