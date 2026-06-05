const menuToggle = document.querySelector('.menu-toggle');
const mainNav = document.querySelector('.main-nav');
const navBackdrop = document.querySelector('.nav-backdrop');
const yearNode = document.querySelector('#current-year');

if (yearNode) {
    yearNode.textContent = new Date().getFullYear();
}

if (menuToggle && mainNav) {
    const closeMenu = () => {
        mainNav.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('nav-open');
        document.body.style.overflow = '';
    };

    const openMenu = () => {
        mainNav.classList.add('is-open');
        menuToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('nav-open');
        document.body.style.overflow = 'hidden';
    };

    menuToggle.addEventListener('click', () => {
        const isOpen = mainNav.classList.contains('is-open');
        if (isOpen) {
            closeMenu();
            return;
        }

        openMenu();
    });

    navBackdrop?.addEventListener('click', closeMenu);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 820) {
            closeMenu();
        }
    });

    mainNav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });
}