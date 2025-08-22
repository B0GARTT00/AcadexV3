<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            if (!Schema::hasColumn('course_outcome_attainments', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->after('student_id');
            }
            if (!Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
                $table->unsignedBigInteger('course_outcome_id')->after('subject_id');
            }
            if (Schema::hasColumn('course_outcome_attainments', 'co_id')) {
                $table->dropColumn('co_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            if (Schema::hasColumn('course_outcome_attainments', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
        });
    }
};
