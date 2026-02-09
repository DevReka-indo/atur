<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActualProgress extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'baseline_id',
        'date',
        'actual_cumulative_percentage',
        'completed_tasks_count',
        'total_tasks_count',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'actual_cumulative_percentage' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relationships

    /**
     * Project yang memiliki actual progress ini
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Baseline yang direferensikan
     */
    public function baseline()
    {
        return $this->belongsTo(ProjectBaseline::class, 'baseline_id');
    }

    /**
     * User yang me-record progress ini
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get schedule variance (SV = actual - planned)
     */
    public function getScheduleVarianceAttribute()
    {
        $planned = PlannedProgress::where('baseline_id', $this->baseline_id)
                                  ->where('date', $this->date)
                                  ->first();

        if (!$planned) {
            return null;
        }

        return $this->actual_cumulative_percentage - $planned->planned_cumulative_percentage;
    }
}
