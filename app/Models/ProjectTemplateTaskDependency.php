<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplateTaskDependency extends Model
{
    protected $fillable = [
        'project_template_id',
        'project_template_task_id',
        'predecessor_template_task_id',
        'dependency_type',
        'lag_days',
    ];

    protected $attributes = [
        'dependency_type' => 'FS',
        'lag_days' => 0,
    ];

    protected function casts(): array
    {
        return ['lag_days' => 'integer'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateTask::class, 'project_template_task_id');
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateTask::class, 'predecessor_template_task_id');
    }
}
