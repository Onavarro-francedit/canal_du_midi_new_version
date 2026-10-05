/* Mapa de /etapes/ (05/10): un punto por etapa sobre el trazado del canal; el popup de la primera se abre al cargar
   y cada clic abre el de su etapa. Datos: CDM_ETAPES (etape-route.php). Google Maps se carga al acercarse el mapa. */
(function () {
    var D = window.CDM_ETAPES, box = document.getElementById('etapes-map-box');
    if (!D || !box || !window.CDM_ETAPES_MAPS) return;
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; }); };
    var map, iw, markers = [], current = -1, trace = [];

    function icon(on, robine) {
        return { path: google.maps.SymbolPath.CIRCLE, scale: on ? 11 : 8, fillColor: on ? '#2BB6C4' : (robine ? '#8a7ee8' : '#6a63d9'),
            fillOpacity: 1, strokeColor: '#fff', strokeWeight: 3 };
    }

    function popup(p, i) {
        var next = p.next ? '<p class="etapes-iw-next"><b>Jusqu’à ' + esc(p.next.name) + ' :</b> ' + esc(p.next.km) + ' · ' + esc(p.next.locks)
            + ' · ' + esc(p.next.boat) + ' en bateau · ' + esc(p.next.bike) + ' à vélo</p>' : '';
        return '<div class="etapes-iw">'
            + '<img src="' + esc(p.img) + '" alt="" width="280" height="140">'
            + '<div class="etapes-iw-body">'
            + '<p class="etapes-iw-meta">' + esc(p.canal === 'robine' ? 'Canal de la Robine' : [p.pk, p.from].filter(Boolean).join(' · ')) + '</p>'
            + '<h3>' + esc(p.name) + '</h3>'
            + (p.offer.length ? '<ul class="etapes-iw-offer">' + p.offer.map(function (o) { return '<li>' + esc(o) + '</li>'; }).join('') + '</ul>' : '')
            + (p.voir.length ? '<p class="etapes-iw-voir"><b>À voir :</b> ' + p.voir.map(esc).join(', ') + '</p>' : '')
            + next
            + '<div class="etapes-iw-actions"><a class="etapes-btn etapes-btn--primary" href="' + esc(p.url) + '">Découvrir l’étape</a>'
            + (D.points[i + 1] && D.points[i + 1].canal === p.canal ? '<button type="button" class="etapes-btn" data-etape-next="' + (i + 1) + '">Étape suivante →</button>' : '')
            + '</div></div></div>';
    }

    function open(i, pan) {
        if (current >= 0) markers[current].setIcon(icon(false, D.points[current].canal === 'robine'));
        current = i;
        markers[i].setIcon(icon(true));
        markers[i].setZIndex(10 + i);
        var node = document.createElement('div');
        node.innerHTML = popup(D.points[i], i);
        // Google Maps no deja subir los clics del popup al documento: el botón se conecta aquí.
        var nb = node.querySelector('[data-etape-next]');
        if (nb) nb.addEventListener('click', function () { open(i + 1, true); });
        iw.setContent(node.firstChild);
        iw.open({ map: map, anchor: markers[i], shouldFocus: false });
        if (pan) map.panTo(markers[i].getPosition());
        document.querySelectorAll('.etapes-chip').forEach(function (c, k) { c.setAttribute('aria-current', k === i ? 'true' : 'false'); });
    }

    window.cdmEtapesInitMap = function () {
        map = new google.maps.Map(document.getElementById('etapes-map'), {
            center: { lat: 43.33, lng: 2.4 }, zoom: 8, mapTypeControl: false, streetViewControl: false, fullscreenControl: false, clickableIcons: false,
            styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }, { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#bfe6ec' }] }, { featureType: 'landscape', stylers: [{ color: '#f4f2fa' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] }, { featureType: 'road', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }]
        });
        iw = new google.maps.InfoWindow({ maxWidth: 300 });
        if (trace.length) {
            new google.maps.Polyline({ map: map, path: trace.map(function (p) { return { lat: p[0], lng: p[1] }; }), strokeColor: '#6a63d9', strokeOpacity: .55, strokeWeight: 4 });
        }
        var bounds = new google.maps.LatLngBounds();
        D.points.forEach(function (p, i) {
            var m = new google.maps.Marker({ map: map, position: { lat: p.lat, lng: p.lng }, title: p.name, icon: icon(false, p.canal === 'robine'), cursor: 'pointer', zIndex: 5 });
            m.addListener('click', function () { open(i, false); });
            markers.push(m);
            bounds.extend(m.getPosition());
        });
        // Los popups se abren por encima del punto: se reserva arriba su altura para que el canal no quede cortado.
        var h = document.getElementById('etapes-map').offsetHeight;
        map.fitBounds(bounds, { top: Math.min(330, Math.round(h * .6)), right: 30, bottom: 24, left: 30 });
        google.maps.event.addListenerOnce(map, 'idle', function () { open(0, false); });
        box.classList.add('is-ready');
    };

    // Chips de la lista (sin JS, la chip es un enlace a la etapa).
    document.addEventListener('click', function (e) {
        var c = e.target.closest('.etapes-chip');
        if (c && map) {
            e.preventDefault();
            open(+c.dataset.i, true);
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    function load() {
        fetch(D.traceUrl).then(function (r) { return r.json(); }).catch(function () { return []; }).then(function (t) {
            trace = t || [];
            if (window.google && window.google.maps) return window.cdmEtapesInitMap();
            var s = document.createElement('script');
            s.src = window.CDM_ETAPES_MAPS + (window.CDM_ETAPES_MAPS.indexOf('?') < 0 ? '?' : '&') + 'callback=cdmEtapesInitMap';
            s.async = true;
            document.head.appendChild(s);
        });
    }
    box.classList.remove('is-unavailable');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (es) {
            if (es.some(function (x) { return x.isIntersecting; })) { io.disconnect(); load(); }
        }, { rootMargin: '300px' });
        io.observe(box);
    } else {
        load();
    }
})();
