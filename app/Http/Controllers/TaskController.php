<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\TaskStatusHistory;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TaskComment;
use App\Models\TaskAttachment;
use App\Services\ProjectProgressService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Jobs\SendEmailNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
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

    public function index(Request $request)
    {
        $user = Auth::user();
        app(DashboardController::class)->sendDeadlineNotificationsPublic($user);
        $view = $request->get('view', 'list');
        $status = $request->get('status', 'all');

        $query = Task::query()
            ->with(['project.workspace', 'assignees', 'statusWeight', 'subtasks'])
            ->whereHas('assignees', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tasks = $query->orderByRaw("FIELD(priority, 'urgent') DESC")->latest()->get();
        $kanbanStatuses = [
            'to_do' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'completed' => 'Completed',
            'stopped' => 'Stopped',
            'cancelled' => 'Cancelled',
        ];
        $kanbanTasks = $tasks->groupBy('status');

        return view('tasks.index', compact('tasks', 'view', 'kanbanStatuses', 'kanbanTasks'));
    }

    public function create(Request $request)
    {
        $projectToken = $request->query('project_token');
        $project = null;

        if ($projectToken) {
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

        return view('tasks.create', compact('projects', 'project', 'assignees'));
    }

    public function store(Request $request)
    {
        $projectId = $request->integer('project_id');

        $validated = $request->validate(
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

        $project = Project::findOrFail($validated['project_id']);
        if (!$project->canContribute(Auth::user())) {
            abort(403, 'Only manager/member can create tasks.');
        }

        DB::beginTransaction();
        try {
            $task = Task::create([
                'project_id' => $validated['project_id'],
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'weight' => $validated['weight'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
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
                    Notification::create([
                        'user_id' => $assigneeId,
                        'type' => 'assignment',
                        'title' => 'Task Baru Ditugaskan',
                        'message' => 'Anda mendapat task baru: "' . $task->name . '" di project ' . $project->name,
                        'task_id' => $task->id,
                        'project_id' => $project->id,
                    ]);

                    // Kirim email via Job
                    $recipient = User::find($assigneeId);
                    if ($recipient) {
                        SendEmailNotification::dispatch($recipient, 'Task Baru Ditugaskan', 'Anda mendapat task baru: "' . $task->name . '" di project ' . $project->name, route('tasks.show', $task->token));
                    }
                }
            }

            if ($task->priority === 'urgent') {
                foreach ($assigneeIds as $assigneeId) {
                    $assignee = User::find($assigneeId);
                    if ($assignee) {
                        SendEmailNotification::dispatch($assignee, 'Urgent Task Alert', 'Task "' . $task->name . '" is marked as urgent and needs immediate attention!', route('tasks.show', $task->token));
                    }
                }
            }
            app(ProjectProgressService::class)->syncPlannedProgress($project);
            app(ProjectProgressService::class)->recordActualProgress($project);

            DB::commit();

            return redirect()->route('projects.show', $project->token)->with('success', 'Task created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Failed to create task: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function edit(string $token)
    {
        $task = $this->findByToken($token);
        $task->load('project', 'assignee');

        if (!$task->project) {
            abort(404, 'Task tidak memiliki project.');
        }

        if (!$task->project->isMember(Auth::user())) {
            abort(403);
        }

        session(['task_back_url' => url()->previous()]);

        $projects = Auth::user()->projects;
        $assignees = $task->project->members;
        $back_url = url()->previous();

        return view('tasks.edit', compact('task', 'projects', 'assignees', 'back_url'));
    }

    public function show(string $token)
    {
        $task = $this->findByToken($token);

        $isSuperAdmin = Auth::user()->isSuperAdmin();
        if (!$isSuperAdmin && !$task->project->isMember(Auth::user())) {
            abort(403, 'You do not have access to this task.');
        }

        $task->load(['project.workspace', 'assignee', 'creator', 'statusWeight', 'subtasks.assignee', 'comments.user', 'attachments.uploader', 'statusHistory.changer']);

        // Deteksi dari mana task diakses
        $fromMyTask = str_contains(url()->previous(), '/tasks') && !str_contains(url()->previous(), '/projects');

        return view('tasks.show', compact('task', 'fromMyTask'));
    }

    public function update(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer can only view this task.');
        }

        $validator = Validator::make(
            $request->all(),
            [
                'parent_task_id' => ['prohibited'],
                'name' => ['required', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'assignee_ids' => ['nullable', 'array'],
                'assignee_ids.*' => ['distinct', Rule::exists('project_members', 'user_id')->where(fn($query) => $query->where('project_id', $task->project_id))],
                'status' => ['required', 'in:to_do,in_progress,review,completed,stopped,cancelled'],
                'priority' => ['required', 'in:low,medium,high,urgent'],
                'weight' => ['required', 'numeric', 'min:0.01'],
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

        $validator->after(function (\Illuminate\Validation\Validator $validator) use ($request, $task): void {
            if (!$request->filled('predecessor_id') || $validator->errors()->has('predecessor_id')) {
                return;
            }

            if ($this->wouldCreateCircularDependency($task, $request->integer('predecessor_id'))) {
                $validator->errors()->add('predecessor_id', 'The selected predecessor creates a circular dependency.');
            }
        });

        $validated = $validator->validate();

        DB::beginTransaction();
        try {
            $oldStatus = $task->status;
            $oldAssigneeIds = $task->assignees->pluck('id')->toArray();
            $changes = [];

            foreach ($validated as $key => $value) {
                if ($key === 'assignee_ids') {
                    continue;
                }
                if ($task->{$key} != $value) {
                    $changes[$key] = ['old' => $task->{$key}, 'new' => $value];
                }
            }

            $task->update(
                array_merge(
                    collect($validated)
                        ->except(['assignee_ids', 'parent_task_id', 'predecessor_id', 'dependency_type'])
                        ->toArray(),
                    [
                        'predecessor_id' => $validated['predecessor_id'] ?? null,
                        'dependency_type' => $validated['dependency_type'] ?? 'FS',
                    ],
                ),
            );

            $newAssigneeIds = $validated['assignee_ids'] ?? [];
            $task->assignees()->sync($newAssigneeIds);

            $addedAssignees = array_diff($newAssigneeIds, $oldAssigneeIds);
            foreach ($addedAssignees as $assigneeId) {
                if ($assigneeId != Auth::id()) {
                    Notification::create([
                        'user_id' => $assigneeId,
                        'type' => 'assignment',
                        'title' => 'Task Ditugaskan ke Anda',
                        'message' => 'Anda ditugaskan ke task: "' . $task->name . '" di project ' . $task->project->name,
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                    ]);

                    // Kirim email via Job
                    $recipient = User::find($assigneeId);
                    if ($recipient) {
                        SendEmailNotification::dispatch($recipient, 'Task Ditugaskan ke Anda', 'Anda ditugaskan ke task: "' . $task->name . '" di project ' . $task->project->name, route('tasks.show', $task->token));
                    }
                }
            }

            if ($oldStatus != $validated['status']) {
                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'from_status' => $oldStatus,
                    'to_status' => $validated['status'],
                    'changed_by' => Auth::id(),
                ]);

                if ($validated['status'] === 'stopped') {
                    $previousWeight = \App\Models\TaskStatusWeight::where('status', $oldStatus)->first();
                    $currentProgress = $previousWeight ? $previousWeight->weight_value * 100 : 0;

                    $task->update([
                        'stopped_progress' => $currentProgress,
                        'completed_at' => null,
                    ]);
                } elseif ($validated['status'] === 'completed') {
                    $task->update([
                        'stopped_progress' => null,
                        'completed_at' => now(),
                    ]);
                } else {
                    $task->update([
                        'stopped_progress' => null,
                        'completed_at' => null,
                    ]);
                }

                $notifMessage = 'Status task "' . $task->name . '" diubah dari ' . ucfirst(str_replace('_', ' ', $oldStatus)) . ' menjadi ' . ucfirst(str_replace('_', ' ', $validated['status']));

                $recipients = collect($newAssigneeIds)->push($task->created_by)->filter()->unique()->reject(fn($id) => $id == Auth::id());

                foreach ($recipients as $recipientId) {
                    Notification::create([
                        'user_id' => $recipientId,
                        'type' => 'status_change',
                        'title' => 'Status Task Berubah',
                        'message' => $notifMessage,
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                    ]);

                    // Kirim email via Job
                    $recipient = User::find($recipientId);
                    if ($recipient) {
                        SendEmailNotification::dispatch($recipient, 'Status Task Berubah', $notifMessage, route('tasks.show', $task->token));
                    }
                }
            }

            if (!empty($changes)) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'entity_type' => 'task',
                    'entity_id' => $task->id,
                    'description' => 'Updated task: ' . $task->name,
                    'old_value' => json_encode($changes),
                    'new_value' => json_encode($changes),
                ]);
            }

            app(ProjectProgressService::class)->syncPlannedProgress($task->project);
            app(ProjectProgressService::class)->recordActualProgress($task->project);

            DB::commit();

            $backUrl = session('task_back_url', route('projects.show', $task->project->token));
            return redirect($backUrl)->with('success', 'Task updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
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

        DB::beginTransaction();
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'description' => 'Deleted task: ' . $task->name,
            ]);

            $project = $task->project;
            $projectToken = $project->token;
            $task->delete();

            app(ProjectProgressService::class)->syncPlannedProgress($project);
            app(ProjectProgressService::class)->recordActualProgress($project);

            DB::commit();

            $backUrl = $request->input('back_url') ?? (session('task_back_url') ?? route('projects.show', $projectToken));

            return redirect($backUrl)->with('success', 'Task deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete task.']);
        }
    }

    public function updateStatus(Request $request, string $token)
    {
        $task = $this->findByToken($token);

        $isSuperAdmin = Auth::user()->isSuperAdmin();
        if (!$isSuperAdmin && !$task->project->isMember(Auth::user())) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:to_do,in_progress,review,completed,stopped,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $task->status;

            if ($request->status === 'stopped') {
                $previousWeight = \App\Models\TaskStatusWeight::where('status', $oldStatus)->first();
                $currentProgress = $previousWeight ? $previousWeight->weight_value * 100 : 0;

                $task->update([
                    'status' => $request->status,
                    'stopped_progress' => $currentProgress,
                    'completed_at' => null,
                ]);
            } elseif ($request->status === 'completed') {
                $task->update([
                    'status' => $request->status,
                    'stopped_progress' => null,
                    'completed_at' => now(),
                ]);
            } else {
                $task->update([
                    'status' => $request->status,
                    'stopped_progress' => null,
                    'completed_at' => null,
                ]);
            }

            TaskStatusHistory::create([
                'task_id' => $task->id,
                'from_status' => $oldStatus,
                'to_status' => $request->status,
                'changed_by' => Auth::id(),
            ]);

            $notifMessage = 'Status task "' . $task->name . '" diubah dari ' . ucfirst(str_replace('_', ' ', $oldStatus)) . ' menjadi ' . ucfirst(str_replace('_', ' ', $request->status));

            $task->load('assignees');

            $recipients = collect($task->assignees->pluck('id')->toArray())
                ->push($task->created_by)
                ->filter()
                ->unique()
                ->reject(fn($id) => $id == Auth::id());

            foreach ($recipients as $recipientId) {
                Notification::create([
                    'user_id' => $recipientId,
                    'type' => 'status_change',
                    'title' => 'Status Task Berubah',
                    'message' => $notifMessage,
                    'task_id' => $task->id,
                    'project_id' => $task->project_id,
                ]);

                // Kirim email via Job
                $recipient = User::find($recipientId);
                if ($recipient) {
                    SendEmailNotification::dispatch($recipient, 'Status Task Berubah', $notifMessage, route('tasks.show', $task->token));
                }
            }

            app(ProjectProgressService::class)->syncPlannedProgress($task->project);
            app(ProjectProgressService::class)->recordActualProgress($task->project);
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'description' => 'Mengubah status task "' . $task->name . '" dari ' . $oldStatus . ' menjadi ' . $request->status,
                'old_value' => ['status' => $oldStatus],
                'new_value' => ['status' => $request->status],
            ]);
            DB::commit();

            // Kirim email jika task urgent
            if ($request->status !== 'completed' && $task->priority === 'urgent') {
                $task->load('assignees');
                foreach ($task->assignees as $assignee) {
                    SendEmailNotification::dispatch($assignee, 'Urgent Task Alert', 'Task "' . $task->name . '" is marked as urgent and needs immediate attention!', route('tasks.show', $task->token));
                }
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'status' => $task->status]);
            }
            return back()->with('success', 'Status updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update status.']);
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
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = Task::query()
            ->with(['project', 'assignees', 'assignee'])
            ->whereHas('assignees', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereNotNull('start_date')
            ->whereNotNull('due_date');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tasks = $query->get();

        $depTypeMap = ['FS' => 0, 'SS' => 1, 'FF' => 2, 'SF' => 3];

        $data = $tasks->map(function ($task) {
            $start = Carbon::parse($task->start_date);
            $end = Carbon::parse($task->due_date);
            $duration = max(1, $start->diffInDays($end));

            $progress = match ($task->status) {
                'completed' => 1,
                'review' => 0.8,
                'in_progress' => 0.5,
                default => 0,
            };

            $resource = '';
            if ($task->assignees->count()) {
                $resource = $task->assignees->pluck('name')->implode(', ');
            } elseif ($task->assignee) {
                $resource = $task->assignee->name;
            }

            $isMilestone = $start->equalTo($end);

            $item = [
                'id' => $task->id,
                'text' => $task->name . ($task->project ? ' (' . $task->project->name . ')' : ''),
                'start_date' => $start->format('d-m-Y'),
                'duration' => $duration,
                'progress' => $progress,
                'status' => $task->status,
                'priority' => $task->priority,
                'token' => $task->token,
                'predecessor_id' => $task->predecessor_id,
                'dependency_type' => $task->dependency_type ?? 'FS',
                'resource' => $resource,
            ];

            if ($task->parent_task_id) {
                $item['parent'] = $task->parent_task_id;
            }

            if ($isMilestone) {
                $item['type'] = 'milestone';
            }

            return $item;
        });

        $links = $tasks
            ->whereNotNull('predecessor_id')
            ->values()
            ->map(function ($task) use ($depTypeMap) {
                return [
                    'id' => $task->id,
                    'source' => $task->predecessor_id,
                    'target' => $task->id,
                    'type' => (string) ($depTypeMap[$task->dependency_type ?? 'FS'] ?? 0),
                ];
            });

        return response()->json([
            'data' => $data->values(),
            'links' => $links->values(),
        ]);
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
