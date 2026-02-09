<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

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
}
