@php
    $ganttMinHeight = $ganttMinHeight ?? '540px';
@endphp

<div class="flex w-full flex-col" data-gantt-section>
    @include('tasks.partials.gantt._legend')

    <div
        class="relative flex-1"
        style="min-height: {{ $ganttMinHeight }};"
    >
        <div
            id="{{ $ganttContainerId }}"
            class="h-full w-full"
            style="min-height: {{ $ganttMinHeight }};"
        ></div>

        @include('tasks.partials.gantt._empty-state', [
            'ganttEmptyStateId' => $ganttEmptyStateId,
        ])
    </div>
</div>

@include('tasks.partials.gantt._scripts', [
    'ganttContainerId' => $ganttContainerId,
    'ganttEmptyStateId' => $ganttEmptyStateId,
    'ganttPayload' => $ganttPayload ?? null,
    'ganttDataUrl' => $ganttDataUrl ?? null,
    'ganttUseFixedSummaryDates' => $ganttUseFixedSummaryDates ?? false,
])
