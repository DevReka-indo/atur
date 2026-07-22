@extends('layouts.app')

@section('title', 'Detail User')

@section('content')
    @php
        $roleStyles = [
            'super_admin' => [
                'label' => 'Super Admin',
                'class' => 'bg-red-100 text-red-700 border-red-200',
                'icon' => 'fa-solid fa-crown',
            ],
            'contributor' => [
                'label' => 'Contributor',
                'class' => 'bg-blue-100 text-blue-700 border-blue-200',
                'icon' => 'fa-solid fa-pen-ruler',
            ],
            'member' => [
                'label' => 'Member',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-solid fa-user',
            ],
        ];

        $roleStyle = $roleStyles[$roleName] ?? [
            'label' => str($roleName)->replace('_', ' ')->title()->toString(),
            'class' => 'bg-violet-100 text-violet-700 border-violet-200',
            'icon' => 'fa-solid fa-user-tag',
        ];
    @endphp

    <div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <a
                    href="{{ route('management-users.index') }}"
                    class="transition hover:text-blue-600"
                >
                    Management Users
                </a>

                <i class="fa-solid fa-chevron-right text-xs text-slate-300"></i>

                <span class="font-medium text-slate-700">
                    {{ $managementUser->name }}
                </span>
            </nav>

            {{-- Flash --}}
            @if (session('success'))
                <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Header --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="h-2 bg-gradient-to-r from-sky-500 via-blue-500 to-indigo-500"></div>

                <div class="flex flex-col gap-6 p-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-5">
                        @if ($managementUser->profile_photo)
                            <img
                                src="{{ asset('storage/' . $managementUser->profile_photo) }}"
                                alt="{{ $managementUser->name }}"
                                class="h-20 w-20 flex-shrink-0 rounded-2xl border border-slate-200 object-cover shadow-sm"
                            >
                        @else
                            <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-100 to-indigo-100 text-2xl font-bold text-indigo-700">
                                {{ strtoupper(substr($managementUser->name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="truncate text-2xl font-bold text-slate-900 sm:text-3xl">
                                    {{ $managementUser->name }}
                                </h1>

                                @if ($managementUser->is(Auth::user()))
                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        Akun Anda
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1 truncate text-sm text-slate-500">
                                {{ $managementUser->email }}
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold {{ $roleStyle['class'] }}">
                                    <i class="{{ $roleStyle['icon'] }}"></i>
                                    {{ $roleStyle['label'] }}
                                </span>

                                @if ($managementUser->is_active)
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        Tidak Aktif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @can('management-users.update')
                            <a
                                href="{{ route('management-users.edit', $managementUser) }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Edit User
                            </a>
                        @endcan

                        @can('management-users.toggle-status')
                            @if (! $managementUser->is(Auth::user()))
                                <form
                                    method="POST"
                                    action="{{ route('management-users.toggle-status', $managementUser) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        onclick="return confirm('{{ $managementUser->is_active ? 'Nonaktifkan user ini?' : 'Aktifkan user ini?' }}')"
                                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition
                                            {{ $managementUser->is_active
                                                ? 'border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100'
                                                : 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                    >
                                        <i class="fa-solid fa-power-off"></i>

                                        {{ $managementUser->is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan' }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </section>

            {{-- Summary cards --}}
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Workspace</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">
                                {{ $managementUser->workspaces->count() }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Project</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">
                                {{ $managementUser->projects->count() }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                            <i class="fa-solid fa-diagram-project"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Task Aktif</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">
                                {{ $activeTaskCount }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Task Selesai</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">
                                {{ $completedTaskCount }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-3">

                {{-- Left column --}}
                <div class="space-y-6 xl:col-span-2">

                    {{-- Profile --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                                <i class="fa-solid fa-address-card"></i>
                            </div>

                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Informasi User
                                </h2>
                                <p class="text-sm text-slate-500">
                                    Informasi identitas dan akun pengguna.
                                </p>
                            </div>
                        </div>

                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Nama
                                </dt>
                                <dd class="mt-1 font-semibold text-slate-800">
                                    {{ $managementUser->name }}
                                </dd>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Email
                                </dt>
                                <dd class="mt-1 break-all font-semibold text-slate-800">
                                    {{ $managementUser->email }}
                                </dd>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Job Title
                                </dt>
                                <dd class="mt-1 font-semibold text-slate-800">
                                    {{ $managementUser->job_title ?? '-' }}
                                </dd>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Department
                                </dt>
                                <dd class="mt-1 font-semibold text-slate-800">
                                    {{ $managementUser->department ?? '-' }}
                                </dd>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Dibuat
                                </dt>
                                <dd class="mt-1 font-semibold text-slate-800">
                                    {{ $managementUser->created_at?->format('d M Y H:i') ?? '-' }}
                                </dd>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Aktivitas Terakhir
                                </dt>
                                <dd class="mt-1 font-semibold text-slate-800">
                                    {{ $managementUser->last_activity?->diffForHumans() ?? 'Belum tersedia' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Workspaces --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Workspace
                                </h2>
                                <p class="text-sm text-slate-500">
                                    Workspace yang diikuti user dan role lokalnya.
                                </p>
                            </div>

                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ $managementUser->workspaces->count() }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($managementUser->workspaces as $workspace)
                                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('workspaces.show', $workspace->token) }}"
                                            class="font-semibold text-slate-800 transition hover:text-blue-600"
                                        >
                                            {{ $workspace->name }}
                                        </a>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Bergabung:
                                            {{ $workspace->pivot->joined_at
                                                ? \Carbon\Carbon::parse($workspace->pivot->joined_at)->format('d M Y')
                                                : '-' }}
                                        </p>
                                    </div>

                                    <span class="inline-flex w-fit rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                        {{ str($workspace->pivot->role)->replace('_', ' ')->title() }}
                                    </span>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 py-10 text-center">
                                    <i class="fa-solid fa-layer-group text-2xl text-slate-300"></i>
                                    <p class="mt-3 text-sm text-slate-500">
                                        User belum bergabung dengan workspace.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </section>

                    {{-- Projects --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Project
                                </h2>
                                <p class="text-sm text-slate-500">
                                    Project yang diikuti user dan role lokalnya.
                                </p>
                            </div>

                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ $managementUser->projects->count() }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($managementUser->projects as $project)
                                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('projects.show', $project->token) }}"
                                            class="font-semibold text-slate-800 transition hover:text-blue-600"
                                        >
                                            {{ $project->name }}
                                        </a>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Status:
                                            {{ str($project->status)->replace('_', ' ')->title() }}
                                        </p>
                                    </div>

                                    <span class="inline-flex w-fit rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        {{ str($project->pivot->role)->replace('_', ' ')->title() }}
                                    </span>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 py-10 text-center">
                                    <i class="fa-solid fa-diagram-project text-2xl text-slate-300"></i>
                                    <p class="mt-3 text-sm text-slate-500">
                                        User belum bergabung dengan project.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- Right column --}}
                <div class="space-y-6">

                    {{-- Permission --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Permission Efektif
                                </h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    Hak akses global berdasarkan role.
                                </p>
                            </div>

                            @can('roles.view')
                                @if ($managementUser->roles->first())
                                    <a
                                        href="{{ route('management-roles.edit', $managementUser->roles->first()) }}"
                                        title="Kelola permission role"
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-blue-100 hover:text-blue-700"
                                    >
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </a>
                                @endif
                            @endcan
                        </div>

                        @if ($managementUser->isSuperAdmin())
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-crown mt-0.5"></i>

                                    <div>
                                        <p class="font-semibold">
                                            Full Access
                                        </p>
                                        <p class="mt-1 text-red-600">
                                            Super Admin memiliki seluruh permission secara otomatis melalui sistem.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif ($permissionsByGroup->isEmpty())
                            <div class="rounded-xl border border-dashed border-slate-300 py-8 text-center">
                                <i class="fa-solid fa-shield text-2xl text-slate-300"></i>
                                <p class="mt-3 text-sm text-slate-500">
                                    Role ini belum memiliki permission global.
                                </p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($permissionsByGroup as $group => $permissions)
                                    <div>
                                        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">
                                            {{ $group }}
                                        </h3>

                                        <div class="space-y-2">
                                            @foreach ($permissions as $permission)
                                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                                    <p class="text-xs font-semibold text-slate-700">
                                                        {{ str($permission->name)
                                                            ->after('.')
                                                            ->replace('-', ' ')
                                                            ->title() }}
                                                    </p>

                                                    <p class="mt-0.5 break-all text-[10px] text-slate-400">
                                                        {{ $permission->name }}
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- Task summary --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="font-bold text-slate-900">
                            Ringkasan Task
                        </h2>

                        <div class="mt-4 space-y-3">
                            <div class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3">
                                <span class="text-sm text-amber-700">
                                    Task aktif
                                </span>
                                <span class="font-bold text-amber-800">
                                    {{ $activeTaskCount }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3">
                                <span class="text-sm text-emerald-700">
                                    Task selesai
                                </span>
                                <span class="font-bold text-emerald-800">
                                    {{ $completedTaskCount }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-sky-50 px-4 py-3">
                                <span class="text-sm text-sky-700">
                                    Task dibuat
                                </span>
                                <span class="font-bold text-sky-800">
                                    {{ $createdTaskCount }}
                                </span>
                            </div>
                        </div>
                    </section>

                    {{-- Recent activity --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="font-bold text-slate-900">
                            Aktivitas Terbaru
                        </h2>

                        <div class="mt-4 space-y-4">
                            @forelse ($recentActivities as $activity)
                                <div class="relative pl-6">
                                    <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full bg-blue-500"></span>

                                    @if (! $loop->last)
                                        <span class="absolute left-[4px] top-4 h-[calc(100%+8px)] w-px bg-slate-200"></span>
                                    @endif

                                    <p class="text-sm font-medium text-slate-700">
                                        {{ $activity->description }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $activity->created_at?->diffForHumans() }}
                                    </p>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 py-8 text-center">
                                    <i class="fa-solid fa-clock-rotate-left text-2xl text-slate-300"></i>
                                    <p class="mt-3 text-sm text-slate-500">
                                        Belum ada aktivitas user.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
