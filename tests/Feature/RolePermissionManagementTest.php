<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchema();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('management-roles.index'))->assertRedirect(route('login'));
    }

    public function test_member_and_contributor_cannot_access_role_management_routes(): void
    {
        $contributorRole = Role::findByName('contributor', 'web');

        foreach ([User::factory()->member()->create(), User::factory()->contributor()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('management-roles.index'))
                ->assertForbidden();
            $this->actingAs($user)
                ->get(route('management-roles.edit', $contributorRole))
                ->assertForbidden();
            $this->actingAs($user)
                ->put(route('management-roles.update', $contributorRole), ['permissions' => []])
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_open_index_and_roles_are_ordered(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('management-roles.index'))
            ->assertOk()
            ->assertSeeInOrder(['Super Admin', 'Contributor', 'Member'])
            ->assertSee('Full Access');
    }

    public function test_super_admin_can_open_contributor_permissions_with_initial_mapping(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $contributorRole = Role::findByName('contributor', 'web');

        $this->actingAs($superAdmin)
            ->get(route('management-roles.edit', $contributorRole))
            ->assertOk()
            ->assertSee('Project Template Categories')
            ->assertSee('project-template-categories.create')
            ->assertSee('project-templates.update')
            ->assertSee('Roles &amp; Permissions', false);

        $this->assertTrue($contributorRole->hasPermissionTo('project-templates.update'));
        $this->assertFalse($contributorRole->hasPermissionTo('roles.view'));
        $this->assertFalse($contributorRole->hasPermissionTo('roles.update'));
    }

    public function test_update_replaces_old_permissions_and_refreshes_effective_access(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $contributor = User::factory()->contributor()->create();
        $contributorRole = Role::findByName('contributor', 'web');

        $this->assertTrue($contributor->can('project-templates.update'));
        $this->assertFalse($contributor->can('management-users.view'));

        $this->actingAs($superAdmin)
            ->put(route('management-roles.update', $contributorRole), [
                'permissions' => ['management-users.view'],
            ])
            ->assertRedirect(route('management-roles.edit', $contributorRole))
            ->assertSessionHas('success');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $contributor->unsetRelation('roles')->unsetRelation('permissions');
        $contributorRole->refresh();

        $this->assertSame(['management-users.view'], $contributorRole->permissions()->pluck('name')->all());
        $this->assertTrue($contributor->can('management-users.view'));
        $this->assertFalse($contributor->can('project-templates.update'));
    }

    public function test_invalid_and_non_web_permissions_are_rejected_without_changing_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $contributorRole = Role::findByName('contributor', 'web');
        $originalPermissions = $contributorRole->permissions()->pluck('name')->sort()->values()->all();
        $apiPermission = Permission::create(['name' => 'api.reports.view', 'guard_name' => 'api']);

        $this->actingAs($superAdmin)
            ->put(route('management-roles.update', $contributorRole), [
                'permissions' => ['permission.does-not-exist'],
            ])
            ->assertInvalid(['permissions.0']);

        $this->actingAs($superAdmin)
            ->put(route('management-roles.update', $contributorRole), [
                'permissions' => [$apiPermission->name],
            ])
            ->assertInvalid(['permissions.0']);

        $this->assertSame(
            $originalPermissions,
            $contributorRole->fresh()->permissions()->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_super_admin_role_and_non_web_role_cannot_be_updated(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $superAdminRole = Role::findByName('super_admin', 'web');
        $apiRole = Role::create(['name' => 'api_operator', 'guard_name' => 'api']);

        $this->actingAs($superAdmin)
            ->put(route('management-roles.update', $superAdminRole), ['permissions' => []])
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('management-roles.edit', $apiRole))
            ->assertNotFound();
        $this->actingAs($superAdmin)
            ->put(route('management-roles.update', $apiRole), ['permissions' => []])
            ->assertNotFound();
    }

    public function test_member_cannot_self_grant_role_permissions_even_with_route_permission(): void
    {
        $member = User::factory()->member()->create();
        $memberRole = Role::findByName('member', 'web');
        $member->givePermissionTo(['roles.view', 'roles.update']);

        $this->actingAs($member)
            ->put(route('management-roles.update', $memberRole), [
                'permissions' => ['roles.update'],
            ])
            ->assertForbidden();

        $this->assertFalse($memberRole->fresh()->hasPermissionTo('roles.update'));
    }

    public function test_sidebar_role_link_is_only_visible_to_super_admin_by_default(): void
    {
        foreach ([User::factory()->member()->create(), User::factory()->contributor()->create()] as $user) {
            $html = (string) $this->actingAs($user)->view('layouts.sidebar');
            $this->assertStringNotContainsString('Role &amp; Permissions', $html);
        }

        $superAdmin = User::factory()->superAdmin()->create();
        $html = (string) $this->actingAs($superAdmin)->view('layouts.sidebar');
        $this->assertStringContainsString('Role &amp; Permissions', $html);
        $this->assertStringContainsString(route('management-roles.index'), $html);
    }

    public function test_seeder_is_idempotent_and_adds_only_super_admin_role_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(1, Permission::where('name', 'roles.view')->where('guard_name', 'web')->count());
        $this->assertSame(1, Permission::where('name', 'roles.update')->where('guard_name', 'web')->count());
        $this->assertTrue(Role::findByName('super_admin', 'web')->hasAllPermissions(['roles.view', 'roles.update']));
        $this->assertFalse(Role::findByName('contributor', 'web')->hasAnyPermission(['roles.view', 'roles.update']));
        $this->assertFalse(Role::findByName('member', 'web')->hasAnyPermission(['roles.view', 'roles.update']));
        $this->assertDatabaseCount('permissions', 22);
        $this->assertDatabaseCount('roles', 3);
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
