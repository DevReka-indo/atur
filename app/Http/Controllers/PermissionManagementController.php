<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionManagementController extends Controller
{
    /** @var list<string> */
    private const COMMON_ACTIONS = [
        'view',
        'create',
        'update',
        'delete',
        'toggle-status',
        'approve',
        'export',
        'manage',
        'custom',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('permissions.view');
        abort_unless($request->user()->isSuperAdmin(), 403);

        $groups = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->map(fn (string $name): string => Str::beforeLast($name, '.'))
            ->unique()
            ->sort()
            ->values();

        $search = $request->string('search')->trim()->toString();
        $group = $request->string('group')->trim()->toString();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->with('roles')
            ->withCount('roles')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($group !== '', fn ($query) => $query->where('name', 'like', $group.'.%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Permission $permission): array => [
                'permission' => $permission,
                'module' => Str::beforeLast($permission->name, '.'),
                'action' => Str::afterLast($permission->name, '.'),
            ]);

        return view('managementpermissions.index', compact('permissions', 'groups', 'search', 'group'));
    }

    public function create(): View
    {
        Gate::authorize('permissions.create');
        abort_unless(request()->user()->isSuperAdmin(), 403);

        return view('managementpermissions.create', [
            'actions' => self::COMMON_ACTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('permissions.create');
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'module' => ['required', 'string', 'max:125'],
            'action' => ['required', Rule::in(self::COMMON_ACTIONS)],
            'custom_action' => ['nullable', 'required_if:action,custom', 'string', 'max:125'],
        ]);

        $module = Str::of($validated['module'])->squish()->kebab()->lower()->toString();
        $action = $validated['action'] === 'custom'
            ? Str::of($validated['custom_action'])->squish()->kebab()->lower()->toString()
            : $validated['action'];
        $permissionName = $module.'.'.$action;

        Validator::make(
            ['permission_name' => $permissionName],
            [
                'permission_name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*\.[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique(config('permission.table_names.permissions'), 'name')
                        ->where('guard_name', 'web'),
                ],
            ],
            [
                'permission_name.regex' => 'Module dan action hanya boleh berisi huruf kecil, angka, dan dash.',
                'permission_name.unique' => 'Permission tersebut sudah tersedia.',
            ],
        )->validate();

        DB::transaction(function () use ($permissionName): void {
            $permission = new Permission;
            $permission->name = $permissionName;
            $permission->guard_name = 'web';
            $permission->save();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('management-permissions.index')
            ->with('success', 'Permission baru berhasil dibuat.');
    }
}
