(function () {
    'use strict';

    /* ── State ─────────────────────────────────────────────────────────── */
    var currentPlan   = null;

    /* ── Screen IDs and step mapping ───────────────────────────────────── */
    var SCREENS  = ['screen-input','screen-loading','screen-plan','screen-contact','screen-confirmation'];
    var STEP_MAP = { 'screen-input':1, 'screen-plan':2, 'screen-contact':3, 'screen-confirmation':4 };

    function showScreen(id) {
        SCREENS.forEach(function(s) {
            var el = document.getElementById(s);
            if (!el) return;
            el.classList.remove('active');
        });
        var target = document.getElementById(id);
        if (target) {
            target.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        updateSteps(STEP_MAP[id] || null);
    }

    function updateSteps(active) {
        document.querySelectorAll('.planner-step-item').forEach(function(item, i) {
            var step = i + 1;
            item.classList.remove('active','done');
            if (active === null) return;
            if (step < active) item.classList.add('done');
            else if (step === active) item.classList.add('active');
        });
        document.querySelectorAll('.step-connector').forEach(function(conn, i) {
            conn.classList.toggle('done', active !== null && i + 1 < active);
        });
    }

    /* ── Loading messages ──────────────────────────────────────────────── */
    var LOADING_MSGS = [
        'Analyse de votre demande',
        'Sélection des prestataires',
        'Construction de votre itinéraire',
        'Ajout des conseils pratiques',
        'Finalisation du plan',
    ];
    var loadIdx   = 0;
    var loadTimer = null;

    function startLoading() {
        loadIdx = 0;
        var el = document.getElementById('loading-msg');
        if (el) el.textContent = LOADING_MSGS[0];
        loadTimer = setInterval(function() {
            loadIdx = (loadIdx + 1) % LOADING_MSGS.length;
            var msgEl = document.getElementById('loading-msg');
            if (!msgEl) return;
            msgEl.style.opacity = '0';
            setTimeout(function() {
                msgEl.textContent = LOADING_MSGS[loadIdx];
                msgEl.style.opacity = '1';
            }, 300);
        }, 2200);
    }

    function stopLoading() {
        clearInterval(loadTimer);
    }

    /* ── Prompt chips ──────────────────────────────────────────────────── */
    document.querySelectorAll('.prompt-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var ta = document.getElementById('vacation-prompt');
            if (!ta) return;
            ta.value = chip.dataset.prompt || '';
            ta.dispatchEvent(new Event('input'));
            ta.focus();
        });
    });

    /* ── Char counter ──────────────────────────────────────────────────── */
    var ta = document.getElementById('vacation-prompt');
    if (ta) {
        ta.addEventListener('input', function() {
            var c = document.getElementById('char-count');
            if (c) c.textContent = ta.value.length;
        });
    }

    /* ── Generate plan ─────────────────────────────────────────────────── */
    var btnGen = document.getElementById('btn-generate');
    if (btnGen) btnGen.addEventListener('click', generatePlan);

    function generatePlan() {
        var prompt  = (document.getElementById('vacation-prompt') || {}).value || '';
        prompt = prompt.trim();
        var errEl   = document.getElementById('input-error');

        if (!prompt) {
            if (errEl) { errEl.textContent = 'Veuillez décrire votre voyage pour continuer.'; errEl.style.display = 'block'; }
            return;
        }
        if (errEl) errEl.style.display = 'none';

        showScreen('screen-loading');
        startLoading();

        var langVal = (typeof lang !== 'undefined') ? lang : 'fr';

        fetch(BASE_URL + 'ai-plan-generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'prompt=' + encodeURIComponent(prompt) + '&lang=' + encodeURIComponent(langVal),
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            stopLoading();
            if (!data.days || !data.days.length) throw new Error('Plan vide');
            currentPlan = JSON.parse(JSON.stringify(data));
            renderPlan(currentPlan);
            showScreen('screen-plan');
        })
        .catch(function() {
            stopLoading();
            showScreen('screen-input');
            var err = document.getElementById('input-error');
            if (err) { err.textContent = 'Impossible de générer le plan. Veuillez réessayer.'; err.style.display = 'block'; }
        });
    }

    /* ── Render plan ───────────────────────────────────────────────────── */
    function renderPlan(plan) {
        var summaryEl  = document.getElementById('plan-summary');
        var durationEl = document.getElementById('plan-duration');
        var container  = document.getElementById('days-container');

        if (summaryEl)  summaryEl.textContent  = plan.summary || '';
        if (durationEl) durationEl.innerHTML   = '<i class="bi bi-calendar3"></i> ' + (plan.duration_days || plan.days.length) + ' jour' + ((plan.duration_days || plan.days.length) > 1 ? 's' : '');
        if (!container) return;

        container.innerHTML = '';
        if (!plan.days || !plan.days.length) {
            container.innerHTML = '<p style="color:var(--muted);text-align:center;padding:40px;">Aucune activité dans le plan.</p>';
            return;
        }
        plan.days.forEach(function(day, di) {
            container.appendChild(buildDayCard(day, di));
        });
    }

    var SLOT_EMOJI = { 'matin':'🌅', 'après-midi':'☀️', 'soir':'🌙' };

    function buildDayCard(day, di) {
        var card = document.createElement('div');
        card.className = 'day-card';

        var hdr = document.createElement('div');
        hdr.className = 'day-header';
        hdr.innerHTML = '<span class="day-number">Jour ' + day.day + '</span>'
            + '<span class="day-label">' + esc(day.label || '') + '</span>';
        card.appendChild(hdr);

        var actsWrap = document.createElement('div');
        actsWrap.className = 'day-activities';

        if (!day.activities || !day.activities.length) {
            actsWrap.innerHTML = '<p style="padding:14px;color:var(--muted);font-size:13px;">Aucune activité.</p>';
        } else {
            day.activities.forEach(function(act, ai) {
                actsWrap.appendChild(buildActCard(act, di, ai));
            });
        }
        card.appendChild(actsWrap);
        return card;
    }

    function buildActCard(act, di, ai) {
        var el = document.createElement('div');
        el.className = 'activity-card';

        var emoji   = SLOT_EMOJI[act.slot] || '📍';
        var imgHtml = act.image
            ? '<img class="act-thumb" src="' + esc(act.image) + '" alt="' + esc(act.title || '') + '" loading="lazy" onerror="this.style.display=\'none\'">'
            : '<div class="act-thumb-placeholder">🏡</div>';

        el.innerHTML =
            '<button class="act-remove" title="Supprimer" aria-label="Supprimer cette activité"><i class="bi bi-x"></i></button>'
            + '<div class="act-slot">' + emoji + ' ' + esc(act.slot || '') + '</div>'
            + '<div class="act-body">'
            +   imgHtml
            +   '<div class="act-info">'
            +     (act.type  ? '<span class="act-type">' + esc(act.type) + '</span>' : '')
            +     '<strong class="act-title">' + esc(act.title || '') + '</strong>'
            +     (act.city  ? '<span class="act-city"><i class="bi bi-geo-alt"></i> ' + esc(act.city) + '</span>' : '')
            +     (act.price ? '<span class="act-price">' + esc(act.price) + '</span>' : '')
            +     (act.note  ? '<p class="act-note">' + esc(act.note) + '</p>' : '')
            +   '</div>'
            + '</div>';

        el.querySelector('.act-remove').addEventListener('click', function() {
            removeActivity(di, ai);
        });
        return el;
    }

    function removeActivity(di, ai) {
        if (!currentPlan || !currentPlan.days[di]) return;
        currentPlan.days[di].activities.splice(ai, 1);
        if (!currentPlan.days[di].activities.length) {
            currentPlan.days.splice(di, 1);
            currentPlan.duration_days = currentPlan.days.length;
        }
        renderPlan(currentPlan);
    }

    /* ── Navigation ────────────────────────────────────────────────────── */
    var backToInput = document.getElementById('btn-back-to-input');
    var backToPlan  = document.getElementById('btn-back-to-plan');
    var toContact   = document.getElementById('btn-to-contact');

    if (backToInput) backToInput.addEventListener('click', function() { showScreen('screen-input'); });
    if (backToPlan)  backToPlan.addEventListener('click',  function() { showScreen('screen-plan');  });
    if (toContact)   toContact.addEventListener('click',   function() { buildRecap(); showScreen('screen-contact'); });

    /* ── Plan recap (sidebar) ──────────────────────────────────────────── */
    function buildRecap() {
        var el = document.getElementById('plan-recap');
        if (!el || !currentPlan) return;
        var html = '';
        (currentPlan.days || []).forEach(function(day) {
            html += '<div class="recap-day">'
                + '<div class="recap-day-title">Jour ' + day.day + ' — ' + esc(day.label || '') + '</div>';
            (day.activities || []).forEach(function(act) {
                html += '<div class="recap-act">'
                    + '<span class="recap-slot">' + (SLOT_EMOJI[act.slot] || '') + ' ' + esc(act.slot || '') + '</span>'
                    + '<span>' + esc(act.title || '') + '</span>'
                    + '</div>';
            });
            html += '</div>';
        });
        el.innerHTML = html || '<p style="color:var(--muted);font-size:13px;">Plan vide.</p>';
    }

    /* ── Contact form submit ───────────────────────────────────────────── */
    var contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var errEl = document.getElementById('submit-error');
            if (errEl) errEl.style.display = 'none';

            var fd = new FormData(contactForm);
            if (!fd.get('name') || !fd.get('email')) {
                if (errEl) { errEl.textContent = 'Veuillez renseigner les champs obligatoires.'; errEl.style.display = 'block'; }
                return;
            }
            fd.append('plan', JSON.stringify(currentPlan));

            var btn  = document.getElementById('btn-submit');
            var span = btn ? btn.querySelector('span') : null;
            if (btn)  btn.disabled = true;
            if (span) span.textContent = 'Envoi en cours…';

            fetch(BASE_URL + 'ai-plan-submit', { method: 'POST', body: fd })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                if (!data.success) throw new Error(data.error || 'Erreur');
                var confEmail = document.getElementById('conf-email');
                var btnPdf    = document.getElementById('btn-pdf');
                if (confEmail) confEmail.textContent = fd.get('email');
                if (btnPdf)    btnPdf.href = BASE_URL + 'vacation-pdf/' + data.reference;
                showScreen('screen-confirmation');
            })
            .catch(function() {
                if (errEl) { errEl.textContent = 'Une erreur est survenue. Veuillez réessayer.'; errEl.style.display = 'block'; }
                if (btn)  btn.disabled = false;
                if (span) span.textContent = "Envoyer ma demande d'intérêt";
            });
        });
    }

    /* ── HTML escape ────────────────────────────────────────────────────── */
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ── Init ───────────────────────────────────────────────────────────── */
    showScreen('screen-input');

})();
