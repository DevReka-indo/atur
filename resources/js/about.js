const initializePrivacyDocument = () => {
    const modal = document.querySelector('[data-privacy-modal]');
    let modalTrigger = null;

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'select:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    const closeModal = () => {
        if (!modal || modal.classList.contains('hidden')) {
            return;
        }

        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        modalTrigger?.focus();
    };

    const openModal = (trigger) => {
        if (!modal) {
            return;
        }

        modalTrigger = trigger;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        modal.querySelector('[data-privacy-modal-close]')?.focus();
    };

    document.querySelectorAll('[data-privacy-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger));
    });

    modal?.querySelectorAll('[data-privacy-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });
    modal?.querySelector('[data-privacy-modal-overlay]')?.addEventListener('click', closeModal);

    modal?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = [...modal.querySelectorAll(focusableSelector)]
            .filter((element) => element.getClientRects().length > 0);
        const first = focusable.at(0);
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    document.querySelectorAll('[data-privacy-toc-link]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const sectionId = link.getAttribute('href')?.slice(1);
            const scope = link.closest('[data-privacy-modal]') ?? document;
            const section = sectionId ? scope.querySelector(`#${sectionId}`) : null;

            if (!section) {
                return;
            }

            event.preventDefault();
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            section.setAttribute('tabindex', '-1');
            section.focus({ preventScroll: true });
        });
    });

    document.querySelectorAll('[data-privacy-section-select]').forEach((select) => {
        select.addEventListener('change', () => {
            const scope = select.closest('[data-privacy-modal]') ?? document;
            const section = select.value ? scope.querySelector(`#${select.value}`) : null;

            section?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
};

document.addEventListener('DOMContentLoaded', initializePrivacyDocument);
