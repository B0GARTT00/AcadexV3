@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="bi bi-journal-text me-2"></i>
            Final Grades
            @if($departmentId)
                <span class="text-muted">- {{ $departments->firstWhere('id', $departmentId)->department_description ?? '' }}</span>
            @endif
            @if($courseId)
                <span class="text-muted">- {{ $courses->firstWhere('id', $courseId)->course_code ?? '' }}</span>
            @endif
        </h1>
        <a href="{{ route('vpaa.departments') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Departments
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('vpaa.grades') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="department_id" class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select" onchange="updateCourses()">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_description }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="course_id" class="form-label">Course</label>
                    <select name="course_id" id="course_id" class="form-select" {{ !$departmentId ? 'disabled' : '' }}>
                        <option value="">Select Course</option>
                        @if($departmentId)
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->course_code }} - {{ $course->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($courseId)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0">
                    <i class="bi bi-people me-2"></i>
                    Instructors
                </h5>
            </div>
            <div class="card-body">
                @if($instructors->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($instructors as $instructor)
                            <span class="badge bg-primary-subtle text-primary p-2">
                                <i class="bi bi-person-fill me-1"></i>
                                {{ $instructor->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No instructors found for this course.</p>
                @endif
            </div>
        </div>

        @if($students->isNotEmpty() && $subjects->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle">Student</th>
                            <th colspan="{{ $subjects->count() }}" class="text-center">Subjects</th>
                            <th rowspan="2" class="align-middle text-center">GPA</th>
                        </tr>
                        <tr>
                            @foreach($subjects as $subject)
                                <th class="text-center" style="min-width: 120px;">
                                    <div class="small text-truncate" title="{{ $subject->subject_code }} - {{ $subject->subject_description }}">
                                        {{ $subject->subject_code }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $subject->instructor ? $subject->instructor->name : 'N/A' }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $student->last_name }}, {{ $student->first_name }}</div>
                                    <div class="small text-muted">{{ $student->student_id }}</div>
                                </td>
                                @php
                                    $totalGrade = 0;
                                    $gradedSubjects = 0;
                                @endphp
                                @foreach($subjects as $subject)
                                    @php
                                        $grade = $finalGrades[$student->id][$subject->id][0] ?? null;
                                        if ($grade && $grade->grade) {
                                            $totalGrade += $grade->grade;
                                            $gradedSubjects++;
                                        }
                                    @endphp
                                    <td class="text-center {{ $grade && $grade->grade ? ($grade->grade < 3.0 ? 'table-success' : '') : '' }}">
                                        {{ $grade && $grade->grade ? number_format($grade->grade, 2) : 'N/A' }}
                                    </td>
                                @endforeach
                                <td class="text-center fw-bold">
                                    {{ $gradedSubjects > 0 ? number_format($totalGrade / $gradedSubjects, 2) : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No grade data available for the selected filters.
            </div>
        @endif
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-funnel" style="font-size: 2.5rem;"></i>
                </div>
                <h5>Select a department and course to view grades</h5>
                <p class="text-muted">
                    Please select a department and course from the filters above to view the final grades.
                </p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function updateCourses() {
        const departmentId = document.getElementById('department_id').value;
        const courseSelect = document.getElementById('course_id');
        
        if (departmentId) {
            // Fetch courses for the selected department
            fetch(`/api/departments/${departmentId}/courses`)
                .then(response => response.json())
                .then(data => {
                    courseSelect.innerHTML = '<option value="">Select Course</option>';
                    data.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = `${course.course_code} - ${course.name}`;
                        courseSelect.appendChild(option);
                    });
                    courseSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching courses:', error);
                    courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                });
        } else {
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            courseSelect.disabled = true;
        }
    }
</script>
@endpush
@endsection
