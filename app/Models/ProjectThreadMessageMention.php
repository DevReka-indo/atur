<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectThreadMessageMention extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_thread_message_id',
        'user_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            ProjectThreadMessage::class,
            'project_thread_message_id',
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
