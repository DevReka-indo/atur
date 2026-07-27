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
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->after('invited_by');
            $table->string('pending_key', 64)->nullable()->unique()->after('token');
            $table->timestamp('accepted_at')->nullable()->after('expires_at');
            $table->timestamp('revoked_at')->nullable()->after('accepted_at');
            $table->timestamp('last_sent_at')->nullable()->after('revoked_at');
            $table->index(['type', 'invitable_id', 'status']);
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->timestamp('invite_token_expires_at')->nullable()->after('invite_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('invite_token_expires_at');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropIndex(['type', 'invitable_id', 'status']);
            $table->dropUnique(['pending_key']);
            $table->dropColumn([
                'role',
                'pending_key',
                'accepted_at',
                'revoked_at',
                'last_sent_at',
            ]);
        });
    }
};
