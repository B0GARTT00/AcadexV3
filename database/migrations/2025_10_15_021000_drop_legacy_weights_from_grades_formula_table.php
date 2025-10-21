<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['quiz_weight', 'ocr_weight', 'exam_weight'] as $column) {
            if (Schema::hasColumn('grades_formula', $column)) {
                Schema::table('grades_formula', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'quiz_weight' => [5, 4],
            'ocr_weight' => [5, 4],
            'exam_weight' => [5, 4],
        ] as $column => $precision) {
            if (! Schema::hasColumn('grades_formula', $column)) {
                Schema::table('grades_formula', function (Blueprint $table) use ($column, $precision) {
                    $table->decimal($column, $precision[0], $precision[1])->nullable();
                });
            }
        }
    }
};
