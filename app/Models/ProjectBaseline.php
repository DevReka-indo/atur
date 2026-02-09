<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectBaseline extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'baseline_name',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships

    /**
     * Project yang memiliki baseline ini
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User yang membuat baseline ini
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Planned progress dari baseline ini
     */
    public function plannedProgress()
    {
        return $this->hasMany(PlannedProgress::class, 'baseline_id');
    }

    /**
     * Actual progress dari baseline ini
     */
    public function actualProgress()
    {
        return $this->hasMany(ActualProgress::class, 'baseline_id');
    }
}
