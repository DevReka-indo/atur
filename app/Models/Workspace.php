<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    // Relationships

    /**
     * User yang membuat workspace ini
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Members dari workspace ini
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'workspace_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps(); // SEKARANG BISA PAKAI INI
    }

    /**
     * Workspace members (pivot table)
     */
    public function workspaceMembers()
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * Projects dalam workspace ini
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Check apakah user adalah admin workspace
     */
    public function isAdmin(User $user)
    {
        return $this->members()
                    ->wherePivot('user_id', $user->id)
                    ->wherePivot('role', 'admin')
                    ->exists();
    }

    /**
     * Check apakah user adalah member workspace
     */
    public function isMember(User $user)
    {
        return $this->members()
                    ->wherePivot('user_id', $user->id)
                    ->exists();
    }
}
