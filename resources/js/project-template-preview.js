const createElement = (tagName, classNames = [], text = null) => {
    const element = document.createElement(tagName);
    element.classList.add(...classNames);

    if (text !== null) {
        element.textContent = text;
    }

    return element;
};

const formatDate = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${value}T00:00:00Z`));
};

const formatNumber = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
}).format(value);

const initializeProjectTemplatePreview = () => {
    const templateSelect = document.getElementById('project_template_id');
    const preview = document.querySelector('[data-project-template-preview]');
    const startDateInput = document.getElementById('start_date');
    const dueDateInput = document.getElementById('due_date');

    if (!templateSelect || !preview || !startDateInput || !dueDateInput) {
        return;
    }

    const states = new Map(
        [...preview.querySelectorAll('[data-preview-state]')]
            .map((element) => [element.dataset.previewState, element]),
    );
    let activeRequest = null;

    const showState = (stateName) => {
        states.forEach((element, name) => {
            element.classList.toggle('hidden', name !== stateName);
        });
    };

    const updateMinimumDueDate = () => {
        if (startDateInput.value) {
            dueDateInput.min = startDateInput.value;
        } else {
            dueDateInput.removeAttribute('min');
        }
    };

    const appendTaskNode = (task, container) => {
        const indentationClasses = [
            ['border-slate-200'],
            ['ml-3', 'border-indigo-200', 'sm:ml-5'],
            ['ml-6', 'border-sky-200', 'sm:ml-10'],
        ];
        const wrapper = createElement('div', [
            'rounded-xl',
            'border-l-4',
            'bg-white',
            'p-3',
            'shadow-sm',
            ...(indentationClasses[Math.min(task.depth, 2)]),
        ]);
        const header = createElement('div', ['flex', 'flex-wrap', 'items-start', 'justify-between', 'gap-2']);
        const identity = createElement('div', ['min-w-0']);
        const titleRow = createElement('div', ['flex', 'flex-wrap', 'items-center', 'gap-2']);
        const icon = createElement('i', [
            'fa-solid',
            task.is_leaf ? 'fa-circle-check' : 'fa-folder-tree',
            task.is_leaf ? 'text-sky-500' : 'text-indigo-500',
            'text-xs',
        ]);
        const title = createElement('p', ['break-words', 'text-sm', 'font-semibold', 'text-slate-900'], task.name);
        const badge = createElement(
            'span',
            [
                'rounded-full',
                'px-2',
                'py-0.5',
                'text-[11px]',
                'font-semibold',
                task.is_leaf ? 'bg-sky-50' : 'bg-indigo-50',
                task.is_leaf ? 'text-sky-700' : 'text-indigo-700',
            ],
            task.is_leaf ? 'Leaf' : 'Parent',
        );
        titleRow.append(icon, title, badge);
        identity.append(titleRow);

        if (task.description) {
            identity.append(createElement('p', ['mt-1', 'text-xs', 'leading-5', 'text-slate-500'], task.description));
        }

        const weightLabel = task.is_leaf
            ? `Weight ${formatNumber(task.weight)}`
            : `Beban turunan ${formatNumber(task.aggregate_weight)}`;
        const metadata = [
            weightLabel,
            `Hari ke-${task.start_offset_days + 1}`,
            `Durasi ${task.duration_days} hari`,
        ];

        if (task.start_date && task.due_date) {
            metadata.push(`${formatDate(task.start_date, '')}–${formatDate(task.due_date, '')}`);
        }

        identity.append(createElement('p', ['mt-2', 'text-xs', 'font-medium', 'text-slate-600'], metadata.join(' · ')));
        header.append(identity);
        wrapper.append(header);

        if (task.predecessor) {
            wrapper.append(createElement(
                'p',
                ['mt-2', 'text-xs', 'font-medium', 'text-indigo-600'],
                `${task.predecessor.dependency_type} dari ${task.predecessor.name} · Lag ${task.predecessor.lag_days} hari`,
            ));
        }

        container.append(wrapper);
        task.children.forEach((child) => appendTaskNode(child, container));
    };

    const renderPreview = (data) => {
        preview.querySelector('[data-preview-name]').textContent = data.name;
        preview.querySelector('[data-preview-meta]').textContent = `${data.category} · Version ${data.version}`;
        preview.querySelector('[data-preview-description]').textContent = data.description || 'Tanpa deskripsi.';

        const summaryValues = {
            tasks: data.summary.tasks_count,
            roots: data.summary.root_tasks_count,
            leaves: data.summary.leaf_tasks_count,
            levels: data.summary.hierarchy_levels,
            weight: formatNumber(data.summary.total_leaf_weight),
            duration: `${data.summary.duration_days} hari`,
        };

        Object.entries(summaryValues).forEach(([key, value]) => {
            preview.querySelector(`[data-summary="${key}"]`).textContent = value;
        });

        preview.querySelector('[data-timeline="start"]').textContent = formatDate(
            data.timeline.project_start_date,
            'Belum diisi',
        );
        preview.querySelector('[data-timeline="requested"]').textContent = formatDate(
            data.timeline.requested_due_date,
            'Belum diisi',
        );
        preview.querySelector('[data-timeline="estimated"]').textContent = formatDate(
            data.timeline.estimated_end_date,
            'Isi start date',
        );
        preview.querySelector('[data-timeline-warning]').classList.toggle(
            'hidden',
            !data.timeline.will_extend_project,
        );

        const tree = preview.querySelector('[data-preview-tree]');
        tree.replaceChildren();
        data.tasks.forEach((task) => appendTaskNode(task, tree));
        showState('content');
    };

    const loadPreview = async () => {
        activeRequest?.abort();
        activeRequest = null;

        const selectedOption = templateSelect.options[templateSelect.selectedIndex];
        if (!selectedOption?.dataset.previewUrl) {
            showState('empty');
            return;
        }

        const requestController = new AbortController();
        activeRequest = requestController;
        const previewUrl = new URL(selectedOption.dataset.previewUrl, window.location.origin);

        if (startDateInput.value) {
            previewUrl.searchParams.set('start_date', startDateInput.value);
        }

        if (dueDateInput.value) {
            previewUrl.searchParams.set('due_date', dueDateInput.value);
        }

        showState('loading');

        try {
            const response = await fetch(previewUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Preview template tidak tersedia.');
            }

            if (activeRequest !== requestController) {
                return;
            }

            renderPreview(payload);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            preview.querySelector('[data-preview-error-message]').textContent =
                error.message || 'Silakan periksa tanggal atau pilih kembali template.';
            showState('error');
        } finally {
            if (activeRequest === requestController) {
                activeRequest = null;
            }
        }
    };

    templateSelect.addEventListener('change', loadPreview);
    startDateInput.addEventListener('change', () => {
        updateMinimumDueDate();
        loadPreview();
    });
    dueDateInput.addEventListener('change', loadPreview);

    updateMinimumDueDate();
    loadPreview();
};

document.addEventListener('DOMContentLoaded', initializeProjectTemplatePreview);
