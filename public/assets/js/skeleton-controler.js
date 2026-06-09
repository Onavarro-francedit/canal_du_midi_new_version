(function () {
    const signalImagesReady = () => {
        window.dispatchEvent(new CustomEvent('search:images-ready'));
    };

    const images = Array.from(document.querySelectorAll('.search-layout-page .card-image img[data-src]'));

    if (images.length === 0) {
        signalImagesReady();
        return;
    }

    let settledImages = 0;
    const markSettled = () => {
        settledImages += 1;
        if (settledImages >= images.length) {
            signalImagesReady();
        }
    };

    images.forEach((img) => {
        const src = img.dataset.src;
        if (!src) {
            markSettled();
            return;
        }

        const wrapper = img.closest('.card-image');

        img.addEventListener('load', () => {
            wrapper?.classList.add('is-loaded');
            markSettled();
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
            markSettled();
        }, { once: true });

        img.src = src;
    });
})();