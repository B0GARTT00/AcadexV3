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
                    <li class="breadcrumb-item"><a href="{{ route('vpaa.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Departments</li>
                </ol>
            </nav>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                <i class="bi bi-plus-lg me-2"></i>Add Department
            </button>
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
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4 px-3 py-3">
        @forelse($departments as $department)
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-shadow"
                     onclick="window.location.href='{{ route('vpaa.instructors', $department->id) }}'"
                     style="cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    
                    <!-- Header with Icon -->
                    <div class="d-flex align-items-center p-4" style="background: linear-gradient(135deg, #4ecd85, #3cb371);">
                        <div class="bg-white rounded-3 p-3 me-3">
                            <i class="bi bi-building fs-4" style="color: #4ecd85;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1 fw-bold text-dark">{{ $department->department_code }}</h5>
                            <p class="text-muted mb-0 small">
                                {{ $department->department_description ? Str::limit($department->department_description, 25) : 'No description' }}
                            </p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-around align-items-center mb-4">
                            <div class="text-center px-2">
                                <div class="h4 mb-1 fw-bold text-primary">{{ $department->instructor_count ?? 0 }}</div>
                                <span class="text-muted small">Instructors</span>
                            </div>
                            <div class="vr mx-1"></div>
                            <div class="text-center px-2">
                                <div class="h4 mb-1 fw-bold text-success">{{ $department->student_count ?? 0 }}</div>
                                <span class="text-muted small">Students</span>
                            </div>
                        </div>
                        <a href="{{ route('vpaa.instructors', $department->id) }}" 
                           class="btn btn-outline-primary w-100 rounded-pill px-3">
                            <i class="bi bi-arrow-right-circle me-1"></i> View Details
                        </a>
                    </div>
                    
                    <!-- Hidden form for delete action -->
                    <form id="delete-department-{{ $department->id }}" action="{{ route('vpaa.departments.destroy', $department->id) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="bi bi-building fs-1 opacity-25"></i>
                        </div>
                        <h5 class="text-muted">No departments found</h5>
                    </div>
                </div>
            </div>
        @endforelse
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
@endsection
