@php
    $subtaskAssignees = $subtask->assignees->isNotEmpty()
        ? $subtask->assignees
        : collect([$subtask->assignee])->filter();
@endphp

<article class="rounded-xl border border-gray-200 bg-gray-50/70 p-4" data-hierarchy-depth="{{ $subtask->hierarchy_depth }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.show', $subtask->token) }}"
                    class="truncate font-semibold text-gray-900 hover:text-indigo-700">
                    {{ $subtask->name }}
                </a>
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                    {{ (int) $subtask->hierarchy_depth === 1 ? 'Subtask' : 'Sub-subtask' }}
                </span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-600">
                    {{ str($subtask->status)->replace('_', ' ')->title() }}
                </span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-600">
                    {{ ucfirst($subtask->priority) }}
                </span>
            </div>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                <span>Bobot {{ number_format((float) $subtask->subtask_weight_percentage, 2) }}%</span>
                <span>Progress {{ number_format((float) $subtask->hierarchy_progress_percentage, 2) }}%</span>
                <span>{{ $subtask->start_date?->format('d M Y') ?? '-' }} → {{ $subtask->due_date?->format('d M Y') ?? '-' }}</span>
                <span>PIC: {{ $subtaskAssignees->pluck('name')->join(', ') ?: 'Unassigned' }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tasks.show', $subtask->token) }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                Lihat
            </a>
            @if ($canContribute)
                <a href="{{ route('tasks.edit', $subtask->token) }}"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    Edit
                </a>
            @endif
        </div>
    </div>

    @if ($subtask->subtasks->isNotEmpty() && (int) $subtask->hierarchy_depth < 2)
        <div class="mt-3 grid gap-3 border-l-2 border-indigo-200 pl-4">
            @foreach ($subtask->subtasks as $nestedSubtask)
                @include('tasks.partials._subtask-item', [
                    'subtask' => $nestedSubtask,
                    'canContribute' => $canContribute,
                ])
            @endforeach
        </div>
    @endif
</article>
