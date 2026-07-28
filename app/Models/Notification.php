<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    public const TYPE_WORKSPACE_CHAT_MENTION = 'workspace_chat_mention';

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'task_id',
        'project_id',
        'workspace_id',
        'workspace_chat_message_id',
        'url',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function workspaceChatMessage(): BelongsTo
    {
        return $this->belongsTo(WorkspaceChatMessage::class);
    }

    public function targetUrl(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->task_id && $this->task) {
            return route('tasks.show', $this->task->token);
        }

        if ($this->project_id && $this->project) {
            return route('projects.show', $this->project->token);
        }

        return null;
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
