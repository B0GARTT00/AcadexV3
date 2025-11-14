@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-semibold text-gray-800 mb-0">
                <i class="bi bi-building me-2"></i>
                Departments Overview
            </h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('vpaa.dashboard') }}" class="text-success">Dashboard</a></li>
                    <li class="breadcrumb-item active text-success" aria-current="page">Departments</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>
                {{ session('status') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Departments Grid -->
        {{-- Departments Grid --}}
    <div class="row g-4">
        @foreach($departments as $department)
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden" 
                     style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                     onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'"
                     onclick="window.location.href='{{ route('vpaa.instructors', ['department_id' => $department->id]) }}'">
                    
                    {{-- Green Header Section --}}
                    <div class="position-relative" style="height: 70px; background: linear-gradient(135deg, #4ecd85, #3ba76a);">
                        <div class="position-absolute start-50 translate-middle"
                             style="top: 100%; transform: translate(-50%, -50%); width: 70px; height: 70px; 
                                    background: linear-gradient(135deg, #4da674, #023336); 
                                    border-radius: 15%; display: flex; align-items: center; justify-content: center; 
                                    box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.3s ease;">
                            <i class="bi bi-building-fill text-white" style="font-size: 28px;"></i>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="card-body pt-4 text-center px-3 pb-4">
                        <h6 class="fw-bold mt-3 mb-3 text-dark text-truncate" title="{{ $department->department_description }}" style="font-size: 1rem; line-height: 1.3;">
                            {{ $department->department_description }}
                        </h6>
                        
                        {{-- Department Badge --}}
                        <div class="mb-3">
                            <span class="badge bg-primary text-white px-3 py-1" style="font-size: 0.8rem;">Department</span>
                        </div>
                        
                        {{-- Mini Stats --}}
                        <div class="row g-2 px-2">
                            <div class="col-6">
                                <div class="bg-success-subtle rounded-3 p-2 text-center border border-success border-opacity-25">
                                    <div class="fw-bold text-success mb-1" style="font-size: 1.4rem;">{{ $department->instructor_count ?? 0 }}</div>
                                    <div class="small text-success fw-semibold" style="font-size: 0.8rem;">Instructors</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-info-subtle rounded-3 p-2 text-center border border-info border-opacity-25">
                                    <div class="fw-bold text-info mb-1" style="font-size: 1.4rem;">{{ $department->student_count ?? 0 }}</div>
                                    <div class="small text-info fw-semibold" style="font-size: 0.8rem;">Students</div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Click Indicator --}}
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-arrow-right-circle me-1"></i>View Instructors
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('vpaa.departments.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="department_code" class="form-label">Department Code</label>
                            <input type="text" class="form-control" id="department_code" name="department_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="department_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="department_description" name="department_description" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modals -->
    @foreach($departments as $department)
        <div class="modal fade" id="editDepartmentModal{{ $department->id }}" tabindex="-1" aria-labelledby="editDepartmentModalLabel{{ $department->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-semibold" id="editDepartmentModalLabel{{ $department->id }}">
                            <i class="bi bi-pencil-square me-2"></i>Edit Department
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('vpaa.departments.update', $department->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_department_code_{{ $department->id }}" class="form-label">Department Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_department_code_{{ $department->id }}" name="department_code" value="{{ $department->department_code }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_department_description_{{ $department->id }}" class="form-label">Department Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_department_description_{{ $department->id }}" name="department_description" value="{{ $department->department_description }}" required>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* GE Coordinator Style Card Hover Effects */
    .card:hover .position-absolute {
        transform: translate(-50%, -50%) scale(1.1) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;
    }
    
    .card {
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .mini-stat-card {
        transition: all 0.2s ease;
    }
    
    .card:hover .mini-stat-card {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .hover-lift {
        transition: all 0.3s ease-in-out;
    }

    .hover-lift:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    /* Green theme colors to match GE Coordinator */
    .bg-primary-subtle {
        background-color: rgba(78, 205, 133, 0.1) !important;
    }
    
    .text-primary {
        color: #4ecd85 !important;
    }
</style>
@endpush
@endsection
