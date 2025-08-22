<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('course_outcome_attainments', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->after('student_id');
            }
            if (!Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
                $table->unsignedBigInteger('course_outcome_id')->after('subject_id');
            }
        });

        // Drop foreign key constraint and column in a separate operation
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            if (Schema::hasColumn('course_outcome_attainments', 'co_id')) {
                // Drop foreign key constraint first
                $table->dropForeign(['co_id']);
                // Then drop the column
                $table->dropColumn('co_id');
            }
        });

        // Add new foreign key constraints
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            if (Schema::hasColumn('course_outcome_attainments', 'subject_id')) {
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            }
            if (Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
                $table->foreign('course_outcome_id')->references('id')->on('course_outcomes')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            // Drop foreign key constraints first
            if (Schema::hasColumn('course_outcome_attainments', 'subject_id')) {
                $table->dropForeign(['subject_id']);
            }
            if (Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
                $table->dropForeign(['course_outcome_id']);
            }
        });

        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            // Drop the columns
            if (Schema::hasColumn('course_outcome_attainments', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('course_outcome_attainments', 'course_outcome_id')) {
                $table->dropColumn('course_outcome_id');
            }
            
            // Re-add the original co_id column and constraint
            if (!Schema::hasColumn('course_outcome_attainments', 'co_id')) {
                $table->unsignedBigInteger('co_id')->after('term');
                $table->foreign('co_id')->references('id')->on('course_outcomes')->onDelete('cascade');
            }
        });
    }
};
