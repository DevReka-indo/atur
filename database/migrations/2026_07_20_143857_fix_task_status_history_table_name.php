<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('task_status_history')
            && ! Schema::hasTable('task_status_histories')
        ) {
            Schema::rename(
                'task_status_history',
                'task_status_histories'
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('task_status_histories')
            && ! Schema::hasTable('task_status_history')
        ) {
            Schema::rename(
                'task_status_histories',
                'task_status_history'
            );
        }
    }
};
