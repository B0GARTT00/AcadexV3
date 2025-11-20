<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Department;
use App\Models\FinalGrade;
use App\Models\Score;
use App\Models\Subject;
use App\Models\UserLog;
use App\Models\User;
use App\Models\GradesFormula;
use App\Models\StructureTemplate;
use App\Models\TermGrade;
use App\Services\GradesFormulaService;
use App\Support\Grades\FormulaStructure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================
    // Departments
    // ============================

    public function departments()
    {
        Gate::authorize('admin');

        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();

        return view('admin.departments', compact('departments'));
    }

    public function createDepartment()
    {
        Gate::authorize('admin');
        return view('admin.create-department');
    }

    public function storeDepartment(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'department_code' => 'required|string|max:50',
            'department_description' => 'required|string|max:255',
        ]);

        Department::create([
            'department_code' => $request->department_code,
            'department_description' => $request->department_description,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.departments')->with('success', 'Department added successfully.');
    }

    // ============================
    // Courses
    // ============================

    public function courses()
    {
        Gate::authorize('admin');
    
        $courses = Course::where('is_deleted', false)
            ->orderBy('course_code')
            ->get();
    
        // Pass departments for the modal
        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();
    
        return view('admin.courses', compact('courses', 'departments'));
    }
    

    public function createCourse()
    {
        Gate::authorize('admin');

        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();

        return view('admin.create-course', compact('departments'));
    }

    public function storeCourse(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'course_code' => 'required|string|max:50',
            'course_description' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        Course::create([
            'course_code' => $request->course_code,
            'course_description' => $request->course_description,
            'department_id' => $request->department_id,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.courses')->with('success', 'Course added successfully.');
    }

    // ============================
    // Subjects
    // ============================

    public function subjects()
    {
        Gate::authorize('admin');

        $subjects = Subject::with(['department', 'course', 'academicPeriod'])
            ->where('is_deleted', false)
            ->orderBy('subject_code')
            ->get();

        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();

        $courses = Course::where('is_deleted', false)
            ->orderBy('course_code')
            ->get();

        $academicPeriods = AcademicPeriod::orderBy('academic_year', 'desc')
            ->orderBy('semester')
            ->get();

        return view('admin.subjects', compact('subjects', 'departments', 'courses', 'academicPeriods'));
    }

    public function createSubject()
    {
        Gate::authorize('admin');

        $departments = Department::where('is_deleted', false)->orderBy('department_code')->get();
        $courses = Course::where('is_deleted', false)->orderBy('course_code')->get();
        $academicPeriods = AcademicPeriod::orderBy('academic_year', 'desc')->orderBy('semester')->get();

        return view('admin.create-subject', compact('departments', 'courses', 'academicPeriods'));
    }

    public function storeSubject(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'subject_code' => 'required|string|max:255|unique:subjects,subject_code',
            'subject_description' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:6',
            'year_level' => 'required|integer|min:1|max:5',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'department_id' => 'required|exists:departments,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        Subject::create([
            'subject_code' => $request->subject_code,
            'subject_description' => $request->subject_description,
            'units' => $request->units,
            'year_level' => $request->year_level,
            'academic_period_id' => $request->academic_period_id,
            'department_id' => $request->department_id,
            'course_id' => $request->course_id,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.subjects')->with('success', 'Subject added successfully.');
    }

    // ============================
    // Academic Periods (legacy fallback view)
    // ============================

    public function academicPeriods()
    {
        Gate::authorize('admin');

        $periods = AcademicPeriod::orderBy('academic_year', 'desc')->orderBy('semester')->get();
        return view('admin.academic-periods', compact('periods'));
    }

    public function viewUserLogs(Request $request)
    {
        Gate::authorize('admin');

        $dateToday = now()->timezone(config('app.timezone'))->format('Y-m-d');
        $selectedDate = $request->input('date', $dateToday);

        $userLogs = UserLog::whereDate('created_at', $selectedDate)->get();

        return view('admin.user-logs', compact('userLogs', 'dateToday', 'selectedDate'));
    }

    public function gradesFormula()
    {
        Gate::authorize('admin');

        $request = request();

        $requiresSelection = ! $request->filled('academic_period_id');

        if ($requiresSelection) {
            $periods = AcademicPeriod::where('is_deleted', false)
                ->orderByDesc('academic_year')
                ->orderByRaw("FIELD(semester, '1st', '2nd', 'Summer')")
                ->get();

            if ($periods->isEmpty()) {
                return view('admin.grades-formula-select-period', [
                    'academicPeriods' => collect(),
                ]);
            }

            return view('admin.grades-formula-select-period', [
                'academicPeriods' => $periods,
            ]);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
            $selectedSemester = $periodContext['semester'] ?? null;
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $departments = Department::where('is_deleted', false)
            ->with(['courses' => function ($query) use ($selectedAcademicPeriodId) {
                $query->where('is_deleted', false)
                    ->with(['subjects' => function ($subjectQuery) use ($selectedAcademicPeriodId) {
                        $subjectQuery->where('is_deleted', false)
                            ->when($selectedAcademicPeriodId, fn ($q, $periodId) => $q->where('academic_period_id', $periodId))
                            ->select('id', 'course_id', 'subject_code', 'subject_description', 'academic_period_id');
                    }])
                    ->select('id', 'department_id', 'course_code', 'course_description', 'is_deleted');
            }])
            ->orderBy('department_code')
            ->get();

        $subjectIds = $departments
            ->flatMap(fn (Department $department) => ($department->courses ?? collect())->flatMap(fn (Course $course) => $course->subjects ?? collect()))
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        $subjectsWithGrades = collect();

        if ($subjectIds->isNotEmpty()) {
            $termGradeSubjects = TermGrade::whereIn('subject_id', $subjectIds)
                ->where('is_deleted', false)
                ->when($selectedAcademicPeriodId, fn ($query, $periodId) => $query->where('academic_period_id', $periodId))
                ->pluck('subject_id');

            $finalGradeSubjects = FinalGrade::whereIn('subject_id', $subjectIds)
                ->where('is_deleted', false)
                ->when($selectedAcademicPeriodId, fn ($query, $periodId) => $query->where('academic_period_id', $periodId))
                ->pluck('subject_id');

            $activitySubjects = Activity::whereIn('subject_id', $subjectIds)
                ->where('is_deleted', false)
                ->whereHas('scores', fn ($query) => $query->where('is_deleted', false))
                ->pluck('subject_id');

            $subjectsWithGrades = collect()
                ->merge($termGradeSubjects)
                ->merge($finalGradeSubjects)
                ->merge($activitySubjects)
                ->unique()
                ->values();
        }

        $subjectsWithGradesMap = $subjectsWithGrades->flip();

        $departments = $departments->map(function (Department $department) use ($subjectsWithGradesMap) {
            $courses = $department->courses ?? collect();

            $department->setRelation('courses', $courses->map(function (Course $course) use ($subjectsWithGradesMap) {
                $subjects = $course->subjects ?? collect();

                $course->setRelation('subjects', $subjects->map(function (Subject $subject) use ($subjectsWithGradesMap) {
                    $subject->setAttribute('has_recorded_grades', $subjectsWithGradesMap->has($subject->id));
                    return $subject;
                })->values());

                return $course;
            })->values());

            return $department;
        });

        $departmentIds = $departments->pluck('id');

        $fallbacks = $this->applyPeriodFilters(
            GradesFormula::whereIn('department_id', $departmentIds)
                ->where('scope_level', 'department')
                ->where('is_department_fallback', true),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->get()
            ->keyBy('department_id');

        $missingFallbacks = $departmentIds->diff($fallbacks->keys());

        foreach ($missingFallbacks as $departmentId) {
            $department = $departments->firstWhere('id', $departmentId);
            if ($department) {
                $fallbacks->put($departmentId, $this->ensureDepartmentFallback($department, $periodContext));
            }
        }

        $departmentCatalogs = GradesFormula::with('weights')
            ->whereIn('department_id', $departmentIds)
            ->where('scope_level', 'department')
            ->get()
            ->groupBy('department_id');

        $courseFormulas = $this->applyPeriodFilters(
            GradesFormula::whereNotNull('course_id')
                ->where('scope_level', 'course'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->get(['id', 'course_id', 'label'])
            ->keyBy('course_id');

        $subjectFormulaQuery = GradesFormula::where('scope_level', 'subject')
            ->whereNotNull('subject_id');

        if ($subjectIds->isNotEmpty()) {
            $subjectFormulaQuery->whereIn('subject_id', $subjectIds);
        }

        $subjectFormulas = $this->applyPeriodFilters(
            $subjectFormulaQuery,
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->get(['id', 'subject_id', 'label'])
            ->keyBy('subject_id');

        $globalFormula = $this->getGlobalFormula();

        $departmentsSummary = $departments->map(function (Department $department) use (
            $fallbacks,
            $departmentCatalogs,
            $courseFormulas,
            $subjectFormulas,
            $globalFormula,
            $selectedSemester,
            $selectedAcademicPeriodId
        ) {
            $courses = $department->courses;

            $courseCount = $courses->count();
            $coursesWithFormula = $courses->filter(fn ($course) => $courseFormulas->has($course->id))->count();

            $subjects = $courses->flatMap(fn (Course $course) => $course->subjects ?? collect());
            $subjectCount = $subjects->count();
            $subjectsWithFormula = $subjects->filter(fn ($subject) => $subjectFormulas->has($subject->id))->count();

            $fallback = $fallbacks->get($department->id) ?? $globalFormula;
            $catalog = $departmentCatalogs->get($department->id, collect());
            $nonFallbackCount = $catalog->filter(fn ($formula) => ! $formula->is_department_fallback)->count();
            $matchingCatalogCount = $catalog->filter(function ($formula) use ($selectedSemester, $selectedAcademicPeriodId) {
                if ($formula->is_department_fallback) {
                    return false;
                }

                $semesterMatches = $selectedSemester === null
                    ? $formula->semester === null
                    : $formula->semester === $selectedSemester;

                $periodMatches = $selectedAcademicPeriodId === null
                    ? $formula->academic_period_id === null
                    : (int) $formula->academic_period_id === (int) $selectedAcademicPeriodId;

                return $semesterMatches && $periodMatches;
            })->count();

            if ($matchingCatalogCount > 0) {
                $status = 'custom';
                $scopeText = 'Catalog ready with formulas for this period.';
            } elseif ($nonFallbackCount > 0) {
                $status = 'custom';
                $scopeText = 'Catalog formulas available in other periods.';
            } else {
                $status = 'default';
                $scopeText = 'Using baseline department formula.';
            }

            return [
                'department' => $department,
                'catalog_count' => $nonFallbackCount,
                'catalog_available_count' => $matchingCatalogCount,
                'missing_course_count' => max($courseCount - $coursesWithFormula, 0),
                'missing_subject_count' => max($subjectCount - $subjectsWithFormula, 0),
                'formula_label' => $fallback->label ?? $globalFormula->label,
                'formula_scope' => 'Department Baseline',
                'status' => $status,
                'scope_text' => $scopeText,
            ];
        });

        $structureCatalog = collect($this->getStructureCatalog())
            ->map(function (array $entry, string $key) {
                $normalized = FormulaStructure::fromPercentPayload($entry['structure'] ?? []);
                
                // Build a better weight display that shows hierarchical structure
                $weights = $this->buildStructureWeightDisplay($normalized);

                return [
                    'id' => $entry['id'] ?? null,
                    'template_key' => $entry['template_key'] ?? $key,
                    'key' => $key,
                    'label' => $entry['label'] ?? FormulaStructure::formatLabel($key),
                    'description' => $entry['description'] ?? '',
                    'weights' => $weights,
                    'structure' => $entry['structure'] ?? [],
                    'is_custom' => (bool) ($entry['is_custom'] ?? false),
                    'is_system_default' => (bool) ($entry['is_system_default'] ?? false),
                ];
            })
            ->values();

        // Fetch global formulas
        $globalFormulasList = GradesFormula::with('weights')
            ->where('scope_level', 'global')
            ->whereNull('department_id')
            ->whereNull('course_id')
            ->whereNull('subject_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.grades-formula-wildcards', [
            'globalFormula' => $globalFormula,
            'globalFormulasList' => $globalFormulasList,
            'departmentsSummary' => $departmentsSummary,
            'departments' => $departments,
            'departmentFallbacks' => $fallbacks,
            'departmentCatalogs' => $departmentCatalogs,
            'courseFormulas' => $courseFormulas,
            'subjectFormulas' => $subjectFormulas,
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
            'structureCatalog' => $structureCatalog,
        ]);
    }

    // REMOVED: bulkApplyDepartmentFormula() method
    // This method was removed as part of deprecating the Departments tab.
    // The Formulas section now provides better template management functionality.

    public function applyDepartmentTemplate(Request $request, Department $department)
    {
        Gate::authorize('admin');

        if ($department->is_deleted) {
            abort(404);
        }

        $structureDefinitions = FormulaStructure::getAllStructureDefinitions();
        $templateKeys = array_keys($structureDefinitions);

        $validated = $request->validate([
            'template_key' => ['required', 'string', Rule::in($templateKeys)],
        ]);

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $academicPeriods = $periodContext['academic_periods'];

        $structureKey = $validated['template_key'];
        $structure = $this->resolveStructureConfigForKey($structureKey, $structureDefinitions);
        $structureErrors = FormulaStructure::validate($structure);

        if (! empty($structureErrors)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => implode(' ', $structureErrors),
                ], 422);
            }

            return back()
                ->withErrors(['template_key' => implode(' ', $structureErrors)])
                ->withInput();
        }

        $weightInserts = collect(FormulaStructure::flattenWeights($structure))
            ->map(fn (array $node) => [
                'activity_type' => $node['activity_type'],
                'weight' => $node['weight'],
            ])
            ->values();

        $fallback = $this->ensureDepartmentFallback($department, $periodContext);
        $fallback->loadMissing('weights');

        DB::transaction(function () use ($fallback, $structureKey, $structure, $weightInserts) {
            $fallback->structure_type = $structureKey;
            $fallback->structure_config = $structure;
            $fallback->save();

            $fallback->weights()->delete();

            if ($weightInserts->isNotEmpty()) {
                $fallback->weights()->createMany($weightInserts->all());
            }
        });

        GradesFormulaService::flushCache();

        $fallback = $fallback->fresh('weights');

        $periodLookup = collect($academicPeriods ?? [])->keyBy('id');

        $contextParts = [];
        if ($fallback->academic_period_id) {
            $period = $periodLookup->get($fallback->academic_period_id);
            if ($period) {
                $contextParts[] = trim($period->academic_year ?? '') !== ''
                    ? $period->academic_year
                    : 'Academic Period #' . $fallback->academic_period_id;
                if (! empty($period->semester)) {
                    $contextParts[] = trim($period->semester) . ' Semester';
                }
            } else {
                $contextParts[] = 'Academic Period #' . $fallback->academic_period_id;
            }
        }

        if ($fallback->academic_period_id === null && $fallback->semester) {
            $contextParts[] = trim($fallback->semester) . ' Semester';
        }

        if (empty($contextParts)) {
            $contextParts[] = 'Applies to all periods';
        }

        $contextLabel = implode(' · ', array_filter($contextParts));

        $weightDisplay = collect($fallback->weight_map)
            ->map(fn ($weight, $type) => [
                'type' => strtoupper($type),
                'percent' => number_format($weight * 100, 0),
            ])
            ->values()
            ->all();

        $structureDefinitions = FormulaStructure::getAllStructureDefinitions();
        $structureLabel = $structureDefinitions[$fallback->structure_type]['label']
            ?? FormulaStructure::formatLabel($fallback->structure_type ?? 'template');

        $payload = [
            'id' => $fallback->id,
            'label' => $fallback->label,
            'base_score' => $fallback->base_score,
            'scale_multiplier' => $fallback->scale_multiplier,
            'passing_grade' => $fallback->passing_grade,
            'is_fallback' => true,
            'context_match' => $this->formulaMatchesContext($fallback, $selectedSemester, $selectedAcademicPeriodId),
            'context_label' => $contextLabel,
            'semester' => $fallback->semester,
            'academic_period_id' => $fallback->academic_period_id,
            'weights' => $weightDisplay,
            'edit_url' => route('admin.gradesFormula.edit.department', array_merge([
                'department' => $department->id,
            ], $this->formulaQueryParams())),
            'updated_at' => optional($fallback->updated_at)->diffForHumans() ?? 'Just now',
            'structure_type' => $fallback->structure_type,
            'structure_label' => $structureLabel,
        ];

        $departmentLabel = trim($department->department_description ?? 'Department');
        $message = sprintf(
            'Applied the %s template to %s\'s baseline formula.',
            $structureLabel,
            $departmentLabel !== '' ? $departmentLabel : 'the department'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'formula' => $payload,
            ]);
        }

        return redirect()
            ->route('admin.gradesFormula', $this->formulaQueryParams())
            ->with('success', $message);
    }

    public function gradesFormulaDefault()
    {
        Gate::authorize('admin');

        $defaultFormula = $this->getGlobalFormula();

        $structurePayload = $this->prepareStructurePayload($defaultFormula);

        return view('admin.grades-formula-form', [
            'context' => 'default',
            'department' => null,
            'course' => null,
            'subject' => null,
            'formula' => $defaultFormula,
            'fallbackFormula' => $defaultFormula,
            'structurePayload' => $structurePayload,
            'structureCatalog' => $this->getStructureCatalog(),
            'defaultFormula' => $defaultFormula,
        ]);
    }

    public function gradesFormulaDepartment(Department $department)
    {
        Gate::authorize('admin');

        if ($department->is_deleted) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $department->load(['courses' => function ($query) use ($selectedAcademicPeriodId) {
            $query->where('is_deleted', false)
                ->withCount(['subjects' => function ($subjectQuery) use ($selectedAcademicPeriodId) {
                    $subjectQuery->where('is_deleted', false)
                        ->when($selectedAcademicPeriodId, fn ($q, $periodId) => $q->where('academic_period_id', $periodId));
                }])
                ->with(['subjects' => function ($subjectQuery) use ($selectedAcademicPeriodId) {
                    $subjectQuery->where('is_deleted', false)
                        ->when($selectedAcademicPeriodId, fn ($q, $periodId) => $q->where('academic_period_id', $periodId))
                        ->select('id', 'course_id', 'subject_code', 'subject_description', 'academic_period_id');
                }])
                ->orderBy('course_code');
        }]);

        $fallbackFormula = $this->ensureDepartmentFallback($department, $periodContext);
        $fallbackFormula->loadMissing('weights');

        $departmentFormulas = $this->applyPeriodFilters(
            GradesFormula::with('weights')
                ->where('department_id', $department->id)
                ->where('scope_level', 'department'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->orderByDesc('is_department_fallback')
            ->orderBy('label')
            ->get();

        if ($departmentFormulas->isEmpty()) {
            $departmentFormulas = collect([$fallbackFormula]);
        }

        $catalogFormulas = $departmentFormulas->filter(fn ($formula) => ! $formula->is_department_fallback);
        $catalogCount = $catalogFormulas->count();

        $globalFormula = $this->getGlobalFormula();

        $courseFormulas = $this->applyPeriodFilters(
            GradesFormula::whereIn('course_id', $department->courses->pluck('id'))
                ->where('scope_level', 'course'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->get(['id', 'course_id', 'label'])
            ->keyBy('course_id');

        $subjectIds = $department->courses->flatMap(fn (Course $course) => $course->subjects ?? collect())->pluck('id');
        $subjectFormulaIds = $this->applyPeriodFilters(
            GradesFormula::whereIn('subject_id', $subjectIds)
                ->where('scope_level', 'subject'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->pluck('subject_id')
            ->toArray();

        $courseSummaries = $department->courses->map(function (Course $course) use ($courseFormulas, $subjectFormulaIds, $fallbackFormula, $globalFormula) {
            $subjects = $course->subjects ?? collect();
            $subjectIds = $subjects->pluck('id');
            $subjectCount = $course->subjects_count ?? $subjectIds->count();
            $subjectsWithFormula = $subjectIds->filter(fn ($subjectId) => in_array($subjectId, $subjectFormulaIds))->count();

            $courseFormula = $courseFormulas->get($course->id);
            $hasCourseFormula = (bool) $courseFormula;

            if ($hasCourseFormula) {
                $formulaScope = 'Course Formula';
                $formulaLabel = $courseFormula->label;
                $status = 'custom';
                $scopeText = 'Course formula applied.';
            } elseif ($fallbackFormula) {
                $formulaScope = 'Department Baseline';
                $formulaLabel = $fallbackFormula->label;
                $status = 'default';
                $scopeText = 'Using department baseline formula.';
            } else {
                $formulaScope = 'System Default Formula';
                $formulaLabel = $globalFormula->label;
                $status = 'default';
                $scopeText = 'Using system default.';
            }

            return [
                'course' => $course,
                'has_formula' => $hasCourseFormula,
                'missing_subject_count' => max($subjectCount - $subjectsWithFormula, 0),
                'formula_label' => $formulaLabel,
                'formula_scope' => $formulaScope,
                'status' => $status,
                'scope_text' => $scopeText,
            ];
        });

        return view('admin.grades-formula-department', [
            'department' => $department,
            'departmentFallback' => $fallbackFormula,
            'departmentFormulas' => $departmentFormulas,
            'catalogFormulas' => $catalogFormulas,
            'globalFormula' => $globalFormula,
            'courseSummaries' => $courseSummaries,
            'needsDepartmentFormula' => $catalogCount === 0,
            'catalogCount' => $catalogCount,
            'catalogTotal' => $departmentFormulas->count(),
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
    }

    public function gradesFormulaCourse(Department $department, Course $course)
    {
        Gate::authorize('admin');

        if ($department->is_deleted || $course->is_deleted || $course->department_id !== $department->id) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $course->load(['subjects' => function ($query) use ($selectedAcademicPeriodId) {
            $query->where('is_deleted', false)
                ->when($selectedAcademicPeriodId, fn ($q, $periodId) => $q->where('academic_period_id', $periodId))
                ->orderBy('subject_code')
                ->select('id', 'course_id', 'subject_code', 'subject_description', 'department_id', 'academic_period_id', 'is_deleted');
        }]);

        $departmentFallback = $this->ensureDepartmentFallback($department, $periodContext);
        $departmentFallback->loadMissing('weights');

        $courseFormulaQuery = GradesFormula::with('weights')
            ->where('course_id', $course->id)
            ->where('scope_level', 'course');

        $courseFormulaQuery = $this->applyPeriodFilters($courseFormulaQuery, $selectedSemester, $selectedAcademicPeriodId);

        if ($selectedAcademicPeriodId) {
            $courseFormulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
        } else {
            $courseFormulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
        }

        if ($selectedSemester) {
            $courseFormulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
        } else {
            $courseFormulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
        }

        $courseFormula = $courseFormulaQuery->first();

        $globalFormula = $this->getGlobalFormula();

        $subjects = $course->subjects ?? collect();

        $subjectSummaries = $subjects->map(function (Subject $subject) use ($selectedSemester, $selectedAcademicPeriodId, $globalFormula) {
            $settings = GradesFormulaService::getSettings(
                $subject->id,
                $subject->course_id,
                $subject->department_id,
                $selectedSemester,
                $selectedAcademicPeriodId
            );

            $meta = $settings['meta'] ?? [];
            $scope = $meta['scope'] ?? 'global';
            $label = $meta['label'] ?? ($globalFormula->label ?? 'System Default');

            switch ($scope) {
                case 'subject':
                    $status = 'custom';
                    $formulaScope = 'Subject Formula';
                    $scopeText = 'Subject formula applied.';
                    break;
                case 'course':
                    $status = 'default';
                    $formulaScope = 'Course Formula';
                    $scopeText = 'Inherits course formula.';
                    break;
                case 'department':
                    $status = 'default';
                    $formulaScope = 'Department Baseline';
                    $scopeText = 'Inherits department baseline.';
                    break;
                default:
                    $status = 'default';
                    $formulaScope = 'System Default Formula';
                    $scopeText = 'Using system default.';
                    break;
            }

            return [
                'subject' => $subject,
                'has_formula' => $scope === 'subject',
                'status' => $status,
                'formula_scope' => $formulaScope,
                'formula_label' => $label,
                'scope_text' => $scopeText,
            ];
        });

        return view('admin.grades-formula-course', [
            'department' => $department,
            'course' => $course,
            'departmentFallback' => $departmentFallback,
            'courseFormula' => $courseFormula,
            'globalFormula' => $globalFormula,
            'subjectSummaries' => $subjectSummaries,
            'needsCourseFormula' => ! $courseFormula,
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
    }

    public function gradesFormulaEditDepartment(Request $request, Department $department)
    {
        Gate::authorize('admin');

        if ($department->is_deleted) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $formula = $this->ensureDepartmentFallback($department, $periodContext);
        $formula->loadMissing('weights');

        $fallbackFormula = $formula;

        $structurePayload = $this->prepareStructurePayload($formula);

        if (Str::startsWith($request->old('form_context'), 'department') && $request->old('structure_config')) {
            $structurePayload = $this->prepareStructurePayloadFromOldInput(
                $request->old('structure_type'),
                $request->old('structure_config')
            );
        }

        return view('admin.grades-formula-form', [
            'context' => 'department',
            'department' => $department,
            'course' => null,
            'subject' => null,
            'formula' => $formula,
            'fallbackFormula' => $fallbackFormula,
            'structurePayload' => $structurePayload,
            'structureCatalog' => $this->getStructureCatalog(),
            'defaultFormula' => $this->getGlobalFormula(),
            'formMode' => 'edit-department-fallback',
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
    }

    public function gradesFormulaEditCourse(Request $request, Department $department, Course $course)
    {
        Gate::authorize('admin');

        if ($department->is_deleted || $course->is_deleted || $course->department_id !== $department->id) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $formulaQuery = GradesFormula::with('weights')
            ->where('course_id', $course->id)
            ->where('scope_level', 'course');

        $formulaQuery = $this->applyPeriodFilters($formulaQuery, $selectedSemester, $selectedAcademicPeriodId);

        if ($selectedAcademicPeriodId) {
            $formulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
        } else {
            $formulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
        }

        if ($selectedSemester) {
            $formulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
        } else {
            $formulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
        }

        $rawFormula = $formulaQuery->first();

        $departmentFallback = $this->ensureDepartmentFallback($department, $periodContext);
        $departmentFallback->loadMissing('weights');

        $courseFormula = null;
        $fallbackCandidates = collect();

        if ($rawFormula && $this->formulaMatchesContext($rawFormula, $selectedSemester, $selectedAcademicPeriodId)) {
            $courseFormula = $rawFormula;
        } elseif ($rawFormula) {
            $fallbackCandidates->push($rawFormula);
        }

        if ($departmentFallback) {
            $fallbackCandidates->push($departmentFallback);
        }

        $fallbackFormula = $courseFormula
            ?? $fallbackCandidates->first()
            ?? $this->getGlobalFormula();

        $structurePayload = $this->prepareStructurePayload($courseFormula ?? $fallbackFormula);

        if (Str::startsWith($request->old('form_context'), 'course') && $request->old('structure_config')) {
            $structurePayload = $this->prepareStructurePayloadFromOldInput(
                $request->old('structure_type'),
                $request->old('structure_config')
            );
        }

        return view('admin.grades-formula-form', [
            'context' => 'course',
            'department' => $department,
            'course' => $course,
            'subject' => null,
            'formula' => $courseFormula,
            'fallbackFormula' => $fallbackFormula,
            'structurePayload' => $structurePayload,
            'structureCatalog' => $this->getStructureCatalog(),
            'defaultFormula' => $this->getGlobalFormula(),
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
    }

    public function createDepartmentFormula(Request $request, Department $department)
    {
        Gate::authorize('admin');

        if ($department->is_deleted) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $fallbackFormula = $this->ensureDepartmentFallback($department, $periodContext);
        $fallbackFormula->loadMissing('weights');

        $structurePayload = $this->prepareStructurePayload($fallbackFormula);

        if (Str::startsWith($request->old('form_context'), 'department') && $request->old('structure_config')) {
            $structurePayload = $this->prepareStructurePayloadFromOldInput(
                $request->old('structure_type'),
                $request->old('structure_config')
            );
        }

        return view('admin.grades-formula-form', [
            'context' => 'department',
            'department' => $department,
            'course' => null,
            'subject' => null,
            'formula' => null,
            'fallbackFormula' => $fallbackFormula,
            'structurePayload' => $structurePayload,
            'structureCatalog' => $this->getStructureCatalog(),
            'defaultFormula' => $this->getGlobalFormula(),
            'formMode' => 'create-department',
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
    }

    public function editDepartmentFormulaEntry(Request $request, Department $department, GradesFormula $formula)
    {
        Gate::authorize('admin');

        if ($department->is_deleted || $formula->department_id !== $department->id || $formula->scope_level !== 'department') {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $formula->loadMissing('weights');
        $fallbackFormula = $this->ensureDepartmentFallback($department, $periodContext);
        $fallbackFormula->loadMissing('weights');

        $structurePayload = $this->prepareStructurePayload($formula);

        if (Str::startsWith($request->old('form_context'), 'department') && $request->old('structure_config')) {
            $structurePayload = $this->prepareStructurePayloadFromOldInput(
                $request->old('structure_type'),
                $request->old('structure_config')
            );
        }

        return view('admin.grades-formula-form', [
            'context' => 'department',
            'department' => $department,
            'course' => null,
            'subject' => null,
            'formula' => $formula,
            'fallbackFormula' => $fallbackFormula,
            'structurePayload' => $structurePayload,
            'structureCatalog' => $this->getStructureCatalog(),
            'defaultFormula' => $this->getGlobalFormula(),
            'formMode' => 'edit-department',
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
    }

    public function destroyDepartmentFormula(Department $department, GradesFormula $formula, Request $request)
    {
        Gate::authorize('admin');

        // Validate password first
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Verify the password matches the authenticated user
        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput()
                ->with('reopen_structure_template_modal', true)
                ->with('structure_template_error', true);
        }

        if (
            $department->is_deleted
            || $formula->department_id !== $department->id
            || $formula->scope_level !== 'department'
        ) {
            abort(404);
        }

        if ($formula->is_department_fallback) {
            return back()->withErrors([
                'formula' => 'The department fallback formula cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($formula) {
            $formula->weights()->delete();
            $formula->delete();
        });

        GradesFormulaService::flushCache();

        return redirect()
            ->route('admin.gradesFormula.department', array_merge([
                'department' => $department->id,
            ], $this->formulaQueryParams()))
            ->with('success', 'Formula deleted successfully.');
    }

    public function destroyGlobalFormula(GradesFormula $formula, Request $request)
    {
        Gate::authorize('admin');

        // Validate password first
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Verify the password matches the authenticated user
        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput();
        }

        if ($formula->scope_level !== 'global') {
            abort(404, 'This formula is not a global formula.');
        }

        DB::transaction(function () use ($formula) {
            $formula->weights()->delete();
            $formula->delete();
        });

        GradesFormulaService::flushCache();

        return redirect()
            ->route('admin.gradesFormula', array_merge($this->formulaQueryParams(), ['view' => 'formulas']))
            ->with('success', 'Global formula deleted successfully.');
    }

    public function storeStructureTemplate(Request $request)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'template_label' => ['required', 'string', 'max:100'],
            'template_key' => ['required', 'string', 'max:50', Rule::unique('structure_templates', 'template_key')->where('is_deleted', false)],
            'template_description' => ['nullable', 'string', 'max:500'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.activity_type' => ['required', 'string', 'max:100'],
            'components.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'components.*.label' => ['required', 'string', 'max:100'],
            'components.*.max_items' => ['nullable', 'integer', 'min:1', 'max:5'],
            'components.*.is_main' => ['nullable', 'boolean'],
            'components.*.parent_id' => ['nullable', 'integer'],
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput()
                ->with('reopen_structure_template_modal', true)
                ->with('structure_template_error', true)
                ->with('structure_template_mode', 'create');
        }

        try {
            $structureConfig = $this->buildStructureTemplateConfig($validated['components']);
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput()
                ->with('reopen_structure_template_modal', true)
                ->with('structure_template_error', true)
                ->with('structure_template_mode', 'create');
        }

        DB::beginTransaction();

        try {
            $template = new StructureTemplate();
            $template->template_key = $validated['template_key'];
            $template->label = $validated['template_label'];
            $template->description = $validated['template_description'] !== '' ? $validated['template_description'] : null;
            $template->structure_config = $structureConfig;
            $template->is_system_default = false;
            $template->is_deleted = false;
            $template->created_by = Auth::id();
            $template->updated_by = Auth::id();
            $template->save();

            DB::commit();

            return redirect()
                ->route('admin.gradesFormula', array_merge($this->formulaQueryParams(), ['view' => 'formulas']))
                ->with('success', "Structure template '{$template->label}' created successfully.");
        } catch (\Throwable $exception) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'Failed to create structure template: ' . $exception->getMessage()])
                ->withInput()
                ->with('reopen_structure_template_modal', true)
                ->with('structure_template_error', true)
                ->with('structure_template_mode', 'create');
        }
    }

    public function updateStructureTemplate(Request $request, StructureTemplate $template)
    {
        Gate::authorize('admin');

        if ($template->is_deleted) {
            abort(404);
        }

        if ($template->is_system_default) {
            abort(403, 'System templates cannot be modified.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'template_label' => ['required', 'string', 'max:100'],
            'template_key' => [
                'required',
                'string',
                'max:50',
                Rule::unique('structure_templates', 'template_key')->ignore($template->id)->where('is_deleted', false),
            ],
            'template_description' => ['nullable', 'string', 'max:500'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.activity_type' => ['required', 'string', 'max:100'],
            'components.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'components.*.label' => ['required', 'string', 'max:100'],
            'components.*.max_items' => ['nullable', 'integer', 'min:1', 'max:5'],
            'components.*.is_main' => ['nullable', 'boolean'],
            'components.*.parent_id' => ['nullable', 'integer'],
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput()
                ->with('reopen_structure_template_modal', true)
                ->with('structure_template_error', true)
                ->with('structure_template_mode', 'edit')
                ->with('structure_template_edit_id', $template->id);
        }

        try {
            $structureConfig = $this->buildStructureTemplateConfig($validated['components']);
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput()
                ->with('reopen_structure_template_modal', true)
                ->with('structure_template_error', true)
                ->with('structure_template_mode', 'edit')
                ->with('structure_template_edit_id', $template->id);
        }

        $template->template_key = $validated['template_key'];
        $template->label = $validated['template_label'];
        $template->description = $validated['template_description'] !== '' ? $validated['template_description'] : null;
        $template->structure_config = $structureConfig;
        $template->updated_by = Auth::id();
        $template->save();

        return redirect()
            ->route('admin.gradesFormula', array_merge($this->formulaQueryParams(), ['view' => 'formulas']))
            ->with('success', "Structure template '{$template->label}' updated successfully.");
    }

    public function editStructureTemplate(StructureTemplate $template)
    {
        Gate::authorize('admin');

        if ($template->is_deleted) {
            abort(404);
        }

        if ($template->is_system_default) {
            abort(403, 'System templates cannot be modified.');
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $structureConfig = $template->structure_config ?? [];

        if ($this->isNewTemplateFormat(is_array($structureConfig) ? $structureConfig : [])) {
            $structureConfig = $this->convertNewFormatToOld($structureConfig);
        }

        try {
            $structureConfig = FormulaStructure::normalize($structureConfig);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'structure_config' => 'The stored template structure could not be rendered: ' . $exception->getMessage(),
            ]);
        }

        $template->structure_config = $structureConfig;

        return view('admin.structure-template-edit', [
            'template' => $template,
            'semester' => $periodContext['semester'] ?? null,
            'academicPeriods' => $periodContext['academic_periods'] ?? collect(),
            'academicYears' => $periodContext['academic_years'] ?? collect(),
            'selectedAcademicYear' => $periodContext['academic_year'] ?? null,
            'selectedAcademicPeriodId' => $periodContext['academic_period_id'] ?? null,
            'availableSemesters' => $periodContext['available_semesters'] ?? collect(),
        ]);
    }

    public function destroyStructureTemplate(Request $request, StructureTemplate $template)
    {
        Gate::authorize('admin');

        if ($template->is_deleted) {
            abort(404);
        }

        if ($template->is_system_default) {
            abort(403, 'System templates cannot be deleted.');
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput()
                ->with('reopen_structure_template_delete_modal', $template->id);
        }

        $template->is_deleted = true;
        $template->updated_by = Auth::id();
        $template->save();

        return redirect()
            ->route('admin.gradesFormula', array_merge($this->formulaQueryParams(), ['view' => 'formulas']))
            ->with('success', "Structure template '{$template->label}' deleted successfully.");
    }

    private function buildStructureTemplateConfig(array $components): array
    {
        if (empty($components)) {
            throw ValidationException::withMessages([
                'components' => 'Add at least one main component before saving the template.',
            ]);
        }

        $mainComponents = [];
        $subComponents = [];

        foreach ($components as $id => $component) {
            $component = is_array($component) ? $component : [];
            $activityType = trim((string) ($component['activity_type'] ?? ''));
            $label = trim((string) ($component['label'] ?? ''));
            $weight = (float) ($component['weight'] ?? 0);
            $maxItems = isset($component['max_items']) && $component['max_items'] !== '' ? (int) $component['max_items'] : null;
            $isMain = ! empty($component['is_main']);
            $parentId = $component['parent_id'] ?? null;

            $normalized = [
                'activity_type' => $activityType,
                'label' => $label,
                'weight' => $weight,
                'max_items' => $maxItems,
                'is_main' => $isMain,
                'parent_id' => $parentId,
            ];

            if ($isMain) {
                $mainComponents[$id] = $normalized;
            } elseif ($parentId !== null && $parentId !== '') {
                $subComponents[$parentId] ??= [];
                $subComponents[$parentId][] = $normalized;
            }
        }

        if (empty($mainComponents)) {
            throw ValidationException::withMessages([
                'components' => 'Add at least one main component before saving the template.',
            ]);
        }

        foreach ($subComponents as $parentId => $subs) {
            if (! isset($mainComponents[$parentId])) {
                throw ValidationException::withMessages([
                    'components' => 'All sub-components must belong to a valid main component.',
                ]);
            }
        }

        $totalMainWeight = array_sum(array_column($mainComponents, 'weight'));
        if (abs($totalMainWeight - 100) > 0.1) {
            throw ValidationException::withMessages([
                'components' => "Total weight of main components must equal 100%. Current total: {$totalMainWeight}%",
            ]);
        }

        foreach ($subComponents as $parentId => $subs) {
            $subTotal = array_sum(array_column($subs, 'weight'));
            if (abs($subTotal - 100) > 0.1) {
                $parentLabel = $mainComponents[$parentId]['label'] ?? "Component {$parentId}";
                throw ValidationException::withMessages([
                    'components' => "Sub-components of '{$parentLabel}' must total 100%. Current: {$subTotal}%",
                ]);
            }
        }

        foreach ($mainComponents as $id => &$mainComponent) {
            $identifierSource = $mainComponent['activity_type'] ?: $mainComponent['label'];
            $mainComponent['normalized_identifier'] = $this->normalizeTemplateIdentifier($identifierSource, 'component_' . $id);
        }
        unset($mainComponent);

        foreach ($subComponents as $parentId => &$subs) {
            foreach ($subs as $index => &$subComponent) {
                $identifierSource = $subComponent['activity_type'] ?: $subComponent['label'];
                $subComponent['normalized_identifier'] = $this->normalizeTemplateIdentifier(
                    $identifierSource,
                    'component_' . $parentId . '_child_' . ($index + 1)
                );
            }
            unset($subComponent);
        }
        unset($subs);

        $structureConfig = [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => [],
        ];

        foreach ($mainComponents as $id => $mainComponent) {
            $mainWeight = $mainComponent['weight'] / 100;
            $mainIdentifier = $mainComponent['normalized_identifier'];
            $label = $mainComponent['label'];
            $maxItems = $mainComponent['max_items'];

            if (isset($subComponents[$id]) && count($subComponents[$id]) > 0) {
                $children = [];

                foreach ($subComponents[$id] as $subComponent) {
                    $childIdentifier = $subComponent['normalized_identifier'];
                    $compositeActivityType = $mainIdentifier . '.' . $childIdentifier;

                    $childNode = [
                        'key' => $compositeActivityType,
                        'type' => 'activity',
                        'label' => $subComponent['label'],
                        'activity_type' => $compositeActivityType,
                        'weight' => $subComponent['weight'] / 100,
                    ];

                    if ($subComponent['max_items'] !== null) {
                        $childNode['max_assessments'] = $subComponent['max_items'];
                    }

                    $children[] = $childNode;
                }

                $structureConfig['children'][] = [
                    'key' => $mainIdentifier,
                    'type' => 'composite',
                    'label' => $label,
                    'weight' => $mainWeight,
                    'children' => $children,
                ];
            } else {
                $mainNode = [
                    'key' => $mainIdentifier,
                    'type' => 'activity',
                    'label' => $label,
                    'activity_type' => $mainIdentifier,
                    'weight' => $mainWeight,
                ];

                if ($maxItems !== null) {
                    $mainNode['max_assessments'] = $maxItems;
                }

                $structureConfig['children'][] = $mainNode;
            }
        }

        return $structureConfig;
    }

    public function editGlobalFormula(GradesFormula $formula)
    {
        Gate::authorize('admin');

        if ($formula->scope_level !== 'global') {
            abort(404, 'This formula is not a global formula.');
        }

        $formula->loadMissing('weights');

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        return view('admin.grades-formula-edit-global', compact(
            'formula',
            'selectedSemester',
            'selectedAcademicPeriodId',
            'selectedAcademicYear',
            'academicPeriods',
            'academicYears'
        ));
    }

    public function gradesFormulaEditSubject(Request $request, Subject $subject)
    {
        Gate::authorize('admin');

        if ($subject->is_deleted) {
            abort(404);
        }

        $subject->load(['course.department']);

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $formulaQuery = GradesFormula::with('weights')
            ->where('subject_id', $subject->id);

        $formulaQuery = $this->applyPeriodFilters($formulaQuery, $selectedSemester, $selectedAcademicPeriodId);

        if ($selectedAcademicPeriodId) {
            $formulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
        } else {
            $formulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
        }

        if ($selectedSemester) {
            $formulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
        } else {
            $formulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
        }

        $rawSubjectFormula = $formulaQuery->first();

        $exactCourseFormula = null;
        if ($subject->course) {
            $courseFormulaQuery = GradesFormula::with('weights')
                ->where('course_id', $subject->course_id)
                ->where('scope_level', 'course');

            $courseFormulaQuery = $this->applyPeriodFilters($courseFormulaQuery, $selectedSemester, $selectedAcademicPeriodId);

            if ($selectedAcademicPeriodId) {
                $courseFormulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
            } else {
                $courseFormulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
            }

            if ($selectedSemester) {
                $courseFormulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
            } else {
                $courseFormulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
            }

            $courseFormula = $courseFormulaQuery->first();
            if ($courseFormula && $this->formulaMatchesContext($courseFormula, $selectedSemester, $selectedAcademicPeriodId)) {
                $exactCourseFormula = $courseFormula;
            }
        }

        $departmentFallback = null;
        if ($subject->department) {
            $departmentFallback = $this->ensureDepartmentFallback($subject->department, $periodContext);
            $departmentFallback->loadMissing('weights');
        }

        $subjectFormula = null;
        $fallbackCandidates = collect();

        if ($rawSubjectFormula && $this->formulaMatchesContext($rawSubjectFormula, $selectedSemester, $selectedAcademicPeriodId)) {
            $subjectFormula = $rawSubjectFormula;
        } elseif ($rawSubjectFormula) {
            $fallbackCandidates->push($rawSubjectFormula);
        }

        if ($exactCourseFormula) {
            $fallbackCandidates->push($exactCourseFormula);
        } elseif (isset($courseFormula) && $courseFormula) {
            $fallbackCandidates->push($courseFormula);
        }

        if ($departmentFallback) {
            $fallbackCandidates->push($departmentFallback);
        }

        $fallbackFormula = $subjectFormula
            ?? $fallbackCandidates->first()
            ?? $this->getGlobalFormula();

        $structurePayload = $this->prepareStructurePayload($subjectFormula ?? $fallbackFormula);

        if (Str::startsWith($request->old('form_context'), 'subject') && $request->old('structure_config')) {
            $structurePayload = $this->prepareStructurePayloadFromOldInput(
                $request->old('structure_type'),
                $request->old('structure_config')
            );
        }

        $subjectHasExistingGrades = $this->subjectHasRecordedGrades($subject, $selectedAcademicPeriodId);

        return view('admin.grades-formula-form', [
            'context' => 'subject',
            'department' => $subject->department,
            'course' => $subject->course,
            'subject' => $subject,
            'formula' => $subjectFormula,
            'fallbackFormula' => $fallbackFormula,
            'structurePayload' => $structurePayload,
            'structureCatalog' => $this->getStructureCatalog(),
            'defaultFormula' => $this->getGlobalFormula(),
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
            // Password NOT required for editing existing formulas, only for applying templates
            'requiresPasswordConfirmation' => false,
        ]);
    }

    public function gradesFormulaSubject(Subject $subject)
    {
        Gate::authorize('admin');

        if ($subject->is_deleted) {
            abort(404);
        }

        $subject->load(['course.department']);

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $selectedAcademicYear = $periodContext['academic_year'];
        $academicPeriods = $periodContext['academic_periods'];
        $academicYears = $periodContext['academic_years'];

        $formulaQuery = GradesFormula::with('weights')
            ->where('subject_id', $subject->id);

        $formulaQuery = $this->applyPeriodFilters($formulaQuery, $selectedSemester, $selectedAcademicPeriodId);

        if ($selectedAcademicPeriodId) {
            $formulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
        } else {
            $formulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
        }

        if ($selectedSemester) {
            $formulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
        } else {
            $formulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
        }

        $rawSubjectFormula = $formulaQuery->first();

        $courseFormula = null;
        $exactCourseFormula = null;
        if ($subject->course) {
            $courseFormulaQuery = GradesFormula::with('weights')
                ->where('course_id', $subject->course_id)
                ->where('scope_level', 'course');

            $courseFormulaQuery = $this->applyPeriodFilters($courseFormulaQuery, $selectedSemester, $selectedAcademicPeriodId);

            if ($selectedAcademicPeriodId) {
                $courseFormulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
            } else {
                $courseFormulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
            }

            if ($selectedSemester) {
                $courseFormulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
            } else {
                $courseFormulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
            }

            $courseFormula = $courseFormulaQuery->first();
            if ($courseFormula && $this->formulaMatchesContext($courseFormula, $selectedSemester, $selectedAcademicPeriodId)) {
                $exactCourseFormula = $courseFormula;
            }
        }

        $departmentFallback = null;

        if ($subject->department) {
            $departmentFallback = $this->ensureDepartmentFallback($subject->department, $periodContext);
            $departmentFallback->loadMissing('weights');
        }

        $globalFormula = $this->getGlobalFormula();

        $subjectFormula = null;
        $subjectFallbackCandidates = collect();

        if ($rawSubjectFormula && $this->formulaMatchesContext($rawSubjectFormula, $selectedSemester, $selectedAcademicPeriodId)) {
            $subjectFormula = $rawSubjectFormula;
        } elseif ($rawSubjectFormula) {
            $subjectFallbackCandidates->push($rawSubjectFormula);
        }

        if ($exactCourseFormula) {
            $subjectFallbackCandidates->push($exactCourseFormula);
        } elseif ($courseFormula) {
            $subjectFallbackCandidates->push($courseFormula);
        }

        if ($departmentFallback) {
            $subjectFallbackCandidates->push($departmentFallback);
        }

        $activeScope = $subjectFormula
            ? 'subject'
            : ($exactCourseFormula
                ? 'course'
                : ($departmentFallback ? 'department' : 'default'));

        $resolvedSettings = GradesFormulaService::getSettings(
            $subject->id,
            $subject->course_id,
            $subject->department_id,
            $selectedSemester,
            $selectedAcademicPeriodId
        );

        $courseFormulaForView = $exactCourseFormula ?? $courseFormula;
        $subjectFallback = $subjectFallbackCandidates->first() ?? $globalFormula;

        $allStructureDefinitions = FormulaStructure::getAllStructureDefinitions();
        $structureDefinitions = collect($allStructureDefinitions);

        $structureOptions = $structureDefinitions
            ->map(function (array $definition, string $key) {
                return [
                    'key' => $key,
                    'label' => $definition['label'] ?? Str::of($key)->replace('_', ' ')->title()->toString(),
                ];
            })
            ->values();

        $baselineFormula = $departmentFallback ?? $globalFormula;
        $baselineStructureType = $baselineFormula->structure_type ?? 'lecture_only';

        $structureBlueprints = $structureDefinitions
            ->map(function (array $definition, string $key) use ($baselineFormula, $baselineStructureType, $allStructureDefinitions) {
                $structure = $this->resolveStructureConfigForKey($key, $allStructureDefinitions);
                $flattened = collect(FormulaStructure::flattenWeights($structure));

                $weights = $flattened
                    ->map(function (array $entry) {
                        $activityType = $entry['activity_type'] ?? $entry['key'] ?? 'component';
                        $weight = (float) ($entry['weight'] ?? 0);
                        $formattedLabel = Str::of($entry['label'] ?? FormulaStructure::formatLabel($activityType))
                            ->replace(['.', '_'], ' ')
                            ->upper()
                            ->toString();

                        return [
                            'type' => $formattedLabel,
                            'label' => $formattedLabel,
                            'display' => number_format($weight * 100, 0),
                            'progress' => $weight,
                        ];
                    })
                    ->values();

                return [
                    'key' => $key,
                    'label' => $definition['label'] ?? Str::of($key)->replace('_', ' ')->title()->toString(),
                    'description' => $definition['description'] ?? null,
                    'base_score' => (float) ($baselineFormula->base_score ?? 40),
                    'scale_multiplier' => (float) ($baselineFormula->scale_multiplier ?? 60),
                    'passing_grade' => (float) ($baselineFormula->passing_grade ?? 75),
                    'weights' => $weights,
                    'is_baseline' => $baselineStructureType === $key,
                ];
            })
            ->values();

        $activeStructureType = $resolvedSettings['meta']['structure_type']
            ?? ($subjectFormula->structure_type ?? null)
            ?? $baselineStructureType;

        $subjectHasExistingGrades = $this->subjectHasRecordedGrades($subject, $selectedAcademicPeriodId);

        return view('admin.grades-formula-subject', [
            'subject' => $subject,
            'course' => $subject->course,
            'department' => $subject->department,
            'subjectFormula' => $subjectFormula,
            'courseFormula' => $courseFormulaForView,
            'departmentFallback' => $departmentFallback,
            'globalFormula' => $globalFormula,
            'activeScope' => $activeScope,
            'activeMeta' => $resolvedSettings['meta'] ?? [],
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
            'fallbackFormula' => $subjectFallback,
            'structureOptions' => $structureOptions,
            'structureBlueprints' => $structureBlueprints,
            'selectedStructureType' => $activeStructureType,
            'requiresPasswordConfirmation' => $subjectHasExistingGrades,
        ]);
    }

    public function applySubjectFormula(Request $request, Subject $subject)
    {
        Gate::authorize('admin');

        if ($subject->is_deleted) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];

        $subjectRequiresPassword = $this->subjectHasRecordedGrades($subject, $selectedAcademicPeriodId);

        $validated = $request->validate([
            'department_formula_id' => ['nullable', 'integer'],
            'structure_type' => ['nullable', Rule::in(array_keys(FormulaStructure::getAllStructureDefinitions()))],
            'current_password' => $subjectRequiresPassword ? ['required', 'current_password'] : ['nullable'],
        ]);

        if (empty($validated['department_formula_id']) && empty($validated['structure_type'])) {
            return back()
                ->withErrors(['structure_type' => 'Select a structure template to apply.'])
                ->withInput();
        }

        $subject->load(['course.department']);

        if (! empty($validated['structure_type'])) {
            $baselineFormula = $subject->department
                ? $this->ensureDepartmentFallback($subject->department, $periodContext)
                : $this->getGlobalFormula();

            $baselineFormula->loadMissing('weights');

            $this->applyStructureTypeToSubject($subject, $validated['structure_type'], $baselineFormula);
            $this->resetSubjectAssessmentsForNewStructure($subject);
            GradesFormulaService::flushCache();

            return redirect()
                ->route('admin.gradesFormula.subject', array_merge([
                    'subject' => $subject->id,
                ], $this->formulaQueryParams()))
                ->with('success', 'Structure template applied to this subject.');
        }

        $selectedFormula = GradesFormula::with('weights')
            ->where('id', $validated['department_formula_id'])
            ->where('scope_level', 'department')
            ->first();

        if (! $selectedFormula || $selectedFormula->department_id !== $subject->department_id) {
            return back()
                ->withErrors(['department_formula_id' => 'Select a formula from this subject’s department.'])
                ->withInput();
        }

        $this->cloneFormulaToSubject($subject, $selectedFormula);
        GradesFormulaService::flushCache();

        return redirect()
            ->route('admin.gradesFormula.subject', array_merge([
                'subject' => $subject->id,
            ], $this->formulaQueryParams()))
            ->with('success', 'Formula applied to this subject.');
    }

    public function removeSubjectFormula(Request $request, Subject $subject)
    {
        Gate::authorize('admin');

        if ($subject->is_deleted) {
            abort(404);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];

        $subjectFormulaQuery = GradesFormula::where('subject_id', $subject->id)
            ->where('scope_level', 'subject');

        $subjectFormulaQuery = $this->applyPeriodFilters($subjectFormulaQuery, $selectedSemester, $selectedAcademicPeriodId);

        if ($selectedAcademicPeriodId) {
            $subjectFormulaQuery->orderByRaw('CASE WHEN academic_period_id = ? THEN 0 WHEN academic_period_id IS NULL THEN 1 ELSE 2 END', [$selectedAcademicPeriodId]);
        } else {
            $subjectFormulaQuery->orderByRaw('CASE WHEN academic_period_id IS NULL THEN 0 ELSE 1 END');
        }

        if ($selectedSemester) {
            $subjectFormulaQuery->orderByRaw('CASE WHEN semester = ? THEN 0 WHEN semester IS NULL THEN 1 ELSE 2 END', [$selectedSemester]);
        } else {
            $subjectFormulaQuery->orderByRaw('CASE WHEN semester IS NULL THEN 0 ELSE 1 END');
        }

        $subjectFormula = $subjectFormulaQuery->first();

        if (! $subjectFormula || ! $this->formulaMatchesContext($subjectFormula, $selectedSemester, $selectedAcademicPeriodId)) {
            $subjectFormula = GradesFormula::where('subject_id', $subject->id)
                ->where('scope_level', 'subject')
                ->orderByDesc('updated_at')
                ->first();
        }

        if (! $subjectFormula) {
            return redirect()
                ->route('admin.gradesFormula.subject', array_merge([
                    'subject' => $subject->id,
                ], $this->formulaQueryParams()))
                ->with('success', 'Subject already inherits its department formula.');
        }

        DB::transaction(function () use ($subjectFormula) {
            $subjectFormula->delete();
        });

        GradesFormulaService::flushCache();

        return redirect()
            ->route('admin.gradesFormula.subject', array_merge([
                'subject' => $subject->id,
            ], $this->formulaQueryParams()))
            ->with('success', 'Custom subject formula removed. This subject now inherits department settings.');
    }

    /**
     * Persist a new grades formula for the provided scope.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeGradesFormula(Request $request)
    {
        Gate::authorize('admin');

        $scope = $request->input('scope_level');
        $passwordProvided = $request->filled('password');
        $requiresPassword = in_array($scope, ['global']);

        if ($requiresPassword || $passwordProvided) {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (!Hash::check($request->input('password'), Auth::user()->password)) {
                return back()
                    ->withErrors(['password' => 'The provided password is incorrect.'])
                    ->withInput();
            }
        }

        $scopeRules = [
            'scope_level' => ['required', Rule::in(['global', 'department', 'course', 'subject'])],
            'label' => ['nullable', 'string', 'max:255'],
            'base_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scale_multiplier' => ['required', 'numeric', 'min:0', 'max:100'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'structure_type' => ['required', Rule::in(array_keys(FormulaStructure::getAllStructureDefinitions()))],
            'structure_config' => ['required', 'string'],
        ];

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];

        if ($scope === 'global') {
            // Global formulas are department-independent
            // No additional validation needed
        } elseif ($scope === 'department') {
            $scopeRules['department_id'] = [
                'required',
                'exists:departments,id',
            ];
            $scopeRules['is_department_fallback'] = ['nullable', 'boolean'];
        } elseif ($scope === 'course') {
            $scopeRules['course_id'] = [
                'required',
                'exists:courses,id',
            ];
        } elseif ($scope === 'subject') {
            $scopeRules['subject_id'] = [
                'required',
                'exists:subjects,id',
                Rule::unique('grades_formula', 'subject_id')->where(function ($query) use ($selectedSemester, $selectedAcademicPeriodId) {
                    $query->where('scope_level', 'subject');

                    if ($selectedSemester === null) {
                        $query->whereNull('semester');
                    } else {
                        $query->where('semester', $selectedSemester);
                    }

                    if ($selectedAcademicPeriodId === null) {
                        $query->whereNull('academic_period_id');
                    } else {
                        $query->where('academic_period_id', $selectedAcademicPeriodId);
                    }
                }),
            ];
        }

        $validated = $request->validate($scopeRules);

        if ($scope === 'course') {
            $existingCourseFormula = GradesFormula::where('course_id', $validated['course_id'] ?? null)
                ->where('scope_level', 'course')
                ->when($selectedSemester, fn ($q, $sem) => $q->where('semester', $sem), fn ($q) => $q->whereNull('semester'))
                ->when(
                    $selectedAcademicPeriodId !== null,
                    fn ($q) => $q->where('academic_period_id', $selectedAcademicPeriodId),
                    fn ($q) => $q->whereNull('academic_period_id')
                )
                ->first();

            if ($existingCourseFormula) {
                return $this->updateGradesFormula($request, $existingCourseFormula);
            }
        } elseif ($scope === 'subject') {
            $existingSubjectFormula = GradesFormula::where('subject_id', $validated['subject_id'] ?? null)
                ->where('scope_level', 'subject')
                ->when($selectedSemester, fn ($q, $sem) => $q->where('semester', $sem), fn ($q) => $q->whereNull('semester'))
                ->when(
                    $selectedAcademicPeriodId !== null,
                    fn ($q) => $q->where('academic_period_id', $selectedAcademicPeriodId),
                    fn ($q) => $q->whereNull('academic_period_id')
                )
                ->first();

            if ($existingSubjectFormula) {
                return $this->updateGradesFormula($request, $existingSubjectFormula);
            }
        }

        $isFallback = $scope === 'department' ? $request->boolean('is_department_fallback') : false;

        if (abs(($validated['base_score'] + $validated['scale_multiplier']) - 100) > 0.001) {
            return back()
                ->withErrors(['base_score' => 'Base score and scale multiplier must add up to 100 to keep the grading scale consistent.'])
                ->withInput();
        }
        try {
            $percentStructure = json_decode($validated['structure_config'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            return back()
                ->withErrors(['structure_config' => 'Unable to read structure configuration payload.'])
                ->withInput();
        }

        if (! is_array($percentStructure)) {
            return back()
                ->withErrors(['structure_config' => 'Invalid structure configuration payload.'])
                ->withInput();
        }

        $normalizedStructure = FormulaStructure::fromPercentPayload($percentStructure);
        $structureErrors = FormulaStructure::validate($normalizedStructure);

        if (! empty($structureErrors)) {
            return back()
                ->withErrors(['structure_config' => implode(' ', $structureErrors)])
                ->withInput();
        }

        $flattenedWeights = collect(FormulaStructure::flattenWeights($normalizedStructure));

        if ($flattenedWeights->isEmpty()) {
            return back()
                ->withErrors(['structure_config' => 'The grade structure must include at least one assessment component.'])
                ->withInput();
        }

        $weights = $flattenedWeights
            ->map(fn ($entry) => [
                'activity_type' => $entry['activity_type'],
                'weight' => $entry['weight'],
            ]);

        $department = null;
        $course = null;
        $subject = null;

        if ($scope === 'global') {
            // Global formulas don't have department, course, or subject associations
        } elseif ($scope === 'department') {
            $department = Department::findOrFail($validated['department_id']);
        } elseif ($scope === 'course') {
            $course = Course::with('department')->findOrFail($validated['course_id']);
            $department = $course->department;
        } elseif ($scope === 'subject') {
            $subject = Subject::with(['course.department'])->findOrFail($validated['subject_id']);
            $course = $subject->course;
            $department = $subject->department ?? $course?->department;

            // Password is NOT required when creating subject formulas
            // It is only required when applying templates (handled in applySubjectFormula)
            // This allows instructors to create and fine-tune formulas without password prompts
        }

        $label = $validated['label'] ?? match ($scope) {
            'global' => 'Custom Global Formula',
            'department' => ($department?->department_description ?? 'Department') . ' Formula',
            'course' => ($course?->course_code ? $course->course_code . ' · ' : '') . ($course?->course_description ?? 'Course') . ' Formula',
            'subject' => ($subject?->subject_code ? $subject->subject_code . ' · ' : '') . ($subject?->subject_description ?? 'Subject') . ' Formula',
            default => 'Custom Formula',
        };

        DB::transaction(function () use (
            $scope,
            $department,
            $course,
            $subject,
            $label,
            $validated,
            $weights,
            $isFallback,
            $selectedSemester,
            $selectedAcademicPeriodId,
            $normalizedStructure
        ) {
            if ($scope === 'department' && $department) {
                if ($isFallback) {
                    GradesFormula::where('department_id', $department->id)
                        ->where('scope_level', 'department')
                        ->when(
                            $selectedSemester !== null,
                            fn ($q) => $q->where('semester', $selectedSemester),
                            fn ($q) => $q->whereNull('semester')
                        )
                        ->when(
                            $selectedAcademicPeriodId !== null,
                            fn ($q) => $q->where('academic_period_id', $selectedAcademicPeriodId),
                            fn ($q) => $q->whereNull('academic_period_id')
                        )
                        ->update(['is_department_fallback' => false]);
                }
            }

            $name = $this->generateFormulaName($scope, $department, $course, $subject, $selectedAcademicPeriodId, $selectedSemester);

            $formula = GradesFormula::create([
                'name' => $name,
                'label' => $label,
                'scope_level' => $scope,
                'department_id' => $department?->id,
                'course_id' => $scope === 'course' ? optional($course)->id : null,
                'subject_id' => $scope === 'subject' ? optional($subject)->id : null,
                'semester' => $selectedSemester,
                'academic_period_id' => $selectedAcademicPeriodId,
                'base_score' => $validated['base_score'],
                'scale_multiplier' => $validated['scale_multiplier'],
                'passing_grade' => $validated['passing_grade'],
                'structure_type' => $validated['structure_type'],
                'structure_config' => $normalizedStructure,
                'is_department_fallback' => $scope === 'department' ? $isFallback : false,
            ]);

            $formula->weights()->createMany($weights->all());
        });

        GradesFormulaService::flushCache();

        if ($scope === 'global') {
            return redirect()
                ->route('admin.gradesFormula', array_merge($this->formulaQueryParams(), ['view' => 'formulas']))
                ->with('success', 'Global formula saved successfully.');
        }

        $redirectRoute = match ($scope) {
            'department' => $department
                ? route('admin.gradesFormula.department', array_merge(['department' => $department->id], $this->formulaQueryParams()))
                : route('admin.gradesFormula', $this->formulaQueryParams()),
            'course' => ($department && $course)
                ? route('admin.gradesFormula.course', array_merge(['department' => $department->id, 'course' => $course->id], $this->formulaQueryParams()))
                : route('admin.gradesFormula', $this->formulaQueryParams()),
            'subject' => $subject
                ? route('admin.gradesFormula.subject', array_merge(['subject' => $subject->id], $this->formulaQueryParams()))
                : route('admin.gradesFormula', $this->formulaQueryParams()),
            default => route('admin.gradesFormula', $this->formulaQueryParams()),
        };

        return redirect($redirectRoute)
            ->with('success', 'Grades formula saved successfully.');
    }

    public function updateGradesFormula(Request $request, GradesFormula $formula)
    {
        Gate::authorize('admin');

        $scope = $formula->scope_level ?? 'department';

        // Validate password for global formulas
        if ($scope === 'global') {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (!Hash::check($request->input('password'), Auth::user()->password)) {
                return back()
                    ->withErrors(['password' => 'The provided password is incorrect.'])
                    ->withInput();
            }
        }

        if ($scope === 'subject') {
            $formula->loadMissing(['subject.course.department']);
        }

        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];
        $contextExplicit = $request->hasAny(['semester', 'academic_year', 'academic_period_id']);

        if (! $contextExplicit) {
            $selectedSemester ??= $formula->semester;
            $selectedAcademicPeriodId ??= $formula->academic_period_id;
        }

        if ($selectedSemester === null) {
            $selectedAcademicPeriodId = null;
        }

        // Password is NOT required when editing an existing subject formula
        // It is only required when creating/applying a new formula (handled in storeGradesFormula/applySubjectFormula)
        // This allows instructors to fine-tune weights without password prompts

        $rules = [
            'label' => ['nullable', 'string', 'max:255'],
            'base_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scale_multiplier' => ['required', 'numeric', 'min:0', 'max:100'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'structure_type' => ['required', Rule::in(array_keys(FormulaStructure::getAllStructureDefinitions()))],
            'structure_config' => ['required', 'string'],
        ];

        if ($scope === 'department') {
            $rules['is_department_fallback'] = ['nullable', 'boolean'];
        }
        $validated = $request->validate($rules);

        $isFallback = $scope === 'department'
            ? $request->boolean('is_department_fallback', $formula->is_department_fallback)
            : $formula->is_department_fallback;

        if ($scope === 'department' && ! $isFallback && $formula->is_department_fallback) {
            $otherFallbackExists = GradesFormula::where('department_id', $formula->department_id)
                ->where('scope_level', 'department')
                ->where('id', '!=', $formula->id)
                ->where('is_department_fallback', true)
                ->exists();

            if (! $otherFallbackExists) {
                return back()
                    ->withErrors(['is_department_fallback' => 'Each department needs at least one fallback formula.'])
                    ->withInput();
            }
        }

        if (abs(($validated['base_score'] + $validated['scale_multiplier']) - 100) > 0.001) {
            return back()
                ->withErrors(['base_score' => 'Base score and scale multiplier must add up to 100 to keep the grading scale consistent.'])
                ->withInput();
        }
        try {
            $percentStructure = json_decode($validated['structure_config'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            return back()
                ->withErrors(['structure_config' => 'Unable to read structure configuration payload.'])
                ->withInput();
        }

        if (! is_array($percentStructure)) {
            return back()
                ->withErrors(['structure_config' => 'Invalid structure configuration payload.'])
                ->withInput();
        }

        $normalizedStructure = FormulaStructure::fromPercentPayload($percentStructure);
        $structureErrors = FormulaStructure::validate($normalizedStructure);

        if (! empty($structureErrors)) {
            return back()
                ->withErrors(['structure_config' => implode(' ', $structureErrors)])
                ->withInput();
        }

        $flattenedWeights = collect(FormulaStructure::flattenWeights($normalizedStructure));

        if ($flattenedWeights->isEmpty()) {
            return back()
                ->withErrors(['structure_config' => 'The grade structure must include at least one assessment component.'])
                ->withInput();
        }

        $weights = $flattenedWeights
            ->map(fn ($entry) => [
                'activity_type' => $entry['activity_type'],
                'weight' => $entry['weight'],
            ]);

        $label = $validated['label'] ?? $formula->label;

    DB::transaction(function () use ($formula, $label, $validated, $weights, $scope, $isFallback, $selectedSemester, $selectedAcademicPeriodId, $normalizedStructure) {
            if ($scope === 'department' && $isFallback) {
                GradesFormula::where('department_id', $formula->department_id)
                    ->where('scope_level', 'department')
                    ->when(
                        $selectedSemester !== null,
                        fn ($q) => $q->where('semester', $selectedSemester),
                        fn ($q) => $q->whereNull('semester')
                    )
                    ->when(
                        $selectedAcademicPeriodId !== null,
                        fn ($q) => $q->where('academic_period_id', $selectedAcademicPeriodId),
                        fn ($q) => $q->whereNull('academic_period_id')
                    )
                    ->where('id', '!=', $formula->id)
                    ->update(['is_department_fallback' => false]);
            }

            if ($scope !== 'course') {
                $formula->course_id = null;
            }

            $formula->fill([
                'label' => $label,
                'base_score' => $validated['base_score'],
                'scale_multiplier' => $validated['scale_multiplier'],
                'passing_grade' => $validated['passing_grade'],
                'semester' => $selectedSemester,
                'academic_period_id' => $selectedAcademicPeriodId,
                'is_department_fallback' => $scope === 'department' ? $isFallback : $formula->is_department_fallback,
                'structure_type' => $validated['structure_type'],
                'structure_config' => $normalizedStructure,
            ]);
            $formula->save();

            $formula->weights()->delete();
            $formula->weights()->createMany($weights->all());
        });

        GradesFormulaService::flushCache();

        $formula->loadMissing(['department', 'course', 'subject']);

        $queryParams = $this->formulaQueryParams();

        if ($scope === 'global') {
            return redirect()
                ->route('admin.gradesFormula', array_merge($queryParams, ['view' => 'formulas']))
                ->with('success', 'Global formula updated successfully.');
        }

        $redirectRoute = match ($scope) {
            'department' => $formula->department
                ? route('admin.gradesFormula.department', array_merge(['department' => $formula->department->id], $queryParams))
                : route('admin.gradesFormula', $queryParams),
            'course' => ($formula->department && $formula->course)
                ? route('admin.gradesFormula.course', array_merge(['department' => $formula->department->id, 'course' => $formula->course->id], $queryParams))
                : route('admin.gradesFormula', $queryParams),
            'subject' => $formula->subject
                ? route('admin.gradesFormula.subject', array_merge(['subject' => $formula->subject->id], $queryParams))
                : route('admin.gradesFormula', $queryParams),
            default => route('admin.gradesFormula', $queryParams),
        };

        return redirect()->to($redirectRoute)
            ->with('success', 'Grades formula updated successfully.');
    }

    protected function subjectHasRecordedGrades(Subject $subject, ?int $academicPeriodId = null): bool
    {
        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->where('is_deleted', false)
            ->when($academicPeriodId, fn ($query, $periodId) => $query->where('academic_period_id', $periodId));

        if ($termGrades->exists()) {
            return true;
        }

        $finalGrades = FinalGrade::where('subject_id', $subject->id)
            ->where('is_deleted', false)
            ->when($academicPeriodId, fn ($query, $periodId) => $query->where('academic_period_id', $periodId));

        if ($finalGrades->exists()) {
            return true;
        }

        return Score::where('is_deleted', false)
            ->whereHas('activity', function ($query) use ($subject) {
                $query->where('subject_id', $subject->id)
                    ->where('is_deleted', false);
            })
            ->exists();
    }

    protected function resolveFormulaPeriodContext(): array
    {
        $periods = AcademicPeriod::orderBy('academic_year', 'desc')
            ->orderBy('semester')
            ->get();

        $academicYears = $periods->pluck('academic_year')->unique()->values();

        $requestedPeriodValue = request()->input('academic_period_id');
        $forceAllPeriods = $requestedPeriodValue === 'all';
        $requestedPeriodId = null;

        if (! $forceAllPeriods && $requestedPeriodValue !== null && $requestedPeriodValue !== '') {
            $requestedPeriodId = (int) $requestedPeriodValue;
        }

        $requestedYear = request()->input('academic_year');
        $requestedSemester = request()->filled('semester') ? request()->input('semester') : null;
        if ($requestedSemester === '') {
            $requestedSemester = null;
        }

        $selectedPeriod = null;

        if ($requestedPeriodId !== null) {
            $selectedPeriod = $periods->firstWhere('id', $requestedPeriodId);
            if ($selectedPeriod) {
                $requestedYear = $selectedPeriod->academic_year;
                $requestedSemester = $selectedPeriod->semester;
            }
        }

        if ($requestedYear && $requestedSemester) {
            $selectedPeriod = $periods->first(function (AcademicPeriod $period) use ($requestedYear, $requestedSemester) {
                return $period->academic_year === $requestedYear && $period->semester === $requestedSemester;
            });
        }

        if (! $selectedPeriod && $requestedYear) {
            $selectedPeriod = $periods->firstWhere('academic_year', $requestedYear);
            if (! $requestedSemester && $selectedPeriod) {
                $requestedSemester = $selectedPeriod->semester;
            }
        }

        if (! $selectedPeriod && $requestedSemester) {
            $selectedPeriod = $periods->firstWhere('semester', $requestedSemester);
        }

        if (! $selectedPeriod && ! $forceAllPeriods && session('active_academic_period_id')) {
            $selectedPeriod = $periods->firstWhere('id', (int) session('active_academic_period_id'));
        }

        if (! $selectedPeriod && ! $forceAllPeriods && $periods->isNotEmpty()) {
            $selectedPeriod = $periods->first();
        }

        $selectedAcademicYear = $requestedYear ?? $selectedPeriod?->academic_year;
        $selectedSemester = $requestedSemester ?? $selectedPeriod?->semester;
        $selectedAcademicPeriodId = $selectedPeriod?->id;

        if ($forceAllPeriods) {
            $selectedAcademicYear = null;
            $selectedSemester = null;
            $selectedAcademicPeriodId = null;
        } elseif ($selectedSemester === null) {
            $selectedAcademicPeriodId = null;
        } elseif (! $selectedAcademicPeriodId && $selectedAcademicYear) {
            $matchingPeriod = $periods->first(function (AcademicPeriod $period) use ($selectedAcademicYear, $selectedSemester) {
                return $period->academic_year === $selectedAcademicYear && $period->semester === $selectedSemester;
            });
            $selectedAcademicPeriodId = $matchingPeriod?->id;
        }

        $availableSemesters = $selectedAcademicYear
            ? $periods->where('academic_year', $selectedAcademicYear)->pluck('semester')->unique()->values()
            : $periods->pluck('semester')->unique()->values();

        return [
            'academic_periods' => $periods,
            'academic_years' => $academicYears,
            'academic_year' => $selectedAcademicYear,
            'semester' => $selectedSemester,
            'academic_period_id' => $selectedAcademicPeriodId,
            'available_semesters' => $availableSemesters,
        ];
    }

    protected function applyPeriodFilters($query, ?string $semester, ?int $academicPeriodId)
    {
        return $query
            ->when($academicPeriodId, function ($q) use ($academicPeriodId) {
                $q->where(function ($scoped) use ($academicPeriodId) {
                    $scoped->where('academic_period_id', $academicPeriodId)
                        ->orWhereNull('academic_period_id');
                });
            })
            ->when($semester, function ($q) use ($semester) {
                $q->where(function ($scoped) use ($semester) {
                    $scoped->where('semester', $semester)
                        ->orWhereNull('semester');
                });
            });
    }

    protected function generateFormulaName(
        string $scope,
        ?Department $department,
        ?Course $course,
        ?Subject $subject,
        ?int $academicPeriodId,
        ?string $semester
    ): string {
        $segments = [$scope];

        if ($department && $scope !== 'subject') {
            $segments[] = 'dept_' . $department->id;
        }

        if ($course && in_array($scope, ['course', 'subject'], true)) {
            $segments[] = 'course_' . $course->id;
        }

        if ($subject && $scope === 'subject') {
            $segments[] = 'subject_' . $subject->id;
        }

        if ($academicPeriodId !== null) {
            $segments[] = 'period_' . $academicPeriodId;
        }

        if ($semester !== null && $semester !== '') {
            $segments[] = 'sem_' . Str::slug($semester, '_');
        }

        $segments[] = Str::uuid()->toString();

        return implode('_', $segments);
    }

    protected function generateFallbackName(Department $department, ?string $semester, ?string $academicYear): string
    {
        $segments = [
            'department',
            $department->id,
            'fallback',
        ];

        if ($academicYear) {
            $segments[] = Str::slug($academicYear, '_');
        }

        if ($semester) {
            $segments[] = Str::slug($semester, '_');
        }

        return strtolower(implode('_', array_filter($segments)));
    }

    protected function formulaMatchesContext(?GradesFormula $formula, ?string $semester, ?int $academicPeriodId): bool
    {
        if (! $formula) {
            return false;
        }

        $semesterMatch = $semester === null
            ? $formula->semester === null
            : $formula->semester === $semester;

        $periodMatch = $academicPeriodId === null
            ? $formula->academic_period_id === null
            : (int) $formula->academic_period_id === (int) $academicPeriodId;

        return $semesterMatch && $periodMatch;
    }

    protected function formulaQueryParams(array $merge = []): array
    {
        $params = array_merge(
            request()->only(['semester', 'academic_year', 'academic_period_id']),
            $merge
        );

        return collect($params)
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
    }

    protected function ensureDepartmentFallback(Department $department, ?array $periodContext = null): GradesFormula
    {
        $context = $periodContext ?? $this->resolveFormulaPeriodContext();
        $selectedSemester = $context['semester'] ?? null;
        $selectedAcademicPeriodId = $context['academic_period_id'] ?? null;
        $selectedAcademicYear = $context['academic_year'] ?? null;

        $baseQuery = GradesFormula::with('weights')
            ->where('department_id', $department->id)
            ->where('scope_level', 'department')
            ->where('is_department_fallback', true)
            ->orderByDesc('updated_at');

        if ($selectedAcademicPeriodId) {
            $specific = (clone $baseQuery)
                ->where('academic_period_id', $selectedAcademicPeriodId)
                ->when($selectedSemester, fn ($q, $sem) => $q->where('semester', $sem))
                ->first();
            if ($specific) {
                return $specific;
            }

            $periodFallback = (clone $baseQuery)
                ->where('academic_period_id', $selectedAcademicPeriodId)
                ->whereNull('semester')
                ->first();
            if ($periodFallback) {
                return $periodFallback;
            }
        }

        if ($selectedSemester) {
            $semesterFallback = (clone $baseQuery)
                ->whereNull('academic_period_id')
                ->where('semester', $selectedSemester)
                ->first();
            if ($semesterFallback) {
                return $semesterFallback;
            }
        }

        $genericFallback = (clone $baseQuery)
            ->whereNull('academic_period_id')
            ->whereNull('semester')
            ->first();
        if ($genericFallback) {
            return $genericFallback;
        }

        $label = trim(($department->department_description ?? 'Department') . ' Baseline Formula');
        if ($label === '') {
            $label = 'Department Baseline Formula';
        }

        $fallbackName = $this->generateFallbackName($department, $selectedSemester, $selectedAcademicYear);
        $semesterForInsert = $selectedSemester;
        $periodForInsert = $selectedAcademicPeriodId;

        $fallback = DB::transaction(function () use ($department, $label, $fallbackName, $semesterForInsert, $periodForInsert) {
            $formula = GradesFormula::create([
                'name' => $fallbackName,
                'label' => $label,
                'scope_level' => 'department',
                'department_id' => $department->id,
                'semester' => $semesterForInsert,
                'academic_period_id' => $periodForInsert,
                'base_score' => 40,
                'scale_multiplier' => 60,
                'passing_grade' => 75,
                'is_department_fallback' => true,
            ]);

            $formula->weights()->createMany([
                ['activity_type' => 'quiz', 'weight' => 0.40],
                ['activity_type' => 'ocr', 'weight' => 0.20],
                ['activity_type' => 'exam', 'weight' => 0.40],
            ]);

            return $formula;
        });

        GradesFormulaService::flushCache();

        return $fallback->fresh('weights');
    }

    protected function formulasEquivalent(GradesFormula $first, GradesFormula $second): bool
    {
        $first->loadMissing('weights');
        $second->loadMissing('weights');

        $numericFieldsMatch = abs(($first->base_score ?? 0) - ($second->base_score ?? 0)) < 0.0001
            && abs(($first->scale_multiplier ?? 0) - ($second->scale_multiplier ?? 0)) < 0.0001
            && abs(($first->passing_grade ?? 0) - ($second->passing_grade ?? 0)) < 0.0001;

        if (! $numericFieldsMatch) {
            return false;
        }

        $firstWeights = collect($first->weights)
            ->mapWithKeys(fn ($weight) => [mb_strtolower($weight->activity_type) => round((float) $weight->weight, 4)])
            ->sortKeys();
        $secondWeights = collect($second->weights)
            ->mapWithKeys(fn ($weight) => [mb_strtolower($weight->activity_type) => round((float) $weight->weight, 4)])
            ->sortKeys();

        return $firstWeights->all() === $secondWeights->all();
    }

    protected function cloneFormulaToCourse(Course $course, GradesFormula $sourceFormula): GradesFormula
    {
        $sourceFormula->loadMissing('weights');

        $label = trim(($course->course_code ? $course->course_code . ' - ' : '') . ($course->course_description ?? 'Course') . ' Formula');
        if ($label === '') {
            $label = 'Course Formula';
        }

        return DB::transaction(function () use ($course, $sourceFormula, $label) {
            $requestSemester = request('semester');
            $requestPeriodId = request('academic_period_id');

            $activePeriodId = null;
            if ($requestPeriodId !== null && $requestPeriodId !== '') {
                $activePeriodId = (int) $requestPeriodId;
            } elseif (session()->has('active_academic_period_id')) {
                $activePeriodId = (int) session('active_academic_period_id');
            }

            $periodModel = $activePeriodId ? AcademicPeriod::find($activePeriodId) : null;

            $selectedSemester = $requestSemester !== null && $requestSemester !== ''
                ? $requestSemester
                : ($periodModel?->semester ?? null);

            if ($selectedSemester === null && $periodModel) {
                $selectedSemester = $periodModel->semester;
            }

            $formula = GradesFormula::firstOrNew([
                'course_id' => $course->id,
                'semester' => $selectedSemester,
                'academic_period_id' => $activePeriodId,
            ]);

            if (! $formula->exists) {
                $formula->name = $this->generateFormulaName('course', $course->department, $course, null, $activePeriodId, $selectedSemester);
                $formula->scope_level = 'course';
            }

            $formula->fill([
                'label' => $label,
                'scope_level' => 'course',
                'department_id' => $course->department_id,
                'subject_id' => null,
                'semester' => $selectedSemester,
                'academic_period_id' => $activePeriodId,
                'base_score' => $sourceFormula->base_score,
                'scale_multiplier' => $sourceFormula->scale_multiplier,
                'passing_grade' => $sourceFormula->passing_grade,
                'structure_type' => $sourceFormula->structure_type,
                'structure_config' => $sourceFormula->structure_config,
                'is_department_fallback' => false,
            ]);

            $formula->save();

            $weights = $sourceFormula->weights
                ->map(fn ($weight) => [
                    'activity_type' => $weight->activity_type,
                    'weight' => (float) $weight->weight,
                ])
                ->values()
                ->all();

            $formula->weights()->delete();

            if (! empty($weights)) {
                $formula->weights()->createMany($weights);
            }

            return $formula->fresh('weights');
        });
    }

    protected function cloneFormulaToSubject(Subject $subject, GradesFormula $sourceFormula): GradesFormula
    {
        $sourceFormula->loadMissing('weights');

        $label = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? 'Subject') . ' Formula');
        if ($label === '') {
            $label = 'Subject Formula';
        }

        return DB::transaction(function () use ($subject, $sourceFormula, $label) {
            $requestSemester = request('semester');
            $requestPeriodId = request('academic_period_id');

            $activePeriodId = null;
            if ($requestPeriodId !== null && $requestPeriodId !== '') {
                $activePeriodId = (int) $requestPeriodId;
            } elseif (session()->has('active_academic_period_id')) {
                $activePeriodId = (int) session('active_academic_period_id');
            }

            $periodModel = $activePeriodId ? AcademicPeriod::find($activePeriodId) : null;

            $selectedSemester = $requestSemester !== null && $requestSemester !== ''
                ? $requestSemester
                : ($periodModel?->semester ?? null);

            if ($selectedSemester === null && $periodModel) {
                $selectedSemester = $periodModel->semester;
            }

            $formula = GradesFormula::firstOrNew([
                'subject_id' => $subject->id,
                'semester' => $selectedSemester,
                'academic_period_id' => $activePeriodId,
            ]);

            if (! $formula->exists) {
                $formula->name = $this->generateFormulaName('subject', $subject->department, $subject->course, $subject, $activePeriodId, $selectedSemester);
                $formula->scope_level = 'subject';
            }

            $formula->fill([
                'label' => $label,
                'scope_level' => 'subject',
                'department_id' => $subject->department_id,
                'course_id' => null,
                'semester' => $selectedSemester,
                'academic_period_id' => $activePeriodId,
                'base_score' => $sourceFormula->base_score,
                'scale_multiplier' => $sourceFormula->scale_multiplier,
                'passing_grade' => $sourceFormula->passing_grade,
                'structure_type' => $sourceFormula->structure_type,
                'structure_config' => $sourceFormula->structure_config,
                'is_department_fallback' => false,
            ]);

            $formula->save();

            $weights = $sourceFormula->weights
                ->map(fn ($weight) => [
                    'activity_type' => $weight->activity_type,
                    'weight' => (float) $weight->weight,
                ])
                ->values()
                ->all();

            $formula->weights()->delete();

            if (! empty($weights)) {
                $formula->weights()->createMany($weights);
            }

            return $formula->fresh('weights');
        });
    }

    protected function applyStructureTypeToSubject(Subject $subject, string $structureType, GradesFormula $baseline): GradesFormula
    {
        $structure = $this->resolveStructureConfigForKey($structureType);

        $weights = collect(FormulaStructure::flattenWeights($structure))
            ->map(function (array $entry) {
                $activityType = mb_strtolower($entry['activity_type'] ?? $entry['key'] ?? 'component');

                return [
                    'activity_type' => $activityType,
                    'weight' => (float) ($entry['weight'] ?? 0),
                ];
            })
            ->values()
            ->all();

        $label = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? 'Subject') . ' Formula');
        if ($label === '') {
            $label = 'Subject Formula';
        }

        return DB::transaction(function () use ($subject, $baseline, $label, $structureType, $structure, $weights) {
            $requestSemester = request('semester');
            $requestPeriodId = request('academic_period_id');

            $activePeriodId = null;
            if ($requestPeriodId !== null && $requestPeriodId !== '') {
                $activePeriodId = (int) $requestPeriodId;
            } elseif (session()->has('active_academic_period_id')) {
                $activePeriodId = (int) session('active_academic_period_id');
            }

            $periodModel = $activePeriodId ? AcademicPeriod::find($activePeriodId) : null;

            $selectedSemester = $requestSemester !== null && $requestSemester !== ''
                ? $requestSemester
                : ($periodModel?->semester ?? null);

            if ($selectedSemester === null && $periodModel) {
                $selectedSemester = $periodModel->semester;
            }

            $formula = GradesFormula::firstOrNew([
                'subject_id' => $subject->id,
                'semester' => $selectedSemester,
                'academic_period_id' => $activePeriodId,
            ]);

            if (! $formula->exists) {
                $formula->name = $this->generateFormulaName('subject', $subject->department, $subject->course, $subject, $activePeriodId, $selectedSemester);
                $formula->scope_level = 'subject';
            }

            $formula->fill([
                'label' => $label,
                'scope_level' => 'subject',
                'department_id' => $subject->department_id,
                'course_id' => null,
                'semester' => $selectedSemester,
                'academic_period_id' => $activePeriodId,
                'base_score' => $baseline->base_score,
                'scale_multiplier' => $baseline->scale_multiplier,
                'passing_grade' => $baseline->passing_grade,
                'structure_type' => $structureType,
                'structure_config' => $structure,
                'is_department_fallback' => false,
            ]);

            $formula->save();

            $formula->weights()->delete();

            if (! empty($weights)) {
                $formula->weights()->createMany($weights);
            }

            return $formula->fresh('weights');
        });
    }

    protected function resetSubjectAssessmentsForNewStructure(Subject $subject): void
    {
        $actorId = Auth::id();

        DB::transaction(function () use ($subject, $actorId) {
            $activities = Activity::where('subject_id', $subject->id)
                ->where('is_deleted', false)
                ->get();

            if ($activities->isNotEmpty()) {
                $activityIds = $activities->pluck('id');

                Activity::whereIn('id', $activityIds)->update([
                    'is_deleted' => true,
                    'updated_by' => $actorId,
                ]);

                if ($activityIds->isNotEmpty()) {
                    Score::whereIn('activity_id', $activityIds)->update([
                        'is_deleted' => true,
                        'updated_by' => $actorId,
                    ]);
                }
            }

            TermGrade::where('subject_id', $subject->id)->delete();
            FinalGrade::where('subject_id', $subject->id)->delete();
        });
    }

    private function getGlobalFormula(): GradesFormula
    {
        $formula = GradesFormula::with('weights')
            ->where('scope_level', 'global')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $formula) {
            $formula = GradesFormula::with('weights')
                ->whereNull('department_id')
                ->whereNull('course_id')
                ->whereNull('subject_id')
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->first();
        }

        if (! $formula) {
            $formula = new GradesFormula([
                'label' => 'ASBME Default',
                'scope_level' => 'global',
                'base_score' => 40,
                'scale_multiplier' => 60,
                'passing_grade' => 75,
            ]);
            $formula->setRelation('weights', collect());
        }

        return $formula;
    }

    private function prepareStructurePayload(GradesFormula $formula): array
    {
        $type = $formula->structure_type ?? 'lecture_only';
        $structure = $formula->structure_config ?? $this->resolveStructureConfigForKey($type);

        return [
            'type' => $type,
            'structure' => \App\Support\Grades\FormulaStructure::toPercentPayload($structure),
        ];
    }

    private function prepareStructurePayloadFromOldInput(?string $type, ?string $payload): array
    {
        $type = $type ?: 'lecture_only';

        if ($payload) {
            try {
                $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return [
                        'type' => $type,
                        'structure' => $decoded,
                    ];
                }
            } catch (\Throwable $exception) {
                // Fallback to defaults when payload cannot be decoded.
            }
        }

        return [
            'type' => $type,
            'structure' => \App\Support\Grades\FormulaStructure::toPercentPayload(
                $this->resolveStructureConfigForKey($type)
            ),
        ];
    }

    private function resolveStructureConfigForKey(string $structureKey, ?array $definitions = null): array
    {
        $definitions ??= FormulaStructure::getAllStructureDefinitions();
        $definition = $definitions[$structureKey] ?? null;

        $structureConfig = $definition['structure_config'] ?? null;

        if (is_string($structureConfig) && $structureConfig !== '') {
            $decoded = json_decode($structureConfig, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $structureConfig = $decoded;
            }
        }

        if (is_array($structureConfig) && ! empty($structureConfig)) {
            if ($this->isNewTemplateFormat($structureConfig)) {
                $structureConfig = $this->convertNewFormatToOld($structureConfig);
            }

            try {
                return FormulaStructure::normalize($structureConfig);
            } catch (\Throwable $exception) {
                // Fall through to the default structure when normalization fails.
            }
        }

        return FormulaStructure::normalize(FormulaStructure::default($structureKey));
    }

    private function getStructureCatalog(): array
    {
        return collect(FormulaStructure::getAllStructureDefinitions())
            ->mapWithKeys(function ($meta, $key) {
                // Handle custom templates differently
                if (isset($meta['is_custom']) && $meta['is_custom']) {
                    $structureConfig = $meta['structure_config'] ?? [];

                    if (is_string($structureConfig)) {
                        $decoded = json_decode($structureConfig, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $structureConfig = $decoded;
                        } else {
                            $structureConfig = [];
                        }
                    }

                    if (! is_array($structureConfig) || empty($structureConfig)) {
                        $structureConfig = FormulaStructure::default('lecture_only');
                    }

                    // Check if this is the NEW format (with is_main, parent_id)
                    // and convert it to the OLD format (with key, type, children)
                    if ($this->isNewTemplateFormat($structureConfig)) {
                        $structureConfig = $this->convertNewFormatToOld($structureConfig);
                    }

                    try {
                        $structurePayload = FormulaStructure::toPercentPayload($structureConfig);
                    } catch (\Throwable $exception) {
                        $structurePayload = FormulaStructure::toPercentPayload(FormulaStructure::default('lecture_only'));
                    }

                    return [
                        $key => [
                            'id' => $meta['id'] ?? null,
                            'template_key' => $meta['template_key'] ?? $key,
                            'label' => $meta['label'],
                            'description' => $meta['description'],
                            'structure' => $structurePayload,
                            'is_custom' => true,
                            'is_system_default' => (bool) ($meta['is_system_default'] ?? false),
                        ],
                    ];
                }

                // Handle hardcoded templates
                return [
                    $key => [
                        'id' => null,
                        'template_key' => $key,
                        'label' => $meta['label'],
                        'description' => $meta['description'],
                        'structure' => FormulaStructure::toPercentPayload(FormulaStructure::default($key)),
                        'is_custom' => false,
                        'is_system_default' => true,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Check if structure config is in the new format (with 'type' and 'structure' array).
     */
    private function isNewTemplateFormat(array $config): bool
    {
        // New format has a top-level 'type' and 'structure' key
        // with 'structure' being an array of components with 'is_main' flags
        if (! isset($config['structure']) || ! is_array($config['structure'])) {
            return false;
        }

        // Check if any entry has 'is_main' key (new format indicator)
        foreach ($config['structure'] as $entry) {
            if (is_array($entry) && array_key_exists('is_main', $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert new template format to old format.
     * 
     * New format: { type: 'custom', structure: [{ is_main, parent_id, label, weight, activity_type }] }
     * Old format: { key, type, label, children: [{ key, type, label, weight, activity_type }] }
     */
    private function convertNewFormatToOld(array $config): array
    {
        $entries = $config['structure'] ?? [];
        
        // Separate main and sub components
        $mainComponents = [];
        $subComponents = [];
        $mainCounter = 1;
        
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            
            $isMain = (bool) ($entry['is_main'] ?? false);
            
            if ($isMain) {
                // Use component_id if available, otherwise generate from counter
                $componentId = $entry['component_id'] ?? 'comp_' . $mainCounter;
                $mainComponents[] = [
                    'entry' => $entry,
                    'id' => $componentId,
                    'children' => [],
                ];
                $mainCounter++;
            } else {
                $subComponents[] = $entry;
            }
        }
        
        // Build lookup map for main components
        $componentLookup = [];
        foreach ($mainComponents as $index => $component) {
            if ($component['id'] !== null) {
                $componentLookup[$component['id']] = $index;
            }
        }
        
        // Attach sub-components to their parents
        foreach ($subComponents as $sub) {
            $parentId = $sub['parent_id'] ?? null;
            
            if ($parentId !== null && isset($componentLookup[$parentId])) {
                $parentIndex = $componentLookup[$parentId];
                $mainComponents[$parentIndex]['children'][] = $sub;
            }
        }
        
        // Build the old format structure
        $children = [];
        
        foreach ($mainComponents as $component) {
            $entry = $component['entry'];
            $subs = $component['children'];
            
            $activityType = $entry['activity_type'] ?? 'component';
            $label = $entry['label'] ?? 'Component';
            $weight = isset($entry['weight']) ? (float) $entry['weight'] / 100.0 : 0.0;
            $maxAssessments = $this->normalizeMaxAssessments($entry['max_items'] ?? null);
            
            $key = Str::slug($activityType, '_');
            
            if (empty($subs)) {
                // No sub-components, create a simple activity node
                $children[] = [
                    'key' => $key,
                    'type' => 'activity',
                    'label' => $label,
                    'activity_type' => $activityType,
                    'weight' => $weight,
                    'max_assessments' => $maxAssessments,
                ];
            } else {
                // Has sub-components, create a composite node
                $subChildren = [];
                
                foreach ($subs as $sub) {
                    $subActivityType = $sub['activity_type'] ?? 'component';
                    $subLabel = $sub['label'] ?? 'Component';
                    $subWeight = isset($sub['weight']) ? (float) $sub['weight'] / 100.0 : 0.0;
                    $subKey = Str::slug($key . '.' . $subActivityType, '_');
                    $subMaxAssessments = $this->normalizeMaxAssessments($sub['max_items'] ?? null);
                    
                    $subChildren[] = [
                        'key' => $subKey,
                        'type' => 'activity',
                        'label' => $subLabel,
                        'activity_type' => $key . '.' . $subActivityType,
                        'weight' => $subWeight,
                        'max_assessments' => $subMaxAssessments,
                    ];
                }
                
                $children[] = [
                    'key' => $key,
                    'type' => 'composite',
                    'label' => $label,
                    'weight' => $weight,
                    'children' => $subChildren,
                ];
            }
        }
        
        return [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => $children,
        ];
    }

    private function normalizeMaxAssessments($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $intValue = (int) $value;

        if ($intValue < 1) {
            return null;
        }

        return min($intValue, 5);
    }

    private function normalizeTemplateIdentifier(?string $value, string $fallback): string
    {
        $candidate = Str::slug((string) $value, '_');

        if ($candidate === '') {
            $candidate = Str::slug($fallback, '_');
        }

        return $candidate !== '' ? $candidate : 'component_' . Str::random(6);
    }

    /**
     * Build a hierarchical weight display that better represents nested structures
     */
    private function buildStructureWeightDisplay(array $structure): array
    {
        $weights = [];
        $children = $structure['children'] ?? [];

        foreach ($children as $child) {
            $childType = $child['type'] ?? 'activity';
            $childWeight = isset($child['weight']) ? (float) $child['weight'] : 0.0;
            $childLabel = $child['label'] ?? FormulaStructure::formatLabel($child['key'] ?? 'component');
            $childPercent = (int) round($childWeight * 100);
            $maxAssessments = $child['max_assessments'] ?? null;

            if ($childType === 'composite' && !empty($child['children'])) {
                // This is a composite node (e.g., "Lecture Component 60%")
                // Add the main component weight
                $weights[] = [
                    'type' => $childLabel,
                    'percent' => $childPercent,
                    'is_composite' => true,
                    'max_items' => null, // Composites don't have max items
                ];

                // Add sub-components with relative weights
                foreach ($child['children'] as $subChild) {
                    $subWeight = isset($subChild['weight']) ? (float) $subChild['weight'] : 0.0;
                    $subActivityType = $subChild['activity_type'] ?? $subChild['key'] ?? 'component';
                    $subLabel = $subChild['label'] ?? FormulaStructure::formatLabel($subActivityType);
                    $subPercent = (int) round($subWeight * 100);
                    $subMaxAssessments = $subChild['max_assessments'] ?? null;

                    $weights[] = [
                        'type' => $subLabel,
                        'percent' => $subPercent,
                        'is_sub' => true,
                        'parent_label' => $childLabel,
                        'max_items' => $subMaxAssessments,
                    ];
                }
            } else {
                // This is a simple activity node
                $activityType = $child['activity_type'] ?? $child['key'] ?? 'component';
                $label = $childLabel;

                $weights[] = [
                    'type' => $label,
                    'percent' => $childPercent,
                    'is_composite' => false,
                    'max_items' => $maxAssessments,
                ];
            }
        }

        return $weights;
    }


    public function viewUsers()
    {
        Gate::authorize('admin');


        // Show all users, including instructors (role 0)
        $users = User::orderBy('role', 'asc')->get();

        $departments = Department::all();
        $courses = Course::all();

        // Detect if the disabled_until column exists so the view can surface a migration notice
        $hasDisabledUntilColumn = Schema::hasColumn('users', 'disabled_until');

        return view('admin.users', compact('users', 'departments', 'courses', 'hasDisabledUntilColumn'));
    }

    /**
     * Re-enable a disabled user account
     */
    public function enableUser(Request $request, User $user)
    {
        Gate::authorize('admin');

        try {
            $user->is_active = true;
            if (Schema::hasColumn('users', 'disabled_until')) {
                $user->disabled_until = null;
            }
            $user->save();

            return response()->json([
                'success' => true,
                'message' => "Account for {$user->first_name} {$user->last_name} has been re-enabled."
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Enable user failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to enable user. Please try again.'
            ], 500);
        }
    }

    public function adminConfirmUserCreationWithPassword(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'confirm_password' => 'required|string',
        ]);

        // Get the currently authenticated user
        $user = Auth::user();

        // Check if the entered password matches the stored password
        if (Hash::check($request->confirm_password, $user->password)) {
            // If password matches, proceed with the action (e.g., store the new user or perform other actions)
            // Return a success response for AJAX
            return response()->json(['success' => true, 'message' => 'Password confirmed successfully']);
        }

        // If password is incorrect, return an error message
        return response()->json(['success' => false, 'message' => 'The password you entered is incorrect.']);
    }

    
    public function storeUser(Request $request)
    {
        $validationRules = [
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['nullable', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'regex:/^[^@]+$/', 'max:255', 'unique:users,email'],
            'role'          => ['required', 'in:1,2,3,5'],
            'password'      => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ];

        // Add department validation for non-admin and non-VPAA roles
        if ($request->role != 3 && $request->role != 5) {
            $validationRules['department_id'] = ['required', 'exists:departments,id'];
            
            // Course validation based on role
            if ($request->role == 1) { // Chairperson
                $validationRules['course_id'] = ['required', 'exists:courses,id'];
            } else if ($request->role == 2) { // Dean
                $validationRules['course_id'] = ['nullable', 'exists:courses,id'];
            }
        }

        $request->validate($validationRules);

        $fullEmail = $request->email . '@brokenshire.edu.ph';

        $userData = [
            'first_name'    => $request->first_name,
            'middle_name'   => $request->middle_name,
            'last_name'     => $request->last_name,
            'email'         => $fullEmail,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'is_active'     => true,
        ];

        // Add department for non-admin and non-VPAA roles
        if ($request->role != 3 && $request->role != 5) {
            $userData['department_id'] = $request->department_id;
            
            // Add course_id only if it's provided (for Dean) or required (for Chairperson)
            if ($request->role == 1 || ($request->role == 2 && $request->has('course_id'))) {
                $userData['course_id'] = $request->course_id;
            }
        }

        User::create($userData);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    /**
     * Force logout a user from all devices by clearing their sessions
     */
    public function forceLogoutUser(Request $request, User $user)
    {
        Gate::authorize('admin');

        try {
            // Only attempt to delete sessions if the application is using database sessions
            $driver = config('session.driver');
            $skippedDeletion = false;
            if ($driver === 'database' && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();
            } else {
                // Log a note for maintainers if this isn't possible
                \Illuminate\Support\Facades\Log::warning("Skipping session deletion for user {$user->id}. Session driver: {$driver}");
                $skippedDeletion = true;
            }

            return response()->json([
                'success' => true,
                'skipped_session_deletion' => $skippedDeletion,
                'message' => $skippedDeletion
                    ? "Sessions not deleted because session driver is not 'database' or sessions table missing."
                    : "Successfully logged out {$user->first_name} {$user->last_name} from all devices."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout user. Please try again.'
            ], 500);
        }
    }

    /**
     * Disable a user account for a specified duration
     */
    public function disableUser(Request $request, User $user)
    {
        Gate::authorize('admin');

        // Prevent an admin from disabling their own account
        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot disable your own account.'
            ], 403);
        }

        $request->validate([
            'duration' => 'required|in:1_week,1_month,indefinite,custom',
            'custom_disable_datetime' => 'required_if:duration,custom|date|after:now',
        ]);

        try {
            $duration = $request->duration;
            $now = now();

            // Calculate disabled_until based on duration
            switch ($duration) {
                case '1_week':
                    if (Schema::hasColumn('users', 'disabled_until')) {
                        $user->disabled_until = $now->copy()->addWeek();
                    }
                    break;
                case '1_month':
                    if (Schema::hasColumn('users', 'disabled_until')) {
                        $user->disabled_until = $now->copy()->addMonth();
                    }
                    break;
                case 'indefinite':
                    if (Schema::hasColumn('users', 'disabled_until')) {
                        // Use a sentinel far-future date that reliably indicates 'indefinite' and fits DATETIME range
                        $user->disabled_until = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '9999-12-31 23:59:59');
                    }
                    break;
                case 'custom':
                    if (Schema::hasColumn('users', 'disabled_until')) {
                        $user->disabled_until = $request->custom_disable_datetime;
                    }
                    break;
            }

            $user->is_active = false;
            $user->save();

            // Force logout the user from all devices if database sessions are enabled
            $driver = config('session.driver');
            if ($driver === 'database' && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();
            } else {
                \Illuminate\Support\Facades\Log::warning("Skipping session deletion for user {$user->id} (session driver: {$driver}).");
            }

            $userName = trim("{$user->first_name} {$user->last_name}");
            $message = "Account for {$userName} has been disabled";

            if (Schema::hasColumn('users', 'disabled_until') && $user->disabled_until) {
                if ($duration === 'indefinite') {
                    $message .= ' indefinitely.';
                } else {
                    $message .= " until " . (new \Carbon\Carbon($user->disabled_until))->format('M d, Y h:i A') . '.';
                }
            } else {
                // If the column is missing or value isn't set, append a generic message
                $message .= ' (no re-enable time recorded. Ensure migrations have been run.)';
            }

            $disabledAt = null;
            if (Schema::hasColumn('users', 'disabled_until') && $user->disabled_until) {
                $carbonUntil = new \Carbon\Carbon($user->disabled_until);
                // Return a special string for sentinel indefinite values
                $disabledAt = $carbonUntil->year >= 9999 ? 'indefinite' : $carbonUntil->toISOString();
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'disabled_until' => $disabledAt,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Disable user failed: ' . $e->getMessage(), ['exception' => $e]);
            // If disabled_until column is missing, provide actionable advice
            if (!Schema::hasColumn('users', 'disabled_until')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The disabled_until column is missing in the users table. Please run the latest migrations.'
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to disable user. Please try again.'
            ], 500);
        }
    }

    // ============================
    // Structure Template Requests
    // ============================

    /**
     * Display a listing of structure template requests from chairpersons.
     */
    public function indexStructureTemplateRequests(Request $request)
    {
        Gate::authorize('admin');

        $status = $request->query('status', 'all');

        $query = \App\Models\StructureTemplateRequest::with(['chairperson', 'reviewer']);

        if ($status === 'pending') {
            $query->pending();
        } elseif ($status === 'approved') {
            $query->approved();
        } elseif ($status === 'rejected') {
            $query->rejected();
        }

        $requests = $query->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->get();

        $pendingCount = \App\Models\StructureTemplateRequest::pending()->count();

        return view('admin.structure-template-requests', compact('requests', 'status', 'pendingCount'));
    }

    /**
     * Display the specified structure template request.
     */
    public function showStructureTemplateRequest(\App\Models\StructureTemplateRequest $request)
    {
        Gate::authorize('admin');

        $request->load(['chairperson', 'reviewer']);
        $structureCatalog = \App\Support\Grades\FormulaStructure::getAllStructureDefinitions();

        return view('admin.structure-template-request-show', compact('request', 'structureCatalog'));
    }

    /**
     * Approve a structure template request and create the template.
     */
    public function approveStructureTemplateRequest(Request $request, \App\Models\StructureTemplateRequest $templateRequest)
    {
        Gate::authorize('admin');

        if ($templateRequest->status !== 'pending') {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Only pending requests can be approved.']);
        }

        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $structureConfig = $templateRequest->structure_config;

            if (! is_array($structureConfig)) {
                throw new \InvalidArgumentException('The submitted structure is invalid.');
            }

            if ($this->isNewTemplateFormat($structureConfig)) {
                $structureConfig = $this->convertNewFormatToOld($structureConfig);
            }

            if (empty(data_get($structureConfig, 'children', []))) {
                throw new \InvalidArgumentException('The request does not contain any grading components.');
            }

            // Generate a unique template key
            $baseKey = Str::slug($templateRequest->label);
            if ($baseKey === '') {
                $baseKey = 'template-' . Str::random(6);
            }

            $templateKey = $baseKey;
            $counter = 1;

            while (StructureTemplate::where('template_key', $templateKey)->exists()) {
                $templateKey = $baseKey . '-' . $counter;
                $counter++;
            }

            $structureConfig = $templateRequest->structure_config ?? [];

            if ($this->isNewTemplateFormat(is_array($structureConfig) ? $structureConfig : [])) {
                $structureConfig = $this->convertNewFormatToOld($structureConfig);
            }

            try {
                $structureConfig = FormulaStructure::normalize($structureConfig);
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'structure_config' => 'The submitted template structure could not be normalized: ' . $exception->getMessage(),
                ]);
            }

            // Create the structure template
            StructureTemplate::create([
                'template_key' => $templateKey,
                'label' => $templateRequest->label,
                'description' => $templateRequest->description,
                'structure_config' => $structureConfig,
                'is_system_default' => false,
                'is_deleted' => false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Update the request status
            $templateRequest->status = 'approved';
            $templateRequest->admin_notes = $request->input('admin_notes');
            $templateRequest->reviewed_by = Auth::id();
            $templateRequest->reviewed_at = now();
            $templateRequest->save();

            DB::commit();

            return redirect()
                ->route('admin.structureTemplateRequests.index')
                ->with('success', "Structure template '{$templateRequest->label}' approved and added to the catalog.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to approve template request: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a structure template request.
     */
    public function rejectStructureTemplateRequest(Request $request, \App\Models\StructureTemplateRequest $templateRequest)
    {
        Gate::authorize('admin');

        if ($templateRequest->status !== 'pending') {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Only pending requests can be rejected.']);
        }

        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $templateRequest->status = 'rejected';
        $templateRequest->admin_notes = $request->input('admin_notes');
        $templateRequest->reviewed_by = Auth::id();
        $templateRequest->reviewed_at = now();
        $templateRequest->save();

        return redirect()
            ->route('admin.structureTemplateRequests.index')
            ->with('success', "Structure template request from {$templateRequest->chairperson->first_name} {$templateRequest->chairperson->last_name} has been rejected.");
    }

    /**
     * Get active session count for a user
     */
    public function getUserSessionCount(User $user)
    {
        Gate::authorize('admin');

        $driver = config('session.driver');
        $sessionCount = 0;
        if ($driver === 'database' && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            $sessionCount = DB::table('sessions')
                ->where('user_id', $user->id)
                ->count();
        } else {
            \Illuminate\Support\Facades\Log::debug("getUserSessionCount: skipped DB query; session driver={$driver}");
        }

        return response()->json([
            'success' => true,
            'count' => $sessionCount
        ]);
    }

    // ============================
    // Session Management
    // ============================

    /**
     * Display all active user sessions for admin management.
     *
     * @return \Illuminate\View\View
     */
    public function sessions(Request $request)
    {
        Gate::authorize('admin');

        $sessionsQuery = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select(
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'sessions.last_activity_at',
                'sessions.device_type',
                'sessions.browser',
                'sessions.platform',
                'sessions.device_fingerprint',
                DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'),
                'users.email',
                'users.role',
                'users.is_active'
            )
            ->whereNotNull('sessions.user_id')
            ->orderByDesc('sessions.last_activity');

        $sessions = $sessionsQuery->paginate(10, ['*'], 'sessions_page')->through(function ($session) {
                $lastActivity = \Carbon\Carbon::createFromTimestamp($session->last_activity);
                $session->last_activity_formatted = $session->last_activity_at
                    ? \Carbon\Carbon::parse($session->last_activity_at)->diffForHumans()
                    : $lastActivity->diffForHumans();
                $session->last_activity_date = $lastActivity->format('M d, Y g:i A');
                $session->is_current = $session->id === session()->getId();
                
                // Determine session status
                $minutesInactive = $lastActivity->diffInMinutes(now());
                $session->status = $minutesInactive > config('session.lifetime', 120) ? 'expired' : 'active';
                
                return $session;
            })->appends($request->except('logs_page'));

        // Get user logs with optional date filtering and pagination
        $userLogsQuery = UserLog::with('user')
            ->orderByDesc('created_at');

        if ($request->has('date') && $request->input('date')) {
            $date = $request->input('date');
            $userLogsQuery->whereDate('created_at', $date);
        }

        $userLogs = $userLogsQuery->paginate(10, ['*'], 'logs_page')->appends($request->except('sessions_page'));
        $selectedDate = $request->input('date', '');

        return view('admin.sessions', compact('sessions', 'userLogs', 'selectedDate'));
    }

    /**
     * Revoke a specific user session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revokeSession(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'session_id' => 'required|string',
            'password' => 'required|string',
        ]);

        // Verify admin password
        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput();
        }

        $sessionId = $request->input('session_id');
        
        // Prevent admin from revoking their own session
        if ($sessionId === session()->getId()) {
            return back()
                ->withErrors(['session_id' => 'You cannot revoke your own active session.'])
                ->withInput();
        }

        // Get session info before deletion for logging
        $sessionInfo = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.user_id', DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'))
            ->where('sessions.id', $sessionId)
            ->first();

        // Delete the session
        $deleted = DB::table('sessions')->where('id', $sessionId)->delete();

        if ($deleted && $sessionInfo) {
            // Log the session revocation
            DB::table('user_logs')->insert([
                'user_id' => $sessionInfo->user_id,
                'event_type' => 'session_revoked',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'browser' => (new \Jenssegers\Agent\Agent())->browser(),
                'device' => (new \Jenssegers\Agent\Agent())->device(),
                'platform' => (new \Jenssegers\Agent\Agent())->platform(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()
                ->route('admin.sessions')
                ->with('success', "Session for {$sessionInfo->user_name} has been revoked successfully.");
        }

        return back()
            ->withErrors(['session_id' => 'Session not found or already terminated.'])
            ->withInput();
    }

    /**
     * Revoke all sessions for a specific user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revokeUserSessions(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'password' => 'required|string',
        ]);

        // Verify admin password
        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput();
        }

        $userId = $request->input('user_id');
        
        // Prevent admin from revoking their own sessions
        if ($userId === Auth::id()) {
            return back()
                ->withErrors(['user_id' => 'You cannot revoke your own sessions.'])
                ->withInput();
        }

        // Get user info
        $user = User::findOrFail($userId);

        // Delete all sessions for the user
        $deleted = DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();

        if ($deleted > 0) {
            // Log the bulk session revocation
            DB::table('user_logs')->insert([
                'user_id' => $userId,
                'event_type' => 'all_sessions_revoked',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'browser' => (new \Jenssegers\Agent\Agent())->browser(),
                'device' => (new \Jenssegers\Agent\Agent())->device(),
                'platform' => (new \Jenssegers\Agent\Agent())->platform(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()
                ->route('admin.sessions')
                ->with('success', "All {$deleted} session(s) for {$user->full_name} have been revoked successfully.");
        }

        return back()
            ->withErrors(['user_id' => 'No active sessions found for this user.'])
            ->withInput();
    }

    /**
     * Revoke all sessions except the current admin session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revokeAllSessions(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify admin password
        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput();
        }

        $currentSessionId = session()->getId();

        // Delete all sessions except current admin session
        $deleted = DB::table('sessions')
            ->where('id', '!=', $currentSessionId)
            ->whereNotNull('user_id')
            ->delete();

        // Log the bulk revocation
        DB::table('user_logs')->insert([
            'user_id' => Auth::id(),
            'event_type' => 'bulk_sessions_revoked',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'browser' => (new \Jenssegers\Agent\Agent())->browser(),
            'device' => (new \Jenssegers\Agent\Agent())->device(),
            'platform' => (new \Jenssegers\Agent\Agent())->platform(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.sessions')
            ->with('success', "Successfully revoked {$deleted} user session(s). Your session remains active.");
    }
}
