<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            if (! Schema::hasColumn('grades_formula', 'academic_period_id')) {
                $table->foreignId('academic_period_id')
                    ->nullable()
                    ->after('semester')
                    ->constrained('academic_periods')
                    ->nullOnDelete();
            }
        });

        // Refresh unique indexes to include academic_period_id for scoping.
        $this->dropIndexIfExists('grades_formula', 'grades_formula_course_semester_unique');
        $this->dropIndexIfExists('grades_formula', 'grades_formula_subject_semester_unique');
        $this->dropIndexIfExists('grades_formula', 'grades_formula_dept_semester_fallback');
        $this->dropIndexIfExists('grades_formula', 'grades_formula_course_unique');
        $this->dropIndexIfExists('grades_formula', 'grades_formula_subject_unique');

        $this->createIndexSafely('grades_formula', 'grades_formula_course_period_unique', 'UNIQUE (`course_id`, `semester`, `academic_period_id`)');
        $this->createIndexSafely('grades_formula', 'grades_formula_subject_period_unique', 'UNIQUE (`subject_id`, `semester`, `academic_period_id`)');
        $this->createIndexSafely('grades_formula', 'grades_formula_dept_period_fallback', 'INDEX (`department_id`, `scope_level`, `is_department_fallback`, `semester`, `academic_period_id`)');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('grades_formula', 'grades_formula_course_period_unique');
        $this->dropIndexIfExists('grades_formula', 'grades_formula_subject_period_unique');
        $this->dropIndexIfExists('grades_formula', 'grades_formula_dept_period_fallback');

        Schema::table('grades_formula', function (Blueprint $table) {
            if (Schema::hasColumn('grades_formula', 'academic_period_id')) {
                $table->dropForeign(['academic_period_id']);
                $table->dropColumn('academic_period_id');
            }
        });

        $this->createIndexSafely('grades_formula', 'grades_formula_course_semester_unique', 'UNIQUE (`course_id`, `semester`)');
        $this->createIndexSafely('grades_formula', 'grades_formula_subject_semester_unique', 'UNIQUE (`subject_id`, `semester`)');
        $this->createIndexSafely('grades_formula', 'grades_formula_dept_semester_fallback', 'INDEX (`department_id`, `scope_level`, `is_department_fallback`, `semester`)');
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            $exists = DB::select('SHOW INDEX FROM `' . $table . '` WHERE `Key_name` = ?', [$index]);
            if (! empty($exists)) {
                DB::statement('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
            }
        } catch (\Throwable $e) {
            // Ignore drop failures to keep migration idempotent across environments.
        }
    }

    private function createIndexSafely(string $table, string $index, string $sqlFragment): void
    {
        try {
            $exists = DB::select('SHOW INDEX FROM `' . $table . '` WHERE `Key_name` = ?', [$index]);
            if (empty($exists)) {
                DB::statement('ALTER TABLE `' . $table . '` ADD ' . $sqlFragment . '');
            }
        } catch (\Throwable $e) {
            // Ignore add failures; administrators can adjust manually if needed.
        }
    }
};
