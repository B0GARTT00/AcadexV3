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
        $academicYear = $request->input('academic_year');
        $semester = $request->input('semester');

        $periods = AcademicPeriod::all();

        // Filter subjects by semester (enum) in AcademicPeriod
        $semester = $request->input('semester');
        $subjectsQuery = Subject::where('instructor_id', $request->user()->id)
            ->where('is_deleted', false);

        $periodFilter = AcademicPeriod::query();
        if ($academicYear) {
            $periodFilter->where('academic_year', $academicYear);
        }
        if ($semester) {
            $periodFilter->where('semester', $semester);
        }
        $periodIds = $periodFilter->pluck('id')->toArray();
        if (!empty($periodIds)) {
            $subjectsQuery->whereIn('academic_period_id', $periodIds);
        } else if ($academicYear || $semester) {
            // If filtering and no periods match, return empty collection
            $subjects = collect();
            return view('instructor.course-outcomes-wildcards', [
                'subjects' => $subjects,
                'periods' => $periods,
                'academicYear' => $academicYear,
                'semester' => $semester,
            ]);
        }
        $subjects = $subjectsQuery->get();

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
                'academicYear' => $academicYear,
            ]);
        } else {
            return view('instructor.course-outcomes-wildcards', [
                'subjects' => $subjects,
                'periods' => $periods,
                'academicYear' => $academicYear,
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

        // Filter subjects by academic year for consistency
        $academicYear = $request->input('academic_year');
        $periods = AcademicPeriod::all();
        $subjectsQuery = Subject::where('instructor_id', $request->user()->id)
            ->where('is_deleted', false);
        if ($academicYear) {
            $periodIds = AcademicPeriod::where('academic_year', $academicYear)->pluck('id')->toArray();
            if (!empty($periodIds)) {
                $subjectsQuery->whereIn('academic_period_id', $periodIds);
            } else {
                $subjects = collect();
                return view('instructor.course-outcomes-table', [
                    'cos' => collect(),
                    'periods' => $periods,
                    'subjects' => $subjects,
                    'selectedSubject' => null,
                    'academicYear' => $academicYear,
                ])->with('success', 'Course Outcome created successfully.');
            }
        }
        $subjects = $subjectsQuery->get();
        return view('instructor.course-outcomes-table', [
            'cos' => CourseOutcomes::where('subject_id', $validated['subject_id'])
                ->where('is_deleted', false)
                ->with(['subject', 'academicPeriod'])
                ->get(),
            'periods' => $periods,
            'subjects' => $subjects,
            'selectedSubject' => $subjects->firstWhere('id', $validated['subject_id']),
            'academicYear' => $academicYear,
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


