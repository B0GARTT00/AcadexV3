<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;

trait ActivityManagementTrait
{
    protected function getOrCreateDefaultActivities(int $subjectId, string $term): Collection
    {
        $activities = Activity::where('subject_id', $subjectId)
            ->where('term', $term)
            ->where('is_deleted', false)
            ->with('courseOutcome') // Eager load course outcomes including soft deleted ones
            ->orderBy('type')
            ->orderBy('created_at')
            ->get();
            
        if ($activities->isEmpty()) {
            $defaultActivities = [];
            foreach (['quiz' => 3, 'ocr' => 3, 'exam' => 1] as $type => $count) {
                for ($i = 1; $i <= $count; $i++) {
                    $defaultActivities[] = [
                        'subject_id' => $subjectId,
                        'term' => $term,
                        'type' => $type,
                        'title' => ucfirst($type) . ' ' . $i,
                        'number_of_items' => 100,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            Activity::insert($defaultActivities);
            
            $activities = Activity::where('subject_id', $subjectId)
                ->where('term', $term)
                ->where('is_deleted', false)
                ->with('courseOutcome') // Eager load course outcomes including soft deleted ones
                ->orderBy('type')
                ->orderBy('created_at')
                ->get();
        }
        
        return $activities;
    }
} 