const initializeNotificationsPage = () => {
    const root = document.querySelector('[data-notifications-page]');
    if (!root) {
        return;
    }

    const checkboxes = () => [...root.querySelectorAll('[data-notification-checkbox]')];
    const selectedCheckboxes = () => checkboxes().filter((checkbox) => checkbox.checked);
    const selectAll = root.querySelector('[data-notification-select-all]');
    const bulkForm = root.querySelector('[data-notification-bulk-form]');
    const bulkSubmit = root.querySelector('[data-notification-bulk-submit]');
    const selectedCount = root.querySelector('[data-notification-selected-count]');

    const updateSelection = () => {
        const all = checkboxes();
        const selected = selectedCheckboxes();
        const count = selected.length;

        selectedCount.textContent = String(count);
        bulkSubmit.disabled = count === 0;

        if (selectAll) {
            selectAll.checked = all.length > 0 && count === all.length;
            selectAll.indeterminate = count > 0 && count < all.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        checkboxes().forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateSelection();
    });

    root.addEventListener('change', (event) => {
        if (event.target.matches('[data-notification-checkbox]')) {
            updateSelection();
        }
    });

    bulkForm?.addEventListener('submit', (event) => {
        const selected = selectedCheckboxes();
        if (selected.length === 0
            || !window.confirm(bulkForm.dataset.confirm)) {
            event.preventDefault();
            return;
        }

        bulkForm.querySelectorAll('[data-generated-notification-id]').forEach(
            (input) => input.remove(),
        );
        selected.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'notification_ids[]';
            input.value = checkbox.value;
            input.dataset.generatedNotificationId = '';
            bulkForm.append(input);
        });
    });

    root.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    const isInteractiveTarget = (target) => target.closest(
        'a, button, input, label, form, summary, details',
    );

    root.querySelectorAll('[data-notification-card]').forEach((card) => {
        const openCard = () => {
            if (card.dataset.notificationUrl) {
                window.location.assign(card.dataset.notificationUrl);
            }
        };

        card.addEventListener('click', (event) => {
            if (!isInteractiveTarget(event.target)) {
                openCard();
            }
        });
        card.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ')
                && !isInteractiveTarget(event.target)) {
                event.preventDefault();
                openCard();
            }
        });
    });

    document.addEventListener('click', (event) => {
        root.querySelectorAll('[data-notification-menu][open]').forEach((menu) => {
            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            root.querySelectorAll('[data-notification-menu][open]').forEach(
                (menu) => menu.removeAttribute('open'),
            );
        }
    });

    updateSelection();
};

document.addEventListener('DOMContentLoaded', initializeNotificationsPage);
