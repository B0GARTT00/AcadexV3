<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'department_code' => 'GE',
                'department_description' => 'General Education',
            ],
            [
                'department_code' => 'ASE',
                'department_description' => 'Arts, Science, and Education',
            ],
            [
                'department_code' => 'SBISM',
                'department_description' => 'School of Business, Information Science and Management',
            ],
            [
                'department_code' => 'NURSING',
                'department_description' => 'School of Nursing',
            ],
            [
                'department_code' => 'MEDICINE',
                'department_description' => 'School of Medicine',
            ],
            [
                'department_code' => 'ALLIED',
                'department_description' => 'Allied Health',
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['department_code' => $department['department_code']],
                $department
            );
        }
    }
}
