<?php
/**
 * Plantilla « Accueil 2026 » — cabecera y pie del tema (menú + pubs intactos).
 * Textos validados por el usuario (spec 2026-09-28).
 */
defined('ABSPATH') || exit;

$heroImage  = home_url(CANAL_HOME_HERO_IMAGE);
$heroTypes  = canal_home_hero_types();
$heroStages = canal_home_stages();
$categories = canal_home_categories(6);
$sejours    = canal_home_sejours(4);
$link   = function (string $path): string { return esc_url(home_url($path)); };
$upload = function (string $path): string { return esc_url(home_url('/wp-content/uploads/' . $path)); };

get_header();
?>
<div class="cdm-home">
<main id="top">
    <!-- 1. HERO -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-card" data-reveal="zoom">
                <div class="hero-card-media">
                    <img class="hero-card-img" src="<?= esc_url($heroImage) ?>" alt="Le Canal du Midi bordé de platanes" loading="eager">
                    <div class="hero-card-overlay"></div>
                </div>
                <div class="hero-card-content">
                    <div class="eyebrow">L'Officiel du Canal du Midi</div>
                    <h1>Explorez le Canal du Midi,<br>de Toulouse à la <em>Méditerranée</em></h1>
                    <p style="color:#fff;">
                        Hébergements, location de bateaux et de vélos, restaurants, visites : trouvez les meilleures
                        adresses le long du canal et préparez votre séjour en toute liberté.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat"><strong>240 km</strong><span>de voie navigable</span></div>
                        <div class="hero-stat"><strong>63 écluses</strong><span>de génie hydraulique</span></div>
                        <div class="hero-stat"><strong>1681</strong><span>année de création</span></div>
                        <div class="hero-stat"><strong>UNESCO</strong><span>patrimoine mondial</span></div>
                    </div>
                </div>

                <form class="hero-search" id="home-search-form" action="<?= $link('/explorer/') ?>" method="GET">
                    <input type="hidden" name="type" value="prestataires-touristiques">
                    <div class="search-field search-field-primary">
                        <span class="search-field-head">
                            <span class="search-field-icon"><i class="bi bi-search"></i></span>
                            <span class="search-field-copy"><span class="search-field-label">Que cherchez-vous ?</span></span>
                        </span>
                        <input type="text" name="search_keywords" id="home-search-input" class="search-field-input"
                               list="home-search-suggestions" autocomplete="off"
                               placeholder="Hôtel, location de bateau, vélo…">
                    </div>

                    <label class="search-field">
                        <span class="search-field-head">
                            <span class="search-field-icon"><i class="bi bi-geo-alt"></i></span>
                            <span class="search-field-copy"><span class="search-field-label">Destination</span></span>
                        </span>
                        <span class="search-field-select-wrap">
                            <?php // /explorer/ no filtra por taxonomía region: geocodifica search_location (radio 10 km). ?>
                            <select name="search_location" class="search-field-input">
                                <option value="">Toutes les étapes</option>
                                <?php foreach ($heroStages as $name): ?>
                                    <option value="<?= esc_attr($name) ?>"><?= esc_html($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="search-field-select-caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                        </span>
                    </label>

                    <label class="search-field">
                        <span class="search-field-head">
                            <span class="search-field-icon"><i class="bi bi-sliders"></i></span>
                            <span class="search-field-copy"><span class="search-field-label">Type</span></span>
                        </span>
                        <span class="search-field-select-wrap">
                            <select name="category" class="search-field-input">
                                <option value="">Tous les types</option>
                                <?php foreach ($heroTypes as $slug => $name): ?>
                                    <option value="<?= esc_attr($slug) ?>"><?= esc_html($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="search-field-select-caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                        </span>
                    </label>

                    <div class="search-buttons-group">
                        <button class="button hero-search-submit" type="submit"><i class="bi bi-search"></i> Rechercher</button>
                        <button type="button" id="home-ai-btn" class="button ai-magic-btn" title="Utiliser l'IA">
                            <i class="bi bi-stars"></i> Assistant IA
                        </button>
                    </div>
                </form>
            </div>

            <datalist id="home-search-suggestions">
                <option value="Location de bateau sans permis"></option>
                <option value="Location de vélo"></option>
                <option value="Chambre d'hôtes au bord du canal"></option>
                <option value="Restaurant à Carcassonne"></option>
            </datalist>

            <div id="home-ai-modal" class="hero-ai-modal" aria-hidden="true">
                <div class="hero-ai-dialog" role="dialog" aria-modal="true" aria-labelledby="home-ai-modal-title">
                    <button type="button" class="hero-ai-close" data-close-home-ai aria-label="Fermer la fenêtre IA">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="hero-ai-kicker">Assistant IA</div>
                    <h2 id="home-ai-modal-title">Décrivez votre séjour idéal</h2>
                    <p>Expliquez librement ce que vous cherchez : l'assistant sélectionne les adresses du canal qui vous correspondent le mieux.</p>
                    <form id="home-ai-modal-form" class="hero-ai-form">
                        <label class="hero-ai-label" for="home-ai-prompt">Votre demande</label>
                        <textarea id="home-ai-prompt" class="hero-ai-textarea" rows="5" maxlength="500"
                                  placeholder="Ex : un week-end en amoureux près de Carcassonne avec une balade en bateau"></textarea>
                        <p id="home-ai-feedback" class="hero-ai-feedback" aria-live="polite"></p>
                        <div id="home-ai-results" class="cdm-ai-results" aria-live="polite"></div>
                        <div class="hero-ai-actions">
                            <button type="button" class="button button-ghost" data-close-home-ai>Fermer</button>
                            <button type="submit" id="home-ai-submit" class="button">
                                <i class="bi bi-stars"></i> <span>Trouver mes adresses</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. DESTINATIONS -->
    <section id="destinations" class="section section-tight">
        <div class="container">
            <div class="section-heading center" data-reveal="up">
                <div class="eyebrow">À découvrir</div>
                <h2>Que faire le long du canal ?</h2>
                <p>Plus de 250 prestataires sélectionnés, classés par activité, pour composer votre séjour.</p>
            </div>
            <div class="destination-grid" data-reveal-stagger>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= esc_url($cat['url']) ?>" class="destination-card-link">
                        <article class="destination-card">
                            <span class="destination-card-media" style="background-image: linear-gradient(180deg, transparent 40%, rgba(14, 20, 36, 0.88)), url('<?= esc_url($cat['image']) ?>');"></span>
                            <span class="pill"><?= (int) $cat['count'] ?> adresse<?= $cat['count'] > 1 ? 's' : '' ?></span>
                            <h3><?= esc_html($cat['name']) ?></h3>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="cdm-center-cta">
                <a href="<?= $link('/explorer/') ?>" class="button"><i class="bi bi-map"></i> Voir tous les prestataires sur la carte</a>
            </div>
        </div>
    </section>

    <!-- 3. EXPÉRIENCES -->
    <section id="experiences" class="section section-alt">
        <div class="container split-layout">
            <div class="stacked-photos" data-reveal="left">
                <figure class="photo-card photo-large">
                    <img src="<?= $upload('2024/04/Dominique_VIET_CRTLOccitanie_0017338_MD_RET3-1.jpg') ?>" alt="Le Canal du Midi bordé d'arbres" loading="lazy">
                    <figcaption class="photo-credit">© D. Viet / CRTL Occitanie</figcaption>
                </figure>
                <figure class="photo-card photo-small top">
                    <img src="<?= $upload('2022/03/peniche_toulouse.jpg') ?>" alt="Péniche amarrée à Toulouse" loading="lazy">
                </figure>
                <figure class="photo-card photo-small bottom">
                    <img src="<?= $upload('2020/04/rando-velo_2.webp') ?>" alt="Balade à vélo le long du canal" loading="lazy">
                </figure>
            </div>
            <div class="split-copy" data-reveal="right">
                <div class="eyebrow">Votre séjour</div>
                <h2>Préparer et profiter de votre séjour</h2>
                <p>
                    Site unique inscrit au patrimoine mondial de l'UNESCO, le Canal du Midi se découvre à son rythme :
                    en péniche avec ou sans permis, à vélo sur les chemins de halage, ou d'étape en étape entre
                    villages, vignobles et cités historiques.
                </p>
                <ul class="check-list">
                    <li>Location de bateaux avec ou sans permis</li>
                    <li>Location de vélos et voyages à vélo organisés</li>
                    <li>Hébergements, restaurants et producteurs locaux</li>
                    <li>Calcul des distances et temps de trajet entre écluses</li>
                </ul>
                <div class="contact-strip">
                    <a class="button button-soft" href="<?= $link('/organiser-votre-sejour/') ?>">Organiser votre séjour</a>
                    <a class="button button-soft muted" href="<?= $link('/calcul-de-distance-canal-du-midi/') ?>">Calculer une distance</a>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. SÉJOURS -->
    <?php if ($sejours): ?>
    <section class="section">
        <div class="container">
            <div class="section-heading" data-reveal="up">
                <div class="eyebrow">Sur l'eau et à vélo</div>
                <h2>Croisières, balades et excursions</h2>
            </div>
            <div class="tour-grid">
                <?php foreach ($sejours as $s): ?>
                    <a href="<?= esc_url($s['url']) ?>">
                        <article class="tour-card">
                            <img src="<?= esc_url($s['image']) ?>" alt="<?= esc_attr($s['title']) ?>" loading="lazy">
                            <div class="tour-body">
                                <h3><?= esc_html($s['title']) ?></h3>
                                <div class="tour-meta">
                                    <?php if ($s['category'] !== ''): ?><span><?= esc_html($s['category']) ?></span><?php endif; ?>
                                    <?php if ($s['city'] !== ''): ?><span><?= esc_html($s['city']) ?></span><?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 5. BANDE IMMERSIVE -->
    <section class="immersive-band">
        <div class="container band-inner" data-reveal="zoom">
            <h2>Votre séjour sur mesure, sans chercher</h2>
            <p>Indiquez vos dates, vos envies et le secteur qui vous intéresse : votre demande est transmise directement aux prestataires du canal qui y correspondent.</p>
            <a class="button" href="<?= $link('/organiser-votre-sejour/') ?>">Envoyer ma demande</a>
        </div>
    </section>

    <!-- 6. POURQUOI -->
    <section id="why-us" class="section section-wave-top">
        <div class="container">
            <div class="section-heading center" data-reveal="up">
                <div class="eyebrow">Nos atouts</div>
                <h2>Pourquoi « L'Officiel du Canal du Midi » ?</h2>
            </div>
            <div class="feature-grid" data-reveal-stagger>
                <article class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Des prestataires locaux</h3>
                    <p>Des professionnels installés le long du canal, de Toulouse à l'étang de Thau.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Le plan officiel</h3>
                    <p>Écluses, ports, services et points d'intérêt réunis sur un plan édité chaque année.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Des outils pratiques</h3>
                    <p>Carte interactive, calcul de distance, règles de navigation et météo du canal.</p>
                </article>
            </div>
            <div class="offer-grid" data-reveal-stagger>
                <article class="offer-card blue">
                    <div>
                        <span class="offer-kicker">En bateau</span>
                        <h3>Naviguer sur le canal</h3>
                    </div>
                    <a class="button button-small button-white" href="<?= $link('/navigation/regles-de-navigation/') ?>">Les règles de navigation</a>
                </article>
                <article class="offer-card sand">
                    <div>
                        <span class="offer-kicker">À vélo</span>
                        <h3>Voie verte et véloroute</h3>
                    </div>
                    <a class="button button-small button-white" href="<?= $link('/voie-verte-et-veloroute/') ?>">Préparer ma balade</a>
                </article>
            </div>
        </div>
    </section>

    <!-- 7. PLAN -->
    <section id="plan" class="section newsletter-section">
        <div class="container newsletter-box" data-reveal="up">
            <div class="plan-viewer" style="flex:0 0 480px;max-width:100%;">
                <img src="<?= $upload('2026/05/couv_canal_du_midi_2026.png') ?>"
                     alt="Plan du Canal du Midi 2026 — cliquez pour le feuilleter" role="button" tabindex="0"
                     style="display:block;width:100%;max-width:480px;height:400px;object-fit:contain;border:0;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.12);margin:0 auto;cursor:pointer;"
                     data-open-plan-modal>
            </div>
            <div>
                <div class="eyebrow">Plan officiel 2026</div>
                <h2>Le plan du Canal du Midi 2026</h2>
                <p style="color:var(--muted);margin-bottom:1rem;">Toutes les écluses, ports, services et étapes de Toulouse à la Méditerranée.</p>
                <div class="plan-actions">
                    <a href="<?= $upload('pdf/Plan-Canal-du-Midi-2026.pdf') ?>" class="btn-pdf" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-arrow-down"></i> Télécharger le plan gratuit (PDF)
                    </a>
                    <a href="<?= $link('/recevoir-le-plan-du-canal-du-midi-2/') ?>" class="button button-ghost">
                        <i class="bi bi-envelope"></i> Recevoir le plan par courrier
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div id="plan-modal" style="display: none;" aria-hidden="true">
        <div class="plan-modal-content" role="dialog" aria-modal="true" aria-labelledby="plan-modal-title" style="max-width: 58rem;">
            <button type="button" class="plan-modal-close" data-close-plan-modal aria-label="Fermer la fenêtre du plan">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="plan-modal-title">Plan du Canal du Midi 2026</h2>
            <iframe data-src="https://v.calameo.com/?bkcode=003331405edc35288442a&amp;mode=mini"
                    width="480" height="400" allowfullscreen referrerpolicy="no-referrer"
                    sandbox="allow-scripts allow-same-origin allow-popups allow-forms" scrolling="no"
                    style="display:block;width:100%;max-width:67rem;height:43rem;border:0;border-radius:12px;margin:0 auto;"
                    title="Plan du Canal du Midi 2026 — Calaméo" loading="lazy"></iframe>
        </div>
    </div>
</main>
</div>
<?php
get_footer();
