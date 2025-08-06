@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Course Chair Overview 👨‍💼</h2>
            <p class="text-muted mb-0">Monitor Course performance and faculty management</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('chairperson.instructors') }}" class="btn btn-success rounded-pill px-3 shadow-sm">
                <i class="bi bi-person-plus"></i> Manage Instructors
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4">
        @php
            $cards = [
                [
                    'label' => 'Total Instructors',
                    'icon' => 'bi bi-person-video3',
                    'value' => $countInstructors,
                    'color' => 'primary',
                    'trend' => 'Department faculty'
                ],
                [
                    'label' => 'Total Students',
                    'icon' => 'bi bi-mortarboard-fill',
                    'value' => $countStudents,
                    'color' => 'success',
                    'trend' => 'Enrolled this semester'
                ],
                [
                    'label' => 'Active Courses',
                    'icon' => 'bi bi-journal-text',
                    'value' => $countCourses,
                    'color' => 'info',
                    'trend' => 'Current offerings'
                ]
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-2 bg-{{ $card['color'] }}-subtle me-3">
                                <i class="{{ $card['icon'] }} text-{{ $card['color'] }} fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">{{ $card['label'] }}</h6>
                                <h3 class="fw-bold text-{{ $card['color'] }} mb-0">{{ $card['value'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-arrow-right"></i> {{ $card['trend'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mt-4">
        {{-- Faculty Status Overview --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    {{-- Header Section --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2 bg-primary-subtle me-3">
                                <i class="bi bi-person-video3 text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-1">Faculty Status Overview</h5>
                                <p class="text-muted small mb-0">
                                    Managing <span class="fw-bold">{{ $countInstructors }}</span> Course Faculty Members
                                </p>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-pill px-3 hover-lift" type="button" id="helpDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-info-circle me-1"></i> Help
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-3 shadow-sm" aria-labelledby="helpDropdown" style="min-width: 280px;">
                                <li class="small">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                                        <h6 class="mb-0">Status Guide</h6>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item border-0 px-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-mortarboard-fill text-success me-2"></i>
                                                <strong>Active:</strong>
                                                <span class="ms-2 text-muted small">Currently teaching</span>
                                            </div>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-x-fill text-danger me-2"></i>
                                                <strong>Inactive:</strong>
                                                <span class="ms-2 text-muted small">On leave/deactivated</span>
                                            </div>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-plus text-warning me-2"></i>
                                                <strong>Pending:</strong>
                                                <span class="ms-2 text-muted small">Awaiting verification</span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Overall Progress Bar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-semibold mb-0">
                                <i class="bi bi-graph-up text-primary me-2"></i>
                                Faculty Distribution
                            </h6>
                            <div class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                Total: {{ $countInstructors }} Members
                            </div>
                        </div>
                        <div class="progress rounded-pill" style="height: 12px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: {{ $countInstructors > 0 ? ($countActiveInstructors / $countInstructors) * 100 : 0 }}%" 
                                data-bs-toggle="tooltip" 
                                title="Active: {{ $countActiveInstructors }} faculty members">
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: {{ $countInstructors > 0 ? ($countInactiveInstructors / $countInstructors) * 100 : 0 }}%" 
                                data-bs-toggle="tooltip" 
                                title="Inactive: {{ $countInactiveInstructors }} faculty members">
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                style="width: {{ $countInstructors > 0 ? ($countUnverifiedInstructors / $countInstructors) * 100 : 0 }}%" 
                                data-bs-toggle="tooltip" 
                                title="Pending: {{ $countUnverifiedInstructors }} faculty members">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <div class="small">
                                <i class="bi bi-circle-fill text-success me-1"></i> Active
                            </div>
                            <div class="small">
                                <i class="bi bi-circle-fill text-danger me-1"></i> Inactive
                            </div>
                            <div class="small">
                                <i class="bi bi-circle-fill text-warning me-1"></i> Pending
                            </div>
                        </div>
                    </div>

                    {{-- Status Cards --}}
                    <div class="row g-4">
                        {{-- Active Faculty Card --}}
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-success-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-3 p-2 bg-success text-white me-3">
                                            <i class="bi bi-mortarboard-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-success small fw-semibold">Active Faculty</div>
                                            <h3 class="fw-bold text-success mb-0">{{ $countActiveInstructors }}</h3>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Currently Teaching</span>
                                        <span class="badge bg-success text-white px-2 py-1">
                                            {{ $countInstructors > 0 ? number_format(($countActiveInstructors / $countInstructors) * 100, 1) : '0.0' }}%
                                        </span>
                                    </div>
                                    @if($countActiveInstructors > 0)
                                        <a href="{{ route('chairperson.instructors') }}#active" 
                                           class="stretched-link" 
                                           data-bs-toggle="tooltip" 
                                           title="View active faculty members">
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Inactive Faculty Card --}}
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-danger-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-3 p-2 bg-danger text-white me-3">
                                            <i class="bi bi-person-x-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-danger small fw-semibold">Inactive Faculty</div>
                                            <h3 class="fw-bold text-danger mb-0">{{ $countInactiveInstructors }}</h3>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">On Deactivated</span>
                                        <span class="badge bg-danger text-white px-2 py-1">
                                            {{ $countInstructors > 0 ? number_format(($countInactiveInstructors / $countInstructors) * 100, 1) : '0.0' }}%
                                        </span>
                                    </div>
                                    @if($countInactiveInstructors > 0)
                                        <a href="{{ route('chairperson.instructors') }}#inactive" 
                                           class="stretched-link" 
                                           data-bs-toggle="tooltip" 
                                           title="Review inactive faculty members">
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Pending Verification Card --}}
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-warning-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-3 p-2 bg-warning text-white me-3">
                                            <i class="bi bi-person-plus fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-warning small fw-semibold">Pending Verification</div>
                                            <h3 class="fw-bold text-warning mb-0">{{ $countUnverifiedInstructors }}</h3>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Awaiting Approval</span>
                                        <span class="badge bg-warning text-dark px-2 py-1">
                                            {{ $countInstructors > 0 ? number_format(($countUnverifiedInstructors / $countInstructors) * 100, 1) : '0.0' }}%
                                        </span>
                                    </div>
                                    @if($countUnverifiedInstructors > 0)
                                        <a href="{{ route('chairperson.instructors') }}#pending" 
                                           class="stretched-link" 
                                           data-bs-toggle="tooltip" 
                                           title="Review pending faculty accounts">
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">
                            <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Actions
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-pill px-3 hover-lift" type="button" id="quickActionsHelp" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-info-circle me-1"></i> Help
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-3 shadow-sm" aria-labelledby="quickActionsHelp" style="min-width: 280px;">
                                <li class="small">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                                        <h6 class="mb-0">Quick Actions Guide</h6>
                                    </div>
                                    <p class="text-muted small mb-2">Access frequently used features to manage your department efficiently.</p>
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item border-0 px-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-journal-plus text-success me-2"></i>
                                                <div>
                                                    <strong>Assign Subjects:</strong>
                                                    <span class="d-block text-muted small">Manage teaching loads and course assignments</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-mortarboard-fill text-primary me-2"></i>
                                                <div>
                                                    <strong>Student List:</strong>
                                                    <span class="d-block text-muted small">View and manage student records by year level</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-clipboard-data text-info me-2"></i>
                                                <div>
                                                    <strong>View Grades:</strong>
                                                    <span class="d-block text-muted small">Monitor and analyze student performance</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-column gap-3 flex-grow-1 justify-content-between">
                        <a href="{{ route('chairperson.assign-subjects') }}" 
                           class="btn btn-light text-start rounded-3 p-3 hover-lift position-relative"
                           data-bs-toggle="tooltip"
                           data-bs-placement="right"
                           title="Manage teaching loads and course assignments">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-2 bg-success-subtle me-3">
                                    <i class="bi bi-journal-plus text-success fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Assign Subjects</h6>
                                    <small class="text-muted d-block">Manage teaching loads</small>
                                </div>
                                <div class="ms-auto">
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                        
                        <a href="{{ route('chairperson.studentsByYear') }}" 
                           class="btn btn-light text-start rounded-3 p-3 hover-lift position-relative"
                           data-bs-toggle="tooltip"
                           data-bs-placement="right"
                           title="View and manage student records by year level">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-2 bg-primary-subtle me-3">
                                    <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Student List</h6>
                                    <small class="text-muted d-block">View students by year</small>
                                </div>
                                <div class="ms-auto">
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('chairperson.viewGrades') }}" 
                           class="btn btn-light text-start rounded-3 p-3 hover-lift position-relative"
                           data-bs-toggle="tooltip"
                           data-bs-placement="right"
                           title="Monitor and analyze student performance">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-2 bg-info-subtle me-3">
                                    <i class="bi bi-clipboard-data text-info fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">View Grades</h6>
                                    <small class="text-muted d-block">Monitor student performance</small>
                                </div>
                                <div class="ms-auto">
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    :root {
        --theme-primary: #4e73df;
        --theme-success: #1cc88a;
        --theme-info: #36b9cc;
        --theme-warning: #f6c23e;
        --theme-danger: #e74a3b;
        --theme-secondary: #858796;
        --theme-light: #f8f9fc;
        --theme-dark: #5a5c69;
    }

    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
    }

    /* Custom Theme Colors */
    .bg-primary-subtle {
        background-color: rgba(78, 115, 223, 0.1) !important;
    }
    .bg-success-subtle {
        background-color: rgba(28, 200, 138, 0.1) !important;
    }
    .bg-info-subtle {
        background-color: rgba(54, 185, 204, 0.1) !important;
    }
    .bg-warning-subtle {
        background-color: rgba(246, 194, 62, 0.1) !important;
    }
    .bg-danger-subtle {
        background-color: rgba(231, 74, 59, 0.1) !important;
    }

    .text-primary {
        color: var(--theme-primary) !important;
    }
    .text-success {
        color: var(--theme-success) !important;
    }
    .text-info {
        color: var(--theme-info) !important;
    }
    .text-warning {
        color: var(--theme-warning) !important;
    }
    .text-danger {
        color: var(--theme-danger) !important;
    }

    .btn-primary {
        background-color: var(--theme-primary);
        border-color: var(--theme-primary);
    }
    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
    }

    .btn-light {
        background-color: var(--theme-light);
        border-color: #e3e6f0;
    }
    .btn-light:hover {
        background-color: #eaecf4;
        border-color: #d1d3e2;
    }

    /* Status Cards */
    .border-success-subtle {
        border-color: rgba(28, 200, 138, 0.2) !important;
    }
    .border-danger-subtle {
        border-color: rgba(231, 74, 59, 0.2) !important;
    }
    .border-warning-subtle {
        border-color: rgba(246, 194, 62, 0.2) !important;
    }

    /* Quick Actions */
    .btn-light:hover .bg-primary-subtle {
        background-color: rgba(78, 115, 223, 0.15) !important;
    }
    .btn-light:hover .bg-success-subtle {
        background-color: rgba(28, 200, 138, 0.15) !important;
    }
    .btn-light:hover .bg-info-subtle {
        background-color: rgba(54, 185, 204, 0.15) !important;
    }

    /* Status Cards Enhancements */
    .status-card {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .status-card:hover {
        transform: translateY(-5px);
    }
    
    .status-help-item {
        transition: background-color 0.3s ease;
    }
    
    .status-help-item:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    
    /* Responsive adjustments */
    .min-w-0 {
        min-width: 0;
        flex: 1;
    }
    
    .text-truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .flex-shrink-0 {
        flex-shrink: 0;
    }
    
    /* Progress bar enhancements */
    .progress {
        overflow: visible;
    }
    
    .progress-bar {
        transition: width 0.6s ease;
        position: relative;
    }
    
    /* Mobile optimizations */
    @media (max-width: 576px) {
        .status-card {
            padding: 0.75rem !important;
        }
        
        .status-card .rounded-circle {
            padding: 0.5rem !important;
        }
        
        .status-card .fs-5 {
            font-size: 1rem !important;
        }
        
        .status-card h3 {
            font-size: 1.5rem !important;
        }
        
        .alert .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
    
    /* Accessibility improvements */
    .stretched-link:focus {
        outline: none;
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        border-radius: 0.5rem;
    }
    
    /* Print optimization */
    @media print {
        .status-card {
            break-inside: avoid;
        }
        
        .hover-lift {
            transform: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
    
    // Add hover effect to status cards
    document.querySelectorAll('.status-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endpush
