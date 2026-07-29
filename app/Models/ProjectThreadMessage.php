<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectThreadMessage extends Model
{
    use HasFactory;

    protected $fillable = ['project_thread_id', 'user_id', 'content', 'edited_at'];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(ProjectThread::class, 'project_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(
            ProjectThreadMessageMention::class,
            'project_thread_message_id',
        );
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'project_thread_message_mentions',
        )->withTimestamps();
    }
}
