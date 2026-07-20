<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserManagementDeletionSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    public function test_regular_member_cannot_access_any_user_management_mutation(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $target = User::factory()->create();

        $requests = [
            fn () => $this->actingAs($member)->get(route('management-users.create')),
            fn () => $this->actingAs($member)->post(route('management-users.store'), []),
            fn () => $this->actingAs($member)->get(route('management-users.edit', $target)),
            fn () => $this->actingAs($member)->put(route('management-users.update', $target), []),
            fn () => $this->actingAs($member)->patch(route('management-users.toggle-status', $target)),
            fn () => $this->actingAs($member)->delete(route('management-users.destroy', $target)),
        ];

        foreach ($requests as $request) {
            $request()->assertForbidden();
        }

        $this->assertNotNull($target->fresh());
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_profile_self_delete_deactivates_user_without_changing_related_data(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $name = $user->name;
        $email = $user->email;
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $project = Project::factory()->for($workspace)->for($user, 'creator')->create();
        $project->members()->attach($user->id, ['role' => 'manager', 'joined_at' => now()]);
        $task = $this->createTask($project, $user, ['assignee_id' => $user->id]);
        $task->assignees()->attach($user->id);
        TaskStatusHistory::create([
            'task_id' => $task->id,
            'from_status' => 'to_do',
            'to_status' => 'in_progress',
            'changed_by' => $user->id,
            'changed_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'entity_type' => 'task',
            'entity_id' => $task->id,
            'description' => 'Test history',
        ]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $deactivatedUser = $user->fresh();
        $this->assertNotNull($deactivatedUser);
        $this->assertFalse($deactivatedUser->is_active);
        $this->assertSame($name, $deactivatedUser->name);
        $this->assertSame($email, $deactivatedUser->email);
        $this->assertGuest();
        $this->assertNotNull($workspace->fresh());
        $this->assertNotNull($project->fresh());
        $this->assertNotNull($task->fresh());
        $this->assertSame($user->id, $task->fresh()->created_by);
        $this->assertSame($user->id, $task->fresh()->assignee_id);
        $this->assertSame(1, $task->fresh()->assignees()->where('users.id', $user->id)->count());
        $this->assertTrue($workspace->members()->where('users.id', $user->id)->exists());
        $this->assertTrue($project->members()->where('users.id', $user->id)->exists());
        $this->assertSame(1, TaskStatusHistory::where('changed_by', $user->id)->count());
        $this->assertSame(1, ActivityLog::where('user_id', $user->id)->count());
    }

    public function test_inactive_user_cannot_log_in_with_password(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_super_admin_can_hard_delete_an_account_without_substantive_data(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create();

        $this->actingAs($superAdmin)
            ->delete(route('management-users.destroy', $target))
            ->assertRedirect(route('management-users.index'));

        $this->assertNull($target->fresh());
    }

    public function test_task_ownership_blocks_hard_delete_without_changing_any_data(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create();
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'creator')->create();
        $project = Project::factory()->for($workspace)->for($owner, 'creator')->create();
        $task = $this->createTask($project, $target);
        $project->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);
        DB::table('notifications')->insert([
            'user_id' => $target->id,
            'type' => 'assignment',
            'title' => 'Test',
            'message' => 'Test',
            'task_id' => $task->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $countsBefore = [
            'users' => User::count(),
            'tasks' => Task::count(),
            'memberships' => DB::table('project_members')->count(),
            'notifications' => DB::table('notifications')->count(),
        ];

        $this->actingAs($superAdmin)
            ->from(route('management-users.index'))
            ->delete(route('management-users.destroy', $target))
            ->assertSessionHasErrors('user')
            ->assertRedirect(route('management-users.index'));

        $this->assertNotNull($target->fresh());
        $this->assertNotNull($task->fresh());
        $this->assertSame($target->id, $task->fresh()->created_by);
        $this->assertSame($countsBefore['users'], User::count());
        $this->assertSame($countsBefore['tasks'], Task::count());
        $this->assertSame($countsBefore['memberships'], DB::table('project_members')->count());
        $this->assertSame($countsBefore['notifications'], DB::table('notifications')->count());
    }

    public function test_workspace_and_project_ownership_block_hard_delete(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $workspaceOwner = User::factory()->create();
        $projectOwner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $ownedWorkspace = Workspace::factory()->for($workspaceOwner, 'creator')->create();
        $otherWorkspace = Workspace::factory()->for($otherOwner, 'creator')->create();
        $ownedProject = Project::factory()->for($otherWorkspace)->for($projectOwner, 'creator')->create();

        $this->actingAs($superAdmin)
            ->delete(route('management-users.destroy', $workspaceOwner))
            ->assertSessionHasErrors('user');
        $this->actingAs($superAdmin)
            ->delete(route('management-users.destroy', $projectOwner))
            ->assertSessionHasErrors('user');

        $this->assertNotNull($workspaceOwner->fresh());
        $this->assertNotNull($projectOwner->fresh());
        $this->assertNotNull($ownedWorkspace->fresh());
        $this->assertNotNull($ownedProject->fresh());
    }

    public function test_status_history_and_activity_log_block_hard_delete(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create();
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'creator')->create();
        $project = Project::factory()->for($workspace)->for($owner, 'creator')->create();
        $task = $this->createTask($project, $owner);
        TaskStatusHistory::create([
            'task_id' => $task->id,
            'from_status' => 'to_do',
            'to_status' => 'in_progress',
            'changed_by' => $target->id,
            'changed_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => $target->id,
            'action' => 'updated',
            'entity_type' => 'task',
            'entity_id' => $task->id,
            'description' => 'Test history',
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('management-users.destroy', $target))
            ->assertSessionHasErrors('user');

        $this->assertNotNull($target->fresh());
        $this->assertSame(1, TaskStatusHistory::where('changed_by', $target->id)->count());
        $this->assertSame(1, ActivityLog::where('user_id', $target->id)->count());
    }

    public function test_transient_relations_do_not_block_safe_hard_delete_or_delete_assigned_task(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create();
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'creator')->create();
        $project = Project::factory()->for($workspace)->for($owner, 'creator')->create();
        $task = $this->createTask($project, $owner, ['assignee_id' => $target->id]);
        $task->assignees()->attach($target->id);
        $workspace->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);
        $project->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);
        DB::table('notifications')->insert([
            'user_id' => $target->id,
            'type' => 'assignment',
            'title' => 'Test',
            'message' => 'Test',
            'task_id' => $task->id,
            'project_id' => $project->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('device_users')->insert([
            'device_id' => 'test-device',
            'user_id' => $target->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('management-users.destroy', $target))
            ->assertRedirect(route('management-users.index'));

        $this->assertNull($target->fresh());
        $this->assertNotNull($task->fresh());
        $this->assertSame($owner->id, $task->fresh()->created_by);
        $this->assertNull($task->fresh()->assignee_id);
        $this->assertSame(0, $task->fresh()->assignees()->count());
        $this->assertSame(0, DB::table('workspace_members')->where('user_id', $target->id)->count());
        $this->assertSame(0, DB::table('project_members')->where('user_id', $target->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('user_id', $target->id)->count());
        $this->assertSame(0, DB::table('device_users')->where('user_id', $target->id)->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $target->id)->count());
    }

    public function test_delete_controllers_do_not_call_the_legacy_assigned_tasks_delete_relation(): void
    {
        $profileController = file_get_contents(app_path('Http/Controllers/ProfileController.php'));
        $userController = file_get_contents(app_path('Http/Controllers/UserController.php'));

        $this->assertStringNotContainsString('assignedTasks()->delete()', $profileController);
        $this->assertStringNotContainsString('assignedTasks()->delete()', $userController);
    }

    private function createTask(Project $project, User $creator, array $attributes = []): Task
    {
        return Task::factory()->for($project)->for($creator, 'creator')->create($attributes);
    }

    private function createTestSchema(): void
    {
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('member');
            $table->boolean('is_active')->default(true);
            $table->string('google_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('to_do');
            $table->string('priority')->default('medium');
            $table->decimal('weight', 10, 2)->default(1);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('token', 32)->nullable()->unique();
            $table->foreignId('predecessor_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('dependency_type')->default('FS');
            $table->timestamps();
        });

        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('task_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('changed_at')->useCurrent();
        });

        Schema::create('task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('task_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('project_baselines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
        });

        Schema::create('actual_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('description')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->foreignId('task_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create('project_thread_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('token')->unique();
            $table->string('type');
            $table->unsignedBigInteger('invitable_id');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_users', function (Blueprint $table): void {
            $table->id();
            $table->string('device_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['device_id', 'user_id']);
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });
    }
}
