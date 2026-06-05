document.addEventListener('DOMContentLoaded', () => {
    const aiSubmitBtn = document.getElementById('ai-submit-button');
    const aiPrompt = document.getElementById('ai-prompt');
    const responseEmpty = document.getElementById('ai-response-empty');
    const responseLoading = document.getElementById('ai-response-loading');
    const responseBody = document.getElementById('ai-response-body');
    const strategyBtns = document.querySelectorAll('.ai-prompt-button');

    // 1. Manejar botones de sugerencia rápidos
    strategyBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const strategy = btn.dataset.aiStrategy;
            let text = "";
            if (strategy === 'best-value') text = "Trouver le meilleur rapport qualité / prix";
            if (strategy === 'hybrid') text = "Prioriser les adresses avec restauration";
            if (strategy === 'spacious') text = "Voir les établissements les plus spacieux";
            
            aiPrompt.value = text;
            aiSubmitBtn.click();
        });
    });

    // 2. Procesar el análisis
    aiSubmitBtn.addEventListener('click', async () => {
        const text = aiPrompt.value.trim();
        if (!text) return;

        aiSubmitBtn.disabled = true;
        aiSubmitBtn.innerHTML = '<i class="bi bi-cpu"></i> Analyse en cours...';
        responseEmpty?.classList.add('is-hidden');
        responseBody?.classList.add('is-hidden');
        responseLoading?.classList.remove('is-hidden');

        try {
            const response = await fetch(`${BASE_URL}${lang}/ai-analyze`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `prompt=${encodeURIComponent(text)}`
            });

            const data = await response.json();

            // 3. Mostrar respuesta elegante
            responseEmpty.classList.add('is-hidden');
            responseBody.classList.remove('is-hidden');
            responseLoading?.classList.add('is-hidden');

            document.getElementById('ai-response-label').innerText = data.type;
            document.getElementById('ai-response-title').innerText = data.title;
            document.getElementById('ai-response-text').innerText = data.text;
            document.getElementById('ai-response-meta').innerText = data.price;
            const openLink = document.getElementById('ai-open-link');
            if (openLink) {
                openLink.href = data.id ? `${BASE_URL}${lang}/service/${data.id}` : '#';
            }

            // 4. Efecto Wow: Scroll e iluminación del resultado en la lista
            const targetCard = document.querySelector(`.explore-card[data-id="${data.id}"]`);
            if (targetCard) {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetCard.style.outline = "4px solid #6a63d9";
                targetCard.style.outlineOffset = "4px";
                setTimeout(() => { targetCard.style.transition = "outline 2s"; targetCard.style.outlineColor = "transparent"; }, 3000);
            }

            // 5. Destacar en el mapa (llamamos a la función global de map.js)
            if (window.highlightMarker) window.highlightMarker(data.id);

        } catch (error) {
            console.error("AI Error:", error);
            responseLoading?.classList.add('is-hidden');
            responseEmpty?.classList.remove('is-hidden');
            responseBody?.classList.add('is-hidden');
        } finally {
            aiSubmitBtn.disabled = false;
            aiSubmitBtn.innerText = "Analyser ma demande";
        }
    });
});