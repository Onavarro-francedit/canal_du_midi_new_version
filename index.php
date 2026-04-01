<?php
$destinations = [
    ['name' => 'Canal du Midi', 'tag' => 'Cruise', 'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=900&q=80'],
    ['name' => 'Carcassonne', 'tag' => 'Heritage', 'image' => 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=900&q=80'],
    ['name' => 'Toulouse', 'tag' => 'City Break', 'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80'],
    ['name' => 'Béziers', 'tag' => 'Sunset', 'image' => 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=900&q=80'],
    ['name' => 'Minerve', 'tag' => 'Village', 'image' => 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=900&q=80'],
    ['name' => 'Sète', 'tag' => 'Lagoon', 'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80'],
];

$tours = [
    ['title' => 'Sunrise Cruise', 'days' => '3 Days', 'price' => '420 EUR', 'image' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=900&q=80'],
    ['title' => 'Wine Route Escape', 'days' => '5 Days', 'price' => '690 EUR', 'image' => 'https://images.unsplash.com/photo-1510798831971-661eb04b3739?auto=format&fit=crop&w=900&q=80'],
    ['title' => 'Cycling by the Locks', 'days' => '4 Days', 'price' => '510 EUR', 'image' => 'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=900&q=80'],
    ['title' => 'Private Houseboat Week', 'days' => '7 Days', 'price' => '980 EUR', 'image' => 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80'],
];

$features = [
    ['title' => 'Routes sur mesure', 'text' => 'Itinéraires dessinés pour couples, familles et petits groupes.'],
    ['title' => 'Réservation simple', 'text' => 'Dates, activités et hébergements regroupés dans un seul parcours.'],
    ['title' => 'Guides locaux', 'text' => 'Des recommandations réalistes, ancrées dans la région.'],
    ['title' => 'Support rapide', 'text' => 'Réponse rapide pour ajuster une étape ou une activité.'],
    ['title' => 'Offres saisonnières', 'text' => 'Promotions visibles sans obliger l’utilisateur à chercher.'],
    ['title' => 'Expérience mobile', 'text' => 'Sections fluides, lisibles et faciles à parcourir sur téléphone.'],
];

$reviews = [
    ['name' => 'Claire Martin', 'role' => 'Family Trip', 'text' => 'Le parcours était clair, élégant et vraiment simple à réserver.'],
    ['name' => 'Jonas Reed', 'role' => 'Couple Escape', 'text' => 'On a trouvé en quelques minutes un séjour crédible, pas une vitrine vide.'],
];

$articles = [
    ['title' => 'Comment planifier une semaine au fil de l’eau', 'category' => 'Guide', 'image' => 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=1200&q=80'],
    ['title' => 'Les meilleures haltes gourmandes autour du canal', 'category' => 'Food', 'image' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=1200&q=80'],
    ['title' => 'Quand partir pour éviter la foule', 'category' => 'Season', 'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canal du Midi | Voyages et escapades</title>
    <meta name="description" content="Landing page inspirée d'un site de voyage premium pour découvrir le Canal du Midi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <div class="container header-row">
                <a class="brand" href="#top" aria-label="Canal du Midi accueil">
                    <span class="brand-mark"></span>
                    <span class="brand-text">Canal du Midi</span>
                </a>
                <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                    <span></span>
                    <span></span>
                </button>
                <nav id="primary-nav" class="main-nav">
                    <a href="#destinations">Destinations</a>
                    <a href="#experiences">Expériences</a>
                    <a href="#why-us">Pourquoi nous</a>
                    <a href="#reviews">Avis</a>
                    <a href="#news">Actualités</a>
                </nav>
                <a class="button button-small button-ghost" href="#newsletter">S'inscrire</a>
            </div>
        </header>

        <main id="top">
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

            <section id="destinations" class="section section-tight">
                <div class="container">
                    <div class="section-heading center">
                        <div class="eyebrow">Top destinations</div>
                        <h2>Les étapes qui structurent le voyage</h2>
                        <p>Une grille d’entrée proche de la référence, adaptée au Canal du Midi.</p>
                    </div>
                    <div class="destination-grid">
                        <?php foreach ($destinations as $destination): ?>
                            <article class="destination-card" style="background-image: linear-gradient(180deg, transparent, rgba(14, 20, 36, 0.88)), url('<?php echo htmlspecialchars($destination['image'], ENT_QUOTES); ?>');">
                                <span class="pill"><?php echo htmlspecialchars($destination['tag']); ?></span>
                                <h3><?php echo htmlspecialchars($destination['name']); ?></h3>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

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
                            <li>Design responsive sans framework imposé</li>
                            <li>Base simple à intégrer dans un projet PHP</li>
                        </ul>
                        <div class="contact-strip">
                            <a class="button button-soft" href="tel:+33500000000">+33 5 00 00 00 00</a>
                            <a class="button button-soft muted" href="mailto:bonjour@canaldumidi.local">bonjour@canaldumidi.local</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="container">
                    <div class="section-heading">
                        <div class="eyebrow">Popular tours</div>
                        <h2>Des cartes produits claires et commerciales</h2>
                        <p>Chaque carte peut ensuite pointer vers une fiche détaillée ou un moteur de réservation.</p>
                    </div>
                    <div class="tour-grid">
                        <?php foreach ($tours as $tour): ?>
                            <article class="tour-card">
                                <img src="<?php echo htmlspecialchars($tour['image'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($tour['title']); ?>">
                                <div class="tour-body">
                                    <h3><?php echo htmlspecialchars($tour['title']); ?></h3>
                                    <div class="tour-meta">
                                        <span><?php echo htmlspecialchars($tour['days']); ?></span>
                                        <span><?php echo htmlspecialchars($tour['price']); ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="immersive-band">
                <div class="container band-inner">
                    <button class="play-button" type="button" aria-label="Lire la vidéo de présentation">▶</button>
                    <h2>Where would you like to go?</h2>
                    <p>Un bloc immersif pour casser le rythme et pousser vers l’exploration.</p>
                </div>
            </section>

            <section class="section">
                <div class="container planner-grid">
                    <div class="planner-copy">
                        <div class="eyebrow">Perfect travel planner</div>
                        <h2>Une zone de confiance orientée famille et longs séjours.</h2>
                        <p>
                            La composition reprend l’idée d’une grande promesse marketing avec photo ronde,
                            mini-statistiques et appel à l’action.
                        </p>
                        <div class="avatar-row">
                            <span></span><span></span><span></span><span></span>
                            <small>+1 200 voyageurs satisfaits</small>
                        </div>
                    </div>
                    <div class="planner-visual">
                        <div class="circle-photo">
                            <img src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80" alt="Maison de vacances">
                        </div>
                        <div class="stats-card">
                            <h3>Waiting for adventure? Don’t miss them</h3>
                            <p>Lorem ipsum style content volontairement court pour garder la mise en page nette.</p>
                            <div class="stats-row">
                                <div><strong>20</strong><span>Water routes</span></div>
                                <div><strong>400</strong><span>Local hosts</span></div>
                                <div><strong>50k+</strong><span>Curious visitors</span></div>
                            </div>
                            <a class="button button-small" href="#newsletter">Explorer</a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="why-us" class="section section-wave-top">
                <div class="container">
                    <div class="section-heading center">
                        <div class="eyebrow">Why choose us?</div>
                        <h2>Les bénéfices sont présentés en cartes compactes</h2>
                        <p>Format utile pour rassurer sans alourdir la page.</p>
                    </div>
                    <div class="feature-grid">
                        <?php foreach ($features as $feature): ?>
                            <article class="feature-card">
                                <div class="feature-icon"></div>
                                <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                                <p><?php echo htmlspecialchars($feature['text']); ?></p>
                            </article>
                        <?php endforeach; ?>
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

            <section id="reviews" class="section section-alt-light">
                <div class="container review-layout">
                    <div class="section-heading slim">
                        <div class="eyebrow">What our travelers are saying</div>
                        <h2>Des témoignages visibles et crédibles</h2>
                        <p>Deux cartes suffisent pour reprendre le rythme de la maquette.</p>
                    </div>
                    <div class="review-cards">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card">
                                <div class="stars">★★★★★</div>
                                <p><?php echo htmlspecialchars($review['text']); ?></p>
                                <div class="review-author">
                                    <strong><?php echo htmlspecialchars($review['name']); ?></strong>
                                    <span><?php echo htmlspecialchars($review['role']); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section id="news" class="section section-news">
                <div class="container">
                    <div class="section-heading">
                        <div class="eyebrow">Latest blog & news</div>
                        <h2>Une rangée d’articles visuels comme dans la référence</h2>
                        <p>Les cartes peuvent ensuite être reliées à un vrai blog ou CMS.</p>
                    </div>
                    <div class="article-grid">
                        <?php foreach ($articles as $article): ?>
                            <article class="article-card" style="background-image: linear-gradient(180deg, rgba(10, 18, 32, 0.05), rgba(10, 18, 32, 0.88)), url('<?php echo htmlspecialchars($article['image'], ENT_QUOTES); ?>');">
                                <span class="pill"><?php echo htmlspecialchars($article['category']); ?></span>
                                <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section id="newsletter" class="section newsletter-section">
                <div class="container newsletter-box">
                    <div>
                        <div class="eyebrow">Sign up for our newsletter</div>
                        <h2>Gardez la structure, changez le contenu et les visuels</h2>
                    </div>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Votre adresse email">
                        <button class="button button-small" type="submit">Submit</button>
                    </form>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="container footer-grid">
                <div>
                    <a class="brand footer-brand" href="#top">
                        <span class="brand-mark"></span>
                        <span class="brand-text">Canal du Midi</span>
                    </a>
                    <p>Landing inspirée de votre capture, prête à servir de base pour un site touristique.</p>
                </div>
                <div>
                    <h3>Navigation</h3>
                    <a href="#destinations">Destinations</a>
                    <a href="#experiences">Expériences</a>
                    <a href="#news">Blog</a>
                </div>
                <div>
                    <h3>Contact</h3>
                    <a href="tel:+33500000000">+33 5 00 00 00 00</a>
                    <a href="mailto:bonjour@canaldumidi.local">bonjour@canaldumidi.local</a>
                    <span>Toulouse, France</span>
                </div>
            </div>
            <div class="container footer-bottom">
                <span>© <span id="current-year"><?php echo date('Y'); ?></span> Canal du Midi</span>
                <span>Built with PHP, CSS and JS</span>
            </div>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>