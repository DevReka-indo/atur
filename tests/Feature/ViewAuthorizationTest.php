<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ViewAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchema();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sidebar_uses_global_permissions_for_management_links(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $superAdminHtml = (string) $this->actingAs($superAdmin)->view('layouts.sidebar');

        $this->assertStringContainsString('Management Users', $superAdminHtml);
        $this->assertStringContainsString('Management Projects', $superAdminHtml);
        $this->assertStringContainsString('Management Workspaces', $superAdminHtml);
        $this->assertStringContainsString('Role &amp; Permissions', $superAdminHtml);

        foreach ([User::factory()->member()->create(), User::factory()->contributor()->create()] as $user) {
            $html = (string) $this->actingAs($user)->view('layouts.sidebar');

            $this->assertStringNotContainsString('Management Users', $html);
            $this->assertStringNotContainsString('Management Projects', $html);
            $this->assertStringNotContainsString('Management Workspaces', $html);
            $this->assertStringNotContainsString('Role &amp; Permissions', $html);
        }

        $memberWithPermission = User::factory()->member()->create();
        $memberWithPermission->givePermissionTo('management-users.view');
        $html = (string) $this->actingAs($memberWithPermission)->view('layouts.sidebar');

        $this->assertStringContainsString('Management Users', $html);
        $this->assertStringNotContainsString('Management Projects', $html);
    }

    public function test_user_management_buttons_follow_their_specific_permissions(): void
    {
        $actor = User::factory()->member()->create();
        $target = User::factory()->member()->create();
        $actor->givePermissionTo('management-users.view');

        $response = $this->actingAs($actor)->get(route('management-users.index'));
        $response->assertOk()
            ->assertDontSee('Add User')
            ->assertDontSee('title="Edit"', false)
            ->assertDontSee('title="Delete"', false)
            ->assertDontSee('title="Deactivate"', false);

        $actor->givePermissionTo([
            'management-users.create',
            'management-users.update',
            'management-users.delete',
            'management-users.toggle-status',
        ]);

        $this->actingAs($actor)->get(route('management-users.index'))
            ->assertOk()
            ->assertSee('Add User')
            ->assertSee(route('management-users.edit', $target), false)
            ->assertSee('title="Delete"', false)
            ->assertSee('title="Deactivate"', false);
    }

    public function test_role_and_permission_creation_buttons_follow_permissions(): void
    {
        $member = User::factory()->member()->create();
        $roles = Role::query()->withCount(['users', 'permissions'])->get();
        $roleViewData = [
            'roles' => $roles,
            'roleLabels' => [],
            'roleDescriptions' => [],
        ];
        $permissionViewData = [
            'permissions' => new LengthAwarePaginator([], 0, 20),
            'groups' => collect(),
            'search' => null,
            'group' => null,
        ];

        $member->givePermissionTo(['roles.view', 'permissions.view']);
        $rolesHtml = (string) $this->actingAs($member)->view('managementroles.index', $roleViewData);
        $permissionsHtml = (string) $this->actingAs($member)->view('managementpermissions.index', $permissionViewData);
        $this->assertStringNotContainsString('Tambah Role', $rolesHtml);
        $this->assertStringNotContainsString('Tambah Permission', $permissionsHtml);

        $member->givePermissionTo(['roles.create', 'permissions.create']);
        $rolesHtml = (string) $this->actingAs($member)->view('managementroles.index', $roleViewData);
        $permissionsHtml = (string) $this->actingAs($member)->view('managementpermissions.index', $permissionViewData);
        $this->assertStringContainsString('Tambah Role', $rolesHtml);
        $this->assertStringContainsString('Tambah Permission', $permissionsHtml);
    }

    public function test_project_task_creation_remains_project_role_based(): void
    {
        $owner = User::factory()->member()->create();
        $workspace = Workspace::create(['name' => 'Local Workspace', 'created_by' => $owner->id]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Local Project',
            'created_by' => $owner->id,
        ]);
        $manager = User::factory()->member()->create();
        $member = User::factory()->member()->create();
        $viewer = User::factory()->member()->create();
        $project->members()->attach([
            $manager->id => ['role' => 'manager'],
            $member->id => ['role' => 'member'],
            $viewer->id => ['role' => 'viewer'],
        ]);

        foreach ([$manager, $member] as $user) {
            $html = $this->renderProjectTabBar($project, $user);
            $this->assertStringContainsString('Create Task', $html);
        }

        $this->assertStringNotContainsString('Create Task', $this->renderProjectTabBar($project, $viewer));
    }

    public function test_workspace_management_actions_remain_workspace_role_based(): void
    {
        $owner = User::factory()->member()->create();
        $workspace = Workspace::create(['name' => 'Workspace Roles', 'created_by' => $owner->id]);
        $admin = User::factory()->member()->create();
        $member = User::factory()->member()->create();
        $workspace->members()->attach([
            $admin->id => ['role' => Workspace::ROLE_ADMIN],
            $member->id => ['role' => Workspace::ROLE_MEMBER],
        ]);

        foreach ([$owner, $admin] as $user) {
            $html = $this->renderWorkspaceActions($workspace, $user);
            $this->assertStringContainsString('Create Project', $html);
            $this->assertStringContainsString('Invite Member', $html);
        }

        $memberHtml = $this->renderWorkspaceActions($workspace, $member);
        $this->assertStringNotContainsString('Create Project', $memberHtml);
        $this->assertStringNotContainsString('Invite Member', $memberHtml);
    }

    public function test_management_delete_buttons_follow_global_delete_permissions(): void
    {
        $actor = User::factory()->member()->create();
        $owner = User::factory()->member()->create();
        $workspace = Workspace::create(['name' => 'Managed Workspace', 'created_by' => $owner->id]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Managed Project',
            'created_by' => $owner->id,
        ]);
        $project->load(['creator', 'workspace', 'members'])->setAttribute('tasks_count', 0);
        $workspace->load('creator')->loadCount(['members', 'projects']);

        $projectViewData = [
            'projects' => collect([$project]),
            'totalUsers' => 2,
            'editableProjectIds' => collect(),
        ];
        $workspaceViewData = ['workspaces' => collect([$workspace])];

        $projectHtml = (string) $this->actingAs($actor)->view('managementprojects.index', $projectViewData);
        $workspaceHtml = (string) $this->actingAs($actor)->view('managementworkspaces.index', $workspaceViewData);
        $this->assertStringNotContainsString(route('managementprojects.destroy', $project->token), $projectHtml);
        $this->assertStringNotContainsString("deleteWorkspace('{$workspace->token}')", $workspaceHtml);

        $actor->givePermissionTo(['management-projects.delete', 'management-workspaces.delete']);
        $projectHtml = (string) $this->actingAs($actor)->view('managementprojects.index', $projectViewData);
        $workspaceHtml = (string) $this->actingAs($actor)->view('managementworkspaces.index', $workspaceViewData);
        $this->assertStringContainsString(route('managementprojects.destroy', $project->token), $projectHtml);
        $this->assertStringContainsString("deleteWorkspace('{$workspace->token}')", $workspaceHtml);
    }

    private function renderProjectTabBar(Project $project, User $user): string
    {
        return (string) $this->actingAs($user)->view('projects.partials.show._tab-bar', [
            'project' => $project,
            'currentTab' => 'tasks',
            'currentView' => 'list',
            'canContribute' => $project->canContribute($user),
        ]);
    }

    private function renderWorkspaceActions(Workspace $workspace, User $user): string
    {
        return (string) $this->actingAs($user)->view('workspaces.partials._management-actions', [
            'workspace' => $workspace,
            'canCreateProject' => $workspace->canCreateProject($user),
            'canManageMembers' => $workspace->canManageMembers($user),
        ]);
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
            $table->rememberToken();
            $table->timestamps();
        });

        $permissionMigration = require database_path('migrations/2026_07_22_083512_create_permission_tables.php');
        $permissionMigration->up();

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('invite_token')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('token')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default(Workspace::ROLE_MEMBER);
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
