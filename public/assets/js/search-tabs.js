document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.search-sidebar-tab');
    const contents = document.querySelectorAll('.search-sidebar-content');
    const helpBadge = document.getElementById('keywords-help-badge');
    const helpTooltip = document.getElementById('keywords-help-tooltip');
    let tooltipTimer = null;

    const showTooltip = () => {
        if (!helpBadge || !helpTooltip) return;
        helpTooltip.classList.add('is-visible');
        helpBadge.setAttribute('aria-expanded', 'true');
    };

    const hideTooltip = () => {
        if (!helpBadge || !helpTooltip) return;
        helpTooltip.classList.remove('is-visible');
        helpBadge.setAttribute('aria-expanded', 'false');
    };

    const clearTooltipTimer = () => {
        if (!tooltipTimer) return;
        window.clearTimeout(tooltipTimer);
        tooltipTimer = null;
    };

    const scheduleTooltip = () => {
        clearTooltipTimer();
        tooltipTimer = window.setTimeout(showTooltip, 2000);
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tabTarget;

            // 1. Quitar activo de todas las pestañas
            tabs.forEach(t => t.classList.remove('is-active'));
            // 2. Ocultar todos los contenidos
            contents.forEach(c => c.classList.remove('is-active'));

            // 3. Activar el seleccionado
            tab.classList.add('is-active');
            document.getElementById(target).classList.add('is-active');
        });
    });

    if (helpBadge && helpTooltip) {
        helpBadge.addEventListener('mouseenter', scheduleTooltip);
        helpBadge.addEventListener('mouseleave', () => {
            clearTooltipTimer();
            hideTooltip();
        });

        helpBadge.addEventListener('focus', showTooltip);
        helpBadge.addEventListener('blur', hideTooltip);

        helpBadge.addEventListener('click', (event) => {
            event.preventDefault();
            clearTooltipTimer();

            const isOpen = helpTooltip.classList.contains('is-visible');
            if (isOpen) {
                hideTooltip();
                return;
            }

            showTooltip();
        });

        helpTooltip.addEventListener('mouseenter', clearTooltipTimer);
        helpTooltip.addEventListener('mouseleave', hideTooltip);

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) return;
            if (helpBadge.contains(target) || helpTooltip.contains(target)) return;
            clearTooltipTimer();
            hideTooltip();
        });
    }
});