@php($children = $tasksByParent->get($taskItem->id, collect()))
@php($siblings = $tasksByParent->get((int) ($taskItem->parent_id ?? 0), collect())->values())
@php($siblingIndex = $siblings->search(fn ($sibling) => $sibling->is($taskItem)))
<article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-700">Level {{ $depth + 1 }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($taskItem->priority) }}</span>
                @if($children->isNotEmpty())<span class="rounded-full bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-700">Summary</span>@else<span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Leaf · {{ number_format((float) $taskItem->weight, 2) }}</span>@endif
            </div>
            <h3 class="mt-2 font-bold text-slate-900">{{ $taskItem->name }}</h3>
            <p class="mt-1 text-sm text-slate-500">Offset {{ $taskItem->start_offset_days }} hari · Durasi {{ $taskItem->duration_days }} hari · Posisi {{ $taskItem->position }}</p>
            @if($taskItem->dependency)<p class="mt-1 text-xs font-medium text-indigo-600"><i class="fa-solid fa-link mr-1"></i>{{ $taskItem->dependency->dependency_type }} dari {{ $taskItem->dependency->predecessor?->name }} · lag {{ $taskItem->dependency->lag_days }} hari</p>@endif
        </div>
        @can('project-templates.update')
            <div class="flex items-center gap-1">
                @foreach([['offset' => -1, 'icon' => 'fa-arrow-up', 'label' => 'Naik'], ['offset' => 1, 'icon' => 'fa-arrow-down', 'label' => 'Turun']] as $move)
                    @php($targetIndex = $siblingIndex + $move['offset'])
                    @if($siblingIndex !== false && $targetIndex >= 0 && $targetIndex < $siblings->count())
                        @php($orderedIds = $siblings->pluck('id')->all())
                        @php([$orderedIds[$siblingIndex], $orderedIds[$targetIndex]] = [$orderedIds[$targetIndex], $orderedIds[$siblingIndex]])
                        <form method="POST" action="{{ route('project-templates.tasks.reorder', $template) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="parent_id" value="{{ $taskItem->parent_id }}">
                            @foreach($orderedIds as $orderedId)<input type="hidden" name="task_ids[]" value="{{ $orderedId }}">@endforeach
                            <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800" title="{{ $move['label'] }}"><i class="fa-solid {{ $move['icon'] }}"></i></button>
                        </form>
                    @endif
                @endforeach
                <form method="POST" action="{{ route('project-templates.tasks.destroy', [$template, $taskItem]) }}" data-confirm="Task dan seluruh turunannya akan dihapus. Lanjutkan?">@csrf @method('DELETE')<button class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Hapus task"><i class="fa-solid fa-trash"></i></button></form>
            </div>
        @endcan
    </div>

    @can('project-templates.update')
        <details class="mt-4"><summary class="cursor-pointer text-sm font-semibold text-sky-700">Edit task dan dependency</summary><div class="mt-3">@include('project-templates.partials.tasks._form', ['taskItem' => $taskItem])@include('project-templates.partials.tasks._dependency-fields')</div></details>
        @if($depth < 2)
            <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold text-slate-700"><i class="fa-solid fa-plus mr-1"></i>Tambah child</summary><div class="mt-3">@include('project-templates.partials.tasks._form', ['taskItem' => null, 'parentId' => $taskItem->id])</div></details>
        @endif
    @endcan

    @if($children->isNotEmpty())
        <div class="mt-4 space-y-3 border-l-2 border-sky-200 pl-4">
            @foreach($children as $child)
                @include('project-templates.partials.tasks._item', ['taskItem' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</article>
