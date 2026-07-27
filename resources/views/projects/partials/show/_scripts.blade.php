<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
    function switchProjectTab(tabName) {
        document
            .querySelectorAll('.project-tab-content')
            .forEach((element) => {
                element.classList.add('hidden');
            });

        document
            .querySelectorAll('.project-tab-button')
            .forEach((button) => {
                button.classList.remove(
                    'bg-[#ADE8F4]',
                    'text-gray-700'
                );

                button.classList.add(
                    'text-gray-600',
                    'hover:text-gray-900'
                );
            });

        const selectedTab = document.getElementById(
            `project-tab-${tabName}`
        );

        if (selectedTab) {
            selectedTab.classList.remove('hidden');
        }

        const selectedButton = document.querySelector(
            `[data-project-tab="${tabName}"]`
        );

        if (selectedButton) {
            selectedButton.classList.remove(
                'text-gray-600',
                'hover:text-gray-900'
            );

            selectedButton.classList.add(
                'bg-[#ADE8F4]',
                'text-gray-700'
            );
        }

        const taskActions = document.getElementById(
            'project-task-actions'
        );

        if (taskActions) {
            taskActions.classList.toggle(
                'hidden',
                tabName !== 'tasks'
            );
        }

        const url = new URL(window.location.href);

        url.searchParams.set('tab', tabName);

        window.history.replaceState({}, '', url);
    }

    function toggleProjectDropdown(id) {
        const targetId = `dropdown-${id}`;
        const target = document.getElementById(targetId);

        document
            .querySelectorAll('.project-dropdown-menu')
            .forEach((dropdown) => {
                if (dropdown.id !== targetId) {
                    dropdown.classList.add('hidden');
                }
            });

        if (target) {
            target.classList.toggle('hidden');
        }
    }

    function openProjectModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeProjectModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dropdown]')) {
            document
                .querySelectorAll('.project-dropdown-menu')
                .forEach((dropdown) => {
                    dropdown.classList.add('hidden');
                });
        }

        const modal = event.target.closest('.project-modal');

        if (
            modal
            && event.target === modal
        ) {
            closeProjectModal(modal.id);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const initialTab = params.get('tab') || @json($currentTab);

        switchProjectTab(initialTab);
    });
</script>

@if (! empty($chartData['labels']))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (
                typeof Chart === 'undefined'
                || typeof ChartDataLabels === 'undefined'
            ) {
                return;
            }

            Chart.register(ChartDataLabels);

            const canvas = document.getElementById(
                'projectProgressChart'
            );

            if (!canvas) {
                return;
            }

            const rawData = @json($chartData);
            const actualExtended = [];

            let lastActual = null;

            rawData.actual.forEach((value) => {
                if (value !== null && value !== undefined) {
                    lastActual = value;
                }

                actualExtended.push(lastActual);
            });

            const plannedValues = rawData.planned.filter(
                (value) => value !== null
            );

            const actualValues = actualExtended.filter(
                (value) => value !== null
            );

            const maxValue = Math.max(
                100,
                ...plannedValues,
                ...actualValues
            );

            function getLastValidIndex(data) {
                for (
                    let index = data.length - 1;
                    index >= 0;
                    index--
                ) {
                    if (
                        data[index] !== null
                        && data[index] !== undefined
                    ) {
                        return index;
                    }
                }

                return -1;
            }

            const lastPlannedIndex = getLastValidIndex(
                rawData.planned
            );

            const lastActualIndex = getLastValidIndex(
                actualExtended
            );

            new Chart(canvas, {
                type: 'line',

                data: {
                    labels: rawData.labels,

                    datasets: [
                        {
                            label: 'Planned',
                            data: rawData.planned,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            spanGaps: true,

                            datalabels: {
                                display(context) {
                                    return context.dataIndex
                                        === lastPlannedIndex;
                                },

                                align: 'top',
                                anchor: 'center',

                                formatter(value) {
                                    return value !== null
                                        ? `${value}%`
                                        : '';
                                },

                                color: '#ffffff',

                                font: {
                                    weight: 'bold',
                                    size: 11,
                                },

                                backgroundColor: '#6366f1',
                                borderRadius: 6,

                                padding: {
                                    top: 3,
                                    bottom: 3,
                                    left: 7,
                                    right: 7,
                                },
                            },
                        },

                        {
                            label: 'Actual',
                            data: actualExtended,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            spanGaps: true,

                            datalabels: {
                                display(context) {
                                    return context.dataIndex
                                        === lastActualIndex;
                                },

                                align: 'top',
                                anchor: 'center',

                                formatter(value) {
                                    return value !== null
                                        ? `${value}%`
                                        : '';
                                },

                                color: '#ffffff',

                                font: {
                                    weight: 'bold',
                                    size: 11,
                                },

                                backgroundColor: '#10b981',
                                borderRadius: 6,

                                padding: {
                                    top: 3,
                                    bottom: 3,
                                    left: 7,
                                    right: 7,
                                },
                            },
                        },
                    ],
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    layout: {
                        padding: {
                            top: 30,
                        },
                    },

                    plugins: {
                        legend: {
                            position: 'top',
                        },

                        datalabels: {},

                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return context.parsed.y === null
                                        ? `${context.dataset.label}: —`
                                        : `${context.dataset.label}: ${context.parsed.y}%`;
                                },
                            },
                        },
                    },

                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 12,
                            },

                            grid: {
                                color: 'rgba(148,163,184,0.2)',
                            },
                        },

                        y: {
                            min: 0,
                            max: Math.ceil(maxValue / 25) * 25 + 25,

                            ticks: {
                                stepSize: 25,

                                callback(value) {
                                    return `${value}%`;
                                },
                            },

                            grid: {
                                color: 'rgba(148,163,184,0.25)',
                            },
                        },
                    },
                },
            });
        });
    </script>
@endif

<script>
    const projectTaskStatusPortal = document.getElementById(
        'project-task-status-dropdown-portal'
    );

    let activeProjectTaskStatusButton = null;

    const projectTaskCsrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function openTaskStatusDropdown(button) {
        if (!projectTaskStatusPortal) {
            return;
        }

        if (
            activeProjectTaskStatusButton === button
            && !projectTaskStatusPortal.classList.contains('hidden')
        ) {
            closeTaskDropdown();

            return;
        }

        closeTaskDropdown();

        activeProjectTaskStatusButton = button;

        const options = JSON.parse(
            button.dataset.options ?? '[]'
        );

        const currentStatus = button.dataset.currentStatus;
        const updateUrl = button.dataset.updateUrl;

        projectTaskStatusPortal.innerHTML = options
            .map((option) => `
                <form method="POST" action="${updateUrl}">
                    <input
                        type="hidden"
                        name="_token"
                        value="${projectTaskCsrfToken}"
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
                        class="w-full px-4 py-2 text-left text-sm
                            transition-colors hover:bg-gray-50
                            ${option.value === currentStatus
                                ? 'bg-gray-100 font-semibold text-gray-900'
                                : 'text-gray-600'}"
                    >
                        ${option.label}
                    </button>
                </form>
            `)
            .join('');

        projectTaskStatusPortal.classList.remove('hidden');

        const rect = button.getBoundingClientRect();
        const portalHeight =
            projectTaskStatusPortal.offsetHeight || 220;
        const portalWidth =
            projectTaskStatusPortal.offsetWidth || 176;

        const spaceBelow =
            window.innerHeight - rect.bottom;

        const top = spaceBelow < portalHeight + 10
            ? rect.top + window.scrollY - portalHeight - 8
            : rect.bottom + window.scrollY + 8;

        const left = Math.min(
            rect.left + window.scrollX,
            window.scrollX
                + window.innerWidth
                - portalWidth
                - 16
        );

        projectTaskStatusPortal.style.top =
            `${Math.max(8, top)}px`;

        projectTaskStatusPortal.style.left =
            `${Math.max(8, left)}px`;
    }

    function closeTaskDropdown() {
        if (!projectTaskStatusPortal) {
            return;
        }

        projectTaskStatusPortal.classList.add('hidden');
        projectTaskStatusPortal.innerHTML = '';

        activeProjectTaskStatusButton = null;
    }

    document.addEventListener('click', (event) => {
        if (
            !event.target.closest(
                '#project-task-status-dropdown-portal'
            )
            && !event.target.closest('[id^="status-btn-"]')
        ) {
            closeTaskDropdown();
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

    window.addEventListener(
        'resize',
        closeTaskDropdown
    );
</script>

<style>
    .project-tab-content {
        transition: opacity 0.2s ease-in-out;
    }

    .project-dropdown-menu {
        transition: opacity 0.15s ease-in-out;
    }
</style>
