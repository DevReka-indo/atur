<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class ProjectTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_template_category_id',
        'name',
        'slug',
        'description',
        'version',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'version' => 1,
        'is_active' => false,
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateCategory::class, 'project_template_category_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class)
            ->orderBy('position')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function rootTasks(): HasMany
    {
        return $this->tasks()->whereNull('parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function isEffectivelyActive(): bool
    {
        return $this->is_active
            && ! $this->trashed()
            && $this->category !== null
            && $this->category->isEffectivelyActive();
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>|null  $tasks
     */
    public function totalLeafWeight(?Collection $tasks = null): float
    {
        $tasks ??= $this->tasks()->get();
        $parentIds = $tasks->pluck('parent_id')->filter()->map(fn ($id): int => (int) $id)->all();

        return round((float) $tasks
            ->reject(fn (ProjectTemplateTask $task): bool => in_array($task->id, $parentIds, true))
            ->sum('weight'), 2);
    }
}
