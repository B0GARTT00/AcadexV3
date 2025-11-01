@extends('layouts.app')

@section('content')
@php
    $queryParams = array_filter([
        'academic_year' => $selectedAcademicYear,
        'academic_period_id' => $selectedAcademicPeriodId,
        'semester' => $semester,
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
                    @if($department)
                        <li class="breadcrumb-item">
                            <a href="{{ $buildRoute('admin.gradesFormula.department', ['department' => $department->id]) }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                                {{ $department->department_code }} Department
                            </a>
                        </li>
                    @endif
                    @if($course)
                        <li class="breadcrumb-item">
                            <a href="{{ $buildRoute('admin.gradesFormula.course', ['department' => $department->id, 'course' => $course->id]) }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                                {{ $course->course_code }} Course
                            </a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        {{ $subject->subject_code }} Subject
                    </li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-journal-text text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">
                            {{ $subject->subject_code }} · {{ $subject->subject_description }}
                        </h4>
                        <small class="text-muted">
                            Inspect formulas across all levels before editing this subject's grading scale.
                        </small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $buildRoute('admin.gradesFormula.edit.subject', ['subject' => $subject->id]) }}" class="btn btn-success btn-sm rounded-pill shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i>{{ $subjectFormula ? 'Edit Subject Formula' : 'Create Subject Formula' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="{{ route('admin.gradesFormula.subject', ['subject' => $subject->id]) }}" class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex flex-column">
                <label class="text-success small mb-1">Academic Year</label>
                <select name="academic_year" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="" {{ $selectedAcademicYear ? '' : 'selected' }}>All Years</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" {{ $selectedAcademicYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex flex-column">
                <label class="text-success small mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 150px;">
                    <option value="" {{ $semester ? '' : 'selected' }}>All/Default</option>
                    @foreach($availableSemesters as $availableSemester)
                        <option value="{{ $availableSemester }}" {{ $semester === $availableSemester ? 'selected' : '' }}>{{ $availableSemester }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success shadow-sm">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if ($errors->has('department_formula_id'))
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first('department_formula_id') }}
        </div>
    @endif

    @php
        $subjectName = trim(($subject->subject_code ? $subject->subject_code . ' - ' : '') . ($subject->subject_description ?? ''));
        if ($subjectName === '') {
            $subjectName = 'this subject';
        }

        $courseName = $course
            ? trim(($course->course_code ? $course->course_code . ' - ' : '') . ($course->course_description ?? ''))
            : '';
        if ($courseName === '') {
            $courseName = 'this course';
        }

        $departmentName = $department
            ? trim(($department->department_code ? $department->department_code . ' - ' : '') . ($department->department_description ?? ''))
            : '';
        if ($departmentName === '') {
            $departmentName = 'this department';
        }

        $activeLabel = $activeMeta['label'] ?? ($subjectFormula?->label ?? 'ASBME Default');
        $activeScopeLabel = match ($activeScope ?? 'default') {
            'subject' => 'Subject Custom Formula',
            'course' => 'Inherits Course Formula',
            'department' => 'Inherits Department Formula',
            default => 'System Default Formula',
        };

        $activeWeights = collect($activeMeta['relative_weights'] ?? $activeMeta['weights'] ?? [])
            ->map(fn ($weight, $type) => [
                'type' => strtoupper($type),
                'percent' => number_format($weight, 0),
            ])
            ->values();

        $manageHeadline = $subjectFormula ? 'Fine-tune this subject’s grading scale.' : 'Give this subject its own grading scale.';
        $manageCopy = $subjectFormula
            ? 'Adjust weights, base score, and passing mark to reflect unique assessment plans for this subject.'
            : 'Start with department or course guidance, then tailor the weights and scaling just for this subject.';
        $manageCta = $subjectFormula ? 'Edit Subject Formula' : 'Create Subject Formula';
        $hasSubjectFormula = (bool) $subjectFormula;
    @endphp

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-3 align-items-start">
                        <div>
                            <h5 class="text-success fw-semibold mb-1">Current Formula</h5>
                            <p class="text-muted mb-0">{{ $activeScopeLabel }} powering {{ $subjectName }}.</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-success fw-semibold">{{ $activeLabel }}</span>
                            <div class="small text-muted mt-1">Base {{ number_format($activeMeta['base_score'] ?? $subjectFormula?->base_score ?? 0, 0) }} · Scale ×{{ number_format($activeMeta['scale_multiplier'] ?? $subjectFormula?->scale_multiplier ?? 0, 0) }} · Passing {{ number_format($activeMeta['passing_grade'] ?? $subjectFormula?->passing_grade ?? 0, 0) }}</div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($activeWeights as $weight)
                            <span class="badge bg-success-subtle text-success px-3 py-2">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                        @endforeach
                        @if ($activeWeights->isEmpty())
                            <span class="text-muted small">No activity weights defined.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="text-success fw-semibold mb-1">Choose Department Formula</h5>
                    <p class="text-muted mb-0">Apply one of {{ $departmentName }}’s catalog formulas to {{ $subjectName }}. You can still fine-tune a subject-specific override afterward.</p>
                </div>
                <div class="card-body">
                    <form
                        id="subject-formula-apply-form"
                        method="POST"
                        action="{{ $buildRoute('admin.gradesFormula.subject.apply', ['subject' => $subject->id]) }}"
                        data-has-subject-formula="{{ $hasSubjectFormula ? '1' : '0' }}"
                        data-subject-name="{{ $subjectName }}"
                    >
                        @csrf
                        @if ($departmentFormulas->isEmpty())
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-info-circle me-2"></i>No department formulas found yet. Create one in the department catalog to proceed.
                            </div>
                        @else
                            @if ($hasSubjectFormula)
                                <div class="alert alert-warning d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                                    <div>
                                        This subject already has a custom formula. Applying a department formula will replace the current subject override.
                                    </div>
                                </div>
                            @endif
                            <div class="row g-3">
                                @foreach ($departmentFormulas as $formula)
                                @php
                                    $inputId = 'department-formula-' . $formula->id;
                                    $weights = collect($formula->weight_map ?? [])
                                        ->map(fn ($weight, $type) => [
                                            'type' => strtoupper($type),
                                            'percent' => number_format($weight * 100, 0),
                                        ])
                                        ->values();
                                    $selectedFormulaId = old('department_formula_id', $matchingDepartmentFormulaId);
                                    $isSelected = (int) $selectedFormulaId === $formula->id;
                                @endphp
                                <div class="col-lg-6">
                                    <label class="w-100">
                                        <input
                                            type="radio"
                                            id="{{ $inputId }}"
                                            name="department_formula_id"
                                            value="{{ $formula->id }}"
                                            class="form-check-input formula-option-input"
                                            @checked($isSelected)
                                        >
                                        <div class="formula-option-card position-relative h-100 p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="fw-semibold text-success mb-1">{{ $formula->label }}</h6>
                                                    <p class="small text-muted mb-0">
                                                        Base {{ number_format($formula->base_score, 0) }} · Scale ×{{ number_format($formula->scale_multiplier, 0) }} · Passing {{ number_format($formula->passing_grade, 0) }}
                                                    </p>
                                                </div>
                                                @if ($formula->is_department_fallback)
                                                    <span class="badge bg-success-subtle text-success">Baseline</span>
                                                @else
                                                    <span class="badge bg-light text-secondary">Catalog</span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                @foreach ($weights as $weight)
                                                    <span class="badge bg-success-subtle text-success">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                                                @endforeach
                                            </div>
                                            @if ($isSelected)
                                                <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-success">Selected</span>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                            <small class="text-muted">Need unique weights? Create a subject formula after applying a baseline.</small>
                            <button type="submit" class="btn btn-success">Apply Formula</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h5 class="text-success fw-semibold mb-1">Add or Edit Subject Formula</h5>
                        <p class="text-muted mb-0">{{ $manageHeadline }} {{ $manageCopy }}</p>
                    </div>
                    <a href="{{ $buildRoute('admin.gradesFormula.edit.subject', ['subject' => $subject->id]) }}" class="btn btn-outline-success px-4">{{ $manageCta }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const applyForm = document.getElementById('subject-formula-apply-form');
        if (! applyForm) {
            return;
        }

        const hasSubjectFormula = applyForm.dataset.hasSubjectFormula === '1';
        if (! hasSubjectFormula) {
            return;
        }

        applyForm.addEventListener('submit', (event) => {
            const subjectName = applyForm.dataset.subjectName || 'this subject';
            const message = `This will replace the existing custom formula for ${subjectName}. Continue?`;
            if (! window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
.formula-option-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.formula-option-card {
    border: 1px solid #dee2e6;
    border-radius: 1rem;
    background: #ffffff;
    transition: all 0.2s ease-in-out;
    position: relative;
}

label:hover .formula-option-card {
    border-color: #20c997;
    box-shadow: 0 10px 25px rgba(33, 150, 83, 0.1);
}

.formula-option-input:checked + .formula-option-card {
    border-color: #198754;
    box-shadow: 0 12px 28px rgba(25, 135, 84, 0.15);
}

.formula-option-input:disabled + .formula-option-card {
    opacity: 0.6;
    border-style: dashed;
    box-shadow: none;
}
</style>
@endpush
