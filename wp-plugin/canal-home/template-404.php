<?php
/**
 * Página 404 del sitio publicado (TASK-064): misma cabecera y pie que las páginas 2026, buscador hacia la carte
 * y accesos a lo más usado. El estado HTTP sigue siendo 404 (lo fija WordPress).
 */
defined('ABSPATH') || exit;

$links = [
    ['bi-map', 'Carte interactive', 'Ports, écluses, hébergements et activités', home_url(CANAL_CARTE_PATH)],
    ['bi-rulers', 'Calcul de distance', 'Distances et temps en bateau ou à vélo', home_url('/' . CANAL_CALCUL_SLUG . CANAL_CONTENU_SUFFIX . '/')],
    ['bi-geo-alt', 'Les étapes du canal', 'Villes et villages de Toulouse à la Méditerranée', home_url(CANAL_ETAPES_PATH)],
    ['bi-file-earmark-arrow-down', 'Plan du canal (PDF)', 'Le plan officiel, gratuit', home_url(CANAL_PLAN_PDF_PATH)],
];

get_header();
?>
<div class="cdm-contenu">
<main class="contenu">
    <div class="contenu-wrap">
        <header class="contenu-head">
            <h1>Page introuvable</h1>
            <p class="contenu-dates">Cette adresse n'existe pas ou a changé. Cherchez un prestataire, une ville ou une écluse :</p>
        </header>
        <form class="contenu-404-search" action="<?= esc_url(home_url(CANAL_CARTE_PATH)) ?>" method="get" role="search">
            <label for="contenu-404-q" class="screen-reader-text">Rechercher sur la carte</label>
            <input id="contenu-404-q" name="q" type="search" placeholder="Ex. : location de bateau, Le Somail, écluse de Bram" autocomplete="off">
            <button type="submit">Rechercher</button>
        </form>
        <ul class="contenu-tools contenu-404-links">
            <?php foreach ($links as $l): ?>
                <li>
                    <a href="<?= esc_url($l[3]) ?>">
                        <i class="bi <?= esc_attr($l[0]) ?>" aria-hidden="true"></i>
                        <span><strong><?= esc_html($l[1]) ?></strong><small><?= esc_html($l[2]) ?></small></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="etape-back"><a href="<?= esc_url(home_url(CANAL_HOME_PATH)) ?>">← Retour à l'accueil</a></p>
    </div>
</main>
</div>
<?php
get_footer();
