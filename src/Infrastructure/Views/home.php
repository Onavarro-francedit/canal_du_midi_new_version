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
        <div class="hero-media"></div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <div class="eyebrow">Slow travel en Occitanie</div>
            <h1>Explorez le Canal du Midi avec une esthétique premium et une structure prête à convertir.</h1>
            <p>
                Une landing pensée pour vendre des séjours, des croisières et des week-ends le long du canal,
                avec des blocs éditoriaux proches de votre référence.
            </p>
            
            <!-- Buscador (Lógica futura de IA) -->
            <form class="hero-search" action="#destinations" method="get">
                <label>
                    <span>Mot-clé</span>
                    <input type="text" name="keyword" placeholder="Croisière, vélo, village...">
                </label>
                <label>
                    <span>Destination</span>
                    <select name="destination">
                        <option>Canal du Midi</option>
                        <option>Carcassonne</option>
                        <option>Toulouse</option>
                        <option>Sète</option>
                    </select>
                </label>
                <label>
                    <span>Durée</span>
                    <select name="duration">
                        <option>3 à 5 jours</option>
                        <option>Une semaine</option>
                        <option>Long séjour</option>
                    </select>
                </label>
                <button class="button button-round" type="submit">Rechercher</button>
            </form>

            <div class="hero-stats">
                <div>
                    <strong>12K+</strong>
                    <span>visiteurs inspirés</span>
                </div>
                <div>
                    <strong>48</strong>
                    <span>expériences locales</span>
                </div>
                <div>
                    <strong>4.9</strong>
                    <span>note moyenne</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. DESTINATIONS SECTION (DINÁMICA) -->
    <section id="destinations" class="section section-tight">
        <div class="container">
            <div class="section-heading center">
                <div class="eyebrow">Top destinations</div>
                <h2>Les étapes qui structurent le voyage</h2>
                <p>Contenu extrait dynamiquement de la base de données MySQL.</p>
            </div>
            <div class="destination-grid">
                <?php foreach ($destinations as $dest): ?>
                    <article class="destination-card" style="background-image: linear-gradient(180deg, transparent, rgba(14, 20, 36, 0.88)), url('<?= htmlspecialchars($dest->imageUrl) ?>');">
                        <span class="pill"><?= htmlspecialchars($dest->translations['tag'] ?? 'Exploration') ?></span>
                        <h3><?= htmlspecialchars($dest->translations['title'] ?? 'Sans titre') ?></h3>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 3. EXPERIENCES SECTION (EDITORIAL) -->
    <section id="experiences" class="section section-alt">
        <div class="container split-layout">
            <div class="stacked-photos">
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
            <div class="split-copy">
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
            <div class="section-heading">
                <div class="eyebrow">Popular tours</div>
                <h2>Des cartes produits claires et commerciales</h2>
                <p>Récupéré via ServiceRepository (Capa de Infraestructura).</p>
            </div>
            <div class="tour-grid">
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
        <div class="container band-inner">
            <button class="play-button" type="button" aria-label="Lire la vidéo">▶</button>
            <h2>Where would you like to go?</h2>
            <p>Un bloc immersif pour casser le rythme et pousser vers l’exploration.</p>
        </div>
    </section>

    <!-- 6. WHY CHOOSE US (FEATURES) -->
    <section id="why-us" class="section section-wave-top">
        <div class="container">
            <div class="section-heading center">
                <div class="eyebrow">Why choose us?</div>
                <h2>Les bénéfices sont présentés en cartes compactes</h2>
            </div>
            
            <div class="feature-grid">
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

            <div class="offer-grid">
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
        <div class="container newsletter-box">
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