<?php
/**
 * Índice de las étapes 2026 (TASK-060): tarjetas en orden de PK, Canal du Midi y canal de la Robine.
 */
defined('ABSPATH') || exit;

$s = canal_etape_state();
$sections = ['midi' => 'Le Canal du Midi, de Toulouse à l’étang de Thau', 'robine' => 'Le canal de la Robine, vers Narbonne et la Méditerranée'];

get_header();
?>
<div class="cdm-contenu cdm-etape">
<main class="contenu">
    <div class="contenu-wrap">
        <nav class="contenu-crumbs" aria-label="Fil d'Ariane">
            <ol>
                <li><a href="<?= esc_url(home_url(CANAL_HOME_PATH)) ?>">Accueil</a></li>
                <li><span aria-current="page">Étapes du Canal du Midi</span></li>
            </ol>
        </nav>
        <header class="contenu-head">
            <h1>Les étapes du Canal du Midi</h1>
            <p class="contenu-dates"><?= count($s['index']['midi']) + count($s['index']['robine']) ?> villes et villages : ports, écluses, hébergements, location de bateaux et distances.</p>
        </header>

        <?php foreach ($sections as $key => $title): ?>
            <section class="etape-section" aria-labelledby="etapes-<?= esc_attr($key) ?>">
                <h2 id="etapes-<?= esc_attr($key) ?>"><?= esc_html($title) ?></h2>
                <ol class="etape-index">
                    <?php foreach ($s['index'][$key] as $it): ?>
                        <li>
                            <a href="<?= esc_url($it['url']) ?>">
                                <img src="<?= esc_url($it['image']) ?>" alt="" loading="lazy" width="384" height="256">
                                <span class="etape-card-body">
                                    <strong><?= esc_html($it['e']['name']) ?></strong>
                                    <small><?= esc_html($it['lead']) ?></small>
                                    <?php if ($it['count']): ?><em><?= (int) $it['count'] ?> prestataires</em><?php endif; ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endforeach; ?>
    </div>
</main>
</div>
<?php
get_footer();
