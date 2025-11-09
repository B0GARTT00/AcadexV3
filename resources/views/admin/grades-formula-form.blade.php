@extends('layouts.app')

@section('content')
@php
    $isDefault = $context === 'default';
    $hasFormula = (bool) $formula;
    $activeFormula = $formula ?? $fallbackFormula ?? $defaultFormula;
    $baseScoreValue = old('base_score', optional($activeFormula)->base_score ?? optional($defaultFormula)->base_score ?? 0);
    $scaleMultiplierValue = old('scale_multiplier', optional($activeFormula)->scale_multiplier ?? optional($defaultFormula)->scale_multiplier ?? 0);
    $passingGradeValue = old('passing_grade', optional($activeFormula)->passing_grade ?? optional($defaultFormula)->passing_grade ?? 0);
    $structurePayload = $structurePayload ?? [
        'type' => 'lecture_only',
        'structure' => \App\Support\Grades\FormulaStructure::toPercentPayload(\App\Support\Grades\FormulaStructure::default('lecture_only')),
    ];
    $structureCatalog = $structureCatalog ?? [];

    $requiresPasswordPrompt = $context === 'subject' && ($requiresPasswordConfirmation ?? false);
    $passwordErrorMessage = $requiresPasswordPrompt ? ($errors->first('current_password') ?? '') : '';

    $subjectStructureContext = 'this subject';
    if (($requiresPasswordPrompt || $context === 'subject') && isset($subject)) {
        $subjectCode = trim($subject->subject_code ?? '');
        $subjectDescription = trim($subject->subject_description ?? '');

        if ($subjectCode !== '' && $subjectDescription !== '') {
            $subjectStructureContext = $subjectCode . ' - ' . $subjectDescription;
        } elseif ($subjectCode !== '') {
            $subjectStructureContext = $subjectCode;
        } elseif ($subjectDescription !== '') {
            $subjectStructureContext = $subjectDescription;
        }
    }

    $labelSuggestion = $defaultFormula->label ?? 'Grades Formula';
    if ($context === "department" && isset($department)) {
        $labelSuggestion = trim(($department->department_description ?? 'Department') . ' Formula');
    } elseif ($context === 'course' && isset($course)) {
        $courseLabel = trim(($course->course_code ? $course->course_code . ' - ' : '') . ($course->course_description ?? 'Course'));
        $labelSuggestion = $courseLabel ? $courseLabel . ' Formula' : $labelSuggestion;
    } elseif ($context === 'subject' && isset($subject)) {
        $subjectLabel = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? 'Subject'));
        $labelSuggestion = $subjectLabel ? $subjectLabel . ' Formula' : $labelSuggestion;
    }
    $labelValue = old('label', $formula->label ?? $labelSuggestion);

    $queryParams = array_filter([
        'academic_year' => $selectedAcademicYear ?? null,
        'academic_period_id' => $selectedAcademicPeriodId ?? null,
        'semester' => $semester ?? null,
    ], function ($value) {
        return $value !== null && $value !== '';
    });

    $buildRoute = function (string $name, array $parameters = []) use ($queryParams) {
        $url = route($name, $parameters);

        if (empty($queryParams)) {
            return $url;
        }

        return $url . '?' . http_build_query($queryParams);
    };

    $backRoute = $buildRoute('admin.gradesFormula');
    $backLabel = 'Back to Wildcards';
    if ($context === 'department' && isset($department)) {
        $backRoute = $buildRoute('admin.gradesFormula.department', ['department' => $department->id]);
        $backLabel = 'Back to Department';
    } elseif ($context === 'course' && isset($department, $course)) {
        $backRoute = $buildRoute('admin.gradesFormula.course', ['department' => $department->id, 'course' => $course->id]);
        $backLabel = 'Back to Course';
    } elseif ($context === 'subject' && isset($subject)) {
        $backRoute = $buildRoute('admin.gradesFormula.subject', ['subject' => $subject->id]);
        $backLabel = 'Back to Subject';
    }

    $pageTitle = 'Grades Formula';
    if ($context === 'default') {
        $pageTitle = 'System Default Formula';
    } elseif ($context === 'department' && isset($department)) {
        $pageTitle = trim(($department->department_code ? $department->department_code . ' - ' : '') . ($department->department_description ?? 'Department'));
    } elseif ($context === 'course' && isset($course)) {
        $pageTitle = trim(($course->course_code ? $course->course_code . ' - ' : '') . ($course->course_description ?? 'Course'));
    } elseif ($context === 'subject' && isset($subject)) {
        $pageTitle = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? 'Subject'));
    }

    $pageSubtitle = null;
    if ($context === 'default') {
        $pageSubtitle = 'Baseline scaling applied when no specific formula exists.';
    } elseif ($context === 'department') {
        $pageSubtitle = $hasFormula
            ? 'Update the custom department formula to reflect current activities.'
            : 'Create a department formula to replace the system default.';
    } elseif ($context === 'course') {
        $pageSubtitle = $hasFormula
            ? 'Update this course formula to fine-tune department guidance.'
            : 'Create a course formula to tailor grading for this program.';
    } elseif ($context === 'subject') {
        $pageSubtitle = $hasFormula
            ? 'Update the subject formula to capture unique assessment weighting.'
            : 'Create a subject formula to replace course settings.';
    }

    if ($hasFormula) {
        $submitLabel = 'Save Changes';
    } elseif ($context === 'department') {
        $submitLabel = 'Create Department Formula';
    } elseif ($context === 'course') {
        $submitLabel = 'Create Course Formula';
    } elseif ($context === 'subject') {
        $submitLabel = 'Create Subject Formula';
    } else {
        $submitLabel = 'Save Formula';
    }

    $formRouteName = $hasFormula ? 'admin.gradesFormula.update' : 'admin.gradesFormula.store';
    $formRouteParameters = $hasFormula && isset($formula)
        ? ['formula' => $formula->id]
        : [];
    $formAction = $buildRoute($formRouteName, $formRouteParameters);
@endphp

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
                    <li class="breadcrumb-item">
                        <a href="{{ $buildRoute('admin.gradesFormula') }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                            <i class="bi bi-sliders me-1"></i>Grades Formula
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        {{ $pageTitle }}
                    </li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-sliders text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">{{ $pageTitle }}</h4>
                        @if ($pageSubtitle)
                            <small class="text-muted">{{ $pageSubtitle }}</small>
                        @endif
                    </div>
                </div>
                <a href="{{ $backRoute }}" class="btn btn-outline-success btn-sm rounded-pill shadow-sm" style="font-weight: 600;">
                    <i class="bi bi-arrow-left me-1"></i>{{ $backLabel }}
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

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div>
                    <strong class="d-block mb-2">We spotted a few issues:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-semibold" style="color: #198754;">
                    {{ $isDefault ? 'Default Weights Snapshot' : 'Current Weighting' }}
                </h5>
                <small class="text-muted">
                    {{ $isDefault ? 'These values power all departments without a dedicated formula.' : 'Tailor the distribution to match this scope\'s learning activities.' }}
                </small>
            </div>
            @if (data_get($activeFormula, 'weight_map'))
                <div class="badge bg-light text-dark fw-semibold">
                    @foreach (data_get($activeFormula, 'weight_map', []) as $type => $weight)
                        <span class="me-2">{{ strtoupper($type) }} {{ number_format($weight * 100, 0) }}%</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="card-body p-4">
            @if ($isDefault)
                <form
                    method="POST"
                    action="{{ $buildRoute('admin.gradesFormula.update', ['formula' => $defaultFormula->id]) }}"
                    x-data="structuredFormulaEditor({ initial: @js($structurePayload), catalog: @js($structureCatalog) })"
                    @submit="handleSubmit"
                    class="row g-4 js-validated-form"
                    novalidate
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_context" value="default">
                    <input type="hidden" name="label" value="{{ $defaultFormula->label }}">
                    <input type="hidden" name="semester" value="{{ $semester ?? '' }}">
                    @if (isset($selectedAcademicYear))
                        <input type="hidden" name="academic_year" value="{{ $selectedAcademicYear }}">
                    @endif
                    @if (isset($selectedAcademicPeriodId))
                        <input type="hidden" name="academic_period_id" value="{{ $selectedAcademicPeriodId }}">
                    @endif

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Base Score</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">+</span>
                            <input type="number" step="0.01" min="0" max="100" name="base_score" class="form-control" value="{{ $baseScoreValue }}" required>
                        </div>
                        <small class="text-muted">Minimum value after scaling (commonly 50 for a 50-100 range).</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Scale Multiplier</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">&times;</span>
                            <input type="number" step="0.01" min="0" max="100" name="scale_multiplier" class="form-control" value="{{ $scaleMultiplierValue }}" required>
                        </div>
                        <small class="text-muted">Base score + scale multiplier should equal 100 for consistency.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Passing Grade</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-mortarboard"></i></span>
                            <input type="number" step="0.01" min="0" max="100" name="passing_grade" class="form-control" value="{{ $passingGradeValue }}" required>
                        </div>
                        <small class="text-muted">Used to label final grades as Passed or Failed.</small>
                    </div>

                    @include('admin.partials.formula-structure-editor')

                    <div class="col-12">
                        <div class="alert alert-info mb-2">
                            <strong>Formula:</strong> <code>(score / items) * scale multiplier + base score</code> &middot;
                            <strong>Passing mark:</strong> {{ $passingGradeValue }} &middot;
                            <strong>Layout:</strong> <span x-text="catalog[structureType]?.label ?? 'Custom'"></span>
                        </div>
                        <div class="alert alert-danger py-2 mb-0 validation-error d-none">Please complete all required fields and ensure each component group totals 100%.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-lg" :disabled="! formIsValid()">Save Default Formula</button>
                    </div>
                </form>
            @else
                <form
                    method="POST"
                    action="{{ $formAction }}"
                    x-data="structuredFormulaEditor({ initial: @js($structurePayload), catalog: @js($structureCatalog) })"
                    @submit="handleSubmit"
                    class="row g-4 js-validated-form"
                    id="gradesFormulaEditorForm"
                    data-requires-password="{{ $requiresPasswordPrompt ? '1' : '0' }}"
                    data-password-error="{{ $passwordErrorMessage ? '1' : '0' }}"
                    data-password-error-message="{{ $passwordErrorMessage ? e($passwordErrorMessage) : '' }}"
                    novalidate
                >
                    @csrf
                    @if ($hasFormula)
                        @method('PUT')
                    @endif

                    <input type="hidden" name="form_context" value="{{ $context }}">
                    <input type="hidden" name="scope_level" value="{{ $context }}">

                    @if ($context === 'department' && isset($department))
                        <input type="hidden" name="department_id" value="{{ $department->id }}">
                    @elseif ($context === 'course' && isset($department, $course))
                        <input type="hidden" name="department_id" value="{{ $department->id }}">
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                    @elseif ($context === 'subject' && isset($subject))
                        @if (isset($department))
                            <input type="hidden" name="department_id" value="{{ $department->id }}">
                        @endif
                        @if (isset($course))
                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                        @endif
                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                    @endif

                    @if ($requiresPasswordPrompt)
                        <input type="hidden" name="current_password" id="formulaCurrentPasswordField">
                    @endif

                    <input type="hidden" name="semester" value="{{ $semester ?? '' }}">
                    @if (isset($selectedAcademicYear))
                        <input type="hidden" name="academic_year" value="{{ $selectedAcademicYear }}">
                    @endif
                    @if (isset($selectedAcademicPeriodId))
                        <input type="hidden" name="academic_period_id" value="{{ $selectedAcademicPeriodId }}">
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Display Label</label>
                        <input type="text" name="label" class="form-control" value="{{ $labelValue }}" placeholder="Enter a friendly formula name" {{ $hasFormula ? '' : 'required' }}>
                        <small class="text-muted">This appears on reports and dashboards.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Base Score</label>
                        <div class="input-group">
                            <span class="input-group-text">+</span>
                            <input type="number" step="0.01" min="0" max="100" name="base_score" class="form-control" value="{{ $baseScoreValue }}" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Scale Multiplier</label>
                        <div class="input-group">
                            <span class="input-group-text">&times;</span>
                            <input type="number" step="0.01" min="0" max="100" name="scale_multiplier" class="form-control" value="{{ $scaleMultiplierValue }}" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Passing Grade</label>
                        <input type="number" step="0.01" min="0" max="100" name="passing_grade" class="form-control" value="{{ $passingGradeValue }}" required>
                    </div>

                    @include('admin.partials.formula-structure-editor')

                    <div class="col-12">
                        <div class="alert alert-{{ $hasFormula ? 'info' : 'secondary' }} mb-2">
                            <strong>{{ $hasFormula ? 'Reminder:' : 'Tip:' }}</strong>
                            Base score + scale multiplier should total 100 to preserve the grading scale.
                        </div>
                        <div class="alert alert-danger py-2 mb-0 validation-error d-none">Please complete all required fields and ensure each component group totals 100%.</div>
                    </div>

                    @if ($requiresPasswordPrompt)
                        <div class="col-12">
                            <div class="alert alert-warning border-0 shadow-sm-sm d-flex align-items-center gap-2 mb-0">
                                <i class="bi bi-lock-fill"></i>
                                <span>This subject already has recorded grades. Confirm your password before saving changes.</span>
                            </div>
                        </div>
                    @endif

                    @if ($passwordErrorMessage)
                        <div class="col-12">
                            <div class="alert alert-danger border-0 shadow-sm-sm d-flex align-items-center gap-2 mb-0">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>{{ $passwordErrorMessage }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-lg" :disabled="! formIsValid()">
                            {{ $submitLabel }}
                        </button>
                    </div>
                </form>

                @if ($requiresPasswordPrompt)
                    <div class="modal fade" id="formulaPasswordModal" tabindex="-1" aria-labelledby="formulaPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-sm">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="formulaPasswordModalLabel">
                                        <i class="bi bi-lock-fill me-2"></i>Confirm Sensitive Change
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted mb-3">This subject already has recorded grades. Enter your password to continue updating its grading formula.</p>
                                    <div class="alert alert-warning border-0 shadow-sm-sm mb-3" style="white-space: pre-wrap; font-size: 0.9rem;">
Choose Department Formula
Department formulas replace the old department baselines. Pick one to baseline {{ $subjectStructureContext }} and refine a subject-specific override afterward.

Custom subject formula active
Applying a structure template will replace the current override.
This subject already has a custom formula. Applying a structure template will replace the current subject override.
                                    </div>
                                    <div class="mb-3">
                                        <label for="formulaPasswordInput" class="form-label fw-semibold">Account Password</label>
                                        <input type="password" class="form-control" id="formulaPasswordInput" placeholder="Enter your password" autocomplete="current-password">
                                        <div class="invalid-feedback" id="formulaPasswordInlineError"></div>
                                    </div>
                                    <div class="alert alert-danger d-none" id="formulaPasswordServerError"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-success" id="confirmFormulaPasswordBtn">Confirm &amp; Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('structuredFormulaEditor', ({ initial, catalog }) => ({
            structureType: initial?.type ?? 'lecture_only',
            catalog: catalog ?? {},
            structure: null,
            init() {
                this.catalog = this.catalog ?? {};
                this.loadStructure(initial);
            },
            loadStructure(payload) {
                const template = payload?.structure
                    ?? (this.catalog[this.structureType]?.structure ?? { key: 'period_grade', label: 'Period Grade', type: 'composite', children: [] });

                this.structure = this.decorateNode(this.cloneStructure(template), true, 100);
                this.syncTotals();
            },
            switchStructure() {
                this.loadStructure({ type: this.structureType, structure: this.catalog[this.structureType]?.structure });
            },
            decorateNode(node, isRoot = false, parentOverall = 100) {
                node = node ?? {};
                node.type = node.type ?? ((node.children ?? []).length ? 'composite' : 'activity');
                node.label = node.label ?? this.titleCase(node.key ?? 'component');
                node.weight_percent = isRoot
                    ? 100
                    : Number(node.weight_percent ?? (node.weight ?? 0) * 100);
                node.max_assessments = node.max_assessments ?? null;
                node.uid = this.generateUid();
                node.overall_percent = isRoot
                    ? 100
                    : Number(((parentOverall ?? 0) * (node.weight_percent ?? 0)) / 100);
                node.children = (node.children ?? []).map(child => this.decorateNode(child, false, node.overall_percent));
                return node;
            },
            orderedNodes() {
                const items = [];
                if (!this.structure) {
                    return items;
                }
                let index = 0;
                const walk = (parent, depth = 0) => {
                    (parent.children ?? []).forEach(child => {
                        items.push({ ref: child, parent, depth, index: index++ });
                        walk(child, depth + 1);
                    });
                };
                walk(this.structure, 0);
                return items;
            },
            syncWeight(node) {
                const numeric = Number(node.weight_percent);
                node.weight_percent = Number.isFinite(numeric) ? Math.max(0, Math.min(100, numeric)) : 0;
                this.syncTotals();
            },
            syncTotals() {
                this.collectCompositeNodes().forEach(composite => {
                    composite.total_percent = (composite.children ?? []).reduce(
                        (sum, child) => sum + Number(child.weight_percent ?? 0),
                        0
                    );
                });
                this.recalculateOverall();
            },
            collectCompositeNodes() {
                const nodes = [];
                const walk = (item) => {
                    if (item && (item.children ?? []).length) {
                        nodes.push(item);
                        item.children.forEach(walk);
                    }
                };
                if (this.structure) {
                    walk(this.structure);
                }
                return nodes;
            },
            compositeWarning(node) {
                return Math.abs(Number(node.total_percent ?? 0) - 100) > 0.1;
            },
            structureIsBalanced() {
                return this.collectCompositeNodes().every(node => !this.compositeWarning(node));
            },
            formIsComplete() {
                const required = this.$el.querySelectorAll('[required]');
                return Array.from(required).every(field => {
                    if (field.type === 'number') {
                        return field.value !== '' && Number.isFinite(parseFloat(field.value));
                    }
                    return field.value.trim() !== '';
                });
            },
            formIsValid() {
                return this.formIsComplete() && this.structureIsBalanced();
            },
            handleSubmit(event) {
                const banner = this.$el.querySelector('.validation-error');
                if (! this.formIsValid()) {
                    event.preventDefault();
                    if (banner) {
                        banner.classList.remove('d-none');
                        banner.textContent = 'Please complete all required fields and ensure each component group totals 100%.';
                    }
                } else if (banner) {
                    banner.classList.add('d-none');
                }
            },
            serializeStructure() {
                const clone = this.cloneStructure(this.structure);
                this.stripRuntimeFields(clone, true);
                return JSON.stringify(clone);
            },
            stripRuntimeFields(node, isRoot = false) {
                if (! node) {
                    return;
                }
                delete node.uid;
                delete node.total_percent;
                delete node.overall_percent;
                if (! isRoot) {
                    node.weight_percent = Number(node.weight_percent ?? 0);
                    delete node.weight;
                }
                if (node.children) {
                    node.children = node.children.map(child => {
                        this.stripRuntimeFields(child, false);
                        return child;
                    });
                }
            },
            cloneStructure(value) {
                return JSON.parse(JSON.stringify(value ?? {}));
            },
            generateUid() {
                return window.crypto?.randomUUID?.() ?? Math.random().toString(36).slice(2);
            },
            isComposite(node) {
                return (node.type ?? '') === 'composite';
            },
            formatPercent(value) {
                return `${Number(value ?? 0).toFixed(1)}%`;
            },
            displayMaxAssessments(node) {
                return node.max_assessments ? node.max_assessments : 'Flexible';
            },
            titleCase(value) {
                return (value ?? '')
                    .toString()
                    .replace(/[._]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .replace(/\b\w/g, match => match.toUpperCase());
            },
            recalculateOverall() {
                const assign = (node, parentOverall = 100) => {
                    if (! node) {
                        return;
                    }
                    node.overall_percent = parentOverall;
                    (node.children ?? []).forEach(child => {
                        const childRelative = Number(child.weight_percent ?? 0);
                        const childOverall = (parentOverall * childRelative) / 100;
                        assign(child, childOverall);
                    });
                };
                if (this.structure) {
                    assign(this.structure, 100);
                }
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('gradesFormulaEditorForm');
        if (!form || form.dataset.requiresPassword !== '1') {
            return;
        }

        const hiddenField = document.getElementById('formulaCurrentPasswordField');
        const modalElement = document.getElementById('formulaPasswordModal');
        const confirmBtn = document.getElementById('confirmFormulaPasswordBtn');
        const modalCtor = window.bootstrap && typeof window.bootstrap.Modal === 'function' ? window.bootstrap.Modal : null;

        if (!hiddenField) {
            return;
        }

        if (!modalElement || !confirmBtn || !modalCtor) {
            form.addEventListener('submit', (event) => {
                if (form.dataset.passwordBypass === '1' || event.defaultPrevented) {
                    return;
                }

                event.preventDefault();
                const response = window.prompt('Enter your password to confirm this change:');
                if (!response || !response.trim()) {
                    return;
                }

                hiddenField.value = response.trim();
                form.dataset.passwordError = '0';
                form.dataset.passwordErrorMessage = '';
                form.dataset.passwordBypass = '1';
                form.requestSubmit();
                setTimeout(() => {
                    delete form.dataset.passwordBypass;
                    hiddenField.value = '';
                }, 0);
            });
            return;
        }

        const passwordInput = document.getElementById('formulaPasswordInput');
        const inlineError = document.getElementById('formulaPasswordInlineError');
        const serverError = document.getElementById('formulaPasswordServerError');
        const modal = modalCtor.getOrCreateInstance(modalElement);

        const resetInlineError = () => {
            if (inlineError) {
                inlineError.textContent = '';
                inlineError.classList.remove('d-block');
            }
            if (passwordInput) {
                passwordInput.classList.remove('is-invalid');
            }
        };

        const showInlineError = (message) => {
            if (!inlineError) {
                return;
            }
            inlineError.textContent = message;
            inlineError.classList.add('d-block');
            if (passwordInput) {
                passwordInput.classList.add('is-invalid');
            }
        };

        const setServerError = (message) => {
            if (!serverError) {
                return;
            }
            if (message) {
                serverError.textContent = message;
                serverError.classList.remove('d-none');
            } else {
                serverError.textContent = '';
                serverError.classList.add('d-none');
            }
        };

        const getServerErrorMessage = () => form.dataset.passwordErrorMessage || '';

        setServerError(getServerErrorMessage());

        const openModal = () => {
            resetInlineError();
            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.focus();
            }
            setServerError(getServerErrorMessage());
            modal.show();
        };

        form.addEventListener('submit', (event) => {
            if (form.dataset.passwordBypass === '1') {
                return;
            }

            if (event.defaultPrevented) {
                return;
            }

            event.preventDefault();
            openModal();
        });

        confirmBtn.addEventListener('click', () => {
            const password = passwordInput ? passwordInput.value.trim() : '';
            resetInlineError();

            if (!password) {
                showInlineError('Password is required.');
                if (passwordInput) {
                    passwordInput.focus();
                }
                return;
            }

            hiddenField.value = password;
            form.dataset.passwordError = '0';
            form.dataset.passwordErrorMessage = '';
            setServerError('');
            form.dataset.passwordBypass = '1';
            modal.hide();

            setTimeout(() => {
                form.requestSubmit();
                setTimeout(() => {
                    delete form.dataset.passwordBypass;
                    hiddenField.value = '';
                }, 0);
            }, 150);
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            if (form.dataset.passwordBypass === '1') {
                return;
            }

            if (hiddenField) {
                hiddenField.value = '';
            }
            resetInlineError();
        });

        if (form.dataset.passwordError === '1' && getServerErrorMessage()) {
            setTimeout(() => {
                modal.show();
                if (passwordInput) {
                    passwordInput.focus();
                }
            }, 200);
        }
    });
</script>
@endpush

@push('styles')
<style>
.shadow-sm-sm {
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}

.weight-editor-card {
    border: 1px dashed rgba(25, 135, 84, 0.35);
}
</style>
@endpush
