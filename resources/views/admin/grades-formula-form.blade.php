@extends('layouts.app')

@section('content')
@php
    $isDefault = $context === 'default';
    $hasFormula = (bool) $formula;
    $activeFormula = $formula ?? $fallbackFormula ?? $defaultFormula;
    $baseScoreValue = old('base_score', optional($activeFormula)->base_score ?? optional($defaultFormula)->base_score ?? 0);
    $scaleMultiplierValue = old('scale_multiplier', optional($activeFormula)->scale_multiplier ?? optional($defaultFormula)->scale_multiplier ?? 0);
    $passingGradeValue = old('passing_grade', optional($activeFormula)->passing_grade ?? optional($defaultFormula)->passing_grade ?? 0);
    $weightPayload = $weightPayload ?? [];

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
                    x-data="formulaEditor(@js($weightPayload))"
                    @submit="if (! formIsValid()) { $event.preventDefault(); }"
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

                    @include('admin.partials.formula-weight-editor')

                    <div class="col-12">
                        <div class="alert alert-info mb-2">
                            <strong>Formula:</strong> <code>(score / items) * scale multiplier + base score</code> &middot;
                            <strong>Passing mark:</strong> {{ $passingGradeValue }} &middot;
                            <strong>Total weight:</strong> <span x-text="weightTotal().toFixed(0) + '%'" class="fw-semibold"></span>
                        </div>
                        <div
                            class="alert alert-danger py-2 mb-0 weight-error"
                            style="display: none;"
                            x-show="weightTotal() > 100"
                            x-transition.opacity
                        >
                            Total activity weight must not exceed 100%.
                        </div>
                        <div class="alert alert-danger py-2 mb-0 validation-error d-none">Please complete all required fields and ensure total weight does not exceed 100%.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-lg" :disabled="! formIsValid()">Save Default Formula</button>
                    </div>
                </form>
            @else
                <form
                    method="POST"
                    action="{{ $formAction }}"
                    x-data="formulaEditor(@js($weightPayload))"
                    @submit="if (! formIsValid()) { $event.preventDefault(); }"
                    class="row g-4 js-validated-form"
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

                    @include('admin.partials.formula-weight-editor')

                    <div class="col-12">
                        <div class="alert alert-{{ $hasFormula ? 'info' : 'secondary' }} mb-2">
                            <strong>{{ $hasFormula ? 'Reminder:' : 'Tip:' }}</strong>
                            Base score + scale multiplier should total 100 to preserve the grading scale.
                        </div>
                        <div
                            class="alert alert-danger py-2 mb-0 weight-error"
                            style="display: none;"
                            x-show="weightTotal() > 100"
                            x-transition.opacity
                        >
                            Total activity weight must not exceed 100%.
                        </div>
                        <div class="alert alert-danger py-2 mb-0 validation-error d-none">Please complete all required fields and ensure total weight does not exceed 100%.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-lg" :disabled="! formIsValid()">
                            {{ $submitLabel }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formulaEditor', (initialWeights = []) => ({
            weights: initialWeights.length ? JSON.parse(JSON.stringify(initialWeights)) : [{ activity_type: '', weight: 0 }],
            addRow() {
                this.weights.push({ activity_type: '', weight: 0 });
            },
            removeRow(index) {
                if (this.weights.length > 1) {
                    this.weights.splice(index, 1);
                }
            },
            weightTotal() {
                return this.weights.reduce((sum, weight) => {
                    const numeric = parseFloat(weight.weight);
                    return sum + (Number.isFinite(numeric) ? numeric : 0);
                }, 0);
            },
            formIsComplete() {
                const requiredFields = this.$el.querySelectorAll('[required]');
                return Array.from(requiredFields).every((field) => {
                    if (field.type === 'number') {
                        return field.value !== '' && Number.isFinite(parseFloat(field.value));
                    }

                    return field.value.trim() !== '';
                });
            },
            hasValidTotal() {
                return this.weightTotal() <= 100;
            },
            formIsValid() {
                return this.formIsComplete() && this.hasValidTotal();
            }
        }));
    });
</script>
<script>
// Failsafe validator (vanilla JS) to ensure required fields are present and total weight <= 100
document.addEventListener('DOMContentLoaded', () => {
    // small debounce helper to prevent thrashing when many mutations/inputs fire
    function debounce(fn, wait) {
        let t = null;
        return function () {
            const args = arguments;
            clearTimeout(t);
            t = setTimeout(() => fn.apply(null, args), wait);
        };
    }

    function parseWeightInput(el) {
        const v = parseFloat(el.value);
        return Number.isFinite(v) ? v : 0;
    }

    function computeTotal(form) {
        // weight inputs are named like weights[<index>][weight]
        const weightInputs = form.querySelectorAll('input[name$="[weight]"]');
        let total = 0;
        weightInputs.forEach(i => total += parseWeightInput(i));
        return total;
    }

    function formComplete(form) {
        const required = Array.from(form.querySelectorAll('[required]'));
        return required.every(f => {
            if (f.type === 'number') return f.value !== '' && Number.isFinite(parseFloat(f.value));
            return f.value.trim() !== '';
        });
    }

    function updateFormState(form) {
        const total = computeTotal(form);
        const valid = formComplete(form) && total <= 100;

        // toggle error messages
        const weightErr = form.querySelector('.weight-error');
        if (weightErr) weightErr.style.display = total > 100 ? 'block' : 'none';

        const validationErr = form.querySelector('.validation-error');
        if (validationErr) validationErr.classList.toggle('d-none', valid);

        // disable/enable submit buttons inside this form
        const submits = form.querySelectorAll('button[type="submit"]');
        submits.forEach(btn => btn.disabled = !valid);

        return valid;
    }

    function attachForm(form) {
        if (form.__validatorAttached) return;
        form.__validatorAttached = true;

        // initial state
        updateFormState(form);

        // watch inputs and dynamic changes
        // debounce input/change handlers so rapid typing doesn't thrash
        const debouncedUpdater = debounce(() => updateFormState(form), 80);
        form.addEventListener('input', debouncedUpdater);
        form.addEventListener('change', debouncedUpdater);

        // observe DOM changes (rows added/removed). IMPORTANT: do NOT observe attributes
        // because updateFormState mutates element attributes (disabled/style) which can
        // re-trigger the observer and cause a feedback loop. We also debounce the callback.
        const mo = new MutationObserver(debounce(() => updateFormState(form), 100));
        mo.observe(form, { childList: true, subtree: true });

        // final guard on submit
        form.addEventListener('submit', (e) => {
            if (! updateFormState(form)) {
                e.preventDefault();
            }
        });
    }

    document.querySelectorAll('.js-validated-form').forEach(attachForm);
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
