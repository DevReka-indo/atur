<?php

namespace App\Http\Middleware;

namespace App\Http\Controllers;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function checkAdmin()
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }
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
        return view('managementusers.edit', compact('management_user'));
    }


    public function update(Request $request, User $management_user)
    {
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
        // Hapus pivot
        $management_user->workspaces()->detach();
        $management_user->projects()->detach();

        // Hapus relasi hasMany
        $management_user->activityLogs()->delete();
        $management_user->comments()->delete();
        $management_user->attachments()->delete();
        $management_user->assignedTasks()->delete();
        $management_user->createdTasks()->delete();
        $management_user->createdBaselines()->delete();
        $management_user->recordedProgress()->delete();
        $management_user->createdProjects()->delete();
        $management_user->createdWorkspaces()->delete();
        \App\Models\Notification::where('user_id', $management_user->id)->delete();
        \App\Models\TaskStatusHistory::where('changed_by', $management_user->id)->delete();

        // Hapus user
        $management_user->delete();

        return redirect()
            ->route('management-users.index')
            ->with('success', 'User berhasil dihapus.');
    }


    public function toggleStatus(User $management_user)
    {
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
