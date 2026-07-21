<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Services\TaskHierarchyService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskHierarchyServiceTest extends TestCase
{
    private TaskHierarchyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        Queue::fake();
        $this->seedStatusWeights();
        DB::table('users')->insert(['id' => 1]);
        DB::table('projects')->insert([['id' => 1], ['id' => 2]]);

        $this->service = app(TaskHierarchyService::class);
    }

    public function test_regular_task_uses_database_status_progress(): void
    {
        $task = $this->createTask();

        foreach (['to_do' => 0, 'in_progress' => 50, 'review' => 75, 'completed' => 100, 'cancelled' => 0] as $status => $expected) {
            $task->update(['status' => $status, 'stopped_progress' => null]);
            $task->unsetRelation('statusWeight');

            $this->assertSame((float) $expected, $this->service->resolveProgressPercentage($task));
        }
    }

    public function test_parent_progress_and_earned_contribution_use_child_percentages(): void
    {
        $parent = $this->createTask(['weight' => 1]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 40,
            'status' => 'completed',
        ]);
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 60,
            'status' => 'to_do',
        ]);

        $this->assertSame(40.0, $this->service->resolveProgressPercentage($parent));
        $this->assertSame(0.4, $this->service->resolveEarnedContribution($parent));
    }

    public function test_four_child_weights_are_calculated_correctly(): void
    {
        $parent = $this->createTask();

        foreach ([[20, 'completed'], [30, 'in_progress'], [20, 'to_do'], [30, 'completed']] as [$weight, $status]) {
            $this->createTask([
                'parent_task_id' => $parent->id,
                'subtask_weight_percentage' => $weight,
                'status' => $status,
            ]);
        }

        $this->assertSame(65.0, $this->service->resolveProgressPercentage($parent));
    }

    public function test_nested_hierarchy_progress_is_resolved_recursively(): void
    {
        $root = $this->createTask();
        $childParent = $this->createTask([
            'parent_task_id' => $root->id,
            'subtask_weight_percentage' => 100,
        ]);
        $this->createTask([
            'parent_task_id' => $childParent->id,
            'subtask_weight_percentage' => 40,
            'status' => 'completed',
        ]);
        $this->createTask([
            'parent_task_id' => $childParent->id,
            'subtask_weight_percentage' => 60,
            'status' => 'to_do',
        ]);

        $this->assertSame(40.0, $this->service->resolveProgressPercentage($root));
    }

    public function test_cancelled_and_stopped_tasks_follow_the_central_status_resolver(): void
    {
        $parent = $this->createTask();
        $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'status' => 'cancelled',
        ]);
        $stopped = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
            'status' => 'stopped',
            'stopped_progress' => 40,
        ]);

        $this->assertSame(40.0, $this->service->resolveStatusProgressPercentage($stopped));
        $this->assertSame(20.0, $this->service->resolveProgressPercentage($parent));

        $stopped->update(['stopped_progress' => null]);
        TaskStatusHistory::create([
            'task_id' => $stopped->id,
            'from_status' => 'review',
            'to_status' => 'stopped',
            'changed_by' => 1,
        ]);
        $stopped->unsetRelation('statusHistory');

        $this->assertSame(75.0, $this->service->resolveStatusProgressPercentage($stopped));
    }

    public function test_progress_cycle_is_detected_without_infinite_traversal(): void
    {
        $first = $this->createTask(['subtask_weight_percentage' => 100]);
        $second = $this->createTask([
            'parent_task_id' => $first->id,
            'subtask_weight_percentage' => 100,
        ]);
        $first->update(['parent_task_id' => $second->id]);

        $this->expectException(DomainException::class);

        $this->service->resolveProgressPercentage($first->fresh());
    }

    #[DataProvider('derivedStatusCases')]
    public function test_parent_status_is_derived_from_direct_children(array $statuses, string $expectedStatus): void
    {
        $parent = $this->createTask(['status' => 'in_progress']);
        foreach ($statuses as $status) {
            $this->createTask(['parent_task_id' => $parent->id, 'status' => $status]);
        }

        $this->assertSame($expectedStatus, $this->service->deriveStatus($parent));
    }

    public static function derivedStatusCases(): array
    {
        return [
            'all to do' => [['to_do', 'to_do'], 'to_do'],
            'all completed' => [['completed', 'completed'], 'completed'],
            'all cancelled' => [['cancelled', 'cancelled'], 'cancelled'],
            'review without in progress' => [['review', 'completed'], 'review'],
            'stopped and cancelled only' => [['stopped', 'cancelled'], 'stopped'],
            'mixed active statuses' => [['review', 'in_progress'], 'in_progress'],
        ];
    }

    public function test_synchronization_updates_completed_at_and_only_records_real_status_changes(): void
    {
        $parent = $this->createTask(['status' => 'to_do']);
        $child = $this->createTask(['parent_task_id' => $parent->id, 'status' => 'completed']);

        $this->service->synchronizeAncestors($child, 1);

        $this->assertSame('completed', $parent->fresh()->status);
        $this->assertNotNull($parent->fresh()->completed_at);
        $this->assertSame(1, TaskStatusHistory::where('task_id', $parent->id)->count());

        $this->service->synchronizeAncestors($child, 1);
        $this->assertSame(1, TaskStatusHistory::where('task_id', $parent->id)->count());

        $child->update(['status' => 'to_do']);
        $this->service->synchronizeAncestors($child, 1);

        $this->assertSame('to_do', $parent->fresh()->status);
        $this->assertNull($parent->fresh()->completed_at);
        $this->assertSame(2, TaskStatusHistory::where('task_id', $parent->id)->count());
    }

    public function test_synchronization_updates_all_ancestors(): void
    {
        $root = $this->createTask(['status' => 'to_do']);
        $middle = $this->createTask(['parent_task_id' => $root->id, 'status' => 'to_do']);
        $leaf = $this->createTask(['parent_task_id' => $middle->id, 'status' => 'completed']);

        $this->service->synchronizeAncestors($leaf, 1);

        $this->assertSame('completed', $middle->fresh()->status);
        $this->assertSame('completed', $root->fresh()->status);
        $this->assertSame(2, TaskStatusHistory::count());
    }

    public function test_total_sibling_weight_cannot_exceed_one_hundred(): void
    {
        $parent = $this->createTask();
        $this->createTask(['parent_task_id' => $parent->id, 'subtask_weight_percentage' => 60]);
        $candidate = $this->newTaskContext(1, $parent->id, 50);

        $this->assertValidationError(
            fn () => $this->service->validateSubtaskWeight($candidate),
            'subtask_weight_percentage'
        );
    }

    public function test_total_sibling_weight_may_remain_below_one_hundred(): void
    {
        $parent = $this->createTask();
        $this->createTask(['parent_task_id' => $parent->id, 'subtask_weight_percentage' => 30]);
        $candidate = $this->newTaskContext(1, $parent->id, 40);

        $this->service->validateSubtaskWeight($candidate);

        $this->addToAssertionCount(1);
    }

    public function test_root_and_child_weight_semantics_are_validated(): void
    {
        $rootWithPercentage = $this->newTaskContext(projectId: 1, percentage: 25);
        $childWithoutPercentage = $this->newTaskContext(projectId: 1, parentTaskId: 10);

        $this->assertValidationError(
            fn () => $this->service->validateSubtaskWeight($rootWithPercentage),
            'subtask_weight_percentage'
        );
        $this->assertValidationError(
            fn () => $this->service->validateSubtaskWeight($childWithoutPercentage),
            'subtask_weight_percentage'
        );
    }

    public function test_child_cannot_leave_to_do_until_sibling_weights_total_one_hundred(): void
    {
        $parent = $this->createTask();
        $child = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 40,
            'status' => 'to_do',
        ]);
        $sibling = $this->createTask([
            'parent_task_id' => $parent->id,
            'subtask_weight_percentage' => 50,
        ]);

        try {
            $this->service->validateStatusTransition($child, 'in_progress');
            $this->fail('A child left To Do before sibling weights reached 100%.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $sibling->update(['subtask_weight_percentage' => 60]);
        $this->service->validateStatusTransition($child, 'in_progress');
        $this->addToAssertionCount(1);
    }

    public function test_parent_from_another_project_is_rejected(): void
    {
        $parent = $this->createTask(['project_id' => 2]);
        $candidate = $this->newTaskContext(projectId: 1);

        $this->assertValidationError(
            fn () => $this->service->validateParentCandidate($candidate, $parent),
            'parent_task_id'
        );
    }

    public function test_hierarchy_deeper_than_three_levels_is_rejected(): void
    {
        $root = $this->createTask();
        $child = $this->createTask(['parent_task_id' => $root->id]);
        $grandchild = $this->createTask(['parent_task_id' => $child->id]);
        $candidate = $this->newTaskContext(projectId: 1);

        $this->assertSame(2, $this->service->hierarchyDepth($grandchild));
        $this->assertValidationError(
            fn () => $this->service->validateParentCandidate($candidate, $grandchild),
            'parent_task_id'
        );
    }

    public function test_selecting_a_descendant_as_parent_is_rejected(): void
    {
        $root = $this->createTask();
        $child = $this->createTask(['parent_task_id' => $root->id]);

        $this->assertValidationError(
            fn () => $this->service->validateParentCandidate($root, $child),
            'parent_task_id'
        );
    }

    public function test_selecting_task_itself_as_parent_is_rejected(): void
    {
        $task = $this->createTask();

        $this->assertValidationError(
            fn () => $this->service->validateParentCandidate($task, $task),
            'parent_task_id'
        );
    }

    private function createTask(array $attributes = []): Task
    {
        return Task::query()->create(array_merge([
            'project_id' => 1,
            'parent_task_id' => null,
            'name' => 'Hierarchy task',
            'status' => 'to_do',
            'priority' => 'medium',
            'weight' => 1,
            'subtask_weight_percentage' => null,
            'created_by' => 1,
            'dependency_type' => 'FS',
        ], $attributes));
    }

    private function newTaskContext(int $projectId, ?int $parentTaskId = null, ?float $percentage = null): Task
    {
        return new Task([
            'project_id' => $projectId,
            'parent_task_id' => $parentTaskId,
            'subtask_weight_percentage' => $percentage,
        ]);
    }

    private function assertValidationError(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail("Expected validation error for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
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
            $table->string('name')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
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

        Schema::create('task_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by');
            $table->timestamp('changed_at')->useCurrent();
        });

        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('user_id');
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
