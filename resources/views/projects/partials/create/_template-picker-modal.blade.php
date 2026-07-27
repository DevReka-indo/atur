<div id="project-template-picker-modal"
    class="fixed inset-0 z-50 hidden"
    data-template-picker-modal
    aria-hidden="true">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" data-template-picker-backdrop></div>

    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
        <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="template-picker-title"
            aria-describedby="template-picker-description"
            tabindex="-1"
            data-template-picker-dialog>
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 id="template-picker-title" class="text-xl font-bold text-slate-900">Select Project Template</h2>
                    <p id="template-picker-description" class="mt-1 text-sm leading-6 text-slate-600">
                        Pilih struktur pekerjaan yang paling sesuai. Pilihan baru diterapkan setelah dikonfirmasi.
                    </p>
                </div>
                <button type="button"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    aria-label="Close template picker"
                    data-close-template-picker>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
                    <label class="relative block">
                        <span class="sr-only">Cari template</span>
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="search"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Cari nama, deskripsi, atau kategori..."
                            autocomplete="off"
                            data-template-search>
                    </label>

                    <label>
                        <span class="sr-only">Filter kategori</span>
                        <select class="w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            data-template-category-filter>
                            <option value="">Semua kategori</option>
                            @foreach ($projectTemplateCategories as $category)
                                <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="grid gap-4 md:grid-cols-2" data-template-options>
                    <button type="button"
                        class="group relative rounded-2xl border-2 border-slate-200 bg-white p-5 text-left transition hover:border-indigo-300 hover:shadow-md"
                        data-template-option
                        data-template-id=""
                        data-template-name="Tanpa Template"
                        data-template-description="Project akan dibuat menggunakan enam task default."
                        data-template-category="Default"
                        data-template-category-id=""
                        data-template-version=""
                        data-template-tasks="6"
                        data-template-levels="1"
                        data-template-weight=""
                        data-template-duration=""
                        data-template-preview-url=""
                        aria-pressed="false">
                        <span class="absolute right-4 top-4 hidden h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs text-white"
                            data-template-selected-indicator>
                            <i class="fa-solid fa-check"></i>
                        </span>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <i class="fa-solid fa-list-check"></i>
                        </span>
                        <span class="mt-4 block pr-9 text-base font-bold text-slate-900">Tanpa Template</span>
                        <span class="mt-1 block text-sm leading-6 text-slate-600">
                            Project akan dibuat menggunakan enam task default.
                        </span>
                        <span class="mt-4 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            Default
                        </span>
                    </button>

                    @foreach ($projectTemplates as $template)
                        <button type="button"
                            class="group relative rounded-2xl border-2 border-slate-200 bg-white p-5 text-left transition hover:border-indigo-300 hover:shadow-md"
                            data-template-option
                            data-template-id="{{ $template['id'] }}"
                            data-template-name="{{ $template['name'] }}"
                            data-template-description="{{ $template['description'] }}"
                            data-template-category="{{ $template['category'] }}"
                            data-template-category-id="{{ $template['category_id'] }}"
                            data-template-version="{{ $template['version'] }}"
                            data-template-tasks="{{ $template['tasks_count'] }}"
                            data-template-levels="{{ $template['hierarchy_levels'] }}"
                            data-template-weight="{{ $template['total_leaf_weight'] }}"
                            data-template-duration="{{ $template['duration_days'] }}"
                            data-template-preview-url="{{ $template['preview_url'] }}"
                            aria-pressed="false">
                            <span class="absolute right-4 top-4 hidden h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs text-white"
                                data-template-selected-indicator>
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <span class="flex items-center gap-2 pr-10">
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                    {{ $template['category'] }}
                                </span>
                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                    v{{ $template['version'] }}
                                </span>
                            </span>
                            <span class="mt-4 block pr-9 text-base font-bold text-slate-900">{{ $template['name'] }}</span>
                            <span class="mt-1 line-clamp-2 block min-h-12 text-sm leading-6 text-slate-600">
                                {{ $template['description'] ?: 'Tanpa deskripsi.' }}
                            </span>
                            <span class="mt-4 grid grid-cols-3 gap-2 text-center">
                                <span class="rounded-xl bg-slate-50 px-2 py-2">
                                    <span class="block text-xs text-slate-500">Task</span>
                                    <span class="mt-0.5 block text-sm font-bold text-slate-900">{{ $template['tasks_count'] }}</span>
                                </span>
                                <span class="rounded-xl bg-slate-50 px-2 py-2">
                                    <span class="block text-xs text-slate-500">Level</span>
                                    <span class="mt-0.5 block text-sm font-bold text-slate-900">{{ $template['hierarchy_levels'] }}</span>
                                </span>
                                <span class="rounded-xl bg-slate-50 px-2 py-2">
                                    <span class="block text-xs text-slate-500">Durasi</span>
                                    <span class="mt-0.5 block text-sm font-bold text-slate-900">{{ $template['duration_days'] }} hari</span>
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="hidden py-12 text-center" data-template-no-results>
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <p class="mt-3 font-semibold text-slate-900">Template tidak ditemukan</p>
                    <p class="mt-1 text-sm text-slate-500">Coba kata kunci atau kategori lain.</p>
                </div>
            </div>

            <footer class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    data-close-template-picker>
                    Cancel
                </button>
                <button type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                    data-confirm-template>
                    <i class="fa-solid fa-check"></i>
                    Use Selected Template
                </button>
            </footer>
        </div>
    </div>
</div>
