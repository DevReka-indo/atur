<?php

namespace Tests\Feature;

use App\Models\ActualProgress;
use App\Models\PlannedProgress;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskBaseline;
use App\Services\ProjectProgressService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectHierarchyProgressTest extends TestCase
{
    private Project $project;

    private ProjectProgressService $progressService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->seedStatusWeights();

        DB::table('users')->insert(['id' => 1]);
        $this->project = Project::query()->create([
            'name' => 'Hierarchy progress project',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_by' => 1,
        ]);
        $this->progressService = app(ProjectProgressService::class);
    }

    public function test_regular_tasks_keep_the_existing_weighted_status_progress(): void
    {
        $this->createTask(['weight' => 2, 'status' => 'completed']);
        $this->createTask(['weight' => 1, 'status' => 'in_progress']);

        $this->assertEqualsWithDelta(83.33, $this->project->calculateProgress(), 0.01);
    }

    public function test_parent_uses_derived_progress_without_counting_child_weight_in_project_denominator(): void
    {
        $parent = $this->createTask(['weight' => 1]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'weight' => 900,
            'subtask_weight_percentage' => 40,
            'status' => 'completed',
        ]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'weight' => 700,
            'subtask_weight_percentage' => 60,
            'status' => 'to_do',
        ]);

        $this->assertSame(40.0, $this->project->calculateProgress());
        $this->assertSame(0.4, $parent->fresh()->earned_value);
        $this->assertSame(0.0, Task::query()->where('parent_task_id', $parent->id)->firstOrFail()->earned_value);
    }

    public function test_nested_hierarchy_is_resolved_recursively(): void
    {
        $root = $this->createTask(['weight' => 4]);
        $middle = $this->createTask([
            'parent_task_id' => $root->id,
            'subtask_weight_percentage' => 100,
        ]);
        $this->createTask([
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 25,
            'status' => 'completed',
        ]);
        $this->createTask([
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 75,
            'status' => 'in_progress',
        ]);

        $this->assertSame(62.5, $this->project->calculateProgress());
    }

    public function test_cancelled_roots_are_excluded_and_stopped_uses_the_central_resolver(): void
    {
        $this->createTask(['weight' => 100, 'status' => 'cancelled']);
        $this->createTask(['weight' => 1, 'status' => 'stopped', 'stopped_progress' => 40]);

        $this->assertSame(40.0, $this->project->calculateProgress());
    }

    public function test_project_without_tasks_has_zero_actual_progress_and_default_planned_curve(): void
    {
        $this->assertSame(0.0, $this->project->calculateProgress());

        $baseline = $this->progressService->syncPlannedProgress($this->project);

        $planned = PlannedProgress::query()->where('baseline_id', $baseline->id)->orderBy('date')->get();
        $this->assertCount(11, $planned);
        $this->assertSame(0.0, (float) $planned->first()->planned_cumulative_percentage);
        $this->assertSame(100.0, (float) $planned->last()->planned_cumulative_percentage);
    }

    public function test_actual_progress_counts_only_executable_leaf_tasks(): void
    {
        $parent = $this->createTask();
        $this->createTask(['parent_task_id' => $parent->id, 'subtask_weight_percentage' => 50, 'status' => 'completed']);
        $middle = $this->createTask(['parent_task_id' => $parent->id, 'subtask_weight_percentage' => 50]);
        $this->createTask(['parent_task_id' => $middle->id, 'subtask_weight_percentage' => 50, 'status' => 'completed']);
        $this->createTask(['parent_task_id' => $middle->id, 'subtask_weight_percentage' => 50, 'status' => 'cancelled']);
        $this->createTask(['status' => 'completed']);

        $this->progressService->recordActualProgress($this->project);

        $actual = ActualProgress::query()->where('project_id', $this->project->id)->firstOrFail();
        $this->assertSame(4, $actual->total_tasks_count);
        $this->assertSame(3, $actual->completed_tasks_count);
    }

    public function test_planned_progress_distributes_four_children_without_a_parent_double_curve(): void
    {
        $parent = $this->createTask([
            'weight' => 1,
            'start_date' => '2026-01-01',
            'due_date' => '2026-12-31',
        ]);

        foreach ([[20, '2026-02-01'], [30, '2026-02-02'], [20, '2026-02-03'], [30, '2026-02-04']] as [$weight, $date]) {
            $this->createTask([
                'parent_task_id' => $parent->id,
                'subtask_weight_percentage' => $weight,
                'start_date' => $date,
                'due_date' => $date,
            ]);
        }

        $baseline = $this->progressService->syncPlannedProgress($this->project);
        $planned = PlannedProgress::query()
            ->where('baseline_id', $baseline->id)
            ->orderBy('date')
            ->pluck('planned_cumulative_percentage', 'date')
            ->map(fn ($value): float => (float) $value)
            ->all();

        $this->assertSame([
            '2026-02-01' => 20.0,
            '2026-02-02' => 50.0,
            '2026-02-03' => 70.0,
            '2026-02-04' => 100.0,
        ], $planned);
    }

    public function test_nested_planned_progress_and_incomplete_child_weights_are_not_normalized(): void
    {
        $completeRoot = $this->createTask(['weight' => 1]);
        $middle = $this->createTask([
            'parent_task_id' => $completeRoot->id,
            'subtask_weight_percentage' => 100,
        ]);
        $this->createTask([
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 40,
            'start_date' => '2026-03-01',
            'due_date' => '2026-03-01',
        ]);
        $this->createTask([
            'parent_task_id' => $middle->id,
            'subtask_weight_percentage' => 60,
            'start_date' => '2026-03-02',
            'due_date' => '2026-03-02',
        ]);

        $baseline = $this->progressService->syncPlannedProgress($this->project);
        $this->assertSame(
            100.0,
            (float) PlannedProgress::query()->where('baseline_id', $baseline->id)->latest('date')->value('planned_cumulative_percentage')
        );

        $incompleteProject = Project::query()->create([
            'name' => 'Incomplete hierarchy',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_by' => 1,
        ]);
        $incompleteParent = $this->createTask(['project_id' => $incompleteProject->id]);
        $this->createTask([
            'project_id' => $incompleteProject->id,
            'parent_task_id' => $incompleteParent->id,
            'subtask_weight_percentage' => 30,
            'start_date' => '2026-04-01',
            'due_date' => '2026-04-01',
        ]);
        $this->createTask([
            'project_id' => $incompleteProject->id,
            'parent_task_id' => $incompleteParent->id,
            'subtask_weight_percentage' => 40,
            'start_date' => '2026-04-02',
            'due_date' => '2026-04-02',
        ]);

        $incompleteBaseline = $this->progressService->syncPlannedProgress($incompleteProject);
        $this->assertSame(
            70.0,
            (float) PlannedProgress::query()->where('baseline_id', $incompleteBaseline->id)->latest('date')->value('planned_cumulative_percentage')
        );
    }

    public function test_parent_baseline_uses_descendant_timeline_and_snapshots_every_hierarchy_level_once(): void
    {
        $parent = $this->createTask([
            'start_date' => '2026-01-01',
            'due_date' => '2026-12-31',
        ]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'start_date' => '2026-02-01',
            'due_date' => '2026-02-10',
        ]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'start_date' => '2026-03-01',
            'due_date' => '2026-03-20',
        ]);

        $baseline = $this->progressService->syncPlannedProgress($this->project);
        $this->progressService->syncPlannedProgress($this->project);

        $parentBaseline = TaskBaseline::query()
            ->where('project_baseline_id', $baseline->id)
            ->where('task_id', $parent->id)
            ->firstOrFail();

        $this->assertSame('2026-02-01', $parentBaseline->baseline_start->toDateString());
        $this->assertSame('2026-03-20', $parentBaseline->baseline_end->toDateString());
        $this->assertSame(3, TaskBaseline::query()->where('project_baseline_id', $baseline->id)->count());
    }

    private function createTask(array $attributes = []): Task
    {
        return Task::query()->create(array_merge([
            'project_id' => $this->project->id,
            'parent_task_id' => null,
            'name' => 'Progress task',
            'status' => 'to_do',
            'priority' => 'medium',
            'weight' => 1,
            'subtask_weight_percentage' => null,
            'created_by' => 1,
            'dependency_type' => 'FS',
        ], $attributes));
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
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('created_by');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
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
            $table->string('status')->default('to_do');
            $table->string('priority')->default('medium');
            $table->decimal('weight', 10, 2)->default(1);
            $table->decimal('subtask_weight_percentage', 5, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
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
    }
}
