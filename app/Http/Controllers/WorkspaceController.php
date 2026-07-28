<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ActivityLogService;
use App\Services\WorkspaceChatService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class WorkspaceController extends Controller
{
    private const SHOW_TABS = ['overview', 'members', 'activity', 'chat'];

    private const WORKSPACE_ACTIVITY_EVENT_GROUPS = [
        'member' => [
            ActivityLog::EVENT_WORKSPACE_MEMBER_ADDED,
            ActivityLog::EVENT_WORKSPACE_MEMBER_ROLE_CHANGED,
            ActivityLog::EVENT_WORKSPACE_MEMBER_REMOVED,
        ],
        'invitation' => [
            ActivityLog::EVENT_WORKSPACE_INVITATION_SENT,
            ActivityLog::EVENT_WORKSPACE_INVITATION_RESENT,
            ActivityLog::EVENT_WORKSPACE_INVITATION_REVOKED,
            ActivityLog::EVENT_WORKSPACE_INVITATION_ACCEPTED,
        ],
        'invite_link' => [
            ActivityLog::EVENT_WORKSPACE_INVITE_LINK_REGENERATED,
            ActivityLog::EVENT_WORKSPACE_INVITE_LINK_DISABLED,
            ActivityLog::EVENT_WORKSPACE_JOINED_VIA_INVITE_LINK,
        ],
    ];

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

    public function show(
        Request $request,
        string $token,
        WorkspaceChatService $chatService,
    ) {
        $workspace = $this->findByToken($token);
        $user = $request->user();

        if (! $user->isSuperAdmin()
            && ! $workspace->isOwner($user)
            && ! $workspace->isMember($user)) {
            abort(403, 'You do not have access to this workspace.');
        }

        $requestedTab = $request->string('tab')->toString();
        $activeTab = in_array($requestedTab, self::SHOW_TABS, true)
            ? $requestedTab
            : 'overview';
        $currentRole = $workspace->roleForUser($user);
        $canManageMembers = in_array($currentRole, [Workspace::ROLE_OWNER, Workspace::ROLE_ADMIN], true);
        $canCreateProject = $canManageMembers;
        $canViewInvitationEmail = $canManageMembers;
        $activities = null;
        $activityFilter = 'all';
        $activitySearch = '';
        $chatMessages = collect();
        $chatHasMore = false;
        $chatUnreadCount = 0;
        $chatTargetMessageId = null;
        $chatTargetMissing = false;

        $workspace->loadCount('projects');

        if (Schema::hasTable('workspace_chat_messages')
            && Schema::hasTable('workspace_chat_reads')) {
            $chatUnreadCount = $chatService->unreadCount($workspace, $user);
        }

        if ($activeTab === 'overview') {
            $workspace->load([
                'projects' => function ($query) {
                    $query->withCount('tasks')
                        ->with([
                            'tasks.statusWeight',
                            'members:id,name',
                        ])
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
        }

        if ($activeTab === 'members') {
            $workspace->load([
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
        }

        if ($activeTab === 'activity') {
            $activityFilter = array_key_exists(
                $request->string('filter')->toString(),
                self::WORKSPACE_ACTIVITY_EVENT_GROUPS,
            )
                ? $request->string('filter')->toString()
                : 'all';
            $activitySearch = $request->string('search')->trim()->limit(100)->toString();
            $activities = $this->workspaceActivities(
                $workspace,
                $activityFilter,
                $activitySearch,
                $canViewInvitationEmail,
            );
        }

        if ($activeTab === 'chat'
            && Schema::hasTable('workspace_chat_messages')
            && Schema::hasTable('workspace_chat_reads')) {
            $requestedMessageId = $request->integer('message');
            $chatTargetMessageId = $requestedMessageId > 0
                && $workspace->chatMessages()->whereKey($requestedMessageId)->exists()
                    ? $requestedMessageId
                    : null;
            $chatTargetMissing = $request->has('message')
                && $chatTargetMessageId === null;
            $chatPage = $chatTargetMessageId !== null
                ? $chatService->messages($workspace, $chatTargetMessageId + 1)
                : $chatService->messages($workspace);
            $chatMessages = $chatPage['messages'];
            $chatMessages->each(function ($message) use ($chatService): void {
                $message->setAttribute(
                    'rendered_content',
                    $chatService->renderedContent($message->content),
                );
            });
            $chatHasMore = $chatPage['has_more'];
            $chatService->markRead($workspace, $user, $chatMessages->last()?->id);
            $chatUnreadCount = $chatService->unreadCount($workspace, $user);
        }

        return view('workspaces.show', compact(
            'workspace',
            'currentRole',
            'activeTab',
            'canManageMembers',
            'canCreateProject',
            'canViewInvitationEmail',
            'activities',
            'activityFilter',
            'activitySearch',
            'chatMessages',
            'chatHasMore',
            'chatUnreadCount',
            'chatTargetMessageId',
            'chatTargetMissing',
        ));
    }

    private function workspaceActivities(
        Workspace $workspace,
        string $filter,
        string $search,
        bool $canViewInvitationEmail,
    ): LengthAwarePaginator {
        return ActivityLog::query()
            ->select([
                'id',
                'user_id',
                'action',
                'entity_type',
                'entity_id',
                'description',
                'old_value',
                'new_value',
                'created_at',
                'updated_at',
            ])
            ->with('user:id,name')
            ->where('entity_type', 'workspace')
            ->where('entity_id', $workspace->id)
            ->when(
                $filter !== 'all',
                fn ($query) => $query->whereIn(
                    'new_value->event',
                    self::WORKSPACE_ACTIVITY_EVENT_GROUPS[$filter],
                ),
            )
            ->when($search !== '', function ($query) use ($search, $canViewInvitationEmail): void {
                $likeSearch = "%{$search}%";

                $query->where(function ($searchQuery) use (
                    $likeSearch,
                    $canViewInvitationEmail,
                ): void {
                    $searchQuery
                        ->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $likeSearch))
                        ->orWhere('new_value->target_name', 'like', $likeSearch);

                    if ($canViewInvitationEmail) {
                        $searchQuery
                            ->orWhere('description', 'like', $likeSearch)
                            ->orWhere('new_value->target_email', 'like', $likeSearch);
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
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

        $targetUser = User::query()->findOrFail($validated['user_id']);

        DB::transaction(function () use ($workspace, $targetUser, $validated): void {
            $workspace->members()->attach($targetUser->id, [
                'role' => $validated['role'],
                'joined_at' => now(),
            ]);
            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_MEMBER_ADDED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => $targetUser->id,
                    'target_name' => $targetUser->name,
                    'target_email' => $targetUser->email,
                    'role' => $validated['role'],
                    'role_label' => Workspace::roleLabel($validated['role']),
                    'source' => 'registered_user',
                    'status' => 'active',
                ],
            );
        });

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Member added successfully.');
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

        $oldRole = $workspace->roleForUser($user);

        DB::transaction(function () use ($workspace, $user, $validated, $oldRole): void {
            $workspace->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);
            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_MEMBER_ROLE_CHANGED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => $user->id,
                    'target_name' => $user->name,
                    'target_email' => $user->email,
                    'role' => $validated['role'],
                    'role_label' => Workspace::roleLabel($validated['role']),
                    'old_role' => $oldRole,
                    'old_role_label' => Workspace::roleLabel((string) $oldRole),
                    'source' => 'registered_user',
                    'status' => 'active',
                ],
                [
                    'role' => $oldRole,
                    'role_label' => Workspace::roleLabel((string) $oldRole),
                ],
            );
        });

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

        $role = $workspace->roleForUser($user);

        DB::transaction(function () use ($workspace, $user, $role): void {
            $workspace->members()->detach($user->id);
            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_MEMBER_REMOVED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => $user->id,
                    'target_name' => $user->name,
                    'target_email' => $user->email,
                    'role' => $role,
                    'role_label' => Workspace::roleLabel((string) $role),
                    'source' => 'registered_user',
                    'status' => 'removed',
                ],
                [
                    'target_user_id' => $user->id,
                    'role' => $role,
                    'role_label' => Workspace::roleLabel((string) $role),
                    'status' => 'active',
                ],
            );
        });

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
            $role = $workspace->roleForUser($user);
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

            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_MEMBER_REMOVED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => $user->id,
                    'target_name' => $user->name,
                    'target_email' => $user->email,
                    'role' => $role,
                    'role_label' => Workspace::roleLabel((string) $role),
                    'source' => 'registered_user',
                    'status' => 'removed',
                    'project_membership_count' => $projectMembershipCount,
                    'pivot_assignment_count' => $pivotAssignmentCount,
                    'legacy_assignment_count' => $legacyAssignmentCount,
                ],
                [
                    'target_user_id' => $user->id,
                    'role' => $role,
                    'role_label' => Workspace::roleLabel((string) $role),
                    'status' => 'active',
                    'project_membership_count' => $projectMembershipCount,
                    'pivot_assignment_count' => $pivotAssignmentCount,
                    'legacy_assignment_count' => $legacyAssignmentCount,
                ],
            );
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

        DB::transaction(function () use ($workspace): void {
            $workspace->generateInviteToken();
            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_INVITE_LINK_REGENERATED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => null,
                    'role' => Workspace::ROLE_MEMBER,
                    'role_label' => Workspace::roleLabel(Workspace::ROLE_MEMBER),
                    'source' => 'reusable_link',
                    'status' => 'active',
                    'expires_at' => $workspace->invite_token_expires_at?->toIso8601String(),
                ],
            );
        });

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Invite link berhasil dibuat!');
    }

    public function resetInviteLink(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(auth()->user())) {
            abort(403);
        }

        DB::transaction(function () use ($workspace): void {
            $workspace->resetInviteToken();
            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_INVITE_LINK_REGENERATED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => null,
                    'role' => Workspace::ROLE_MEMBER,
                    'role_label' => Workspace::roleLabel(Workspace::ROLE_MEMBER),
                    'source' => 'reusable_link',
                    'status' => 'active',
                    'expires_at' => $workspace->invite_token_expires_at?->toIso8601String(),
                ],
            );
        });

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Invite link berhasil direset. Link lama tidak berlaku lagi.');
    }

    public function revokeInviteLink(string $token)
    {
        $workspace = $this->findByToken($token);

        if (! $workspace->canManageMembers(auth()->user())) {
            abort(403);
        }

        DB::transaction(function () use ($workspace): void {
            $workspace->revokeInviteToken();
            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_INVITE_LINK_DISABLED,
                $workspace,
                Auth::user(),
                [
                    'invitation_id' => null,
                    'target_user_id' => null,
                    'role' => Workspace::ROLE_MEMBER,
                    'role_label' => Workspace::roleLabel(Workspace::ROLE_MEMBER),
                    'source' => 'reusable_link',
                    'status' => 'disabled',
                    'expires_at' => null,
                ],
            );
        });

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Invite link berhasil dinonaktifkan.');
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
