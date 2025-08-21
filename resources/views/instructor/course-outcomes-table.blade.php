@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('instructor.course_outcomes.index') }}" class="text-decoration-none">Course Outcomes</a></li>
            @if(isset($selectedSubject))
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}
                </li>
            @endif
        </ol>
    </nav>

    {{-- Page Title --}}
    @if(isset($selectedSubject))
        <div class="mb-4">
            <h2 class="fw-bold text-dark">Subject: {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}</h2>
        </div>
    @endif

    {{-- Course Outcomes Management Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-bar-chart-fill text-success fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Course Outcomes Management</h5>
                                <p class="text-muted mb-0">
                                    Subject: {{ $selectedSubject->subject_code ?? 'N/A' }} - {{ $selectedSubject->subject_description ?? 'N/A' }}
                                    @if($currentPeriod)
                                        | {{ $currentPeriod->academic_year }} - {{ $currentPeriod->semester }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
                                <i class="bi bi-plus-circle me-2"></i>Add Course Outcome
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- No Data Available Section --}}
    @if(!$cos || $cos->count() == 0)
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-3">No Course Outcome Data Available</h4>
                        <p class="text-muted mb-4">
                            No course outcomes have been set for {{ $selectedSubject->subject_code ?? 'this subject' }} yet.
                        </p>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
                            <i class="bi bi-plus-circle me-2"></i>Create First Course Outcome
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Course Outcomes Table --}}
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-light border-0 py-3">
                        <h5 class="fw-bold mb-0">Course Outcomes List</h5>
                    </div>
                    <div class="card-body p-0 course-outcomes-table-container">
                        @if($cos->count() > 0)
                            <div class="table-responsive course-outcomes-table-scroll">
                                <table class="table table-hover align-middle mb-0 course-outcomes-table">
                                    <thead class="table-success">
                                        <tr>
                                            <th class="border-0 py-3 px-4 fw-semibold">
                                                <i class="bi bi-hash me-2"></i>CO Code
                                            </th>
                                            <th class="border-0 py-3 fw-semibold">
                                                <i class="bi bi-tag me-2"></i>Identifier
                                            </th>
                                            <th class="border-0 py-3 fw-semibold">
                                                <i class="bi bi-file-text me-2"></i>Description
                                            </th>
                                            <th class="border-0 py-3 fw-semibold text-center">
                                                <i class="bi bi-calendar-event me-2"></i>Academic Period
                                            </th>
                                            <th class="border-0 py-3 fw-semibold text-center">
                                                <i class="bi bi-percent me-2"></i>Target %
                                            </th>
                                            <th class="border-0 py-3 fw-semibold text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cos as $co)
                                            <tr class="border-bottom">
                                                <td class="fw-bold px-4 text-success">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                                            <i class="bi bi-mortarboard text-success"></i>
                                                        </div>
                                                        {{ $co->co_code }}
                                                    </div>
                                                </td>
                                                <td class="fw-semibold">{{ $co->co_identifier }}</td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;" title="{{ $co->description }}">
                                                        {{ $co->description }}
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($co->academicPeriod)
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                            {{ $co->academicPeriod->academic_year }} - {{ $co->academicPeriod->semester }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success fs-6 px-3 py-2">75%</span>
                                                </td>
                                                <td class="text-center px-4">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-outline-success btn-sm" 
                                                                onclick="openEditModal({{ $co->id }}, '{{ $co->co_code }}', '{{ $co->co_identifier }}', '{{ addslashes($co->description) }}')"
                                                                title="Edit Course Outcome">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="openDeleteModal({{ $co->id }}, '{{ $co->co_code }}')"
                                                                title="Delete Course Outcome">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            {{-- Results Counter --}}
                            @if($cos->count() > 5)
                                <div class="card-footer bg-light border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            Showing 5 of {{ $cos->count() }} course outcomes (scroll to see more)
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-arrow-down me-1"></i>Scrollable content
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="card-footer bg-light border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            Showing {{ $cos->count() }} of {{ $cos->count() }} course outcomes
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-check-circle me-1"></i>All items visible
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bi bi-mortarboard text-muted" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="text-muted mb-2">No Course Outcomes Found</h5>
                                <p class="text-muted mb-4">Get started by creating your first course outcome for this subject.</p>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
                                    <i class="bi bi-plus-circle me-2"></i>Create First Course Outcome
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Information Cards Section --}}
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="bi bi-question-circle-fill text-success fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">What are Course Outcomes?</h6>
                    </div>
                    <p class="text-muted mb-0">
                        Specific, measurable statements that describe what students should be able to demonstrate, know, or do by the end of the course.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="bi bi-lightbulb-fill text-warning fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Why Set Course Outcomes?</h6>
                    </div>
                    <ul class="text-muted mb-0 small">
                        <li>Track student learning progress</li>
                        <li>Align assessments with goals</li>
                        <li>Generate performance reports</li>
                        <li>Meet accreditation requirements</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="bi bi-gear-fill text-primary fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Getting Started</h6>
                    </div>
                    <p class="text-muted mb-0">
                        Click the button above to create your first course outcome. Define specific learning objectives that students should achieve.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Course Outcome Modal --}}
<div class="modal fade" id="addCourseOutcomeModal" tabindex="-1" aria-labelledby="addCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('instructor.course_outcomes.store') }}">
            @csrf
            <div class="modal-content shadow border-0 rounded-3">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addCourseOutcomeModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add Course Outcome
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">CO Code <span class="text-danger">*</span></label>
                                <input type="text" name="co_code" id="co_code" class="form-control" readonly style="background-color: #f8f9fa;" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Identifier <span class="text-danger">*</span></label>
                                <input type="text" name="co_identifier" id="co_identifier" class="form-control" readonly style="background-color: #f8f9fa;" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Enter the course outcome description..." required></textarea>
                    </div>
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id ?? request('subject_id') }}">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Add Outcome
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Course Outcome Modal --}}
<div class="modal fade" id="editCourseOutcomeModal" tabindex="-1" aria-labelledby="editCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-content shadow border-0 rounded-3">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editCourseOutcomeModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Edit Course Outcome
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">CO Code <span class="text-danger">*</span></label>
                                <input type="text" name="co_code" id="edit_co_code" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Identifier <span class="text-danger">*</span></label>
                                <input type="text" name="co_identifier" id="edit_co_identifier" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="edit_description" class="form-control" rows="4" placeholder="Enter the course outcome description..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Update Outcome
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteCourseOutcomeModal" tabindex="-1" aria-labelledby="deleteCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="deleteForm">
            @csrf
            @method('DELETE')
            <div class="modal-content shadow border-0 rounded-3">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title" id="deleteCourseOutcomeModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="mb-3">Are you sure you want to delete this course outcome?</h6>
                    <div class="alert alert-warning border-0">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle text-warning me-2"></i>
                            <div>
                                <strong>Course Outcome:</strong> <span id="delete_co_code" class="fw-bold text-danger"></span><br>
                                <small class="text-muted">This action cannot be undone and will remove all associated activities and scores.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Delete Permanently
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate CO Code and Identifier when modal is shown
    const modal = document.getElementById('addCourseOutcomeModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(e) {
            generateNextCOCode();
        });
    }

    function generateNextCOCode() {
        // Get subject code from the current subject selection
        @if(isset($selectedSubject))
            const subjectCode = '{{ $selectedSubject->subject_code }}';
        @else
            const subjectCode = '';
        @endif
        
        // Get existing course outcomes from the table
        const existingCOs = [];
        const coRows = document.querySelectorAll('tbody tr');
        
        coRows.forEach(row => {
            const coCodeCell = row.querySelector('td:first-child');
            if (coCodeCell) {
                const coCode = coCodeCell.textContent.trim();
                // Extract number from CO code (e.g., "CO1" -> 1)
                const match = coCode.match(/CO(\d+)/i);
                if (match) {
                    existingCOs.push(parseInt(match[1]));
                }
            }
        });

        // Determine next CO number
        let nextCONumber = 1;
        if (existingCOs.length > 0) {
            const maxCO = Math.max(...existingCOs);
            nextCONumber = maxCO + 1;
        }

        // Set the auto-generated values
        const coCodeInput = document.getElementById('co_code');
        const coIdentifierInput = document.getElementById('co_identifier');
        
        if (coCodeInput && coIdentifierInput) {
            const newCOCode = `CO${nextCONumber}`;
            const newIdentifier = subjectCode ? `${subjectCode}.${nextCONumber}` : `CO${nextCONumber}`;
            
            coCodeInput.value = newCOCode;
            coIdentifierInput.value = newIdentifier;
        }
    }
});

// Edit Modal Functions
function openEditModal(id, coCode, coIdentifier, description) {
    document.getElementById('edit_co_code').value = coCode;
    document.getElementById('edit_co_identifier').value = coIdentifier;
    document.getElementById('edit_description').value = description;
    
    // Set the form action URL
    document.getElementById('editForm').action = `/instructor/course_outcomes/${id}`;
    
    // Show the modal
    const editModal = new bootstrap.Modal(document.getElementById('editCourseOutcomeModal'));
    editModal.show();
}

// Delete Modal Functions
function openDeleteModal(id, coCode) {
    document.getElementById('delete_co_code').textContent = coCode;
    
    // Set the form action URL
    document.getElementById('deleteForm').action = `/instructor/course_outcomes/${id}`;
    
    // Show the modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCourseOutcomeModal'));
    deleteModal.show();
}
</script>
@endpush

@push('styles')
<style>
.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.table th {
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
}

.modal-content {
    border: none;
}

.form-control:focus, .form-select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

/* Success toast styling */
.toast {
    border-radius: 0.5rem;
}

.progress {
    border-radius: 0;
}

/* Fixed height table styling */
.course-outcomes-table-container {
    height: 350px;
    overflow: hidden;
}

.course-outcomes-table-scroll {
    height: 100%;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 #f8f9fa;
}

.course-outcomes-table-scroll::-webkit-scrollbar {
    width: 6px;
}

.course-outcomes-table-scroll::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 3px;
}

.course-outcomes-table-scroll::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.course-outcomes-table-scroll::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Ensure table rows have consistent height */
.course-outcomes-table tbody tr {
    height: 50px;
}

.course-outcomes-table thead tr {
    height: 50px;
}

.course-outcomes-table thead th {
    position: sticky;
    top: 0;
    background: #d1e7dd;
    z-index: 10;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endpush