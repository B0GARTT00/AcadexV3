<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('grades_formula')
            ->where('scope_level', 'subject')
            ->whereNotNull('course_id')
            ->update(['course_id' => null]);

        $this->dropIndexIfExists('grades_formula', 'grades_formula_course_unique');
    }

    public function down(): void
    {
        // No down mutation to avoid reintroducing duplicate constraints without context checks.
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            $exists = DB::select('SHOW INDEX FROM `' . $table . '` WHERE `Key_name` = ?', [$index]);
            if (! empty($exists)) {
                DB::statement('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
            }
        } catch (\Throwable $exception) {
            // Ignore drop failures so the migration remains idempotent.
        }
    }
};
