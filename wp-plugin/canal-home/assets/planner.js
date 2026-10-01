/* Planificateur 2026 (TASK-044) — composer, ideas, chat y llamadas REST. Datos: window.CDM_PLANNER. */
(() => {
  const C = window.CDM_PLANNER;
  const root = document.getElementById('cdm-planner');
  if (!C || !root) return;
  const $ = (s) => root.querySelector(s);
  const hero = $('#pl-hero'), chat = $('#pl-chat'), ideas = $('#pl-ideas'), wrap = $('#pl-wrap');
  const log = $('#pl-log'), form = $('#pl-composer'), input = $('#pl-input'), website = $('#pl-website');
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
  let history = [], plan = null, stage = 'plan', busy = false;

  function addPlan(reply, p) {
    plan = p;
    const b = el('div', 'pl-ba');
    b.append(el('div', null, reply));
    if (!p.days.length) { aiRow(b); return; }
    const pills = el('div', 'pl-pills');
    pills.append(el('span', 'pl-pill pl-t-love', p.title), el('span', 'pl-pill pl-t-boat', MODE[p.mode] || ''));
    if (p.when) pills.append(el('span', 'pl-pill pl-t-family', p.when));
    if (p.people) pills.append(el('span', 'pl-pill pl-t-bike', p.people));
    b.append(pills);
    const days = el('div', 'pl-days');
    p.days.forEach((d, i) => {
      const c = el('div', 'pl-day'); c.style.animationDelay = (i * 90) + 'ms';
      const head = el('b', null, d.label);
      head.append(el('em', null, d.km === null ? '' : 'km ' + d.km));
      c.append(head, el('strong', null, d.place), el('span', null, d.text));
      if (d.url) { const a = el('a', null, d.name + ' →'); a.href = d.url; a.target = '_blank'; a.rel = 'noopener'; c.append(a); }
      days.append(c);
    });
    b.append(days);
    if (p.providers.length) {
      const acts = el('div', 'pl-actions');
      const ask = el('button', 'is-primary', 'Demander les disponibilités');
      const adj = el('button', null, 'Ajuster le séjour');
      [ask, adj].forEach((x) => (x.type = 'button'));
      ask.addEventListener('click', () => { acts.querySelectorAll('button').forEach((x) => (x.disabled = true)); askProviders(); });
      adj.addEventListener('click', () => input.focus());
      acts.append(ask, adj);
      b.append(acts);
    }
    aiRow(b);
  }

  function askProviders() {
    if (plan.missing.length) {
      addText('Pour quelles dates et combien de personnes ? Écrivez-le ci-dessous, je mets votre séjour à jour.');
      stage = 'plan';
      input.placeholder = 'Ex. du 14 au 17 mai, 2 adultes et 2 enfants';
      input.focus();
      return;
    }
    const b = el('div', 'pl-ba');
    b.append(el('div', null, `Je peux écrire à ces ${plan.names.length} prestataires pour vos dates. Ils vous répondront directement :`));
    const who = el('div', 'pl-who'); plan.names.forEach((n) => who.append(el('span', null, n))); b.append(who);
    b.append(el('div', null, 'Quelle est votre adresse e-mail ? Écrivez-la ci-dessous.'));
    b.append(el('p', 'pl-fine', "Elle ne sera transmise qu'à ces prestataires, après confirmation de votre part. Conservée 12 mois."));
    aiRow(b);
    stage = 'email'; input.placeholder = 'votre@adresse.fr'; input.focus();
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
    if (stage === 'email') {
      const r = await post(C.requestUrl, { plan, email: t, website: website.value });
      typing.remove();
      if (r.ok) {
        ga('planner_request_pending', {});
        addText(`C'est noté. Je viens d'envoyer un e-mail à ${t} : ouvrez-le et confirmez, vos demandes partent aussitôt.`);
        stage = 'done'; input.placeholder = 'Une question sur votre séjour ?';
      } else {
        addText(r.data.message || "Il me faut une adresse e-mail valide, par exemple marie@exemple.fr.");
      }
    } else {
      history.push({ role: 'user', text: t });
      const r = await post(C.planUrl, { messages: history.slice(-8), plan });
      typing.remove();
      if (r.ok) {
        history.push({ role: 'assistant', text: r.data.reply });
        addPlan(r.data.reply, r.data.plan);
        ga('planner_plan', { days: r.data.plan.days.length });
        input.placeholder = 'Ajustez : « plus calme », « un jour de plus »…';
      } else {
        history.pop();
        const b = addText(r.data.message || "L'assistant est momentanément indisponible.");
        if (r.data.error === 'unavailable' || r.data.error === 'daily') {
          const a = el('a', null, 'Explorer la carte →'); a.href = C.carteUrl; b.append(a);
        }
      }
    }
    busy = false; sendBtn.disabled = false;
    setThinking(false);
    input.focus();
  }

  function reset() {
    history = []; plan = null; stage = 'plan'; busy = false; sendBtn.disabled = false;
    log.innerHTML = '';
    chat.hidden = true; hero.hidden = false; ideas.hidden = false; wrap.classList.remove('is-chatting');
    setThinking(false); input.value = '';
  }

  form.addEventListener('submit', (e) => { e.preventDefault(); send(input.value); });
  input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); } });
  $('#pl-reset').addEventListener('click', reset);

  /* ── Llegada desde el enlace del e-mail: resumen + botón (abrir el enlace no envía nada) ── */
  const cf = C.confirm;
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
          ga('planner_request', { providers: r.data.providers, fe: r.data.fe });
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
