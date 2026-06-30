<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Project;
use App\Models\WorkspaceMember;

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
        'invite_token',
        'token',
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

    public function generateInviteToken(): string
    {
        $token = Str::random(32);
        $this->update(['invite_token' => $token]);
        return $token;
    }

    public function resetInviteToken(): string
    {
        return $this->generateInviteToken();
    }

    public function getInviteLinkAttribute(): ?string
    {
        if (!$this->invite_token) return null;
        return route('workspaces.invite.join', $this->invite_token);
    }

    protected static function booted(): void
    {
        static::creating(function ($workspace) {
            $workspace->token = Str::random(32);
        });
    }
}
