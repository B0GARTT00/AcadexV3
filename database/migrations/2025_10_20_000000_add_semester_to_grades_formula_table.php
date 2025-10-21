<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSemesterToGradesFormulaTable extends Migration
{
    public function up(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            if (! Schema::hasColumn('grades_formula', 'semester')) {
                $table->string('semester', 16)->nullable()->after('subject_id');
            }
        });

        // Relax previous unique constraints and add semester-aware ones.
        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_course_unique']);
            if (! empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` DROP INDEX `grades_formula_course_unique`');
            }
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_subject_unique']);
            if (! empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` DROP INDEX `grades_formula_subject_unique`');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_course_semester_unique']);
            if (empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_course_semester_unique` (`course_id`, `semester`)');
            }
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_subject_semester_unique']);
            if (empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_subject_semester_unique` (`subject_id`, `semester`)');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Helpful index for department fallback resolution per semester.
        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_dept_semester_fallback']);
            if (empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD INDEX `grades_formula_dept_semester_fallback` (`department_id`, `scope_level`, `is_department_fallback`, `semester`)');
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // Best-effort rollback: keep data, just drop semester uniques and column
        try {
            DB::statement('ALTER TABLE `grades_formula` DROP INDEX `grades_formula_course_semester_unique`');
        } catch (\Throwable $e) {}
        try {
            DB::statement('ALTER TABLE `grades_formula` DROP INDEX `grades_formula_subject_semester_unique`');
        } catch (\Throwable $e) {}
        try {
            DB::statement('ALTER TABLE `grades_formula` DROP INDEX `grades_formula_dept_semester_fallback`');
        } catch (\Throwable $e) {}

        Schema::table('grades_formula', function (Blueprint $table) {
            if (Schema::hasColumn('grades_formula', 'semester')) {
                $table->dropColumn('semester');
            }
        });

        // Optionally try to recreate previous unique indexes (safe if duplicates not present)
        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_course_unique']);
            if (empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_course_unique` (`course_id`)');
            }
        } catch (\Throwable $e) {}
        try {
            $exists = DB::select('SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?', ['grades_formula_subject_unique']);
            if (empty($exists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_subject_unique` (`subject_id`)');
            }
        } catch (\Throwable $e) {}
    }
}
