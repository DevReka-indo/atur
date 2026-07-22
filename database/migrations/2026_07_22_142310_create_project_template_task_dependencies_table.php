<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('project_template_task_dependencies')) {
            $foreignKeys = collect(Schema::getForeignKeys('project_template_task_dependencies'))
                ->pluck('name');
            $indexes = collect(Schema::getIndexes('project_template_task_dependencies'))
                ->pluck('name');

            Schema::table('project_template_task_dependencies', function (Blueprint $table) use ($foreignKeys, $indexes) {
                if (! $foreignKeys->contains('pt_dependencies_task_foreign')) {
                    $table->foreign('project_template_task_id', 'pt_dependencies_task_foreign')
                        ->references('id')->on('project_template_tasks')->cascadeOnDelete();
                }
                if (! $foreignKeys->contains('pt_dependencies_predecessor_foreign')) {
                    $table->foreign('predecessor_template_task_id', 'pt_dependencies_predecessor_foreign')
                        ->references('id')->on('project_template_tasks')->cascadeOnDelete();
                }
                if (! $indexes->contains('project_template_dependencies_successor_unique')) {
                    $table->unique('project_template_task_id', 'project_template_dependencies_successor_unique');
                }
                if (! $indexes->contains('project_template_dependencies_predecessor_index')) {
                    $table->index(
                        ['project_template_id', 'predecessor_template_task_id'],
                        'project_template_dependencies_predecessor_index'
                    );
                }
            });

            return;
        }

        Schema::create('project_template_task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id');
            $table->foreignId('project_template_task_id');
            $table->foreignId('predecessor_template_task_id');
            $table->enum('dependency_type', ['FS', 'SS', 'FF', 'SF']);
            $table->unsignedInteger('lag_days')->default(0);
            $table->timestamps();

            $table->foreign('project_template_id', 'pt_dependencies_template_foreign')
                ->references('id')->on('project_templates')->cascadeOnDelete();
            $table->foreign('project_template_task_id', 'pt_dependencies_task_foreign')
                ->references('id')->on('project_template_tasks')->cascadeOnDelete();
            $table->foreign('predecessor_template_task_id', 'pt_dependencies_predecessor_foreign')
                ->references('id')->on('project_template_tasks')->cascadeOnDelete();
            $table->unique('project_template_task_id', 'project_template_dependencies_successor_unique');
            $table->index(
                ['project_template_id', 'predecessor_template_task_id'],
                'project_template_dependencies_predecessor_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_template_task_dependencies');
    }
};
