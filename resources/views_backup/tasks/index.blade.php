@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
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

    <div class="bg-gray-50" style="height: calc(100vh - 121px); display: flex; flex-direction: column; overflow: hidden;">

        {{-- header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <h1 class="text-3xl font-bold text-gray-800">My Tasks</h1>
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
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl flex-1" style="min-height:0; position:relative;">

            {{-- list --}}
            @if ($currentView === 'list')
                <div style="position:absolute; inset:0; overflow-x:auto; overflow-y:auto;">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-[#ADE8F4]">
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Task</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Project</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Workspace</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Status</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Start Date</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Due Date</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($tasks as $task)
                                @php
                                    // filter
                                    if ($currentStatus !== 'all' && $task->status !== $currentStatus) {
                                        continue;
                                    }

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
                                        <span
                                            class="block text-sm font-semibold text-gray-900 {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                            {{ $task->name }}
                                        </span>
                                        @if ($task->description)
                                            <span
                                                class="block text-xs text-gray-400 mt-0.5">{{ Str::limit($task->description, 40) }}</span>
                                        @endif
                                    </td>

                                    {{-- project --}}
                                    <td class="px-5 py-4">
                                        @if ($task->project)
                                            <span class="inline-flex items-center gap-2 text-sm text-gray-500">
                                                <i class="fa-solid fa-diagram-project"></i>{{ $task->project->name }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- workspace --}}
                                    <td class="px-5 py-4">
                                        @if ($task->project?->workspace)
                                            <span
                                                class="inline-flex items-center gap-2 text-sm text-gray-500 whitespace-nowrap">
                                                <i
                                                    class="fa-solid fa-layer-group w-5 text-center text-sm"></i>{{ $task->project->workspace->name }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- status --}}
                                    <td class="px-5 py-4">
                                        <button id="status-btn-{{ $task->id }}" data-task-id="{{ $task->id }}"
                                            data-current-status="{{ $task->status }}"
                                            data-update-url="{{ route('tasks.updateStatus', $task) }}"
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
                                            <a href="{{ route('tasks.show', $task) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            {{-- Edit: hanya manager & member --}}
                                            @if ($task->project && $task->project->canContribute(Auth::user()))
                                                <a href="{{ route('tasks.edit', $task) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                            @endif

                                            {{-- Delete: hanya manager --}}
                                            @if ($task->project && $task->project->isManager(Auth::user()))
                                                <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                                    class="inline" onsubmit="return confirm('Delete this task?');">
                                                    @csrf @method('DELETE')
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
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-16 h-16 mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                                                <i class="fa-regular fa-clipboard text-2xl text-gray-400"></i>
                                            </div>
                                            <p class="text-sm text-gray-500 font-medium">No tasks found</p>
                                            <p class="text-xs text-gray-400 mt-1">Create your first task to get started</p>
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
                <div id="gantt_here" style="position:absolute; inset:0; width:100%; height:100%;"></div>
                <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css">
                <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
                <script>
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
                        },
                    ];
                    gantt.config.columns = [{
                            name: "text",
                            label: "Task / Project",
                            tree: true,
                            width: 220
                        },
                        {
                            name: "start_date",
                            label: "Mulai",
                            align: "center",
                            width: 90
                        },
                        {
                            name: "duration",
                            label: "Durasi",
                            align: "center",
                            width: 60,
                            template: function(task) {
                                return task.duration + "d";
                            }
                        },
                        {
                            name: "priority",
                            label: "Prioritas",
                            align: "center",
                            width: 80,
                            template: function(task) {
                                return task.priority ?? "—";
                            }
                        },
                    ];
                    gantt.config.readonly = true;
                    gantt.config.open_tree_initially = true;
                    gantt.config.fit_tasks = true;
                    gantt.templates.task_class = function(start, end, task) {
                        if (!task.parent) return "gantt-project";
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
                    gantt.templates.tooltip_text = function(start, end, task) {
                        if (!task.parent) return "<b>" + task.text + "</b>";
                        return "<b>" + task.text + "</b><br/>" +
                            "Status: " + (task.status ?? "—") + "<br/>" +
                            "Prioritas: " + (task.priority ?? "—") + "<br/>" +
                            "Mulai: " + gantt.templates.tooltip_date_format(start) + "<br/>" +
                            "Selesai: " + gantt.templates.tooltip_date_format(end);
                    };
                    gantt.init("gantt_here");
                    gantt.load("{{ route('gant.data') }}", "json");
                </script>
                <style>
                    .gantt-project .gantt_task_content {
                        background: #6366f1;
                        color: white;
                        font-weight: 600;
                        border-radius: 4px;
                    }

                    .gantt-project .gantt_task_progress {
                        background: #4f46e5;
                    }

                    .gantt-done .gantt_task_content {
                        background: #22c55e;
                        color: white;
                        border-radius: 4px;
                    }

                    .gantt-done .gantt_task_progress {
                        background: #16a34a;
                    }

                    .gantt-progress .gantt_task_content {
                        background: #eab308;
                        color: white;
                        border-radius: 4px;
                    }

                    .gantt-progress .gantt_task_progress {
                        background: #ca8a04;
                    }

                    .gantt-review .gantt_task_content {
                        background: #3b82f6;
                        color: white;
                        border-radius: 4px;
                    }

                    .gantt-review .gantt_task_progress {
                        background: #2563eb;
                    }

                    .gantt-todo .gantt_task_content {
                        background: #94a3b8;
                        color: white;
                        border-radius: 4px;
                    }

                    .gantt-todo .gantt_task_progress {
                        background: #64748b;
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
                                    'cancelled' => '#f4f4f5',
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
                                            draggable="true" data-task-id="{{ $task->id }}"
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
                                                @if ($task->assignees->count())
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
                                                @else
                                                    <span class="text-xs text-gray-300">Unassigned</span>
                                                @endif
                                                <div class="flex items-center gap-1">
                                                    <a href="{{ route('tasks.show', $task) }}"
                                                        class="w-6 h-6 flex items-center justify-center rounded text-blue-400 hover:bg-blue-50 transition-colors">
                                                        <i class="fa-regular fa-eye text-xs"></i>
                                                    </a>
                                                    <a href="{{ route('tasks.edit', $task) }}"
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
                    style="position:fixed; top:24px; left:50%; transform:translateX(-50%); z-index:9999; display:none; align-items:center; gap:10px;
                           background:#1e293b; color:#fff; padding:12px 20px; border-radius:12px;
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

@endsection
