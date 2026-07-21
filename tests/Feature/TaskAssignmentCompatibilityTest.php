<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskAssignmentCompatibilityTest extends TestCase
{
    private User $user;

    private User $otherUser;

    private Workspace $workspace;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->registerSqliteFieldFunction();

        DB::table('task_status_weights')->insert([
            'status' => 'to_do',
            'weight_value' => 0,
            'description' => 'Not started',
        ]);

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->workspace = Workspace::factory()->for($this->user, 'creator')->create();
        $this->workspace->members()->attach($this->user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        $this->project = Project::factory()
            ->for($this->workspace)
            ->for($this->user, 'creator')
            ->create();
        $this->project->members()->attach($this->user->id, [
            'role' => 'manager',
            'joined_at' => now(),
        ]);

        Queue::fake();
        $this->actingAs($this->user);
    }

    public function test_task_index_includes_legacy_and_pivot_assignments_once_and_excludes_other_users(): void
    {
        $legacyTask = $this->createTask(['name' => 'Legacy index task', 'assignee_id' => $this->user->id]);
        $pivotTask = $this->createTask(['name' => 'Pivot index task']);
        $pivotTask->assignees()->sync([$this->user->id]);
        $bothTask = $this->createTask(['name' => 'Both index task', 'assignee_id' => $this->user->id]);
        $bothTask->assignees()->sync([$this->user->id]);
        $pivotOverridesLegacyTask = $this->createTask([
            'name' => 'Pivot overrides legacy task',
            'assignee_id' => $this->user->id,
        ]);
        $pivotOverridesLegacyTask->assignees()->sync([$this->otherUser->id]);
        $this->createTask(['name' => 'Other user task', 'assignee_id' => $this->otherUser->id]);
        $this->createTask(['name' => 'Unassigned index task']);

        $view = app(TaskController::class)->index(Request::create('/tasks', 'GET'));
        $taskIds = $view->getData()['tasks']->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$legacyTask->id, $pivotTask->id, $bothTask->id], $taskIds);
        $this->assertSame(3, Task::assignedTo($this->user->id)->count());
        $this->assertSame(3, Task::assignedToUser($this->user->id)->count());
        $this->assertNotContains($pivotOverridesLegacyTask->id, $taskIds);
        $this->assertTrue(Task::assignedToUser($this->otherUser->id)->whereKey($pivotOverridesLegacyTask)->exists());
    }

    public function test_dashboard_assigned_count_counts_legacy_and_pivot_tasks_once(): void
    {
        $this->createTask(['assignee_id' => $this->user->id]);
        $pivotTask = $this->createTask();
        $pivotTask->assignees()->sync([$this->user->id]);
        $bothTask = $this->createTask(['assignee_id' => $this->user->id]);
        $bothTask->assignees()->sync([$this->user->id]);
        $this->createTask(['assignee_id' => $this->otherUser->id]);

        $view = app(DashboardController::class)->index();

        $this->assertSame(3, $view->getData()['stats']['assigned_tasks']);
    }

    public function test_personal_gantt_includes_legacy_and_pivot_tasks(): void
    {
        $legacyTask = $this->createTask(['name' => 'Legacy Gantt task', 'assignee_id' => $this->user->id]);
        $pivotTask = $this->createTask(['name' => 'Pivot Gantt task']);
        $pivotTask->assignees()->sync([$this->user->id]);
        $this->createTask(['name' => 'Other Gantt task', 'assignee_id' => $this->otherUser->id]);

        $response = app(TaskController::class)->ganttData(Request::create('/gantt/data', 'GET'));
        $taskIds = collect($response->getData(true)['data'])->pluck('id')->all();

        $this->assertEqualsCanonicalizing([
            'task-'.$legacyTask->id,
            'task-'.$pivotTask->id,
        ], $taskIds);
    }

    public function test_project_card_shows_all_pivot_assignees_then_legacy_fallback_and_unassigned(): void
    {
        $firstPivotAssignee = User::factory()->create(['name' => 'Pivot Alpha']);
        $secondPivotAssignee = User::factory()->create(['name' => 'Pivot Beta']);
        $legacyAssignee = User::factory()->create(['name' => 'Legacy Charlie']);

        $pivotTask = $this->createTask(['name' => 'Pivot display task']);
        $pivotTask->assignees()->sync([$firstPivotAssignee->id, $secondPivotAssignee->id]);
        $this->createTask(['name' => 'Legacy display task', 'assignee_id' => $legacyAssignee->id]);
        $this->createTask(['name' => 'Unassigned display task']);

        $response = $this->get(route('projects.show', [
            'token' => $this->project->token,
            'view' => 'kanban',
        ]));

        $response->assertOk();
        $response->assertSee('Pivot Alpha');
        $response->assertSee('Pivot Beta');
        $response->assertSee('Legacy Charlie');
        $response->assertSee('Unassigned');
    }

    public function test_edit_legacy_task_displays_and_selects_the_legacy_assignee_without_writing_pivot(): void
    {
        $legacyAssignee = User::factory()->create(['name' => 'Legacy Editor']);
        $this->project->members()->attach($legacyAssignee->id, [
            'role' => 'viewer',
            'joined_at' => now(),
        ]);
        $legacyTask = $this->createTask(['assignee_id' => $legacyAssignee->id]);

        $response = $this->get(route('tasks.edit', $legacyTask->token));

        $response->assertOk();
        $response->assertSee('Legacy Editor');
        $this->assertMatchesRegularExpression(
            '/name="assignee_ids\[\]" value="'.$legacyAssignee->id.'"[\s\S]*?checked/',
            $response->getContent(),
        );
        $this->assertSame(0, $legacyTask->assignees()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTask(array $attributes = []): Task
    {
        return Task::factory()
            ->for($this->project)
            ->for($this->user, 'creator')
            ->create($attributes);
    }

    private function registerSqliteFieldFunction(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction(
            'FIELD',
            static function (mixed $value, mixed ...$values): int {
                $position = array_search($value, $values, true);

                return $position === false ? 0 : $position + 1;
            },
            -1,
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
            $table->string('profile_photo')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by');
            $table->string('invite_token')->nullable();
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
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
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

        Schema::create('device_users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('device_id');
            $table->foreignId('user_id');
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
            $table->foreignId('baseline_id');
            $table->date('date');
            $table->decimal('actual_cumulative_percentage', 5, 2);
            $table->integer('completed_tasks_count')->default(0);
            $table->integer('total_tasks_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('task_baselines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_baseline_id');
            $table->foreignId('task_id');
            $table->date('baseline_start');
            $table->date('baseline_end');
            $table->timestamps();
        });
    }
}
