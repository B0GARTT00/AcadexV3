@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="bi bi-mortarboard me-2"></i>
            Students
            @if($selectedDepartment)
                <span class="text-muted">- {{ $selectedDepartment->name ?? '' }}</span>
                @if($selectedCourseId)
                    <span class="text-muted">- {{ $courses->firstWhere('id', $selectedCourseId)->course_code ?? '' }}</span>
                @endif
            @endif
        </h1>
        <a href="{{ route('vpaa.departments') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Departments
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
                            <option value="{{ $dept->id }}" {{ $selectedDepartmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_description }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="course_id" class="form-label">Course</label>
                    <select name="course_id" id="course_id" class="form-select" {{ !$selectedDepartmentId ? 'disabled' : '' }} onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @if($selectedDepartmentId)
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $selectedCourseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->course_code }} - {{ $course->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Department</th>
                        <th>Year Level</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>{{ $student->student_id }}</td>
                            <td>{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</td>
                            <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                            <td>{{ $student->department->name ?? 'N/A' }}</td>
                            <td>{{ $student->year_level }}</td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                No students found.
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
