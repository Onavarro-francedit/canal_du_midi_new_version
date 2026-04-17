/**
 * Módulo: search-map.js
 * Funcionalidad: Mapa global de resultados con interactividad cruzada.
 */

let map;
let markers = {};
let detailMap;
let detailMarker;

document.addEventListener('DOMContentLoaded', () => {
    const mapElement = document.getElementById('explore-map');
    const pageShell = document.querySelector('.search-layout-page');
    const backdrop = document.getElementById('search-mobile-backdrop');
    const mobileTriggers = document.querySelectorAll('.mobile-view-trigger');
    const detailModal = document.getElementById('listing-detail-modal');
    const detailOpenButtons = document.querySelectorAll('.card-detail-trigger');
    const detailCloseButtons = document.querySelectorAll('[data-close-listing-modal]');
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');

    if (!mapElement || typeof L === 'undefined') return;

    const validResults = Array.isArray(searchResults)
        ? searchResults.filter((item) => Number(item.lat) !== 0 && Number(item.lng) !== 0)
        : [];

    // 1. Inicializar Mapa centrado en el Canal du Midi
    map = L.map('explore-map', { zoomControl: false }).setView([43.6, 1.44], 10);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png').addTo(map);

    // 2. Añadir Marcadores con clustering
    const clusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        iconCreateFunction: function(cluster) {
            const count = cluster.getChildCount();
            let size = 'small';
            if (count >= 50) size = 'large';
            else if (count >= 20) size = 'medium';
            return L.divIcon({
                html: `<div class="cluster-marker cluster-${size}"><span>${count}</span></div>`,
                className: 'search-cluster-icon',
                iconSize: [44, 44],
                iconAnchor: [22, 22]
            });
        }
    });

    validResults.forEach(item => {
        const customIcon = L.divIcon({
            className: 'search-marker',
            html: `<div class="map-pin" id="marker-${item.id}"><i class="bi bi-geo-alt-fill"></i></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32]
        });

        const marker = L.marker([item.lat, item.lng], { icon: customIcon })
            .bindPopup(`<div class="map-popup"><strong>${item.title}</strong><br><span class="map-popup-type">${item.type}</span><br><a href="${item.url}">Voir la fiche →</a></div>`);
        
        clusterGroup.addLayer(marker);
        markers[item.id] = marker;
    });

    map.addLayer(clusterGroup);

    // Ajustar el zoom automáticamente para que se vean todos los marcadores
    if (validResults.length > 0) {
        map.fitBounds(clusterGroup.getBounds(), { padding: [50, 50] });
    }

    const setMobileView = (view) => {
        if (!pageShell) return;

        pageShell.dataset.mobileView = view;
        mobileTriggers.forEach((button) => {
            const isActive = button.dataset.mobileTarget === view;
            button.classList.toggle('is-active', isActive);
        });

        document.body.style.overflow = view === 'filters' ? 'hidden' : '';

        if (view === 'map') {
            window.setTimeout(() => {
                map.invalidateSize();
            }, 180);
        }
    };

    mobileTriggers.forEach((button) => {
        button.addEventListener('click', () => {
            const targetView = button.dataset.mobileTarget || 'list';
            setMobileView(targetView);
        });
    });

    if (backdrop) {
        backdrop.addEventListener('click', () => setMobileView('list'));
    }

    const desktopMedia = window.matchMedia('(min-width: 901px)');
    const syncLayoutMode = (event) => {
        if (event.matches) {
            document.body.style.overflow = '';
            if (pageShell) {
                pageShell.dataset.mobileView = 'list';
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

    const detailElements = {
        image: document.getElementById('listing-detail-image'),
        title: document.getElementById('listing-detail-title'),
        type: document.getElementById('listing-detail-type'),
        description: document.getElementById('listing-detail-description'),
        tags: document.getElementById('listing-detail-tags'),
        price: document.getElementById('listing-detail-price'),
        link: document.getElementById('listing-detail-link'),
    };

    const buildTags = (service) => {
        const tags = [
            { icon: 'bi-geo-alt', label: service.address || 'Canal du Midi' },
            { icon: 'bi-bookmark', label: service.type || 'Adresse' },
        ];

        if (service.label) {
            tags.push({ icon: 'bi-award', label: service.label });
        }

        if (service.phone) {
            tags.push({ icon: 'bi-telephone', label: service.phone });
        }

        return tags;
    };

    const renderDetailMap = (service) => {
        const mapTarget = document.getElementById('listing-detail-map');
        if (!mapTarget) return;

        if (!detailMap) {
            detailMap = L.map('listing-detail-map', { zoomControl: true, attributionControl: true });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(detailMap);
        }

        if (detailMarker) {
            detailMarker.remove();
        }

        detailMarker = L.marker([service.lat, service.lng]).addTo(detailMap);
        detailMarker.bindPopup(`<strong>${service.title}</strong>`).openPopup();
        detailMap.setView([service.lat, service.lng], 14);
        setTimeout(() => detailMap.invalidateSize(), 120);
    };

    const openDetailModal = (serviceId) => {
        const service = Array.isArray(searchResults)
            ? searchResults.find((item) => String(item.id) === String(serviceId))
            : null;

        if (!service || !detailModal) return;

        detailElements.image.src = service.image || '';
        detailElements.image.alt = service.title || '';
        detailElements.title.textContent = service.title || '';
        detailElements.type.textContent = service.type || 'Adresse';
        detailElements.description.textContent = service.description || service.tag || 'Une adresse sélectionnée pour découvrir le Canal du Midi.';
        detailElements.price.textContent = service.price || '';
        detailElements.link.href = service.url || '#';

        detailElements.tags.innerHTML = '';
        buildTags(service).forEach((tag) => {
            const node = document.createElement('span');
            node.className = 'listing-detail-tag';
            node.innerHTML = `<i class="bi ${tag.icon}"></i>${tag.label}`;
            detailElements.tags.appendChild(node);
        });

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
        document.body.style.overflow = pageShell?.dataset.mobileView === 'filters' ? 'hidden' : '';
    };

    detailOpenButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openDetailModal(button.dataset.serviceId);
        });
    });

    detailCloseButtons.forEach((button) => {
        button.addEventListener('click', closeDetailModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDetailModal();
        }
    });


    if (highlightId && window.highlightMarker) {
        // Esperar un poco a que el mapa cargue
        setTimeout(() => {
            window.highlightMarker(highlightId);
            // Hacer scroll a la card
            const card = document.querySelector(`.explore-card[data-id="${highlightId}"]`);
            if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 1000);
    }
});

// Funciones para interactividad desde la lista
window.highlightMarker = (id) => {
    const markerDiv = document.getElementById(`marker-${id}`);
    if (markerDiv) {
        markerDiv.classList.add('is-active');
        markers[id].setZIndexOffset(1000);
    }
};

window.resetMarker = (id) => {
    const markerDiv = document.getElementById(`marker-${id}`);
    if (markerDiv) markerDiv.classList.remove('is-active');
};