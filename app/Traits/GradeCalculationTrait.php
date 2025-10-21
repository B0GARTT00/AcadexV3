<?php

namespace App\Traits;

use App\Models\FinalGrade;
use App\Models\Score;
use App\Models\Subject;
use App\Models\TermGrade;
use App\Services\GradesFormulaService;
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
        // ensure weights keys are lowercase
        $weights = array_change_key_case($formula['weights'], CASE_LOWER);

        $scoresByType = [];
        foreach (array_keys($weights) as $type) {
            $scoresByType[$type] = ['total' => 0, 'count' => 0];
        }

        $allScored = true;

        foreach ($activities as $activity) {
            // match activity type case-insensitively by lowercasing the activity type
            $type = mb_strtolower($activity->type);

            if (! array_key_exists($type, $scoresByType)) {
                $scoresByType[$type] = ['total' => 0, 'count' => 0];
            }

            $score = Score::where('student_id', $studentId)
                ->where('activity_id', $activity->id)
                ->first();

            if ($score && $score->score !== null) {
                $denominator = max($activity->number_of_items, 1);
                $scaledScore = ($score->score / $denominator) * $formula['scale_multiplier'] + $formula['base_score'];
                $scoresByType[$type]['total'] += $scaledScore;
                $scoresByType[$type]['count']++;
            } else {
                $allScored = false;
            }
        }

        return [
            'scores' => $scoresByType,
            'weights' => $weights,
            'formula' => $formula,
            'allScored' => $allScored,
        ];
    }

    protected function calculateTermGrade(array $scoresByType, array $weights): ?float
    {
        $weightedTotal = 0;

        foreach ($weights as $type => $weight) {
            $count = $scoresByType[$type]['count'] ?? 0;
            $average = $count > 0 ? $scoresByType[$type]['total'] / $count : 0;
            $weightedTotal += $average * $weight;
        }

        return round($weightedTotal, 2);
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
} 