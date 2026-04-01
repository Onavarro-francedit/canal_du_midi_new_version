/**
 * Módulo: Lightbox.js
 * Funcionalidad: Visor de imágenes premium para la galería del Canal du Midi.
 */

document.addEventListener('DOMContentLoaded', () => {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const triggers = document.querySelectorAll('.lightbox-trigger');
    const closeBtn = document.querySelector('.lightbox-close');
    const prevBtn = document.querySelector('.lightbox-prev');
    const nextBtn = document.querySelector('.lightbox-next');

    let currentIndex = 0;
    const images = Array.from(triggers).map(img => img.src);

    if (triggers.length > 0 && lightbox) {
        
        // --- ABRIR ---
        triggers.forEach((trigger, index) => {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                currentIndex = index;
                updateImage();
                lightbox.classList.add('is-active');
                lightbox.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Bloquear scroll fondo
            });
        });

        // --- NAVEGACIÓN ---
        const updateImage = () => {
            lightboxImg.src = images[currentIndex];
            // Efecto de entrada suave
            lightboxImg.style.opacity = 0;
            setTimeout(() => { lightboxImg.style.opacity = 1; }, 50);
        };

        const showNext = (e) => {
            if (e) e.stopPropagation();
            currentIndex = (currentIndex + 1) % images.length;
            updateImage();
        };

        const showPrev = (e) => {
            if (e) e.stopPropagation();
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateImage();
        };

        const closeLightbox = () => {
            lightbox.style.display = 'none';
            lightbox.classList.remove('is-active');
            document.body.style.overflow = 'auto';
        };

        // Eventos de botones
        nextBtn.addEventListener('click', showNext);
        prevBtn.addEventListener('click', showPrev);
        closeBtn.addEventListener('click', closeLightbox);
        
        // Cerrar al hacer clic fuera de la imagen
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox || e.target === lightbox.querySelector('.lightbox-content')) {
                closeLightbox();
            }
        });

        // --- SOPORTE TECLADO ---
        document.addEventListener('keydown', (e) => {
            if (lightbox.style.display === 'flex') {
                if (e.key === 'ArrowRight') showNext();
                if (e.key === 'ArrowLeft') showPrev();
                if (e.key === 'Escape') closeLightbox();
            }
        });
    }
});