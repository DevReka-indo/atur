<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planned_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baseline_id')->constrained('project_baselines')->onDelete('cascade');
            $table->date('date');
            $table->decimal('planned_cumulative_percentage', 5, 2); // 0.00 - 100.00
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique(['baseline_id', 'date']);
            $table->index(['baseline_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planned_progress');
    }
};
