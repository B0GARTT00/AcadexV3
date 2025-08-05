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
        echo "=== ENSURING GE SUBJECTS ACADEMIC PERIOD ===\n";
        
        // Update GE subjects to have academic_period_id = 1
        $updatedCount = DB::table('subjects')
            ->where('course_id', 1)
            ->update(['academic_period_id' => 1]);
        echo "Updated {$updatedCount} GE subjects to academic_period_id = 1\n";
        
        // Verify the fix
        $geSubjects = DB::table('subjects')
            ->where('course_id', 1)
            ->where('academic_period_id', 1)
            ->get();
        echo "GE subjects with academic_period_id = 1: " . $geSubjects->count() . "\n";
        
        // Show the GE subjects
        echo "\n=== GE Subjects ===\n";
        foreach ($geSubjects as $subject) {
            echo "ID: {$subject->id}, Code: {$subject->subject_code}, Course: {$subject->course_id}, Period: {$subject->academic_period_id}, Universal: " . ($subject->is_universal ? 'true' : 'false') . "\n";
        }
        
        // Test the exact GE Coordinator query
        $geCoordinatorQuery = DB::table('subjects')
            ->where('course_id', 1)
            ->where('is_deleted', false)
            ->where('academic_period_id', 1)
            ->get();
        echo "\nGE Coordinator query result: " . $geCoordinatorQuery->count() . " subjects\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data fix, so we can't easily reverse it
    }
};
