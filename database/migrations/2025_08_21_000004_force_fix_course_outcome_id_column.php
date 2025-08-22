<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop foreign key constraint on co_id if it exists
        DB::statement('ALTER TABLE course_outcome_attainments DROP FOREIGN KEY course_outcome_attainments_co_id_foreign');
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
