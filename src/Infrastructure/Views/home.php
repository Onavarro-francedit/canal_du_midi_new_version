<?php
/**
 * Vista: home.php
 * Recibe: 
 * - $destinations (Array de objetos Service de tipo destination)
 * - $tours (Array de objetos Service de tipo tour)
 * - $lang (String del idioma actual)
 */
?>

<main id="top">
    <!-- 1. HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-card" data-reveal="zoom">
                <img class="hero-card-img" src="https://images.unsplash.com/photo-1732604447830-b4ba3c4ece89?auto=format&fit=crop&w=1800&q=80" alt="Canal du Midi bordé de platanes" loading="eager">
                <div class="hero-card-overlay"></div>
                <div class="hero-card-content">
                    <div class="eyebrow">Slow travel en Occitanie</div>
                    <h1>Explorez le Canal du Midi<br>et vivez l'art du voyage lent.</h1>
                    <p style="color:#fff;">
                        Croisières en péniche, escapades à vélo, villages de charme —
                        composez votre itinéraire idéal de Toulouse à la Méditerranée.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <strong>12K+</strong>
                            <span>voyageurs inspirés</span>
                        </div>
                        <div class="hero-stat">
                            <strong>48</strong>
                            <span>expériences locales</span>
                        </div>
                        <div class="hero-stat">
                            <strong>4.9</strong>
                            <span>note moyenne</span>
                        </div>
                    </div>
                </div>
            </div>

            <form class="hero-search" data-reveal="up" data-delay="300" id="home-search-form" action="<?= BASE_URL . $lang ?>/search" method="GET">
                <div class="search-field search-field-primary">
                    <span class="search-field-head">
                        <span class="search-field-icon"><i class="bi bi-search"></i></span>
                        <span class="search-field-copy">
                            <span class="search-field-label">Que cherchez-vous ?</span>
                        </span>
                    </span>
                    <input type="text" name="q" id="home-search-input" class="search-field-input"
                        list="home-search-suggestions" autocomplete="off"
                        placeholder="Hôtel romantique, croisière, vélo…">
                </div>

                <label class="search-field">
                    <span class="search-field-head">
                        <span class="search-field-icon"><i class="bi bi-geo-alt"></i></span>
                        <span class="search-field-copy">
                            <span class="search-field-label">Destination</span>
                        </span>
                    </span>
                    <span class="search-field-select-wrap">
                        <select name="city" class="search-field-input">
                            <option value="">Toutes les étapes</option>
                            <option value="Toulouse">Toulouse</option>
                            <option value="Carcassonne">Carcassonne</option>
                        </select>
                        <span class="search-field-select-caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                    </span>
                </label>

                <label class="search-field">
                    <span class="search-field-head">
                        <span class="search-field-icon"><i class="bi bi-sliders"></i></span>
                        <span class="search-field-copy">
                            <span class="search-field-label">Type</span>
                        </span>
                    </span>
                    <span class="search-field-select-wrap">
                        <select name="type" class="search-field-input">
                            <option value="">Tous les types</option>
                            <option value="hotel">Hôtel</option>
                            <option value="boat">Bateau</option>
                        </select>
                        <span class="search-field-select-caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                    </span>
                </label>

                <div class="search-buttons-group">
                    <button class="button hero-search-submit" type="submit">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                    <button type="button" id="home-ai-btn" class="button ai-magic-btn" title="Utiliser l'IA">
                        <i class="bi bi-stars"></i> Assistant IA
                    </button>
                </div>
            </form>

            <datalist id="home-search-suggestions">
                <option value="Un hôtel romantique avec spa"></option>
                <option value="Une balade à vélo le long du canal"></option>
                <option value="Une croisière paisible en péniche"></option>
                <option value="Un séjour en famille près de Carcassonne"></option>
            </datalist>

            <div id="home-ai-modal" class="hero-ai-modal" aria-hidden="true">
                <div class="hero-ai-dialog" role="dialog" aria-modal="true" aria-labelledby="home-ai-modal-title">
                    <button type="button" class="hero-ai-close" data-close-home-ai aria-label="Fermer la fenêtre IA">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="hero-ai-kicker">Assistant IA</div>
                    <h2 id="home-ai-modal-title">Décrivez votre voyage idéal</h2>
                    <p>Expliquez librement ce que vous cherchez et l'IA trouvera le résultat le plus pertinent.</p>

                    <form id="home-ai-modal-form" class="hero-ai-form">
                        <label class="hero-ai-label" for="home-ai-prompt">Votre demande</label>
                        <textarea id="home-ai-prompt" class="hero-ai-textarea" rows="6"
                            placeholder="Ex: Je veux un week-end romantique près de Carcassonne avec spa et vue sur l'eau"></textarea>
                        <p id="home-ai-feedback" class="hero-ai-feedback" aria-live="polite"></p>
                        <div class="hero-ai-actions">
                            <button type="button" class="button button-ghost" data-close-home-ai>Annuler</button>
                            <button type="submit" id="home-ai-submit" class="button">
                                <i class="bi bi-stars"></i>
                                <span>Envoyer à l'IA</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. DESTINATIONS SECTION (DINÁMICA) -->
    <section id="destinations" class="section section-tight">
        <div class="container">
            <div class="section-heading center" data-reveal="up">
                <div class="eyebrow">Top destinations</div>
                <h2>Les étapes qui structurent le voyage</h2>
                <p>Découvrez les lieux emblématiques du Canal du Midi, sélectionnés pour leur patrimoine et leur beauté.</p>
            </div>
            
            <div class="destination-grid" data-reveal-stagger>
                <?php foreach ($randomCategories as $cat): ?>
                    <?php 
                        // La imagen ahora viene directamente de la DB ($cat['image_url'])
                        // Ponemos un fallback por si alguna categoría no tiene foto
                        $bgImage = !empty($cat['image_url']) 
                                ? $cat['image_url'] 
                                : 'https://images.unsplash.com/photo-1517760444937-f6397edcbbcd?auto=format&fit=crop&w=800&q=80';
                        
                        $url = BASE_URL . $lang . '/search?type=' . $cat['slug'];
                        $name = htmlspecialchars($cat['name'] ?? ucfirst($cat['slug']));
                        $count = (int)$cat['offers_count'];
                    ?>
                    <a href="<?= $url ?>" class="destination-card-link">
                        <article class="destination-card" style="background-image: linear-gradient(180deg, transparent, rgba(14, 20, 36, 0.88)), url('<?= $bgImage ?>');">
                            <div class="card-icon-top"><i class="bi <?= $cat['icon_class'] ?>"></i></div>
                            <span class="pill"><?= $count ?> offre<?= $count !== 1 ? 's' : '' ?></span>
                            <h3><?= $name ?></h3>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Botón Ver Todo -->
            <div style="width: 100%; display: flex; justify-content: center; margin-top: 2rem;">
                <a href="<?= BASE_URL . $lang ?>/search" class="button button-ghost" style="background-color: #544dbe !important; color: #fff !important;">
                    <i class="bi bi-map"></i> Voir tous les établissements sur la carte
                </a>
            </div>
        </div>
    </section>

    <!-- 3. EXPERIENCES SECTION (EDITORIAL) -->
    <section id="experiences" class="section section-alt">
        <div class="container split-layout">
            <div class="stacked-photos" data-reveal="left">
                <figure class="photo-card photo-large">
                    <img src="https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=1000&q=80" alt="Bateau sur le canal">
                </figure>
                <figure class="photo-card photo-small top">
                    <img src="https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=800&q=80" alt="Village près de l'eau">
                </figure>
                <figure class="photo-card photo-small bottom">
                    <img src="https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=800&q=80" alt="Vélo de voyage">
                </figure>
            </div>
            <div class="split-copy" data-reveal="right">
                <div class="eyebrow">A simply perfect place to get lost</div>
                <h2>Une section éditoriale forte pour raconter la destination.</h2>
                <p>
                    Cette partie reprend le principe visuel de la maquette: photos superposées,
                    texte dense, liste d’arguments, puis deux actions rapides.
                </p>
                <ul class="check-list">
                    <li>Parcours fluvial, vélo et patrimoine réunis</li>
                    <li>Contenu prêt à remplacer par vos vraies offres</li>
                    <li>Design responsive sans framework imposé (Pure CSS)</li>
                    <li>Architecture Hexagonale prête pour la croissance</li>
                </ul>
                <div class="contact-strip">
                    <a class="button button-soft" href="tel:+33500000000">+33 5 00 00 00 00</a>
                    <a class="button button-soft muted" href="mailto:bonjour@canaldumidi.local">bonjour@canaldumidi.local</a>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. POPULAR TOURS (DINÁMICO) -->
    <section class="section">
        <div class="container">
            <div class="section-heading" data-reveal="up">
                <div class="eyebrow">Popular tours</div>
                <h2>Des cartes produits claires et commerciales</h2>
                <p>Récupéré via ServiceRepository (Capa de Infraestructura).</p>
            </div>
            <div class="tour-grid" data-reveal-stagger>
                <?php foreach ($tours as $tour): ?>
                    <a href="/canal_du_midi/<?= $lang ?>/service/<?= $tour->id ?>">
                        <article class="tour-card">
                            <img src="<?= htmlspecialchars($tour->imageUrl) ?>" alt="<?= htmlspecialchars($tour->translations['title'] ?? 'Tour') ?>">
                            <div class="tour-body">
                                <h3><?= htmlspecialchars($tour->translations['title'] ?? 'Titre non disponible') ?></h3>
                                <div class="tour-meta">
                                    <span><?= htmlspecialchars($tour->translations['tag'] ?? 'Durée flexible') ?></span>
                                    <span><?= $tour->getFormattedPrice() ?></span>
                                </div>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 5. IMMERSIVE BAND -->
    <section class="immersive-band">
        <div class="container band-inner" data-reveal="zoom">
            <button class="play-button" type="button" aria-label="Lire la vidéo">▶</button>
            <h2>Where would you like to go?</h2>
            <p>Un bloc immersif pour casser le rythme et pousser vers l’exploration.</p>
        </div>
    </section>

    <!-- 6. WHY CHOOSE US (FEATURES) -->
    <section id="why-us" class="section section-wave-top">
        <div class="container">
            <div class="section-heading center" data-reveal="up">
                <div class="eyebrow">Why choose us?</div>
                <h2>Les bénéfices sont présentés en cartes compactes</h2>
            </div>
            
            <div class="feature-grid" data-reveal-stagger>
                <!-- Por brevedad, mantenemos estas estáticas o podrías traerlas también de la DB -->
                <article class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Routes sur mesure</h3>
                    <p>Itinéraires dessinés pour couples, familles et petits groupes.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Réservation simple</h3>
                    <p>Dates, activités et hébergements regroupés dans un seul parcours.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"></div>
                    <h3>Guides locaux</h3>
                    <p>Des recommandations réalistes, ancrées dans la région.</p>
                </article>
            </div>

            <div class="offer-grid" data-reveal-stagger>
                <article class="offer-card blue">
                    <div>
                        <span class="offer-kicker">Weekly flash deals</span>
                        <h3>Jusqu’à -35% sur certaines dates</h3>
                    </div>
                    <a class="button button-small button-white" href="#newsletter">Voir les offres</a>
                </article>
                <article class="offer-card sand">
                    <div>
                        <span class="offer-kicker">Summer escapes</span>
                        <h3>Des séjours prêts pour juillet et août</h3>
                    </div>
                    <a class="button button-small button-white" href="#news">Voir plus</a>
                </article>
            </div>
        </div>
    </section>

    <!-- 7. NEWSLETTER -->
    <section id="newsletter" class="section newsletter-section">
        <div class="container newsletter-box" data-reveal="up">
            <div>
                <div class="eyebrow">Sign up for our newsletter</div>
                <h2>Inscrivez-vous pour recevoir des mises à jour sur le Canal.</h2>
            </div>
            <form class="newsletter-form">
                <input type="email" placeholder="Votre adresse email">
                <button class="button button-small" type="submit">Submit</button>
            </form>
        </div>
    </section>
</main>