<div class="absolute inset-0 flex flex-col" data-gantt-section>
    @include('projects.partials.gantt._legend')

    <div class="relative min-h-0 flex-1">
        <div id="project_gantt" class="h-full w-full overflow-hidden"></div>
        @include('tasks.partials.gantt._empty-state', ['ganttEmptyStateId' => 'project_gantt_empty'])
    </div>
</div>

@include('projects.partials.gantt._scripts', ['ganttPayload' => $ganttPayload])
