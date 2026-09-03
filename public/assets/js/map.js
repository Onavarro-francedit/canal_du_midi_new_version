/**
 * Módulo: map.js (Versión On-Demand)
 * Motor: Google Maps JS API.
 */
document.addEventListener('DOMContentLoaded', () => {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || typeof google === 'undefined' || !google.maps) return;

    const sLat = parseFloat(mapContainer.dataset.lat);
    const sLng = parseFloat(mapContainer.dataset.lng);
    const sTitle = mapContainer.dataset.title;

    const map = new google.maps.Map(mapContainer, {
        center: { lat: sLat, lng: sLng },
        zoom: 14,
        scrollwheel: false,
    });

    const mainIcon = {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26"><circle cx="13" cy="13" r="10" fill="#6a63d9" stroke="white" stroke-width="3"/></svg>'
        ),
        scaledSize: new google.maps.Size(26, 26),
        anchor: new google.maps.Point(13, 13),
    };

    const mainMarker = new google.maps.Marker({
        position: { lat: sLat, lng: sLng },
        map,
        icon: mainIcon,
        zIndex: 1000,
        title: sTitle,
    });

    const mainInfoWindow = new google.maps.InfoWindow({ content: sTitle });
    mainMarker.addListener('click', () => mainInfoWindow.open({ map, anchor: mainMarker }));

    // --- Lógica Dinámica de POIs ---
    let activeMarker = null;

    const poiIcon = {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><circle cx="15" cy="15" r="10" fill="#ef4444" stroke="white" stroke-width="3"/></svg>'
        ),
        scaledSize: new google.maps.Size(30, 30),
        anchor: new google.maps.Point(15, 15),
    };

    document.querySelectorAll('.poi-hover-trigger').forEach(trigger => {
        trigger.addEventListener('mouseenter', () => {
            const lat = parseFloat(trigger.dataset.lat);
            const lng = parseFloat(trigger.dataset.lng);
            const name = trigger.dataset.name;

            // 1. Si hay un marcador anterior, lo quitamos
            if (activeMarker) activeMarker.setMap(null);

            // 2. Creamos el nuevo marcador
            activeMarker = new google.maps.Marker({
                position: { lat, lng },
                map,
                icon: poiIcon,
                animation: google.maps.Animation.BOUNCE,
            });

            const poiInfoWindow = new google.maps.InfoWindow({ content: '<strong>' + name + '</strong>' });
            poiInfoWindow.open({ map, anchor: activeMarker });

            // 3. Zoom suave hacia el punto
            map.panTo({ lat, lng });
            map.setZoom(15);
        });

        trigger.addEventListener('mouseleave', () => {
            // Opcional: Volver al hotel al salir
            if (activeMarker) {
                activeMarker.setMap(null);
                activeMarker = null;
            }
            map.panTo({ lat: sLat, lng: sLng });
            map.setZoom(14);
        });
    });
});
