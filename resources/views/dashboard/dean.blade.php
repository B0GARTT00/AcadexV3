@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Dean's Academic Overview </h2>
            <p class="text-muted mb-0">Monitor academic performance and department statistics</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('dean.grades') }}" class="btn btn-success rounded-pill px-3 shadow-sm">
                <i class="bi bi-clipboard-data"></i> View Grades
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-2 p-2 bg-primary-subtle me-2">
                            <i class="bi bi-people-fill text-primary fs-5"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 small">Total Students</h6>
                            <h4 class="fw-bold text-primary mb-0">{{ $studentsPerDepartment->sum() }}</h4>
                        </div>
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                        <i class="bi bi-arrow-right"></i> Across all departments
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-2 p-2 bg-success-subtle me-2">
                            <i class="bi bi-person-video3 text-success fs-5"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 small">Total Instructors</h6>
                            <h4 class="fw-bold text-success mb-0">{{ $totalInstructors }}</h4>
                        </div>
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                        <i class="bi bi-arrow-right"></i> Active faculty members
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-2 p-2 bg-info-subtle me-2">
                            <i class="bi bi-book-half text-info fs-5"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 small">Total Courses</h6>
                            <h4 class="fw-bold text-info mb-0">{{ $studentsPerCourse->count() }}</h4>
                        </div>
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                        <i class="bi bi-arrow-right"></i> Active academic courses
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm rounded-3 hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-2 p-2 bg-warning-subtle me-2">
                            <i class="bi bi-building text-warning fs-5"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 small">Departments</h6>
                            <h4 class="fw-bold text-warning mb-0">{{ $studentsPerDepartment->count() }}</h4>
                        </div>
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                        <i class="bi bi-arrow-right"></i> Active departments
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        {{-- Program Distribution --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">
                            <i class="bi bi-pie-chart-fill me-2"></i>Course Distribution
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Course Code</th>
                                    <th>Students</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentsPerCourse as $courseCode => $total)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-mortarboard text-primary me-2"></i>
                                                <strong>{{ $courseCode }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $total }}</td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ ($total / $studentsPerDepartment->sum()) * 100 }}%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Department Overview --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-4">
                        <i class="bi bi-building me-2"></i>Department Overview
                    </h5>
                    
                    @foreach ($studentsPerDepartment as $department => $count)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ $department }}</span>
                                <span class="badge bg-primary">{{ $count }} students</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: {{ ($count / $studentsPerDepartment->sum()) * 100 }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
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
        transition: transform 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
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

    .progress-bar {
        background-color: var(--theme-primary);
    }

    .badge.bg-primary-subtle {
        background-color: rgba(78, 115, 223, 0.1) !important;
        color: var(--theme-primary) !important;
    }

    /* Table Styles */
    .table-light {
        background-color: var(--theme-light);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }

    /* Custom Badge Colors */
    .badge.bg-success-subtle {
        background-color: rgba(28, 200, 138, 0.1) !important;
        color: var(--theme-success) !important;
    }
</style>
@endpush
