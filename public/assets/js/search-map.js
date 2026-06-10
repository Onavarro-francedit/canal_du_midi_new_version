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

    if (!mapElement || typeof L === 'undefined') {
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
    let mapTilesReady = false;
    let markersReady = false;
    let mapReadyEmitted = false;

    const maybeSignalMapReady = () => {
        if (mapReadyEmitted || !mapTilesReady || !markersReady) return;
        mapReadyEmitted = true;
        window.dispatchEvent(new CustomEvent('search:map-ready'));
    };

    map = L.map('explore-map', { zoomControl: false }).setView([43.6, 1.44], 10);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    const tileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap contributors © CARTO',
    });

    tileLayer.once('load', () => {
        mapTilesReady = true;
        maybeSignalMapReady();
    });

    tileLayer.once('tileerror', () => {
        mapTilesReady = true;
        maybeSignalMapReady();
    });

    tileLayer.addTo(map);

    // 2. Añadir Marcadores con clustering
    let clusterGroup = null;
    let carouselDestroyFns = {};

    const createClusterGroup = () => L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        iconCreateFunction: function (cluster) {
            const count = cluster.getChildCount();
            const size  = count >= 50 ? 'large' : count >= 20 ? 'medium' : 'small';
            return L.divIcon({
                html: '<div class="cluster-marker cluster-' + size + '"><span>' + count + '</span></div>',
                className: 'search-cluster-icon',
                iconSize: [44, 44],
                iconAnchor: [22, 22],
            });
        },
    });

    const buildMarkers = (items) => {
        carouselDestroyFns = {};
        markers = {};

        const nextClusterGroup = createClusterGroup();

        items.forEach((item) => {
            const customIcon = L.divIcon({
                className: 'search-marker',
                html: '<div class="map-pin" id="marker-' + escapeText(item.id) + '"><i class="bi bi-geo-alt-fill"></i></div>',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
            });

            const galleryImages = Array.isArray(item.gallery) && item.gallery.length > 0
                ? item.gallery
                : (item.image ? [item.image] : []);

            const { element: carouselEl, destroy: destroyCarousel } = createPopupCarousel(galleryImages, item.title);
            carouselDestroyFns[item.id] = destroyCarousel;

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

            const marker = L.marker([item.lat, item.lng], { icon: customIcon })
                .bindPopup(popupContent);

            marker.on('popupclose', () => {
                if (typeof carouselDestroyFns[item.id] === 'function') {
                    carouselDestroyFns[item.id]();
                }
            });

            nextClusterGroup.addLayer(marker);
            markers[item.id] = marker;
        });

        return nextClusterGroup;
    };

    const renderMapResults = (items) => {
        const normalizedItems = Array.isArray(items)
            ? items.filter((item) => item && Number(item.lat) !== 0 && Number(item.lng) !== 0)
            : [];

        if (clusterGroup) {
            map.removeLayer(clusterGroup);
        }

        clusterGroup = buildMarkers(normalizedItems);
        map.addLayer(clusterGroup);

        if (normalizedItems.length > 0) {
            map.fitBounds(clusterGroup.getBounds(), { padding: [50, 50] });
        } else {
            map.setView([43.6, 1.44], 10);
        }

        markersReady = true;
        maybeSignalMapReady();

        return normalizedItems;
    };

    window.setSearchMapResults = renderMapResults;

    renderMapResults(validResults);

    maybeSignalMapReady();

    /* ── Vista móvil ── */

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
            window.setTimeout(() => map.invalidateSize(), 180);
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
            map.invalidateSize();
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
            detailMap = L.map('listing-detail-map', { zoomControl: true, attributionControl: true });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap contributors © CARTO',
            }).addTo(detailMap);
        }

        if (detailMarker) detailMarker.remove();

        // textContent en lugar de innerHTML para el título del popup
        const popupDiv = document.createElement('div');
        const strong   = document.createElement('strong');
        strong.textContent = escapeText(service.title);
        popupDiv.appendChild(strong);

        detailMarker = L.marker([service.lat, service.lng]).addTo(detailMap);
        detailMarker.bindPopup(popupDiv).openPopup();
        detailMap.setView([service.lat, service.lng], 14);
        setTimeout(() => detailMap.invalidateSize(), 120);
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
    const markerDiv = document.getElementById('marker-' + id);
    if (markerDiv) {
        markerDiv.classList.add('is-active');
        if (markers[id]) markers[id].setZIndexOffset(1000);
    }
};

window.resetMarker = (id) => {
    const markerDiv = document.getElementById('marker-' + id);
    if (markerDiv) markerDiv.classList.remove('is-active');
    if (markers[id]) markers[id].setZIndexOffset(0);
};