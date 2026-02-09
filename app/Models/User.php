<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'job_title',
        'department',
        'is_active',
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
        ];
    }

    // Relationships

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
}
