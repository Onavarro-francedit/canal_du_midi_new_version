(function () {
    // IntersectionObserver: carga la imagen solo cuando la card
    // entra en el viewport (lazy load manual con control del fade).
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
 
            const img = entry.target;
            const src = img.dataset.src;
            if (!src) return;
 
            img.src = src;
 
            img.addEventListener('load', () => {
                img.closest('.card-image')?.classList.add('is-loaded');
            }, { once: true });
 
            // Si la imagen falla (404, CORS, etc.) quitamos el shimmer
            // y mostramos el placeholder en su lugar.
            img.addEventListener('error', () => {
                const wrapper = img.closest('.card-image');
                if (!wrapper) return;
                wrapper.classList.add('is-loaded', 'card-image--placeholder');
                img.remove();
                const icon = document.createElement('div');
                icon.className = 'card-image-icon';
                icon.innerHTML = '<i class="bi bi-building"></i>';
                wrapper.appendChild(icon);
            }, { once: true });
 
            observer.unobserve(img);
        });
    }, {
        // Empieza a cargar 200px antes de que la card sea visible
        rootMargin: '200px 0px',
        threshold: 0,
    });
 
    // Observar todas las imágenes con data-src
    document.querySelectorAll('.card-image img[data-src]').forEach((img) => {
        observer.observe(img);
    });
})();