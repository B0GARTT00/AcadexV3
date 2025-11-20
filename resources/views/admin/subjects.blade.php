@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-dark fw-bold mb-0">📚 Courses</h1>
            <p class="text-muted mb-0">Manage academic courses (subjects)</p>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#subjectModal">
            <i class="bi bi-plus-lg me-1"></i> Add Course
        </button>
    </div>

    {{-- Subjects Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="subjectsTable" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Units</th>
                            <th>Year Level</th>
                            <th>Department</th>
                            <th>Program</th>
                            <th>Academic Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td>{{ $subject->id }}</td>
                                <td class="fw-semibold">{{ $subject->subject_code }}</td>
                                <td>{{ $subject->subject_description ?? '-' }}</td>
                                <td>{{ $subject->units }}</td>
                                <td>{{ $subject->year_level ?? '-' }}</td>
                                <td>{{ $subject->department->department_description ?? '-' }}</td>
                                <td>{{ $subject->course->course_description ?? '-' }}</td>
                                <td>{{ $subject->academicPeriod->academic_year ?? '-' }} {{ $subject->academicPeriod->semester ?? '' }}</td>
                                {{-- Actions removed as requested --}}
                            </tr>
                        @empty
                            {{-- DataTables will handle empty state --}}
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Subject Modal --}}
<div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="subjectModalLabel">Add New Course</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.storeSubject') }}">
                @csrf
                <div class="modal-body">
                    {{-- Academic Period --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Academic Period</label>
                        <select name="academic_period_id" class="form-select" required>
                            <option value="">-- Select Academic Period --</option>
                            @foreach($academicPeriods as $period)
                                <option value="{{ $period->id }}">
                                    {{ $period->academic_year }} - {{ ucfirst($period->semester) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Department --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <select name="department_id" class="form-select" required id="department-select">
                            <option value="">-- Select Department --</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->department_description }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Course --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program</label>
                        <select name="course_id" class="form-select" required id="course-select">
                            <option value="">-- Select Program --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" data-department="{{ $course->department_id }}">
                                    {{ $course->course_description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Course Code</label>
                        <input type="text" name="subject_code" class="form-control" placeholder="e.g. ITE 101" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Course Description</label>
                        <input type="text" name="subject_description" class="form-control" placeholder="e.g. Introduction to Computing" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Units</label>
                            <input type="number" name="units" class="form-control" required min="1" max="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Year Level</label>
                            <select name="year_level" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                                <option value="5">5th Year</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->any())
<div class="alert alert-danger position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@push('scripts')
<script>
    $(document).ready(function() {
        $('#subjectsTable').DataTable({
            order: [[1, 'asc']], // Sort by Code
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search courses...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ courses",
                emptyTable: "No courses found"
            },
            // Keep default ordering behavior after removing Actions column
        });

        // Filter courses based on selected department
        $('#department-select').change(function() {
            const departmentId = $(this).val();
            const courseSelect = $('#course-select');
            
            // Reset course selection
            courseSelect.val('');
            
            // Show/hide courses based on department
            courseSelect.find('option').each(function() {
                if (!departmentId || $(this).val() === '' || $(this).data('department') == departmentId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>
@endpush
@endsection
