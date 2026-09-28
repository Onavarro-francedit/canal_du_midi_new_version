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

    // ── Scroll reveal ───────────────────────────────────────────────────
    function initReveal() {
        if (reduceMotion || !('IntersectionObserver' in window)) return;
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

    initHero();
    initReveal();
    initPlanModal();
    initSearchForm();
    initAiModal();
})();
