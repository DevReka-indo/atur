<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskBaseline extends Model
{
    protected $fillable = [
        'project_baseline_id',
        'task_id',
        'baseline_start',
        'baseline_end',
    ];

    protected $casts = [
        'baseline_start' => 'date',
        'baseline_end'   => 'date',
    ];

    public function projectBaseline()
    {
        return $this->belongsTo(ProjectBaseline::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
