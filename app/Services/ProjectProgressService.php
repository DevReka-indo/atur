<?php

namespace App\Services;

use App\Models\ActualProgress;
use App\Models\PlannedProgress;
use App\Models\Project;
use App\Models\ProjectBaseline;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectProgressService
{
    public function ensureBaseline(Project $project): ProjectBaseline
    {
        $baseline = $project->activeBaseline()->first();

        if (!$baseline) {
            $baseline = ProjectBaseline::create([
                'project_id' => $project->id,
                'baseline_name' => 'Auto Baseline',
                'is_active' => true,
                'created_by' => $project->created_by,
            ]);
        }

        return $baseline;
    }

    public function syncPlannedProgress(Project $project): ProjectBaseline
    {
        $baseline = $this->ensureBaseline($project);

        $tasks = $project->tasks()->get();

        $projectStart = $project->start_date
            ? Carbon::parse($project->start_date)->startOfDay()
            : null;

        $projectEnd = $project->end_date
            ? Carbon::parse($project->end_date)->startOfDay()
            : null;

        if ($tasks->isEmpty()) {
            $this->createDefaultPlannedCurve($baseline, $projectStart, $projectEnd);

            return $baseline;
        }

        $totalWeight = (float) $tasks->sum('weight');
        if ($totalWeight <= 0) {
            $this->createDefaultPlannedCurve($baseline, $projectStart, $projectEnd);

            return $baseline;
        }

        $startCandidates = $tasks->pluck('start_date')->filter()->map(fn ($date) => Carbon::parse($date)->startOfDay());
        $dueCandidates = $tasks->pluck('due_date')->filter()->map(fn ($date) => Carbon::parse($date)->startOfDay());

        $timelineStart = $startCandidates->min() ?? $projectStart ?? Carbon::now()->startOfDay();
        $timelineEnd = $dueCandidates->max() ?? $projectEnd ?? $timelineStart->copy()->addDays(30);

        if ($timelineEnd->lessThanOrEqualTo($timelineStart)) {
            $timelineEnd = $timelineStart->copy()->addDays(30);
        }

        $checkpoints = $this->buildCheckpoints($timelineStart, $timelineEnd, $tasks);

        $baseline->plannedProgress()->delete();

        foreach ($checkpoints as $date) {
            $plannedValue = $tasks->sum(function ($task) use ($date, $timelineStart, $timelineEnd) {
                $weight = (float) $task->weight;
                if ($weight <= 0) {
                    return 0;
                }

                $taskStart = $task->start_date ? Carbon::parse($task->start_date)->startOfDay() : $timelineStart;
                $taskEnd = $task->due_date ? Carbon::parse($task->due_date)->startOfDay() : $timelineEnd;

                if ($taskEnd->lessThan($taskStart)) {
                    $taskEnd = $taskStart->copy();
                }

                return $weight * $this->plannedTaskCompletionAt($date, $taskStart, $taskEnd);
            });

            $percentage = round(($plannedValue / $totalWeight) * 100, 2);

            PlannedProgress::create([
                'baseline_id' => $baseline->id,
                'date' => $date->toDateString(),
                'planned_cumulative_percentage' => $percentage,
            ]);
        }

        return $baseline;
    }

    public function recordActualProgress(Project $project): void
    {
        $baseline = $this->ensureBaseline($project);

        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->where('status', 'completed')->count();
        $actualPercentage = round((float) $project->calculateProgress(), 2);

        ActualProgress::updateOrCreate(
            [
                'project_id' => $project->id,
                'baseline_id' => $baseline->id,
                'date' => now()->toDateString(),
            ],
            [
                'actual_cumulative_percentage' => $actualPercentage,
                'completed_tasks_count' => $completedTasks,
                'total_tasks_count' => $totalTasks,
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

        for ($i = 0; $i <= $steps; $i++) {
            $ratio = $i / $steps;
            $sCurve = ($ratio * $ratio) * (3 - 2 * $ratio);
            PlannedProgress::create([
                'baseline_id' => $baseline->id,
                'date' => $start->copy()->addDays((int) round($totalDays * $ratio))->toDateString(),
                'planned_cumulative_percentage' => round($sCurve * 100, 2),
            ]);
        }
    }

    private function buildCheckpoints(Carbon $start, Carbon $end, Collection $tasks): Collection
    {
        $dates = collect([$start->copy(), $end->copy()])
            ->merge($tasks->pluck('start_date')->filter()->map(fn ($date) => Carbon::parse($date)->startOfDay()))
            ->merge($tasks->pluck('due_date')->filter()->map(fn ($date) => Carbon::parse($date)->startOfDay()))
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->sort()
            ->values();

        return $dates->map(fn ($date) => Carbon::parse($date));
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
}
