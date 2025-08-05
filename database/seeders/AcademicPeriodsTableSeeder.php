<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicPeriod;

class AcademicPeriodsTableSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            [
                'academic_year' => '2025-2026',
                'semester' => '1st',
            ],
            [
                'academic_year' => '2025-2026',
                'semester' => '2nd',
            ],
            [
                'academic_year' => '2025',
                'semester' => 'Summer',
            ],
        ];

        foreach ($periods as $period) {
            AcademicPeriod::updateOrCreate(
                [
                    'academic_year' => $period['academic_year'],
                    'semester' => $period['semester'],
                ],
                $period
            );
        }
    }
}
