<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlannedProgress extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'baseline_id',
        'date',
        'planned_cumulative_percentage',
    ];

    protected $casts = [
        'date' => 'date',
        'planned_cumulative_percentage' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relationships

    /**
     * Baseline yang memiliki planned progress ini
     */
    public function baseline()
    {
        return $this->belongsTo(ProjectBaseline::class, 'baseline_id');
    }
}
