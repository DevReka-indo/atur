const initializeProjectCreate = () => {
    const templateInput = document.getElementById('project_template_id');
    const selector = document.querySelector('[data-project-template-selector]');
    const modal = document.querySelector('[data-template-picker-modal]');
    const startDateInput = document.getElementById('start_date');
    const dueDateInput = document.getElementById('due_date');

    if (!templateInput || !selector || !modal || !startDateInput || !dueDateInput) {
        return;
    }

    const dialog = modal.querySelector('[data-template-picker-dialog]');
    const searchInput = modal.querySelector('[data-template-search]');
    const categoryFilter = modal.querySelector('[data-template-category-filter]');
    const options = [...modal.querySelectorAll('[data-template-option]')];
    const noResults = modal.querySelector('[data-template-no-results]');
    const emptyState = selector.querySelector('[data-template-empty-state]');
    const selectedState = selector.querySelector('[data-template-selected-state]');
    const selectedName = selector.querySelector('[data-selected-template-name]');
    const selectedDescription = selector.querySelector('[data-selected-template-description]');
    const selectedCategory = selector.querySelector('[data-selected-template-category]');
    const selectedVersion = selector.querySelector('[data-selected-template-version]');
    const selectedSummaries = new Map(
        [...selector.querySelectorAll('[data-selected-template-summary]')]
            .map((element) => [element.dataset.selectedTemplateSummary, element]),
    );
    let temporaryTemplateId = templateInput.value;
    let opener = null;

    const optionById = (templateId) => options.find(
        (option) => option.dataset.templateId === String(templateId),
    );

    const updateOptionSelection = () => {
        options.forEach((option) => {
            const isSelected = option.dataset.templateId === temporaryTemplateId;
            option.setAttribute('aria-pressed', String(isSelected));
            option.classList.toggle('border-indigo-600', isSelected);
            option.classList.toggle('bg-indigo-50/60', isSelected);
            option.classList.toggle('border-slate-200', !isSelected);
            option.querySelector('[data-template-selected-indicator]').classList.toggle('hidden', !isSelected);
            option.querySelector('[data-template-selected-indicator]').classList.toggle('flex', isSelected);
        });
    };

    const filterOptions = () => {
        const query = searchInput.value.trim().toLocaleLowerCase('id-ID');
        const categoryId = categoryFilter.value;
        let visibleCount = 0;

        options.forEach((option) => {
            const searchableText = [
                option.dataset.templateName,
                option.dataset.templateDescription,
                option.dataset.templateCategory,
            ].join(' ').toLocaleLowerCase('id-ID');
            const matchesQuery = searchableText.includes(query);
            const matchesCategory = !categoryId || option.dataset.templateCategoryId === categoryId;
            const isVisible = matchesQuery && matchesCategory;

            option.classList.toggle('hidden', !isVisible);
            visibleCount += isVisible ? 1 : 0;
        });

        noResults.classList.toggle('hidden', visibleCount > 0);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        opener?.focus();
    };

    const openModal = (event) => {
        opener = event.currentTarget;
        temporaryTemplateId = templateInput.value;
        searchInput.value = '';
        categoryFilter.value = '';
        filterOptions();
        updateOptionSelection();
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        window.requestAnimationFrame(() => searchInput.focus());
    };

    const updateSelectedCard = (option) => {
        const hasTemplate = option && option.dataset.templateId !== '';

        emptyState.classList.toggle('hidden', hasTemplate);
        selectedState.classList.toggle('hidden', !hasTemplate);

        if (!hasTemplate) {
            return;
        }

        selectedName.textContent = option.dataset.templateName;
        selectedDescription.textContent = option.dataset.templateDescription || 'Tanpa deskripsi.';
        selectedCategory.textContent = option.dataset.templateCategory;
        selectedVersion.textContent = `v${option.dataset.templateVersion}`;
        selectedSummaries.get('tasks').textContent = option.dataset.templateTasks;
        selectedSummaries.get('levels').textContent = option.dataset.templateLevels;
        selectedSummaries.get('duration').textContent = `${option.dataset.templateDuration} hari`;
        selectedSummaries.get('weight').textContent = option.dataset.templateWeight;
    };

    const applyTemplate = (option) => {
        const templateId = option?.dataset.templateId ?? '';

        templateInput.value = templateId;
        templateInput.dataset.previewUrl = option?.dataset.templatePreviewUrl ?? '';
        updateSelectedCard(option);
        templateInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    selector.querySelectorAll('[data-open-template-picker]').forEach((button) => {
        button.addEventListener('click', openModal);
    });

    selector.querySelector('[data-remove-template]').addEventListener('click', () => {
        applyTemplate(optionById(''));
    });

    modal.querySelectorAll('[data-close-template-picker]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.querySelector('[data-template-picker-backdrop]').addEventListener('click', closeModal);

    options.forEach((option) => {
        option.addEventListener('click', () => {
            temporaryTemplateId = option.dataset.templateId;
            updateOptionSelection();
        });
    });

    modal.querySelector('[data-confirm-template]').addEventListener('click', () => {
        applyTemplate(optionById(temporaryTemplateId));
        closeModal();
    });

    searchInput.addEventListener('input', filterOptions);
    categoryFilter.addEventListener('change', filterOptions);

    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = [...dialog.querySelectorAll(
            'button:not([disabled]):not(.hidden), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )].filter((element) => element.offsetParent !== null);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements.at(-1);

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    });

    const synchronizeDueDate = () => {
        dueDateInput.min = startDateInput.value;

        if (startDateInput.value && dueDateInput.value && dueDateInput.value < startDateInput.value) {
            dueDateInput.value = '';
            dueDateInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    startDateInput.addEventListener('change', synchronizeDueDate);
    synchronizeDueDate();
};

document.addEventListener('DOMContentLoaded', initializeProjectCreate);
