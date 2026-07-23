<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
    <div class="h-1.5 bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500"></div>
    <div class="flex flex-1 flex-col gap-5 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                    <i class="fa-solid fa-layer-group text-[10px]"></i>
                    <span class="truncate">{{ $template->category->name }}</span>
                </span>
                <h2 class="mt-3 break-words text-lg font-bold text-slate-900">{{ $template->name }}</h2>
            </div>
            <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                v{{ $template->version }}
            </span>
        </div>

        <p class="line-clamp-3 min-h-[60px] text-sm leading-5 text-slate-600">
            {{ $template->description ?: 'Template ini belum memiliki deskripsi.' }}
        </p>

        <dl class="grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-slate-500">Task</dt>
                <dd class="mt-1 font-bold text-slate-900">{{ $summary['tasks_count'] }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-slate-500">Level</dt>
                <dd class="mt-1 font-bold text-slate-900">{{ $summary['hierarchy_levels'] }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-slate-500">Total Beban</dt>
                <dd class="mt-1 font-bold text-slate-900">{{ number_format($summary['total_leaf_weight'], 2) }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-slate-500">Durasi Kalender</dt>
                <dd class="mt-1 font-bold text-slate-900">{{ $summary['duration_days'] }} hari</dd>
            </div>
        </dl>

        <div class="mt-auto grid grid-cols-1 gap-2 sm:grid-cols-2">
            <a href="{{ route('template-gallery.show', $template) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                <i class="fa-solid fa-eye"></i>
                Lihat Detail
            </a>
            <a href="{{ route('projects.create', ['project_template_id' => $template->id]) }}"
                data-use-template
                data-template-id="{{ $template->id }}"
                data-template-name="{{ $template->name }}"
                data-template-category="{{ $template->category->name }}"
                data-template-tasks="{{ $summary['tasks_count'] }}"
                data-template-levels="{{ $summary['hierarchy_levels'] }}"
                data-template-weight="{{ number_format($summary['total_leaf_weight'], 2, '.', '') }}"
                data-template-duration="{{ $summary['duration_days'] }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                <i class="fa-solid fa-arrow-right"></i>
                Gunakan Template
            </a>
        </div>
    </div>
</article>
