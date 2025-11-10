<?php

namespace App\Traits;

use App\Models\FinalGrade;
use App\Models\Score;
use App\Models\Subject;
use App\Models\TermGrade;
use App\Services\GradesFormulaService;
use App\Support\Grades\FormulaStructure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait GradeCalculationTrait
{
    protected function getGradesFormulaSettings(?int $subjectId = null, ?int $courseId = null, ?int $departmentId = null): array
    {
        return GradesFormulaService::getSettings(
            $subjectId,
            $courseId,
            $departmentId,
            null,
            session('active_academic_period_id')
        );
    }

    protected function calculateActivityScores(Collection $activities, int $studentId, ?Subject $subject = null, ?array $formulaSettings = null): array
    {
        $formula = $formulaSettings
            ?? $this->getGradesFormulaSettings(
                $subject?->id,
                $subject?->course_id,
                $subject?->department_id
            );

        $structure = $formula['structure'] ?? null;
        if (! is_array($structure) || empty($structure)) {
            $structure = FormulaStructure::default($formula['meta']['structure_type'] ?? 'lecture_only');
        }

        $activitiesByType = $activities
            ->groupBy(fn ($activity) => mb_strtolower($activity->type));

        $scores = Score::where('student_id', $studentId)
            ->whereIn('activity_id', $activities->pluck('id')->all())
            ->get()
            ->keyBy('activity_id');

        $details = [
            'activities' => [],
            'composites' => [],
        ];
        $allScored = true;

        $rawPercent = $this->evaluateStructureNode(
            $structure,
            $activitiesByType,
            $scores,
            $formula,
            $details,
            $allScored,
            []
        );

        $termGrade = null;

        if ($rawPercent !== null) {
            $rawPercent = $this->clampGradeValue($rawPercent);
            $termGrade = $this->applyTransmutation($rawPercent, $formula);
        }

        return [
            'grade' => $termGrade,
            'raw_percent' => $rawPercent,
            'details' => $details,
            'weights' => $formula['weights'] ?? [],
            'formula' => $formula,
            'allScored' => $allScored,
        ];
    }
    
    protected function updateTermGrade(int $studentId, int $subjectId, int $termId, int $academicPeriodId, float $termGrade): void
    {
        TermGrade::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'term_id' => $termId
            ],
            [
                'term_grade' => $termGrade,
                'academic_period_id' => $academicPeriodId,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]
        );
    }
    
    protected function calculateAndUpdateFinalGrade(int $studentId, Subject $subject, int $academicPeriodId, ?array $formulaSettings = null): void
    {
        $termGrades = TermGrade::where('student_id', $studentId)
            ->where('subject_id', $subject->id)
            ->whereIn('term_id', [1, 2, 3, 4])
            ->get();
            
        if ($termGrades->count() === 4) {
            $formula = $formulaSettings
                ?? $this->getGradesFormulaSettings(
                    $subject->id,
                    $subject->course_id,
                    $subject->department_id
                );
            $finalGrade = round($termGrades->avg('term_grade'), 2);
            $remarks = $finalGrade >= $formula['passing_grade'] ? 'Passed' : 'Failed';
            
            FinalGrade::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_id' => $subject->id
                ],
                [
                    'academic_period_id' => $academicPeriodId,
                    'final_grade' => $finalGrade,
                    'remarks' => $remarks,
                    'is_deleted' => false,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]
            );
            
            Log::info("Final grade updated for student {$studentId} in subject {$subject->id}: {$finalGrade} ({$remarks})");
        }
    }
    
    protected function getTermId(string $term): ?int
    {
        return [
            'prelim' => 1,
            'midterm' => 2,
            'prefinal' => 3,
            'final' => 4,
        ][$term] ?? null;
    }

    /**
     * Recursively evaluate the grade structure tree to produce a term grade.
     */
    protected function evaluateStructureNode(
        array $node,
        \Illuminate\Support\Collection $activitiesByType,
        \Illuminate\Support\Collection $scores,
        array $formula,
        array &$details,
        bool &$allScored,
        array $path
    ): ?float {
        $type = $node['type'] ?? 'composite';
        $label = $node['label'] ?? ($path ? end($path) : 'Period Grade');
        $currentPath = array_merge($path, [$label]);

        if ($type === 'activity') {
            return $this->evaluateActivityNode($node, $activitiesByType, $scores, $formula, $details, $allScored, $currentPath);
        }

        $children = $node['children'] ?? [];
        if (empty($children)) {
            $allScored = false;
            return null;
        }

        $compositeTotal = 0.0;
        foreach ($children as $child) {
            $weight = (float) ($child['weight'] ?? 0);
            $value = $this->evaluateStructureNode($child, $activitiesByType, $scores, $formula, $details, $allScored, $currentPath);

            if ($value === null) {
                return null;
            }

            $compositeTotal += $value * $weight;
        }

        $details['composites'][] = [
            'path' => $currentPath,
            'value' => round($compositeTotal, 2),
        ];

        return $compositeTotal;
    }

    /**
     * Evaluate an activity leaf node and return the averaged score.
     */
    protected function evaluateActivityNode(
        array $node,
        \Illuminate\Support\Collection $activitiesByType,
        \Illuminate\Support\Collection $scores,
        array $formula,
        array &$details,
        bool &$allScored,
        array $path
    ): ?float {
        $activityType = mb_strtolower($node['activity_type'] ?? $node['key'] ?? '');
        $activities = $activitiesByType->get($activityType, collect());

        if ($activities->isEmpty()) {
            $allScored = false;
            return null;
        }

        $maxAssessments = $node['max_assessments'] ?? null;
        if (is_numeric($maxAssessments) && $maxAssessments > 0) {
            $activities = $activities->take((int) $maxAssessments);
        }

        $collected = [];
        foreach ($activities as $activity) {
            $score = $scores->get($activity->id);

            if (! $score || $score->score === null) {
                $allScored = false;
                return null;
            }

            $denominator = max($activity->number_of_items, 1);
            $percentage = ($score->score / $denominator) * 100;
            $collected[] = $this->clampGradeValue($percentage);
        }

        if (empty($collected)) {
            $allScored = false;
            return null;
        }

        $average = array_sum($collected) / count($collected);
        $details['activities'][$activityType] = [
            'path' => $path,
            'average' => round($average, 2),
            'count' => count($collected),
            'scores' => $collected,
        ];

        return $average;
    }

    /**
     * Clamp grade values to the acceptable 0-100 range.
     */
    protected function clampGradeValue(float $value): float
    {
        return max(0, min(100, round($value, 2)));
    }

    protected function applyTransmutation(float $rawPercent, array $formula): float
    {
        $scale = (float) ($formula['scale_multiplier'] ?? 0);
        $base = (float) ($formula['base_score'] ?? 0);

        $transmuted = ($rawPercent / 100) * $scale + $base;

        return $this->clampGradeValue($transmuted);
    }
} 