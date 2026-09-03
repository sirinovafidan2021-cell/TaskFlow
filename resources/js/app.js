const mobileNavigation = document.querySelector('[data-mobile-nav]');
const mobileNavigationToggle = document.querySelector('[data-mobile-nav-toggle]');
const mobileNavigationClose = document.querySelector('[data-mobile-nav-close]');
const mobileNavigationBackdrop = document.querySelector('[data-mobile-nav-backdrop]');

if (mobileNavigation && mobileNavigationToggle && mobileNavigationBackdrop) {
    const closeNavigation = () => {
        mobileNavigation.classList.add('-translate-x-full');
        mobileNavigationBackdrop.classList.add('hidden');
        mobileNavigationToggle.setAttribute('aria-expanded', 'false');
    };

    const openNavigation = () => {
        mobileNavigation.classList.remove('-translate-x-full');
        mobileNavigationBackdrop.classList.remove('hidden');
        mobileNavigationToggle.setAttribute('aria-expanded', 'true');
        mobileNavigationClose?.focus();
    };

    mobileNavigationToggle.addEventListener('click', openNavigation);
    mobileNavigationClose?.addEventListener('click', closeNavigation);
    mobileNavigationBackdrop.addEventListener('click', closeNavigation);
    mobileNavigation.querySelectorAll('[data-mobile-nav-link]').forEach((link) => {
        link.addEventListener('click', closeNavigation);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileNavigationToggle.getAttribute('aria-expanded') === 'true') {
            closeNavigation();
            mobileNavigationToggle.focus();
        }
    });
}

const confirmationModal = document.querySelector('[data-confirm-modal]');

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();

        if (!confirmationModal?.showModal) {
            if (window.confirm(form.dataset.confirm)) {
                form.dataset.confirmed = 'true';
                form.requestSubmit();
            }

            return;
        }

        confirmationModal.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        confirmationModal.showModal();
        confirmationModal.querySelector('[data-confirm-submit]').onclick = () => {
            confirmationModal.close();
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        };
    });
});
