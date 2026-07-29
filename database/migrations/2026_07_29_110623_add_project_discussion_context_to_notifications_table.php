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
            $table->unsignedBigInteger('project_thread_id')
                ->nullable()
                ->after('workspace_chat_message_id');
            $table->unsignedBigInteger('project_thread_message_id')
                ->nullable()
                ->after('project_thread_id');

            $table->foreign('project_thread_id', 'pt_notif_thread_fk')
                ->references('id')
                ->on('project_threads')
                ->nullOnDelete();
            $table->foreign('project_thread_message_id', 'pt_notif_message_fk')
                ->references('id')
                ->on('project_thread_messages')
                ->nullOnDelete();
            $table->unique(
                ['user_id', 'type', 'project_thread_message_id'],
                'pt_notif_message_user_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('pt_notif_message_user_unique');
            $table->dropForeign('pt_notif_message_fk');
            $table->dropForeign('pt_notif_thread_fk');
            $table->dropColumn([
                'project_thread_id',
                'project_thread_message_id',
            ]);
        });
    }
};
