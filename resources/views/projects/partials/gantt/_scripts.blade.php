@include('tasks.partials.gantt._scripts', [
    'ganttContainerId' => 'project_gantt',
    'ganttEmptyStateId' => 'project_gantt_empty',
    'ganttPayload' => $ganttPayload,
    'ganttDataUrl' => null,
    'ganttUseFixedSummaryDates' => false,
])
