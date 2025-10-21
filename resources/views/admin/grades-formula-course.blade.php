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
                    <li class="breadcrumb-item">
                        <a href="{{ $buildRoute('admin.gradesFormula.department', ['department' => $department->id]) }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                            {{ $department->department_code }} Department
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        {{ $course->course_code }} Course
                    </li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-journal-check text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">
                            {{ $course->course_code }} · {{ $course->course_description }}
                        </h4>
                        <small class="text-muted">
                            Review course formula and drill down into subject weighting.
                        </small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $buildRoute('admin.gradesFormula.department', ['department' => $department->id]) }}" class="btn btn-outline-success btn-sm rounded-pill shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Department
                    </a>
                    <a href="{{ $buildRoute('admin.gradesFormula.edit.course', ['department' => $department->id, 'course' => $course->id]) }}" class="btn btn-success btn-sm rounded-pill shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i>{{ $needsCourseFormula ? 'Create Course Formula' : 'Edit Course Formula' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="{{ route('admin.gradesFormula.course', ['department' => $department->id, 'course' => $course->id]) }}" class="d-flex align-items-center gap-2 flex-wrap">
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
        $totalSubjects = $subjectSummaries->count();
        $customSubjects = $subjectSummaries->filter(fn ($summary) => $summary['has_formula'])->count();
        $defaultSubjects = max($totalSubjects - $customSubjects, 0);
    $fallbackScope = $courseFormula ? 'Course Formula' : ($departmentFallback ? 'Department Baseline' : 'System Default Formula');
    $fallbackLabel = $courseFormula->label ?? $departmentFallback->label ?? $globalFormula->label ?? 'Default Formula';
    @endphp

    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #198754, #20c997); color: white;">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle" style="background: rgba(255,255,255,0.15);">
                        <i class="bi bi-journals text-white" style="font-size: 1rem;"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-bold">Subject Wildcards Overview</h6>
                        <small class="opacity-90">{{ $totalSubjects }} subjects · {{ $customSubjects }} custom formulas · {{ $defaultSubjects }} using fallback</small>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="bg-white bg-opacity-25 rounded-pill px-3 py-1 d-inline-flex align-items-center gap-2">
                        <small class="fw-semibold text-dark mb-0">
                            <i class="bi bi-lightbulb me-1"></i>{{ $fallbackScope }} · {{ $fallbackLabel }}
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
                        <span class="badge bg-white text-success ms-1">{{ $totalSubjects }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill wildcard-filter-btn" data-filter="custom">
                        <i class="bi bi-star-fill me-1"></i>Formulas
                        <span class="badge bg-success text-white ms-1">{{ $customSubjects }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill wildcard-filter-btn" data-filter="default">
                        <i class="bi bi-shield-check me-1"></i>Subjects
                        <span class="badge bg-success text-white ms-1">{{ $defaultSubjects }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($needsCourseFormula)
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle me-2"></i>No custom course formula yet. Subjects will inherit the {{ $departmentFallback ? 'department baseline' : 'system default' }} unless a course or subject formula is created.
        </div>
    @endif

    @if($subjectSummaries->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-journal-minus fs-1 opacity-50"></i>
                </div>
                <h5 class="text-muted mb-2">No subjects found</h5>
                <p class="text-muted mb-0">Add subjects to this course to configure subject-level formulas.</p>
            </div>
        </div>
    @else
        <div class="row g-4" id="subject-wildcards">
            @foreach($subjectSummaries as $summary)
                @php
                    $subject = $summary['subject'];
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" data-status="{{ $summary['status'] }}" data-url="{{ $buildRoute('admin.gradesFormula.subject', ['subject' => $subject->id]) }}">
                        <div class="position-relative" style="height: 80px; background: linear-gradient(135deg, #0f5132, #198754);"></div>
                        <div class="wildcard-circle" style="background: linear-gradient(135deg, #23a362, #0b3d23);">
                            <span class="text-white fw-bold">{{ $subject->subject_code }}</span>
                        </div>
                        <div class="card-body pt-5 text-center d-flex flex-column align-items-center gap-3">
                            <div>
                                <h6 class="fw-semibold mt-2 text-dark wildcard-title" title="{{ $subject->subject_description }}">
                                    {{ $subject->subject_description }}
                                </h6>
                                <p class="text-muted small mb-0">{{ $summary['scope_text'] }}</p>
                            </div>
                            <div class="d-flex flex-column gap-2 w-100">
                                <span class="badge rounded-pill {{ $summary['has_formula'] ? 'bg-success-subtle text-success' : 'bg-light text-secondary' }}">{{ $summary['formula_scope'] }}</span>
                                <span class="badge rounded-pill badge-formula-label">{{ $summary['formula_label'] }}</span>
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
        const cards = document.querySelectorAll('#subject-wildcards .wildcard-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;

                filterButtons.forEach(btn => btn.classList.remove('btn-success', 'active'));
                filterButtons.forEach(btn => btn.classList.add('btn-outline-success'));
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-success', 'active');

                cards.forEach(card => {
                    const status = card.dataset.status;
                    const matches = filter === 'all' || status === filter;
                    card.parentElement.classList.toggle('d-none', !matches);
                });
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
    });
</script>
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
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(25, 135, 84, 0.18);
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

.wildcard-circle {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(15, 81, 50, 0.35);
    position: absolute;
    top: 40px;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    padding: 0 12px;
    word-break: break-word;
}

.badge-formula-label {
    background-color: #ffffff;
    color: #198754;
    border: 1px solid rgba(25, 135, 84, 0.25);
    font-weight: 600;
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
        width: 72px;
        height: 72px;
        top: 36px;
    }
}
</style>
@endpush
