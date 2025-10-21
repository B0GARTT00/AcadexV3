<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grades_formula_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grades_formula_id')
                ->constrained('grades_formula')
                ->cascadeOnDelete();
            $table->string('activity_type');
            $table->decimal('weight', 6, 4);
            $table->timestamps();
        });

        if (Schema::hasTable('grades_formula')) {
            $formulas = DB::table('grades_formula')->get();

            foreach ($formulas as $formula) {
                $weights = [];

                if (Schema::hasColumn('grades_formula', 'quiz_weight') && $formula->quiz_weight !== null) {
                    $weights[] = ['grades_formula_id' => $formula->id, 'activity_type' => 'quiz', 'weight' => $formula->quiz_weight];
                }
                if (Schema::hasColumn('grades_formula', 'ocr_weight') && $formula->ocr_weight !== null) {
                    $weights[] = ['grades_formula_id' => $formula->id, 'activity_type' => 'ocr', 'weight' => $formula->ocr_weight];
                }
                if (Schema::hasColumn('grades_formula', 'exam_weight') && $formula->exam_weight !== null) {
                    $weights[] = ['grades_formula_id' => $formula->id, 'activity_type' => 'exam', 'weight' => $formula->exam_weight];
                }

                if (! empty($weights)) {
                    $now = now();
                    $weights = array_map(function ($weight) use ($now) {
                        return array_merge($weight, [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }, $weights);

                    DB::table('grades_formula_weights')->insert($weights);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades_formula_weights');
    }
};
