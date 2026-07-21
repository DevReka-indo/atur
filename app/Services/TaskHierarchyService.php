<?php

namespace App\Services;

use App\Jobs\SendEmailNotification;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\TaskStatusWeight;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskHierarchyService
{
    public const MAXIMUM_DEPTH = 2;

    /** @var array<string, float> */
    private array $statusProgressCache = [];

    public function resolveProgressPercentage(Task $task): float
    {
        $task->loadMissing([
            'statusWeight',
            'statusHistory',
            'subtasks.statusWeight',
            'subtasks.statusHistory',
            'subtasks.subtasks.statusWeight',
            'subtasks.subtasks.statusHistory',
        ]);

        return $this->resolveProgress($task, []);
    }

    public function resolveEarnedContribution(Task $task): float
    {
        if ($task->parent_task_id !== null) {
            return 0.0;
        }

        return (float) $task->weight * ($this->resolveProgressPercentage($task) / 100);
    }

    public function resolveProjectProgressPercentage(Project $project): float
    {
        $rootTasks = $this->loadProjectRoots($project)
            ->reject(fn (Task $task): bool => $task->status === 'cancelled')
            ->filter(fn (Task $task): bool => (float) $task->weight > 0);

        $totalWeight = (float) $rootTasks->sum('weight');
        if ($totalWeight <= 0) {
            return 0.0;
        }

        $earnedValue = $rootTasks->sum(
            fn (Task $task): float => $this->resolveEarnedContribution($task)
        );

        return $this->clampPercentage(($earnedValue / $totalWeight) * 100);
    }

    /**
     * @return Collection<int, Task>
     */
    public function loadProjectRoots(Project $project): Collection
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->whereNull('parent_task_id')
            ->with($this->progressHierarchyRelations())
            ->get();
    }

    /**
     * @return array{total: int, completed: int}
     */
    public function executableLeafCounts(Project $project): array
    {
        $counts = Task::query()
            ->where('project_id', $project->id)
            ->whereDoesntHave('subtasks')
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->first();

        return [
            'total' => (int) ($counts->total ?? 0),
            'completed' => (int) ($counts->completed ?? 0),
        ];
    }

    public function resolveStatusProgressPercentage(Task $task): float
    {
        if ($task->status === 'stopped') {
            if ($task->stopped_progress !== null) {
                return $this->clampPercentage((float) $task->stopped_progress);
            }

            $task->loadMissing('statusHistory');
            $previousStatus = $task->statusHistory
                ->sortByDesc('id')
                ->first(fn (TaskStatusHistory $history): bool => $history->from_status !== null)
                ?->from_status ?? 'to_do';

            return $this->statusProgressPercentage($previousStatus);
        }

        if ($task->relationLoaded('statusWeight') && $task->statusWeight !== null) {
            return $this->clampPercentage((float) $task->statusWeight->weight_value * 100);
        }

        return $this->statusProgressPercentage($task->status);
    }

    public function deriveStatus(Task $parent): string
    {
        $children = $parent->subtasks()->get(['id', 'parent_task_id', 'status']);

        return $this->deriveStatusFromChildren($parent, $children);
    }

    public function synchronizeAncestors(Task $task, User|int $actor): void
    {
        $actorId = $actor instanceof User ? $actor->getKey() : $actor;

        DB::transaction(function () use ($task, $actorId): void {
            $parentTaskId = $task->parent_task_id;
            $visitedTaskIds = [];

            while ($parentTaskId !== null) {
                if (isset($visitedTaskIds[$parentTaskId])) {
                    throw new DomainException('Circular task hierarchy detected.');
                }

                $visitedTaskIds[$parentTaskId] = true;
                $parent = Task::query()->lockForUpdate()->findOrFail($parentTaskId);
                $children = Task::query()
                    ->where('parent_task_id', $parent->id)
                    ->lockForUpdate()
                    ->get(['id', 'parent_task_id', 'status']);
                $derivedStatus = $this->deriveStatusFromChildren($parent, $children);

                if ($parent->status !== $derivedStatus) {
                    $oldStatus = $parent->status;
                    $parent->update([
                        'status' => $derivedStatus,
                        'completed_at' => $derivedStatus === 'completed' ? now() : null,
                    ]);

                    TaskStatusHistory::create([
                        'task_id' => $parent->id,
                        'from_status' => $oldStatus,
                        'to_status' => $derivedStatus,
                        'changed_by' => $actorId,
                    ]);

                    ActivityLog::create([
                        'user_id' => $actorId,
                        'action' => 'status_changed',
                        'entity_type' => 'task',
                        'entity_id' => $parent->id,
                        'description' => 'Status task utama "'.$parent->name.'" diperbarui otomatis dari '.$oldStatus.' menjadi '.$derivedStatus.' setelah perubahan task "'.$task->name.'".',
                        'old_value' => [
                            'status' => $oldStatus,
                            'trigger_task_id' => $task->id,
                        ],
                        'new_value' => [
                            'status' => $derivedStatus,
                            'trigger_task_id' => $task->id,
                        ],
                    ]);

                    $this->notifyAutomaticStatusChange($parent, $oldStatus, $derivedStatus, $actorId);
                }

                $parentTaskId = $parent->parent_task_id;
            }
        });
    }

    public function assertManualStatusAllowed(Task $task): void
    {
        if ($task->subtasks()->exists()) {
            $this->failValidation(
                'status',
                'Status task utama tidak dapat diubah langsung karena mengikuti status subtask.'
            );
        }
    }

    public function changeStatus(Task $task, string $newStatus, User|int $actor): bool
    {
        $this->assertManualStatusAllowed($task);
        $this->validateStatusTransition($task, $newStatus);

        if ($task->status === $newStatus) {
            return false;
        }

        $actorId = $actor instanceof User ? $actor->getKey() : $actor;
        $oldStatus = $task->status;
        $stoppedProgress = null;

        if ($newStatus === 'stopped') {
            $stoppedProgress = $this->resolveStatusProgressPercentage($task);
        }

        $task->update([
            'status' => $newStatus,
            'stopped_progress' => $stoppedProgress,
            'completed_at' => $newStatus === 'completed' ? now() : null,
        ]);

        TaskStatusHistory::create([
            'task_id' => $task->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $actorId,
        ]);

        $this->synchronizeAncestors($task, $actorId);

        return true;
    }

    public function hierarchyDepth(Task $task): int
    {
        $depth = 0;
        $parentTaskId = $task->parent_task_id;
        $visitedTaskIds = $task->exists ? [$task->getKey() => true] : [];

        while ($parentTaskId !== null) {
            if (isset($visitedTaskIds[$parentTaskId])) {
                throw new DomainException('Circular task hierarchy detected.');
            }

            $visitedTaskIds[$parentTaskId] = true;
            $parent = Task::query()->find($parentTaskId, ['id', 'parent_task_id']);

            if ($parent === null) {
                break;
            }

            $depth++;
            $parentTaskId = $parent->parent_task_id;
        }

        return $depth;
    }

    public function validateParentCandidate(Task $taskOrNewContext, Task $parent): void
    {
        if ((int) $taskOrNewContext->project_id !== (int) $parent->project_id) {
            $this->failValidation('parent_task_id', 'The parent task must belong to the same project.');
        }

        if ($taskOrNewContext->exists && $taskOrNewContext->is($parent)) {
            $this->failValidation('parent_task_id', 'A task cannot be its own parent.');
        }

        $candidate = $parent;
        $visitedTaskIds = [];

        while ($candidate !== null) {
            if (isset($visitedTaskIds[$candidate->getKey()])) {
                $this->failValidation('parent_task_id', 'The selected parent belongs to a circular hierarchy.');
            }

            $visitedTaskIds[$candidate->getKey()] = true;

            if ($taskOrNewContext->exists && $candidate->is($taskOrNewContext)) {
                $this->failValidation('parent_task_id', 'A descendant task cannot be selected as the parent.');
            }

            $candidate = $candidate->parent_task_id === null
                ? null
                : Task::query()->find($candidate->parent_task_id, ['id', 'project_id', 'parent_task_id']);
        }

        if ($this->hierarchyDepth($parent) + 1 > self::MAXIMUM_DEPTH) {
            $this->failValidation('parent_task_id', 'Task hierarchy may contain at most three levels.');
        }
    }

    public function validateSubtaskWeight(Task $task): void
    {
        if ($task->parent_task_id === null) {
            if ($task->subtask_weight_percentage !== null) {
                $this->failValidation('subtask_weight_percentage', 'A root task cannot have a subtask weight percentage.');
            }

            return;
        }

        $percentage = $task->subtask_weight_percentage;
        if ($percentage === null || (float) $percentage <= 0 || (float) $percentage > 100) {
            $this->failValidation('subtask_weight_percentage', 'A child task weight percentage must be greater than 0 and at most 100.');
        }

        $siblingsQuery = Task::query()->where('parent_task_id', $task->parent_task_id);
        if ($task->exists) {
            $siblingsQuery->whereKeyNot($task->getKey());
        }

        $siblingPercentages = $siblingsQuery
            ->lockForUpdate()
            ->pluck('subtask_weight_percentage');
        $totalPercentage = (float) $siblingPercentages->sum() + (float) $percentage;
        if (round($totalPercentage, 2) > 100.00) {
            $this->failValidation('subtask_weight_percentage', 'The total weight percentage of sibling tasks cannot exceed 100%.');
        }
    }

    public function validateStatusTransition(Task $task, string $newStatus): void
    {
        if ($task->parent_task_id === null || $task->status !== 'to_do' || $newStatus === 'to_do') {
            return;
        }

        $siblingsQuery = Task::query()
            ->where('parent_task_id', $task->parent_task_id)
            ->lockForUpdate();

        if ($task->exists) {
            $siblingsQuery->whereKeyNot($task->getKey());
        }

        $siblings = $siblingsQuery->get(['id', 'subtask_weight_percentage']);
        $siblings->push($task);
        $hasUndefinedWeight = $siblings->contains(
            fn (Task $sibling): bool => $sibling->subtask_weight_percentage === null
        );
        $totalPercentage = round((float) $siblings->sum('subtask_weight_percentage'), 2);

        if ($hasUndefinedWeight || $totalPercentage !== 100.00) {
            $this->failValidation('status', 'Child tasks cannot leave To Do until sibling weights total exactly 100%.');
        }
    }

    public function validatePredecessorCandidate(Task $taskOrNewContext, Task $predecessor): void
    {
        if ((int) $taskOrNewContext->project_id !== (int) $predecessor->project_id) {
            $this->failValidation('predecessor_id', 'The predecessor must belong to the same project.');
        }

        if ($taskOrNewContext->exists && $taskOrNewContext->is($predecessor)) {
            $this->failValidation('predecessor_id', 'A task cannot be its own predecessor.');
        }

        if ($predecessor->subtasks()->exists()) {
            $this->failValidation('predecessor_id', 'A task that has subtasks cannot be selected as a predecessor.');
        }

        if ($this->isAncestorOf($predecessor, $taskOrNewContext)) {
            $this->failValidation('predecessor_id', 'A parent or ancestor task cannot be selected as a predecessor.');
        }

        if ($taskOrNewContext->exists && $this->isAncestorOf($taskOrNewContext, $predecessor)) {
            $this->failValidation('predecessor_id', 'A descendant task cannot be selected as a predecessor.');
        }
    }

    /**
     * @param  array<int, true>  $visitedTaskIds
     */
    private function resolveProgress(Task $task, array $visitedTaskIds): float
    {
        if ($task->getKey() !== null && isset($visitedTaskIds[$task->getKey()])) {
            throw new DomainException('Circular task hierarchy detected.');
        }

        if ($task->getKey() !== null) {
            $visitedTaskIds[$task->getKey()] = true;
        }

        $children = $task->relationLoaded('subtasks')
            ? $task->subtasks
            : $task->subtasks()->with(['statusWeight', 'statusHistory'])->get();

        if ($children->isEmpty()) {
            return $this->resolveStatusProgressPercentage($task);
        }

        $progress = $children->sum(function (Task $child) use ($visitedTaskIds): float {
            $percentage = (float) ($child->subtask_weight_percentage ?? 0);

            return ($percentage / 100) * $this->resolveProgress($child, $visitedTaskIds);
        });

        return $this->clampPercentage((float) $progress);
    }

    /**
     * @param  Collection<int, Task>  $children
     */
    private function deriveStatusFromChildren(Task $parent, Collection $children): string
    {
        if ($children->isEmpty()) {
            return $parent->status;
        }

        $statuses = $children->pluck('status');

        if ($statuses->every(fn (string $status): bool => $status === 'to_do')) {
            return 'to_do';
        }

        if ($statuses->every(fn (string $status): bool => $status === 'completed')) {
            return 'completed';
        }

        if ($statuses->every(fn (string $status): bool => $status === 'cancelled')) {
            return 'cancelled';
        }

        if ($statuses->contains('review') && ! $statuses->contains('in_progress')) {
            return 'review';
        }

        if ($statuses->every(fn (string $status): bool => in_array($status, ['stopped', 'cancelled'], true))) {
            return 'stopped';
        }

        return 'in_progress';
    }

    private function statusProgressPercentage(string $status): float
    {
        if (! array_key_exists($status, $this->statusProgressCache)) {
            $this->statusProgressCache[$status] = (float) (TaskStatusWeight::query()
                ->where('status', $status)
                ->value('weight_value') ?? 0) * 100;
        }

        return $this->clampPercentage($this->statusProgressCache[$status]);
    }

    private function clampPercentage(float $percentage): float
    {
        return max(0, min(100, $percentage));
    }

    /**
     * @return list<string>
     */
    private function progressHierarchyRelations(): array
    {
        return [
            'statusWeight',
            'statusHistory',
            'subtasks.statusWeight',
            'subtasks.statusHistory',
            'subtasks.subtasks.statusWeight',
            'subtasks.subtasks.statusHistory',
        ];
    }

    private function isAncestorOf(Task $possibleAncestor, Task $task): bool
    {
        $parentTaskId = $task->parent_task_id;
        $visitedTaskIds = [];

        while ($parentTaskId !== null) {
            if (isset($visitedTaskIds[$parentTaskId])) {
                $this->failValidation('predecessor_id', 'The task hierarchy contains a circular relationship.');
            }

            if ((int) $possibleAncestor->getKey() === (int) $parentTaskId) {
                return true;
            }

            $visitedTaskIds[$parentTaskId] = true;
            $parentTaskId = Task::query()->whereKey($parentTaskId)->value('parent_task_id');
        }

        return false;
    }

    private function notifyAutomaticStatusChange(Task $parent, string $oldStatus, string $newStatus, int $actorId): void
    {
        $pivotRecipientIds = $parent->assignees()->pluck('users.id');
        $recipientIds = ($pivotRecipientIds->isNotEmpty()
            ? $pivotRecipientIds
            : collect([$parent->assignee_id]))
            ->push($parent->created_by)
            ->filter()
            ->unique()
            ->reject(fn ($recipientId): bool => (int) $recipientId === $actorId);
        $message = 'Status task utama "'.$parent->name.'" diperbarui otomatis dari '.str_replace('_', ' ', $oldStatus).' menjadi '.str_replace('_', ' ', $newStatus).'.';

        foreach ($recipientIds as $recipientId) {
            Notification::create([
                'user_id' => $recipientId,
                'type' => 'status_change',
                'title' => 'Status Task Utama Berubah',
                'message' => $message,
                'task_id' => $parent->id,
                'project_id' => $parent->project_id,
            ]);

            $recipient = User::query()->find($recipientId);
            if ($recipient !== null) {
                DB::afterCommit(function () use ($recipient, $parent, $message): void {
                    SendEmailNotification::dispatch(
                        $recipient,
                        'Status Task Utama Berubah',
                        $message,
                        route('tasks.show', $parent->token)
                    );
                });
            }
        }
    }

    private function failValidation(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
