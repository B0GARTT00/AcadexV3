@extends('layouts.app')

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
            </div>
        </div>
    </div>

    @php
    $departmentCount = $departmentsSummary->count();
    $overrideCount = $departmentsSummary->filter(fn ($summary) => $summary['catalog_count'] > 0)->count();
    $defaultCount = max($departmentCount - $overrideCount, 0);
        $hasSubjects = $departments->flatMap->courses->flatMap->subjects->isNotEmpty();
        $defaultBadgeLabel = optional($globalFormula)->label ?? 'System Default';
    @endphp

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @unless($hasSubjects)
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
                    <i class="bi bi-filter text-success"></i>
                    <span class="fw-semibold text-success">Filter wildcards</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success btn-sm rounded-pill wildcard-filter-btn active" data-filter="all">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>All
                        <span class="badge bg-white text-success ms-1">{{ $departmentCount }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill wildcard-filter-btn" data-filter="custom">
                        <i class="bi bi-star-fill me-1"></i>Formulas
                        <span class="badge bg-success text-white ms-1">{{ $overrideCount }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm rounded-pill wildcard-filter-btn" data-filter="default">
                        <i class="bi bi-shield-check me-1"></i>Subjects
                        <span class="badge bg-success text-white ms-1">{{ $defaultCount }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4" id="wildcard-selection">
        <div class="d-flex justify-content-end mb-3">
            <form method="GET" action="{{ route('admin.gradesFormula') }}" class="d-flex align-items-center gap-2">
                <label class="text-success small mb-0">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="" {{ request('semester') ? '' : 'selected' }}>All/Default</option>
                    <option value="1st" {{ request('semester')==='1st' ? 'selected' : '' }}>1st</option>
                    <option value="2nd" {{ request('semester')==='2nd' ? 'selected' : '' }}>2nd</option>
                    <option value="Summer" {{ request('semester')==='Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </form>
        </div>

        @foreach($departmentsSummary as $summary)
            @php
                $department = $summary['department'];
                $status = $summary['status'];
            @endphp
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="wildcard-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" data-status="{{ $status }}" data-url="{{ route('admin.gradesFormula.department', array_merge(['department' => $department->id], request()->only('semester'))) }}">
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.wildcard-filter-btn');
        const cards = document.querySelectorAll('#wildcard-selection .wildcard-card');

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
