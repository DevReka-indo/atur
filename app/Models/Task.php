<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'start_date',
        'due_date',
        'position',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // Relationships

    /**
     * Project yang memiliki task ini
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Parent task (untuk subtask)
     */
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Subtasks dari task ini
     */
    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * User yang di-assign untuk task ini
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * User yang membuat task ini
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Status weight untuk task ini
     */
    public function statusWeight()
    {
        return $this->belongsTo(TaskStatusWeight::class, 'status', 'status');
    }

    /**
     * Status history dari task ini
     */
    public function statusHistory()
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    /**
     * Comments pada task ini
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Attachments pada task ini
     */
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Calculate earned value dari task ini
     */
    public function getEarnedValueAttribute()
    {
        $statusWeight = $this->statusWeight;
        return $this->weight * ($statusWeight ? $statusWeight->weight_value : 0);
    }

    /**
     * Check apakah task sudah overdue
     */
    public function isOverdue()
    {
        return $this->due_date &&
               $this->due_date < now() &&
               $this->status !== 'completed';
    }

    /**
     * Scope untuk filter by status
     */
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
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }
}
