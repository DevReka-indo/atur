<?php
// database/migrations/xxxx_xx_xx_add_timestamps_to_project_members_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah kolom timestamps belum ada
        if (!Schema::hasColumn('project_members', 'created_at')) {
            Schema::table('project_members', function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
