document.addEventListener('DOMContentLoaded', () => {
    const aiSubmitBtn = document.getElementById('ai-submit-button');
    const aiPrompt = document.getElementById('ai-prompt');
    const responseEmpty = document.getElementById('ai-response-empty');
    const responseLoading = document.getElementById('ai-response-loading');
    const responseBody = document.getElementById('ai-response-body');
    const responseLabel = document.getElementById('ai-response-label');
    const responseTitle = document.getElementById('ai-response-title');
    const responseText = document.getElementById('ai-response-text');
    const responseMeta = document.getElementById('ai-response-meta');
    const highlightButton = document.getElementById('ai-highlight-button');
    const resultsColumn = document.getElementById('results-list');
    const resultsCount = document.querySelector('.search-results-count');
    const activeFilters = document.querySelector('.search-active-filters');
    const pageShell = document.getElementById('search-page');
    const strategyBtns = document.querySelectorAll('.ai-prompt-button');
    const pendingPromptKey = 'canal_du_midi.pending_ai_prompt';
    const currentParams = new URLSearchParams(window.location.search);
    const baseSearchUrl = `${BASE_URL}${lang}/search`;

    if (!aiSubmitBtn || !aiPrompt || !responseEmpty || !responseBody) return;

    const getAvailableResults = () => {
        if (Array.isArray(window.searchResults)) return window.searchResults;
        if (typeof searchResults !== 'undefined' && Array.isArray(searchResults)) return searchResults;
        return [];
    };

    const hasAppliedFilters = () => {
        const keys = ['q', 'city', 'type', 'type[]'];
        return keys.some((key) => currentParams.has(key) && currentParams.getAll(key).some((value) => String(value || '').trim() !== ''));
    };

    const normalizeResult = (service) => {
        const baseResults = getAvailableResults();
        const fallback = baseResults.find((item) => String(item?.id) === String(service?.id)) || {};

        return {
            id: service?.id ?? fallback.id ?? null,
            title: service?.title ?? fallback.title ?? 'Adresse Canal du Midi',
            type: service?.type ?? fallback.type ?? '',
            price: service?.price ?? fallback.price ?? '',
            text: service?.text ?? service?.description ?? fallback.text ?? fallback.description ?? '',
            address: service?.address ?? fallback.address ?? '',
            lat: Number(service?.lat ?? fallback.lat ?? 0),
            lng: Number(service?.lng ?? fallback.lng ?? 0),
            image: service?.image ?? fallback.image ?? '',
            gallery: Array.isArray(service?.gallery) ? service.gallery : (Array.isArray(fallback.gallery) ? fallback.gallery : []),
            url: service?.url ?? fallback.url ?? '',
            phone: service?.phone ?? fallback.phone ?? '',
            email: service?.email ?? fallback.email ?? '',
            roomsCount: Number(service?.roomsCount ?? fallback.roomsCount ?? 0),
            hybrid: Boolean(service?.hybrid ?? fallback.hybrid ?? false),
            priceValue: Number(service?.priceValue ?? fallback.priceValue ?? 0),
        };
    };

    const normalizeResults = (data) => {
        if (Array.isArray(data?.results) && data.results.length > 0) {
            return data.results.map(normalizeResult).filter((item) => item.id !== null);
        }

        if (data?.id !== null && data?.id !== undefined) {
            return [normalizeResult(data)].filter((item) => item.id !== null);
        }

        return [];
    };

    const createCard = (service) => {
        const article = document.createElement('article');
        article.className = 'explore-card is-ai-result';
        article.dataset.id = String(service.id);
        article.dataset.lat = String(service.lat || 0);
        article.dataset.lng = String(service.lng || 0);

        article.addEventListener('mouseenter', () => {
            if (typeof window.highlightMarker === 'function') {
                window.highlightMarker(service.id);
            }
        });

        article.addEventListener('mouseleave', () => {
            if (typeof window.resetMarker === 'function') {
                window.resetMarker(service.id);
            }
        });

        const link = document.createElement('a');
        link.className = 'explore-card-link';
        link.href = service.url || '#';

        const media = document.createElement('div');
        media.className = 'card-image' + (service.image ? '' : ' card-image--placeholder');

        if (service.image) {
            const img = document.createElement('img');
            img.alt = service.title || 'Service';
            img.width = 400;
            img.height = 260;
            img.decoding = 'async';
            media.appendChild(img);

            const markLoaded = () => {
                media.classList.add('is-loaded');
            };

            img.addEventListener('load', markLoaded, { once: true });
            img.addEventListener('error', () => {
                media.classList.add('is-loaded', 'card-image--placeholder');
                img.remove();
                const icon = document.createElement('div');
                icon.className = 'card-image-icon';
                icon.innerHTML = '<i class="bi bi-building"></i>';
                media.appendChild(icon);
            }, { once: true });

            img.src = service.image;

            if (img.complete && img.naturalWidth > 0) {
                markLoaded();
            }
        } else {
            const icon = document.createElement('div');
            icon.className = 'card-image-icon';
            icon.innerHTML = '<i class="bi bi-building"></i>';
            media.appendChild(icon);
        }

        const body = document.createElement('div');
        body.className = 'card-body';

        const title = document.createElement('h3');
        title.className = 'card-title';
        title.textContent = service.title || 'Adresse Canal du Midi';
        body.appendChild(title);

        if (service.address) {
            const location = document.createElement('div');
            location.className = 'card-location';
            location.innerHTML = '<i class="bi bi-geo-alt"></i>';
            const span = document.createElement('span');
            span.textContent = service.address;
            location.appendChild(span);
            body.appendChild(location);
        }

        if (service.text) {
            const tagline = document.createElement('p');
            tagline.className = 'card-tagline';
            tagline.textContent = String(service.text).slice(0, 120) + (String(service.text).length > 120 ? '…' : '');
            body.appendChild(tagline);
        }

        const footer = document.createElement('div');
        footer.className = 'card-footer-row--right-aligned';

        const detailLink = document.createElement('a');
        detailLink.className = 'card-detail-trigger';
        detailLink.href = service.url || '#';
        detailLink.innerHTML = '<span>Voir la fiche</span><i class="bi bi-arrow-right-short"></i>';
        footer.appendChild(detailLink);

        link.appendChild(media);
        article.appendChild(link);
        article.appendChild(body);
        article.appendChild(footer);

        return article;
    };

    const updateResultsCount = (count) => {
        if (!resultsCount) return;
        resultsCount.textContent = `${count} résultat${count > 1 ? 's' : ''}`;
    };

    const renderResultSet = (services) => {
        const resultsList = resultsColumn?.querySelector('.explore-list');
        const noResultsCard = resultsColumn?.querySelector('.no-results-card');

        if (noResultsCard) {
            noResultsCard.remove();
        }

        let list = resultsList;
        if (!list && resultsColumn) {
            list = document.createElement('div');
            list.className = 'explore-list';
            resultsColumn.appendChild(list);
        }

        if (!list) return;

        list.innerHTML = '';
        services.forEach((service) => {
            list.appendChild(createCard(service));
        });

        updateResultsCount(services.length);

        if (activeFilters) {
            activeFilters.remove();
        }

        window.searchResults = services;
        if (typeof window.setSearchMapResults === 'function') {
            window.setSearchMapResults(services);
        }
    };

    const showNoResults = (message) => {
        if (!resultsColumn) return;

        const list = resultsColumn.querySelector('.explore-list');
        if (list) {
            list.remove();
        }

        let noResultsCard = resultsColumn.querySelector('.no-results-card');
        if (!noResultsCard) {
            noResultsCard = document.createElement('div');
            noResultsCard.className = 'no-results-card';
            noResultsCard.innerHTML = `
                <div class="no-results-icon"><i class="bi bi-compass"></i></div>
                <h2>Aucun résultat trouvé</h2>
                <p></p>
                <a href="${baseSearchUrl}" class="button button-small">Réinitialiser</a>
            `;
            resultsColumn.appendChild(noResultsCard);
        }

        const paragraph = noResultsCard.querySelector('p');
        if (paragraph) {
            paragraph.textContent = message;
        }

        updateResultsCount(0);
        window.searchResults = [];
        if (typeof window.setSearchMapResults === 'function') {
            window.setSearchMapResults([]);
        }
    };

    const renderResponseMeta = (services, data) => {
        if (!responseMeta) return;

        responseMeta.innerHTML = '';

        const metaItems = [];

        if (services.length > 1) {
            metaItems.push({ icon: 'bi-layers', label: `${services.length} résultats` });
            services.slice(0, 4).forEach((service) => {
                if (service?.title) {
                    metaItems.push({ icon: 'bi-bookmark-star', label: service.title });
                }
            });
        } else if (data.price || services[0]?.price) {
            metaItems.push({ icon: 'bi-cash-stack', label: data.price || services[0]?.price });
        }

        if (metaItems.length === 0) {
            return;
        }

        metaItems.forEach((item) => {
            const pill = document.createElement('span');
            pill.className = 'ai-response-pill';
            pill.innerHTML = `<i class="bi ${item.icon}"></i>`;
            const label = document.createElement('span');
            label.textContent = item.label;
            pill.appendChild(label);
            responseMeta.appendChild(pill);
        });
    };

    const applyAiResults = (data) => {
        const services = normalizeResults(data);

        responseEmpty.classList.remove('is-hidden');
        responseBody.classList.remove('is-hidden');
        responseLoading?.classList.add('is-hidden');

        responseLabel.textContent = data.count > 1
            ? `IA · ${data.count} résultats`
            : (data.type || 'Résultat IA');
        responseTitle.textContent = data.title || (services[0]?.title ?? 'Résultat IA');
        responseText.textContent = data.count > 1
            ? (data.text || 'Voici plusieurs résultats correspondant à votre demande. Consultez la liste ci-dessous pour voir les autres adresses retenues.')
            : (data.text || 'Voici la recommandation sélectionnée par l’assistant.');
        renderResponseMeta(services, data);

        if (services.length > 0) {
            renderResultSet(services);
        } else {
            showNoResults('L’assistant n’a pas pu trouver de résultat exploitable pour cette demande.');
        }
    };

    const executeAiSearch = async (text) => {
        aiSubmitBtn.disabled = true;
        aiSubmitBtn.innerHTML = '<i class="bi bi-cpu"></i> Analyse en cours...';
        responseEmpty?.classList.add('is-hidden');
        responseBody?.classList.add('is-hidden');
        responseLoading?.classList.remove('is-hidden');

        try {
            const response = await fetch(`${BASE_URL}${lang}/ai-analyze`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `prompt=${encodeURIComponent(text)}`,
            });

            const data = await response.json();
            applyAiResults(data);
        } catch (error) {
            console.error('AI Error:', error);
            responseLoading?.classList.add('is-hidden');
            responseEmpty?.classList.remove('is-hidden');
            responseBody?.classList.add('is-hidden');
        } finally {
            aiSubmitBtn.disabled = false;
            aiSubmitBtn.innerText = 'Analyser ma demande';
        }
    };

    const submitAiSearch = (text) => {
        const cleanText = String(text || '').trim();
        if (!cleanText) return;

        if (hasAppliedFilters()) {
            sessionStorage.setItem(pendingPromptKey, cleanText);
            window.location.href = baseSearchUrl;
            return;
        }

        executeAiSearch(cleanText);
    };

    strategyBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const strategy = btn.dataset.aiStrategy;
            let text = '';
            if (strategy === 'best-value') text = 'Trouver le meilleur rapport qualité / prix';
            if (strategy === 'hybrid') text = 'Prioriser les adresses avec restauration';
            if (strategy === 'spacious') text = 'Voir les établissements les plus spacieux';
            aiPrompt.value = text;
            submitAiSearch(text);
        });
    });

    aiSubmitBtn.addEventListener('click', () => {
        submitAiSearch(aiPrompt.value);
    });

    const pendingPrompt = sessionStorage.getItem(pendingPromptKey);
    if (pendingPrompt) {
        sessionStorage.removeItem(pendingPromptKey);
        aiPrompt.value = pendingPrompt;
        window.requestAnimationFrame(() => {
            submitAiSearch(pendingPrompt);
        });
    }

    if (highlightButton) {
        highlightButton.addEventListener('click', () => {
            const currentResults = getAvailableResults();
            const highlightedId = currentResults[0]?.id;
            if (!highlightedId) return;
            const card = document.querySelector(`.explore-card[data-id="${highlightedId}"]`);
            if (!card) return;
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof highlightMarker === 'function') {
                highlightMarker(highlightedId);
            }
        });
    }
});
