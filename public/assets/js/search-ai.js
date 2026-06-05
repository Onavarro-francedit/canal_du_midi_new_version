document.addEventListener('DOMContentLoaded', () => {
    const promptButtons = document.querySelectorAll('.ai-prompt-button');
    const miniChips = document.querySelectorAll('.ai-mini-chip');
    const responseEmpty = document.getElementById('ai-response-empty');
    const responseBody = document.getElementById('ai-response-body');
    const responseLabel = document.getElementById('ai-response-label');
    const responseTitle = document.getElementById('ai-response-title');
    const responseText = document.getElementById('ai-response-text');
    const responseMeta = document.getElementById('ai-response-meta');
    const highlightButton = document.getElementById('ai-highlight-button');
    const openLink = document.getElementById('ai-open-link');
    const insightGrid = document.getElementById('ai-insight-grid');
    const promptInput = document.getElementById('ai-prompt');
    const submitButton = document.getElementById('ai-submit-button');

    if (!responseEmpty || !responseBody || !Array.isArray(searchResults)) return;

    const results = searchResults.filter((item) => item && item.id);
    let selectedSuggestion = null;

    const hybridCount = results.filter((item) => item.hybrid).length;
    const pricedResults = results.filter((item) => Number.isFinite(Number(item.priceValue)) && Number(item.priceValue) > 0);

    if (insightGrid) {
        const cards = insightGrid.querySelectorAll('.ai-insight-card');
        if (cards[0]) {
            cards[0].innerHTML = `<strong>${results.length}</strong><span>résultats analysés</span>`;
        }
        if (cards[1]) {
            cards[1].innerHTML = `<strong>${hybridCount}</strong><span>adresses avec restauration intégrée</span>`;
        }
    }

    const strategies = {
        'best-value': {
            label: 'Rapport qualité / prix',
            pick() {
                const pool = pricedResults.length ? pricedResults : results;
                return pool.slice().sort((a, b) => Number(a.priceValue || 999999) - Number(b.priceValue || 999999))[0] || null;
            },
            buildText(service) {
                return `${service.title} ressort comme une option pertinente si vous cherchez une fiche claire, bien positionnée et avec un tarif plus accessible que le reste de la sélection.`;
            },
        },
        hybrid: {
            label: 'Adresse avec restauration',
            pick() {
                return results.find((item) => item.hybrid) || null;
            },
            buildText(service) {
                return `${service.title} est une bonne candidate si vous souhaitez simplifier votre séjour avec une offre d'hébergement complétée par la restauration sur place.`;
            },
        },
        spacious: {
            label: 'Établissement spacieux',
            pick() {
                return results.slice().sort((a, b) => Number(b.roomsCount || 0) - Number(a.roomsCount || 0))[0] || null;
            },
            buildText(service) {
                return `${service.title} apparaît comme l'une des options les plus généreuses en capacité, pratique si vous privilégiez un établissement plus structuré.`;
            },
        },
    };

    const setFocusedCard = (serviceId) => {
        document.querySelectorAll('.explore-card').forEach((card) => {
            card.classList.toggle('is-ai-focus', card.dataset.id === String(serviceId));
        });
    };

    const renderMeta = (service) => {
        responseMeta.innerHTML = '';

        const pills = [
            { icon: 'bi-geo-alt', label: service.address || 'Canal du Midi' },
            { icon: 'bi-cash-stack', label: service.price || 'Sur demande' },
        ];

        if (service.hybrid) {
            pills.push({ icon: 'bi-cup-hot', label: 'Restauration' });
        }

        if (Number(service.roomsCount) > 0) {
            pills.push({ icon: 'bi-door-open', label: `${service.roomsCount} chambres` });
        }

        pills.forEach((pill) => {
            const node = document.createElement('span');
            node.className = 'ai-response-pill';
            node.innerHTML = `<i class="bi ${pill.icon}"></i>${pill.label}`;
            responseMeta.appendChild(node);
        });
    };

    const renderSuggestion = (strategyKey) => {
        const strategy = strategies[strategyKey];
        if (!strategy) return;

        const service = strategy.pick();
        if (!service) {
            responseEmpty.classList.remove('is-hidden');
            responseBody.classList.add('is-hidden');
            responseEmpty.innerHTML = '<i class="bi bi-exclamation-circle"></i><p>Aucune recommandation exploitable n\'a pu être calculée avec les résultats actuels.</p>';
            return;
        }

        selectedSuggestion = service;
        responseEmpty.classList.add('is-hidden');
        responseBody.classList.remove('is-hidden');
        responseLabel.textContent = strategy.label;
        responseTitle.textContent = service.title;
        responseText.textContent = strategy.buildText(service);
        renderMeta(service);
        openLink.href = service.url || '#';
        setFocusedCard(service.id);
    };

    promptButtons.forEach((button) => {
        button.addEventListener('click', () => {
            renderSuggestion(button.dataset.aiStrategy);
        });
    });

    miniChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!promptInput) return;
            promptInput.value = chip.dataset.aiFill || '';
            promptInput.focus();
            promptInput.setSelectionRange(promptInput.value.length, promptInput.value.length);
        });
    });

    if (highlightButton) {
        highlightButton.addEventListener('click', () => {
            if (!selectedSuggestion) return;
            const card = document.querySelector(`.explore-card[data-id="${selectedSuggestion.id}"]`);
            if (!card) return;
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setFocusedCard(selectedSuggestion.id);
            if (typeof highlightMarker === 'function') {
                highlightMarker(selectedSuggestion.id);
            }
        });
    }
});
