/**
 * home-hero.js — Orquestación WOW del hero de la home (TASK-010).
 *
 * - Patrón IIFE 'use strict', sin librerías (SEC-003: ningún CDN).
 * - Early-return si el usuario pide reduced-motion: el CSS ya deja todo
 *   visible; no tocamos nada.
 * - Tras window.load añade html.hero-ready, que dispara en CSS la entrada
 *   escalonada + Ken Burns.
 * - Parallax sutil: traslada SOLO .hero-card-img con translate3d en scroll
 *   (máx ~7% del alto del hero). No toca otras secciones. Sin scroll-jacking.
 * - Puramente decorativo: solo clases y transform, nunca innerHTML.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        return;
    }

    var root = document.documentElement;
    var heroSection = document.querySelector('.hero-section');
    var heroImg = document.querySelector('.hero-card-img');

    // Activa la secuencia de entrada (la define el CSS bajo .hero-ready).
    function triggerSequence() {
        requestAnimationFrame(function () {
            root.classList.add('hero-ready');
        });
    }

    if (document.readyState === 'complete') {
        triggerSequence();
    } else {
        window.addEventListener('load', triggerSequence, { once: true });
    }

    // Parallax sutil del hero — solo la imagen, solo transform.
    if (!heroSection || !heroImg) {
        return;
    }

    var ticking = false;
    var MAX_RATIO = 0.07; // ~7% del alto del hero como desplazamiento máximo

    function updateParallax() {
        ticking = false;
        var rect = heroSection.getBoundingClientRect();
        // Fuera de vista: no hace falta mover nada.
        if (rect.bottom < 0 || rect.top > window.innerHeight) {
            return;
        }
        var maxShift = rect.height * MAX_RATIO;
        // Progreso del scroll relativo al hero (0 arriba, crece al bajar).
        var progress = -rect.top / rect.height;
        var shift = progress * maxShift;
        // Acotamos para que nunca exponga el borde de la imagen.
        if (shift > maxShift) { shift = maxShift; }
        if (shift < -maxShift) { shift = -maxShift; }
        // Propiedad individual `translate` (no `transform`) para componer con
        // la animación Ken Burns, que usa la propiedad individual `scale`.
        heroImg.style.translate = '0 ' + shift.toFixed(1) + 'px';
    }

    function onScroll() {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(updateParallax);
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    updateParallax();
})();
