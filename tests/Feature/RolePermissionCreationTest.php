<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchema();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_role_and_permission_creation(): void
    {
        $this->get(route('management-roles.create'))->assertRedirect(route('login'));
        $this->post(route('management-roles.store'), [])->assertRedirect(route('login'));
        $this->get(route('management-permissions.index'))->assertRedirect(route('login'));
        $this->get(route('management-permissions.create'))->assertRedirect(route('login'));
        $this->post(route('management-permissions.store'), [])->assertRedirect(route('login'));
    }

    public function test_member_and_contributor_cannot_create_roles_or_permissions(): void
    {
        foreach ([User::factory()->member()->create(), User::factory()->contributor()->create()] as $user) {
            $this->actingAs($user)->get(route('management-roles.create'))->assertForbidden();
            $this->actingAs($user)->post(route('management-roles.store'), [])->assertForbidden();
            $this->actingAs($user)->get(route('management-permissions.index'))->assertForbidden();
            $this->actingAs($user)->get(route('management-permissions.create'))->assertForbidden();
            $this->actingAs($user)->post(route('management-permissions.store'), [])->assertForbidden();
        }
    }

    public function test_non_super_admin_with_manipulated_permissions_is_still_rejected(): void
    {
        $member = User::factory()->member()->create();
        $member->givePermissionTo(['roles.create', 'permissions.view', 'permissions.create']);

        $this->actingAs($member)->get(route('management-roles.create'))->assertForbidden();
        $this->actingAs($member)->post(route('management-roles.store'), [
            'display_name' => 'Unauthorized Role',
        ])->assertForbidden();
        $this->actingAs($member)->get(route('management-permissions.index'))->assertForbidden();
        $this->actingAs($member)->post(route('management-permissions.store'), [
            'module' => 'unauthorized',
            'action' => 'view',
        ])->assertForbidden();
    }

    public function test_super_admin_can_open_role_and_permission_creation_pages(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('management-roles.create'))
            ->assertOk()
            ->assertSee('Tambah Role');
        $this->actingAs($superAdmin)->get(route('management-permissions.index'))
            ->assertOk()
            ->assertSee('Tambah Permission');
        $this->actingAs($superAdmin)->get(route('management-permissions.create'))
            ->assertOk()
            ->assertSee('Technical Permission Name');
    }

    public function test_super_admin_can_create_normalized_custom_role_with_initial_permissions(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('management-roles.store'), [
            'display_name' => 'Template Reviewer',
            'permissions' => ['management-users.view', 'project-templates.view'],
        ]);

        $role = Role::findByName('template_reviewer', 'web');
        $response->assertRedirect(route('management-roles.edit', $role))->assertSessionHas('success');
        $this->assertSame('web', $role->guard_name);
        $this->assertTrue($role->hasAllPermissions(['management-users.view', 'project-templates.view']));
    }

    public function test_duplicate_reserved_and_non_web_role_input_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Role::create(['name' => 'project_auditor', 'guard_name' => 'web']);
        $apiPermission = Permission::create(['name' => 'api.audit', 'guard_name' => 'api']);

        $this->actingAs($superAdmin)->post(route('management-roles.store'), [
            'display_name' => 'Project Auditor',
        ])->assertInvalid(['name']);

        $this->actingAs($superAdmin)->post(route('management-roles.store'), [
            'display_name' => 'Super Admin',
        ])->assertInvalid(['name']);

        $this->actingAs($superAdmin)->post(route('management-roles.store'), [
            'display_name' => 'API Reviewer',
            'permissions' => [$apiPermission->name],
        ])->assertInvalid(['permissions.0']);

        $this->assertFalse(Role::where('name', 'api_reviewer')->where('guard_name', 'web')->exists());
    }

    public function test_custom_role_is_available_and_synchronized_in_user_management(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customRole = Role::create(['name' => 'project_auditor', 'guard_name' => 'web']);
        $target = User::factory()->member()->create();

        $this->actingAs($superAdmin)->get(route('management-users.create'))
            ->assertOk()
            ->assertSee('Project Auditor');
        $this->actingAs($superAdmin)->get(route('management-users.edit', $target))
            ->assertOk()
            ->assertSee('Project Auditor');

        $this->actingAs($superAdmin)->post(route('management-users.store'), [
            'name' => 'Custom Role User',
            'email' => 'custom-role@example.test',
            'role' => $customRole->name,
            'password' => 'password',
        ])->assertRedirect(route('management-users.index'));

        $createdUser = User::where('email', 'custom-role@example.test')->firstOrFail();
        $this->assertSame('project_auditor', $createdUser->role);
        $this->assertTrue($createdUser->hasRole('project_auditor'));

        $this->actingAs($superAdmin)->put(route('management-users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $customRole->name,
            'password' => '',
        ])->assertRedirect(route('management-users.index'));

        $this->assertSame('project_auditor', $target->fresh()->role);
        $this->assertTrue($target->fresh()->hasRole('project_auditor'));
        $this->assertFalse($target->fresh()->hasRole('member'));
    }

    public function test_custom_role_badge_uses_role_name_instead_of_member_fallback(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Role::create(['name' => 'project_auditor', 'guard_name' => 'web']);
        User::factory()->create(['role' => 'project_auditor']);

        $this->actingAs($superAdmin)->get(route('management-users.index'))
            ->assertOk()
            ->assertSee('Project Auditor');
    }

    public function test_super_admin_can_create_web_permission_without_assigning_it_to_any_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('management-permissions.store'), [
            'module' => 'Project Reports',
            'action' => 'view',
            'guard_name' => 'api',
        ])->assertRedirect(route('management-permissions.index'));

        $permission = Permission::findByName('project-reports.view', 'web');
        $this->assertSame('web', $permission->guard_name);
        $this->assertSame(0, $permission->roles()->count());
        $this->assertFalse(Permission::where('name', 'project-reports.view')->where('guard_name', 'api')->exists());
    }

    public function test_custom_action_is_normalized_and_permission_search_and_group_filter_work(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('management-permissions.store'), [
            'module' => 'Project Reports',
            'action' => 'custom',
            'custom_action' => 'Publish Report',
        ])->assertRedirect(route('management-permissions.index'));

        $this->assertTrue(Permission::where('name', 'project-reports.publish-report')->where('guard_name', 'web')->exists());

        $this->actingAs($superAdmin)->get(route('management-permissions.index', [
            'search' => 'publish',
            'group' => 'project-reports',
        ]))->assertOk()
            ->assertSee('project-reports.publish-report')
            ->assertDontSee('management-users.view');
    }

    public function test_invalid_and_duplicate_permission_names_are_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('management-permissions.store'), [
            'module' => '***',
            'action' => 'view',
        ])->assertInvalid(['permission_name']);

        $this->actingAs($superAdmin)->post(route('management-permissions.store'), [
            'module' => 'management-users',
            'action' => 'view',
        ])->assertInvalid(['permission_name']);
    }

    public function test_seeder_preserves_custom_records_and_additional_role_configuration(): void
    {
        $customPermission = Permission::create(['name' => 'reports.approve', 'guard_name' => 'web']);
        $customRole = Role::create(['name' => 'report_approver', 'guard_name' => 'web']);
        $contributor = Role::findByName('contributor', 'web');
        $member = Role::findByName('member', 'web');
        $customRole->givePermissionTo($customPermission);
        $contributor->givePermissionTo($customPermission);
        $member->givePermissionTo($customPermission);

        $this->seed(RolePermissionSeeder::class);

        $this->assertModelExists($customRole->fresh());
        $this->assertModelExists($customPermission->fresh());
        $this->assertTrue($customRole->fresh()->hasPermissionTo($customPermission));
        $this->assertTrue($contributor->fresh()->hasPermissionTo($customPermission));
        $this->assertTrue($member->fresh()->hasPermissionTo($customPermission));
        $this->assertTrue(Role::findByName('super_admin', 'web')->hasAllPermissions([
            'roles.create',
            'permissions.view',
            'permissions.create',
        ]));
    }

    public function test_super_admin_navigation_contains_creation_actions_and_permission_active_state(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('management-roles.index'))
            ->assertOk()
            ->assertSee('Tambah Role')
            ->assertSee('Permissions');

        $this->actingAs($superAdmin)->get(route('management-permissions.index'))
            ->assertOk()
            ->assertSee('Tambah Permission')
            ->assertSee('background-color: #0096c7', false);

        foreach ([User::factory()->member()->create(), User::factory()->contributor()->create()] as $user) {
            $html = (string) $this->actingAs($user)->view('layouts.sidebar');
            $this->assertStringNotContainsString('Role &amp; Permissions', $html);
        }
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
