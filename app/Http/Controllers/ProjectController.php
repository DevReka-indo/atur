<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Models\Notification;
use App\Services\TaskHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProjectProgressService;
use App\Models\User;
use App\Models\ActivityLog;
use App\Jobs\SendEmailNotification;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function __construct(private TaskHierarchyService $taskHierarchyService) {}

    // Resolve project by token (helper internal)
    private function findByToken(string $token): Project
    {
        return Project::where('token', $token)->firstOrFail();
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        app(DashboardController::class)->sendDeadlineNotificationsPublic($user);
        $status = $request->get('status', 'all');
        $view   = $request->get('view', 'list');

        $query = Project::withCount('tasks')
            ->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $projects = $query->orderByRaw("CASE WHEN status = 'urgent' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return view('projects.index', compact('projects', 'view'));
    }

    public function create()
    {
        $workspaces = Auth::user()->workspaces;

        return view('projects.create', compact('workspaces'));
    }

    public function store(Request $request, ProjectProgressService $projectProgressService)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'start_date'   => 'required|date',
            'due_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:planning,active,on_hold,completed,cancelled,urgent',
        ]);

        $workspace = Workspace::findOrFail($validated['workspace_id']);
        if (!Auth::user()->isSuperAdmin() && !$workspace->canCreateProject(Auth::user())) {
            abort(403, 'Only workspace owner/admin can create projects.');
        }

        $project = DB::transaction(function () use ($projectProgressService, $validated, $workspace): Project {
            $project = Project::create([
                'workspace_id' => $validated['workspace_id'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['due_date'],
                'status' => $validated['status'],
                'created_by' => Auth::id(),
            ]);

            $project->members()->attach(Auth::id(), [
                'role' => 'manager',
                'joined_at' => now(),
            ]);

            $workspaceOwner = User::find($workspace->created_by);
            if ($workspaceOwner && $workspaceOwner->id !== Auth::id()) {
                $project->members()->attach($workspaceOwner->id, [
                    'role' => 'manager',
                    'joined_at' => now(),
                ]);
            }

            $defaultTasks = [
                [
                    'name' => 'Project Kickoff',
                    'description' => 'Mulai project, perkenalan tim, dan briefing awal.',
                    'status' => 'to_do',
                    'priority' => 'high',
                    'weight' => 1.00,
                    'start_date' => $validated['start_date'],
                    'due_date' => $validated['start_date'],
                ],
                [
                    'name' => 'Requirement Gathering',
                    'description' => 'Kumpulkan semua kebutuhan dan spesifikasi project.',
                    'status' => 'to_do',
                    'priority' => 'high',
                    'weight' => 1.00,
                    'start_date' => $validated['start_date'],
                    'due_date' => $validated['due_date'],
                ],
                [
                    'name' => 'Planning & Scheduling',
                    'description' => 'Buat rencana kerja, jadwal, dan pembagian tugas.',
                    'status' => 'to_do',
                    'priority' => 'medium',
                    'weight' => 1.00,
                    'start_date' => $validated['start_date'],
                    'due_date' => $validated['due_date'],
                ],
                [
                    'name' => 'Execution',
                    'description' => 'Pelaksanaan pekerjaan utama project.',
                    'status' => 'to_do',
                    'priority' => 'medium',
                    'weight' => 1.00,
                    'start_date' => $validated['start_date'],
                    'due_date' => $validated['due_date'],
                ],
                [
                    'name' => 'Review & Testing',
                    'description' => 'Evaluasi hasil dan pengujian sebelum selesai.',
                    'status' => 'to_do',
                    'priority' => 'medium',
                    'weight' => 1.00,
                    'start_date' => $validated['start_date'],
                    'due_date' => $validated['due_date'],
                ],
                [
                    'name' => 'Project Closing',
                    'description' => 'Dokumentasi akhir, serah terima, dan penutupan project.',
                    'status' => 'to_do',
                    'priority' => 'low',
                    'weight' => 1.00,
                    'start_date' => $validated['due_date'],
                    'due_date' => $validated['due_date'],
                ],
            ];

            foreach ($defaultTasks as $taskData) {
                $project->tasks()->create([
                    'name' => $taskData['name'],
                    'description' => $taskData['description'],
                    'status' => $taskData['status'],
                    'priority' => $taskData['priority'],
                    'weight' => $taskData['weight'],
                    'start_date' => $taskData['start_date'],
                    'due_date' => $taskData['due_date'],
                    'created_by' => Auth::id(),
                    'project_id' => $project->id,
                ]);
            }

            $projectProgressService->syncPlannedProgress($project);
            $projectProgressService->recordActualProgress($project);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => 'Membuat project: '.$project->name,
            ]);

            return $project;
        });

        return redirect()->route('projects.show', $project->token)
            ->with('success', 'Project created! Template tasks have been added — edit them as needed.');
    }

    public function show(string $token)
    {
        $project = $this->findByToken($token);

        if (!$project->isMember(Auth::user())) {
            return redirect()->back()->with('access_denied', 'Kamu tidak memiliki akses ke project ini. Hubungi manager project untuk ditambahkan sebagai member.');
        }

        $project->loadCount('tasks');
        $project->load([
            'workspace',
            'workspace.members',
            'members',
            'tasks.assignees',
            'tasks.assignee',
            'tasks.statusWeight',
            'tasks.statusHistory',
            'activeBaseline.plannedProgress',
            'activeBaseline.taskBaselines',
            'actualProgress',
        ]);

        $progress         = $project->calculateProgress();
        $availableMembers = $project->workspace->members->whereNotIn('id', $project->members->pluck('id'));
        $tasksByParent = $project->tasks->groupBy(fn (Task $task): int => (int) ($task->parent_task_id ?? 0));
        $taskHierarchyRoots = $tasksByParent->get(0, collect())->values();
        $visitedTaskIds = [];

        foreach ($taskHierarchyRoots as $rootTask) {
            $this->prepareTaskHierarchy($rootTask, $tasksByParent, 0, $visitedTaskIds);
        }

        $baseline        = $project->activeBaseline;
        $plannedProgress = $baseline
            ? $baseline->plannedProgress->sortBy('date')->values()
            : collect();

        $actualProgress = $project->actualProgress
            ->when($baseline, fn($collection) => $collection->where('baseline_id', $baseline->id))
            ->sortBy('date')
            ->values();

        $chartData = ['labels' => [], 'planned' => [], 'actual' => []];

        if ($plannedProgress->isNotEmpty() || $actualProgress->isNotEmpty()) {
            $dateLabels = collect()
                ->merge($plannedProgress->pluck('date')->map(fn($date) => $date->format('Y-m-d')))
                ->merge($actualProgress->pluck('date')->map(fn($date) => $date->format('Y-m-d')))
                ->unique()->sort()->values();

            $plannedMap = $plannedProgress
                ->mapWithKeys(fn($item) => [$item->date->format('Y-m-d') => (float) $item->planned_cumulative_percentage]);

            $actualMap = $actualProgress
                ->mapWithKeys(fn($item) => [$item->date->format('Y-m-d') => (float) $item->actual_cumulative_percentage]);

            $completedMap = $actualProgress
                ->mapWithKeys(fn($item) => [$item->date->format('Y-m-d') => (int) $item->completed_tasks_count]);

            $totalMap = $actualProgress
                ->mapWithKeys(fn($item) => [$item->date->format('Y-m-d') => (int) $item->total_tasks_count]);

            $chartData['labels']          = $dateLabels->map(fn($date) => \Carbon\Carbon::parse($date)->format('d M Y'))->toArray();
            $chartData['planned']         = $dateLabels->map(fn($date) => $plannedMap[$date] ?? null)->toArray();
            $chartData['actual']          = $dateLabels->map(fn($date) => $actualMap[$date] ?? null)->toArray();
            $chartData['completed_tasks'] = $dateLabels->map(fn($date) => $completedMap[$date] ?? null)->toArray();
            $chartData['total_tasks']     = $dateLabels->map(fn($date) => $totalMap[$date] ?? null)->toArray();
        }

        // overload
        $overloadedMemberIds = $project->members->filter(function ($member) use ($project) {
            $count = \App\Models\Task::where('project_id', $project->id)
                ->assignedToUser($member->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();
            return $count >= 5;
        })->pluck('id')->toArray();

        $memberTaskCounts = $project->members->mapWithKeys(function ($member) use ($project) {
            $count = \App\Models\Task::where('project_id', $project->id)
                ->assignedToUser($member->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();
            return [$member->id => $count];
        });

        return view('projects.show', compact('project', 'progress', 'availableMembers', 'baseline', 'chartData', 'overloadedMemberIds', 'memberTaskCounts', 'taskHierarchyRoots'));
    }

    public function edit(string $token)
    {
        $project = $this->findByToken($token);

        if (!Auth::user()->isSuperAdmin() && !$project->workspace->canCreateProject(Auth::user())) {
            abort(403, 'Only workspace owner/admin can edit this project.');
        }

        session(['project_back_url' => url()->previous()]);

        $workspaces = Auth::user()->workspaces;

        return view('projects.edit', compact('project', 'workspaces'));
    }

    public function update(Request $request, string $token)
    {
        $project = $this->findByToken($token);

        if (!$project->workspace->canCreateProject(Auth::user())) {
            abort(403, 'Only workspace owner/admin can update this project.');
        }

        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:planning,active,on_hold,completed,cancelled,urgent',
        ]);

        $project->update($validated);
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'updated',
            'entity_type' => 'project',
            'entity_id'   => $project->id,
            'description' => 'Mengubah project: ' . $project->name,
        ]);
        app(ProjectProgressService::class)->syncPlannedProgress($project);
        app(ProjectProgressService::class)->recordActualProgress($project);

        $backUrl = session('project_back_url', route('workspaces.show', $project->workspace->token));
        return redirect($backUrl)->with('success', 'Project updated successfully!');
    }
    public function destroy(Request $request, string $token)
    {
        $project = $this->findByToken($token);

        if (!Auth::user()->isSuperAdmin() && !$project->workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can delete this project.');
        }

        $workspaceToken = $project->workspace->token;

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'deleted',
            'entity_type' => 'project',
            'entity_id'   => $project->id,
            'description' => 'Menghapus project: ' . $project->name,
        ]);

        $project->delete();

        if (request('redirect') === 'management') {
            return redirect()->route('managementprojects.index')
                ->with('success', 'Project deleted successfully!');
        }

        $backUrl = $request->input('back_url')
            ?? session('project_back_url')
            ?? route('workspaces.show', $workspaceToken);

        return redirect($backUrl)->with('success', 'Project deleted successfully!');
    }

    public function addMember(Request $request, string $token)
    {
        $project = $this->findByToken($token);

        if (!$project->isManager(Auth::user())) {
            abort(403, 'Only project manager can manage project members.');
        }

        $validated = $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'role'       => 'required|in:manager,member,viewer',
        ]);

        $added = 0;
        foreach ($validated['user_ids'] as $userId) {
            if (!$project->workspace->members()->where('user_id', $userId)->exists()) continue;
            if ($project->members()->where('user_id', $userId)->exists()) continue;

            $project->members()->attach($userId, [
                'role'      => $validated['role'],
                'joined_at' => now(),
            ]);

            if ($userId != Auth::id()) {
                Notification::create([
                    'user_id'    => $userId,
                    'type'       => 'project_added',
                    'title'      => 'Ditambahkan ke Project',
                    'message'    => 'Anda ditambahkan ke project "' . $project->name . '" sebagai ' . ucfirst($validated['role']),
                    'project_id' => $project->id,
                    'task_id'    => null,
                ]);

                // Kirim email via Job
                $recipient = User::find($userId);
                if ($recipient) {
                    SendEmailNotification::dispatch(
                        $recipient,
                        'Ditambahkan ke Project',
                        'Anda ditambahkan ke project "' . $project->name . '" sebagai ' . ucfirst($validated['role']),
                        route('projects.show', $project->token),
                    );
                }
            }
            $added++;

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'assigned',
                'entity_type' => 'project',
                'entity_id'   => $project->id,
                'description' => 'Menambahkan anggota ke project: ' . $project->name,
            ]);
        }

        $msg = $added > 0 ? "$added member berhasil ditambahkan." : 'Tidak ada member baru yang ditambahkan.';
        return back()->with('success', $msg);
    }

    public function updateMemberRole(Request $request, string $token, User $user)
    {
        $project = $this->findByToken($token);

        if (!$project->isManager(Auth::user())) {
            abort(403, 'Only project manager can manage project members.');
        }

        $validated = $request->validate([
            'role' => 'required|in:manager,member,viewer',
        ]);

        $project->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Project member role updated.');
    }

    public function removeMember(string $token, User $user)
    {
        $project = $this->findByToken($token);

        if (!$project->isManager(Auth::user())) {
            abort(403, 'Only project manager can manage project members.');
        }

        if ((int) $project->created_by === (int) $user->id) {
            return back()->withErrors(['member' => 'Project creator cannot be removed.']);
        }

        DB::transaction(function () use ($project, $user): void {
            $taskIds = Task::query()->where('project_id', $project->id)->pluck('id')->all();

            $pivotAssignmentCount = DB::table('task_assignees')
                ->where('user_id', $user->id)
                ->whereIn('task_id', $taskIds)
                ->count();
            $legacyAssignmentCount = Task::query()
                ->whereIn('id', $taskIds)
                ->where('assignee_id', $user->id)
                ->count();

            DB::table('task_assignees')
                ->where('user_id', $user->id)
                ->whereIn('task_id', $taskIds)
                ->delete();
            Task::query()
                ->whereIn('id', $taskIds)
                ->where('assignee_id', $user->id)
                ->update(['assignee_id' => null]);

            Notification::query()
                ->where('user_id', $user->id)
                ->where(function ($query) use ($project, $taskIds): void {
                    $query->where('project_id', $project->id)
                        ->orWhereIn('task_id', $taskIds);
                })
                ->delete();

            $project->members()->detach($user->id);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => sprintf(
                    'Actor %s (ID %d) removed member %s (ID %d) from project %s (ID %d); detached %d pivot assignment(s) and cleared %d legacy assignment(s).',
                    Auth::user()->name,
                    Auth::id(),
                    $user->name,
                    $user->id,
                    $project->name,
                    $project->id,
                    $pivotAssignmentCount,
                    $legacyAssignmentCount,
                ),
                'old_value' => [
                    'target_user_id' => $user->id,
                    'pivot_assignment_count' => $pivotAssignmentCount,
                    'legacy_assignment_count' => $legacyAssignmentCount,
                ],
                'new_value' => [
                    'membership' => 'removed',
                    'pivot_assignment_count' => 0,
                    'legacy_assignment_count' => 0,
                ],
            ]);
        });

        return back()->with('success', 'Project member removed.');
    }

    public function managementIndex()
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $totalUsers = User::count();

        $projects = Project::with(['workspace', 'members'])
            ->withCount('tasks')
            ->latest()
            ->get();

        return view('managementprojects.index', compact('projects', 'totalUsers'));
    }

    public function updateStatus(Request $request, string $token)
    {
        $project = $this->findByToken($token);

        $request->validate([
            'status' => 'required|in:planning,active,on_hold,completed,cancelled,urgent',
        ]);

        $isSuperAdmin = $request->user()->isSuperAdmin();

        $userRole = $project->members
            ->where('id', $request->user()->id)
            ->first()?->pivot->role ?? null;

        $canEdit = $isSuperAdmin || in_array($userRole, ['manager', 'member']);

        if (!$canEdit) {
            abort(403, 'You do not have permission to update project status.');
        }

        $project->update(['status' => $request->status]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'status_changed',
            'entity_type' => 'project',
            'entity_id'   => $project->id,
            'description' => 'Mengubah status project: ' . $project->name . ' menjadi ' . $request->status,
        ]);

        return back()->with('success', 'Project status updated.');
    }

    public function ganttData(Request $request)
    {
        $tasks  = [];
        $user   = Auth::user();
        $status = $request->get('status', 'all');

        $query = Project::whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('tasks');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $projects = $query->get();

        foreach ($projects as $project) {
            $projectStart = $project->start_date
                ? \Carbon\Carbon::parse($project->start_date)
                : now();

            $projectEnd = $project->end_date
                ? \Carbon\Carbon::parse($project->end_date)
                : $projectStart->copy()->addDay();

            $tasks[] = [
                'id'         => 'p_' . $project->id,
                'text'       => $project->name,
                'start_date' => $projectStart->format('d-m-Y'),
                'duration'   => max(1, $projectStart->diffInDays($projectEnd) + 1),
                'progress'   => 0,
                'status'     => $project->status,
            ];
        }

        return response()->json([
            'data'  => $tasks,
            'links' => [],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Task>>  $tasksByParent
     * @param  array<int, true>  $visitedTaskIds
     */
    private function prepareTaskHierarchy(Task $task, \Illuminate\Support\Collection $tasksByParent, int $depth, array &$visitedTaskIds): void
    {
        if (isset($visitedTaskIds[$task->id]) || $depth > TaskHierarchyService::MAXIMUM_DEPTH) {
            $task->setRelation('subtasks', collect());

            return;
        }

        $visitedTaskIds[$task->id] = true;
        $subtasks = $tasksByParent->get($task->id, collect())->values();
        $task->setRelation('subtasks', $subtasks);
        $task->setAttribute('hierarchy_depth', $depth);
        $task->setAttribute('subtasks_count', $subtasks->count());
        $task->setAttribute('subtask_weight_total', round((float) $subtasks->sum('subtask_weight_percentage'), 2));

        foreach ($subtasks as $subtask) {
            $this->prepareTaskHierarchy($subtask, $tasksByParent, $depth + 1, $visitedTaskIds);
        }

        $task->setAttribute('hierarchy_progress_percentage', round($this->taskHierarchyService->resolveProgressPercentage($task), 2));
    }
}
