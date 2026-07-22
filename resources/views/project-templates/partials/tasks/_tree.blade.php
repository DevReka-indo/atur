<section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
    <div class="mb-4 flex items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-slate-900">Hierarchy Task</h2><p class="text-sm text-slate-500">Maksimal tiga level. Perubahan struktur menonaktifkan template.</p></div></div>
    <div class="space-y-4">
        @forelse($rootTasks as $taskItem)
            @include('project-templates.partials.tasks._item', ['depth' => 0])
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400">Belum ada task template.</div>
        @endforelse
    </div>
    @can('project-templates.update')
        <details class="mt-5"><summary class="cursor-pointer font-semibold text-sky-700"><i class="fa-solid fa-plus mr-2"></i>Tambah root task</summary><div class="mt-3">@include('project-templates.partials.tasks._form', ['taskItem' => null, 'parentId' => null])</div></details>
    @endcan
</section>
