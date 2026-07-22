<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectTemplateTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_template_id',
        'parent_id',
        'name',
        'description',
        'priority',
        'weight',
        'position',
        'start_offset_days',
        'duration_days',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'position' => 0,
        'start_offset_days' => 0,
        'duration_days' => 1,
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'position' => 'integer',
            'start_offset_days' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('position')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function dependency(): HasOne
    {
        return $this->hasOne(ProjectTemplateTaskDependency::class, 'project_template_task_id');
    }

    public function dependentDependencies(): HasMany
    {
        return $this->hasMany(ProjectTemplateTaskDependency::class, 'predecessor_template_task_id');
    }

    public function isLeaf(): bool
    {
        return $this->relationLoaded('children')
            ? $this->children->isEmpty()
            : ! $this->children()->exists();
    }
}
