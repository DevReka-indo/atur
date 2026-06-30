<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_weights', function (Blueprint $table) {
            $table->id();
            $table->string('status', 50)->unique();
            $table->decimal('weight_value', 3, 2); // 0.00 - 1.00
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Index
            $table->index('status');
        });

        // Insert default values
        DB::table('task_status_weights')->insert([
            ['status' => 'to_do', 'weight_value' => 0.00, 'description' => 'Belum dikerjakan'],
            ['status' => 'in_progress', 'weight_value' => 0.50, 'description' => 'Sedang dikerjakan (50% selesai)'],
            ['status' => 'review', 'weight_value' => 0.75, 'description' => 'Hampir selesai, sedang review'],
            ['status' => 'completed', 'weight_value' => 1.00, 'description' => 'Selesai 100%'],
            ['status' => 'blocked', 'weight_value' => 0.50, 'description' => 'Tertahan, gunakan nilai saat blocked'],
            ['status' => 'cancelled', 'weight_value' => 0.00, 'description' => 'Dibatalkan, tidak dihitung'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_weights');
    }
};
