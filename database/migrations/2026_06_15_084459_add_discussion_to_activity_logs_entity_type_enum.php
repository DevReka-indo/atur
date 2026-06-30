<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activity_logs MODIFY entity_type ENUM('task', 'project', 'workspace', 'comment', 'attachment', 'discussion') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activity_logs MODIFY entity_type ENUM('task', 'project', 'workspace', 'comment', 'attachment') NOT NULL");
    }
};
