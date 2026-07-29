<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailNotification;
use App\Models\DeviceUser;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\NotificationPresentationService;
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

        return view('dashboard.index', compact('stats', 'recentTasks', 'activeProjects', 'projectStats', 'deadlineTasks'));
    }

    public function activityLog(Request $request)
    {
        $user = Auth::user();

        $projectIds = $user->projects()->pluck('projects.id');
        $workspaceIds = $user->workspaces()->pluck('workspaces.id')
            ->merge($user->createdWorkspaces()->pluck('workspaces.id'))
            ->unique()
            ->values();
        $managedWorkspaceIds = Workspace::query()
            ->whereIn('id', $workspaceIds)
            ->where(function ($query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user): void {
                        $memberQuery
                            ->where('users.id', $user->id)
                            ->where('workspace_members.role', Workspace::ROLE_ADMIN);
                    });
            })
            ->pluck('id');

        $query = \App\Models\ActivityLog::with('user')
            ->where(function ($q) use ($user, $projectIds, $workspaceIds) {
                $q->where(function ($ownActivity) use ($user): void {
                    $ownActivity
                        ->where('user_id', $user->id)
                        ->where('entity_type', '!=', 'workspace');
                })
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

        return view('activity.index', compact('activities', 'todayCount', 'managedWorkspaceIds'));
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
    public function notifications(
        Request $request,
        NotificationPresentationService $notificationPresentation,
    ) {
        $userId = (int) Auth::id();
        $filter = $notificationPresentation->normalizeFilter($request->query('filter'));

        $notificationQuery = Notification::query()
            ->where('user_id', $userId)
            ->with([
                'task:id,project_id,token,name',
                'task.project:id,name,token',
                'project:id,name,token',
                'workspace:id,name,token',
            ])
            ->orderByRaw('read_at IS NOT NULL ASC')
            ->latest();
        $notifications = $notificationPresentation
            ->applyFilter($notificationQuery, $filter)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Notification $notification): array => [
                'notification' => $notification,
                'presentation' => $notificationPresentation->forNotification($notification),
            ]);

        $deadlineTasks = Task::where(function ($q) use ($userId) {
            $q->assignedToUser($userId)
                ->orWhere('created_by', $userId);
        })
            ->with(['project'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(3))
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->limit(8)
            ->get();
        $deadlineItems = $deadlineTasks->map(
            fn (Task $task): array => [
                'task' => $task,
                'presentation' => $notificationPresentation->forDeadline($task),
            ],
        );
        $filterCounts = $notificationPresentation->filterCounts($userId);
        $unreadCount = $filterCounts[NotificationPresentationService::FILTER_UNREAD];
        $filterLabels = NotificationPresentationService::FILTER_LABELS;

        return view('notifications.index', compact(
            'notifications',
            'deadlineItems',
            'filter',
            'filterCounts',
            'filterLabels',
            'unreadCount',
        ));
    }

    // Tandai 1 notifikasi dibaca
    public function markAsRead($id)
    {
        $notification = Notification::query()
            ->whereKey($id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

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
                    'workspace_id' => $n->workspace_id,
                    'workspace_chat_message_id' => $n->workspace_chat_message_id,
                    'project_thread_id' => $n->project_thread_id,
                    'project_thread_message_id' => $n->project_thread_message_id,
                    'url' => $n->targetUrl(),
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

    public function destroySelected(Request $request)
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1', 'max:100'],
            'notification_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $deletedCount = Notification::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['notification_ids'])
            ->delete();

        return back()->with(
            'success',
            "{$deletedCount} notifikasi dipilih berhasil dihapus.",
        );
    }

    public function openNotification($id)
    {
        $notification = Notification::query()
            ->whereKey($id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return redirect()->to(
            $notification->targetUrl() ?? route('notifications.index'),
        );
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
        return view('about.index');
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
}
