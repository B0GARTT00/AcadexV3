@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <h1 class="text-2xl font-bold mb-4 d-flex align-items-center">
        <i class="bi bi-people-fill text-success me-2" style="font-size: 2rem; line-height: 1; vertical-align: middle;"></i>
        <span>Manage Students</span>
    </h1>

    {{-- Subject Selection --}}
    <form method="GET" action="{{ route('instructor.students.index') }}" class="mb-4">
        <label class="form-label fw-medium mb-1">Select Course</label>
        <select name="subject_id" class="form-select" onchange="handleSubjectChange(this)">
            <option value="">-- Select Course --</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                    {{ $subject->subject_code }} - {{ $subject->subject_description }}
                </option>
            @endforeach
        </select>
    </form>

    {{-- Add Student Button (only shows when a subject is selected) --}}
    @if(request('subject_id'))
        <div class="mb-3 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enrollStudentModal">
                + Enroll Student
            </button>
        </div>
    @endif

    {{-- Students Table --}}
    @if($students && $students->count())
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Year Level</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $student->last_name }}, {{ $student->first_name }}
                                </td>
                                <td>{{ $student->year_level == 1 ? '1st' : ($student->year_level == 2 ? '2nd' : ($student->year_level == 3 ? '3rd' : '4th')) }} Year</td>
                                <td>
                                    @if($student->pivot->is_deleted)
                                        <span class="badge bg-danger">Dropped</span>
                                    @else
                                        <span class="badge bg-success">Enrolled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button"
                                            class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#manageStudentModal"
                                            data-student-id="{{ $student->id }}"
                                            data-student-first-name="{{ $student->first_name }}"
                                            data-student-last-name="{{ $student->last_name }}"
                                            data-student-year-level="{{ $student->year_level }}"
                                            data-student-status="{{ $student->pivot->is_deleted ? 'dropped' : 'enrolled' }}">
                                        <i class="bi bi-pencil-square"></i> Manage
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirmDropModal"
                                            data-student-id="{{ $student->id }}"
                                            data-student-name="{{ $student->first_name }} {{ $student->last_name }}">
                                        Drop
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(request('subject_id'))
        <div class="alert alert-warning bg-warning-subtle text-dark border-0 text-center">
            No students found for the selected subject.
        </div> 
    @endif
</div>

{{-- Enroll Student Modal --}}
<div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-labelledby="enrollStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('instructor.students.store') }}">
            @csrf
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="enrollStudentModalLabel">
                        📥 Enroll student to 
                        @if(request('subject_id'))
                            @php
                                $selectedSubject = $subjects->firstWhere('id', request('subject_id'));
                            @endphp
                            {{ $selectedSubject ? $selectedSubject->subject_code . ' - ' . $selectedSubject->subject_description : '' }}
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <input type="hidden" name="course_id" value="{{ Auth::user()->course_id }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" class="form-select" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'] as $level => $label)
                                    <option value="{{ $level }}">{{ $label }} Year</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control bg-light" value="{{ Auth::user()->course->course_code }} - {{ Auth::user()->course->course_description }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Enroll Student</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Manage Student Modal --}}
<div class="modal fade" id="manageStudentModal" tabindex="-1" aria-labelledby="manageStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="manageStudentForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="manageStudentModalLabel">👤 Manage Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="manage_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="manage_last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" id="manage_year_level" class="form-select" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'] as $level => $label)
                                    <option value="{{ $level }}">{{ $label }} Year</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control bg-light" value="{{ Auth::user()->course->course_code }} - {{ Auth::user()->course->course_description }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Drop Confirmation Modal --}}
<div class="modal fade" id="confirmDropModal" tabindex="-1" aria-labelledby="confirmDropModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="dropStudentForm">
            @csrf
            @method('DELETE')
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDropModalLabel">⚠ Confirm Drop</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to drop <strong id="studentNamePlaceholder">this student</strong> from the subject?</p>
                    <p class="text-danger mb-2">This action cannot be undone.</p>
                    <div class="mb-3">
                        <label class="form-label">Type "drop" to confirm</label>
                        <input type="text" class="form-control" id="dropConfirmation" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDropBtn" disabled>Drop Student</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Toast Message --}}
@if(session('success') || session('dropped'))
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div class="toast show align-items-center text-bg-{{ session('success') ? 'success' : 'danger' }} border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') ?? session('dropped') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function handleSubjectChange(select) {
    if (select.value === "") {
        window.location.href = "{{ route('instructor.students.index') }}";
    } else {
        select.form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const dropModal = document.getElementById('confirmDropModal');
    const dropConfirmation = document.getElementById('dropConfirmation');
    const confirmDropBtn = document.getElementById('confirmDropBtn');

    dropModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const studentId = button.getAttribute('data-student-id');
        const studentName = button.getAttribute('data-student-name');
        const form = dropModal.querySelector('#dropStudentForm');
        const placeholder = dropModal.querySelector('#studentNamePlaceholder');

        form.action = `/instructor/students/${studentId}/drop`;
        placeholder.textContent = studentName;
        
        dropConfirmation.value = '';
        confirmDropBtn.disabled = true;
    });

    dropConfirmation.addEventListener('input', function() {
        confirmDropBtn.disabled = this.value.toLowerCase() !== 'drop';
    });

    const manageModal = document.getElementById('manageStudentModal');
    manageModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const studentId = button.getAttribute('data-student-id');
        const firstName = button.getAttribute('data-student-first-name');
        const lastName = button.getAttribute('data-student-last-name');
        const yearLevel = button.getAttribute('data-student-year-level');
        
        const form = manageModal.querySelector('#manageStudentForm');
        form.action = `/instructor/students/${studentId}/update`;
        
        document.getElementById('manage_first_name').value = firstName;
        document.getElementById('manage_last_name').value = lastName;
        document.getElementById('manage_year_level').value = yearLevel;
    });
});
</script>
@endpush
