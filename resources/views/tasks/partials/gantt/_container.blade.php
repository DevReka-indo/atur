<div class="absolute inset-0 flex flex-col" data-gantt-section>
    @include('tasks.partials.gantt._legend')

    <div class="relative min-h-0 flex-1">
        <div id="{{ $ganttContainerId }}" class="h-full w-full overflow-hidden"></div>
        @include('tasks.partials.gantt._empty-state', ['ganttEmptyStateId' => $ganttEmptyStateId])
    </div>
</div>

@include('tasks.partials.gantt._scripts', [
    'ganttContainerId' => $ganttContainerId,
    'ganttEmptyStateId' => $ganttEmptyStateId,
    'ganttPayload' => $ganttPayload ?? null,
    'ganttDataUrl' => $ganttDataUrl ?? null,
    'ganttUseFixedSummaryDates' => $ganttUseFixedSummaryDates ?? false,
])
