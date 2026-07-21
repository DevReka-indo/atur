<?php

namespace App\Services;

use App\Models\ActualProgress;
use App\Models\PlannedProgress;
use App\Models\Project;
use App\Models\ProjectBaseline;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\TaskBaseline;
use App\Models\Task;

class ProjectProgressService
{
    public function __construct(private TaskHierarchyService $taskHierarchyService) {}

    public function ensureBaseline(Project $project): ProjectBaseline
    {
        $baseline = $project->activeBaseline()->first();

        if (!$baseline) {
            $baseline = ProjectBaseline::create([
                'project_id'    => $project->id,
                'baseline_name' => 'Auto Baseline',
                'is_active'     => true,
                'created_by'    => $project->created_by,
            ]);

            $this->snapshotTaskBaselines($project, $baseline);
        }

        return $baseline;
    }

    public function syncPlannedProgress(Project $project): ProjectBaseline
    {
        $baseline = $this->ensureBaseline($project);
        $rootTasks = $this->taskHierarchyService->loadProjectRoots($project);
        if (! $baseline->wasRecentlyCreated) {
            $this->snapshotTaskBaselines($project, $baseline, $rootTasks);
        }

        $projectStart = $project->start_date
            ? Carbon::parse($project->start_date)->startOfDay()
            : null;

        $projectEnd = $project->end_date
            ? Carbon::parse($project->end_date)->startOfDay()
            : null;

        if ($rootTasks->isEmpty()) {
            $this->createDefaultPlannedCurve($baseline, $projectStart, $projectEnd);

            return $baseline;
        }

        $totalWeight = (float) $rootTasks->sum(
            fn (Task $task): float => max(0, (float) $task->weight)
        );
        if ($totalWeight <= 0) {
            $this->createDefaultPlannedCurve($baseline, $projectStart, $projectEnd);

            return $baseline;
        }

        $taskTimelines = $rootTasks->map(fn (Task $task): array => $this->hierarchyTimeline($task));
        $startCandidates = $taskTimelines->pluck('start')->filter();
        $dueCandidates = $taskTimelines->pluck('end')->filter();

        $timelineStart = $startCandidates->min() ?? $projectStart ?? Carbon::now()->startOfDay();
        $timelineEnd = $dueCandidates->max() ?? $projectEnd ?? $timelineStart->copy()->addDays(30);

        if ($timelineEnd->lessThanOrEqualTo($timelineStart)) {
            $timelineEnd = $timelineStart->copy()->addDays(30);
        }

        $checkpoints = $this->buildCheckpoints($timelineStart, $timelineEnd, $rootTasks);

        $baseline->plannedProgress()->delete();

        $records = [];
        foreach ($checkpoints as $date) {
            $plannedValue = $rootTasks->sum(function (Task $task) use ($date, $timelineStart, $timelineEnd): float {
                $weight = (float) $task->weight;
                if ($weight <= 0) {
                    return 0.0;
                }

                return $weight * $this->plannedHierarchyCompletionAt(
                    $task,
                    $date,
                    $timelineStart,
                    $timelineEnd,
                    []
                );
            });

            $percentage = round(max(0, min(100, ($plannedValue / $totalWeight) * 100)), 2);

            $records[] = [
                'baseline_id' => $baseline->id,
                'date' => $date->toDateString(),
                'planned_cumulative_percentage' => $percentage,
            ];
        }

        $uniqueRecords = collect($records)->unique('date')->values()->toArray();
        PlannedProgress::insert($uniqueRecords);

        return $baseline;
    }

    public function recordActualProgress(Project $project): void
    {
        $baseline = $this->ensureBaseline($project);
        $leafCounts = $this->taskHierarchyService->executableLeafCounts($project);
        $actualPercentage = round((float) $project->calculateProgress(), 2);

        ActualProgress::updateOrCreate(
            [
                'project_id' => $project->id,
                'baseline_id' => $baseline->id,
                'date' => now()->toDateString(),
            ],
            [
                'actual_cumulative_percentage' => $actualPercentage,
                'completed_tasks_count' => $leafCounts['completed'],
                'total_tasks_count' => $leafCounts['total'],
                'created_by' => auth()->id() ?? $project->created_by,
                'notes' => 'Auto-updated from task changes',
            ]
        );
    }

    private function createDefaultPlannedCurve(ProjectBaseline $baseline, ?Carbon $startDate, ?Carbon $endDate): void
    {
        $start = $startDate ?? Carbon::now()->startOfDay();
        $end = $endDate ?? $start->copy()->addDays(90);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addDays(30);
        }

        $totalDays = max(1, $start->diffInDays($end));
        $steps = 10;

        $baseline->plannedProgress()->delete();

        $records = [];
        for ($i = 0; $i <= $steps; $i++) {
            $ratio = $i / $steps;
            $sCurve = ($ratio * $ratio) * (3 - 2 * $ratio);
            $records[] = [
                'baseline_id' => $baseline->id,
                'date' => $start->copy()->addDays((int) round($totalDays * $ratio))->toDateString(),
                'planned_cumulative_percentage' => round($sCurve * 100, 2),
            ];
        }

        $uniqueRecords = collect($records)->unique('date')->values()->toArray();
        PlannedProgress::insert($uniqueRecords);
    }

    /**
     * @param  Collection<int, Task>  $rootTasks
     * @return Collection<int, Carbon>
     */
    private function buildCheckpoints(Carbon $start, Carbon $end, Collection $rootTasks): Collection
    {
        $dates = collect([$start->copy(), $end->copy()])
            ->merge($rootTasks->flatMap(fn (Task $task): Collection => $this->hierarchyCheckpointDates($task)))
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->unique()
            ->sort()
            ->values();

        return $dates->map(fn (string $date): Carbon => Carbon::parse($date));
    }

    private function plannedTaskCompletionAt(Carbon $date, Carbon $taskStart, Carbon $taskEnd): float
    {
        if ($date->lessThan($taskStart)) {
            return 0.0;
        }

        if ($date->greaterThanOrEqualTo($taskEnd)) {
            return 1.0;
        }

        $duration = max(1, $taskStart->diffInDays($taskEnd));
        $elapsed = $taskStart->diffInDays($date);

        return max(0.0, min(1.0, $elapsed / $duration));
    }

    /**
     * @param  Collection<int, Task>|null  $rootTasks
     */
    private function snapshotTaskBaselines(Project $project, ProjectBaseline $baseline, ?Collection $rootTasks = null): void
    {
        $rootTasks ??= $this->taskHierarchyService->loadProjectRoots($project);
        $tasks = $rootTasks->flatMap(fn (Task $task): Collection => $this->flattenHierarchy($task));
        $records = $tasks->map(function (Task $task) use ($baseline): ?array {
            $timeline = $this->hierarchyTimeline($task);
            if ($timeline['start'] === null || $timeline['end'] === null) {
                return null;
            }

            return [
                'project_baseline_id' => $baseline->id,
                'task_id' => $task->id,
                'baseline_start' => $timeline['start']->toDateString(),
                'baseline_end' => $timeline['end']->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->filter()->values()->toArray();

        $baseline->taskBaselines()->delete();

        if (! empty($records)) {
            TaskBaseline::insert($records);
        }
    }

    /**
     * @param  array<int, true>  $visitedTaskIds
     */
    private function plannedHierarchyCompletionAt(
        Task $task,
        Carbon $date,
        Carbon $fallbackStart,
        Carbon $fallbackEnd,
        array $visitedTaskIds
    ): float {
        if (isset($visitedTaskIds[$task->id])) {
            return 0.0;
        }

        $visitedTaskIds[$task->id] = true;
        $children = $task->relationLoaded('subtasks') ? $task->subtasks : collect();

        if ($children->isEmpty()) {
            $taskStart = $task->start_date?->copy()->startOfDay() ?? $fallbackStart;
            $taskEnd = $task->due_date?->copy()->startOfDay() ?? $fallbackEnd;

            if ($taskEnd->lessThan($taskStart)) {
                $taskEnd = $taskStart->copy();
            }

            return $this->plannedTaskCompletionAt($date, $taskStart, $taskEnd);
        }

        return max(0, min(1, (float) $children->sum(function (Task $child) use ($date, $fallbackStart, $fallbackEnd, $visitedTaskIds): float {
            $childWeight = (float) ($child->subtask_weight_percentage ?? 0) / 100;

            return $childWeight * $this->plannedHierarchyCompletionAt(
                $child,
                $date,
                $fallbackStart,
                $fallbackEnd,
                $visitedTaskIds
            );
        })));
    }

    /**
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    private function hierarchyTimeline(Task $task): array
    {
        $children = $task->relationLoaded('subtasks') ? $task->subtasks : collect();
        if ($children->isNotEmpty()) {
            $childTimelines = $children->map(fn (Task $child): array => $this->hierarchyTimeline($child));
            $childStarts = $childTimelines->pluck('start')->filter();
            $childEnds = $childTimelines->pluck('end')->filter();

            if ($childStarts->isNotEmpty() || $childEnds->isNotEmpty()) {
                return [
                    'start' => $childStarts->min(),
                    'end' => $childEnds->max(),
                ];
            }
        }

        return [
            'start' => $task->start_date?->copy()->startOfDay(),
            'end' => $task->due_date?->copy()->startOfDay(),
        ];
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function hierarchyCheckpointDates(Task $task): Collection
    {
        $children = $task->relationLoaded('subtasks') ? $task->subtasks : collect();
        if ($children->isNotEmpty()) {
            return $children->flatMap(fn (Task $child): Collection => $this->hierarchyCheckpointDates($child));
        }

        return collect([$task->start_date, $task->due_date])
            ->filter()
            ->map(fn (Carbon $date): Carbon => $date->copy()->startOfDay());
    }

    /**
     * @return Collection<int, Task>
     */
    private function flattenHierarchy(Task $task): Collection
    {
        $children = $task->relationLoaded('subtasks') ? $task->subtasks : collect();

        return collect([$task])->concat(
            $children->flatMap(fn (Task $child): Collection => $this->flattenHierarchy($child))
        );
    }
}
