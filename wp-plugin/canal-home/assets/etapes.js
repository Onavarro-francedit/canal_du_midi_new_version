/* /etapes/ « Quel parcours faire ? » (05/10): filtra los parcours por modo, duración y salida, y dibuja en el mapa el
   que está seleccionado. Sin salida: los parcours propuestos (HTML del servidor). Con salida: los calculados
   (CDM_ETAPES.routes = canal_parcours_all). Google Maps se carga al acercarse el mapa. */
(function () {
    var D = window.CDM_ETAPES, ask = document.querySelector('.etapes-ask'), list = document.getElementById('etapes-list');
    if (!D || !ask || !list) return;
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; }); };
    var LABEL = { bateau: 'en bateau', velo: 'à vélo', pied: 'à pied', jour: '1 journée', weekend: 'un week-end', semaine: 'une semaine' };
    var MAX = 6;
    var state = { mode: 'bateau', duree: 'semaine', depart: '' };
    var title = document.getElementById('etapes-props-title'), empty = list.querySelector('.etapes-empty');
    var map, layers = [], trace = [], selected = null;

    function card(r, from, to) {
        var E = D.etapes, a = E[from], b = E[to], lo = Math.min(a.pk, b.pk), hi = Math.max(a.pk, b.pk);
        var via = E.filter(function (e) { return e.pk > lo + 0.5 && e.pk < hi - 0.5; });
        if (a.pk > b.pk) via.reverse();
        var url = D.calcul + (D.calcul.indexOf('?') < 0 ? '?' : '&') + 'de=' + encodeURIComponent(a.calcul) + '&a=' + encodeURIComponent(b.calcul);
        return '<article class="etapes-prop is-computed" data-a="' + from + '" data-b="' + to + '">'
            + '<img src="' + esc(a.img) + '" alt="" loading="lazy" width="384" height="256">'
            + '<div class="etapes-prop-body"><h3>' + esc(a.name) + ' → ' + esc(b.name) + '</h3>'
            + '<ul class="etapes-chips">' + r.chips.map(function (c, i) { return '<li' + (i === r.chips.length - 1 ? ' class="is-key"' : '') + '>' + esc(c) + '</li>'; }).join('') + '</ul>'
            + (via.length ? '<p><b>Vous passez par :</b> ' + via.map(function (e) { return '<a href="' + esc(e.url) + '">' + esc(e.name) + '</a>'; }).join(' · ') + '</p>' : '')
            + '<div class="etapes-prop-actions"><a class="etapes-btn etapes-btn--primary" href="' + esc(url) + '">Voir le détail</a>'
            + (a.labels[state.mode] ? '<a class="etapes-btn" href="' + esc(a.links[state.mode]) + '">' + esc(a.labels[state.mode]) + '</a>' : '') + '</div>'
            + '</div></article>';
    }

    function apply() {
        list.querySelectorAll('.is-computed').forEach(function (el) { el.remove(); });
        var shown = [];
        if (state.depart === '') {
            list.querySelectorAll('.etapes-prop').forEach(function (el) {
                var on = el.dataset.mode === state.mode && el.dataset.duree === state.duree;
                el.hidden = !on;
                if (on) shown.push(el);
            });
        } else {
            list.querySelectorAll('.etapes-prop').forEach(function (el) { el.hidden = true; });
            var d = +state.depart;
            var rs = D.routes.filter(function (r) { return r.mode === state.mode && r.duree === state.duree && (r.a === d || r.b === d); })
                .sort(function (x, y) { return x.km - y.km; }).slice(0, MAX);
            empty.insertAdjacentHTML('beforebegin', rs.map(function (r) { return card(r, d, r.a === d ? r.b : r.a); }).join(''));
            shown = [].slice.call(list.querySelectorAll('.is-computed'));
        }
        empty.hidden = shown.length > 0;
        var from = state.depart === '' ? '' : ' au départ de ' + D.etapes[+state.depart].name;
        title.textContent = shown.length + ' parcours ' + LABEL[state.mode] + ' pour ' + LABEL[state.duree] + from;
        select(shown[0] || null);
    }

    function select(el) {
        list.querySelectorAll('.etapes-prop.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
        selected = el;
        if (el) el.classList.add('is-selected');
        draw();
    }

    // ---- Mapa: el tramo del parcours seleccionado sobre el canal, con sus etapas ----
    function atPk(pk) {
        for (var i = 1; i < trace.length; i++) {
            if (trace[i][2] >= pk) {
                var p = trace[i - 1], q = trace[i], t = q[2] > p[2] ? (pk - p[2]) / (q[2] - p[2]) : 0;
                return { lat: p[0] + t * (q[0] - p[0]), lng: p[1] + t * (q[1] - p[1]) };
            }
        }
        return { lat: trace[trace.length - 1][0], lng: trace[trace.length - 1][1] };
    }
    function draw() {
        if (!map) return;
        layers.forEach(function (l) { l.setMap(null); });
        layers = [];
        if (!selected) return;
        var a = D.etapes[+selected.dataset.a], b = D.etapes[+selected.dataset.b];
        var lo = Math.min(a.pk, b.pk), hi = Math.max(a.pk, b.pk);
        var seg = [atPk(lo)].concat(trace.filter(function (p) { return p[2] > lo && p[2] < hi; }).map(function (p) { return { lat: p[0], lng: p[1] }; }), [atPk(hi)]);
        layers.push(new google.maps.Polyline({ map: map, path: seg, strokeColor: '#fff', strokeWeight: 11, zIndex: 2 }));
        layers.push(new google.maps.Polyline({ map: map, path: seg, strokeColor: '#6a63d9', strokeWeight: 6, zIndex: 3 }));
        D.etapes.forEach(function (e) {
            if (e.pk < lo - 0.01 || e.pk > hi + 0.01) return;
            var end = e === a || e === b;
            var m = new google.maps.Marker({ map: map, position: atPk(e.pk), title: e.name, zIndex: end ? 5 : 4,
                label: end ? { text: e === a ? 'A' : 'B', color: '#fff', fontWeight: '800', fontSize: '12px' } : null,
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: end ? 12 : 6, fillColor: end ? (e === a ? '#13606b' : '#4f48b7') : '#fff', fillOpacity: 1, strokeColor: end ? '#fff' : '#4f48b7', strokeWeight: end ? 3 : 2 } });
            m.addListener('click', function () { location.href = e.url; });
            layers.push(m);
        });
        var bounds = new google.maps.LatLngBounds();
        seg.forEach(function (p) { bounds.extend(p); });
        map.fitBounds(bounds, 48);
    }
    window.cdmEtapesInitMap = function () {
        map = new google.maps.Map(document.getElementById('etapes-map'), {
            center: { lat: 43.33, lng: 2.4 }, zoom: 8, mapTypeControl: false, streetViewControl: false, fullscreenControl: false, clickableIcons: false,
            styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }, { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#bfe6ec' }] }, { featureType: 'landscape', stylers: [{ color: '#f4f2fa' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] }, { featureType: 'road', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }]
        });
        new google.maps.Polyline({ map: map, path: trace.map(function (p) { return { lat: p[0], lng: p[1] }; }), strokeColor: '#c9c4e6', strokeWeight: 4, zIndex: 1 });
        draw();
    };
    function loadMap() {
        fetch(D.traceUrl).then(function (r) { return r.json(); }).then(function (t) {
            trace = t;
            if (window.google && window.google.maps) return window.cdmEtapesInitMap();
            var s = document.createElement('script');
            s.src = window.CDM_ETAPES_MAPS + (window.CDM_ETAPES_MAPS.indexOf('?') < 0 ? '?' : '&') + 'callback=cdmEtapesInitMap';
            s.async = true;
            document.head.appendChild(s);
        }).catch(function () { document.getElementById('etapes-map-box').classList.add('is-unavailable'); });
    }

    // ---- Eventos ----
    ask.hidden = false;
    ask.addEventListener('click', function (e) {
        var b = e.target.closest('.etapes-seg button');
        if (!b) return;
        b.parentNode.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
        state[b.parentNode.dataset.q] = b.dataset.v;
        apply();
    });
    document.getElementById('etapes-depart').addEventListener('change', function (e) { state.depart = e.target.value; apply(); });
    // Tocar una tarjeta (fuera de sus enlaces) la dibuja en el mapa.
    list.addEventListener('click', function (e) {
        var c = e.target.closest('.etapes-prop');
        if (c && !e.target.closest('a')) select(c);
    });
    apply();

    var box = document.getElementById('etapes-map-box');
    if (window.CDM_ETAPES_MAPS) {
        box.classList.remove('is-unavailable');
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (es) {
                if (es.some(function (x) { return x.isIntersecting; })) { io.disconnect(); loadMap(); }
            }, { rootMargin: '300px' });
            io.observe(box);
        } else {
            loadMap();
        }
    }
})();
