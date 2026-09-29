/**
 * home.js — Home « Accueil 2026 » (plugin canal-home).
 * Sin librerías. Nunca innerHTML con datos: solo textContent / atributos.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Hero: entrada escalonada + parallax sutil de la imagen ──────────
    function initHero() {
        var hero = document.querySelector('.cdm-home .hero-section');
        var img = hero && hero.querySelector('.hero-card-img');
        if (!hero || reduceMotion) return;

        var ready = function () { requestAnimationFrame(function () { hero.classList.add('hero-ready'); }); };
        if (document.readyState === 'complete') ready(); else window.addEventListener('load', ready, { once: true });

        if (!img) return;
        var ticking = false;
        var update = function () {
            ticking = false;
            var rect = hero.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) return;
            var max = rect.height * 0.07;
            var shift = Math.max(-max, Math.min(max, (-rect.top / rect.height) * max));
            img.style.translate = '0 ' + shift.toFixed(1) + 'px';
        };
        var onScroll = function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    }

    // ── Banda inmersiva: parallax de la foto (muestra otra parte según el scroll) ──
    // background-position de la 2ª capa (la foto) de 0 % a 100 % mientras la banda cruza
    // la pantalla; la 1ª capa (velo oscuro) no se mueve. Sin background-attachment:fixed
    // porque iOS no lo soporta.
    function initBandParallax() {
        var band = document.querySelector('.cdm-home .immersive-band');
        if (!band || reduceMotion) return;
        var ticking = false;
        var update = function () {
            ticking = false;
            var rect = band.getBoundingClientRect();
            var vh = window.innerHeight;
            if (rect.bottom < 0 || rect.top > vh) return;
            var progress = Math.max(0, Math.min(1, (vh - rect.top) / (vh + rect.height)));
            band.style.backgroundPosition = '0% 0%, center ' + (progress * 100).toFixed(1) + '%';
        };
        var onScroll = function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    }

    // ── Scroll reveal ───────────────────────────────────────────────────
    function initReveal() {
        if (reduceMotion || !('IntersectionObserver' in window)) return;
        // Solo ahora (JS operativo) el CSS oculta los elementos para animarlos.
        var root = document.querySelector('.cdm-home');
        if (root) root.classList.add('js-reveal');
        var observe = function (selector, threshold, onShow) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    onShow(entry.target);
                    io.unobserve(entry.target);
                });
            }, { threshold: threshold, rootMargin: '0px 0px -40px 0px' });
            document.querySelectorAll(selector).forEach(function (el) { io.observe(el); });
        };
        observe('.cdm-home [data-reveal]', 0.12, function (el) { el.classList.add('is-visible'); });
        observe('.cdm-home [data-reveal-stagger]', 0.08, function (el) {
            Array.prototype.forEach.call(el.children, function (child, i) {
                child.style.setProperty('--stagger-delay', (i * 100) + 'ms');
            });
            el.classList.add('is-visible');
        });
    }

    // ── Modal del plan (Calaméo, carga diferida) ────────────────────────
    function initPlanModal() {
        var modal = document.getElementById('plan-modal');
        var opener = document.querySelector('[data-open-plan-modal]');
        if (!modal || !opener) return;
        var iframe = modal.querySelector('iframe[data-src]');
        var closeBtn = modal.querySelector('[data-close-plan-modal]');
        var lastFocused = null;

        var open = function () {
            if (iframe && !iframe.src) iframe.src = iframe.getAttribute('data-src');
            lastFocused = document.activeElement;
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            if (closeBtn) closeBtn.focus();
        };
        var close = function () {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (lastFocused && lastFocused.focus) lastFocused.focus();
        };
        opener.addEventListener('click', open);
        opener.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
        });
        if (closeBtn) closeBtn.addEventListener('click', close);
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display === 'block') close();
        });
    }

    // ── Buscador clásico: no enviar campos vacíos + estado de carga ─────
    function initSearchForm() {
        var form = document.getElementById('home-search-form');
        if (!form) return;
        form.addEventListener('submit', function () {
            form.querySelectorAll('input[name], select[name]').forEach(function (field) {
                if (field.value.trim() === '') field.disabled = true;
            });
            var btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.textContent = 'Recherche…'; }
        });
        // Volver atrás desde /explorer/ restaura el formulario desde bfcache.
        window.addEventListener('pageshow', function () {
            form.querySelectorAll('[disabled]').forEach(function (el) { el.disabled = false; });
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.textContent = 'Rechercher';
        });
    }

    // ── Modal IA: apertura/cierre (el envío se implementa en Task 5) ────
    var ai = {};
    function initAiModal() {
        ai.btn = document.getElementById('home-ai-btn');
        ai.modal = document.getElementById('home-ai-modal');
        ai.form = document.getElementById('home-ai-modal-form');
        ai.prompt = document.getElementById('home-ai-prompt');
        ai.feedback = document.getElementById('home-ai-feedback');
        ai.submit = document.getElementById('home-ai-submit');
        ai.results = document.getElementById('home-ai-results');
        ai.search = document.getElementById('home-search-input');
        if (!ai.btn || !ai.modal || !ai.form || !ai.prompt || !ai.feedback || !ai.submit || !ai.results) return false;

        ai.open = function () {
            if (ai.search && ai.search.value.trim() !== '' && ai.prompt.value.trim() === '') {
                ai.prompt.value = ai.search.value.trim();
            }
            ai.modal.classList.add('is-open');
            ai.modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            window.setTimeout(function () { ai.prompt.focus(); }, 30);
        };
        ai.close = function () {
            ai.modal.classList.remove('is-open');
            ai.modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            ai.btn.focus();
        };
        ai.btn.addEventListener('click', ai.open);
        ai.modal.querySelectorAll('[data-close-home-ai]').forEach(function (b) { b.addEventListener('click', ai.close); });
        ai.modal.addEventListener('click', function (e) { if (e.target === ai.modal) ai.close(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && ai.modal.classList.contains('is-open')) ai.close();
        });
        return true;
    }

    // ── Modal IA: envío y resultados ────────────────────────────────────
    function explorerLink(prompt) {
        var a = document.createElement('a');
        a.href = CDM_HOME.explorerUrl + '?type=prestataires-touristiques&search_keywords=' + encodeURIComponent(prompt);
        a.textContent = "Voir les résultats dans l'explorateur";
        return a;
    }

    function showMessage(text, prompt) {
        ai.feedback.textContent = text + ' ';
        if (prompt) ai.feedback.appendChild(explorerLink(prompt));
    }

    function renderResults(results) {
        ai.results.replaceChildren();
        results.forEach(function (r) {
            var card = document.createElement('a');
            card.className = 'cdm-ai-card';
            card.href = r.url;

            var img = document.createElement('img');
            img.src = r.image;
            img.alt = '';
            img.loading = 'lazy';

            var body = document.createElement('div');
            body.className = 'cdm-ai-card-body';
            var title = document.createElement('h3');
            title.textContent = r.title;
            var meta = document.createElement('p');
            meta.className = 'cdm-ai-card-meta';
            meta.textContent = [r.category, r.city].filter(Boolean).join(' · ');
            var reason = document.createElement('p');
            reason.className = 'cdm-ai-card-reason';
            reason.textContent = r.reason;

            body.append(title, meta, reason);
            card.append(img, body);
            ai.results.appendChild(card);
        });
    }

    function initAiSubmit() {
        var label = ai.submit.querySelector('span');
        var idleLabel = label ? label.textContent : '';

        ai.form.addEventListener('submit', function (e) {
            e.preventDefault();
            var prompt = ai.prompt.value.trim();
            ai.results.replaceChildren();
            if (prompt.length < 3) {
                showMessage('Décrivez votre envie en quelques mots.', '');
                ai.prompt.focus();
                return;
            }

            // Cierra el teclado en móvil: si no, tapa los resultados al llegar.
            ai.prompt.blur();
            ai.submit.disabled = true;
            if (label) label.textContent = 'Recherche en cours…';
            ai.feedback.textContent = '';

            var controller = 'AbortController' in window ? new AbortController() : null;
            var timer = controller ? window.setTimeout(function () { controller.abort(); }, 40000) : null;

            fetch(CDM_HOME.aiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: prompt }),
                signal: controller ? controller.signal : undefined
            })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (out) {
                    var results = (out.data && Array.isArray(out.data.results)) ? out.data.results : [];
                    if (!out.ok) {
                        showMessage((out.data && out.data.message) || "L'assistant IA est momentanément indisponible.", prompt);
                    } else if (results.length === 0) {
                        showMessage('Aucune adresse ne correspond précisément à votre demande.', prompt);
                    } else {
                        ai.feedback.textContent = 'Voici les adresses qui correspondent le mieux à votre demande :';
                        renderResults(results);
                        ai.feedback.scrollIntoView({ block: 'start', behavior: reduceMotion ? 'auto' : 'smooth' });
                    }
                })
                .catch(function () {
                    showMessage("L'assistant IA est momentanément indisponible.", prompt);
                })
                .then(function () {
                    if (timer) window.clearTimeout(timer);
                    ai.submit.disabled = false;
                    if (label) label.textContent = idleLabel;
                });
        });
    }

    initHero();
    initBandParallax();
    initReveal();
    initPlanModal();
    initSearchForm();
    if (initAiModal()) initAiSubmit();
})();
