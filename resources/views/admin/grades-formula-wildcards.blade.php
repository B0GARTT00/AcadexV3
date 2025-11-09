@extends('layouts.app')

@php
    $request = request();
    $queryParams = $request->query();

    $allowedSections = ['overview', 'formulas', 'departments'];
    $initialSection = $request->query('view');
    if (! in_array($initialSection, $allowedSections, true)) {
        $initialSection = 'overview';
    }

    if ($errors->any() || old('department_id') || ! empty($bulkConflicts ?? [])) {
        $initialSection = 'departments';
    }

    $overviewActive = $initialSection === 'overview';
    $formulasActive = $initialSection === 'formulas';
    $departmentsActive = $initialSection === 'departments';

    $departmentCount = $departmentsSummary->count();
    $overrideCount = $departmentsSummary->filter(fn ($summary) => ($summary['catalog_count'] ?? 0) > 0)->count();
    $defaultCount = max($departmentCount - $overrideCount, 0);

    $preservedQuery = \Illuminate\Support\Arr::only($queryParams, ['semester', 'academic_year', 'academic_period_id']);

    $buildRoute = function (string $name, array $parameters = []) use ($preservedQuery) {
        return route($name, array_merge($parameters, $preservedQuery));
    };

    $periodLookup = collect($academicPeriods ?? [])->keyBy('id');

    $departmentBlueprints = $departments->map(function ($department) use (
        $departmentCatalogs,
        $departmentFallbacks,
        $buildRoute,
        $semester,
        $selectedAcademicPeriodId,
        $periodLookup
    ) {
        $formulas = $departmentCatalogs->get($department->id, collect());

        if ($formulas->isEmpty()) {
            $fallback = $departmentFallbacks->get($department->id);
            if ($fallback) {
                $formulas = collect([$fallback]);
            }
        }

        $sortedFormulas = $formulas
            ->sortBy(function ($formula) use ($semester, $selectedAcademicPeriodId) {
                $priority = $formula->is_department_fallback ? 0 : 1;
                $semesterMatches = $semester === null
                    ? $formula->semester === null
                    : $formula->semester === $semester;

                $periodMatches = $selectedAcademicPeriodId === null
                    ? $formula->academic_period_id === null
                    : (int) $formula->academic_period_id === (int) $selectedAcademicPeriodId;

                $contextPriority = ($semesterMatches && $periodMatches) ? 0 : 1;

                return sprintf('%d-%d-%s', $priority, $contextPriority, mb_strtolower($formula->label ?? ''));
            })
            ->values();

        return [
            'id' => $department->id,
            'code' => $department->department_code,
            'name' => $department->department_description,
            'department_url' => $buildRoute('admin.gradesFormula.department', ['department' => $department->id]),
            'create_formula_url' => $buildRoute('admin.gradesFormula.department.formulas.create', ['department' => $department->id]),
            'edit_fallback_url' => $buildRoute('admin.gradesFormula.edit.department', ['department' => $department->id]),
            'formulas' => $sortedFormulas->map(function ($formula) use ($department, $buildRoute, $semester, $selectedAcademicPeriodId, $periodLookup) {
                $weights = collect($formula->weight_map)
                    ->map(function ($weight, $type) {
                        return [
                            'type' => strtoupper($type),
                            'percent' => number_format($weight * 100, 0),
                        ];
                    })
                    ->values();

                $editUrl = $formula->is_department_fallback
                    ? $buildRoute('admin.gradesFormula.edit.department', ['department' => $department->id])
                    : $buildRoute('admin.gradesFormula.department.formulas.edit', ['department' => $department->id, 'formula' => $formula->id]);

                $semesterMatches = $semester === null
                    ? $formula->semester === null
                    : $formula->semester === $semester;

                $periodMatches = $selectedAcademicPeriodId === null
                    ? $formula->academic_period_id === null
                    : (int) $formula->academic_period_id === (int) $selectedAcademicPeriodId;

                $contextMatches = $semesterMatches && $periodMatches;

                $contextParts = [];
                if ($formula->academic_period_id) {
                    $period = $periodLookup->get($formula->academic_period_id);
                    if ($period) {
                        $contextParts[] = trim(($period->academic_year ?? '') !== '' ? $period->academic_year : 'Academic Period #' . $formula->academic_period_id);
                        if (! empty($period->semester)) {
                            $contextParts[] = trim($period->semester) . ' Semester';
                        }
                    } else {
                        $contextParts[] = 'Academic Period #' . $formula->academic_period_id;
                    }
                }

                if ($formula->academic_period_id === null && $formula->semester) {
                    $contextParts[] = trim($formula->semester) . ' Semester';
                }

                if (empty($contextParts)) {
                    $contextParts[] = 'Applies to all periods';
                }

                $contextLabel = implode(' · ', array_filter($contextParts));

                return [
                    'id' => $formula->id,
                    'label' => $formula->label,
                    'base_score' => $formula->base_score,
                    'scale_multiplier' => $formula->scale_multiplier,
                    'passing_grade' => $formula->passing_grade,
                    'is_fallback' => (bool) $formula->is_department_fallback,
                    'context_match' => $contextMatches,
                    'context_label' => $contextLabel,
                    'semester' => $formula->semester,
                    'academic_period_id' => $formula->academic_period_id,
                    'weights' => $weights,
                    'edit_url' => $editUrl,
                    'updated_at' => optional($formula->updated_at)->diffForHumans() ?? 'Recently updated',
                ];
            })->values(),
            'courses' => ($department->courses ?? collect())->map(function ($course) {
                $subjects = $course->subjects ?? collect();

                $subjectPayload = $subjects->map(function ($subject) {
                    $subjectLabel = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? ''));
                    if ($subjectLabel === '') {
                        $subjectLabel = 'Unnamed Subject';
                    }

                    return [
                        'id' => $subject->id,
                        'label' => $subjectLabel,
                        'has_grades' => (bool) $subject->getAttribute('has_recorded_grades'),
                    ];
                })->values();

                $courseLabel = trim(($course->course_code ? $course->course_code . ' - ' : '') . ($course->course_description ?? ''));

                return [
                    'id' => $course->id,
                    'code' => $course->course_code,
                    'name' => $course->course_description,
                    'label' => $courseLabel !== '' ? $courseLabel : 'Course',
                    'subjects' => $subjectPayload,
                ];
            })->values(),
        ];
    })->values();

    $departmentFormulaCount = $departmentBlueprints->sum(function ($blueprint) {
        return collect($blueprint['formulas'] ?? [])->count();
    });

    $structureTemplates = collect($structureCatalog ?? []);
    $structureTemplatePayload = $structureTemplates->map(function ($template) {
        return [
            'key' => $template['key'] ?? '',
            'label' => $template['label'] ?? '',
            'description' => $template['description'] ?? '',
            'weights' => collect($template['weights'] ?? [])->map(function ($weight) {
                return [
                    'type' => $weight['type'] ?? '',
                    'percent' => (int) ($weight['percent'] ?? 0),
                ];
            })->values()->all(),
        ];
    })->values();

    $formulaCount = $structureTemplates->count();

    $departmentOptions = $departments->map(function ($department) {
        $label = trim(($department->department_code ? $department->department_code . ' - ' : '') . ($department->department_description ?? 'Department'));

        return [
            'id' => $department->id,
            'label' => $label !== '' ? $label : 'Department',
        ];
    })->values();

    $bulkConflicts = $bulkConflicts ?? [];
    $requiresBulkPassword = (bool) ($requiresBulkPassword ?? false);
@endphp

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-2">
        <div class="col">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        <i class="bi bi-sliders me-1"></i>Grades Formula
                    </li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-sliders text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">Grades Formula Management</h4>
                        <small class="text-muted">Select a wildcard to manage its grading scale</small>
                    </div>
                </div>
                <a href="{{ route('admin.gradesFormula') }}" class="btn btn-outline-success btn-sm rounded-pill shadow-sm">
                    <i class="bi bi-calendar-week me-1"></i>Change Academic Period
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @unless($departments->flatMap->courses->flatMap->subjects->isNotEmpty())
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-journal-x me-2"></i>No subjects are registered yet. Add subjects under Courses to start configuring subject-level formulas.
        </div>
    @endunless

    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #198754, #20c997); color: white;">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle" style="background: rgba(255,255,255,0.15);">
                        <i class="bi bi-collection text-white" style="font-size: 1rem;"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-bold">Wildcard Summary</h6>
                        <small class="opacity-90">{{ $departmentCount }} departments · {{ $overrideCount }} with catalogs · {{ $defaultCount }} using baseline</small>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="bg-white bg-opacity-25 rounded-pill px-3 py-1 d-inline-flex align-items-center gap-2">
                        <small class="fw-semibold text-dark mb-0">
                            <i class="bi bi-lightbulb me-1"></i>Click a card to review or edit its formula
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-layout-three-columns text-success"></i>
                    <span class="fw-semibold text-success">Workspace views</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm rounded-pill wildcard-section-btn {{ $overviewActive ? 'btn-success active' : 'btn-outline-success' }}" data-section-target="overview">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Overview
                        <span class="badge bg-white text-success ms-1">{{ $departmentCount }}</span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill wildcard-section-btn {{ $formulasActive ? 'btn-success active' : 'btn-outline-success' }}" data-section-target="formulas">
                        <i class="bi bi-star-fill me-1"></i>Formulas
                        <span class="badge bg-success text-white ms-1">{{ $formulaCount }}</span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill wildcard-section-btn {{ $departmentsActive ? 'btn-success active' : 'btn-outline-success' }}" data-section-target="departments">
                        <i class="bi bi-diagram-3 me-1"></i>Departments
                        <span class="badge bg-success text-white ms-1">{{ $departmentCount }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div data-section-container data-initial-section="{{ $initialSection }}">
        <div data-section="overview" class="{{ $overviewActive ? '' : 'd-none' }}">
            <div class="section-scroll">
                <div class="d-flex justify-content-end mb-3">
                <form method="GET" action="{{ route('admin.gradesFormula') }}" class="d-flex align-items-center gap-2">
                    <label class="text-success small mb-0">Semester</label>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 180px;">
                        <option value="" {{ request('semester') ? '' : 'selected' }}>All/Default</option>
                        <option value="1st" {{ request('semester')==='1st' ? 'selected' : '' }}>1st</option>
                        <option value="2nd" {{ request('semester')==='2nd' ? 'selected' : '' }}>2nd</option>
                        <option value="Summer" {{ request('semester')==='Summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                    @foreach ($queryParams as $key => $value)
                        @if($key !== 'semester')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                </form>
                </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-filter text-success"></i>
                            <span class="fw-semibold text-success">Filter departments</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-success btn-sm rounded-pill view-filter-btn" data-status-filter="all">
                                <i class="bi bi-grid-1x2-fill me-1"></i>All
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill view-filter-btn" data-status-filter="custom">
                                <i class="bi bi-star-fill me-1"></i>Catalogs
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill view-filter-btn" data-status-filter="default">
                                <i class="bi bi-shield-check me-1"></i>Baseline
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4" id="overview-department-grid">
                @php
                    $defaultBadgeLabel = optional($globalFormula)->label ?? 'System Default';
                @endphp
                @foreach($departmentsSummary as $summary)
                    @php
                        $department = $summary['department'];
                        $status = $summary['status'];
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" data-status="{{ $status }}" data-url="{{ $buildRoute('admin.gradesFormula.department', ['department' => $department->id]) }}">
                            <div class="position-relative" style="height: 80px; background: linear-gradient(135deg, #0f5132, #198754);"></div>
                            <div class="wildcard-circle" style="background: linear-gradient(135deg, #23a362, #0b3d23);">
                                <span class="text-white fw-bold">{{ $department->department_code }}</span>
                            </div>
                            <div class="card-body pt-5 text-center d-flex flex-column align-items-center gap-3">
                                <div>
                                    <h6 class="fw-semibold mt-2 text-dark wildcard-title" title="{{ $department->department_description }}">
                                        {{ $department->department_description }}
                                    </h6>
                                    <p class="text-muted small mb-0">{{ $summary['scope_text'] }}</p>
                                </div>
                                <div class="d-flex flex-column gap-2 w-100">
                                    @if($summary['missing_course_count'] > 0)
                                        <span class="badge bg-danger-subtle text-danger">{{ $summary['missing_course_count'] }} course{{ $summary['missing_course_count'] === 1 ? '' : 's' }} pending</span>
                                    @endif
                                    @if($summary['missing_subject_count'] > 0)
                                        <span class="badge bg-warning-subtle text-warning">{{ $summary['missing_subject_count'] }} subject{{ $summary['missing_subject_count'] === 1 ? '' : 's' }} pending</span>
                                    @endif
                                    <span class="badge rounded-pill {{ $summary['catalog_count'] > 0 ? 'bg-success-subtle text-success' : 'bg-light text-secondary' }}">Department Baseline</span>
                                    <span class="badge rounded-pill badge-formula-label">{{ $summary['formula_label'] ?? $defaultBadgeLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($departmentCount === 0)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-5 text-center">
                                <div class="text-muted mb-3">
                                    <i class="bi bi-collection fs-1 opacity-50"></i>
                                </div>
                                <h5 class="text-muted mb-2">No departments available</h5>
                                <p class="text-muted mb-0">Add at least one department to configure formulas.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="text-center py-5">
                <div class="card border-0 shadow-sm mx-auto" style="max-width: 520px;">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <div class="p-4 rounded-circle mx-auto d-inline-flex" style="background: linear-gradient(135deg, #198754, #20c997);">
                                <i class="bi bi-hand-index-thumb text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3" style="color: #198754;">Pick a wildcard to continue</h4>
                        <p class="text-muted mb-0">
                            Choose a department card above to view and edit its grading formula on a dedicated page.
                        </p>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <div data-section="formulas" class="{{ $formulasActive ? '' : 'd-none' }}">
            <div class="section-scroll">
            <div class="row g-4">
                @forelse($structureTemplates as $template)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="structure-card card h-100 border-0 shadow-lg rounded-4">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h5 class="fw-semibold text-dark mb-1">{{ $template['label'] }}</h5>
                                        <p class="text-muted small mb-0">{{ $template['description'] }}</p>
                                    </div>
                                    <span class="badge bg-success-subtle text-success">Structure</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($template['weights'] as $weight)
                                        <span class="badge bg-success-subtle text-success">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                    @endforeach
                                </div>
                                <div class="structure-card-footer mt-auto">
                                    <small class="text-muted">Select this template when creating a new formula to start with the recommended weights.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info shadow-sm mb-0">
                            <i class="bi bi-info-circle me-2"></i>No structure templates available yet.
                        </div>
                    </div>
                @endforelse
            </div>
            </div>
        </div>

        <div data-section="departments" class="{{ $departmentsActive ? '' : 'd-none' }}">
            <div class="section-scroll">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4 d-flex gap-3 align-items-start">
                        <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #198754, #20c997);">
                            <i class="bi bi-diagram-3 text-white" style="font-size: 1.25rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold mb-1" style="color: #198754;">Bulk apply department formulas</h5>
                            <p class="text-muted mb-0">Select a department formula and push it to specific courses. Subjects inherit the course formula unless a subject override exists.</p>
                        </div>
                    </div>
                </div>

                @if ($departments->isEmpty())
                    <div class="alert alert-info shadow-sm mb-0">
                        <i class="bi bi-info-circle me-2"></i>No departments available. Add departments before applying formulas.
                    </div>
                @else
                    @if ($errors->any())
                        <div class="alert alert-danger shadow-sm">
                            <i class="bi bi-exclamation-triangle me-2"></i>Please resolve the errors below before applying the formula.
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="bulk-conflict-server" class="{{ empty($bulkConflicts) ? 'd-none' : '' }}">
                        <div class="alert alert-warning shadow-sm">
                            <div class="fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Recorded grades detected</div>
                            <p class="text-muted small mb-2">Enter your password to confirm overwriting the grading baseline for the following subjects:</p>
                            <ul class="mb-0 ps-3">
                                @foreach($bulkConflicts as $entry)
                                    <li>{{ $entry['subject'] ?? 'Subject' }} <span class="text-muted">({{ $entry['course'] ?? 'Course' }})</span></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ $buildRoute('admin.gradesFormula.department.bulkApply') }}" id="bulk-apply-form" data-old-department-id="{{ old('department_id') }}" data-old-formula-id="{{ old('department_formula_id') }}" data-old-course-ids='@json(array_map("intval", old('course_ids', [])))' data-server-conflicts='@json($bulkConflicts)' data-requires-password="{{ $requiresBulkPassword ? '1' : '0' }}" data-password-error="{{ $errors->has('current_password') ? '1' : '0' }}">
                                @csrf
                                <input type="hidden" name="current_password" id="bulk-password-hidden">

                                <div class="row g-4">
                                    <div class="col-12 col-lg-4">
                                        <label for="bulk-department-select" class="form-label fw-semibold text-success">Department</label>
                                        <select class="form-select" id="bulk-department-select" name="department_id" required>
                                            <option value="">-- Select Department --</option>
                                            @foreach ($departmentOptions as $option)
                                                <option value="{{ $option['id'] }}" {{ (string) old('department_id') === (string) $option['id'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-2">Pick the department whose formula you want to push to one or more courses.</small>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                            <label class="form-label fw-semibold text-success mb-0">Department Formulas</label>
                                            <div class="btn-group btn-group-sm d-none" id="bulk-formula-filter-group" role="group" aria-label="Formula filters">
                                                <button type="button" class="btn btn-outline-success active" data-formula-filter="all">All</button>
                                                <button type="button" class="btn btn-outline-success" data-formula-filter="catalog">Catalog</button>
                                                <button type="button" class="btn btn-outline-success" data-formula-filter="fallback">Fallback</button>
                                            </div>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text text-success bg-white"><i class="bi bi-search"></i></span>
                                            <input type="search" class="form-control" id="bulk-formula-search" placeholder="Search formulas" autocomplete="off" disabled>
                                            <button class="btn btn-outline-secondary" type="button" id="bulk-formula-search-clear" disabled>
                                                <span class="visually-hidden">Reset formula search</span>
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div id="bulk-formula-meta" class="text-muted small mb-2">Select a department to load available formulas.</div>
                                        <div id="bulk-formula-options" class="formula-list border rounded-4 p-3 bg-light bg-opacity-50">
                                            <div class="text-muted small">Select a department to load available formulas.</div>
                                        </div>
                                        <small class="text-muted d-block mt-2">Choose one catalog entry or fallback baseline to apply to the selected courses.</small>
                                        @error('department_formula_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div id="bulk-formula-selection-hint" class="text-danger small mt-2 d-none">Select a formula to continue.</div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                            <label class="form-label fw-semibold text-success mb-0">Courses</label>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Course selection shortcuts">
                                                <button class="btn btn-outline-success" type="button" id="bulk-select-all" title="Select all courses currently in view">Select visible</button>
                                                <button class="btn btn-outline-secondary" type="button" id="bulk-clear-all" title="Clear the current selection">Clear</button>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center mb-2">
                                            <div class="input-group input-group-sm flex-grow-1">
                                                <span class="input-group-text text-success bg-white"><i class="bi bi-search"></i></span>
                                                <input type="search" class="form-control" id="bulk-course-search" placeholder="Search courses or subjects" autocomplete="off" disabled>
                                            </div>
                                            <button class="btn btn-outline-secondary btn-sm flex-shrink-0" type="button" id="bulk-course-search-clear" disabled>
                                                <i class="bi bi-x-lg me-1"></i>Reset
                                            </button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
                                            <div id="bulk-course-summary" class="text-muted small">No courses selected yet.</div>
                                            <div id="bulk-course-count" class="text-muted small"></div>
                                        </div>
                                        <div id="bulk-course-options" class="bulk-course-scroll rounded-4 p-3 bg-light bg-opacity-50">
                                            <div class="text-muted small">Select a department to load courses.</div>
                                        </div>
                                        @error('course_ids')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div id="bulk-conflict-container" class="d-none mt-4"></div>
                                <div id="bulk-password-error" role="alert" aria-live="assertive" class="text-danger small mt-2 {{ $errors->has('current_password') ? '' : 'd-none' }}">
                                    @error('current_password'){{ $message }}@enderror
                                </div>

                                <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center gap-2 mt-4">
                                    <a href="#" class="btn btn-outline-secondary btn-lg d-none" id="bulk-manage-link">
                                        <i class="bi bi-diagram-3 me-1"></i>Manage Department
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg" id="bulk-apply-button">
                                        Apply Formula to Courses
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulk-password-modal" tabindex="-1" aria-labelledby="bulk-password-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold text-success" id="bulk-password-modal-label">
                    <i class="bi bi-shield-lock me-2"></i>Confirm bulk update
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">This operation can overwrite recorded grades. Confirm your account password to continue.</p>
                <div class="mb-2">
                    <label for="bulk-password-input" class="form-label fw-semibold text-success">Account password</label>
                    <input type="password" class="form-control" id="bulk-password-input" autocomplete="current-password" placeholder="Enter your password">
                    <div class="invalid-feedback">Password is required.</div>
                </div>
                <div id="bulk-password-modal-error" class="text-danger small d-none" role="alert" aria-live="assertive"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="bulk-password-confirm">
                    <i class="bi bi-check-circle me-1"></i>Confirm and apply
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sectionButtons = document.querySelectorAll('.wildcard-section-btn');
        const sectionContainer = document.querySelector('[data-section-container]');
        const sections = sectionContainer ? sectionContainer.querySelectorAll('[data-section]') : [];

        const setActiveSection = (sectionName) => {
            sections.forEach((section) => {
                const matches = section.dataset.section === sectionName;
                section.classList.toggle('d-none', ! matches);
            });

            sectionButtons.forEach((button) => {
                const matches = button.dataset.sectionTarget === sectionName;
                button.classList.toggle('btn-success', matches);
                button.classList.toggle('active', matches);
                if (! matches) {
                    button.classList.add('btn-outline-success');
                } else {
                    button.classList.remove('btn-outline-success');
                }
            });
        };

        const initialSection = sectionContainer?.dataset.initialSection ?? 'overview';
        setActiveSection(initialSection);

        sectionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.sectionTarget;
                if (! target) {
                    return;
                }
                setActiveSection(target);
            });
        });

        const overviewCards = document.querySelectorAll('#overview-department-grid .wildcard-card');
        const filterButtons = document.querySelectorAll('.view-filter-btn');

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.statusFilter ?? 'all';

                filterButtons.forEach((btn) => {
                    btn.classList.toggle('btn-success', btn === button);
                    btn.classList.toggle('btn-outline-success', btn !== button);
                });

                overviewCards.forEach((card) => {
                    const status = card.dataset.status;
                    const matches = filter === 'all' || status === filter;
                    card.parentElement.classList.toggle('d-none', ! matches);
                });
            });
        });

        overviewCards.forEach((card) => {
            const titleElement = card.querySelector('.wildcard-title');
            if (titleElement && ! card.getAttribute('aria-label')) {
                const label = titleElement.textContent?.trim();
                if (label) {
                    card.setAttribute('aria-label', `View ${label} formula details`);
                }
            }

            card.setAttribute('role', 'link');
            card.setAttribute('tabindex', '0');

            const clearPressedState = () => {
                card.classList.remove('is-pressed');
            };

            card.addEventListener('pointerdown', () => {
                card.classList.add('is-pressed');
            });

            card.addEventListener('pointerup', clearPressedState);
            card.addEventListener('pointerleave', clearPressedState);
            card.addEventListener('blur', clearPressedState);
            card.addEventListener('keyup', (event) => {
                if (event.key === 'Tab' || event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
                    clearPressedState();
                }
            });

            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    card.classList.add('is-pressed');
                    card.click();
                }
            });

            card.addEventListener('click', (event) => {
                const url = card.dataset.url;
                if (! url) {
                    return;
                }

                const isInteractiveChild = event.target.closest('a, button, form, input, label');
                if (isInteractiveChild) {
                    clearPressedState();
                    return;
                }

                clearPressedState();
                window.location.href = url;
            });
        });

        const bulkForm = document.getElementById('bulk-apply-form');
        if (! bulkForm) {
            return;
        }

        const departmentSelect = document.getElementById('bulk-department-select');
    const formulaContainer = document.getElementById('bulk-formula-options');
    const formulaMeta = document.getElementById('bulk-formula-meta');
    const formulaFilterGroup = document.getElementById('bulk-formula-filter-group');
    const formulaFilterButtons = formulaFilterGroup ? formulaFilterGroup.querySelectorAll('[data-formula-filter]') : [];
    const formulaSearchInput = document.getElementById('bulk-formula-search');
    const formulaSearchClear = document.getElementById('bulk-formula-search-clear');
    const formulaSelectionHint = document.getElementById('bulk-formula-selection-hint');
    const courseContainer = document.getElementById('bulk-course-options');
    const courseSummary = document.getElementById('bulk-course-summary');
    const courseCountLabel = document.getElementById('bulk-course-count');
    const courseSearchInput = document.getElementById('bulk-course-search');
    const courseSearchClear = document.getElementById('bulk-course-search-clear');
        const hiddenPasswordInput = document.getElementById('bulk-password-hidden');
        const passwordFormError = document.getElementById('bulk-password-error');
        const passwordModalElement = document.getElementById('bulk-password-modal');
        const passwordModalInput = document.getElementById('bulk-password-input');
        const passwordModalError = document.getElementById('bulk-password-modal-error');
        const passwordModalConfirm = document.getElementById('bulk-password-confirm');
        const bulkApplyButton = document.getElementById('bulk-apply-button');
        const selectAllBtn = document.getElementById('bulk-select-all');
        const clearAllBtn = document.getElementById('bulk-clear-all');
        const manageLink = document.getElementById('bulk-manage-link');
        const conflictContainer = document.getElementById('bulk-conflict-container');
        const serverConflictWrapper = document.getElementById('bulk-conflict-server');
        const bootstrapModal = window.bootstrap?.Modal;
        const passwordModalInstance = passwordModalElement && bootstrapModal ? new bootstrapModal(passwordModalElement) : null;
        const structureTemplates = @json($structureTemplatePayload);
        const baselinePasswordRequirement = bulkForm.dataset.requiresPassword === '1';

        let currentDepartment = null;
        let currentFormulaId = null;
        const selectedCourses = new Set();
        const expandedCourses = new Set();
        let availableFormulas = [];
        let currentFormulaFilter = 'all';
        let currentFormulaSearch = '';
        let allCourses = [];
        let currentCourseSearch = '';
    let visibleCourseIds = [];
    let totalCourseCount = 0;
    let visibleCourseCount = 0;
        let departmentBlueprints = @json($departmentBlueprints);
        if (! Array.isArray(departmentBlueprints)) {
            departmentBlueprints = [];
        }

        let serverConflictEntries = [];
        try {
            serverConflictEntries = JSON.parse(bulkForm.dataset.serverConflicts || '[]');
        } catch (error) {
            serverConflictEntries = [];
        }

        let hasServerConflicts = Array.isArray(serverConflictEntries) && serverConflictEntries.length > 0;
        let passwordRequired = baselinePasswordRequirement || hasServerConflicts;
        let isSubmitting = false;

        if (bulkForm.dataset.passwordError === '1') {
            passwordRequired = true;
        }

        const syncServerConflictVisibility = () => {
            if (! serverConflictWrapper) {
                return;
            }
            serverConflictWrapper.classList.toggle('d-none', ! hasServerConflicts);
        };
        syncServerConflictVisibility();

        const formatGradeNumber = (value) => {
            const numeric = Number(value);
            if (Number.isFinite(numeric)) {
                return numeric.toFixed(0);
            }
            return typeof value === 'string' && value.trim() !== '' ? value : '—';
        };

        const escapeHtml = (value) => {
            if (value === null || value === undefined) {
                return '';
            }
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const getSelectedCourseRecords = () => {
            const courses = Array.isArray(allCourses) ? allCourses : [];
            return courses.filter((course) => selectedCourses.has(String(course.id)));
        };

        const refreshFormulaSelectionStyles = () => {
            if (! formulaContainer) {
                return;
            }
            formulaContainer.querySelectorAll('.formula-card').forEach((card) => {
                const input = card.querySelector('input[type="radio"]');
                card.classList.toggle('is-selected', Boolean(input?.checked));
            });
        };

        const syncApplyButtonState = () => {
            const formulaSelected = Boolean(currentFormulaId);
            const coursesSelected = selectedCourses.size > 0;

            if (bulkApplyButton) {
                bulkApplyButton.disabled = ! formulaSelected || ! coursesSelected;
            }

            if (formulaSelectionHint) {
                const shouldShowHint = ! formulaSelected && availableFormulas.length > 0;
                formulaSelectionHint.classList.toggle('d-none', ! shouldShowHint);
            }
        };

        const renderStructureTemplateMarkup = () => {
            if (! currentDepartment || ! Array.isArray(structureTemplates) || structureTemplates.length === 0) {
                return '';
            }

            const createUrl = currentDepartment.create_formula_url;
            if (! createUrl) {
                return '';
            }

            const cards = structureTemplates.map((template) => {
                const weightBadges = (template.weights ?? []).map((weight) => `
                    <span class="badge bg-success-subtle text-success">${escapeHtml(weight.type ?? '')} ${escapeHtml(weight.percent ?? '')}%</span>
                `).join('');

                return `
                    <div class="structure-template-card border rounded-4 p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h6 class="fw-semibold mb-1 text-dark">${escapeHtml(template.label ?? 'Structure')}</h6>
                                <p class="text-muted small mb-2">${escapeHtml(template.description ?? '')}</p>
                            </div>
                            <span class="badge bg-light text-secondary">Template</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            ${weightBadges}
                        </div>
                        <a class="btn btn-sm btn-outline-success rounded-pill" href="${createUrl}?structure=${encodeURIComponent(template.key)}">
                            <i class="bi bi-plus-circle me-1"></i>Create from Template
                        </a>
                    </div>
                `;
            }).join('');

            return `
                <div class="structure-template-wrapper mt-3">
                    <div class="text-muted small mb-2">Need a starting point? Use a template to create or update ${escapeHtml(currentDepartment.name ?? 'this department')}'s baseline formula, then select the saved entry above to apply it to courses.</div>
                    <div class="structure-template-grid">
                        ${cards}
                    </div>
                </div>
            `;
        };

        const renderFormulaOptions = (formulas = []) => {
            if (! formulaContainer) {
                return;
            }

            if (! currentFormulaId && bulkForm.dataset.oldFormulaId) {
                currentFormulaId = String(bulkForm.dataset.oldFormulaId);
            }
            bulkForm.dataset.oldFormulaId = '';

            availableFormulas = Array.isArray(formulas) ? formulas : [];
            const totalCount = availableFormulas.length;
            const catalogCount = availableFormulas.filter((formula) => ! formula.is_fallback).length;
            const fallbackCount = totalCount - catalogCount;
            const matchingCatalogCount = availableFormulas.filter((formula) => ! formula.is_fallback && formula.context_match).length;
            const matchingTotalCount = availableFormulas.filter((formula) => formula.context_match).length;

            if (formulaSearchInput) {
                formulaSearchInput.disabled = totalCount === 0;
                if (totalCount === 0) {
                    currentFormulaSearch = '';
                    formulaSearchInput.value = '';
                }
            }
            if (formulaSearchClear) {
                formulaSearchClear.disabled = totalCount === 0 || currentFormulaSearch === '';
            }

            if (formulaFilterGroup) {
                formulaFilterGroup.classList.toggle('d-none', totalCount === 0);
            }

            const normalizedSearch = currentFormulaSearch.trim().toLowerCase();
            const filteredFormulas = availableFormulas.filter((formula) => {
                if (currentFormulaFilter === 'catalog' && formula.is_fallback) {
                    return false;
                }
                if (currentFormulaFilter === 'fallback') {
                    return true;
                }

                if (normalizedSearch !== '') {
                    const haystack = [
                        formula.label ?? '',
                        formatGradeNumber(formula.base_score),
                        formatGradeNumber(formula.scale_multiplier),
                        formatGradeNumber(formula.passing_grade),
                        ...(formula.weights ?? []).map((weight) => `${weight.type ?? ''} ${weight.percent ?? ''}%`),
                    ].join(' ').toLowerCase();

                    if (! haystack.includes(normalizedSearch)) {
                        return false;
                    }
                }

                return true;
            });

            let selectionExists = false;
            if (currentFormulaId) {
                selectionExists = availableFormulas.some((formula) => String(formula.id) === String(currentFormulaId));
                if (! selectionExists) {
                    currentFormulaId = null;
                }
            }

            let selectionVisible = false;
            if (currentFormulaId) {
                selectionVisible = filteredFormulas.some((formula) => String(formula.id) === String(currentFormulaId));
            }

            const selectionHiddenNotice = (selectionExists && ! selectionVisible && filteredFormulas.length === 0 && (normalizedSearch !== '' || currentFormulaFilter !== 'all'))
                ? '<div class="alert alert-warning shadow-sm small"><i class="bi bi-info-circle me-1"></i>The selected formula is hidden by the current filters. Adjust or clear the filters to review it.</div>'
                : '';

            const fallbackHelper = currentFormulaFilter === 'fallback' && totalCount > 0
                ? '<div class="text-muted small mb-2">All department formulas appear below; the active fallback is highlighted for quick review.</div>'
                : '';

            let markup = '';

            if (filteredFormulas.length === 0) {
                const hint = totalCount === 0 ? 'Create one from a template below.' : 'Reset the filters to view all available formulas.';
                markup = `<div class="text-muted small">No formulas match the current filters. ${escapeHtml(hint)}</div>`;
            } else {
                markup = filteredFormulas.map((formula) => {
                    const formulaId = String(formula.id);
                    const checked = currentFormulaId && formulaId === String(currentFormulaId);
                    const weights = (formula.weights ?? []).map((weight) => `
                        <span class="badge bg-success-subtle text-success">${escapeHtml(weight.type ?? '')} ${escapeHtml(weight.percent ?? '')}%</span>
                    `).join('');

                    const badgeClass = formula.is_fallback ? 'bg-success-subtle text-success' : 'bg-light text-secondary';
                    const badgeLabel = formula.is_fallback ? 'Fallback' : 'Catalog';
                    const mismatchClass = formula.context_match ? '' : 'formula-context-mismatch';
                    const contextBadge = formula.context_match
                        ? '<span class="badge bg-success-subtle text-success">Matches active context</span>'
                        : '<span class="badge bg-warning-subtle text-warning">Other period</span>';
                    const contextLabel = escapeHtml(formula.context_label ?? 'Applies to all periods');
                    const mismatchNotice = formula.context_match
                        ? ''
                        : '<div class="text-warning small mt-1">Applying this formula will update courses using a different academic period. Double-check before continuing.</div>';

                    return `
                        <label class="formula-card form-check border rounded-4 p-3 ${formula.is_fallback ? 'formula-fallback' : ''} ${mismatchClass}" data-formula-id="${formulaId}">
                            <div class="d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1" type="radio" name="department_formula_id" value="${formulaId}" ${checked ? 'checked' : ''}>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div class="min-width-0">
                                            <span class="fw-semibold d-block formula-card-title" title="${escapeHtml(formula.label ?? '')}">${escapeHtml(formula.label ?? 'Formula')}</span>
                                            <div class="text-muted small">Base ${formatGradeNumber(formula.base_score)} · Scale ×${formatGradeNumber(formula.scale_multiplier)} · Passing ${formatGradeNumber(formula.passing_grade)}</div>
                                        </div>
                                        <span class="badge ${badgeClass}">${badgeLabel}</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-2">${weights}</div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 formula-context-row">
                                        ${contextBadge}
                                        <span class="text-muted small">${contextLabel}</span>
                                    </div>
                                    ${mismatchNotice}
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 text-muted small">
                                        <span>Last updated ${escapeHtml(formula.updated_at ?? '—')}</span>
                                        ${formula.edit_url ? `<a class="text-decoration-none" href="${escapeHtml(formula.edit_url)}"><i class="bi bi-pencil-square me-1"></i>Edit</a>` : ''}
                                    </div>
                                </div>
                            </div>
                        </label>
                    `;
                }).join('');
            }

            const summaryText = (() => {
                if (! currentDepartment) {
                    return 'Select a department to load available formulas.';
                }
                if (totalCount === 0) {
                    return 'No saved formulas yet. Use a template below to create the department baseline.';
                }
                const filterText = (() => {
                    if (currentFormulaFilter === 'catalog') {
                        return 'Catalog';
                    }
                    if (currentFormulaFilter === 'fallback') {
                        return 'Fallback (highlighting baseline options)';
                    }
                    return 'All';
                })();
                const pieces = [`${totalCount} formula${totalCount === 1 ? '' : 's'} available`, `${catalogCount} catalog${catalogCount !== matchingCatalogCount ? ` (${matchingCatalogCount} for this selection)` : ''}`, `${fallbackCount} fallback`];
                if (matchingTotalCount !== totalCount) {
                    pieces.push(`${matchingTotalCount} match${matchingTotalCount === 1 ? 'es' : ''} current context`);
                }
                const selectionSuffix = currentFormulaId ? '' : (totalCount > 0 ? ' · No formula selected' : '');
                return `${pieces.join(' · ')}${selectionSuffix} · Showing ${filteredFormulas.length} (${filterText}${normalizedSearch ? ` · matching "${escapeHtml(currentFormulaSearch)}"` : ''})`;
            })();

            if (formulaMeta) {
                formulaMeta.textContent = summaryText;
            }

            formulaContainer.innerHTML = `${selectionHiddenNotice}${fallbackHelper}${markup}${renderStructureTemplateMarkup()}`;
            formulaContainer.classList.toggle('formula-filter-fallback', currentFormulaFilter === 'fallback');

            formulaFilterButtons.forEach((button) => {
                const matches = button.dataset.formulaFilter === currentFormulaFilter;
                button.classList.toggle('btn-success', matches);
                button.classList.toggle('active', matches);
                button.classList.toggle('btn-outline-success', ! matches);
            });

            formulaContainer.querySelectorAll('input[type="radio"]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    currentFormulaId = radio.value;
                    refreshFormulaSelectionStyles();
                    syncApplyButtonState();
                });
            });

            const selectedRadio = formulaContainer.querySelector('input[name="department_formula_id"]:checked');
            if (selectedRadio) {
                currentFormulaId = selectedRadio.value;
                syncApplyButtonState();
            }

            refreshFormulaSelectionStyles();
            syncApplyButtonState();
        };

        syncApplyButtonState();

        const drawCourseList = () => {
            if (! courseContainer) {
                return;
            }

            const normalizedSearch = currentCourseSearch.trim().toLowerCase();

            if (courseSearchInput) {
                courseSearchInput.disabled = totalCourseCount === 0;
            }
            if (courseSearchClear) {
                courseSearchClear.disabled = totalCourseCount === 0 || normalizedSearch === '';
            }

            const matches = Array.isArray(allCourses) ? allCourses.filter((course) => {
                if (normalizedSearch === '') {
                    return true;
                }

                const courseLabel = (course.label ?? '').toLowerCase();
                const courseCode = (course.code ?? '').toLowerCase();
                const courseName = (course.name ?? '').toLowerCase();

                if (courseLabel.includes(normalizedSearch) || courseCode.includes(normalizedSearch) || courseName.includes(normalizedSearch)) {
                    return true;
                }

                const subjects = Array.isArray(course.subjects) ? course.subjects : [];
                return subjects.some((subject) => (subject.label ?? '').toLowerCase().includes(normalizedSearch));
            }) : [];

            visibleCourseIds = matches.map((course) => String(course.id));
            visibleCourseCount = visibleCourseIds.length;

            if (courseCountLabel) {
                courseCountLabel.textContent = totalCourseCount === 0
                    ? ''
                    : `${visibleCourseCount} of ${totalCourseCount} course${totalCourseCount === 1 ? '' : 's'} showing`;
            }

            if (totalCourseCount === 0) {
                courseContainer.innerHTML = '<div class="text-muted small">No courses available for this department.</div>';
                updateCourseSummary();
                return;
            }

            if (visibleCourseCount === 0) {
                courseContainer.innerHTML = '<div class="text-muted small">No courses match your search terms.</div>';
                updateCourseSummary();
                return;
            }

            const searchActive = normalizedSearch !== '';

            courseContainer.innerHTML = matches.map((course) => {
                const courseId = String(course.id);
                const checked = selectedCourses.has(courseId);
                const subjects = Array.isArray(course.subjects) ? course.subjects : [];
                const gradedCount = subjects.filter((subject) => subject.has_grades).length;
                const totalSubjects = subjects.length;
                const manuallyExpanded = expandedCourses.has(courseId);
                const autoExpanded = searchActive && totalSubjects > 0;
                const isExpanded = totalSubjects === 0 || manuallyExpanded || autoExpanded;

                const subjectMarkup = subjects.length === 0
                    ? '<div class="text-muted small">No subjects assigned yet.</div>'
                    : subjects.map((subject) => {
                        const label = subject.label ?? 'Subject';
                        const pillClass = subject.has_grades ? 'subject-pill subject-pill-warning' : 'subject-pill';
                        const icon = subject.has_grades ? 'bi-exclamation-triangle-fill text-danger' : 'bi-journal-text text-success';
                        return `
                            <span class="${pillClass}" title="${escapeHtml(label)}">
                                <i class="bi ${icon}"></i>
                                <span class="subject-pill-text">${escapeHtml(label)}</span>
                            </span>
                        `;
                    }).join('');

                const toggleIcon = isExpanded ? 'bi-chevron-up' : 'bi-chevron-down';
                const toggleLabel = isExpanded ? 'Hide subjects' : 'Show subjects';
                const subjectsHiddenClass = isExpanded ? '' : 'd-none';

                return `
                    <div class="bulk-course-card ${checked ? 'is-selected' : ''} ${isExpanded ? 'is-expanded' : ''}" data-course-id="${courseId}">
                        <div class="bulk-course-card-header d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <div class="form-check flex-grow-1">
                                <input class="form-check-input bulk-course-checkbox" type="checkbox" id="bulk-course-${courseId}" name="course_ids[]" value="${courseId}" ${checked ? 'checked' : ''}>
                                <label class="form-check-label fw-semibold course-label" for="bulk-course-${courseId}">
                                    ${escapeHtml(course.label ?? 'Course')}
                                </label>
                                <div class="text-muted small course-meta">
                                    ${totalSubjects} subject${totalSubjects === 1 ? '' : 's'}${gradedCount > 0 ? ` · ${gradedCount} with recorded grades` : ''}
                                </div>
                            </div>
                            ${totalSubjects > 0 ? `
                                <button type="button" class="btn btn-outline-success btn-sm bulk-course-toggle" data-course-id="${courseId}" aria-expanded="${isExpanded ? 'true' : 'false'}">
                                    <i class="bi ${toggleIcon} me-1"></i>${toggleLabel}
                                </button>
                            ` : ''}
                        </div>
                        <div class="bulk-course-subjects ${subjectsHiddenClass}">
                            <div class="bulk-course-subject-grid">
                                ${subjectMarkup}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            courseContainer.querySelectorAll('.bulk-course-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const courseId = checkbox.value;
                    if (checkbox.checked) {
                        selectedCourses.add(courseId);
                    } else {
                        selectedCourses.delete(courseId);
                    }
                    checkbox.closest('.bulk-course-card')?.classList.toggle('is-selected', checkbox.checked);
                    updateCourseSummary();
                });
            });

            courseContainer.querySelectorAll('.bulk-course-toggle').forEach((button) => {
                button.addEventListener('click', () => {
                    const courseId = button.dataset.courseId;
                    if (! courseId) {
                        return;
                    }
                    const card = button.closest('.bulk-course-card');
                    const subjectsPanel = card?.querySelector('.bulk-course-subjects');
                    if (! card || ! subjectsPanel) {
                        return;
                    }
                    const expanded = card.classList.toggle('is-expanded');
                    subjectsPanel.classList.toggle('d-none', ! expanded);
                    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    button.innerHTML = `<i class="bi ${expanded ? 'bi-chevron-up' : 'bi-chevron-down'} me-1"></i>${expanded ? 'Hide subjects' : 'Show subjects'}`;
                    if (expanded) {
                        expandedCourses.add(courseId);
                    } else {
                        expandedCourses.delete(courseId);
                    }
                });
            });

            updateCourseSummary();
        };

        const renderCourseOptions = (courses = []) => {
            if (! courseContainer) {
                return;
            }

            const oldCourseIds = (() => {
                try {
                    const raw = bulkForm.dataset.oldCourseIds;
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed.map((id) => String(id)) : [];
                } catch (error) {
                    return [];
                }
            })();
            bulkForm.dataset.oldCourseIds = '[]';

            selectedCourses.clear();
            oldCourseIds.forEach((id) => selectedCourses.add(id));

            allCourses = Array.isArray(courses) ? courses : [];
            totalCourseCount = allCourses.length;
            selectedCourses.forEach((id) => {
                if (! allCourses.some((course) => String(course.id) === id)) {
                    selectedCourses.delete(id);
                }
            });
            visibleCourseIds = [];
            visibleCourseCount = 0;
            expandedCourses.clear();
            currentCourseSearch = '';

            if (courseSearchInput) {
                courseSearchInput.value = '';
                courseSearchInput.disabled = totalCourseCount === 0;
            }
            if (courseSearchClear) {
                courseSearchClear.disabled = true;
            }

            if (totalCourseCount === 0) {
                courseContainer.innerHTML = '<div class="text-muted small">No courses available for this department.</div>';
                if (courseCountLabel) {
                    courseCountLabel.textContent = '';
                }
                if (selectAllBtn) {
                    selectAllBtn.disabled = true;
                }
                if (clearAllBtn) {
                    clearAllBtn.disabled = selectedCourses.size === 0;
                }
                updateCourseSummary();
                return;
            }

            drawCourseList();
        };

        formulaFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.formulaFilter ?? 'all';
                if (filter === currentFormulaFilter) {
                    return;
                }
                currentFormulaFilter = filter;
                renderFormulaOptions(availableFormulas);
            });
        });

        formulaSearchInput?.addEventListener('input', (event) => {
            currentFormulaSearch = event.target.value;
            renderFormulaOptions(availableFormulas);
        });

        formulaSearchClear?.addEventListener('click', () => {
            if (! formulaSearchInput) {
                return;
            }
            currentFormulaSearch = '';
            formulaSearchInput.value = '';
            formulaSearchInput.focus();
            renderFormulaOptions(availableFormulas);
        });

        courseSearchInput?.addEventListener('input', (event) => {
            currentCourseSearch = event.target.value;
            drawCourseList();
        });

        courseSearchClear?.addEventListener('click', () => {
            if (! courseSearchInput) {
                return;
            }
            currentCourseSearch = '';
            courseSearchInput.value = '';
            courseSearchInput.focus();
            drawCourseList();
        });

        const updateCourseSummary = () => {
            const selectedCount = selectedCourses.size;
            const visibleSelectedCount = visibleCourseIds.filter((id) => selectedCourses.has(id)).length;

            if (courseSummary) {
                if (selectedCount === 0) {
                    courseSummary.textContent = 'No courses selected yet.';
                } else {
                    const totalSuffix = totalCourseCount > 0 ? ` of ${totalCourseCount}` : '';
                    const visibleSuffix = totalCourseCount > 0 ? ` · ${visibleSelectedCount} in view` : '';
                    courseSummary.textContent = `${selectedCount} course${selectedCount === 1 ? '' : 's'} selected${totalSuffix}${visibleSuffix}.`;
                }
            }

            if (selectAllBtn) {
                selectAllBtn.disabled = visibleCourseCount === 0;
            }

            if (clearAllBtn) {
                clearAllBtn.disabled = selectedCount === 0;
            }

            updatePasswordState(getSelectedCourseRecords());
            syncApplyButtonState();
        };

        const updatePasswordState = (courses = []) => {
            const subjectsNeedingConfirmation = [];

            courses.forEach((course) => {
                (course.subjects ?? []).forEach((subject) => {
                    if (subject.has_grades) {
                        subjectsNeedingConfirmation.push({
                            course: course.label,
                            subject: subject.label,
                        });
                    }
                });
            });

            if (subjectsNeedingConfirmation.length > 0) {
                passwordRequired = true;
                hasServerConflicts = false;
                serverConflictEntries = [];
                if (conflictContainer) {
                    conflictContainer.classList.remove('d-none');
                    conflictContainer.innerHTML = `
                        <div class="alert alert-warning shadow-sm">
                            <div class="fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Recorded grades detected</div>
                            <p class="text-muted small mb-2">Enter your password to confirm overwriting the grading baseline for the following subjects:</p>
                            <ul class="mb-0 ps-3">
                                ${subjectsNeedingConfirmation.map((entry) => `<li>${entry.subject} <span class="text-muted">(${entry.course})</span></li>`).join('')}
                            </ul>
                        </div>
                    `;
                }
                syncServerConflictVisibility();
                if (passwordFormError) {
                    passwordFormError.classList.add('d-none');
                }
            } else {
                if (conflictContainer) {
                    conflictContainer.classList.add('d-none');
                    conflictContainer.innerHTML = '';
                }
                passwordRequired = baselinePasswordRequirement || hasServerConflicts;
                syncServerConflictVisibility();
                if (! passwordRequired && hiddenPasswordInput) {
                    hiddenPasswordInput.value = '';
                }
                if (! passwordRequired && passwordFormError) {
                    passwordFormError.classList.add('d-none');
                }
            }
        };

        const loadDepartment = (departmentId) => {
            const blueprint = departmentBlueprints.find((entry) => String(entry.id) === String(departmentId));

            if (! blueprint) {
                currentDepartment = null;
                availableFormulas = [];
                currentFormulaFilter = 'all';
                currentFormulaSearch = '';
                if (formulaMeta) {
                    formulaMeta.textContent = 'Select a department to load available formulas.';
                }
                if (formulaFilterGroup) {
                    formulaFilterGroup.classList.add('d-none');
                }
                if (formulaSearchInput) {
                    formulaSearchInput.value = '';
                    formulaSearchInput.disabled = true;
                }
                if (formulaSearchClear) {
                    formulaSearchClear.disabled = true;
                }
                if (formulaContainer) {
                    formulaContainer.innerHTML = '<div class="text-muted small">No formulas available for this department.</div>';
                }
                allCourses = [];
                totalCourseCount = 0;
                visibleCourseIds = [];
                visibleCourseCount = 0;
                expandedCourses.clear();
                currentCourseSearch = '';
                if (courseSearchInput) {
                    courseSearchInput.value = '';
                    courseSearchInput.disabled = true;
                }
                if (courseSearchClear) {
                    courseSearchClear.disabled = true;
                }
                if (courseCountLabel) {
                    courseCountLabel.textContent = '';
                }
                if (courseContainer) {
                    courseContainer.innerHTML = '<div class="text-muted small">No courses available for this department.</div>';
                }
                manageLink?.classList.add('d-none');
                selectedCourses.clear();
                serverConflictEntries = [];
                hasServerConflicts = false;
                passwordRequired = baselinePasswordRequirement;
                syncServerConflictVisibility();
                if (! passwordRequired && hiddenPasswordInput) {
                    hiddenPasswordInput.value = '';
                }
                updateCourseSummary();
                return;
            }

            currentDepartment = blueprint;
            currentFormulaId = null;
            availableFormulas = [];
            currentFormulaFilter = 'all';
            currentFormulaSearch = '';
            if (formulaSearchInput) {
                formulaSearchInput.value = '';
            }
            if (formulaSearchClear) {
                formulaSearchClear.disabled = true;
            }
            hasServerConflicts = serverConflictEntries.length > 0;
            passwordRequired = baselinePasswordRequirement || hasServerConflicts;
            syncServerConflictVisibility();
            if (! passwordRequired && hiddenPasswordInput) {
                hiddenPasswordInput.value = '';
            }
            if (manageLink) {
                manageLink.href = blueprint.department_url;
                manageLink.classList.remove('d-none');
            }

            renderFormulaOptions(blueprint.formulas ?? []);
            renderCourseOptions(blueprint.courses ?? []);
        };

        departmentSelect?.addEventListener('change', (event) => {
            const departmentId = event.target.value;
            serverConflictEntries = [];
            hasServerConflicts = false;
            passwordRequired = baselinePasswordRequirement;
            syncServerConflictVisibility();
            if (hiddenPasswordInput) {
                hiddenPasswordInput.value = '';
            }
            if (passwordFormError) {
                passwordFormError.classList.add('d-none');
            }
            if (! departmentId) {
                loadDepartment(null);
                return;
            }

            loadDepartment(departmentId);
        });

        selectAllBtn?.addEventListener('click', () => {
            if (! courseContainer) {
                return;
            }

            courseContainer.querySelectorAll('.bulk-course-checkbox').forEach((checkbox) => {
                checkbox.checked = true;
                selectedCourses.add(checkbox.value);
                checkbox.closest('.bulk-course-card')?.classList.add('is-selected');
            });
            updateCourseSummary();
        });

        clearAllBtn?.addEventListener('click', () => {
            if (! courseContainer) {
                return;
            }

            courseContainer.querySelectorAll('.bulk-course-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
                selectedCourses.delete(checkbox.value);
                checkbox.closest('.bulk-course-card')?.classList.remove('is-selected');
            });
            updateCourseSummary();
        });

        const showPasswordModal = () => {
            if (! passwordModalInstance) {
                return;
            }

            if (passwordModalInput) {
                passwordModalInput.value = '';
                passwordModalInput.classList.remove('is-invalid');
            }

            if (passwordModalError) {
                passwordModalError.classList.add('d-none');
                passwordModalError.textContent = '';
            }

            if (passwordFormError && passwordFormError.textContent.trim() !== '') {
                const message = passwordFormError.textContent.trim();
                if (passwordModalError && message !== '') {
                    passwordModalError.textContent = message;
                    passwordModalError.classList.remove('d-none');
                }
                passwordFormError.classList.add('d-none');
            }

            passwordModalInstance.show();
        };

        bulkForm.addEventListener('submit', (event) => {
            if (! currentFormulaId) {
                if (availableFormulas.length > 0) {
                    event.preventDefault();
                    syncApplyButtonState();
                    formulaContainer?.querySelector('input[type="radio"]')?.focus();
                }
                return;
            }

            if (isSubmitting) {
                return;
            }

            if (! passwordRequired) {
                return;
            }

            if (hiddenPasswordInput && hiddenPasswordInput.value) {
                return;
            }

            if (! passwordModalInstance) {
                return;
            }

            event.preventDefault();
            showPasswordModal();
        });

        passwordModalElement?.addEventListener('shown.bs.modal', () => {
            window.setTimeout(() => passwordModalInput?.focus(), 120);
        });

        passwordModalElement?.addEventListener('hidden.bs.modal', () => {
            if (! hiddenPasswordInput?.value && passwordFormError && passwordFormError.textContent.trim() !== '') {
                passwordFormError.classList.remove('d-none');
            }
        });

        passwordModalConfirm?.addEventListener('click', () => {
            if (! passwordModalInput) {
                return;
            }

            const value = passwordModalInput.value.trim();
            if (value === '') {
                passwordModalInput.classList.add('is-invalid');
                if (passwordModalError) {
                    passwordModalError.textContent = 'Password is required.';
                    passwordModalError.classList.remove('d-none');
                }
                passwordModalInput.focus();
                return;
            }

            if (hiddenPasswordInput) {
                hiddenPasswordInput.value = value;
            }
            passwordModalInput.value = '';
            passwordModalInput.classList.remove('is-invalid');
            if (passwordModalError) {
                passwordModalError.classList.add('d-none');
                passwordModalError.textContent = '';
            }

            isSubmitting = true;
            passwordModalInstance?.hide();
            window.requestAnimationFrame(() => {
                bulkForm.requestSubmit();
            });
        });

        passwordModalInput?.addEventListener('input', () => {
            passwordModalInput.classList.remove('is-invalid');
            if (passwordModalError) {
                passwordModalError.classList.add('d-none');
                passwordModalError.textContent = '';
            }
        });

        if (departmentSelect && departmentSelect.value) {
            loadDepartment(departmentSelect.value);
        }

        if (bulkForm.dataset.passwordError === '1') {
            showPasswordModal();
        }
    });
</script>
@endpush

@push('styles')
<style>
.section-scroll {
    max-height: calc(100vh - 220px);
    overflow-y: auto;
    padding-right: 0.5rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(25, 135, 84, 0.35) transparent;
}

.section-scroll::-webkit-scrollbar {
    width: 8px;
}

.section-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(25, 135, 84, 0.35);
    border-radius: 999px;
}

.section-scroll:hover::-webkit-scrollbar-thumb {
    background-color: rgba(25, 135, 84, 0.55);
}

.bulk-course-scroll {
    max-height: 260px;
    overflow-y: auto;
    background: rgba(25, 135, 84, 0.04);
    border: 1px solid rgba(25, 135, 84, 0.12);
    scrollbar-width: thin;
    scrollbar-color: rgba(25, 135, 84, 0.35) transparent;
}

.bulk-course-scroll::-webkit-scrollbar {
    width: 8px;
}

.bulk-course-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(25, 135, 84, 0.35);
    border-radius: 999px;
}

.bulk-course-scroll:hover::-webkit-scrollbar-thumb {
    background-color: rgba(25, 135, 84, 0.55);
}

.bulk-course-scroll {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.formula-list {
    max-height: 320px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(25, 135, 84, 0.35) transparent;
}

.formula-list::-webkit-scrollbar {
    width: 8px;
}

.formula-list::-webkit-scrollbar-thumb {
    background-color: rgba(25, 135, 84, 0.35);
    border-radius: 999px;
}

.formula-list:hover::-webkit-scrollbar-thumb {
    background-color: rgba(25, 135, 84, 0.55);
}

.bulk-course-card {
    background: #ffffff;
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 1rem;
    padding: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.bulk-course-card:hover {
    border-color: rgba(25, 135, 84, 0.45);
    box-shadow: 0 12px 24px rgba(25, 135, 84, 0.1);
}

.bulk-course-card .form-check-input:checked ~ label {
    color: #0f5132;
}

.bulk-course-card.is-selected {
    border-color: rgba(25, 135, 84, 0.6);
    box-shadow: 0 16px 30px rgba(25, 135, 84, 0.12);
}

.bulk-course-card-header .course-label {
    display: block;
    word-break: break-word;
}

.bulk-course-card-header .course-meta {
    word-break: break-word;
}

.bulk-course-card-header .bulk-course-toggle {
    white-space: nowrap;
}

.bulk-course-subjects {
    margin-top: 0.75rem;
}

.bulk-course-subject-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.subject-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: rgba(25, 135, 84, 0.12);
    color: #0f5132;
    max-width: 100%;
}

.subject-pill-warning {
    background: rgba(220, 53, 69, 0.12);
    color: #842029;
}

.subject-pill-text {
    white-space: normal;
    word-break: break-word;
}

.formula-card {
    background: #ffffff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.formula-card:hover,
.formula-card:focus-within {
    border-color: rgba(25, 135, 84, 0.45);
    box-shadow: 0 14px 32px rgba(25, 135, 84, 0.12);
}

.formula-card.is-selected {
    border-color: rgba(25, 135, 84, 0.6);
    box-shadow: 0 18px 34px rgba(25, 135, 84, 0.16);
}

.formula-card.formula-context-mismatch {
    border-color: rgba(255, 193, 7, 0.45);
    box-shadow: 0 14px 32px rgba(255, 193, 7, 0.18);
}

.formula-card.formula-context-mismatch.is-selected {
    border-color: rgba(220, 53, 69, 0.6);
    box-shadow: 0 20px 40px rgba(220, 53, 69, 0.2);
}

.formula-context-row .badge {
    font-size: 0.7rem;
}

.formula-card input[type="radio"] {
    cursor: pointer;
}

.formula-card-title {
    word-break: break-word;
}

.formula-list.formula-filter-fallback .formula-card.formula-fallback {
    border-color: rgba(25, 135, 84, 0.65);
    box-shadow: 0 20px 40px rgba(25, 135, 84, 0.2);
}

.formula-list.formula-filter-fallback .formula-card:not(.formula-fallback) {
    opacity: 0.9;
}

.wildcard-card {
    position: relative;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    min-height: 240px;
    background: #ffffff;
}

.wildcard-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(25, 135, 84, 0.18);
}

.wildcard-card.is-pressed {
    transform: translateY(-2px) scale(0.99);
    box-shadow: 0 14px 32px rgba(25, 135, 84, 0.2);
}

.wildcard-card:focus-visible {
    outline: 3px solid rgba(25, 135, 84, 0.45);
    outline-offset: 4px;
}

.wildcard-circle {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(15, 81, 50, 0.35);
    position: absolute;
    top: 55px;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 0 18px;
    background: linear-gradient(135deg, #23a362, #0b3d23);
    max-width: calc(100% - 24px);
}

.wildcard-circle span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    text-align: center;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 0.03em;
    overflow-wrap: anywhere;
    word-break: normal;
    white-space: normal;
    font-size: clamp(0.6rem, 0.52rem + 0.45vw, 0.95rem);
}

.wildcard-filter-btn {
    transition: all 0.3s ease;
}

.wildcard-filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.15);
}

.wildcard-filter-btn.active {
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.25);
}

.badge.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.15) !important;
    color: #0f5132 !important;
}


.badge-formula-label {
    background-color: #ffffff;
    color: #198754;
    border: 1px solid rgba(25, 135, 84, 0.25);
    font-weight: 600;
}

.wildcard-card .badge-formula-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    white-space: normal;
    overflow-wrap: anywhere;
    width: 100%;
    line-height: 1.2;
    padding: 0.45rem 0.75rem;
    border-radius: 999px;
}

.wildcard-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
}

.structure-card {
    background: #ffffff;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.structure-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(25, 135, 84, 0.18);
}

.structure-card .badge {
    font-weight: 600;
}

.structure-template-wrapper {
    border-top: 1px dashed rgba(25, 135, 84, 0.25);
    padding-top: 1rem;
    margin-top: 1rem;
}

.structure-template-grid {
    display: grid;
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .structure-template-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
}

@media (max-width: 768px) {
    .section-scroll {
        max-height: none;
        padding-right: 0;
    }
}

.structure-template-card {
    background: #f8fff9;
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
}

.structure-template-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(25, 135, 84, 0.12);
    border-color: rgba(25, 135, 84, 0.45);
}

.structure-template-card .badge {
    font-weight: 500;
}

@media (max-width: 576px) {
    .wildcard-card {
        min-height: 200px;
    }

    .wildcard-circle {
        width: 90px;
        height: 90px;
        top: 44px;
        padding: 0 14px;
    }
}

</style>
@endpush
