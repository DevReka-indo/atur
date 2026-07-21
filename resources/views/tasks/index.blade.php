@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
    <div class="flex h-[calc(100vh-121px)] flex-col overflow-hidden">
        <div
            class="fixed inset-0 -z-10 bg-gradient-to-br
                from-gray-50 to-gray-100/50"
        ></div>

        @include('tasks.partials.index._header')

        @include('tasks.partials.index._toolbar', [
            'currentStatus' => $currentStatus,
            'currentView' => $view,
        ])

        <div
            class="relative min-h-0 flex-1 overflow-hidden rounded-xl
                border border-gray-200 bg-white shadow-sm"
        >
            @if ($view === 'list')
                @include('tasks.partials.index._task-tree', [
                    'taskTree' => $taskTree,
                    'currentStatus' => $currentStatus,
                    'statusOptions' => $statusOptions,
                ])
            @elseif ($view === 'gantt')
                @include('tasks.partials.gantt._container', [
                    'ganttContainerId' => 'personal_gantt',
                    'ganttEmptyStateId' => 'personal_gantt_empty',
                    'ganttDataUrl' => route('gant.data', [
                        'status' => $currentStatus,
                    ]),
                    'ganttUseFixedSummaryDates' => true,
                ])
            @elseif ($view === 'kanban')
                @include('tasks.partials.kanban._board', [
                    'kanbanStatuses' => $kanbanStatuses,
                    'kanbanTasks' => $kanbanTasks,
                    'kanbanCurrentStatus' => $currentStatus,
                    'kanbanCanContribute' => null,
                    'kanbanShowProject' => true,
                ])
            @endif
        </div>
    </div>

    @include('tasks.partials.index._status-dropdown')

    @include('tasks.partials.index._info-modal')

    @include('tasks.partials.index._scripts')
@endsection
