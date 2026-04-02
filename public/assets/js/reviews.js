document.addEventListener('DOMContentLoaded', () => {
    const loadMoreBtn = document.getElementById('load-more-reviews');
    const container = document.getElementById('reviews-items-wrapper');
    const feedback = document.getElementById('reviews-feedback');
    const loadedCountNode = document.getElementById('reviews-loaded-count');

    if (loadMoreBtn && container) {
        loadMoreBtn.addEventListener('click', async () => {
            const page = parseInt(loadMoreBtn.dataset.page, 10);
            const sid = loadMoreBtn.dataset.sid;
            const total = parseInt(loadMoreBtn.dataset.total || '0', 10);

            loadMoreBtn.disabled = true;
            loadMoreBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Chargement...';
            if (feedback) feedback.textContent = 'Chargement des avis suivants...';

            try {
                const response = await fetch(`${BASE_URL}${lang}/get-more-reviews?service_id=${sid}&page=${page}`);
                const payload = await response.json();

                if (payload.html) {
                    container.insertAdjacentHTML('beforeend', payload.html);
                }

                if (loadedCountNode) {
                    const currentLoaded = parseInt(loadedCountNode.textContent || '0', 10);
                    loadedCountNode.textContent = String(currentLoaded + (payload.loadedCount || 0));
                }

                if (payload.hasMore) {
                    loadMoreBtn.dataset.page = String(payload.nextPage || (page + 1));
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Voir plus d\'avis';
                    if (feedback) feedback.textContent = `${loadedCountNode?.textContent || ''} avis affichés sur ${total}.`;
                } else {
                    loadMoreBtn.remove();
                    if (feedback) feedback.textContent = 'Tous les avis disponibles sont affichés.';
                }
            } catch (error) {
                console.error("Error cargando más reseñas:", error);
                loadMoreBtn.disabled = false;
                loadMoreBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Voir plus d\'avis';
                if (feedback) feedback.textContent = 'Impossible de charger plus d\'avis pour le moment.';
            }
        });
    }
});