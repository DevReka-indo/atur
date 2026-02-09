<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actual_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('baseline_id')->constrained('project_baselines')->onDelete('cascade');
            $table->date('date');
            $table->decimal('actual_cumulative_percentage', 5, 2); // 0.00 - 100.00
            $table->integer('completed_tasks_count')->default(0);
            $table->integer('total_tasks_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique(['project_id', 'baseline_id', 'date']);
            $table->index(['project_id', 'baseline_id', 'date']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actual_progress');
    }
};
