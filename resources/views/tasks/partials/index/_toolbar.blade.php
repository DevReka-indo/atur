@php
    $statuses = [
        'all' => 'All',
        'to_do' => 'To Do',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'completed' => 'Completed',
        'stopped' => 'Stopped',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="mb-2 flex flex-shrink-0 items-center justify-between gap-2">
    @include('tasks.partials.index._status-filter', [
        'statuses' => $statuses,
        'currentStatus' => $currentStatus,
        'currentView' => $currentView,
    ])

    <div class="flex items-center gap-3">
        @include('tasks.partials.index._view-switcher', [
            'currentStatus' => $currentStatus,
            'currentView' => $currentView,
        ])

        <a
            href="{{ route('tasks.create') }}"
            class="group inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 font-medium
                text-white shadow-lg shadow-indigo-500/30 transition-all duration-300 hover:bg-indigo-700"
        >
            <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
            Create Task
        </a>
    </div>
</div>
