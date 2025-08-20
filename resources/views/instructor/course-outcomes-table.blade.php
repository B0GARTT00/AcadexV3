@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course-outcomes-table.css') }}">
@endpush

@push('scripts')
<script>
// Pass Laravel data to JavaScript
window.courseOutcomesData = {
    subjectCode: '{{ $selectedSubject->subject_code ?? "" }}'
};
</script>
<script src="{{ asset('js/course-outcomes-table.js') }}"></script>
@endpush

@section('content')
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080; min-width: 350px;">
    @if(session('success'))
        <div class="toast align-items-center text-bg-success border-0 show mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" id="toast-success">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-dark" id="toast-success-bar" role="progressbar" style="width: 100%; transition: width 5s linear;"></div>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="toast align-items-center text-bg-danger border-0 show mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" id="toast-error">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-dark" id="toast-error-bar" role="progressbar" style="width: 100%; transition: width 5s linear;"></div>
            </div>
        </div>
    @endif
    @if(session('info'))
        <div class="toast align-items-center text-bg-info border-0 show mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" id="toast-info">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('info') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-dark" id="toast-info-bar" role="progressbar" style="width: 100%; transition: width 5s linear;"></div>
            </div>
        </div>
    @endif
</div>
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('instructor.course_outcomes.index') }}">Course Outcomes</a></li>
            @if(isset($selectedSubject))
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}
                </li>
            @endif
        </ol>
    </nav>

    {{-- Subject Info --}}
    @if(isset($selectedSubject))
        <div class="mb-4">
            <h4 class="fw-bold">Subject: {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}</h4>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="header-section">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="header-title">📋 Course Outcomes Management</h1>
                <p class="header-subtitle">
                    Subject: <strong>{{ $selectedSubject->subject_code ?? 'N/A' }} - {{ $selectedSubject->subject_description ?? 'N/A' }}</strong>
                    @if($currentPeriod)
                        | {{ $currentPeriod->academic_year }} - {{ $currentPeriod->semester }}
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-3 no-print">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
                    ➕ Add Course Outcome
                </button>
            </div>
        </div>
    </div>

    {{-- Course Outcomes Table Section --}}
    <div class="main-results-container">
        @if($cos && $cos->count())
            <div class="results-card">
                <div class="card-header-custom">
                    <i class="bi bi-table me-2"></i>Course Outcomes Overview
                </div>
                <div class="table-responsive p-3">
                    <table class="table co-table table-bordered table-hover align-middle mb-0 course-outcomes-table table-striped">
                        <thead class="table-success">
                            <tr>
                                <th class="co-code-col">
                                    <i class="bi bi-hash"></i> CO Code
                                </th>
                                <th class="co-identifier-col">
                                    <i class="bi bi-tag"></i> Identifier
                                </th>
                                </th>
                                <th class="co-description-col">
                                    <i class="bi bi-pencil-square"></i> Description 
                                    <small class="text-white opacity-100">(Double-click to edit)</small>
                                </th>
                                <th class="co-period-col">
                                    <i class="bi bi-calendar-range"></i> Academic Period
                                </th>
                                <th class="co-actions-col text-end">
                                    <i class="bi bi-gear"></i> Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cos as $co)
                                <tr>
                                    <td class="fw-semibold co-code-cell">{{ $co->co_code }}</td>
                                    <td class="co-identifier-cell">{{ $co->co_identifier }}</td>
                                    <td class="co-description-cell" data-co-id="{{ $co->id }}">
                                        <div class="description-wrapper">
                                            <div class="description-text editable-description" 
                                                 title="Double-click to edit: {{ $co->description }}"
                                                 data-original-text="{{ $co->description }}">
                                                {{ $co->description }}
                                            </div>
                                            <input type="text" 
                                                   class="form-control description-input d-none" 
                                                   value="{{ $co->description }}"
                                                   maxlength="1000">
                                            @if(strlen($co->description) > 100)
                                                <button class="btn btn-link btn-sm p-0 mt-1 expand-btn" type="button">
                                                    <small>Show more</small>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="co-period-cell">
                                        @if($co->academicPeriod)
                                            <span class="text-nowrap">{{ $co->academicPeriod->academic_year }} - {{ $co->academicPeriod->semester }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end co-actions-cell">
                                        <form action="{{ route('instructor.course_outcomes.destroy', $co->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this course outcome? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm delete-btn" title="Delete Course Outcome">
                                                <i class="bi bi-trash"></i>
                                                <span class="btn-text">Delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="results-card">
                <div class="card-header-custom">
                    <i class="bi bi-info-circle me-2"></i>No Course Outcomes Found
                </div>
                <div class="p-4 text-center">
                    <div class="empty-state">
                        <i class="bi bi-clipboard-x display-1 text-muted mb-3"></i>
                        <h5 class="text-muted mb-3">No course outcomes found for this subject</h5>
                        <p class="text-muted mb-4">Get started by adding your first course outcome.</p>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
                            <i class="bi bi-plus-circle me-2"></i>Add First Course Outcome
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>


{{-- Add Course Outcome Modal --}}
<div class="modal fade" id="addCourseOutcomeModal" tabindex="-1" aria-labelledby="addCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('instructor.course_outcomes.store') }}">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title d-flex align-items-center" id="addCourseOutcomeModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Course Outcome
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-hash me-1"></i>CO Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="co_code" id="co_code" class="form-control form-control-lg" readonly style="background-color: #f8f9fa;" required>
                            <small class="form-text text-muted">Auto-generated based on existing course outcomes</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-tag me-1"></i>Identifier <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="co_identifier" id="co_identifier" class="form-control form-control-lg" readonly style="background-color: #f8f9fa;" required>
                            <small class="form-text text-muted">Follows subject code pattern</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-pencil-square me-1"></i>Description <span class="text-danger">*</span>
                        </label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Enter a clear and specific course outcome description..." required></textarea>
                        <small class="form-text text-muted">Describe what students should be able to demonstrate or achieve</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-range me-1"></i>Academic Period <span class="text-danger">*</span>
                        </label>
                        <input type="hidden" name="academic_period_id" value="{{ $currentPeriod->id ?? '' }}">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                            <input type="text" class="form-control form-control-lg" value="{{ $currentPeriod->academic_year ?? '' }} - {{ $currentPeriod->semester ?? '' }}" readonly style="background-color: #f8f9fa;">
                        </div>
                        <small class="form-text text-muted">Course outcome will be created for the current academic period</small>
                    </div>
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id ?? '' }}">
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Create Course Outcome
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


@endsection