<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This migration is handled by 2025_08_21_000002_add_subject_id_to_course_outcome_attainments_table
        // Do nothing to avoid conflicts
    }

    public function down(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            if (!Schema::hasColumn('course_outcome_attainments', 'co_id')) {
                $table->unsignedBigInteger('co_id')->after('term');
            }
            if (Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
                $table->dropColumn('course_outcome_id');
            }
        });
    }
};
