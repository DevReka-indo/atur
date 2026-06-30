@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    @php
        $statuses = [
            'all' => 'All',
            'planning' => 'Planning',
            'active' => 'Active',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $currentStatus = request('status', 'all');
        $view = $view ?? 'list';
    @endphp

    <div class="bg-gray-50" style="height: calc(100vh - 121px); display: flex; flex-direction: column; overflow: hidden;">

        {{-- Page Header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <h1 class="text-3xl font-bold text-gray-800">Projects</h1>
            <p class="mt-1 text-sm text-gray-500">Manage and monitor all your Active projects.</p>
        </div>

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 mb-4 flex-shrink-0">

            {{-- FILTER (HANYA LIST VIEW) --}}
            @if ($view === 'list')
                <div class="flex items-center gap-1 bg-white rounded-lg p-1 overflow-x-auto">
                    @foreach ($statuses as $key => $label)
                        <a href="{{ route('projects.index', ['status' => $key, 'view' => $view]) }}"
                            class="px-4 py-1.5 rounded-md text-sm whitespace-nowrap transition-all
                            {{ $currentStatus === $key ? 'bg-[#ADE8F4] text-gray-900 font-medium shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @else
                {{-- Spacer biar layout tetap balance --}}
                <div></div>
            @endif

            <div class="flex items-center gap-3">
                {{-- View Toggle --}}
                <div class="relative group">
                    <button
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 shadow-sm transition-all duration-200 cursor-pointer">
                        @if ($view === 'gantt')
                            <i class="fa-solid fa-chart-gantt text-indigo-500"></i> Gantt
                        @else
                            <i class="fa-solid fa-list text-indigo-500"></i> List
                        @endif
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                    </button>

                    <div
                        class="absolute right-0 mt-1 w-36 bg-white border border-gray-100 rounded-xl shadow-lg z-50
        invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200">
                        <a href="{{ route('projects.index', ['view' => 'list', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-xl transition-colors
            {{ $view === 'list' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-list w-4 text-center"></i> List
                        </a>
                        <a href="{{ route('projects.index', ['view' => 'gantt', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-b-xl transition-colors
            {{ $view === 'gantt' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-chart-gantt w-4 text-center"></i> Gantt
                        </a>
                    </div>
                </div>

                <a href="{{ route('projects.create') }}"
                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                           bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all duration-300">
                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                    Create Project
                </a>
            </div>
        </div>

        {{-- SINGLE CARD CONTAINER --}}
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl flex-1" style="min-height:0; position:relative;">

            {{-- LIST VIEW --}}
            @if ($view === 'list')
                <div style="position:absolute; inset:0; overflow-x:auto; overflow-y:auto;">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-[#ADE8F4]">
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
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
                                    End Date</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Tasks</th>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Progress</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($projects as $project)
                                @php
                                    if ($currentStatus !== 'all' && $project->status !== $currentStatus) {
                                        continue;
                                    }
                                    $isManager = $project->isManager(Auth::user());
                                    $progress = $project->tasks_count > 0 ? round($project->calculateProgress(), 1) : 0;
                                    $doneTasks = $project->tasks()->where('status', 'completed')->count();
                                    $progressBarClasses = $progress > 0 ? 'bg-indigo-600' : 'bg-gray-300';
                                    $badgeClasses = match ($project->status) {
                                        'planning' => 'bg-gray-200 text-gray-800',
                                        'active' => 'bg-emerald-200 text-emerald-800',
                                        'on_hold' => 'bg-amber-200 text-amber-800',
                                        'completed' => 'bg-blue-200 text-blue-800',
                                        'cancelled' => 'bg-red-200 text-red-800',
                                        default => 'bg-gray-200 text-gray-800',
                                    };
                                    $statusOptions = [
                                        ['value' => 'planning', 'label' => 'Planning'],
                                        ['value' => 'active', 'label' => 'Active'],
                                        ['value' => 'on_hold', 'label' => 'On hold'],
                                        ['value' => 'completed', 'label' => 'Completed'],
                                        ['value' => 'cancelled', 'label' => 'Cancelled'],
                                    ];
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-4">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $project->name }}</span>
                                        @if ($project->description)
                                            <span
                                                class="block text-xs text-gray-400 mt-0.5">{{ Str::limit($project->description, 45) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex items-center gap-2 text-sm text-gray-500 whitespace-nowrap">
                                            <i class="fa-solid fa-layer-group w-5 text-center text-sm"></i>
                                            {{ $project->workspace?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button id="status-btn-{{ $project->id }}" data-project-id="{{ $project->id }}"
                                            data-current-status="{{ $project->status }}"
                                            data-update-url="{{ route('projects.updateStatus', $project) }}"
                                            data-options="{{ json_encode($statusOptions) }}"
                                            onclick="openStatusDropdown(this)"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-90 transition-opacity w-full justify-between {{ $badgeClasses }}">
                                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                            <i class="fa-solid fa-chevron-down text-[10px] opacity-60"></i>
                                        </button>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-700">
                                            {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-700">
                                            {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="block text-sm font-medium text-gray-800">{{ $project->tasks_count }}</span>
                                        <span class="block text-xs text-gray-400">tasks</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($project->tasks_count > 0)
                                            <div class="w-32">
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span
                                                        class="text-xs font-semibold text-gray-800">{{ $progress }}%</span>
                                                    <span
                                                        class="text-xs text-gray-400">{{ $doneTasks }}/{{ $project->tasks_count }}</span>
                                                </div>
                                                <div
                                                    class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200">
                                                    <div class="h-full rounded-full {{ $progressBarClasses }} transition-all duration-500 ease-in-out"
                                                        style="width: {{ $progress }}%"></div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No tasks yet</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('projects.show', $project) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            @if ($isManager)
                                                <a href="{{ route('projects.edit', $project) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                                                    class="inline" onsubmit="return confirm('Delete this project?')">
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
                                    <td colspan="8" class="px-6 py-16 text-center text-sm text-gray-400">
                                        No projects found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- GANTT VIEW --}}
            @if ($view === 'gantt')
                <div class="absolute inset-0">
                    <div id="gantt_here" class="w-full h-full"></div>
                </div>

                <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css">
                <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>

                <script>
                    gantt.config.date_format = "%d-%m-%Y";

                    gantt.config.scales = [
                        { unit: "month", step: 1, format: "%F %Y" },
                        { unit: "day", step: 1, format: "%d" },
                    ];

                    gantt.config.columns = [
                        { name: "text", label: "Project", tree: true, width: 220 },
                        { name: "start_date", label: "Mulai", align: "center", width: 90 },
                        { name: "duration", label: "Durasi", align: "center", width: 60, template: t => t.duration + "d" },
                        { name: "status", label: "Status", align: "center", width: 90, template: t => t.status ?? "—" },
                    ];

                    gantt.config.readonly = true;
                    gantt.config.open_tree_initially = true;
                    gantt.config.fit_tasks = true;

                    // 🔥 FULL TAILWIND (tanpa CSS)
                    gantt.templates.task_class = function(start, end, task) {
                        switch (task.status) {
                            case "active":
                                return "bg-indigo-500 text-white rounded";
                            case "completed":
                                return "bg-green-500 text-white rounded";
                            case "on_hold":
                                return "bg-yellow-400 text-white rounded";
                            case "cancelled":
                                return "bg-red-500 text-white rounded";
                            default:
                                return "bg-slate-400 text-white rounded";
                        }
                    };

                    // 🔥 biar warna kena full (karena gantt pakai inner div)
                    gantt.templates.task_text = function(start, end, task) {
                        return `<div class="w-full h-full flex items-center px-2 rounded bg-inherit text-inherit">
                                    ${task.text}
                                </div>`;
                    };

                    gantt.templates.tooltip_text = function(start, end, task) {
                        return "<b>" + task.text + "</b><br/>" +
                            "Status: " + (task.status ?? "—") + "<br/>" +
                            "Mulai: " + gantt.templates.tooltip_date_format(start) + "<br/>" +
                            "Selesai: " + gantt.templates.tooltip_date_format(end);
                    };

                    gantt.init("gantt_here");
                    gantt.load("{{ route('gant.data') }}?status={{ $currentStatus }}", "json");
                </script>
            @endif

            </div>
            {{-- akhir card container --}}

            </div>
            {{-- akhir wrapper --}}

            {{-- Dropdown portal --}}
            <div id="status-dropdown-portal"
                class="hidden fixed z-[9999] w-40 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden py-1">
            </div>

            <script>
                const portal = document.getElementById('status-dropdown-portal');
                let activeBtn = null;
                let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                function openStatusDropdown(btn) {
                    if (activeBtn === btn && !portal.classList.contains('hidden')) {
                        closeDropdown();
                        return;
                    }

                    activeBtn = btn;

                    const options = JSON.parse(btn.dataset.options);
                    const current = btn.dataset.currentStatus;
                    const url = btn.dataset.updateUrl;

                    portal.innerHTML = options.map(opt => `
                        <form method="POST" action="${url}">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="status" value="${opt.value}">
                            <button type="submit"
                                class="w-full text-left px-3 py-2 text-xs transition-colors hover:bg-gray-50
                                ${opt.value === current ? 'bg-gray-100 font-semibold' : ''}">
                                ${opt.label}
                            </button>
                        </form>
                    `).join('');

                    portal.classList.remove('hidden');

                    const rect = btn.getBoundingClientRect();
                    const dropdownHeight = portal.offsetHeight;
                    const spaceBelow = window.innerHeight - rect.bottom;

                    const top = spaceBelow < dropdownHeight + 8
                        ? rect.top + window.scrollY - dropdownHeight - 4
                        : rect.bottom + window.scrollY + 4;

                    portal.style.top = top + 'px';
                    portal.style.left = (rect.left + window.scrollX) + 'px';
                }

                function closeDropdown() {
                    portal.classList.add('hidden');
                    activeBtn = null;
                }

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('#status-dropdown-portal') && !e.target.closest('[id^="status-btn-"]')) {
                        closeDropdown();
                    }
                });

                window.addEventListener('scroll', closeDropdown, true);
            </script>

            @endsection
