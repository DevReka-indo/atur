<?php

namespace App\Models;

use App\Services\TaskHierarchyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parent_task_id',
        'name',
        'description',
        'assignee_id',
        'status',
        'priority',
        'weight',
        'subtask_weight_percentage',
        'start_date',
        'due_date',
        'position',
        'completed_at',
        'stopped_progress',
        'created_by',
        'token',
        'predecessor_id',
        'dependency_type',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'subtask_weight_percentage' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function getEarnedValueAttribute(): float
    {
        return $this->projectEarnedValue(app(TaskHierarchyService::class));
    }

    public function projectEarnedValue(TaskHierarchyService $taskHierarchyService): float
    {
        return $taskHierarchyService->resolveEarnedContribution($this);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function predecessor()
    {
        return $this->belongsTo(Task::class, 'predecessor_id');
    }

    // public function children()
    // {
    //     return $this->hasMany(Task::class, 'parent_task_id');
    // }

    public function statusWeight()
    {
        return $this->belongsTo(TaskStatusWeight::class, 'status', 'status');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function isOverdue()
    {
        return $this->due_date &&
            $this->status !== 'completed' &&
            $this->due_date->isPast();
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope untuk filter by assignee
     */
    public function scopeAssignedToUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $assignmentQuery) use ($userId): void {
            $assignmentQuery
                ->whereHas('assignees', function (Builder $assigneeQuery) use ($userId): void {
                    $assigneeQuery->where('users.id', $userId);
                })
                ->orWhere(function (Builder $legacyAssignmentQuery) use ($userId): void {
                    $legacyAssignmentQuery
                        ->whereDoesntHave('assignees')
                        ->where('assignee_id', $userId);
                });
        });
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->assignedToUser($userId);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($task) {
            $task->token = Str::random(32);
        });
        static::deleted(function (Task $task) {
            Notification::where('task_id', $task->id)->delete();
        });
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
    }
}
