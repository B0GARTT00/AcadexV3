@extends('layouts.app')

@section('content')
<style>
    .import-courses-wrapper {
        min-height: 100vh;
        background-color: #EAF8E7;
        padding: 0;
        margin: 0;
    }

    .import-courses-container {
        max-width: 100%;
        padding: 2rem 2rem;
    }

    .page-title {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid rgba(77, 166, 116, 0.2);
    }

    .page-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
        display: flex;
        align-items: center;
    }

    .page-title h1 i {
        color: #198754;
        font-size: 2rem;
        margin-right: 0.75rem;
    }

    .page-subtitle {
        color: #6c757d;
        font-size: 0.875rem;
        margin: 0;
    }

    /* Breadcrumb Styling */
    .breadcrumb {
        background-color: transparent;
        padding: 0.75rem 0;
    }

    .breadcrumb-item a {
        color: #4da674;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .breadcrumb-item a.active {
        text-decoration: underline;
        text-underline-offset: 4px;
        font-weight: 600;
    }

    .breadcrumb-item a:hover {
        color: #3d8a5e;
        text-decoration: underline;
    }

    .breadcrumb-item.active {
        color: #6c757d;
        font-weight: 500;
    }

    .breadcrumb-item + .breadcrumb-item::before {
        color: #4da674;
    }

    /* Enhanced Card Styling */
    .subject-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .subject-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }

    .subject-card:hover::before {
        left: 100%;
    }

    .subject-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 24px rgba(77, 166, 116, 0.3) !important;
    }

    .subject-card .position-relative {
        background: linear-gradient(135deg, #4da674 0%, #3d8a5e 100%) !important;
    }

    .subject-card:hover .position-relative {
        background: linear-gradient(135deg, #3d8a5e 0%, #2d6a4e 100%) !important;
    }

    .subject-circle {
        transition: all 0.3s ease;
    }

    .subject-card:hover .subject-circle {
        transform: translate(-50%, -50%) rotate(5deg) scale(1.1) !important;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
    }

    .subject-card .card-body {
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
        transition: background 0.3s;
    }

    .subject-card:hover .card-body {
        background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    }

    .subject-card h6 {
        transition: color 0.3s;
    }

    .subject-card:hover h6 {
        color: #4da674 !important;
    }

    .subject-card .badge {
        transition: all 0.3s;
    }

    .subject-card:hover .badge {
        background-color: #4da674 !important;
        transform: scale(1.05);
    }

    /* Add ripple effect */
    @keyframes ripple {
        0% {
            transform: scale(0);
            opacity: 1;
        }
        100% {
            transform: scale(4);
            opacity: 0;
        }
    }

    .subject-card::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(77, 166, 116, 0.5);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .subject-card:active::after {
        width: 300px;
        height: 300px;
        opacity: 0;
        transition: 0s;
    }
</style>

<div class="import-courses-wrapper">
    <div class="import-courses-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>
                <i class="bi bi-bar-chart-fill"></i>
                Students' Final Grades
            </h1>
            <p class="page-subtitle">Select an instructor and subject to view students' final grades</p>
        </div>

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
                            <th class="text-center" style="min-width: 200px;">Notes</th>
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
                                
                                // Get final grade record for notes
                                $finalGradeRecord = $student->finalGrades->first();
                                $notes = $finalGradeRecord->notes ?? '';
                                $finalGradeId = $finalGradeRecord->id ?? null;
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
                                    @if($finalGradeId)
                                        <button 
                                            class="btn btn-sm btn-outline-primary open-notes-modal"
                                            data-final-grade-id="{{ $finalGradeId }}"
                                            data-student-name="{{ $student->last_name }}, {{ $student->first_name }}"
                                            data-notes="{{ $notes }}"
                                            title="View/Edit notes"
                                        >
                                            <i class="bi bi-sticky"></i>
                                            @if($notes)
                                                <span class="badge bg-success ms-1">Has Notes</span>
                                            @else
                                                Add Notes
                                            @endif
                                        </button>
                                    @else
                                        <span class="text-muted fst-italic">No final grade yet</span>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="notesModalLabel">
                    <i class="bi bi-sticky me-2"></i>
                    Student Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Student Name:</label>
                    <p class="text-muted" id="studentNameDisplay"></p>
                </div>
                <div class="mb-3">
                    <label for="notesTextarea" class="form-label fw-semibold">Notes/Remarks:</label>
                    <textarea 
                        class="form-control" 
                        id="notesTextarea" 
                        rows="6" 
                        maxlength="1000"
                        placeholder="Enter notes or remarks for this student..."
                    ></textarea>
                    <div class="form-text">
                        <span id="charCount">0</span> / 1000 characters
                    </div>
                </div>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> The "Passed/Failed" remarks are automatically calculated based on grades and will not be affected by these notes.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="saveNotesBtn">
                    <i class="bi bi-check-circle me-1"></i>
                    Save Notes
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notesModal = new bootstrap.Modal(document.getElementById('notesModal'), {
        backdrop: false
    });
    const notesTextarea = document.getElementById('notesTextarea');
    const studentNameDisplay = document.getElementById('studentNameDisplay');
    const saveNotesBtn = document.getElementById('saveNotesBtn');
    const charCount = document.getElementById('charCount');
    let currentFinalGradeId = null;
    let currentButton = null;

    // Update character count
    notesTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });

    // Handle open notes modal button click
    document.querySelectorAll('.open-notes-modal').forEach(button => {
        button.addEventListener('click', function() {
            currentFinalGradeId = this.dataset.finalGradeId;
            currentButton = this;
            const studentName = this.dataset.studentName;
            const notes = this.dataset.notes || '';
            
            // Populate modal
            studentNameDisplay.textContent = studentName;
            notesTextarea.value = notes;
            charCount.textContent = notes.length;
            
            // Show modal
            notesModal.show();
        });
    });

    // Handle save notes button click
    saveNotesBtn.addEventListener('click', async function() {
        if (!currentFinalGradeId) return;
        
        const notes = notesTextarea.value.trim();
        
        // Disable button during save
        this.disabled = true;
        const originalHTML = this.innerHTML;
        this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
        
        try {
            const response = await fetch('{{ route('chairperson.saveGradeNotes') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    final_grade_id: currentFinalGradeId,
                    notes: notes
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Update button appearance
                if (currentButton) {
                    const badgeExists = currentButton.querySelector('.badge');
                    if (notes) {
                        if (!badgeExists) {
                            currentButton.innerHTML = `
                                <i class="bi bi-sticky"></i>
                                <span class="badge bg-success ms-1">Has Notes</span>
                            `;
                        }
                    } else {
                        currentButton.innerHTML = `
                            <i class="bi bi-sticky"></i>
                            Add Notes
                        `;
                    }
                    // Update data attribute for next time
                    currentButton.dataset.notes = notes;
                }
                
                // Show success feedback
                this.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Saved!';
                
                // Show toast notification
                showToast('Success', data.message, 'success');
                
                // Close modal after short delay
                setTimeout(() => {
                    notesModal.hide();
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                }, 1000);
            } else {
                throw new Error(data.message || 'Failed to save notes');
            }
        } catch (error) {
            console.error('Error saving notes:', error);
            this.innerHTML = originalHTML;
            this.disabled = false;
            showToast('Error', error.message || 'Failed to save notes. Please try again.', 'error');
        }
    });

    // Reset modal when closed
    document.getElementById('notesModal').addEventListener('hidden.bs.modal', function() {
        currentFinalGradeId = null;
        currentButton = null;
        notesTextarea.value = '';
        charCount.textContent = '0';
        studentNameDisplay.textContent = '';
    });
    
    // Helper function to show toast notifications
    function showToast(title, message, type = 'info') {
        // Create toast element
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}:</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        // Get or create toast container
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Add toast to container
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
});
</script>
@endpush

    </div>
</div>
@endsection
