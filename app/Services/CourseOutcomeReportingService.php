<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Course;
use App\Models\CourseOutcomes;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use App\Traits\CourseOutcomeTrait;

/**
 * Aggregates Course Outcome (CO) attainment across different scopes.
 *
 * Contract
 * - Inputs: subject IDs, course ID, or department ID with active academic period
 * - Output: associative arrays keyed by CO code number (1..6) with raw, max, percent
 * - Missing scores count as 0 while the max still counts (consistent with computeCoAttainment)
 */
class CourseOutcomeReportingService
{
    use CourseOutcomeTrait;

    /**
     * Compute CO attainment for a single student in a specific subject across terms.
     * Returns structure compatible with computeCoAttainment plus term/column helpers.
     */
    public function computeStudentSubject(int $studentId, int $subjectId): array
    {
        $terms = ['prelim', 'midterm', 'prefinal', 'final'];

        // Activities grouped by term with COs
        $activitiesByTerm = [];
        $coColumnsByTerm = [];
        foreach ($terms as $term) {
            $activities = Activity::where('subject_id', $subjectId)
                ->where('term', $term)
                ->where('is_deleted', false)
                ->whereNotNull('course_outcome_id')
                ->get();
            $activitiesByTerm[$term] = $activities;

            $coIds = $activities->pluck('course_outcome_id')->unique()->toArray();
            if (!empty($coIds)) {
                $sortedCos = CourseOutcomes::whereIn('id', $coIds)
                    ->where('is_deleted', false)
                    ->orderBy('co_code')
                    ->pluck('id')
                    ->toArray();
                $coColumnsByTerm[$term] = $sortedCos;
            } else {
                $coColumnsByTerm[$term] = [];
            }
        }

        // Map activity->CO per term
        $activityCoMap = [];
        foreach ($activitiesByTerm as $term => $activities) {
            foreach ($activities as $activity) {
                $activityCoMap[$term][$activity->id] = $activity->course_outcome_id;
            }
        }

        // Build scores for this student only
        $studentScores = [];
        foreach ($activitiesByTerm as $term => $activities) {
            foreach ($activities as $activity) {
                $score = Score::where('student_id', $studentId)
                    ->where('activity_id', $activity->id)
                    ->where('is_deleted', false)
                    ->first();
                $studentScores[$term][$activity->id] = [
                    'score' => $score ? (int)$score->score : 0,
                    'max' => (int)$activity->number_of_items,
                ];
            }
        }

        $coAttainment = $this->computeCoAttainment($studentScores, $activityCoMap);

        // CO details for display - get unique CO IDs across all terms
        $finalCOs = array_unique(array_merge(...array_values($coColumnsByTerm ?: [[]])));
        $coDetails = empty($finalCOs)
            ? collect()
            : CourseOutcomes::whereIn('id', $finalCOs)->where('is_deleted', false)->get()->keyBy('id');

        // Sort final COs by numeric code order and map IDs to codes
        usort($finalCOs, function($a, $b) use ($coDetails) {
            $codeA = $coDetails[$a]->co_code ?? '';
            $codeB = $coDetails[$b]->co_code ?? '';
            $numA = (int)preg_replace('/[^0-9]/', '', $codeA);
            $numB = (int)preg_replace('/[^0-9]/', '', $codeB);
            return $numA <=> $numB;
        });
        
        // Map CO IDs to CO codes for display
        $coCodesForHeader = [];
        foreach ($finalCOs as $coId) {
            if (isset($coDetails[$coId])) {
                $coCodesForHeader[] = $coDetails[$coId]->co_code;
            }
        }

        // Transform coResults to use CO codes instead of IDs
        // Structure: [term][coCode] => ['raw' => int, 'max' => int, 'percent' => float]
        $coResultsByCode = [];
        foreach ($terms as $term) {
            foreach ($finalCOs as $coId) {
                if (isset($coDetails[$coId])) {
                    $coCode = $coDetails[$coId]->co_code;
                    $percent = $coAttainment['per_term'][$term][$coId] ?? 0;
                    
                    // Get raw scores from the original data
                    $rawScore = 0;
                    $maxScore = 0;
                    if (isset($studentScores[$term])) {
                        foreach ($studentScores[$term] as $activityId => $scoreData) {
                            if (($activityCoMap[$term][$activityId] ?? null) == $coId) {
                                $rawScore += $scoreData['score'];
                                $maxScore += $scoreData['max'];
                            }
                        }
                    }
                    
                    if ($maxScore > 0) {
                        $coResultsByCode[$term][$coCode] = [
                            'raw' => $rawScore,
                            'max' => $maxScore,
                            'percent' => $percent,
                        ];
                    }
                }
            }
        }
        
        // Add final/overall row with semester totals
        $finalCOs_data = [];
        foreach ($finalCOs as $coId) {
            if (isset($coDetails[$coId])) {
                $coCode = $coDetails[$coId]->co_code;
                $finalCOs_data[$coCode] = [
                    'raw' => $coAttainment['semester_raw'][$coId] ?? 0,
                    'max' => $coAttainment['semester_max'][$coId] ?? 0,
                    'percent' => $coAttainment['semester_total'][$coId] ?? 0,
                ];
            }
        }

        return [
            'terms' => $terms,
            'coColumnsByTerm' => $coCodesForHeader,
            'coDetails' => $coDetails,
            'finalCOs' => $finalCOs_data,
            'coResults' => $coResultsByCode,
        ];
    }
    /**
     * Compute aggregated CO attainment for a single subject across all enrolled students and terms.
     * Returns: [co_code_number => ['raw' => int, 'max' => int, 'percent' => float]]
     */
    public function aggregateSubject(int $subjectId): array
    {
        $subject = Subject::with('course')->findOrFail($subjectId);

        // Enrolled students for the subject (pivot student_subjects)
        $students = Student::whereHas('subjects', function ($q) use ($subjectId) {
            $q->where('subject_id', $subjectId)->where('student_subjects.is_deleted', false);
        })->where('students.is_deleted', false)->get();

        $studentCount = $students->count();
        if ($studentCount === 0) {
            return [];
        }

        $coTotals = [];

        // Get all activities for this subject mapped to COs
        $activities = Activity::where('subject_id', $subjectId)
            ->where('is_deleted', false)
            ->whereNotNull('course_outcome_id')
            ->get();

        if ($activities->isEmpty()) {
            return [];
        }

        // Prefetch scores for all involved activities and students to minimize queries
        $activityIds = $activities->pluck('id')->all();
        $scores = Score::whereIn('activity_id', $activityIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy(function ($s) { return $s->activity_id.'::'.$s->student_id; });

        foreach ($activities as $activity) {
            // Determine CO number from the course_outcomes.co_code (e.g., CO1 -> 1)
            $coId = $activity->course_outcome_id;
            $co = CourseOutcomes::find($coId);
            if (!$co || $co->is_deleted) {
                continue;
            }
            $coNum = (int)preg_replace('/[^0-9]/', '', (string)$co->co_code);
            if ($coNum <= 0) {
                // Fallback by position if code missing
                $coNum = 0;
            }

            if (!isset($coTotals[$coNum])) {
                $coTotals[$coNum] = ['raw' => 0, 'max' => 0];
            }

            // For each enrolled student, add score (0 if missing) and add max (number_of_items)
            foreach ($students as $student) {
                $key = $activity->id.'::'.$student->id;
                $score = $scores->get($key)?->first();
                $coTotals[$coNum]['raw'] += $score ? (int)$score->score : 0;
                $coTotals[$coNum]['max'] += (int)$activity->number_of_items;
            }
        }

        // Compute final percentages
        $result = [];
        foreach ($coTotals as $num => $totals) {
            $percent = $totals['max'] > 0 ? round(($totals['raw'] / $totals['max']) * 100, 2) : 0.0;
            $result[$num] = [
                'raw' => $totals['raw'],
                'max' => $totals['max'],
                'percent' => $percent,
            ];
        }

        ksort($result);
        return $result;
    }

    /**
     * Aggregate CO attainment for a course (program) across all its subjects in a given academic period.
     * Returns same structure as aggregateSubject but merged across subjects.
     */
    public function aggregateCourse(int $courseId, ?int $academicPeriodId = null): array
    {
        $subjects = Subject::where('course_id', $courseId)
            ->where('is_deleted', false)
            ->when($academicPeriodId, function ($q) use ($academicPeriodId) {
                $q->where('academic_period_id', $academicPeriodId);
            })
            ->pluck('id')
            ->all();

        return $this->aggregateSubjects($subjects);
    }

    /**
     * Aggregate CO attainment across multiple subjects by merging totals per CO number.
     */
    public function aggregateSubjects(array $subjectIds): array
    {
        $merged = [];

        foreach ($subjectIds as $sid) {
            $agg = $this->aggregateSubject((int)$sid);
            foreach ($agg as $coNum => $vals) {
                if (!isset($merged[$coNum])) {
                    $merged[$coNum] = ['raw' => 0, 'max' => 0];
                }
                $merged[$coNum]['raw'] += (int)$vals['raw'];
                $merged[$coNum]['max'] += (int)$vals['max'];
            }
        }

        $result = [];
        foreach ($merged as $num => $totals) {
            $percent = $totals['max'] > 0 ? round(($totals['raw'] / $totals['max']) * 100, 2) : 0.0;
            $result[$num] = [
                'raw' => $totals['raw'],
                'max' => $totals['max'],
                'percent' => $percent,
            ];
        }

        ksort($result);
        return $result;
    }

    /**
     * Aggregate per course within a department for a given period.
     * Returns: [course_id => ['course' => Course, 'co' => [num => ['raw','max','percent']]]]
     */
    public function aggregateDepartmentByCourse(int $departmentId, ?int $academicPeriodId = null): array
    {
        $courses = Course::where('department_id', $departmentId)
            ->where('is_deleted', false)
            ->get();

        $out = [];
        foreach ($courses as $course) {
            $out[$course->id] = [
                'course' => $course,
                'co' => $this->aggregateCourse($course->id, $academicPeriodId),
            ];
        }
        return $out;
    }

    /**
     * Aggregate CO attainment for GE subjects across all courses/programs.
     * For each program, only count GE subjects taken by students from that program.
     * Returns: [course_id => ['course' => Course, 'co' => [num => ['raw','max','percent']]]]
     */
    public function aggregateGESubjectsAcrossCourses(?int $academicPeriodId = null): array
    {
        // Get all courses (programs)
        $courses = Course::where('is_deleted', false)->get();

        // Get all GE subjects for the period
        $geSubjects = Subject::where('department_id', 1) // GE department
            ->where('is_deleted', false)
            ->when($academicPeriodId, function ($q) use ($academicPeriodId) {
                $q->where('academic_period_id', $academicPeriodId);
            })
            ->get();

        $out = [];
        foreach ($courses as $course) {
            // For each course, we need to calculate CO for GE subjects taken by students from this course
            $coTotals = [];

            // Get students from this course/program
            $studentsInCourse = Student::where('course_id', $course->id)
                ->where('is_deleted', false)
                ->pluck('id')
                ->all();

            if (empty($studentsInCourse)) {
                continue; // Skip courses with no students
            }

            // For each GE subject, calculate CO attainment for students from this course
            foreach ($geSubjects as $subject) {
                // Get students from this course who are enrolled in this GE subject
                $enrolledStudents = Student::where('course_id', $course->id)
                    ->where('students.is_deleted', false)
                    ->whereHas('subjects', function ($q) use ($subject) {
                        $q->where('subject_id', $subject->id)
                          ->where('student_subjects.is_deleted', false);
                    })
                    ->pluck('students.id')
                    ->all();

                if (empty($enrolledStudents)) {
                    continue; // No students from this course in this GE subject
                }

                // Get activities for this subject with COs
                $activities = Activity::where('subject_id', $subject->id)
                    ->where('is_deleted', false)
                    ->whereNotNull('course_outcome_id')
                    ->get();

                if ($activities->isEmpty()) {
                    continue;
                }

                // Prefetch scores for these specific students and activities
                $activityIds = $activities->pluck('id')->all();
                $scores = Score::whereIn('activity_id', $activityIds)
                    ->whereIn('student_id', $enrolledStudents)
                    ->where('is_deleted', false)
                    ->get()
                    ->groupBy(function ($s) { return $s->activity_id.'::'.$s->student_id; });

                // Aggregate CO data
                foreach ($activities as $activity) {
                    $coId = $activity->course_outcome_id;
                    $co = CourseOutcomes::find($coId);
                    if (!$co || $co->is_deleted) {
                        continue;
                    }
                    $coNum = (int)preg_replace('/[^0-9]/', '', (string)$co->co_code);
                    if ($coNum <= 0) {
                        continue;
                    }

                    if (!isset($coTotals[$coNum])) {
                        $coTotals[$coNum] = ['raw' => 0, 'max' => 0];
                    }

                    // For each enrolled student from this course, add score and max
                    foreach ($enrolledStudents as $studentId) {
                        $key = $activity->id.'::'.$studentId;
                        $score = $scores->get($key)?->first();
                        $coTotals[$coNum]['raw'] += $score ? (int)$score->score : 0;
                        $coTotals[$coNum]['max'] += (int)$activity->number_of_items;
                    }
                }
            }

            // Compute final percentages for this course
            if (!empty($coTotals)) {
                $result = [];
                foreach ($coTotals as $num => $totals) {
                    $percent = $totals['max'] > 0 ? round(($totals['raw'] / $totals['max']) * 100, 2) : 0.0;
                    $result[$num] = [
                        'raw' => $totals['raw'],
                        'max' => $totals['max'],
                        'percent' => $percent,
                    ];
                }
                ksort($result);

                $out[$course->id] = [
                    'course' => $course,
                    'co' => $result,
                ];
            }
        }

        return $out;
    }
}
