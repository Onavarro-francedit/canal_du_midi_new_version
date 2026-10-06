/* Calcul de distance 2026 (TASK-057): recalcula en vivo con las mismas reglas que includes/calcul-core.php
   (datos de CDM_CALCUL) y carga el mapa en diferido: escritorio tras `load`, móvil solo al pulsar (como la carte). */
(function () {
    'use strict';
    var D = window.CDM_CALCUL;
    if (!D) return;
    var M = D.model;
    var $ = function (id) { return document.getElementById(id); };
    var fold = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); };
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; }); };

    var places = new Map();
    D.towns.forEach(function (t) { places.set(t.name, t.pk); });
    D.locks.forEach(function (l) { places.set(l.name, l.pk); });
    var find = function (q) {
        var f = fold(q);
        for (var name of places.keys()) if (fold(name) === f) return name;
        return null;
    };
    var town = function (name) { return D.towns.find(function (t) { return t.name === name; }) || null; };

    function locksBetween(a, b) {
        var lo = Math.min(a, b), hi = Math.max(a, b);
        var ls = D.locks.filter(function (l) { return l.pk > lo + 0.01 && l.pk <= hi + 0.01; });
        return a > b ? ls.reverse() : ls;
    }
    function compute(a, b) {
        var km = Math.abs(b - a), ls = locksBetween(a, b);
        var sas = ls.reduce(function (s, l) { return s + l.sas; }, 0);
        return { km: km, sites: ls.length, sas: sas, locks: ls, lo: Math.min(a, b), hi: Math.max(a, b),
            boat: km / M.boatKmh + sas * M.minPerSas / 60, bike: km / M.bikeKmh, walk: km / M.walkKmh };
    }
    function duration(h) {
        var min = Math.round(h * 6) * 10, H = Math.floor(min / 60), m = min % 60;
        return H ? H + ' h' + (m ? ' ' + String(m).padStart(2, '0') : '') : m + ' min';
    }
    var daysBoat = function (h) { return h <= M.boatHoursDay ? 'dans la journée' : '≈ ' + Math.ceil(h / M.boatHoursDay) + ' jours de navigation'; };
    var daysBike = function (km) { return km <= M.bikeKmOneDay ? 'dans la journée' : '≈ ' + Math.ceil(km / M.bikeKmDay) + ' jours'; };
    var daysWalk = function (km) { var d = Math.ceil(km / M.walkKmDay); return d <= 1 ? 'dans la journée' : '≈ ' + d + ' jours'; };
    var locksLabel = function (n, sas) { return n ? n + ' écluse' + (n > 1 ? 's' : '') + (sas > n ? ' · ' + sas + ' sas' : '') : 'Aucune écluse'; };
    var lower = function (n) { return n.replace(/^Écluses? /, function (m) { return m.toLowerCase(); }); };
    function de(n) {
        var t = town(n);
        if (t && t.de) return t.de;
        if (/^Écluses /.test(n)) return 'des ' + lower(n);
        if (/^Écluse /.test(n)) return "de l'" + lower(n);
        return (/^[AEIOUYÉÈ]/.test(n) ? "d'" : 'de ') + n;
    }
    function a_(n) {
        var t = town(n);
        if (t && t.a) return t.a;
        if (/^Écluses /.test(n)) return 'aux ' + lower(n);
        if (/^Écluse /.test(n)) return "à l'" + lower(n);
        return 'à ' + n;
    }
    function arrival(to) {
        var t = town(to);
        if (t) return { title: 'Que faire ' + a_(to) + ' ?', search: t.search };
        var pk = places.get(to), near = D.towns.reduce(function (b, c) { return Math.abs(c.pk - pk) < Math.abs(b.pk - pk) ? c : b; });
        return { title: 'Que faire près ' + de(to) + ' ?', search: near.search };
    }
    var pkLabel = function (pk) { return 'PK ' + pk.toFixed(1).replace('.', ','); };

    // ---- Mapa: trazado [lat,lng,PK] + Google Maps (URL del tema con la clave del sitio) ----
    var TRACE = null, map = null, layers = [], iw = null, requested = false;
    function atPk(pk) {
        for (var i = 1; i < TRACE.length; i++) {
            var a = TRACE[i - 1], b = TRACE[i];
            if (pk <= b[2]) { var t = (pk - a[2]) / ((b[2] - a[2]) || 1); return { lat: a[0] + (b[0] - a[0]) * t, lng: a[1] + (b[1] - a[1]) * t }; }
        }
        var z = TRACE[TRACE.length - 1];
        return { lat: z[0], lng: z[1] };
    }
    function drawMap(fa, fb, r) {
        if (!map || !TRACE) return;
        layers.forEach(function (l) { l.setMap(null); });
        layers = [];
        var seg = [atPk(r.lo)].concat(TRACE.filter(function (p) { return p[2] > r.lo && p[2] < r.hi; }).map(function (p) { return { lat: p[0], lng: p[1] }; }), [atPk(r.hi)]);
        layers.push(new google.maps.Polyline({ map: map, path: seg, strokeColor: '#ffffff', strokeWeight: 11, strokeOpacity: 1, zIndex: 2 }));
        layers.push(new google.maps.Polyline({ map: map, path: seg, strokeColor: '#6a63d9', strokeWeight: 6, strokeOpacity: 1, zIndex: 3 }));
        r.locks.forEach(function (l) {
            var m = new google.maps.Marker({ map: map, position: atPk(l.pk), title: l.name, zIndex: 4, cursor: 'pointer',
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: l.sas > 1 ? 6 : 5, fillColor: '#fff', fillOpacity: 1, strokeColor: '#4f48b7', strokeWeight: 2 } });
            m.addListener('click', function () {
                iw.setContent('<div class="calc-iw">' + (l.photo ? '<img src="' + esc(l.photo) + '" alt="" width="232" height="116">' : '') + '<strong>' + esc(l.name) + '</strong>' + pkLabel(l.pk)
                    + (l.sas > 1 ? ' · ' + l.sas + ' sas' : '') + (l.url ? '<br><a href="' + esc(l.url) + '">Voir la fiche →</a>' : '') + '</div>');
                iw.open({ map: map, anchor: m });
            });
            layers.push(m);
        });
        [[places.get(fa), '#13606b', 'A', fa], [places.get(fb), '#4f48b7', 'B', fb]].forEach(function (p) {
            layers.push(new google.maps.Marker({ map: map, position: atPk(p[0]), title: p[3], zIndex: 5, label: { text: p[2], color: '#fff', fontWeight: '800', fontSize: '12px' },
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 12, fillColor: p[1], fillOpacity: 1, strokeColor: '#fff', strokeWeight: 3 } }));
        });
        var bounds = new google.maps.LatLngBounds();
        seg.forEach(function (p) { bounds.extend(p); });
        map.fitBounds(bounds, 48);
    }
    window.cdmCalculInitMap = function () {
        map = new google.maps.Map($('calc-map'), { center: { lat: 43.33, lng: 2.4 }, zoom: 8, mapTypeControl: false, streetViewControl: false, fullscreenControl: false, clickableIcons: false,
            styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }, { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#bfe6ec' }] }, { featureType: 'landscape', stylers: [{ color: '#f4f2fa' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] }, { featureType: 'road', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }] });
        iw = new google.maps.InfoWindow({ minWidth: 248, maxWidth: 248 });
        new google.maps.Polyline({ map: map, path: TRACE.map(function (p) { return { lat: p[0], lng: p[1] }; }), strokeColor: '#c9c4e6', strokeWeight: 4, strokeOpacity: 1, zIndex: 1 });
        update(false);
    };
    function loadMap() {
        if (requested) return;
        requested = true;
        $('calc-map-box').classList.remove('is-lazy');
        fetch(D.traceUrl).then(function (r) { return r.json(); }).then(function (t) {
            TRACE = t;
            if (window.google && window.google.maps) return window.cdmCalculInitMap();
            var s = document.createElement('script');
            s.src = window.CDM_CALCUL_MAPS + (window.CDM_CALCUL_MAPS.indexOf('?') < 0 ? '?' : '&') + 'callback=cdmCalculInitMap';
            s.async = true;
            document.head.appendChild(s);
        }).catch(function () { $('calc-map-box').classList.add('is-unavailable'); });
    }

    // ---- Resultado ----
    function update(push) {
        var fa = find($('calc-from').value), fb = find($('calc-to').value);
        if (!fa || !fb) return;
        var r = compute(places.get(fa), places.get(fb));
        $('calc-km').innerHTML = Math.round(r.km) + '<small>km</small>';
        $('calc-locks').textContent = locksLabel(r.sites, r.sas);
        $('calc-sentence').textContent = de(fa).charAt(0).toUpperCase() + de(fa).slice(1) + ' ' + a_(fb) + ' : ' + Math.round(r.km) + ' km par le Canal du Midi'
            + (r.sites ? ' et ' + r.sites + ' écluse' + (r.sites > 1 ? 's' : '') + ' à franchir' : '') + '.';
        $('calc-t-boat').textContent = duration(r.boat); $('calc-d-boat').textContent = daysBoat(r.boat);
        $('calc-t-bike').textContent = duration(r.bike); $('calc-d-bike').textContent = daysBike(r.km);
        $('calc-t-walk').textContent = duration(r.walk); $('calc-d-walk').textContent = daysWalk(r.km);
        var list = $('calc-locks-list');
        list.hidden = !r.sites;
        $('calc-locks-title').innerHTML = 'Les ' + r.sites + ' écluse' + (r.sites > 1 ? 's' : '') + " du trajet <small>dans l'ordre de passage</small>";
        $('calc-locks-ol').innerHTML = r.locks.map(function (l) {
            return '<li>' + (l.photo ? '<img class="calc-thumb" src="' + esc(l.photo) + '" alt="" width="64" height="46" loading="lazy">' : '<span class="calc-thumb calc-thumb--none" aria-hidden="true">◦</span>')
                + '<span class="calc-lock-name">' + (l.url ? '<a href="' + esc(l.url) + '">' + esc(l.name) + '</a>' : esc(l.name))
                + '<span class="calc-pk">' + pkLabel(l.pk) + (l.sas > 1 ? ' <span class="calc-sas">· ' + l.sas + ' sas</span>' : '') + '</span></span></li>';
        }).join('');
        var arr = arrival(fb);
        $('calc-arrival-title').textContent = arr.title;
        document.querySelectorAll('#calc-photos a').forEach(function (a) {
            var u = new URL(D.carteUrl, location.href);
            u.searchParams.set('type', a.dataset.type);
            u.searchParams.set('search_location', arr.search);
            a.href = u.toString();
            a.hidden = !((D.carteCounts[arr.search] || {})[a.dataset.type] > 0);
        });
        if (push !== false) {
            var u = new URL(location.href);
            u.searchParams.set('de', fa);
            u.searchParams.set('a', fb);
            history.replaceState(null, '', u.toString());
        }
        drawMap(fa, fb, r);
        clearTimeout(sendTimer);
        sendTimer = setTimeout(function () {
            var key = fa + '→' + fb;
            if (key !== lastSent && typeof window.gtag === 'function') {
                lastSent = key;
                window.gtag('event', 'calcul_trajet', { de: fa, a: fb, km: Math.round(r.km) });
            }
        }, 2000);
    }
    var sendTimer = 0, lastSent = '';

    ['calc-from', 'calc-to'].forEach(function (id) {
        $(id).addEventListener('input', update);
        $(id).addEventListener('focus', function (e) { e.target.select(); });
        $(id).addEventListener('change', function (e) { var n = find(e.target.value); if (n) e.target.value = n; });
    });
    $('calc-form').addEventListener('submit', function (e) { e.preventDefault(); update(); });
    $('calc-swap').addEventListener('click', function () { var a = $('calc-from').value; $('calc-from').value = $('calc-to').value; $('calc-to').value = a; update(); });
    $('calc-share').addEventListener('click', function () {
        if (navigator.clipboard) navigator.clipboard.writeText(location.href);
        $('calc-share').querySelector('span').textContent = 'Lien copié';
    });
    $('calc-matrix').addEventListener('click', function (e) {
        var a = e.target.closest('a[data-de]');
        if (!a) return;
        e.preventDefault();
        $('calc-from').value = a.dataset.de; $('calc-to').value = a.dataset.a;
        update();
        $('calc-from').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // El hueco del mapa viene reservado en el HTML (is-lazy: en móvil, botón « voir la carte »; en escritorio no hace nada).
    // Sin clave de Maps en el tema, se oculta (is-unavailable) y el resto funciona.
    if (!window.CDM_CALCUL_MAPS) {
        $('calc-map-box').classList.add('is-unavailable');
    } else {
        if (matchMedia('(max-width: 600px)').matches) {
            $('calc-map-open').addEventListener('click', loadMap);
        } else if (document.readyState === 'complete') {
            setTimeout(loadMap, 300);
        } else {
            addEventListener('load', function () { setTimeout(loadMap, 300); });
        }
    }
})();
