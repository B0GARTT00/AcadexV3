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
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        {{ $department->department_code }} Department
                    </li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-building text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">
                            {{ $department->department_description }}
                        </h4>
                        <small class="text-muted">
                            Review department formulas and drill down into courses and subjects.
                        </small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $buildRoute('admin.gradesFormula') }}" class="btn btn-outline-success btn-sm rounded-pill shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                    <a href="{{ $buildRoute('admin.gradesFormula.edit.department', ['department' => $department->id]) }}" class="btn btn-success btn-sm rounded-pill shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i>{{ $needsDepartmentFormula ? 'Create Department Formula' : 'Edit Department Formula' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
    <form method="GET" action="{{ route('admin.gradesFormula.department', ['department' => $department->id]) }}" class="d-flex align-items-center gap-2 flex-wrap">
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

    @php
        $totalCourses = $courseSummaries->count();
        $customCourses = $courseSummaries->filter(fn ($summary) => $summary['has_formula'])->count();
        $defaultCourses = max($totalCourses - $customCourses, 0);
        $fallbackLabel = $departmentFallback->label ?? $globalFormula->label ?? 'Baseline Formula';
        $catalogTotal = $departmentFormulas->count();
    @endphp

    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #198754, #20c997); color: white;">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle" style="background: rgba(255,255,255,0.15);">
                        <i class="bi bi-collection text-white" style="font-size: 1rem;"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-bold">Course Wildcards Overview</h6>
                        <small class="opacity-90">{{ $totalCourses }} courses · {{ $customCourses }} subject overrides · {{ $defaultCourses }} using baseline</small>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="bg-white bg-opacity-25 rounded-pill px-3 py-1 d-inline-flex align-items-center gap-2">
                        <small class="fw-semibold text-dark mb-0">
                            <i class="bi bi-lightbulb me-1"></i>Baseline Formula · {{ $fallbackLabel }}
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
                    <i class="bi bi-filter text-success"></i>
                    <span class="fw-semibold text-success">Filter wildcards</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success btn-sm rounded-pill wildcard-filter-btn active" data-filter="all">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>All
                        <span class="badge bg-white text-success ms-1">{{ $totalCourses }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill wildcard-filter-btn" data-filter="custom">
                        <i class="bi bi-star-fill me-1"></i>Formulas
                        <span class="badge bg-success text-white ms-1">{{ $catalogTotal }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill wildcard-filter-btn" data-filter="default">
                        <i class="bi bi-shield-check me-1"></i>Subjects
                        <span class="badge bg-success text-white ms-1">{{ $defaultCourses }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 d-none" id="department-formula-catalog" data-wildcard-section="catalog">
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                    <div>
                        <h5 class="fw-semibold mb-1" style="color: #198754;">Department Formula Catalog</h5>
                        <p class="text-muted mb-0">Start subjects from a consistent baseline or tailor alternatives for special cases.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $buildRoute('admin.gradesFormula.department.formulas.create', ['department' => $department->id]) }}" class="btn btn-success btn-sm rounded-pill shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i>Create Catalog Formula
                        </a>
                        <a href="{{ $buildRoute('admin.gradesFormula.edit.department', ['department' => $department->id]) }}" class="btn btn-outline-success btn-sm rounded-pill shadow-sm">
                            <i class="bi bi-pencil-square me-1"></i>Edit Fallback Formula
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden bg-success bg-opacity-10" data-status="catalog" data-url="{{ $buildRoute('admin.gradesFormula.department.formulas.create', ['department' => $department->id]) }}">
                <div class="position-relative" style="height: 80px; background: linear-gradient(135deg, #198754, #20c997);"></div>
                <div class="wildcard-circle" style="background: linear-gradient(135deg, #198754, #0f5132);">
                    <i class="bi bi-plus-lg text-white" style="font-size: 1.2rem;"></i>
                </div>
                    <div class="card-body pt-5 text-center d-flex flex-column align-items-center gap-3">
                    <div>
                        <h6 class="fw-semibold wildcard-title mt-2 text-dark">Create New Catalog Formula</h6>
                        <p class="text-muted small mb-0">Define reusable weightings for instructors to apply across subjects.</p>
                    </div>
                    {{-- Bottom badge intentionally removed to avoid empty placeholder --}}
                </div>
            </div>
        </div>

        @foreach($departmentFormulas as $formula)
            @php
                $weights = collect($formula->weight_map)
                    ->map(fn ($weight, $type) => [
                        'type' => strtoupper($type),
                        'percent' => number_format($weight * 100, 0),
                    ])
                    ->values();
                $isFallback = (bool) $formula->is_department_fallback;
                                $editRoute = $isFallback
                                    ? $buildRoute('admin.gradesFormula.edit.department', ['department' => $department->id])
                                    : $buildRoute('admin.gradesFormula.department.formulas.edit', ['department' => $department->id, 'formula' => $formula->id]);
            @endphp
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden formula-card" data-status="catalog" data-url="{{ $editRoute }}">
                    <div class="position-relative" style="height: 80px; background: linear-gradient(135deg, #0f5132, #198754);"></div>
                    <div class="wildcard-circle" style="background: linear-gradient(135deg, #23a362, #0b3d23);">
                        <span class="text-white fw-bold">{{ $isFallback ? 'FALLBACK' : 'CATALOG' }}</span>
                    </div>
                    <div class="card-body pt-5 d-flex flex-column gap-3">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h6 class="fw-semibold wildcard-title text-dark mb-0" title="{{ $formula->label }}">{{ $formula->label }}</h6>
                                <span class="badge {{ $isFallback ? 'bg-success-subtle text-success' : 'bg-light text-secondary' }}">
                                    {{ $isFallback ? 'Fallback' : 'Catalog' }}
                                </span>
                            </div>
                            <p class="text-muted small mb-1">Base {{ number_format($formula->base_score, 0) }} · Scale ×{{ number_format($formula->scale_multiplier, 0) }} · Passing {{ number_format($formula->passing_grade, 0) }}</p>
                            <div class="text-muted small">Updated {{ $formula->updated_at?->diffForHumans() ?? 'n/a' }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($weights as $weight)
                                <span class="badge bg-success-subtle text-success">{{ $weight['type'] }} {{ $weight['percent'] }}%</span>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ $editRoute }}" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </a>
                            @unless($isFallback)
                                    <form action="{{ $buildRoute('admin.gradesFormula.department.formulas.destroy', ['department' => $department->id, 'formula' => $formula->id]) }}" method="POST" onsubmit="return confirm('Delete this formula? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($courseSummaries->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-journal-minus fs-1 opacity-50"></i>
                </div>
                <h5 class="text-muted mb-2">No courses found</h5>
                <p class="text-muted mb-0">Add courses to this department to configure course-level formulas.</p>
            </div>
        </div>
    @else
        <div class="row g-4" id="course-wildcards" data-wildcard-section="courses">
            @foreach($courseSummaries as $summary)
                @php
                    $course = $summary['course'];
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" data-status="{{ $summary['status'] }}" data-url="{{ $buildRoute('admin.gradesFormula.course', ['department' => $department->id, 'course' => $course->id]) }}" style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        {{-- Top header --}}
                        <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                            <div class="wildcard-circle position-absolute start-50 translate-middle"
                                style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                <span class="text-white fw-bold">{{ $course->course_code }}</span>
                            </div>
                        </div>

                        {{-- Card body --}}
                        <div class="card-body pt-5 text-center">
                            <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $course->course_description }}">
                                {{ $course->course_description }}
                            </h6>
                            <p class="text-muted small mb-3">{{ $summary['scope_text'] }}</p>

                            {{-- Footer badges --}}
                            <div class="d-flex flex-column gap-2 mt-4">
                                @if($summary['missing_subject_count'] > 0)
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                        ⚠️ {{ $summary['missing_subject_count'] }} subject{{ $summary['missing_subject_count'] === 1 ? '' : 's' }} pending
                                    </span>
                                @endif
                                <span class="badge px-3 py-2 fw-semibold rounded-pill {{ $summary['has_formula'] ? 'bg-success' : 'bg-secondary' }}">
                                    @if($summary['has_formula'])
                                        ✓ {{ $summary['formula_scope'] }}
                                    @else
                                        {{ $summary['formula_scope'] }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterButtons = document.querySelectorAll('.wildcard-filter-btn');
        const cards = document.querySelectorAll('[data-wildcard-section] .wildcard-card');
        const catalogSection = document.querySelector('[data-wildcard-section="catalog"]');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;

                filterButtons.forEach(btn => btn.classList.remove('btn-success', 'active'));
                filterButtons.forEach(btn => btn.classList.add('btn-outline-success'));
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-success', 'active');

                cards.forEach(card => {
                    const status = card.dataset.status;
                    let matches = false;

                    if (filter === 'all') {
                        matches = status !== 'catalog';
                    } else if (filter === 'custom') {
                        matches = status === 'catalog';
                    } else {
                        matches = status === filter;
                    }

                    card.parentElement.classList.toggle('d-none', !matches);
                });

                if (catalogSection) {
                    catalogSection.classList.toggle('d-none', filter !== 'custom');
                }
            });
        });

        cards.forEach(card => {
            card.addEventListener('click', (event) => {
                const url = card.dataset.url;
                if (! url) {
                    return;
                }

                const isInteractiveChild = event.target.closest('a, button, form, input, label');
                if (isInteractiveChild) {
                    return;
                }

                window.location.href = url;
            });
        });

        // Initialize default state for catalog section visibility
        if (catalogSection) {
            catalogSection.classList.add('d-none');
        }
    });
</script>
<!-- Bottom badge elements removed to prevent empty rounded pills. -->
@endpush

@push('styles')
<style>
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

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #842029 !important;
}

.wildcard-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
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
</style>
@endpush
