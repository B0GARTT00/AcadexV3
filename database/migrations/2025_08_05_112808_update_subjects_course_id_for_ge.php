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
        // Update subjects with is_universal = true to have course_id = 1 (General Education)
        DB::table('subjects')
            ->where('is_universal', true)
            ->update(['course_id' => 1]);
            
        // Also update subjects that don't have is_universal = true to have course_id != 1
        // Get the first non-GE course
        $nonGeCourse = DB::table('courses')
            ->where('course_description', '!=', 'General Education')
            ->first();
            
        if ($nonGeCourse) {
            DB::table('subjects')
                ->where('is_universal', false)
                ->whereNull('course_id')
                ->update(['course_id' => $nonGeCourse->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data migration, so we can't easily reverse it
        // The subjects will keep their course_id assignments
    }
};
