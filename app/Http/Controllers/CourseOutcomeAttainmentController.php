<?php

namespace App\Http\Controllers;

use App\Models\CourseOutcomeAttainment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Traits\CourseOutcomeTrait;

class CourseOutcomeAttainmentController extends Controller
{
    use CourseOutcomeTrait;

    public function subject($subjectId)
    {
        $academicPeriodId = session('active_academic_period_id');

        // Get the selected subject with course and academicPeriod relationships
        $selectedSubject = \App\Models\Subject::with(['course', 'academicPeriod'])->findOrFail($subjectId);

        // Get students enrolled in the subject
        $students = \App\Models\Student::whereHas('subjects', function($q) use ($subjectId) {
            $q->where('subject_id', $subjectId);
        })->get();

        // Terms
        $terms = ['prelim', 'midterm', 'prefinal', 'final'];
        $termIds = [1 => 'prelim', 2 => 'midterm', 3 => 'prefinal', 4 => 'final'];

        // Get activities for the subject, grouped by term
        $activitiesByTerm = [];
        $coColumnsByTerm = [];
        foreach ($terms as $term) {
            $activities = \App\Models\Activity::where('subject_id', $subjectId)
                ->where('term', $term)
                ->where('is_deleted', false)
                ->whereNotNull('course_outcome_id')
                ->get();
            $activitiesByTerm[$term] = $activities;
            
            // Get unique course outcome IDs and sort them properly
            $coIds = $activities->pluck('course_outcome_id')->unique()->toArray();
            
            // Sort by getting the actual CourseOutcomes and ordering by co_code
            if (!empty($coIds)) {
                $sortedCos = \App\Models\CourseOutcomes::whereIn('id', $coIds)
                    ->orderBy('co_code')
                    ->pluck('id')
                    ->toArray();
                $coColumnsByTerm[$term] = $sortedCos;
            } else {
                $coColumnsByTerm[$term] = [];
            }
        }

        // Build activityCoMap: [term => [activity_id => co_id]]
        $activityCoMap = [];
        foreach ($activitiesByTerm as $term => $activities) {
            foreach ($activities as $activity) {
                $activityCoMap[$term][$activity->id] = $activity->course_outcome_id;
            }
        }

        // Build studentScores: [student_id => [term => [activity_id => ['score'=>, 'max'=>]]]]
        $studentScores = [];
        foreach ($students as $student) {
            foreach ($activitiesByTerm as $term => $activities) {
                foreach ($activities as $activity) {
                    $score = \App\Models\Score::where('student_id', $student->id)
                        ->where('activity_id', $activity->id)
                        ->first();
                    $studentScores[$student->id][$term][$activity->id] = [
                        'score' => $score ? $score->score : 0,
                        'max' => $activity->number_of_items,
                    ];
                }
            }
        }

        // Compute CO attainment for each student
        $coResults = [];
        foreach ($students as $student) {
            $coResults[$student->id] = $this->computeCoAttainment($studentScores[$student->id] ?? [], $activityCoMap);
        }

        // Get CO details for columns
        $coDetails = \App\Models\CourseOutcomes::whereIn('id', array_unique(array_merge(...array_values($coColumnsByTerm))))->get()->keyBy('id');

        // Create properly sorted finalCOs for the combined table
        $finalCOs = array_unique(array_merge(...array_values($coColumnsByTerm)));
        
        // Sort finalCOs by co_code numerically (CO1, CO2, CO3, CO4)
        usort($finalCOs, function($a, $b) use ($coDetails) {
            $codeA = $coDetails[$a]->co_code ?? '';
            $codeB = $coDetails[$b]->co_code ?? '';
            
            // Extract numeric part from CO codes (CO1 -> 1, CO2 -> 2, etc.)
            $numA = (int)preg_replace('/[^0-9]/', '', $codeA);
            $numB = (int)preg_replace('/[^0-9]/', '', $codeB);
            
            return $numA <=> $numB; // Numeric comparison
        });

        // Reindex the array to ensure sequential indices (0, 1, 2, 3...)
        $finalCOs = array_values($finalCOs);

        return view('instructor.scores.course-outcome-results', [
            'students' => $students,
            'coResults' => $coResults,
            'coColumnsByTerm' => $coColumnsByTerm,
            'coDetails' => $coDetails,
            'finalCOs' => $finalCOs,
            'terms' => $terms,
            'subjectId' => $subjectId,
            'selectedSubject' => $selectedSubject,
        ]);
    }

    public function index(Request $request)
    {
        // Get academic year and semester from request or session
        $academicYear = $request->input('academic_year');
        $semester = $request->input('semester') ?? session('active_semester');

        // If active_academic_period_id is set and active_semester is not, set it from the period
        $period = null;
        if (session('active_academic_period_id')) {
            $period = \App\Models\AcademicPeriod::find(session('active_academic_period_id'));
            if ($period && !session('active_semester')) {
                session(['active_semester' => $period->semester]);
                $semester = $period->semester;
            }
            if (!$academicYear && $period) {
                $academicYear = $period->academic_year;
            }
        }

        $periods = \App\Models\AcademicPeriod::all();

        // Only show subjects in the selected academic year and semester
        $subjectsQuery = \App\Models\Subject::query()
            ->join('academic_periods', 'subjects.academic_period_id', '=', 'academic_periods.id')
            ->where(function($query) {
                $query->where('subjects.instructor_id', Auth::id())
                      ->orWhereHas('instructors', function($q) {
                          $q->where('instructor_id', Auth::id());
                      });
            })
            ->where('subjects.is_deleted', false);
        if ($academicYear) {
            $subjectsQuery->where('academic_periods.academic_year', $academicYear);
        }
        if ($semester) {
            $subjectsQuery->where('academic_periods.semester', $semester);
        }
        // If filtering and no periods match, return empty collection
        if (($academicYear || $semester) && $subjectsQuery->count() === 0) {
            $subjects = collect();
            return view('instructor.scores.course-outcome-results-wildcards', [
                'subjects' => $subjects,
                'periods' => $periods,
                'academicYear' => $academicYear,
                'semester' => $semester,
            ]);
        }
        $subjects = $subjectsQuery->select('subjects.*', 'academic_periods.academic_year as academic_year', 'academic_periods.semester as semester')->get();

        return view('instructor.scores.course-outcome-results-wildcards', [
            'subjects' => $subjects,
            'periods' => $periods,
            'academicYear' => $academicYear,
            'semester' => $semester,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'term' => 'required|string',
            'co_id' => 'required|exists:course_outcomes,id',
            'score' => 'required|integer',
            'max' => 'required|integer',
            'semester_total' => 'required|numeric',
        ]);
        $attainment = CourseOutcomeAttainment::create($data);
        return response()->json(['status' => 'success', 'attainment' => $attainment]);
    }

    public function show($id)
    {
        $attainment = CourseOutcomeAttainment::with(['student', 'courseOutcome'])->findOrFail($id);
        return response()->json($attainment);
    }

    public function update(Request $request, $id)
    {
        $attainment = CourseOutcomeAttainment::findOrFail($id);
        $data = $request->validate([
            'score' => 'integer',
            'max' => 'integer',
            'semester_total' => 'numeric',
        ]);
        $attainment->update($data);
        return response()->json(['status' => 'success', 'attainment' => $attainment]);
    }

    public function destroy($id)
    {
        $attainment = CourseOutcomeAttainment::findOrFail($id);
        $attainment->delete();
        return response()->json(['status' => 'deleted']);
    }
}
