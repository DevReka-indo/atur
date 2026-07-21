<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskHierarchyMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_task_id')->nullable();
            $table->decimal('weight', 10, 2)->default(1);
        });
    }

    public function test_root_task_remains_without_subtask_weight(): void
    {
        DB::table('tasks')->insert(['id' => 1, 'parent_task_id' => null, 'weight' => 7.25]);

        $this->runMigration();

        $this->assertTrue(Schema::hasColumn('tasks', 'subtask_weight_percentage'));
        $this->assertNull(DB::table('tasks')->where('id', 1)->value('subtask_weight_percentage'));
    }

    public function test_single_existing_child_is_backfilled_to_one_hundred_percent(): void
    {
        DB::table('tasks')->insert([
            ['id' => 1, 'parent_task_id' => null, 'weight' => 3.00],
            ['id' => 2, 'parent_task_id' => 1, 'weight' => 9.00],
        ]);

        $this->runMigration();

        $this->assertSame(100.0, (float) DB::table('tasks')->where('id', 2)->value('subtask_weight_percentage'));
    }

    public function test_multiple_existing_children_are_not_divided_automatically(): void
    {
        DB::table('tasks')->insert([
            ['id' => 1, 'parent_task_id' => null, 'weight' => 1.00],
            ['id' => 2, 'parent_task_id' => 1, 'weight' => 1.00],
            ['id' => 3, 'parent_task_id' => 1, 'weight' => 2.00],
        ]);

        $this->runMigration();

        $this->assertNull(DB::table('tasks')->where('id', 2)->value('subtask_weight_percentage'));
        $this->assertNull(DB::table('tasks')->where('id', 3)->value('subtask_weight_percentage'));
    }

    public function test_backfill_does_not_change_existing_task_weights(): void
    {
        DB::table('tasks')->insert([
            ['id' => 1, 'parent_task_id' => null, 'weight' => 4.50],
            ['id' => 2, 'parent_task_id' => 1, 'weight' => 8.75],
        ]);

        $this->runMigration();

        $this->assertSame(4.5, (float) DB::table('tasks')->where('id', 1)->value('weight'));
        $this->assertSame(8.75, (float) DB::table('tasks')->where('id', 2)->value('weight'));
    }

    public function test_backfill_is_idempotent_for_previously_resolved_children(): void
    {
        DB::table('tasks')->insert([
            ['id' => 1, 'parent_task_id' => null, 'weight' => 1.00],
            ['id' => 2, 'parent_task_id' => 1, 'weight' => 1.00],
        ]);

        $migration = $this->runMigration();
        DB::table('tasks')->where('id', 2)->update(['subtask_weight_percentage' => 75.00]);
        $migration->up();

        $this->assertSame(75.0, (float) DB::table('tasks')->where('id', 2)->value('subtask_weight_percentage'));
    }

    private function runMigration(): Migration
    {
        $migration = require database_path('migrations/2026_07_20_154916_add_subtask_weight_percentage_to_tasks_table.php');
        $migration->up();

        return $migration;
    }
}
