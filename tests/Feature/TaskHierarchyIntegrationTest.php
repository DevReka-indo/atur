<?php

namespace Tests\Feature;

use App\Jobs\SendEmailNotification;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectProgressService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class TaskHierarchyIntegrationTest extends TestCase
{
    private User $manager;

    private User $member;

    private User $viewer;

    private Project $project;

    private Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        DB::connection()->getPdo()->sqliteCreateFunction(
            'FIELD',
            function ($value, ...$values): int {
                $position = array_search($value, $values, true);

                return $position === false ? 0 : $position + 1;
            }
        );
        $this->seedStatusWeights();

        $this->manager = User::factory()->create();
        $this->member = User::factory()->create();
        $this->viewer = User::factory()->create();
        $workspace = Workspace::factory()->for($this->manager, 'creator')->create();
        $this->project = Project::factory()->for($workspace)->for($this->manager, 'creator')->create();
        $this->otherProject = Project::factory()->for($workspace)->for($this->manager, 'creator')->create();

        $this->addMember($this->project, $this->manager, 'manager');
        $this->addMember($this->project, $this->member, 'member');
        $this->addMember($this->project, $this->viewer, 'viewer');
        $this->addMember($this->otherProject, $this->manager, 'manager');

        Queue::fake();
        $this->mockProgressService();
    }

    public function test_status_authorization_and_parent_manual_guards_are_enforced(): void
    {
        $ordinaryTask = $this->createTask();
        $this->actingAs($this->viewer)
            ->patchJson(route('tasks.updateStatus', $ordinaryTask->token), ['status' => 'in_progress'])
            ->assertForbidden();

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $ordinaryTask->token), ['status' => 'in_progress'])
            ->assertOk();
        $this->assertSame('in_progress', $ordinaryTask->fresh()->status);

        $parent = $this->createTask(['status' => 'completed', 'completed_at' => now()]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 100,
            'status' => 'completed',
        ]);
        $historyCount = TaskStatusHistory::where('task_id', $parent->id)->count();

        $response = $this->actingAs($this->manager)
            ->patchJson(route('tasks.updateStatus', $parent->token), ['status' => 'to_do']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->assertSame('completed', $parent->fresh()->status);
        $this->assertNotNull($parent->fresh()->completed_at);
        $this->assertSame($historyCount, TaskStatusHistory::where('task_id', $parent->id)->count());
    }

    public function test_contributor_can_open_subtask_form_with_safe_parent_context_while_viewer_cannot(): void
    {
        $parent = $this->createTask();
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 35,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('tasks.create', ['parent' => $parent->token]));

        $response->assertOk()
            ->assertSee('Tambah Subtask')
            ->assertSee($parent->name)
            ->assertSee('35.00%')
            ->assertSee('65.00%')
            ->assertSee('name="parent_task_id" value="'.$parent->id.'"', false)
            ->assertSee('name="subtask_weight_percentage"', false)
            ->assertDontSee('type="number" name="weight"', false);

        $this->actingAs($this->viewer)
            ->get(route('tasks.create', ['parent' => $parent->token]))
            ->assertForbidden();
    }

    public function test_root_create_keeps_project_weight_field_and_no_subtask_weight_field(): void
    {
        $response = $this->actingAs($this->manager)->get(route('tasks.create'));

        $response->assertOk()
            ->assertSee('type="number" name="weight"', false)
            ->assertDontSee('name="subtask_weight_percentage"', false);
    }

    public function test_edit_forms_distinguish_child_parent_and_regular_task_status(): void
    {
        $parent = $this->createTask();
        $child = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 100,
        ]);

        $this->actingAs($this->member)
            ->get(route('tasks.edit', $child->token))
            ->assertOk()
            ->assertSee($parent->name)
            ->assertSee('name="subtask_weight_percentage"', false)
            ->assertDontSee('name="parent_task_id"', false);

        $this->actingAs($this->viewer)
            ->get(route('tasks.edit', $child->token))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('tasks.edit', $parent->token))
            ->assertOk()
            ->assertSee('Status mengikuti subtask')
            ->assertSee('type="hidden" name="status"', false)
            ->assertDontSee('<select name="status"', false);

        $ordinaryTask = $this->createTask();
        $this->actingAs($this->manager)
            ->get(route('tasks.edit', $ordinaryTask->token))
            ->assertOk()
            ->assertSee('<select name="status"', false);
    }

    public function test_task_detail_renders_nested_hierarchy_breadcrumb_weight_and_derived_progress(): void
    {
        $root = $this->createTask(['name' => 'Root hierarchy']);
        $middle = $this->createTask([
            'name' => 'Middle hierarchy',
            'parent_task_id' => $root->id,
            'subtask_weight_percentage' => 100,
        ]);
        $completedLeaf = $this->createTask([
            'name' => 'Completed leaf',
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 40,
            'status' => 'completed',
        ]);
        $this->createTask([
            'name' => 'Pending leaf',
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 60,
        ]);

        $this->actingAs($this->manager)
            ->get(route('tasks.show', $root->token))
            ->assertOk()
            ->assertSeeInOrder(['Root hierarchy', 'Middle hierarchy', 'Completed leaf', 'Pending leaf'])
            ->assertSee('40.00%')
            ->assertSee('Complete');

        $this->actingAs($this->manager)
            ->get(route('tasks.show', $middle->token))
            ->assertOk()
            ->assertSeeInOrder([$this->project->name, 'Root hierarchy', 'Middle hierarchy'])
            ->assertSee('Subtask');

        $this->actingAs($this->viewer)
            ->get(route('tasks.show', $root->token))
            ->assertOk()
            ->assertDontSee('Tambah Subtask');

        $this->actingAs($this->manager)
            ->get(route('tasks.show', $completedLeaf->token))
            ->assertOk()
            ->assertDontSee('Tambah Subtask');

    }

    public function test_task_list_adds_parent_context_only_to_children(): void
    {
        $root = $this->createTask(['name' => 'List root']);
        $child = $this->createTask([
            'name' => 'List child',
            'parent_task_id' => $root->id,
            'subtask_weight_percentage' => 100,
        ]);
        $root->assignees()->sync([$this->manager->id]);
        $child->assignees()->sync([$this->manager->id]);

        $response = $this->actingAs($this->manager)
            ->get(route('tasks.index', ['view' => 'list']));

        $response->assertOk()
            ->assertSee('List root')
            ->assertSee('List child')
            ->assertSee('Parent: List root')
            ->assertSee('Subtask');
    }

    public function test_project_list_renders_roots_and_descendants_in_hierarchy_order(): void
    {
        $root = $this->createTask(['name' => 'Project hierarchy root', 'priority' => 'urgent']);
        $child = $this->createTask([
            'name' => 'Project hierarchy child',
            'parent_task_id' => $root->id,
            'subtask_weight_percentage' => 100,
        ]);
        $this->createTask([
            'name' => 'Project hierarchy grandchild',
            'parent_task_id' => $child->id,
            'subtask_weight_percentage' => 100,
        ]);
        $this->createTask(['name' => 'Second project root']);

        $this->actingAs($this->manager)
            ->get(route('projects.show', ['token' => $this->project->token, 'view' => 'list']))
            ->assertOk()
            ->assertSeeInOrder([
                'Project hierarchy root',
                'Project hierarchy child',
                'Project hierarchy grandchild',
                'Second project root',
            ]);
    }

    public function test_full_update_rejects_parent_status_but_allows_non_status_fields(): void
    {
        $parent = $this->createTask();
        $this->createTask(['parent_task_id' => $parent->id, 'subtask_weight_percentage' => 100]);

        $this->actingAs($this->manager)
            ->put(route('tasks.update', $parent->token), $this->updatePayload($parent, ['status' => 'completed']))
            ->assertSessionHasErrors('status');

        $this->actingAs($this->manager)
            ->put(route('tasks.update', $parent->token), $this->updatePayload($parent, ['name' => 'Renamed parent']))
            ->assertRedirect();

        $this->assertSame('Renamed parent', $parent->fresh()->name);
        $this->assertSame('to_do', $parent->fresh()->status);
    }

    public function test_child_readiness_and_weight_limits_are_enforced(): void
    {
        $parent = $this->createTask();
        $first = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 40,
        ]);
        $second = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
        ]);

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $first->token), ['status' => 'in_progress'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->manager)
            ->put(route('tasks.update', $second->token), $this->updatePayload($second, ['subtask_weight_percentage' => 60]))
            ->assertRedirect();

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $first->token), ['status' => 'in_progress'])
            ->assertOk();

        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload([
                'parent_task_id' => $parent->id,
                'subtask_weight_percentage' => 1,
            ]))
            ->assertSessionHasErrors('subtask_weight_percentage');

        $this->actingAs($this->manager)
            ->put(route('tasks.update', $first->token), $this->updatePayload($first, ['subtask_weight_percentage' => 50]))
            ->assertSessionHasErrors('subtask_weight_percentage');
    }

    public function test_full_update_child_uses_the_same_status_flow_and_records_only_real_changes(): void
    {
        $parent = $this->createTask();
        $child = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 100,
        ]);

        $this->actingAs($this->member)
            ->put(route('tasks.update', $child->token), $this->updatePayload($child, ['status' => 'stopped']))
            ->assertRedirect();

        $this->assertSame('stopped', $child->fresh()->status);
        $this->assertEquals(0, $child->fresh()->stopped_progress);
        $this->assertSame('stopped', $parent->fresh()->status);
        $this->assertSame(1, TaskStatusHistory::where('task_id', $child->id)->count());
        $this->assertSame(1, TaskStatusHistory::where('task_id', $parent->id)->count());

        $this->actingAs($this->member)
            ->put(route('tasks.update', $child->token), $this->updatePayload($child->fresh()))
            ->assertRedirect();

        $this->assertSame(1, TaskStatusHistory::where('task_id', $child->id)->count());
        $this->assertSame(1, TaskStatusHistory::where('task_id', $parent->id)->count());
    }

    public function test_child_status_synchronizes_nested_ancestors_and_history_once(): void
    {
        $root = $this->createTask(['created_by' => $this->viewer->id]);
        $middle = $this->createTask([
            'parent_task_id' => $root->id,
            'subtask_weight_percentage' => 100,
            'created_by' => $this->viewer->id,
        ]);
        $leaf = $this->createTask([
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 100,
        ]);

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $leaf->token), ['status' => 'completed'])
            ->assertOk();

        $this->assertSame('completed', $middle->fresh()->status);
        $this->assertSame('completed', $root->fresh()->status);
        $this->assertNotNull($middle->fresh()->completed_at);
        $this->assertNotNull($root->fresh()->completed_at);
        $this->assertSame(2, TaskStatusHistory::whereIn('task_id', [$middle->id, $root->id])->count());
        $this->assertSame(
            [$this->member->id],
            TaskStatusHistory::whereIn('task_id', [$middle->id, $root->id])->pluck('changed_by')->unique()->all()
        );
        $this->assertSame(2, ActivityLog::whereIn('entity_id', [$middle->id, $root->id])->where('action', 'status_changed')->count());

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $leaf->token), ['status' => 'completed'])
            ->assertOk();
        $this->assertSame(2, TaskStatusHistory::whereIn('task_id', [$middle->id, $root->id])->count());
    }

    public function test_status_change_that_keeps_parent_derived_status_creates_no_parent_history_or_notification(): void
    {
        $parent = $this->createTask(['created_by' => $this->viewer->id, 'status' => 'in_progress']);
        $first = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'status' => 'in_progress',
        ]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $first->token), ['status' => 'review'])
            ->assertOk();

        $this->assertSame('in_progress', $parent->fresh()->status);
        $this->assertSame(0, TaskStatusHistory::where('task_id', $parent->id)->count());
        $this->assertSame(0, Notification::where('task_id', $parent->id)->count());
    }

    public function test_create_hierarchy_validation_and_parent_synchronization(): void
    {
        $parent = $this->createTask(['status' => 'completed']);

        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload(['subtask_weight_percentage' => 50]))
            ->assertSessionHasErrors('subtask_weight_percentage');

        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload(['parent_task_id' => $parent->id]))
            ->assertSessionHasErrors('subtask_weight_percentage');

        $foreignParent = $this->createTask(['project_id' => $this->otherProject->id]);
        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload([
                'parent_task_id' => $foreignParent->id,
                'subtask_weight_percentage' => 100,
            ]))
            ->assertSessionHasErrors('parent_task_id');

        $middle = $this->createTask(['parent_task_id' => $parent->id, 'subtask_weight_percentage' => 100]);
        $grandchild = $this->createTask(['parent_task_id' => $middle->id, 'subtask_weight_percentage' => 100]);
        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload([
                'parent_task_id' => $grandchild->id,
                'subtask_weight_percentage' => 100,
            ]))
            ->assertSessionHasErrors('parent_task_id');

        $newParent = $this->createTask(['status' => 'completed']);
        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload([
                'name' => 'Created child',
                'parent_task_id' => $newParent->id,
                'subtask_weight_percentage' => 100,
            ]))
            ->assertRedirect();
        $this->assertSame('to_do', $newParent->fresh()->status);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $newParent->id,
            'from_status' => 'completed',
            'to_status' => 'to_do',
        ]);
    }

    public function test_dependency_hierarchy_guards_reject_ancestor_descendant_and_summary(): void
    {
        $root = $this->createTask();
        $child = $this->createTask(['parent_task_id' => $root->id, 'subtask_weight_percentage' => 100]);

        $this->actingAs($this->manager)
            ->put(route('tasks.update', $child->token), $this->updatePayload($child, ['predecessor_id' => $root->id]))
            ->assertSessionHasErrors('predecessor_id');

        $this->actingAs($this->manager)
            ->put(route('tasks.update', $root->token), $this->updatePayload($root, ['predecessor_id' => $child->id]))
            ->assertSessionHasErrors('predecessor_id');

        $summary = $this->createTask();
        $this->createTask(['parent_task_id' => $summary->id, 'subtask_weight_percentage' => 100]);
        $ordinary = $this->createTask();
        $this->actingAs($this->manager)
            ->put(route('tasks.update', $ordinary->token), $this->updatePayload($ordinary, ['predecessor_id' => $summary->id]))
            ->assertSessionHasErrors('predecessor_id');
    }

    public function test_delete_child_synchronizes_parent_and_last_child_makes_parent_editable(): void
    {
        $parent = $this->createTask(['status' => 'in_progress']);
        $first = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'status' => 'completed',
        ]);
        $last = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'status' => 'to_do',
        ]);

        $this->actingAs($this->manager)->delete(route('tasks.destroy', $last->token))->assertRedirect();
        $this->assertSame('completed', $parent->fresh()->status);
        $this->assertDatabaseHas('tasks', ['id' => $first->id]);

        $this->actingAs($this->manager)->delete(route('tasks.destroy', $first->token))->assertRedirect();
        $this->assertSame('completed', $parent->fresh()->status);

        $this->actingAs($this->manager)
            ->patchJson(route('tasks.updateStatus', $parent->token), ['status' => 'to_do'])
            ->assertOk();
        $this->assertSame('to_do', $parent->fresh()->status);
    }

    public function test_automatic_parent_notification_is_deduplicated_and_email_runs_after_commit(): void
    {
        $parent = $this->createTask(['created_by' => $this->viewer->id]);
        $parent->assignees()->sync([$this->viewer->id]);
        $child = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 100,
        ]);

        $this->actingAs($this->member)
            ->patchJson(route('tasks.updateStatus', $child->token), ['status' => 'completed'])
            ->assertOk();

        $this->assertSame(1, Notification::where('task_id', $parent->id)->count());
        Queue::assertPushed(SendEmailNotification::class, fn (SendEmailNotification $job): bool => $job->title === 'Status Task Utama Berubah');
    }

    public function test_create_and_delete_roll_back_all_hierarchy_side_effects_and_jobs(): void
    {
        $parent = $this->createTask(['created_by' => $this->viewer->id, 'status' => 'completed']);
        $taskCount = Task::count();
        $this->forceProgressFailure();

        $this->actingAs($this->manager)
            ->post('/tasks', $this->storePayload([
                'name' => 'Rolled back child',
                'parent_task_id' => $parent->id,
                'subtask_weight_percentage' => 100,
                'assignee_ids' => [$this->viewer->id],
            ]))
            ->assertSessionHasErrors('error');

        $this->assertSame($taskCount, Task::count());
        $this->assertSame('completed', $parent->fresh()->status);
        $this->assertSame(0, TaskStatusHistory::where('task_id', $parent->id)->count());
        $this->assertSame(0, Notification::count());
        Queue::assertNothingPushed();

        $this->mockProgressService();
        $child = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 100,
            'status' => 'to_do',
        ]);
        $this->forceProgressFailure();

        $this->actingAs($this->manager)->delete(route('tasks.destroy', $child->token))->assertSessionHasErrors('error');
        $this->assertDatabaseHas('tasks', ['id' => $child->id]);
        $this->assertSame('completed', $parent->fresh()->status);
        Queue::assertNothingPushed();
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'project_id' => $this->project->id,
            'name' => 'Hierarchy task',
            'description' => null,
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

    private function updatePayload(Task $task, array $overrides = []): array
    {
        return array_merge([
            'name' => $task->name,
            'description' => $task->description,
            'assignee_ids' => $task->assignees()->pluck('users.id')->all(),
            'status' => $task->status,
            'priority' => $task->priority,
            'weight' => $task->weight,
            'subtask_weight_percentage' => $task->subtask_weight_percentage,
            'start_date' => $task->start_date?->toDateString(),
            'due_date' => $task->due_date?->toDateString(),
            'predecessor_id' => $task->predecessor_id,
            'dependency_type' => $task->dependency_type,
        ], $overrides);
    }

    private function createTask(array $attributes = []): Task
    {
        return Task::factory()->for($this->project)->for($this->manager, 'creator')->create($attributes);
    }

    private function addMember(Project $project, User $user, string $role): void
    {
        $project->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);
    }

    private function mockProgressService(): void
    {
        $this->clearProgressMock();
        $this->mock(ProjectProgressService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncPlannedProgress')->zeroOrMoreTimes();
            $mock->shouldReceive('recordActualProgress')->zeroOrMoreTimes();
        });
    }

    private function forceProgressFailure(): void
    {
        $this->clearProgressMock();
        $this->mock(ProjectProgressService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncPlannedProgress')->andThrow(new RuntimeException('Forced rollback.'));
            $mock->shouldReceive('recordActualProgress')->zeroOrMoreTimes();
        });
    }

    private function clearProgressMock(): void
    {
        $this->app->forgetInstance(ProjectProgressService::class);
    }

    private function seedStatusWeights(): void
    {
        DB::table('task_status_weights')->insert([
            ['status' => 'to_do', 'weight_value' => 0],
            ['status' => 'in_progress', 'weight_value' => 0.50],
            ['status' => 'review', 'weight_value' => 0.75],
            ['status' => 'completed', 'weight_value' => 1],
            ['status' => 'stopped', 'weight_value' => 0.50],
            ['status' => 'cancelled', 'weight_value' => 0],
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
        Schema::create('task_status_weights', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->unique();
            $table->decimal('weight_value', 3, 2);
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
            $table->decimal('subtask_weight_percentage', 5, 2)->nullable();
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
        Schema::create('task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('user_id');
            $table->text('comment');
            $table->timestamps();
        });
        Schema::create('task_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('uploaded_by');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
        Schema::create('project_baselines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('baseline_name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by');
            $table->timestamps();
        });
        Schema::create('task_baselines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_baseline_id');
            $table->foreignId('task_id');
            $table->date('baseline_start');
            $table->date('baseline_end');
            $table->timestamps();
        });
        Schema::create('planned_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('baseline_id');
            $table->date('date');
            $table->decimal('planned_cumulative_percentage', 5, 2);
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('actual_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('baseline_id')->nullable();
            $table->date('date');
            $table->decimal('actual_cumulative_percentage', 5, 2);
            $table->integer('completed_tasks_count')->default(0);
            $table->integer('total_tasks_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by');
            $table->timestamp('created_at')->useCurrent();
        });
    }
}
