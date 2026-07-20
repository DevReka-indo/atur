<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MemberRemovalIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    public function test_project_member_removal_cleans_only_that_projects_access_and_assignments(): void
    {
        [$manager, $target, $otherUser, $project, $otherProject] = $this->projectRemovalFixture();

        $parent = $this->createTask($project, $otherUser, ['assignee_id' => $target->id]);
        $subtask = $this->createTask($project, $otherUser, ['parent_task_id' => $parent->id]);
        $subtask->assignees()->attach($target->id);
        $otherTask = $this->createTask($otherProject, $otherUser, ['assignee_id' => $target->id]);
        $otherTask->assignees()->attach($target->id);

        $removedProjectNotification = $this->createNotification($target, ['project_id' => $project->id]);
        $removedTaskNotification = $this->createNotification($target, ['task_id' => $subtask->id]);
        $otherProjectNotification = $this->createNotification($target, ['project_id' => $otherProject->id]);
        $otherUserNotification = $this->createNotification($otherUser, ['project_id' => $project->id]);

        $this->actingAs($manager)
            ->delete(route('projects.members.destroy', [$project->token, $target]))
            ->assertRedirect();

        $this->assertFalse($project->members()->where('users.id', $target->id)->exists());
        $this->assertTrue($otherProject->members()->where('users.id', $target->id)->exists());
        $this->assertNull($parent->fresh()->assignee_id);
        $this->assertSame(0, $subtask->fresh()->assignees()->count());
        $this->assertSame($target->id, $otherTask->fresh()->assignee_id);
        $this->assertSame(1, $otherTask->fresh()->assignees()->where('users.id', $target->id)->count());
        $this->assertNotNull($parent->fresh());
        $this->assertNotNull($subtask->fresh());
        $this->assertSame($parent->id, $subtask->fresh()->parent_task_id);
        $this->assertSame($otherUser->id, $parent->fresh()->created_by);
        $this->assertSame($otherUser->id, $subtask->fresh()->created_by);
        $this->assertNull($removedProjectNotification->fresh());
        $this->assertNull($removedTaskNotification->fresh());
        $this->assertNotNull($otherProjectNotification->fresh());
        $this->assertNotNull($otherUserNotification->fresh());

        $log = ActivityLog::where('entity_type', 'project')->where('entity_id', $project->id)->sole();
        $this->assertSame('updated', $log->action);
        $this->assertSame($target->id, $log->old_value['target_user_id']);
        $this->assertSame(1, $log->old_value['pivot_assignment_count']);
        $this->assertSame(1, $log->old_value['legacy_assignment_count']);
    }

    public function test_project_creator_cannot_be_removed(): void
    {
        $manager = User::factory()->create();
        $creator = User::factory()->create();
        $workspace = Workspace::factory()->for($manager, 'creator')->create();
        $project = Project::factory()->for($workspace)->for($creator, 'creator')->create();
        $project->members()->attach($manager->id, ['role' => 'manager', 'joined_at' => now()]);
        $project->members()->attach($creator->id, ['role' => 'member', 'joined_at' => now()]);

        $this->actingAs($manager)
            ->delete(route('projects.members.destroy', [$project->token, $creator]))
            ->assertSessionHasErrors('member');

        $this->assertTrue($project->members()->where('users.id', $creator->id)->exists());
    }

    public function test_project_cleanup_failure_rolls_back_assignment_and_membership_changes(): void
    {
        [$manager, $target, $creator, $project] = $this->projectRemovalFixture();
        $task = $this->createTask($project, $creator, ['assignee_id' => $target->id]);
        $task->assignees()->attach($target->id);
        $notification = $this->createNotification($target, ['project_id' => $project->id]);

        ActivityLog::creating(function (): never {
            throw new RuntimeException('Forced member removal log failure.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($manager)->delete(route('projects.members.destroy', [$project->token, $target]));
            $this->fail('The forced activity log exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced member removal log failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertTrue($project->members()->where('users.id', $target->id)->exists());
        $this->assertSame($target->id, $task->fresh()->assignee_id);
        $this->assertSame(1, $task->fresh()->assignees()->where('users.id', $target->id)->count());
        $this->assertNotNull($notification->fresh());
    }

    public function test_workspace_cascade_cleans_only_that_workspaces_memberships_assignments_and_notifications(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create();
        $creator = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'creator')->create();
        $otherWorkspace = Workspace::factory()->for($creator, 'creator')->create();
        $workspace->members()->attach([$owner->id => ['role' => 'owner', 'joined_at' => now()], $target->id => ['role' => 'member', 'joined_at' => now()]]);
        $otherWorkspace->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);

        $firstProject = Project::factory()->for($workspace)->for($creator, 'creator')->create();
        $secondProject = Project::factory()->for($workspace)->for($creator, 'creator')->create();
        $otherProject = Project::factory()->for($otherWorkspace)->for($creator, 'creator')->create();
        foreach ([$firstProject, $secondProject, $otherProject] as $project) {
            $project->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);
        }

        $firstTask = $this->createTask($firstProject, $creator, ['assignee_id' => $target->id]);
        $secondTask = $this->createTask($secondProject, $creator);
        $secondTask->assignees()->attach($target->id);
        $otherTask = $this->createTask($otherProject, $creator, ['assignee_id' => $target->id]);
        $otherTask->assignees()->attach($target->id);

        $firstNotification = $this->createNotification($target, ['project_id' => $firstProject->id]);
        $secondNotification = $this->createNotification($target, ['task_id' => $secondTask->id]);
        $otherNotification = $this->createNotification($target, ['project_id' => $otherProject->id]);

        $this->actingAs($owner)
            ->delete(route('workspaces.members.destroy.cascade', [$workspace->token, $target]))
            ->assertRedirect();

        $this->assertFalse($workspace->members()->where('users.id', $target->id)->exists());
        $this->assertTrue($otherWorkspace->members()->where('users.id', $target->id)->exists());
        $this->assertFalse($firstProject->members()->where('users.id', $target->id)->exists());
        $this->assertFalse($secondProject->members()->where('users.id', $target->id)->exists());
        $this->assertTrue($otherProject->members()->where('users.id', $target->id)->exists());
        $this->assertNull($firstTask->fresh()->assignee_id);
        $this->assertSame(0, $secondTask->fresh()->assignees()->count());
        $this->assertSame($target->id, $otherTask->fresh()->assignee_id);
        $this->assertSame(1, $otherTask->fresh()->assignees()->where('users.id', $target->id)->count());
        $this->assertSame(3, Task::count());
        $this->assertSame($creator->id, $firstTask->fresh()->created_by);
        $this->assertSame($creator->id, $secondTask->fresh()->created_by);
        $this->assertNull($firstNotification->fresh());
        $this->assertNull($secondNotification->fresh());
        $this->assertNotNull($otherNotification->fresh());

        $log = ActivityLog::where('entity_type', 'workspace')->where('entity_id', $workspace->id)->sole();
        $this->assertSame('updated', $log->action);
        $this->assertSame(2, $log->old_value['project_membership_count']);
        $this->assertSame(1, $log->old_value['pivot_assignment_count']);
        $this->assertSame(1, $log->old_value['legacy_assignment_count']);
    }

    public function test_workspace_cascade_failure_rolls_back_every_cleanup_step(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create();
        $creator = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'creator')->create();
        $workspace->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);
        $project = Project::factory()->for($workspace)->for($creator, 'creator')->create();
        $project->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);
        $task = $this->createTask($project, $creator, ['assignee_id' => $target->id]);
        $task->assignees()->attach($target->id);
        $notification = $this->createNotification($target, ['task_id' => $task->id]);

        ActivityLog::creating(function (): never {
            throw new RuntimeException('Forced workspace member removal log failure.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($owner)->delete(route('workspaces.members.destroy.cascade', [$workspace->token, $target]));
            $this->fail('The forced activity log exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced workspace member removal log failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertTrue($workspace->members()->where('users.id', $target->id)->exists());
        $this->assertTrue($project->members()->where('users.id', $target->id)->exists());
        $this->assertSame($target->id, $task->fresh()->assignee_id);
        $this->assertSame(1, $task->fresh()->assignees()->where('users.id', $target->id)->count());
        $this->assertNotNull($notification->fresh());
    }

    /**
     * @return array{User, User, User, Project, Project}
     */
    private function projectRemovalFixture(): array
    {
        $manager = User::factory()->create();
        $target = User::factory()->create();
        $creator = User::factory()->create();
        $workspace = Workspace::factory()->for($manager, 'creator')->create();
        $project = Project::factory()->for($workspace)->for($creator, 'creator')->create();
        $otherProject = Project::factory()->for($workspace)->for($creator, 'creator')->create();
        $project->members()->attach([$manager->id => ['role' => 'manager', 'joined_at' => now()], $target->id => ['role' => 'member', 'joined_at' => now()]]);
        $otherProject->members()->attach($target->id, ['role' => 'member', 'joined_at' => now()]);

        return [$manager, $target, $creator, $project, $otherProject];
    }

    private function createTask(Project $project, User $creator, array $attributes = []): Task
    {
        return Task::factory()->for($project)->for($creator, 'creator')->create($attributes);
    }

    private function createNotification(User $user, array $attributes): Notification
    {
        return Notification::create($attributes + [
            'user_id' => $user->id,
            'type' => 'assignment',
            'title' => 'Assignment',
            'message' => 'Test notification',
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('member');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id');
            $table->foreignId('user_id');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('user_id');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('parent_task_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable();
            $table->string('status')->default('to_do');
            $table->string('priority')->default('medium');
            $table->decimal('weight', 10, 2)->default(1);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by');
            $table->string('token', 32)->nullable()->unique();
            $table->foreignId('predecessor_id')->nullable();
            $table->string('dependency_type')->default('FS');
            $table->timestamps();
        });

        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->foreignId('task_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('description')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamps();
        });
    }
}
