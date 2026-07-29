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
        Schema::create('project_thread_message_mentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_thread_message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('project_thread_message_id', 'pt_mentions_message_fk')
                ->references('id')
                ->on('project_thread_messages')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'pt_mentions_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(
                ['project_thread_message_id', 'user_id'],
                'pt_mentions_message_user_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_thread_message_mentions');
    }
};
