/**
 * Módulo: booking-ui.js (Actualizado)
 */

const BookingUI = {
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('is-active'), 10);
        document.body.style.overflow = 'hidden';
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('is-active');
        setTimeout(() => modal.style.display = 'none', 300);
        document.body.style.overflow = 'auto';
    },

    // --- NUEVO: Gestión visual de la Promo ---
    showPromoInput() {
        const link = document.getElementById('show-promo-link');
        const wrapper = document.getElementById('promo-input-wrapper');
        if (link && wrapper) {
            link.style.display = 'none';
            wrapper.classList.remove('is-hidden');
        }
    },

    updatePriceDisplay(original, discounted) {
        const display = document.querySelector('.price-big');
        if (display) {
            display.innerHTML = `<span class="price-old">${original} €</span> ${discounted.toFixed(0)} €`;
        }
    },

    setPromoMessage(text, isError = false) {
        const msg = document.getElementById('promo-message');
        if (msg) {
            msg.innerText = text;
            msg.className = isError ? 'promo-feedback error' : 'promo-feedback success';
        }
    },

    // --- Resumen ---
    updateSummary(data) {
        document.getElementById('sum-checkin').innerText = data.checkin;
        document.getElementById('sum-checkout').innerText = data.checkout;
        document.getElementById('sum-guests').innerText = `${data.adults} Ad., ${data.children} Enf.`;
        document.getElementById('sum-total').innerText = `${data.totalPrice} €`;

        const specialBox = document.getElementById('sum-special');
        if (data.specialNotes.length > 0) {
            specialBox.classList.remove('is-hidden');
            document.getElementById('sum-special-text').innerText = data.specialNotes.join(', ');
        } else {
            specialBox.classList.add('is-hidden');
        }
    }
};

window.closeBookingModal = () => {
    BookingUI.closeModal('booking-modal');
};

window.closeSummaryModal = () => {
    BookingUI.closeModal('summary-modal');
};

window.showPromoInput = () => {
    BookingUI.showPromoInput();
};

// Listener para cerrar modales al hacer clic fuera (opcional pero recomendado)
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        BookingUI.closeModal(e.target.id);
    }
});