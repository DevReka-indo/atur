<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships

    /**
     * User yang melakukan action ini
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get entity yang terkait (polymorphic)
     */
    public function entity()
    {
        $models = [
            'workspace' => Workspace::class,
            'project' => Project::class,
            'task' => Task::class,
            'comment' => TaskComment::class,
            'attachment' => TaskAttachment::class,
        ];

        $modelClass = $models[$this->entity_type] ?? null;

        if ($modelClass) {
            return $modelClass::find($this->entity_id);
        }

        return null;
    }
    /**
     * Get nama entity yang terkait
     */
    public function getEntityNameAttribute(): ?string
    {
        $models = [
            'workspace'  => Workspace::class,
            'project'    => Project::class,
            'task'       => Task::class,
            'comment'    => TaskComment::class,
            'attachment' => TaskAttachment::class,
        ];

        $modelClass = $models[$this->entity_type] ?? null;
        if (!$modelClass) return null;

        $entity = $modelClass::find($this->entity_id);
        if (!$entity) return null;

        return match ($this->entity_type) {
            'task'       => $entity->name,
            'project'    => $entity->name,
            'workspace'  => $entity->name,
            'comment'    => 'komentar di task #' . $entity->task_id,
            'attachment' => $entity->file_name,
            default      => null,
        };
    }
    /**
     * Scope untuk filter by entity type
     */
    public function scopeByEntityType($query, $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope untuk filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    public function getEntityUrlAttribute(): ?string
    {
        try {
            return match ($this->entity_type) {
                'task' => \App\Models\Task::find($this->entity_id)?->token
                    ? route('tasks.show', \App\Models\Task::find($this->entity_id)->token)
                    : null,
                'project' => \App\Models\Project::find($this->entity_id)?->token
                    ? route('projects.show', \App\Models\Project::find($this->entity_id)->token)
                    : null,
                'workspace' => \App\Models\Workspace::find($this->entity_id)?->token
                    ? route('workspaces.show', \App\Models\Workspace::find($this->entity_id)->token)
                    : null,
                'comment' => \App\Models\Task::find($this->entity_id)?->token
                    ? route('tasks.show', \App\Models\Task::find($this->entity_id)->token)
                    : null,
                'attachment' => \App\Models\Task::find(
                    \App\Models\TaskAttachment::find($this->entity_id)?->task_id
                )?->token
                    ? route('tasks.show', \App\Models\Task::find(
                        \App\Models\TaskAttachment::find($this->entity_id)?->task_id
                    )->token)
                    : null,
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }
}
