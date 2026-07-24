@php
    $restoredSummary = $restoredTemplate['summary'] ?? null;
@endphp

<div id="use-template-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto p-4 sm:p-6"
    data-reopen="{{ $restoredTemplate ? 'true' : 'false' }}"
    @if ($restoredTemplate)
        data-restored-template-id="{{ $restoredTemplate['id'] }}"
        data-restored-template-name="{{ $restoredTemplate['name'] }}"
        data-restored-template-category="{{ $restoredTemplate['category'] }}"
        data-restored-template-tasks="{{ $restoredSummary['tasks_count'] }}"
        data-restored-template-levels="{{ $restoredSummary['hierarchy_levels'] }}"
        data-restored-template-weight="{{ number_format($restoredSummary['total_leaf_weight'], 2, '.', '') }}"
        data-restored-template-duration="{{ $restoredSummary['duration_days'] }}"
    @endif>

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"
        data-template-modal-backdrop>
    </div>

    {{-- Modal Dialog --}}
    <section
        class="relative z-10 my-auto w-full max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        role="dialog"
        aria-modal="true"
        aria-labelledby="use-template-modal-title"
        tabindex="-1"
        data-template-modal-dialog>

        {{-- Accent Bar --}}
        <div class="h-1.5 bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500"></div>

        {{-- Modal Header --}}
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-7 sm:py-5">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-sky-700">
                    Use Project Template
                </p>

                <h2 id="use-template-modal-title"
                    class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">
                    Create Project
                </h2>

                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span class="truncate font-medium text-slate-700"
                        data-selected-template-name>
                        {{ $restoredTemplate['name'] ?? 'Pilih template' }}
                    </span>

                    <span aria-hidden="true">·</span>

                    <span data-selected-template-category>
                        {{ $restoredTemplate['category'] ?? 'Kategori' }}
                    </span>
                </p>
            </div>

            <button type="button"
                data-template-modal-close
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Tutup modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST"
            action="{{ route('projects.store') }}"
            data-use-template-form>
            @csrf

            <input type="hidden"
                name="project_template_id"
                value="{{ old('project_template_id') }}"
                data-project-template-id>

            <div class="max-h-[calc(100vh-11rem)] overflow-y-auto">

                <div class="space-y-8 px-5 py-6 sm:px-7">

                    {{-- Validation Summary --}}
                    @if ($errors->any() && old('project_template_id'))
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
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
                            {{-- <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                <i class="fa-solid fa-diagram-project"></i>
                            </div> --}}

                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    Project Information
                                </h3>

                                <p class="mt-0.5 text-sm text-slate-500">
                                    Tentukan workspace, nama, dan status awal project.
                                </p>
                            </div>
                        </div>

                        @if ($workspaces->isEmpty())
                            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>

                                    <div>
                                        <p class="font-semibold">
                                            Belum ada workspace yang dapat digunakan
                                        </p>

                                        <p class="mt-1 leading-relaxed">
                                            Anda perlu memiliki akses pembuatan project pada workspace sebelum menggunakan template.
                                        </p>

                                        <a href="{{ route('workspaces.index') }}"
                                            class="mt-3 inline-flex items-center gap-2 font-semibold text-amber-900 underline underline-offset-2">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            Lihat workspace
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            {{-- Workspace --}}
                            <div>
                                <label for="gallery-workspace-id"
                                    class="mb-2 block text-sm font-semibold text-slate-800">
                                    Workspace
                                    <span class="text-red-500">*</span>
                                </label>

                                <select id="gallery-workspace-id"
                                    name="workspace_id"
                                    required
                                    @disabled($workspaces->isEmpty())
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm
                                        transition-all duration-200
                                        focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30
                                        @error('workspace_id') border-red-400 bg-red-50/50 @enderror">

                                    <option value="">
                                        Pilih workspace
                                    </option>

                                    @foreach ($workspaces as $workspace)
                                        <option value="{{ $workspace->id }}"
                                            data-gallery-workspace-id="{{ $workspace->id }}"
                                            @selected((int) old('workspace_id') === $workspace->id)>
                                            {{ $workspace->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('workspace_id')
                                    <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Project Name --}}
                            <div>
                                <label for="gallery-project-name"
                                    class="mb-2 block text-sm font-semibold text-slate-800">
                                    Project Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input id="gallery-project-name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    maxlength="255"
                                    placeholder="e.g. Website Redesign"
                                    data-project-name
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm
                                        transition-all duration-200
                                        focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30
                                        @error('name') border-red-400 bg-red-50/50 @enderror">

                                @error('name')
                                    <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div class="sm:col-span-2">
                                <label for="gallery-project-status"
                                    class="mb-2 block text-sm font-semibold text-slate-800">
                                    Initial Status
                                    <span class="text-red-500">*</span>
                                </label>

                                <select id="gallery-project-status"
                                    name="status"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm
                                        transition-all duration-200
                                        focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30
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
                                            @selected(old('status', 'planning') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs leading-relaxed text-slate-500">
                                    Gunakan status Planning jika project masih berada pada tahap persiapan.
                                </p>

                                @error('status')
                                    <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    {{-- Section 2: Template Summary --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            {{-- <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                                <i class="fa-solid fa-table-cells-large"></i>
                            </div> --}}

                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    Template Summary
                                </h3>

                                <p class="mt-0.5 text-sm text-slate-500">
                                    Ringkasan struktur pekerjaan yang akan diterapkan pada project.
                                </p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-500">
                                    Task
                                </dt>

                                <dd class="mt-1 text-lg font-bold text-slate-900"
                                    data-template-summary-tasks>
                                    {{ $restoredSummary['tasks_count'] ?? '—' }}
                                </dd>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-500">
                                    Level
                                </dt>

                                <dd class="mt-1 text-lg font-bold text-slate-900"
                                    data-template-summary-levels>
                                    {{ $restoredSummary['hierarchy_levels'] ?? '—' }}
                                </dd>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-500">
                                    Total Beban
                                </dt>

                                <dd class="mt-1 text-lg font-bold text-slate-900"
                                    data-template-summary-weight>
                                    {{ $restoredSummary
                                        ? number_format($restoredSummary['total_leaf_weight'], 2)
                                        : '—' }}
                                </dd>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-500">
                                    Durasi Kalender
                                </dt>

                                <dd class="mt-1 text-lg font-bold text-slate-900"
                                    data-template-summary-duration>
                                    {{ $restoredSummary
                                        ? $restoredSummary['duration_days'].' hari'
                                        : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    {{-- Section 3: Timeline --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            {{-- <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div> --}}

                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    Project Timeline
                                </h3>

                                <p class="mt-0.5 text-sm text-slate-500">
                                    Tentukan tanggal mulai dan target awal penyelesaian project.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            {{-- Start Date --}}
                            <div>
                                <label for="gallery-start-date"
                                    class="mb-2 block text-sm font-semibold text-slate-800">
                                    Start Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <i class="fa-regular fa-calendar"></i>
                                    </div>

                                    <input id="gallery-start-date"
                                        type="date"
                                        name="start_date"
                                        value="{{ old('start_date') }}"
                                        required
                                        data-project-start-date
                                        class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-sm
                                            transition-all duration-200
                                            focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30
                                            @error('start_date') border-red-400 bg-red-50/50 @enderror">
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    Tanggal dimulainya project.
                                </p>

                                @error('start_date')
                                    <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Due Date --}}
                            <div>
                                <label for="gallery-due-date"
                                    class="mb-2 block text-sm font-semibold text-slate-800">
                                    Due Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <i class="fa-regular fa-calendar-check"></i>
                                    </div>

                                    <input id="gallery-due-date"
                                        type="date"
                                        name="due_date"
                                        value="{{ old('due_date') }}"
                                        min="{{ old('start_date') }}"
                                        required
                                        data-project-due-date
                                        class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-sm
                                            transition-all duration-200
                                            focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30
                                            @error('due_date') border-red-400 bg-red-50/50 @enderror">
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    Target awal penyelesaian project.
                                </p>

                                @error('due_date')
                                    <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">

                            <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>

                            <p class="leading-relaxed">
                                Jika task terakhir template melewati Due Date, sistem akan memperpanjang timeline project secara otomatis.
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    {{-- Section 4: Description --}}
                    <section>
                        <div class="mb-5 flex items-start gap-3">
                            {{-- <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <i class="fa-solid fa-align-left"></i>
                            </div> --}}

                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    Project Description
                                </h3>

                                <p class="mt-0.5 text-sm text-slate-500">
                                    Tambahkan ruang lingkup, tujuan, atau informasi penting project.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="gallery-project-description"
                                class="mb-2 block text-sm font-semibold text-slate-800">
                                Description

                                <span class="text-xs font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <textarea id="gallery-project-description"
                                name="description"
                                rows="4"
                                placeholder="Describe the project objectives, scope, and expected results..."
                                class="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 text-sm
                                    transition-all duration-200
                                    focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30
                                    @error('description') border-red-400 bg-red-50/50 @enderror">{{ old('description') }}</textarea>

                            @error('description')
                                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </section>
                </div>
            </div>

            {{-- Footer Actions --}}
            <div
                class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4
                    sm:flex-row sm:items-center sm:justify-between sm:px-7">

                <button type="button"
                    data-template-modal-close
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5
                        text-sm font-semibold text-slate-700 transition hover:bg-slate-100">

                    <i class="fa-solid fa-xmark mr-2"></i>

                    Cancel
                </button>

                <button type="submit"
                    @disabled($workspaces->isEmpty())
                    class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-6 py-2.5
                        text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700
                        disabled:cursor-not-allowed disabled:bg-slate-300">

                    <i class="fa-solid fa-folder-plus mr-2"></i>

                    Create Project
                </button>
            </div>
        </form>
    </section>
</div>
