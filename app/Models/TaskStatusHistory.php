<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatusHistory extends Model
{
    use HasFactory;

    /**
     * Explicit table name because migration uses singular table name.
     */
    protected $table = 'task_status_history';

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // Relationships

    /**
     * Task yang memiliki history ini
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * User yang mengubah status
     */
    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
