<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thread_user_reads', function (Blueprint $table) {
            $table->foreignId('last_read_message_id')
                ->nullable()
                ->after('last_read_at')
                ->constrained('project_thread_messages')
                ->nullOnDelete();
        });

        Schema::table('project_thread_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('content');
            $table->index(
                ['project_thread_id', 'id'],
                'project_thread_messages_thread_id_id_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('project_thread_messages', function (Blueprint $table) {
            $table->dropIndex('project_thread_messages_thread_id_id_index');
            $table->dropColumn('edited_at');
        });

        Schema::table('thread_user_reads', function (Blueprint $table) {
            $table->dropForeign(['last_read_message_id']);
            $table->dropColumn('last_read_message_id');
        });
    }
};
