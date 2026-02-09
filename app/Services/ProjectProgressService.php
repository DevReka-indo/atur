<?php

namespace App\Services;

use App\Models\ActualProgress;
use App\Models\PlannedProgress;
use App\Models\Project;
use App\Models\ProjectBaseline;
use Carbon\Carbon;

class ProjectProgressService
{
    public function ensureBaselineAndPlanned(Project $project): ProjectBaseline
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

        if ($baseline->plannedProgress()->exists()) {
            return $baseline;
        }

        $startDate = $project->start_date
            ? Carbon::parse($project->start_date)->startOfDay()
            : Carbon::now()->startOfDay();

        $endDate = $project->end_date
            ? Carbon::parse($project->end_date)->startOfDay()
            : $startDate->copy()->addDays(90);

        if ($endDate->lessThanOrEqualTo($startDate)) {
            $endDate = $startDate->copy()->addDays(30);
        }

        $totalDays = max(1, $startDate->diffInDays($endDate));
        $steps = 10;

        for ($i = 0; $i <= $steps; $i++) {
            $ratio = $i / $steps;
            $sCurve = ($ratio * $ratio) * (3 - 2 * $ratio);
            $percentage = round($sCurve * 100, 2);

            PlannedProgress::updateOrCreate(
                [
                    'baseline_id' => $baseline->id,
                    'date' => $startDate->copy()->addDays((int) round($totalDays * $ratio))->toDateString(),
                ],
                [
                    'planned_cumulative_percentage' => $percentage,
                ]
            );
        }

        return $baseline;
    }

    public function recordActualProgress(Project $project): void
    {
        $baseline = $this->ensureBaselineAndPlanned($project);

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
}
