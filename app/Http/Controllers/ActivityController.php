<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Subject;
use App\Models\AcademicPeriod;
use App\Services\GradesFormulaService;
use App\Traits\ActivityManagementTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException as ValidationError;

class ActivityController extends Controller
{
    use ActivityManagementTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    // 🗂 List Activities for an Instructor's Subjects
    public function index(Request $request)
    {
        Gate::authorize('instructor');
    
        $academicPeriodId = session('active_academic_period_id');
    
        // Fetch instructor's subjects for current academic period
        $subjects = Subject::where('instructor_id', Auth::id())
            ->where('is_deleted', false)
            ->when($academicPeriodId, fn($q) => $q->where('academic_period_id', $academicPeriodId))
            ->get();

    $activities = collect();
    $activityTypes = [];

        if ($request->filled('subject_id')) {
            $subject = Subject::findOrFail($request->subject_id);
    
            if ($subject->instructor_id !== Auth::id()) {
                abort(403, 'Unauthorized access to subject.');
            }
    
            if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
                abort(403, 'This subject does not belong to the current academic period.');
            }
    
            // Auto-generate activities if none exist
            $existing = Activity::where('subject_id', $subject->id)
                ->where('is_deleted', false)
                ->count();
    
            if ($existing === 0) {
                foreach (['prelim', 'midterm', 'prefinal', 'final'] as $termName) {
                    $this->getOrCreateDefaultActivities($subject->id, $termName);
                }
            }
    
            // Filter activities by subject (and term if present)
            $activities = Activity::where('subject_id', $subject->id)
                ->where('is_deleted', false)
                ->when($request->filled('term'), fn($q) => $q->where('term', $request->term))
                ->orderBy('term')
                ->orderBy('type')
                ->orderBy('created_at')
                ->get();

            $activityTypes = GradesFormulaService::getActivityTypes($subject->id, $subject->course_id, $subject->department_id);
        }

        return view('instructor.activities.index', compact('subjects', 'activities', 'activityTypes'));
    }
    
    // ➕ Full Create Activity Form
    public function create()
    {

        Gate::authorize('instructor');

        $subjects = Subject::where('instructor_id', Auth::id())
            ->where('is_deleted', false)
            ->get();

        $academicPeriods = AcademicPeriod::where('is_deleted', false)
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester')
            ->get();

    $activityTypes = GradesFormulaService::getActivityTypes(null, null, Auth::user()->department_id);

        return view('instructor.activities.create', compact('subjects', 'academicPeriods', 'activityTypes'));
    }

    // 🎯 Quick Add Form from inside Manage Grades
    public function addActivity(Request $request)
    {
        Gate::authorize('instructor');
    
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,prefinal,final',
        ]);
    
        $subject = Subject::findOrFail($request->subject_id);
        $academicPeriodId = session('active_academic_period_id');
    
        if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
            abort(403, 'This subject does not belong to the current academic period.');
        }
    
        $courseOutcomes = \App\Models\CourseOutcomes::where('subject_id', $subject->id)
            ->where('is_deleted', false)
            ->get();
        return view('instructor.activities.add', [
            'subject' => $subject,
            'term' => $request->term,
            'courseOutcomes' => $courseOutcomes,
            'activityTypes' => GradesFormulaService::getActivityTypes($subject->id, $subject->course_id, $subject->department_id),
        ]);
    }

    // 💾 Store Activity (both standard and inline)
    public function store(Request $request)
    {
        Gate::authorize('instructor');
    
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,prefinal,final',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'number_of_items' => 'required|integer|min:1',
            'course_outcome_id' => 'nullable|exists:course_outcomes,id',
        ]);
    
        $subject = Subject::findOrFail($validated['subject_id']);
        $academicPeriodId = session('active_academic_period_id');
    
        if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
            abort(403, 'This subject does not belong to the active academic period.');
        }

    $allowedTypes = GradesFormulaService::getActivityTypes($subject->id, $subject->course_id, $subject->department_id);
        $normalizedType = mb_strtolower($validated['type']);

        $allowedNormalized = array_map('mb_strtolower', $allowedTypes);
        if (! in_array($normalizedType, $allowedNormalized, true)) {
            throw ValidationError::withMessages([
                'type' => 'Selected activity type is not allowed for the active grade formula.',
            ]);
        }
    
        Activity::create([
            'subject_id' => $subject->id,
            'term' => $request->term,
            'type' => $normalizedType,
            'title' => $request->title,
            'number_of_items' => $request->number_of_items,
            'course_outcome_id' => $request->course_outcome_id,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    
        return redirect()->route('instructor.grades.index', [
            'subject_id' => $subject->id,
            'term' => $request->term,
        ])->with('success', 'Activity created successfully.');
    }    

    // 🔁 Update Activity
    public function update(Request $request, Activity $activity)
    {
        Gate::authorize('instructor');

        try {
            $validated = $request->validate([
                'type' => 'required|string',
                'title' => 'required|string|max:255',
                'number_of_items' => 'required|integer|min:1',
                'course_outcome_id' => 'nullable|exists:course_outcomes,id',
            ]);

            $subject = $activity->subject;

            // Authorization check
            if ($subject->instructor_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this activity.'
                ], 403);
            }

            // Academic period check
            $academicPeriodId = session('active_academic_period_id');
            if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This subject does not belong to the current academic period.'
                ], 403);
            }

            $allowedTypes = GradesFormulaService::getActivityTypes($subject->id, $subject->course_id, $subject->department_id);
            $normalizedType = mb_strtolower($validated['type']);
            $allowedNormalized = array_map('mb_strtolower', $allowedTypes);

            if (! in_array($normalizedType, $allowedNormalized, true)) {
                throw ValidationError::withMessages([
                    'type' => 'Selected activity type is not allowed for the active grade formula.',
                ]);
            }

            $activity->update([
                'type' => $normalizedType,
                'title' => $validated['title'],
                'number_of_items' => $validated['number_of_items'],
                'course_outcome_id' => $validated['course_outcome_id'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Activity updated successfully',
                    'data' => [
                        'activity' => $activity->fresh()
                    ]
                ]);
            }

            return redirect()->route('instructor.activities.index', [
                'subject_id' => $activity->subject_id,
                'term' => $activity->term,
            ])->with('success', 'Activity updated successfully.');

    } catch (ValidationError $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the activity',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // 🗑 Soft Delete Activity
    public function delete($id)
    {
        Gate::authorize('instructor');

        $activity = Activity::where('id', $id)
            ->where('is_deleted', false)
            ->firstOrFail();

        $activity->update([
            'is_deleted' => true,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Activity deleted successfully.');
    }
}
