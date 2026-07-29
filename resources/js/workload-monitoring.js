const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

let activeModal = null;
let returnFocusTarget = null;

function openModal(modal, trigger) {
    if (!modal) {
        return;
    }

    activeModal = modal;
    returnFocusTarget = trigger;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    modal.querySelector('[data-workload-modal-panel]')?.focus();
}

function closeModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    activeModal = null;
    returnFocusTarget?.focus();
    returnFocusTarget = null;
}

function createElement(tagName, className, text) {
    const element = document.createElement(tagName);

    if (className) {
        element.className = className;
    }

    if (text !== undefined && text !== null) {
        element.appendChild(document.createTextNode(String(text)));
    }

    return element;
}

function appendMetric(container, label, value, className = 'text-slate-900') {
    const wrapper = createElement('div');
    wrapper.appendChild(createElement('dt', 'text-xs text-slate-500', label));
    wrapper.appendChild(createElement('dd', `mt-1 font-semibold ${className}`, value));
    container.appendChild(wrapper);
}

function renderMember(container, member) {
    container.replaceChildren();

    const wrapper = createElement('div', 'flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between');
    const identity = createElement('div');
    identity.appendChild(createElement('h3', 'text-lg font-bold text-slate-900', member.name));
    identity.appendChild(createElement('p', 'mt-1 text-sm text-slate-500', member.employee_id || member.email));
    identity.appendChild(createElement('p', 'mt-2 text-sm leading-6 text-slate-600', member.reason));

    const score = createElement('div', 'shrink-0 rounded-2xl bg-white p-4 text-left shadow-sm sm:text-right');
    score.appendChild(createElement('p', 'text-xs font-medium uppercase tracking-wide text-slate-500', 'Skor Beban Tugas'));
    score.appendChild(createElement('p', 'mt-1 text-3xl font-bold text-slate-900', Number(member.score).toFixed(2)));
    score.appendChild(createElement('p', 'mt-1 text-xs font-semibold text-slate-600', member.level_label));

    wrapper.append(identity, score);
    container.appendChild(wrapper);

    const metrics = createElement('dl', 'mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4');
    appendMetric(metrics, 'Task', member.contributing_task_count);
    appendMetric(metrics, 'Project', member.contributing_project_count);
    appendMetric(metrics, 'Overdue', member.overdue_count, 'text-rose-700');
    appendMetric(metrics, 'Unscheduled', member.unscheduled_count);
    container.appendChild(metrics);
}

function renderProjects(container, projects) {
    container.replaceChildren();

    if (projects.length === 0) {
        container.appendChild(createElement('p', 'text-sm text-slate-500', 'Tidak ada project yang berkontribusi.'));
        return;
    }

    projects.forEach((project) => {
        const card = createElement('article', 'rounded-xl border border-slate-200 bg-white p-4');
        const identity = createElement('div', 'flex min-w-0 items-center gap-1.5');
        const icon = createElement('i', 'fa-solid fa-folder-open shrink-0 text-xs text-slate-400');
        const link = createElement('a', 'block min-w-0 truncate font-semibold text-slate-900 hover:text-sky-700', project.name);
        icon.setAttribute('aria-hidden', 'true');
        link.href = project.url;
        identity.append(icon, link);
        card.appendChild(identity);

        const metrics = createElement('dl', 'mt-3 grid grid-cols-2 gap-3');
        appendMetric(metrics, 'Skor', Number(project.score).toFixed(2));
        appendMetric(metrics, 'Task', project.task_count);
        appendMetric(metrics, 'Overdue', project.overdue_count, 'text-rose-700');
        appendMetric(metrics, 'Unscheduled', project.unscheduled_count);
        card.appendChild(metrics);
        container.appendChild(card);
    });
}

function renderTasks(container, tasks, emptyMessage) {
    container.replaceChildren();

    if (tasks.length === 0) {
        container.appendChild(createElement('p', 'rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500', emptyMessage));
        return;
    }

    tasks.forEach((task) => {
        const card = createElement('article', 'rounded-xl border border-slate-200 bg-white p-4');
        const heading = createElement('div', 'flex min-w-0 items-start justify-between gap-3');
        const taskLink = createElement('a', 'block line-clamp-2 font-semibold leading-5 text-slate-900 hover:text-sky-700', task.name);
        taskLink.href = task.task_url;
        const projectIdentity = createElement('div', 'mt-1.5 flex min-w-0 items-center gap-1.5 text-xs text-slate-500');
        const projectIcon = createElement('i', 'fa-solid fa-folder-open shrink-0 text-[11px] text-slate-400');
        const projectLink = createElement('a', 'block min-w-0 truncate hover:text-sky-700', task.project_name);
        projectIcon.setAttribute('aria-hidden', 'true');
        projectLink.href = task.project_url;
        projectIdentity.append(projectIcon, projectLink);
        const identity = createElement('div', 'min-w-0 flex-1');
        identity.append(taskLink, projectIdentity);
        heading.appendChild(identity);

        if (task.is_overdue) {
            heading.appendChild(createElement('span', 'shrink-0 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700', 'Overdue'));
        }

        card.appendChild(heading);

        const metrics = createElement('dl', 'mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4');
        appendMetric(metrics, 'Status', task.status);
        appendMetric(metrics, 'Assignee aktif', task.active_assignee_count);
        appendMetric(metrics, 'Kontribusi', Number(task.contribution).toFixed(2));
        appendMetric(metrics, 'Jadwal', task.start_date && task.due_date ? `${task.start_date} – ${task.due_date}` : 'Belum lengkap');
        card.appendChild(metrics);
        container.appendChild(card);
    });
}

async function loadDetail(modal, trigger) {
    const loading = modal.querySelector('[data-workload-detail-loading]');
    const content = modal.querySelector('[data-workload-detail-content]');
    const error = modal.querySelector('[data-workload-detail-error]');
    const url = new URL(trigger.dataset.detailUrl, window.location.origin);

    new URLSearchParams(window.location.search).forEach((value, key) => {
        if (key !== 'page') {
            url.searchParams.set(key, value);
        }
    });

    loading.classList.remove('hidden');
    content.classList.add('hidden');
    content.classList.remove('flex');
    error.classList.add('hidden');
    error.textContent = '';

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Rincian tidak dapat dimuat untuk scope ini.');
        }

        const data = await response.json();
        renderMember(modal.querySelector('[data-workload-detail-member]'), data.member);
        renderProjects(modal.querySelector('[data-workload-projects]'), data.projects);
        renderTasks(modal.querySelector('[data-workload-tasks]'), data.contributing_tasks, 'Tidak ada contributing task.');
        renderTasks(modal.querySelector('[data-workload-unscheduled]'), data.unscheduled_tasks, 'Tidak ada task unscheduled.');
        modal.querySelector('[data-workload-detail-period]').textContent = `${data.period.label}: ${data.period.start} – ${data.period.end}`;
        modal.querySelector('[data-workload-detail-disclaimer]').textContent = data.disclaimer;
        content.classList.remove('hidden');
        content.classList.add('flex');
    } catch (detailError) {
        error.textContent = detailError.message;
        error.classList.remove('hidden');
    } finally {
        loading.classList.add('hidden');
    }
}

function initializePeriodFilter() {
    const period = document.querySelector('[data-workload-period]');
    const range = document.querySelector('[data-workload-custom-range]');

    if (!period || !range) {
        return;
    }

    const updateRangeVisibility = () => {
        const isCustom = period.value === 'custom';
        range.classList.toggle('hidden', !isCustom);
        range.classList.toggle('flex', isCustom);
    };

    period.addEventListener('change', updateRangeVisibility);
    updateRangeVisibility();
}

document.addEventListener('click', (event) => {
    const calculationTrigger = event.target.closest('[data-workload-calculation-open]');
    const detailTrigger = event.target.closest('[data-workload-detail-open]');
    const closeTrigger = event.target.closest('[data-workload-modal-close]');

    if (calculationTrigger) {
        openModal(document.getElementById('workload-calculation-modal'), calculationTrigger);
        return;
    }

    if (detailTrigger) {
        const modal = document.querySelector('[data-workload-detail-modal]');
        openModal(modal, detailTrigger);
        loadDetail(modal, detailTrigger);
        return;
    }

    if (closeTrigger) {
        closeModal(closeTrigger.closest('[data-workload-modal]'));
        return;
    }

    if (activeModal && event.target === activeModal) {
        closeModal(activeModal);
    }
});

document.addEventListener('keydown', (event) => {
    if (!activeModal) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeModal(activeModal);
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusableElements = [...activeModal.querySelectorAll(focusableSelector)];
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement?.focus();
    } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement?.focus();
    }
});

initializePeriodFilter();
