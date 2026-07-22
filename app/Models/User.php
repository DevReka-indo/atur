<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'has_password',
        'role',
        'profile_photo',
        'job_title',
        'department',
        'is_active',
        'google_id',
        'sso_id',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_activity' => 'datetime',
        ];
    }

    // Relationships
    public function isActive()
    {
        return $this->is_active == 1;
    }

    /**
     * Workspaces yang dibuat oleh user ini
     */
    public function createdWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    /**
     * Workspaces dimana user ini menjadi member
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps(); // SEKARANG BISA PAKAI INI
    }

    /**
     * Projects yang dibuat oleh user ini
     */
    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasCompatibleGlobalRole('super_admin');
    }

    public function isContributor(): bool
    {
        return $this->hasCompatibleGlobalRole('contributor');
    }

    public function isMember(): bool
    {
        return $this->hasCompatibleGlobalRole('member');
    }

    public function isPermissionSystemReady(): bool
    {
        return Schema::hasTable(config('permission.table_names.roles', 'roles'))
            && Schema::hasTable(config('permission.table_names.permissions', 'permissions'));
    }

    /**
     * Projects dimana user ini menjadi member
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps(); // SEKARANG BISA PAKAI INI
    }

    /**
     * Tasks yang dibuat oleh user ini
     */
    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Tasks yang di-assign ke user ini
     */
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * Comments yang dibuat oleh user ini
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'user_id');
    }

    /**
     * Attachments yang di-upload oleh user ini
     */
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'uploaded_by');
    }

    /**
     * Activity logs dari user ini
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * Baselines yang dibuat oleh user ini
     */
    public function createdBaselines()
    {
        return $this->hasMany(ProjectBaseline::class, 'created_by');
    }

    /**
     * Actual progress yang di-record oleh user ini
     */
    public function recordedProgress()
    {
        return $this->hasMany(ActualProgress::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function getChatTheme(): array
    {
        $themes = [
            [
                'avatar' => 'bg-blue-100 text-blue-700',
                'avatar_border' => 'border-blue-200',
                'bubble' => 'bg-blue-50 text-gray-800',
                'bubble_border' => 'border-blue-100',
            ],
            [
                'avatar' => 'bg-green-100 text-green-700',
                'avatar_border' => 'border-green-200',
                'bubble' => 'bg-green-50 text-gray-800',
                'bubble_border' => 'border-green-100',
            ],
            [
                'avatar' => 'bg-purple-100 text-purple-700',
                'avatar_border' => 'border-purple-200',
                'bubble' => 'bg-purple-50 text-gray-800',
                'bubble_border' => 'border-purple-100',
            ],
            [
                'avatar' => 'bg-yellow-100 text-yellow-700',
                'avatar_border' => 'border-yellow-200',
                'bubble' => 'bg-yellow-50 text-gray-800',
                'bubble_border' => 'border-yellow-100',
            ],
            [
                'avatar' => 'bg-pink-100 text-pink-700',
                'avatar_border' => 'border-pink-200',
                'bubble' => 'bg-pink-50 text-gray-800',
                'bubble_border' => 'border-pink-100',
            ],
            [
                'avatar' => 'bg-orange-100 text-orange-700',
                'avatar_border' => 'border-orange-200',
                'bubble' => 'bg-orange-50 text-gray-800',
                'bubble_border' => 'border-orange-100',
            ],
        ];

        $theme = $themes[$this->id % count($themes)];
        $theme['initial'] = strtoupper(substr($this->name, 0, 1));

        return $theme;
    }

    private function hasCompatibleGlobalRole(string $role): bool
    {
        if (! $this->isPermissionSystemReady()) {
            return $this->role === $role;
        }

        if ($this->hasRole($role)) {
            return true;
        }

        return $this->roles->isEmpty() && $this->role === $role;
    }
}
