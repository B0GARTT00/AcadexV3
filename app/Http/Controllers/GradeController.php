<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermGrade;
use App\Models\FinalGrade;
use App\Traits\GradeCalculationTrait;
use App\Traits\ActivityManagementTrait;
use App\Services\GradesFormulaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GradeController extends Controller
{
    /**
     * AJAX: Return course outcomes for a subject and term.
     */
    public function ajaxCourseOutcomes(Request $request)
    {
        $subjectId = $request->query('subject_id');
        $term = $request->query('term');
        
        if (!$subjectId) {
            return response()->json([]);
        }

        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json([]);
        }

        // Get course outcomes for this subject
        $outcomes = \App\Models\CourseOutcomes::where('subject_id', $subjectId)
            ->where('is_deleted', false)
            ->get()
            ->sortBy(function($co) {
                // Extract the numeric part after the last space or dot for proper sorting
                preg_match('/([\d\.]+)$/', $co->co_identifier, $matches);
                return isset($matches[1]) ? floatval($matches[1]) : $co->co_identifier;
            });

        $result = $outcomes->map(function($co) {
            return [
                'id' => $co->id,
                'code' => $co->co_code,
                'identifier' => $co->co_identifier,
            ];
        });
        
        return response()->json($result->values());
    }
    use GradeCalculationTrait, ActivityManagementTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the grade management dashboard for the instructor.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index(Request $request)
    {
        Gate::authorize('instructor');
    
        $academicPeriodId = session('active_academic_period_id');
        $term = $request->term ?? 'prelim';
    
        $subjects = Subject::where(function($query) use ($academicPeriodId) {
            $query->where('instructor_id', Auth::id())
                  ->orWhereHas('instructors', function($q) {
                      $q->where('instructor_id', Auth::id());
                  });
        })
        ->when($academicPeriodId, fn($q) => $q->where('academic_period_id', $academicPeriodId))
        ->withCount('students')
        ->get();
    
        foreach ($subjects as $subject) {
            $total = $subject->students_count;
            $terms = ['prelim', 'midterm', 'prefinal', 'final'];
            $gradedCount = 0;
    
            foreach ($terms as $t) {
                $gradedTerms = TermGrade::where('subject_id', $subject->id)
                    ->where('term_id', $this->getTermId($t))
                    ->distinct('student_id')
                    ->count('student_id');
    
                if ($gradedTerms === $total && $total > 0) {
                    $gradedCount++;
                }
            }
    
            $subject->grade_status = match (true) {
                $total === 0 => 'not_started',
                $gradedCount === 0 => 'pending',
                $gradedCount < count($terms) => 'pending',
                default => 'completed',
            };
        }
    
            $students = $activities = $scores = $termGrades = [];
            $subject = null;
            $courseOutcomes = collect();
            $activityTypes = [];
            $passingGrade = null;
            $formulaMeta = null;
    
        if ($request->filled('subject_id')) {
            $subject = Subject::where('id', $request->subject_id)
                ->when($academicPeriodId, fn($q) => $q->where('academic_period_id', $academicPeriodId))
                ->firstOrFail();

            if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
                abort(403, 'Subject does not belong to the current academic period.');
            }

            $students = Student::whereHas('subjects', fn($q) => $q->where('subject_id', $subject->id))
                ->where('is_deleted', false)
                ->get();

            $activities = $this->getOrCreateDefaultActivities($subject->id, $term);
            $formulaSettings = GradesFormulaService::getSettings(
                $subject->id,
                $subject->course_id,
                $subject->department_id,
                null,
                session('active_academic_period_id')
            );
            $activityTypes = array_keys($formulaSettings['weights']);
            $passingGrade = $formulaSettings['passing_grade'] ?? null;
            $formulaMeta = $formulaSettings['meta'] ?? null;

            // Get all course outcomes for this subject and term's academic period
            $courseOutcomes = \App\Models\CourseOutcomes::where('subject_id', $subject->id)
                ->where('is_deleted', false)
                ->get()
                ->sortBy(function($co) {
                    // Extract the numeric part after the last space or dot for proper sorting
                    preg_match('/([\d\.]+)$/', $co->co_identifier, $matches);
                    return isset($matches[1]) ? floatval($matches[1]) : $co->co_identifier;
                });
                
            foreach ($students as $student) {
                $activityScores = $this->calculateActivityScores($activities, $student->id, $subject, $formulaSettings);
                foreach ($activities as $activity) {
                    $scoreRecord = $student->scores()->where('activity_id', $activity->id)->first();
                    $scores[$student->id][$activity->id] = $scoreRecord?->score;
                }
                if ($activityScores['allScored'] && $activityScores['grade'] !== null) {
                    $termGrades[$student->id] = round($activityScores['grade'], 2);
                } else {
                    $termGrades[$student->id] = null;
                }
            }
        }
    
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('instructor.partials.grade-body', compact(
                'subject', 'term', 'students', 'activities', 'scores', 'termGrades', 'courseOutcomes', 'activityTypes', 'passingGrade', 'formulaMeta'
            ));
        }

        return view('instructor.manage-grades', compact(
            'subjects', 'subject', 'term', 'students', 'activities', 'scores', 'termGrades', 'courseOutcomes', 'activityTypes', 'passingGrade', 'formulaMeta'
        ));
    }

    public function store(Request $request)
    {
        Gate::authorize('instructor');
    
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,prefinal,final',
            'scores' => 'required|array',
            'course_outcomes' => 'array',
        ]);
    
        $subject = Subject::findOrFail($request->subject_id);
        $termId = $this->getTermId($request->term);
        $activities = $this->getOrCreateDefaultActivities($subject->id, $request->term);
        $formulaSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            null,
            session('active_academic_period_id')
        );
    
        // Update course_outcome_id for each activity if provided
        if ($request->has('course_outcomes')) {
            foreach ($request->course_outcomes as $activityId => $coId) {
                $activity = Activity::find($activityId);
                if ($activity && ($coId === null || $coId === '' || \App\Models\CourseOutcomes::find($coId))) {
                    $activity->course_outcome_id = $coId ?: null;
                    $activity->save();
                }
            }
        }

        foreach ($request->scores as $studentId => $activityScores) {
            // Save individual scores
            foreach ($activityScores as $activityId => $score) {
                if ($score !== null && $score !== '') {
                    Score::updateOrCreate(
                        ['student_id' => $studentId, 'activity_id' => $activityId],
                        ['score' => $score, 'updated_by' => Auth::id()]
                    );
                }
            }

            // Calculate and update term grade
            $activityScores = $this->calculateActivityScores($activities, $studentId, $subject, $formulaSettings);
            if ($activityScores['allScored'] && $activityScores['grade'] !== null) {
                $termGrade = round($activityScores['grade'], 2);
                $this->updateTermGrade($studentId, $subject->id, $termId, $subject->academic_period_id, $termGrade);
                $this->calculateAndUpdateFinalGrade($studentId, $subject, $subject->academic_period_id, $activityScores['formula']);
            }

            // --- NEW: Save Course Outcome Attainment ---
            // Group activities by course_outcome_id
            $coScores = [];
            foreach ($activities as $activity) {
                /** @var \App\Models\Activity $activity */
                $coId = $activity->course_outcome_id;
                if (!$coId) continue;
                $score = isset($request->scores[$studentId][$activity->id]) ? $request->scores[$studentId][$activity->id] : null;
                if ($score !== null && $score !== '') {
                    $coScores[$coId]['score'] = ($coScores[$coId]['score'] ?? 0) + $score;
                    $coScores[$coId]['max'] = ($coScores[$coId]['max'] ?? 0) + $activity->number_of_items;
                }
            }
            foreach ($coScores as $coId => $data) {
                if (!isset($data['score']) || !isset($data['max'])) continue;
                \App\Models\CourseOutcomeAttainment::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $subject->id,
                        'course_outcome_id' => $coId,
                        'term' => $request->term,
                    ],
                    [
                        'score' => $data['score'],
                        'max' => $data['max'],
                        'semester_total' => $data['max'],
                    ]
                );
            }
            // --- END NEW ---
        }
    
        return redirect()->route('instructor.grades.index', [
            'subject_id' => $request->subject_id,
            'term' => $request->term
        ])->with('success', 'Scores saved and grades updated successfully.');
    }

    public function ajaxSaveScore(Request $request)
    {
        Gate::authorize('instructor');
    
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'activity_id' => 'required|exists:activities,id',
            'score' => 'nullable|numeric|min:0',
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,prefinal,final',
            'course_outcome_id' => 'nullable|exists:course_outcomes,id',
        ]);
    
        $studentId = $request->student_id;
        $subject = Subject::findOrFail($request->subject_id);
        $termId = $this->getTermId($request->term);
    
        // Save the individual score
        Score::updateOrCreate(
            ['student_id' => $studentId, 'activity_id' => $request->activity_id],
            ['score' => $request->score, 'updated_by' => Auth::id()]
        );
    
        // Calculate and update term grade
        $activities = $this->getOrCreateDefaultActivities($subject->id, $request->term);
        $formulaSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            null,
            session('active_academic_period_id')
        );
        $activityScores = $this->calculateActivityScores($activities, $studentId, $subject, $formulaSettings);
        
        if ($activityScores['allScored'] && $activityScores['grade'] !== null) {
            $termGrade = round($activityScores['grade'], 2);
            $this->updateTermGrade($studentId, $subject->id, $termId, $subject->academic_period_id, $termGrade);
            $this->calculateAndUpdateFinalGrade($studentId, $subject, $subject->academic_period_id, $activityScores['formula']);
        }
    
        return response()->json(['status' => 'success']);
    }

    public function partial(Request $request)
    {
        $subject = Subject::findOrFail($request->subject_id);
        $term = $request->term;
    
        $students = Student::whereHas('subjects', fn($q) => $q->where('subject_id', $subject->id))
            ->where('is_deleted', false)
            ->get();

        $activities = $this->getOrCreateDefaultActivities($subject->id, $term);
        $formulaSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            null,
            session('active_academic_period_id')
        );
        $activityTypes = array_keys($formulaSettings['weights']);
        $passingGrade = $formulaSettings['passing_grade'] ?? null;
        $formulaMeta = $formulaSettings['meta'] ?? null;
        
        $courseOutcomes = \App\Models\CourseOutcomes::where('subject_id', $subject->id)
            ->where('is_deleted', false)
            ->get()
            ->sortBy(function($co) {
                // Extract the numeric part after the last space or dot for proper sorting
                preg_match('/([\d\.]+)$/', $co->co_identifier, $matches);
                return isset($matches[1]) ? floatval($matches[1]) : $co->co_identifier;
            });
            
        $scores = [];
        $termGrades = [];

        foreach ($students as $student) {
            $activityScores = $this->calculateActivityScores($activities, $student->id, $subject, $formulaSettings);
            $weights = $activityScores['weights'];
            
            foreach ($activities as $activity) {
                $scoreRecord = $student->scores()->where('activity_id', $activity->id)->first();
                $scores[$student->id][$activity->id] = $scoreRecord?->score;
            }
            
            if ($activityScores['allScored'] && $activityScores['grade'] !== null) {
                $termGrades[$student->id] = round($activityScores['grade'], 2);
            } else {
                $termGrades[$student->id] = null;
            }
        }

        return view('instructor.partials.grade-body', compact(
            'subject', 'term', 'students', 'activities', 'scores', 'termGrades', 'courseOutcomes', 'activityTypes', 'passingGrade', 'formulaMeta'
        ));
    }

    private function getTermId($term)
    {
        return [
            'prelim' => 1,
            'midterm' => 2,
            'prefinal' => 3,
            'final' => 4,
        ][$term] ?? null;
    }
}
