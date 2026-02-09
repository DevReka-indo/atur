<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Auth::user()->projects()
            ->with(['workspace', 'members'])
            ->withCount('tasks')
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $workspaces = Auth::user()->workspaces;

        return view('projects.create', compact('workspaces'));
    }

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

        $workspace = Workspace::findOrFail($validated['workspace_id']);
        if (!$workspace->canCreateProject(Auth::user())) {
            abort(403, 'Only workspace owner/admin can create projects.');
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

        $project->members()->attach(Auth::id(), [
            'role' => 'manager',
            'joined_at' => now(),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully!');
    }

    public function show(Project $project)
    {
        if (!$project->isMember(Auth::user())) {
            abort(403, 'You do not have access to this project.');
        }

        $project->load(['workspace', 'workspace.members', 'members', 'tasks.assignee', 'tasks.statusWeight']);
        $progress = $project->calculateProgress();
        $availableMembers = $project->workspace->members->whereNotIn('id', $project->members->pluck('id'));

        return view('projects.show', compact('project', 'progress', 'availableMembers'));
    }

    public function edit(Project $project)
    {
        if (!$project->workspace->canCreateProject(Auth::user())) {
            abort(403, 'Only workspace owner/admin can edit this project.');
        }

        $workspaces = Auth::user()->workspaces;

        return view('projects.edit', compact('project', 'workspaces'));
    }

    public function update(Request $request, Project $project)
    {
        if (!$project->workspace->canCreateProject(Auth::user())) {
            abort(403, 'Only workspace owner/admin can update this project.');
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

    public function destroy(Project $project)
    {
        if (!$project->workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can delete this project.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }

    public function addMember(Request $request, Project $project)
    {
        if (!$project->workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can manage project members.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,member,viewer',
        ]);

        if (!$project->workspace->members()->where('user_id', $validated['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'User must join workspace first.']);
        }

        if ($project->members()->where('user_id', $validated['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'User already in this project.']);
        }

        $project->members()->attach($validated['user_id'], [
            'role' => $validated['role'],
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Project member added.');
    }

    public function updateMemberRole(Request $request, Project $project, User $user)
    {
        if (!$project->workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can manage project members.');
        }

        $validated = $request->validate([
            'role' => 'required|in:manager,member,viewer',
        ]);

        $project->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Project member role updated.');
    }

    public function removeMember(Project $project, User $user)
    {
        if (!$project->workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can manage project members.');
        }

        if ((int) $project->created_by === (int) $user->id) {
            return back()->withErrors(['member' => 'Project creator cannot be removed.']);
        }

        $project->members()->detach($user->id);

        return back()->with('success', 'Project member removed.');
    }
}
