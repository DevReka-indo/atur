<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function index()
    {
        $workspaces = Auth::user()->workspaces()
            ->withCount('projects')
            ->withCount('members')
            ->latest()
            ->get();

        return view('workspaces.index', compact('workspaces'));
    }

    public function create()
    {
        return view('workspaces.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'created_by' => Auth::id(),
        ]);

        $workspace->members()->attach(Auth::id(), [
            'role' => Workspace::ROLE_OWNER,
            'joined_at' => now(),
        ]);

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace created successfully!');
    }

    public function show(Workspace $workspace)
    {
        $user = Auth::user();
        if (!$workspace->isMember($user)) {
            abort(403, 'You do not have access to this workspace.');
        }

        $workspace->load([
            'projects' => function ($query) {
                $query->withCount('tasks')
                    ->with([
                        'tasks.statusWeight',
                        'members:id,name',
                    ]);
            },
            'members',
        ]);
        $availableUsers = User::whereNotIn('id', $workspace->members->pluck('id'))
            ->orderBy('name')
            ->get();
        $currentRole = $workspace->roleForUser($user);

        return view('workspaces.show', compact('workspace', 'availableUsers', 'currentRole'));
    }

    public function edit(Workspace $workspace)
    {
        if (!$workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can edit this workspace.');
        }

        return view('workspaces.edit', compact('workspace'));
    }

    public function update(Request $request, Workspace $workspace)
    {
        if (!$workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can update this workspace.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $workspace->update($validated);

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace updated successfully!');
    }

    public function destroy(Workspace $workspace)
    {
        if (!$workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can delete this workspace.');
        }

        $workspace->delete();

        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace deleted successfully!');
    }

    public function addMember(Request $request, Workspace $workspace)
    {
        if (!$workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can add members.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member',
        ]);

        if ($workspace->members()->where('user_id', $validated['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'User already in this workspace.']);
        }

        $workspace->members()->attach($validated['user_id'], [
            'role' => $validated['role'],
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Member added successfully.');
    }

    public function updateMemberRole(Request $request, Workspace $workspace, User $user)
    {
        if (!$workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can update members.');
        }

        if ($workspace->isOwner($user)) {
            return back()->withErrors(['role' => 'Owner role cannot be changed.']);
        }

        if ((int) Auth::id() === (int) $user->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,member',
        ]);

        $workspace->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Member role updated.');
    }

    public function removeMember(Workspace $workspace, User $user)
    {
        if (!$workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can remove members.');
        }

        if ($workspace->isOwner($user)) {
            return back()->withErrors(['member' => 'Owner cannot be removed.']);
        }

        if ((int) Auth::id() === (int) $user->id) {
            return back()->withErrors(['member' => 'You cannot remove yourself from this workspace.']);
        }

        $workspace->members()->detach($user->id);

        return back()->with('success', 'Member removed.');
    }
}
