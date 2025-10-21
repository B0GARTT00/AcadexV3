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
            if (! Schema::hasColumn('grades_formula', 'is_department_fallback')) {
                $table->boolean('is_department_fallback')->default(false)->after('scope_level');
            }
            
            try {
                $table->dropForeign(['department_id']);
            } catch (\Throwable $exception) {
                // Foreign key may not exist; ignore.
            }

            try {
                $table->dropUnique('grades_formula_department_unique');
            } catch (\Throwable $exception) {
                // Index may not exist when running fresh migrations; safely ignore.
            }

            try {
                // Recreate the foreign key constraint if possible. If the
                // constraint already exists this will throw and be ignored.
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            } catch (\Throwable $exception) {
                // If we can't recreate the foreign key it's non-fatal here.
            }
        });

        DB::table('grades_formula')
            ->where('scope_level', 'department')
            ->update(['is_department_fallback' => true]);
    }

    public function down(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            if (Schema::hasColumn('grades_formula', 'is_department_fallback')) {
                $table->dropColumn('is_department_fallback');
            }

            try {
                $table->unique('department_id', 'grades_formula_department_unique');
            } catch (\Throwable $exception) {
                // Ignore if duplicates prevent re-applying the index during rollback.
            }
        });
    }
};
