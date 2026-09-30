/**
 * Ficha 2026 — carrusel del hero, lightbox de la galería y mapa (Google Maps, cargado por el tema).
 * Port de hero-carousel.js, lightbox.js y map.js (app local).
 */
document.addEventListener('DOMContentLoaded', () => {
    // WP: la cabecera del tema se esconde al bajar (transform): la barra de acciones sticky sigue su borde inferior.
    const header = document.querySelector('.cdm-header');
    const root = document.querySelector('.cdm-fiche');
    if (header && root) {
        let pending = false;
        const syncBar = () => {
            pending = false;
            root.style.setProperty('--fiche-bar-top', Math.max(0, header.getBoundingClientRect().bottom) + 'px');
        };
        const onScroll = () => { if (!pending) { pending = true; requestAnimationFrame(syncBar); } };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
        header.addEventListener('transitionend', syncBar);
        syncBar();
    }

    // --- Hero: fondo cruzado entre las fotos cada 5 s ---
    const hero = document.getElementById('service-hero');
    if (hero) {
        let slides = [];
        try { slides = JSON.parse(hero.dataset.gallery || '[]'); } catch (e) { slides = []; }
        slides = Array.isArray(slides) ? slides.filter((u) => /^https:\/\//.test(u)) : [];
        const layers = [hero.querySelector('.service-hero-bg--a'), hero.querySelector('.service-hero-bg--b')];
        if (slides.length > 1 && layers[0] && layers[1] && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            let active = 0;
            let index = 0;
            setInterval(() => {
                index = (index + 1) % slides.length;
                const next = active === 0 ? 1 : 0;
                layers[next].style.backgroundImage = 'url("' + encodeURI(slides[index]) + '")';
                layers[next].classList.add('is-visible');
                layers[active].classList.remove('is-visible');
                active = next;
            }, 5000);
        }
    }

    // --- Lightbox ---
    const lightbox = document.getElementById('lightbox');
    const triggers = Array.from(document.querySelectorAll('.lightbox-trigger'));
    if (lightbox && triggers.length) {
        const img = document.getElementById('lightbox-img');
        const images = triggers.map((t) => t.dataset.full || t.currentSrc || t.src); // WP: original en el visor, reducida en la rejilla
        let current = 0;
        let opener = null;
        const show = () => { img.src = images[current]; img.alt = triggers[current].alt; };
        const open = (i) => {
            current = i; opener = document.activeElement; show();
            lightbox.classList.add('is-active'); lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            lightbox.querySelector('.lightbox-close').focus();
        };
        const close = () => {
            lightbox.style.display = 'none'; lightbox.classList.remove('is-active');
            document.body.style.overflow = '';
            if (opener) opener.focus();
        };
        const step = (d) => (e) => { if (e) e.stopPropagation(); current = (current + d + images.length) % images.length; show(); };
        triggers.forEach((t, i) => {
            t.tabIndex = 0;
            t.addEventListener('click', () => open(i));
            t.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(i); } });
        });
        lightbox.querySelector('.lightbox-next').addEventListener('click', step(1));
        lightbox.querySelector('.lightbox-prev').addEventListener('click', step(-1));
        lightbox.querySelector('.lightbox-close').addEventListener('click', close);
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox || e.target.classList.contains('lightbox-content')) close(); });
        document.addEventListener('keydown', (e) => {
            if (lightbox.style.display !== 'flex') return;
            if (e.key === 'ArrowRight') step(1)();
            if (e.key === 'ArrowLeft') step(-1)();
            if (e.key === 'Escape') close();
        });
    }

    // --- Mapa (WP: Google Maps en diferido; antes lo cargaba el tema en todas las páginas, ~400 KB) ---
    const el = document.getElementById('map');
    if (!el) return;
    const initMap = () => {
        const home = { lat: parseFloat(el.dataset.lat), lng: parseFloat(el.dataset.lng) };
        const map = new google.maps.Map(el, { center: home, zoom: 14, scrollwheel: false });
        const dot = (color, size) => ({
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
                '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '"><circle cx="' + size / 2 + '" cy="' + size / 2 + '" r="10" fill="' + color + '" stroke="white" stroke-width="3"/></svg>'),
            scaledSize: new google.maps.Size(size, size),
            anchor: new google.maps.Point(size / 2, size / 2),
        });
        const label = (text) => { const s = document.createElement('strong'); s.textContent = text; return s; };
        const main = new google.maps.Marker({ position: home, map, icon: dot('#6a63d9', 26), zIndex: 1000, title: el.dataset.title });
        const mainInfo = new google.maps.InfoWindow({ content: label(el.dataset.title) });
        main.addListener('click', () => mainInfo.open({ map, anchor: main }));

        let hover = null;
        let hoverInfo = null;
        document.querySelectorAll('.poi-hover-trigger').forEach((t) => {
            t.addEventListener('mouseenter', () => {
                const pos = { lat: parseFloat(t.dataset.lat), lng: parseFloat(t.dataset.lng) };
                if (hover) hover.setMap(null);
                hover = new google.maps.Marker({ position: pos, map, icon: dot('#ef4444', 30) });
                hoverInfo = new google.maps.InfoWindow({ content: label(t.dataset.name) });
                hoverInfo.open({ map, anchor: hover });
                const bounds = new google.maps.LatLngBounds();
                bounds.extend(home); bounds.extend(pos);
                map.fitBounds(bounds, 60);
            });
            t.addEventListener('mouseleave', () => {
                if (hover) { hover.setMap(null); hover = null; }
                if (hoverInfo) { hoverInfo.close(); hoverInfo = null; }
                map.panTo(home); map.setZoom(14);
            });
        });
    };
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
        return;
    }
    const src = (window.CDM_FICHE && CDM_FICHE.mapsSrc) || '';
    if (!src) return;
    let requested = false;
    const load = () => {
        if (requested) return;
        requested = true;
        window.canalFicheMapReady = initMap;
        const s = document.createElement('script');
        s.src = src + (src.indexOf('?') === -1 ? '?' : '&') + 'loading=async&callback=canalFicheMapReady';
        s.async = true;
        document.head.appendChild(s);
    };
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting)) { io.disconnect(); load(); }
        }, { rootMargin: '400px' });
        io.observe(el);
    } else {
        load();
    }
});
