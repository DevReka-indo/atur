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
        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('project_template_tasks')->cascadeOnDelete();
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->decimal('weight', 10, 2)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('start_offset_days')->default(0);
            $table->unsignedInteger('duration_days')->default(1);
            $table->timestamps();

            $table->index(
                ['project_template_id', 'parent_id', 'position'],
                'project_template_tasks_parent_position_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_template_tasks');
    }
};
