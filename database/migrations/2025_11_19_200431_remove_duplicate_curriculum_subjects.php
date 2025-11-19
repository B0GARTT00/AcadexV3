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
        // Get all curriculum subjects grouped by their unique identifiers
        $duplicates = DB::table('curriculum_subjects')
            ->select('curriculum_id', 'subject_code', 'year_level', 'semester')
            ->groupBy('curriculum_id', 'subject_code', 'year_level', 'semester')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        // For each group of duplicates, keep only the first one (lowest ID)
        foreach ($duplicates as $duplicate) {
            // Get all IDs for this duplicate group
            $ids = DB::table('curriculum_subjects')
                ->where('curriculum_id', $duplicate->curriculum_id)
                ->where('subject_code', $duplicate->subject_code)
                ->where('year_level', $duplicate->year_level)
                ->where('semester', $duplicate->semester)
                ->orderBy('id')
                ->pluck('id')
                ->toArray();

            // Remove the first ID (keep it) and delete the rest
            array_shift($ids);
            
            if (!empty($ids)) {
                DB::table('curriculum_subjects')->whereIn('id', $ids)->delete();
            }
        }

        // Add unique constraint to prevent future duplicates
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            $table->unique(['curriculum_id', 'subject_code', 'year_level', 'semester'], 'curriculum_subjects_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table) {
            $table->dropUnique('curriculum_subjects_unique');
        });
    }
};
