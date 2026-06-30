<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    use HasFactory;

    // public $timestamps = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    // Relationships

    /**
     * Project yang dimiliki member ini
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User dari member ini
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
