<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch Departments and Courses (updated department codes: ASE and SBISM)
        $ase = Department::where('department_code', 'ASE')->first();
        $sbism = Department::where('department_code', 'SBISM')->first();
        $bsit = Course::where('course_code', 'BSIT')->first();
        $bsba = Course::where('course_code', 'BSBA')->first();
        $bspsy = Course::where('course_code', 'BSPSY')->first();

        if (! $ase || ! $sbism || ! $bsit || ! $bsba || ! $bspsy) {
            throw new \Exception('Required department or courses not found. Seed departments and courses first.');
        }

        $users = [
            // Admin
            [
                'first_name' => 'Admin',
                'middle_name' => null,
                'last_name' => 'User',
                'email' => 'admin@brokenshire.edu.ph',
                'role' => 3,
                'department_id' => $ase->id,
                'course_id' => null,
            ],
            // GE Coordinator
            [
                'first_name' => 'GE',
                'middle_name' => null,
                'last_name' => 'Coordinator',
                'email' => 'gecoordinator@brokenshire.edu.ph',
                'role' => 4,
                'department_id' => $ase->id,
                'course_id' => null,
            ],
            // VPAA
            [
                'first_name' => 'VPAA',
                'middle_name' => null,
                'last_name' => 'User',
                'email' => 'vpaa@brokenshire.edu.ph',
                'role' => 5,
                'department_id' => $ase->id,
                'course_id' => null,
            ],
            // BSIT Users (now under SBISM)
            [
                'first_name' => 'Chairperson',
                'middle_name' => null,
                'last_name' => 'BSIT',
                'email' => 'chairperson.bsit@brokenshire.edu.ph',
                'role' => 1,
                'department_id' => $sbism->id,
                'course_id' => $bsit->id,
            ],
            [
                'first_name' => 'Instructor',
                'middle_name' => null,
                'last_name' => 'BSIT',
                'email' => 'instructor.bsit@brokenshire.edu.ph',
                'role' => 0,
                'department_id' => $sbism->id,
                'course_id' => $bsit->id,
            ],
            // BSBA Users (now under SBISM)
            [
                'first_name' => 'Chairperson',
                'middle_name' => null,
                'last_name' => 'BSBA',
                'email' => 'chairperson.bsba@brokenshire.edu.ph',
                'role' => 1,
                'department_id' => $sbism->id,
                'course_id' => $bsba->id,
            ],
            [
                'first_name' => 'Instructor',
                'middle_name' => null,
                'last_name' => 'BSBA',
                'email' => 'instructor.bsba@brokenshire.edu.ph',
                'role' => 0,
                'department_id' => $sbism->id,
                'course_id' => $bsba->id,
            ],
            // BSPSY Users (now under ASE)
            [
                'first_name' => 'Chairperson',
                'middle_name' => null,
                'last_name' => 'BSPSY',
                'email' => 'chairperson.bspsy@brokenshire.edu.ph',
                'role' => 1,
                'department_id' => $ase->id,
                'course_id' => $bspsy->id,
            ],
            [
                'first_name' => 'Dean',
                'middle_name' => null,
                'last_name' => 'User',
                'email' => 'dean@brokenshire.edu.ph',
                'role' => 2,
                'department_id' => $ase->id,
            ],
            [
                'first_name' => 'Instructor',
                'middle_name' => null,
                'last_name' => 'BSPSY',
                'email' => 'instructor.bspsy@brokenshire.edu.ph',
                'role' => 0,
                'department_id' => $ase->id,
                'course_id' => $bspsy->id,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ])
            );
        }
    }
}
