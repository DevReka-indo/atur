@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-gray-50 to-gray-100/50"></div>

    <div class="mx-auto max-w-7xl px-4 pb-10 pt-3 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('dashboard') }}"
                class="transition-colors hover:text-indigo-600">
                Home
            </a>

            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

            <a href="{{ route('projects.index') }}"
                class="transition-colors hover:text-indigo-600">
                Projects
            </a>

            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

            <span class="font-medium text-gray-700">
                Create
            </span>
        </nav>

        {{-- Page Header --}}
        <div class="mb-6">
            <h1
                class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-3xl font-bold text-transparent">
                Create Project
            </h1>

            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-600">
                Lengkapi informasi project, tentukan template pekerjaan, dan atur timeline pelaksanaannya.
            </p>
        </div>

        {{-- Form Card --}}
        <div
            class="overflow-hidden rounded-2xl border border-gray-200/70 bg-white/95 shadow-xl backdrop-blur-sm">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-[#219ebc]"></div>

            <form method="POST" action="{{ route('projects.store') }}">
                @csrf

                <div class="space-y-8 p-6 sm:p-8">

                    {{-- Validation Summary --}}
                    @if ($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-red-800">
                                        Beberapa data belum valid
                                    </p>

                                    <ul class="mt-1 list-inside list-disc space-y-1 text-sm text-red-700">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Section 1: Project Information --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                <i class="fa-solid fa-diagram-project"></i>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-gray-900">
                                    Project Information
                                </h2>

                                <p class="mt-0.5 text-sm text-gray-500">
                                    Tentukan workspace dan informasi utama project.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                            {{-- Workspace --}}
                            <div>
                                <label for="workspace_id"
                                    class="mb-2 block text-sm font-semibold text-gray-800">
                                    Workspace
                                    <span class="text-red-500">*</span>
                                </label>

                                @if (request('workspace_id') && $workspaces->firstWhere('id', request('workspace_id')))
                                    @php
                                        $lockedWorkspace = $workspaces->firstWhere(
                                            'id',
                                            request('workspace_id'),
                                        );
                                    @endphp

                                    <input type="hidden"
                                        name="workspace_id"
                                        value="{{ $lockedWorkspace->id }}">

                                    <div
                                        class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-gray-700">

                                        <div
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white text-sky-700 shadow-sm">
                                            <i class="fa-solid fa-layer-group text-sm"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold">
                                                {{ $lockedWorkspace->name }}
                                            </p>

                                            <p class="text-xs text-gray-500">
                                                Workspace dipilih dari halaman sebelumnya
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <select id="workspace_id"
                                        name="workspace_id"
                                        required
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3
                                            transition-all duration-200
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('workspace_id') border-red-400 bg-red-50/50 @enderror">

                                        <option value="">
                                            Select workspace
                                        </option>

                                        @foreach ($workspaces as $workspace)
                                            <option value="{{ $workspace->id }}"
                                                {{ (string) old('workspace_id') === (string) $workspace->id
                                                    ? 'selected'
                                                    : '' }}>
                                                {{ $workspace->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('workspace_id')
                                        <div
                                            class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                @endif
                            </div>

                            {{-- Project Name --}}
                            <div>
                                <label for="name"
                                    class="mb-2 block text-sm font-semibold text-gray-800">
                                    Project Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="e.g. Website Redesign"
                                    required
                                    maxlength="255"
                                    autofocus
                                    class="w-full rounded-xl border border-gray-300 px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('name') border-red-400 bg-red-50/50 @enderror">

                                @error('name')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div class="lg:col-span-2">
                                <label for="status"
                                    class="mb-2 block text-sm font-semibold text-gray-800">
                                    Initial Status
                                    <span class="text-red-500">*</span>
                                </label>

                                <select id="status"
                                    name="status"
                                    required
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('status') border-red-400 bg-red-50/50 @enderror">

                                    @foreach ([
                                        'planning' => 'Planning',
                                        'active' => 'Active',
                                        'on_hold' => 'On Hold',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        'urgent' => 'Urgent',
                                    ] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('status', 'planning') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs leading-relaxed text-gray-500">
                                    Gunakan status Planning untuk project yang masih berada pada tahap persiapan.
                                </p>

                                @error('status')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-gray-100"></div>

                    {{-- Section 2: Project Template --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                                <i class="fa-solid fa-table-cells-large"></i>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-gray-900">
                                    Project Template
                                </h2>

                                <p class="mt-0.5 text-sm text-gray-500">
                                    Gunakan template untuk membuat struktur task secara otomatis.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-5">

                            {{-- Template Selector --}}
                            <div class="max-w-2xl">
                                @include('projects.partials._template-selector')
                            </div>

                            {{-- Template Preview --}}
                            <div>
                                @include('projects.partials._template-preview')
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-gray-100"></div>

                    {{-- Section 3: Timeline --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-gray-900">
                                    Project Timeline
                                </h2>

                                <p class="mt-0.5 text-sm text-gray-500">
                                    Tentukan rentang waktu awal project. Timeline dapat diperpanjang mengikuti template.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                            {{-- Start Date --}}
                            <div>
                                <label for="start_date"
                                    class="mb-2 block text-sm font-semibold text-gray-800">
                                    Start Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                        <i class="fa-regular fa-calendar"></i>
                                    </div>

                                    <input type="date"
                                        id="start_date"
                                        name="start_date"
                                        value="{{ old('start_date') }}"
                                        required
                                        class="w-full rounded-xl border border-gray-300 py-3 pl-11 pr-4
                                            transition-all duration-200
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('start_date') border-red-400 bg-red-50/50 @enderror">
                                </div>

                                <p class="mt-2 text-xs text-gray-500">
                                    Tanggal dimulainya project.
                                </p>

                                @error('start_date')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Due Date --}}
                            <div>
                                <label for="due_date"
                                    class="mb-2 block text-sm font-semibold text-gray-800">
                                    Due Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                        <i class="fa-regular fa-calendar-check"></i>
                                    </div>

                                    <input type="date"
                                        id="due_date"
                                        name="due_date"
                                        value="{{ old('due_date') }}"
                                        min="{{ old('start_date') }}"
                                        required
                                        class="w-full rounded-xl border border-gray-300 py-3 pl-11 pr-4
                                            transition-all duration-200
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('due_date') border-red-400 bg-red-50/50 @enderror">
                                </div>

                                <p class="mt-2 text-xs text-gray-500">
                                    Target awal penyelesaian project.
                                </p>

                                @error('due_date')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            <i class="fa-solid fa-circle-info mt-0.5 flex-shrink-0"></i>

                            <p class="leading-relaxed">
                                Jika task terakhir dari template melewati Due Date, sistem akan memperpanjang
                                timeline project secara otomatis.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-100"></div>

                    {{-- Section 4: Description --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <i class="fa-solid fa-align-left"></i>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-gray-900">
                                    Project Description
                                </h2>

                                <p class="mt-0.5 text-sm text-gray-500">
                                    Tambahkan ruang lingkup, tujuan, atau informasi penting project.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="description"
                                class="mb-2 block text-sm font-semibold text-gray-800">
                                Description
                                <span class="text-xs font-normal text-gray-400">
                                    (optional)
                                </span>
                            </label>

                            <textarea id="description"
                                name="description"
                                rows="5"
                                placeholder="Describe the project objectives, scope, and expected results..."
                                class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3
                                    transition-all duration-200
                                    focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                    @error('description') border-red-400 bg-red-50/50 @enderror">{{ old('description') }}</textarea>

                            @error('description')
                                <div
                                    class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </section>
                </div>

                {{-- Sticky Footer Actions --}}
                <div
                    class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">

                    <a href="{{ route('projects.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300
                            bg-white px-6 py-3 text-sm font-medium text-gray-700
                            transition-all duration-200 hover:bg-gray-100">

                        <i class="fa-solid fa-arrow-left mr-2"></i>

                        Cancel
                    </a>

                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-7 py-3
                            text-sm font-semibold text-white shadow-lg shadow-blue-500/30
                            transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700">

                        <i class="fa-solid fa-check mr-2"></i>

                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
