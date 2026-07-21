<div
    id="project-tab-tasks"
    class="project-tab-content relative
        {{ $currentTab !== 'tasks' ? 'hidden' : '' }}"
>
    @if ($currentView === 'list')
        @include('projects.partials._task-hierarchy', [
            'project' => $project,
            'taskHierarchyRoots' => $taskHierarchyRoots,
            'canContribute' => $canContribute,
        ])
    @elseif ($currentView === 'gantt')
        <div class="relative min-h-[600px] overflow-hidden">
            @include('projects.partials.gantt._container', [
                'ganttPayload' => $ganttPayload,
            ])
        </div>
    @elseif ($currentView === 'kanban')
        <div class="relative h-[calc(100vh-360px)] min-h-[520px] overflow-hidden">
            @include('tasks.partials.kanban._board', [
                'kanbanStatuses' => $kanbanStatuses,
                'kanbanTasks' => $kanbanTasks,
                'kanbanCurrentStatus' => 'all',
                'kanbanCanContribute' => $canContribute,
                'kanbanShowProject' => true,
            ])
        </div>
    @endif
</div>
