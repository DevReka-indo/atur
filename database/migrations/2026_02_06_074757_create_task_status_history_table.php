<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);

            $table->foreignId('changed_by')
                ->constrained('users');

            $table->timestamp('changed_at')->useCurrent();

            $table->index(['task_id', 'changed_at']);
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_histories');
    }
};
