(function () {
    // WP: las imágenes ya vienen con src en el HTML (TASK-035: LCP); aquí solo el fundido del resto,
    // el icono si fallan y el aviso de « listas » cuando han cargado las primeras.
    const FIRST_IMAGES = 2; // = CANAL_CARTE_EAGER_IMAGES
    const images = Array.from(document.querySelectorAll('.search-layout-page .card-image img'));
    const target = Math.min(images.length, FIRST_IMAGES);
    let settledImages = 0;

    const signalImagesReady = () => {
        window.dispatchEvent(new CustomEvent('search:images-ready'));
    };
    const markSettled = (index) => {
        if (index >= FIRST_IMAGES) return;
        settledImages += 1;
        if (settledImages === target) signalImagesReady();
    };

    if (target === 0) {
        signalImagesReady();
        return;
    }

    const showIcon = (img) => {
        const wrapper = img.closest('.card-image');
        if (!wrapper) return;
        wrapper.classList.add('is-loaded', 'card-image--placeholder');
        img.remove();
        const icon = document.createElement('div');
        icon.className = 'card-image-icon';
        icon.innerHTML = '<i class="bi bi-building"></i>';
        wrapper.appendChild(icon);
    };

    images.forEach((img, index) => {
        if (img.complete) {
            if (img.naturalWidth === 0) showIcon(img);
            else img.closest('.card-image')?.classList.add('is-loaded');
            markSettled(index);
            return;
        }
        img.addEventListener('load', () => {
            img.closest('.card-image')?.classList.add('is-loaded');
            markSettled(index);
        }, { once: true });
        img.addEventListener('error', () => {
            showIcon(img);
            markSettled(index);
        }, { once: true });
    });
})();
