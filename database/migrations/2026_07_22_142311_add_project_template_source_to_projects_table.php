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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_template_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained('project_templates')
                ->nullOnDelete();
            $table->string('source_template_name')->nullable()->after('project_template_id');
            $table->unsignedInteger('source_template_version')->nullable()->after('source_template_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_template_id']);
            $table->dropColumn([
                'project_template_id',
                'source_template_name',
                'source_template_version',
            ]);
        });
    }
};
