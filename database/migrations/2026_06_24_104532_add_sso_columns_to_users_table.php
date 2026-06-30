<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'sso_id')) {
                $table->string('sso_id')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->nullable()->unique()->after('sso_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sso_id')) {
                $table->dropUnique(['sso_id']);
            }

            if (Schema::hasColumn('users', 'employee_id')) {
                $table->dropUnique(['employee_id']);
            }

            $columns = [];

            if (Schema::hasColumn('users', 'sso_id')) {
                $columns[] = 'sso_id';
            }

            if (Schema::hasColumn('users', 'employee_id')) {
                $columns[] = 'employee_id';
            }

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
