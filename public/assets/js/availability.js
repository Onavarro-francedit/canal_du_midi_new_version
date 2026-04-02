/**
 * Módulo: availability.js
 * Responsabilidad: Validación de fechas en tiempo real mediante AJAX.
 */

document.addEventListener('DOMContentLoaded', () => {
    const checkinInput = document.querySelector('input[name="checkin"]');
    const checkoutInput = document.querySelector('input[name="checkout"]');
    const bookingForm = document.getElementById('booking-form');
    const submitBtn = bookingForm?.querySelector('button[type="submit"]');

    if (checkinInput && checkoutInput) {
        
        const checkDates = async () => {
            const start = checkinInput.value;
            const end = checkoutInput.value;
            const sid = document.querySelector('input[name="service_id"]').value;

            // Solo validamos si ambas fechas están puestas y la de salida es posterior a la de entrada
            if (start && end && start < end) {
                
                BookingUI.setPromoMessage("Vérification de la disponibilité...", false);
                submitBtn.disabled = true;

                try {
                    const response = await fetch(`${BASE_URL}${lang}/check-availability`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `service_id=${sid}&checkin=${start}&checkout=${end}`
                    });

                    const result = await response.json();

                    if (result.available) {
                        BookingUI.setPromoMessage("Dates disponibles ! ✨", false);
                        submitBtn.disabled = false;
                    } else {
                        BookingUI.setPromoMessage("Désolé, ces dates sont déjà réservées. ❌", true);
                        submitBtn.disabled = true;
                    }
                } catch (error) {
                    console.error("Error validando disponibilidad:", error);
                }
            }
        };

        checkinInput.addEventListener('change', checkDates);
        checkoutInput.addEventListener('change', checkDates);
    }
});