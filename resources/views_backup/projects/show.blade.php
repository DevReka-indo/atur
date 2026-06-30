@extends('layouts.app')

@section('title', $project->name)

@section('content')
    @php
        $isManager = $project->isManager(Auth::user());
        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('status', 'completed')->count();
        $overdueTasks = $project->tasks->filter(fn($task) => $task->isOverdue())->count();
        $progress = $project->tasks->count() > 0 ? round($project->calculateProgress(), 1) : 0;
        $canManageMembers = $project->isManager(Auth::user());
        $canContribute = $project->canContribute(Auth::user());
        $currentView = request('view', 'list');
        $currentTab = request('tab', 'tasks');
        $kanbanStatuses = [
            'to_do' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'completed' => 'Completed',
            'stopped' => 'Stopped',
            'cancelled' => 'Cancelled',
        ];
        $kanbanTasks = $project->tasks->groupBy('status');
    @endphp

    <div class="min-h-screen bg-gray-50 px-2 py-4 sm:px-4 lg:px-6">

        {{-- Header Section --}}
        <div class="max-w-7xl mx-auto mb-8">
            {{-- Breadcrumb --}}
            <nav class="text-sm text-gray-500 mb-4 flex items-center gap-2">
                <a href="{{ route('workspaces.show', $project->workspace) }}"
                    class="hover:text-indigo-600 hover:underline transition-colors">
                    {{ $project->workspace->name }}
                </a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">{{ $project->name }}</span>
            </nav>

            {{-- Project Title & Actions --}}
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-6">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ $project->name }}</h1>
                    <div class="flex flex-wrap items-center gap-4">
                        {{-- Status Dropdown --}}
                        <div class="relative" data-dropdown="project-status">
                            @php
                                $statusColors = [
                                    'planning' => 'bg-gray-200 text-gray-700',
                                    'active' => 'bg-blue-300 text-blue-700',
                                    'on_hold' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-300 text-green-700',
                                    'cancelled' => 'bg-red-200 text-red-700',
                                ];
                            @endphp
                            <button onclick="toggleDropdown('project-status')"
                                class="px-3 py-1.5 text-sm font-medium rounded-full transition flex items-center gap-2 hover:opacity-80
                            {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ str($project->status)->replace('_', ' ')->title() }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="dropdown-project-status"
                                class="dropdown-menu hidden absolute mt-2 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden">
                                @foreach (['planning', 'active', 'on_hold', 'completed', 'cancelled'] as $status)
                                    <form method="POST" action="{{ route('projects.updateStatus', $project) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 transition
                                        {{ $project->status === $status ? 'bg-gray-100 font-semibold' : '' }}">
                                            {{ str($status)->replace('_', ' ')->title() }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>

                        <span class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $project->start_date?->format('d M Y') ?? '-' }} -
                            {{ $project->end_date?->format('d M Y') ?? '-' }}
                        </span>
                    </div>
                </div>

                @if ($isManager)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('projects.edit', $project) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white
                        bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </a>
                        <button onclick="openModal('delete-project-modal')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white
                        bg-red-600 hover:bg-red-700 rounded-xl transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    </div>
                @endif
            </div>

            {{-- Progress Bar --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                    <span class="text-2xl font-bold text-indigo-600">{{ round($progress, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500"
                        style="width: {{ $progress }}%"></div>
                </div>
            </div>
        </div>

        {{-- Main Content - FULL HEIGHT LAYOUT --}}
        <div class="max-w-7xl mx-auto" style="height: calc(100vh - 280px);">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full"
                style="display: flex; flex-direction: column; overflow: hidden;">

                {{-- Tab Bar --}}
                <div class="border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center justify-between px-2 pt-2 pb-2">
                        <nav class="flex gap-1">
                            <button onclick="switchTab('tasks')" data-tab="tasks"
                                class="tab-button px-4 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ $currentTab === 'tasks' ? 'bg-[#ADE8F4] text-gray-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Tasks
                                </div>
                            </button>
                            <button onclick="switchTab('members')" data-tab="members"
                                class="tab-button px-4 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ $currentTab === 'members' ? 'bg-[#ADE8F4] text-gray-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Members
                                </div>
                            </button>
                            <button onclick="switchTab('chart')" data-tab="chart"
                                class="tab-button px-4 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ $currentTab === 'chart' ? 'bg-[#ADE8F4] text-gray-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Progress Chart
                                </div>
                            </button>
                        </nav>

                        {{-- View Toggle + Create Task (only in tasks tab) --}}
                        <div id="tasks-actions"
                            class="flex items-center gap-3 {{ $currentTab !== 'tasks' ? 'hidden' : '' }}">
                            {{-- View Dropdown --}}
                            <div class="relative group">
                                <button
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl
                                text-sm font-medium text-gray-600 hover:bg-gray-50 shadow-sm transition-all cursor-pointer">
                                    @if ($currentView === 'gantt')
                                        <i class="fa-solid fa-chart-gantt text-indigo-500"></i> Gantt
                                    @elseif ($currentView === 'kanban')
                                        <i class="fa-solid fa-table-columns text-indigo-500"></i> Kanban
                                    @else
                                        <i class="fa-solid fa-list text-indigo-500"></i> List
                                    @endif
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                                </button>
                                <div
                                    class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-50
                                invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200">
                                    <a href="{{ route('projects.show', ['project' => $project->id, 'view' => 'list']) }}"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-xl transition-colors
                                    {{ $currentView === 'list' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <i class="fa-solid fa-list w-4 text-center"></i> List
                                    </a>
                                    <a href="{{ route('projects.show', ['project' => $project->id, 'view' => 'gantt']) }}"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm border-y border-gray-100 transition-colors
                                    {{ $currentView === 'gantt' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <i class="fa-solid fa-chart-gantt w-4 text-center"></i> Gantt
                                    </a>
                                    <a href="{{ route('projects.show', ['project' => $project->id, 'view' => 'kanban']) }}"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-b-xl transition-colors
                                    {{ $currentView === 'kanban' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <i class="fa-solid fa-table-columns w-4 text-center"></i> Kanban
                                    </a>
                                </div>
                            </div>

                            {{-- Create Task --}}
                            @if ($canContribute)
                                <a href="{{ route('tasks.create') }}?project_id={{ $project->id }}"
                                    class="group inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg
                                bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/30 transition-all duration-300">
                                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                                    Create Task
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ============ TASKS TAB ============ --}}
                <div id="tab-tasks" class="tab-content {{ $currentTab !== 'tasks' ? 'hidden' : '' }}"
                    style="flex: 1; overflow: hidden; position: relative;">
                    {{-- LIST VIEW --}}
                    @if ($currentView === 'list')
                        <div style="position: absolute; inset: 0; overflow-x: auto; overflow-y: auto;">
                            <div class="p-6">
                                <div class="rounded-xl border border-gray-200 overflow-hidden">
                                    <table class="min-w-full text-sm text-left">
                                        <thead class="bg-cyan-200 text-gray-700 uppercase text-xs tracking-wide">
                                            <tr class="bg-[#ADE8F4]">
                                                <th class="px-6 py-3">Task</th>
                                                <th class="px-6 py-3">Duration</th>
                                                <th class="px-6 py-3">Status</th>
                                                <th class="px-6 py-3">Start Date</th>
                                                <th class="px-6 py-3">Due Date</th>
                                                <th class="px-6 py-3">Progress</th>
                                                <th class="px-6 py-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @forelse ($project->tasks as $task)
                                                @php
                                                    $taskStatusColors = [
                                                        'to_do' => 'bg-yellow-100 text-yellow-700',
                                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                                        'review' => 'bg-purple-100 text-purple-700',
                                                        'completed' => 'bg-green-100 text-green-700',
                                                    ];

                                                    $startDate = $task->start_date ?? $task->created_at;
                                                    $dueDate = $task->due_date ?? $task->created_at->copy()->addDay();
                                                    $durationDays = max(0, $startDate->diffInDays($dueDate, false));

                                                    $progressPercentage = match ($task->status) {
                                                        'to_do' => 0,
                                                        'in_progress' => 50,
                                                        'review' => 80,
                                                        'completed' => 100,
                                                        'stopped' => 0,
                                                        'cancelled' => 0,
                                                        default => 0,
                                                    };
                                                @endphp
                                                <tr class="hover:bg-gray-50 transition">
                                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $task->name }}
                                                    </td>
                                                    <td class="px-6 py-4 text-gray-600">
                                                        @if ($task->start_date && $task->due_date)
                                                            <span class="inline-flex items-center gap-1">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                {{ $durationDays }} days
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        @php
                                                            $statusOptions = [
                                                                ['value' => 'to_do', 'label' => 'To Do'],
                                                                ['value' => 'in_progress', 'label' => 'In Progress'],
                                                                ['value' => 'review', 'label' => 'Review'],
                                                                ['value' => 'completed', 'label' => 'Completed'],
                                                                ['value' => 'stopped', 'label' => 'Stopped'],
                                                                ['value' => 'cancelled', 'label' => 'Cancelled'],
                                                            ];
                                                        @endphp
                                                        <button id="status-btn-{{ $task->id }}"
                                                            data-task-id="{{ $task->id }}"
                                                            data-current-status="{{ $task->status }}"
                                                            data-update-url="{{ route('tasks.updateStatus', $task) }}"
                                                            data-options="{{ json_encode($statusOptions) }}"
                                                            onclick="openTaskStatusDropdown(this)"
                                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $taskStatusColors[$task->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M19 9l-7 7-7-7" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-4 text-gray-600">
                                                        {{ $task->start_date?->format('d M Y') ?? '-' }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span
                                                            class="flex items-center gap-2 {{ $task->isOverdue() ? 'text-red-600' : 'text-gray-600' }}">
                                                            <span class="w-2 h-2 rounded-full bg-current"></span>
                                                            {{ $task->due_date?->format('d M Y') ?? '-' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="w-full">
                                                            <div class="flex items-center justify-between mb-1">
                                                                <span
                                                                    class="text-xs font-medium text-gray-600">{{ $progressPercentage }}%</span>
                                                            </div>
                                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                                                                    style="width: {{ $progressPercentage }}%"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div
                                                            class="table-action-icons flex items-center justify-center gap-3">
                                                            <a href="{{ route('tasks.show', $task) }}"
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                                <i class="fa-regular fa-eye"></i>
                                                            </a>
                                                            @if ($isManager)
                                                                <a href="{{ route('tasks.edit', $task) }}"
                                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                                    <i class="fa-solid fa-pen"></i>
                                                                </a>
                                                                <form method="POST"
                                                                    action="{{ route('tasks.destroy', $task) }}"
                                                                    class="inline"
                                                                    onsubmit="return confirm('Delete this task?')">
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
                                                    <td colspan="7" class="px-6 py-20 text-center">
                                                        <div class="flex flex-col items-center justify-center gap-3">
                                                            <div
                                                                class="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center">
                                                                <svg class="w-8 h-8 text-indigo-400" fill="none"
                                                                    stroke="currentColor" stroke-width="1.5"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <p class="text-sm font-semibold text-gray-700">Belum ada
                                                                    task</p>
                                                                <p class="text-xs text-gray-400 mt-1">Mulai dengan membuat
                                                                    task pertama di project ini.</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- GANTT VIEW --}}
                    @if ($currentView === 'gantt')
                        <div id="gantt_project" style="position: absolute; inset: 0; width: 100%; height: 100%;"></div>
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
                                    label: "Task",
                                    tree: true,
                                    width: 200
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
                                    template: t => t.duration + "d"
                                },
                                {
                                    name: "priority",
                                    label: "Prioritas",
                                    align: "center",
                                    width: 80,
                                    template: t => t.priority ?? "—"
                                },
                            ];
                            gantt.config.readonly = true;
                            gantt.config.open_tree_initially = true;
                            gantt.config.fit_tasks = true;
                            gantt.templates.task_class = function(start, end, task) {
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
                                return "<b>" + task.text + "</b><br/>" +
                                    "Status: " + (task.status ?? "—") + "<br/>" +
                                    "Prioritas: " + (task.priority ?? "—") + "<br/>" +
                                    "Mulai: " + gantt.templates.tooltip_date_format(start) + "<br/>" +
                                    "Selesai: " + gantt.templates.tooltip_date_format(end);
                            };
                            gantt.init("gantt_project");
                            gantt.parse({
                                data: [
                                    @foreach ($project->tasks as $task)
                                        @php
                                            $gStart = $task->start_date ?? $task->created_at;
                                            $gEnd = $task->due_date ?? $task->created_at->copy()->addDay();
                                            $gDur = max(1, $gStart->diffInDays($gEnd));
                                        @endphp {
                                            id: {{ $task->id }},
                                            text: "{{ addslashes($task->name) }}",
                                            start_date: "{{ $gStart->format('d-m-Y') }}",
                                            duration: {{ $gDur }},
                                            status: "{{ $task->status }}",
                                            priority: "{{ $task->priority ?? '' }}",
                                            progress: {{ $task->status === 'completed' ? 1 : 0 }},
                                        },
                                    @endforeach
                                ],
                                links: []
                            });
                        </script>
                        <style>
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

                    {{-- KANBAN VIEW --}}
                    @if ($currentView === 'kanban')
                        <div style="position: absolute; inset: 0; overflow-x: auto; overflow-y: hidden;">
                            <div style="display: flex; gap: 1rem; padding: 1rem; min-width: max-content; height: 100%;">
                                @foreach ($kanbanStatuses as $statusKey => $statusLabel)
                                    @php
                                        $columnTasks = $kanbanTasks->get($statusKey, collect());
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
                                    <div class="kanban-column flex-shrink-0 bg-gray-100 rounded-xl border-t-4 {{ $columnColor }}"
                                        data-status="{{ $statusKey }}"
                                        style="width:270px; display:flex; flex-direction:column; height:100%;">
                                        <div class="px-4 py-3 flex items-center justify-between rounded-t-xl flex-shrink-0"
                                            style="background-color:{{ $headerBg }}; color:{{ $headerText }};">
                                            <span class="text-sm font-semibold">{{ $statusLabel }}</span>
                                            <span
                                                class="kanban-count text-xs font-bold px-2 py-0.5 rounded-full bg-white bg-opacity-70">
                                                {{ $columnTasks->count() }}
                                            </span>
                                        </div>
                                        <div class="kanban-drop-zone p-3 flex flex-col gap-3 overflow-y-auto flex-1"
                                            data-status="{{ $statusKey }}">
                                            @forelse ($columnTasks as $task)
                                                @php
                                                    $priorityColor = match ($task->priority ?? '') {
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
                                                        <span class="text-gray-300 select-none" title="Drag to move">
                                                            <i class="fa-solid fa-grip-vertical text-xs"></i>
                                                        </span>
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
                                                    <div
                                                        class="flex items-center justify-between pt-2 border-t border-gray-100">
                                                        @if ($task->assignee ?? null)
                                                            <span
                                                                class="inline-flex items-center gap-1 text-xs text-gray-500">
                                                                <div
                                                                    class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-[10px]">
                                                                    {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                                                </div>
                                                                {{ Str::limit($task->assignee->name, 12) }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-gray-300">Unassigned</span>
                                                        @endif
                                                        <div class="flex items-center gap-1">
                                                            <a href="{{ route('tasks.show', $task) }}"
                                                                class="w-6 h-6 flex items-center justify-center rounded text-blue-400 hover:bg-blue-50 transition-colors">
                                                                <i class="fa-regular fa-eye text-xs"></i>
                                                            </a>
                                                            @if ($canContribute)
                                                                <a href="{{ route('tasks.edit', $task) }}"
                                                                    class="w-6 h-6 flex items-center justify-center rounded text-amber-400 hover:bg-amber-50 transition-colors">
                                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div
                                                    class="kanban-empty flex flex-col items-center justify-center py-6 text-gray-300">
                                                    <i class="fa-regular fa-clipboard text-2xl mb-1"></i>
                                                    <p class="text-xs">No tasks</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ========== TOAST NOTIFICATION ========== --}}
                        <div id="kanban-toast"
                            style="position:fixed; top:24px; left:50%; transform:translateX(-50%); z-index:9999; display:none; align-items:center; gap:10px;
           background:#1e293b; color:#fff; padding:12px 20px; border-radius:12px;
           box-shadow:0 8px 32px rgba(0,0,0,0.2); font-size:14px; font-weight:500;
           min-width:240px; transition:opacity .3s;">
                            <span id="kanban-toast-icon"></span>
                            <span id="kanban-toast-msg"></span>
                        </div>

                        @push('scripts')
                            <script>
                                (function() {
                                    /* ── State ─────────────────────────────────────────────────────── */
                                    let draggingCard = null;
                                    let sourceStatus = null;
                                    let placeholder = null;

                                    /* ── Toast helper ──────────────────────────────────────────────── */
                                    function showToast(msg, type) {
                                        const toast = document.getElementById('kanban-toast');
                                        const icon = document.getElementById('kanban-toast-icon');
                                        const msgEl = document.getElementById('kanban-toast-msg');
                                        icon.innerHTML = type === 'error' ?
                                            '<i class="fa-solid fa-circle-xmark" style="color:#f87171;font-size:16px"></i>' :
                                            '<i class="fa-solid fa-circle-check" style="color:#34d399;font-size:16px"></i>';
                                        msgEl.textContent = msg;
                                        toast.style.display = 'flex';
                                        clearTimeout(toast._t);
                                        toast._t = setTimeout(() => {
                                            toast.style.display = 'none';
                                        }, 3000);
                                    }

                                    function makePlaceholder() {
                                        const ph = document.createElement('div');
                                        ph.id = 'kanban-ph';
                                        ph.style.cssText = `
                                            height:80px; border-radius:10px;
                                            border:2px dashed #6366f1;
                                            background:rgba(99,102,241,0.07);
                                            flex-shrink:0; pointer-events:none;
                                            transition:height .1s;`;
                                        return ph;
                                    }

                                    function refreshCount(zone) {
                                        const col = zone.closest('.kanban-column');
                                        if (!col) return;
                                        const cnt = col.querySelectorAll('.kanban-card').length;
                                        const badge = col.querySelector('.kanban-count');
                                        if (badge) badge.textContent = cnt;
                                        const empty = zone.querySelector('.kanban-empty');
                                        if (empty) empty.style.display = cnt === 0 ? 'flex' : 'none';
                                    }

                                    /* ── Find element to insert before based on mouse Y ────────────── */
                                    function afterElement(zone, y) {
                                        const cards = [...zone.querySelectorAll('.kanban-card')]
                                            .filter(c => c !== draggingCard);
                                        return cards.reduce((closest, child) => {
                                            const box = child.getBoundingClientRect();
                                            const offset = y - box.top - box.height / 2;
                                            if (offset < 0 && offset > closest.offset)
                                                return {
                                                    offset,
                                                    element: child
                                                };
                                            return closest;
                                        }, {
                                            offset: -Infinity
                                        }).element;
                                    }

                                    /* ── Drag card events ───────────────────────────────────────────── */
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

                                    /* ── Drop-zone events ───────────────────────────────────────────── */
                                    function onDragOver(e) {
                                        e.preventDefault();
                                        e.dataTransfer.dropEffect = 'move';
                                        if (!draggingCard) return;

                                        this.style.background = 'rgba(99,102,241,0.05)';
                                        this.style.outline = '2px dashed #a5b4fc';
                                        this.style.outlineOffset = '-2px';

                                        const after = afterElement(this, e.clientY);
                                        if (after) this.insertBefore(placeholder, after);
                                        else this.appendChild(placeholder);
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

                                        // ── Simpan referensi SEBELUM async ──
                                        const card = draggingCard;
                                        const zone = this;
                                        const newStatus = zone.dataset.status;
                                        const oldStatus = sourceStatus;
                                        const taskId = card.dataset.taskId;

                                        zone.style.background = '';
                                        zone.style.outline = '';
                                        placeholder?.remove();

                                        /* Same column: re-order saja */
                                        if (newStatus === oldStatus) {
                                            const after = afterElement(zone, e.clientY);
                                            after ? zone.insertBefore(card, after) : zone.appendChild(card);
                                            return;
                                        }

                                        /* Cross-column: PATCH ke server */
                                        try {
                                            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ||
                                                '{{ csrf_token() }}';

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

                                            /* Pindah card di DOM */
                                            const after = afterElement(zone, e.clientY);
                                            after ? zone.insertBefore(card, after) : zone.appendChild(card);

                                            /* Update card dataset & strikethrough */
                                            card.dataset.status = newStatus;
                                            const title = card.querySelector('p.text-sm.font-semibold');
                                            if (title) {
                                                const done = newStatus === 'completed';
                                                title.classList.toggle('line-through', done);
                                                title.classList.toggle('text-gray-400', done);
                                                title.classList.toggle('text-gray-800', !done);
                                            }

                                            /* Refresh badge count */
                                            const srcZone = document.querySelector(`.kanban-drop-zone[data-status="${oldStatus}"]`);
                                            if (srcZone) refreshCount(srcZone);
                                            refreshCount(zone);

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
                                            console.error('Kanban drop error:', err);
                                            /* Rollback ke kolom asal */
                                            const srcZone = document.querySelector(`.kanban-drop-zone[data-status="${oldStatus}"]`);
                                            if (srcZone) srcZone.appendChild(card);
                                            showToast('Failed to update. Please try again.', 'error');
                                        }
                                    }

                                    /* ── Bootstrap ──────────────────────────────────────────────────── */
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
                        @endpush
                    @endif
                </div>{{-- END TASKS TAB --}}

                {{-- ============ MEMBERS TAB ============ --}}
                <div id="tab-members" class="tab-content {{ $currentTab !== 'members' ? 'hidden' : '' }}"
                    style="flex: 1; overflow-y: auto; position: relative;">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">Projects Members</h3>
                                <p class="text-gray-500 text-sm mt-1">Manage your project team members</p>
                            </div>
                            <div class="bg-[#ADE8F4] text-gray-700 px-4 py-2 rounded-lg font-semibold text-sm">
                                {{ $project->members->count() }} {{ Str::plural('Member', $project->members->count()) }}
                            </div>
                        </div>
                        @if ($canManageMembers)
                            @php
                                $candidateList = $availableMembers
                                    ->map(
                                        fn($u) => [
                                            'id' => $u->id,
                                            'name' => $u->name,
                                            'initials' => strtoupper(
                                                implode(
                                                    '',
                                                    array_map(
                                                        fn($w) => $w[0],
                                                        array_slice(explode(' ', $u->name), 0, 2),
                                                    ),
                                                ),
                                            ),
                                        ],
                                    )
                                    ->values()
                                    ->toArray();
                            @endphp

                            {{-- ===== FORM WRAPPER ===== --}}
                            <form method="POST" action="{{ route('projects.members.store', $project) }}"
                                id="addMemberForm" class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                                @csrf

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                                    {{-- Trigger button (pengganti <select>) --}}
                                    <div class="md:col-span-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Select workspace member
                                        </label>

                                        {{-- Hidden input yang dikirim ke server --}}
                                        <input type="hidden" name="user_id" id="selectedUserId">

                                        {{-- Tombol trigger modal --}}
                                        <button type="button" id="memberTriggerBtn" onclick="memberModal.open()"
                                            class="w-full flex items-center justify-between gap-2 px-4 py-2.5
                       border border-gray-300 rounded-lg bg-white text-sm
                       hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300
                       transition-colors text-gray-400">
                                            <span id="memberTriggerLabel">Select workspace member</span>
                                            <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Role --}}
                                    <div class="md:col-span-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                        <select name="role"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg
                       focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required>
                                            <option value="member">Member</option>
                                            <option value="manager">Manager</option>
                                            <option value="viewer">Viewer</option>
                                        </select>
                                    </div>

                                    {{-- Submit --}}
                                    <div class="md:col-span-3">
                                        <button type="submit"
                                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg
                       px-4 py-2.5 font-medium transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                            Add Member
                                        </button>
                                    </div>
                                </div>
                            </form>

                            {{-- ===== BACKDROP ===== --}}
                            <div id="memberBackdrop" onclick="memberModal.close()"
                                style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:998;">
                            </div>

                            {{-- ===== MODAL ===== --}}
                            <div id="memberModalBox"
                                style="display:none; position:fixed; inset:0; z-index:999;
            align-items:center; justify-content:center; pointer-events:none;">

                                <div id="memberModalInner" onclick="event.stopPropagation()"
                                    style="pointer-events:auto; background:#fff; border-radius:16px;
                border:1px solid #e5e7eb; width:100%; max-width:440px; margin:0 1rem;
                display:flex; flex-direction:column; max-height:540px;
                box-shadow:0 20px 60px rgba(0,0,0,0.15);">

                                    {{-- Header --}}
                                    <div style="padding:18px 20px 12px; border-bottom:1px solid #f1f5f9;">
                                        <div
                                            style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                            <h3 style="font-size:15px; font-weight:600; color:#111827; margin:0;">
                                                Pilih Workspace Member
                                            </h3>
                                            <button onclick="memberModal.close()" type="button"
                                                style="width:28px; height:28px; border:none; background:transparent;
                               cursor:pointer; color:#9ca3af; font-size:20px; line-height:1;
                               display:flex; align-items:center; justify-content:center;
                               border-radius:6px;"
                                                onmouseover="this.style.background='#f3f4f6'"
                                                onmouseout="this.style.background='transparent'">
                                                &times;
                                            </button>
                                        </div>

                                        {{-- Search --}}
                                        <div id="memberSearchWrap"
                                            style="display:flex; align-items:center; gap:8px;
                        background:#f9fafb; border:1px solid #e5e7eb;
                        border-radius:8px; padding:8px 12px; transition:border-color .15s;">
                                            <svg style="width:14px;height:14px;color:#9ca3af;flex-shrink:0;"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <circle cx="11" cy="11" r="7" />
                                                <path d="M21 21l-4.35-4.35" />
                                            </svg>
                                            <input type="text" id="memberSearchInput"
                                                placeholder="Cari nama member..." oninput="memberModal.filter()"
                                                style="flex:1; border:none; background:transparent; outline:none;
                              font-size:13px; color:#374151; font-family:inherit;"
                                                autocomplete="off">
                                            <button id="memberSearchClear" type="button"
                                                onclick="memberModal.clearSearch()"
                                                style="display:none; border:none; background:transparent;
                               cursor:pointer; color:#9ca3af; font-size:18px; line-height:1; padding:0;">
                                                &times;
                                            </button>
                                        </div>
                                    </div>

                                    {{-- List --}}
                                    <div id="memberList" style="flex:1; overflow-y:auto; padding:6px 0;">
                                    </div>

                                    {{-- Footer --}}
                                    <div
                                        style="padding:12px 20px; border-top:1px solid #f1f5f9;
                    display:flex; align-items:center; justify-content:space-between;">
                                        <p style="font-size:12px; color:#9ca3af; margin:0;">
                                            <span id="memberSelCount" style="color:#4f46e5; font-weight:600;">0</span>
                                            dipilih &bull;
                                            <span id="memberFilterCount"></span> member
                                        </p>
                                        <div style="display:flex; gap:8px;">
                                            <button type="button" onclick="memberModal.close()"
                                                style="padding:7px 16px; border-radius:8px; border:1px solid #e5e7eb;
                               background:#fff; color:#374151; font-size:13px; cursor:pointer;
                               font-family:inherit;"
                                                onmouseover="this.style.background='#f9fafb'"
                                                onmouseout="this.style.background='#fff'">
                                                Batal
                                            </button>
                                            <button type="button" onclick="memberModal.confirm()"
                                                style="padding:7px 16px; border-radius:8px; border:none;
                               background:#4f46e5; color:#fff; font-size:13px;
                               font-weight:500; cursor:pointer; font-family:inherit;"
                                                onmouseover="this.style.background='#4338ca'"
                                                onmouseout="this.style.background='#4f46e5'">
                                                Konfirmasi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @push('scripts')
                                <script>
                                    (function() {
                                        /* ── Data dari Blade ── */
                                        const CANDIDATES = @json($candidateList);

                                        const AVATAR_COLORS = [{
                                                bg: '#dbeafe',
                                                color: '#1d4ed8'
                                            }, {
                                                bg: '#dcfce7',
                                                color: '#166534'
                                            },
                                            {
                                                bg: '#fef9c3',
                                                color: '#854d0e'
                                            }, {
                                                bg: '#fce7f3',
                                                color: '#9d174d'
                                            },
                                            {
                                                bg: '#ede9fe',
                                                color: '#5b21b6'
                                            }, {
                                                bg: '#ffedd5',
                                                color: '#9a3412'
                                            },
                                            {
                                                bg: '#d1fae5',
                                                color: '#065f46'
                                            }, {
                                                bg: '#e0f2fe',
                                                color: '#0369a1'
                                            },
                                            {
                                                bg: '#fef3c7',
                                                color: '#92400e'
                                            }, {
                                                bg: '#f3e8ff',
                                                color: '#7e22ce'
                                            },
                                        ];

                                        function avatarStyle(idx) {
                                            const p = AVATAR_COLORS[idx % AVATAR_COLORS.length];
                                            return `background:${p.bg};color:${p.color}`;
                                        }

                                        function highlight(str, q) {
                                            if (!q) return str;
                                            const i = str.toLowerCase().indexOf(q.toLowerCase());
                                            if (i < 0) return str;
                                            return str.slice(0, i) +
                                                `<mark style="background:#fef08a;border-radius:2px;padding:0 1px">` +
                                                str.slice(i, i + q.length) +
                                                '</mark>' +
                                                str.slice(i + q.length);
                                        }

                                        /* ── State ── */
                                        let tempIds = []; // ids yang sedang dipilih di dalam modal
                                        let confirmed = []; // ids yang sudah dikonfirmasi

                                        /* ── DOM refs ── */
                                        const backdrop = document.getElementById('memberBackdrop');
                                        const modalBox = document.getElementById('memberModalBox');
                                        const searchInput = document.getElementById('memberSearchInput');
                                        const searchClear = document.getElementById('memberSearchClear');
                                        const list = document.getElementById('memberList');
                                        const selCount = document.getElementById('memberSelCount');
                                        const filterCount = document.getElementById('memberFilterCount');
                                        const triggerLabel = document.getElementById('memberTriggerLabel');
                                        const triggerBtn = document.getElementById('memberTriggerBtn');
                                        const hiddenInput = document.getElementById('selectedUserId');

                                        window.memberModal = {

                                            open() {
                                                tempIds = [...confirmed];
                                                backdrop.style.display = 'block';
                                                modalBox.style.display = 'flex';
                                                searchInput.value = '';
                                                searchClear.style.display = 'none';
                                                this.render();
                                                setTimeout(() => searchInput.focus(), 60);
                                            },

                                            close() {
                                                backdrop.style.display = 'none';
                                                modalBox.style.display = 'none';
                                            },

                                            clearSearch() {
                                                searchInput.value = '';
                                                searchClear.style.display = 'none';
                                                this.render();
                                                searchInput.focus();
                                            },

                                            filter() {
                                                searchClear.style.display = searchInput.value ? 'block' : 'none';
                                                this.render();
                                            },

                                            render() {
                                                const q = searchInput.value.trim().toLowerCase();
                                                const filtered = q ?
                                                    CANDIDATES.filter(m => m.name.toLowerCase().includes(q)) :
                                                    CANDIDATES;

                                                filterCount.textContent = filtered.length;
                                                selCount.textContent = tempIds.length;

                                                if (filtered.length === 0) {
                                                    list.innerHTML = `
                <div style="padding:32px 16px; text-align:center; color:#9ca3af;">
                    <svg style="width:36px;height:36px;margin:0 auto 8px;opacity:.4;"
                         fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <p style="font-size:13px;">Tidak ada member ditemukan</p>
                </div>`;
                                                    return;
                                                }

                                                list.innerHTML = filtered.map((m) => {
                                                    const realIdx = CANDIDATES.findIndex(c => c.id === m.id);
                                                    const isSelected = tempIds.includes(m.id);
                                                    return `
            <div onclick="memberModal.select(${m.id})"
                 style="display:flex; align-items:center; gap:12px;
                        padding:9px 20px; cursor:pointer; transition:background .1s;
                        background:${isSelected ? '#f5f3ff' : 'transparent'};"
                 onmouseover="if(!${isSelected}) this.style.background='#f9fafb'"
                 onmouseout="this.style.background='${isSelected ? '#f5f3ff' : 'transparent'}'">
                <div style="width:34px;height:34px;border-radius:50%;flex-shrink:0;
                            display:flex;align-items:center;justify-content:center;
                            font-size:11px;font-weight:600;${avatarStyle(realIdx)}">
                    ${m.initials}
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:13px;font-weight:500;color:#111827;
                              margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        ${highlight(m.name, searchInput.value.trim())}
                    </p>
                </div>
                <div style="width:18px;height:18px;border-radius:5px;flex-shrink:0;
                            display:flex;align-items:center;justify-content:center;
                            border:${isSelected ? 'none' : '1.5px solid #d1d5db'};
                            background:${isSelected ? '#4f46e5' : '#fff'};
                            transition:all .12s;">
                    ${isSelected ? `<svg style="width:10px;height:10px;" fill="none"
                                                                                                                                                                                                                                                                                                                                                                                        stroke="#fff" stroke-width="3" viewBox="0 0 24 24">
                                                                                                                                                                                                                                                                                                                                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>` : ''}
                </div>
            </div>`;
                                                }).join('');
                                            },

                                            select(id) {
                                                const i = tempIds.indexOf(id);
                                                if (i >= 0) tempIds.splice(i, 1);
                                                else tempIds.push(id);
                                                this.render();
                                            },

                                            confirm() {
                                                confirmed = [...tempIds];

                                                // Hapus hidden inputs lama
                                                document.querySelectorAll('input[name="user_ids[]"]').forEach(el => el.remove());

                                                if (confirmed.length > 0) {
                                                    confirmed.forEach(id => {
                                                        const input = document.createElement('input');
                                                        input.type = 'hidden';
                                                        input.name = 'user_ids[]';
                                                        input.value = id;
                                                        document.getElementById('addMemberForm').appendChild(input);
                                                    });

                                                    const names = confirmed.map(id => CANDIDATES.find(c => c.id === id)?.name).filter(Boolean);
                                                    if (names.length === 1) {
                                                        triggerLabel.textContent = names[0];
                                                    } else {
                                                        triggerLabel.textContent = names[0] + ' +' + (names.length - 1) + ' lainnya';
                                                    }
                                                    triggerLabel.style.color = '#111827';
                                                } else {
                                                    triggerLabel.textContent = 'Select workspace member';
                                                    triggerLabel.style.color = '#9ca3af';
                                                }
                                                this.close();
                                            },
                                        };

                                        /* ── Tutup dengan Escape ── */
                                        document.addEventListener('keydown', function(e) {
                                            if (e.key === 'Escape') memberModal.close();
                                        });

                                        /* ── Validasi form: user_id wajib dipilih ── */
                                        document.getElementById('addMemberForm').addEventListener('submit', function(e) {
                                            if (confirmed.length === 0) {
                                                e.preventDefault();
                                                triggerBtn.style.borderColor = '#ef4444';
                                                triggerBtn.style.boxShadow = '0 0 0 3px rgba(239,68,68,.15)';
                                                setTimeout(() => {
                                                    triggerBtn.style.borderColor = '';
                                                    triggerBtn.style.boxShadow = '';
                                                }, 2000);
                                                memberModal.open();
                                            }
                                        });

                                    })();
                                </script>
                            @endpush
                        @endif
                        @php
                            $currentUserId = Auth::id();
                            $workspaceOwnerId = $project->workspace->created_by;
                            $managers = $project->members
                                ->filter(fn($m) => $m->pivot->role === 'manager')
                                ->sortByDesc(fn($m) => $m->id === $currentUserId);
                            $members = $project->members
                                ->filter(fn($m) => $m->pivot->role === 'member')
                                ->sortByDesc(fn($m) => $m->id === $currentUserId);
                            $viewers = $project->members
                                ->filter(fn($m) => $m->pivot->role === 'viewer')
                                ->sortByDesc(fn($m) => $m->id === $currentUserId);
                        @endphp
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {{-- Managers --}}
                            <div class="bg-purple-50/50 rounded-xl p-4 border-2 border-purple-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-purple-900 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Managers
                                    </h4>
                                    <span
                                        class="bg-purple-200 text-purple-800 text-xs font-bold px-2 py-1 rounded-full">{{ $managers->count() }}</span>
                                </div>
                                <div class="space-y-2">
                                    @foreach ($managers as $member)
                                        <div
                                            class="bg-white rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                                            <div class="flex items-center gap-2">
                                                @if ($member->profile_photo)
                                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                        alt="{{ $member->name }}"
                                                        class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                                @else
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-semibold text-gray-900 text-sm truncate">
                                                        {{ $member->name }}</p>
                                                </div>
                                                @if ($member->id === $workspaceOwnerId)
                                                    <span
                                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                                        <i class="fa-solid fa-crown text-[10px] mr-0.5"></i> Owner
                                                    </span>
                                                @endif
                                                @if ($member->id === $project->created_by && $member->id !== $workspaceOwnerId)
                                                    <span
                                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                                        <i class="fa-solid fa-star text-[10px] mr-0.5"></i> Creator
                                                    </span>
                                                @endif
                                                @if ($member->id === Auth::id())
                                                    <span
                                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                                @endif
                                                @if (
                                                    $canManageMembers &&
                                                        $member->id !== Auth::id() &&
                                                        $member->id !== $workspaceOwnerId &&
                                                        ($member->id !== $project->created_by || Auth::id() === $workspaceOwnerId))
                                                    <div class="relative flex-shrink-0">
                                                        <button
                                                            onclick="toggleMemberDropdown('{{ $member->id }}', 'pm')"
                                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors text-lg font-bold leading-none">⋮</button>
                                                        <div id="dd-pm-{{ $member->id }}"
                                                            class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                            <button onclick="toggleSubRoles('sr-pm-{{ $member->id }}')"
                                                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-purple-50 transition-colors">
                                                                <span class="flex items-center gap-2">
                                                                    <svg class="w-4 h-4 text-purple-500" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-3 1 1-3a4 4 0 01.828-1.414z" />
                                                                    </svg>
                                                                    Change Role
                                                                </span>
                                                                <svg class="w-3 h-3 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </button>
                                                            <div id="sr-pm-{{ $member->id }}"
                                                                class="hidden border-t border-gray-100">
                                                                @foreach (['manager' => 'Manager', 'member' => 'Member', 'viewer' => 'Viewer'] as $value => $label)
                                                                    @if ($member->pivot->role !== $value)
                                                                        <form method="POST"
                                                                            action="{{ route('projects.members.update', [$project, $member]) }}">
                                                                            @csrf @method('PATCH')
                                                                            <input type="hidden" name="role"
                                                                                value="{{ $value }}">
                                                                            <button type="submit"
                                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ $label }}</button>
                                                                        </form>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                            <div class="border-t border-gray-100 my-1"></div>
                                                            <form method="POST"
                                                                action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                                                onsubmit="return confirm('Hapus member ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit"
                                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                                    <svg class="w-4 h-4" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Members --}}
                            <div class="bg-blue-50/50 rounded-xl p-4 border-2 border-blue-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-blue-900 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Members
                                    </h4>
                                    <span
                                        class="bg-blue-200 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">{{ $members->count() }}</span>
                                </div>
                                <div class="space-y-2">
                                    @foreach ($members as $member)
                                        <div
                                            class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
                                            <div class="flex items-center gap-2">
                                                @if ($member->profile_photo)
                                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                        alt="{{ $member->name }}"
                                                        class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                                @else
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-semibold text-gray-900 text-sm truncate">
                                                        {{ $member->name }}</p>
                                                    <p class="text-gray-400 text-xs truncate">
                                                        {{ $member->job_title ?: 'No job title' }}</p>
                                                </div>
                                                @if ($member->id === Auth::id())
                                                    <span
                                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                                @endif
                                                @if ($canManageMembers && $member->id !== Auth::id())
                                                    <div class="relative flex-shrink-0">
                                                        <button
                                                            onclick="toggleMemberDropdown('{{ $member->id }}', 'mem')"
                                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors text-lg font-bold leading-none">⋮</button>
                                                        <div id="dd-mem-{{ $member->id }}"
                                                            class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                            <button onclick="toggleSubRoles('sr-mem-{{ $member->id }}')"
                                                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                                                                <span class="flex items-center gap-2">
                                                                    <svg class="w-4 h-4 text-blue-500" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-3 1 1-3a4 4 0 01.828-1.414z" />
                                                                    </svg>
                                                                    Change Role
                                                                </span>
                                                                <svg class="w-3 h-3 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </button>
                                                            <div id="sr-mem-{{ $member->id }}"
                                                                class="hidden border-t border-gray-100">
                                                                @foreach (['manager' => 'Manager', 'member' => 'Member', 'viewer' => 'Viewer'] as $value => $label)
                                                                    @if ($member->pivot->role !== $value)
                                                                        <form method="POST"
                                                                            action="{{ route('projects.members.update', [$project, $member]) }}">
                                                                            @csrf @method('PATCH')
                                                                            <input type="hidden" name="role"
                                                                                value="{{ $value }}">
                                                                            <button type="submit"
                                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-blue-50 transition-colors">{{ $label }}</button>
                                                                        </form>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                            <div class="border-t border-gray-100 my-1"></div>
                                                            <form method="POST"
                                                                action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                                                onsubmit="return confirm('Hapus member ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit"
                                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                                    <svg class="w-4 h-4" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Viewers --}}
                            <div class="bg-yellow-50 rounded-xl p-4 border-2 border-yellow-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-yellow-700 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Viewers
                                    </h4>
                                    <span
                                        class="bg-yellow-300 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full">{{ $viewers->count() }}</span>
                                </div>
                                <div class="space-y-2">
                                    @foreach ($viewers as $member)
                                        <div
                                            class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
                                            <div class="flex items-center gap-2">
                                                @if ($member->profile_photo)
                                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                        alt="{{ $member->name }}"
                                                        class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                                @else
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-semibold text-gray-900 text-sm truncate">
                                                        {{ $member->name }}</p>
                                                    <p class="text-gray-400 text-xs truncate">
                                                        {{ $member->job_title ?: 'No job title' }}</p>
                                                </div>
                                                @if ($member->id === Auth::id())
                                                    <span
                                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                                @endif
                                                @if ($canManageMembers && $member->id !== Auth::id())
                                                    <div class="relative flex-shrink-0">
                                                        <button
                                                            onclick="toggleMemberDropdown('{{ $member->id }}', 'vw')"
                                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors text-lg font-bold leading-none">⋮</button>
                                                        <div id="dd-vw-{{ $member->id }}"
                                                            class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                            <button onclick="toggleSubRoles('sr-vw-{{ $member->id }}')"
                                                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-yellow-50 transition-colors">
                                                                <span class="flex items-center gap-2">
                                                                    <svg class="w-4 h-4 text-yellow-500" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-3 1 1-3a4 4 0 01.828-1.414z" />
                                                                    </svg>
                                                                    Change Role
                                                                </span>
                                                                <svg class="w-3 h-3 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </button>
                                                            <div id="sr-vw-{{ $member->id }}"
                                                                class="hidden border-t border-gray-100">
                                                                @foreach (['manager' => 'Manager', 'member' => 'Member', 'viewer' => 'Viewer'] as $value => $label)
                                                                    @if ($member->pivot->role !== $value)
                                                                        <form method="POST"
                                                                            action="{{ route('projects.members.update', [$project, $member]) }}">
                                                                            @csrf @method('PATCH')
                                                                            <input type="hidden" name="role"
                                                                                value="{{ $value }}">
                                                                            <button type="submit"
                                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-yellow-50 transition-colors">{{ $label }}</button>
                                                                        </form>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                            <div class="border-t border-gray-100 my-1"></div>
                                                            <form method="POST"
                                                                action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                                                onsubmit="return confirm('Hapus member ini?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit"
                                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                                    <svg class="w-4 h-4" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- END MEMBERS TAB --}}

                {{-- ============ CHART TAB ============ --}}
                <div id="tab-chart" class="tab-content {{ $currentTab !== 'chart' ? 'hidden' : '' }}"
                    style="flex: 1; overflow-y: auto; position: relative;">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900">S-Curve: Planned vs Actual</h3>
                            <span class="text-sm text-gray-500 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Baseline: {{ $baseline?->baseline_name ?? 'No active baseline' }}
                            </span>
                        </div>
                        @if (empty($chartData['labels']))
                            <div class="p-16 text-center bg-gray-50 rounded-xl border border-gray-200">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-gray-600 font-medium">No progress data available yet for chart
                                    visualization.</p>
                                <p class="text-sm text-gray-500 mt-1">Start adding tasks and updating their progress to see
                                    the chart.</p>
                            </div>
                        @else
                            <div class="h-96 bg-gray-50 rounded-xl border border-gray-200 p-4">
                                <canvas id="projectProgressChart"></canvas>
                            </div>
                        @endif
                    </div>
                </div>{{-- END CHART TAB --}}
            </div>
        </div>

        {{-- Delete Modal --}}
        <div id="delete-project-modal" class="modal hidden fixed inset-0 z-50 overflow-y-auto" style="display:none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    onclick="closeModal('delete-project-modal')"></div>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900">Delete Project</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete this project? This action cannot be undone and all
                                        associated tasks and data will be permanently removed.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <form method="POST" action="{{ route('projects.destroy', $project) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Project
                            </button>
                        </form>
                        <button onclick="closeModal('delete-project-modal')" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // ========== TAB SWITCHING ==========
            function switchTab(tabName) {
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(el => {
                    el.classList.add('hidden');
                    el.style.display = 'none';
                });

                // Remove active state from all tab buttons
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('bg-[#ADE8F4]', 'text-gray-700', 'font-medium');
                    btn.classList.add('text-gray-600', 'hover:text-gray-900');
                });

                // Show selected tab content
                const selectedTab = document.getElementById('tab-' + tabName);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                    selectedTab.style.display = '';
                }

                // Add active state to selected button
                const selectedBtn = document.querySelector('[data-tab="' + tabName + '"]');
                if (selectedBtn) {
                    selectedBtn.classList.remove('text-gray-600', 'hover:text-gray-900');
                    selectedBtn.classList.add('bg-[#ADE8F4]', 'text-gray-700', 'font-medium');
                }

                // Show/hide tasks actions
                const tasksActions = document.getElementById('tasks-actions');
                if (tasksActions) {
                    if (tabName === 'tasks') {
                        tasksActions.classList.remove('hidden');
                    } else {
                        tasksActions.classList.add('hidden');
                    }
                }

                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }

            // ========== DROPDOWN TOGGLE ==========
            function toggleDropdown(id) {
                // Close all other dropdowns first
                document.querySelectorAll('.dropdown-menu').forEach(el => {
                    if (el.id !== 'dropdown-' + id) {
                        el.classList.add('hidden');
                    }
                });

                // Toggle current dropdown
                const dropdown = document.getElementById('dropdown-' + id);
                if (dropdown) {
                    dropdown.classList.toggle('hidden');
                }
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('[data-dropdown]')) {
                    document.querySelectorAll('.dropdown-menu').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // ========== MODAL FUNCTIONS ==========
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.style.display = 'block';
                }
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }
            }

            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.classList.add('hidden');
                    e.target.style.display = 'none';
                }
            });

            // ========== MEMBER DROPDOWN ==========
            function toggleMemberDropdown(memberId, prefix) {
                const dropdownId = 'dd-' + prefix + '-' + memberId;
                const dropdown = document.getElementById(dropdownId);

                // Close all other member dropdowns
                document.querySelectorAll('[id^="dd-"]').forEach(el => {
                    if (el.id !== dropdownId) {
                        el.classList.add('hidden');
                    }
                });

                if (dropdown) {
                    dropdown.classList.toggle('hidden');
                }
            }

            function toggleSubRoles(subId) {
                const sub = document.getElementById(subId);
                if (sub) {
                    sub.classList.toggle('hidden');
                }
            }

            // Close member dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('[id^="dd-"]') && !e.target.closest('button[onclick^="toggleMemberDropdown"]')) {
                    document.querySelectorAll('[id^="dd-"]').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // ========== CHART JS ==========
            @if (!empty($chartData['labels']))
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('projectProgressChart');
                    if (!ctx) return;

                    const rawData = @json($chartData);
                    const actualExtended = [];
                    let lastActual = null;

                    for (const value of rawData.actual) {
                        if (value !== null && value !== undefined) {
                            lastActual = value;
                            actualExtended.push(value);
                        } else {
                            actualExtended.push(lastActual);
                        }
                    }

                    const maxValue = Math.max(100, ...rawData.planned.filter(v => v !== null), ...actualExtended.filter(
                        v => v !== null));

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: rawData.labels,
                            datasets: [{
                                    label: 'Planned',
                                    data: rawData.planned,
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99,102,241,0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    spanGaps: true
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
                                    spanGaps: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: c => c.parsed.y === null ? `${c.dataset.label}: -` :
                                            `${c.dataset.label}: ${c.parsed.y}%`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 12
                                    },
                                    grid: {
                                        color: 'rgba(148,163,184,0.2)'
                                    }
                                },
                                y: {
                                    min: 0,
                                    max: Math.ceil(maxValue / 25) * 25 + 25,
                                    ticks: {
                                        stepSize: 25,
                                        callback: v => v + '%'
                                    },
                                    grid: {
                                        color: 'rgba(148,163,184,0.25)'
                                    }
                                }
                            }
                        }
                    });
                });
            @endif

            // ========== INITIALIZATION ==========
            document.addEventListener('DOMContentLoaded', function() {
                // Get initial tab from URL or default to 'tasks'
                const urlParams = new URLSearchParams(window.location.search);
                const initialTab = urlParams.get('tab') || 'tasks';

                // Initialize tab state
                switchTab(initialTab);
            });
        </script>

        {{-- Status Dropdown Portal --}}
        <div id="task-status-dropdown-portal"
            class="hidden fixed z-[9999] w-44 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden py-1">
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
                    class="w-full text-left px-4 py-2 text-sm transition-colors hover:bg-gray-50
                    ${opt.value === current ? 'bg-gray-100 font-semibold' : ''}">
                    ${opt.label}
                </button>
            </form>
        `).join('');

                taskPortal.classList.remove('hidden');

                const rect = btn.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const dropdownHeight = taskPortal.offsetHeight || 200;
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

        <style>
            /* Utility classes */
            .hidden {
                display: none !important;
            }

            #memberBackdrop,
            #memberModalBox {
                display: none;
            }

            #memberBackdrop.show,
            #memberModalBox.show {
                display: block !important;
            }

            /* Modal transitions */
            .modal {
                transition: opacity 0.2s ease-in-out;
            }

            /* Dropdown transitions */
            .dropdown-menu {
                transition: opacity 0.15s ease-in-out;
            }

            /* Tab content transitions */
            .tab-content {
                transition: opacity 0.2s ease-in-out;
            }

            #memberSearchInput:focus,
            #memberSearchInput:focus-visible {
                outline: none !important;
                box-shadow: none !important;
            }

            #memberSearchWrap:focus-within {
                outline: none !important;
                box-shadow: none !important;
                border-color: #e5e7eb !important;
            }
        </style>
    @endpush

@endsection
