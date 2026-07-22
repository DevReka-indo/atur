<?php

namespace App\Services;

use App\Models\ProjectTemplateTask;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProjectTemplateScheduleCalculator
{
    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     * @return array<int, array{start_date: CarbonImmutable, due_date: CarbonImmutable, duration_days: int, predecessor_id: ?int, dependency_type: ?string, lag_days: int}>
     */
    public function calculate(DateTimeInterface|string $projectStartDate, Collection $tasks): array
    {
        $projectStart = CarbonImmutable::parse($projectStartDate)->startOfDay();
        $tasksById = $tasks->keyBy('id');
        $parentIds = $tasks->pluck('parent_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $schedule = [];
        $visiting = [];

        $resolve = function (ProjectTemplateTask $task) use (&$resolve, &$schedule, &$visiting, $projectStart, $tasksById, $parentIds): array {
            if (isset($schedule[$task->id])) {
                return $schedule[$task->id];
            }

            if (isset($visiting[$task->id])) {
                $this->fail('predecessor_template_task_id', 'Dependency template membentuk circular relationship.');
            }

            if ($task->duration_days < 1 || $task->start_offset_days < 0) {
                $this->fail('duration_days', 'Duration minimal 1 hari dan offset tidak boleh negatif.');
            }

            $visiting[$task->id] = true;
            $baseStart = $projectStart->addDays($task->start_offset_days);
            $finalStart = $baseStart;
            $dependency = $task->dependency;

            if ($dependency !== null) {
                if ($dependency->lag_days < 0 || $parentIds->contains($task->id)) {
                    $this->fail('predecessor_template_task_id', 'Dependency hanya diperbolehkan antar-leaf task dengan lag non-negatif.');
                }

                $predecessor = $tasksById->get($dependency->predecessor_template_task_id);
                if ($predecessor === null
                    || $parentIds->contains($predecessor->id)
                    || (int) $predecessor->project_template_id !== (int) $task->project_template_id
                    || $predecessor->is($task)) {
                    $this->fail('predecessor_template_task_id', 'Predecessor harus merupakan leaf task dari template yang sama.');
                }

                $predecessorSchedule = $resolve($predecessor);
                $lagDays = $dependency->lag_days;

                $impliedStart = match ($dependency->dependency_type) {
                    'FS' => $predecessorSchedule['due_date']->addDays(1 + $lagDays),
                    'SS' => $predecessorSchedule['start_date']->addDays($lagDays),
                    'FF' => $predecessorSchedule['due_date']->addDays($lagDays)->subDays($task->duration_days - 1),
                    'SF' => $predecessorSchedule['start_date']->addDays($lagDays)->subDays($task->duration_days - 1),
                    default => $this->fail('dependency_type', 'Tipe dependency harus FS, SS, FF, atau SF.'),
                };

                if ($impliedStart->greaterThan($finalStart)) {
                    $finalStart = $impliedStart;
                }
            }

            unset($visiting[$task->id]);

            return $schedule[$task->id] = [
                'start_date' => $finalStart,
                'due_date' => $finalStart->addDays($task->duration_days - 1),
                'duration_days' => $task->duration_days,
                'predecessor_id' => $dependency?->predecessor_template_task_id,
                'dependency_type' => $dependency?->dependency_type,
                'lag_days' => $dependency?->lag_days ?? 0,
            ];
        };

        foreach ($tasks as $task) {
            $resolve($task);
        }

        return $schedule;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
