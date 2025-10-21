<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Subject;
use App\Services\GradesFormulaService;
use Illuminate\Database\Eloquent\Collection;

trait ActivityManagementTrait
{
    protected function getOrCreateDefaultActivities(int $subjectId, string $term): Collection
    {
        $subject = Subject::find($subjectId);

        $rawTypes = GradesFormulaService::getActivityTypes(
            $subject?->id,
            $subject?->course_id,
            $subject?->department_id
        );

        $types = collect($rawTypes ?? [])
            ->map(fn ($type) => mb_strtolower($type))
            ->unique()
            ->values();

        $activities = $this->orderedActivityQuery($subjectId, $term, $types)->get();

        if ($activities->isEmpty()) {
            $defaultActivities = [];

            foreach ($types as $type) {
                $count = str_contains($type, 'exam') ? 1 : 3;

                for ($i = 1; $i <= $count; $i++) {
                    $defaultActivities[] = [
                        'subject_id' => $subjectId,
                        'term' => $term,
                        'type' => $type,
                        'title' => ucwords(str_replace('_', ' ', $type)) . ' ' . $i,
                        'number_of_items' => 100,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            Activity::insert($defaultActivities);

            $activities = $this->orderedActivityQuery($subjectId, $term, $types)->get();
        }

        return $activities;
    }

    protected function orderedActivityQuery(int $subjectId, string $term, \Illuminate\Support\Collection $typeOrder)
    {
        $query = Activity::where('subject_id', $subjectId)
            ->where('term', $term)
            ->where('is_deleted', false)
            ->with('courseOutcome');

        $this->applyActivityTypeOrdering($query, $typeOrder);

        return $query->orderBy('created_at');
    }

    protected function applyActivityTypeOrdering($query, \Illuminate\Support\Collection $typeOrder): void
    {
        if ($typeOrder->isEmpty()) {
            $query->orderBy('type');
            return;
        }

        $case = 'CASE LOWER(type) ';
        $bindings = [];

        foreach ($typeOrder as $index => $type) {
            $case .= 'WHEN ? THEN ' . $index . ' ';
            $bindings[] = $type;
        }

        $case .= 'ELSE ? END';
        $bindings[] = $typeOrder->count();

        $query->orderByRaw($case, $bindings);
    }
} 