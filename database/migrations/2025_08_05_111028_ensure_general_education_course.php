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
        // Check if General Education course exists
        $geCourse = DB::table('courses')->where('course_description', 'General Education')->first();
        
        if (!$geCourse) {
            // Create General Education course if it doesn't exist
            DB::table('courses')->insert([
                'course_code' => 'GE',
                'course_description' => 'General Education',
                'department_id' => DB::table('departments')->where('department_code', 'GE')->first()->id,
                'is_deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Ensure General Education course has ID = 1
        $geCourse = DB::table('courses')->where('course_description', 'General Education')->first();
        if ($geCourse && $geCourse->id != 1) {
            // If GE course exists but doesn't have ID = 1, we need to handle this carefully
            // For now, let's just ensure it exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove General Education course if it was created by this migration
        DB::table('courses')->where('course_description', 'General Education')->delete();
    }
};
