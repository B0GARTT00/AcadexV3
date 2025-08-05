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
        echo "=== FIXING GE SUBJECTS COURSE_ID ===\n";
        
        // First, let's see what subjects should be GE based on their codes
        $geSubjectCodes = ['GE 1', 'GE 2', 'GE 3', 'GE 4', 'GE 5', 'GE 6', 'GE 7', 'GE 8', 'GE 9', 'GE 10'];
        
        // Update subjects with GE codes to have course_id = 1
        $updatedCount = DB::table('subjects')
            ->whereIn('subject_code', $geSubjectCodes)
            ->update(['course_id' => 1, 'is_universal' => true]);
        echo "Updated {$updatedCount} subjects with GE codes to course_id = 1\n";
        
        // Update all other subjects to have course_id != 1
        $otherSubjectsCount = DB::table('subjects')
            ->whereNotIn('subject_code', $geSubjectCodes)
            ->update(['course_id' => 2, 'is_universal' => false]); // Assuming course_id 2 is a regular course
        echo "Updated {$otherSubjectsCount} other subjects to course_id = 2\n";
        
        // Verify the fix
        $geSubjects = DB::table('subjects')->where('course_id', 1)->get();
        echo "Subjects with course_id = 1: " . $geSubjects->count() . "\n";
        
        $otherSubjects = DB::table('subjects')->where('course_id', 2)->get();
        echo "Subjects with course_id = 2: " . $otherSubjects->count() . "\n";
        
        // Show the GE subjects
        echo "\n=== GE Subjects ===\n";
        foreach ($geSubjects as $subject) {
            echo "ID: {$subject->id}, Code: {$subject->subject_code}, Course: {$subject->course_id}, Universal: " . ($subject->is_universal ? 'true' : 'false') . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data fix, so we can't easily reverse it
    }
};
