<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Score;
use App\Models\TermGrade;
use App\Models\FinalGrade;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait GradeCalculationTrait
{
    protected function calculateActivityScores(Collection $activities, int $studentId): array
    {
        $scoresByType = [
            'quiz' => ['total' => 0, 'count' => 0],
            'ocr' => ['total' => 0, 'count' => 0],
            'exam' => ['total' => 0, 'count' => 0],
        ];
        
        $allScored = true;
        
        foreach ($activities as $activity) {
            $score = Score::where('student_id', $studentId)
                ->where('activity_id', $activity->id)
                ->first();
                
            if ($score && $score->score !== null) {
                $scaledScore = ($score->score / $activity->number_of_items) * 60 + 40;
                $scoresByType[$activity->type]['total'] += $scaledScore;
                $scoresByType[$activity->type]['count']++;
            } else {
                $allScored = false;
            }
        }
        
        return [
            'scores' => $scoresByType,
            'allScored' => $allScored
        ];
    }
    
    protected function calculateTermGrade(array $scoresByType): ?float
    {
        $quizAvg = $scoresByType['quiz']['count'] > 0
            ? $scoresByType['quiz']['total'] / $scoresByType['quiz']['count']
            : 0;
            
        $ocrAvg = $scoresByType['ocr']['count'] > 0
            ? $scoresByType['ocr']['total'] / $scoresByType['ocr']['count']
            : 0;
            
        $examAvg = $scoresByType['exam']['count'] > 0
            ? $scoresByType['exam']['total'] / $scoresByType['exam']['count']
            : 0;
            
        return round(
            ($quizAvg * 0.4) + ($ocrAvg * 0.2) + ($examAvg * 0.4),
            2
        );
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
    
    protected function calculateAndUpdateFinalGrade(int $studentId, int $subjectId, int $academicPeriodId): void
    {
        $termGrades = TermGrade::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->whereIn('term_id', [1, 2, 3, 4])
            ->get();
            
        if ($termGrades->count() === 4) {
            $finalGrade = round($termGrades->avg('term_grade'), 2);
            $remarks = $finalGrade >= 75 ? 'Passed' : 'Failed';
            
            FinalGrade::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_id' => $subjectId
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
            
            Log::info("Final grade updated for student {$studentId} in subject {$subjectId}: {$finalGrade} ({$remarks})");
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