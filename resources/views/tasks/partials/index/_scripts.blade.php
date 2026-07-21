<script>
    (() => {
        const params = new URLSearchParams(window.location.search);

        if (!params.has('view')) {
            const savedView = localStorage.getItem('tasks_preferred_view');

            if (savedView && savedView !== 'list') {
                params.set('view', savedView);

                window.location.replace(
                    window.location.pathname + '?' + params.toString()
                );
            }
        }
    })();

    const taskStatusPortal = document.getElementById(
        'task-status-dropdown-portal'
    );

    let activeTaskStatusButton = null;

    const taskCsrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function openTaskStatusDropdown(button) {
        if (!taskStatusPortal) {
            return;
        }

        if (
            activeTaskStatusButton === button
            && !taskStatusPortal.classList.contains('hidden')
        ) {
            closeTaskDropdown();

            return;
        }

        closeTaskDropdown();

        activeTaskStatusButton = button;

        const options = JSON.parse(button.dataset.options ?? '[]');
        const currentStatus = button.dataset.currentStatus;
        const updateUrl = button.dataset.updateUrl;

        taskStatusPortal.innerHTML = options.map((option) => `
            <form method="POST" action="${updateUrl}">
                <input
                    type="hidden"
                    name="_token"
                    value="${taskCsrfToken}"
                >

                <input
                    type="hidden"
                    name="_method"
                    value="PATCH"
                >

                <input
                    type="hidden"
                    name="status"
                    value="${option.value}"
                >

                <button
                    type="submit"
                    class="w-full px-3 py-2 text-left text-xs transition-colors
                        hover:bg-gray-50
                        ${option.value === currentStatus
                            ? 'bg-gray-100 font-semibold'
                            : ''}"
                >
                    ${option.label}
                </button>
            </form>
        `).join('');

        taskStatusPortal.classList.remove('hidden');

        const buttonRect = button.getBoundingClientRect();
        const dropdownHeight = taskStatusPortal.offsetHeight || 240;
        const dropdownWidth = taskStatusPortal.offsetWidth || 160;
        const availableSpaceBelow = window.innerHeight - buttonRect.bottom;

        const top = availableSpaceBelow < dropdownHeight + 8
            ? buttonRect.top + window.scrollY - dropdownHeight - 4
            : buttonRect.bottom + window.scrollY + 4;

        const left = Math.min(
            buttonRect.left + window.scrollX,
            window.scrollX + window.innerWidth - dropdownWidth - 16
        );

        taskStatusPortal.style.top = `${Math.max(8, top)}px`;
        taskStatusPortal.style.left = `${Math.max(8, left)}px`;
    }

    function closeTaskDropdown() {
        if (!taskStatusPortal) {
            return;
        }

        taskStatusPortal.classList.add('hidden');
        taskStatusPortal.innerHTML = '';

        activeTaskStatusButton = null;
    }

    function openTaskInfoModal() {
        const modal = document.getElementById('taskInfoModal');

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeTaskInfoModal() {
        const modal = document.getElementById('taskInfoModal');

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('click', (event) => {
        if (
            !event.target.closest('#task-status-dropdown-portal')
            && !event.target.closest('[id^="status-btn-"]')
        ) {
            closeTaskDropdown();
        }

        const modal = document.getElementById('taskInfoModal');

        if (modal && event.target === modal) {
            closeTaskInfoModal();
        }
    });

    window.addEventListener(
        'scroll',
        closeTaskDropdown,
        {
            passive: true,
            capture: true,
        }
    );

    window.addEventListener('resize', closeTaskDropdown);

    document
        .querySelectorAll('a[href*="view="]')
        .forEach((link) => {
            link.addEventListener('click', function () {
                const url = new URL(this.href);
                const selectedView = url.searchParams.get('view');

                if (selectedView) {
                    localStorage.setItem(
                        'tasks_preferred_view',
                        selectedView
                    );
                }
            });
        });
</script>
