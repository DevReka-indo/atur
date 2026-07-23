const initializeProjectTemplateGallery = () => {
    const modal = document.getElementById('use-template-modal');

    if (!modal) {
        return;
    }

    const dialog = modal.querySelector('[data-template-modal-dialog]');
    const form = modal.querySelector('[data-use-template-form]');
    const templateIdInput = modal.querySelector('[data-project-template-id]');
    const projectNameInput = modal.querySelector('[data-project-name]');
    const startDateInput = modal.querySelector('[data-project-start-date]');
    const dueDateInput = modal.querySelector('[data-project-due-date]');
    const templateName = modal.querySelector('[data-selected-template-name]');
    const templateCategory = modal.querySelector('[data-selected-template-category]');
    const summaryTasks = modal.querySelector('[data-template-summary-tasks]');
    const summaryLevels = modal.querySelector('[data-template-summary-levels]');
    const summaryWeight = modal.querySelector('[data-template-summary-weight]');
    const summaryDuration = modal.querySelector('[data-template-summary-duration]');

    if (!dialog || !form || !templateIdInput || !projectNameInput || !startDateInput || !dueDateInput) {
        return;
    }

    let triggerElement = null;

    const setTemplateMetadata = (metadata) => {
        templateIdInput.value = metadata.id || '';
        templateName.textContent = metadata.name || 'Pilih template';
        templateCategory.textContent = metadata.category || 'Kategori';
        summaryTasks.textContent = metadata.tasks || '—';
        summaryLevels.textContent = metadata.levels || '—';
        summaryWeight.textContent = metadata.weight || '—';
        summaryDuration.textContent = metadata.duration ? `${metadata.duration} hari` : '—';
    };

    const openModal = (metadata, preserveInput = false) => {
        if (!preserveInput) {
            form.reset();
            form.elements.workspace_id.value = '';
            form.elements.name.value = '';
            form.elements.start_date.value = '';
            form.elements.due_date.value = '';
            form.elements.status.value = 'planning';
            form.elements.description.value = '';
        }

        setTemplateMetadata(metadata);
        dueDateInput.min = startDateInput.value;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        window.requestAnimationFrame(() => {
            projectNameInput.focus();
        });
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        triggerElement?.focus();
    };

    document.querySelectorAll('[data-use-template]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            triggerElement = trigger;
            event.preventDefault();
            openModal({
                id: trigger.dataset.templateId,
                name: trigger.dataset.templateName,
                category: trigger.dataset.templateCategory,
                tasks: trigger.dataset.templateTasks,
                levels: trigger.dataset.templateLevels,
                weight: trigger.dataset.templateWeight,
                duration: trigger.dataset.templateDuration,
            });
        });
    });

    modal.querySelectorAll('[data-template-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.querySelector('[data-template-modal-backdrop]')?.addEventListener('click', closeModal);

    startDateInput.addEventListener('change', () => {
        dueDateInput.min = startDateInput.value;

        if (dueDateInput.value && dueDateInput.value < startDateInput.value) {
            dueDateInput.value = '';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (modal.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = [...dialog.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )].filter((element) => !element.hasAttribute('hidden'));

        if (focusableElements.length === 0) {
            event.preventDefault();
            dialog.focus();
            return;
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    });

    if (modal.dataset.reopen === 'true') {
        openModal({
            id: modal.dataset.restoredTemplateId,
            name: modal.dataset.restoredTemplateName,
            category: modal.dataset.restoredTemplateCategory,
            tasks: modal.dataset.restoredTemplateTasks,
            levels: modal.dataset.restoredTemplateLevels,
            weight: modal.dataset.restoredTemplateWeight,
            duration: modal.dataset.restoredTemplateDuration,
        }, true);
    }
};

document.addEventListener('DOMContentLoaded', initializeProjectTemplateGallery);
