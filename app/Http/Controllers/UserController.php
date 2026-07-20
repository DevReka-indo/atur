<?php

namespace App\Http\Middleware;

namespace App\Http\Controllers;

use Closure;
use App\Models\User;
use App\Models\Invitation;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\TaskStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function checkAdmin(): void
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }

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
        $this->checkAdmin();
        $search    = $request->get('search');
        $sort      = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        // Whitelist kolom yang boleh di-sort
        $allowedSorts = ['name', 'role', 'created_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $users = User::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
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

    public function create()
    {
        $this->checkAdmin();
        return view('managementusers.create');
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:super_admin,admin,member',
            'password' => 'required|min:6'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated + ['is_active' => 1]);

        return redirect()->route('management-users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $management_user)
    {
        $this->checkAdmin();

        return view('managementusers.edit', compact('management_user'));
    }


    public function update(Request $request, User $management_user)
    {
        $this->checkAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $management_user->id,
            'role' => 'required|string'
        ]);

        $management_user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        return redirect()
            ->route('management-users.index')
            ->with('success', 'User berhasil diupdate.');
    }


    public function destroy(User $management_user)
    {
        $this->checkAdmin();

        $substantiveData = DB::transaction(function () use ($management_user): array {
            $lockedUser = User::whereKey($management_user->id)->lockForUpdate()->firstOrFail();
            $substantiveData = $this->substantiveDataLabels($lockedUser);

            if ($substantiveData === []) {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->id)
                    ->delete();
                $lockedUser->delete();
            }

            return $substantiveData;
        });

        if ($substantiveData !== []) {
            return back()->withErrors([
                'user' => 'User tidak dapat dihapus permanen karena memiliki data substantif: '
                    . implode(', ', $substantiveData)
                    . '. Nonaktifkan akun melalui perubahan status.',
            ]);
        }

        return redirect()
            ->route('management-users.index')
            ->with('success', 'User berhasil dihapus.');
    }


    public function toggleStatus(User $management_user)
    {
        $this->checkAdmin();

        $management_user->update([
            'is_active' => !$management_user->is_active
        ]);

        return back()->with('success', 'Status user berhasil diubah.');
    }

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();

            return redirect('/login')
                ->with('error', 'Akun Anda tidak aktif.');
        }

        return $next($request);
    }
}
