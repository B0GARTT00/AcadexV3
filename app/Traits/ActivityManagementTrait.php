<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Subject;
use App\Services\GradesFormulaService;
use App\Support\Grades\FormulaStructure;
use Illuminate\Database\Eloquent\Collection;

trait ActivityManagementTrait
{
    protected function getOrCreateDefaultActivities(int $subjectId, string $term): Collection
    {
        $subject = Subject::with('academicPeriod')->find($subjectId);

        $formulaSettings = GradesFormulaService::getSettings(
            $subject?->id,
            $subject?->course_id,
            $subject?->department_id,
            $subject?->academicPeriod?->semester,
            $subject?->academic_period_id,
        );

        $rawTypes = array_keys($formulaSettings['weights'] ?? []);
        $maxAssessments = $formulaSettings['meta']['max_assessments'] ?? [];
        $labels = $formulaSettings['meta']['activity_labels'] ?? [];

        $types = collect($rawTypes ?? [])
            ->map(fn ($type) => mb_strtolower($type))
            ->unique()
            ->values();

        $activities = $this->orderedActivityQuery($subjectId, $term, $types)->get();

        if ($activities->isEmpty()) {
            $defaultActivities = [];

            foreach ($types as $type) {
                $baseType = FormulaStructure::baseActivityType($type);
                $maxPerComponent = (int) ($maxAssessments[$type] ?? $maxAssessments[$baseType] ?? ($baseType === 'exam' ? 1 : 3));
                $defaultCount = $baseType === 'exam' ? 1 : min(3, $maxPerComponent);
                $label = $labels[$type] ?? $labels[$baseType] ?? FormulaStructure::formatLabel($type);

                for ($i = 1; $i <= max(1, $defaultCount); $i++) {
                    $defaultActivities[] = [
                        'subject_id' => $subjectId,
                        'term' => $term,
                        'type' => $type,
                        'title' => trim($label . ' ' . $i),
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

    protected function realignActivitiesToFormula(Subject $subject, ?string $term = null, ?int $actingUserId = null): array
    {
        $subject->loadMissing('academicPeriod');

        $formulaSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            optional($subject->academicPeriod)->semester,
            $subject->academic_period_id,
        );

        $weightDetails = collect($formulaSettings['meta']['weight_details'] ?? [])
            ->map(function (array $detail) {
                $detail['activity_type'] = mb_strtolower($detail['activity_type']);
                $detail['base_type'] = FormulaStructure::baseActivityType($detail['activity_type']);
                $detail['relative_weight_percent'] = $detail['relative_weight_percent'] ?? $detail['weight_percent'];
                return $detail;
            })
            ->keyBy('activity_type');

        if ($weightDetails->isEmpty()) {
            return [
                'processed_terms' => 0,
                'created' => 0,
                'archived' => 0,
                'per_term' => [],
            ];
        }

        $allowedTypes = $weightDetails->keys();
        $maxAssessments = collect($formulaSettings['meta']['max_assessments'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [mb_strtolower($key) => $value]);

        $terms = $term
            ? [$term]
            : ['prelim', 'midterm', 'prefinal', 'final'];

        $summary = [
            'processed_terms' => 0,
            'created' => 0,
            'archived' => 0,
            'per_term' => [],
        ];

        foreach ($terms as $termName) {
            $termName = mb_strtolower($termName);
            $termSummary = ['created' => 0, 'archived' => 0];

            foreach ($allowedTypes as $activityType) {
                $detail = $weightDetails->get($activityType);
                if (! $detail) {
                    continue;
                }

                $baseType = $detail['base_type'];
                $label = $detail['label'] ?? FormulaStructure::formatLabel($activityType);

                $existing = Activity::where('subject_id', $subject->id)
                    ->where('term', $termName)
                    ->where('type', $activityType)
                    ->where('is_deleted', false)
                    ->orderBy('created_at')
                    ->get();

                $existingCount = $existing->count();
                $minRequired = $detail['relative_weight_percent'] > 0 ? 1 : 0;
                if ($baseType === 'exam') {
                    $minRequired = max(1, $minRequired);
                }

                if ($existingCount < $minRequired) {
                    $toCreate = $minRequired - $existingCount;
                    $sequenceStart = $existingCount + 1;

                    for ($index = 0; $index < $toCreate; $index++) {
                        Activity::create([
                            'subject_id' => $subject->id,
                            'term' => $termName,
                            'type' => $activityType,
                            'title' => trim($label . ' ' . ($sequenceStart + $index)),
                            'number_of_items' => 100,
                            'course_outcome_id' => null,
                            'is_deleted' => false,
                            'created_by' => $actingUserId,
                            'updated_by' => $actingUserId,
                        ]);
                    }

                    $termSummary['created'] += $toCreate;
                    $summary['created'] += $toCreate;

                    $existing = Activity::where('subject_id', $subject->id)
                        ->where('term', $termName)
                        ->where('type', $activityType)
                        ->where('is_deleted', false)
                        ->orderBy('created_at')
                        ->get();
                }

                $maxAllowed = $maxAssessments[$activityType]
                    ?? $maxAssessments[$baseType]
                    ?? null;

                if ($maxAllowed !== null && $existing->count() > $maxAllowed) {
                    $excess = $existing->sortByDesc('created_at')
                        ->take($existing->count() - $maxAllowed);

                    foreach ($excess as $activity) {
                        $activity->update([
                            'is_deleted' => true,
                            'updated_by' => $actingUserId,
                        ]);
                    }

                    $termSummary['archived'] += $excess->count();
                    $summary['archived'] += $excess->count();
                }
            }

            $extraActivities = Activity::where('subject_id', $subject->id)
                ->where('term', $termName)
                ->whereNotIn('type', $allowedTypes->all())
                ->where('is_deleted', false)
                ->get();

            foreach ($extraActivities as $activity) {
                $activity->update([
                    'is_deleted' => true,
                    'updated_by' => $actingUserId,
                ]);
            }

            if ($extraActivities->isNotEmpty()) {
                $termSummary['archived'] += $extraActivities->count();
                $summary['archived'] += $extraActivities->count();
            }

            $summary['per_term'][$termName] = $termSummary;
            $summary['processed_terms']++;
        }

        return $summary;
    }
} 