<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'subtask_weight_percentage')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->decimal('subtask_weight_percentage', 5, 2)
                    ->nullable()
                    ->after('weight');
            });
        }

        DB::table('tasks')
            ->select('parent_task_id')
            ->whereNotNull('parent_task_id')
            ->groupBy('parent_task_id')
            ->havingRaw('COUNT(*) = 1')
            ->pluck('parent_task_id')
            ->chunk(500)
            ->each(function ($parentTaskIds): void {
                DB::table('tasks')
                    ->whereIn('parent_task_id', $parentTaskIds)
                    ->whereNull('subtask_weight_percentage')
                    ->update(['subtask_weight_percentage' => 100.00]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'subtask_weight_percentage')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->dropColumn('subtask_weight_percentage');
            });
        }
    }
};
