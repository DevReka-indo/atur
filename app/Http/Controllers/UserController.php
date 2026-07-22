<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * @return list<string>
     */
    private function substantiveDataLabels(User $user): array
    {
        $checks = [
            'workspace ownership' => $user->createdWorkspaces()->exists(),
            'project ownership' => $user->createdProjects()->exists(),
            'task ownership' => $user->createdTasks()->exists(),
            'task status history' => TaskStatusHistory::where('changed_by', $user->id)->exists(),
            'task comments' => $user->comments()->exists(),
            'task attachments' => $user->attachments()->exists(),
            'activity logs' => $user->activityLogs()->exists(),
            'project baselines' => $user->createdBaselines()->exists(),
            'actual progress' => $user->recordedProgress()->exists(),
            'discussion threads' => ProjectThread::where('user_id', $user->id)->exists(),
            'discussion messages' => ProjectThreadMessage::where('user_id', $user->id)->exists(),
            'invitations' => Invitation::where('invited_by', $user->id)->exists(),
        ];

        return array_keys(array_filter($checks));
    }

    public function index(Request $request)
    {
        Gate::authorize('management-users.view');

        $search = $request->get('search');
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        // Whitelist kolom yang boleh di-sort
        $allowedSorts = ['name', 'role', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $users = User::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
        })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        // Untuk autocomplete
        if ($request->ajax()) {
            $suggestions = User::where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orderBy('name', 'asc')
                ->limit(5)
                ->get(['id', 'name', 'email']);

            return response()->json($suggestions);
        }

        return view('managementusers.index', compact('users', 'search'));
    }

    public function show(User $management_user)
    {
        Gate::authorize('management-users.view');

        $management_user->load([
            'roles.permissions',
            'workspaces' => function ($query) {
                $query->withPivot('role', 'joined_at')->latest('workspace_members.joined_at');
            },
            'projects' => function ($query) {
                $query->withPivot('role', 'joined_at')->latest('project_members.joined_at');
            },
        ]);

        $effectivePermissions = $management_user->isSuperAdmin() ? collect() : $management_user->getAllPermissions()->sortBy('name')->values();

        $permissionsByGroup = $effectivePermissions->groupBy(function ($permission): string {
            return str($permission->name)->before('.')->replace('-', ' ')->title()->toString();
        });

        $assignedTaskQuery = Task::query()->where(function ($query) use ($management_user): void {
            $query->where('assignee_id', $management_user->id)->orWhereHas('assignees', function ($assigneeQuery) use ($management_user): void {
                $assigneeQuery->where('users.id', $management_user->id);
            });
        });

        $activeTaskCount = (clone $assignedTaskQuery)->whereNotIn('status', ['completed', 'cancelled'])->count();

        $completedTaskCount = (clone $assignedTaskQuery)->where('status', 'completed')->count();

        $createdTaskCount = $management_user->createdTasks()->count();

        $recentActivities = $management_user->activityLogs()->latest()->limit(10)->get();

        $roleName = $management_user->roles->first()?->name ?? ($management_user->role ?? 'member');

        return view('managementusers.show', [
            'managementUser' => $management_user,
            'roleName' => $roleName,
            'permissionsByGroup' => $permissionsByGroup,
            'activeTaskCount' => $activeTaskCount,
            'completedTaskCount' => $completedTaskCount,
            'createdTaskCount' => $createdTaskCount,
            'recentActivities' => $recentActivities,
        ]);
    }

    public function create()
    {
        Gate::authorize('management-users.create');

        return view('managementusers.create', [
            'roles' => $this->webRoles(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('management-users.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::exists(config('permission.table_names.roles'), 'name')->where('guard_name', 'web')],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        DB::transaction(function () use ($validated): void {
            $user = User::create($validated + ['is_active' => true]);
            $user->syncRoles([$validated['role']]);
        });

        return redirect()->route('management-users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $management_user)
    {
        Gate::authorize('management-users.update');

        return view('managementusers.edit', [
            'management_user' => $management_user,
            'roles' => $this->webRoles(),
        ]);
    }

    public function update(Request $request, User $management_user)
    {
        Gate::authorize('management-users.update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($management_user)],
            'role' => ['required', Rule::exists(config('permission.table_names.roles'), 'name')->where('guard_name', 'web')],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if ($management_user->is(Auth::user()) && $management_user->isSuperAdmin() && $validated['role'] !== 'super_admin') {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menurunkan role super admin milik akun sendiri.',
            ]);
        }

        DB::transaction(function () use ($management_user, $validated): void {
            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            if (! empty($validated['password'])) {
                $attributes['password'] = Hash::make($validated['password']);
            }

            $management_user->update($attributes);
            $management_user->syncRoles([$validated['role']]);
        });

        return redirect()->route('management-users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $management_user)
    {
        Gate::authorize('management-users.delete');

        if ($management_user->is(Auth::user())) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menghapus akun sendiri.',
            ]);
        }

        $substantiveData = DB::transaction(function () use ($management_user): array {
            $lockedUser = User::whereKey($management_user->id)->lockForUpdate()->firstOrFail();
            $substantiveData = $this->substantiveDataLabels($lockedUser);

            if ($substantiveData === []) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $lockedUser->id)->delete();
                $lockedUser->delete();
            }

            return $substantiveData;
        });

        if ($substantiveData !== []) {
            return back()->withErrors([
                'user' => 'User tidak dapat dihapus permanen karena memiliki data substantif: '.implode(', ', $substantiveData).'. Nonaktifkan akun melalui perubahan status.',
            ]);
        }

        return redirect()->route('management-users.index')->with('success', 'User berhasil dihapus.');
    }

    public function toggleStatus(User $management_user)
    {
        Gate::authorize('management-users.toggle-status');

        if ($management_user->is(Auth::user())) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        $management_user->update([
            'is_active' => ! $management_user->is_active,
        ]);

        return back()->with('success', 'Status user berhasil diubah.');
    }

    /** @return Collection<int, Role> */
    private function webRoles(): Collection
    {
        $systemRoleOrder = ['super_admin', 'contributor', 'member'];

        return Role::query()
            ->where('guard_name', 'web')
            ->get()
            ->sortBy(function (Role $role) use ($systemRoleOrder): string {
                $position = array_search($role->name, $systemRoleOrder, true);

                return sprintf('%03d-%s', $position === false ? count($systemRoleOrder) : $position, $role->name);
            })
            ->values();
    }
}
