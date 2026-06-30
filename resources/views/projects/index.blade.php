@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <style>
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        .animate-pulse-slow {
            animation: pulse 2s infinite;
        }
    </style>

    <script>
        // Simpan view ke localStorage setiap kali ada ?view= di URL
        const urlParams = new URLSearchParams(window.location.search);
        const viewParam = urlParams.get('view');

        if (viewParam) {
            localStorage.setItem('projects.view', viewParam);
        }

        // Kalau URL tidak ada ?view= dan ada saved preference, redirect
        if (!viewParam) {
            const savedView = localStorage.getItem('projects.view');
            if (savedView && savedView !== 'list') {
                const url = new URL(window.location.href);
                url.searchParams.set('view', savedView);
                window.location.replace(url.toString());
            }
        }
    </script>

    @php
        $statuses = [
            'all' => 'All',
            'planning' => 'Planning',
            'active' => 'Active',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'urgent' => 'Urgent',
        ];
        $currentStatus = request('status', 'all');
        $view = $view ?? 'list';
    @endphp

    <div style="height: calc(100vh - 121px); display: flex; flex-direction: column; overflow: hidden;">
        <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

        {{-- Page Header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-4xl font-semibold text-slate-900">
                    Projects
                </h1>

                <button onclick="openProjectInfoModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
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
                        class="absolute right-0 mt-1 w-36 bg-white border border-gray-100 rounded-xl shadow-lg z-50 invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200">
                        <a href="{{ route('projects.index', ['view' => 'list', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-xl transition-colors {{ $view === 'list' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-list w-4 text-center"></i> List
                        </a>
                        <a href="{{ route('projects.index', ['view' => 'gantt', 'status' => $currentStatus]) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-b-xl transition-colors {{ $view === 'gantt' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fa-solid fa-chart-gantt w-4 text-center"></i> Gantt
                        </a>
                    </div>
                </div>

                <a href="{{ route('projects.create') }}"
                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all duration-300">
                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                    Create Project
                </a>
            </div>
        </div>

        {{-- SINGLE CARD CONTAINER --}}
        <div class="border border-gray-200 shadow-sm rounded-xl flex-1 overflow-hidden bg-white"
            style="min-height:0; position:relative;">

            {{-- LIST VIEW --}}
            @if ($view === 'list')
                <div style="position:absolute; inset:0; overflow-x:auto; overflow-y:auto;">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-[#ADE8F4]">
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 flex-shrink-0"></div>
                                        Project
                                    </div>
                                </th>
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

                        @php
                            $sortedProjects = $projects->sortBy(function ($project) {
                                $progress = $project->tasks_count > 0 ? round($project->calculateProgress(), 1) : 0;

                                if ($project->status === 'cancelled') {
                                    return 2;
                                }

                                if ($project->status === 'completed') {
                                    return 4;
                                }

                                if ($progress >= 100) {
                                    return 3;
                                }

                                if ($project->status === 'urgent' && $progress < 100) {
                                    return 0;
                                }

                                return 1;
                            });
                        @endphp

                        <tbody>
                            @forelse ($sortedProjects as $project)
                                @php

                                    $isManager = $project->isManager(Auth::user());
                                    $progress = $project->tasks_count > 0 ? round($project->calculateProgress(), 1) : 0;
                                    $doneTasks = $project->tasks()->where('status', 'completed')->count();
                                    $progressBarClasses = $progress > 0 ? 'bg-indigo-600' : 'bg-gray-300';
                                    $badgeClasses = match ($project->status) {
                                        'planning' => 'bg-gray-100 text-gray-700',
                                        'active' => 'bg-emerald-100 text-emerald-800',
                                        'on_hold' => 'bg-amber-100 text-amber-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'urgent' => 'bg-orange-200 text-orange-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };

                                    $isDeadlineNear = false;
                                    if ($project->end_date && !in_array($project->status, ['completed', 'cancelled'])) {
                                        $endDate = \Carbon\Carbon::parse($project->end_date)->startOfDay();
                                        $today = \Carbon\Carbon::today()->startOfDay();
                                        $diffDays = $today->diffInDays($endDate, true);

                                        if ($endDate->isPast() || $endDate->isToday() || $diffDays <= 3) {
                                            $isDeadlineNear = true;
                                        }
                                    }

                                    $statusOptions = [
                                        ['value' => 'planning', 'label' => 'Planning'],
                                        ['value' => 'active', 'label' => 'Active'],
                                        ['value' => 'on_hold', 'label' => 'On hold'],
                                        ['value' => 'completed', 'label' => 'Completed'],
                                        ['value' => 'cancelled', 'label' => 'Cancelled'],
                                        ['value' => 'urgent', 'label' => 'Urgent'],
                                    ];
                                @endphp

                                @if ($project->status === 'urgent' && $progress < 100)
                                    <tr
                                        class="group border-b border-red-200 transition-colors border-l-4 border-red-400 hover:bg-gray-50/70">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-2">
                                                {{-- Warning Badge di DEPAN nama --}}
                                                <div class="w-6 h-6 flex-shrink-0">
                                                    @if ($project->status === 'urgent')
                                                        <div
                                                            class="w-6 h-6 flex items-center justify-center rounded-lg flex-shrink-0 bg-gradient-to-br from-red-600 to-red-500 shadow-[0_2px_4px_rgba(220,38,38,0.3)]">
                                                            <i
                                                                class="fa-solid fa-triangle-exclamation text-white text-xs animate-pulse"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                {{-- Nama Project --}}
                                                <span
                                                    class="block text-sm font-semibold text-gray-900">{{ $project->name }}</span>
                                            </div>
                                            @if ($project->description)
                                                <span class="block text-xs text-gray-400 mt-0.5 truncate max-w-[180px]">
                                                    {{ Str::limit($project->description, 45) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Workspace --}}
                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap">
                                                <i class="fa-solid fa-layer-group text-gray-400 text-xs"></i>
                                                {{ $project->workspace?->name ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- Status column --}}
                                        <td class="px-4 py-3">
                                            @php
                                                // Ambil role user di project ini dari pivot table
                                                $userRole =
                                                    $project->members->where('id', Auth::id())->first()?->pivot->role ??
                                                    null;

                                                // Manager & Member bisa edit, Viewer hanya lihat
                                                $canEditStatus = in_array($userRole, ['manager', 'member']);
                                            @endphp

                                            @if ($canEditStatus)
                                                {{-- Manager/Member: Dropdown button dengan arrow ▼ --}}
                                                <button id="status-btn-{{ $project->id }}"
                                                    data-project-id="{{ $project->id }}"
                                                    data-current-status="{{ $project->status }}"
                                                    data-update-url="{{ route('projects.updateStatus', $project->token) }}"
                                                    data-options="{{ json_encode($statusOptions) }}"
                                                    onclick="openStatusDropdown(this)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $badgeClasses }}">
                                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                                    <i class="fa-solid fa-chevron-down text-[9px] opacity-50"></i>
                                                </button>
                                            @else
                                                {{-- Viewer: Static badge, centered, NO arrow, not clickable --}}
                                                <span
                                                    class="inline-flex items-center justify-center px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $badgeClasses }}">
                                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Start date --}}
                                        <td class="px-5 py-4">
                                            <span class="text-sm text-gray-600">
                                                {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '—' }}
                                            </span>
                                        </td>

                                        {{-- End date --}}
                                        <td class="px-5 py-4">
                                            <span
                                                class="text-sm {{ $isDeadlineNear ? 'font-semibold text-red-600' : 'text-gray-600' }}">
                                                {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '—' }}
                                                @if ($isDeadlineNear)
                                                @endif
                                            </span>
                                        </td>

                                        {{-- Tasks count --}}
                                        <td class="px-5 py-4">
                                            <span
                                                class="block text-sm font-semibold text-gray-800">{{ $project->tasks_count }}</span>
                                            <span class="block text-xs text-gray-400">tasks</span>
                                        </td>

                                        {{-- Progress --}}
                                        <td class="px-5 py-4">
                                            @if ($project->tasks_count > 0)
                                                @php
                                                    $progressVal = min(round($progress), 100);
                                                    $hue = ($progressVal / 100) * 120;
                                                    $colorStart = "hsl($hue, 65%, 75%)";
                                                    $colorEnd = 'hsl(' . ($hue + 10) . ', 70%, 70%)';
                                                    $textColor =
                                                        $progressVal >= 100 ? 'text-emerald-500' : 'text-gray-600';
                                                @endphp
                                                <div class="w-36">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs font-semibold {{ $textColor }}">
                                                            {{ $progressVal }}%
                                                        </span>
                                                        <span class="text-xs text-gray-400">
                                                            {{ $doneTasks }}/{{ $project->tasks_count }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200">
                                                        <div class="h-full rounded-full transition-all duration-500 ease-in-out"
                                                            style="width: {{ $progressVal }}%;
                                                                    background: linear-gradient(90deg, {{ $colorStart }}, {{ $colorEnd }});
                                                                    box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);">
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 italic">No tasks yet</span>
                                            @endif
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('projects.show', $project->token) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                                @if ($isManager)
                                                    <a href="{{ route('projects.edit', $project->token) }}"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors"
                                                        title="Edit">
                                                        <i class="fa-solid fa-pen text-sm"></i>
                                                    </a>
                                                    <form method="POST"
                                                        action="{{ route('projects.destroy', $project->token) }}"
                                                        class="inline" onsubmit="return confirm('Delete this project?')">
                                                        @csrf @method('DELETE')
                                                        <input type="hidden" name="back_url"
                                                            value="{{ url()->current() }}">
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer"
                                                            title="Delete">
                                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    {{-- NON-URGENT: standard table row --}}
                                    <tr
                                        class="hover:bg-gray-50/70 transition-colors border-b border-gray-100 last:border-0">

                                        {{-- Project name --}}
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="block text-sm font-semibold {{ $project->status === 'completed' || $progress == 100 ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                                    {{ $project->name }}
                                                </span>
                                            </div>
                                            @if ($project->description)
                                                <span
                                                    class="block text-xs text-gray-400 mt-0.5 truncate max-w-[180px]">{{ Str::limit($project->description, 45) }}</span>
                                            @endif
                                        </td>

                                        {{-- Workspace --}}
                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center gap-2 text-sm text-gray-500 whitespace-nowrap">
                                                <i class="fa-solid fa-layer-group text-gray-400 text-xs"></i>
                                                {{ $project->workspace?->name ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- Status column --}}
                                        <td class="px-4 py-3">
                                            @php
                                                $userRole =
                                                    $project->members->where('id', Auth::id())->first()?->pivot->role ??
                                                    null;

                                                // Manager & Member bisa edit, Viewer hanya lihat
                                                $canEditStatus = in_array($userRole, ['manager', 'member']);
                                            @endphp

                                            @if ($canEditStatus)
                                                {{-- Manager/Member: Dropdown button dengan arrow --}}
                                                <button id="status-btn-{{ $project->id }}"
                                                    data-project-id="{{ $project->id }}"
                                                    data-current-status="{{ $project->status }}"
                                                    data-update-url="{{ route('projects.updateStatus', $project->token) }}"
                                                    data-options="{{ json_encode($statusOptions) }}"
                                                    onclick="openStatusDropdown(this)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $badgeClasses }}">
                                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                                    <i class="fa-solid fa-chevron-down text-[9px] opacity-50"></i>
                                                </button>
                                            @else
                                                {{-- Viewer: Static badge, centered, NO arrow, not clickable --}}
                                                <span
                                                    class="inline-flex items-center justify-center px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $badgeClasses }}">
                                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Start date --}}
                                        <td class="px-5 py-4">
                                            <span class="text-sm text-gray-600">
                                                {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '—' }}
                                            </span>
                                        </td>

                                        {{-- End date --}}
                                        <td class="px-5 py-4">
                                            <span class="text-sm text-gray-600">
                                                {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '—' }}
                                            </span>
                                        </td>

                                        {{-- Tasks count --}}
                                        <td class="px-5 py-4">
                                            <span
                                                class="block text-sm font-semibold text-gray-800">{{ $project->tasks_count }}</span>
                                            <span class="block text-xs text-gray-400">tasks</span>
                                        </td>

                                        {{-- Progress --}}
                                        <td class="px-5 py-4">
                                            @if ($project->tasks_count > 0)
                                                @php
                                                    $progressVal = min(round($progress), 100);
                                                    $hue = ($progressVal / 100) * 120;
                                                    $colorStart = "hsl($hue, 65%, 75%)";
                                                    $colorEnd = 'hsl(' . ($hue + 10) . ', 70%, 70%)';
                                                    $textColor =
                                                        $progressVal >= 100 ? 'text-emerald-500' : 'text-gray-600';
                                                @endphp
                                                <div class="w-36">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs font-semibold {{ $textColor }}">
                                                            {{ $progressVal }}%
                                                        </span>
                                                        <span class="text-xs text-gray-400">
                                                            {{ $doneTasks }}/{{ $project->tasks_count }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200">
                                                        <div class="h-full rounded-full transition-all duration-500 ease-in-out"
                                                            style="width: {{ $progressVal }}%;
                                                                    background: linear-gradient(90deg, {{ $colorStart }}, {{ $colorEnd }});
                                                                    box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);">
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 italic">No tasks yet</span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-5 py-4">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('projects.show', $project->token) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                                @if ($isManager)
                                                    <a href="{{ route('projects.edit', $project->token) }}"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors"
                                                        title="Edit">
                                                        <i class="fa-solid fa-pen text-sm"></i>
                                                    </a>
                                                    <form method="POST"
                                                        action="{{ route('projects.destroy', $project->token) }}"
                                                        class="inline" onsubmit="return confirm('Delete this project?')">
                                                        @csrf @method('DELETE')
                                                        <input type="hidden" name="back_url"
                                                            value="{{ url()->current() }}">
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer"
                                                            title="Delete">
                                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div
                                                class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center">
                                                <i class="fa-solid fa-diagram-project text-emerald-500 text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700">No projects found</p>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    {{ $currentStatus !== 'all' ? 'No projects match the selected status.' : 'Create your first project to get started.' }}
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

            {{-- GANTT VIEW --}}
            @if ($view === 'gantt')
                <div class="absolute inset-0">
                    <div id="gantt_here" class="w-full h-full"></div>
                </div>

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
                            label: "Project",
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
                            template: t => t.duration + "d"
                        },
                        {
                            name: "status",
                            label: "Status",
                            align: "center",
                            width: 90,
                            template: t => t.status ?? "—"
                        },
                    ];

                    gantt.config.readonly = true;
                    gantt.config.open_tree_initially = true;
                    gantt.config.fit_tasks = true;

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

                    // biar warna kena full (karena gantt pakai inner div)
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
                    gantt.load("{{ route('gantt.project.data') }}?status={{ $currentStatus }}", "json");
                </script>
            @endif

        </div>

    </div>

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

            const top = spaceBelow < dropdownHeight + 8 ?
                rect.top + window.scrollY - dropdownHeight - 4 :
                rect.bottom + window.scrollY + 4;

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


    <!-- POP UP PROJECT YA LEK KU SAMPE KE BAWAH INI ADA SCRIPTNYA-->
    <div id="projectInfoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">
                About Projects
            </h2>

            <div class="space-y-4 text-sm text-slate-600">

                <p>
                    This page lists all projects you're a member of, with their status, dates,
                    tasks, and progress.
                </p>

                <div class="border-t pt-4 space-y-3">
                    <div class="flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Starter tasks</span> —
                            Creating a project automatically adds 6 template tasks: Kickoff, Requirement Gathering,
                            Planning, Execution, Review & Testing, and Closing.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Roles</span> —
                            Manager and member can update status and contribute; viewer can only view. Only the manager can
                            edit or delete the project.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Urgent priority</span> —
                            Urgent projects that aren't 100% complete are sorted to the top of the list.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">List & Gantt view</span> —
                            Switch between a sortable task list and a Gantt timeline of all projects.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button onclick="confirmProjectInfo()"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Done
                </button>
            </div>

        </div>
    </div>


    <!-- SCRIPT POP UP-->
    <script>
        function openProjectInfoModal() {
            document.getElementById('projectInfoModal').classList.remove('hidden');
            document.getElementById('projectInfoModal').classList.add('flex');
        }

        function closeProjectInfoModal() {
            document.getElementById('projectInfoModal').classList.add('hidden');
            document.getElementById('projectInfoModal').classList.remove('flex');
        }

        function confirmProjectInfo() {
            closeProjectInfoModal();
            console.log("User lanjut project");
        }
    </script>
@endsection
