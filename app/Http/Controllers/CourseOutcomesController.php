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
use Illuminate\Support\Facades\Hash;

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
        // Use the active academic period from session
        $academicPeriodId = session('active_academic_period_id');
        
        if (!$academicPeriodId) {
            return redirect()->route('dashboard')->with('error', 'Please select an academic period first.');
        }

        $period = AcademicPeriod::find($academicPeriodId);
        if (!$period) {
            return redirect()->route('dashboard')->with('error', 'Invalid academic period.');
        }

        // Only show subjects in the current academic period
        $subjectsQuery = Subject::query()
            ->where('academic_period_id', $academicPeriodId)
            ->where('is_deleted', false);

        // Role-based filtering
        if (Auth::user()->role === 1) {
            // Chairperson: Show only subjects from their course (e.g., BSIT chairperson sees only BSIT subjects)
            // Exclude universal subjects (GE subjects should only be managed by GE Coordinator)
            $userCourseId = Auth::user()->course_id;
            if (!$userCourseId) {
                return redirect()->route('dashboard')->with('error', 'No course assigned to your account.');
            }
            $subjectsQuery->where('course_id', $userCourseId)
                          ->where('is_universal', false);
        } elseif (Auth::user()->role === 4) {
            // GE Coordinator: Show only universal subjects (is_universal = true)
            $subjectsQuery->where('is_universal', true);
        } else {
            // Instructor: Only show assigned subjects
            $subjectsQuery->where(function($query) {
                $query->where('instructor_id', Auth::id())
                      ->orWhereHas('instructors', function($q) {
                          $q->where('instructor_id', Auth::id());
                      });
            });
        }

        $subjects = $subjectsQuery->orderBy('year_level')->orderBy('subject_code')->get();
        
        // Group subjects by year level for better organization
        $subjectsByYear = $subjects->groupBy('year_level');

        if ($request->filled('subject_id')) {
            $query = CourseOutcomes::where('is_deleted', false)
                ->with(['subject', 'academicPeriod'])
                ->where('subject_id', $request->subject_id)
                ->orderBy('created_at', 'asc');

            $cos = $query->get();
            
            // Determine route prefix based on user role
            $routePrefix = Auth::user()->role === 1 ? 'chairperson' : (Auth::user()->role === 4 ? 'gecoordinator' : 'instructor');

            return view('instructor.course-outcomes-table', [
                'cos' => $cos,
                'subjects' => $subjects,
                'subjectsByYear' => $subjectsByYear,
                'selectedSubject' => $subjects->firstWhere('id', $request->subject_id),
                'currentPeriod' => $period,
                'routePrefix' => $routePrefix,
            ]);
        } else {
            // Determine route prefix based on user role
            $routePrefix = Auth::user()->role === 1 ? 'chairperson' : (Auth::user()->role === 4 ? 'gecoordinator' : 'instructor');
            return view('instructor.course-outcomes-wildcards', [
                'subjects' => $subjects,
                'subjectsByYear' => $subjectsByYear,
                'currentPeriod' => $period,
                'routePrefix' => $routePrefix,
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

        // Check if subject already has 6 course outcomes (maximum limit)
        $existingCOCount = $subject->courseOutcomes()->count();
        if ($existingCOCount >= 6) {
            return redirect()->back()
                ->with('error', 'Maximum limit reached! This subject already has 6 course outcomes, which is the maximum allowed. Please delete an existing CO before adding a new one.');
        }

        // Validate CO code format and check for duplicates
        if (!preg_match('/^CO[1-6]$/i', $validated['co_code'])) {
            return redirect()->back()
                ->withErrors(['co_code' => 'CO Code must be in format CO1, CO2, CO3, CO4, CO5, or CO6.'])
                ->withInput();
        }

        // Check if CO code already exists for this subject
        $existingCO = $subject->courseOutcomes()
            ->where('co_code', $validated['co_code'])
            ->first();
        
        if ($existingCO) {
            return redirect()->back()
                ->withErrors(['co_code' => 'Course Outcome ' . $validated['co_code'] . ' already exists for this subject.'])
                ->withInput();
        }

        $validated['academic_period_id'] = $subject->academic_period_id;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        CourseOutcomes::create($validated);

        // Determine route prefix based on user role
        $routePrefix = Auth::user()->role === 1 ? 'chairperson' : (Auth::user()->role === 4 ? 'gecoordinator' : 'instructor');

        // Redirect to the same page with subject_id for a full refresh
        return redirect()->route($routePrefix . '.course_outcomes.index', ['subject_id' => $validated['subject_id']])
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

        // Validate CO code format (must be CO1-CO6)
        if (!preg_match('/^CO[1-6]$/i', $validated['co_code'])) {
            return redirect()->back()
                ->withErrors(['co_code' => 'CO Code must be in format CO1, CO2, CO3, CO4, CO5, or CO6.'])
                ->withInput();
        }

        // Check if CO code already exists for this subject (excluding current CO)
        $existingCO = CourseOutcomes::where('subject_id', $courseOutcome->subject_id)
            ->where('co_code', $validated['co_code'])
            ->where('id', '!=', $courseOutcome->id)
            ->where('is_deleted', false)
            ->first();
        
        if ($existingCO) {
            return redirect()->back()
                ->withErrors(['co_code' => 'Course Outcome ' . $validated['co_code'] . ' already exists for this subject.'])
                ->withInput();
        }

        // Get the academic period from the subject (maintain consistency)
        $subject = $courseOutcome->subject;
        if ($subject && $subject->academic_period_id) {
            $validated['academic_period_id'] = $subject->academic_period_id;
        }

        $validated['updated_by'] = $request->user()->id;

        $courseOutcome->update($validated);

        // Determine route prefix based on user role
        $routePrefix = Auth::user()->role === 1 ? 'chairperson' : (Auth::user()->role === 4 ? 'gecoordinator' : 'instructor');

        return redirect()->route($routePrefix . '.course_outcomes.index', ['subject_id' => $courseOutcome->subject_id])
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

        // Determine route prefix based on user role
        $routePrefix = Auth::user()->role === 1 ? 'chairperson' : (Auth::user()->role === 4 ? 'gecoordinator' : 'instructor');

        return redirect()->route($routePrefix . '.course_outcomes.index', ['subject_id' => $courseOutcome->subject_id])
            ->with('success', 'Course Outcome deleted.');
    }

    /**
     * Generate 3 standard course outcomes for multiple subjects (Chairperson only)
     */
    public function generateCourseOutcomes(Request $request)
    {
        // Ensure only chairperson and GE coordinator can access this
        if (Auth::user()->role !== 1 && Auth::user()->role !== 4) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $validated = $request->validate([
            'generation_mode' => 'required|in:missing_only,override_all',
            'year_levels' => 'nullable|array',
            'year_levels.*' => 'string',
            'password_confirmation' => 'required_if:generation_mode,override_all|string|min:1',
        ]);

        // Verify password for override mode with detailed validation
        if ($validated['generation_mode'] === 'override_all') {
            if (empty($validated['password_confirmation'])) {
                return redirect()->back()
                    ->withErrors(['password_confirmation' => 'Password confirmation is required for override operations.'])
                    ->withInput()
                    ->with('error', 'Password verification failed. Override operation cancelled for security.');
            }
            
            if (!Hash::check($validated['password_confirmation'], Auth::user()->password)) {
                return redirect()->back()
                    ->withErrors(['password_confirmation' => 'The provided password is incorrect.'])
                    ->withInput()
                    ->with('error', 'Invalid password. Override operation cancelled for security. Please ensure you are entering your current account password.');
            }
        }

        $academicPeriodId = session('active_academic_period_id');
        if (!$academicPeriodId) {
            return redirect()->back()->with('error', 'No active academic period set.');
        }

        // Get subjects based on user's role and permissions
        if (Auth::user()->role === 1) {
            // Chairperson: Show only subjects from their course, exclude universal subjects
            $userCourseId = Auth::user()->course_id;
            if (!$userCourseId) {
                return redirect()->back()->with('error', 'No course assigned to your account.');
            }
            $subjectsQuery = Subject::where('academic_period_id', $academicPeriodId)
                ->where('course_id', $userCourseId)
                ->where('is_universal', false)
                ->where('is_deleted', false);
        } elseif (Auth::user()->role === 4) {
            // GE Coordinator: Show only universal subjects (is_universal = true)
            $subjectsQuery = Subject::where('academic_period_id', $academicPeriodId)
                ->where('is_universal', true)
                ->where('is_deleted', false);
        } else {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        // Filter by year levels if specified
        if (!empty($validated['year_levels']) && !in_array('all', $validated['year_levels'])) {
            $subjectsQuery->whereIn('year_level', $validated['year_levels']);
        }

        $subjects = $subjectsQuery->get();

        if ($subjects->isEmpty()) {
            return redirect()->back()->with('warning', 'No subjects found matching the selected criteria.');
        }

        $generatedCount = 0;
        $skippedCount = 0;
        $overriddenCount = 0;
        $totalCOsDeleted = 0;
        $limitReachedCount = 0;

        foreach ($subjects as $subject) {
            $existingCOs = $subject->courseOutcomes()->get();
            $existingCOCount = $existingCOs->count();

            // Skip if mode is "missing_only" and subject already has COs
            if ($validated['generation_mode'] === 'missing_only' && $existingCOCount > 0) {
                $skippedCount++;
                continue;
            }

            // If mode is "override_all", delete existing COs
            if ($validated['generation_mode'] === 'override_all' && $existingCOCount > 0) {
                $subject->courseOutcomes()->update([
                    'is_deleted' => true,
                    'updated_by' => Auth::id(),
                ]);
                $overriddenCount++;
                $totalCOsDeleted += $existingCOCount;
                $existingCOs = collect(); // Reset existing COs after deletion
                $existingCOCount = 0;
            }

            // Check if we've already reached the 6 CO limit
            if ($existingCOCount >= 6) {
                $limitReachedCount++;
                continue;
            }

            // Get existing CO numbers to find missing ones
            $existingCONumbers = $existingCOs->pluck('co_code')
                ->map(function ($coCode) {
                    preg_match('/CO(\d+)/i', $coCode, $matches);
                    return isset($matches[1]) ? (int)$matches[1] : null;
                })
                ->filter()
                ->toArray();

            // Find missing CO numbers (1-6)
            $missingCONumbers = [];
            for ($i = 1; $i <= 6; $i++) {
                if (!in_array($i, $existingCONumbers)) {
                    $missingCONumbers[] = $i;
                }
            }

            // Limit to maximum of 6 COs total
            $slotsAvailable = 6 - $existingCOCount;
            
            // For override mode, generate all 6 COs. For missing_only mode, generate missing COs up to limit
            if ($validated['generation_mode'] === 'override_all') {
                $cosToGenerate = range(1, 6); // Generate all 6 COs for override
            } else {
                $cosToGenerate = array_slice($missingCONumbers, 0, $slotsAvailable); // Generate only missing COs
            }

            if (empty($cosToGenerate)) {
                $limitReachedCount++;
                continue;
            }

            // Generate course outcomes for missing numbers
            foreach ($cosToGenerate as $coNumber) {
                CourseOutcomes::create([
                    'subject_id' => $subject->id,
                    'academic_period_id' => $academicPeriodId,
                    'co_code' => 'CO' . $coNumber,
                    'co_identifier' => $subject->subject_code . '.' . $coNumber,
                    'description' => 'Students have achieved 75% of the course outcomes',
                    'is_deleted' => false,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $generatedCount++;
        }

        // Create appropriate success message based on mode
        if ($validated['generation_mode'] === 'override_all') {
            $message = "⚠️ Override operation completed! ";
            $message .= "Generated new COs for {$generatedCount} subject(s). ";
            if ($overriddenCount > 0) {
                $message .= "Deleted {$totalCOsDeleted} existing COs from {$overriddenCount} subject(s). ";
            }
            if ($limitReachedCount > 0) {
                $message .= "Skipped {$limitReachedCount} subject(s) that already have 6 COs (maximum limit). ";
            }
            $message .= "All affected subjects now have standardized course outcomes.";
        } else {
            $message = "✅ Course outcomes generation completed! ";
            $message .= "Generated COs for {$generatedCount} subject(s). ";
            if ($skippedCount > 0) {
                $message .= "Skipped {$skippedCount} subject(s) that already had COs. ";
            }
            if ($limitReachedCount > 0) {
                $message .= "Skipped {$limitReachedCount} subject(s) that already have 6 COs (maximum limit). ";
            }
        }

        $message .= " Note: Each subject can have a maximum of 6 course outcomes (CO1-CO6).";

        return redirect()->back()->with('success', $message);
    }

    /**
     * Validate password for override operations (AJAX endpoint)
     */
    public function validatePassword(Request $request)
    {
        // Ensure only chairperson and GE coordinator can access this
        if (Auth::user()->role !== 1 && Auth::user()->role !== 4) {
            return response()->json(['valid' => false, 'error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $isValid = Hash::check($request->password, Auth::user()->password);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Password verified' : 'Invalid password'
        ]);
    }
}


