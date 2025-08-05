<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Term;

class TermsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = ['Prelim', 'Midterm', 'Prefinal', 'Final'];

        foreach ($terms as $term) {
            Term::updateOrCreate(
                ['name' => $term],
                [
                    'term_name' => $term, // required if your table expects it
                    'is_deleted' => false,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}
