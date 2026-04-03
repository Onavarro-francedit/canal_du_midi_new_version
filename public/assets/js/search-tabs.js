document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.search-sidebar-tab');
    const contents = document.querySelectorAll('.search-sidebar-content');

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
});