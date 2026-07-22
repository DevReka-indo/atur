<?php

namespace Tests\Support;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

trait CreatesProjectTemplateTestSchema
{
    protected function createProjectTemplateTestSchema(): void
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

        $permissionMigration = require database_path('migrations/2026_07_22_083512_create_permission_tables.php');
        $permissionMigration->up();

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
            $table->timestamp('joined_at')->nullable();
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
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });
        Schema::create('task_status_weights', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->unique();
            $table->decimal('weight_value', 5, 2);
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
            $table->unsignedInteger('position')->default(0);
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
            $table->timestamp('changed_at')->nullable();
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
            $table->timestamp('created_at')->nullable();
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
            $table->timestamp('created_at')->nullable();
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

        foreach ([
            '2026_07_22_142307_create_project_template_categories_table.php',
            '2026_07_22_142308_create_project_templates_table.php',
            '2026_07_22_142309_create_project_template_tasks_table.php',
            '2026_07_22_142310_create_project_template_task_dependencies_table.php',
            '2026_07_22_142311_add_project_template_source_to_projects_table.php',
        ] as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->up();
        }

        DB::table('task_status_weights')->insert([
            ['status' => 'to_do', 'weight_value' => 0],
            ['status' => 'in_progress', 'weight_value' => 0.5],
            ['status' => 'review', 'weight_value' => 0.8],
            ['status' => 'completed', 'weight_value' => 1],
            ['status' => 'stopped', 'weight_value' => 0],
            ['status' => 'cancelled', 'weight_value' => 0],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolePermissionSeeder::class);
    }
}
