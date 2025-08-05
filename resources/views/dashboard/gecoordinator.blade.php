@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">GE Coordinator Overview 👨‍💼</h2>
            <p class="text-muted mb-0">Monitor General Education program performance and faculty management</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('gecoordinator.instructors') }}" class="btn btn-success rounded-pill px-3 shadow-sm">
                <i class="bi bi-person-plus"></i> Manage GE Instructors
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4">
        @php
            $cards = [
                [
                    'label' => 'Total GE Instructors',
                    'icon' => 'bi bi-person-video3',
                    'value' => $countInstructors,
                    'color' => 'primary',
                    'trend' => 'GE faculty members'
                ],
                [
                    'label' => 'Total Students Enrolled',
                    'icon' => 'bi bi-mortarboard-fill',
                    'value' => $countStudents,
                    'color' => 'success',
                    'trend' => 'In GE subjects this semester'
                ],
                [
                    'label' => 'Active GE Courses',
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2 bg-primary-subtle me-3">
                                <i class="bi bi-person-video3 text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-1">GE Faculty Status</h5>
                                <p class="text-muted small mb-0">
                                    Managing <span class="fw-bold">{{ $countInstructors }}</span> GE Faculty Members
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('gecoordinator.instructors') }}" class="btn btn-outline-primary btn-sm">
                            View All Instructors
                        </a>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-success-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-3 p-2 bg-success text-white me-3">
                                            <i class="bi bi-person-check fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">Active Instructors</h6>
                                            <h3 class="mb-0">{{ $countActiveInstructors }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-warning-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-3 p-2 bg-warning text-white me-3">
                                            <i class="bi bi-person-x fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">Inactive Instructors</h6>
                                            <h3 class="mb-0">{{ $countInactiveInstructors }}</h3>
                                        </div>
                                    </div>
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
                    <h5 class="fw-semibold mb-4">
                        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Actions
                    </h5>
                    
                    <div class="d-flex flex-column gap-3">
                        <a href="{{ route('gecoordinator.assignSubjects') }}" class="btn btn-light text-start rounded-3 p-3 hover-lift">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="bi bi-journal-text fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Assign GE Subjects</h6>
                                    <small class="text-muted">Manage subject assignments</small>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('gecoordinator.studentsByYear') }}" class="btn btn-light text-start rounded-3 p-3 hover-lift">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success me-3">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">View Students by Year</h6>
                                    <small class="text-muted">Analyze student distribution</small>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('gecoordinator.viewGrades') }}" class="btn btn-light text-start rounded-3 p-3 hover-lift">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info me-3">
                                    <i class="bi bi-clipboard-data fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">View Grades</h6>
                                    <small class="text-muted">Monitor student performance</small>
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
<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .rounded-4 {
        border-radius: 1rem !important;
    }
</style>
@endpush
