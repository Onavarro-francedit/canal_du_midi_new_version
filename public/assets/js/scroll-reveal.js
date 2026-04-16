/**
 * Scroll Reveal — Intersection Observer based animations.
 *
 * Usage in HTML:
 *   data-reveal="up|down|left|right|zoom|fade"  → single element reveal
 *   data-reveal-stagger                          → children animate in sequence
 *   data-delay="200"                             → optional extra delay (ms)
 */
(function () {
    'use strict';

    // Respect user motion preferences
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var STAGGER_STEP = 100; // ms between each staggered child

    // Single-element reveals
    var singles = document.querySelectorAll('[data-reveal]');
    // Staggered containers
    var staggers = document.querySelectorAll('[data-reveal-stagger]');

    if (!singles.length && !staggers.length) return;

    // --- Observer for single reveals ---
    var singleObserver = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var delay = parseInt(el.getAttribute('data-delay'), 10) || 0;
                if (delay) {
                    setTimeout(function () { el.classList.add('is-visible'); }, delay);
                } else {
                    el.classList.add('is-visible');
                }
                singleObserver.unobserve(el);
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    singles.forEach(function (el) {
        singleObserver.observe(el);
    });

    // --- Observer for staggered containers ---
    var staggerObserver = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var container = entry.target;
                var children = container.children;
                for (var i = 0; i < children.length; i++) {
                    children[i].style.setProperty('--stagger-delay', (i * STAGGER_STEP) + 'ms');
                }
                container.classList.add('is-visible');
                staggerObserver.unobserve(container);
            });
        },
        { threshold: 0.08, rootMargin: '0px 0px -30px 0px' }
    );

    staggers.forEach(function (el) {
        staggerObserver.observe(el);
    });
})();
