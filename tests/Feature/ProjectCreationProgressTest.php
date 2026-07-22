<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ActualProgress;
use App\Models\PlannedProgress;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\Task;
use App\Models\TaskBaseline;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ProjectCreationProgressTest extends TestCase
{
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
    }

    public function test_project_creation_builds_members_tasks_baseline_and_progress_in_order(): void
    {
        $workspaceOwner = User::factory()->create();
        $creator = User::factory()->create();
        $workspace = Workspace::factory()->for($workspaceOwner, 'creator')->create();
        $workspace->members()->attach($creator->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($creator)->post(route('projects.store'), $this->validPayload($workspace));

        $project = Project::where('name', 'Transactional project')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project->token));

        $tasks = Task::where('project_id', $project->id)->get();
        $this->assertCount(6, $tasks);
        $this->assertEqualsCanonicalizing([
            'Project Kickoff',
            'Requirement Gathering',
            'Planning & Scheduling',
            'Execution',
            'Review & Testing',
            'Project Closing',
        ], $tasks->pluck('name')->all());

        $this->assertSame(
            'manager',
            $project->members()->where('users.id', $creator->id)->firstOrFail()->pivot->role
        );
        $this->assertSame(
            'manager',
            $project->members()->where('users.id', $workspaceOwner->id)->firstOrFail()->pivot->role
        );

        $this->assertSame(1, ProjectBaseline::where('project_id', $project->id)->where('is_active', true)->count());
        $baseline = ProjectBaseline::where('project_id', $project->id)->where('is_active', true)->firstOrFail();

        $this->assertSame(6, TaskBaseline::where('project_baseline_id', $baseline->id)->count());
        foreach ($tasks as $task) {
            $taskBaseline = TaskBaseline::where('project_baseline_id', $baseline->id)
                ->where('task_id', $task->id)
                ->firstOrFail();

            $this->assertSame($task->start_date->toDateString(), $taskBaseline->baseline_start->toDateString());
            $this->assertSame($task->due_date->toDateString(), $taskBaseline->baseline_end->toDateString());
        }

        $plannedProgress = PlannedProgress::where('baseline_id', $baseline->id)->orderBy('date')->get();
        $this->assertCount(2, $plannedProgress);
        $this->assertNotCount(11, $plannedProgress);
        $this->assertSame(16.67, (float) $plannedProgress->first()->planned_cumulative_percentage);
        $this->assertSame(100.0, (float) $plannedProgress->last()->planned_cumulative_percentage);

        $actualProgress = ActualProgress::where('project_id', $project->id)
            ->where('baseline_id', $baseline->id)
            ->firstOrFail();
        $this->assertSame(6, $actualProgress->total_tasks_count);
        $this->assertSame(0, $actualProgress->completed_tasks_count);
        $this->assertSame(0.0, (float) $actualProgress->actual_cumulative_percentage);
        $this->assertSame(1, ProjectBaseline::where('project_id', $project->id)->count());
        $this->assertSame(1, ActivityLog::where('entity_type', 'project')->where('entity_id', $project->id)->count());
    }

    public function test_exception_before_activity_log_is_saved_rolls_back_the_entire_flow(): void
    {
        $workspaceOwner = User::factory()->create();
        $creator = User::factory()->create();
        $workspace = Workspace::factory()->for($workspaceOwner, 'creator')->create();
        $workspace->members()->attach($creator->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        ActivityLog::creating(function (): never {
            throw new RuntimeException('Forced activity log failure.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($creator)->post(route('projects.store'), $this->validPayload($workspace));
            $this->fail('The forced activity log exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced activity log failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertSame(0, Project::count());
        $this->assertSame(0, DB::table('project_members')->count());
        $this->assertSame(0, Task::count());
        $this->assertSame(0, ProjectBaseline::count());
        $this->assertSame(0, TaskBaseline::count());
        $this->assertSame(0, PlannedProgress::count());
        $this->assertSame(0, ActualProgress::count());
        $this->assertSame(0, ActivityLog::count());
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Workspace $workspace): array
    {
        return [
            'workspace_id' => $workspace->id,
            'name' => 'Transactional project',
            'description' => 'Project creation transaction test.',
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'status' => 'planning',
        ];
    }

    private function registerSqliteFieldFunction(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction(
            'FIELD',
            static function (mixed $value, mixed ...$values): int {
                $position = array_search($value, $values, true);

                return $position === false ? 0 : $position + 1;
            },
            -1
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
            $table->foreignId('project_template_id')->nullable();
            $table->string('source_template_name')->nullable();
            $table->unsignedInteger('source_template_version')->nullable();
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

        Schema::create('task_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by');
            $table->timestamp('changed_at')->useCurrent();
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
            $table->unique(['baseline_id', 'date']);
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
            $table->unique(['project_id', 'baseline_id', 'date']);
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
