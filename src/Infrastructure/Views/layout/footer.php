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
                    <span>© <?= date('Y') ?> Canal du Midi</span>
                    <span>Built with PHP, CSS and JS (Hexagonal Architecture)</span>
                </div>
            </footer>
        </div>
        <script src="<?= BASE_URL ?>public/assets/js/main.js"></script>
         <!-- CARGA CONDICIONAL DE SCRIPTS -->
        <?php if (isset($page) && $page === 'service'): ?>
            <!-- Leaflet JS -->
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
            <script src="<?= BASE_URL ?>public/assets/js/map.js"></script>
            <script src="<?= BASE_URL ?>public/assets/js/lightbox.js"></script>
            <script src="<?= BASE_URL ?>public/assets/js/booking.js"></script>
        <?php endif; ?>
    </body>
</html>