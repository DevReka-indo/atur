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
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" data-template-modal-backdrop></div>

    <section class="relative z-10 my-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        role="dialog" aria-modal="true" aria-labelledby="use-template-modal-title" tabindex="-1"
        data-template-modal-dialog>
        <div class="h-1.5 bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500"></div>

        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-sky-700">Use Project Template</p>
                <h2 id="use-template-modal-title" class="mt-1 text-xl font-bold text-slate-900">Create Project</h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span data-selected-template-name>{{ $restoredTemplate['name'] ?? 'Pilih template' }}</span>
                    <span aria-hidden="true">·</span>
                    <span data-selected-template-category>{{ $restoredTemplate['category'] ?? 'Kategori' }}</span>
                </p>
            </div>
            <button type="button" data-template-modal-close
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Tutup modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('projects.store') }}" data-use-template-form>
            @csrf
            <input type="hidden" name="project_template_id" value="{{ old('project_template_id') }}"
                data-project-template-id>

            <div class="max-h-[calc(100vh-12rem)] space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <dl class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-50 p-3 text-xs sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-3">
                        <dt class="text-slate-500">Task</dt>
                        <dd class="mt-1 font-bold text-slate-900" data-template-summary-tasks>
                            {{ $restoredSummary['tasks_count'] ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-white p-3">
                        <dt class="text-slate-500">Level</dt>
                        <dd class="mt-1 font-bold text-slate-900" data-template-summary-levels>
                            {{ $restoredSummary['hierarchy_levels'] ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-white p-3">
                        <dt class="text-slate-500">Total Beban</dt>
                        <dd class="mt-1 font-bold text-slate-900" data-template-summary-weight>
                            {{ $restoredSummary ? number_format($restoredSummary['total_leaf_weight'], 2) : '—' }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-white p-3">
                        <dt class="text-slate-500">Durasi Kalender</dt>
                        <dd class="mt-1 font-bold text-slate-900" data-template-summary-duration>
                            {{ $restoredSummary ? $restoredSummary['duration_days'].' hari' : '—' }}
                        </dd>
                    </div>
                </dl>

                @if ($workspaces->isEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold">Belum ada workspace yang dapat digunakan.</p>
                        <p class="mt-1">Anda perlu menjadi owner atau admin workspace untuk membuat project.</p>
                        <a href="{{ route('workspaces.index') }}"
                            class="mt-3 inline-flex items-center gap-2 font-semibold text-amber-900 underline underline-offset-2">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            Lihat workspace
                        </a>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="gallery-workspace-id" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Workspace <span class="text-red-500">*</span>
                        </label>
                        <select id="gallery-workspace-id" name="workspace_id" required @disabled($workspaces->isEmpty())
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 @error('workspace_id') border-red-400 @enderror">
                            <option value="">Pilih workspace</option>
                            @foreach ($workspaces as $workspace)
                                <option value="{{ $workspace->id }}" data-gallery-workspace-id="{{ $workspace->id }}"
                                    @selected((int) old('workspace_id') === $workspace->id)>
                                    {{ $workspace->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('workspace_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="gallery-project-name" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Project Name <span class="text-red-500">*</span>
                        </label>
                        <input id="gallery-project-name" type="text" name="name" value="{{ old('name') }}" required
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 @error('name') border-red-400 @enderror"
                            placeholder="Masukkan nama project" data-project-name>
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="gallery-start-date" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input id="gallery-start-date" type="date" name="start_date" value="{{ old('start_date') }}"
                            required
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 @error('start_date') border-red-400 @enderror"
                            data-project-start-date>
                        @error('start_date')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="gallery-due-date" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input id="gallery-due-date" type="date" name="due_date" value="{{ old('due_date') }}"
                            min="{{ old('start_date') }}" required
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 @error('due_date') border-red-400 @enderror"
                            data-project-due-date>
                        @error('due_date')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="gallery-project-status" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="gallery-project-status" name="status" required
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 @error('status') border-red-400 @enderror">
                            @foreach (['planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'urgent' => 'Urgent'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'planning') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="gallery-project-description" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Description
                        </label>
                        <textarea id="gallery-project-description" name="description" rows="3"
                            class="w-full resize-none rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 @error('description') border-red-400 @enderror"
                            placeholder="Tambahkan deskripsi project">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button type="button" data-template-modal-close
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" @disabled($workspaces->isEmpty())
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                    <i class="fa-solid fa-folder-plus"></i>
                    Create Project
                </button>
            </div>
        </form>
    </section>
</div>
