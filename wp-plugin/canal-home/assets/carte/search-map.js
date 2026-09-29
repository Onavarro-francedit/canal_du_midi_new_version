/**
 * Módulo: search-map.js
 * Funcionalidad: Mapa global de resultados con interactividad cruzada.
 *
 * Mejoras v2:
 *  - Carrusel automático con pausa al hover/focus
 *  - Botones prev/next siempre visibles (construidos sin innerHTML)
 *  - Validación de URLs sin window.location.origin como base (evita URLs relativas)
 *  - Iconos creados con createElement en lugar de innerHTML (evita XSS)
 *  - Datos de servidor asignados solo via textContent (evita XSS)
 *  - Limpieza de timers al cerrar popups (evita memory leaks)
 *  - CSS.escape() en selectores dinámicos
 *  - rel="noopener noreferrer" en enlaces con target="_blank"
 *  - resetMarker también reinicia zIndexOffset
 *
 * v3: motor de mapa migrado de Leaflet a Google Maps JS API + @googlemaps/markerclusterer.
 */

const CAROUSEL_INTERVAL_MS = 3500;

let map;
let markers = {};
let detailMap;
let detailMarker;

/* ── Utilidades de seguridad ── */

const isSafeHttpUrl = (value) => {
    if (!value || typeof value !== 'string') return false;
    try {
        // Sin segundo argumento: rechaza URLs relativas y data:/javascript:
        const parsed = new URL(value);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch {
        return false;
    }
};

const escapeText = (value) => String(value ?? '').trim();

// Crea <i class="bi bi-*"> sin innerHTML para evitar XSS
const createIcon = (iconClass) => {
    const i = document.createElement('i');
    if (/^bi bi-[a-z0-9-]+$/.test(iconClass)) {
        i.className = iconClass;
    }
    return i;
};

const buildMapsDirectionsUrl = (lat, lng, label, address) => {
    const latitude = Number(lat);
    const longitude = Number(lng);

    if (Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0 && longitude !== 0) {
        return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(latitude + ',' + longitude);
    }

    const fallbackQuery = escapeText(address || label);
    return fallbackQuery !== ''
        ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(fallbackQuery)
        : '#';
};

/* ── Carrusel ── */

const createPopupCarousel = (images, title) => {
    const safeImages = Array.isArray(images) ? images.filter(isSafeHttpUrl) : [];
    const safeTitle  = escapeText(title);

    const wrapper = document.createElement('div');
    wrapper.className = 'map-popup';

    const media = document.createElement('div');
    media.className = 'map-popup__media';
    wrapper.appendChild(media);

    if (safeImages.length === 0) {
        const placeholder = document.createElement('div');
        placeholder.className = 'map-popup__media--placeholder';
        placeholder.textContent = 'Sin imagen disponible';
        media.appendChild(placeholder);
        return { element: wrapper, destroy: () => {} };
    }

    const track = document.createElement('div');
    track.className = 'map-popup__track';
    media.appendChild(track);

    const slides = safeImages.map((url, index) => {
        const slide = document.createElement('div');
        slide.className = 'map-popup__slide' + (index === 0 ? ' is-active' : '');
        slide.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');

        const img = document.createElement('img');
        img.src      = url;
        img.alt      = safeTitle + ' - imagen ' + (index + 1);
        img.loading  = 'lazy';
        img.decoding = 'async';
        slide.appendChild(img);
        track.appendChild(slide);
        return slide;
    });

    let currentIndex = 0;
    let autoTimer    = null;

    // Declarar indicators aquí para que updateCarousel lo pueda usar
    const indicators = document.createElement('div');
    indicators.className = 'map-popup__indicators';

    const updateCarousel = (nextIndex, resetTimer) => {
        if (resetTimer === undefined) resetTimer = true;
        currentIndex = ((nextIndex % slides.length) + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const active = i === currentIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        indicators.querySelectorAll('button').forEach((btn, i) => {
            const active = i === currentIndex;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-current', active ? 'true' : 'false');
        });

        if (resetTimer) restartAutoPlay();
    };

    const startAutoPlay = () => {
        if (slides.length <= 1) return;
        autoTimer = window.setInterval(() => updateCarousel(currentIndex + 1, false), CAROUSEL_INTERVAL_MS);
    };

    const stopAutoPlay = () => {
        if (autoTimer !== null) {
            window.clearInterval(autoTimer);
            autoTimer = null;
        }
    };

    const restartAutoPlay = () => { stopAutoPlay(); startAutoPlay(); };

    // Pausa al hover/focus
    media.addEventListener('mouseenter', stopAutoPlay);
    media.addEventListener('focusin',    stopAutoPlay);
    media.addEventListener('mouseleave', startAutoPlay);
    media.addEventListener('focusout',   startAutoPlay);

    if (slides.length > 1) {
        const controls = document.createElement('div');
        controls.className = 'map-popup__controls';

        const prevButton = document.createElement('button');
        prevButton.type = 'button';
        prevButton.className = 'map-popup__nav map-popup__nav--prev';
        prevButton.setAttribute('aria-label', 'Imagen anterior');
        prevButton.appendChild(createIcon('bi bi-chevron-left'));

        const nextButton = document.createElement('button');
        nextButton.type = 'button';
        nextButton.className = 'map-popup__nav map-popup__nav--next';
        nextButton.setAttribute('aria-label', 'Imagen siguiente');
        nextButton.appendChild(createIcon('bi bi-chevron-right'));

        prevButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            updateCarousel(currentIndex - 1);
        });

        nextButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            updateCarousel(currentIndex + 1);
        });

        controls.appendChild(prevButton);
        controls.appendChild(nextButton);
        media.appendChild(controls);

        safeImages.forEach((_, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'map-popup__indicator' + (i === 0 ? ' is-active' : '');
            btn.setAttribute('aria-label', 'Ver imagen ' + (i + 1));
            btn.setAttribute('aria-current', i === 0 ? 'true' : 'false');
            btn.addEventListener('click', () => updateCarousel(i));
            indicators.appendChild(btn);
        });

        media.appendChild(indicators);
    }

    startAutoPlay();

    const content = document.createElement('div');
    content.className = 'map-popup__content';
    wrapper.appendChild(content);

    const heading = document.createElement('strong');
    heading.className = 'map-popup__title';
    heading.textContent = safeTitle;
    content.appendChild(heading);

    return { element: wrapper, destroy: stopAutoPlay };
};

/* ── Iconos de marcador (SVG data URI, sin dependencia de sprites externos) ── */

const PIN_COLOR        = '#6366f1';
const PIN_COLOR_ACTIVE = '#ef4444';
const CLUSTER_TIERS = [
    { min: 50, size: 60, color: '#8b5cf6' },
    { min: 20, size: 52, color: '#6366f1' },
    { min: 0,  size: 44, color: '#1a1d26' },
];

const svgToDataUrl = (svg) => 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);

const buildPinIcon = (active) => {
    const color = active ? PIN_COLOR_ACTIVE : PIN_COLOR;
    const size  = active ? 40 : 32;
    return {
        url: svgToDataUrl(
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">' +
            '<path fill="' + color + '" stroke="#fff" stroke-width="1" ' +
            'd="M12 0C7.6 0 4 3.6 4 8c0 5.4 6.6 13.2 7.1 13.8.2.2.5.3.9.3s.7-.1.9-.3C13.4 21.2 20 13.4 20 8c0-4.4-3.6-8-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"/>' +
            '</svg>'
        ),
        scaledSize: new google.maps.Size(size, size),
        anchor: new google.maps.Point(size / 2, size),
    };
};

const buildClusterIcon = (count) => {
    const tier = CLUSTER_TIERS.find((t) => count >= t.min);
    const r    = tier.size / 2;
    return {
        icon: {
            url: svgToDataUrl(
                '<svg xmlns="http://www.w3.org/2000/svg" width="' + tier.size + '" height="' + tier.size + '" viewBox="0 0 ' + tier.size + ' ' + tier.size + '">' +
                '<circle cx="' + r + '" cy="' + r + '" r="' + (r - 2) + '" fill="' + tier.color + '" stroke="#fff" stroke-width="3"/>' +
                '</svg>'
            ),
            scaledSize: new google.maps.Size(tier.size, tier.size),
            anchor: new google.maps.Point(r, r),
        },
        label: {
            text: String(count),
            color: '#fff',
            fontWeight: '800',
            fontSize: count >= 50 ? '1rem' : count >= 20 ? '0.9rem' : '0.85rem',
        },
    };
};

/* ── DOMContentLoaded ── */

document.addEventListener('DOMContentLoaded', () => {
    const mapElement         = document.getElementById('explore-map');
    const pageShell          = document.querySelector('.search-layout-page');
    const backdrop           = document.getElementById('search-mobile-backdrop');
    const mobileTriggers     = document.querySelectorAll('.mobile-view-trigger');
    const detailModal        = document.getElementById('listing-detail-modal');
    const detailOpenButtons  = document.querySelectorAll('.card-detail-trigger');
    const detailCloseButtons = document.querySelectorAll('[data-close-listing-modal]');
    const urlParams          = new URLSearchParams(window.location.search);
    const highlightId        = urlParams.get('highlight');

    const signalMapReady = () => {
        window.dispatchEvent(new CustomEvent('search:map-ready'));
    };

    if (!mapElement || typeof google === 'undefined' || !google.maps) {
        // WP: sin Google Maps (clave ausente o bloqueada) → aviso en lugar de un panel vacío.
        if (mapElement) {
            const notice = document.createElement('div');
            notice.className = 'map-unavailable';
            notice.textContent = 'Carte indisponible';
            mapElement.appendChild(notice);
        }
        signalMapReady();
        return;
    }

    const compactMedia = window.matchMedia('(max-width: 1180px)');

    // Mantiene compatibilidad: acepta tanto searchResults global como window.searchResults
    const rawResults = Array.isArray(window.searchResults)
        ? window.searchResults
        : (typeof searchResults !== 'undefined' ? searchResults : []);
    const validResults = Array.isArray(rawResults)
        ? rawResults.filter((item) => item && Number(item.lat) !== 0 && Number(item.lng) !== 0)
        : [];

    // 1. Inicializar Mapa centrado en el Canal du Midi
    let mapReadyEmitted = false;

    const maybeSignalMapReady = () => {
        if (mapReadyEmitted) return;
        mapReadyEmitted = true;
        window.dispatchEvent(new CustomEvent('search:map-ready'));
    };

    map = new google.maps.Map(mapElement, {
        center: { lat: 43.6, lng: 1.44 },
        zoom: 10,
        zoomControl: true,
        zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_BOTTOM },
        mapTypeControl: true,
        mapTypeControlOptions: { position: google.maps.ControlPosition.TOP_LEFT },
        streetViewControl: true,
        streetViewControlOptions: { position: google.maps.ControlPosition.RIGHT_BOTTOM },
        fullscreenControl: true,
    });

    google.maps.event.addListenerOnce(map, 'idle', maybeSignalMapReady);

    const infoWindow = new google.maps.InfoWindow({ maxWidth: 320 });
    let activeCarouselDestroy = null;
    infoWindow.addListener('closeclick', () => {
        if (typeof activeCarouselDestroy === 'function') activeCarouselDestroy();
        activeCarouselDestroy = null;
    });

    // 2. Añadir Marcadores con clustering
    let clusterer = null;

    const buildMarkers = (items) => items.map((item) => {
        const marker = new google.maps.Marker({
            position: { lat: item.lat, lng: item.lng },
            icon: buildPinIcon(false),
        });
        marker.set('serviceId', item.id);

        const galleryImages = Array.isArray(item.gallery) && item.gallery.length > 0
            ? item.gallery
            : (item.image ? [item.image] : []);

        marker.addListener('click', () => {
            const { element: carouselEl, destroy: destroyCarousel } = createPopupCarousel(galleryImages, item.title);

            const popupContent = document.createElement('div');
            popupContent.className = 'map-popup-shell';
            popupContent.appendChild(carouselEl);

            const popupAdresse = document.createElement('span');
            popupAdresse.className = 'map-popup-type';
            popupAdresse.textContent = item.address
                ? item.address.trim().replace(', ' + escapeText(item.title), '')
                : 'Adresse';
            popupContent.appendChild(popupAdresse);

            const actions = document.createElement('div');
            actions.className = 'map-popup__actions';

            const phone = String(item.phone ?? '').trim();
            if (phone !== '') {
                const callLink = document.createElement('a');
                callLink.className = 'map-popup__action map-popup__action--call';
                callLink.href = 'tel:' + phone.replace(/\s+/g, '');
                callLink.setAttribute('aria-label', 'Appeler');
                callLink.appendChild(createIcon('bi bi-telephone-fill'));
                const callText = document.createElement('span');
                callText.textContent = 'Appeler';
                callLink.appendChild(callText);
                actions.appendChild(callLink);
            }

            const email = String(item.email ?? '').trim();
            if (email !== '') {
                const emailLink = document.createElement('a');
                emailLink.className = 'map-popup__action map-popup__action--email';
                emailLink.href = 'mailto:' + encodeURIComponent(email);
                emailLink.setAttribute('aria-label', 'Envoyer un email');
                emailLink.appendChild(createIcon('bi bi-envelope-fill'));
                const emailText = document.createElement('span');
                emailText.textContent = 'Email';
                emailLink.appendChild(emailText);
                actions.appendChild(emailLink);
            }

            const itineraryLink = document.createElement('a');
            itineraryLink.className = 'map-popup__action map-popup__action--route';
            itineraryLink.href = buildMapsDirectionsUrl(item.lat, item.lng, item.title, item.address);
            itineraryLink.target = '_blank';
            itineraryLink.rel = 'noopener noreferrer';
            itineraryLink.setAttribute('aria-label', 'Obtenir l\'itinéraire');
            itineraryLink.appendChild(createIcon('bi bi-sign-turn-right-fill'));
            const routeText = document.createElement('span');
            routeText.textContent = 'Itinéraire';
            itineraryLink.appendChild(routeText);
            actions.appendChild(itineraryLink);

            popupContent.appendChild(actions);

            const popupLink = document.createElement('a');
            popupLink.href = isSafeHttpUrl(item.url) ? item.url : '#';
            popupLink.className = 'map-popup-link';
            popupLink.textContent = 'Voir la fiche →';
            popupLink.target = '_blank';
            popupLink.rel = 'noopener noreferrer';
            popupContent.appendChild(popupLink);

            if (typeof activeCarouselDestroy === 'function') activeCarouselDestroy();
            activeCarouselDestroy = destroyCarousel;

            infoWindow.setContent(popupContent);
            infoWindow.open({ map, anchor: marker });
        });

        markers[item.id] = marker;
        return marker;
    });

    const clusterRenderer = {
        render: ({ count, position }) => {
            const { icon, label } = buildClusterIcon(count);
            return new google.maps.Marker({ position, icon, label, zIndex: 1000 + count });
        },
    };

    const renderMapResults = (items) => {
        const normalizedItems = Array.isArray(items)
            ? items.filter((item) => item && Number(item.lat) !== 0 && Number(item.lng) !== 0)
            : [];

        if (clusterer) {
            clusterer.clearMarkers();
        }
        markers = {};

        const newMarkers = buildMarkers(normalizedItems);
        clusterer = new markerClusterer.MarkerClusterer({ map, markers: newMarkers, renderer: clusterRenderer });

        if (normalizedItems.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            normalizedItems.forEach((item) => bounds.extend({ lat: item.lat, lng: item.lng }));
            map.fitBounds(bounds, 50);
        } else {
            map.setCenter({ lat: 43.6, lng: 1.44 });
            map.setZoom(10);
        }

        maybeSignalMapReady();

        return normalizedItems;
    };

    window.setSearchMapResults = renderMapResults;

    renderMapResults(validResults);

    /* ── Vista móvil ── */

    const refreshMapSize = () => {
        const center = map.getCenter();
        google.maps.event.trigger(map, 'resize');
        if (center) map.setCenter(center);
    };

    const setMobileView = (view) => {
        if (!pageShell) return;

        const currentView    = pageShell.dataset.mobileView || 'list';
        const keepMapVisible = compactMedia.matches && view === 'filters' && currentView === 'map';

        pageShell.dataset.mobileView  = keepMapVisible ? 'map' : view;
        pageShell.dataset.filtersOpen = view === 'filters' ? 'true' : 'false';

        const activeTarget = pageShell.dataset.filtersOpen === 'true' ? 'filters' : pageShell.dataset.mobileView;
        mobileTriggers.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.mobileTarget === activeTarget);
        });

        document.body.style.overflow = pageShell.dataset.filtersOpen === 'true' ? 'hidden' : '';

        if (pageShell.dataset.mobileView === 'map') {
            window.setTimeout(refreshMapSize, 180);
        }
    };

    mobileTriggers.forEach((button) => {
        button.addEventListener('click', () => {
            setMobileView(button.dataset.mobileTarget || 'list');
        });
    });

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            if (!pageShell) return;
            if (pageShell.dataset.filtersOpen === 'true') {
                pageShell.dataset.filtersOpen = 'false';
                setMobileView(pageShell.dataset.mobileView || 'list');
                return;
            }
            setMobileView('list');
        });
    }

    const desktopMedia   = window.matchMedia('(min-width: 1181px)');
    const syncLayoutMode = (event) => {
        if (event.matches) {
            document.body.style.overflow = '';
            if (pageShell) {
                pageShell.dataset.mobileView  = 'list';
                pageShell.dataset.filtersOpen = 'false';
            }
            mobileTriggers.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.mobileTarget === 'list');
            });
            refreshMapSize();
        } else {
            setMobileView(pageShell?.dataset.mobileView || 'list');
        }
    };

    syncLayoutMode(desktopMedia);
    desktopMedia.addEventListener('change', syncLayoutMode);

    /* ── Modal de detalle ── */

    const detailElements = {
        image:       document.getElementById('listing-detail-image'),
        title:       document.getElementById('listing-detail-title'),
        type:        document.getElementById('listing-detail-type'),
        description: document.getElementById('listing-detail-description'),
        tags:        document.getElementById('listing-detail-tags'),
        price:       document.getElementById('listing-detail-price'),
        link:        document.getElementById('listing-detail-link'),
    };

    const buildTags = (service) => {
        const tags = [
            { icon: 'bi bi-geo-alt',  label: service.address || 'Canal du Midi' },
            { icon: 'bi bi-bookmark', label: service.type    || 'Adresse' },
        ];
        if (service.label) tags.push({ icon: 'bi bi-award',     label: service.label });
        if (service.phone) tags.push({ icon: 'bi bi-telephone', label: service.phone });
        return tags;
    };

    const renderDetailMap = (service) => {
        const mapTarget = document.getElementById('listing-detail-map');
        if (!mapTarget) return;

        if (!detailMap) {
            detailMap = new google.maps.Map(mapTarget, {
                center: { lat: service.lat, lng: service.lng },
                zoom: 14,
                zoomControl: true,
            });
        }

        if (detailMarker) detailMarker.setMap(null);

        detailMarker = new google.maps.Marker({
            position: { lat: service.lat, lng: service.lng },
            map: detailMap,
        });

        // textContent en lugar de innerHTML para el título del popup
        const popupDiv = document.createElement('div');
        const strong   = document.createElement('strong');
        strong.textContent = escapeText(service.title);
        popupDiv.appendChild(strong);

        const detailInfoWindow = new google.maps.InfoWindow({ content: popupDiv });
        detailInfoWindow.open({ map: detailMap, anchor: detailMarker });

        detailMap.setCenter({ lat: service.lat, lng: service.lng });
        detailMap.setZoom(14);
        setTimeout(() => google.maps.event.trigger(detailMap, 'resize'), 120);
    };

    const openDetailModal = (serviceId) => {
        const service = Array.isArray(rawResults)
            ? rawResults.find((item) => item && String(item.id) === String(serviceId))
            : null;

        if (!service || !detailModal) return;

        if (detailElements.image) {
            detailElements.image.src = isSafeHttpUrl(service.image) ? service.image : '';
            detailElements.image.alt = escapeText(service.title);
        }
        if (detailElements.title)       detailElements.title.textContent       = escapeText(service.title);
        if (detailElements.type)        detailElements.type.textContent        = escapeText(service.type) || 'Adresse';
        if (detailElements.description) detailElements.description.textContent =
            escapeText(service.description || service.tag) || 'Une adresse sélectionnée pour découvrir le Canal du Midi.';
        if (detailElements.price)       detailElements.price.textContent       = escapeText(service.price);
        if (detailElements.link) {
            detailElements.link.href   = isSafeHttpUrl(service.url) ? service.url : '#';
            detailElements.link.rel    = 'noopener noreferrer';
            detailElements.link.target = '_blank';
        }

        if (detailElements.tags) {
            detailElements.tags.innerHTML = '';
            buildTags(service).forEach(({ icon, label }) => {
                const node = document.createElement('span');
                node.className = 'listing-detail-tag';
                node.appendChild(createIcon(icon));
                node.appendChild(document.createTextNode(escapeText(label)));
                detailElements.tags.appendChild(node);
            });
        }

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (Number(service.lat) !== 0 && Number(service.lng) !== 0) {
            renderDetailMap(service);
        }
    };

    const closeDetailModal = () => {
        if (!detailModal) return;
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = pageShell?.dataset.filtersOpen === 'true' ? 'hidden' : '';
    };

    detailOpenButtons.forEach((button) => {
        button.addEventListener('click', () => openDetailModal(button.dataset.serviceId));
    });

    detailCloseButtons.forEach((button) => {
        button.addEventListener('click', closeDetailModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeDetailModal();
    });

    if (highlightId && window.highlightMarker) {
        setTimeout(() => {
            window.highlightMarker(highlightId);
            const card = document.querySelector('.explore-card[data-id="' + CSS.escape(highlightId) + '"]');
            if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 1000);
    }
});

/* ── API pública ── */

window.highlightMarker = (id) => {
    const marker = markers[id];
    if (!marker) return;
    marker.setIcon(buildPinIcon(true));
    marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
};

window.resetMarker = (id) => {
    const marker = markers[id];
    if (!marker) return;
    marker.setIcon(buildPinIcon(false));
    marker.setZIndex(null);
};
