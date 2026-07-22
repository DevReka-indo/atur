<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    /** @var array<string, string> */
    private const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'contributor' => 'Contributor',
        'member' => 'Member',
    ];

    /** @var array<string, string> */
    private const ROLE_DESCRIPTIONS = [
        'super_admin' => 'Full access otomatis melalui sistem.',
        'contributor' => 'Akses kontribusi dan pengelolaan template sesuai permission.',
        'member' => 'Akses pengguna dasar.',
    ];

    /** @var array<string, string> */
    private const GROUP_LABELS = [
        'management-users' => 'Management Users',
        'management-projects' => 'Management Projects',
        'management-workspaces' => 'Management Workspaces',
        'project-template-categories' => 'Project Template Categories',
        'project-templates' => 'Project Templates',
        'roles' => 'Roles & Permissions',
    ];

    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
        'toggle-status' => 'Ubah Status',
    ];

    /** @var list<string> */
    private const ACTION_ORDER = ['view', 'create', 'update', 'delete', 'toggle-status'];

    public function index(): View
    {
        Gate::authorize('roles.view');

        $roleOrder = array_keys(self::ROLE_LABELS);
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount(['users', 'permissions'])
            ->get()
            ->sortBy(fn (Role $role): int => ($position = array_search($role->name, $roleOrder, true)) === false
                ? count($roleOrder)
                : $position)
            ->values();

        return view('managementroles.index', [
            'roles' => $roles,
            'roleLabels' => self::ROLE_LABELS,
            'roleDescriptions' => self::ROLE_DESCRIPTIONS,
        ]);
    }

    public function edit(Role $role): View
    {
        Gate::authorize('roles.view');
        $this->ensureWebRole($role);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->get();

        $permissionGroups = $this->groupPermissions($permissions);
        $selectedPermissionNames = $role->name === 'super_admin'
            ? $permissions->pluck('name')->all()
            : $role->permissions()->pluck('name')->all();

        return view('managementroles.edit', [
            'role' => $role,
            'roleLabel' => self::ROLE_LABELS[$role->name] ?? str($role->name)->headline()->toString(),
            'roleDescription' => self::ROLE_DESCRIPTIONS[$role->name] ?? 'Role aplikasi dengan permission yang dapat dikonfigurasi.',
            'permissionGroups' => $permissionGroups,
            'selectedPermissionNames' => $selectedPermissionNames,
            'canUpdateRole' => $role->name !== 'super_admin'
                && request()->user()->isSuperAdmin()
                && Gate::allows('roles.update'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('roles.create');
        abort_unless(request()->user()->isSuperAdmin(), 403);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->get();

        return view('managementroles.create', [
            'permissionGroups' => $this->groupPermissions($permissions),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('roles.create');
        abort_unless($request->user()->isSuperAdmin(), 403);

        $request->merge([
            'name' => Str::of($request->string('display_name'))->squish()->snake()->lower()->toString(),
        ]);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:125'],
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z0-9]+(?:_[a-z0-9]+)*$/',
                Rule::notIn(array_keys(self::ROLE_LABELS)),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                'distinct:strict',
                Rule::exists(config('permission.table_names.permissions'), 'name')
                    ->where('guard_name', 'web'),
            ],
        ], [
            'name.regex' => 'Nama teknis role hanya boleh berisi huruf kecil, angka, dan underscore.',
            'name.not_in' => 'Nama role tersebut merupakan role sistem yang tidak dapat dibuat ulang.',
            'name.unique' => 'Nama role tersebut sudah digunakan.',
            'permissions.*.exists' => 'Permission yang dipilih tidak valid untuk aplikasi web.',
        ]);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $validated['permissions'] ?? [])
            ->get();

        $role = DB::transaction(function () use ($validated, $permissions): Role {
            $role = new Role;
            $role->name = $validated['name'];
            $role->guard_name = 'web';
            $role->save();
            $role->syncPermissions($permissions);

            return $role;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('management-roles.edit', $role)
            ->with('success', 'Role baru berhasil dibuat.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('roles.update');
        abort_unless($request->user()->isSuperAdmin(), 403);
        $this->ensureWebRole($role);
        abort_if($role->name === 'super_admin', 403, 'Permission Super Admin tidak dapat diubah.');

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                'distinct:strict',
                Rule::exists(config('permission.table_names.permissions'), 'name')
                    ->where('guard_name', 'web'),
            ],
        ], [
            'permissions.*.exists' => 'Permission yang dipilih tidak valid untuk aplikasi web.',
        ]);

        $permissionNames = $validated['permissions'] ?? [];
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->get();

        DB::transaction(function () use ($role, $permissions): void {
            $role->syncPermissions($permissions);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('management-roles.edit', $role)
            ->with('success', 'Permission role berhasil diperbarui.');
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return Collection<string, Collection<int, array{permission: Permission, action_label: string}>>
     */
    private function groupPermissions(Collection $permissions): Collection
    {
        $groupOrder = array_keys(self::GROUP_LABELS);

        return $permissions
            ->map(function (Permission $permission): array {
                [$prefix, $action] = $this->splitPermissionName($permission->name);

                return [
                    'permission' => $permission,
                    'group_key' => $prefix,
                    'group_label' => self::GROUP_LABELS[$prefix] ?? str($prefix)->headline()->toString(),
                    'action' => $action,
                    'action_label' => self::ACTION_LABELS[$action] ?? str($action)->headline()->toString(),
                ];
            })
            ->sortBy(function (array $item) use ($groupOrder): string {
                $groupPosition = array_search($item['group_key'], $groupOrder, true);
                $actionPosition = array_search($item['action'], self::ACTION_ORDER, true);

                return sprintf(
                    '%03d-%03d-%s',
                    $groupPosition === false ? count($groupOrder) : $groupPosition,
                    $actionPosition === false ? count(self::ACTION_ORDER) : $actionPosition,
                    $item['permission']->name,
                );
            })
            ->groupBy('group_label');
    }

    /** @return array{string, string} */
    private function splitPermissionName(string $permissionName): array
    {
        $separatorPosition = strrpos($permissionName, '.');

        if ($separatorPosition === false) {
            return [$permissionName, $permissionName];
        }

        return [
            substr($permissionName, 0, $separatorPosition),
            substr($permissionName, $separatorPosition + 1),
        ];
    }

    private function ensureWebRole(Role $role): void
    {
        abort_unless($role->guard_name === 'web', 404);
    }
}
