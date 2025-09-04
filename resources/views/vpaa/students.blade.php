@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="bi bi-mortarboard me-2"></i>
            Students Overview
            @if(isset($department))
                <span class="text-muted">- {{ $department->department_description ?? '' }}</span>
            @endif
        </h1>
        <a href="{{ route('vpaa.students') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Students Overview
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('vpaa.students') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="department_id" class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (isset($department) && $department->id == $dept->id) ? 'selected' : '' }}>
                                {{ $dept->department_description }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="course_id" class="form-label">Course</label>
                    <select name="course_id" id="course_id" class="form-select" {{ !isset($department) ? 'disabled' : '' }} onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @if(isset($courses) && isset($department))
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ (isset($selectedCourseId) && $selectedCourseId == $course->id) ? 'selected' : '' }}>
                                    {{ $course->course_code }} - {{ $course->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="px-4 py-3 fw-semibold">Name</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Course</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Department</th>
                        <th scope="col" class="px-4 py-3 fw-semibold">Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill text-info"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                    {{ $student->course->course_code ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-muted">{{ $student->department->department_description ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                    Year {{ $student->year_level }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted mb-3">
                                    <i class="bi bi-people-x fs-1 opacity-50"></i>
                                </div>
                                <h6 class="text-muted mb-1">No students found</h6>
                                <p class="text-muted small mb-0">
                                    @if(isset($department))
                                        No students are assigned to this department.
                                    @else
                                        Try selecting a different department.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const departmentSelect = document.getElementById('department_id');
        const courseSelect = document.getElementById('course_id');
        
        departmentSelect.addEventListener('change', function() {
            if (this.value) {
                // Enable course select and fetch courses for the selected department
                courseSelect.disabled = false;
            } else {
                // Disable and reset course select if no department is selected
                courseSelect.disabled = true;
                courseSelect.innerHTML = '<option value="">All Courses</option>';
            }
        });
    });
</script>
@endpush
@endsection
