@php($taskItem = $taskItem ?? null)
@php($formParentId = $parentId ?? $taskItem?->parent_id)
<form method="POST" action="{{ $taskItem ? route('project-templates.tasks.update', [$template, $taskItem]) : route('project-templates.tasks.store', $template) }}" class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
    @csrf
    @if($taskItem)@method('PUT')@endif
    <input type="hidden" name="parent_id" value="{{ $formParentId }}">
    <div><label class="text-xs font-semibold text-slate-600">Nama</label><input name="name" required maxlength="500" value="{{ old('name', $taskItem?->name) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
    <div><label class="text-xs font-semibold text-slate-600">Priority</label><select name="priority" class="mt-1 w-full rounded-lg border-slate-300">@foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', $taskItem?->priority ?? 'medium') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600">Deskripsi</label><textarea name="description" rows="2" class="mt-1 w-full rounded-lg border-slate-300">{{ old('description', $taskItem?->description) }}</textarea></div>
    <div><label class="text-xs font-semibold text-slate-600">Leaf Weight</label><input name="weight" type="number" min="0.01" step="0.01" value="{{ old('weight', $taskItem?->weight ?? 1) }}" class="mt-1 w-full rounded-lg border-slate-300" {{ $taskItem?->children->isNotEmpty() ? 'disabled' : 'required' }}><p class="mt-1 text-xs leading-relaxed text-slate-500">Semakin besar weight, semakin besar beban dan kontribusi relatif task terhadap progress project.</p></div>
    <div><label class="text-xs font-semibold text-slate-600">Position</label><input name="position" type="number" min="0" value="{{ old('position', $taskItem?->position ?? 0) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
    <div><label class="text-xs font-semibold text-slate-600">Start Offset (hari)</label><input name="start_offset_days" type="number" min="0" required value="{{ old('start_offset_days', $taskItem?->start_offset_days ?? 0) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
    <div><label class="text-xs font-semibold text-slate-600">Duration (hari)</label><input name="duration_days" type="number" min="1" required value="{{ old('duration_days', $taskItem?->duration_days ?? 1) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
    <div class="md:col-span-2"><button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700"><i class="fa-solid fa-floppy-disk mr-2"></i>{{ $taskItem ? 'Simpan Task' : 'Tambah Task' }}</button></div>
</form>
