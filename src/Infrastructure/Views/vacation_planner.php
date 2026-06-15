<?php /* vacation_planner.php — Planificateur de voyage IA */ ?>
<div class="planner-page">

    <!-- ══ Steps indicator ══════════════════════════════════════════════════ -->
    <div class="planner-steps-bar">
        <div class="planner-steps-inner">
            <div class="planner-step-item active" data-step="1">
                <div class="step-circle"><span class="step-num">1</span></div>
                <span>Votre voyage</span>
            </div>
            <div class="step-connector"></div>
            <div class="planner-step-item" data-step="2">
                <div class="step-circle"><span class="step-num">2</span></div>
                <span>Votre plan</span>
            </div>
            <div class="step-connector"></div>
            <div class="planner-step-item" data-step="3">
                <div class="step-circle"><span class="step-num">3</span></div>
                <span>Vos infos</span>
            </div>
            <div class="step-connector"></div>
            <div class="planner-step-item" data-step="4">
                <div class="step-circle"><span class="step-num">4</span></div>
                <span>Confirmation</span>
            </div>
        </div>
    </div>

    <!-- ══ Screen 1 : Saisie du voyage ══════════════════════════════════════ -->
    <section class="planner-screen active" id="screen-input">
        <div class="container">
            <div class="planner-input-card">
                <div class="planner-badge">✨ Planificateur IA</div>
                <h1 class="planner-title">
                    Votre séjour idéal sur le<br>
                    <em>Canal du Midi</em>
                </h1>
                <p class="planner-subtitle">
                    Décrivez votre voyage en quelques mots.<br>
                    Notre IA compose un itinéraire personnalisé avec les meilleurs prestataires de la région.
                </p>

                <div class="prompt-chips">
                    <button class="prompt-chip" data-prompt="Séjour romantique 2 jours pour 2 personnes, dîner gastronomique et balade en barque sur le canal">Romantique 🌹</button>
                    <button class="prompt-chip" data-prompt="3 jours en famille avec 2 enfants, vélo le long du canal, sortie en bateau et bonnes tables le soir">Famille 👨‍👩‍👧‍👦</button>
                    <button class="prompt-chip" data-prompt="Week-end aventure 2 jours : kayak, randonnée et camping nature au bord du canal">Aventure 🚵</button>
                    <button class="prompt-chip" data-prompt="Séjour luxe 4 jours : hébergement de charme, croisière privée et gastronomie raffinée">Luxe ✨</button>
                </div>

                <div class="prompt-wrapper">
                    <textarea
                        id="vacation-prompt"
                        class="vacation-textarea"
                        placeholder="Ex : Je pars 3 jours avec ma femme et mes 2 enfants. On veut faire du vélo le long du canal, une sortie en bateau et bien manger le soir..."
                        rows="4"
                        maxlength="500"
                    ></textarea>
                    <div class="prompt-meta">
                        <span class="char-counter"><span id="char-count">0</span>/500</span>
                    </div>
                </div>

                <div id="input-error" class="planner-error" style="display:none;"></div>

                <button id="btn-generate" class="btn-planner-primary">
                    <span>Générer mon itinéraire</span>
                    <i class="bi bi-magic"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- ══ Screen 2 : Chargement ══════════════════════════════════════════ -->
    <section class="planner-screen" id="screen-loading">
        <div class="loading-wrapper">
            <div class="loading-canal">
                <div class="canal-water"></div>
                <div class="canal-boat">⛵</div>
                <div class="canal-ripple"></div>
            </div>
            <p class="loading-title">Création de votre itinéraire&hellip;</p>
            <p class="loading-msg" id="loading-msg">Analyse de votre demande</p>
            <div class="loading-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </section>

    <!-- ══ Screen 3 : Plan généré ════════════════════════════════════════ -->
    <section class="planner-screen" id="screen-plan">
        <div class="container">
            <div class="plan-top-bar">
                <button class="btn-back" id="btn-back-to-input">
                    <i class="bi bi-arrow-left"></i> Modifier ma demande
                </button>
            </div>
            <div class="plan-header">
                <span class="plan-label-badge">✅ Votre plan est prêt</span>
                <h2 class="plan-summary-text" id="plan-summary"></h2>
                <div class="plan-meta-row">
                    <span class="plan-duration-badge" id="plan-duration"></span>
                </div>
            </div>

            <div class="days-grid" id="days-container"></div>

            <div class="plan-cta-bar">
                <p class="plan-cta-hint">
                    <i class="bi bi-info-circle"></i>
                    Supprimez les activités qui ne vous conviennent pas en cliquant sur <i class="bi bi-x-circle"></i>.
                </p>
                <button class="btn-planner-primary" id="btn-to-contact">
                    Ce plan me convient <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- ══ Screen 4 : Coordonnées ════════════════════════════════════════ -->
    <section class="planner-screen" id="screen-contact">
        <div class="container">
            <div class="plan-top-bar">
                <button class="btn-back" id="btn-back-to-plan">
                    <i class="bi bi-arrow-left"></i> Retour au plan
                </button>
            </div>
            <div class="contact-layout">
                <div class="contact-form-col">
                    <h2>Vos coordonnées</h2>
                    <p class="contact-intro">
                        Nous allons transmettre votre demande à chaque prestataire de votre plan.
                        Ils vous contacteront directement pour confirmer votre séjour.
                    </p>

                    <form id="contact-form" class="contact-form" novalidate>
                        <div class="form-group">
                            <label for="f-name">Prénom &amp; Nom <span class="req">*</span></label>
                            <input type="text" id="f-name" name="name" required placeholder="Marie Dupont">
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="f-email">Email <span class="req">*</span></label>
                                <input type="email" id="f-email" name="email" required placeholder="marie@email.com">
                            </div>
                            <div class="form-group">
                                <label for="f-phone">Téléphone</label>
                                <input type="tel" id="f-phone" name="phone" placeholder="+33 6 00 00 00 00">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="f-checkin">Arrivée prévue</label>
                                <input type="date" id="f-checkin" name="checkin">
                            </div>
                            <div class="form-group">
                                <label for="f-checkout">Départ prévu</label>
                                <input type="date" id="f-checkout" name="checkout">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="f-adults">Adultes</label>
                                <input type="number" id="f-adults" name="adults" min="1" max="20" value="2">
                            </div>
                            <div class="form-group">
                                <label for="f-children">Enfants</label>
                                <input type="number" id="f-children" name="children" min="0" max="20" value="0">
                            </div>
                        </div>
                        <div id="submit-error" class="planner-error" style="display:none;"></div>
                        <button type="submit" id="btn-submit" class="btn-planner-primary btn-full">
                            <span>Envoyer ma demande d'intérêt</span>
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>

                <div class="contact-recap-col">
                    <div class="recap-card">
                        <h3>Récapitulatif du plan</h3>
                        <div id="plan-recap"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══ Screen 5 : Confirmation ═══════════════════════════════════════ -->
    <section class="planner-screen" id="screen-confirmation">
        <div class="confirmation-wrapper">
            <div class="success-circle">
                <i class="bi bi-check-lg"></i>
            </div>
            <h2>Demande envoyée avec succès !</h2>
            <p class="conf-text">
                Chaque prestataire de votre plan a reçu vos coordonnées
                et vous contactera directement pour confirmer votre séjour.
            </p>
            <div class="conf-email-note">
                <i class="bi bi-envelope-check"></i>
                Un récapitulatif a été envoyé à <strong id="conf-email"></strong>
            </div>
            <div class="conf-actions">
                <a id="btn-pdf" href="#" target="_blank" class="btn-planner-primary">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Voir &amp; imprimer mon plan
                </a>
                <a href="<?= BASE_URL . ($lang ?? 'fr') ?>/home" class="btn-planner-ghost">
                    <i class="bi bi-house"></i>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </section>

</div>
