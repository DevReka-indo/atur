<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatusWeight extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'status',
        'weight_value',
        'description',
    ];

    protected $casts = [
        'weight_value' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relationships

    /**
     * Tasks dengan status ini
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'status', 'status');
    }
}
