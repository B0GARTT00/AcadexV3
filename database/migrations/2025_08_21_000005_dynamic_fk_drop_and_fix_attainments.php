<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        // Try to drop any foreign key on co_id
        $sm = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'course_outcome_attainments' AND COLUMN_NAME = 'co_id' AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        foreach ($sm as $row) {
            $fk = $row->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE course_outcome_attainments DROP FOREIGN KEY `$fk`");
        }
        // Drop co_id column if it exists
        if (Schema::hasColumn('course_outcome_attainments', 'co_id')) {
            Schema::table('course_outcome_attainments', function (Blueprint $table) {
                $table->dropColumn('co_id');
            });
        }
        // Add course_outcome_id column if it does not exist
        if (!Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
            Schema::table('course_outcome_attainments', function (Blueprint $table) {
                $table->unsignedBigInteger('course_outcome_id')->after('subject_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        // Add co_id column back if needed
        if (!Schema::hasColumn('course_outcome_attainments', 'co_id')) {
            Schema::table('course_outcome_attainments', function (Blueprint $table) {
                $table->unsignedBigInteger('co_id')->after('term');
            });
        }
        // Drop course_outcome_id column if it exists
        if (Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
            Schema::table('course_outcome_attainments', function (Blueprint $table) {
                $table->dropColumn('course_outcome_id');
            });
        }
    }
};
