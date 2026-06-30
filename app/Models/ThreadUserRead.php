<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadUserRead extends Model
{
    protected $fillable = ['thread_id', 'user_id', 'last_read_at'];
    protected $casts = ['last_read_at' => 'datetime'];

    public function thread()
    {
        return $this->belongsTo(ProjectThread::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
