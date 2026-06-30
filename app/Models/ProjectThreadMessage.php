<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectThreadMessage extends Model
{
    use HasFactory;

    protected $fillable = ['project_thread_id', 'user_id', 'content'];

    public function thread()
    {
        return $this->belongsTo(ProjectThread::class, 'project_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
