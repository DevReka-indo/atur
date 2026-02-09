<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\TaskStatusHistory;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index()
    {
        // Get tasks assigned to current user
        $tasks = Auth::user()->assignedTasks()
            ->with(['project.workspace', 'assignee', 'statusWeight'])
            ->latest()
            ->get();

        return view('tasks.index', compact('tasks'));
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(Request $request)
    {
        $projectId = $request->query('project_id');
        $project = null;

        if ($projectId) {
            $project = Project::findOrFail($projectId);

            // Check if user is project member
            if (!$project->isMember(Auth::user())) {
                abort(403, 'You must be a project member to create tasks.');
            }
        }

        // Get projects where user is member
        $projects = Auth::user()->projects;

        // Get potential assignees (project members)
        $assignees = $project ? $project->members : collect();

        return view('tasks.create', compact('projects', 'project', 'assignees'));
    }

    /**
     * Store a newly created task in storage.
     */
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

        // Check if user is project member
        $project = Project::findOrFail($validated['project_id']);
        if (!$project->isMember(Auth::user())) {
            abort(403, 'You must be a project member to create tasks.');
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

            // Log status history
            TaskStatusHistory::create([
                'task_id' => $task->id,
                'from_status' => null,
                'to_status' => $validated['status'],
                'changed_by' => Auth::id(),
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'description' => 'Created task: ' . $task->name,
            ]);

            DB::commit();

            return redirect()->route('projects.show', $project)
                ->with('success', 'Task created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create task.'])->withInput();
        }
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task)
    {
        // Check if user is project member
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

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        // Check if user is project member
        if (!$task->project->isMember(Auth::user())) {
            abort(403, 'You do not have access to edit this task.');
        }

        $project = $task->project;
        $assignees = $project->members;

        return view('tasks.edit', compact('task', 'project', 'assignees'));
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Task $task)
    {
        // Check if user is project member
        if (!$task->project->isMember(Auth::user())) {
            abort(403, 'You do not have access to update this task.');
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

            // Track changes
            foreach ($validated as $key => $value) {
                if ($task->{$key} != $value) {
                    $changes[$key] = [
                        'old' => $task->{$key},
                        'new' => $value
                    ];
                }
            }

            // Update task
            $task->update($validated);

            // If status changed, log it
            if ($oldStatus != $validated['status']) {
                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'from_status' => $oldStatus,
                    'to_status' => $validated['status'],
                    'changed_by' => Auth::id(),
                ]);

                // If completed, set completed_at
                if ($validated['status'] === 'completed') {
                    $task->update(['completed_at' => now()]);
                } else {
                    $task->update(['completed_at' => null]);
                }
            }

            // Log activity
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

            DB::commit();

            return redirect()->route('tasks.show', $task)
                ->with('success', 'Task updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update task.'])->withInput();
        }
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        // Check if user is project manager
        if (!$task->project->isManager(Auth::user())) {
            abort(403, 'Only project managers can delete tasks.');
        }

        DB::beginTransaction();
        try {
            // Log activity before delete
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'description' => 'Deleted task: ' . $task->name,
            ]);

            $projectId = $task->project_id;
            $task->delete();

            DB::commit();

            return redirect()->route('projects.show', $projectId)
                ->with('success', 'Task deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete task.']);
        }
    }
}
