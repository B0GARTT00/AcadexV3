<?php

namespace App\Http\Controllers;

use App\Models\
{
    CourseOutcomes,
    Subject
};
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseOutcomesController extends Controller
{
    /**
     * AJAX: Return course outcomes for a subject and term.
     */
    public function ajaxCourseOutcomes(Request $request)
    {
        $subjectId = $request->query('subject_id');
        $term = $request->query('term');
        if (!$subjectId || !$term) {
            return response()->json([]);
        }

        // Find the academic period for the subject and term
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json([]);
        }
        $academicPeriodId = $subject->academic_period_id;

        // Get course outcomes for this subject and term
        $outcomes = CourseOutcomes::where('subject_id', $subjectId)
            ->where('academic_period_id', $academicPeriodId)
            ->where('is_deleted', false)
            ->get();

        $result = $outcomes->map(function($co) {
            return [
                'id' => $co->id,
                'code' => $co->co_code,
                'name' => $co->co_identifier,
            ];
        });
        return response()->json($result);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $academicYear = $request->input('academic_year');
        $semester = $request->input('semester') ?? session('active_semester');

        // If active_academic_period_id is set and active_semester is not, set it from the period
        $period = null;
        if (session('active_academic_period_id')) {
            $period = AcademicPeriod::find(session('active_academic_period_id'));
            if ($period && !session('active_semester')) {
                session(['active_semester' => $period->semester]);
                $semester = $period->semester;
            }
            // If academicYear is not set, use the year from the selected period
            if (!$academicYear && $period) {
                $academicYear = $period->academic_year;
            }
        }

        $periods = AcademicPeriod::all()->filter(function($period) {
            return $period && isset($period->id) && isset($period->academic_year) && isset($period->semester);
        });

        // Only show subjects in the selected academic year and semester
        $subjectsQuery = Subject::query()
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
            return view('instructor.course-outcomes-wildcards', [
                'subjects' => $subjects,
                'periods' => $periods,
                'academicYear' => $academicYear,
                'semester' => $semester,
            ]);
        }
        $subjects = $subjectsQuery->select('subjects.*', 'academic_periods.academic_year as debug_academic_year', 'academic_periods.semester as debug_semester')->get();

        if ($request->filled('subject_id')) {
            $query = CourseOutcomes::where('is_deleted', false)
                ->with(['subject', 'academicPeriod'])
                ->where('subject_id', $request->subject_id)
                ->orderBy('created_at', 'asc');

            $cos = $query->get();

            return view('instructor.course-outcomes-table', [
                'cos' => $cos,
                'periods' => $periods,
                'subjects' => $subjects,
                'selectedSubject' => $subjects->firstWhere('id', $request->subject_id),
                'academicYear' => $academicYear,
                'debugSemester' => $semester,
                'currentPeriod' => $period ?? $periods->first(),
            ]);
        } else {
            return view('instructor.course-outcomes-wildcards', [
                'subjects' => $subjects,
                'periods' => $periods,
                'academicYear' => $academicYear,
                'debugSemester' => $semester,
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subjects = Subject::all();
        $periods = AcademicPeriod::all();

        return view('course_outcomes.create', compact('subjects', 'periods'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'co_code' => 'required|string|max:255',
            'co_identifier' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Get the academic period from the subject
        $subject = Subject::find($validated['subject_id']);
        if (!$subject || !$subject->academic_period_id) {
            return redirect()->back()->with('error', 'Subject not found or no academic period assigned.');
        }

        $validated['academic_period_id'] = $subject->academic_period_id;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        CourseOutcomes::create($validated);

        // Redirect to the same page with subject_id for a full refresh
        return redirect()->route('instructor.course_outcomes.index', ['subject_id' => $validated['subject_id']])
            ->with('success', 'Course Outcome created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseOutcomes $courseOutcome)
    {
        return view('course_outcomes.show', compact('courseOutcome'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseOutcomes $courseOutcome)
    {
        $subjects = Subject::all();
        $periods = AcademicPeriod::all();

        return view('course_outcomes.edit', compact('courseOutcome', 'subjects', 'periods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseOutcomes $courseOutcome)
    {
        $validated = $request->validate([
            'co_code' => 'required|string|max:255',
            'co_identifier' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Get the academic period from the subject (maintain consistency)
        $subject = $courseOutcome->subject;
        if ($subject && $subject->academic_period_id) {
            $validated['academic_period_id'] = $subject->academic_period_id;
        }

        $validated['updated_by'] = $request->user()->id;

        $courseOutcome->update($validated);

        return redirect()->route('instructor.course_outcomes.index', ['subject_id' => $courseOutcome->subject_id])
            ->with('success', 'Course Outcome updated successfully.');
    }

    /**
     * Update only the description via AJAX for inline editing.
     */
    public function updateDescription(Request $request, CourseOutcomes $courseOutcome)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $courseOutcome->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Description updated successfully.',
            'description' => $courseOutcome->description
        ]);
    }

    /**
     * Soft-delete the specified resource.
     */
    public function destroy(Request $request, CourseOutcomes $courseOutcome)
    {
        $courseOutcome->update(['is_deleted' => 1]);


        return redirect()->route('instructor.course_outcomes.index', ['subject_id' => $courseOutcome->subject_id])
            ->with('success', 'Course Outcome deleted.');
    }
}


