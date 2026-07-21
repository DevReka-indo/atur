<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailNotification;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\ProjectProgressService;
use App\Services\TaskGanttService;
use App\Services\TaskHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;
use Throwable;

class TaskController extends Controller
{
    public function __construct(private TaskHierarchyService $taskHierarchyService, private ProjectProgressService $projectProgressService, private TaskGanttService $taskGanttService) {}

    // task token
    private function findByToken(string $token): Task
    {
        return Task::where('token', $token)->firstOrFail();
    }

    private function wouldCreateCircularDependency(Task $task, int $predecessorId): bool
    {
        $visitedTaskIds = [];
        $currentTaskId = $predecessorId;

        while ($currentTaskId !== null) {
            if ($currentTaskId === $task->id) {
                return true;
            }

            if (isset($visitedTaskIds[$currentTaskId])) {
                return true;
            }

            $visitedTaskIds[$currentTaskId] = true;
            $nextPredecessorId = Task::query()->whereKey($currentTaskId)->where('project_id', $task->project_id)->value('predecessor_id');

            $currentTaskId = $nextPredecessorId === null ? null : (int) $nextPredecessorId;
        }

        return false;
    }

    public function index(Request $request, TaskHierarchyService $taskHierarchyService)
    {
        $user = Auth::user();

        app(DashboardController::class)->sendDeadlineNotificationsPublic($user);

        $view = $request->string('view', 'list')->toString();
        $status = $request->string('status', 'all')->toString();

        $allowedViews = ['list', 'gantt', 'kanban'];
        $allowedStatuses = ['all', 'to_do', 'in_progress', 'review', 'completed', 'stopped', 'cancelled'];

        if (!in_array($view, $allowedViews, true)) {
            $view = 'list';
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $kanbanStatuses = [
            'to_do' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'completed' => 'Completed',
            'stopped' => 'Stopped',
            'cancelled' => 'Cancelled',
        ];

        $statusOptions = collect($kanbanStatuses)
            ->map(
                fn(string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ],
            )
            ->values()
            ->all();

        $tasks = collect();
        $taskTree = collect();
        $kanbanTasks = collect();

        /*
    |--------------------------------------------------------------------------
    | Kanban
    |--------------------------------------------------------------------------
    |
    | Kanban hanya menampilkan executable leaf task.
    |
    */
        if ($view === 'kanban') {
            $query = Task::query()
                ->with(['project.workspace', 'assignees', 'assignee', 'statusWeight', 'parent:id,token,name,parent_task_id', 'parent.parent:id,token,name,parent_task_id'])
                ->assignedToUser($user->id)
                ->whereDoesntHave('subtasks');

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $tasks = $query->orderByRaw("FIELD(priority, 'urgent') DESC")->latest()->get();

            $taskProjectIds = $tasks->pluck('project_id')->filter()->unique()->values();

            $contributableProjectIds = $user->isSuperAdmin()
                ? $taskProjectIds
                : $user
                    ->projects()
                    ->wherePivotIn('role', ['manager', 'member'])
                    ->whereIn('projects.id', $taskProjectIds)
                    ->pluck('projects.id');

            $tasks->each(function (Task $task) use ($contributableProjectIds): void {
                $task->setAttribute('kanban_can_contribute', $contributableProjectIds->contains($task->project_id));
            });

            $kanbanTasks = $tasks->groupBy('status');
        }

        /*
    |--------------------------------------------------------------------------
    | List
    |--------------------------------------------------------------------------
    |
    | List menampilkan task milik user dalam bentuk tree.
    | Ancestor tetap ditampilkan sebagai context meskipun bukan assignee.
    |
    */
        if ($view === 'list') {
            $assignedTaskQuery = Task::query()
                ->with(['parent:id,token,name,parent_task_id', 'parent.parent:id,token,name,parent_task_id'])
                ->assignedToUser($user->id);

            if ($status !== 'all') {
                $assignedTaskQuery->where('status', $status);
            }

            $assignedTasks = $assignedTaskQuery->get();

            $assignedTaskIds = $assignedTasks->pluck('id')->unique()->values();

            $ancestorIds = $assignedTasks
                ->flatMap(function (Task $task): array {
                    return [$task->parent_task_id, $task->parent?->parent_task_id];
                })
                ->filter()
                ->unique()
                ->values();

            $visibleTaskIds = $assignedTaskIds->merge($ancestorIds)->unique()->values();

            if ($visibleTaskIds->isNotEmpty()) {
                $tasks = Task::query()
                    ->with(['project.workspace', 'assignees', 'assignee', 'statusWeight', 'statusHistory', 'parent:id,token,name,parent_task_id', 'parent.parent:id,token,name,parent_task_id', 'subtasks' => fn($query) => $query->with(['statusWeight', 'statusHistory', 'subtasks.statusWeight', 'subtasks.statusHistory'])->orderBy('created_at'), 'subtasks.subtasks' => fn($query) => $query->with(['statusWeight', 'statusHistory'])->orderBy('created_at')])
                    ->withCount('subtasks')
                    ->whereIn('id', $visibleTaskIds)
                    ->get();

                $projectIds = $tasks->pluck('project_id')->filter()->unique()->values();

                $contributableProjectIds = $user->isSuperAdmin()
                    ? $projectIds
                    : $user
                        ->projects()
                        ->wherePivotIn('role', ['manager', 'member'])
                        ->whereIn('projects.id', $projectIds)
                        ->pluck('projects.id');

                $manageableProjectIds = $user->isSuperAdmin() ? $projectIds : $user->projects()->wherePivot('role', 'manager')->whereIn('projects.id', $projectIds)->pluck('projects.id');

                $assignedTaskIdLookup = $assignedTaskIds->flip();

                $tasks->each(function (Task $task) use ($assignedTaskIdLookup, $contributableProjectIds, $manageableProjectIds): void {
                    $task->setAttribute('my_task_is_assigned', $assignedTaskIdLookup->has($task->id));

                    $task->setAttribute('my_task_is_context', !$assignedTaskIdLookup->has($task->id));

                    $task->setAttribute('my_task_can_contribute', $contributableProjectIds->contains($task->project_id));

                    $task->setAttribute('my_task_can_manage', $manageableProjectIds->contains($task->project_id));
                });

                $taskTree = $this->buildMyTaskTree(tasks: $tasks, taskHierarchyService: $taskHierarchyService);
            }
        }

        return view('tasks.index', [
            'tasks' => $tasks,
            'taskTree' => $taskTree,
            'view' => $view,
            'currentStatus' => $status,
            'kanbanStatuses' => $kanbanStatuses,
            'kanbanTasks' => $kanbanTasks,
            'statusOptions' => $statusOptions,
        ]);
    }
    // public function index(Request $request)
    // {
    //     $user = Auth::user();
    //     app(DashboardController::class)->sendDeadlineNotificationsPublic($user);
    //     $view = $request->get('view', 'list');
    //     $status = $request->get('status', 'all');

    //     $query = Task::query()
    //         ->with([
    //             'project.workspace',
    //             'assignees',
    //             'assignee',
    //             'statusWeight',
    //             'parent:id,token,name,parent_task_id',
    //             'parent.parent:id,token,name,parent_task_id',
    //         ])
    //         ->assignedToUser($user->id);

    //     if ($view === 'kanban') {
    //         $query->whereDoesntHave('subtasks');
    //     } else {
    //         $query->withCount('subtasks');
    //     }

    //     if ($status !== 'all') {
    //         $query->where('status', $status);
    //     }

    //     $tasks = $query->orderByRaw("FIELD(priority, 'urgent') DESC")->latest()->get();
    //     if ($view === 'kanban') {
    //         $contributableProjectIds = $user->isSuperAdmin()
    //             ? $tasks->pluck('project_id')->unique()
    //             : $user->projects()
    //                 ->wherePivotIn('role', ['manager', 'member'])
    //                 ->whereIn('projects.id', $tasks->pluck('project_id')->unique())
    //                 ->pluck('projects.id');

    //         $tasks->each(function (Task $task) use ($contributableProjectIds): void {
    //             $task->setAttribute('kanban_can_contribute', $contributableProjectIds->contains($task->project_id));
    //         });
    //     }
    //     $kanbanStatuses = [
    //         'to_do' => 'To Do',
    //         'in_progress' => 'In Progress',
    //         'review' => 'Review',
    //         'completed' => 'Completed',
    //         'stopped' => 'Stopped',
    //         'cancelled' => 'Cancelled',
    //     ];
    //     $kanbanTasks = $tasks->groupBy('status');

    //     return view('tasks.index', compact('tasks', 'view', 'kanbanStatuses', 'kanbanTasks'));
    // }
private function buildMyTaskTree(
    Collection $tasks,
    TaskHierarchyService $taskHierarchyService
): Collection {
    $taskLookup = $tasks->keyBy('id');

    $childrenByParent = $tasks
        ->groupBy(fn (Task $task): int|string => $task->parent_task_id ?? 'root');

    $rootTasks = $tasks
        ->filter(function (Task $task) use ($taskLookup): bool {
            return $task->parent_task_id === null
                || ! $taskLookup->has($task->parent_task_id);
        });

    return $this->sortMyTaskCollection($rootTasks)
        ->map(function (Task $task) use (
            $childrenByParent,
            $taskHierarchyService
        ): array {
            return $this->buildMyTaskTreeNode(
                task: $task,
                childrenByParent: $childrenByParent,
                taskHierarchyService: $taskHierarchyService,
                level: 0,
                visitedTaskIds: []
            );
        })
        ->values();
}

private function buildMyTaskTreeNode(
    Task $task,
    Collection $childrenByParent,
    TaskHierarchyService $taskHierarchyService,
    int $level,
    array $visitedTaskIds
): array {
    if (in_array($task->id, $visitedTaskIds, true)) {
        return [
            'task' => $task,
            'children' => collect(),
            'level' => $level,
            'context_only' => (bool) $task->getAttribute('my_task_is_context'),
            'is_assigned' => (bool) $task->getAttribute('my_task_is_assigned'),
            'progress' => 0,
            'leaf_count' => 0,
            'completed_leaf_count' => 0,
            'cycle_detected' => true,
        ];
    }

    $visitedTaskIds[] = $task->id;

    $visibleChildren = $childrenByParent->get($task->id, collect());

    $childNodes = $this->sortMyTaskCollection($visibleChildren)
        ->map(function (Task $child) use (
            $childrenByParent,
            $taskHierarchyService,
            $level,
            $visitedTaskIds
        ): array {
            return $this->buildMyTaskTreeNode(
                task: $child,
                childrenByParent: $childrenByParent,
                taskHierarchyService: $taskHierarchyService,
                level: $level + 1,
                visitedTaskIds: $visitedTaskIds
            );
        })
        ->values();

    return [
        'task' => $task,
        'children' => $childNodes,
        'level' => $level,
        'context_only' => (bool) $task->getAttribute('my_task_is_context'),
        'is_assigned' => (bool) $task->getAttribute('my_task_is_assigned'),
        'progress' => round(
            $taskHierarchyService->resolveProgressPercentage($task),
            1
        ),
        'leaf_count' => $this->countHierarchyLeafTasks($task),
        'completed_leaf_count' => $this->countCompletedHierarchyLeafTasks($task),
        'cycle_detected' => false,
    ];
}

private function sortMyTaskCollection(Collection $tasks): Collection
{
    $now = now();

    return $tasks
        ->sortBy(function (Task $task) use ($now): array {
            $statusOrder = match ($task->status) {
                'completed' => 2,
                'cancelled' => 3,
                default => 1,
            };

            $priorityOrder = match ($task->priority) {
                'urgent' => 0,
                'high' => 1,
                'medium' => 2,
                'low' => 3,
                default => 4,
            };

            $dueDateOrder = $task->due_date
                ? $now->diffInMinutes($task->due_date, false)
                : PHP_INT_MAX;

            return [
                $statusOrder,
                $priorityOrder,
                $dueDateOrder,
                $task->created_at?->timestamp ?? 0,
            ];
        })
        ->values();
}

private function countHierarchyLeafTasks(Task $task): int
{
    if ($task->subtasks->isEmpty()) {
        return 1;
    }

    return $task->subtasks->sum(
        fn (Task $child): int => $this->countHierarchyLeafTasks($child)
    );
}

private function countCompletedHierarchyLeafTasks(Task $task): int
{
    if ($task->subtasks->isEmpty()) {
        return $task->status === 'completed' ? 1 : 0;
    }

    return $task->subtasks->sum(
        fn (Task $child): int => $this->countCompletedHierarchyLeafTasks($child)
    );
}


    public function create(Request $request)
    {
        $projectToken = $request->query('project_token');
        $parentToken = $request->query('parent');
        $project = null;
        $parentTask = null;
        $parentDepth = null;
        $usedSubtaskWeight = 0.0;
        $remainingSubtaskWeight = 100.0;

        if ($parentToken) {
            $parentTask = Task::query()
                ->where('token', $parentToken)
                ->with(['project.workspace', 'project.members', 'parent.parent', 'subtasks'])
                ->withCount('subtasks')
                ->firstOrFail();
            $project = $parentTask->project;

            if (!$project->canContribute(Auth::user())) {
                abort(403, 'Only manager/member can create subtasks.');
            }

            $parentDepth = $this->taskHierarchyService->hierarchyDepth($parentTask);
            if ($parentDepth >= TaskHierarchyService::MAXIMUM_DEPTH) {
                abort(422, 'Task ini sudah berada pada kedalaman hierarchy maksimum.');
            }

            $usedSubtaskWeight = round((float) $parentTask->subtasks->sum('subtask_weight_percentage'), 2);
            $remainingSubtaskWeight = max(0, round(100 - $usedSubtaskWeight, 2));
        } elseif ($projectToken) {
            $project = Project::where('token', $projectToken)->firstOrFail();
            if (!$project->canContribute(Auth::user())) {
                abort(403, 'Only manager/member can create tasks.');
            }
        }

        $projects = Auth::user()
            ->projects()
            ->wherePivotIn('role', ['manager', 'member'])
            ->get();
        $assignees = $project ? $project->members : collect();

        return view('tasks.create', compact('projects', 'project', 'assignees', 'parentTask', 'parentDepth', 'usedSubtaskWeight', 'remainingSubtaskWeight'));
    }

    public function store(Request $request)
    {
        $projectId = $request->integer('project_id');

        $validator = Validator::make(
            $request->all(),
            [
                'project_id' => ['required', 'exists:projects,id'],
                'parent_task_id' => ['nullable', Rule::exists('tasks', 'id')->where(fn($query) => $query->where('project_id', $projectId))],
                'name' => ['required', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'assignee_ids' => ['nullable', 'array'],
                'assignee_ids.*' => ['distinct', Rule::exists('project_members', 'user_id')->where(fn($query) => $query->where('project_id', $projectId))],
                'status' => ['required', 'in:to_do,in_progress,review,completed,stopped,cancelled'],
                'priority' => ['required', 'in:low,medium,high,urgent'],
                'weight' => ['required', 'numeric', 'min:0.01'],
                'subtask_weight_percentage' => [$request->filled('parent_task_id') ? 'required' : 'prohibited', 'nullable', 'numeric', 'gt:0', 'lte:100'],
                'start_date' => ['nullable', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'predecessor_id' => ['nullable', Rule::exists('tasks', 'id')->where(fn($query) => $query->where('project_id', $projectId))],
                'dependency_type' => ['nullable', 'in:FS,SS,FF,SF'],
            ],
            [
                'parent_task_id.exists' => 'The parent task must belong to the selected project.',
                'predecessor_id.exists' => 'The predecessor must belong to the selected project.',
                'assignee_ids.*.distinct' => 'Each assignee may only be selected once.',
                'assignee_ids.*.exists' => 'Each assignee must be a member of the selected project.',
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $validator) use ($request, $projectId): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $context = new Task([
                'project_id' => $projectId,
                'parent_task_id' => $request->integer('parent_task_id') ?: null,
                'subtask_weight_percentage' => $request->input('subtask_weight_percentage'),
                'status' => 'to_do',
            ]);

            $this->appendHierarchyValidationErrors($validator, function () use ($context, $request): void {
                if ($context->parent_task_id !== null) {
                    $parent = Task::query()->findOrFail($context->parent_task_id);
                    $this->taskHierarchyService->validateParentCandidate($context, $parent);
                }

                $this->taskHierarchyService->validateSubtaskWeight($context);
                $this->taskHierarchyService->validateStatusTransition($context, $request->string('status')->toString());

                if ($request->filled('predecessor_id')) {
                    $predecessor = Task::query()->findOrFail($request->integer('predecessor_id'));
                    $this->taskHierarchyService->validatePredecessorCandidate($context, $predecessor);
                }
            });
        });

        $validated = $validator->validate();

        $project = Project::findOrFail($validated['project_id']);
        if (!$project->canContribute(Auth::user())) {
            abort(403, 'Only manager/member can create tasks.');
        }

        try {
            $task = DB::transaction(function () use ($validated, $project): Task {
                $context = new Task([
                    'project_id' => $validated['project_id'],
                    'parent_task_id' => $validated['parent_task_id'] ?? null,
                    'subtask_weight_percentage' => $validated['subtask_weight_percentage'] ?? null,
                    'status' => 'to_do',
                ]);
                if ($context->parent_task_id !== null) {
                    $parent = Task::query()->lockForUpdate()->findOrFail($context->parent_task_id);
                    $this->taskHierarchyService->validateParentCandidate($context, $parent);
                }
                $this->taskHierarchyService->validateSubtaskWeight($context);
                $this->taskHierarchyService->validateStatusTransition($context, $validated['status']);

                if (!empty($validated['predecessor_id'])) {
                    $predecessor = Task::query()->lockForUpdate()->findOrFail($validated['predecessor_id']);
                    $this->taskHierarchyService->validatePredecessorCandidate($context, $predecessor);
                }

                $task = Task::create([
                    'project_id' => $validated['project_id'],
                    'parent_task_id' => $validated['parent_task_id'] ?? null,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'status' => $validated['status'],
                    'priority' => $validated['priority'],
                    'weight' => $validated['weight'],
                    'subtask_weight_percentage' => $validated['subtask_weight_percentage'] ?? null,
                    'start_date' => $validated['start_date'] ?? null,
                    'due_date' => $validated['due_date'] ?? null,
                    'predecessor_id' => $validated['predecessor_id'] ?? null,
                    'dependency_type' => $validated['dependency_type'] ?? 'FS',
                    'created_by' => Auth::id(),
                ]);

                $assigneeIds = $validated['assignee_ids'] ?? [];
                $task->assignees()->sync($assigneeIds);

                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'from_status' => null,
                    'to_status' => $validated['status'],
                    'changed_by' => Auth::id(),
                ]);

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'created',
                    'entity_type' => 'task',
                    'entity_id' => $task->id,
                    'description' => 'Created task: ' . $task->name,
                ]);

                foreach ($assigneeIds as $assigneeId) {
                    if ($assigneeId != Auth::id()) {
                        $message = 'Anda mendapat task baru: "' . $task->name . '" di project ' . $project->name;
                        Notification::create([
                            'user_id' => $assigneeId,
                            'type' => 'assignment',
                            'title' => 'Task Baru Ditugaskan',
                            'message' => $message,
                            'task_id' => $task->id,
                            'project_id' => $project->id,
                        ]);

                        $recipient = User::find($assigneeId);
                        if ($recipient) {
                            $this->queueEmailAfterCommit($recipient, 'Task Baru Ditugaskan', $message, $task);
                        }
                    }
                }

                if ($task->priority === 'urgent') {
                    foreach ($assigneeIds as $assigneeId) {
                        $assignee = User::find($assigneeId);
                        if ($assignee) {
                            $this->queueEmailAfterCommit($assignee, 'Urgent Task Alert', 'Task "' . $task->name . '" is marked as urgent and needs immediate attention!', $task);
                        }
                    }
                }

                if ($task->parent_task_id !== null) {
                    $this->taskHierarchyService->synchronizeAncestors($task, Auth::id());
                }

                $this->projectProgressService->syncPlannedProgress($project);
                $this->projectProgressService->recordActualProgress($project);

                return $task;
            });

            return redirect()->route('projects.show', $project->token)->with('success', 'Task created successfully!');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $e) {
            return back()
                ->withErrors(['error' => 'Failed to create task: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function edit(string $token)
    {
        $task = $this->findByToken($token);
        $task->load(['project.workspace', 'assignees', 'assignee', 'parent.parent', 'subtasks']);
        $task->loadCount('subtasks');

        if (!$task->project) {
            abort(404, 'Task tidak memiliki project.');
        }

        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer can only view this task.');
        }

        session(['task_back_url' => url()->previous()]);

        $projects = Auth::user()->projects;
        $assignees = $task->project->members;
        $back_url = url()->previous();
        $taskDepth = $this->taskHierarchyService->hierarchyDepth($task);
        $siblingWeightWithoutTask = $task->parent_task_id === null ? 0.0 : round((float) Task::query()->where('parent_task_id', $task->parent_task_id)->whereKeyNot($task->id)->sum('subtask_weight_percentage'), 2);
        $remainingSubtaskWeight = max(0, round(100 - $siblingWeightWithoutTask, 2));
        $totalSiblingWeight = $task->parent_task_id === null ? 0.0 : round($siblingWeightWithoutTask + (float) ($task->subtask_weight_percentage ?? 0), 2);
        $taskHasSubtasks = $task->subtasks_count > 0;
        $subtaskStatusReady = $task->parent_task_id === null || $task->status !== 'to_do' || $totalSiblingWeight === 100.0;

        return view('tasks.edit', compact('task', 'projects', 'assignees', 'back_url', 'taskDepth', 'siblingWeightWithoutTask', 'remainingSubtaskWeight', 'totalSiblingWeight', 'taskHasSubtasks', 'subtaskStatusReady'));
    }

    public function show(string $token)
    {
        $task = $this->findByToken($token);

        $isSuperAdmin = Auth::user()->isSuperAdmin();
        if (!$isSuperAdmin && !$task->project->isMember(Auth::user())) {
            abort(403, 'You do not have access to this task.');
        }

        $task->load(['project.workspace', 'assignees', 'assignee', 'creator', 'parent.parent', 'statusWeight', 'statusHistory.changer', 'subtasks.assignees', 'subtasks.assignee', 'subtasks.statusWeight', 'subtasks.statusHistory', 'subtasks.subtasks.assignees', 'subtasks.subtasks.assignee', 'subtasks.subtasks.statusWeight', 'subtasks.subtasks.statusHistory', 'comments.user', 'attachments.uploader']);
        $task->loadCount('subtasks');

        $taskDepth = $this->taskHierarchyService->hierarchyDepth($task);
        $hierarchyAncestors = $this->hierarchyAncestors($task);
        $visitedTaskIds = [];
        $this->decorateHierarchyTask($task, $taskDepth, $visitedTaskIds);
        $taskHasSubtasks = $task->subtasks_count > 0;
        $canAddSubtask = $task->project->canContribute(Auth::user()) && $taskDepth < TaskHierarchyService::MAXIMUM_DEPTH;
        $hierarchyProgressPercentage = (float) $task->getAttribute('hierarchy_progress_percentage');
        $hierarchyEarnedValue = $this->taskHierarchyService->resolveEarnedContribution($task);

        // Deteksi dari mana task diakses
        $fromMyTask = str_contains(url()->previous(), '/tasks') && !str_contains(url()->previous(), '/projects');

        return view('tasks.show', compact('task', 'fromMyTask', 'taskDepth', 'hierarchyAncestors', 'taskHasSubtasks', 'canAddSubtask', 'hierarchyProgressPercentage', 'hierarchyEarnedValue'));
    }

    public function update(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer can only view this task.');
        }

        $validationData = $request->all();
        if ($task->parent_task_id !== null && !array_key_exists('subtask_weight_percentage', $validationData)) {
            $validationData['subtask_weight_percentage'] = $task->subtask_weight_percentage;
        }

        $validator = Validator::make(
            $validationData,
            [
                'parent_task_id' => ['prohibited'],
                'name' => ['required', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'assignee_ids' => ['nullable', 'array'],
                'assignee_ids.*' => ['distinct', Rule::exists('project_members', 'user_id')->where(fn($query) => $query->where('project_id', $task->project_id))],
                'status' => ['required', 'in:to_do,in_progress,review,completed,stopped,cancelled'],
                'priority' => ['required', 'in:low,medium,high,urgent'],
                'weight' => ['required', 'numeric', 'min:0.01'],
                'subtask_weight_percentage' => [$task->parent_task_id !== null ? 'required' : 'prohibited', 'nullable', 'numeric', 'gt:0', 'lte:100'],
                'start_date' => ['nullable', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'predecessor_id' => ['nullable', Rule::notIn([$task->id]), Rule::exists('tasks', 'id')->where(fn($query) => $query->where('project_id', $task->project_id))],
                'dependency_type' => ['nullable', 'in:FS,SS,FF,SF'],
            ],
            [
                'parent_task_id.prohibited' => 'The parent task cannot be changed.',
                'predecessor_id.not_in' => 'A task cannot be its own predecessor.',
                'predecessor_id.exists' => 'The predecessor must belong to the same project.',
                'assignee_ids.*.distinct' => 'Each assignee may only be selected once.',
                'assignee_ids.*.exists' => 'Each assignee must be a member of the task project.',
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $validator) use ($validationData, $task): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $candidate = clone $task;
            $candidate->subtask_weight_percentage = $validationData['subtask_weight_percentage'] ?? null;

            $this->appendHierarchyValidationErrors($validator, function () use ($candidate, $validationData, $task): void {
                $this->taskHierarchyService->validateSubtaskWeight($candidate);

                if ($validationData['status'] !== $task->status) {
                    $this->taskHierarchyService->assertManualStatusAllowed($task);
                    $this->taskHierarchyService->validateStatusTransition($candidate, $validationData['status']);
                }

                if (!empty($validationData['predecessor_id'])) {
                    $predecessor = Task::query()->findOrFail((int) $validationData['predecessor_id']);
                    $this->taskHierarchyService->validatePredecessorCandidate($task, $predecessor);

                    if ($this->wouldCreateCircularDependency($task, $predecessor->id)) {
                        throw ValidationException::withMessages([
                            'predecessor_id' => 'The selected predecessor creates a circular dependency.',
                        ]);
                    }
                }
            });
        });

        $validated = $validator->validate();

        try {
            DB::transaction(function () use ($validated, $task): void {
                $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
                $lockedTask->load(['assignees', 'project']);
                $candidate = clone $lockedTask;
                $candidate->subtask_weight_percentage = $validated['subtask_weight_percentage'] ?? null;
                $this->taskHierarchyService->validateSubtaskWeight($candidate);

                if (!empty($validated['predecessor_id'])) {
                    $predecessor = Task::query()->lockForUpdate()->findOrFail($validated['predecessor_id']);
                    $this->taskHierarchyService->validatePredecessorCandidate($lockedTask, $predecessor);

                    if ($this->wouldCreateCircularDependency($lockedTask, $predecessor->id)) {
                        throw ValidationException::withMessages([
                            'predecessor_id' => 'The selected predecessor creates a circular dependency.',
                        ]);
                    }
                }

                $oldStatus = $lockedTask->getOriginal('status');
                $oldAssigneeIds = $lockedTask->assignees->pluck('id')->all();
                $newAssigneeIds = $validated['assignee_ids'] ?? [];
                $changes = [];

                foreach ($validated as $key => $value) {
                    if (in_array($key, ['assignee_ids', 'parent_task_id'], true)) {
                        continue;
                    }

                    if ($lockedTask->{$key} != $value) {
                        $changes[$key] = ['old' => $lockedTask->{$key}, 'new' => $value];
                    }
                }

                $lockedTask->update(
                    array_merge(
                        collect($validated)
                            ->except(['assignee_ids', 'parent_task_id', 'status', 'predecessor_id', 'dependency_type'])
                            ->toArray(),
                        [
                            'predecessor_id' => $validated['predecessor_id'] ?? null,
                            'dependency_type' => $validated['dependency_type'] ?? 'FS',
                        ],
                    ),
                );

                $lockedTask->assignees()->sync($newAssigneeIds);
                foreach (array_diff($newAssigneeIds, $oldAssigneeIds) as $assigneeId) {
                    if ($assigneeId != Auth::id()) {
                        $message = 'Anda ditugaskan ke task: "' . $lockedTask->name . '" di project ' . $lockedTask->project->name;
                        Notification::create([
                            'user_id' => $assigneeId,
                            'type' => 'assignment',
                            'title' => 'Task Ditugaskan ke Anda',
                            'message' => $message,
                            'task_id' => $lockedTask->id,
                            'project_id' => $lockedTask->project_id,
                        ]);

                        $recipient = User::find($assigneeId);
                        if ($recipient) {
                            $this->queueEmailAfterCommit($recipient, 'Task Ditugaskan ke Anda', $message, $lockedTask);
                        }
                    }
                }

                $statusChanged = false;
                if ($oldStatus !== $validated['status']) {
                    $statusChanged = $this->taskHierarchyService->changeStatus($lockedTask, $validated['status'], Auth::id());
                    $this->createManualStatusNotifications($lockedTask, $oldStatus, $validated['status']);
                } elseif ($lockedTask->parent_task_id !== null && array_key_exists('subtask_weight_percentage', $changes)) {
                    $this->taskHierarchyService->synchronizeAncestors($lockedTask, Auth::id());
                }

                if (!empty($changes)) {
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'updated',
                        'entity_type' => 'task',
                        'entity_id' => $lockedTask->id,
                        'description' => 'Updated task: ' . $lockedTask->name,
                        'old_value' => $changes,
                        'new_value' => $changes,
                    ]);
                }

                if ($statusChanged) {
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'status_changed',
                        'entity_type' => 'task',
                        'entity_id' => $lockedTask->id,
                        'description' => 'Mengubah status task "' . $lockedTask->name . '" dari ' . $oldStatus . ' menjadi ' . $validated['status'],
                        'old_value' => ['status' => $oldStatus],
                        'new_value' => ['status' => $validated['status']],
                    ]);
                }

                $this->projectProgressService->syncPlannedProgress($lockedTask->project);
                $this->projectProgressService->recordActualProgress($lockedTask->project);
            });

            $backUrl = session('task_back_url', route('projects.show', $task->project->token));

            return redirect($backUrl)->with('success', 'Task updated successfully!');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $e) {
            return back()
                ->withErrors(['error' => 'Failed to update task: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        if (!$task->project->isManager(Auth::user())) {
            abort(403);
        }

        try {
            $project = $task->project;
            $projectToken = $project->token;

            DB::transaction(function () use ($task, $project): void {
                $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
                $wasChild = $lockedTask->parent_task_id !== null;

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'deleted',
                    'entity_type' => 'task',
                    'entity_id' => $lockedTask->id,
                    'description' => 'Deleted task: ' . $lockedTask->name,
                ]);

                $lockedTask->delete();

                if ($wasChild) {
                    $this->taskHierarchyService->synchronizeAncestors($lockedTask, Auth::id());
                }

                $this->projectProgressService->syncPlannedProgress($project);
                $this->projectProgressService->recordActualProgress($project);
            });

            $backUrl = $request->input('back_url') ?? (session('task_back_url') ?? route('projects.show', $projectToken));

            return redirect($backUrl)->with('success', 'Task deleted successfully!');
        } catch (Throwable $e) {
            return back()->withErrors(['error' => 'Failed to delete task.']);
        }
    }

    public function updateStatus(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        $isSuperAdmin = Auth::user()->isSuperAdmin();
        if (!$isSuperAdmin && !$task->project->canContribute(Auth::user())) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:to_do,in_progress,review,completed,stopped,cancelled',
        ]);

        try {
            DB::transaction(function () use ($task, $validated): void {
                $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
                $oldStatus = $lockedTask->status;
                $statusChanged = $this->taskHierarchyService->changeStatus($lockedTask, $validated['status'], Auth::id());

                if ($statusChanged) {
                    $this->createManualStatusNotifications($lockedTask, $oldStatus, $validated['status']);
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'status_changed',
                        'entity_type' => 'task',
                        'entity_id' => $lockedTask->id,
                        'description' => 'Mengubah status task "' . $lockedTask->name . '" dari ' . $oldStatus . ' menjadi ' . $validated['status'],
                        'old_value' => ['status' => $oldStatus],
                        'new_value' => ['status' => $validated['status']],
                    ]);

                    if ($validated['status'] !== 'completed' && $lockedTask->priority === 'urgent') {
                        $lockedTask->load('assignees');
                        foreach ($lockedTask->assignees as $assignee) {
                            $this->queueEmailAfterCommit($assignee, 'Urgent Task Alert', 'Task "' . $lockedTask->name . '" is marked as urgent and needs immediate attention!', $lockedTask);
                        }
                    }
                }

                $this->projectProgressService->syncPlannedProgress($lockedTask->project);
                $this->projectProgressService->recordActualProgress($lockedTask->project);
            });

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'status' => $task->fresh()->status]);
            }

            return back()->with('success', 'Status updated successfully!');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to update status.'], 500);
            }

            return back()->withErrors(['error' => 'Failed to update status.']);
        }
    }

    private function appendHierarchyValidationErrors(\Illuminate\Validation\Validator $validator, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        }
    }

    private function createManualStatusNotifications(Task $task, string $oldStatus, string $newStatus): void
    {
        $task->load('assignees');
        $recipientIds = collect($task->assignees->pluck('id')->all())
            ->push($task->created_by)
            ->filter()
            ->unique()
            ->reject(fn($recipientId) => $recipientId == Auth::id());
        $message = 'Status task "' . $task->name . '" diubah dari ' . ucfirst(str_replace('_', ' ', $oldStatus)) . ' menjadi ' . ucfirst(str_replace('_', ' ', $newStatus));

        foreach ($recipientIds as $recipientId) {
            Notification::create([
                'user_id' => $recipientId,
                'type' => 'status_change',
                'title' => 'Status Task Berubah',
                'message' => $message,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ]);

            $recipient = User::find($recipientId);
            if ($recipient) {
                $this->queueEmailAfterCommit($recipient, 'Status Task Berubah', $message, $task);
            }
        }
    }

    private function queueEmailAfterCommit(User $recipient, string $title, string $message, Task $task): void
    {
        DB::afterCommit(function () use ($recipient, $title, $message, $task): void {
            SendEmailNotification::dispatch($recipient, $title, $message, route('tasks.show', $task->token));
        });
    }

    private function hierarchyAncestors(Task $task): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $ancestor = $task->parent;
        $visitedTaskIds = [];

        while ($ancestor !== null && $ancestors->count() < TaskHierarchyService::MAXIMUM_DEPTH) {
            if (isset($visitedTaskIds[$ancestor->id])) {
                break;
            }

            $visitedTaskIds[$ancestor->id] = true;
            $ancestors->prepend($ancestor);
            $ancestor = $ancestor->relationLoaded('parent') ? $ancestor->parent : null;
        }

        return $ancestors;
    }

    /**
     * @param  array<int, true>  $visitedTaskIds
     */
    private function decorateHierarchyTask(Task $task, int $depth, array &$visitedTaskIds): void
    {
        if (isset($visitedTaskIds[$task->id]) || $depth > TaskHierarchyService::MAXIMUM_DEPTH) {
            return;
        }

        $visitedTaskIds[$task->id] = true;
        $subtasks = $task->relationLoaded('subtasks') ? $task->subtasks : collect();

        $task->setAttribute('hierarchy_depth', $depth);
        $task->setAttribute('subtasks_count', $subtasks->count());
        $task->setAttribute('completed_subtasks_count', $subtasks->where('status', 'completed')->count());
        $task->setAttribute('subtask_weight_total', round((float) $subtasks->sum('subtask_weight_percentage'), 2));
        $task->setAttribute('remaining_subtask_weight', max(0, round(100 - (float) $subtasks->sum('subtask_weight_percentage'), 2)));
        $task->setAttribute('hierarchy_progress_percentage', round($this->taskHierarchyService->resolveProgressPercentage($task), 2));

        foreach ($subtasks as $subtask) {
            $this->decorateHierarchyTask($subtask, $depth + 1, $visitedTaskIds);
        }
    }

    public function storeComment(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer cannot add comment.');
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'commented',
            'entity_type' => 'comment',
            'entity_id' => $task->id,
            'description' => 'Added comment on task: ' . $task->name,
        ]);

        return redirect()->route('tasks.show', $token)->with('success', 'Comment added successfully.')->withFragment('comments');
    }

    public function storeAttachment(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer cannot upload attachment.');
        }

        $validated = $request->validate([
            'attachment' => 'required|file|max:10240',
        ]);

        $file = $validated['attachment'];
        $path = $file->store('task-attachments/' . $task->id, 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => Auth::id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'entity_type' => 'attachment',
            'entity_id' => $attachment->id,
            'description' => 'Uploaded attachment for task: ' . $task->name,
        ]);

        return redirect()->route('tasks.show', $token)->with('success', 'Attachment uploaded successfully.')->withFragment('documents');
    }

    public function downloadAttachment(string $token, TaskAttachment $attachment): StreamedResponse
    {
        $task = $this->findByToken($token);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->isSuperAdmin() && (!$task->project->isMember($user) || $attachment->task_id !== $task->id)) {
            abort(403, 'You do not have access to this attachment.');
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->download($attachment->file_path, $attachment->file_name);
    }

    public function ganttData(Request $request)
    {
        return response()->json($this->taskGanttService->forUser(Auth::user(), $request->get('status', 'all')));
    }

    public function tasksJson($id)
    {
        $tasks = Task::where('project_id', $id)->select('id', 'name', 'start_date', 'due_date')->get();

        return response()->json($tasks);
    }

    public function assigneesJson($id)
    {
        $project = Project::findOrFail($id);
        return response()->json($project->members()->select('users.id', 'users.name', 'users.profile_photo')->get());
    }

    public function markSeen(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        if (!$task->project->isMember(Auth::user())) {
            abort(403);
        }

        $type = $request->input('type');

        if (in_array($type, ['comments', 'documents'])) {
            session(["task_{$task->id}_{$type}_seen" => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
