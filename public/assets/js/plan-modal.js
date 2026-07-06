/**
 * plan-modal.js — Modal du « Plan du Canal du Midi 2026 » (section #plan de la home).
 *
 * - Patrón IIFE 'use strict', sin librerías (SEC-003: ningún CDN).
 * - Abre el modal al hacer clic (o Enter/Espacio) sobre la miniatura [data-open-plan-modal].
 * - Cierra con el botón [data-close-plan-modal], clic en el fondo, o tecla Escape.
 * - Lazy-load real: el iframe de Calaméo solo carga (data-src → src) la primera
 *   vez que se abre el modal; no se descarga en cada visita a la home.
 * - Accesibilidad: bloquea el scroll de fondo, gestiona aria-hidden y devuelve
 *   el foco al elemento que abrió el modal al cerrarlo.
 * - Nunca usa innerHTML: solo clases, atributos y .src del iframe.
 */
(function () {
    'use strict';

    var modal = document.getElementById('plan-modal');
    var opener = document.querySelector('[data-open-plan-modal]');
    if (!modal || !opener) {
        return;
    }

    var iframe = modal.querySelector('iframe[data-src]');
    var closeBtn = modal.querySelector('[data-close-plan-modal]');
    var lastFocused = null;

    function openModal() {
        // Lazy-load del iframe la primera vez.
        if (iframe && !iframe.src) {
            iframe.src = iframe.getAttribute('data-src');
        }
        lastFocused = document.activeElement;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeModal() {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    opener.addEventListener('click', openModal);
    opener.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            openModal();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // Clic en el fondo (fuera del contenido) cierra el modal.
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Escape cierra el modal solo si está abierto.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
})();
