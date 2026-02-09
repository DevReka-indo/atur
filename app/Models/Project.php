<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships

    /**
     * Workspace yang memiliki project ini
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * User yang membuat project ini
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Members dari project ini
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps(); // SEKARANG BISA PAKAI INI
    }

    /**
     * Project members (pivot table)
     */
    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Tasks dalam project ini
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Tasks utama (tanpa parent)
     */
    public function mainTasks()
    {
        return $this->hasMany(Task::class)->whereNull('parent_task_id');
    }

    /**
     * Baselines dari project ini
     */
    public function baselines()
    {
        return $this->hasMany(ProjectBaseline::class);
    }

    /**
     * Active baseline
     */
    public function activeBaseline()
    {
        return $this->hasOne(ProjectBaseline::class)->where('is_active', true);
    }

    /**
     * Actual progress records
     */
    public function actualProgress()
    {
        return $this->hasMany(ActualProgress::class);
    }

    /**
     * Check apakah user adalah manager project
     */
    public function isManager(User $user)
    {
        return $this->members()
                    ->wherePivot('user_id', $user->id)
                    ->wherePivot('role', 'manager')
                    ->exists();
    }

    /**
     * Check apakah user adalah member project
     */
    public function isMember(User $user)
    {
        return $this->members()
                    ->wherePivot('user_id', $user->id)
                    ->exists();
    }


    public function roleForUser(User $user): ?string
    {
        $member = $this->members()
            ->wherePivot('user_id', $user->id)
            ->first();

        return $member?->pivot?->role;
    }

    public function canContribute(User $user): bool
    {
        return in_array($this->roleForUser($user), ['manager', 'member'], true);
    }

    public function isViewer(User $user): bool
    {
        return $this->roleForUser($user) === 'viewer';
    }

    /**
     * Calculate project progress berdasarkan task weights
     */
    public function calculateProgress()
    {
        $tasks = $this->tasks()->with('statusWeight')->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalWeight = $tasks->sum('weight');
        $totalEarnedValue = $tasks->sum(function ($task) {
            return $task->weight * ($task->statusWeight->weight_value ?? 0);
        });

        return $totalWeight > 0 ? ($totalEarnedValue / $totalWeight) * 100 : 0;
    }
}
