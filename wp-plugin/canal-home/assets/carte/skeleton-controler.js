(function () {
    const signalImagesReady = () => {
        window.dispatchEvent(new CustomEvent('search:images-ready'));
    };

    // WP: hasta 254 fichas → solo las 6 primeras imágenes deciden cuándo se muestra la página;
    // el resto se carga en diferido (loading="lazy").
    const FIRST_IMAGES = 6;
    const images = Array.from(document.querySelectorAll('.search-layout-page .card-image img[data-src]'));

    if (images.length === 0) {
        signalImagesReady();
        return;
    }

    const target = Math.min(images.length, FIRST_IMAGES);
    let settledImages = 0;
    const markSettled = (index) => {
        if (index >= FIRST_IMAGES) return;
        settledImages += 1;
        if (settledImages >= target) {
            signalImagesReady();
        }
    };

    images.forEach((img, index) => {
        const src = img.dataset.src;
        if (!src) {
            markSettled(index);
            return;
        }

        if (index >= FIRST_IMAGES) img.loading = 'lazy';

        const wrapper = img.closest('.card-image');

        img.addEventListener('load', () => {
            wrapper?.classList.add('is-loaded');
            markSettled(index);
        }, { once: true });

        img.addEventListener('error', () => {
            if (wrapper) {
                wrapper.classList.add('is-loaded', 'card-image--placeholder');
                img.remove();
                const icon = document.createElement('div');
                icon.className = 'card-image-icon';
                icon.innerHTML = '<i class="bi bi-building"></i>';
                wrapper.appendChild(icon);
            }
            markSettled(index);
        }, { once: true });

        if (img.dataset.srcset) img.srcset = img.dataset.srcset; // WP: tamaños reducidos de WordPress
        img.src = src;
    });
})();