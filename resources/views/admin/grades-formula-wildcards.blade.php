@extends('layouts.app')

@php
    $request = request();
    $queryParams = $request->query();

    $allowedSections = ['overview', 'formulas'];
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

    if ($shouldReopenTemplateModal || $shouldReopenCreateFormulaModal) {
        $initialSection = 'formulas';
    }

    $overviewActive = $initialSection === 'overview';
    $formulasActive = $initialSection === 'formulas';

    $departmentCount = $departmentsSummary->count();
    $overrideCount = $departmentsSummary->filter(fn ($summary) => ($summary['catalog_count'] ?? 0) > 0)->count();
    $defaultCount = max($departmentCount - $overrideCount, 0);

    $preservedQuery = \Illuminate\Support\Arr::only($queryParams, ['semester', 'academic_year', 'academic_period_id']);

    $buildRoute = function (string $name, array $parameters = []) use ($preservedQuery) {
        return route($name, array_merge($parameters, $preservedQuery));
    };

    $periodLookup = collect($academicPeriods ?? [])->keyBy('id');

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
@endphp

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-2">
        <div class="col">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none" style="color: #198754;">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
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
                        <a href="{{ $buildRoute('admin.gradesFormula.department', ['department' => $department->id]) }}" class="text-decoration-none text-reset">
                            <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" data-status="{{ $status }}" data-url="{{ $buildRoute('admin.gradesFormula.department', ['department' => $department->id]) }}" style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                            {{-- Top header --}}
                            <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                                <div class="wildcard-circle position-absolute start-50 translate-middle"
                                    style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                    <span class="text-white fw-bold">{{ $department->department_code }}</span>
                                </div>
                            </div>

                            {{-- Card body --}}
                            <div class="card-body pt-5 text-center">
                                <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $department->department_description }}">
                                    {{ $department->department_description }}
                                </h6>
                                <p class="text-muted small mb-3">{{ $summary['scope_text'] }}</p>

                                {{-- Footer badges --}}
                                <div class="d-flex flex-column gap-2 mt-4">
                                    @if($summary['missing_course_count'] > 0)
                                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill">
                                            ⚠️ {{ $summary['missing_course_count'] }} course{{ $summary['missing_course_count'] === 1 ? '' : 's' }} pending
                                        </span>
                                    @endif
                                    @if($summary['missing_subject_count'] > 0)
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                            ⚠️ {{ $summary['missing_subject_count'] }} subject{{ $summary['missing_subject_count'] === 1 ? '' : 's' }} pending
                                        </span>
                                    @endif
                                    <span class="badge px-3 py-2 fw-semibold rounded-pill {{ $summary['catalog_count'] > 0 ? 'bg-success' : 'bg-secondary' }}">
                                        @if($summary['catalog_count'] > 0)
                                            ✓ Department Baseline
                                        @else
                                            Department Baseline
                                        @endif
                                    </span>
                                </div>
                            </div>
                            </div>
                        </a>
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
                                                <a href="{{ route('admin.gradesFormula.structureTemplate.edit', array_merge(['template' => $template['id']], $preservedQuery)) }}" class="btn btn-outline-secondary">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                                </a>
                                                <button type="button" class="btn btn-outline-danger js-delete-structure-template" data-template-id="{{ $template['id'] }}" data-template-label="{{ $template['label'] }}">
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
                                                @if(isset($weight['max_items']) && $weight['max_items'] !== null)
                                                    · max {{ $weight['max_items'] }}
                                                @endif
                                            </span>
                                        @else
                                            {{-- Simple activity --}}
                                            <span class="badge bg-success-subtle text-success">
                                                {{ $weight['type'] }} {{ $weight['percent'] }}%
                                                @if(isset($weight['max_items']) && $weight['max_items'] !== null)
                                                    · max {{ $weight['max_items'] }}
                                                @endif
                                            </span>
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
                // REMOVED: $allDepartmentFormulas - department blueprints no longer exist
                $allDepartmentFormulas = collect([]);

                // Fetch global formulas from the controller data
                $globalFormulas = collect($globalFormulasList ?? [])->map(function ($formula) {
                    // Build hierarchical weight display matching structure template format
                    $weights = [];
                    if (is_array($formula->structure_config) && !empty($formula->structure_config)) {
                        $structure = $formula->structure_config;
                        
                        // Walk through the structure to build badges
                        $walkStructure = function($node, $parentWeight = 1.0) use (&$walkStructure, &$weights) {
                            $children = $node['children'] ?? [];
                            
                            foreach ($children as $child) {
                                $childType = $child['type'] ?? 'activity';
                                $childWeight = (float) ($child['weight'] ?? 0);
                                $childLabel = $child['label'] ?? \App\Support\Grades\FormulaStructure::formatLabel($child['key'] ?? '');
                                $relativePercent = (int) round($childWeight * 100);
                                
                                if ($childType === 'composite') {
                                    // This is a composite component (e.g., "Lecture Component 60%")
                                    $weights[] = [
                                        'type' => $childLabel,
                                        'percent' => $relativePercent,
                                        'is_composite' => true,
                                    ];
                                    
                                    // Recursively process children with indentation marker
                                    $walkStructure($child, $parentWeight * $childWeight);
                                } else {
                                    // This is an activity (e.g., "Lecture Quizzes 40%")
                                    $weights[] = [
                                        'type' => $childLabel,
                                        'percent' => $relativePercent,
                                        'is_sub' => isset($node['type']) && $node['type'] === 'composite' && $node['key'] !== 'period_grade',
                                    ];
                                }
                            }
                        };
                        
                        $walkStructure($structure);
                    }

                    return [
                        'id' => $formula->id,
                        'label' => $formula->label ?? 'Global Formula',
                        'is_fallback' => false,
                        'context_label' => $formula->academic_period_id 
                            ? ($formula->semester ? "{$formula->semester} Semester" : 'Period-specific')
                            : 'Applies to all periods',
                        'weights' => $weights,
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
                                                @if(!empty($weight['is_composite']))
                                                    {{-- Main composite component (e.g., Lecture Component 60%) --}}
                                                    <span class="badge bg-primary text-white fw-semibold">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                                @elseif(!empty($weight['is_sub']))
                                                    {{-- Sub-component under a composite (e.g., Lecture Quizzes 40%) --}}
                                                    <span class="badge bg-info-subtle text-info ps-3">
                                                        <i class="bi bi-arrow-return-right me-1"></i>{{ $weight['type'] }} {{ $weight['percent'] }}%
                                                    </span>
                                                @else
                                                    {{-- Simple activity (for lecture-only structure) --}}
                                                    <span class="badge bg-info-subtle text-info">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                                @endif
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

            {{-- Department Baseline Formulas Section --}}
            <div class="mb-4">
                <h4 class="mb-3 fw-semibold text-dark">
                    <i class="bi bi-building me-2"></i>Department Baseline Formulas
                </h4>
                <p class="text-muted small mb-3">Configure the default grading formula for each department</p>
            </div>

            <div class="row g-4 mb-4">
                @forelse($departments as $dept)
                    @php
                        // Check if this department has a baseline formula in departmentCatalogs
                        $deptFormulas = $departmentCatalogs->get($dept->id, collect());
                        $baselineFormula = $deptFormulas->first();
                        $hasCustomBaseline = $baselineFormula !== null;
                        $formulaLabel = $hasCustomBaseline ? ($baselineFormula->label ?? 'Unnamed Formula') : null;
                        $globalLabel = optional($globalFormula)->label ?? 'System Default';
                        
                        // Build hierarchical weight display for department baseline
                        $weights = [];
                        if ($hasCustomBaseline && $baselineFormula) {
                            if (is_array($baselineFormula->structure_config) && !empty($baselineFormula->structure_config)) {
                                $structure = $baselineFormula->structure_config;
                                
                                // Walk through the structure to build badges
                                $walkStructure = function($node, $parentWeight = 1.0) use (&$walkStructure, &$weights) {
                                    $children = $node['children'] ?? [];
                                    
                                    foreach ($children as $child) {
                                        $childType = $child['type'] ?? 'activity';
                                        $childWeight = (float) ($child['weight'] ?? 0);
                                        $childLabel = $child['label'] ?? \App\Support\Grades\FormulaStructure::formatLabel($child['key'] ?? '');
                                        $relativePercent = (int) round($childWeight * 100);
                                        
                                        if ($childType === 'composite') {
                                            // Composite component
                                            $weights[] = [
                                                'type' => $childLabel,
                                                'percent' => $relativePercent,
                                                'is_composite' => true,
                                            ];
                                            
                                            // Recursively process children
                                            $walkStructure($child, $parentWeight * $childWeight);
                                        } else {
                                            // Activity
                                            $weights[] = [
                                                'type' => $childLabel,
                                                'percent' => $relativePercent,
                                                'is_sub' => isset($node['type']) && $node['type'] === 'composite' && $node['key'] !== 'period_grade',
                                            ];
                                        }
                                    }
                                };
                                
                                $walkStructure($structure);
                            }
                        }
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="formula-card card h-100 border-0 shadow-lg rounded-4 {{ $hasCustomBaseline ? 'border-success' : '' }}" data-department-id="{{ $dept->id }}">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-success fw-semibold">{{ $dept->department_code }}</span>
                                            <h5 class="fw-semibold text-dark mb-0">{{ $dept->department_description }}</h5>
                                        </div>
                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-shield-check me-1"></i>Department Baseline
                                        </p>
                                        @if($hasCustomBaseline)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="bi bi-check-circle-fill me-1"></i>Custom: {{ $formulaLabel }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary">
                                                <i class="bi bi-globe2 me-1"></i>Using Global: {{ $globalLabel }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($hasCustomBaseline && !empty($weights))
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($weights as $weight)
                                            @if(!empty($weight['is_composite']))
                                                {{-- Main composite component --}}
                                                <span class="badge bg-success text-white fw-semibold">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                            @elseif(!empty($weight['is_sub']))
                                                {{-- Sub-component under a composite --}}
                                                <span class="badge bg-success-subtle text-success ps-3">
                                                    <i class="bi bi-arrow-return-right me-1"></i>{{ $weight['type'] }} {{ $weight['percent'] }}%
                                                </span>
                                            @else
                                                {{-- Simple activity --}}
                                                <span class="badge bg-success-subtle text-success">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-auto">
                                    <a href="{{ $buildRoute('admin.gradesFormula.edit.department', ['department' => $dept->id]) }}" 
                                       class="btn btn-sm btn-outline-success w-100">
                                        <i class="bi bi-pencil me-1"></i>Configure Baseline
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info shadow-sm mb-0">
                            <i class="bi bi-info-circle me-2"></i>No departments available to configure
                        </div>
                    </div>
                @endforelse
            </div>

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

        // REMOVED: Bulk apply form logic (Departments tab removed)

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

        function addComponent(type = '', weight = '', label = '', isMain = true, parentId = null, maxItems = '') {
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
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Activity Type</label>
                                <input type="text" class="form-control form-control-sm component-activity-type" name="components[${currentId}][activity_type]" value="${type}" placeholder="e.g., Quiz, Exam, OCR" required>
                                ${!isSubComponent ? `<input type="hidden" name="components[${currentId}][is_main]" value="1">` : `<input type="hidden" name="components[${currentId}][parent_id]" value="${parentId}">`}
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Weight (%)</label>
                                <input type="number" class="form-control form-control-sm component-weight" name="components[${currentId}][weight]" value="${weight}" min="0" max="100" step="0.1" required>
                                ${!isSubComponent ? '<small class="text-muted">Main component weight</small>' : '<small class="text-muted">Sub-component weight (relative to parent)</small>'}
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Max Components</label>
                                <input type="number" class="form-control form-control-sm component-max-items" name="components[${currentId}][max_items]" value="${maxItems}" min="1" max="5" step="1" placeholder="1-5">
                                <small class="text-muted">Limit: 1-5</small>
                            </div>
                            <div class="col-md-3">
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
                    null,
                    resolveComponentValue(component, 'max_items', '')
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
                    parentNewId,
                    resolveComponentValue(component, 'max_items', '')
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
    transform: scale(1.05);
    box-shadow: 0 20px 30px rgba(0,0,0,0.1);
}

.wildcard-card.is-pressed {
    transform: scale(0.99);
    box-shadow: 0 14px 28px rgba(0,0,0,0.08);
}

.wildcard-card:focus-visible {
    outline: 3px solid rgba(25, 135, 84, 0.45);
    outline-offset: 4px;
}

.wildcard-circle {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.wildcard-card:hover .wildcard-circle {
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    transform: translate(-50%, -55%) scale(1.05);
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

.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.15) !important;
    color: #664d03 !important;
}

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #842029 !important;
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
        width: 80px;
        height: 80px;
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

/* Ensure long badge/text content wraps inside cards instead of overflowing */
.card-body .badge,
.formula-card .badge,
.structure-card .badge,
.wildcard-card .badge-formula-label,
.card .badge-formula-label {
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
    display: inline-flex !important;
    align-items: center;
    max-width: 100%;
}

/* For pill-style badges that may otherwise extend the card width, limit their max width
   and allow them to break into multiple lines for small viewports. */
.badge-formula-label,
.badge.bg-success-subtle,
.badge.bg-light {
    max-width: 100%;
}

/* Hide any empty formula-label badges that render as blank pills at the
   bottom of wildcard cards while preserving non-empty labels like
   "Department Baseline". This avoids visual empty placeholders. */
.badge-formula-label:empty {
    display: none !important;
}

</style>
@endpush
