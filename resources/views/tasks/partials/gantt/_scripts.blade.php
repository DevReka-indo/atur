<link
    rel="stylesheet"
    href="https://cdn.dhtmlx.com/gantt/8.0/dhtmlxgantt.css"
>

<script src="https://cdn.dhtmlx.com/gantt/8.0/dhtmlxgantt.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const containerId = @json($ganttContainerId);
        const emptyStateId = @json($ganttEmptyStateId);

        const ganttContainer = document.getElementById(containerId);
        const emptyState = document.getElementById(emptyStateId);

        const inlinePayload =
            {{ Illuminate\Support\Js::from($ganttPayload ?? null) }};

        const dataUrl =
            {{ Illuminate\Support\Js::from($ganttDataUrl ?? null) }};

        const useFixedSummaryDates =
            {{ Illuminate\Support\Js::from($ganttUseFixedSummaryDates ?? false) }};

        if (!ganttContainer || !emptyState) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Helpers
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {
            const element = document.createElement('div');

            element.textContent = value ?? '';

            return element.innerHTML;
        }

        function showMessage(title, message) {
            const titleElement = emptyState.querySelector(
                '[data-gantt-empty-title]'
            );

            const messageElement = emptyState.querySelector(
                '[data-gantt-empty-message]'
            );

            if (titleElement) {
                titleElement.textContent = title;
            }

            if (messageElement) {
                messageElement.textContent = message;
            }

            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
        }

        function hideMessage() {
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');
        }

        function calculateInitialGridWidth() {
            const containerWidth = ganttContainer.clientWidth;

            if (!containerWidth) {
                return 560;
            }

            return Math.min(
                760,
                Math.max(
                    380,
                    Math.floor(containerWidth * 0.5)
                )
            );
        }

        function render(payload) {
            if (
                !payload
                || !Array.isArray(payload.data)
                || payload.data.length === 0
            ) {
                gantt.clearAll();

                showMessage(
                    'Belum ada task untuk ditampilkan.',
                    'Task yang sesuai filter akan muncul di sini.'
                );

                return;
            }

            hideMessage();
            gantt.clearAll();

            if (useFixedSummaryDates) {
                payload.data.forEach(function (task) {
                    if (task.is_summary) {
                        task.type = 'task';
                    }
                });
            }

            gantt.parse(payload);
            gantt.render();
        }

        /*
        |--------------------------------------------------------------------------
        | Konfigurasi dasar
        |--------------------------------------------------------------------------
        */

        gantt.config.date_format = '%d-%m-%Y';
        gantt.config.readonly = true;
        gantt.config.open_tree_initially = true;
        gantt.config.fit_tasks = true;

        gantt.config.row_height = 32;
        gantt.config.scale_height = 48;
        gantt.config.scroll_size = 16;
        gantt.config.min_grid_column_width = 50;
        gantt.config.grid_elastic_columns = false;

        /*
        |--------------------------------------------------------------------------
        | Skala timeline
        |--------------------------------------------------------------------------
        */

        gantt.config.scales = [
            {
                unit: 'month',
                step: 1,
                format: '%F %Y',
            },
            {
                unit: 'day',
                step: 1,
                format: '%d',
            },
        ];

        /*
        |--------------------------------------------------------------------------
        | Kolom tabel
        |--------------------------------------------------------------------------
        |
        | Total kolom boleh lebih lebar daripada area tabel karena tabel kiri
        | mempunyai scrollbar horizontal sendiri.
        |
        */

        gantt.config.columns = [
            {
                name: 'wbs',
                label: '#',
                width: 52,
                align: 'center',
                resize: true,

                template: function (task) {
                    return gantt.getWBSCode(task);
                },
            },
            {
                name: 'text',
                label: 'Nama Task',
                tree: true,
                width: 280,
                min_width: 180,
                resize: true,

                template: function (task) {
                    let levelLabel = '';

                    if (task.is_summary) {
                        levelLabel =
                            '<span class="gantt-context-label gantt-context-summary">'
                            + 'Summary Task'
                            + '</span>';
                    } else if (task.hierarchy_level === 2) {
                        levelLabel =
                            '<span class="gantt-context-label gantt-context-sub-subtask">'
                            + 'Sub-subtask'
                            + '</span>';
                    } else if (task.hierarchy_level === 1) {
                        levelLabel =
                            '<span class="gantt-context-label gantt-context-subtask">'
                            + 'Subtask'
                            + '</span>';
                    }

                    const taskName = escapeHtml(task.text);

                    if (!task.detail_url) {
                        return levelLabel + taskName;
                    }

                    return levelLabel
                        + '<a class="gantt-detail-link" href="'
                        + encodeURI(task.detail_url)
                        + '">'
                        + taskName
                        + '</a>';
                },
            },
            {
                name: 'start_date',
                label: 'Mulai',
                align: 'center',
                width: 100,
                resize: true,

                template: function (task) {
                    return gantt.templates.date_grid(
                        task.start_date,
                        task
                    );
                },
            },
            {
                name: 'end_date',
                label: 'Selesai',
                align: 'center',
                width: 100,
                resize: true,

                template: function (task) {
                    const endDate = new Date(task.start_date);

                    endDate.setDate(
                        endDate.getDate() + task.duration - 1
                    );

                    return gantt.templates.date_grid(
                        endDate,
                        task
                    );
                },
            },
            {
                name: 'progress',
                label: '%',
                align: 'center',
                width: 64,
                resize: true,

                template: function (task) {
                    return Math.round(task.progress * 100) + '%';
                },
            },
            {
                name: 'priority',
                label: 'Prioritas',
                align: 'center',
                width: 90,
                resize: true,

                template: function (task) {
                    return escapeHtml(
                        (task.priority || 'medium').toUpperCase()
                    );
                },
            },
            {
                name: 'predecessor',
                label: 'Pred.',
                align: 'center',
                width: 80,
                resize: true,

                template: function (task) {
                    if (
                        !task.predecessor_id
                        || !gantt.isTaskExists(task.predecessor_id)
                    ) {
                        return '—';
                    }

                    return escapeHtml(
                        task.dependency_type || 'FS'
                    );
                },
            },
            {
                name: 'resource',
                label: 'Resource',
                align: 'left',
                width: 180,
                resize: true,

                template: function (task) {
                    return escapeHtml(task.resource || '—');
                },
            },
        ];

        /*
        |--------------------------------------------------------------------------
        | Custom layout
        |--------------------------------------------------------------------------
        |
        | Grid dan timeline benar-benar dibuat sebagai dua kolom sejajar.
        |
        | Grid:
        | - scrollbar horizontal sendiri;
        | - scrollbar vertikal bersama;
        | - scrollable agar total lebar kolom tidak mendorong timeline.
        |
        | Timeline:
        | - scrollbar horizontal sendiri;
        | - scrollbar vertikal bersama.
        |
        | Resizer:
        | - dapat ditarik memakai mouse untuk mengubah pembagian lebar.
        |
        */

        const initialGridWidth = calculateInitialGridWidth();

        gantt.config.layout = {
            css: 'gantt_container',

            cols: [
                {
                    width: initialGridWidth,
                    minWidth: 360,
                    maxWidth: 760,

                    rows: [
                        {
                            view: 'grid',
                            id: 'grid',
                            scrollX: 'gridHorizontalScroll',
                            scrollY: 'verticalScroll',
                            scrollable: true,
                        },
                        {
                            view: 'scrollbar',
                            id: 'gridHorizontalScroll',
                            scroll: 'x',
                            height: 18,
                        },
                    ],
                },
                {
                    resizer: true,
                    width: 8,
                },
                {
                    gravity: 1,

                    rows: [
                        {
                            view: 'timeline',
                            id: 'timeline',
                            scrollX: 'timelineHorizontalScroll',
                            scrollY: 'verticalScroll',
                        },
                        {
                            view: 'scrollbar',
                            id: 'timelineHorizontalScroll',
                            scroll: 'x',
                            height: 18,
                        },
                    ],
                },
                {
                    view: 'scrollbar',
                    id: 'verticalScroll',
                    scroll: 'y',
                },
            ],
        };

        /*
        |--------------------------------------------------------------------------
        | Plugin
        |--------------------------------------------------------------------------
        */

        gantt.plugins({
            critical_path: true,
            tooltip: true,
            marker: true,
        });

        gantt.config.highlight_critical_path = true;

        gantt.addMarker({
            start_date: new Date(),
            css: 'today-marker',
            text: 'Hari ini',
            title: 'Tanggal hari ini',
        });

        /*
        |--------------------------------------------------------------------------
        | Style task
        |--------------------------------------------------------------------------
        */

        gantt.templates.task_class = function (start, end, task) {
            if (task.is_summary) {
                return 'gantt-summary';
            }

            const status = (task.status || 'to_do')
                .replaceAll('_', '-');

            return 'gantt-status-' + status;
        };

        gantt.templates.progress_text = function (
            start,
            end,
            task
        ) {
            return Math.round(task.progress * 100) + '%';
        };

        /*
        |--------------------------------------------------------------------------
        | Tooltip
        |--------------------------------------------------------------------------
        */

        gantt.templates.tooltip_text = function (
            start,
            end,
            task
        ) {
            let taskType = 'Task';

            if (task.is_summary) {
                taskType = 'Summary Task';
            } else if (task.hierarchy_level === 2) {
                taskType = 'Sub-subtask';
            } else if (task.hierarchy_level === 1) {
                taskType = 'Subtask';
            }

            return [
                '<b>' + escapeHtml(task.text) + '</b>',
                '<b>Tipe:</b> ' + taskType,
                '<b>Status:</b> '
                    + escapeHtml(task.status || '—'),
                '<b>Mulai:</b> '
                    + gantt.templates.tooltip_date_format(start),
                '<b>Selesai:</b> '
                    + gantt.templates.tooltip_date_format(end),
                '<b>Progress:</b> '
                    + Math.round(task.progress * 100)
                    + '%',
                '<b>Prioritas:</b> '
                    + escapeHtml(
                        (task.priority || 'medium').toUpperCase()
                    ),
                '<b>Resource:</b> '
                    + escapeHtml(task.resource || '—'),
            ].join('<br>');
        };

        /*
        |--------------------------------------------------------------------------
        | Dependency
        |--------------------------------------------------------------------------
        */

        gantt.templates.link_class = function (link) {
            return [
                'link-fs',
                'link-ss',
                'link-ff',
                'link-sf',
            ][Number(link.type)] || 'link-fs';
        };

        /*
        |--------------------------------------------------------------------------
        | Navigasi detail task
        |--------------------------------------------------------------------------
        */

        gantt.attachEvent(
            'onTaskClick',
            function (id, event) {
                if (
                    event.target.closest('.gantt_tree_icon')
                    || event.target.closest('.gantt_open')
                    || event.target.closest('.gantt_close')
                ) {
                    return true;
                }

                const task = gantt.getTask(id);

                if (task.detail_url) {
                    window.location.assign(task.detail_url);

                    return false;
                }

                return true;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Init
        |--------------------------------------------------------------------------
        */

        gantt.init(containerId);

        /*
        |--------------------------------------------------------------------------
        | Responsive resize
        |--------------------------------------------------------------------------
        |
        | Tidak menghitung ulang lebar grid agar ukuran hasil drag user tidak
        | di-reset. Hanya meminta DHTMLX menghitung ulang layout.
        |
        */

        let resizeTimer = null;

        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);

            resizeTimer = window.setTimeout(
                function () {
                    if (typeof gantt.setSizes === 'function') {
                        gantt.setSizes();
                    }

                    gantt.render();
                },
                150
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Load payload
        |--------------------------------------------------------------------------
        */

        if (inlinePayload) {
            render(inlinePayload);

            return;
        }

        if (!dataUrl) {
            showMessage(
                'Gantt tidak dapat dimuat.',
                'Sumber data Gantt tidak tersedia.'
            );

            return;
        }

        fetch(dataUrl, {
            headers: {
                Accept: 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Gantt request failed.');
                }

                return response.json();
            })
            .then(render)
            .catch(function () {
                showMessage(
                    'Gantt tidak dapat dimuat.',
                    'Silakan muat ulang halaman atau coba beberapa saat lagi.'
                );
            });
    });
</script>

<style>
    /*
    |--------------------------------------------------------------------------
    | Warna status
    |--------------------------------------------------------------------------
    */

    .gantt-status-completed .gantt_task_content {
        background: linear-gradient(
            135deg,
            #22c55e,
            #16a34a
        );
    }

    .gantt-status-in-progress .gantt_task_content {
        background: linear-gradient(
            135deg,
            #378add,
            #185fa5
        );
    }

    .gantt-status-review .gantt_task_content {
        background: linear-gradient(
            135deg,
            #a855f7,
            #7c3aed
        );
    }

    .gantt-status-stopped .gantt_task_content {
        background: linear-gradient(
            135deg,
            #f59e0b,
            #b45309
        );
    }

    .gantt-status-cancelled .gantt_task_content {
        background: linear-gradient(
            135deg,
            #9ca3af,
            #6b7280
        );
    }

    .gantt-status-to-do .gantt_task_content {
        background: linear-gradient(
            135deg,
            #94a3b8,
            #64748b
        );
    }

    .gantt-summary .gantt_task_content {
        background: linear-gradient(
            135deg,
            #475569,
            #1e293b
        ) !important;

        font-weight: 700;
    }

    .gantt-summary .gantt_task_progress {
        background: rgba(
            255,
            255,
            255,
            0.25
        ) !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Grid dan timeline
    |--------------------------------------------------------------------------
    */

    .gantt_grid {
        border-right: 1px solid #cbd5e1;
    }

    .gantt_grid_scale,
    .gantt_task_scale {
        background: #f8fafc !important;
    }

    .gantt_grid_head_cell {
        overflow: hidden;
        background: #f8fafc !important;
        color: #374151 !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .gantt_cell {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .gantt_grid_data .gantt_row:hover,
    .gantt_grid_data .gantt_row.odd:hover {
        background: #f8fafc;
    }

    /*
    |--------------------------------------------------------------------------
    | Resizer pembatas grid dan timeline
    |--------------------------------------------------------------------------
    */

    .gantt_resizer {
        position: relative !important;
        background: #f8fafc;
        cursor: col-resize !important;
    }

    .gantt_resizer::before {
        content: '';

        position: absolute;

        top: 0;
        bottom: 0;
        left: 50%;

        width: 1px;

        background: #cbd5e1;

        transform: translateX(-50%);

        transition:
            width 0.15s ease,
            background-color 0.15s ease;
    }

    .gantt_resizer:hover {
        background: rgba(
            99,
            102,
            241,
            0.1
        );
    }

    .gantt_resizer:hover::before {
        width: 2px;
        background: #6366f1;
    }

    .gantt_resizer:active {
        background: rgba(
            99,
            102,
            241,
            0.15
        );
    }

    .gantt_resizer:active::before {
        width: 2px;
        background: #4f46e5;
    }

    /*
    |--------------------------------------------------------------------------
    | Scrollbar horizontal terpisah
    |--------------------------------------------------------------------------
    */

    .gantt_hor_scroll {
        background: #f8fafc !important;
        border-top: 1px solid #e2e8f0;
    }

    .gantt_hor_scroll > div {
        border-radius: 9999px;
        background: #94a3b8;
    }

    .gantt_hor_scroll:hover > div {
        background: #64748b;
    }

    /*
    |--------------------------------------------------------------------------
    | Hierarchy
    |--------------------------------------------------------------------------
    */

    .gantt_tree_indent {
        width: 18px !important;
    }

    .gantt-context-label {
        display: inline-flex;
        margin-right: 6px;
        align-items: center;
        border-radius: 9999px;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: 700;
        line-height: 1.2;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .gantt-context-summary {
        background: #e2e8f0;
        color: #334155;
    }

    .gantt-context-subtask {
        background: #e0e7ff;
        color: #4338ca;
    }

    .gantt-context-sub-subtask {
        background: #ede9fe;
        color: #6d28d9;
    }

    /*
    |--------------------------------------------------------------------------
    | Link detail
    |--------------------------------------------------------------------------
    */

    .gantt-detail-link {
        color: inherit;
        text-decoration: none;
    }

    .gantt-detail-link:hover {
        color: #4f46e5;
        text-decoration: underline;
    }

    /*
    |--------------------------------------------------------------------------
    | Marker hari ini
    |--------------------------------------------------------------------------
    */

    .today-marker {
        border-left: 2px solid #d4537e;
    }

    .today-marker .gantt_marker_content {
        border-radius: 0 3px 3px 0;
        background: #d4537e;
        padding: 2px 5px;
        color: #ffffff;
        font-size: 10px;
    }

    /*
    |--------------------------------------------------------------------------
    | Dependency
    |--------------------------------------------------------------------------
    */

    .link-fs .gantt_line_wrapper div {
        background: #6366f1;
    }

    .link-ss .gantt_line_wrapper div {
        background: #0891b2;
    }

    .link-ff .gantt_line_wrapper div {
        background: #d97706;
    }

    .link-sf .gantt_line_wrapper div {
        background: #dc2626;
    }

    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media (max-width: 900px) {
        .gantt-context-label {
            display: none;
        }

        .gantt_tree_indent {
            width: 14px !important;
        }
    }
</style>
