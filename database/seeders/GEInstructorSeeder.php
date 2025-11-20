<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnverifiedUser;
use App\Models\Department;
use App\Models\Course;
use Illuminate\Support\Facades\Hash;

class GEInstructorSeeder extends Seeder
{
    public function run(): void
    {
        $geDepartment = Department::where('department_code', 'GE')->first();
        $course = Course::first(); // Get any course
        
        if (!$geDepartment) {
            echo "GE Department not found. Please run DepartmentsTableSeeder first.\n";
            return;
        }
        
        if (!$course) {
            echo "No courses found. Please run CoursesTableSeeder first.\n";
            return;
        }

        UnverifiedUser::updateOrCreate(
            ['email' => 'geinstructor@brokenshire.edu.ph'],
            [
                'first_name' => 'GE',
                'last_name' => 'Instructor',
                'middle_name' => null,
                'password' => Hash::make('password'),
                'department_id' => $geDepartment->id,
                'course_id' => $course->id,
                'email_verified_at' => now(), // Mark as verified so it appears in pending approvals
            ]
        );

        echo "GE Instructor pending account created/updated successfully.\n";
        echo "Email: geinstructor@brokenshire.edu.ph\n";
        echo "Password: password\n";
        echo "Status: Pending approval\n";
    }
}
