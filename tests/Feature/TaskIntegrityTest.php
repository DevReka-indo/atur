<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectProgressService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class TaskIntegrityTest extends TestCase
{
    private User $manager;

    private Project $project;

    private Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();

        $this->manager = User::factory()->create();
        $workspace = Workspace::factory()->for($this->manager, 'creator')->create();
        $this->project = Project::factory()
            ->for($workspace)
            ->for($this->manager, 'creator')
            ->create();
        $this->otherProject = Project::factory()
            ->for($workspace)
            ->for($this->manager, 'creator')
            ->create();

        $this->addProjectMember($this->project, $this->manager, 'manager');
        $this->addProjectMember($this->otherProject, $this->manager, 'manager');

        Queue::fake();
        $this->mock(ProjectProgressService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncPlannedProgress')->zeroOrMoreTimes();
            $mock->shouldReceive('recordActualProgress')->zeroOrMoreTimes();
        });
    }

    public function test_create_with_parent_and_predecessor_from_same_project_succeeds(): void
    {
        $parent = $this->createTask();
        $predecessor = $this->createTask();

        $response = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'name' => 'Valid related task',
            'parent_task_id' => $parent->id,
            'predecessor_id' => $predecessor->id,
        ]));

        $response->assertRedirect(route('projects.show', $this->project->token));
        $task = Task::where('name', 'Valid related task')->firstOrFail();

        $this->assertSame($parent->id, $task->parent_task_id);
        $this->assertSame($predecessor->id, $task->predecessor_id);
    }

    public function test_parent_from_another_project_is_rejected_without_side_effects(): void
    {
        $foreignParent = $this->createTask($this->otherProject);
        $taskCount = Task::count();
        $assignmentCount = $this->assignmentCount();
        $notificationCount = Notification::count();

        $response = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'parent_task_id' => $foreignParent->id,
        ]));

        $response->assertSessionHasErrors('parent_task_id');
        $this->assertSame($taskCount, Task::count());
        $this->assertSame($assignmentCount, $this->assignmentCount());
        $this->assertSame($notificationCount, Notification::count());
    }

    public function test_predecessor_from_another_project_is_rejected_on_create(): void
    {
        $foreignPredecessor = $this->createTask($this->otherProject);

        $response = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'predecessor_id' => $foreignPredecessor->id,
        ]));

        $response->assertSessionHasErrors('predecessor_id');
    }

    public function test_predecessor_from_another_project_is_rejected_on_update(): void
    {
        $task = $this->createTask();
        $foreignPredecessor = $this->createTask($this->otherProject);

        $response = $this->actingAs($this->manager)->put(
            route('tasks.update', $task->token),
            $this->validUpdatePayload($task, ['predecessor_id' => $foreignPredecessor->id])
        );

        $response->assertSessionHasErrors('predecessor_id');
        $this->assertNull($task->refresh()->predecessor_id);
    }

    public function test_non_member_assignee_is_rejected_on_create(): void
    {
        $nonMember = User::factory()->create();

        $response = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'assignee_ids' => [$nonMember->id],
        ]));

        $response->assertSessionHasErrors('assignee_ids.0');
    }

    public function test_non_member_assignee_is_rejected_on_update_without_changing_existing_assignment(): void
    {
        $member = User::factory()->create();
        $nonMember = User::factory()->create();
        $this->addProjectMember($this->project, $member, 'member');
        $task = $this->createTask();
        $task->assignees()->sync([$member->id]);
        $notificationCount = Notification::count();

        $response = $this->actingAs($this->manager)->put(
            route('tasks.update', $task->token),
            $this->validUpdatePayload($task, ['assignee_ids' => [$nonMember->id]])
        );

        $response->assertSessionHasErrors('assignee_ids.0');
        $this->assertSame([$member->id], $task->assignees()->pluck('users.id')->all());
        $this->assertSame($notificationCount, Notification::count());
    }

    public function test_multiple_project_members_including_viewer_can_be_assigned(): void
    {
        $member = User::factory()->create();
        $viewer = User::factory()->create();
        $this->addProjectMember($this->project, $member, 'member');
        $this->addProjectMember($this->project, $viewer, 'viewer');

        $response = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'name' => 'Task with viewer',
            'assignee_ids' => [$member->id, $viewer->id],
        ]));

        $response->assertRedirect();
        $task = Task::where('name', 'Task with viewer')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$member->id, $viewer->id],
            $task->assignees()->pluck('users.id')->all()
        );
    }

    public function test_self_predecessor_is_rejected(): void
    {
        $task = $this->createTask();

        $response = $this->actingAs($this->manager)->put(
            route('tasks.update', $task->token),
            $this->validUpdatePayload($task, ['predecessor_id' => $task->id])
        );

        $response->assertSessionHasErrors('predecessor_id');
        $this->assertNull($task->refresh()->predecessor_id);
    }

    public function test_two_task_predecessor_cycle_is_rejected(): void
    {
        $task = $this->createTask();
        $predecessor = $this->createTask(attributes: ['predecessor_id' => $task->id]);

        $response = $this->actingAs($this->manager)->put(
            route('tasks.update', $task->token),
            $this->validUpdatePayload($task, ['predecessor_id' => $predecessor->id])
        );

        $response->assertSessionHasErrors('predecessor_id');
        $this->assertNull($task->refresh()->predecessor_id);
    }

    public function test_three_task_predecessor_cycle_is_rejected(): void
    {
        $task = $this->createTask();
        $thirdTask = $this->createTask(attributes: ['predecessor_id' => $task->id]);
        $secondTask = $this->createTask(attributes: ['predecessor_id' => $thirdTask->id]);

        $response = $this->actingAs($this->manager)->put(
            route('tasks.update', $task->token),
            $this->validUpdatePayload($task, ['predecessor_id' => $secondTask->id])
        );

        $response->assertSessionHasErrors('predecessor_id');
        $this->assertNull($task->refresh()->predecessor_id);
    }

    public function test_parent_task_id_is_prohibited_on_update(): void
    {
        $originalParent = $this->createTask();
        $newParent = $this->createTask();
        $task = $this->createTask(attributes: ['parent_task_id' => $originalParent->id]);

        $response = $this->actingAs($this->manager)->put(
            route('tasks.update', $task->token),
            $this->validUpdatePayload($task, ['parent_task_id' => $newParent->id])
        );

        $response->assertSessionHasErrors('parent_task_id');
        $this->assertSame($originalParent->id, $task->refresh()->parent_task_id);
    }

    public function test_assignees_must_be_an_array_with_unique_values(): void
    {
        $scalarResponse = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'assignee_ids' => (string) $this->manager->id,
        ]));
        $scalarResponse->assertSessionHasErrors('assignee_ids');

        $duplicateResponse = $this->actingAs($this->manager)->post('/tasks', $this->validStorePayload([
            'assignee_ids' => [$this->manager->id, $this->manager->id],
        ]));
        $duplicateResponse->assertSessionHasErrors('assignee_ids.0');
    }

    private function addProjectMember(Project $project, User $user, string $role): void
    {
        $project->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'joined_at' => now(),
            ],
        ]);
    }

    private function createTask(?Project $project = null, array $attributes = []): Task
    {
        $project ??= $this->project;

        return Task::factory()
            ->for($project)
            ->for($this->manager, 'creator')
            ->create($attributes);
    }

    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'project_id' => $this->project->id,
            'name' => 'New task',
            'description' => 'Task description',
            'assignee_ids' => [$this->manager->id],
            'status' => 'to_do',
            'priority' => 'medium',
            'weight' => 1,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'predecessor_id' => null,
            'dependency_type' => 'FS',
        ], $overrides);
    }

    private function validUpdatePayload(Task $task, array $overrides = []): array
    {
        return array_merge([
            'name' => $task->name,
            'description' => $task->description,
            'assignee_ids' => $task->assignees()->pluck('users.id')->all(),
            'status' => $task->status,
            'priority' => $task->priority,
            'weight' => $task->weight,
            'start_date' => $task->start_date?->toDateString(),
            'due_date' => $task->due_date?->toDateString(),
            'predecessor_id' => $task->predecessor_id,
            'dependency_type' => $task->dependency_type,
        ], $overrides);
    }

    private function assignmentCount(): int
    {
        return Task::where('project_id', $this->project->id)->get()->sum(
            fn (Task $task): int => $task->assignees()->count()
        );
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

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('planning');
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
            $table->string('token', 32)->nullable()->unique();
            $table->foreignId('project_id');
            $table->foreignId('parent_task_id')->nullable();
            $table->foreignId('predecessor_id')->nullable();
            $table->string('dependency_type')->default('FS');
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable();
            $table->string('status')->default('to_do');
            $table->string('priority')->default('medium');
            $table->decimal('weight', 10, 2)->default(1);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->decimal('stopped_progress', 5, 2)->nullable();
            $table->foreignId('created_by');
            $table->timestamps();
        });

        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('task_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by');
            $table->timestamp('changed_at')->useCurrent();
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
    }
}
