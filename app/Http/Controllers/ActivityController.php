<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Subject;
use App\Models\CourseOutcomes;
use App\Services\GradesFormulaService;
use App\Support\Grades\FormulaStructure;
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
        return $this->create($request);
    }
    
    // ➕ Full Create Activity Form
    public function create(Request $request)
    {
        Gate::authorize('instructor');

        $academicPeriodId = session('active_academic_period_id');

        $subjects = Subject::where('instructor_id', Auth::id())
            ->where('is_deleted', false)
            ->when($academicPeriodId, fn ($query) => $query->where('academic_period_id', $academicPeriodId))
            ->with(['course', 'department', 'academicPeriod'])
            ->orderBy('subject_code')
            ->get();

        $termLabels = [
            'prelim' => 'Prelim',
            'midterm' => 'Midterm',
            'prefinal' => 'Prefinal',
            'final' => 'Final',
        ];

        $selectedSubject = null;
        $selectedTerm = null;
        $activities = collect();
        $activityTypes = [];
        $formulaSettings = null;
        $componentStatuses = [];
        $structureDetails = collect();
        $courseOutcomes = collect();
        $alignmentSummary = [
            'missing' => 0,
            'exceeds' => 0,
            'extra' => 0,
        ];

        if ($subjects->isNotEmpty()) {
            $selectedSubjectId = $request->integer('subject_id');
            $selectedSubject = $subjects->firstWhere('id', $selectedSubjectId) ?? $subjects->first();

            $requestedTerm = $request->input('term');
            $selectedTerm = array_key_exists($requestedTerm, $termLabels) ? $requestedTerm : null;

            $selectedSubjectSemester = optional($selectedSubject->academicPeriod)->semester;
            $selectedSubjectPeriodId = $selectedSubject->academic_period_id;

            $formulaSettings = GradesFormulaService::getSettings(
                $selectedSubject->id,
                $selectedSubject->course_id,
                $selectedSubject->department_id,
                $selectedSubjectSemester,
                $selectedSubjectPeriodId,
            );
            $courseOutcomes = CourseOutcomes::where('subject_id', $selectedSubject->id)
                ->where('is_deleted', false)
                ->orderBy('co_code')
                ->get();

            $activityTypes = GradesFormulaService::getActivityTypes(
                $selectedSubject->id,
                $selectedSubject->course_id,
                $selectedSubject->department_id,
                $selectedSubjectSemester,
                $selectedSubjectPeriodId,
            );

            $structureDetails = collect($formulaSettings['meta']['weight_details'] ?? [])
                ->map(function ($detail) {
                    $activityType = mb_strtolower($detail['activity_type']);
                    $baseType = FormulaStructure::baseActivityType($activityType);

                    return [
                        'activity_type' => $activityType,
                        'label' => $detail['label'] ?? FormulaStructure::formatLabel($activityType),
                        'weight_percent' => $detail['relative_weight_percent'] ?? $detail['weight_percent'],
                        'overall_weight_percent' => $detail['weight_percent'],
                        'relative_weight_percent' => $detail['relative_weight_percent'] ?? $detail['weight_percent'],
                        'max_assessments' => $detail['max_assessments'] ?? null,
                        'base_type' => $baseType,
                    ];
                })
                ->values();

            $allowedTypes = $structureDetails->pluck('activity_type')->all();

            $existingCount = Activity::where('subject_id', $selectedSubject->id)
                ->where('is_deleted', false)
                ->count();

            if ($existingCount === 0) {
                foreach (array_keys($termLabels) as $termName) {
                    $this->getOrCreateDefaultActivities($selectedSubject->id, $termName);
                }
            }

            $activities = Activity::where('subject_id', $selectedSubject->id)
                ->where('is_deleted', false)
                ->when($selectedTerm, fn ($query) => $query->where('term', $selectedTerm))
                ->orderBy('term')
                ->orderBy('type')
                ->orderBy('created_at')
                ->get();

            $groupedCounts = Activity::selectRaw('term, LOWER(type) as type, COUNT(*) as total')
                ->where('subject_id', $selectedSubject->id)
                ->where('is_deleted', false)
                ->groupBy('term', 'type')
                ->get()
                ->groupBy('term');

            $componentStatuses = [];

            foreach ($termLabels as $termKey => $termLabel) {
                $termCounts = $groupedCounts->get($termKey, collect());
                $termComponents = [];

                foreach ($structureDetails as $component) {
                    $match = $termCounts->firstWhere('type', $component['activity_type']);
                    $actualCount = $match ? (int) $match->total : 0;
                    $minRequired = $component['weight_percent'] > 0 ? 1 : 0;

                    if ($component['base_type'] === 'exam') {
                        $minRequired = 1;
                    }

                    $maxAllowed = $component['max_assessments'];
                    $isMissing = $minRequired > 0 && $actualCount < $minRequired;
                    $exceeds = $maxAllowed !== null && $actualCount > $maxAllowed;

                    if ($isMissing) {
                        $alignmentSummary['missing']++;
                    } elseif ($exceeds) {
                        $alignmentSummary['exceeds']++;
                    }

                    $termComponents[] = [
                        'type' => $component['activity_type'],
                        'label' => $component['label'],
                        'weight' => $component['weight_percent'],
                        'overall_weight' => $component['overall_weight_percent'],
                        'count' => $actualCount,
                        'min_required' => $minRequired,
                        'max_allowed' => $maxAllowed,
                        'status' => $isMissing ? 'missing' : ($exceeds ? 'exceeds' : 'ok'),
                    ];
                }

                $extras = $termCounts
                    ->filter(fn ($row) => ! in_array($row->type, $allowedTypes, true))
                    ->map(fn ($row) => [
                        'type' => $row->type,
                        'count' => (int) $row->total,
                    ])
                    ->values()
                    ->all();

                if (! empty($extras)) {
                    $alignmentSummary['extra'] += count($extras);
                }

                $componentStatuses[$termKey] = [
                    'label' => $termLabel,
                    'components' => $termComponents,
                    'extras' => $extras,
                ];
            }
        }

        $isAligned = $alignmentSummary['missing'] === 0
            && $alignmentSummary['exceeds'] === 0
            && $alignmentSummary['extra'] === 0;

        return view('instructor.activities.create', [
            'subjects' => $subjects,
            'selectedSubject' => $selectedSubject,
            'selectedTerm' => $selectedTerm,
            'activities' => $activities,
            'activityTypes' => $activityTypes,
            'formulaSettings' => $formulaSettings,
            'structureDetails' => $structureDetails,
            'termLabels' => $termLabels,
            'componentStatuses' => $componentStatuses,
            'alignmentSummary' => $alignmentSummary,
            'isAligned' => $isAligned,
            'courseOutcomes' => $courseOutcomes,
        ]);
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
        return redirect()
            ->route('instructor.activities.create', [
                'subject_id' => $subject->id,
                'term' => $request->term,
            ])
            ->with('info', 'Use the Manage Activities screen to add new assessments.');
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
    
    $subject = Subject::with('academicPeriod')->findOrFail($validated['subject_id']);
        $academicPeriodId = session('active_academic_period_id');
    
        if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
            abort(403, 'This subject does not belong to the active academic period.');
        }

        $subjectSemester = optional($subject->academicPeriod)->semester;
        $formulaSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            $subjectSemester,
            $subject->academic_period_id,
        );
        $allowedTypes = GradesFormulaService::getActivityTypes(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            $subjectSemester,
            $subject->academic_period_id,
        );
        $normalizedType = mb_strtolower($validated['type']);

        $allowedNormalized = array_map('mb_strtolower', $allowedTypes);
        if (! in_array($normalizedType, $allowedNormalized, true)) {
            throw ValidationError::withMessages([
                'type' => 'Selected activity type is not allowed for the active grade formula.',
            ]);
        }

        $maxAssessmentsMap = $formulaSettings['meta']['max_assessments'] ?? [];
        $baseType = FormulaStructure::baseActivityType($normalizedType);
        $maxAllowed = $maxAssessmentsMap[$normalizedType] ?? $maxAssessmentsMap[$baseType] ?? null;

        if ($maxAllowed !== null) {
            $existingCount = Activity::where('subject_id', $subject->id)
                ->where('term', $request->term)
                ->where('type', $normalizedType)
                ->where('is_deleted', false)
                ->count();

            if ($existingCount >= (int) $maxAllowed) {
                throw ValidationError::withMessages([
                    'type' => 'You have reached the maximum number of assessments for this component.',
                ]);
            }
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

    public function realign(Request $request)
    {
        Gate::authorize('instructor');

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'nullable|in:prelim,midterm,prefinal,final',
        ]);

        $subject = Subject::with('academicPeriod')
            ->where('id', $validated['subject_id'])
            ->where('instructor_id', Auth::id())
            ->where('is_deleted', false)
            ->firstOrFail();

        $academicPeriodId = session('active_academic_period_id');
        if ($academicPeriodId && $subject->academic_period_id !== (int) $academicPeriodId) {
            abort(403, 'This subject does not belong to the active academic period.');
        }

        $summary = $this->realignActivitiesToFormula($subject, $validated['term'] ?? null, Auth::id());

        $messageSegments = [];
        if ($summary['created'] > 0) {
            $messageSegments[] = $summary['created'] . ' created';
        }
        if ($summary['archived'] > 0) {
            $messageSegments[] = $summary['archived'] . ' archived';
        }

        $message = 'Activities realigned to match the active formula.';
        if (! empty($messageSegments)) {
            $message .= ' (' . implode(', ', $messageSegments) . ')';
        }

        $routeParameters = ['subject_id' => $subject->id];
        if (! empty($validated['term'])) {
            $routeParameters['term'] = $validated['term'];
        }

        return redirect()->route('instructor.activities.create', $routeParameters)
            ->with('success', $message);
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

            $subject = $activity->subject->loadMissing('academicPeriod');

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

            $subjectSemester = optional($subject->academicPeriod)->semester;
            $formulaSettings = GradesFormulaService::getSettings(
                $subject->id,
                $subject->course_id,
                $subject->department_id,
                $subjectSemester,
                $subject->academic_period_id,
            );
            $allowedTypes = GradesFormulaService::getActivityTypes(
                $subject->id,
                $subject->course_id,
                $subject->department_id,
                $subjectSemester,
                $subject->academic_period_id,
            );
            $normalizedType = mb_strtolower($validated['type']);
            $allowedNormalized = array_map('mb_strtolower', $allowedTypes);

            if (! in_array($normalizedType, $allowedNormalized, true)) {
                throw ValidationError::withMessages([
                    'type' => 'Selected activity type is not allowed for the active grade formula.',
                ]);
            }

            $maxAssessmentsMap = $formulaSettings['meta']['max_assessments'] ?? [];
            $baseType = FormulaStructure::baseActivityType($normalizedType);
            $maxAllowed = $maxAssessmentsMap[$normalizedType] ?? $maxAssessmentsMap[$baseType] ?? null;

            if ($maxAllowed !== null) {
                $existingCount = Activity::where('subject_id', $subject->id)
                    ->where('term', $activity->term)
                    ->where('type', $normalizedType)
                    ->where('is_deleted', false)
                    ->when($activity->id, fn ($q) => $q->where('id', '!=', $activity->id))
                    ->count();

                if ($existingCount >= (int) $maxAllowed) {
                    throw ValidationError::withMessages([
                        'type' => 'You have reached the maximum number of assessments for this component.',
                    ]);
                }
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
