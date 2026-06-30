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
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('token', 32)->unique()->nullable()->after('id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('token', 32)->unique()->nullable()->after('id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('token', 32)->unique()->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('token');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('token');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
