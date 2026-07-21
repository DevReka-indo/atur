<article class="rounded-xl border border-gray-200 bg-gray-50/60 p-4" data-hierarchy-depth="{{ $hierarchyTask->hierarchy_depth }}">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.show', $hierarchyTask->token) }}"
                    class="truncate font-semibold text-gray-900 hover:text-indigo-700">
                    {{ $hierarchyTask->name }}
                </a>
                @if ((int) $hierarchyTask->hierarchy_depth > 0)
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                        {{ (int) $hierarchyTask->hierarchy_depth === 1 ? 'Subtask' : 'Sub-subtask' }}
                    </span>
                @endif
                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-600">
                    {{ str($hierarchyTask->status)->replace('_', ' ')->title() }}
                </span>
            </div>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                <span>Progress {{ number_format((float) $hierarchyTask->hierarchy_progress_percentage, 2) }}%</span>
                @if ((int) $hierarchyTask->hierarchy_depth > 0)
                    <span>Bobot parent {{ number_format((float) $hierarchyTask->subtask_weight_percentage, 2) }}%</span>
                @elseif ($hierarchyTask->subtasks_count > 0)
                    <span>Bobot child {{ number_format((float) $hierarchyTask->subtask_weight_total, 2) }}%</span>
                @else
                    <span>Bobot project {{ number_format((float) $hierarchyTask->weight, 2) }}</span>
                @endif
                <span>{{ $hierarchyTask->start_date?->format('d M Y') ?? '-' }} → {{ $hierarchyTask->due_date?->format('d M Y') ?? '-' }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tasks.show', $hierarchyTask->token) }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                Lihat
            </a>
            @if ($canContribute)
                <a href="{{ route('tasks.edit', $hierarchyTask->token) }}"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    Edit
                </a>
            @endif
        </div>
    </div>

    @if ($hierarchyTask->subtasks->isNotEmpty() && (int) $hierarchyTask->hierarchy_depth < 2)
        <div class="mt-3 grid gap-3 border-l-2 border-indigo-200 pl-4">
            @foreach ($hierarchyTask->subtasks as $childTask)
                @include('projects.partials._task-hierarchy-item', [
                    'hierarchyTask' => $childTask,
                    'canContribute' => $canContribute,
                ])
            @endforeach
        </div>
    @endif
</article>
