<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailNotification;
use App\Models\DeviceUser;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // CATAT DEVICE SETELAH LOGIN
        $this->afterLogin();
        $this->sendDeadlineNotificationsPublic($user);
        $this->sendOverloadNotificationsOnLogin($user);

        $allProjects = $user->projects()->with('tasks.statusWeight')->get();
        $stats = [
            'total_workspaces' => $user->workspaces()->count(),
            'total_projects' => $user->projects()->count(),
            'assigned_tasks' => Task::assignedToUser($user->id)->count(),
            'completed_tasks' => $allProjects->filter(function ($project) {
                $totalWeight = $project->tasks->sum('weight');
                $earnedValue = $project->tasks->sum(
                    fn ($task) => $task->weight * ($task->statusWeight->weight_value ?? 0)
                );
                $progress = $totalWeight > 0 ? ($earnedValue / $totalWeight) * 100 : 0;

                return $progress >= 100;
            })->count(),
        ];

        $projectIds = $user->projects()->pluck('projects.id');

        $recentTasks = Task::query()
            ->with(['project', 'statusWeight'])
            ->where(function ($query) use ($user, $projectIds) {
                $query->assignedToUser($user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereIn('project_id', $projectIds);
            })
            ->latest()
            ->take(5)
            ->get();

        $activeProjects = $user->projects()
            ->where('status', 'active')
            ->with(['workspace'])
            ->withCount('tasks')
            ->take(5)
            ->get();

        $allProjects = $user->projects()->with('tasks.statusWeight')->get();

        $projectStats = [];
        foreach ($allProjects as $project) {
            $status = $project->status;

            // Urgent yang progress 100% → tidak dihitung sebagai urgent
            if ($status === 'urgent') {
                $totalWeight = $project->tasks->sum('weight');
                $earnedValue = $project->tasks->sum(
                    fn ($task) => $task->weight * ($task->statusWeight->weight_value ?? 0)
                );
                $progress = $totalWeight > 0 ? ($earnedValue / $totalWeight) * 100 : 0;

                if ($progress >= 100) {
                    $status = 'completed'; // pindahkan ke completed
                }
            }

            $projectStats[$status] = ($projectStats[$status] ?? 0) + 1;
        }

        $deadlineTasks = Task::where(function ($q) use ($user, $projectIds) {
            $q->assignedToUser($user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereIn('project_id', $projectIds);
        })
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(3))
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->get();

        $isSuperAdmin = $user->isSuperAdmin();

        $overloadedMembers = collect();
        $projects = $isSuperAdmin
            ? \App\Models\Project::with('members')->get()
            : $user->projects()->with('members')->get();

        foreach ($projects as $project) {
            if (! $isSuperAdmin && ! $project->members->contains('id', $user->id)) {
                continue;
            }

            foreach ($project->members as $member) {
                $count = Task::where('project_id', $project->id)
                    ->assignedToUser($member->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count();

                if ($count >= 5) {
                    $overloadedMembers->push([
                        'name' => $member->name,
                        'project' => $project->name,
                        'task_count' => $count,
                        'project_token' => $project->token,
                        'project_id' => $project->id,
                    ]);
                }
            }
        }

        return view('dashboard.index', compact('stats', 'recentTasks', 'activeProjects', 'projectStats', 'deadlineTasks', 'overloadedMembers'));
    }

    public function activityLog(Request $request)
    {
        $user = Auth::user();

        $projectIds = $user->projects()->pluck('projects.id');
        $workspaceIds = $user->workspaces()->pluck('workspaces.id');

        $query = \App\Models\ActivityLog::with('user')
            ->where(function ($q) use ($user, $projectIds, $workspaceIds) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($q2) use ($projectIds) {
                        $q2->where('entity_type', 'task')
                            ->whereIn('entity_id', function ($sub) use ($projectIds) {
                                $sub->select('id')->from('tasks')->whereIn('project_id', $projectIds);
                            });
                    })
                    ->orWhere(function ($q2) use ($projectIds) {
                        $q2->where('entity_type', 'project')
                            ->whereIn('entity_id', $projectIds);
                    })
                    ->orWhere(function ($q2) use ($workspaceIds) {
                        $q2->where('entity_type', 'workspace')
                            ->whereIn('entity_id', $workspaceIds);
                    })
                    ->orWhere(function ($q2) use ($projectIds) {
                        $q2->where('entity_type', 'discussion')
                            ->whereIn('entity_id', function ($sub) use ($projectIds) {
                                $sub->select('id')->from('project_threads')->whereIn('project_id', $projectIds);
                            });
                    });
            });

        if ($request->filled('type')) {
            $query->where('entity_type', $request->type);
        }

        $todayCount = (clone $query)->whereDate('created_at', today())->count();

        $activities = $query->latest()->paginate(20);

        return view('activity.index', compact('activities', 'todayCount'));
    }

    // LIVE SEARCH
    public function live(Request $request)
    {
        $query = trim($request->input('q'));
        $user = Auth::user();

        if (empty($query)) {
            return response()->json([
                'workspaces' => [],
                'projects' => [],
                'tasks' => [],
            ]);
        }

        $workspaces = \App\Models\Workspace::whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'token')
            ->limit(5)
            ->get();

        $projects = Project::whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'token')
            ->limit(5)
            ->get();

        $tasks = Task::assignedToUser($user->id)
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'token')
            ->limit(5)
            ->get();

        return response()->json([
            'workspaces' => $workspaces,
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }

    // SWITCH ACCOUNT
    public function switchAccount($id)
    {
        abort_if(Auth::id() == $id, 403);

        // CATAT LAST ACTIVITY AKUN YANG DITINGGALKAN
        if (Auth::check()) {
            Auth::user()->update([
                'last_activity' => now(),
            ]);
        }

        $deviceId = $this->getDeviceId();

        $allowed = DeviceUser::where('device_id', $deviceId)
            ->where('user_id', $id)
            ->exists();

        abort_if(! $allowed, 403);

        Auth::logout();
        Auth::loginUsingId($id);

        request()->session()->regenerate();

        return redirect('/dashboard');
    }

    // hapus akun device
    public function removeAccountFromDevice($id)
    {
        // tidak boleh hapus akun yang sedang aktif
        abort_if(Auth::id() == $id, 403);

        $deviceId = request()->cookie('device_id');

        DeviceUser::where('device_id', $deviceId)
            ->where('user_id', $id)
            ->delete();

        return back()->with('success', 'Akun berhasil dihapus dari device ini');
    }

    // DEVICE ID
    private function getDeviceId()
    {
        if (! request()->cookie('device_id')) {
            $deviceId = Str::uuid()->toString();
            cookie()->queue('device_id', $deviceId, 60 * 24 * 365);

            return $deviceId;
        }

        return request()->cookie('device_id');
    }

    // AFTER LOGIN
    public function afterLogin()
    {
        $deviceId = $this->getDeviceId();

        DeviceUser::firstOrCreate([
            'device_id' => $deviceId,
            'user_id' => Auth::id(),
        ]);
    }

    // notifikasi
    public function notifications()
    {
        $userId = Auth::id();

        $notifications = Notification::where('user_id', $userId)
            ->with(['task', 'project'])
            ->orderByRaw('read_at IS NOT NULL ASC')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($notif) {
                if ($notif->task_id && ! $notif->task) {
                    $notif->delete();

                    return false;
                }
                if ($notif->project_id && ! $notif->project) {
                    $notif->delete();

                    return false;
                }

                return true;
            });

        $deadlineTasks = Task::where(function ($q) use ($userId) {
            $q->assignedToUser($userId)
                ->orWhere('created_by', $userId);
        })
            ->with(['project'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(3))
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->get();

        $urgentTasks = Task::where('priority', 'urgent')
            ->whereNotIn('status', ['completed', 'stopped', 'cancelled'])
            ->where(function ($query) use ($userId) {
                $query->assignedToUser($userId)
                    ->orWhere('created_by', $userId);
            })
            ->with('project')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $urgentProjects = Project::where('status', 'urgent')
            ->whereHas('members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with('workspace')
            ->get();

        $unreadCount = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return view('notifications.index', compact(
            'notifications',
            'deadlineTasks',
            'unreadCount',
            'urgentTasks',
            'urgentProjects',
        ));
    }

    // Tandai 1 notifikasi dibaca
    public function markAsRead($id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);

        return back()->with('success', 'Notifikasi ditandai dibaca.');
    }

    // Tandai semua dibaca
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    public function poll()
    {
        $userId = Auth::id();

        $unreadCount = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $priorityNotifs = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->whereIn('type', ['member_overload', 'deadline_warning', 'urgent_task'])
            ->latest()
            ->take(5)
            ->get();

        $otherNotifs = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->whereNotIn('type', ['member_overload', 'deadline_warning', 'urgent_task'])
            ->latest()
            ->take(5)
            ->get();

        $latest = $priorityNotifs->merge($otherNotifs)
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'task_id' => $n->task_id,
                    'project_id' => $n->project_id,
                    'time' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $latest,
        ]);
    }

    public function destroy($id)
    {
        $notif = Notification::where('id', $id)->where('user_id', auth::id())->firstOrFail();
        $notif->delete();

        return back()->with('success', 'Notifikasi dihapus.');
    }

    public function account()
    {
        $deviceId = $this->getDeviceId();

        $users = User::whereIn('id', function ($query) use ($deviceId) {
            $query->select('user_id')
                ->from('device_users')
                ->where('device_id', $deviceId);
        })->get();

        return view('settings.account', compact('users'));
    }

    public function about()
    {
        return view('settings.about');
    }

    // overload
    public function overloadList()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $overloadedMembers = collect();

        $projects = $isSuperAdmin
            ? \App\Models\Project::with('members')->get()
            : $user->projects()->with('members')->get();

        foreach ($projects as $project) {
            if (! $isSuperAdmin && ! $project->members->contains('id', $user->id)) {
                continue;
            }

            foreach ($project->members as $member) {
                $tasks = Task::where('project_id', $project->id)
                    ->assignedToUser($member->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->get()
                    ->map(fn ($t) => [
                        'title' => $t->name,
                        'status' => $t->status,
                        'due_date' => $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M Y') : null,
                        'token' => $t->token,
                        'days_until_due' => $t->due_date ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($t->due_date)->startOfDay(), false) : null,
                    ])->toArray();

                $count = count($tasks);

                if ($count >= 5) {
                    $overloadedMembers->push([
                        'name' => $member->name,
                        'initial' => strtoupper(substr($member->name, 0, 1)),
                        'project' => $project->name,
                        'task_count' => $count,
                        'project_token' => $project->token,
                        'tasks' => $tasks,
                    ]);
                }
            }
        }

        if ($overloadedMembers->isNotEmpty()) {
            $this->sendOverloadNotifications($overloadedMembers, $user);
        }

        return view('overload.index', compact('overloadedMembers', 'isSuperAdmin'));
    }

    private function sendOverloadNotifications($overloadedMembers, $currentUser)
    {
        $isSuperAdmin = $currentUser->isSuperAdmin();

        if ($isSuperAdmin) {
            if (cache()->has("overload_sent_{$currentUser->id}")) {
                return;
            }

            $overloadCount = $overloadedMembers->count();

            Notification::where('user_id', $currentUser->id)
                ->where('type', 'member_overload')
                ->delete();

            Notification::create([
                'user_id' => $currentUser->id,
                'type' => 'member_overload',
                'title' => 'Member Overload Detected',
                'message' => $overloadCount.' member(s) are overloaded across all projects! Please redistribute the tasks immediately.',
                'task_id' => null,
                'project_id' => null,
                'read_at' => null,
            ]);

            SendEmailNotification::dispatch(
                $currentUser,
                'Member Overload Detected',
                $overloadCount.' member(s) are overloaded across all projects! Please redistribute the tasks immediately.',
                route('overload.index'),
            );

            cache()->forever("overload_sent_{$currentUser->id}", true);
        } else {
            $projectGroups = $overloadedMembers->groupBy('project_token');

            foreach ($projectGroups as $projectToken => $members) {
                $project = \App\Models\Project::with('members')
                    ->where('token', $projectToken)
                    ->first();

                if (! $project) {
                    continue;
                }

                $overloadCount = $members->count();

                foreach ($project->members as $recipient) {
                    if (cache()->has("overload_sent_{$recipient->id}_{$project->id}")) {
                        continue;
                    }

                    Notification::where('user_id', $recipient->id)
                        ->where('type', 'member_overload')
                        ->where('project_id', $project->id)
                        ->delete();

                    Notification::create([
                        'user_id' => $recipient->id,
                        'type' => 'member_overload',
                        'title' => 'Member Overload Detected',
                        'message' => $overloadCount.' member(s) are overloaded in project '.$project->name.'! Please redistribute the tasks immediately.',
                        'task_id' => null,
                        'project_id' => $project->id,
                        'read_at' => null,
                    ]);

                    SendEmailNotification::dispatch(
                        $recipient,
                        'Member Overload Detected',
                        $overloadCount.' member(s) are overloaded in project '.$project->name.'! Please redistribute the tasks immediately.',
                        route('overload.index'),
                    );

                    cache()->forever("overload_sent_{$recipient->id}_{$project->id}", true);
                }
            }
        }
    }

    public function sendDeadlineNotificationsPublic($user)
    {
        if (cache()->has("deadline_sent_{$user->id}")) {
            return;
        }

        Notification::where('user_id', $user->id)
            ->where('type', 'deadline_warning')
            ->delete();

        $deadlineCount = Task::assignedToUser($user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(5)->toDateString())
            ->count();

        if ($deadlineCount > 0) {
            SendEmailNotification::dispatch(
                $user,
                'Upcoming Deadline',
                'You have '.$deadlineCount.' task(s) approaching or past the deadline! Please complete them immediately.',
                route('overload.index'),
            );
        }

        $urgentTasks = Task::assignedToUser($user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('priority', 'urgent')
            ->get();

        if ($urgentTasks->count() > 0) {
            Notification::where('user_id', $user->id)
                ->where('type', 'urgent_task')
                ->delete();

            Notification::create([
                'user_id' => $user->id,
                'type' => 'urgent_task',
                'title' => 'Urgent Task Alert',
                'message' => 'You have '.$urgentTasks->count().' urgent task(s) that need immediate attention!',
                'task_id' => null,
                'project_id' => null,
                'read_at' => null,
            ]);

            foreach ($urgentTasks as $task) {
                SendEmailNotification::dispatch(
                    $user,
                    'Urgent Task Alert',
                    'Task "'.$task->name.'" needs immediate attention!',
                    route('tasks.show', $task->token),
                );
            }
        }

        cache()->forever("deadline_sent_{$user->id}", true);
    }

    public function sendOverloadNotificationsOnLogin($user)
    {
        if (cache()->has("overload_sent_{$user->id}")) {
            return;
        }

        $isSuperAdmin = $user->isSuperAdmin();

        $projects = $isSuperAdmin
            ? \App\Models\Project::with('members')->get()
            : $user->projects()->with('members')->get();

        $overloadedMembers = collect();

        foreach ($projects as $project) {
            if (! $isSuperAdmin && ! $project->members->contains('id', $user->id)) {
                continue;
            }
            foreach ($project->members as $member) {
                $count = Task::where('project_id', $project->id)
                    ->assignedToUser($member->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count();

                if ($count >= 5) {
                    $overloadedMembers->push([
                        'name' => $member->name,
                        'project' => $project->name,
                        'task_count' => $count,
                        'project_token' => $project->token,
                        'project_id' => $project->id,
                    ]);
                }
            }
        }

        if ($overloadedMembers->isEmpty()) {
            Notification::where('user_id', $user->id)
                ->where('type', 'member_overload')
                ->delete();

            return;
        }

        $this->sendOverloadNotifications($overloadedMembers, $user);

        cache()->forever("overload_sent_{$user->id}", true);
    }
}
