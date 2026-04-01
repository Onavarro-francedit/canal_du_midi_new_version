/**
 * Módulo: booking.js
 * Funcionalidad: Envío de reservas mediante AJAX y gestión del modal de éxito.
 */

document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.getElementById('booking-form');

    if (bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Evitar que la página se recargue

            // 1. Obtener los datos del formulario
            const formData = new FormData(bookingForm);
            const submitButton = bookingForm.querySelector('button[type="submit"]');
            
            // Bloquear botón para evitar doble clic
            submitButton.disabled = true;
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Envoi en cours...';

            try {
                // 2. Enviar petición al controlador de PHP
                const response = await fetch(bookingForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Avisar a PHP que es una petición AJAX
                    }
                });

                if (!response.ok) throw new Error('Error en la respuesta del servidor');

                const result = await response.json();

                // 3. Procesar la respuesta
                if (result.success) {
                    showBookingModal();
                    bookingForm.reset(); // Limpiar campos del formulario
                } else {
                    alert("Oups ! Une erreur est survenue lors de l'envoi. Veuillez réessayer.");
                }

            } catch (error) {
                console.error('Error al enviar la reserva:', error);
                alert("Impossible de contacter le serveur. Vérifiez votre connexion.");
            } finally {
                // Restaurar el botón
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }

    // Cerrar modal al hacer clic fuera del contenido
    const modalOverlay = document.getElementById('booking-modal');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                closeBookingModal();
            }
        });
    }
});

/**
 * Función global para mostrar el modal de éxito
 */
function showBookingModal() {
    const modal = document.getElementById('booking-modal');
    if (modal) {
        modal.style.display = 'flex';
        // Pequeño delay para que la transición CSS de opacidad funcione
        setTimeout(() => {
            modal.classList.add('is-active');
        }, 10);
        document.body.style.overflow = 'hidden'; // Bloquear scroll de la web
    }
}

/**
 * Función global para cerrar el modal (llamada desde el botón del modal)
 */
function closeBookingModal() {
    const modal = document.getElementById('booking-modal');
    if (modal) {
        modal.classList.remove('is-active');
        // Esperar a que termine la animación CSS para ocultar el display
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
        document.body.style.overflow = 'auto'; // Restaurar scroll
    }
}