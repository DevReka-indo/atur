@php
    $kanbanCurrentStatus = $kanbanCurrentStatus ?? 'all';
    $kanbanCanContribute = $kanbanCanContribute ?? null;
    $kanbanShowProject = $kanbanShowProject ?? true;
@endphp

<div class="js-kanban-board absolute inset-0 overflow-x-auto overflow-y-hidden">
    <div class="flex h-full min-w-max gap-4 p-4">
        @foreach ($kanbanStatuses as $statusKey => $statusLabel)
            @if ($kanbanCurrentStatus === 'all' || $kanbanCurrentStatus === $statusKey)
                @include('tasks.partials.kanban._column', [
                    'statusKey' => $statusKey,
                    'statusLabel' => $statusLabel,
                    'columnTasks' => $kanbanTasks->get($statusKey, collect()),
                    'kanbanCanContribute' => $kanbanCanContribute,
                    'kanbanShowProject' => $kanbanShowProject,
                ])
            @endif
        @endforeach
    </div>

    <div class="js-kanban-toast fixed left-1/2 top-6 z-[9999] hidden min-w-60 -translate-x-1/2 items-center gap-2.5 rounded-xl bg-slate-800 px-5 py-3 text-sm font-medium text-white shadow-2xl"
        role="status" aria-live="polite">
        <span class="js-kanban-toast-icon"></span>
        <span class="js-kanban-toast-message"></span>
    </div>
</div>

@include('tasks.partials.kanban._scripts')
