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
$sejours    = canal_home_sejours();
canal_home_seo_items($sejours); // para el ItemList del JSON-LD (wp_head va en get_header())
$link   = function (string $path): string { return esc_url(home_url($path)); };
$upload = function (string $path): string { return esc_url(home_url('/wp-content/uploads/' . $path)); };

get_header();
?>
<div class="cdm-home">
<main id="top">
    <!-- 1. HERO -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-card">
                <div class="hero-card-media">
                    <img class="hero-card-img" src="<?= esc_url($heroImage) ?>" alt="Le Canal du Midi bordé de platanes" width="1898" height="682" loading="eager" fetchpriority="high" decoding="async"<?= ($heroSrcset = wp_get_attachment_image_srcset(17698, 'full')) ? ' srcset="' . esc_attr($heroSrcset) . '" sizes="(max-width: 1200px) 100vw, 1160px"' : '' ?>>
                    <div class="hero-card-overlay"></div>
                </div>
                <div class="hero-card-content">
                    <div class="eyebrow">L'Officiel du Canal du Midi</div>
                    <h1>Canal du Midi : carte et tracé<br>de Toulouse à la <em>Méditerranée</em></h1>
                    <p style="color:#fff;">
                        <span class="hero-lead-full">Carte interactive, plan détaillé gratuit, hébergements, location de bateaux et de vélos, restaurants, visites : préparez votre séjour le long du canal en toute liberté.</span>
                        <span class="hero-lead-short">Carte, plan gratuit, bateaux, vélos et hébergements du canal.</span>
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat"><strong>240 km</strong><span>de voie navigable</span></div>
                        <div class="hero-stat"><strong>63 écluses</strong><span>de génie hydraulique</span></div>
                        <div class="hero-stat"><strong>1681</strong><span>année de création</span></div>
                        <div class="hero-stat"><strong>UNESCO</strong><span>patrimoine mondial</span></div>
                    </div>
                </div>

                <form class="hero-search" id="home-search-form" action="<?= $link(CANAL_CARTE_PATH) ?>" method="GET">
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
                            <?php // La carte filtra search_location por texto en commune y dirección (no por la taxonomía region). ?>
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
                    <p id="home-ai-modal-title" class="hero-ai-title">Décrivez votre séjour idéal</p>
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
                            <span class="destination-card-media" role="img" aria-label="<?= esc_attr($cat['name'] . ' le long du Canal du Midi') ?>" style="background-image: linear-gradient(180deg, transparent 40%, rgba(14, 20, 36, 0.88));" data-bg="linear-gradient(180deg, transparent 40%, rgba(14, 20, 36, 0.88)), <?= canal_home_css_url($cat['image']) ?>"></span>
                            <span class="pill"><?= (int) $cat['count'] ?> adresse<?= $cat['count'] > 1 ? 's' : '' ?></span>
                            <h3><?= esc_html($cat['name']) ?></h3>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="cdm-center-cta">
                <a href="<?= $link(CANAL_CARTE_PATH) ?>" class="button"><i class="bi bi-map"></i> Voir tous les prestataires sur la carte</a>
            </div>
        </div>
    </section>

    <!-- 3. EXPÉRIENCES -->
    <section id="experiences" class="section section-alt">
        <div class="container split-layout">
            <div class="stacked-photos" data-reveal="left">
                <figure class="photo-card photo-large">
                    <img src="<?= $upload('2024/04/Dominique_VIET_CRTLOccitanie_0017338_MD_RET3-1.jpg') ?>" alt="Le Canal du Midi bordé d'arbres" width="1200" height="507" loading="lazy" decoding="async"<?= ($expSrcset = wp_get_attachment_image_srcset(17010, 'full')) ? ' srcset="' . esc_attr($expSrcset) . '" sizes="(max-width: 560px) calc(100vw - 32px), (max-width: 820px) calc(100vw - 72px), (max-width: 1080px) calc(100vw - 122px), 463px"' : '' ?>>
                </figure>
                <figure class="photo-card photo-small top">
                    <img src="<?= esc_url(CANAL_HOME_URL . 'assets/peniche-toulouse-480.jpg') ?>" srcset="<?= esc_url(CANAL_HOME_URL . 'assets/peniche-toulouse-480.jpg') ?> 480w, <?= esc_url(CANAL_HOME_URL . 'assets/peniche-toulouse-800.jpg') ?> 800w" sizes="(max-width: 760px) 130px, (max-width: 1080px) 160px, 220px" alt="Péniche amarrée à Toulouse" width="800" height="450" loading="lazy" decoding="async">
                </figure>
                <figure class="photo-card photo-small bottom">
                    <img src="<?= $upload('2020/01/img_8404_1-300x200.jpeg') ?>" alt="Balade à vélo sur le chemin de halage du canal" width="300" height="200" loading="lazy" decoding="async"<?= ($bikeSrcset = wp_get_attachment_image_srcset(10263, 'medium')) ? ' srcset="' . esc_attr($bikeSrcset) . '" sizes="(max-width: 760px) 130px, (max-width: 1080px) 160px, 220px"' : '' ?>>
                </figure>
            </div>
            <div class="split-copy" data-reveal="right">
                <div class="eyebrow">Votre séjour</div>
                <h2>Comment préparer votre séjour sur le canal ?</h2>
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
                    <a href="<?= esc_url($s['url']) ?>" class="tour-card-link">
                        <article class="tour-card">
                            <div class="tour-card-media" role="img" aria-label="<?= esc_attr($s['title']) ?>" data-bg="<?= canal_home_css_url($s['image']) ?>"></div>
                            <div class="tour-body">
                                <?php if ($s['category'] !== ''): ?><span class="tour-category"><?= esc_html($s['category']) ?></span><?php endif; ?>
                                <h3><?= esc_html($s['title']) ?></h3>
                                <?php if ($s['city'] !== ''): ?>
                                    <p class="tour-city"><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= esc_html($s['city']) ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ÉTAPES (AEO: itinerario con puntos kilométricos del sitio) -->
    <section id="etapes" class="section section-tight">
        <div class="container">
            <div class="section-heading center" data-reveal="up">
                <div class="eyebrow">Carte et tracé</div>
                <h2>Quel est le tracé du Canal du Midi ?</h2>
                <p>Le Canal du Midi part de Toulouse, au port de l'Embouchure, et rejoint l'étang de Thau aux Onglous, près de Sète : 240 km et 63 écluses à travers le Lauragais, Carcassonne, le Minervois et Béziers. Distances en points kilométriques (PK) depuis Toulouse.</p>
            </div>
            <ol class="etapes-list">
                <?php foreach (CANAL_HOME_ETAPES as $etape): ?>
                    <li class="etape">
                        <span class="etape-km">km <?= (int) $etape['km'] ?></span>
                        <?php if ($etape['search'] !== ''): ?>
                            <a class="etape-name" href="<?= esc_url(add_query_arg(['search_location' => $etape['search']], home_url(CANAL_CARTE_PATH))) ?>"><?= esc_html($etape['name']) ?></a>
                        <?php else: ?>
                            <span class="etape-name"><?= esc_html($etape['name']) ?></span>
                        <?php endif; ?>
                        <span class="etape-note"><?= esc_html($etape['note']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="etapes-more">Pour suivre le parcours écluse par écluse, ouvrez la carte interactive ou le plan officiel 2026, une carte détaillée gratuite en PDF.</p>
            <div class="cdm-center-cta etapes-cta">
                <a class="button" href="<?= $link(CANAL_CARTE_PATH) ?>"><i class="bi bi-map" aria-hidden="true"></i> Ouvrir la carte interactive</a>
                <a class="button button-soft" href="#plan"><i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Plan détaillé gratuit</a>
            </div>
        </div>
    </section>

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
                <p>Édité chaque année par Azur Communications, L'Officiel du Canal du Midi réunit le plan-guide du canal et plus de 250 prestataires référencés, de Toulouse à l'étang de Thau.</p>
            </div>
            <div class="feature-grid" data-reveal-stagger>
                <article class="feature-card feature-card--violet">
                    <div class="feature-icon"><i class="bi bi-shop" aria-hidden="true"></i></div>
                    <h3>Des prestataires locaux</h3>
                    <p>Des professionnels installés le long du canal, de Toulouse à l'étang de Thau.</p>
                </article>
                <article class="feature-card feature-card--water">
                    <div class="feature-icon"><i class="bi bi-map" aria-hidden="true"></i></div>
                    <h3>Le plan officiel</h3>
                    <p>Écluses, ports, services et points d'intérêt réunis sur un plan édité chaque année.</p>
                </article>
                <article class="feature-card feature-card--terracotta">
                    <div class="feature-icon"><i class="bi bi-compass" aria-hidden="true"></i></div>
                    <h3>Des outils pratiques</h3>
                    <p>Carte interactive, calcul de distance, règles de navigation et météo du canal.</p>
                </article>
            </div>
            <div class="offer-grid" data-reveal-stagger>
                <article class="offer-card blue">
                    <i class="bi bi-water offer-watermark" aria-hidden="true"></i>
                    <div>
                        <span class="offer-kicker"><i class="bi bi-water" aria-hidden="true"></i> En bateau</span>
                        <h3>Naviguer sur le canal</h3>
                    </div>
                    <a class="button button-small button-white" href="<?= $link('/navigation/regles-de-navigation/') ?>">Les règles de navigation</a>
                </article>
                <article class="offer-card sand">
                    <i class="bi bi-bicycle offer-watermark" aria-hidden="true"></i>
                    <div>
                        <span class="offer-kicker"><i class="bi bi-bicycle" aria-hidden="true"></i> À vélo</span>
                        <h3>Voie verte et véloroute</h3>
                    </div>
                    <a class="button button-small button-white" href="<?= $link('/voie-verte-et-veloroute/') ?>">Préparer ma balade</a>
                </article>
            </div>
        </div>
    </section>

    <!-- FAQ (AEO: preguntas y respuestas; mismo texto que el FAQPage del JSON-LD) -->
    <section id="faq" class="section">
        <div class="container">
            <div class="section-heading center" data-reveal="up">
                <div class="eyebrow">Questions fréquentes</div>
                <h2>Tout savoir avant de partir</h2>
            </div>
            <div class="faq-list">
                <?php foreach (CANAL_HOME_FAQ as $i => $item): ?>
                    <details class="faq-item"<?= $i === 0 ? ' open' : '' ?>>
                        <summary><h3><?= esc_html($item['q']) ?></h3></summary>
                        <p><?= esc_html($item['a']) ?></p>
                        <?php if (!empty($item['url'])): ?>
                            <a class="faq-link" href="<?= $link($item['url']) ?>"><?= esc_html($item['link']) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($item['source'])): ?>
                            <p class="faq-source">Source : <a href="<?= esc_url($item['source'][1]) ?>" target="_blank" rel="noopener"><?= esc_html($item['source'][0]) ?></a></p>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 7. PLAN (mismo diseño que la home local) -->
    <?php
    $planMessages = [
        'ok'      => ['#f0fdf4', '#86efac', '#166534', 'Vérifiez votre boîte mail — votre plan est en route !'],
        'invalid' => ['#fef2f2', '#fca5a5', '#991b1b', 'Adresse e-mail invalide. Veuillez vérifier et réessayer.'],
        'rate'    => ['#fffbeb', '#fcd34d', '#92400e', 'Trop de demandes pour le moment. Réessayez un peu plus tard ou téléchargez le PDF ci-dessous.'],
        'error'   => ['#fffbeb', '#fcd34d', '#92400e', 'Une erreur est survenue. Veuillez réessayer dans quelques instants.'],
    ];
    $planStatus = isset($_GET['plan']) ? sanitize_key(wp_unslash($_GET['plan'])) : '';
    $planMsg = $planMessages[$planStatus] ?? null;
    ?>
    <section id="plan" class="section newsletter-section">
        <div class="container newsletter-box" data-reveal="up">
            <div class="plan-viewer">
                <img src="<?= esc_url(CANAL_HOME_URL . 'assets/plan-canal-du-midi-2026.jpg?ver=' . filemtime(CANAL_HOME_DIR . 'assets/plan-canal-du-midi-2026.jpg')) ?>"
                     alt="Plan du Canal du Midi 2026 — cliquez pour le feuilleter" role="button" tabindex="0"
                     width="960" height="748" loading="lazy"
                     class="plan-viewer-img"
                     data-open-plan-modal>
            </div>
            <div>
                <div class="eyebrow">Guide officiel</div>
                <h2>Recevez le plan du Canal du Midi 2026</h2>
                <p style="color:var(--muted);margin-bottom:24px;">
                    Carte détaillée et gratuite du Canal du Midi<?= ($planYear = canal_plan_pdf_year()) !== '' ? ', édition ' . esc_html($planYear) : '' ?> : toutes les étapes, écluses, ports et points d'intérêt de Toulouse à l'étang de Thau. En PDF à télécharger ou directement dans votre boîte mail.
                </p>

                <?php if ($planMsg): ?>
                    <p role="alert" style="padding:12px 16px;background:<?= esc_attr($planMsg[0]) ?>;border:1px solid <?= esc_attr($planMsg[1]) ?>;border-radius:8px;color:<?= esc_attr($planMsg[2]) ?>;font-size:15.2px;margin-bottom:16px;">
                        <?= esc_html($planMsg[3]) ?>
                    </p>
                <?php endif; ?>

                <form class="newsletter-form" method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                    <input type="hidden" name="action" value="canal_home_plan">
                    <label class="cdm-hp" aria-hidden="true">Ne pas remplir
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </label>
                    <input type="email" name="email" required placeholder="Votre adresse e-mail" autocomplete="email">
                    <button class="button button-small" type="submit">Recevoir le plan par e-mail</button>
                </form>

                <div style="margin-top:20px;display:flex;flex-direction:column;align-items:center;gap:10.4px;flex-wrap:wrap;">
                    <span style="font-size:13.6px;color:var(--muted);font-weight:bold;">ou</span>
                    <a href="<?= esc_url(home_url(CANAL_PLAN_PDF_PATH)) ?>" download class="btn-pdf">
                        <i class="bi bi-file-earmark-arrow-down"></i> Télécharger le PDF
                        <span class="btn-pdf__size"><?= esc_html(str_replace(' MB', ' Mo', size_format((int) @filesize(canal_plan_pdf_file())))) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <div id="plan-modal" style="display: none;" aria-hidden="true">
        <div class="plan-modal-content" role="dialog" aria-modal="true" aria-labelledby="plan-modal-title" style="max-width: 928px;">
            <button type="button" class="plan-modal-close" data-close-plan-modal aria-label="Fermer la fenêtre du plan">
                <i class="bi bi-x-lg"></i>
            </button>
            <p id="plan-modal-title">Plan du Canal du Midi 2026</p>
            <iframe data-src="https://v.calameo.com/?bkcode=003331405edc35288442a&amp;mode=mini"
                    width="480" height="400" allowfullscreen referrerpolicy="no-referrer"
                    sandbox="allow-scripts allow-same-origin allow-popups allow-forms" scrolling="no"
                    style="display:block;width:100%;max-width:1072px;height:688px;border:0;border-radius:12px;margin:0 auto;"
                    title="Plan du Canal du Midi 2026 — Calaméo" loading="lazy"></iframe>
        </div>
    </div>
</main>
</div>
<?php
get_footer();
