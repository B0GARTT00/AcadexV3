<?php

use App\Models\GradesFormula;
use App\Support\Grades\FormulaStructure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            if (! Schema::hasColumn('grades_formula', 'structure_type')) {
                $table->string('structure_type', 64)->default('lecture_only')->after('scope_level');
            }

            if (! Schema::hasColumn('grades_formula', 'structure_config')) {
                $table->json('structure_config')->nullable()->after('structure_type');
            }
        });

        GradesFormula::query()->chunkById(50, function ($formulas) {
            /** @var \Illuminate\Support\Collection<int, GradesFormula> $formulas */
            foreach ($formulas as $formula) {
                $structure = $formula->structure_config ?? null;

                if (! is_array($structure) || empty($structure)) {
                    $structure = FormulaStructure::default('lecture_only');
                } else {
                    $structure = FormulaStructure::normalize($structure);
                }

                $formula->structure_type = $formula->structure_type ?: 'lecture_only';
                $formula->structure_config = $structure;
                $formula->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('grades_formula', function (Blueprint $table) {
            if (Schema::hasColumn('grades_formula', 'structure_config')) {
                $table->dropColumn('structure_config');
            }
            if (Schema::hasColumn('grades_formula', 'structure_type')) {
                $table->dropColumn('structure_type');
            }
        });
    }
};
