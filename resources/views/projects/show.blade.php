@extends('layouts.app')

@section('title', 'Project — ' . $project->name)

@section('content')

    @php
        $isManager = $project->isManager(Auth::user());
        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('status', 'completed')->count();
        $overdueTasks = $project->tasks->filter(fn($task) => $task->isOverdue())->count();
        $progress = $project->tasks->count() > 0 ? round($project->calculateProgress(), 1) : 0;
        $canManageMembers = $project->isManager(Auth::user());
        $canContribute = $canContribute ?? $project->canContribute(Auth::user());
        $currentView = request('view', 'list');
        $currentTab = request('tab', 'tasks');
        // over load
        $overloadedMemberIds = $overloadedMemberIds ?? [];
        $memberTaskCounts = $memberTaskCounts ?? collect();

        $kanbanStatuses = [
            'to_do' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'completed' => 'Completed',
            'stopped' => 'Stopped',
            'cancelled' => 'Cancelled',
            'urgent' => 'Urgent',
        ];
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    {{-- Header Section --}}
    <div class="max-w-8xl mx-auto mb-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500 mb-4 flex items-center gap-2">
            <a href="{{ route('workspaces.show', $project->workspace->token) }}"
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
                    {{-- Status Dropdown / Badge Section --}}
                    <div class="relative" data-dropdown="project-status">
                        @php
                            // Cek apakah user Super Admin
                            $isSuperAdmin = Auth::user()->isSuperAdmin();

                            // Ambil role user di project ini dari pivot table
                            $userProjectRole =
                                $project->members->where('id', Auth::id())->first()?->pivot->role ?? null;

                            // Viewer hanya lihat
                            $canChangeProjectStatus =
                                $isSuperAdmin || in_array($userProjectRole, ['manager', 'member']);

                            // warna untuk status
                            $statusColors = [
                                'planning' => 'bg-gray-200 text-gray-700',
                                'active' => 'bg-blue-300 text-blue-700',
                                'on_hold' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-green-300 text-green-700',
                                'cancelled' => 'bg-red-200 text-red-700',
                                'urgent' => 'bg-orange-200 text-orange-800',
                            ];
                        @endphp

                        @if ($canChangeProjectStatus)
                            {{-- Super Admin/Manager/Member: Dropdown button --}}
                            <button onclick="toggleDropdown('project-status')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-full transition
                                hover:opacity-80 cursor-pointer
                                {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ str($project->status)->replace('_', ' ')->title() }}
                                <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Menu Dropdown --}}
                            <div id="dropdown-project-status"
                                class="hidden absolute mt-2 w-40 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50 py-1">
                                @foreach (['planning', 'active', 'on_hold', 'completed', 'cancelled', 'urgent'] as $status)
                                    <form method="POST" action="{{ route('projects.updateStatus', $project->token) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm transition-colors flex items-center gap-2
                                            {{ $project->status === $status ? 'bg-gray-100 font-semibold' : 'hover:bg-gray-50' }}">
                                            @if ($project->status === $status)
                                                <span class="text-green-500 text-xs">✓</span>
                                            @else
                                                <span class="w-3"></span>
                                            @endif
                                            <span>{{ str($status)->replace('_', ' ')->title() }}</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @else
                            {{-- Viewer: Static badge, centered text, NO arrow, not clickable --}}
                            <span
                                class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-full
                                {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-700' }}"
                                title="Viewers cannot change project status">
                                {{ str($project->status)->replace('_', ' ')->title() }}
                            </span>
                        @endif
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
                    <a href="{{ route('projects.edit', $project->token) }}"
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
        @php
            $progressVal = min(round($progress, 1), 100);
            $hue = ($progressVal / 100) * 120;
            $colorStart = "hsl($hue, 65%, 75%)";
            $colorEnd = 'hsl(' . ($hue + 10) . ', 70%, 70%)';
            $textColor = $progressVal >= 100 ? 'text-emerald-500' : 'text-gray-600';
        @endphp

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                <span class="text-2xl font-bold {{ $textColor }}">
                    {{ $progressVal }}%
                </span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-3 rounded-full transition-all duration-500"
                    style="width: {{ $progressVal }}%;
                                background: linear-gradient(90deg, {{ $colorStart }}, {{ $colorEnd }});
                                box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-8xl mx-auto" style="height: calc(100vh - 280px);">
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

                    {{-- View Toggle + Create Task --}}
                    <div id="tasks-actions"
                        class="flex items-center gap-3 {{ $currentTab !== 'tasks' ? 'hidden' : '' }}">
                        {{-- Dropdown --}}
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
                                <a href="{{ route('projects.show', ['token' => $project->token, 'view' => 'list']) }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-xl transition-colors
                                    {{ $currentView === 'list' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-list w-4 text-center"></i> List
                                </a>
                                <a href="{{ route('projects.show', ['token' => $project->token, 'view' => 'gantt']) }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm border-y border-gray-100 transition-colors
                                    {{ $currentView === 'gantt' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-chart-gantt w-4 text-center"></i> Gantt
                                </a>
                                <a href="{{ route('projects.show', ['token' => $project->token, 'view' => 'kanban']) }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-b-xl transition-colors
                                    {{ $currentView === 'kanban' ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-table-columns w-4 text-center"></i> Kanban
                                </a>
                            </div>
                        </div>

                        {{-- Create Task --}}
                        @if ($canContribute)
                            <a href="{{ route('tasks.create') }}?project_token={{ $project->token }}"
                                class="group inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-lg
                                bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/30 transition-all duration-300">
                                <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                                Create Task
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- task --}}
            <div id="tab-tasks" class="tab-content {{ $currentTab !== 'tasks' ? 'hidden' : '' }}"
                style="flex: 1; overflow: hidden; position: relative;">
                {{-- LIST VIEW --}}
                @if ($currentView === 'list')
                    @include('projects.partials._task-hierarchy', [
                        'project' => $project,
                        'taskHierarchyRoots' => $taskHierarchyRoots,
                        'canContribute' => $canContribute,
                    ])
                @endif

                @if ($currentView === 'gantt')
                    @include('projects.partials.gantt._container', ['ganttPayload' => $ganttPayload])
                @endif

                {{-- KANBAN VIEW --}}
                @if ($currentView === 'kanban')
                    @include('tasks.partials.kanban._board', [
                        'kanbanStatuses' => $kanbanStatuses,
                        'kanbanTasks' => $kanbanTasks,
                        'kanbanCurrentStatus' => 'all',
                        'kanbanCanContribute' => $canContribute,
                        'kanbanShowProject' => true,
                    ])
                @endif
            </div>

            {{-- == MEMBERS TAB== --}}
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
                        <form method="POST" action="{{ route('projects.members.store', $project->token) }}"
                            class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                <div class="md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select workspace
                                        member</label>
                                    <select name="user_ids[]"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required>
                                        <option value="">Select workspace member</option>
                                        @foreach ($availableMembers as $candidate)
                                            <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                    <select name="role"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required>
                                        <option value="member">Member</option>
                                        <option value="manager">Admin</option>
                                        <option value="viewer">Viewer</option>
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <button
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2.5 font-medium transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                        Add Member
                                    </button>
                                </div>
                            </div>
                        </form>
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
                                    Admin
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
                                                @if (in_array($member->id, $overloadedMemberIds))
                                                    <span
                                                        class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600 mt-1">
                                                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                                        Overload ({{ $memberTaskCounts[$member->id] ?? 0 }} tasks)
                                                    </span>
                                                @endif
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
                                                    <button onclick="toggleMemberDropdown('{{ $member->id }}', 'pm')"
                                                        class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors text-lg font-bold leading-none">⋮</button>
                                                    <div id="dd-pm-{{ $member->id }}"
                                                        class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                        <button onclick="toggleSubRoles('sr-pm-{{ $member->id }}')"
                                                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-purple-50 transition-colors">
                                                            <span class="flex items-center gap-2">
                                                                <svg class="w-4 h-4 text-purple-500" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
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
                                                                        action="{{ route('projects.members.update', [$project->token, $member->id]) }}">
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
                                                            action="{{ route('projects.members.destroy', [$project->token, $member->id]) }}"
                                                            onsubmit="return confirm('Hapus member ini?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
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
                                                @if (in_array($member->id, $overloadedMemberIds))
                                                    <span
                                                        class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600 mt-1">
                                                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                                        Overload ({{ $memberTaskCounts[$member->id] ?? 0 }} tasks)
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($member->id === Auth::id())
                                                <span
                                                    class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                            @endif
                                            @if ($canManageMembers && $member->id !== Auth::id())
                                                <div class="relative flex-shrink-0">
                                                    <button onclick="toggleMemberDropdown('{{ $member->id }}', 'mem')"
                                                        class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors text-lg font-bold leading-none">⋮</button>
                                                    <div id="dd-mem-{{ $member->id }}"
                                                        class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                        <button onclick="toggleSubRoles('sr-mem-{{ $member->id }}')"
                                                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 transition-colors">
                                                            <span class="flex items-center gap-2">
                                                                <svg class="w-4 h-4 text-blue-500" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
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
                                                                        action="{{ route('projects.members.update', [$project->token, $member->id]) }}">
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
                                                            action="{{ route('projects.members.destroy', [$project->token, $member->id]) }}"
                                                            onsubmit="return confirm('Hapus member ini?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
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
                                                    <button onclick="toggleMemberDropdown('{{ $member->id }}', 'vw')"
                                                        class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors text-lg font-bold leading-none">⋮</button>
                                                    <div id="dd-vw-{{ $member->id }}"
                                                        class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                        <button onclick="toggleSubRoles('sr-vw-{{ $member->id }}')"
                                                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-yellow-50 transition-colors">
                                                            <span class="flex items-center gap-2">
                                                                <svg class="w-4 h-4 text-yellow-500" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
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
                                                                        action="{{ route('projects.members.update', [$project->token, $member->id]) }}">
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
                                                            action="{{ route('projects.members.destroy', [$project->token, $member->id]) }}"
                                                            onsubmit="return confirm('Hapus member ini?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
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
            </div>

            {{-- == CHART TAB== --}}
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
            </div>
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
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js">
        </script>
        <script>
            // TAB SWITCHING
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

                // Show/hide tasks
                const tasksActions = document.getElementById('tasks-actions');
                if (tasksActions) {
                    if (tabName === 'tasks') {
                        tasksActions.classList.remove('hidden');
                    } else {
                        tasksActions.classList.add('hidden');
                    }
                }

                // Update URL
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }

            // DROPDOWN TOGGLE
            function toggleDropdown(id) {
                document.querySelectorAll('.dropdown-menu').forEach(el => {
                    if (el.id !== 'dropdown-' + id) {
                        el.classList.add('hidden');
                    }
                });
                const dropdown = document.getElementById('dropdown-' + id);
                if (dropdown) {
                    dropdown.classList.toggle('hidden');
                }
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('[data-dropdown]')) {
                    document.querySelectorAll('.dropdown-menu').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // MODAL FUNCTIONS
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

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.classList.add('hidden');
                    e.target.style.display = 'none';
                }
            });

            // MEMBER DROPDOWN
            function toggleMemberDropdown(memberId, prefix) {
                const dropdownId = 'dd-' + prefix + '-' + memberId;
                const dropdown = document.getElementById(dropdownId);

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

            document.addEventListener('click', function(e) {
                if (!e.target.closest('[id^="dd-"]') && !e.target.closest('button[onclick^="toggleMemberDropdown"]')) {
                    document.querySelectorAll('[id^="dd-"]').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // CHART JS
            @if (!empty($chartData['labels']))
                document.addEventListener('DOMContentLoaded', function() {
                    Chart.register(ChartDataLabels);

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

                    function getLastValidIndex(data) {
                        for (let i = data.length - 1; i >= 0; i--) {
                            if (data[i] !== null && data[i] !== undefined) return i;
                        }
                        return -1;
                    }

                    const lastPlannedIndex = getLastValidIndex(rawData.planned);
                    const lastActualIndex = getLastValidIndex(actualExtended);

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
                                    spanGaps: true,
                                    datalabels: {
                                        display: function(context) {
                                            return context.dataIndex === lastPlannedIndex;
                                        },
                                        align: 'top',
                                        anchor: 'center',
                                        formatter: function(value) {
                                            return value !== null ? value + '%' : '';
                                        },
                                        color: '#ffffff',
                                        font: {
                                            weight: 'bold',
                                            size: 11
                                        },
                                        backgroundColor: '#6366f1',
                                        borderRadius: 6,
                                        padding: {
                                            top: 3,
                                            bottom: 3,
                                            left: 7,
                                            right: 7
                                        },
                                    }
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
                                        display: function(context) {
                                            return context.dataIndex === lastActualIndex;
                                        },
                                        align: 'top',
                                        anchor: 'center',
                                        formatter: function(value) {
                                            return value !== null ? value + '%' : '';
                                        },
                                        color: '#ffffff',
                                        font: {
                                            weight: 'bold',
                                            size: 11
                                        },
                                        backgroundColor: '#10b981',
                                        borderRadius: 6,
                                        padding: {
                                            top: 3,
                                            bottom: 3,
                                            left: 7,
                                            right: 7
                                        },
                                    }
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 30
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                datalabels: {},
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

            document.addEventListener('DOMContentLoaded', function() {
                //  Get initial tab from URL or default to 'tasks'
                const urlParams = new URLSearchParams(window.location.search);
                const initialTab = urlParams.get('tab') || 'tasks';

                switchTab(initialTab);
            });
        </script>

        {{-- status dropdown task --}}
        <div id="task-status-dropdown-portal"
            class="hidden fixed z-[99999] w-44 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden py-1"
            style="pointer-events: auto;">
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

                closeTaskDropdown();

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
                            ${opt.value === current ? 'bg-gray-100 font-semibold text-gray-900' : 'text-gray-600'}">
                            ${opt.label}
                        </button>
                    </form>
                `).join('');

                taskPortal.classList.remove('hidden');
                taskPortal.style.pointerEvents = 'auto';

                const rect = btn.getBoundingClientRect();
                const portalHeight = taskPortal.offsetHeight || 200;
                const spaceBelow = window.innerHeight - rect.bottom;
                const top = spaceBelow < portalHeight + 10 ?
                    rect.top + window.scrollY - portalHeight - 8 :
                    rect.bottom + window.scrollY + 8;

                const left = Math.min(
                    rect.left + window.scrollX,
                    window.innerWidth - portalHeight - 16
                );

                taskPortal.style.top = top + 'px';
                taskPortal.style.left = left + 'px';
            }

            function closeTaskDropdown() {
                if (taskPortal) {
                    taskPortal.classList.add('hidden');
                    taskPortal.style.pointerEvents = 'none';
                }
                activeTaskBtn = null;
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#task-status-dropdown-portal') &&
                    !e.target.closest('[id^="status-btn-"]')) {
                    closeTaskDropdown();
                }
            });

            window.addEventListener('scroll', closeTaskDropdown, {
                passive: true
            });

            window.addEventListener('resize', closeTaskDropdown);
        </script>

        <style>
            .hidden {
                display: none !important;
            }

            .modal {
                transition: opacity 0.2s ease-in-out;
            }

            .dropdown-menu {
                transition: opacity 0.15s ease-in-out;
            }

            .tab-content {
                transition: opacity 0.2s ease-in-out;
            }
        </style>
    @endpush

@endsection
