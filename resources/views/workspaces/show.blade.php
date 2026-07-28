@extends('layouts.app')

@section('title', 'Workspace — '.$workspace->name)

@section('content')
    @php
        $user = Auth::user();
        $isOwner = $workspace->isOwner($user);
    @endphp

    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-gray-50 to-gray-100/50"></div>

    <div class="mx-auto max-w-8xl px-4 py-8 sm:px-6 lg:px-8" data-workspace-show>
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('workspaces.index') }}" class="transition-colors hover:text-indigo-600">Workspaces</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400" aria-hidden="true"></i>
            <span class="font-medium text-gray-700" aria-current="page">{{ $workspace->name }}</span>
        </nav>

        <header class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 flex-1 items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-2xl font-bold text-white shadow-lg shadow-indigo-500/30">
                        {{ str($workspace->name)->substr(0, 1)->upper() }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-2xl font-bold text-gray-900 sm:text-3xl">{{ $workspace->name }}</h1>
                        <p class="mt-2 leading-relaxed text-gray-600">
                            {{ $workspace->description ?: 'Tidak ada deskripsi untuk workspace ini.' }}
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-diagram-project w-5 text-center text-sm" aria-hidden="true"></i>
                                <span class="font-medium text-gray-700">{{ $workspace->projects_count }}</span>
                                Projects
                            </span>

                            @if ($isOwner)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                                    {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_OWNER) }}
                                </span>
                            @elseif ($currentRole === \App\Models\Workspace::ROLE_ADMIN)
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium text-gray-500">
                                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                    {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_ADMIN) }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium text-gray-500">
                                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                                    {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($isOwner)
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('workspaces.edit', $workspace->token) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                            <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                            Edit
                        </a>
                        <button type="button" onclick="openModal('delete-workspace-modal')"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700">
                            <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                            Delete
                        </button>
                    </div>
                @endif
            </div>
        </header>

        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @include('workspaces.partials.show._tabs')
            @include('workspaces.partials._management-actions')
        </div>

        @if ($activeTab === 'members')
            @include('workspaces.partials.show.members._index')
        @elseif ($activeTab === 'activity')
            @include('workspaces.partials.show.activity._index')
        @else
            @include('workspaces.partials.show._overview')
        @endif
    </div>

    @if ($isOwner)
        @include('workspaces.partials.show._delete-modal')
    @endif

    @if ($activeTab === 'members')
        @include('workspaces.partials.show.members._remove-modal')

        @if ($canManageMembers)
            @include('workspaces.partials.members._invite-modal', [
                'workspace' => $workspace,
            ])
        @endif
    @endif
@endsection
