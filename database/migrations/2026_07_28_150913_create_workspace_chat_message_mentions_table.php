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
        Schema::create('workspace_chat_message_mentions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('workspace_chat_message_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamps();

            $table->foreign(
                'workspace_chat_message_id',
                'wc_mentions_message_fk'
            )
                ->references('id')
                ->on('workspace_chat_messages')
                ->cascadeOnDelete();

            $table->foreign(
                'user_id',
                'wc_mentions_user_fk'
            )
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(
                ['workspace_chat_message_id', 'user_id'],
                'wc_mentions_message_user_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_chat_message_mentions');
    }
};
