<section aria-labelledby="project-template-title" data-project-template-selector>
    <div class="mb-5 flex items-start gap-3">
        {{-- <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
            <i class="fa-solid fa-table-cells-large"></i>
        </div> --}}
        <div>
            <h2 id="project-template-title" class="text-base font-semibold text-gray-900">Project Template</h2>
            <p class="mt-0.5 text-sm text-gray-500">Gunakan template untuk membuat struktur task secara otomatis.</p>
        </div>
    </div>

    <input type="hidden" name="project_template_id" id="project_template_id"
        value="{{ $selectedProjectTemplateId }}"
        @if ($selectedProjectTemplate) data-preview-url="{{ $selectedProjectTemplate['preview_url'] }}" @endif>

    <div data-template-empty-state @class(['hidden' => $selectedProjectTemplate !== null])>
        <div class="flex flex-col gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm">
                    <i class="fa-solid fa-list-check"></i>
                </span>
                <div>
                    <p class="font-semibold text-slate-900">Belum menggunakan template</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Project akan dibuat menggunakan enam task default yang dapat Anda sesuaikan.
                    </p>
                </div>
            </div>
            <button type="button" data-open-template-picker
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <i class="fa-solid fa-magnifying-glass"></i>
                Browse Templates
            </button>
        </div>
    </div>

    <div data-template-selected-state @class(['hidden' => $selectedProjectTemplate === null])>
        <div class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-sky-50 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-indigo-700 shadow-sm"
                            data-selected-template-category>
                            {{ $selectedProjectTemplate['category'] ?? '' }}
                        </span>
                        <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white"
                            data-selected-template-version>
                            v{{ $selectedProjectTemplate['version'] ?? '' }}
                        </span>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900" data-selected-template-name>
                        {{ $selectedProjectTemplate['name'] ?? '' }}
                    </h3>
                    <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600" data-selected-template-description>
                        {{ $selectedProjectTemplate['description'] ?? '' }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <button type="button" data-open-template-picker
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-white px-3.5 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                        <i class="fa-solid fa-rotate"></i>
                        Change Template
                    </button>
                    <button type="button" data-remove-template
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-3.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                        <i class="fa-solid fa-xmark"></i>
                        Remove
                    </button>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ([
                    'tasks' => ['Task', $selectedProjectTemplate['tasks_count'] ?? '—'],
                    'levels' => ['Level', $selectedProjectTemplate['hierarchy_levels'] ?? '—'],
                    'duration' => ['Durasi', isset($selectedProjectTemplate) ? $selectedProjectTemplate['duration_days'].' hari' : '—'],
                    'weight' => ['Total Beban', isset($selectedProjectTemplate) ? number_format($selectedProjectTemplate['total_leaf_weight'], 2) : '—'],
                ] as $key => [$label, $value])
                    <div class="rounded-xl border border-white/80 bg-white/80 p-3">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-bold text-slate-900" data-selected-template-summary="{{ $key }}">
                            {{ $value }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    @error('project_template_id')
        <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror
</section>
