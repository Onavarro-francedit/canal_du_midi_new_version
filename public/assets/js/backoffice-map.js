// public/assets/js/backoffice-map.js
// Carte avec repère déplaçable dans le modal "Adresse" du backoffice.
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('bo-address-map');
    const trigger = document.querySelector('[data-modal="modal-address"]');
    if (!container || !trigger || typeof google === 'undefined' || !google.maps) return;

    const latInput = document.getElementById('bo-lat');
    const lngInput = document.getElementById('bo-lng');
    let map = null;
    let marker = null;

    function setPosition(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
    }

    function initMap() {
        if (map) return;

        const lat = parseFloat(container.dataset.lat) || 43.6;
        const lng = parseFloat(container.dataset.lng) || 2.35;

        map = new google.maps.Map(container, {
            center: { lat, lng },
            zoom: 14,
        });

        marker = new google.maps.Marker({
            position: { lat, lng },
            map,
            draggable: true,
        });

        marker.addListener('dragend', () => {
            const pos = marker.getPosition();
            setPosition(pos.lat(), pos.lng());
        });

        map.addListener('click', (e) => {
            marker.setPosition(e.latLng);
            setPosition(e.latLng.lat(), e.latLng.lng());
        });
    }

    trigger.addEventListener('click', () => {
        // La carte Google Maps a besoin d'un conteneur visible pour se dimensionner correctement.
        setTimeout(() => {
            initMap();
            google.maps.event.trigger(map, 'resize');
            map.setCenter(marker.getPosition());
        }, 50);
    });
});
