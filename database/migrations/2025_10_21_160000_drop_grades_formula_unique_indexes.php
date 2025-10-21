<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $indexes = [
            'grades_formula_subject_period_unique',
            'grades_formula_course_period_unique',
            'grades_formula_subject_unique',
            'grades_formula_course_unique',
            'grades_formula_subject_semester_unique',
            'grades_formula_course_semester_unique',
            'grades_formula_course_scope_unique',
        ];

        foreach ($indexes as $index) {
            $this->dropIndexIfExists('grades_formula', $index);
        }
    }

    public function down(): void
    {
        // Intentionally left blank; unique constraints remain removed unless reintroduced manually.
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            $exists = DB::select('SHOW INDEX FROM `' . $table . '` WHERE `Key_name` = ?', [$index]);
            if (! empty($exists)) {
                DB::statement('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
            }
        } catch (\Throwable $exception) {
            // Ignore drop failures to keep migration idempotent across environments.
        }
    }
};
