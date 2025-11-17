<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TermGrade;
use App\Services\GradesFormulaService;
use App\Traits\ActivityManagementTrait;
use App\Traits\GradeCalculationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FinalGradeController extends Controller
{
    use GradeCalculationTrait, ActivityManagementTrait;
    public function __construct()
    {
        $this->middleware('auth');
    }

    // 📊 View Final Grades for a Subject
    public function index(Request $request)
    {
        Gate::authorize('instructor');

        $subjects = Subject::where('instructor_id', Auth::id())->get();
        $finalData = [];

        if ($request->filled('subject_id')) {
            $subjectId = $request->subject_id;
            $students = Student::whereHas('subjects', fn($q) => $q->where('subject_id', $subjectId))
                ->where('is_deleted', false)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            $terms = ['prelim', 'midterm', 'prefinal', 'final'];
            $termGrades = [];

            foreach ($terms as $term) {
                $termId = $this->getTermId($term);
                $termGrades[$term] = TermGrade::where('subject_id', $subjectId)
                    ->where('term_id', $termId)
                    ->get()
                    ->keyBy('student_id');
            }

            foreach ($students as $student) {
                // Get final grade record for notes
                $finalGradeRecord = FinalGrade::where('student_id', $student->id)
                    ->where('subject_id', $subjectId)
                    ->first();

                $row = [
                    'student' => $student,
                    'prelim' => data_get($termGrades, "prelim.{$student->id}.term_grade"),
                    'midterm' => data_get($termGrades, "midterm.{$student->id}.term_grade"),
                    'prefinal' => data_get($termGrades, "prefinal.{$student->id}.term_grade"),
                    'final' => data_get($termGrades, "final.{$student->id}.term_grade"),
                    'final_average' => null,
                    'remarks' => null,
                    'notes' => $finalGradeRecord->notes ?? '',
                    'has_notes' => !empty($finalGradeRecord->notes ?? ''),
                ];                

                if (
                    isset($row['prelim'], $row['midterm'], $row['prefinal'], $row['final'])
                ) {
                    $avg = round(array_sum([
                        $row['prelim'],
                        $row['midterm'],
                        $row['prefinal'],
                        $row['final'],
                    ]) / 4, 2);

                    $row['final_average'] = $avg;
                    $row['remarks'] = $avg >= 75 ? 'Passed' : 'Failed';
                }

                $finalData[] = $row;
            }
        }

        return view('instructor.scores.final-grades', compact('subjects', 'finalData'));
    }

    // ⚙️ Generate Final Grades for Students with Complete Term Grades
    public function generate(Request $request)
    {
        Gate::authorize('instructor');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        $subjectId = $subject->id;

        $students = Student::whereHas('subjects', fn($q) => $q->where('subject_id', $subjectId))->get();

        $terms = ['prelim', 'midterm', 'prefinal', 'final'];
        $gradesByTerm = [];

        foreach ($terms as $term) {
            $gradesByTerm[$term] = TermGrade::where('subject_id', $subjectId)
                ->where('term_id', $this->getTermId($term))
                ->get()
                ->keyBy('student_id');
        }

        foreach ($students as $student) {
            $hasAll = true;
            $total = 0;

            foreach ($terms as $term) {
                $grade = $gradesByTerm[$term][$student->id]->term_grade ?? null;
                if (is_null($grade)) {
                    $hasAll = false;
                    break;
                }
                $total += $grade;
            }

            if ($hasAll) {
                $average = round($total / 4, 2);
                $remarks = $average >= 75 ? 'Passed' : 'Failed';

                FinalGrade::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                    ],
                    [
                        'academic_period_id' => $subject->academic_period_id,
                        'final_grade' => $average,
                        'remarks' => $remarks,
                        'is_deleted' => false,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );
            }
        }

        return redirect()->route('instructor.final-grades.index', ['subject_id' => $subjectId])
        ->with('success', 'Final grades generated successfully.');
    
    }

    public function termReport(Request $request)
    {
        Gate::authorize('instructor');

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,prefinal,final',
        ]);

        $subject = Subject::with('course')
            ->where('id', $validated['subject_id'])
            ->where('instructor_id', Auth::id())
            ->firstOrFail();

        $students = Student::whereHas('subjects', fn ($query) => $query->where('subject_id', $subject->id))
            ->where('is_deleted', false)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $activities = $this->getOrCreateDefaultActivities($subject->id, $validated['term']);

        $studentIds = $students->pluck('id');
        $activityIds = $activities->pluck('id');

        $scores = Score::whereIn('student_id', $studentIds)
            ->whereIn('activity_id', $activityIds)
            ->get()
            ->groupBy('student_id');

        $formulaSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            null,
            session('active_academic_period_id')
        );

        $rows = $students->map(function ($student) use ($scores, $activities, $subject, $formulaSettings) {
            $studentScores = [];
            $studentScoreGroup = $scores->get($student->id, collect());

            foreach ($activities as $activity) {
                $studentScores[$activity->id] = optional(
                    $studentScoreGroup->firstWhere('activity_id', $activity->id)
                )?->score;
            }

            $termComputation = $activities->isNotEmpty()
                ? $this->calculateActivityScores($activities, $student->id, $subject, $formulaSettings)
                : ['grade' => null, 'allScored' => false];

            $termGrade = $termComputation['allScored'] && $termComputation['grade'] !== null
                ? round($termComputation['grade'], 2)
                : null;

            return [
                'student' => $student,
                'scores' => $studentScores,
                'term_grade' => $termGrade,
            ];
        });

        $academicPeriod = AcademicPeriod::find(session('active_academic_period_id'));
        $semesterLabel = match ($academicPeriod?->semester) {
            '1st' => 'First Semester',
            '2nd' => 'Second Semester',
            'Summer' => 'Summer Term',
            default => $academicPeriod?->semester,
        };

        return response()->view('instructor.scores.partials.term-print', [
            'subject' => $subject,
            'term' => $validated['term'],
            'termLabel' => ucfirst($validated['term']),
            'activities' => $activities,
            'rows' => $rows,
            'formulaMeta' => $formulaSettings['meta'] ?? null,
            'generatedAt' => now(),
            'academicYear' => $academicPeriod?->academic_year,
            'semesterLabel' => $semesterLabel,
        ]);
    }

    // 🔍 Internal Helper
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
