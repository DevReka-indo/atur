<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of workspaces.
     */
    public function index()
    {
        // Get workspaces where user is member
        $workspaces = Auth::user()->workspaces()
            ->withCount('projects')
            ->withCount('members')
            ->latest()
            ->get();

        return view('workspaces.index', compact('workspaces'));
    }

    /**
     * Show the form for creating a new workspace.
     */
    public function create()
    {
        return view('workspaces.create');
    }

    /**
     * Store a newly created workspace in storage.
     */
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

        // Add creator as admin member
        $workspace->members()->attach(Auth::id(), [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace created successfully!');
    }

    /**
     * Display the specified workspace.
     */
    public function show(Workspace $workspace)
    {
        // Check if user is member
        if (!$workspace->isMember(Auth::user())) {
            abort(403, 'You do not have access to this workspace.');
        }

        $workspace->load(['projects', 'members']);

        return view('workspaces.show', compact('workspace'));
    }

    /**
     * Show the form for editing the specified workspace.
     */
    public function edit(Workspace $workspace)
    {
        // Check if user is admin
        if (!$workspace->isAdmin(Auth::user())) {
            abort(403, 'Only workspace admins can edit this workspace.');
        }

        return view('workspaces.edit', compact('workspace'));
    }

    /**
     * Update the specified workspace in storage.
     */
    public function update(Request $request, Workspace $workspace)
    {
        // Check if user is admin
        if (!$workspace->isAdmin(Auth::user())) {
            abort(403, 'Only workspace admins can update this workspace.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $workspace->update($validated);

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace updated successfully!');
    }

    /**
     * Remove the specified workspace from storage.
     */
    public function destroy(Workspace $workspace)
    {
        // Check if user is admin
        if (!$workspace->isAdmin(Auth::user())) {
            abort(403, 'Only workspace admins can delete this workspace.');
        }

        $workspace->delete();

        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace deleted successfully!');
    }
}
