<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index()
    {
        // Get projects where user is member
        $projects = Auth::user()->projects()
            ->with(['workspace', 'members'])
            ->withCount('tasks')
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        // Get workspaces where user is member
        $workspaces = Auth::user()->workspaces;

        return view('projects.create', compact('workspaces'));
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
        ]);

        // Check if user is workspace member
        $workspace = Workspace::findOrFail($validated['workspace_id']);
        if (!$workspace->isMember(Auth::user())) {
            abort(403, 'You must be a workspace member to create projects.');
        }

        $project = Project::create([
            'workspace_id' => $validated['workspace_id'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'created_by' => Auth::id(),
        ]);

        // Add creator as project manager
        $project->members()->attach(Auth::id(), [
            'role' => 'manager',
            'joined_at' => now(),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        // Check if user is member
        if (!$project->isMember(Auth::user())) {
            abort(403, 'You do not have access to this project.');
        }

        $project->load(['workspace', 'members', 'tasks.assignee', 'tasks.statusWeight']);

        // Calculate project progress
        $progress = $project->calculateProgress();

        return view('projects.show', compact('project', 'progress'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        // Check if user is manager
        if (!$project->isManager(Auth::user())) {
            abort(403, 'Only project managers can edit this project.');
        }

        $workspaces = Auth::user()->workspaces;

        return view('projects.edit', compact('project', 'workspaces'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        // Check if user is manager
        if (!$project->isManager(Auth::user())) {
            abort(403, 'Only project managers can update this project.');
        }

        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        // Check if user is manager
        if (!$project->isManager(Auth::user())) {
            abort(403, 'Only project managers can delete this project.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }
}
