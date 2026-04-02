/**
 * Módulo: booking.js (Actualizado)
 */

document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.getElementById('booking-form');
    const confirmBtn = document.getElementById('confirm-booking-btn');
    const showPromoLink = document.getElementById('show-promo-link');
    const applyPromoBtn = document.getElementById('apply-promo-btn');

    if (!bookingForm) return;

    // --- LÓGICA DE CÓDIGO PROMO ---
    if (showPromoLink) {
        showPromoLink.addEventListener('click', () => BookingUI.showPromoInput());
    }

    if (applyPromoBtn) {
        applyPromoBtn.addEventListener('click', async () => {
            const promoInput = document.getElementById('promo-code-input');
            const code = promoInput.value.trim();
            if (!code) return;

            const finalPriceDisplay = document.querySelector('.price-big');
            const originalPrice = parseFloat(finalPriceDisplay.innerText.replace(/[^\d]/g, ''));

            try {
                const response = await fetch(`${BASE_URL}${lang}/validate-promo`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `code=${code}`
                });

                const result = await response.json();

                if (result.success) {
                    let newPrice = originalPrice;
                    if (result.type === 'percentage') {
                        newPrice = originalPrice - (originalPrice * (result.value / 100));
                    } else {
                        newPrice = originalPrice - result.value;
                    }

                    BookingUI.updatePriceDisplay(originalPrice, newPrice);
                    BookingUI.setPromoMessage(result.message, false);
                    document.getElementById('final-promo-code').value = code;
                    document.getElementById('promo-input-wrapper').classList.add('is-hidden');
                } else {
                    BookingUI.setPromoMessage(result.message, true);
                }
            } catch (err) {
                console.error("Error validando promo:", err);
            }
        });
    }

    // --- LÓGICA DE RESUMEN ---
    bookingForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(bookingForm);
        
        // Obtenemos el precio que se ve actualmente (por si hay promo aplicada)
        const currentPriceText = document.querySelector('.price-big').lastChild.textContent;
        const pricePerNight = parseFloat(currentPriceText.replace(/[^\d]/g, ''));

        const diffDays = Math.ceil(Math.abs(new Date(formData.get('checkout')) - new Date(formData.get('checkin'))) / (1000 * 60 * 60 * 24)) || 1;

        let notes = [];
        if (formData.get('is_pregnant')) notes.push("Femme enceinte");
        if (formData.get('has_disabled')) notes.push("Accès PMR");

        BookingUI.updateSummary({
            checkin: formData.get('checkin'),
            checkout: formData.get('checkout'),
            adults: formData.get('adults'),
            children: formData.get('children'),
            totalPrice: (pricePerNight * diffDays),
            specialNotes: notes
        });
        BookingUI.openModal('summary-modal');
    });

    // --- LÓGICA DE ENVÍO FINAL ---
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            confirmBtn.disabled = true;
            confirmBtn.innerText = 'Envoi...';

            try {
                const response = await fetch(bookingForm.action, {
                    method: 'POST',
                    body: new FormData(bookingForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    BookingUI.closeModal('summary-modal');
                    BookingUI.openModal('booking-modal');
                    bookingForm.reset();
                }
            } catch (error) { console.error(error); } 
            finally {
                confirmBtn.disabled = false;
                confirmBtn.innerText = "Confirmer la réservation";
            }
        });
    }
});