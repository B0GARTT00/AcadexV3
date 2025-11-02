@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Welcome Back, {{ Auth::user()->name }}! 👋</h2>
            <p class="text-muted mb-0">Oversee academic operations and institutional management</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('vpaa.departments') }}" class="btn btn-success rounded-pill px-3 shadow-sm">
                <i class="bi bi-building"></i> Manage Departments
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4">
        @php
            $cards = [
                [
                    'label' => 'Total Departments',
                    'icon' => 'bi bi-building',
                    'value' => $departmentsCount ?? 0,
                    'color' => 'primary',
                    'trend' => 'Active departments'
                ],
                [
                    'label' => 'Total Instructors',
                    'icon' => 'bi bi-people-fill',
                    'value' => $instructorsCount ?? 0,
                    'color' => 'success',
                    'trend' => 'Active faculty'
                ],
                [
                    'label' => 'Total Students',
                    'icon' => 'bi bi-mortarboard-fill',
                    'value' => $studentsCount ?? 0,
                    'color' => 'info',
                    'trend' => 'Enrolled students'
                ],
                [
                    'label' => 'Academic Programs',
                    'icon' => 'bi bi-journal-text',
                    'value' => $departmentsCount * 3 ?? 0, // Estimated programs per department
                    'color' => 'warning',
                    'trend' => 'Course offerings'
                ]
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-md-3">
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

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span class="me-auto">{{ session('status') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="row g-4 mt-4">
        {{-- Departments Overview --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    {{-- Header Section --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2 bg-primary-subtle me-3">
                                <i class="bi bi-building text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-0">Department Management</h5>
                                <p class="text-muted small mb-0">Academic department overview and status</p>
                            </div>
                        </div>
                        <a href="{{ route('vpaa.departments') }}" class="btn btn-outline-primary btn-sm rounded-pill">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    {{-- Quick Stats Grid --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-primary-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="rounded-3 p-2 bg-primary text-white me-3">
                                            <i class="bi bi-building fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-primary small fw-semibold">Active Departments</div>
                                            <h3 class="fw-bold text-primary mb-0">{{ $departmentsCount ?? 0 }}</h3>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Currently operational</span>
                                        <span class="badge bg-primary px-2 py-1">100%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 bg-success-subtle hover-lift">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="rounded-3 p-2 bg-success text-white me-3">
                                            <i class="bi bi-people-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-success small fw-semibold">Faculty Members</div>
                                            <h3 class="fw-bold text-success mb-0">{{ $instructorsCount ?? 0 }}</h3>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Active instructors</span>
                                        <span class="badge bg-success px-2 py-1">
                                            {{ $departmentsCount > 0 ? number_format(($instructorsCount / $departmentsCount), 1) : '0.0' }} avg
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Access Panel --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">
                            <i class="bi bi-speedometer2 me-2"></i>Quick Access
                        </h5>
                    </div>

                    {{-- Quick Access Items --}}
                    <div class="flex-grow-1">
                        <div class="row g-3">
                            <div class="col-12">
                                <a href="{{ route('vpaa.departments') }}" class="card border-0 bg-primary bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                                    <div class="card-body text-center p-3">
                                        <div class="bg-primary bg-opacity-25 text-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                            <i class="bi bi-building fs-4"></i>
                                        </div>
                                        <h6 class="mb-1 fw-semibold">Departments</h6>
                                        <p class="text-muted small mb-0">Manage academic departments</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="{{ route('vpaa.instructors') }}" class="card border-0 bg-success bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                                    <div class="card-body text-center p-3">
                                        <div class="bg-success bg-opacity-25 text-success rounded-circle p-2 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                            <i class="bi bi-people-fill fs-4"></i>
                                        </div>
                                        <h6 class="mb-1 fw-semibold">Instructors</h6>
                                        <p class="text-muted small mb-0">View and manage faculty</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="{{ route('vpaa.students') }}" class="card border-0 bg-info bg-opacity-10 hover-bg-opacity-25 transition-all rounded-3 text-decoration-none">
                                    <div class="card-body text-center p-3">
                                        <div class="bg-info bg-opacity-25 text-info rounded-circle p-2 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                            <i class="bi bi-mortarboard-fill fs-4"></i>
                                        </div>
                                        <h6 class="mb-1 fw-semibold">Students</h6>
                                        <p class="text-muted small mb-0">Access student records</p>
                                    </div>
                                </a>
                            </div>
                        </div>
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
        transition: transform 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-3px);
    }
    
    .hover-bg-opacity-25:hover {
        background-opacity: 0.25 !important;
    }
    
    .transition-all {
        transition: all 0.2s ease;
    }
    
    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .bg-soft-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
</style>
@endpush
