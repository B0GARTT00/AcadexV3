@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="bi bi-speedometer2 text-success me-2"></i>
                VPAA Dashboard
            </h1>
            <div class="text-muted">
                <i class="bi bi-calendar3 me-1"></i>
                {{ now()->format('F j, Y') }}
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-6 bg-success bg-opacity-10 border border-success text-success rounded-4 shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span class="me-auto">{{ session('status') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="row g-4 mb-6">
            <!-- Departments Card -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-soft-primary bg-opacity-25 text-primary rounded-3 p-3 me-3">
                                <i class="bi bi-building fs-2"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small fw-semibold text-uppercase">Departments</h6>
                                <h3 class="mb-0 fw-bold">{{ $departmentsCount ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light bg-opacity-25 border-top-0 py-3 px-4">
                        <a href="{{ route('vpaa.departments') }}" class="text-decoration-none text-primary fw-medium">
                            View all <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Instructors Card -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-soft-success bg-opacity-25 text-success rounded-3 p-3 me-3">
                                <i class="bi bi-people-fill fs-2"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small fw-semibold text-uppercase">Instructors</h6>
                                <h3 class="mb-0 fw-bold">{{ $instructorsCount ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light bg-opacity-25 border-top-0 py-3 px-4">
                        <a href="{{ route('vpaa.instructors') }}" class="text-decoration-none text-success fw-medium">
                            View all <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Students Card -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-soft-info bg-opacity-25 text-info rounded-3 p-3 me-3">
                                <i class="bi bi-mortarboard-fill fs-2"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small fw-semibold text-uppercase">Students</h6>
                                <h3 class="mb-0 fw-bold">{{ $studentsCount ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light bg-opacity-25 border-top-0 py-3 px-4">
                        <a href="{{ route('vpaa.students') }}" class="text-decoration-none text-info fw-medium">
                            View all <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-6">
            <div class="card-header bg-white border-0 py-4 px-4">
                <h5 class="mb-0 fw-semibold text-gray-800">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Manage Departments -->
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ route('vpaa.departments') }}" class="card h-100 border-0 bg-light bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                            <div class="card-body text-center p-4">
                                <div class="bg-soft-primary bg-opacity-25 text-primary rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-building fs-3"></i>
                                </div>
                                <h5 class="mb-1 fw-semibold">Departments</h5>
                                <p class="text-muted small mb-0">Manage academic departments</p>
                            </div>
                        </a>
                    </div>

                    <!-- Manage Instructors -->
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ route('vpaa.instructors') }}" class="card h-100 border-0 bg-light bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                            <div class="card-body text-center p-4">
                                <div class="bg-soft-success bg-opacity-25 text-success rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-people-fill fs-3"></i>
                                </div>
                                <h5 class="mb-1 fw-semibold">Instructors</h5>
                                <p class="text-muted small mb-0">View and manage instructors</p>
                            </div>
                        </a>
                    </div>

                    <!-- Manage Students -->
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ route('vpaa.students') }}" class="card h-100 border-0 bg-light bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                            <div class="card-body text-center p-4">
                                <div class="bg-soft-info bg-opacity-25 text-info rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-mortarboard-fill fs-3"></i>
                                </div>
                                <h5 class="mb-1 fw-semibold">Students</h5>
                                <p class="text-muted small mb-0">View student records</p>
                            </div>
                        </a>
                    </div>

                    <!-- Course Outcome Attainment -->
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ route('vpaa.course-outcome-attainment') }}" class="card h-100 border-0 bg-light bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                            <div class="card-body text-center p-4">
                                <div class="bg-soft-warning bg-opacity-25 text-warning rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-graph-up fs-3"></i>
                                </div>
                                <h5 class="mb-1 fw-semibold">Outcome Reports</h5>
                                <p class="text-muted small mb-0">View course outcome attainment</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-4 px-4">
                <h5 class="mb-0 fw-semibold text-gray-800">
                    <i class="bi bi-activity text-primary me-2"></i>
                    Recent Activities
                </h5>
            </div>
            <div class="card-body p-0">
                @if(isset($recentActivities) && count($recentActivities) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentActivities as $activity)
                            <div class="list-group-item border-0 px-4 py-3">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-sm">
                                            <span class="avatar-title rounded-circle bg-soft-{{ $activity['type'] }} text-{{ $activity['type'] }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="{{ $activity['icon'] }} fs-5"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-medium">{{ $activity['title'] }}</h6>
                                        <p class="text-muted small mb-1">{{ $activity['description'] }}</p>
                                        <div class="text-muted small">
                                            <i class="bi bi-clock me-1"></i> {{ $activity['time'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <hr class="my-0">
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="bi bi-inbox fs-1 opacity-50"></i>
                        </div>
                        <h6 class="text-muted mb-1">No recent activities</h6>
                        <p class="text-muted small mb-0">Activities will appear here as they happen</p>
                    </div>
                @endif
            </div>
            @if(isset($recentActivities) && count($recentActivities) > 0)
                <div class="card-footer bg-light bg-opacity-25 border-0 py-3 px-4">
                    <a href="{{ route('vpaa.activities') }}" class="text-decoration-none fw-medium">
                        View all activities <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
