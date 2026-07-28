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
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('project_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('workspace_chat_message_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained('workspace_chat_messages')
                ->nullOnDelete();
            $table->string('url', 1000)->nullable()->after('workspace_chat_message_id');
            $table->json('metadata')->nullable()->after('url');

            $table->unique(
                ['user_id', 'type', 'workspace_chat_message_id'],
                'notifications_workspace_chat_mention_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('notifications_workspace_chat_mention_unique');
            $table->dropForeign(['workspace_chat_message_id']);
            $table->dropForeign(['workspace_id']);
            $table->dropColumn([
                'workspace_id',
                'workspace_chat_message_id',
                'url',
                'metadata',
            ]);
        });
    }
};
