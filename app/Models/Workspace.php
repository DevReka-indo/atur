<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function workspaceMembers()
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function isAdmin(User $user)
    {
        return $this->isOwner($user) || $this->members()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', self::ROLE_ADMIN)
            ->exists();
    }

    public function isOwner(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id;
    }

    public function roleForUser(User $user): ?string
    {
        if ($this->isOwner($user)) {
            return self::ROLE_OWNER;
        }

        $member = $this->members()
            ->wherePivot('user_id', $user->id)
            ->first();

        return $member?->pivot?->role;
    }

    public function isMember(User $user)
    {
        return $this->members()
            ->wherePivot('user_id', $user->id)
            ->exists();
    }

    public function canManageMembers(User $user): bool
    {
        return in_array($this->roleForUser($user), [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    public function canManageSettings(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function canCreateProject(User $user): bool
    {
        return in_array($this->roleForUser($user), [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }
}
