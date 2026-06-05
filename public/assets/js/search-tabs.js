document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.search-sidebar-tab');
    const contents = document.querySelectorAll('.search-sidebar-content');
    const helpBadge = document.getElementById('keywords-help-badge');
    const helpTooltip = document.getElementById('keywords-help-tooltip');
    const typeCustom = document.getElementById('search-type-custom');
    const typeHiddenContainer = document.getElementById('search-type-hidden-container');
    const typeTrigger = document.getElementById('search-type-trigger');
    const typeValue = document.getElementById('search-type-value');
    const typeDropdown = document.getElementById('search-type-dropdown');
    const typeFilter = document.getElementById('search-type-filter');
    const typeOptions = Array.from(document.querySelectorAll('.modern-type-option'));
    let tooltipTimer = null;

    const typeInitial = (() => {
        try { return JSON.parse(typeCustom ? (typeCustom.dataset.selected || '[]') : '[]'); }
        catch (e) { return []; }
    })();
    const selectedTypes = new Set(typeInitial);

    const openTypeDropdown = () => {
        if (!typeCustom || !typeDropdown || !typeTrigger) return;
        typeCustom.classList.add('is-open');
        typeDropdown.hidden = false;
        typeTrigger.setAttribute('aria-expanded', 'true');
        if (typeFilter) {
            typeFilter.value = '';
            filterTypeOptions('');
            typeFilter.focus();
        }
    };

    const closeTypeDropdown = () => {
        if (!typeCustom || !typeDropdown || !typeTrigger) return;
        typeCustom.classList.remove('is-open');
        typeDropdown.hidden = true;
        typeTrigger.setAttribute('aria-expanded', 'false');
    };

    const filterTypeOptions = (term) => {
        const normalized = String(term || '').toLowerCase().trim();
        typeOptions.forEach((option) => {
            const label = String(option.dataset.label || '').toLowerCase();
            option.hidden = normalized !== '' && !label.includes(normalized);
        });
    };

    const syncHiddenInputs = () => {
        if (!typeHiddenContainer) return;
        typeHiddenContainer.innerHTML = '';
        selectedTypes.forEach((value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'type[]';
            input.value = value;
            typeHiddenContainer.appendChild(input);
        });
    };

    const updateTriggerText = () => {
        if (!typeValue) return;
        if (selectedTypes.size === 0) {
            typeValue.textContent = 'Tous les types';
        } else if (selectedTypes.size === 1) {
            const slug = [...selectedTypes][0];
            const option = typeOptions.find((o) => o.dataset.value === slug);
            typeValue.textContent = option ? (option.dataset.label || slug) : slug;
        } else {
            typeValue.textContent = selectedTypes.size + ' type(s) sélectionné(s)';
        }
    };

    const updateOptionStates = () => {
        typeOptions.forEach((option) => {
            const value = option.dataset.value || '';
            const isSelected = value === '' ? selectedTypes.size === 0 : selectedTypes.has(value);
            option.classList.toggle('is-selected', isSelected);
            option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        });
    };

    const toggleTypeOption = (option) => {
        const value = option.dataset.value || '';
        if (value === '') {
            selectedTypes.clear();
        } else {
            if (selectedTypes.has(value)) {
                selectedTypes.delete(value);
            } else {
                selectedTypes.add(value);
            }
        }
        syncHiddenInputs();
        updateTriggerText();
        updateOptionStates();
    };

    if (typeCustom && typeHiddenContainer && typeTrigger && typeDropdown && typeValue && typeOptions.length > 0) {
        updateOptionStates();
        updateTriggerText();

        typeTrigger.addEventListener('click', () => {
            typeDropdown.hidden ? openTypeDropdown() : closeTypeDropdown();
        });

        typeTrigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
                event.preventDefault();
                openTypeDropdown();
            }
        });

        typeOptions.forEach((option) => {
            option.addEventListener('click', () => {
                toggleTypeOption(option);
                if ((option.dataset.value || '') === '') {
                    closeTypeDropdown();
                }
            });
        });

        if (typeFilter) {
            typeFilter.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                filterTypeOptions(target.value);
            });

            typeFilter.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeTypeDropdown();
                    typeTrigger.focus();
                }
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const firstVisible = typeOptions.find((option) => !option.hidden);
                    if (firstVisible) toggleTypeOption(firstVisible);
                }
            });
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) return;
            if (typeCustom.contains(target)) return;
            closeTypeDropdown();
        });
    }

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