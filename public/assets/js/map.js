/**
 * Módulo: map.js (Versión On-Demand)
 */
document.addEventListener('DOMContentLoaded', () => {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || typeof L === 'undefined') return;

    const sLat = parseFloat(mapContainer.dataset.lat);
    const sLng = parseFloat(mapContainer.dataset.lng);
    const sTitle = mapContainer.dataset.title;

    const map = L.map('map', { scrollWheelZoom: false }).setView([sLat, sLng], 14);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png').addTo(map);

    // Marcador fijo del Hotel
    const mainIcon = L.divIcon({
        className: 'main-marker',
        html: `<div style="background-color: #6a63d9; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>`,
        iconSize: [20, 20]
    });
    L.marker([sLat, sLng], { icon: mainIcon, zIndexOffset: 1000 }).addTo(map).bindPopup(sTitle);

    // --- Lógica Dinámica de POIs ---
    let activeMarker = null;

    document.querySelectorAll('.poi-hover-trigger').forEach(trigger => {
        trigger.addEventListener('mouseenter', () => {
            const lat = parseFloat(trigger.dataset.lat);
            const lng = parseFloat(trigger.dataset.lng);
            const name = trigger.dataset.name;

            // 1. Si hay un marcador anterior, lo quitamos
            if (activeMarker) map.removeLayer(activeMarker);

            // 2. Creamos el nuevo marcador
            const poiIcon = L.divIcon({
                className: 'poi-active-marker',
                html: `<div class="poi-pulse"></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            activeMarker = L.marker([lat, lng], { icon: poiIcon }).addTo(map);
            activeMarker.bindPopup(`<strong>${name}</strong>`).openPopup();

            // 3. Zoom suave hacia el punto
            map.flyTo([lat, lng], 15, { duration: 1 });
        });

        trigger.addEventListener('mouseleave', () => {
            // Opcional: Volver al hotel al salir
            if (activeMarker) {
                map.removeLayer(activeMarker);
                activeMarker = null;
            }
            map.flyTo([sLat, sLng], 14, { duration: 1 });
        });
    });
});