<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceMember extends Model
{
    use HasFactory;

    // public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    // Relationships

    /**
     * Workspace yang dimiliki member ini
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * User dari member ini
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
