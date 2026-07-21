@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('view')) {
                const savedView = localStorage.getItem('tasks_preferred_view');
                if (savedView && savedView !== 'list') {
                    params.set('view', savedView);
                    window.location.replace(window.location.pathname + '?' + params.toString());
                }
            }
        })();
    </script>
    @php
        $statuses = [
            'all' => 'All',
            'to_do' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'completed' => 'Completed',
            'stopped' => 'Stopped',
            'cancelled' => 'Cancelled',
        ];
        $currentStatus = request('status', 'all');
        $currentView = $view ?? 'list';

    @endphp

    <div style="height: calc(100vh - 121px); display: flex; flex-direction: column; overflow: hidden;">
        <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

        {{-- header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-4xl font-semibold text-slate-900">
                    My Tasks
                </h1>

                <button onclick="openTaskInfoModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">Manage and organize your daily work.</p>
        </div>

        {{-- toolbar --}}
        <div class="flex items-center justify-between gap-2 mb-2 flex-shrink-0">

            {{-- filter --}}
            <div
                class="flex items-center gap-1 bg-white rounded-lg p-1 overflow-x-auto mb-4
                        {{ $currentView !== 'list' ? 'invisible h-0 overflow-hidden p-0' : '' }}">
                @foreach ($statuses as $key => $label)
                    <a href="{{ route('tasks.index', ['view' => $currentView, 'status' => $key]) }}"
                        class="px-4 py-1.5 rounded-md text-sm whitespace-nowrap transition-all
                        {{ $currentStatus === $key ? 'bg-[#ADE8F4] text-gray-900 font-medium shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Header Buttons (View Toggle & Create Task) --}}
            <div class="flex items-center gap-3">
                {{-- View Toggle Dropdown --}}
                <div class="relative group">
                    <button
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl
                            text-sm font-medium text-gray-600 hover:bg-gray-50 shadow-sm transition-all duration-200 cursor-pointer">
                        @if ($currentView === 'gantt')
                            <i class="fa-solid fa-chart-gantt text-indigo-500"></i> Gantt
                        @elseif($currentView === 'kanban')
                            <i class="fa-solid fa-table-columns text-indigo-500"></i> Kanban
                        @else
                            <i class="fa-solid fa-list text-indigo-500"></i> List
                        @endif
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                    </button>
                    <div
                        class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-50
                            invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200">
                        <a href="{{ route('tasks.index', ['view' => 'list', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-xl transition-colors
                        {{ $currentView === 'list' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-list w-4 text-center"></i> List
                        </a>
                        <a href="{{ route('tasks.index', ['view' => 'gantt', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm border-y border-gray-100 transition-colors
                        {{ $currentView === 'gantt' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-chart-gantt w-4 text-center"></i> Gantt
                        </a>
                        <a href="{{ route('tasks.index', ['view' => 'kanban', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-b-xl transition-colors
                        {{ $currentView === 'kanban' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-table-columns w-4 text-center"></i> Kanban
                        </a>
                    </div>
                </div>

                {{-- create task --}}
                <a href="{{ route('tasks.create') }}"
                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                    bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all duration-300">
                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                    Create Task
                </a>
            </div>
        </div>

        {{-- SINGLE CARD CONTAINER --}}
        <div class="border border-gray-200 shadow-sm rounded-xl flex-1 overflow-hidden bg-white"
            style="min-height:0; position:relative;">

            {{-- list --}}
            @if ($currentView === 'list')
                <div style="position:absolute; inset:0; overflow-x:auto; overflow-y:auto;">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-[#ADE8F4]">
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 flex-shrink-0"></div>
                                        Task
                                    </div>
                                </th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    Project</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    Workspace</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    Status</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    Start Date</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    Due Date</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-[#ADE8F4] whitespace-nowrap">
                                    Actions</th>
                            </tr>
                        </thead>
                        @php
                            $filteredTasks =
                                $currentStatus !== 'all' ? $tasks->where('status', $currentStatus) : $tasks;
                            $now = \Carbon\Carbon::now();

                            $sortedTasks = $filteredTasks->sortBy(function ($task) use ($now) {
                                if ($task->status === 'completed') {
                                    return PHP_INT_MAX - 1;
                                }
                                if ($task->status === 'cancelled') {
                                    return PHP_INT_MAX;
                                }

                                $due = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;

                                if (!$due) {
                                    return 999999;
                                }

                                $daysFromNow = $now->diffInDays($due, false);

                                return $daysFromNow;
                            });
                        @endphp

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sortedTasks as $task)
                                @php
                                    $isOverdue =
                                        $task->due_date &&
                                        \Carbon\Carbon::parse($task->due_date)->isPast() &&
                                        $task->status !== 'completed';
                                    $isToday = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isToday();
                                    $statusBadge = match ($task->status) {
                                        'to_do' => 'bg-amber-100 text-amber-700',
                                        'in_progress' => 'bg-blue-200 text-blue-800',
                                        'review' => 'bg-purple-200 text-purple-800',
                                        'completed' => 'bg-emerald-200 text-emerald-800',
                                        'stopped' => 'bg-red-200 text-red-800',
                                        'cancelled' => 'bg-zinc-300 text-zinc-800',
                                        default => 'bg-slate-200 text-slate-800',
                                    };
                                    $statusOptions = [
                                        ['value' => 'to_do', 'label' => 'To Do'],
                                        ['value' => 'in_progress', 'label' => 'In Progress'],
                                        ['value' => 'review', 'label' => 'Review'],
                                        ['value' => 'completed', 'label' => 'Completed'],
                                        ['value' => 'stopped', 'label' => 'Stopped'],
                                        ['value' => 'cancelled', 'label' => 'Cancelled'],
                                    ];
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    {{-- nama task --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            {{-- Icon Warning untuk Urgent --}}
                                            @if ($task->priority === 'urgent' && $task->status !== 'completed')
                                                <div
                                                    class="w-6 h-6 flex items-center justify-center rounded-lg flex-shrink-0 bg-gradient-to-br from-red-600 to-red-500 shadow-[0_2px_4px_rgba(220,38,38,0.3)]">
                                                    <i
                                                        class="fa-solid fa-triangle-exclamation text-white text-xs animate-pulse"></i>
                                                </div>
                                            @endif

                                            <div class="min-w-0 flex-1 max-w-[220px]">
                                                <span
                                                    class="block text-sm font-semibold truncate {{ $task->status === 'completed' ? 'line-through text-gray-400' : 'text-gray-900' }}"
                                                    title="{{ $task->name }}">
                                                    {{ $task->name }}
                                                </span>
                                                @if ($task->parent)
                                                    <div class="mt-1 flex min-w-0 items-center gap-1.5 text-xs">
                                                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-semibold text-indigo-700">
                                                            Subtask
                                                        </span>
                                                        <a href="{{ route('tasks.show', $task->parent->token) }}"
                                                            class="truncate text-gray-500 hover:text-indigo-700"
                                                            title="Parent: {{ $task->parent->name }}">
                                                            Parent: {{ $task->parent->name }}
                                                        </a>
                                                    </div>
                                                @endif
                                                @if ($task->description)
                                                    <span class="block text-xs text-gray-400 mt-0.5 truncate"
                                                        title="{{ $task->description }}">{{ $task->description }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- project --}}
                                    <td class="px-5 py-4">
                                        @if ($task->project)
                                            <span class="inline-flex items-center gap-2 text-sm text-gray-500 max-w-[150px]"
                                                title="{{ $task->project->name }}">
                                                <i class="fa-solid fa-diagram-project flex-shrink-0"></i>
                                                <span class="truncate">{{ $task->project->name }}</span>
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- workspace --}}
                                    <td class="px-5 py-4">
                                        @if ($task->project?->workspace)
                                            <span class="inline-flex items-center gap-2 text-sm text-gray-500 max-w-[150px]"
                                                title="{{ $task->project->workspace->name }}">
                                                <i
                                                    class="fa-solid fa-layer-group w-5 text-center text-sm flex-shrink-0"></i>
                                                <span class="truncate">{{ $task->project->workspace->name }}</span>
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- status --}}
                                    <td class="px-5 py-4">
                                        <button id="status-btn-{{ $task->id }}" data-task-id="{{ $task->token }}"
                                            data-current-status="{{ $task->status }}"
                                            data-update-url="{{ route('tasks.updateStatus', $task->token) }}"
                                            data-options="{{ json_encode($statusOptions) }}"
                                            onclick="openTaskStatusDropdown(this)"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $statusBadge }}">
                                            {{ str($task->status)->replace('_', ' ')->title() }}
                                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60"></i>
                                        </button>
                                    </td>

                                    {{-- start & due date --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-700 whitespace-nowrap">
                                            {{ $task->start_date ? \Carbon\Carbon::parse($task->start_date)->format('d M Y') : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($task->due_date)
                                            <div class="flex items-center gap-2 whitespace-nowrap">
                                                @if ($isOverdue)
                                                    <span
                                                        class="w-2 h-2 rounded-full bg-red-500 animate-pulse flex-shrink-0"></span>
                                                    <span
                                                        class="text-sm text-red-600 font-medium">{{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}</span>
                                                @elseif ($isToday)
                                                    <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                                                    <span
                                                        class="text-sm text-amber-600 font-medium">{{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}</span>
                                                @else
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                                    <span
                                                        class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- button --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-1">
                                            {{-- View: semua role bisa --}}
                                            <a href="{{ route('tasks.show', $task->token) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            {{-- Edit: hanya manager & member --}}
                                            @if ($task->project && $task->project->canContribute(Auth::user()))
                                                <a href="{{ route('tasks.edit', $task->token) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                            @endif

                                            {{-- Delete: hanya manager --}}
                                            @if ($task->project && $task->project->isManager(Auth::user()))
                                                <form action="{{ route('tasks.destroy', $task->token) }}" method="POST"
                                                    class="inline" onsubmit="return confirm('Delete this task?');">
                                                    @csrf @method('DELETE')
                                                    <input type="hidden" name="back_url" value="{{ url()->full() }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div
                                                class="w-16 h-16 rounded-full bg-amber-50 flex items-center justify-center">
                                                <i class="fa-solid fa-list-check text-amber-500 text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700">No tasks found</p>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    {{ $currentStatus !== 'all' ? 'No tasks match the selected status.' : 'Create your first task to get started.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- gantt --}}
            @if ($currentView === 'gantt')
                <div id="gantt_mytasks"
                    style="position:absolute; inset:0; width:100%; height:100%; display:flex; flex-direction:column;">

                    {{-- Legend Bar --}}
                    <div
                        style="display:flex; align-items:center; gap:16px; padding:8px 16px;
                    border-bottom:1px solid #e5e7eb; background:#f9fafb; flex-shrink:0; flex-wrap:wrap;">
                        <span style="font-size:11px; color:#6b7280; font-weight:600;">LEGENDA:</span>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="width:14px;height:14px;background:#E24B4A;border-radius:3px;"></div>
                            <span style="font-size:11px;color:#374151;">Critical Path</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="width:14px;height:14px;background:#378ADD;border-radius:3px;"></div>
                            <span style="font-size:11px;color:#374151;">Normal Task</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="width:14px;height:14px;background:#22c55e;border-radius:3px;"></div>
                            <span style="font-size:11px;color:#374151;">Completed</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div
                                style="width:12px;height:12px;background:#BA7517;transform:rotate(45deg);border-radius:2px;">
                            </div>
                            <span style="font-size:11px;color:#374151;">Milestone</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="width:14px;height:14px;background:#5F5E5A;border-radius:3px;"></div>
                            <span style="font-size:11px;color:#374151;">Summary / WBS</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="width:2px;height:14px;background:#D4537E;border-radius:1px;"></div>
                            <span style="font-size:11px;color:#374151;">Today</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="width:14px;height:0;border-top:2px dashed #6366f1;"></div>
                            <span style="font-size:11px;color:#374151;">Dependency (FS/SS/FF/SF)</span>
                        </div>
                    </div>

                    {{-- Gantt Container --}}
                    <div id="gantt_here" style="flex:1; overflow:hidden;"></div>
                </div>

                <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/8.0/dhtmlxgantt.css">
                <script src="https://cdn.dhtmlx.com/gantt/8.0/dhtmlxgantt.js"></script>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        gantt.config.date_format = "%d-%m-%Y";
                        gantt.config.scales = [{
                                unit: "month",
                                step: 1,
                                format: "%F %Y"
                            },
                            {
                                unit: "day",
                                step: 1,
                                format: "%d"
                            }
                        ];

                        gantt.config.columns = [{
                                name: "wbs",
                                label: "#",
                                width: 40,
                                align: "center",
                                template: function(task) {
                                    return gantt.getWBSCode(task);
                                }
                            },
                            {
                                name: "text",
                                label: "Nama Task",
                                tree: true,
                                width: 200
                            },
                            {
                                name: "start_date",
                                label: "Mulai",
                                align: "center",
                                width: 80,
                                template: function(task) {
                                    return gantt.templates.date_grid(task.start_date, task);
                                }
                            },
                            {
                                name: "end_date",
                                label: "Selesai",
                                align: "center",
                                width: 80,
                                template: function(task) {
                                    var end = new Date(task.start_date);
                                    end.setDate(end.getDate() + task.duration - 1);
                                    return gantt.templates.date_grid(end, task);
                                }
                            },
                            {
                                name: "duration",
                                label: "Dur.",
                                align: "center",
                                width: 45,
                                template: function(t) {
                                    return t.duration + "d";
                                }
                            },
                            {
                                name: "progress",
                                label: "% Done",
                                align: "center",
                                width: 55,
                                template: function(task) {
                                    return Math.round(task.progress * 100) + "%";
                                }
                            },
                            {
                                name: "priority",
                                label: "Prioritas",
                                align: "center",
                                width: 70,
                                template: function(t) {
                                    var colors = {
                                        urgent: '#ef4444',
                                        high: '#f97316',
                                        medium: '#eab308',
                                        low: '#94a3b8'
                                    };
                                    var p = t.priority || 'medium';
                                    return '<span style="color:' + (colors[p] || '#94a3b8') +
                                        ';font-weight:600;font-size:11px;">' + p.toUpperCase() + '</span>';
                                }
                            },
                            {
                                name: "predecessor",
                                label: "Pred.",
                                align: "center",
                                width: 100,
                                template: function(task) {
                                    if (!task.predecessor_id) return "—";
                                    var predTask = gantt.getTask(task.predecessor_id);
                                    var predName = predTask ? predTask.text : task.predecessor_id;
                                    return predName + " [" + (task.dependency_type || "FS") + "]";
                                }
                            },
                            {
                                name: "resource",
                                label: "Resource",
                                align: "left",
                                width: 90,
                                template: function(task) {
                                    return task.resource || "—";
                                }
                            },
                        ];

                        gantt.config.readonly = true;
                        gantt.config.open_tree_initially = true;
                        gantt.config.fit_tasks = true;

                        gantt.plugins({
                            critical_path: true,
                            tooltip: true,
                            marker: true,
                        });

                        gantt.config.highlight_critical_path = true;

                        gantt.addMarker({
                            start_date: new Date(),
                            css: "today-marker",
                            text: "Hari ini",
                            title: "Tanggal hari ini"
                        });

                        gantt.templates.task_class = function(start, end, task) {
                            if (task.type === gantt.config.types.milestone) return "gantt-milestone";
                            if (task.type === gantt.config.types.project) return "gantt-summary";
                            switch (task.status) {
                                case "completed":
                                    return "gantt-done";
                                case "in_progress":
                                    return "gantt-progress";
                                case "review":
                                    return "gantt-review";
                                default:
                                    return "gantt-todo";
                            }
                        };

                        gantt.templates.progress_text = function(start, end, task) {
                            return Math.round(task.progress * 100) + "%";
                        };

                        gantt.templates.task_row_class = function(start, end, task) {
                            if (task.baseline_start) {
                                var baseEnd = new Date(task.baseline_end);
                                if (end > baseEnd) return "task-delayed";
                                if (end < baseEnd) return "task-ahead";
                            }
                            return "";
                        };

                        gantt.templates.tooltip_text = function(start, end, task) {
                            var lines = [
                                "<b style='font-size:13px'>" + task.text + "</b>",
                                "<hr style='margin:4px 0;border:none;border-top:1px solid #e5e7eb'>",
                                "<b>Status:</b> " + (task.status || "—"),
                                "<b>Prioritas:</b> " + (task.priority || "—"),
                                "<b>Mulai:</b> " + gantt.templates.tooltip_date_format(start),
                                "<b>Selesai:</b> " + gantt.templates.tooltip_date_format(end),
                                "<b>Durasi:</b> " + task.duration + " hari",
                                "<b>% Selesai:</b> " + Math.round(task.progress * 100) + "%",
                                "<b>Resource:</b> " + (task.resource || "—"),
                            ];
                            if (task.predecessor_id) {
                                lines.push("<b>Predecessor:</b> Task #" + task.predecessor_id + " [" + (task
                                    .dependency_type || "FS") + "]");
                            }
                            return lines.join("<br/>");
                        };

                        gantt.templates.link_class = function(link) {
                            switch (link.type) {
                                case "0":
                                    return "link-fs";
                                case "1":
                                    return "link-ss";
                                case "2":
                                    return "link-ff";
                                case "3":
                                    return "link-sf";
                                default:
                                    return "link-fs";
                            }
                        };

                        gantt.init("gantt_here");
                        gantt.load("{{ route('gant.data') }}", "json");
                    });
                </script>

                <style>
                    .gantt-done .gantt_task_content {
                        background: linear-gradient(135deg, #22c55e, #16a34a);
                    }

                    .gantt-done .gantt_task_progress {
                        background: rgba(255, 255, 255, 0.3);
                    }

                    .gantt-progress .gantt_task_content {
                        background: linear-gradient(135deg, #378ADD, #185FA5);
                    }

                    .gantt-progress .gantt_task_progress {
                        background: rgba(255, 255, 255, 0.4);
                    }

                    .gantt-review .gantt_task_content {
                        background: linear-gradient(135deg, #a855f7, #7c3aed);
                    }

                    .gantt-review .gantt_task_progress {
                        background: rgba(255, 255, 255, 0.3);
                    }

                    .gantt-todo .gantt_task_content {
                        background: linear-gradient(135deg, #94a3b8, #64748b);
                    }

                    .gantt-todo .gantt_task_progress {
                        background: rgba(255, 255, 255, 0.2);
                    }

                    .gantt-summary .gantt_task_content {
                        background: linear-gradient(135deg, #5F5E5A, #3a3937) !important;
                        font-weight: bold !important;
                    }

                    .gantt-summary .gantt_task_progress {
                        background: rgba(255, 255, 255, 0.2) !important;
                    }

                    .gantt-milestone .gantt_task_content {
                        background: linear-gradient(135deg, #BA7517, #8a6a1f) !important;
                        border-color: #7a5a17 !important;
                    }

                    .gantt_critical_task .gantt_task_content {
                        background: linear-gradient(135deg, #E24B4A, #A32D2D) !important;
                    }

                    .gantt_critical_task .gantt_task_progress {
                        background: rgba(255, 255, 255, 0.3) !important;
                    }

                    .gantt_task_progress_wrapper {
                        overflow: visible !important;
                    }

                    .gantt_task_progress_drag {
                        display: none;
                    }

                    .today-marker {
                        border-left: 2px solid #D4537E;
                    }

                    .today-marker .gantt_marker_content {
                        background: #D4537E;
                        color: white;
                        font-size: 10px;
                        padding: 2px 5px;
                        border-radius: 0 3px 3px 0;
                    }

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

                    .gantt_link_arrow {
                        border-color: #6366f1;
                    }

                    .gantt_tree_indent {
                        width: 20px !important;
                    }

                    .gantt_row:hover {
                        background: #f0f9ff !important;
                    }

                    .gantt_task_row:hover {
                        background: #f0f9ff !important;
                    }

                    .gantt_grid_head_cell {
                        font-size: 11px !important;
                        font-weight: 600 !important;
                        color: #374151 !important;
                        background: #f8fafc !important;
                    }

                    .gantt_layout_content::-webkit-scrollbar {
                        height: 6px;
                        width: 6px;
                    }

                    .gantt_layout_content::-webkit-scrollbar-thumb {
                        background: #d1d5db;
                        border-radius: 3px;
                    }

                    .task-delayed .gantt_task_content {
                        border: 2px solid #f97316 !important;
                    }

                    .task-ahead .gantt_task_content {
                        border: 2px solid #5DCAA5 !important;
                    }
                </style>
            @endif

            {{-- kanban --}}
            @if ($currentView === 'kanban')
                <div style="position:absolute; inset:0; overflow-x:auto; overflow-y:hidden;">
                    <div style="display:flex; gap:1rem; padding:1rem; min-width:max-content; height:100%;">
                        @foreach ($kanbanStatuses as $statusKey => $statusLabel)
                            @php
                                $columnTasks = $kanbanTasks->get($statusKey, collect());
                                if ($currentStatus !== 'all' && $currentStatus !== $statusKey) {
                                    continue;
                                }
                                $columnColor = match ($statusKey) {
                                    'to_do' => 'border-amber-400',
                                    'in_progress' => 'border-blue-400',
                                    'review' => 'border-purple-400',
                                    'completed' => 'border-emerald-400',
                                    'stopped' => 'border-red-400',
                                    'cancelled' => 'border-zinc-400',
                                    default => 'border-gray-300',
                                };
                                $headerBg = match ($statusKey) {
                                    'to_do' => '#fef3c7',
                                    'in_progress' => '#dbeafe',
                                    'review' => '#ede9fe',
                                    'completed' => '#d1fae5',
                                    'stopped' => '#fee2e2',
                                    'cancelled' => '#D5D5D5',
                                    default => '#f3f4f6',
                                };
                                $headerText = match ($statusKey) {
                                    'to_do' => '#92400e',
                                    'in_progress' => '#1e40af',
                                    'review' => '#5b21b6',
                                    'completed' => '#065f46',
                                    'stopped' => '#991b1b',
                                    'cancelled' => '#3f3f46',
                                    default => '#374151',
                                };
                            @endphp
                            <div class="flex-shrink-0 bg-gray-100 rounded-xl border-t-4 {{ $columnColor }}"
                                style="width:280px; display:flex; flex-direction:column; height:100%;">

                                {{-- Header --}}
                                <div class="px-4 py-3 flex items-center justify-between rounded-t-xl flex-shrink-0"
                                    style="background-color: {{ $headerBg }}; color: {{ $headerText }};">
                                    <span class="text-sm font-semibold">{{ $statusLabel }}</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-white bg-opacity-70">
                                        {{ $columnTasks->count() }}
                                    </span>
                                </div>

                                {{-- Cards --}}
                                <div class="kanban-drop-zone p-3 flex flex-col gap-3 overflow-y-auto flex-1"
                                    data-status="{{ $statusKey }}">
                                    @forelse($columnTasks as $task)
                                        @php
                                            $priorityColor = match ($task->priority) {
                                                'urgent' => 'text-red-500',
                                                'high' => 'text-orange-500',
                                                'medium' => 'text-amber-500',
                                                default => 'text-gray-400',
                                            };
                                            $isOverdue =
                                                $task->due_date &&
                                                \Carbon\Carbon::parse($task->due_date)->isPast() &&
                                                $task->status !== 'completed';
                                        @endphp
                                        <div class="kanban-card bg-white rounded-lg p-3 shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex-shrink-0 cursor-grab active:cursor-grabbing select-none"
                                            draggable="true" data-task-id="{{ $task->token }}"
                                            data-status="{{ $task->status }}">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs {{ $priorityColor }} font-medium capitalize">
                                                    <i class="fa-solid fa-flag text-[10px]"></i>
                                                    {{ $task->priority ?? 'none' }}
                                                </span>
                                                @if ($task->project)
                                                    <span
                                                        class="text-xs text-gray-400 truncate max-w-28">{{ $task->project->name }}</span>
                                                @endif
                                            </div>
                                            <p
                                                class="text-sm font-semibold text-gray-800 mb-2 {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                                {{ $task->name }}
                                            </p>
                                            @if ($task->due_date)
                                                <div class="flex items-center gap-1 mb-3">
                                                    <i
                                                        class="fa-regular fa-calendar text-xs {{ $isOverdue ? 'text-red-400' : 'text-gray-400' }}"></i>
                                                    <span
                                                        class="text-xs {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                                                        {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                                @if ($task->assignees->isNotEmpty())
                                                    <div class="flex items-center gap-0.5">
                                                        @foreach ($task->assignees->take(3) as $assignee)
                                                            <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-[10px]"
                                                                title="{{ $assignee->name }}">
                                                                {{ strtoupper(substr($assignee->name, 0, 1)) }}
                                                            </div>
                                                        @endforeach
                                                        @if ($task->assignees->count() > 3)
                                                            <span
                                                                class="text-xs text-gray-400">+{{ $task->assignees->count() - 3 }}</span>
                                                        @endif
                                                    </div>
                                                @elseif ($task->assignee)
                                                    <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-[10px]"
                                                        title="{{ $task->assignee->name }}">
                                                        {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-300">Unassigned</span>
                                                @endif
                                                <div class="flex items-center gap-1">
                                                    <a href="{{ route('tasks.show', $task->token) }}"
                                                        class="w-6 h-6 flex items-center justify-center rounded text-blue-400 hover:bg-blue-50 transition-colors">
                                                        <i class="fa-regular fa-eye text-xs"></i>
                                                    </a>
                                                    <a href="{{ route('tasks.edit', $task->token) }}"
                                                        class="w-6 h-6 flex items-center justify-center rounded text-amber-400 hover:bg-amber-50 transition-colors">
                                                        <i class="fa-solid fa-pen text-xs"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-6 text-gray-300">
                                            <i class="fa-regular fa-clipboard text-2xl mb-1"></i>
                                            <p class="text-xs">No tasks</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                {{-- TOAST --}}
                <div id="kanban-toast"
                    style="position:fixed; top:24px; left:50%; transform:translateX(-50%); z-index:9999; display:none; align-items:center; gap:10px; background:#1e293b; color:#fff; padding:12px 20px; border-radius:12px;
                        box-shadow:0 8px 32px rgba(0,0,0,0.2); font-size:14px; font-weight:500;
                        min-width:240px; transition:opacity .3s;">
                    <span id="kanban-toast-icon"></span>
                    <span id="kanban-toast-msg"></span>
                </div>

                <script>
                    (function() {
                        let draggingCard = null,
                            sourceStatus = null,
                            placeholder = null;

                        function showToast(msg, type) {
                            const toast = document.getElementById('kanban-toast');
                            document.getElementById('kanban-toast-icon').innerHTML = type === 'error' ?
                                '<i class="fa-solid fa-circle-xmark" style="color:#f87171;font-size:16px"></i>' :
                                '<i class="fa-solid fa-circle-check" style="color:#34d399;font-size:16px"></i>';
                            document.getElementById('kanban-toast-msg').textContent = msg;
                            toast.style.display = 'flex';
                            clearTimeout(toast._t);
                            toast._t = setTimeout(() => {
                                toast.style.display = 'none';
                            }, 3000);
                        }

                        function makePlaceholder() {
                            const ph = document.createElement('div');
                            ph.style.cssText = `height:80px; border-radius:10px; border:2px dashed #6366f1;
                            background:rgba(99,102,241,0.07); flex-shrink:0; pointer-events:none;`;
                            return ph;
                        }

                        function afterElement(zone, y) {
                            return [...zone.querySelectorAll('.kanban-card')]
                                .filter(c => c !== draggingCard)
                                .reduce((closest, child) => {
                                    const offset = y - child.getBoundingClientRect().top - child.getBoundingClientRect()
                                        .height / 2;
                                    return offset < 0 && offset > closest.offset ? {
                                        offset,
                                        element: child
                                    } : closest;
                                }, {
                                    offset: -Infinity
                                }).element;
                        }

                        function onDragStart(e) {
                            draggingCard = this;
                            sourceStatus = this.dataset.status;
                            placeholder = makePlaceholder();
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/plain', this.dataset.taskId);
                            requestAnimationFrame(() => {
                                this.style.opacity = '0.4';
                                this.style.transform = 'rotate(1.5deg) scale(1.02)';
                                this.style.boxShadow = '0 16px 40px rgba(99,102,241,0.3)';
                            });
                        }

                        function onDragEnd() {
                            if (draggingCard) {
                                draggingCard.style.opacity = '';
                                draggingCard.style.transform = '';
                                draggingCard.style.boxShadow = '';
                            }
                            placeholder?.remove();
                            placeholder = null;
                            draggingCard = null;
                            sourceStatus = null;
                            document.querySelectorAll('.kanban-drop-zone').forEach(z => {
                                z.style.background = '';
                                z.style.outline = '';
                            });
                        }

                        function onDragOver(e) {
                            e.preventDefault();
                            if (!draggingCard) return;
                            this.style.background = 'rgba(99,102,241,0.05)';
                            this.style.outline = '2px dashed #a5b4fc';
                            this.style.outlineOffset = '-2px';
                            const after = afterElement(this, e.clientY);
                            after ? this.insertBefore(placeholder, after) : this.appendChild(placeholder);
                        }

                        function onDragLeave(e) {
                            if (!this.contains(e.relatedTarget)) {
                                this.style.background = '';
                                this.style.outline = '';
                            }
                        }

                        async function onDrop(e) {
                            e.preventDefault();
                            if (!draggingCard) return;

                            const card = draggingCard,
                                zone = this;
                            const newStatus = zone.dataset.status;
                            const oldStatus = sourceStatus;
                            const taskId = card.dataset.taskId;

                            zone.style.background = '';
                            zone.style.outline = '';
                            placeholder?.remove();

                            if (newStatus === oldStatus) {
                                const after = afterElement(zone, e.clientY);
                                after ? zone.insertBefore(card, after) : zone.appendChild(card);
                                return;
                            }

                            try {
                                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                                const res = await fetch(`/tasks/${taskId}/status`, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        status: newStatus
                                    }),
                                });

                                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                                const after = afterElement(zone, e.clientY);
                                after ? zone.insertBefore(card, after) : zone.appendChild(card);

                                card.dataset.status = newStatus;
                                const title = card.querySelector('p.text-sm.font-semibold');
                                if (title) {
                                    const done = newStatus === 'completed';
                                    title.classList.toggle('line-through', done);
                                    title.classList.toggle('text-gray-400', done);
                                    title.classList.toggle('text-gray-800', !done);
                                }

                                // Update badge count
                                [oldStatus, newStatus].forEach(s => {
                                    const z = document.querySelector(`.kanban-drop-zone[data-status="${s}"]`);
                                    if (!z) return;
                                    const empty = z.querySelector('.flex-col.items-center.justify-center');
                                    if (empty) empty.style.display = z.querySelectorAll('.kanban-card').length === 0 ?
                                        'flex' : 'none';
                                });
                                [oldStatus, newStatus].forEach(s => {
                                    const col = document.querySelector(`.kanban-drop-zone[data-status="${s}"]`);
                                    if (!col) return;
                                    const cnt = col.querySelectorAll('.kanban-card').length;
                                    const badge = col.closest('.flex-shrink-0')?.querySelector(
                                        'span.text-xs.font-bold');
                                    if (badge) badge.textContent = cnt;
                                });

                                const labels = {
                                    to_do: 'To Do',
                                    in_progress: 'In Progress',
                                    review: 'Review',
                                    completed: 'Completed',
                                    stopped: 'Stopped',
                                    cancelled: 'Cancelled'
                                };
                                showToast(`Moved to "${labels[newStatus] ?? newStatus}"`, 'success');

                            } catch (err) {
                                const srcZone = document.querySelector(`.kanban-drop-zone[data-status="${oldStatus}"]`);
                                if (srcZone) srcZone.appendChild(card);
                                showToast('Failed to update. Please try again.', 'error');
                            }
                        }

                        document.querySelectorAll('.kanban-card').forEach(card => {
                            card.addEventListener('dragstart', onDragStart);
                            card.addEventListener('dragend', onDragEnd);
                        });

                        document.querySelectorAll('.kanban-drop-zone').forEach(zone => {
                            zone.addEventListener('dragover', onDragOver);
                            zone.addEventListener('dragleave', onDragLeave);
                            zone.addEventListener('drop', onDrop);
                        });
                    })();
                </script>
            @endif
        </div>{{-- END SINGLE CARD --}}

    </div>

    {{-- Status Dropdown Portal --}}
    <div id="task-status-dropdown-portal"
        class="hidden fixed z-[9999] w-40 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden py-1">
    </div>

    <script>
        const taskPortal = document.getElementById('task-status-dropdown-portal');
        let activeTaskBtn = null;
        let taskCsrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        function openTaskStatusDropdown(btn) {
            if (activeTaskBtn === btn && !taskPortal.classList.contains('hidden')) {
                closeTaskDropdown();
                return;
            }
            activeTaskBtn = btn;
            const options = JSON.parse(btn.dataset.options);
            const current = btn.dataset.currentStatus;
            const url = btn.dataset.updateUrl;
            taskPortal.innerHTML = options.map(opt => `
                <form method="POST" action="${url}">
                    <input type="hidden" name="_token" value="${taskCsrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="status" value="${opt.value}">
                    <button type="submit"
                        class="w-full text-left px-3 py-2 text-xs transition-colors hover:bg-gray-50
                        ${opt.value === current ? 'bg-gray-100 font-semibold' : ''}">
                        ${opt.label}
                    </button>
                </form>
            `).join('');
            taskPortal.classList.remove('hidden');
            const rect = btn.getBoundingClientRect();
            const dropdownHeight = taskPortal.offsetHeight;
            const spaceBelow = window.innerHeight - rect.bottom;
            const top = spaceBelow < dropdownHeight + 8 ?
                rect.top + window.scrollY - dropdownHeight - 4 :
                rect.bottom + window.scrollY + 4;
            taskPortal.style.top = top + 'px';
            taskPortal.style.left = (rect.left + window.scrollX) + 'px';
        }

        function closeTaskDropdown() {
            taskPortal.classList.add('hidden');
            activeTaskBtn = null;
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#task-status-dropdown-portal') && !e.target.closest('[id^="status-btn-"]')) {
                closeTaskDropdown();
            }
        });

        window.addEventListener('scroll', closeTaskDropdown, true);
    </script>


    <!-- UNTUK POP UP DI SINI YA GES YA -->
    <div id="taskInfoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">
                About my tasks
            </h2>

            <div class="space-y-4 text-sm text-slate-600">

                <p>
                    This page shows all tasks assigned to you across every project in one place.
                </p>

                <div class="border-t pt-4 space-y-3">
                    <div class="flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Personal scope</span> —
                            Shows only tasks where you're listed as an assignee, across all your projects.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Three views</span> —
                            Switch between List, Gantt (with critical path), and Kanban — your last choice is remembered.
                        </p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Drag to update status</span> —
                            On the Kanban board, drag a card to another column to update its status instantly.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Smart sorting</span> —
                            Tasks closest to their due date appear first; completed and cancelled tasks sink to the bottom.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button onclick="confirmTaskInfo()" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Done
                </button>
            </div>

        </div>
    </div>

    <!-- USCIPT POP UP BOSKU -->
    <script>
        function openTaskInfoModal() {
            document.getElementById('taskInfoModal').classList.remove('hidden');
            document.getElementById('taskInfoModal').classList.add('flex');
        }

        function closeTaskInfoModal() {
            document.getElementById('taskInfoModal').classList.add('hidden');
            document.getElementById('taskInfoModal').classList.remove('flex');
        }

        function confirmTaskInfo() {
            closeTaskInfoModal();
            console.log("User lanjut task");
        }
    </script>

    <script>
        document.querySelectorAll('a[href*="view="]').forEach(link => {
            link.addEventListener('click', function() {
                const url = new URL(this.href);
                const view = url.searchParams.get('view');
                if (view) localStorage.setItem('tasks_preferred_view', view);
            });
        });
    </script>
@endsection
