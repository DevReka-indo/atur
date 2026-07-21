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
                @include('tasks.partials.gantt._container', [
                    'ganttContainerId' => 'personal_gantt',
                    'ganttEmptyStateId' => 'personal_gantt_empty',
                    'ganttDataUrl' => route('gant.data', ['status' => $currentStatus]),
                    'ganttUseFixedSummaryDates' => true,
                ])
            @endif

            {{-- kanban --}}
            @if ($currentView === 'kanban')
                @include('tasks.partials.kanban._board', [
                    'kanbanStatuses' => $kanbanStatuses,
                    'kanbanTasks' => $kanbanTasks,
                    'kanbanCurrentStatus' => $currentStatus,
                    'kanbanCanContribute' => null,
                    'kanbanShowProject' => true,
                ])
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
