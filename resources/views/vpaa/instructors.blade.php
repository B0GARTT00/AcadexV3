@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-semibold text-gray-800 mb-0">
                <i class="bi bi-people-fill me-2"></i>
                Instructor Management
                @if($selectedDepartment)
                    <span class="text-muted">• {{ $selectedDepartment->department_description }}</span>
                @endif
            </h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('vpaa.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vpaa.departments') }}">Departments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Instructors</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('vpaa.departments') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Departments
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form action="{{ route('vpaa.instructors') }}" method="GET" class="row g-3">
                <div class="col-md-10">
                    <label for="department_id" class="form-label fw-semibold">Filter by Department</label>
                    <select name="department_id" id="department_id" 
                        class="form-select"
                        onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (request('department_id') == $dept->id || ($selectedDepartment && $selectedDepartment->id == $dept->id)) ? 'selected' : '' }}>
                                {{ $dept->department_code }} - {{ $dept->department_description }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Instructors Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="px-4 py-3 fw-semibold">Name</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Role</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Department</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Email</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($instructors as $instructor)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill text-success"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $instructor->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge 
                                    @if($instructor->role == 0) bg-primary
                                    @elseif($instructor->role == 1) bg-warning
                                    @elseif($instructor->role == 2) bg-info
                                    @elseif($instructor->role == 3) bg-danger
                                    @elseif($instructor->role == 4) bg-success
                                    @elseif($instructor->role == 5) bg-dark
                                    @else bg-secondary
                                    @endif px-3 py-1">
                                    @if($instructor->role == 0) Instructor
                                    @elseif($instructor->role == 1) Chairperson
                                    @elseif($instructor->role == 2) Dean
                                    @elseif($instructor->role == 3) Admin
                                    @elseif($instructor->role == 4) GE Coordinator
                                    @elseif($instructor->role == 5) VPAA
                                    @else Unknown
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-muted">{{ $instructor->department->department_description ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-muted">{{ $instructor->email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $instructor->is_active ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                    {{ $instructor->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted mb-3">
                                    <i class="bi bi-people-x fs-1 opacity-50"></i>
                                </div>
                                <h6 class="text-muted mb-1">No instructors found</h6>
                                <p class="text-muted small mb-0">
                                    @if($selectedDepartment)
                                        No instructors are assigned to this department.
                                    @elseif(request('department_id'))
                                        Try changing your filter criteria.
                                    @else
                                        No instructors are currently registered.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($instructors->hasPages())
            <div class="card-footer bg-light bg-opacity-25 border-0 py-3 px-4">
                {{ $instructors->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
