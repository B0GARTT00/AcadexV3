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

    @if ($errors->has('structure_type') || $errors->has('department_formula_id'))
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first('structure_type') ?? $errors->first('department_formula_id') }}
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
            ->map(function ($weight, $type) {
                $numeric = is_numeric($weight) ? (float) $weight : 0;
                $clamped = max(min($numeric, 100), 0);

                return [
                    'type' => strtoupper($type),
                    'percent' => $clamped,
                    'display' => number_format($clamped, 0),
                    'progress' => $clamped / 100,
                ];
            })
            ->values();

        $manageHeadline = $subjectFormula ? 'Fine-tune this subject’s grading scale.' : 'Give this subject its own grading scale.';
        $manageCopy = $subjectFormula
            ? 'Adjust weights, base score, and passing mark to reflect unique assessment plans for this subject.'
            : 'Start with department or course guidance, then tailor the weights and scaling just for this subject.';
        $manageCta = $subjectFormula ? 'Edit Subject Formula' : 'Create Subject Formula';
        $hasSubjectFormula = (bool) $subjectFormula;

        $structureOptions = collect($structureOptions ?? [])
            ->map(function ($option) {
                $key = $option['key'] ?? 'lecture_only';
                $label = $option['label'] ?? \Illuminate\Support\Str::of($key)->replace('_', ' ')->title()->toString();

                return [
                    'key' => $key,
                    'label' => $label,
                ];
            })
            ->values();
        $structureOptionCount = $structureOptions->count();
        $structureBlueprints = collect($structureBlueprints ?? []);
        $selectedStructureType = $selectedStructureType ?? 'lecture_only';
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
                            <span class="formula-weight-chip" style="--chip-progress: {{ number_format($weight['progress'], 2, '.', '') }};">
                                <span>{{ $weight['type'] }} {{ $weight['display'] }}%</span>
                            </span>
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
                    <h5 class="text-success fw-semibold mb-1">Choose Structure Type</h5>
                    <p class="text-muted mb-0">Structure types replace the old department formulas. Pick one to baseline {{ $subjectName }} and refine a subject-specific override afterward.</p>
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
                        @if ($structureBlueprints->isEmpty())
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-info-circle me-2"></i>No structure templates available yet. Configure templates before applying them to subjects.
                            </div>
                        @else
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasSubjectFormula)
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 shadow-sm-sm">
                                            <i class="bi bi-stars me-1"></i>Custom subject formula active
                                        </span>
                                        <span class="text-muted small">Applying a structure template will replace the current override.</span>
                                    @else
                                        <span class="badge bg-light text-success rounded-pill px-3 py-2 shadow-sm-sm">
                                            <i class="bi bi-brush me-1"></i>No subject override yet
                                        </span>
                                        <span class="text-muted small">Pick a structure template to jump-start this subject.</span>
                                    @endif
                                </div>
                                @if ($hasSubjectFormula)
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#removeSubjectFormulaModal">
                                        <i class="bi bi-trash me-1"></i>Remove Subject Formula
                                    </button>
                                @endif
                            </div>

                            @if ($hasSubjectFormula)
                                <div class="alert alert-warning d-flex align-items-start shadow-sm formula-alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                                    <div>
                                        This subject already has a custom formula. Applying a structure template will replace the current subject override.
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-success d-flex align-items-start shadow-sm formula-alert">
                                    <i class="bi bi-lightning-charge me-2 mt-1"></i>
                                    <div>
                                        Start with a structure template, then fine-tune a subject-specific formula to match unique assessments.
                                    </div>
                                </div>
                            @endif

                            @php
                                $selectedStructureKey = old('structure_type', $selectedStructureType);
                            @endphp

                            @if ($structureOptionCount > 0)
                                <div class="row g-3 align-items-end mb-3">
                                    <div class="col-sm-6 col-md-4 col-lg-3">
                                        <label for="department-structure-filter" class="form-label text-success fw-semibold small mb-1">Structure Type</label>
                                        <select class="form-select form-select-sm" id="department-structure-filter">
                                            <option value="all" selected>All Structures</option>
                                            @foreach ($structureOptions as $option)
                                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="row g-4 formula-option-grid">
                                @foreach ($structureBlueprints as $blueprint)
                                    @php
                                        $inputId = 'structure-type-' . $blueprint['key'];
                                        $weights = collect($blueprint['weights']);
                                        $isSelected = $selectedStructureKey === $blueprint['key'];
                                        $structureTypeKey = $blueprint['key'];
                                        $structureTypeLabel = $blueprint['label'];
                                        $structureTypeDescription = $blueprint['description'] ?? '';
                                    @endphp
                                    <div class="col-xl-4 col-lg-6 formula-card-column" data-structure-type="{{ $structureTypeKey }}">
                                        <label class="w-100 formula-option-wrapper">
                                            <input
                                                type="radio"
                                                id="{{ $inputId }}"
                                                name="structure_type"
                                                value="{{ $structureTypeKey }}"
                                                class="form-check-input formula-option-input"
                                                @checked($isSelected)
                                            >
                                            <div class="formula-option-card position-relative h-100 p-4">
                                                <div class="formula-card-glow" aria-hidden="true"></div>
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h6 class="fw-semibold text-success mb-1">{{ $structureTypeLabel }}</h6>
                                                        <p class="small text-muted mb-0">
                                                            Base {{ number_format($blueprint['base_score'], 0) }} · Scale ×{{ number_format($blueprint['scale_multiplier'], 0) }} · Passing {{ number_format($blueprint['passing_grade'], 0) }}
                                                        </p>
                                                    </div>
                                                    <div class="d-flex flex-column align-items-end gap-1 text-end">
                                                        <span class="badge bg-white text-success border border-success border-opacity-25 rounded-pill">{{ $structureTypeLabel }}</span>
                                                        @if (! empty($blueprint['is_baseline']))
                                                            <span class="badge bg-success-subtle text-success rounded-pill">Department Baseline</span>
                                                        @else
                                                            <span class="badge bg-light text-secondary rounded-pill">Structure Template</span>
                                                        @endif
                                                        <span class="small text-muted">Matches {{ $structureTypeLabel }} blueprint</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($weights as $weight)
                                                        <span class="formula-weight-chip" style="--chip-progress: {{ number_format($weight['progress'], 2, '.', '') }};">
                                                            <span>{{ $weight['type'] }} {{ $weight['display'] }}%</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                                @if ($structureTypeDescription)
                                                    <p class="text-muted small mt-3 mb-0">{{ $structureTypeDescription }}</p>
                                                @endif
                                                <div class="formula-card-footer small text-muted d-flex flex-wrap gap-3 mt-4">
                                                    <span><i class="bi bi-speedometer2 text-success me-1"></i>Base {{ number_format($blueprint['base_score'], 0) }}</span>
                                                    <span><i class="bi bi-diagram-3 text-success me-1"></i>Scale ×{{ number_format($blueprint['scale_multiplier'], 0) }}</span>
                                                    <span><i class="bi bi-mortarboard text-success me-1"></i>Passing {{ number_format($blueprint['passing_grade'], 0) }}</span>
                                                </div>
                                                <div class="formula-check" aria-hidden="true">
                                                    <i class="bi bi-check-lg"></i>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($structureBlueprints->isNotEmpty())
                            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                                <small class="text-muted">Need unique weights? Create a subject formula after applying a template.</small>
                                <button type="submit" class="btn btn-success btn-apply-formula" data-action="apply">Apply Structure</button>
                            </div>
                        @endif
                    </form>
                    @if ($hasSubjectFormula)
                        <div class="modal fade" id="removeSubjectFormulaModal" tabindex="-1" aria-labelledby="removeSubjectFormulaModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title text-success" id="removeSubjectFormulaModalLabel">Remove Subject Formula</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-0">Removing the custom formula will restore {{ $subjectName }} to {{ $departmentName }}’s baseline. You can always create a new subject formula afterward.</p>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <form method="POST" action="{{ $buildRoute('admin.gradesFormula.subject.remove', ['subject' => $subject->id]) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-trash me-1"></i>Remove Formula
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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

        const optionInputs = applyForm.querySelectorAll('.formula-option-input');
        const applyButton = applyForm.querySelector('[data-action="apply"]');
        const structureFilter = document.getElementById('department-structure-filter');
        const formulaColumns = applyForm.querySelectorAll('.formula-card-column');

        const syncSelectionState = () => {
            let hasSelection = false;

            optionInputs.forEach((input) => {
                const card = input.nextElementSibling;
                const selected = input.checked;

                if (card) {
                    card.classList.toggle('is-selected', selected);
                    if (selected) {
                        card.classList.add('pulse');
                    }
                }

                if (selected) {
                    hasSelection = true;
                }
            });

            if (applyButton) {
                applyButton.disabled = ! hasSelection;
            }
        };

        syncSelectionState();

        optionInputs.forEach((input) => {
            const card = input.nextElementSibling;

            input.addEventListener('change', syncSelectionState);

            if (card) {
                card.addEventListener('animationend', () => {
                    card.classList.remove('pulse');
                });
            }
        });

        const applyStructureFilter = () => {
            if (! structureFilter) {
                return;
            }

            const selectedType = structureFilter.value;

            formulaColumns.forEach((column) => {
                const matches = selectedType === 'all' || column.dataset.structureType === selectedType;
                column.classList.toggle('d-none', ! matches);

                if (! matches) {
                    const input = column.querySelector('.formula-option-input');
                    if (input && input.checked) {
                        input.checked = false;
                    }
                }
            });

            syncSelectionState();
        };

        if (structureFilter) {
            structureFilter.addEventListener('change', applyStructureFilter);
            applyStructureFilter();
        }

        const hasSubjectFormula = applyForm.dataset.hasSubjectFormula === '1';

        if (hasSubjectFormula) {
            applyForm.addEventListener('submit', (event) => {
                const subjectName = applyForm.dataset.subjectName || 'this subject';
                const message = `This will replace the existing custom formula for ${subjectName}. Continue?`;
                if (! window.confirm(message)) {
                    event.preventDefault();
                }
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
.formula-option-wrapper {
    display: block;
    position: relative;
}

.formula-option-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.formula-option-card {
    border: 1px solid rgba(25, 135, 84, 0.18);
    border-radius: 1.25rem;
    background: #ffffff;
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    position: relative;
    overflow: hidden;
}

.formula-option-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(25, 135, 84, 0.18);
}

.formula-option-card.is-selected {
    border-color: #198754;
    box-shadow: 0 22px 48px rgba(25, 135, 84, 0.22);
}

.formula-option-card .formula-card-glow {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(32, 201, 151, 0.12));
    opacity: 0;
    transform: scale(0.96);
    transition: opacity 0.35s ease, transform 0.35s ease;
    pointer-events: none;
}

.formula-option-card.is-selected .formula-card-glow {
    opacity: 1;
    transform: scale(1);
}

.formula-check {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754, #20c997);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transform: scale(0.4);
    opacity: 0;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.formula-option-card.is-selected .formula-check {
    opacity: 1;
    transform: scale(1);
}

.formula-option-card.pulse {
    animation: formulaPulse 0.6s ease;
}

@keyframes formulaPulse {
    0% {
        transform: translateY(-4px) scale(0.99);
    }
    40% {
        transform: translateY(-8px) scale(1.02);
    }
    100% {
        transform: translateY(-4px) scale(1);
    }
}

.formula-weight-chip {
    position: relative;
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    background: rgba(25, 135, 84, 0.08);
    font-size: 0.75rem;
    font-weight: 600;
    color: #0f5132;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.formula-weight-chip::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.35), rgba(32, 201, 151, 0.35));
    transform-origin: left center;
    transform: scaleX(var(--chip-progress, 0));
    transition: transform 0.4s ease;
    opacity: 0.6;
}

.formula-weight-chip span {
    position: relative;
    z-index: 1;
}

.formula-alert {
    border-radius: 1rem;
    border: none;
}

.shadow-sm-sm {
    box-shadow: 0 8px 20px rgba(25, 135, 84, 0.12);
}

.btn-apply-formula {
    border-radius: 999px;
    padding: 0.4rem 1.6rem;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.btn-apply-formula:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-apply-formula:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(25, 135, 84, 0.25);
}

@media (max-width: 576px) {
    .formula-option-card {
        border-radius: 1rem;
    }
}
</style>
@endpush
