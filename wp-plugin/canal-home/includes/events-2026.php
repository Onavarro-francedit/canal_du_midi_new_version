<?php
/**
 * Eventos de GA4 de las páginas 2026 (TASK-063 §5): hoy GA4 no mide ninguna conversión. Un solo listener delegado, en
 * línea en el pie; gtag() es el de canal_home_swap_gtag (GA4 diferido, respeta el consentimiento de Sirdata).
 * Conversiones: fiche_contact, plan_commande, plan_pdf y planner_request (este lo envía planner.js).
 */
defined('ABSPATH') || exit;

const CANAL_2026_EVENTS_JS = <<<'JS'
(function () {
    var send = function (name, params) { if (typeof window.gtag === 'function') window.gtag('event', name, params || {}); };
    var last = function () { return location.pathname.split('/').filter(Boolean).pop() || ''; };
    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[href]') : null;
        if (!a) return;
        var href = a.getAttribute('href') || '';
        if (/plan-canal-du-midi\.pdf/.test(href)) { send('plan_pdf', { source: location.pathname }); return; }
        if (a.closest('.cdm-fiche') && (/^(tel:|mailto:)/.test(href) || (a.hostname && a.hostname !== location.hostname))) {
            var method = /^tel:/.test(href) ? 'tel' : /^mailto:/.test(href) ? 'email'
                : /google\.[a-z.]+\/maps|maps\.google|maps\.apple|waze/.test(href) ? 'itineraire'
                : /facebook|instagram|youtube|tiktok/.test(href) ? 'social' : 'web';
            send('fiche_contact', { method: method, fiche: last() });
            return;
        }
        var cards = a.closest('.etape-cards');
        if (cards) {
            var section = cards.closest('section');
            send('etape_clic', { etape: last(), groupe: section ? (section.getAttribute('aria-labelledby') || '').replace('etape-', '') : '' });
        }
    }, true);
    document.addEventListener('wpcf7mailsent', function (e) {
        if (e.detail && String(e.detail.contactFormId) === '12976') send('plan_commande');
    });
})();
JS;

add_action('wp_footer', function () {
    if (canal_2026_is_template_request()) {
        echo '<script>' . CANAL_2026_EVENTS_JS . '</script>' . "\n"; // phpcs:ignore — JS fijo.
    }
}, 50);
