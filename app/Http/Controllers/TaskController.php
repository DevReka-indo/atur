<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskStatusHistory;
use App\Services\ProjectProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $projectIds = $user->projects()->pluck('projects.id');

        $tasks = Task::query()
            ->with(['project.workspace', 'assignee', 'statusWeight'])
            ->where(function ($query) use ($user, $projectIds) {
                $query->where('assignee_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereIn('project_id', $projectIds);
            })
            ->latest()
            ->get();

        return view('tasks.index', compact('tasks'));
    }

    public function create(Request $request)
    {
        $projectId = $request->query('project_id');
        $project = null;

        if ($projectId) {
            $project = Project::findOrFail($projectId);
            if (!$project->canContribute(Auth::user())) {
                abort(403, 'Only manager/member can create tasks.');
            }
        }

        $projects = Auth::user()->projects()->wherePivotIn('role', ['manager', 'member'])->get();
        $assignees = $project ? $project->members : collect();

        return view('tasks.create', compact('projects', 'project', 'assignees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'name' => 'required|string|max:500',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'required|in:to_do,in_progress,review,completed,blocked,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'weight' => 'required|numeric|min:0.01',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        if (!$project->canContribute(Auth::user())) {
            abort(403, 'Only manager/member can create tasks.');
        }

        DB::beginTransaction();
        try {
            $task = Task::create([
                'project_id' => $validated['project_id'],
                'parent_task_id' => $validated['parent_task_id'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'assignee_id' => $validated['assignee_id'],
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'weight' => $validated['weight'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
                'created_by' => Auth::id(),
            ]);

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

            app(ProjectProgressService::class)->syncPlannedProgress($project);
            app(ProjectProgressService::class)->recordActualProgress($project);

            DB::commit();

            return redirect()->route('projects.show', $project)
                ->with('success', 'Task created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create task.'])->withInput();
        }
    }

    public function show(Task $task)
    {
        if (!$task->project->isMember(Auth::user())) {
            abort(403, 'You do not have access to this task.');
        }

        $task->load([
            'project.workspace',
            'assignee',
            'creator',
            'statusWeight',
            'subtasks.assignee',
            'comments.user',
            'attachments.uploader',
            'statusHistory.changer'
        ]);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer can only view this task.');
        }

        $project = $task->project;
        $assignees = $project->members;

        return view('tasks.edit', compact('task', 'project', 'assignees'));
    }

    public function update(Request $request, Task $task)
    {
        if (!$task->project->canContribute(Auth::user())) {
            abort(403, 'Viewer can only view this task.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:500',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'required|in:to_do,in_progress,review,completed,blocked,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'weight' => 'required|numeric|min:0.01',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $task->status;
            $changes = [];

            foreach ($validated as $key => $value) {
                if ($task->{$key} != $value) {
                    $changes[$key] = [
                        'old' => $task->{$key},
                        'new' => $value
                    ];
                }
            }

            $task->update($validated);

            if ($oldStatus != $validated['status']) {
                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'from_status' => $oldStatus,
                    'to_status' => $validated['status'],
                    'changed_by' => Auth::id(),
                ]);

                if ($validated['status'] === 'completed') {
                    $task->update(['completed_at' => now()]);
                } else {
                    $task->update(['completed_at' => null]);
                }
            }

            if (!empty($changes)) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'entity_type' => 'task',
                    'entity_id' => $task->id,
                    'description' => 'Updated task: ' . $task->name,
                    'old_value' => array_column($changes, 'old'),
                    'new_value' => array_column($changes, 'new'),
                ]);
            }

            app(ProjectProgressService::class)->syncPlannedProgress($task->project);
            app(ProjectProgressService::class)->recordActualProgress($task->project);

            DB::commit();

            return redirect()->route('tasks.show', $task)
                ->with('success', 'Task updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update task.'])->withInput();
        }
    }

    public function destroy(Task $task)
    {
        if (!$task->project->isManager(Auth::user())) {
            abort(403, 'Only project managers can delete tasks.');
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
            $projectId = $task->project_id;
            $task->delete();

            app(ProjectProgressService::class)->syncPlannedProgress($project);
            app(ProjectProgressService::class)->recordActualProgress($project);

            DB::commit();

            return redirect()->route('projects.show', $projectId)
                ->with('success', 'Task deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete task.']);
        }
    }

    public function storeComment(Request $request, Task $task)
    {
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

        return back()->with('success', 'Comment added successfully.');
    }

    public function storeAttachment(Request $request, Task $task)
    {
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

        return back()->with('success', 'Attachment uploaded successfully.');
    }

    public function downloadAttachment(Task $task, TaskAttachment $attachment)
    {
        if (!$task->project->isMember(Auth::user()) || $attachment->task_id !== $task->id) {
            abort(403, 'You do not have access to this attachment.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }
}
