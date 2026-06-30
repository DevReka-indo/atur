<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thread_user_reads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('thread_id')->constrained('project_threads')->cascadeOnDelete(); // ← ubah ini
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('last_read_at')->nullable();
    $table->unique(['thread_id', 'user_id']);
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('thread_user_reads');
    }
};
