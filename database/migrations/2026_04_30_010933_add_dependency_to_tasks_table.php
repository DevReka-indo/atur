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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('predecessor_id')->nullable()->after('parent_task_id');
            $table->enum('dependency_type', ['FS', 'SS', 'FF', 'SF'])
                ->default('FS')
                ->after('predecessor_id');
            $table->foreign('predecessor_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('set null');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['predecessor_id']);
            $table->dropColumn(['predecessor_id', 'dependency_type']);
        });
    }
};
