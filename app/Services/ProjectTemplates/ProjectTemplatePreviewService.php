<?php

namespace App\Services\ProjectTemplates;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Services\ProjectTemplateHierarchyService;
use App\Services\ProjectTemplateScheduleCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProjectTemplatePreviewService
{
    public function __construct(
        private ProjectTemplateHierarchyService $hierarchyService,
        private ProjectTemplateScheduleCalculator $scheduleCalculator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ProjectTemplate $template,
        ?string $projectStartDate = null,
        ?string $requestedDueDate = null,
    ): array {
        $template->loadMissing('category:id,name,is_active,deleted_at');
        $tasks = $this->hierarchyService->loadGraph($template);

        return $this->buildFromTasks($template, $tasks, $projectStartDate, $requestedDueDate);
    }

    /**
     * @param  Collection<int, ProjectTemplate>  $templates
     * @return Collection<int, array<string, int|float>>
     */
    public function summaries(Collection $templates): Collection
    {
        $templates->loadMissing([
            'category:id,name,is_active,deleted_at',
            'tasks.dependency.predecessor',
        ]);

        return $templates->mapWithKeys(function (ProjectTemplate $template): array {
            $preview = $this->buildFromTasks($template, $template->tasks);

            return [$template->id => $preview['summary']];
        });
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     * @return array<string, mixed>
     */
    private function buildFromTasks(
        ProjectTemplate $template,
        Collection $tasks,
        ?string $projectStartDate = null,
        ?string $requestedDueDate = null,
    ): array {
        $this->hierarchyService->validateGraph($template, $tasks);

        $calculationStartDate = CarbonImmutable::parse($projectStartDate ?? '2000-01-01')->startOfDay();
        $schedule = $this->scheduleCalculator->calculate($calculationStartDate, $tasks);
        $aggregateWeights = $this->hierarchyService->aggregateWeights($tasks);
        $tasksByParent = $tasks->groupBy(
            fn (ProjectTemplateTask $task): int => (int) ($task->parent_id ?? 0)
        );
        $parentIds = $tasks->pluck('parent_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $rootTasks = $tasksByParent->get(0, collect())->sortBy([['position', 'asc'], ['id', 'asc']])->values();
        $maximumDepth = $tasks->max(
            fn (ProjectTemplateTask $task): int => $this->hierarchyService->depth($task, $tasks)
        ) ?? 0;
        $estimatedEndDate = collect($schedule)->max('due_date');
        $durationDays = $estimatedEndDate instanceof CarbonImmutable
            ? (int) $calculationStartDate->diffInDays($estimatedEndDate) + 1
            : 0;
        $requestedDue = $requestedDueDate === null
            ? null
            : CarbonImmutable::parse($requestedDueDate)->startOfDay();

        return [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->category->name,
            'description' => $template->description,
            'version' => $template->version,
            'summary' => [
                'tasks_count' => $tasks->count(),
                'root_tasks_count' => $rootTasks->count(),
                'leaf_tasks_count' => $tasks->reject(
                    fn (ProjectTemplateTask $task): bool => $parentIds->contains($task->id)
                )->count(),
                'hierarchy_levels' => $maximumDepth + 1,
                'total_leaf_weight' => $this->hierarchyService->totalLeafWeight($tasks),
                'duration_days' => $durationDays,
            ],
            'timeline' => [
                'project_start_date' => $projectStartDate,
                'requested_due_date' => $requestedDueDate,
                'estimated_end_date' => $projectStartDate === null
                    ? null
                    : $estimatedEndDate?->toDateString(),
                'will_extend_project' => $projectStartDate !== null
                    && $requestedDue !== null
                    && $estimatedEndDate?->greaterThan($requestedDue) === true,
            ],
            'tasks' => $rootTasks
                ->map(fn (ProjectTemplateTask $task): array => $this->buildTaskNode(
                    $task,
                    $tasksByParent,
                    $schedule,
                    $aggregateWeights,
                    $projectStartDate !== null,
                    [],
                    0,
                ))
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Collection<int, ProjectTemplateTask>>  $tasksByParent
     * @param  array<int, array<string, mixed>>  $schedule
     * @param  array<int, float>  $aggregateWeights
     * @param  array<int, true>  $visitedTaskIds
     * @return array<string, mixed>
     */
    private function buildTaskNode(
        ProjectTemplateTask $task,
        Collection $tasksByParent,
        array $schedule,
        array $aggregateWeights,
        bool $includeDates,
        array $visitedTaskIds,
        int $depth,
    ): array {
        if (isset($visitedTaskIds[$task->id]) || $depth > ProjectTemplateHierarchyService::MAXIMUM_DEPTH) {
            throw ValidationException::withMessages([
                'parent_id' => 'Hierarchy template membentuk circular relationship atau melebihi batas level.',
            ]);
        }

        $visitedTaskIds[$task->id] = true;
        $children = $tasksByParent
            ->get($task->id, collect())
            ->sortBy([['position', 'asc'], ['id', 'asc']])
            ->values();
        $taskSchedule = $schedule[$task->id];
        $dependency = $task->dependency;

        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'priority' => $task->priority,
            'depth' => $depth,
            'is_leaf' => $children->isEmpty(),
            'weight' => $children->isEmpty() ? (float) $task->weight : null,
            'aggregate_weight' => $aggregateWeights[$task->id],
            'position' => $task->position,
            'start_offset_days' => $task->start_offset_days,
            'duration_days' => $task->duration_days,
            'start_date' => $includeDates ? $taskSchedule['start_date']->toDateString() : null,
            'due_date' => $includeDates ? $taskSchedule['due_date']->toDateString() : null,
            'predecessor' => $dependency === null ? null : [
                'id' => $dependency->predecessor_template_task_id,
                'name' => $dependency->predecessor?->name,
                'dependency_type' => $dependency->dependency_type,
                'lag_days' => $dependency->lag_days,
            ],
            'children' => $children
                ->map(fn (ProjectTemplateTask $child): array => $this->buildTaskNode(
                    $child,
                    $tasksByParent,
                    $schedule,
                    $aggregateWeights,
                    $includeDates,
                    $visitedTaskIds,
                    $depth + 1,
                ))
                ->all(),
        ];
    }
}
