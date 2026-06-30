<?php
// database/migrations/xxxx_xx_xx_add_invited_by_to_workspace_members_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->foreignId('invited_by')->nullable()->after('role')->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'invited', 'suspended'])->default('active')->after('invited_by');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['invited_by', 'status']);
        });
    }
};
