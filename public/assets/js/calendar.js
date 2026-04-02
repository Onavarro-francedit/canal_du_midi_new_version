/**
 * Módulo: calendar.js (Versión Blindada)
 */

document.addEventListener('DOMContentLoaded', () => {
    const normalizeOccupiedDates = (payload) => {
        if (Array.isArray(payload)) return payload;

        if (payload && Array.isArray(payload.occupiedDates)) {
            return payload.occupiedDates;
        }

        if (payload && typeof payload === 'object') {
            return Object.values(payload).filter((value) => typeof value === 'string');
        }

        return [];
    };

    const openCalendarModal = () => {
        if (typeof BookingUI !== 'undefined') {
            BookingUI.openModal('calendar-modal');
            return;
        }

        const modal = document.getElementById('calendar-modal');
        if (!modal) return;

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('is-active'), 10);
        document.body.style.overflow = 'hidden';
    };

    // 1. Captura de elementos
    const calendarDays = document.getElementById('calendar-days');
    const monthDisplay = document.getElementById('current-month-display');
    const prevBtn = document.getElementById('prev-month');
    const nextBtn = document.getElementById('next-month');
    const applyBtn = document.getElementById('apply-dates-btn');
    const dateBtnTrigger = document.getElementById('select-dates-trigger');

    // Si no estamos en una página con calendario, salimos sin dar error
    if (!calendarDays || !dateBtnTrigger) return;

    let currentMonth = new Date();
    let selectedStart = null;
    let selectedEnd = null;
    let occupiedDates = [];

    // 2. Función para renderizar los días
    const renderCalendar = () => {
        if (!calendarDays || !monthDisplay) return;

        calendarDays.innerHTML = ''; // Limpiar días anteriores
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        
        // Mostrar mes y año actual
        monthDisplay.innerText = new Intl.DateTimeFormat(lang, { month: 'long', year: 'numeric' }).format(currentMonth);

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const startOffset = (firstDay === 0) ? 6 : firstDay - 1;

        // Días vacíos iniciales
        for (let i = 0; i < startOffset; i++) {
            const emptyDiv = document.createElement('div');
            calendarDays.appendChild(emptyDiv);
        }

        // Pintar los días del mes
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isOccupied = occupiedDates.includes(dateStr);
            const isPast = new Date(dateStr) < new Date().setHours(0,0,0,0);
            
            const dayEl = document.createElement('div');
            dayEl.className = 'day';
            if (isOccupied) dayEl.classList.add('occupied', 'disabled');
            if (isPast) dayEl.classList.add('disabled');
            if (selectedStart === dateStr || selectedEnd === dateStr) dayEl.classList.add('selected');
            if (selectedStart && selectedEnd && dateStr > selectedStart && dateStr < selectedEnd) dayEl.classList.add('in-range');

            dayEl.innerText = d;
            
            if (!isOccupied && !isPast) {
                dayEl.addEventListener('click', () => selectDate(dateStr));
            }
            calendarDays.appendChild(dayEl);
        }
    };

    // 3. Lógica de selección
    const selectDate = (date) => {
        if (!selectedStart || (selectedStart && selectedEnd)) {
            selectedStart = date;
            selectedEnd = null;
        } else if (date > selectedStart) {
            selectedEnd = date;
        } else {
            selectedStart = date;
        }
        
        if (applyBtn) applyBtn.disabled = !(selectedStart && selectedEnd);
        renderCalendar();
    };

    // 4. Listeners de eventos
    dateBtnTrigger.addEventListener('click', async () => {
        const sidInput = document.querySelector('input[name="service_id"]');
        if (!sidInput) return;

        try {
            const res = await fetch(`${BASE_URL}${lang}/get-booked-dates?service_id=${sidInput.value}`);
            const payload = await res.json();
            occupiedDates = normalizeOccupiedDates(payload);
            renderCalendar();
            openCalendarModal();
        } catch (err) {
            console.error("Error cargando fechas ocupadas:", err);
            occupiedDates = [];
            renderCalendar();
            openCalendarModal();
        }
    });

    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            const checkinInput = document.querySelector('input[name="checkin"]');
            const checkoutInput = document.querySelector('input[name="checkout"]');
            
            if (checkinInput && checkoutInput) {
                checkinInput.value = selectedStart;
                checkoutInput.value = selectedEnd;
                dateBtnTrigger.innerHTML = `<i class="bi bi-calendar-check"></i> ${selectedStart} ➔ ${selectedEnd}`;
            }
            closeCalendarModal();
        });
    }

    if (prevBtn) prevBtn.addEventListener('click', () => {
        currentMonth.setMonth(currentMonth.getMonth() - 1);
        renderCalendar();
    });

    if (nextBtn) nextBtn.addEventListener('click', () => {
        currentMonth.setMonth(currentMonth.getMonth() + 1);
        renderCalendar();
    });
});

// Funciones globales para botones onclick
window.closeCalendarModal = () => {
    if (typeof BookingUI !== 'undefined') {
        BookingUI.closeModal('calendar-modal');
        return;
    }

    const modal = document.getElementById('calendar-modal');
    if (!modal) return;

    modal.classList.remove('is-active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
    document.body.style.overflow = 'auto';
};