/**
 * Módulo: hero-carousel.js
 * Rota el fondo del hero de la ficha de servicio entre las fotos de la galería.
 */
document.addEventListener('DOMContentLoaded', () => {
    const hero = document.getElementById('service-hero');
    if (!hero) return;

    const isSafeHttpUrl = (value) => {
        try {
            const parsed = new URL(value);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch {
            return false;
        }
    };

    let slides = [];
    try {
        slides = JSON.parse(hero.dataset.gallery || '[]');
    } catch {
        slides = [];
    }
    slides = Array.isArray(slides) ? slides.filter(isSafeHttpUrl) : [];
    if (slides.length < 2) return;

    const layerA = hero.querySelector('.service-hero-bg--a');
    const layerB = hero.querySelector('.service-hero-bg--b');
    if (!layerA || !layerB) return;

    const layers = [layerA, layerB];
    let activeLayer = 0;
    let currentIndex = 0;

    setInterval(() => {
        currentIndex = (currentIndex + 1) % slides.length;
        const nextLayer = activeLayer === 0 ? 1 : 0;

        layers[nextLayer].style.backgroundImage = "url('" + slides[currentIndex] + "')";
        layers[nextLayer].classList.add('is-visible');
        layers[activeLayer].classList.remove('is-visible');

        activeLayer = nextLayer;
    }, 5000);
});
