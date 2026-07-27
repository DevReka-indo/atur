<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    public const INVITABLE_ROLE_LABELS = [
        self::ROLE_ADMIN => 'Workspace Admin',
        self::ROLE_MEMBER => 'Workspace Member',
    ];

    public const ROLE_LABELS = [
        self::ROLE_OWNER => 'Workspace Owner',
        self::ROLE_ADMIN => 'Workspace Admin',
        self::ROLE_MEMBER => 'Workspace Member',
    ];

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'invite_token',
        'invite_token_expires_at',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'invite_token_expires_at' => 'datetime',
        ];
    }

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

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invitable_id')
            ->where('type', 'workspace');
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
        $token = Str::random(64);
        $this->update([
            'invite_token' => $token,
            'invite_token_expires_at' => now()->addDays(7),
        ]);

        return $token;
    }

    public function resetInviteToken(): string
    {
        return $this->generateInviteToken();
    }

    public function getInviteLinkAttribute(): ?string
    {
        if (! $this->hasActiveInviteLink()) {
            return null;
        }

        return route('workspaces.invite.join', $this->invite_token);
    }

    public function hasActiveInviteLink(): bool
    {
        return filled($this->invite_token)
            && $this->invite_token_expires_at?->isFuture() === true;
    }

    public function revokeInviteToken(): void
    {
        $this->update([
            'invite_token' => null,
            'invite_token_expires_at' => null,
        ]);
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? $role;
    }

    protected static function booted(): void
    {
        static::creating(function ($workspace) {
            $workspace->token = Str::random(32);
        });
    }
}
