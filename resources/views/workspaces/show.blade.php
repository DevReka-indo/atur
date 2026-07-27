@extends('layouts.app')

@section('title', 'Workspace — ' . $workspace->name)
@push('styles')
    <style>
        /* Urgent Row Animation */
        .urgent-row {
            position: relative;
            animation: urgentPulse 2s infinite;
        }

        .urgent-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #ef4444, #f97316);
            border-radius: 0 4px 4px 0;
            animation: borderGlow 2s infinite;
        }

        @keyframes urgentPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.1);
            }

            50% {
                box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
            }
        }

        @keyframes borderGlow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Urgent Badge Pulse */
        .urgent-badge {
            animation: badgePulse 1.5s infinite;
        }

        @keyframes badgePulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = Auth::user();
        $isOwner = $workspace->isOwner($user);
        $canManageMembers = $workspace->canManageMembers($user);
        $canCreateProject = $workspace->canCreateProject($user);
        $initial = strtoupper(substr($workspace->name, 0, 1));
    @endphp

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
        <a href="{{ route('workspaces.index') }}" class="hover:text-indigo-600 transition-colors">Workspaces</a>
        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
        <span class="text-gray-700 font-medium" aria-current="page">{{ $workspace->name }}</span>
    </nav>

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- HEADER WORKSPACE --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">

                {{-- Workspace Info --}}
                <div class="flex items-start gap-4 flex-1 min-w-0">
                    <div
                        class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600
                                    flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-indigo-500/30">
                        {{ strtoupper(substr($workspace->name, 0, 1)) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">
                            {{ $workspace->name }}
                        </h1>
                        <p class="mt-2 text-gray-600 leading-relaxed">
                            {{ $workspace->description ?: 'Tidak ada deskripsi untuk workspace ini.' }}
                        </p>

                        <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-diagram-project w-5 text-center text-sm"></i>
                                <span class="font-medium text-gray-700">{{ $workspace->projects_count }}</span> Projects
                            </span>
                            @if ($isOwner)
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-crown"></i>
                                    {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_OWNER) }}
                                </span>
                            @elseif ($currentRole === 'admin')
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-500">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_ADMIN) }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-500">
                                    <i class="fa-solid fa-user"></i>
                                    {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if ($isOwner)
                    <div class="flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('workspaces.edit', $workspace->token) }}"
                                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white
                                          bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors duration-200">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                <span>Edit</span>
                            </a>
                            <button onclick="openModal('delete-workspace-modal')"
                                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white
                                               bg-red-600 hover:bg-red-700 rounded-xl transition-colors duration-200">
                                <i class="fa-solid fa-trash text-xs"></i>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- HEADER SECTION --}}
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                {{-- Tabs Navigation --}}
                <div class="bg-white rounded-xl shadow-sm p-1.5 inline-flex">
                    <button onclick="switchTab('projects')" id="tab-btn-projects"
                        class="tab-btn px-5 py-2.5 rounded-lg text-sm transition-all duration-200 flex items-center gap-2
                    bg-[#ADE8F4] text-gray-900 font-medium shadow-sm">
                        <i class="fa-solid fa-diagram-project w-5 text-center text-sm"></i>
                        <span>Project</span>
                    </button>

                    <button onclick="switchTab('members')" id="tab-btn-members"
                        class="tab-btn px-5 py-2.5 rounded-lg text-sm transition-all duration-200 flex items-center gap-2
                    text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Members</span>
                    </button>
                </div>

                {{-- Dynamic Action Buttons --}}
                @include('workspaces.partials._management-actions', [
                    'workspace' => $workspace,
                    'canCreateProject' => $canCreateProject,
                    'canManageMembers' => $canManageMembers,
                ])

            </div>

            @if ($canManageMembers)
                @include('workspaces.partials.members._pending-invitations', [
                    'workspace' => $workspace,
                ])
            @endif
        </div>

        {{-- PROJECTS TAB --}}
        <div id="tab-projects" class="tab-content">

            @if ($workspace->projects->isEmpty())
                <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full
                                    bg-gradient-to-br from-indigo-100 to-violet-100 text-indigo-600 mb-4">
                        <i class="fa-regular fa-folder-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Belum ada project</h3>
                    <p class="mt-1 text-gray-500">Mulai dengan membuat project pertama di workspace ini.</p>
                </div>
            @else
                <div class="bg-white border border-gray-200 shadow-sm mt-4 rounded-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0 rounded-xl overflow-hidden">
                            <thead>
                                <tr class="bg-[#ADE8F4]">
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 flex-shrink-0"></div>
                                            Project
                                        </div>
                                    </th>
                                    <th
                                        class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                        Creator </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[200px]">
                                        Progress</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Tasks</th>
                                    <th
                                        class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">

                                @foreach ($workspace->projects as $project)
                                    @php
                                        $totalWeight = $project->tasks->sum('weight');
                                        $earnedValue = $project->tasks->sum(
                                            fn($task) => $task->weight * ($task->statusWeight->weight_value ?? 0),
                                        );
                                        $progress = $totalWeight > 0 ? ($earnedValue / $totalWeight) * 100 : 0;

                                        // Hitung di sini agar tersedia untuk kondisi <tr> di bawah
                                        $wsProgressVal = min(round($progress), 100);
                                        $wsHue = ($wsProgressVal / 100) * 120;
                                        $wsColorStart = "hsl($wsHue, 65%, 75%)";
                                        $wsColorEnd = 'hsl(' . ($wsHue + 10) . ', 70%, 70%)';
                                        $wsTextColor = $wsProgressVal >= 100 ? 'text-emerald-500' : 'text-gray-600';

                                        $statusConfig = [
                                            'planning' => [
                                                'class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                                'hover' => 'hover:bg-slate-200',
                                            ],
                                            'in_progress' => [
                                                'class' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                                'hover' => 'hover:bg-blue-200',
                                            ],
                                            'active' => [
                                                'class' => 'bg-green-100 text-green-700 border border-green-200',
                                                'hover' => 'hover:bg-green-200',
                                            ],
                                            'review' => [
                                                'class' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                                'hover' => 'hover:bg-amber-200',
                                            ],
                                            'completed' => [
                                                'class' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                                'hover' => 'hover:bg-emerald-200',
                                            ],
                                            'on_hold' => [
                                                'class' => 'bg-yellow-100 text-yellow-700 border border-gray-200',
                                                'hover' => 'hover:bg-gray-200',
                                            ],
                                            'urgent' => [
                                                'class' => 'bg-orange-200 text-orange-800 border border-orange-400',
                                                'hover' => 'hover:bg-orange-300',
                                            ],
                                        ];

                                        $config = $statusConfig[$project->status] ?? $statusConfig['planning'];
                                    @endphp

                                    {{-- Row untuk semua project --}}
                                    <tr
                                        class="hover:bg-gray-50 border-b border-gray-100 transition-colors duration-150
                                            {{ $project->status === 'urgent' && $wsProgressVal < 100 ? 'urgent-row' : '' }}">

                                        {{-- Project name --}}
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                {{-- Warning Icon + Badge untuk Urgent --}}
                                                @if ($project->status === 'urgent' && $wsProgressVal < 100)
                                                    <div class="w-6 h-6 flex-shrink-0">
                                                        <div
                                                            class="w-6 h-6 flex items-center justify-center rounded-lg flex-shrink-0 bg-gradient-to-br from-red-600 to-red-500 shadow-[0_2px_4px_rgba(220,38,38,0.3)]">
                                                            <i
                                                                class="fa-solid fa-triangle-exclamation text-white text-xs animate-pulse"></i>
                                                        </div>
                                                    </div>
                                                @else
                                                    {{-- Placeholder agar alignment tetap rata --}}
                                                    <div class="w-6 h-6 flex-shrink-0"></div>
                                                @endif

                                                {{-- Project Initial Badge --}}
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100
                                                flex items-center justify-center text-indigo-700 font-semibold text-sm">
                                                    {{ strtoupper(substr($project->name, 0, 1)) }}
                                                </div>

                                                {{-- Project Name --}}
                                                <span
                                                    class="font-medium text-gray-900 whitespace-nowrap">{{ $project->name }}</span>
                                            </div>
                                        </td>

                                        {{-- Creator --}}
                                        <td class="px-5 py-4">
                                            @if ($project->creator)
                                                <div class="flex items-center gap-2">
                                                    @if ($project->creator->profile_photo)
                                                        <img src="{{ asset('storage/' . $project->creator->profile_photo) }}"
                                                            class="w-7 h-7 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                                                    @else
                                                        <div
                                                            class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                            <span class="text-xs font-bold text-indigo-600">
                                                                {{ strtoupper(substr($project->creator->name, 0, 1)) }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <span
                                                        class="text-sm text-gray-700">{{ $project->creator->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Status dropdown --}}
                                        <td class="px-6 py-4">
                                            @php
                                                $statusOptions = [
                                                    'planning' => [
                                                        'label' => 'Planning',
                                                        'class' => 'bg-gray-100 text-gray-700',
                                                    ],
                                                    'active' => [
                                                        'label' => 'Active',
                                                        'class' => 'bg-green-100 text-green-700',
                                                    ],
                                                    'completed' => [
                                                        'label' => 'Completed',
                                                        'class' => 'bg-emerald-100 text-emerald-700',
                                                    ],
                                                    'on_hold' => [
                                                        'label' => 'On hold',
                                                        'class' => 'bg-yellow-100 text-yellow-700',
                                                    ],
                                                    'cancelled' => [
                                                        'label' => 'Cancelled',
                                                        'class' => 'bg-red-100 text-red-700',
                                                    ],
                                                    'urgent' => [
                                                        'label' => 'Urgent',
                                                        'class' => 'bg-orange-200 text-orange-800',
                                                    ],
                                                ];

                                                $currentStatus = $project->status;
                                                $currentOption =
                                                    $statusOptions[$currentStatus] ?? $statusOptions['planning'];
                                            @endphp
                                            @php
                                                $canChangeStatus = $isOwner || $currentRole === 'admin';
                                            @endphp

                                            @if ($canChangeStatus)
                                                {{-- Dropdown untuk Owner/Admin --}}
                                                <button type="button"
                                                    onclick="toggleProjectStatusDropdown({{ $project->id }}, this)"
                                                    data-project-id="{{ $project->id }}"
                                                    data-update-url="{{ route('projects.updateStatus', $project->token) }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                    {{ $currentOption['class'] }} hover:opacity-80 transition-all cursor-pointer
                    w-32 justify-between flex-shrink-0 border-0">
                                                    <span class="flex items-center gap-1.5 truncate flex-1">
                                                        {{ $currentOption['label'] }}
                                                    </span>
                                                    <i
                                                        class="fa-solid fa-chevron-down text-[8px] opacity-60 flex-shrink-0"></i>
                                                </button>

                                                {{-- Dropdown Menu --}}
                                                <div id="status-dropdown-{{ $project->id }}"
                                                    class="hidden absolute z-50 mt-1 w-40 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden py-1">
                                                    @foreach ($statusOptions as $value => $option)
                                                        <form method="POST"
                                                            action="{{ route('projects.updateStatus', $project->token) }}"
                                                            class="status-form-{{ $project->id }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status"
                                                                value="{{ $value }}">
                                                            <button type="submit"
                                                                onclick="updateProjectStatus(event, {{ $project->id }})"
                                                                class="w-full text-left px-3 py-2 text-xs transition-colors flex items-center gap-2
                                {{ $value === $currentStatus ? 'bg-gray-100 font-semibold' : 'hover:bg-gray-50' }} border-0">
                                                                @if ($value === $currentStatus)
                                                                    <i
                                                                        class="fa-solid fa-check text-green-500 text-[10px]"></i>
                                                                @else
                                                                    <span class="w-3"></span>
                                                                @endif
                                                                <span>{{ $option['label'] }}</span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            @else
                                                {{-- Badge static untuk non-owner/admin --}}
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                {{ $currentOption['class'] }} transition-colors cursor-default
                w-32 justify-center flex-shrink-0 border-0">
                                                    {{ str($project->status)->replace('_', ' ')->title() }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Progress --}}
                                        <td class="px-6 py-4 align-middle">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-500"
                                                        style="width: {{ $wsProgressVal }}%;
                                    background: linear-gradient(90deg, {{ $wsColorStart }}, {{ $wsColorEnd }});
                                    box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);">
                                                    </div>
                                                </div>
                                                <span class="text-sm font-medium {{ $wsTextColor }} w-12 text-right">
                                                    {{ number_format($wsProgressVal, 0) }}%
                                                </span>
                                            </div>
                                        </td>

                                        {{-- Tasks count --}}
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <span class="font-medium text-gray-900">{{ $project->tasks_count }}</span>
                                            tasks
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('projects.show', $project->token) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                                @if ($isOwner)
                                                    <a href="{{ route('projects.edit', $project->token) }}"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <form action="{{ route('projects.destroy', $project->token) }}"
                                                        method="POST" onsubmit="return confirm('Hapus project ini?')">
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- MEMBERS TAB --}}
        <div id="tab-members" class="tab-content" style="display: none;">


            {{-- Members List Cards --}}
            @php
                $owner = $workspace->members->first(fn($m) => $workspace->isOwner($m));
                $admins = $workspace->members
                    ->filter(fn($m) => $m->pivot->role === 'admin' && !$workspace->isOwner($m))
                    ->sortByDesc(fn($m) => $m->id === Auth::id());
                $regularMembers = $workspace->members
                    ->filter(fn($m) => $m->pivot->role === 'member' && !$workspace->isOwner($m))
                    ->sortByDesc(fn($m) => $m->id === Auth::id());
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- ===== Admins Group ===== --}}
                <div class="bg-purple-50/50 rounded-xl p-4 border-2 border-purple-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-purple-900 flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved"></i> Workspace Admins
                        </h4>
                        <span class="bg-purple-200 text-purple-800 text-xs font-bold px-2 py-1 rounded-full">
                            {{ $admins->count() + ($owner ? 1 : 0) }}
                        </span>
                    </div>
                    <div class="space-y-2">

                        {{-- Owner (tidak ada tombol aksi) --}}
                        @if ($owner)
                            <div class="bg-white rounded-lg p-3 border border-amber-200 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-2">
                                    @if ($owner->profile_photo)
                                        <img src="{{ asset('storage/' . $owner->profile_photo) }}"
                                            alt="{{ $owner->name }}"
                                            class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                            {{ strtoupper(substr($owner->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 text-sm truncate">{{ $owner->name }}
                                        </p>
                                        <p class="text-gray-500 text-xs truncate">{{ $owner->email }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span
                                            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                            <i class="fa-solid fa-crown text-[10px] mr-0.5"></i>
                                            {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_OWNER) }}
                                        </span>
                                        @if ($owner->id === Auth::id())
                                            <span
                                                class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Admin members dengan three dots --}}
                        @foreach ($admins as $member)
                            <div class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-2">

                                    {{-- Avatar --}}
                                    @if ($member->profile_photo)
                                        <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                            alt="{{ $member->name }}"
                                            class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    {{-- Name & Email --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 text-sm truncate">{{ $member->name }}
                                        </p>
                                        <p class="text-gray-500 text-xs truncate">{{ $member->email }}</p>
                                    </div>

                                    {{-- You badge --}}
                                    @if ($member->id === Auth::id())
                                        <span
                                            class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                    @endif

                                    {{-- Three dots dropdown --}}
                                    @if ($canManageMembers && $member->id !== Auth::id())
                                        <div class="relative flex-shrink-0">
                                            <button onclick="toggleMemberDropdown('{{ $member->id }}', 'adm')"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200
               text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors font-bold">
                                                ···
                                            </button>
                                            <div id="dd-adm-{{ $member->id }}"
                                                class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                <button onclick="toggleSubRoles('sr-adm-{{ $member->id }}')"
                                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-purple-50 transition-colors">
                                                    <span class="flex items-center gap-2">
                                                        <i class="fa-solid fa-user-pen w-4 text-purple-500 text-xs"></i>
                                                        Switch Role
                                                    </span>
                                                    <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                                                </button>
                                                <div id="sr-adm-{{ $member->id }}"
                                                    class="hidden border-t border-gray-100">
                                                    @if ($member->pivot->role !== 'admin')
                                                        <form method="POST"
                                                            action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="role" value="admin">
                                                            <button type="submit"
                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_ADMIN) }}</button>
                                                        </form>
                                                    @endif
                                                    @if ($member->pivot->role !== 'member')
                                                        <form method="POST"
                                                            action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="role" value="member">
                                                            <button type="submit"
                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <button type="button"
                                                    onclick="confirmRemoveMember(
        '{{ $member->name }}',
        '{{ route('workspaces.members.destroy', [$workspace->token, $member]) }}',
        '{{ route('workspaces.members.destroy.cascade', [$workspace->token, $member]) }}'
    )"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                    <i class="fa-solid fa-user-minus w-4 text-xs"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ===== Members Group ===== --}}
                <div class="bg-blue-50/50 rounded-xl p-4 border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-blue-900 flex items-center gap-2">
                            <i class="fa-solid fa-user-group"></i> Workspace Members
                        </h4>
                        <span
                            class="bg-blue-200 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">{{ $regularMembers->count() }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach ($regularMembers as $member)
                            <div class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-2">

                                    {{-- Avatar --}}
                                    @if ($member->profile_photo)
                                        <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                            alt="{{ $member->name }}"
                                            class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-cyan-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    {{-- Name & Email --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 text-sm truncate">{{ $member->name }}
                                        </p>
                                        <p class="text-gray-500 text-xs truncate">{{ $member->email }}</p>
                                    </div>

                                    {{-- You badge --}}
                                    @if ($member->id === Auth::id())
                                        <span
                                            class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                    @endif

                                    {{-- Three dots dropdown --}}
                                    @if ($canManageMembers && $member->id !== Auth::id())
                                        <div class="relative flex-shrink-0">
                                            <button onclick="toggleMemberDropdown('{{ $member->id }}', 'mem')"
                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200
               text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors font-bold">
                                                ···
                                            </button>
                                            <div id="dd-mem-{{ $member->id }}"
                                                class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                                <button onclick="toggleSubRoles('sr-mem-{{ $member->id }}')"
                                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-purple-50 transition-colors">
                                                    <span class="flex items-center gap-2">
                                                        <i class="fa-solid fa-user-pen w-4 text-purple-500 text-xs"></i>
                                                        Switch Role
                                                    </span>
                                                    <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                                                </button>
                                                <div id="sr-mem-{{ $member->id }}"
                                                    class="hidden border-t border-gray-100">
                                                    @if ($member->pivot->role !== 'admin')
                                                        <form method="POST"
                                                            action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="role" value="admin">
                                                            <button type="submit"
                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_ADMIN) }}</button>
                                                        </form>
                                                    @endif
                                                    @if ($member->pivot->role !== 'member')
                                                        <form method="POST"
                                                            action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="role" value="member">
                                                            <button type="submit"
                                                                class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <button type="button"
                                                    onclick="confirmRemoveMember(
        '{{ $member->name }}',
        '{{ route('workspaces.members.destroy', [$workspace->token, $member]) }}',
        '{{ route('workspaces.members.destroy.cascade', [$workspace->token, $member]) }}'
    )"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                    <i class="fa-solid fa-user-minus w-4 text-xs"></i> Remove
                                                </button>
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

        {{-- DELETE MODAL --}}
        @if ($isOwner)
            <div id="delete-workspace-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display:none;">
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
                    onclick="closeModal('delete-workspace-modal')"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Confirm Delete</h3>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Are you sure you want to delete
                        <strong class="text-gray-900">{{ $workspace->name }}</strong>?
                        This action cannot be undone and all associated projects and data will be permanently removed.
                    </p>
                    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                        <button onclick="closeModal('delete-workspace-modal')"
                            class="px-5 py-2.5 text-gray-700 font-medium rounded-xl border border-gray-300 bg-white hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('workspaces.destroy', $workspace->token) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5
                            rounded-xl font-medium text-sm text-white bg-red-600 hover:bg-red-700 transition-all">
                                <i class="fa-solid fa-trash mr-2"></i>
                                Yes, Delete Workspace
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
    </div>

    {{-- MODAL: Konfirmasi Remove Member --}}
    <div id="remove-member-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeRemoveMemberModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">
                    <i class="fa-solid fa-user-minus text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Remove Member</h3>
                    <p class="text-sm text-gray-500" id="modal-member-name"></p>
                </div>
            </div>
            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div class="flex gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i>
                    <p class="text-sm text-amber-700" id="modal-project-info"></p>
                </div>
            </div>
            <div class="mt-5 space-y-3">
                <button type="button" onclick="submitRemove('workspace-only')"
                    class="w-full text-left flex items-start gap-3 px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all group">
                    <div
                        class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-building-user text-gray-500 group-hover:text-indigo-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-indigo-700">Hapus dari Workspace
                            saja</p>
                        <p class="text-xs text-gray-500 mt-0.5">Member tetap ada di project yang sudah tergabung</p>
                    </div>
                </button>
                <button type="button" onclick="submitRemove('cascade')"
                    class="w-full text-left flex items-start gap-3 px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-red-400 hover:bg-red-50 transition-all group">
                    <div
                        class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-trash-can text-gray-500 group-hover:text-red-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-red-700">Hapus dari semua tempat</p>
                        <p class="text-xs text-gray-500 mt-0.5">Hapus dari workspace <strong>dan</strong> semua project
                            terkait</p>
                    </div>
                </button>
            </div>
            <div class="mt-5 flex justify-end">
                <button type="button" onclick="closeRemoveMemberModal()"
                    class="px-5 py-2.5 text-sm text-gray-700 font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
            </div>
            <form id="form-remove" method="POST" style="display:none;">
                @csrf @method('DELETE')
            </form>
        </div>
    </div>

    @if ($canManageMembers)
        @include('workspaces.partials.members._invite-modal', [
            'workspace' => $workspace,
        ])
    @endif
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(el => {
                el.style.display = 'none';
            });

            // Reset all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-[#ADE8F4]', 'text-gray-900', 'font-medium', 'shadow-sm');
                btn.classList.add('text-gray-500', 'hover:text-gray-700');
            });

            // Show selected tab content
            document.getElementById('tab-' + tabName).style.display = '';

            // Activate selected tab button
            const activeBtn = document.getElementById('tab-btn-' + tabName);
            activeBtn.classList.add('bg-[#ADE8F4]', 'text-gray-900', 'font-medium', 'shadow-sm');
            activeBtn.classList.remove('text-gray-500', 'hover:text-gray-700');

            // ✅ Toggle action buttons based on active tab
            const createProjectBtn = document.getElementById('action-create-project');
            const inviteMemberBtn = document.getElementById('action-invite-member');

            if (createProjectBtn) {
                createProjectBtn.style.display = tabName === 'projects' ? '' : 'none';
            }
            if (inviteMemberBtn) {
                inviteMemberBtn.style.display = tabName === 'members' ? '' : 'none';
            }
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }

        function toggleMemberDropdown(memberId, prefix) {
            const dropdownId = 'dd-' + prefix + '-' + memberId;
            const dropdown = document.getElementById(dropdownId);
            document.querySelectorAll('[id^="dd-"]').forEach(el => {
                if (el.id !== dropdownId) el.classList.add('hidden');
            });
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        function toggleSubRoles(subId) {
            const sub = document.getElementById(subId);
            if (sub) sub.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="dd-"]') && !e.target.closest('button[onclick^="toggleMemberDropdown"]')) {
                document.querySelectorAll('[id^="dd-"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Ambil tab dari URL param (setelah redirect controller) atau localStorage
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            const tabFromStorage = localStorage.getItem('ws_activeTab');

            const tab = tabFromUrl || tabFromStorage || 'projects';
            switchTab(tab);

            // Simpan tab aktif ke localStorage
            localStorage.setItem('ws_activeTab', tab);

            // Bersihkan URL
            window.history.replaceState({}, '', window.location.pathname);
        });

        // Simpan tab setiap kali user klik tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.id.replace('tab-btn-', '');
                localStorage.setItem('ws_activeTab', tab);
            });
        });

        let _removeUrls = {
            workspaceOnly: '',
            cascade: ''
        };

        function confirmRemoveMember(memberName, workspaceOnlyUrl, cascadeUrl) {
            _removeUrls.workspaceOnly = workspaceOnlyUrl;
            _removeUrls.cascade = cascadeUrl;

            fetch(workspaceOnlyUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.needs_confirmation) {
                        document.getElementById('modal-member-name').textContent = data.user_name;
                        document.getElementById('modal-project-info').innerHTML =
                            `Member ini masih terdaftar di <strong>${data.project_count} project</strong>: ${data.project_names.join(', ')}. Pilih tindakan:`;
                        document.getElementById('remove-member-modal').classList.remove('hidden');
                        document.getElementById('remove-member-modal').style.display = 'flex';
                    } else {
                        window.location.href = window.location.pathname + '?tab=members';
                    }
                })
                .catch(() => {
                    if (confirm('Hapus member ini?')) {
                        document.getElementById('form-remove').action = workspaceOnlyUrl;
                        document.getElementById('form-remove').submit();
                    }
                });
        }

        function submitRemove(type) {
            const url = type === 'cascade' ? _removeUrls.cascade : _removeUrls.workspaceOnly;
            document.getElementById('form-remove').action = url;
            document.getElementById('form-remove').submit();
        }

        function closeRemoveMemberModal() {
            document.getElementById('remove-member-modal').classList.add('hidden');
            document.getElementById('remove-member-modal').style.display = 'none';
        }
    </script>

    <script>
        // ===== PROJECT STATUS DROPDOWN =====
        let activeDropdown = null;

        function toggleProjectStatusDropdown(projectId, btn) {
            const dropdown = document.getElementById(`status-dropdown-${projectId}`);
            const allDropdowns = document.querySelectorAll('[id^="status-dropdown-"]');

            // Close all other dropdowns
            allDropdowns.forEach(d => {
                if (d.id !== `status-dropdown-${projectId}`) {
                    d.classList.add('hidden');
                }
            });

            // Toggle current dropdown
            if (dropdown.classList.contains('hidden')) {
                // Position dropdown
                const rect = btn.getBoundingClientRect();
                const scrollY = window.scrollY || window.pageYOffset;
                const scrollX = window.scrollX || window.pageXOffset;

                dropdown.style.top = (rect.bottom + scrollY + 4) + 'px';
                dropdown.style.left = (rect.left + scrollX) + 'px';

                dropdown.classList.remove('hidden');
                activeDropdown = projectId;
            } else {
                dropdown.classList.add('hidden');
                activeDropdown = null;
            }
        }

        function updateProjectStatus(event, projectId) {
            const btn = event.target.closest('button');
            const form = btn.closest('form');

            // Show loading state
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Updating...';

            // Submit form
            fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'X-HTTP-Method-Override': 'PATCH',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form)
                })
                .then(response => {
                    if (response.ok) {
                        // Reload page to update progress and styling
                        window.location.reload();
                    } else {
                        throw new Error('Failed to update');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                    alert('Gagal mengubah status. Silakan coba lagi.');
                });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="status-dropdown-"]') &&
                !e.target.closest('[onclick^="toggleProjectStatusDropdown"]')) {
                const allDropdowns = document.querySelectorAll('[id^="status-dropdown-"]');
                allDropdowns.forEach(d => d.classList.add('hidden'));
                activeDropdown = null;
            }
        });

        // Close on scroll
        window.addEventListener('scroll', function() {
            const allDropdowns = document.querySelectorAll('[id^="status-dropdown-"]');
            allDropdowns.forEach(d => d.classList.add('hidden'));
            activeDropdown = null;
        }, {
            passive: true
        });
    </script>
@endsection
