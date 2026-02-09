<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE workspace_members MODIFY role ENUM('owner','admin','member') DEFAULT 'member'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE workspace_members MODIFY role ENUM('admin','member') DEFAULT 'member'");
    }
};
