<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectThread extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'user_id', 'title', 'body'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(ProjectThreadMessage::class);
    }

    // ← tambah ini
    public function userReads()
    {
        return $this->hasMany(ThreadUserRead::class, 'thread_id');
    }
}
