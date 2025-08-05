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
        // Check if academic_periods table exists and has data
        if (Schema::hasTable('academic_periods')) {
            $academicPeriods = DB::table('academic_periods')->get();
            
            if ($academicPeriods->isEmpty()) {
                // Create a default academic period
                DB::table('academic_periods')->insert([
                    'name' => '2024-2025',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Check subjects table
        $subjects = DB::table('subjects')->get();
        echo "Total subjects: " . $subjects->count() . "\n";
        
        $geSubjects = DB::table('subjects')->where('course_id', 1)->get();
        echo "GE subjects (course_id = 1): " . $geSubjects->count() . "\n";
        
        // Show some sample subjects
        $sampleSubjects = DB::table('subjects')->limit(5)->get();
        foreach ($sampleSubjects as $subject) {
            echo "Subject ID: {$subject->id}, Code: {$subject->subject_code}, Course ID: {$subject->course_id}, Academic Period: {$subject->academic_period_id}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
};
