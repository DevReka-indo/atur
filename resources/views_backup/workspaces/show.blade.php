@extends('layouts.app')

@section('title', $workspace->name)

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

    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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
                                        <i class="fa-solid fa-crown"></i> Owner
                                    </span>
                                @elseif ($currentRole === 'admin')
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-500">
                                        <i class="fa-solid fa-shield-halved"></i> Admin
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-500">
                                        <i class="fa-solid fa-user"></i> Member
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    @if ($isOwner)
                        <div class="flex-shrink-0">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('workspaces.edit', $workspace) }}"
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
                    <div class="flex items-center gap-2">
                        {{-- Create Project Button (hanya di tab Projects) --}}
                        @if ($canCreateProject)
                            <div id="action-create-project">
                                <a href="{{ route('projects.create') }}?workspace_id={{ $workspace->id }}"
                                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                                        bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30
                                        transition-all duration-300">
                                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                                    Create Project
                                </a>
                            </div>
                        @endif

                        {{-- Invite Member Button (hanya di tab Members) --}}
                        @if ($canManageMembers)
                            <div id="action-invite-member" style="display: none;">
                                <button type="button" onclick="document.getElementById('inviteModal').classList.remove('hidden')"
                                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                                        bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30
                                        transition-all duration-300">
                                    <i class="fa-solid fa-user-plus mr-2 transition-transform group-hover:rotate-110"></i>
                                    Invite Member
                                </button>
                            </div>
                        @endif
                    </div>

                </div>
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
                                            Project</th>
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

                                            // ✅ Status colors dengan variasi lebih jelas + icon

                                            $statusConfig = [
                                                'planning' => [
                                                    'class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                                    'icon' => 'fa-clipboard',
                                                    'hover' => 'hover:bg-slate-200'
                                                ],
                                                'in_progress' => [
                                                    'class' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                                    'icon' => 'fa-spinner fa-spin',
                                                    'hover' => 'hover:bg-blue-200'
                                                ],
                                                'active' => [  // ✅ TAMBAHKAN INI
                                                    'class' => 'bg-green-100 text-green-700 border border-green-200',
                                                    'icon' => 'fa-play',
                                                    'hover' => 'hover:bg-green-200'
                                                ],
                                                'review' => [
                                                    'class' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                                    'icon' => 'fa-eye',
                                                    'hover' => 'hover:bg-amber-200'
                                                ],
                                                'completed' => [
                                                    'class' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                                    'icon' => 'fa-circle-check',
                                                    'hover' => 'hover:bg-emerald-200'
                                                ],
                                                'on_hold' => [
                                                    'class' => 'bg-yellow-100 text-yellow-700 border border-gray-200',
                                                    'icon' => 'fa-pause',
                                                    'hover' => 'hover:bg-gray-200'
                                                ],
                                            ];

                                            $config = $statusConfig[$project->status] ?? $statusConfig['planning'];

                                        @endphp

                                        {{-- nama project --}}
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center text-indigo-700 font-semibold text-sm">
                                                        {{ strtoupper(substr($project->name, 0, 1)) }}
                                                    </div>
                                                    <span class="font-medium text-gray-900">{{ $project->name }}</span>
                                                </div>
                                            </td>

                                            {{-- Owner --}}
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

                                            {{-- Status --}}
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                                                            {{ $config['class'] }} {{ $config['hover'] }} transition-colors cursor-default">
                                                    <i class="fa-solid {{ $config['icon'] }} text-[10px]"></i>
                                                    {{ str($project->status)->replace('_', ' ')->title() }}
                                                </span>
                                            </td>

                                            {{-- progress --}}
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full transition-all duration-500"
                                                            style="width: {{ min(100, max(0, $progress)) }}%"></div>
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-700 w-12 text-right">
                                                        {{ number_format($progress, 0) }}%
                                                    </span>
                                                </div>
                                            </td>

                                            {{-- jumlah task --}}
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <span class="font-medium text-gray-900">{{ $project->tasks_count }}</span>
                                                tasks
                                            </td>

                                            {{-- button action --}}
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('projects.show', $project) }}"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                                        <i class="fa-regular fa-eye"></i>
                                                    </a>
                                                    @if ($isOwner)
                                                        <a href="{{ route('projects.edit', $project) }}"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </a>
                                                        <form action="{{ route('projects.destroy', $project) }}"
                                                            method="POST"
                                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus project ini?')">
                                                            @csrf
                                                            @method('DELETE')
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
                                <i class="fa-solid fa-shield-halved"></i> Admins
                            </h4>
                            <span class="bg-purple-200 text-purple-800 text-xs font-bold px-2 py-1 rounded-full">
                                {{ $admins->count() + ($owner ? 1 : 0) }}
                            </span>
                        </div>
                        <div class="space-y-2">

                            {{-- Owner (tidak ada tombol aksi) --}}
                            @if ($owner)
                                <div
                                    class="bg-white rounded-lg p-3 border border-amber-200 hover:shadow-md transition-shadow">
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
                                                <i class="fa-solid fa-crown text-[10px] mr-0.5"></i> Owner
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
                                <div
                                    class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
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
                                                            <i
                                                                class="fa-solid fa-user-pen w-4 text-purple-500 text-xs"></i>
                                                            Switch Role
                                                        </span>
                                                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                                                    </button>
                                                    <div id="sr-adm-{{ $member->id }}"
                                                        class="hidden border-t border-gray-100">
                                                        @if ($member->pivot->role !== 'admin')
                                                            <form method="POST"
                                                                action="{{ route('workspaces.members.update', [$workspace, $member]) }}">
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="role" value="admin">
                                                                <button type="submit"
                                                                    class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">Admin</button>
                                                            </form>
                                                        @endif
                                                        @if ($member->pivot->role !== 'member')
                                                            <form method="POST"
                                                                action="{{ route('workspaces.members.update', [$workspace, $member]) }}">
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="role" value="member">
                                                                <button type="submit"
                                                                    class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">Member</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                    <div class="border-t border-gray-100 my-1"></div>
                                                    <form method="POST"
                                                        action="{{ route('workspaces.members.destroy', [$workspace, $member]) }}"
                                                        onsubmit="return confirm('Remove member ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                            <i class="fa-solid fa-user-minus w-4 text-xs"></i> Delete
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

                    {{-- ===== Members Group ===== --}}
                    <div class="bg-blue-50/50 rounded-xl p-4 border-2 border-blue-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-blue-900 flex items-center gap-2">
                                <i class="fa-solid fa-user-group"></i> Members
                            </h4>
                            <span
                                class="bg-blue-200 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">{{ $regularMembers->count() }}</span>
                        </div>
                        <div class="space-y-2">
                            @foreach ($regularMembers as $member)
                                <div
                                    class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
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
                                                            <i
                                                                class="fa-solid fa-user-pen w-4 text-purple-500 text-xs"></i>
                                                            Switch Role
                                                        </span>
                                                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                                                    </button>
                                                    <div id="sr-mem-{{ $member->id }}"
                                                        class="hidden border-t border-gray-100">
                                                        @if ($member->pivot->role !== 'admin')
                                                            <form method="POST"
                                                                action="{{ route('workspaces.members.update', [$workspace, $member]) }}">
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="role" value="admin">
                                                                <button type="submit"
                                                                    class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">Admin</button>
                                                            </form>
                                                        @endif
                                                        @if ($member->pivot->role !== 'member')
                                                            <form method="POST"
                                                                action="{{ route('workspaces.members.update', [$workspace, $member]) }}">
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="role" value="member">
                                                                <button type="submit"
                                                                    class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">Member</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                    <div class="border-t border-gray-100 my-1"></div>
                                                    <form method="POST"
                                                        action="{{ route('workspaces.members.destroy', [$workspace, $member]) }}"
                                                        onsubmit="return confirm('Remove member ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                            <i class="fa-solid fa-user-minus w-4 text-xs"></i> Delete
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
                            <form method="POST" action="{{ route('workspaces.destroy', $workspace) }}">
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

    {{-- MODAL INVITE --}}
    <div id="inviteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600
                            flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr($workspace->name, 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Invite to "{{ $workspace->name }}"</h2>
                        <p class="text-xs text-gray-400">Tambahkan anggota baru ke workspace ini</p>
                    </div>
                </div>
                <button onclick="document.getElementById('inviteModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Flash Messages --}}
                @if (session('invite_success'))
                    <div
                        class="px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-xl flex items-center gap-2">
                        <i class="fa-solid fa-circle-check"></i> {{ session('invite_success') }}
                    </div>
                @endif
                @if (session('invite_error'))
                    <div
                        class="px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ session('invite_error') }}
                    </div>
                @endif

                {{-- Section 1: Invite via Email --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-envelope mr-1"></i> Undang via Email
                    </label>
                    <form action="{{ route('invitations.send') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="workspace">
                        <input type="hidden" name="invitable_id" value="{{ $workspace->id }}">
                        <div class="flex gap-2">
                            <input type="email" name="email" required placeholder="colleague@example.com"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-sm
                                   focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <button type="submit"
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white
                                   text-sm font-semibold rounded-xl transition flex items-center gap-1.5">
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                Kirim
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Mereka akan menerima email undangan</p>
                    </form>
                </div>

                {{-- Divider --}}
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs text-gray-400 font-medium">atau bagikan link</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                {{-- Section 2: Copy Link --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-link mr-1"></i> Invite Link
                    </label>
                    <div class="flex gap-2">
                        <input type="text" readonly id="invite-link-input"
                            value="{{ $workspace->invite_token ? route('workspaces.invite.join', $workspace->invite_token) : '' }}"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-xs
                               bg-gray-50 text-gray-500 cursor-default focus:outline-none truncate">
                        <button type="button" onclick="copyInviteLink()" id="copy-link-btn"
                            class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2.5
                               rounded-xl border border-gray-300 bg-white hover:bg-gray-50
                               text-sm font-medium text-gray-700 transition">
                            <i class="fa-regular fa-copy text-sm" id="copy-icon"></i>
                            <span id="copy-label">Salin</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">
                        Siapapun dengan link ini dapat bergabung sebagai Member
                    </p>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-3 bg-gray-50 loborder-t border-gray-100 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-gray-400 text-xs"></i>
                <p class="text-xs text-gray-400">Hanya Owner & Admin yang dapat mengundang anggota</p>
            </div>

        </div>
    </div>
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

        function copyInviteLink() {
            const input = document.getElementById('invite-link-input');
            const btn = document.getElementById('copy-link-btn');
            const icon = document.getElementById('copy-icon');
            const label = document.getElementById('copy-label');

            navigator.clipboard.writeText(input.value).then(() => {
                // Ubah tampilan tombol jadi "Tersalin"
                icon.classList.replace('fa-regular', 'fa-solid');
                icon.classList.replace('fa-copy', 'fa-check');
                icon.classList.add('text-green-500');
                label.textContent = 'Tersalin!';
                btn.classList.add('border-green-300', 'bg-green-50', 'text-green-700');
                btn.classList.remove('border-gray-300', 'bg-white', 'text-gray-700');

                // Kembalikan ke semula setelah 2 detik
                setTimeout(() => {
                    icon.classList.replace('fa-solid', 'fa-regular');
                    icon.classList.replace('fa-check', 'fa-copy');
                    icon.classList.remove('text-green-500');
                    label.textContent = 'Salin';
                    btn.classList.remove('border-green-300', 'bg-green-50', 'text-green-700');
                    btn.classList.add('border-gray-300', 'bg-white', 'text-gray-700');
                }, 2000);
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="dd-"]') && !e.target.closest('button[onclick^="toggleMemberDropdown"]')) {
                document.querySelectorAll('[id^="dd-"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // Auto-switch tab based on URL hash or default to 'projects'
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.replace('#', '');
    const validTabs = ['projects', 'members'];
    const initialTab = validTabs.includes(hash) ? hash : 'projects';

    // Set initial state
    switchTab(initialTab);

    // Update URL hash when tab changes
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.id.replace('tab-btn-', '');
            window.location.hash = tab;
        });
    });
});
    </script>
@endsection
