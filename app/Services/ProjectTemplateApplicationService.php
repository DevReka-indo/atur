<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProjectTemplateApplicationService
{
    public function __construct(
        private ProjectTemplateHierarchyService $hierarchyService,
        private ProjectTemplateScheduleCalculator $scheduleCalculator,
    ) {}

    /**
     * @return array{tasks: Collection<int, Task>, due_date: ?CarbonImmutable, mapping: array<int, int>}
     */
    public function apply(Project $project, ProjectTemplate $template, User $actor): array
    {
        $template->loadMissing('category');
        if (! $template->isEffectivelyActive()) {
            throw ValidationException::withMessages([
                'project_template_id' => 'Template yang dipilih sudah tidak aktif.',
            ]);
        }

        $templateTasks = $this->hierarchyService->loadGraph($template);
        $this->hierarchyService->validateGraph($template, $templateTasks);

        $aggregateWeights = $this->hierarchyService->aggregateWeights($templateTasks);
        $schedule = $this->scheduleCalculator->calculate($project->start_date, $templateTasks);
        $percentages = $this->subtaskPercentages($templateTasks, $aggregateWeights);
        $runtimeTasks = collect();
        $mapping = [];

        foreach ($templateTasks->sortBy([['position', 'asc'], ['id', 'asc']]) as $templateTask) {
            $runtimeTask = Task::query()->create([
                'project_id' => $project->id,
                'name' => $templateTask->name,
                'description' => $templateTask->description,
                'assignee_id' => null,
                'status' => 'to_do',
                'priority' => $templateTask->priority,
                'weight' => $aggregateWeights[$templateTask->id],
                'subtask_weight_percentage' => $percentages[$templateTask->id] ?? null,
                'start_date' => $schedule[$templateTask->id]['start_date']->toDateString(),
                'due_date' => $schedule[$templateTask->id]['due_date']->toDateString(),
                'position' => $templateTask->position,
                'completed_at' => null,
                'stopped_progress' => null,
                'created_by' => $actor->id,
                'parent_task_id' => null,
                'predecessor_id' => null,
                'dependency_type' => $schedule[$templateTask->id]['dependency_type'] ?? 'FS',
            ]);

            $mapping[$templateTask->id] = $runtimeTask->id;
            $runtimeTasks->push($runtimeTask);
        }

        foreach ($templateTasks->whereNotNull('parent_id') as $templateTask) {
            Task::query()->whereKey($mapping[$templateTask->id])->update([
                'parent_task_id' => $mapping[$templateTask->parent_id],
            ]);
        }

        foreach ($templateTasks->filter(fn (ProjectTemplateTask $task): bool => $task->dependency !== null) as $templateTask) {
            Task::query()->whereKey($mapping[$templateTask->id])->update([
                'predecessor_id' => $mapping[$templateTask->dependency->predecessor_template_task_id],
                'dependency_type' => $templateTask->dependency->dependency_type,
            ]);
        }

        $latestDueDate = collect($schedule)->max('due_date');

        return [
            'tasks' => $runtimeTasks,
            'due_date' => $latestDueDate,
            'mapping' => $mapping,
        ];
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     * @param  array<int, float>  $aggregateWeights
     * @return array<int, float>
     */
    private function subtaskPercentages(Collection $tasks, array $aggregateWeights): array
    {
        $percentages = [];
        $childrenGroups = $tasks->whereNotNull('parent_id')->groupBy('parent_id');

        foreach ($childrenGroups as $parentId => $children) {
            $orderedChildren = $children->sortBy([['position', 'asc'], ['id', 'asc']])->values();
            $parentWeight = $aggregateWeights[(int) $parentId] ?? 0.0;
            if ($parentWeight <= 0) {
                throw ValidationException::withMessages(['weight' => 'Aggregate weight parent harus lebih besar dari 0.']);
            }

            $allocated = 0.0;
            foreach ($orderedChildren as $index => $child) {
                $percentage = $index === $orderedChildren->count() - 1
                    ? round(100 - $allocated, 2)
                    : round(($aggregateWeights[$child->id] / $parentWeight) * 100, 2);
                $percentages[$child->id] = $percentage;
                $allocated = round($allocated + $percentage, 2);
            }
        }

        return $percentages;
    }
}
