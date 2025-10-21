<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            if (! Schema::hasColumn('grades_formula', 'scope_level')) {
                $table->string('scope_level', 32)->default('global')->after('name');
            }

            if (! Schema::hasColumn('grades_formula', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('grades_formula', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            }

            // Ensure unique constraints per scope (ignores null values)
            $table->unique('department_id', 'grades_formula_department_unique');
            $table->unique('course_id', 'grades_formula_course_unique');
            $table->unique('subject_id', 'grades_formula_subject_unique');
        });

        // Backfill scope levels based on available foreign keys
        if (Schema::hasTable('grades_formula')) {
            DB::table('grades_formula')->update([
                'scope_level' => DB::raw(
                    "CASE " .
                    "WHEN subject_id IS NOT NULL THEN 'subject' " .
                    "WHEN course_id IS NOT NULL THEN 'course' " .
                    "WHEN department_id IS NOT NULL THEN 'department' " .
                    "ELSE 'global' END"
                ),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            $table->dropUnique('grades_formula_subject_unique');
            $table->dropUnique('grades_formula_course_unique');
            $table->dropUnique('grades_formula_department_unique');

            if (Schema::hasColumn('grades_formula', 'subject_id')) {
                $table->dropConstrainedForeignId('subject_id');
            }
            if (Schema::hasColumn('grades_formula', 'course_id')) {
                $table->dropConstrainedForeignId('course_id');
            }
            if (Schema::hasColumn('grades_formula', 'scope_level')) {
                $table->dropColumn('scope_level');
            }
        });
    }
};
