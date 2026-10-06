<?php
/**
 * Calcul de distance 2026 (TASK-057) — maqueta docs/mockups/calcul-distance-2026.html.
 * El servidor pinta el trayecto de ?de=&a= (sin JS funciona y se rastrea); calcul.js recalcula al escribir.
 */
defined('ABSPATH') || exit;

$c = canal_calcul_state();
$r = $c['r'];
$pk = function (float $v): string { return 'PK ' . number_format($v, 1, ',', ''); };
$carte = function (string $type) use ($c): string {
    return esc_url(add_query_arg(['type' => $type, 'search_location' => $c['arrival']['search']], home_url(CANAL_CARTE_PATH)));
};
$photos = [
    ['location-bateau', 'Bateau', 'Louer un bateau', '/wp-content/uploads/2023/02/00Peniche-768x576.jpeg'],
    ['hebergement', 'Se loger', 'Où dormir', '/wp-content/uploads/2019/11/la-marelle-chambre-bleue-big-768x576.jpg'],
    ['location-de-velo', 'Vélo', 'Louer un vélo', '/wp-content/uploads/2019/11/location-de-v%C3%A9los-768x512.jpeg'],
];
$icon = [
    'boat' => '<path d="M3 17l2 3h14l2-3H3zM5 17V11h14v6M8 11V7h8v4M12 4v3"/>',
    'bike' => '<circle cx="6" cy="16" r="3.5"/><circle cx="18" cy="16" r="3.5"/><path d="M6 16l4-8h5l3 8M10 8l2 8h-6M14 5h3"/>',
    'walk' => '<circle cx="13" cy="4.5" r="1.8"/><path d="M10 21l2-6 3 3v3M9 12l3-4 3 3 3 1M12 8l-1 5"/>',
];
$modes = [
    ['boat', 'En bateau', canal_calcul_duration($r['boat']), canal_calcul_days_boat($r['boat'])],
    ['bike', 'À vélo', canal_calcul_duration($r['bike']), canal_calcul_days_bike($r['km'])],
    ['walk', 'À pied', canal_calcul_duration($r['walk']), canal_calcul_days_walk($r['km'])],
];

get_header();
?>
<div class="cdm-calcul">
<main>
  <section class="calc-hero">
    <div class="calc-container">
      <nav class="calc-crumbs" aria-label="Fil d'Ariane"><a href="<?= esc_url(home_url(CANAL_HOME_PATH)) ?>">Accueil</a> › <span aria-current="page">Calcul de distance</span></nav>
      <div class="calc-head">
        <div class="calc-eyebrow">Outil gratuit</div>
        <h1>Calcul de distance sur le Canal du Midi</h1>
        <p>Distance, écluses et temps de trajet en bateau, à vélo ou à pied entre deux villes, ports ou écluses, de Toulouse à l'étang de Thau.</p>
      </div>

      <div class="calc-card" role="region" aria-label="Calculateur de distance">
        <form class="calc-fields" method="get" action="<?= esc_url($c['url']) ?>" id="calc-form">
          <div class="calc-field">
            <label for="calc-from">Départ</label>
            <input id="calc-from" name="de" list="calc-places" value="<?= esc_attr($c['from']) ?>" autocomplete="off" required>
          </div>
          <button type="button" class="calc-swap" id="calc-swap" aria-label="Inverser départ et arrivée">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h12l-3-3M17 17H5l3 3"/></svg>
          </button>
          <div class="calc-field">
            <label for="calc-to">Arrivée</label>
            <input id="calc-to" name="a" list="calc-places" value="<?= esc_attr($c['to']) ?>" autocomplete="off" required>
          </div>
          <noscript><button type="submit" class="calc-submit">Calculer</button></noscript>
          <datalist id="calc-places">
            <?php foreach (array_keys(canal_calcul_places()) as $name): ?><option value="<?= esc_attr($name) ?>"><?php endforeach; ?>
          </datalist>
        </form>

        <div class="calc-result" aria-live="polite">
          <div class="calc-summary">
            <div class="calc-km" id="calc-km"><?= (int) round($r['km']) ?><small>km</small></div>
            <p class="calc-locks" id="calc-locks"><?= esc_html(canal_calcul_locks_label($r['sites'], $r['sas'])) ?></p>
            <p class="calc-sentence" id="calc-sentence"><?= esc_html($c['sentence']) ?></p>
          </div>
          <div class="calc-modes">
            <?php foreach ($modes as $m): ?>
              <div class="calc-mode calc-mode--<?= $m[0] ?>">
                <div class="calc-mode-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon[$m[0]] // phpcs:ignore — SVG fijo. ?></svg></div>
                <h2><?= esc_html($m[1]) ?></h2>
                <div class="calc-mode-txt"><div class="calc-time" id="calc-t-<?= $m[0] ?>"><?= esc_html($m[2]) ?></div><div class="calc-days" id="calc-d-<?= $m[0] ?>"><?= esc_html($m[3]) ?></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="calc-map-box is-lazy" id="calc-map-box"><?php // WP: hueco reservado desde el HTML (CLS); calcul.js lo oculta solo si no hay clave de Maps. ?>
          <div id="calc-map" role="img" aria-label="Carte du trajet sur le Canal du Midi"></div>
          <button type="button" class="calc-map-open" id="calc-map-open"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2zM9 4v14M15 6v14"/></svg> Voir le trajet sur la carte</button>
          <span class="calc-map-credit">Tracé du canal © OpenStreetMap</span>
        </div>
        <div class="calc-map-legend"><span><i style="background:#6a63d9"></i>Votre trajet</span><span><i style="background:#c9c4e6"></i>Canal du Midi</span><span>◦ Écluses du trajet (cliquez pour la fiche)</span></div>

        <details class="calc-locks-list" id="calc-locks-list"<?= $c['route'] ? '' : ' hidden' ?>>
          <summary><span id="calc-locks-title">Les <?= count($c['route']) ?> écluse<?= count($c['route']) > 1 ? 's' : '' ?> du trajet <small>dans l'ordre de passage</small></span></summary>
          <ol id="calc-locks-ol">
            <?php foreach ($c['route'] as $l): ?>
              <li>
                <?php if ($l['photo'] !== ''): ?><img class="calc-thumb" src="<?= esc_url($l['photo']) ?>" alt="" width="64" height="46" loading="lazy"><?php else: ?><span class="calc-thumb calc-thumb--none" aria-hidden="true">◦</span><?php endif; ?>
                <span class="calc-lock-name">
                  <?php if ($l['url'] !== ''): ?><a href="<?= esc_url($l['url']) ?>"><?= esc_html($l['name']) ?></a><?php else: ?><?= esc_html($l['name']) ?><?php endif; ?>
                  <span class="calc-pk"><?= esc_html($pk($l['pk'])) ?><?php if ($l['sas'] > 1): ?> <span class="calc-sas">· <?= (int) $l['sas'] ?> sas</span><?php endif; ?></span>
                </span>
              </li>
            <?php endforeach; ?>
          </ol>
        </details>

        <div class="calc-foot">
          <span>Temps indicatifs : bateau 7 km/h + 10 min par sas d'écluse (comme les loueurs), vélo 15 km/h, à pied 4 km/h.</span>
          <button type="button" class="calc-share" id="calc-share"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a5 5 0 007 0l3-3a5 5 0 00-7-7l-1 1M14 10a5 5 0 00-7 0l-3 3a5 5 0 007 7l1-1"/></svg> <span>Copier le lien du trajet</span></button>
        </div>
      </div>
    </div>
  </section>

  <section class="calc-section calc-section--tight">
    <div class="calc-container">
      <div class="calc-section-head">
        <div class="calc-eyebrow">À l'arrivée</div>
        <h2 id="calc-arrival-title"><?= esc_html($c['arrival']['title']) ?></h2>
      </div>
      <div class="calc-photos" id="calc-photos">
        <?php foreach ($photos as $p): ?>
          <a class="calc-photo" href="<?= $carte($p[0]) // phpcs:ignore — esc_url arriba. ?>" data-type="<?= esc_attr($p[0]) ?>"<?= canal_carte_count(['type' => $p[0], 'search_location' => $c['arrival']['search']]) ? '' : ' hidden' ?>>
            <img src="<?= esc_url(home_url($p[3])) ?>" alt="" loading="lazy" width="768" height="512">
            <span><small><?= esc_html($p[1]) ?></small><strong><?= esc_html($p[2]) ?></strong></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="calc-section calc-section--tight">
    <div class="calc-container">
      <div class="calc-section-head">
        <div class="calc-eyebrow">Tableau des distances</div>
        <h2>Distances entre les villes du Canal du Midi</h2>
        <p>En kilomètres par le canal. Cliquez sur une distance pour voir les écluses et les temps de trajet.</p>
      </div>
      <div class="calc-table-wrap">
        <table id="calc-matrix">
          <thead><tr><th></th><?php foreach (array_keys($c['matrix']) as $col): ?><th scope="col"><?= esc_html($col) ?></th><?php endforeach; ?></tr></thead>
          <tbody>
            <?php $full = array_combine(array_keys($c['matrix']), CANAL_CALCUL_MATRIX); ?>
            <?php foreach ($c['matrix'] as $row => $cols): ?>
              <tr>
                <th scope="row"><?= esc_html($row) ?></th>
                <?php foreach ($cols as $col => $km): ?>
                  <?php if ($row === $col): ?><td class="calc-diag">—</td>
                  <?php else: ?><td><a href="<?= esc_url(add_query_arg(['de' => $full[$row], 'a' => $full[$col]], $c['url'])) ?>" data-de="<?= esc_attr($full[$row]) ?>" data-a="<?= esc_attr($full[$col]) ?>"><?= (int) $km ?></a></td><?php endif; ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="calc-table-note">Points kilométriques (PK) depuis le port de l'Embouchure à Toulouse.</p>
    </div>
  </section>

  <section class="calc-section calc-section--tight">
    <div class="calc-container">
      <div class="calc-section-head">
        <div class="calc-eyebrow">Bon à savoir</div>
        <h2>Avant de partir</h2>
      </div>
      <div class="calc-tips">
        <div class="calc-tip">
          <div class="calc-tip-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
          <h3>Horaires des écluses</h3>
          <p>En haute saison (mai à septembre), de 9h à 12h30 et de 13h30 à 19h. Comptez environ 6 heures de navigation par jour.</p>
          <a href="<?= esc_url(home_url('/navigation/regles-de-navigation' . CANAL_CONTENU_SUFFIX . '/')) ?>">Règles de navigation →</a>
        </div>
        <div class="calc-tip">
          <div class="calc-tip-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l9 16H3L12 3zM12 10v4M12 17h.01"/></svg></div>
          <h3>Fermetures et travaux</h3>
          <p>Chômages d'hiver, travaux ou sécheresse peuvent interrompre la navigation. Vérifiez les avis à la batellerie avant de partir.</p>
          <a href="https://avisbat.vnf.fr/" target="_blank" rel="noopener">Avisbat (VNF) ↗</a>
        </div>
        <div class="calc-tip">
          <div class="calc-tip-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon['bike'] // phpcs:ignore — SVG fijo. ?></svg></div>
          <h3>À vélo : traces et trains</h3>
          <p>Le chemin de halage fait partie du Canal des 2 Mers à vélo : étapes, traces GPX et gares pour revenir en train.</p>
          <a href="https://www.francevelotourisme.com/itineraire/le-canal-des-2-mers-a-velo" target="_blank" rel="noopener">France Vélo Tourisme ↗</a>
        </div>
      </div>
    </div>
  </section>
</main>
</div>
<?php
get_footer();
