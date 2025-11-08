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
use App\Models\TermGrade;
use App\Services\GradesFormulaService;
use App\Support\Grades\FormulaStructure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
        $selectedSemester = $periodContext['semester'];
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

        $departmentCatalogs = $this->applyPeriodFilters(
            GradesFormula::whereIn('department_id', $departmentIds)
                ->where('scope_level', 'department'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->get()
            ->groupBy('department_id');

        $courseFormulas = $this->applyPeriodFilters(
            GradesFormula::whereNotNull('course_id')
                ->where('scope_level', 'course'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->pluck('id', 'course_id');
        $subjectFormulas = $this->applyPeriodFilters(
            GradesFormula::whereNotNull('subject_id')
                ->where('scope_level', 'subject'),
            $selectedSemester,
            $selectedAcademicPeriodId
        )
            ->pluck('id', 'subject_id');

        $globalFormula = $this->getGlobalFormula();

        $departmentsSummary = $departments->map(function (Department $department) use ($fallbacks, $departmentCatalogs, $courseFormulas, $subjectFormulas, $globalFormula) {
            $courses = $department->courses;

            $courseCount = $courses->count();
            $coursesWithFormula = $courses->filter(fn ($course) => $courseFormulas->has($course->id))->count();

            $subjects = $courses->flatMap(fn (Course $course) => $course->subjects ?? collect());
            $subjectCount = $subjects->count();
            $subjectsWithFormula = $subjects->filter(fn ($subject) => $subjectFormulas->has($subject->id))->count();

            $fallback = $fallbacks->get($department->id) ?? $globalFormula;
            $catalog = $departmentCatalogs->get($department->id, collect());
            $nonFallbackCount = $catalog->filter(fn ($formula) => ! $formula->is_department_fallback)->count();

            $status = $nonFallbackCount > 0 ? 'custom' : 'default';
            $scopeText = $nonFallbackCount > 0
                ? 'Catalog ready with department-specific formulas.'
                : 'Using baseline department formula.';

            return [
                'department' => $department,
                'catalog_count' => $nonFallbackCount,
                'missing_course_count' => max($courseCount - $coursesWithFormula, 0),
                'missing_subject_count' => max($subjectCount - $subjectsWithFormula, 0),
                'formula_label' => $fallback->label ?? $globalFormula->label,
                'formula_scope' => 'Department Baseline',
                'status' => $status,
                'scope_text' => $scopeText,
            ];
        });

        return view('admin.grades-formula-wildcards', [
            'globalFormula' => $globalFormula,
            'departmentsSummary' => $departmentsSummary,
            'departments' => $departments,
            'departmentFallbacks' => $fallbacks,
            'departmentCatalogs' => $departmentCatalogs,
            'semester' => $selectedSemester,
            'academicPeriods' => $academicPeriods,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'availableSemesters' => $periodContext['available_semesters'],
        ]);
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

    public function destroyDepartmentFormula(Department $department, GradesFormula $formula)
    {
        Gate::authorize('admin');

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

        $structureDefinitions = collect(FormulaStructure::STRUCTURE_DEFINITIONS);

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
            ->map(function (array $definition, string $key) use ($baselineFormula, $baselineStructureType) {
                $structure = FormulaStructure::default($key);
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
        ]);
    }

    public function applySubjectFormula(Request $request, Subject $subject)
    {
        Gate::authorize('admin');

        if ($subject->is_deleted) {
            abort(404);
        }

        $validated = $request->validate([
            'department_formula_id' => ['nullable', 'integer'],
            'structure_type' => ['nullable', Rule::in(array_keys(FormulaStructure::STRUCTURE_DEFINITIONS))],
        ]);

        if (empty($validated['department_formula_id']) && empty($validated['structure_type'])) {
            return back()
                ->withErrors(['structure_type' => 'Select a structure template to apply.'])
                ->withInput();
        }

        $subject->load(['course.department']);

        if (! empty($validated['structure_type'])) {
            $periodContext = $this->resolveFormulaPeriodContext();

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

        $scopeRules = [
            'scope_level' => ['required', Rule::in(['department', 'course', 'subject'])],
            'label' => ['nullable', 'string', 'max:255'],
            'base_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scale_multiplier' => ['required', 'numeric', 'min:0', 'max:100'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'structure_type' => ['required', Rule::in(array_keys(FormulaStructure::STRUCTURE_DEFINITIONS))],
            'structure_config' => ['required', 'string'],
        ];

        $scope = $request->input('scope_level');
        $periodContext = $this->resolveFormulaPeriodContext();
        $selectedSemester = $periodContext['semester'];
        $selectedAcademicPeriodId = $periodContext['academic_period_id'];

        if ($scope === 'department') {
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

        if ($scope === 'department') {
            $department = Department::findOrFail($validated['department_id']);
        } elseif ($scope === 'course') {
            $course = Course::with('department')->findOrFail($validated['course_id']);
            $department = $course->department;
        } elseif ($scope === 'subject') {
            $subject = Subject::with(['course.department'])->findOrFail($validated['subject_id']);
            $course = $subject->course;
            $department = $subject->department ?? $course?->department;
        }

        $label = $validated['label'] ?? match ($scope) {
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

        $rules = [
            'label' => ['nullable', 'string', 'max:255'],
            'base_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scale_multiplier' => ['required', 'numeric', 'min:0', 'max:100'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'structure_type' => ['required', Rule::in(array_keys(FormulaStructure::STRUCTURE_DEFINITIONS))],
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
        $structure = FormulaStructure::normalize(FormulaStructure::default($structureType));

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
        $structure = $formula->structure_config ?? \App\Support\Grades\FormulaStructure::default($type);

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
                \App\Support\Grades\FormulaStructure::default($type)
            ),
        ];
    }

    private function getStructureCatalog(): array
    {
        return collect(FormulaStructure::STRUCTURE_DEFINITIONS)
            ->mapWithKeys(function ($meta, $key) {
                return [
                    $key => [
                        'label' => $meta['label'],
                        'description' => $meta['description'],
                        'structure' => FormulaStructure::toPercentPayload(FormulaStructure::default($key)),
                    ],
                ];
            })
            ->toArray();
    }


    public function viewUsers()
    {
        Gate::authorize('admin');

        $users = User::whereIn('role', [1, 2, 3, 5])
            ->orderBy('role', 'asc')
            ->get();

        $departments = Department::all();
        $courses = Course::all();

        return view('admin.users', compact('users', 'departments', 'courses'));
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
}
