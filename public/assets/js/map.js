/**
 * Módulo: map.js
 * Funcionalidad: Inicialización de mapa interactivo Leaflet para la ficha de cliente.
 */

document.addEventListener('DOMContentLoaded', () => {
    const mapContainer = document.getElementById('map');

    if (mapContainer && typeof L !== 'undefined') {
        // 1. Obtener coordenadas de los atributos data
        const lat = parseFloat(mapContainer.dataset.lat) || 43.604; // Toulouse por defecto
        const lng = parseFloat(mapContainer.dataset.lng) || 1.444;
        const title = mapContainer.dataset.title || "Établissement";

        // 2. Inicializar el mapa
        const map = L.map('map', {
            scrollWheelZoom: false, // Evitar zoom accidental al hacer scroll
            zoomControl: true
        }).setView([lat, lng], 14);

        // 3. Capa de mapa Estilo Premium (CartoDB Voyager)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // 4. Icono Personalizado (Usando un punto de color corporativo)
        const customIcon = L.divIcon({
            className: 'custom-map-marker',
            html: `<div style="background-color: #6a63d9; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });

        // 5. Añadir Marcador
        L.marker([lat, lng], { icon: customIcon })
            .addTo(map)
            .bindPopup(`<strong>${title}</strong><br>Canal du Midi Experience`)
            .openPopup();
    }
});