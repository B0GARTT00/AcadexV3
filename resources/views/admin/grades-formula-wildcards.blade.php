@extends('layouts.app')

@php
    $request = request();
    $queryParams = $request->query();

    $allowedSections = ['overview', 'formulas', 'departments'];
    $initialSection = $request->query('view');
    if (! in_array($initialSection, $allowedSections, true)) {
        $initialSection = 'overview';
    }

    $structureTemplateError = session('structure_template_error', false);
    $reopenTemplateModalFlag = session('reopen_structure_template_modal', false);
    $errorMessages = $errors->getMessages();

    $templateErrorMessages = collect($errorMessages)
        ->filter(function ($messages, $field) use ($structureTemplateError) {
            if (str_starts_with($field, 'components')) {
                return true;
            }

            if (in_array($field, ['template_label', 'template_key', 'template_description'], true)) {
                return true;
            }

            if ($structureTemplateError && in_array($field, ['password', 'error'], true)) {
                return true;
            }

            return false;
        })
        ->flatten()
        ->map(fn ($message) => (string) $message)
        ->values()
        ->all();

    $oldTemplateInputs = [
        'label' => old('template_label'),
        'key' => old('template_key'),
        'description' => old('template_description'),
        'components' => old('components', []),
    ];

    $oldGlobalFormulaInputs = old('scope_level') === 'global'
        ? [
            'label' => old('label'),
            'template_key' => old('template_key'),
            'context_type' => old('context_type'),
            'semester' => old('semester'),
            'academic_year' => old('academic_year'),
        ]
        : [
            'label' => null,
            'template_key' => null,
            'context_type' => null,
            'semester' => null,
            'academic_year' => null,
        ];

    $shouldReopenCreateFormulaModal = old('scope_level') === 'global' && $errors->any();
    $globalFormulaPasswordError = $shouldReopenCreateFormulaModal ? $errors->first('password') : null;

    $templateModalMode = session('structure_template_mode');
    if ($templateModalMode === null) {
        $templateModalMode = old('template_id') ? 'edit' : 'create';
    }

    $templateModalEditId = session('structure_template_edit_id', old('template_id'));
    $reopenTemplateDeleteId = session('reopen_structure_template_delete_modal');
    $deleteTemplatePasswordError = $reopenTemplateDeleteId ? $errors->first('password') : null;

    $hasOldTemplateData = collect($oldTemplateInputs)
        ->filter(function ($value, $key) {
            if ($key === 'components') {
                return is_array($value) && ! empty($value);
            }

            return $value !== null && $value !== '';
        })
        ->isNotEmpty();

    $shouldReopenTemplateModal = $reopenTemplateModalFlag || $structureTemplateError || ! empty($templateErrorMessages) || $hasOldTemplateData;

    $errorFields = array_keys($errorMessages);
    $hasBulkErrors = $errors->any() && (
        old('department_id') ||
        ! empty($bulkConflicts ?? []) ||
        collect($errorFields)->contains(function ($field) use ($structureTemplateError) {
            if ($structureTemplateError && in_array($field, ['password', 'error'], true)) {
                return false;
            }

            return in_array($field, ['department_id', 'course_ids', 'course_ids.*', 'structure_template', 'password'], true);
        })
    );

    if ($shouldReopenTemplateModal || $shouldReopenCreateFormulaModal) {
        $initialSection = 'formulas';
    } elseif ($hasBulkErrors || old('department_id') || ! empty($bulkConflicts ?? [])) {
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
        $periodLookup,
        $courseFormulas,
        $subjectFormulas,
        $globalFormula
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
            'apply_template_url' => $buildRoute('admin.gradesFormula.department.applyTemplate', ['department' => $department->id]),
            'formulas' => $sortedFormulas->map(function ($formula) use ($department, $buildRoute, $semester, $selectedAcademicPeriodId, $periodLookup) {
                $weights = collect($formula->weight_map)
                    ->map(function ($weight, $type) {
                        return [
                            'type' => strtoupper($type),
                            'percent' => number_format($weight * 100, 0),
                        ];
                    })
                    ->values();

                $structureType = $formula->structure_type ?? 'lecture_only';
                $structureDefinitions = \App\Support\Grades\FormulaStructure::STRUCTURE_DEFINITIONS;
                $structureLabel = $structureDefinitions[$structureType]['label']
                    ?? \App\Support\Grades\FormulaStructure::formatLabel($structureType);

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
                    'structure_type' => $structureType,
                    'structure_label' => $structureLabel,
                ];
            })->values(),
            'courses' => ($department->courses ?? collect())->map(function ($course) use ($department, $departmentFallbacks, $courseFormulas, $subjectFormulas, $globalFormula) {
                $subjects = $course->subjects ?? collect();

                $departmentFallback = $departmentFallbacks->get($department->id);
                $fallbackFormula = $departmentFallback ?? $globalFormula;
                $fallbackLabel = optional($fallbackFormula)->label ?? 'System Default Formula';

                $courseFormula = $courseFormulas->get($course->id);
                $hasCourseFormula = $courseFormula !== null;

                $subjectPayload = $subjects->map(function ($subject) use ($subjectFormulas, $courseFormula, $departmentFallback, $globalFormula) {
                    $subjectLabel = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? ''));
                    if ($subjectLabel === '') {
                        $subjectLabel = 'Unnamed Subject';
                    }

                    $subjectFormula = $subjectFormulas->get($subject->id);
                    $subjectHasFormula = $subjectFormula !== null;

                    if ($subjectHasFormula) {
                        $formulaScope = 'Subject Formula';
                        $formulaSource = 'subject';
                        $formulaLabel = $subjectFormula->label ?? 'Subject Formula';
                        $formulaId = $subjectFormula->id ?? null;
                    } elseif ($courseFormula) {
                        // Check if course formula is a department clone
                        $isDepartmentClone = false;
                        if ($departmentFallback && $courseFormula) {
                            $isDepartmentClone = ($courseFormula->structure_type === $departmentFallback->structure_type) 
                                && ($courseFormula->scope_level === 'course');
                        }
                        
                        if ($isDepartmentClone) {
                            $formulaScope = 'Department Baseline (Applied)';
                            $formulaSource = 'department';
                            $formulaLabel = $departmentFallback->label ?? 'Department Baseline Formula';
                            $formulaId = $courseFormula->id ?? null;
                        } else {
                            $formulaScope = 'Course Formula';
                            $formulaSource = 'course';
                            $formulaLabel = $courseFormula->label ?? 'Course Formula';
                            $formulaId = $courseFormula->id ?? null;
                        }
                    } elseif ($departmentFallback) {
                        $formulaScope = 'Department Baseline';
                        $formulaSource = 'department';
                        $formulaLabel = $departmentFallback->label ?? 'Department Baseline';
                        $formulaId = $departmentFallback->id ?? null;
                    } else {
                        $formulaScope = 'System Default Formula';
                        $formulaSource = 'global';
                        $formulaLabel = optional($globalFormula)->label ?? 'System Default';
                        $formulaId = optional($globalFormula)->id ?? null;
                    }

                    return [
                        'id' => $subject->id,
                        'label' => $subjectLabel,
                        'has_grades' => (bool) $subject->getAttribute('has_recorded_grades'),
                        'formula_label' => $formulaLabel,
                        'formula_scope' => $formulaScope,
                        'formula_source' => $formulaSource,
                        'formula_id' => $formulaId,
                        'has_formula' => $subjectHasFormula,
                    ];
                })->values();

                $courseLabel = trim(($course->course_code ? $course->course_code . ' - ' : '') . ($course->course_description ?? ''));

                // Determine the formula scope for display
                // If course has a formula, check if it matches the department fallback (applied via bulk)
                if ($hasCourseFormula) {
                    // Check if the course formula is identical to the department fallback
                    $isDepartmentClone = false;
                    if ($departmentFallback && $courseFormula) {
                        // Consider it a department clone if it has the same structure type and is department scoped
                        $isDepartmentClone = ($courseFormula->structure_type === $departmentFallback->structure_type) 
                            && ($courseFormula->scope_level === 'course');
                    }
                    
                    if ($isDepartmentClone) {
                        $formulaScope = 'Department Baseline (Applied)';
                        $formulaSource = 'department';
                        $formulaLabel = $departmentFallback->label ?? 'Department Baseline Formula';
                    } else {
                        $formulaScope = 'Course Formula';
                        $formulaSource = 'course';
                        $formulaLabel = $courseFormula->label ?? 'Course Formula';
                    }
                } else {
                    $formulaScope = $departmentFallback ? 'Department Baseline' : 'System Default';
                    $formulaSource = $departmentFallback ? 'department' : 'global';
                    $formulaLabel = $fallbackLabel;
                }

                return [
                    'id' => $course->id,
                    'code' => $course->course_code,
                    'name' => $course->course_description,
                    'label' => $courseLabel !== '' ? $courseLabel : 'Course',
                    'subjects' => $subjectPayload,
                    'formula_label' => $formulaLabel,
                    'formula_scope' => $formulaScope,
                    'formula_source' => $formulaSource,
                    'has_formula' => $hasCourseFormula,
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
            'id' => $template['id'] ?? null,
            'template_key' => $template['template_key'] ?? ($template['key'] ?? ''),
            'key' => $template['key'] ?? '',
            'label' => $template['label'] ?? '',
            'description' => $template['description'] ?? '',
            'weights' => collect($template['weights'] ?? [])->map(function ($weight) {
                return [
                    'type' => $weight['type'] ?? '',
                    'percent' => (int) ($weight['percent'] ?? 0),
                ];
            })->values()->all(),
            'structure' => $template['structure'] ?? [],
            'is_custom' => (bool) ($template['is_custom'] ?? false),
            'is_system_default' => (bool) ($template['is_system_default'] ?? false),
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
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0 fw-semibold text-dark">Structure Templates</h4>
                    <div class="d-flex gap-2">
                        <button type="button" id="open-create-template" class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#create-template-modal">
                            <i class="bi bi-plus-circle me-2"></i>Create Structure Template
                        </button>
                        <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#create-formula-modal">
                            <i class="bi bi-globe2 me-2"></i>Create Global Formula
                        </button>
                    </div>
                </div>
                <p class="text-muted small mb-0">Pre-defined grading structures for common course types</p>
            </div>

            <div class="row g-4 mb-4">
                @forelse($structureTemplates as $template)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="structure-card card h-100 border-0 shadow-lg rounded-4">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h5 class="fw-semibold text-dark mb-1">{{ $template['label'] }}</h5>
                                        <p class="text-muted small mb-0">{{ $template['description'] }}</p>
                                    </div>
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <span class="badge bg-success-subtle text-success">Structure</span>
                                        @if(!empty($template['id']))
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Manage structure template">
                                                <button type="button" class="btn btn-outline-secondary js-edit-structure-template" data-template-id="{{ $template['id'] }}">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                                </button>
                                                <button type="button" class="btn btn-outline-danger js-delete-structure-template" data-template-id="{{ $template['id'] }}">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($template['weights'] as $weight)
                                        @if(!empty($weight['is_composite']))
                                            {{-- Main composite component (e.g., Lecture Component 60%) --}}
                                            <span class="badge bg-primary text-white fw-semibold">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                        @elseif(!empty($weight['is_sub']))
                                            {{-- Sub-component (e.g., Lecture Quiz 40%) --}}
                                            <span class="badge bg-success-subtle text-success ps-3">
                                                <i class="bi bi-arrow-return-right me-1"></i>{{ $weight['type'] }} {{ $weight['percent'] }}%
                                            </span>
                                        @else
                                            {{-- Simple activity --}}
                                            <span class="badge bg-success-subtle text-success">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                        @endif
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

            @php
                $allDepartmentFormulas = $departmentBlueprints->flatMap(function ($blueprint) {
                    return collect($blueprint['formulas'] ?? [])->map(function ($formula) use ($blueprint) {
                        return array_merge($formula, [
                            'department_code' => $blueprint['code'],
                            'department_name' => $blueprint['name'],
                            'department_id' => $blueprint['id'],
                        ]);
                    });
                })->values();

                // Fetch global formulas from the controller data
                $globalFormulas = collect($globalFormulasList ?? [])->map(function ($formula) {
                    $weights = collect($formula->weight_map ?? [])
                        ->map(function ($weight, $type) {
                            return [
                                'type' => ucfirst(str_replace('_', ' ', $type)),
                                'percent' => (int) round($weight * 100),
                            ];
                        })
                        ->values();

                    return [
                        'id' => $formula->id,
                        'label' => $formula->label ?? 'Global Formula',
                        'is_fallback' => false,
                        'context_label' => $formula->academic_period_id 
                            ? ($formula->semester ? "{$formula->semester} Semester" : 'Period-specific')
                            : 'Applies to all periods',
                        'weights' => $weights->all(),
                        'structure_type' => $formula->structure_type ?? 'lecture_only',
                    ];
                });
            @endphp

            @if($globalFormulas->isNotEmpty())
                <div class="mb-4">
                    <h4 class="mb-3 fw-semibold text-dark">
                        <i class="bi bi-globe2 me-2"></i>Global Formulas
                    </h4>
                    <p class="text-muted small mb-3">Department-independent formulas that can be applied across all departments</p>
                </div>

                <div class="row g-4 mb-4">
                    @foreach($globalFormulas as $formula)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="formula-card card h-100 border-0 shadow-lg rounded-4 border-info" data-formula-id="{{ $formula['id'] }}">
                                <div class="card-body p-4 d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <h5 class="fw-semibold text-dark mb-1">{{ $formula['label'] }}</h5>
                                            <p class="text-muted small mb-2">
                                                <i class="bi bi-globe2 me-1"></i>Global Formula · {{ $formula['context_label'] }}
                                            </p>
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="bi bi-diagram-3 me-1"></i>Department-Independent
                                            </span>
                                        </div>
                                    </div>
                                    
                                    @if(!empty($formula['weights']))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($formula['weights'] as $weight)
                                                <span class="badge bg-info-subtle text-info">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-auto d-flex gap-2">
                                        <a href="{{ route('admin.gradesFormula.edit', ['formula' => $formula['id']]) }}" 
                                           class="btn btn-sm btn-outline-primary flex-grow-1">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger js-delete-global-formula" 
                                                data-formula-id="{{ $formula['id'] }}"
                                                data-formula-label="{{ $formula['label'] }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#delete-global-formula-modal">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($allDepartmentFormulas->isNotEmpty())
                <div class="mb-4">
                    <h4 class="mb-3 fw-semibold text-dark">Custom Department Formulas</h4>
                    <p class="text-muted small mb-3">Department-specific grading formulas with custom weights</p>
                </div>

                <div class="row g-4">
                    @foreach($allDepartmentFormulas as $formula)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="formula-card card h-100 border-0 shadow-lg rounded-4" data-formula-id="{{ $formula['id'] }}">
                                <div class="card-body p-4 d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <h5 class="fw-semibold text-dark mb-1">{{ $formula['label'] }}</h5>
                                            <p class="text-muted small mb-2">
                                                <i class="bi bi-building me-1"></i>{{ $formula['department_code'] }} - {{ $formula['department_name'] }}
                                            </p>
                                            @if($formula['is_fallback'])
                                                <span class="badge bg-primary-subtle text-primary">
                                                    <i class="bi bi-shield-check me-1"></i>Department Baseline
                                                </span>
                                            @endif
                                        </div>
                                        @if($formula['context_label'])
                                            <span class="badge bg-info-subtle text-info">{{ $formula['context_label'] }}</span>
                                        @endif
                                    </div>
                                    
                                    @if(!empty($formula['weights']))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($formula['weights'] as $weight)
                                                <span class="badge bg-success-subtle text-success">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-auto d-flex gap-2">
                                        <a href="{{ route('admin.gradesFormula.department.formulas.edit', ['department' => $formula['department_id'], 'formula' => $formula['id']]) }}" 
                                           class="btn btn-sm btn-outline-primary flex-grow-1">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        @if(!$formula['is_fallback'])
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger js-delete-formula" 
                                                    data-formula-id="{{ $formula['id'] }}"
                                                    data-formula-label="{{ $formula['label'] }}"
                                                    data-department-id="{{ $formula['department_id'] }}"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#delete-formula-modal">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
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
                            <h5 class="fw-semibold mb-1" style="color: #198754;">Bulk apply structure templates</h5>
                            <p class="text-muted mb-0">Select a structure template and push it to specific courses. Templates define grading weights that will update the department's baseline formula.</p>
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

                                <div class="row g-4 align-items-stretch">
                                    <div class="col-12">
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

                                    <div class="col-12 col-lg-6">
                                        <div class="bulk-layout-card h-100 d-flex flex-column gap-3">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <span class="form-label fw-semibold text-success mb-0">Structure Templates</span>
                                            </div>
                                            <div id="bulk-template-meta" class="text-muted small">Select a structure template to apply to the selected courses.</div>
                                            <div id="bulk-template-options" class="template-selection-grid flex-grow-1">
                                                @foreach($structureTemplates as $template)
                                                    <label class="structure-template-card rounded-4 p-3 border" data-template-key="{{ $template['key'] ?? '' }}">
                                                        <div class="d-flex align-items-start gap-3">
                                                            <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="structure_template" value="{{ $template['key'] ?? '' }}" {{ old('structure_template') === ($template['key'] ?? '') ? 'checked' : '' }}>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                                                    <h6 class="fw-semibold mb-0">{{ $template['label'] ?? 'Template' }}</h6>
                                                                    <span class="badge bg-success-subtle text-success">Template</span>
                                                                </div>
                                                                <p class="text-muted small mb-2">{{ $template['description'] ?? '' }}</p>
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    @foreach(($template['weights'] ?? []) as $weight)
                                                                        <span class="badge bg-success-subtle text-success">{{ $weight['type'] ?? '' }} {{ $weight['percent'] ?? 0 }}%</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <small class="text-muted">Select a template to instantly update the department's baseline formula with recommended weights.</small>
                                            @error('structure_template')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div id="bulk-template-selection-hint" class="text-danger small d-none mt-1">Select a template to continue.</div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="bulk-layout-card h-100 d-flex flex-column gap-3">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <span class="form-label fw-semibold text-success mb-0">Courses</span>
                                                <div class="btn-group btn-group-sm" role="group" aria-label="Course selection shortcuts">
                                                    <button class="btn btn-outline-success" type="button" id="bulk-select-all" title="Select all courses currently in view">Select visible</button>
                                                    <button class="btn btn-outline-secondary" type="button" id="bulk-clear-all" title="Clear the current selection">Clear</button>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                                                <div class="input-group input-group-sm flex-grow-1">
                                                    <span class="input-group-text text-success bg-white"><i class="bi bi-search"></i></span>
                                                    <input type="search" class="form-control" id="bulk-course-search" placeholder="Search courses or subjects" autocomplete="off" disabled>
                                                </div>
                                                <button class="btn btn-outline-secondary btn-sm flex-shrink-0" type="button" id="bulk-course-search-clear" disabled>
                                                    <i class="bi bi-x-lg me-1"></i>Reset
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                                <div id="bulk-course-summary" class="text-muted small">No courses selected yet.</div>
                                                <div id="bulk-course-count" class="text-muted small"></div>
                                            </div>
                                            <div id="bulk-course-options" class="bulk-course-scroll rounded-4 p-3 bg-white flex-grow-1">
                                                <div class="text-muted small">Select a department to load courses.</div>
                                            </div>
                                            @error('course_ids')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
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
                                        <i class="bi bi-diagram-3 me-1"></i>Apply Template to Courses
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

<div class="modal fade" id="create-formula-modal" tabindex="-1" aria-labelledby="create-formula-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="create-formula-form" method="POST" action="{{ route('admin.gradesFormula.store', $preservedQuery) }}">
                @csrf
                <input type="hidden" name="scope_level" value="global">
                <input type="hidden" name="base_score" value="60">
                <input type="hidden" name="scale_multiplier" value="40">
                <input type="hidden" name="passing_grade" value="75">
                <input type="hidden" id="create-formula-structure-type" name="structure_type" value="">
                <input type="hidden" id="create-formula-structure-config" name="structure_config" value="">
                
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold text-success" id="create-formula-modal-label">
                        <i class="bi bi-globe2 me-2"></i>Create Global Formula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <small><strong>What are Global Formulas?</strong> Global formulas are custom grading structures you create that can be reused across all departments. Unlike structure templates (which are pre-defined), these are fully customizable formulas that you define.</small>
                    </div>

                    <div class="mb-3">
                        <label for="create-formula-label" class="form-label fw-semibold">Formula Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create-formula-label" name="label" placeholder="e.g., ASBME Engineering Standard" value="{{ $oldGlobalFormulaInputs['label'] }}" required>
                        <small class="text-muted">Choose a descriptive name that indicates the formula's purpose</small>
                    </div>

                    <div class="mb-3">
                        <label for="create-formula-template" class="form-label fw-semibold">Base Structure Template <span class="text-danger">*</span></label>
                        <select class="form-select" id="create-formula-template" name="template_key" required>
                            <option value="">Select a structure template to start</option>
                            @foreach($structureTemplates as $template)
                                <option value="{{ $template['key'] }}" 
                                        data-structure-type="{{ $template['key'] }}"
                                        data-structure="{{ json_encode($template['structure'] ?? []) }}"
                                        data-weights="{{ json_encode($template['weights'] ?? []) }}"
                                        {{ $oldGlobalFormulaInputs['template_key'] === $template['key'] ? 'selected' : '' }}>
                                    {{ $template['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Choose a pre-defined structure template as your starting point. You can edit weights after creation.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Scope</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope_type" id="create-formula-scope-global" value="global" checked>
                            <label class="form-check-label" for="create-formula-scope-global">
                                <strong>Global Formula</strong>
                                <div class="text-muted small">Department-independent, can be applied to any department</div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="create-formula-context" class="form-label fw-semibold">Context (Optional)</label>
                        <select class="form-select" id="create-formula-context" name="context_type">
                            <option value="" {{ $oldGlobalFormulaInputs['context_type'] ? '' : 'selected' }}>No specific context (Applies to all periods)</option>
                            <option value="semester" {{ $oldGlobalFormulaInputs['context_type'] === 'semester' ? 'selected' : '' }}>Semester-specific</option>
                            <option value="academic_year" {{ $oldGlobalFormulaInputs['context_type'] === 'academic_year' ? 'selected' : '' }}>Academic Year-specific</option>
                        </select>
                        <small class="text-muted">Leave blank to make this formula available for all academic periods</small>
                    </div>

                    <div id="create-formula-context-semester" class="mb-3 d-none">
                        <label for="create-formula-semester" class="form-label fw-semibold">Semester</label>
                        <select class="form-select" id="create-formula-semester" name="semester">
                            <option value="">Select Semester</option>
                            <option value="1" {{ $oldGlobalFormulaInputs['semester'] === '1' ? 'selected' : '' }}>1st Semester</option>
                            <option value="2" {{ $oldGlobalFormulaInputs['semester'] === '2' ? 'selected' : '' }}>2nd Semester</option>
                            <option value="3" {{ $oldGlobalFormulaInputs['semester'] === '3' ? 'selected' : '' }}>Summer</option>
                        </select>
                    </div>

                    <div id="create-formula-context-year" class="mb-3 d-none">
                        <label for="create-formula-year" class="form-label fw-semibold">Academic Year</label>
                        <input type="text" class="form-control" id="create-formula-year" name="academic_year" placeholder="e.g., 2025-2026" value="{{ $oldGlobalFormulaInputs['academic_year'] }}">
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label for="create-formula-password" class="form-label fw-semibold text-danger">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control {{ $globalFormulaPasswordError ? 'is-invalid' : '' }}" id="create-formula-password" name="password" autocomplete="current-password" placeholder="Enter your password to confirm" required>
                        @if($globalFormulaPasswordError)
                            <div class="invalid-feedback">{{ $globalFormulaPasswordError }}</div>
                        @endif
                        <small class="text-muted">Enter your account password to authorize this action</small>
                    </div>

                    <div id="create-formula-error" class="text-danger small d-none" role="alert"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="create-formula-submit">
                        <i class="bi bi-check-circle me-1"></i>Create Formula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="delete-formula-modal" tabindex="-1" aria-labelledby="delete-formula-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="delete-formula-form" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0 pb-0 bg-danger-subtle">
                    <h5 class="modal-title fw-semibold text-danger" id="delete-formula-modal-label">
                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Formula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>

                    <p class="mb-3">Are you sure you want to delete this formula?</p>
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <strong id="delete-formula-name">Formula Name</strong>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label for="delete-formula-password" class="form-label fw-semibold text-danger">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="delete-formula-password" name="password" autocomplete="current-password" placeholder="Enter your password to confirm" required>
                        <small class="text-muted">Enter your account password to authorize this deletion</small>
                    </div>

                    <div id="delete-formula-error" class="text-danger small d-none" role="alert"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="delete-formula-submit">
                        <i class="bi bi-trash me-1"></i>Delete Formula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="delete-global-formula-modal" tabindex="-1" aria-labelledby="delete-global-formula-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="delete-global-formula-form" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0 pb-0 bg-danger-subtle">
                    <h5 class="modal-title fw-semibold text-danger" id="delete-global-formula-modal-label">
                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Global Formula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This will permanently delete this global formula!
                    </div>

                    <p class="mb-3">Are you sure you want to delete this global formula?</p>
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <strong id="delete-global-formula-name">Formula Name</strong>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label for="delete-global-formula-password" class="form-label fw-semibold text-danger">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="delete-global-formula-password" name="password" autocomplete="current-password" placeholder="Enter your password to confirm" required>
                        <small class="text-muted">Enter your account password to authorize this deletion</small>
                    </div>

                    <div id="delete-global-formula-error" class="text-danger small d-none" role="alert"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="delete-global-formula-submit">
                        <i class="bi bi-trash me-1"></i>Delete Global Formula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="modal fade" id="delete-structure-template-modal" tabindex="-1" aria-labelledby="delete-structure-template-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form
                    id="delete-structure-template-form"
                    method="POST"
                    data-action="{{ route('admin.gradesFormula.structureTemplate.destroy', array_merge(['template' => 'TEMPLATE_ID'], $preservedQuery)) }}"
                >
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-0 pb-0 bg-danger-subtle">
                        <h5 class="modal-title fw-semibold text-danger" id="delete-structure-template-modal-label">
                            <i class="bi bi-exclamation-octagon me-2"></i>Delete Structure Template
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div>
                                <strong id="delete-template-name" class="d-block mb-1"></strong>
                                <span class="small d-block">Existing formulas keep their saved structure. This only removes the reusable template.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="delete-template-password" class="form-label fw-semibold text-danger">Account Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="delete-template-password" name="password" autocomplete="current-password" placeholder="Enter your password">
                            <div class="invalid-feedback" id="delete-template-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="delete-template-confirm">
                            <i class="bi bi-trash me-1"></i>Delete Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<div class="modal fade" id="create-template-modal" tabindex="-1" aria-labelledby="create-template-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form
                id="create-template-form"
                method="POST"
                action="{{ route('admin.gradesFormula.structureTemplate.store', $preservedQuery) }}"
                data-store-action="{{ route('admin.gradesFormula.structureTemplate.store', $preservedQuery) }}"
                data-update-action="{{ route('admin.gradesFormula.structureTemplate.update', array_merge(['template' => 'TEMPLATE_ID'], $preservedQuery)) }}"
                data-initial-mode="{{ $templateModalMode }}"
            >
                @csrf
                <input type="hidden" id="template-method-field" name="_method" value="PUT" disabled>
                <input type="hidden" id="template-id-field" name="template_id" value="{{ $templateModalEditId }}">
                <div class="modal-header border-0 pb-0 bg-primary-subtle">
                    <h5 class="modal-title fw-semibold text-primary" id="create-template-modal-label">
                        <i class="bi bi-diagram-3 me-2"></i>Create Structure Template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <strong>What are Structure Templates?</strong>
                            <p class="mb-2 mt-1 small">Structure templates are reusable grading structures that define how different assessment types contribute to the final grade.</p>
                            <p class="mb-0 small"><strong>Tip:</strong> You can create complex structures like "Lecture + Laboratory" by adding main components (e.g., Lecture 60%, Laboratory 40%) and then clicking "Sub-Component" to add nested assessments (quizzes, exams, OCR) within each.</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template-label" class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="template-label" name="template_label" placeholder="e.g., Lecture + Clinical" required>
                                <small class="text-muted">Choose a descriptive name for this grading structure</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template-key" class="form-label fw-semibold">Template Key <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="template-key" name="template_key" placeholder="e.g., lecture_clinical" pattern="[a-z_]+" required>
                                <small class="text-muted">Unique identifier (lowercase, underscores only)</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="template-description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="template-description" name="template_description" rows="2" placeholder="Describe when this structure should be used..."></textarea>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-semibold mb-0">Grade Components</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-component-btn">
                                <i class="bi bi-plus-circle me-1"></i>Add Component
                            </button>
                        </div>
                        <p class="text-muted small mb-3">Define the assessment types and their weights. Total must equal 100%.</p>
                    </div>

                    <div id="components-container" class="mb-4">
                        <!-- Components will be added here dynamically -->
                    </div>

                    <div class="alert alert-warning mb-4" id="weight-warning" style="display: none;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <small>Total weight must equal <strong>100%</strong>. Current total: <span id="total-weight">0</span>%</small>
                    </div>

                    <input type="hidden" id="template-password-hidden" name="password">
                    <div id="template-error" class="text-danger small d-none" role="alert"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="create-template-submit">
                        <i class="bi bi-check-circle me-1"></i>Create Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="template-password-modal" tabindex="-1" aria-labelledby="template-password-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 bg-primary-subtle">
                <h5 class="modal-title fw-semibold text-primary" id="template-password-modal-label">
                    <i class="bi bi-shield-lock me-2"></i>Confirm Template Creation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Enter your account password to confirm creating this structure template.</p>
                <div class="mb-3">
                    <label for="template-password-input" class="form-label fw-semibold text-primary">Account Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="template-password-input" autocomplete="current-password" placeholder="Enter your password">
                    <div class="invalid-feedback">Password is required.</div>
                </div>
                <div id="template-password-modal-error" class="text-danger small d-none" role="alert" aria-live="assertive"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="template-password-confirm">
                    <i class="bi bi-check-circle me-1"></i>Confirm and Create
                </button>
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
        const shouldReopenTemplateModal = Boolean(@json($shouldReopenTemplateModal));
        const shouldReopenCreateFormulaModal = Boolean(@json($shouldReopenCreateFormulaModal));
        const templateModalInitialMode = @json($templateModalMode);
        const templateModalInitialEditId = @json($templateModalEditId);
        const templateDeleteReopenId = @json($reopenTemplateDeleteId);
        const templateDeleteErrorMessage = @json($deleteTemplatePasswordError);
        const templateUpdatePlaceholder = 'TEMPLATE_ID';
        const structureTemplates = @json($structureTemplatePayload);

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
        const templateContainer = document.getElementById('bulk-template-options');
        const templateMeta = document.getElementById('bulk-template-meta');
        const templateSelectionHint = document.getElementById('bulk-template-selection-hint');
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const baselinePasswordRequirement = bulkForm.dataset.requiresPassword === '1';

        let currentDepartment = null;
        let currentTemplateKey = null;
        const selectedCourses = new Set();
        const expandedCourses = new Set();
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

        const refreshTemplateSelectionStyles = () => {
            if (! templateContainer) {
                return;
            }
            templateContainer.querySelectorAll('.structure-template-card').forEach((card) => {
                const input = card.querySelector('input[type="radio"]');
                card.classList.toggle('is-selected', Boolean(input?.checked));
            });
        };

        const syncApplyButtonState = () => {
            const templateSelected = Boolean(currentTemplateKey);
            const coursesSelected = selectedCourses.size > 0;

            if (bulkApplyButton) {
                bulkApplyButton.disabled = ! templateSelected || ! coursesSelected;
            }

            if (templateSelectionHint) {
                const shouldShowHint = ! templateSelected;
                templateSelectionHint.classList.toggle('d-none', ! shouldShowHint);
            }
        };

        const initializeTemplateSelection = () => {
            if (! templateContainer) {
                return;
            }

            // Add click handlers to template cards
            templateContainer.querySelectorAll('.structure-template-card').forEach((card) => {
                const radio = card.querySelector('input[type="radio"]');
                if (! radio) {
                    return;
                }

                // Make the entire card clickable
                card.addEventListener('click', (event) => {
                    // Don't trigger if clicking directly on radio (it handles itself)
                    if (event.target === radio) {
                        return;
                    }
                    
                    radio.checked = true;
                    currentTemplateKey = radio.value;
                    refreshTemplateSelectionStyles();
                    syncApplyButtonState();
                });

                // Also handle radio change directly
                radio.addEventListener('change', () => {
                    currentTemplateKey = radio.value;
                    refreshTemplateSelectionStyles();
                    syncApplyButtonState();
                });
            });

            // Check if there's a pre-selected template
            const selectedRadio = templateContainer.querySelector('input[name="structure_template"]:checked');
            if (selectedRadio) {
                currentTemplateKey = selectedRadio.value;
            }

            refreshTemplateSelectionStyles();
            syncApplyButtonState();
        };

        // Initialize template selection
        initializeTemplateSelection();
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

                const formulaSourceRaw = typeof course.formula_source === 'string' ? course.formula_source : '';
                const formulaSource = ['course', 'department', 'global'].includes(formulaSourceRaw)
                    ? formulaSourceRaw
                    : 'global';
                const formulaChipClass = formulaSource === 'course'
                    ? 'course-formula-chip course-formula-chip-course'
                    : (formulaSource === 'department'
                        ? 'course-formula-chip course-formula-chip-department'
                        : 'course-formula-chip course-formula-chip-global');
                const formulaIcon = formulaSource === 'course'
                    ? 'bi-stars'
                    : (formulaSource === 'department' ? 'bi-diagram-3' : 'bi-globe2');
                const formulaScope = course.formula_scope ? escapeHtml(course.formula_scope) : '';
                const formulaLabel = course.formula_label ? escapeHtml(course.formula_label) : '';
                const formulaChip = (formulaScope || formulaLabel)
                    ? `<div class="course-formula-meta">
                            <span class="${formulaChipClass}"><i class="bi ${formulaIcon} me-1"></i>${formulaScope || 'Formula'}</span>
                            ${formulaLabel ? `<span class="course-formula-label text-muted">${formulaLabel}</span>` : ''}
                        </div>`
                    : '';

                const subjectMarkup = subjects.length === 0
                    ? '<div class="text-muted small">No subjects assigned yet.</div>'
                    : subjects.map((subject) => {
                        const label = subject.label ?? 'Subject';
                        const pillClass = subject.has_grades ? 'subject-pill subject-pill-warning' : 'subject-pill';
                        const icon = subject.has_grades ? 'bi-exclamation-triangle-fill text-danger' : 'bi-journal-text text-success';

                        const formulaSourceRaw = typeof subject.formula_source === 'string' ? subject.formula_source : 'global';
                        const allowedSources = ['subject', 'course', 'department', 'global'];
                        const formulaSource = allowedSources.includes(formulaSourceRaw) ? formulaSourceRaw : 'global';

                        const subjectFormulaChipClass = (() => {
                            switch (formulaSource) {
                                case 'subject':
                                    return 'subject-formula-chip subject-formula-chip-subject';
                                case 'course':
                                    return 'subject-formula-chip subject-formula-chip-course';
                                case 'department':
                                    return 'subject-formula-chip subject-formula-chip-department';
                                default:
                                    return 'subject-formula-chip subject-formula-chip-global';
                            }
                        })();

                        const subjectFormulaIcon = (() => {
                            switch (formulaSource) {
                                case 'subject':
                                    return 'bi-bullseye';
                                case 'course':
                                    return 'bi-stars';
                                case 'department':
                                    return 'bi-diagram-3';
                                default:
                                    return 'bi-globe2';
                            }
                        })();

                        const formulaScope = subject.formula_scope ? escapeHtml(subject.formula_scope) : '';
                        const formulaLabel = subject.formula_label ? escapeHtml(subject.formula_label) : '';

                        const formulaChip = (formulaScope || formulaLabel)
                            ? `<div class="subject-formula-meta">
                                    <span class="${subjectFormulaChipClass}"><i class="bi ${subjectFormulaIcon} me-1"></i>${formulaScope || 'Formula'}</span>
                                    ${formulaLabel ? `<span class="subject-formula-label text-muted">${formulaLabel}</span>` : ''}
                                </div>`
                            : '';

                        return `
                            <div class="subject-chip-card">
                                <span class="${pillClass}" title="${escapeHtml(label)}">
                                    <i class="bi ${icon}"></i>
                                    <span class="subject-pill-text">${escapeHtml(label)}</span>
                                </span>
                                ${formulaChip}
                            </div>
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
                                ${formulaChip}
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
            if (! currentTemplateKey) {
                event.preventDefault();
                syncApplyButtonState();
                templateContainer?.querySelector('input[type="radio"]')?.focus();
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

        // Create Formula Modal Logic
        const createFormulaModal = document.getElementById('create-formula-modal');
        const createFormulaForm = document.getElementById('create-formula-form');
        const createFormulaContext = document.getElementById('create-formula-context');
        const createFormulaContextSemester = document.getElementById('create-formula-context-semester');
        const createFormulaContextYear = document.getElementById('create-formula-context-year');
        const createFormulaError = document.getElementById('create-formula-error');
        const createFormulaPassword = document.getElementById('create-formula-password');
        const createFormulaTemplate = document.getElementById('create-formula-template');
        const createFormulaStructureType = document.getElementById('create-formula-structure-type');
        const createFormulaStructureConfig = document.getElementById('create-formula-structure-config');
        const createFormulaModalInstance = createFormulaModal && window.bootstrap?.Modal ? window.bootstrap.Modal.getOrCreateInstance(createFormulaModal) : null;

        // Handle template selection to populate hidden fields
        const syncCreateFormulaStructure = () => {
            if (! createFormulaTemplate) {
                return;
            }

            const selectedOption = createFormulaTemplate.options[createFormulaTemplate.selectedIndex];
            if (! selectedOption || ! selectedOption.value) {
                if (createFormulaStructureType) {
                    createFormulaStructureType.value = '';
                }
                if (createFormulaStructureConfig) {
                    createFormulaStructureConfig.value = '';
                }
                return;
            }

            const structureType = selectedOption.dataset.structureType || selectedOption.value || '';
            const structureJson = selectedOption.dataset.structure || '';

            if (createFormulaStructureType) {
                createFormulaStructureType.value = structureType;
            }

            if (createFormulaStructureConfig) {
                try {
                    const payload = structureJson ? JSON.parse(structureJson) : null;
                    createFormulaStructureConfig.value = payload ? JSON.stringify(payload) : '';
                } catch (error) {
                    createFormulaStructureConfig.value = '';
                }
            }
        };

        createFormulaTemplate?.addEventListener('change', syncCreateFormulaStructure);
        syncCreateFormulaStructure();

        const syncCreateFormulaContext = () => {
            const contextType = createFormulaContext?.value ?? '';

            if (createFormulaContextSemester) {
                createFormulaContextSemester.classList.toggle('d-none', contextType !== 'semester');
                const semesterSelect = createFormulaContextSemester.querySelector('select');
                if (semesterSelect) {
                    semesterSelect.required = contextType === 'semester';
                }
            }

            if (createFormulaContextYear) {
                createFormulaContextYear.classList.toggle('d-none', contextType !== 'academic_year');
                const yearInput = createFormulaContextYear.querySelector('input');
                if (yearInput) {
                    yearInput.required = contextType === 'academic_year';
                }
            }
        };

        createFormulaContext?.addEventListener('change', syncCreateFormulaContext);
        syncCreateFormulaContext();

        // Reset form when modal is closed
        createFormulaModal?.addEventListener('hidden.bs.modal', function() {
            if (createFormulaForm) {
                createFormulaForm.reset();
            }
            if (createFormulaError) {
                createFormulaError.classList.add('d-none');
                createFormulaError.textContent = '';
            }
            if (createFormulaPassword) {
                createFormulaPassword.classList.remove('is-invalid');
            }
            if (createFormulaContextSemester) {
                createFormulaContextSemester.classList.add('d-none');
            }
            if (createFormulaContextYear) {
                createFormulaContextYear.classList.add('d-none');
            }
            if (createFormulaStructureType) {
                createFormulaStructureType.value = '';
            }
            if (createFormulaStructureConfig) {
                createFormulaStructureConfig.value = '';
            }
        });

        // Focus password when modal is shown
        createFormulaModal?.addEventListener('shown.bs.modal', function() {
            const formulaLabelInput = document.getElementById('create-formula-label');
            if (formulaLabelInput) {
                formulaLabelInput.focus();
            }
        });

        if (shouldReopenCreateFormulaModal && createFormulaModalInstance) {
            createFormulaModalInstance.show();
        }

        // Delete Formula Modal Logic
        const deleteFormulaModal = document.getElementById('delete-formula-modal');
        const deleteFormulaForm = document.getElementById('delete-formula-form');
        const deleteFormulaName = document.getElementById('delete-formula-name');
        const deleteFormulaError = document.getElementById('delete-formula-error');
        const deleteFormulaPassword = document.getElementById('delete-formula-password');
        const deleteButtons = document.querySelectorAll('.js-delete-formula');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const formulaId = this.dataset.formulaId;
                const formulaLabel = this.dataset.formulaLabel || 'this formula';
                const departmentId = this.dataset.departmentId;

                if (deleteFormulaName) {
                    deleteFormulaName.textContent = formulaLabel;
                }

                if (deleteFormulaForm && formulaId && departmentId) {
                    const actionUrl = `{{ url('/admin/grades-formula/department') }}/${departmentId}/formulas/${formulaId}`;
                    deleteFormulaForm.setAttribute('action', actionUrl);
                }
            });
        });

        // Reset delete form when modal is closed
        deleteFormulaModal?.addEventListener('hidden.bs.modal', function() {
            if (deleteFormulaForm) {
                deleteFormulaForm.reset();
            }
            if (deleteFormulaError) {
                deleteFormulaError.classList.add('d-none');
                deleteFormulaError.textContent = '';
            }
            if (deleteFormulaPassword) {
                deleteFormulaPassword.classList.remove('is-invalid');
            }
        });

        // Focus password when delete modal is shown
        deleteFormulaModal?.addEventListener('shown.bs.modal', function() {
            if (deleteFormulaPassword) {
                window.setTimeout(() => deleteFormulaPassword.focus(), 120);
            }
        });

        // Delete Global Formula Modal Logic
        const deleteGlobalFormulaModal = document.getElementById('delete-global-formula-modal');
        const deleteGlobalFormulaForm = document.getElementById('delete-global-formula-form');
        const deleteGlobalFormulaName = document.getElementById('delete-global-formula-name');
        const deleteGlobalFormulaError = document.getElementById('delete-global-formula-error');
        const deleteGlobalFormulaPassword = document.getElementById('delete-global-formula-password');
        const deleteGlobalButtons = document.querySelectorAll('.js-delete-global-formula');

        deleteGlobalButtons.forEach(button => {
            button.addEventListener('click', function() {
                const formulaId = this.dataset.formulaId;
                const formulaLabel = this.dataset.formulaLabel || 'this formula';

                if (deleteGlobalFormulaName) {
                    deleteGlobalFormulaName.textContent = formulaLabel;
                }

                if (deleteGlobalFormulaForm && formulaId) {
                    const actionUrl = `{{ url('/admin/grades-formula') }}/${formulaId}`;
                    deleteGlobalFormulaForm.setAttribute('action', actionUrl);
                }
            });
        });

        // Reset delete global form when modal is closed
        deleteGlobalFormulaModal?.addEventListener('hidden.bs.modal', function() {
            if (deleteGlobalFormulaForm) {
                deleteGlobalFormulaForm.reset();
            }
            if (deleteGlobalFormulaError) {
                deleteGlobalFormulaError.classList.add('d-none');
                deleteGlobalFormulaError.textContent = '';
            }
            if (deleteGlobalFormulaPassword) {
                deleteGlobalFormulaPassword.classList.remove('is-invalid');
            }
        });

        // Focus password when delete global modal is shown
        deleteGlobalFormulaModal?.addEventListener('shown.bs.modal', function() {
            if (deleteGlobalFormulaPassword) {
                window.setTimeout(() => deleteGlobalFormulaPassword.focus(), 120);
            }
        });

        // Create Structure Template Modal Logic
        const createTemplateModal = document.getElementById('create-template-modal');
        const createTemplateForm = document.getElementById('create-template-form');
        const componentsContainer = document.getElementById('components-container');
        const addComponentBtn = document.getElementById('add-component-btn');
        const weightWarning = document.getElementById('weight-warning');
        const totalWeightSpan = document.getElementById('total-weight');
        const templateKeyInput = document.getElementById('template-key');
        const templateLabelInput = document.getElementById('template-label');
        const templateDescriptionInput = document.getElementById('template-description');
        const templatePasswordHidden = document.getElementById('template-password-hidden');
        const templateErrorContainer = document.getElementById('template-error');
        const templatePasswordModal = document.getElementById('template-password-modal');
        const templatePasswordInput = document.getElementById('template-password-input');
        const templatePasswordConfirm = document.getElementById('template-password-confirm');
        const templatePasswordModalError = document.getElementById('template-password-modal-error');
        const createTemplateSubmitBtn = document.getElementById('create-template-submit');
        const templateMethodField = document.getElementById('template-method-field');
        const templateIdField = document.getElementById('template-id-field');
        const openCreateTemplateBtn = document.getElementById('open-create-template');
        const createTemplateModalLabel = document.getElementById('create-template-modal-label');
        const templatePasswordModalLabel = document.getElementById('template-password-modal-label');
        const deleteTemplateModal = document.getElementById('delete-structure-template-modal');
        const deleteTemplateForm = document.getElementById('delete-structure-template-form');
        const deleteTemplateName = document.getElementById('delete-template-name');
        const deleteTemplatePassword = document.getElementById('delete-template-password');
        const deleteTemplateError = document.getElementById('delete-template-error');
        const templateStoreAction = createTemplateForm?.dataset.storeAction ?? '';
        const templateUpdateActionPattern = createTemplateForm?.dataset.updateAction ?? '';
        const deleteTemplateActionPattern = deleteTemplateForm?.dataset.action ?? '';
        const templateModeState = {
            mode: templateModalInitialMode || 'create',
            editingId: templateModalInitialEditId ? String(templateModalInitialEditId) : null,
        };

    const templateErrorMessages = @json($templateErrorMessages);
        const oldTemplateInputs = @json($oldTemplateInputs);
        const oldTemplateComponents = oldTemplateInputs && oldTemplateInputs.components ? oldTemplateInputs.components : {};
    const editTemplateButtons = document.querySelectorAll('.js-edit-structure-template');
    const deleteTemplateButtons = document.querySelectorAll('.js-delete-structure-template');

        const bootstrapModalInstance = templatePasswordModal && window.bootstrap?.Modal ? new window.bootstrap.Modal(templatePasswordModal) : null;
        const deleteTemplateModalInstance = deleteTemplateModal && window.bootstrap?.Modal ? window.bootstrap.Modal.getOrCreateInstance(deleteTemplateModal) : null;

        let createTemplateModalInstance = null;
        if (createTemplateModal && window.bootstrap?.Modal) {
            createTemplateModalInstance = window.bootstrap.Modal.getOrCreateInstance(createTemplateModal);
        }

        let componentCounter = 0;

        openCreateTemplateBtn?.addEventListener('click', () => {
            applyTemplateMode('create');
            if (componentsContainer) {
                componentsContainer.innerHTML = '';
            }
            componentCounter = 0;
            renderTemplateErrors([]);
            updateWeightWarning();
            if (templatePasswordHidden) {
                templatePasswordHidden.value = '';
            }
            if (templatePasswordInput) {
                templatePasswordInput.value = '';
                templatePasswordInput.classList.remove('is-invalid');
            }
            if (templatePasswordModalError) {
                templatePasswordModalError.classList.add('d-none');
                templatePasswordModalError.textContent = '';
            }
        });

        const formatPercentValue = (value) => {
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return '';
            }

            if (Number.isInteger(numeric)) {
                return numeric.toString();
            }

            const fixed = (Math.round(numeric * 100) / 100).toFixed(2);
            return fixed
                .replace(/(\.\d*?[1-9])0+$/, '$1')
                .replace(/\.00$/, '')
                .replace(/\.$/, '');
        };

        function convertStructureToComponentMap(structure) {
            const map = {};
            if (!structure || typeof structure !== 'object') {
                return map;
            }

            let counter = 1;
            const rootChildren = Array.isArray(structure.children) ? structure.children : [];

            rootChildren.forEach((child) => {
                const entry = typeof child === 'object' && child !== null ? child : {};
                const currentId = counter++;
                const childWeight = entry.weight_percent ?? ((entry.weight ?? 0) * 100);

                map[currentId] = {
                    activity_type: entry.activity_type ?? entry.key ?? '',
                    weight: formatPercentValue(childWeight),
                    label: entry.label ?? '',
                    is_main: 1,
                };

                const subChildren = Array.isArray(entry.children) ? entry.children : [];

                subChildren.forEach((subChild) => {
                    const subEntry = typeof subChild === 'object' && subChild !== null ? subChild : {};
                    const subId = counter++;
                    const subWeight = subEntry.weight_percent ?? ((subEntry.weight ?? 0) * 100);

                    map[subId] = {
                        activity_type: subEntry.activity_type ?? subEntry.key ?? '',
                        weight: formatPercentValue(subWeight),
                        label: subEntry.label ?? '',
                        parent_id: currentId,
                    };
                });
            });

            return map;
        }

        function applyTemplateMode(mode, templateData = null, options = {}) {
            const preserveExistingValues = options.preserveExistingValues ?? false;

            templateModeState.mode = mode;
            templateModeState.editingId = templateData && templateData.id ? String(templateData.id) : null;

            if (!createTemplateForm) {
                return;
            }

            if (mode === 'edit' && templateData) {
                if (templateUpdateActionPattern) {
                    createTemplateForm.action = templateUpdateActionPattern.replace(templateUpdatePlaceholder, templateModeState.editingId ?? '');
                }
                if (templateMethodField) {
                    templateMethodField.disabled = false;
                    templateMethodField.value = 'PUT';
                }
                if (templateIdField) {
                    templateIdField.value = templateModeState.editingId ?? '';
                }
                if (!preserveExistingValues) {
                    if (templateLabelInput) {
                        templateLabelInput.value = templateData.label ?? '';
                    }
                    if (templateDescriptionInput) {
                        templateDescriptionInput.value = templateData.description ?? '';
                    }
                }
                if (templateKeyInput) {
                    templateKeyInput.value = templateData.template_key ?? templateData.key ?? '';
                    templateKeyInput.readOnly = true;
                    templateKeyInput.classList.add('bg-light');
                    templateKeyInput.dataset.userModified = 'true';
                }
                if (createTemplateModalLabel) {
                    createTemplateModalLabel.textContent = 'Edit Structure Template';
                }
                if (createTemplateSubmitBtn) {
                    createTemplateSubmitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Changes';
                }
                if (templatePasswordModalLabel) {
                    templatePasswordModalLabel.innerHTML = '<i class="bi bi-shield-lock me-2"></i>Confirm Template Update';
                }
                if (templatePasswordConfirm) {
                    templatePasswordConfirm.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm and Update';
                }
            } else {
                if (templateStoreAction) {
                    createTemplateForm.action = templateStoreAction;
                }
                if (templateMethodField) {
                    templateMethodField.disabled = true;
                    templateMethodField.value = 'PUT';
                }
                if (templateIdField) {
                    templateIdField.value = '';
                }
                if (!preserveExistingValues) {
                    if (templateLabelInput) {
                        templateLabelInput.value = '';
                    }
                    if (templateDescriptionInput) {
                        templateDescriptionInput.value = '';
                    }
                }
                if (templateKeyInput) {
                    templateKeyInput.readOnly = false;
                    templateKeyInput.classList.remove('bg-light');
                    if (!preserveExistingValues) {
                        templateKeyInput.value = '';
                    }
                    delete templateKeyInput.dataset.userModified;
                }
                if (createTemplateModalLabel) {
                    createTemplateModalLabel.textContent = 'Create Structure Template';
                }
                if (createTemplateSubmitBtn) {
                    createTemplateSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Create Template';
                }
                if (templatePasswordModalLabel) {
                    templatePasswordModalLabel.innerHTML = '<i class="bi bi-shield-lock me-2"></i>Confirm Template Creation';
                }
                if (templatePasswordConfirm) {
                    templatePasswordConfirm.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm and Create';
                }
            }
        }

        function loadTemplateStructure(structure) {
            if (!componentsContainer) {
                return;
            }

            componentsContainer.innerHTML = '';
            componentCounter = 0;

            const componentMap = convertStructureToComponentMap(structure ?? {});
            if (Object.keys(componentMap).length === 0) {
                return;
            }

            restoreTemplateComponents(componentMap);
            updateWeightWarning();
        }

        function calculateTotalWeight() {
            // Only count main components (exclude sub-components)
            const mainComponents = document.querySelectorAll('.component-item[data-is-main="true"]');
            let total = 0;
            mainComponents.forEach(component => {
                const weightInput = component.querySelector('.component-weight');
                if (weightInput) {
                    const value = parseFloat(weightInput.value) || 0;
                    total += value;
                }
            });
            return Math.round(total * 10) / 10;
        }

        function updateWeightWarning() {
            const total = calculateTotalWeight();
            if (totalWeightSpan) {
                totalWeightSpan.textContent = total;
            }
            if (weightWarning) {
                if (Math.abs(total - 100) > 0.1) {
                    weightWarning.style.display = 'block';
                    if (total > 100) {
                        weightWarning.classList.remove('alert-warning');
                        weightWarning.classList.add('alert-danger');
                    } else {
                        weightWarning.classList.remove('alert-danger');
                        weightWarning.classList.add('alert-warning');
                    }
                } else {
                    weightWarning.style.display = 'none';
                }
            }
        }

        function addComponent(type = '', weight = '', label = '', isMain = true, parentId = null) {
            componentCounter++;
            const currentId = componentCounter;
            const isSubComponent = !isMain;

            const componentHtml = `
                <div class="component-item card mb-3 ${isSubComponent ? 'ms-4 border-start border-3 border-primary' : ''}" data-component-id="${currentId}" data-is-main="${isMain}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-semibold ${isSubComponent ? 'text-secondary' : 'text-primary'}">
                                ${isSubComponent ? '<i class="bi bi-arrow-return-right me-1"></i>Sub-Component' : 'Main Component'} ${isSubComponent ? '' : currentId}
                            </h6>
                            <div>
                                ${!isSubComponent ? `
                                <button type="button" class="btn btn-sm btn-outline-primary me-1 add-subcomponent-btn" data-component-id="${currentId}" title="Add Sub-Component">
                                    <i class="bi bi-plus-circle"></i> Sub-Component
                                </button>
                                ` : ''}
                                <button type="button" class="btn btn-sm btn-outline-danger remove-component-btn" data-component-id="${currentId}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Activity Type</label>
                                <input type="text" class="form-control form-control-sm component-activity-type" name="components[${currentId}][activity_type]" value="${type}" placeholder="e.g., Quiz, Exam, OCR" required>
                                ${!isSubComponent ? `<input type="hidden" name="components[${currentId}][is_main]" value="1">` : `<input type="hidden" name="components[${currentId}][parent_id]" value="${parentId}">`}
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Weight (%)</label>
                                <input type="number" class="form-control form-control-sm component-weight" name="components[${currentId}][weight]" value="${weight}" min="0" max="100" step="0.1" required>
                                ${!isSubComponent ? '<small class="text-muted">Main component weight</small>' : '<small class="text-muted">Sub-component weight (relative to parent)</small>'}
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Label</label>
                                <input type="text" class="form-control form-control-sm component-label" name="components[${currentId}][label]" value="${label}" placeholder="e.g., ${isSubComponent ? 'Lecture Quizzes' : 'Lecture Component'}" required>
                            </div>
                        </div>
                    </div>
                    <div class="subcomponents-container" data-parent-id="${currentId}"></div>
                </div>
            `;

            if (isSubComponent && parentId) {
                const parentContainer = document.querySelector(`.subcomponents-container[data-parent-id="${parentId}"]`);
                if (parentContainer) {
                    parentContainer.insertAdjacentHTML('beforeend', componentHtml);
                }
            } else if (componentsContainer) {
                componentsContainer.insertAdjacentHTML('beforeend', componentHtml);
            }

            updateWeightWarning();

            const newComponent = document.querySelector(`.component-item[data-component-id="${currentId}"]`);
            if (!newComponent) {
                return currentId;
            }

            const weightInput = newComponent.querySelector('.component-weight');
            if (weightInput) {
                weightInput.addEventListener('input', updateWeightWarning);
            }

            const activityTypeInput = newComponent.querySelector('.component-activity-type');
            const labelInput = newComponent.querySelector('.component-label');
            if (activityTypeInput && labelInput) {
                activityTypeInput.addEventListener('input', function() {
                    if (!labelInput.dataset.userModified) {
                        labelInput.value = this.value;
                    }
                });
                labelInput.addEventListener('input', function() {
                    if (this.value !== activityTypeInput.value) {
                        this.dataset.userModified = 'true';
                    }
                });
            }

            const removeBtn = newComponent.querySelector('.remove-component-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    const componentId = this.dataset.componentId;
                    const component = document.querySelector(`.component-item[data-component-id="${componentId}"]`);
                    if (component) {
                        component.remove();
                        updateWeightWarning();
                    }
                });
            }

            if (!isSubComponent) {
                const addSubBtn = newComponent.querySelector('.add-subcomponent-btn');
                if (addSubBtn) {
                    addSubBtn.addEventListener('click', function() {
                        const parentIdValue = this.dataset.componentId;
                        addComponent('', '', '', false, parentIdValue);
                    });
                }
            }

            return currentId;
        }

        function resolveComponentValue(component, key, fallback = '') {
            if (!component || typeof component !== 'object') {
                return fallback;
            }

            const value = component[key];
            return value === undefined || value === null ? fallback : value;
        }

        function isMainComponent(component) {
            if (!component || typeof component !== 'object') {
                return false;
            }

            const explicit = resolveComponentValue(component, 'is_main', null);
            if (explicit !== null && explicit !== '' && explicit !== false) {
                return explicit === true || explicit === 1 || explicit === '1';
            }

            const parentId = resolveComponentValue(component, 'parent_id', null);
            return parentId === null || parentId === '';
        }

        function renderTemplateErrors(messages) {
            if (!templateErrorContainer) {
                return;
            }

            templateErrorContainer.innerHTML = '';

            if (!Array.isArray(messages) || messages.length === 0) {
                templateErrorContainer.classList.add('d-none');
                return;
            }

            messages.forEach((message) => {
                const item = document.createElement('div');
                item.textContent = message;
                templateErrorContainer.appendChild(item);
            });

            templateErrorContainer.classList.remove('d-none');
        }

        function restoreTemplateComponents(oldComponents) {
            if (!oldComponents || typeof oldComponents !== 'object' || Object.keys(oldComponents).length === 0) {
                return false;
            }

            const entries = Object.entries(oldComponents);
            if (entries.length === 0) {
                return false;
            }

            const mainEntries = entries.filter(([, component]) => isMainComponent(component));
            const subEntries = entries.filter(([, component]) => !isMainComponent(component) && resolveComponentValue(component, 'parent_id', null) !== null);

            mainEntries.sort((a, b) => Number(a[0]) - Number(b[0]));
            subEntries.sort((a, b) => Number(a[0]) - Number(b[0]));

            const idMap = {};

            mainEntries.forEach(([oldId, component]) => {
                const newId = addComponent(
                    resolveComponentValue(component, 'activity_type', ''),
                    resolveComponentValue(component, 'weight', ''),
                    resolveComponentValue(component, 'label', ''),
                    true,
                    null
                );
                idMap[oldId] = newId;
            });

            subEntries.forEach(([oldId, component]) => {
                const parentOldId = resolveComponentValue(component, 'parent_id', null);
                const parentNewId = parentOldId !== null ? idMap[parentOldId] : null;
                if (!parentNewId) {
                    return;
                }

                addComponent(
                    resolveComponentValue(component, 'activity_type', ''),
                    resolveComponentValue(component, 'weight', ''),
                    resolveComponentValue(component, 'label', ''),
                    false,
                    parentNewId
                );
            });

            updateWeightWarning();
            return true;
        }

        addComponentBtn?.addEventListener('click', () => addComponent());

        editTemplateButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const templateId = button.dataset.templateId;
                if (!templateId) {
                    return;
                }

                const template = structureTemplates.find((entry) => String(entry.id) === String(templateId));
                if (!template) {
                    return;
                }

                if (createTemplateForm) {
                    createTemplateForm.reset();
                }

                applyTemplateMode('edit', template);
                renderTemplateErrors([]);
                if (templatePasswordHidden) {
                    templatePasswordHidden.value = '';
                }
                if (templatePasswordInput) {
                    templatePasswordInput.value = '';
                    templatePasswordInput.classList.remove('is-invalid');
                }
                if (templatePasswordModalError) {
                    templatePasswordModalError.classList.add('d-none');
                    templatePasswordModalError.textContent = '';
                }
                loadTemplateStructure(template.structure);

                if (createTemplateModalInstance) {
                    createTemplateModalInstance.show();
                }
            });
        });

        deleteTemplateButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const templateId = button.dataset.templateId;
                if (!templateId) {
                    return;
                }

                const template = structureTemplates.find((entry) => String(entry.id) === String(templateId));
                if (!template) {
                    return;
                }

                if (deleteTemplateForm && deleteTemplateActionPattern) {
                    deleteTemplateForm.action = deleteTemplateActionPattern.replace(templateUpdatePlaceholder, templateId);
                }

                if (deleteTemplateName) {
                    deleteTemplateName.textContent = template.label ?? 'Structure Template';
                }

                if (deleteTemplatePassword) {
                    deleteTemplatePassword.value = '';
                    deleteTemplatePassword.classList.remove('is-invalid');
                }

                if (deleteTemplateError) {
                    deleteTemplateError.textContent = '';
                }

                if (deleteTemplateModalInstance) {
                    deleteTemplateModalInstance.show();
                }
            });
        });

        deleteTemplateModal?.addEventListener('hidden.bs.modal', () => {
            if (deleteTemplateForm) {
                deleteTemplateForm.reset();
            }
            if (deleteTemplateError) {
                deleteTemplateError.textContent = '';
            }
            deleteTemplatePassword?.classList.remove('is-invalid');
        });

        deleteTemplateModal?.addEventListener('shown.bs.modal', () => {
            window.setTimeout(() => deleteTemplatePassword?.focus(), 120);
        });

        templateLabelInput?.addEventListener('input', function() {
            if (templateKeyInput && !templateKeyInput.dataset.userModified) {
                const key = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
                templateKeyInput.value = key;
            }
        });

        templateKeyInput?.addEventListener('input', function() {
            this.dataset.userModified = 'true';
        });

        if (createTemplateModal) {
            createTemplateModal.addEventListener('shown.bs.modal', function() {
                if (componentsContainer && componentsContainer.children.length === 0) {
                    let restored = false;
                    if (shouldReopenTemplateModal) {
                        restored = restoreTemplateComponents(oldTemplateComponents);
                    }

                    if (!restored) {
                        addComponent('quiz', '40', 'Quizzes');
                        addComponent('exam', '40', 'Exam');
                        addComponent('ocr', '20', 'Other Course Requirements');
                    }
                }
                if (shouldReopenTemplateModal) {
                    if (templateLabelInput) {
                        const labelValue = oldTemplateInputs && oldTemplateInputs.label ? oldTemplateInputs.label : '';
                        templateLabelInput.value = labelValue;
                    }

                    if (templateKeyInput) {
                        const keyValue = oldTemplateInputs && oldTemplateInputs.key ? oldTemplateInputs.key : '';
                        if (keyValue !== '') {
                            templateKeyInput.value = keyValue;
                            templateKeyInput.dataset.userModified = 'true';
                        }
                    }

                    if (templateDescriptionInput) {
                        const descriptionValue = oldTemplateInputs && oldTemplateInputs.description ? oldTemplateInputs.description : '';
                        templateDescriptionInput.value = descriptionValue;
                    }

                    renderTemplateErrors(templateErrorMessages);
                }

                templateLabelInput?.focus();
            });

            createTemplateModal.addEventListener('hidden.bs.modal', function() {
                applyTemplateMode('create');
                if (createTemplateForm) {
                    createTemplateForm.reset();
                }
                if (componentsContainer) {
                    componentsContainer.innerHTML = '';
                }
                componentCounter = 0;
                if (templateKeyInput) {
                    delete templateKeyInput.dataset.userModified;
                }
                if (templatePasswordHidden) {
                    templatePasswordHidden.value = '';
                }
                renderTemplateErrors([]);
                updateWeightWarning();
            });
        }

        if (shouldReopenTemplateModal && createTemplateModalInstance) {
            if (templateModalInitialMode === 'edit' && templateModalInitialEditId) {
                const template = structureTemplates.find((entry) => String(entry.id) === String(templateModalInitialEditId));
                applyTemplateMode('edit', template || null, { preserveExistingValues: true });
            } else {
                applyTemplateMode('create', null, { preserveExistingValues: true });
            }

            createTemplateModalInstance.show();
        }

        if (templateDeleteReopenId && deleteTemplateModalInstance) {
            const template = structureTemplates.find((entry) => String(entry.id) === String(templateDeleteReopenId));

            if (deleteTemplateForm && deleteTemplateActionPattern) {
                deleteTemplateForm.action = deleteTemplateActionPattern.replace(templateUpdatePlaceholder, templateDeleteReopenId);
            }

            if (deleteTemplateName) {
                deleteTemplateName.textContent = template?.label ?? 'Structure Template';
            }

            if (deleteTemplatePassword) {
                deleteTemplatePassword.value = '';
                if (templateDeleteErrorMessage) {
                    deleteTemplatePassword.classList.add('is-invalid');
                }
            }

            if (deleteTemplateError) {
                deleteTemplateError.textContent = templateDeleteErrorMessage ?? '';
            }

            deleteTemplateModalInstance.show();
        }

        // Handle "Create Template" button click - show password modal
        createTemplateSubmitBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate the form first
            if (!createTemplateForm.checkValidity()) {
                createTemplateForm.reportValidity();
                return;
            }

            // Check weight total
            const total = calculateTotalWeight();
            if (Math.abs(total - 100) > 0.1) {
                alert('Total weight must equal 100%. Current total: ' + total + '%');
                return;
            }

            // Show password modal
            if (bootstrapModalInstance) {
                bootstrapModalInstance.show();
            }
        });

        // Handle password modal confirmation
        templatePasswordConfirm?.addEventListener('click', function() {
            const password = templatePasswordInput?.value || '';
            
            if (!password) {
                templatePasswordInput?.classList.add('is-invalid');
                return;
            }

            templatePasswordInput?.classList.remove('is-invalid');

            // Set password in hidden field
            if (templatePasswordHidden) {
                templatePasswordHidden.value = password;
            }

            // Hide password modal
            if (bootstrapModalInstance) {
                bootstrapModalInstance.hide();
            }

            // Submit the form
            if (createTemplateForm) {
                createTemplateForm.submit();
            }
        });

        // Focus password input when modal is shown
        templatePasswordModal?.addEventListener('shown.bs.modal', function() {
            window.setTimeout(() => templatePasswordInput?.focus(), 120);
        });

        // Clear password input when modal is hidden
        templatePasswordModal?.addEventListener('hidden.bs.modal', function() {
            if (templatePasswordInput) {
                templatePasswordInput.value = '';
                templatePasswordInput.classList.remove('is-invalid');
            }
            if (templatePasswordModalError) {
                templatePasswordModalError.classList.add('d-none');
                templatePasswordModalError.textContent = '';
            }
        });

        // Clear error on password input
        templatePasswordInput?.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            if (templatePasswordModalError) {
                templatePasswordModalError.classList.add('d-none');
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
.section-scroll {
    padding: 0.5rem 0.75rem 1.25rem;
}

/* Ensure Bootstrap scrollable modals have an explicit max-height and allow
   inner vertical scrolling so long modal content remains accessible on
   smaller viewports or when many dynamic components are added. */
.modal-dialog-scrollable .modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.component-item {
    transition: all 0.2s ease;
}

.component-item.ms-4 {
    background: rgba(13, 110, 253, 0.02);
}

.component-item .add-subcomponent-btn {
    transition: all 0.2s ease;
}

.component-item .add-subcomponent-btn:hover {
    transform: translateY(-1px);
}

.subcomponents-container {
    padding-left: 0;
}

.subcomponents-container > .component-item:last-child {
    margin-bottom: 0 !important;
}

.bulk-layout-card {
    background: #ffffff;
    border-radius: 1.25rem;
    border: 1px solid rgba(25, 135, 84, 0.12);
    box-shadow: 0 14px 32px rgba(25, 135, 84, 0.08);
    padding: 1.5rem;
}

@media (max-width: 576px) {
    .bulk-layout-card {
        padding: 1.15rem;
        border-radius: 1rem;
    }
}

.bulk-course-scroll {
    background: #ffffff;
    border: 1px solid rgba(25, 135, 84, 0.12);
    box-shadow: 0 10px 26px rgba(25, 135, 84, 0.07);
}

.bulk-course-scroll {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding: 0.85rem 0.95rem 1.15rem;
    flex: 1 1 auto;
}

.formula-list {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding: 0.85rem 0.95rem;
    flex: 1 1 auto;
    background: #ffffff;
    border: 1px solid rgba(25, 135, 84, 0.12) !important;
    border-radius: 1rem;
    box-shadow: 0 8px 22px rgba(25, 135, 84, 0.08);
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

.course-formula-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.35rem;
}

.course-formula-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.course-formula-chip-course {
    background: rgba(25, 135, 84, 0.18);
    color: #0f5132;
    border: 1px solid rgba(25, 135, 84, 0.25);
}

.course-formula-chip-department {
    background: rgba(13, 110, 253, 0.12);
    color: #0a58ca;
    border: 1px solid rgba(13, 110, 253, 0.24);
}

.course-formula-chip-global {
    background: rgba(108, 117, 125, 0.12);
    color: #495057;
    border: 1px solid rgba(108, 117, 125, 0.2);
}

.course-formula-label {
    font-size: 0.75rem;
    font-weight: 600;
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

.subject-chip-card {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.45rem 0.6rem;
    border-radius: 0.85rem;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(25, 135, 84, 0.14);
    box-shadow: 0 6px 16px rgba(25, 135, 84, 0.05);
    min-width: 180px;
}

.subject-chip-card .subject-pill {
    width: 100%;
}

.subject-formula-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}

.subject-formula-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
}

.subject-formula-chip-subject {
    background: rgba(111, 66, 193, 0.18);
    color: #4b2a86;
    border: 1px solid rgba(111, 66, 193, 0.26);
}

.subject-formula-chip-course {
    background: rgba(25, 135, 84, 0.18);
    color: #0f5132;
    border: 1px solid rgba(25, 135, 84, 0.25);
}

.subject-formula-chip-department {
    background: rgba(13, 110, 253, 0.14);
    color: #0a58ca;
    border: 1px solid rgba(13, 110, 253, 0.24);
}

.subject-formula-chip-global {
    background: rgba(108, 117, 125, 0.14);
    color: #495057;
    border: 1px solid rgba(108, 117, 125, 0.2);
}

.subject-formula-label {
    font-size: 0.7rem;
    font-weight: 600;
}

.template-selection-grid {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding: 0.85rem 0.95rem;
    background: #ffffff;
    border: 1px solid rgba(25, 135, 84, 0.12) !important;
    border-radius: 1rem;
    box-shadow: 0 8px 22px rgba(25, 135, 84, 0.08);
}

.structure-template-card {
    display: block;
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 1rem;
    background: #ffffff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    cursor: pointer;
}

.structure-template-card:hover,
.structure-template-card:focus-within {
    border-color: rgba(25, 135, 84, 0.45);
    box-shadow: 0 14px 32px rgba(25, 135, 84, 0.12);
    transform: translateY(-2px);
}

.structure-template-card.is-selected {
    border-color: rgba(25, 135, 84, 0.6);
    box-shadow: 0 18px 34px rgba(25, 135, 84, 0.16);
    transform: translateY(-2px);
    background: rgba(25, 135, 84, 0.02);
}

.structure-template-card input[type="radio"] {
    cursor: pointer;
}

.formula-card {
    display: block;
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 1rem;
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

.structure-card .badge.bg-primary {
    font-size: 0.85rem;
    padding: 0.45rem 0.85rem;
}

.structure-card .badge.ps-3 {
    font-size: 0.8rem;
    border-left: 3px solid rgba(25, 135, 84, 0.3);
}

.structure-card .d-flex.flex-wrap {
    row-gap: 0.5rem;
}

.structure-template-wrapper {
    margin-top: 1.25rem;
    padding: 1.25rem;
    border-radius: 1.1rem;
    border: 1px dashed rgba(25, 135, 84, 0.25);
    background: rgba(25, 135, 84, 0.05);
    box-shadow: 0 12px 28px rgba(25, 135, 84, 0.08);
}

.structure-template-grid {
    display: grid;
    gap: 1.1rem;
}

@media (min-width: 768px) {
    .structure-template-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
}

@media (max-width: 768px) {
    .section-scroll {
        max-height: none;
        padding: 0.5rem 0 1rem;
    }
}

.structure-template-card {
    position: relative;
    background: #f8fff9;
    border: 1px solid rgba(25, 135, 84, 0.18);
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, opacity 0.2s ease;
}

.structure-template-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(25, 135, 84, 0.12);
    border-color: rgba(25, 135, 84, 0.45);
}

.structure-template-card.is-active {
    border-color: rgba(25, 135, 84, 0.6);
    box-shadow: 0 18px 40px rgba(25, 135, 84, 0.18);
}

.structure-template-card.is-loading {
    opacity: 0.6;
    pointer-events: none;
}

.structure-template-card .badge {
    font-weight: 500;
}

.structure-template-card .template-status-badge {
    align-self: flex-start;
}

.structure-template-card.is-active .template-status-badge {
    background: rgba(25, 135, 84, 0.18) !important;
    color: #0f5132 !important;
}

.structure-template-card .js-template-apply {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.structure-template-card .js-template-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(25, 135, 84, 0.18);
}

.structure-template-card .js-template-apply:disabled {
    transform: none;
    box-shadow: none;
    opacity: 0.75;
}

.structure-template-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
    font-weight: 600;
}

.structure-template-chip .bi {
    font-size: 0.8rem;
}

.template-feedback {
    font-size: 0.85rem;
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

.js-delete-formula {
    transition: all 0.2s ease;
}

.js-delete-formula:hover {
    transform: scale(1.05);
}

.formula-card .btn-outline-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(13, 110, 253, 0.2);
}

.formula-card .btn-outline-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(220, 53, 69, 0.2);
}

.formula-card.border-info {
    border-width: 2px;
    border-style: solid;
}

.formula-card.border-info:hover {
    border-color: rgba(13, 202, 240, 0.6);
    box-shadow: 0 16px 36px rgba(13, 202, 240, 0.15);
}

.badge.bg-info-subtle {
    background-color: rgba(13, 202, 240, 0.12) !important;
    color: #055160 !important;
}

</style>
@endpush
