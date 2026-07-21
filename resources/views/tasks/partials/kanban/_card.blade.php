@php
    $taskCanContribute = $kanbanCanContribute ?? (bool) $task->getAttribute('kanban_can_contribute');
    $isChildTask = $task->parent_task_id !== null && $task->parent !== null;
    $hierarchyDepth = $isChildTask && $task->parent->parent_task_id !== null ? 2 : ($isChildTask ? 1 : 0);
    $displayAssignees = $task->assignees->isNotEmpty()
        ? $task->assignees
        : collect([$task->assignee])->filter();
    $priorityColor = match ($task->priority ?? '') {
        'urgent' => 'text-red-500',
        'high' => 'text-orange-500',
        'medium' => 'text-amber-500',
        default => 'text-gray-400',
    };
    $statusColor = match ($task->status) {
        'to_do' => 'bg-amber-50 text-amber-700',
        'in_progress' => 'bg-blue-50 text-blue-700',
        'review' => 'bg-violet-50 text-violet-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'stopped' => 'bg-red-50 text-red-700',
        'cancelled' => 'bg-zinc-100 text-zinc-600',
        default => 'bg-gray-100 text-gray-600',
    };
    $isOverdue = $task->due_date?->isPast() && $task->status !== 'completed';
@endphp

<article
    class="kanban-card flex-shrink-0 select-none rounded-lg border border-gray-100 bg-white p-3 shadow-sm transition hover:shadow-md {{ $taskCanContribute ? 'cursor-grab active:cursor-grabbing' : 'cursor-default' }}"
    draggable="{{ $taskCanContribute ? 'true' : 'false' }}"
    data-task-token="{{ $task->token }}"
    data-status-url="{{ route('tasks.updateStatus', $task->token) }}"
    data-status="{{ $task->status }}"
    aria-label="{{ $task->name }}">
    <div class="flex items-start justify-between gap-2">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-medium capitalize {{ $priorityColor }}">
                <i class="fa-solid fa-flag text-[10px]"></i>
                {{ $task->priority ?? 'none' }}
            </span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusColor }}">
                {{ str($task->status)->replace('_', ' ')->title() }}
            </span>
        </div>
        @if ($taskCanContribute)
            <span class="kanban-drag-handle text-gray-300" title="Drag to move" aria-hidden="true">
                <i class="fa-solid fa-grip-vertical text-xs"></i>
            </span>
        @endif
    </div>

    @if ($isChildTask)
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">
                {{ $hierarchyDepth === 2 ? 'Sub-subtask' : 'Subtask' }}
            </span>
            <span class="text-[11px] font-medium text-indigo-600">
                Bobot parent: {{ number_format((float) $task->subtask_weight_percentage, 2) }}%
            </span>
        </div>
        <div class="mt-1.5 truncate text-xs text-gray-500">
            @if ($hierarchyDepth === 2 && $task->parent->parent)
                <a href="{{ route('tasks.show', $task->parent->parent->token) }}"
                    class="hover:text-indigo-600 hover:underline" draggable="false">
                    {{ $task->parent->parent->name }}
                </a>
                <span class="text-gray-300">→</span>
            @endif
            <span>Parent:</span>
            <a href="{{ route('tasks.show', $task->parent->token) }}"
                class="font-medium text-indigo-600 hover:underline" draggable="false">
                {{ $task->parent->name }}
            </a>
        </div>
    @endif

    <a href="{{ route('tasks.show', $task->token) }}"
        class="mt-2 block text-sm font-semibold {{ $task->status === 'completed' ? 'text-gray-400 line-through' : 'text-gray-800' }} hover:text-indigo-600"
        draggable="false">
        {{ $task->name }}
    </a>

    @if ($kanbanShowProject && $task->project)
        <p class="mt-1 truncate text-xs text-gray-400">{{ $task->project->name }}</p>
    @endif

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
        @if ($task->due_date)
            <span class="{{ $isOverdue ? 'font-medium text-red-500' : '' }}">
                <i class="fa-regular fa-calendar"></i>
                {{ $task->due_date->format('d M Y') }}
            </span>
        @endif
    </div>

    <footer class="mt-3 flex items-center justify-between gap-2 border-t border-gray-100 pt-2">
        @if ($displayAssignees->isNotEmpty())
            <div class="flex min-w-0 items-center gap-0.5">
                @foreach ($displayAssignees->take(3) as $assignee)
                    <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600"
                        title="{{ $assignee->name }}">
                        {{ str($assignee->name)->substr(0, 1)->upper() }}
                    </span>
                @endforeach
                @if ($displayAssignees->count() > 3)
                    <span class="text-xs text-gray-400">+{{ $displayAssignees->count() - 3 }}</span>
                @endif
            </div>
        @else
            <span class="text-xs text-gray-300">Unassigned</span>
        @endif

        <div class="flex items-center gap-1">
            <a href="{{ route('tasks.show', $task->token) }}"
                class="flex h-6 w-6 items-center justify-center rounded text-blue-400 transition-colors hover:bg-blue-50"
                draggable="false" aria-label="Lihat {{ $task->name }}">
                <i class="fa-regular fa-eye text-xs"></i>
            </a>
            @if ($taskCanContribute)
                <a href="{{ route('tasks.edit', $task->token) }}"
                    class="flex h-6 w-6 items-center justify-center rounded text-amber-400 transition-colors hover:bg-amber-50"
                    draggable="false" aria-label="Edit {{ $task->name }}">
                    <i class="fa-solid fa-pen text-xs"></i>
                </a>
            @endif
        </div>
    </footer>
</article>
