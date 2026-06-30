<?php
// app/Models/Project.php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Notification;
use App\Models\ProjectThread;

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
        'token',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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
            ->withTimestamps();
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
        return $this->hasMany(Task::class)
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc');
    }


    /**
     * Tasks utama (tanpa parent)
     */
    public function mainTasks()
    {
        return $this->hasMany(Task::class)
            ->whereNull('parent_task_id')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc');
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
        if ($user->isSuperAdmin()) return true;
        return $this->members()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'manager')
            ->exists();
    }

    /**
     * Check apakah user adalah member project (role apapun)
     */
    public function isMember(User $user)
    {
        if ($user->isSuperAdmin()) return true;
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
        if ($user->isSuperAdmin()) return true;
        return in_array($this->roleForUser($user), ['manager', 'member'], true);
    }


    public function canCreateThread(User $user): bool
    {
        return $this->canContribute($user);
    }

    public function isViewer(User $user): bool
    {
        return $this->roleForUser($user) === 'viewer';
    }

    public function calculateProgress()
    {
        $tasks = $this->tasks()
            ->with(['statusWeight', 'statusHistory'])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        if ($tasks->isEmpty()) return 0;

        $totalWeight = $tasks->sum('weight');
        $totalEarnedValue = $tasks->sum(function ($task) {
            if ($task->status === 'stopped') {

                $prevStatus = $task->statusHistory
                    ->sortByDesc('id')
                    ->firstWhere('from_status', '!=', null)?->from_status ?? 'to_do';

                $weightValue = \Illuminate\Support\Facades\DB::table('task_status_weights')
                    ->where('status', $prevStatus)
                    ->value('weight_value') ?? 0;

                return $task->weight * $weightValue;
            }

            return $task->weight * ($task->statusWeight->weight_value ?? 0);
        });

        return $totalWeight > 0 ? ($totalEarnedValue / $totalWeight) * 100 : 0;
    }

    protected static function booted(): void
    {
        static::creating(function ($project) {
            $project->token = Str::random(32);
        });

        static::deleted(function (Project $project) {
            Notification::where('project_id', $project->id)->delete();
        });
    }

    /**
     * Threads/topics dalam project ini
     */
    public function threads()
    {
        return $this->hasMany(ProjectThread::class);
    }

    public function getInitialColor(): string
    {
        $colors = [
            'bg-blue-100 text-blue-700',
            'bg-green-100 text-green-700',
            'bg-purple-100 text-purple-700',
            'bg-yellow-100 text-yellow-700',
            'bg-red-100 text-red-700',
            'bg-pink-100 text-pink-700',
        ];

        return $colors[crc32($this->name) % count($colors)];
    }
}
