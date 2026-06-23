/**
 * Módulo: home-ai.js
 */
document.addEventListener('DOMContentLoaded', () => {
    const aiBtn = document.getElementById('home-ai-btn');
    const searchInput = document.getElementById('home-search-input');
    const searchForm = document.getElementById('home-search-form');
    const aiModal = document.getElementById('home-ai-modal');
    const aiModalForm = document.getElementById('home-ai-modal-form');
    const aiPromptField = document.getElementById('home-ai-prompt');
    const aiFeedback = document.getElementById('home-ai-feedback');
    const aiSubmitBtn = document.getElementById('home-ai-submit');
    const aiCloseButtons = document.querySelectorAll('[data-close-home-ai]');

    if (!aiBtn || !searchInput || !searchForm || !aiModal || !aiModalForm || !aiPromptField || !aiFeedback || !aiSubmitBtn) {
        return;
    }

    const openModal = () => {
        aiPromptField.value = searchInput.value.trim();
        aiFeedback.textContent = '';
        aiFeedback.classList.remove('is-info');
        aiModal.classList.add('is-open');
        aiModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => aiPromptField.focus(), 30);
    };

    const closeModal = () => {
        aiModal.classList.remove('is-open');
        aiModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        aiBtn.focus();
    };

    aiBtn.addEventListener('click', openModal);

    aiCloseButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    aiModal.addEventListener('click', (event) => {
        if (event.target === aiModal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && aiModal.classList.contains('is-open')) {
            closeModal();
        }
    });

    // TASK-017: feedback de carga en el submit clásico del formulario del hero.
    // Deshabilita el botón "Rechercher" y muestra estado de carga mientras el
    // navegador navega a la página de resultados. El envío GET nativo se mantiene.
    const searchSubmitBtn = searchForm.querySelector('button[type="submit"]');
    if (searchSubmitBtn) {
        searchForm.addEventListener('submit', () => {
            searchSubmitBtn.disabled = true;
            searchSubmitBtn.innerHTML = '<i class="bi bi-arrow-repeat ai-loading"></i> Recherche…';
        });
    }

    aiModalForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const prompt = aiPromptField.value.trim();

        if (prompt.length < 5) {
            aiFeedback.textContent = "Décrivez un peu plus votre envie, par exemple un hôtel avec spa ou une balade à vélo.";
            aiFeedback.classList.remove('is-info');
            aiPromptField.focus();
            return;
        }

        searchInput.value = prompt;
        aiSubmitBtn.disabled = true;
        const originalContent = aiSubmitBtn.innerHTML;
        aiSubmitBtn.innerHTML = '<i class="bi bi-arrow-repeat ai-loading"></i><span>Analyse en cours</span>';
        aiFeedback.textContent = "L'IA analyse votre demande…";
        aiFeedback.classList.add('is-info');

        try {
            const response = await fetch(`${BASE_URL}${lang}/ai-analyze`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `prompt=${encodeURIComponent(prompt)}`
            });

            // TASK-017: verificar HTTP status antes de parsear JSON
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();

            if (data.id) {
                window.location.href = `${BASE_URL}${lang}/search?q=${encodeURIComponent(prompt)}&highlight=${data.id}`;
                return;
            }

            // Sin resultado preciso de IA → búsqueda clásica con el texto introducido
            aiFeedback.textContent = "Aucune correspondance précise trouvée. Lancement d'une recherche classique…";
            searchForm.submit();
        } catch (error) {
            // TASK-017: mensaje de fallback más claro para el usuario
            aiFeedback.textContent = "Le service IA est momentanément indisponible. Lancement d'une recherche classique…";
            searchForm.submit();
        } finally {
            aiSubmitBtn.disabled = false;
            aiSubmitBtn.innerHTML = originalContent;
            aiFeedback.classList.remove('is-info');
            if (!aiModal.classList.contains('is-open')) {
                document.body.style.overflow = '';
            }
        }
    });

    searchInput.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
            event.preventDefault();
            openModal();
        }
    });
});