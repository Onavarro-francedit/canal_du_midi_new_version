/* Planificateur 2026 (TASK-044) — composer, ideas, chat y llamadas REST. Datos: window.CDM_PLANNER. */
(() => {
  const C = window.CDM_PLANNER;
  const root = document.getElementById('cdm-planner');
  if (!C || !root) return;
  const $ = (s) => root.querySelector(s);
  const hero = $('#pl-hero'), chat = $('#pl-chat'), ideas = $('#pl-ideas'), wrap = $('#pl-wrap');
  const log = $('#pl-log'), form = $('#pl-composer'), input = $('#pl-input');
  const mainOrb = $('#pl-main-orb'), statusText = $('#pl-status');
  const catsEl = $('#pl-cats'), cardsEl = $('#pl-cards'), curCat = $('#pl-cur');
  const sendBtn = form.querySelector('.pl-send');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const ga = (name, params) => { if (typeof window.gtag === 'function') window.gtag('event', name, params || {}); };

  // Pantalla fija justo bajo la cabecera real (su alto cambia con la barra de admin y el ancho).
  const placeTop = () => {
    const h = document.querySelector('.cdm-header');
    root.style.setProperty('--planner-top', Math.max(0, Math.round(h ? h.getBoundingClientRect().bottom : 0)) + 'px');
  };
  placeTop();
  addEventListener('resize', placeTop);
  addEventListener('load', placeTop);

  const ICON = {
    boat: '<path d="M2 20c2 1 4 1 6 0s4-1 6 0 4 1 6 0"/><path d="M4 16l1.5-4h13L20 16"/><path d="M12 12V3l5 6h-5"/>',
    bike: '<circle cx="5.5" cy="17" r="3.5"/><circle cx="18.5" cy="17" r="3.5"/><path d="M15 6h2l1.5 11M5.5 17 9 9h6l-3.5 8M9 9 8 6H6"/>',
    family: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    food: '<path d="M3 2v7a3 3 0 0 0 6 0V2M6 2v20M18 15V2a4 4 0 0 0-4 4v6h4zM18 15v7"/>',
    love: '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3C14.7 3 13.5 3.5 12 5c-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7z"/>',
    arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>'
  };
  const svg = (k) => `<svg viewBox="0 0 24 24">${ICON[k] || ''}</svg>`;
  const THEMES = C.themes || [];

  /* ── Ideas por tema (inicio) ── */
  let current = -1, jumpTo = null, hoverCards = false, genToken = 0;
  THEMES.forEach((th, i) => {
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'pl-cat';
    b.innerHTML = svg(th.key);
    b.append(th.label);
    b.addEventListener('click', () => { if (i !== current) jumpTo = i; });
    catsEl.append(b);
  });
  cardsEl.addEventListener('mouseenter', () => (hoverCards = true));
  cardsEl.addEventListener('mouseleave', () => (hoverCards = false));

  async function showTheme(i) {
    current = i;
    const th = THEMES[i], token = ++genToken;
    [...catsEl.children].forEach((c, k) => c.classList.toggle('is-active', k === i));
    curCat.textContent = th.label.toLowerCase();
    mainOrb.classList.add('is-thinking');
    [...cardsEl.children].forEach((c) => c.classList.add('is-out'));
    await sleep(reduce ? 0 : 280);
    if (token !== genToken) return;
    cardsEl.innerHTML = '<div class="pl-skel"><i></i><i></i><i></i><i></i></div>'.repeat(3);
    await sleep(reduce ? 0 : 750);
    if (token !== genToken) return;
    cardsEl.innerHTML = '';
    th.cards.forEach((c, k) => {
      const el = document.createElement('button');
      el.type = 'button'; el.className = 'pl-card';
      el.style.animationDelay = (k * 110) + 'ms';
      el.innerHTML = `<div class="pl-card-top"><span class="pl-ico pl-t-${th.key}">${svg(th.key)}</span><span class="pl-go">${svg('arrow')}</span></div>
        <strong></strong><span class="pl-desc"></span><div class="pl-meta">${c.m.map(() => '<span></span>').join('')}</div>`;
      el.querySelector('strong').textContent = c.t;
      el.querySelector('.pl-desc').textContent = c.d;
      el.querySelectorAll('.pl-meta span').forEach((s, n) => (s.textContent = c.m[n]));
      el.addEventListener('click', () => send(`${c.t} : ${c.d}`));
      cardsEl.append(el);
    });
    setTimeout(() => { if (token === genToken) mainOrb.classList.remove('is-thinking'); }, 500);
  }

  /* ── Placeholder que se escribe y se borra ── */
  const paused = () => input.value.length > 0 || hero.hidden || hoverCards;
  async function waitWhilePaused() { while (paused() && jumpTo === null) await sleep(200); }
  async function typeText(txt) {
    for (let k = 1; k <= txt.length; k++) {
      if (jumpTo !== null) return false;
      await waitWhilePaused();
      input.placeholder = txt.slice(0, k) + '▍';
      await sleep(txt[k - 1] === ',' ? 220 : 28 + Math.random() * 55);
    }
    return true;
  }
  async function hold(txt, ms) {
    const end = Date.now() + ms; let on = true;
    while (Date.now() < end || paused()) {
      if (jumpTo !== null) return false;
      input.placeholder = txt + (on ? '▍' : ''); on = !on;
      await sleep(450);
    }
    return true;
  }
  async function eraseText(txt) {
    for (let k = txt.length; k >= 0; k--) {
      await waitWhilePaused();
      input.placeholder = txt.slice(0, k) + '▍';
      await sleep(jumpTo !== null ? 6 : 14 + Math.random() * 18);
    }
  }
  async function cycle() {
    if (!THEMES.length) return;
    let i = Math.floor(Math.random() * THEMES.length);
    const used = THEMES.map(() => -1);
    for (;;) {
      if (hero.hidden) { await sleep(400); continue; }
      const th = THEMES[i];
      used[i] = (used[i] + 1) % th.prompts.length;
      const txt = th.prompts[used[i]];
      showTheme(i);
      if (reduce) { input.placeholder = txt; await sleep(6000); }
      else {
        await sleep(350);
        if (await typeText(txt)) await hold(txt, 2600);
        await eraseText(input.placeholder.replace('▍', ''));
        await sleep(250);
      }
      if (jumpTo !== null) { i = jumpTo; jumpTo = null; }
      else { let n; do { n = Math.floor(Math.random() * THEMES.length); } while (n === i && THEMES.length > 1); i = n; }
    }
  }

  /* ── Chat ── */
  const el = (tag, cls, txt) => { const n = document.createElement(tag); if (cls) n.className = cls; if (txt != null) n.textContent = txt; return n; };
  const orb = () => $('#pl-orb-tpl').content.firstElementChild.cloneNode(true);
  const scroll = () => log.scrollTo({ top: log.scrollHeight, behavior: 'smooth' });
  const aiRow = (bubble) => { const row = el('div', 'pl-msg'); row.append(orb(), bubble); log.append(row); scroll(); };
  const addUser = (text) => { const row = el('div', 'pl-msg pl-msg--user'); row.append(el('div', 'pl-bu', text)); log.append(row); scroll(); };
  const addText = (text) => { const b = el('div', 'pl-ba'); b.append(el('div', null, text)); aiRow(b); return b; };
  function addTyping() {
    const row = el('div', 'pl-msg'); const o = orb(); o.classList.add('is-thinking');
    const t = el('div', 'pl-typing'); t.setAttribute('aria-label', "L'assistant écrit"); t.append(el('i'), el('i'), el('i'));
    row.append(o, t); log.append(row); scroll(); return row;
  }
  const setThinking = (on) => {
    root.querySelectorAll('.pl-orb').forEach((o) => o.classList.toggle('is-thinking', on));
    statusText.textContent = on ? 'Je compose votre séjour…' : 'En ligne';
  };
  const openChat = () => { hero.hidden = true; ideas.hidden = true; chat.hidden = false; wrap.classList.add('is-chatting'); };

  async function post(url, data) {
    try {
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(data) });
      const json = await res.json().catch(() => ({}));
      return { ok: res.ok, data: json };
    } catch (e) {
      return { ok: false, data: { message: "L'assistant est momentanément indisponible." } };
    }
  }

  const MODE = { bateau: 'En bateau', velo: 'À vélo', pied: 'À pied', voiture: 'En voiture' };
  const SPEED = { bateau: 6, velo: 12, pied: 4 }; // km/h para « environ … » entre etapas
  const CHIPS = ['Plus calme', 'Un jour de plus', 'Avec un bateau'];
  const mobile = matchMedia('(max-width: 860px)');
  const planEl = $('#pl-plan'), chipsEl = $('#pl-chips'), countEl = $('#pl-mbar-count');
  let history = [], plan = null, busy = false, askBtn = null;

  /* ── Vista plan: desde la primera propuesta, chat a la izquierda y plan a la derecha (hoja en móvil) ── */
  const setSplit = (on) => { root.classList.toggle('is-split', on); planEl.hidden = !on; if (!on) sheet(false); };
  const sheet = (on) => root.classList.toggle('is-sheet', on && mobile.matches);
  const countMsgs = () => { countEl.textContent = String(log.querySelectorAll('.pl-msg').length); };
  const duration = (km, mode) => {
    const v = SPEED[mode]; if (!v) return '';
    const min = Math.max(10, Math.round(km / v * 60 / 5) * 5);
    return ' · environ ' + (min < 60 ? min + ' min' : Math.floor(min / 60) + ' h' + (min % 60 ? ' ' + String(min % 60).padStart(2, '0') : ''));
  };
  const same = (a, b) => a && b && a.slug === b.slug && a.place === b.place && a.text === b.text;

  function renderPlan(p, prev) {
    planEl.textContent = '';
    const head = el('div', 'pl-plan-head');
    const info = el('div');
    const title = el('h2', null, p.title); title.id = 'pl-plan-title';
    const pills = el('div', 'pl-plan-pills');
    let total = 0;
    p.days.forEach((d, i) => { const n = p.days[i + 1]; if (n && d.km !== null && n.km !== null) total += Math.abs(n.km - d.km); });
    [MODE[p.mode], p.when, p.people, total ? total + ' km au total' : ''].forEach((t) => { if (t) pills.append(el('span', null, t)); });
    info.append(el('p', 'pl-eyebrow', 'Votre séjour'), title, pills);
    head.append(info);
    askBtn = null;
    if (p.providers.length) {
      const cta = el('div', 'pl-plan-cta');
      askBtn = el('button', null, 'Demander les disponibilités'); askBtn.type = 'button';
      askBtn.addEventListener('click', () => openRequest(plan));
      const n = p.providers.length;
      cta.append(askBtn, el('small', null, n + (n > 1 ? ' prestataires' : ' prestataire') + ' · réponse par e-mail'));
      head.append(cta);
    }
    const days = el('div', 'pl-days');
    p.days.forEach((d, i) => {
      const stop = el('div', 'pl-stop');
      if (prev && !same(d, prev.days[i])) stop.classList.add('is-changed');
      const when = el('div', 'pl-when'); when.append(el('b', null, d.label), el('em', null, d.km === null ? '' : 'km ' + d.km));
      const rail = el('div', 'pl-rail'); rail.append(el('span', 'pl-dot'));
      const card = el(d.url ? 'a' : 'div', 'pl-card-stop');
      if (d.url) { card.href = d.url; card.target = '_blank'; card.rel = 'noopener'; }
      if (d.image) {
        const img = el('img'); img.src = d.image; img.alt = d.name || d.place; img.loading = 'lazy'; img.decoding = 'async';
        if (d.image_srcset) { img.srcset = d.image_srcset; img.sizes = '(max-width: 860px) 100vw, 168px'; }
        card.append(img);
      } else card.classList.add('is-plain');
      const txt = el('div', 'pl-txt'); txt.append(el('h3', null, d.place), el('p', null, d.text));
      if (d.name) { const f = el('div', 'pl-fiche', d.name + ' '); if (d.cat) f.append(el('span', null, '· ' + d.cat)); f.append(' →'); txt.append(f); }
      card.append(txt);
      stop.append(when, rail, card);
      days.append(stop);
      const n = p.days[i + 1];
      if (n && d.km !== null && n.km !== null) days.append(el('div', 'pl-leg', '↓ ' + Math.abs(n.km - d.km) + ' km' + duration(Math.abs(n.km - d.km), p.mode)));
    });
    planEl.append(head, days);
  }

  function showChips(on) {
    chipsEl.textContent = '';
    chipsEl.hidden = !on;
    if (on) CHIPS.forEach((t) => { const b = el('button', null, t); b.type = 'button'; b.addEventListener('click', () => send(t)); chipsEl.append(b); });
  }

  function addPlan(reply, p) {
    if (p.days.length) {
      const prev = plan;
      plan = p;
      renderPlan(p, prev);
      setSplit(true);
      showChips(true);
      if (reply) addText(reply);
    } else {
      // La IA solo pregunta: se queda el chat; el plan anterior (si lo hay) no cambia.
      if (plan) plan = Object.assign({}, plan, { when: p.when || plan.when, people: p.people || plan.people, missing: p.missing });
      addText(reply || 'Pouvez-vous préciser votre envie ?');
    }
    countMsgs();
  }

  async function send(text) {
    const t = (text || '').trim();
    if (!t || busy) return;
    openChat();
    addUser(t);
    input.value = '';
    busy = true; sendBtn.disabled = true;
    const typing = addTyping();
    setThinking(true);
    const daysEl = planEl.querySelector('.pl-days');
    {
      history.push({ role: 'user', text: t });
      if (daysEl) daysEl.classList.add('is-updating');
      const r = await post(C.planUrl, { messages: history.slice(-8), plan });
      typing.remove();
      if (daysEl) daysEl.classList.remove('is-updating');
      if (r.ok) {
        history.push({ role: 'assistant', text: r.data.reply });
        addPlan(r.data.reply, r.data.plan);
        if (r.data.plan.days.length) ga('planner_plan', { days: r.data.plan.days.length });
        input.placeholder = plan ? 'Ajustez votre séjour…' : 'Répondez à l’assistant…';
      } else {
        history.pop();
        const b = addText(r.data.message || "L'assistant est momentanément indisponible.");
        if (r.data.error === 'unavailable' || r.data.error === 'daily') {
          const a = el('a', null, 'Explorer la carte →'); a.href = C.carteUrl; b.append(a);
        }
      }
    }
    countMsgs();
    busy = false; sendBtn.disabled = false;
    setThinking(false);
    input.focus();
  }

  function reset() {
    history = []; plan = null; busy = false; sendBtn.disabled = false;
    log.innerHTML = ''; planEl.textContent = ''; showChips(false); setSplit(false);
    chat.hidden = true; hero.hidden = false; ideas.hidden = false; wrap.classList.remove('is-chatting');
    setThinking(false); input.value = ''; input.placeholder = 'Décrivez votre séjour idéal…';
  }

  /* ── Modal « Demander les disponibilités »: resumen, mapa del recorrido y formulario (fuera del chat) ── */
  const MAPS_SRC = window.CDM_PLANNER_MAPS || '';
  let modal = null, mapsReady = null;
  const ICO = (d) => `<svg class="pl-i" viewBox="0 0 24 24">${d}</svg>`;
  function loadMaps() {
    if (window.google && window.google.maps) return Promise.resolve(true);
    if (!MAPS_SRC) return Promise.resolve(false);
    if (!mapsReady) mapsReady = new Promise((resolve) => {
      window.canalPlannerMapReady = () => resolve(true);
      const sc = document.createElement('script');
      sc.src = MAPS_SRC + (MAPS_SRC.indexOf('?') === -1 ? '?' : '&') + 'loading=async&callback=canalPlannerMapReady';
      sc.async = true; sc.onerror = () => resolve(false);
      setTimeout(() => resolve(false), 8000); // carga colgada: el mapa se oculta en vez de quedarse en gris
      window.gm_authFailure = () => root.ownerDocument.querySelectorAll('.pl-map').forEach((m) => { m.hidden = true; }); // clave rechazada
      document.head.appendChild(sc);
    });
    return mapsReady;
  }
  async function drawRoute(box, stops, list) {
    const pts = stops.filter((d) => typeof d.lat === 'number' && typeof d.lng === 'number');
    if (!pts.length || !(await loadMaps()) || !box.isConnected) { box.hidden = true; return; }
    const map = new google.maps.Map(box, { disableDefaultUI: true, zoomControl: true, gestureHandling: 'cooperative', clickableIcons: false });
    const bounds = new google.maps.LatLngBounds();
    const pin = (hot) => ({ path: google.maps.SymbolPath.CIRCLE, scale: hot ? 15 : 12, fillColor: hot ? '#0E1424' : '#6a63d9', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 3 });
    const markers = stops.map((d, i) => {
      if (typeof d.lat !== 'number') return null;
      const pos = { lat: d.lat, lng: d.lng }; bounds.extend(pos);
      return new google.maps.Marker({ position: pos, map, icon: pin(false), label: { text: String(i + 1), color: '#fff', fontWeight: '700', fontSize: '12px' }, title: d.name });
    });
    new google.maps.Polyline({ map, path: pts.map((d) => ({ lat: d.lat, lng: d.lng })), strokeColor: '#6a63d9', strokeOpacity: .85, strokeWeight: 4 });
    if (pts.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(13); } else map.fitBounds(bounds, 36);
    list.querySelectorAll('li').forEach((li) => {
      const m = markers[+li.dataset.i]; if (!m) return;
      li.addEventListener('mouseenter', () => { m.setIcon(pin(true)); m.setZIndex(1000); });
      li.addEventListener('mouseleave', () => { m.setIcon(pin(false)); m.setZIndex(null); });
    });
  }
  function field(id, label, value, opts) {
    const w = el('div', 'pl-field'); const l = el('label', null, label); l.htmlFor = id;
    if (opts && opts.hint) l.append(el('em', null, ' ' + opts.hint));
    const inp = el(opts && opts.area ? 'textarea' : 'input'); inp.id = id;
    if (!(opts && opts.area)) inp.type = (opts && opts.type) || 'text';
    inp.value = value || ''; if (opts && opts.ph) inp.placeholder = opts.ph;
    if (opts && opts.max) inp.maxLength = opts.max;
    if (opts && opts.auto) inp.autocomplete = opts.auto;
    w.append(l, inp); return [w, inp];
  }
  function closeRequest() {
    if (!modal) return;
    modal.remove(); modal = null; document.removeEventListener('keydown', onKey);
    [...document.body.children].forEach((n) => { if (n.dataset.plInert) { n.inert = false; delete n.dataset.plInert; } });
    if (askBtn) askBtn.focus();
  }
  const onKey = (e) => { if (e.key === 'Escape') closeRequest(); };
  function openRequest(p) {
    closeRequest();
    // Una parada por prestatario (su primer día en el plan), en el orden del recorrido.
    const stops = [];
    p.days.forEach((d) => { if (d.slug && p.providers.indexOf(d.slug) !== -1 && !stops.some((x) => x.slug === d.slug)) stops.push(d); });
    modal = el('div', 'pl-modal');
    const dlg = el('div', 'pl-dialog'); dlg.setAttribute('role', 'dialog'); dlg.setAttribute('aria-modal', 'true'); dlg.setAttribute('aria-labelledby', 'pl-dlg-title');
    const head = el('div', 'pl-dlg-head'); const hd = el('div');
    const h = el('h2', null, p.title); h.id = 'pl-dlg-title';
    const pills = el('div', 'pl-plan-pills');
    [MODE[p.mode], stops.length + (stops.length > 1 ? ' prestataires' : ' prestataire')].forEach((t) => pills.append(el('span', null, t)));
    hd.append(el('p', 'pl-eyebrow', 'Demande de disponibilités'), h, pills);
    const x = el('button', 'pl-dlg-x'); x.type = 'button'; x.setAttribute('aria-label', 'Fermer'); x.innerHTML = ICO('<path d="M6 6l12 12M18 6 6 18"/>');
    x.addEventListener('click', closeRequest);
    head.append(hd, x);
    const body = el('div', 'pl-dlg-body');
    const left = el('div', 'pl-dlg-left');
    const map = el('div', 'pl-map');
    const list = el('ol', 'pl-who-list');
    stops.forEach((d, i) => {
      const li = el('li'); li.dataset.i = String(i);
      const n = el('span', 'pl-n', String(i + 1));
      const im = el('img'); im.alt = ''; im.loading = 'lazy'; if (d.image) im.src = d.image; else im.hidden = true;
      const t = el('div'); t.append(el('b', null, d.name), el('span', null, [d.label, d.place, d.cat].filter(Boolean).join(' · ')));
      li.append(n, im, t); list.append(li);
    });
    left.append(map, el('p', 'pl-who-h', 'Votre demande sera envoyée à'), list);
    const form = el('form', 'pl-dlg-form'); form.noValidate = true;
    const two = el('div', 'pl-two');
    const [fw, iWhen] = field('pl-f-when', 'Dates', p.when, { ph: 'Ex. du 14 au 17 mai', max: 80 });
    const [pw, iPeople] = field('pl-f-people', 'Personnes', p.people, { ph: 'Ex. 2 adultes, 2 enfants', max: 80 });
    two.append(fw, pw);
    const [mw, iMail] = field('pl-f-mail', 'Votre e-mail', '', { type: 'email', ph: 'marie@exemple.fr', auto: 'email', max: 190 });
    const [gw, iMsg] = field('pl-f-msg', 'Un message pour les prestataires', '', { area: true, hint: '(facultatif)', ph: 'Ex. nous voyageons avec un chien, vélos enfants de 8 et 11 ans…', max: 500 });
    const hp = el('input', 'pl-hp'); hp.type = 'text'; hp.name = 'website'; hp.tabIndex = -1; hp.autocomplete = 'off'; hp.setAttribute('aria-hidden', 'true');
    const err = el('p', 'pl-dlg-err'); err.setAttribute('role', 'alert');
    form.append(two, mw, gw, hp, err, el('p', 'pl-legal', "Vous recevrez d'abord un e-mail pour confirmer : rien n'est envoyé aux prestataires avant. Votre adresse ne leur est transmise qu'après confirmation et est conservée 12 mois."));
    body.append(left, form);
    const foot = el('div', 'pl-dlg-foot');
    const go = el('button', 'pl-dlg-send', 'Envoyer ma demande'); go.type = 'submit'; go.setAttribute('form', 'pl-dlg-form'); form.id = 'pl-dlg-form';
    foot.append(el('small', null, 'Les prestataires vous répondent directement par e-mail.'), go);
    dlg.append(head, body, foot); modal.append(dlg);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeRequest(); });
    document.addEventListener('keydown', onKey);
    document.body.append(modal);
    // Foco retenido en el modal: el resto de la página no recibe Tab ni clics mientras está abierto.
    [...document.body.children].forEach((n) => { if (n !== modal && !n.inert && n.tagName !== 'SCRIPT') { n.inert = true; n.dataset.plInert = '1'; } });
    drawRoute(map, stops, list);
    if (!mobile.matches) setTimeout(() => (iWhen.value ? iMail : iWhen).focus(), 250); // en móvil el teclado taparía el resumen
    else if (document.activeElement) document.activeElement.blur(); // el composer del chat no debe dejar el teclado abierto
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      err.textContent = '';
      if (!iWhen.value.trim() || !iPeople.value.trim()) { err.textContent = 'Indiquez vos dates et le nombre de personnes.'; (iWhen.value.trim() ? iPeople : iWhen).focus(); return; }
      if (!iMail.checkValidity() || !iMail.value.trim()) { err.textContent = 'Il me faut une adresse e-mail valide, par exemple marie@exemple.fr.'; iMail.focus(); return; }
      go.disabled = true; go.textContent = 'Envoi…';
      const r = await post(C.requestUrl, { plan: p, email: iMail.value.trim(), when: iWhen.value, people: iPeople.value, message: iMsg.value, website: hp.value });
      if (!modal) return;
      if (!r.ok) { err.textContent = r.data.message || "L'envoi n'a pas abouti, réessayez dans un instant."; go.disabled = false; go.textContent = 'Envoyer ma demande'; return; }
      ga('planner_request_pending', { providers: stops.length });
      body.textContent = '';
      const done = el('div', 'pl-done');
      const ic = el('div', 'pl-done-ico'); ic.innerHTML = ICO('<path d="M5 12l5 5L20 7"/>');
      const msg = el('p'); msg.append("Nous venons d'envoyer un e-mail à ", el('strong', null, iMail.value.trim()), '. Cliquez sur « Confirmer ma demande » : votre demande partira aussitôt ' + (stops.length > 1 ? 'aux ' + stops.length + ' prestataires.' : 'au prestataire.'));
      done.append(ic, el('h3', null, 'Vérifiez votre boîte mail'), msg);
      body.append(done);
      foot.textContent = '';
      const back = el('button', 'pl-dlg-send', 'Revenir à mon séjour'); back.type = 'button'; back.addEventListener('click', closeRequest);
      foot.append(el('small', null, 'Le lien est valable 48 heures.'), back);
      back.focus();
      if (askBtn) { askBtn.textContent = 'Demande envoyée ✓'; askBtn.disabled = true; } // evita una 2.ª demanda del mismo plan
    });
  }

  form.addEventListener('submit', (e) => { e.preventDefault(); send(input.value); });
  input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); } });
  $('#pl-reset').addEventListener('click', reset);
  $('#pl-close').addEventListener('click', () => sheet(false));
  $('#pl-scrim').addEventListener('click', () => sheet(false));
  $('#pl-mbar-ask').addEventListener('click', () => { sheet(true); setTimeout(() => input.focus(), 300); });
  $('#pl-mbar-conv').addEventListener('click', () => sheet(true));
  mobile.addEventListener('change', () => sheet(false));

  /* ── Llegada desde el enlace del e-mail: resumen + botón (abrir el enlace no envía nada) ── */
  const cf = C.confirm;
  // El token no debe quedar en la URL que lee GA (page_location): se quita antes de que cargue gtag (en load).
  // window.history: aquí « history » es el historial del chat (let history = []).
  if (cf && /[?&]confirmer=/.test(location.search)) window.history.replaceState(null, '', location.pathname);
  if (cf) {
    openChat();
    form.hidden = true;
    if (cf.state !== 'ok') {
      const b = addText(cf.state === 'unknown' ? "Ce lien n'est pas valide." : 'Ce lien a expiré ou a déjà été utilisé.');
      const again = el('button', 'pl-ghost', 'Refaire ma demande'); again.type = 'button';
      again.addEventListener('click', () => { location.href = location.pathname; });
      b.append(again);
    } else {
      const b = el('div', 'pl-ba');
      b.append(el('div', null, `Votre séjour « ${cf.title} » est prêt. Confirmez pour envoyer votre demande à :`));
      const who = el('div', 'pl-who'); cf.names.forEach((n) => who.append(el('span', null, n))); b.append(who);
      const acts = el('div', 'pl-actions');
      const go = el('button', 'is-primary', "Confirmer l'envoi"); go.type = 'button';
      go.addEventListener('click', async () => {
        go.disabled = true; setThinking(true);
        const r = await post(C.confirmUrl, { token: cf.token });
        setThinking(false);
        if (r.ok) {
          (r.data.slugs || []).forEach((slug) => ga('planner_request', { listing_slug: slug })); // spec §3: demandas por ficha
          addText('Vos demandes sont parties. Les prestataires vous répondent directement par e-mail ; un récapitulatif vous attend dans votre boîte.');
        } else {
          addText(r.data.message || 'Ce lien a expiré ou a déjà été utilisé.');
        }
      });
      acts.append(go); b.append(acts); aiRow(b);
    }
  } else {
    cycle();
  }
})();
