@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">
        <i class="bi bi-bar-chart-fill text-success me-2"></i>
        Students' Final Grades
    </h1>

    {{-- Breadcrumb Navigation --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item">
                <a href="{{ route('chairperson.viewGrades') }}" class="{{ empty($selectedInstructorId) && empty($selectedSubjectId) ? 'active' : '' }}">Select Instructor</a>
            </li>
            @if (!empty($selectedInstructorId) && empty($selectedSubjectId))
                <li class="breadcrumb-item active" aria-current="page">Select Subject</li>
            @elseif (!empty($selectedInstructorId) && !empty($selectedSubjectId))
                <li class="breadcrumb-item active" aria-current="page">Students' Final Grades</li>
            @endif
        </ol>
    </nav>

    {{-- Step 1: Instructor Selection --}}
    @if (empty($selectedInstructorId) && empty($selectedSubjectId))
        <div class="row g-4 px-4 py-4">
            @foreach($instructors as $instructor)
                <div class="col-md-4">
                    <div
                        class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl hover:border-primary"
                        data-url="{{ route('chairperson.viewGrades', ['instructor_id' => $instructor->id]) }}"
                        style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                        onclick="window.location.href='{{ route('chairperson.viewGrades', ['instructor_id' => $instructor->id]) }}'"
                    >
                        {{-- Top header --}}
                        <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                            <div class="subject-circle position-absolute start-50 translate-middle"
                                style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 10%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                {{-- Person Icon for Instructor (Square Design) --}}
                                <i class="bi bi-person-circle text-white" style="font-size: 40px;"></i>
                            </div>
                        </div>

                        {{-- Card body --}}
                        <div class="card-body pt-5 text-center">
                            <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
                                {{ $instructor->last_name }}, {{ $instructor->first_name }}
                            </h6>
                            {{-- Badge for role --}}
                            <div class="mt-2">
                                <span class="badge bg-primary text-white">Instructor</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif (empty($selectedSubjectId))
        {{-- Step 2: Subject Selection --}}
        @if (!empty($subjects))
            <div class="row g-4 px-4 py-4" id="subject-selection">
                @foreach($subjects as $subjectItem)
                    <div class="col-md-4">
                        <div
                            class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                            data-url="{{ route('chairperson.viewGrades', ['instructor_id' => $selectedInstructorId, 'subject_id' => $subjectItem->id]) }}"
                            style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                            onclick="window.location.href='{{ route('chairperson.viewGrades', ['instructor_id' => $selectedInstructorId, 'subject_id' => $subjectItem->id]) }}'"
                        >
                            {{-- Top header --}}
                            <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                                <div class="subject-circle position-absolute start-50 translate-middle"
                                    style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                    <h5 class="mb-0 text-white fw-bold">{{ $subjectItem->subject_code }}</h5>
                                </div>
                            </div>

                            {{-- Card body --}}
                            <div class="card-body pt-5 text-center">
                                <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $subjectItem->subject_description }}">
                                    {{ $subjectItem->subject_description }}
                                </h6>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-muted mt-8 bg-warning bg-opacity-25 border border-warning px-6 py-4 rounded-4">
                No subjects found for this instructor.
            </div>
        @endif
    @else
        {{-- Step 3: Display Students' Final Grades --}}
        {{-- Students Table --}}
        @if (!empty($students) && count($students))
            <div class="bg-white shadow-lg rounded-4 overflow-x-auto">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Student Name</th>
                            <th class="text-center">Prelim</th>
                            <th class="text-center">Midterm</th>
                            <th class="text-center">Prefinal</th>
                            <th class="text-center">Final</th>
                            <th class="text-center text-success">Final Average</th>
                            <th class="text-center">Remarks</th>
                            <th class="text-center">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            @php
                                $termGrades = $student->termGrades->keyBy('term_id');

                                $prelim = $termGrades[1]->term_grade ?? null;
                                $midterm = $termGrades[2]->term_grade ?? null;
                                $prefinal = $termGrades[3]->term_grade ?? null;
                                $final = $termGrades[4]->term_grade ?? null;

                                $hasAll = !is_null($prelim) && !is_null($midterm) && !is_null($prefinal) && !is_null($final);
                                $average = $hasAll ? round(($prelim + $midterm + $prefinal + $final) / 4) : null;

                                $remarks = $average !== null ? ($average >= 75 ? 'Passed' : 'Failed') : null;
                                
                                $finalGrade = $student->finalGrades->first();
                                $notes = $finalGrade->notes ?? '';
                            @endphp
                            <tr class="hover:bg-light">
                                <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                <td class="text-center">{{ $prelim !== null ? round($prelim) : '-' }}</td>
                                <td class="text-center">{{ $midterm !== null ? round($midterm) : '-' }}</td>
                                <td class="text-center">{{ $prefinal !== null ? round($prefinal) : '-' }}</td>
                                <td class="text-center">{{ $final !== null ? round($final) : '-' }}</td>
                                <td class="text-center fw-semibold text-success">
                                    {{ $average !== null ? $average : '-' }}
                                </td>
                                <td class="text-center">
                                    @if($remarks === 'Passed')
                                        <span class="badge bg-success-subtle text-success fw-medium px-3 py-2 rounded-pill">Passed</span>
                                    @elseif($remarks === 'Failed')
                                        <span class="badge bg-danger-subtle text-danger fw-medium px-3 py-2 rounded-pill">Failed</span>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button 
                                        class="btn btn-sm btn-outline-primary rounded-pill"
                                        onclick="openNotesModal({{ $student->id }}, '{{ addslashes($student->last_name) }}, {{ addslashes($student->first_name) }}', '{{ addslashes($notes) }}')"
                                        data-bs-toggle="tooltip"
                                        title="{{ $notes ? 'Edit note' : 'Add note' }}">
                                        <i class="bi {{ $notes ? 'bi-pencil-square' : 'bi-plus-circle' }}"></i>
                                        {{ $notes ? 'Edit' : 'Add' }}
                                    </button>
                                    @if($notes)
                                        <div class="small text-muted mt-1" style="max-width: 150px;">
                                            {{ Str::limit($notes, 30) }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(!empty($selectedSubjectId))
            <div class="text-center text-muted mt-8 bg-warning bg-opacity-25 border border-warning px-6 py-4 rounded-4">
                No students found for this subject.
            </div>
        @endif
    @endif
</div>

{{-- Notes Modal --}}
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="notesModalLabel">
                    <i class="bi bi-sticky me-2"></i>Grade Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Student:</label>
                    <p class="text-muted" id="studentName"></p>
                </div>
                <div class="mb-3">
                    <label for="notesTextarea" class="form-label fw-semibold">Notes:</label>
                    <textarea 
                        class="form-control" 
                        id="notesTextarea" 
                        rows="5" 
                        maxlength="1000"
                        placeholder="Enter your notes about this student's performance..."></textarea>
                    <div class="form-text">
                        <span id="charCount">0</span>/1000 characters
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveNotes()">
                    <i class="bi bi-save me-1"></i>Save Notes
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentStudentId = null;
const selectedSubjectId = {{ $selectedSubjectId ?? 'null' }};

function openNotesModal(studentId, studentName, notes) {
    currentStudentId = studentId;
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('notesTextarea').value = notes || '';
    updateCharCount();
    
    const modal = new bootstrap.Modal(document.getElementById('notesModal'));
    modal.show();
}

function updateCharCount() {
    const textarea = document.getElementById('notesTextarea');
    const charCount = document.getElementById('charCount');
    charCount.textContent = textarea.value.length;
}

document.getElementById('notesTextarea').addEventListener('input', updateCharCount);

async function saveNotes() {
    if (!currentStudentId || !selectedSubjectId) {
        alert('Error: Missing student or subject information');
        return;
    }

    const notes = document.getElementById('notesTextarea').value;
    const saveButton = event.target;
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    try {
        const response = await fetch('{{ route("chairperson.saveGradeNotes") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                student_id: currentStudentId,
                subject_id: selectedSubjectId,
                notes: notes
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('notesModal')).hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            // Reload page to show updated notes
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            throw new Error(data.error || 'Failed to save notes');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to save notes. Please try again.'
        });
    } finally {
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="bi bi-save me-1"></i>Save Notes';
    }
}
</script>
@endpush
