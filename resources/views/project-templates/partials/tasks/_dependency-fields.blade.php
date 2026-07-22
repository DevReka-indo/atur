@if($taskItem->children->isEmpty())
    <form method="POST" action="{{ route('project-templates.tasks.dependency.update', [$template, $taskItem]) }}" class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 md:grid-cols-[1fr_130px_110px_auto]">
        @csrf @method('PUT')
        <select name="predecessor_template_task_id" required class="rounded-lg border-slate-300 text-sm">
            <option value="">Pilih predecessor</option>
            @foreach($leafTasks->where('id', '!=', $taskItem->id) as $leafTask)<option value="{{ $leafTask->id }}" @selected($taskItem->dependency?->predecessor_template_task_id === $leafTask->id)>{{ $leafTask->name }}</option>@endforeach
        </select>
        <select name="dependency_type" class="rounded-lg border-slate-300 text-sm">@foreach(['FS','SS','FF','SF'] as $type)<option value="{{ $type }}" @selected(($taskItem->dependency?->dependency_type ?? 'FS') === $type)>{{ $type }}</option>@endforeach</select>
        <input name="lag_days" type="number" min="0" value="{{ $taskItem->dependency?->lag_days ?? 0 }}" class="rounded-lg border-slate-300 text-sm" aria-label="Lag days">
        <button class="rounded-lg bg-slate-700 px-3 py-2 text-xs font-semibold text-white">Simpan Dependency</button>
    </form>
    @if($taskItem->dependency)
        <form method="POST" action="{{ route('project-templates.tasks.dependency.destroy', [$template, $taskItem]) }}" class="mt-2">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600 hover:text-red-700"><i class="fa-solid fa-link-slash mr-1"></i>Hapus dependency</button></form>
    @endif
@endif
