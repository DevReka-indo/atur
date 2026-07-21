<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TaskGanttService
{
    /** @var array<string, string> */
    private const DEPENDENCY_TYPES = [
        'FS' => '0',
        'SS' => '1',
        'FF' => '2',
        'SF' => '3',
    ];

    public function __construct(private TaskHierarchyService $taskHierarchyService) {}

    /**
     * @return array{data: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function forProject(Project $project, bool $canContribute): array
    {
        $tasks = $project->relationLoaded('tasks')
            ? $project->tasks
            : Task::query()->where('project_id', $project->id)->get();
        $tasks->loadMissing($this->taskRelations());
        $tasks = $tasks
            ->sortBy(fn (Task $task): string => str_pad((string) $task->position, 10, '0', STR_PAD_LEFT).'-'.str_pad((string) $task->id, 20, '0', STR_PAD_LEFT))
            ->values();

        return $this->buildPayload($tasks, $canContribute);
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    public function forUser(User $user, string $status = 'all'): array
    {
        $assignedLeafQuery = Task::query()
            ->assignedToUser($user->id)
            ->whereDoesntHave('subtasks')
            ->with([
                'parent:id,project_id,parent_task_id',
                'parent.parent:id,project_id,parent_task_id',
            ]);

        if ($status !== 'all') {
            $assignedLeafQuery->where('status', $status);
        }

        $assignedLeaves = $assignedLeafQuery->get(['id', 'project_id', 'parent_task_id']);
        $assignedLeafIds = $assignedLeaves->modelKeys();
        $includedTaskIds = $assignedLeaves
            ->flatMap(fn (Task $task): array => [
                $task->id,
                $task->parent_task_id,
                $task->parent?->parent_task_id,
            ])
            ->filter()
            ->unique()
            ->values();

        if ($includedTaskIds->isEmpty()) {
            return ['data' => [], 'links' => []];
        }

        $tasks = Task::query()
            ->whereIn('id', $includedTaskIds)
            ->with($this->taskRelations())
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $this->buildPayload($tasks, false, $assignedLeafIds);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  array<int, int|string>|null  $assignedLeafIds
     * @return array{data: array<int, array<string, mixed>>, links: array<int, array<string, string>>}
     */
    private function buildPayload(Collection $tasks, bool $canContribute, ?array $assignedLeafIds = null): array
    {
        $tasksById = $tasks->keyBy('id');
        $tasksByParent = $tasks->groupBy(fn (Task $task): int => (int) ($task->parent_task_id ?? 0));
        $assignedLeafLookup = $assignedLeafIds === null ? null : array_fill_keys($assignedLeafIds, true);
        $data = [];
        $visitedTaskIds = [];

        $appendTask = function (Task $task, int $depth) use (&$appendTask, &$data, &$visitedTaskIds, $assignedLeafLookup, $canContribute, $tasksByParent, $tasksById): void {
            if (isset($visitedTaskIds[$task->id]) || $depth > TaskHierarchyService::MAXIMUM_DEPTH) {
                return;
            }

            $visitedTaskIds[$task->id] = true;
            $children = $tasksByParent->get($task->id, collect());
            $isSummary = $children->isNotEmpty();
            $isContext = $assignedLeafLookup !== null && ! isset($assignedLeafLookup[$task->id]);
            [$start, $end] = $this->resolveTimeline($task, $isSummary);

            $data[] = $this->taskItem(
                $task,
                $start,
                $end,
                $isSummary,
                $isSummary || $isContext || ! $canContribute,
                $tasksByParent,
                $tasksById,
            );

            foreach ($children as $child) {
                $appendTask($child, $depth + 1);
            }
        };

        foreach ($tasksByParent->get(0, collect()) as $rootTask) {
            $appendTask($rootTask, 0);
        }

        foreach ($tasks as $task) {
            $parentIsMissing = $task->parent_task_id !== null && ! $tasksById->has($task->parent_task_id);
            if (! isset($visitedTaskIds[$task->id]) && $parentIsMissing) {
                $appendTask($task, 0);
            }
        }

        return [
            'data' => $data,
            'links' => $this->dependencyLinks($tasks, $tasksById, $tasksByParent),
        ];
    }

    /**
     * @param  Collection<int, Collection<int, Task>>  $tasksByParent
     * @param  Collection<int, Task>  $tasksById
     * @return array<string, mixed>
     */
    private function taskItem(
        Task $task,
        CarbonInterface $start,
        CarbonInterface $end,
        bool $isSummary,
        bool $readonly,
        Collection $tasksByParent,
        Collection $tasksById,
    ): array {
        $assignees = $task->assignees->isNotEmpty()
            ? $task->assignees->pluck('name')->values()
            : collect([$task->assignee?->name])->filter()->values();
        $progress = $isSummary
            ? $this->taskHierarchyService->resolveProgressPercentage($task)
            : $this->taskHierarchyService->resolveStatusProgressPercentage($task);
        $duration = max(1, $start->diffInDays($end) + 1);
        $predecessor = $task->predecessor_id === null ? null : 'task-'.$task->predecessor_id;

        return [
            'id' => $this->taskIdentifier($task),
            'name' => $task->name,
            'text' => $task->name,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'start_date' => $start->format('d-m-Y'),
            'end_date' => $end->format('d-m-Y'),
            'duration' => $duration,
            'progress' => round($progress / 100, 4),
            'status' => $task->status,
            'parent' => $task->parent_task_id !== null && $tasksById->has($task->parent_task_id)
                ? 'task-'.$task->parent_task_id
                : 0,
            'type' => $isSummary ? 'project' : 'task',
            'readonly' => $readonly,
            'open' => true,
            'task_token' => $task->token,
            'parent_token' => $task->parent?->token,
            'detail_url' => route('tasks.show', $task->token),
            'priority' => $task->priority,
            'assignees' => $assignees->all(),
            'resource' => $assignees->implode(', '),
            'predecessor_id' => $predecessor,
            'predecessor_token' => $task->predecessor?->token,
            'dependency_type' => $task->dependency_type ?? 'FS',
            'is_summary' => $isSummary,
            'hierarchy_level' => $this->depthInPayload($task, $tasksByParent),
        ];
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function resolveTimeline(Task $task, bool $isSummary): array
    {
        $fallbackStart = $task->start_date ?? $task->created_at ?? now()->startOfDay();
        $fallbackEnd = $task->due_date ?? $fallbackStart;

        if (! $isSummary) {
            return $this->normalizeTimeline($fallbackStart, $fallbackEnd);
        }

        $descendants = $this->loadedDescendantsOf($task);
        $descendantStarts = $descendants->pluck('start_date')->filter();
        $descendantEnds = $descendants->pluck('due_date')->filter();
        $start = $descendantStarts->isNotEmpty() ? $descendantStarts->min() : $fallbackStart;
        $end = $descendantEnds->isNotEmpty() ? $descendantEnds->max() : $fallbackEnd;

        return $this->normalizeTimeline($start, $end);
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function normalizeTimeline(CarbonInterface|string $start, CarbonInterface|string $end): array
    {
        $normalizedStart = $start instanceof CarbonInterface ? $start->copy()->startOfDay() : Carbon::parse($start)->startOfDay();
        $normalizedEnd = $end instanceof CarbonInterface ? $end->copy()->startOfDay() : Carbon::parse($end)->startOfDay();

        if ($normalizedEnd->lt($normalizedStart)) {
            $normalizedEnd = $normalizedStart->copy();
        }

        return [$normalizedStart, $normalizedEnd];
    }

    /**
     * @return Collection<int, Task>
     */
    private function loadedDescendantsOf(Task $task): Collection
    {
        $descendants = collect();
        $pending = $task->subtasks->values();
        $visitedTaskIds = [];

        while ($pending->isNotEmpty()) {
            /** @var Task $descendant */
            $descendant = $pending->shift();
            if (isset($visitedTaskIds[$descendant->id])) {
                continue;
            }

            $visitedTaskIds[$descendant->id] = true;
            $descendants->push($descendant);
            $pending = $pending->concat($descendant->subtasks);
        }

        return $descendants;
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  Collection<int, Task>  $tasksById
     * @param  Collection<int, Collection<int, Task>>  $tasksByParent
     * @return array<int, array<string, string>>
     */
    private function dependencyLinks(Collection $tasks, Collection $tasksById, Collection $tasksByParent): array
    {
        return $tasks
            ->filter(fn (Task $task): bool => $task->predecessor_id !== null)
            ->map(function (Task $task) use ($tasksById, $tasksByParent): ?array {
                $predecessor = $tasksById->get($task->predecessor_id);
                $reason = $this->invalidDependencyReason($task, $predecessor, $tasksByParent);

                if ($reason !== null) {
                    Log::warning('Skipped invalid task dependency in Gantt payload.', [
                        'task_id' => $task->id,
                        'predecessor_id' => $task->predecessor_id,
                        'reason' => $reason,
                    ]);

                    return null;
                }

                return [
                    'id' => 'dependency-'.$task->id,
                    'source' => $this->taskIdentifier($predecessor),
                    'target' => $this->taskIdentifier($task),
                    'type' => self::DEPENDENCY_TYPES[$task->dependency_type ?? 'FS'] ?? '0',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Collection<int, Task>>  $tasksByParent
     */
    private function invalidDependencyReason(Task $task, ?Task $predecessor, Collection $tasksByParent): ?string
    {
        if ($predecessor === null) {
            return 'predecessor_not_in_payload';
        }

        if ((int) $predecessor->project_id !== (int) $task->project_id) {
            return 'cross_project';
        }

        if ($tasksByParent->get($task->id, collect())->isNotEmpty()) {
            return 'summary_target';
        }

        if ($tasksByParent->get($predecessor->id, collect())->isNotEmpty()) {
            return 'summary_predecessor';
        }

        return null;
    }

    /**
     * @param  Collection<int, Collection<int, Task>>  $tasksByParent
     */
    private function depthInPayload(Task $task, Collection $tasksByParent): int
    {
        $depth = 0;
        $parentTaskId = $task->parent_task_id;
        $visitedTaskIds = [];

        while ($parentTaskId !== null && $depth < TaskHierarchyService::MAXIMUM_DEPTH) {
            if (isset($visitedTaskIds[$parentTaskId])) {
                break;
            }

            $visitedTaskIds[$parentTaskId] = true;
            $depth++;
            $parent = $tasksByParent->flatten(1)->firstWhere('id', $parentTaskId);
            $parentTaskId = $parent?->parent_task_id;
        }

        return $depth;
    }

    private function taskIdentifier(Task $task): string
    {
        return 'task-'.$task->id;
    }

    /**
     * @return array<int, string>
     */
    private function taskRelations(): array
    {
        return [
            'project:id,name',
            'parent:id,token,name,parent_task_id',
            'predecessor:id,token,project_id,parent_task_id',
            'assignees:id,name',
            'assignee:id,name',
            'statusWeight',
            'statusHistory',
            'subtasks.statusWeight',
            'subtasks.statusHistory',
            'subtasks.subtasks.statusWeight',
            'subtasks.subtasks.statusHistory',
        ];
    }
}
