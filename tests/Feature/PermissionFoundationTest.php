<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchema();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_legacy_roles_are_synchronized_idempotently(): void
    {
        $superAdmin = User::create($this->userAttributes('legacy-admin@example.test', 'super_admin'));
        $member = User::create($this->userAttributes('legacy-member@example.test', 'member'));
        $contributor = User::create($this->userAttributes('legacy-contributor@example.test', 'contributor'));

        Artisan::call('permissions:sync-legacy-roles');
        Artisan::call('permissions:sync-legacy-roles');

        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
        $this->assertTrue($member->fresh()->hasRole('member'));
        $this->assertTrue($contributor->fresh()->hasRole('contributor'));
        $this->assertDatabaseCount('model_has_roles', 3);
    }

    public function test_legacy_admin_is_downgraded_to_member_and_unknown_role_is_skipped(): void
    {
        $admin = User::create($this->userAttributes('legacy-old-admin@example.test', 'admin'));
        $unknown = User::create($this->userAttributes('legacy-unknown@example.test', 'unexpected'));

        Artisan::call('permissions:sync-legacy-roles');

        $this->assertTrue($admin->fresh()->hasRole('member'));
        $this->assertTrue($unknown->fresh()->roles->isEmpty());
        $this->assertStringContainsString('1 admin dipetakan ke member', Artisan::output());
    }

    public function test_super_admin_gate_bypasses_permissions_and_member_does_not(): void
    {
        Permission::create(['name' => 'foundation.unassigned', 'guard_name' => 'web']);
        $superAdmin = User::factory()->superAdmin()->create();
        $member = User::factory()->member()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('foundation.unassigned'));
        $this->assertFalse(Gate::forUser($member)->allows('foundation.unassigned'));
    }

    public function test_contributor_has_template_permissions_without_delete_or_management_access(): void
    {
        $contributor = User::factory()->contributor()->create();

        foreach ([
            'project-template-categories.view',
            'project-template-categories.create',
            'project-template-categories.update',
            'project-templates.view',
            'project-templates.create',
            'project-templates.update',
        ] as $permission) {
            $this->assertTrue($contributor->can($permission));
        }

        $this->assertFalse($contributor->can('project-templates.delete'));
        $this->assertFalse($contributor->can('management-users.view'));
        $this->assertFalse($contributor->can('management-projects.view'));
        $this->assertFalse($contributor->can('management-workspaces.view'));
    }

    public function test_management_routes_require_authentication_and_permissions(): void
    {
        $this->get(route('management-users.index'))->assertRedirect(route('login'));

        foreach ([User::factory()->member()->create(), User::factory()->contributor()->create()] as $user) {
            $this->actingAs($user)->get(route('management-users.index'))->assertForbidden();
            $this->actingAs($user)->post(route('management-users.store'), [])->assertForbidden();
            $this->actingAs($user)->get(route('managementprojects.index'))->assertForbidden();
            $this->actingAs($user)->get(route('managementworkspaces.index'))->assertForbidden();
        }

        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->get(route('management-users.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('managementprojects.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('managementworkspaces.index'))->assertOk();
    }

    public function test_user_management_keeps_column_and_spatie_role_in_sync(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('management-users.store'), [
            'name' => 'Contributor User',
            'email' => 'contributor@example.test',
            'role' => 'contributor',
            'password' => 'password',
        ])->assertRedirect(route('management-users.index'));

        $contributor = User::where('email', 'contributor@example.test')->firstOrFail();
        $originalPassword = $contributor->password;
        $this->assertSame('contributor', $contributor->role);
        $this->assertTrue($contributor->hasRole('contributor'));

        $this->actingAs($superAdmin)->put(route('management-users.update', $contributor), [
            'name' => $contributor->name,
            'email' => $contributor->email,
            'role' => 'member',
            'password' => '',
        ])->assertRedirect(route('management-users.index'));

        $this->assertSame('member', $contributor->fresh()->role);
        $this->assertSame($originalPassword, $contributor->fresh()->password);
        $this->assertTrue($contributor->fresh()->hasRole('member'));
        $this->assertFalse($contributor->fresh()->hasRole('contributor'));

        $this->actingAs($superAdmin)->put(route('management-users.update', $contributor), [
            'name' => $contributor->name,
            'email' => $contributor->email,
            'role' => 'member',
            'password' => 'new-password',
        ])->assertRedirect(route('management-users.index'));

        $this->assertTrue(Hash::check('new-password', $contributor->fresh()->password));
    }

    public function test_invalid_role_and_self_delete_or_disable_are_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->put(route('management-users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->actingAs($superAdmin)
            ->delete(route('management-users.destroy', $superAdmin))
            ->assertSessionHasErrors('user');
        $this->actingAs($superAdmin)
            ->patch(route('management-users.toggle-status', $superAdmin))
            ->assertSessionHasErrors('user');

        $this->assertNotNull($superAdmin->fresh());
        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_sso_roles_update_column_and_spatie_role(): void
    {
        Http::fake(function (ClientRequest $request) {
            $ssoRole = (string) $request['sso_token'];
            $localRole = match ($ssoRole) {
                'admin', 'super-admin', 'super_admin' => 'super_admin',
                'contributor' => 'contributor',
                default => 'member',
            };

            return Http::response(['user' => [
                'name' => "SSO {$localRole}",
                'email' => "sso-{$localRole}@example.test",
                'roles' => [$ssoRole],
            ]]);
        });

        foreach ([
            'admin' => 'super_admin',
            'super-admin' => 'super_admin',
            'super_admin' => 'super_admin',
            'contributor' => 'contributor',
            'unknown' => 'member',
        ] as $ssoRole => $expectedRole) {
            $response = $this->withSession(['sso_state' => "state-{$expectedRole}"])
                ->get(route('sso.callback', [
                    'sso_token' => $ssoRole,
                    'state' => "state-{$expectedRole}",
                ]));
            $response->assertSessionMissing('error')->assertRedirect('/');

            $user = User::where('email', "sso-{$expectedRole}@example.test")->firstOrFail();
            $this->assertSame($expectedRole, $user->role);
            $this->assertTrue($user->hasRole($expectedRole));
        }
    }

    public function test_sidebar_management_links_are_permission_aware(): void
    {
        $member = User::factory()->member()->create();
        $contributor = User::factory()->contributor()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        foreach ([$member, $contributor] as $user) {
            $html = (string) $this->actingAs($user)->view('layouts.sidebar');
            $this->assertStringNotContainsString('Management Users', $html);
            $this->assertStringNotContainsString('Management Projects', $html);
            $this->assertStringNotContainsString('Management Workspaces', $html);
        }

        $html = (string) $this->actingAs($superAdmin)->view('layouts.sidebar');
        $this->assertStringContainsString('Management Users', $html);
        $this->assertStringContainsString('Management Projects', $html);
        $this->assertStringContainsString('Management Workspaces', $html);
    }

    public function test_inactive_authenticated_user_is_logged_out_by_middleware(): void
    {
        $inactiveUser = User::factory()->member()->create(['is_active' => false]);

        $this->actingAs($inactiveUser)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Akun Anda tidak aktif.');

        $this->assertGuest();
    }

    /**
     * @return array<string, mixed>
     */
    private function userAttributes(string $email, string $role): array
    {
        return [
            'name' => 'Legacy User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ];
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('member');
            $table->boolean('is_active')->default(true);
            $table->string('profile_photo')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('google_id')->nullable();
            $table->string('sso_id')->nullable();
            $table->string('employee_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $permissionMigration = require database_path('migrations/2026_07_22_083512_create_permission_tables.php');
        $permissionMigration->up();

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
