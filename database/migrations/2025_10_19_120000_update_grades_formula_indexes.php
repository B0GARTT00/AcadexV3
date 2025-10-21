<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexNames = [
            'grades_formula_course_scope_unique',
            'grades_formula_course_unique',
            'grades_formula_department_unique',
            'grades_formula_subject_unique',
        ];

        foreach ($indexNames as $indexName) {
            try {
                $exists = DB::select(
                    'SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?',
                    [$indexName]
                );

                if (! empty($exists)) {
                    DB::statement("ALTER TABLE `grades_formula` DROP INDEX `{$indexName}`");
                }
            } catch (\Throwable $exception) {
                // Ignore failures so migration remains idempotent across environments.
            }
        }

        // Remove duplicate subject formulas, keeping the most recently updated entry.
        $duplicateSubjectIds = DB::table('grades_formula')
            ->select('subject_id')
            ->whereNotNull('subject_id')
            ->groupBy('subject_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('subject_id');

        foreach ($duplicateSubjectIds as $subjectId) {
            $subjectFormulaIds = DB::table('grades_formula')
                ->where('subject_id', $subjectId)
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->pluck('id')
                ->all();

            // Keep the most recent formula; remove older duplicates.
            array_shift($subjectFormulaIds);

            if (! empty($subjectFormulaIds)) {
                DB::table('grades_formula')
                    ->whereIn('id', $subjectFormulaIds)
                    ->delete();
            }
        }

        try {
            $subjectIndexExists = DB::select(
                'SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?',
                ['grades_formula_subject_unique']
            );

            if (empty($subjectIndexExists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_subject_unique` (`subject_id`)');
            }
        } catch (\Throwable $exception) {
            // Index already exists or duplicates remain; ignore to keep migration resilient.
        }
    }

    public function down(): void
    {
        try {
            $subjectIndexExists = DB::select(
                'SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?',
                ['grades_formula_subject_unique']
            );

            if (! empty($subjectIndexExists)) {
                DB::statement('ALTER TABLE `grades_formula` DROP INDEX `grades_formula_subject_unique`');
            }
        } catch (\Throwable $exception) {
            // Ignore if the index is already missing.
        }

        try {
            $courseIndexExists = DB::select(
                'SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?',
                ['grades_formula_course_unique']
            );

            if (empty($courseIndexExists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_course_unique` (`course_id`)');
            }
        } catch (\Throwable $exception) {
            // Ignore if duplicates prevent re-applying the index during rollback.
        }

        try {
            $subjectIndexExists = DB::select(
                'SHOW INDEX FROM `grades_formula` WHERE `Key_name` = ?',
                ['grades_formula_subject_unique']
            );

            if (empty($subjectIndexExists)) {
                DB::statement('ALTER TABLE `grades_formula` ADD UNIQUE `grades_formula_subject_unique` (`subject_id`)');
            }
        } catch (\Throwable $exception) {
            // Ignore if the index already exists.
        }
    }
};
