<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    private function findByToken(string $token): Workspace
    {
        return Workspace::where('token', $token)->firstOrFail();
    }

    public function index()
    {
        $workspaces = Auth::user()->workspaces()
            ->withCount('projects')
            ->withCount('members')
            ->with(['members', 'projects' => function ($q) {
                $q->with(['tasks.statusWeight'])
                    ->where(function ($q) {
                        $q->where('status', 'urgent')
                            ->orWhereHas('tasks', fn ($q) => $q->where('priority', 'urgent'));
                    });
            }])
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
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
            'description' => 'Membuat workspace: '.$workspace->name,
        ]);

        return redirect()->route('workspaces.show', $workspace->token)
            ->with('success', 'Workspace created successfully!');
    }

    public function show(string $token)
    {
        $workspace = $this->findByToken($token);
        $user = Auth::user();

        if (! $user->isSuperAdmin() && ! $workspace->isMember($user)) {
            abort(403, 'You do not have access to this workspace.');
        }

        $workspace->load([
            'projects' => function ($query) {
                $query->withCount('tasks')
                    ->with([
                        'tasks.statusWeight',
                        'members:id,name',
                    ])
                    ->orderBy('created_at', 'desc');
            },
            'members' => function ($query) {
                $query->withPivot('role', 'joined_at');
            },
            'invitations' => function ($query) {
                $query->pending()
                    ->where('expires_at', '>', now())
                    ->with('inviter:id,name')
                    ->latest();
            },
        ]);

        $workspace->setRelation(
            'projects',
            $workspace->projects->sortBy(function ($project) {
                $totalWeight = $project->tasks->sum('weight');
                $earnedValue = $project->tasks->sum(
                    fn ($task) => $task->weight * ($task->statusWeight->weight_value ?? 0)
                );
                $progress = $totalWeight > 0 ? ($earnedValue / $totalWeight) * 100 : 0;

                if ($project->status === 'urgent' && $progress < 100) {
                    return 0;
                }

                return 1;
            })->values()
        );

        $currentRole = $workspace->roleForUser($user);

        return view('workspaces.show', compact('workspace', 'currentRole'));
    }

    public function edit(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can edit this workspace.');
        }

        return view('workspaces.edit', compact('workspace'));
    }

    public function update(Request $request, string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can update this workspace.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $workspace->update($validated);
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
            'description' => 'Mengubah workspace: '.$workspace->name,
        ]);

        return redirect()->route('workspaces.show', $workspace->token)
            ->with('success', 'Workspace updated successfully!');
    }

    public function destroy(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageSettings(Auth::user())) {
            abort(403, 'Only workspace owner can delete this workspace.');
        }
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
            'description' => 'Menghapus workspace: '.$workspace->name,
        ]);
        $workspace->delete();

        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace deleted successfully!');
    }

    public function addMember(Request $request, string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(Auth::user())) {
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
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'assigned',
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
            'description' => 'Menambahkan anggota ke workspace: '.$workspace->name,
        ]);

        return back()->with('success', 'Member added successfully.');
    }

    public function updateMemberRole(Request $request, string $token, User $user)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(Auth::user())) {
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

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Member role updated.');
    }

    public function removeMember(string $token, User $user)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(Auth::user())) {
            abort(403, 'Only workspace owner/admin can remove members.');
        }
        if ($workspace->isOwner($user)) {
            return back()->withErrors(['member' => 'Owner cannot be removed.']);
        }
        if ((int) Auth::id() === (int) $user->id) {
            return back()->withErrors(['member' => 'You cannot remove yourself.']);
        }

        $projectsWithUser = $workspace->projects()
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->get(['id', 'name']);

        if ($projectsWithUser->isNotEmpty() && request()->expectsJson()) {
            return response()->json([
                'needs_confirmation' => true,
                'user_name' => $user->name,
                'project_count' => $projectsWithUser->count(),
                'project_names' => $projectsWithUser->pluck('name'),
                'cascade_url' => route('workspaces.members.destroy.cascade', [$workspace->token, $user]),
                'workspace_only_url' => route('workspaces.members.destroy', [$workspace->token, $user]),
            ]);
        }

        $workspace->members()->detach($user->id);

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Member removed from workspace.');
    }

    public function removeMemberCascade(string $token, User $user)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(Auth::user())) {
            abort(403);
        }
        if ($workspace->isOwner($user)) {
            return back()->withErrors(['member' => 'Owner cannot be removed.']);
        }
        if ((int) Auth::id() === (int) $user->id) {
            return back()->withErrors(['member' => 'You cannot remove yourself.']);
        }

        DB::transaction(function () use ($workspace, $user): void {
            $projectIds = $workspace->projects()->pluck('id')->all();
            $taskIds = Task::query()->whereIn('project_id', $projectIds)->pluck('id')->all();

            $projectMembershipCount = DB::table('project_members')
                ->where('user_id', $user->id)
                ->whereIn('project_id', $projectIds)
                ->count();
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

            if ($projectIds !== [] || $taskIds !== []) {
                Notification::query()
                    ->where('user_id', $user->id)
                    ->where(function ($query) use ($projectIds, $taskIds): void {
                        $query->whereIn('project_id', $projectIds)
                            ->orWhereIn('task_id', $taskIds);
                    })
                    ->delete();
            }

            DB::table('project_members')
                ->where('user_id', $user->id)
                ->whereIn('project_id', $projectIds)
                ->delete();
            $workspace->members()->detach($user->id);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'workspace',
                'entity_id' => $workspace->id,
                'description' => sprintf(
                    'Actor %s (ID %d) removed member %s (ID %d) from workspace %s (ID %d); detached %d project membership(s), %d pivot assignment(s), and cleared %d legacy assignment(s).',
                    Auth::user()->name,
                    Auth::id(),
                    $user->name,
                    $user->id,
                    $workspace->name,
                    $workspace->id,
                    $projectMembershipCount,
                    $pivotAssignmentCount,
                    $legacyAssignmentCount,
                ),
                'old_value' => [
                    'target_user_id' => $user->id,
                    'project_membership_count' => $projectMembershipCount,
                    'pivot_assignment_count' => $pivotAssignmentCount,
                    'legacy_assignment_count' => $legacyAssignmentCount,
                ],
                'new_value' => [
                    'workspace_membership' => 'removed',
                    'project_membership_count' => 0,
                    'pivot_assignment_count' => 0,
                    'legacy_assignment_count' => 0,
                ],
            ]);
        });

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Member removed from workspace and all projects.');
    }

    public function generateInviteLink(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(auth()->user())) {
            abort(403);
        }

        $workspace->generateInviteToken();

        return back()->with('success', 'Invite link berhasil dibuat!');
    }

    public function resetInviteLink(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(auth()->user())) {
            abort(403);
        }

        $workspace->resetInviteToken();

        return back()->with('success', 'Invite link berhasil direset. Link lama tidak berlaku lagi.');
    }

    public function revokeInviteLink(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(auth()->user())) {
            abort(403);
        }

        $workspace->revokeInviteToken();

        return back()->with('success', 'Invite link berhasil dinonaktifkan.');
    }

    public function managementIndex()
    {
        Gate::authorize('management-workspaces.view');

        $workspaces = Workspace::with(['creator', 'members', 'projects'])
            ->withCount(['members', 'projects'])
            ->latest()
            ->get();

        return view('managementworkspaces.index', compact('workspaces'));
    }

    public function managementDestroy(string $token)
    {
        Gate::authorize('management-workspaces.delete');

        $workspace = $this->findByToken($token);
        $workspace->delete();

        return redirect()->route('managementworkspaces.index')
            ->with('success', 'Workspace deleted successfully!');
    }
}
