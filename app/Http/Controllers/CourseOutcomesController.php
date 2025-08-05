<?php

namespace App\Http\Controllers;

use App\Models\CourseOutcomes;
use App\Models\Subject;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;

class CourseOutcomesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $subjects = Subject::where('instructor_id', $request->user()->id)
            ->where('is_deleted', false)
            ->get();

        $periods = AcademicPeriod::all();

        if ($request->filled('subject_id')) {
            $query = CourseOutcomes::where('is_deleted', false)
                ->with(['subject', 'academicPeriod'])
                ->where('subject_id', $request->subject_id);

            $cos = $query->get();

            return view('instructor.course-outcomes-table', [
                'cos' => $cos,
                'periods' => $periods,
                'subjects' => $subjects,
                'selectedSubject' => $subjects->firstWhere('id', $request->subject_id),
            ]);
        } else {
            return view('instructor.course-outcomes-wildcards', [
                'subjects' => $subjects
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
            'academic_period_id' => 'required|exists:academic_periods,id',
            'co_code' => 'required|string|max:255',
            'co_identifier' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        CourseOutcomes::create($validated);

        return view('instructor.course-outcomes-table', [
            'cos' => CourseOutcomes::where('subject_id', $validated['subject_id'])
                ->where('is_deleted', false)
                ->with(['subject', 'academicPeriod'])
                ->get(),
            'periods' => AcademicPeriod::all(),
            'subjects' => Subject::where('instructor_id', $request->user()->id)
                ->where('is_deleted', false)
                ->get(),
            'selectedSubject' => Subject::find($validated['subject_id']),
        ])->with('success', 'Course Outcome created successfully.');
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
            'academic_period_id' => 'required|exists:academic_periods,id',
            'co_code' => 'required|string|max:255',
            'co_identifier' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $courseOutcome->update($validated);

        return redirect()->route('instructor.course_outcomes.index')
            ->with('success', 'Course Outcome created successfully.');
    }

    /**
     * Soft-delete the specified resource.
     */
    public function destroy(Request $request, CourseOutcomes $courseOutcome)
    {
        $courseOutcome->update([
            'is_deleted' => true,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('instructor.course_outcomes.index')
            ->with('success', 'Course Outcome created successfully.');
    }
}


