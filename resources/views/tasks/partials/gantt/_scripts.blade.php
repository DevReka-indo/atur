<link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/8.0/dhtmlxgantt.css">
<script src="https://cdn.dhtmlx.com/gantt/8.0/dhtmlxgantt.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const containerId = @json($ganttContainerId);
        const emptyState = document.getElementById(@json($ganttEmptyStateId));
        const inlinePayload = {{ Illuminate\Support\Js::from($ganttPayload ?? null) }};
        const dataUrl = {{ Illuminate\Support\Js::from($ganttDataUrl ?? null) }};
        const useFixedSummaryDates = {{ Illuminate\Support\Js::from($ganttUseFixedSummaryDates ?? false) }};

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';

            return element.innerHTML;
        }

        function showMessage(title, message) {
            emptyState.querySelector('[data-gantt-empty-title]').textContent = title;
            emptyState.querySelector('[data-gantt-empty-message]').textContent = message;
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
        }

        function hideMessage() {
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');
        }

        function render(payload) {
            if (!payload || !Array.isArray(payload.data) || payload.data.length === 0) {
                showMessage('Belum ada task untuk ditampilkan.', 'Task yang sesuai filter akan muncul di sini.');
                return;
            }

            hideMessage();
            gantt.clearAll();
            if (useFixedSummaryDates) {
                payload.data.forEach(function(task) {
                    if (task.is_summary) {
                        task.type = 'task';
                    }
                });
            }
            gantt.parse(payload);
        }

        gantt.config.date_format = '%d-%m-%Y';
        gantt.config.readonly = true;
        gantt.config.open_tree_initially = true;
        gantt.config.fit_tasks = true;
        gantt.config.scales = [{
            unit: 'month',
            step: 1,
            format: '%F %Y'
        }, {
            unit: 'day',
            step: 1,
            format: '%d'
        }];
        gantt.config.columns = [{
            name: 'wbs',
            label: '#',
            width: 44,
            align: 'center',
            template: function(task) {
                return gantt.getWBSCode(task);
            }
        }, {
            name: 'text',
            label: 'Nama Task',
            tree: true,
            width: 250,
            template: function(task) {
                const levelLabel = task.is_summary
                    ? '<span class="gantt-context-label">Summary Task</span>'
                    : task.hierarchy_level === 2
                        ? '<span class="gantt-context-label">Sub-subtask</span>'
                        : task.hierarchy_level === 1
                            ? '<span class="gantt-context-label">Subtask</span>'
                            : '';
                const name = escapeHtml(task.text);

                if (!task.detail_url) {
                    return levelLabel + name;
                }

                return levelLabel + '<a class="gantt-detail-link" href="' + encodeURI(task.detail_url) + '">' + name + '</a>';
            }
        }, {
            name: 'start_date',
            label: 'Mulai',
            align: 'center',
            width: 82,
            template: function(task) {
                return gantt.templates.date_grid(task.start_date, task);
            }
        }, {
            name: 'end_date',
            label: 'Selesai',
            align: 'center',
            width: 82,
            template: function(task) {
                const end = new Date(task.start_date);
                end.setDate(end.getDate() + task.duration - 1);

                return gantt.templates.date_grid(end, task);
            }
        }, {
            name: 'progress',
            label: '% Done',
            align: 'center',
            width: 60,
            template: function(task) {
                return Math.round(task.progress * 100) + '%';
            }
        }, {
            name: 'priority',
            label: 'Prioritas',
            align: 'center',
            width: 74,
            template: function(task) {
                return escapeHtml((task.priority || 'medium').toUpperCase());
            }
        }, {
            name: 'predecessor',
            label: 'Pred.',
            align: 'left',
            width: 120,
            template: function(task) {
                if (!task.predecessor_id || !gantt.isTaskExists(task.predecessor_id)) {
                    return '—';
                }

                return escapeHtml(gantt.getTask(task.predecessor_id).text) + ' [' +
                    escapeHtml(task.dependency_type || 'FS') + ']';
            }
        }, {
            name: 'resource',
            label: 'Resource',
            align: 'left',
            width: 110,
            template: function(task) {
                return escapeHtml(task.resource || '—');
            }
        }];

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
            title: 'Tanggal hari ini'
        });

        gantt.templates.task_class = function(start, end, task) {
            if (task.is_summary) {
                return 'gantt-summary';
            }

            return 'gantt-status-' + (task.status || 'to_do').replaceAll('_', '-');
        };
        gantt.templates.progress_text = function(start, end, task) {
            return Math.round(task.progress * 100) + '%';
        };
        gantt.templates.tooltip_text = function(start, end, task) {
            const context = task.is_summary ? 'Summary Task' :
                (task.hierarchy_level === 2 ? 'Sub-subtask' : (task.hierarchy_level === 1 ? 'Subtask' : 'Task'));

            return [
                '<b>' + escapeHtml(task.text) + '</b>',
                '<b>Tipe:</b> ' + context,
                '<b>Status:</b> ' + escapeHtml(task.status || '—'),
                '<b>Mulai:</b> ' + gantt.templates.tooltip_date_format(start),
                '<b>Selesai:</b> ' + gantt.templates.tooltip_date_format(end),
                '<b>Progress:</b> ' + Math.round(task.progress * 100) + '%',
                '<b>Resource:</b> ' + escapeHtml(task.resource || '—'),
            ].join('<br>');
        };
        gantt.templates.link_class = function(link) {
            return ['link-fs', 'link-ss', 'link-ff', 'link-sf'][Number(link.type)] || 'link-fs';
        };
        gantt.attachEvent('onTaskClick', function(id, event) {
            if (event.target.closest('.gantt_tree_icon')) {
                return true;
            }

            const task = gantt.getTask(id);
            if (task.detail_url) {
                window.location.assign(task.detail_url);
                return false;
            }

            return true;
        });

        gantt.init(containerId);

        if (inlinePayload) {
            render(inlinePayload);
            return;
        }

        fetch(dataUrl, {
            headers: {
                Accept: 'application/json'
            }
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Gantt request failed.');
                }

                return response.json();
            })
            .then(render)
            .catch(function() {
                showMessage('Gantt tidak dapat dimuat.', 'Silakan muat ulang halaman atau coba beberapa saat lagi.');
            });
    });
</script>

<style>
    .gantt-status-completed .gantt_task_content { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .gantt-status-in-progress .gantt_task_content { background: linear-gradient(135deg, #378add, #185fa5); }
    .gantt-status-review .gantt_task_content { background: linear-gradient(135deg, #a855f7, #7c3aed); }
    .gantt-status-stopped .gantt_task_content { background: linear-gradient(135deg, #f59e0b, #b45309); }
    .gantt-status-cancelled .gantt_task_content { background: linear-gradient(135deg, #9ca3af, #6b7280); }
    .gantt-status-to-do .gantt_task_content { background: linear-gradient(135deg, #94a3b8, #64748b); }
    .gantt-summary .gantt_task_content { background: linear-gradient(135deg, #475569, #1e293b) !important; font-weight: 700; }
    .gantt-summary .gantt_task_progress { background: rgba(255, 255, 255, .25) !important; }
    .gantt-context-label { margin-right: 6px; border-radius: 9999px; background: #e0e7ff; padding: 2px 6px; color: #4338ca; font-size: 9px; font-weight: 700; text-transform: uppercase; }
    .gantt-detail-link { color: inherit; text-decoration: none; }
    .gantt-detail-link:hover { color: #4f46e5; text-decoration: underline; }
    .today-marker { border-left: 2px solid #d4537e; }
    .today-marker .gantt_marker_content { border-radius: 0 3px 3px 0; background: #d4537e; padding: 2px 5px; color: white; font-size: 10px; }
    .link-fs .gantt_line_wrapper div { background: #6366f1; }
    .link-ss .gantt_line_wrapper div { background: #0891b2; }
    .link-ff .gantt_line_wrapper div { background: #d97706; }
    .link-sf .gantt_line_wrapper div { background: #dc2626; }
    .gantt_tree_indent { width: 20px !important; }
    .gantt_grid_head_cell { background: #f8fafc !important; color: #374151 !important; font-size: 11px !important; font-weight: 600 !important; }
</style>
