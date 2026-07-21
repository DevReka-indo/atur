@php
    $columnColor = match ($statusKey) {
        'to_do' => 'border-amber-400',
        'in_progress' => 'border-blue-400',
        'review' => 'border-purple-400',
        'completed' => 'border-emerald-400',
        'stopped' => 'border-red-400',
        'cancelled' => 'border-zinc-400',
        default => 'border-gray-300',
    };
    $headerColor = match ($statusKey) {
        'to_do' => 'bg-amber-100 text-amber-800',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'review' => 'bg-violet-100 text-violet-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'stopped' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-zinc-200 text-zinc-700',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<section class="kanban-column flex h-full w-[280px] flex-shrink-0 flex-col rounded-xl border-t-4 bg-gray-100 {{ $columnColor }}"
    data-status="{{ $statusKey }}">
    <header class="flex flex-shrink-0 items-center justify-between rounded-t-xl px-4 py-3 {{ $headerColor }}">
        <span class="text-sm font-semibold">{{ $statusLabel }}</span>
        <span class="kanban-count rounded-full bg-white/70 px-2 py-0.5 text-xs font-bold">
            {{ $columnTasks->count() }}
        </span>
    </header>

    <div class="kanban-drop-zone flex flex-1 flex-col gap-3 overflow-y-auto p-3" data-status="{{ $statusKey }}">
        @foreach ($columnTasks as $task)
            @include('tasks.partials.kanban._card', [
                'task' => $task,
                'kanbanCanContribute' => $kanbanCanContribute,
                'kanbanShowProject' => $kanbanShowProject,
            ])
        @endforeach
        @include('tasks.partials.kanban._empty-state', [
            'hidden' => $columnTasks->isNotEmpty(),
        ])
    </div>
</section>
