@php
    function ordinalSuffix($n) {
        $suffixes = ['th', 'st', 'nd', 'rd'];
        $remainder = $n % 100;
        return $n . ($suffixes[($remainder - 20) % 10] ?? $suffixes[$remainder] ?? $suffixes[0]);
    }
@endphp

@extends('layouts.app')

@section('content')
<style>
    /* Container wrapper for consistent styling */
    .page-wrapper {
        background-color: #EAF8E7;
        min-height: 100vh;
        padding: 0;
        margin: 0;
    }

    .page-container {
        max-width: 100%;
        margin: 0;
        padding: 1.5rem 1rem;
    }

    /* Page title styling */
    .page-title {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid rgba(77, 166, 116, 0.2);
    }

    /* Content wrapper */
    .content-wrapper {
        background-color: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 0.5rem 1.5rem 1.5rem 1.5rem;
        margin-top: 1.5rem;
    }

    /* Alert improvements */
    .alert {
        border-radius: 0.5rem !important;
        border: 0 !important;
    }

    .alert-success {
        background-color: #d4edda !important;
        color: #155724 !important;
    }

    .alert-danger {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    }

    /* Tab styling */
    .nav-tabs {
        border-bottom: 2px solid #d0d0d0 !important;
        margin-bottom: 1.5rem;
        margin-top: 0.5rem;
    }

    .nav-tabs .nav-link {
        border: none !important;
        border-radius: 0.5rem 0.5rem 0 0;
        background-color: transparent;
        transition: all 0.3s ease;
        font-weight: 500;
        color: #666 !important;
        padding: 0.75rem 1.5rem !important;
    }

    .nav-tabs .nav-link:hover {
        background-color: rgba(77, 166, 116, 0.08) !important;
        color: #4da674 !important;
    }

    .nav-tabs .nav-link.active {
        background-color: transparent !important;
        color: #4da674 !important;
        border-bottom: 3px solid #4da674 !important;
    }

    /* Modal improvements for instructor assignment/unassign */
    .modal-header .bi {
        font-size: 1.3rem !important;
    }
    
    /* Ensure confirmation modals appear above instructor modal */
    #confirmBulkAssignModal,
    #confirmUnassignModal {
        z-index: 1060 !important;
    }
    
    /* Ensure modal backdrops appear in correct order */
    .modal-backdrop.show {
        z-index: 1055 !important;
    }
    
    #confirmBulkAssignModal.show ~ .modal-backdrop,
    #confirmUnassignModal.show ~ .modal-backdrop {
        z-index: 1059 !important;
    }
    
    /* Style instructor list items to match import confirmation */
    #assignList > div,
    #unassignList > div {
        padding: 0.5rem 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    #assignList > div:last-child,
    #unassignList > div:last-child {
        border-bottom: none;
    }

    #instructorListModal .modal-body {
        max-height: 60vh;
        overflow-y: auto;
    }

    #unassignList li {
        display: inline-block;
        margin-right: 0.5rem;
        margin-bottom: 0.4rem;
    }

    #unassignList .badge {
        padding: 0.5rem 0.75rem;
        border-radius: 10rem;
        background-color: rgba(0,0,0,0.05);
        color: #333;
    }

    /* Tab styling for instructor modal */
    #instructorListModal .nav-tabs {
        border-bottom: 2px solid #dee2e6;
    }

    #instructorListModal .nav-tabs .nav-link {
        border: none;
        border-radius: 0;
        background-color: transparent;
        color: #6c757d;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }

    #instructorListModal .nav-tabs .nav-link:hover {
        background-color: rgba(77, 166, 116, 0.05);
        color: #4da674;
        border-bottom-color: rgba(77, 166, 116, 0.3);
    }

    #instructorListModal .nav-tabs .nav-link.active {
        background-color: white;
        color: #4da674;
        border-bottom-color: #4da674;
    }

    #instructorListModal .tab-content {
        background-color: white;
    }

    #assignedInstructorsListTab::-webkit-scrollbar,
    #availableInstructorsListTab::-webkit-scrollbar {
        width: 6px;
    }

    #assignedInstructorsListTab::-webkit-scrollbar-track,
    #availableInstructorsListTab::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    #assignedInstructorsListTab::-webkit-scrollbar-thumb,
    #availableInstructorsListTab::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    #assignedInstructorsListTab::-webkit-scrollbar-thumb:hover,
    #availableInstructorsListTab::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Split-pane modal enhancements */
    .hover-bg:hover {
        background-color: rgba(77, 166, 116, 0.05) !important;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .btn-icon-only {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
        opacity: 0;
        transition: all 0.2s ease;
    }

    .form-check:hover .btn-icon-only {
        opacity: 1;
        animation: fadeInButton 0.3s ease;
    }

    @keyframes fadeInButton {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .btn-icon-only:hover {
        transform: scale(1.1);
    }

    .btn-icon-only i {
        font-size: 0.7rem;
    }

    #assignedInstructorsList, #availableInstructorsList {
        max-height: 400px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    #assignedInstructorsList::-webkit-scrollbar,
    #availableInstructorsList::-webkit-scrollbar {
        width: 6px;
    }

    #assignedInstructorsList::-webkit-scrollbar-track,
    #availableInstructorsList::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    #assignedInstructorsList::-webkit-scrollbar-thumb,
    #availableInstructorsList::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    #assignedInstructorsList::-webkit-scrollbar-thumb:hover,
    #availableInstructorsList::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

</style>

<div class="page-wrapper">
    <div class="page-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1 class="text-3xl font-bold mb-2 text-gray-800 flex items-center">
                <i class="bi bi-journal-plus text-success me-3 fs-2"></i>
                Manage Courses
            </h1>
            <p class="text-muted mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                View and manage instructor assignments for each course. Click the "Edit" button to add or remove instructors.
            </p>
        </div>

        {{-- Flash messages (server-side) will be displayed as toasts on load via JS --}}

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 0.5rem;">
                <!-- Year Level Tabs -->
                <ul class="nav nav-tabs mb-0" id="yearTabs" role="tablist" style="border-bottom: none !important;">
                    @for ($level = 1; $level <= 4; $level++)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $level === 1 ? 'active' : '' }}"
                               id="year-level-{{ $level }}"
                               data-bs-toggle="tab"
                               href="#level-{{ $level }}"
                               role="tab"
                               aria-controls="level-{{ $level }}"
                               aria-selected="{{ $level === 1 ? 'true' : 'false' }}">
                               {{ ordinalSuffix($level) }} Year
                            </a>
                        </li>
                    @endfor
                </ul>
                
                <!-- View Mode Switcher -->
                <div class="d-flex align-items-center">
                    <label for="viewMode" class="me-2 fw-semibold">
                        <i class="bi bi-eye me-1"></i>View Mode:
                    </label>
                    <select id="viewMode" class="form-select form-select-sm w-auto" onchange="toggleViewMode()"
                            data-bs-toggle="tooltip" title="Year View: See subjects by year level. Full View: See all subjects at once.">
                        <option value="year" selected>Year View</option>
                        <option value="full">Full View</option>
                    </select>
                </div>
            </div>

            <!-- YEAR VIEW (Tabbed) -->
            <div id="yearView">
        <div class="tab-content" id="yearTabsContent">
            @for ($level = 1; $level <= 4; $level++)
                @php
                    $subjectsByYear = $yearLevels[$level] ?? collect();
                @endphp

                <div class="tab-pane fade {{ $level === 1 ? 'show active' : '' }}"
                     id="level-{{ $level }}"
                     role="tabpanel"
                     aria-labelledby="year-level-{{ $level }}">
                        @if ($subjectsByYear->isNotEmpty())
                            <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                                <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Description</th>
                                        <th class="text-center">Assigned Instructor</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectsByYear as $subject)
                                        <tr data-subject-id="{{ $subject->id }}">
                                            <td>{{ $subject->subject_code }}</td>
                                            <td>{{ $subject->subject_description }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-success subject-view-btn" 
                                                    data-subject-id="{{ $subject->id }}"
                                                    onclick="openViewInstructorsModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')">
                                                    <i class="bi bi-people-fill text-success me-1"></i>
                                                    <span>View (<span class="view-count">{{ $subject->instructors_count ?? $subject->instructors->count() }}</span>)</span>
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <button class="btn btn-sm btn-success subject-edit-btn"
                                                        data-subject-id="{{ $subject->id }}"
                                                        onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}', 'edit')"
                                                        title="Edit Instructors">
                                                        <i class="bi bi-pencil-square"></i> Edit
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        @else
                            <div class="alert alert-warning shadow-sm rounded">
                                No subjects available for {{ ordinalSuffix($level) }} Year.
                            </div>
                        @endif
                </div>
            @endfor
        </div>
    </div>

    <!-- FULL VIEW (All Years) -->
    <div id="fullView" class="d-none">
        <div class="row g-4">
            @for ($level = 1; $level <= 4; $level++)
                @php
                    $subjectsByYear = $yearLevels[$level] ?? collect();
                @endphp
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 fw-semibold text-success">
                                    {{ ordinalSuffix($level) }} Year
                                </h5>
                                <span class="badge bg-success-subtle text-success ms-3">
                                    {{ $subjectsByYear->count() }} {{ Str::plural('subject', $subjectsByYear->count()) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if ($subjectsByYear->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-success">
                                            <tr>
                                                <th class="border-0 py-3">Course Code</th>
                                                <th class="border-0 py-3">Description</th>
                                                <th class="border-0 py-3 text-center">Assigned Instructors</th>
                                                <th class="border-0 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($subjectsByYear as $subject)
                                                <tr data-subject-id="{{ $subject->id }}">
                                                    <td class="fw-medium">{{ $subject->subject_code }}</td>
                                                    <td>{{ $subject->subject_description }}</td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-success subject-view-btn" 
                                                            data-subject-id="{{ $subject->id }}"
                                                            onclick="openViewInstructorsModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')">
                                                            <i class="bi bi-people-fill text-success me-1"></i>
                                                            <span>View (<span class="view-count">{{ $subject->instructors_count ?? $subject->instructors->count() }}</span>)</span>
                                                        </button>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center">
                                                            <button
                                                                class="btn btn-success btn-sm subject-edit-btn"
                                                                data-subject-id="{{ $subject->id }}"
                                                                onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}', 'edit')"
                                                                title="Edit Instructors">
                                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="bi bi-journal-x display-6"></i>
                                    </div>
                                    <p class="text-muted mb-0">No subjects available for {{ ordinalSuffix($level) }} Year.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endfor
        </div>
        </div>
    </div>
</div>

<!-- Confirm Bulk Assign Modal -->
<div class="modal fade" id="confirmBulkAssignModal" tabindex="-1" aria-labelledby="confirmBulkAssignModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title d-flex align-items-center" id="confirmBulkAssignModalLabel">
                    <i class="bi bi-file-earmark-check text-primary me-2 fs-4"></i>
                    <span>Confirm Assign</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small mb-1">Target Subject</label>
                    <div class="fw-semibold" id="assignTargetSubject">Loading...</div>
                </div>
                
                <div class="mb-3">
                    <div id="assignSelectionCount" class="text-muted small"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small mb-2">Selected Instructors</label>
                    <div id="assignList" class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Instructors will be listed here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmBulkAssignBtn">
                    <i class="bi bi-check-circle me-1"></i> Confirm Assign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Assigned Instructors Modal (Read-Only) -->
<div class="modal fade" id="viewInstructorsModal" tabindex="-1" aria-labelledby="viewInstructorsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title d-flex align-items-center" id="viewInstructorsModalLabel">
                    <i class="bi bi-people-fill text-success me-2 fs-4"></i>
                    <span>Assigned Instructors</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small mb-1">Subject</label>
                    <div class="fw-semibold" id="viewSubjectName">Loading...</div>
                </div>
                
                <div class="mb-3">
                    <div id="viewInstructorCount" class="text-muted small"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small mb-2">Instructors</label>
                    <div id="viewInstructorList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2 small">Loading instructors...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Instructor List Modal - Split Pane Design --}}
<div class="modal fade" id="instructorListModal" tabindex="-1" aria-labelledby="instructorListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-success text-white d-flex align-items-start">
                <div class="flex-grow-1">
                    <h5 class="modal-title mb-1" id="instructorListModalLabel">
                        <i class="bi bi-people-fill me-2"></i>
                        <span id="instructorListModalTitle">Manage Instructors</span>
                    </h5>
                    <div class="fw-semibold" style="font-size: 1.1rem;" id="instructorListSubjectName"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs px-3 pt-3 mb-0 bg-light border-bottom-0" role="tablist" style="margin-bottom: 0 !important;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assignPanel" type="button" role="tab" aria-controls="assignPanel" aria-selected="true">
                            <i class="bi bi-person-plus-fill me-2"></i>Assign Instructors
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="unassign-tab" data-bs-toggle="tab" data-bs-target="#unassignPanel" type="button" role="tab" aria-controls="unassignPanel" aria-selected="false">
                            <i class="bi bi-person-dash-fill me-2"></i>Unassign Instructors
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Assign Tab Panel -->
                    <div class="tab-pane fade show active" id="assignPanel" role="tabpanel" aria-labelledby="assign-tab">
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold text-primary">
                                    <i class="bi bi-person-plus-fill me-2"></i>Available Instructors
                                    <span class="badge bg-primary ms-2" id="availableCountTab">0</span>
                                </h6>
                                <small class="text-muted" data-bs-toggle="tooltip" title="These instructors can be added to teach this subject">
                                    <i class="bi bi-info-circle"></i>
                                </small>
                            </div>
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchAvailableTab" 
                                           placeholder="Search instructors..."
                                           data-bs-toggle="tooltip" title="Search by instructor name">
                                </div>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Sort options">
                                    <button type="button" class="btn btn-outline-secondary" id="sortAvailableAscTab" 
                                            data-bs-toggle="tooltip" title="Sort A to Z">
                                        <i class="bi bi-sort-alpha-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="sortAvailableDescTab"
                                            data-bs-toggle="tooltip" title="Sort Z to A">
                                        <i class="bi bi-sort-alpha-up"></i>
                                    </button>
                                </div>
                                <button class="btn btn-success ms-auto" id="assignSelectedBtnTab" disabled
                                        data-bs-toggle="tooltip" title="Check boxes first, then click to add selected instructors"
                                        style="padding: 0.5rem 1.5rem; font-size: 1rem;">
                                    <i class="bi bi-person-plus me-1"></i>Add Selected
                                </button>
                            </div>
                        </div>
                        <div class="p-4" style="max-height: 350px; overflow-y: auto;" id="availableInstructorsListTab">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="small mt-2 mb-0">Loading available instructors...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Unassign Tab Panel -->
                    <div class="tab-pane fade" id="unassignPanel" role="tabpanel" aria-labelledby="unassign-tab">
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold text-success">
                                    <i class="bi bi-person-check-fill me-2"></i>Assigned Instructors
                                    <span class="badge bg-success ms-2" id="assignedCountTab">0</span>
                                </h6>
                                <small class="text-muted" data-bs-toggle="tooltip" title="These instructors are currently teaching this subject">
                                    <i class="bi bi-info-circle"></i>
                                </small>
                            </div>
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchAssignedTab" 
                                           placeholder="Search instructors..."
                                           data-bs-toggle="tooltip" title="Search by instructor name">
                                </div>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Sort options">
                                    <button type="button" class="btn btn-outline-secondary" id="sortAssignedAscTab" 
                                            data-bs-toggle="tooltip" title="Sort A to Z">
                                        <i class="bi bi-sort-alpha-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="sortAssignedDescTab"
                                            data-bs-toggle="tooltip" title="Sort Z to A">
                                        <i class="bi bi-sort-alpha-up"></i>
                                    </button>
                                </div>
                                <button class="btn btn-outline-danger ms-auto" id="unassignSelectedBtnTab" disabled
                                        data-bs-toggle="tooltip" title="Check boxes first, then click to remove selected instructors"
                                        style="padding: 0.5rem 1.5rem; font-size: 1rem;">
                                    <i class="bi bi-person-dash me-1"></i>Remove Selected
                                </button>
                            </div>
                        </div>
                        <div class="p-4" style="max-height: 350px; overflow-y: auto;" id="assignedInstructorsListTab">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="small mt-2 mb-0">Loading assigned instructors...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden original split-pane (keeping for backwards compatibility) -->
                <div class="row g-0 d-none" id="splitPaneView">
                    <!-- Left Panel: Assigned Instructors -->
                    <div class="col-md-6 border-end">
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semibold text-success">
                                    <i class="bi bi-person-check-fill me-2"></i>Assigned Instructors
                                    <span class="badge bg-success ms-2" id="assignedCount">0</span>
                                </h6>
                                <small class="text-muted" data-bs-toggle="tooltip" title="These instructors are currently teaching this subject">
                                    <i class="bi bi-info-circle"></i>
                                </small>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="searchAssigned" 
                                       placeholder="Type to search instructors..."
                                       data-bs-toggle="tooltip" title="Search by instructor name">
                            </div>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" id="sortAssigned"
                                        data-bs-toggle="tooltip" title="Sort the list of instructors">
                                    <option value="name-asc">📊 Name (A-Z)</option>
                                    <option value="name-desc">📊 Name (Z-A)</option>
                                </select>
                                <button class="btn btn-sm btn-outline-danger" id="unassignSelectedBtn" disabled
                                        data-bs-toggle="tooltip" title="Check boxes first, then click to remove selected instructors">
                                    <i class="bi bi-person-dash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                        <div class="p-3" style="max-height: 400px; overflow-y: auto;" id="assignedInstructorsList">
                            <!-- Quick Help Banner -->
                            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" id="helpBanner">
                                <strong><i class="bi bi-lightbulb-fill me-1"></i> How it works:</strong>
                                <ul class="small mb-0 mt-1">
                                    <li>Hover over a name to see quick action buttons</li>
                                    <li>Check boxes to select multiple instructors</li>
                                    <li>Use search to find specific instructors quickly</li>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="small mt-2 mb-0">Loading assigned instructors...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Available Instructors -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semibold text-primary">
                                    <i class="bi bi-person-plus-fill me-2"></i>Available Instructors
                                    <span class="badge bg-primary ms-2" id="availableCount">0</span>
                                </h6>
                                <small class="text-muted" data-bs-toggle="tooltip" title="These instructors can be added to teach this subject">
                                    <i class="bi bi-info-circle"></i>
                                </small>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="searchAvailable" 
                                       placeholder="Type to search instructors..."
                                       data-bs-toggle="tooltip" title="Search by instructor name">
                            </div>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" id="sortAvailable"
                                        data-bs-toggle="tooltip" title="Sort the list of instructors">
                                    <option value="name-asc">📊 Name (A-Z)</option>
                                    <option value="name-desc">📊 Name (Z-A)</option>
                                </select>
                                <button class="btn btn-sm btn-success" id="assignSelectedBtn" disabled
                                        data-bs-toggle="tooltip" title="Check boxes first, then click to add selected instructors">
                                    <i class="bi bi-person-plus me-1"></i>Add
                                </button>
                            </div>
                        </div>
                        <div class="p-3" style="max-height: 400px; overflow-y: auto;" id="availableInstructorsList">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="small mt-2 mb-0">Loading available instructors...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <div class="text-muted small me-auto">
                    <i class="bi bi-info-circle me-1"></i>Select instructors using checkboxes for bulk operations
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
        <!-- Global Toast Container for top-right floating messages -->
        <div id="globalToastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
            <!-- Toasts will be dynamically injected here -->
        </div>
    </div>
</div>

{{-- Confirm Assign Modal --}}
<div class="modal fade" id="confirmAssignModal" tabindex="-1" aria-labelledby="confirmAssignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-success text-white d-flex align-items-start">
                <div>
                    <h5 class="modal-title mb-1" id="confirmAssignModalLabel">
                        <i class="bi bi-plus-circle-dotted me-2"></i> Assign Instructor
                    </h5>
                    <div class="small text-white-50" id="assignSubjectNameSmall"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Assigning instructor to: <span id="assignSubjectName" class="fw-semibold"></span></p>
                <p class="small text-muted mb-2">Select an instructor to assign for the active academic period.</p>
                <form id="assignInstructorForm" class="vstack gap-3">
                    @csrf
                    <input type="hidden" name="subject_id" id="assign_subject_id">
                    <div>
                        <label for="instructor_select" class="form-label">Select Instructor</label>
                        <select id="instructor_select" name="instructor_id" class="form-select" required>
                            <option value="">-- Choose Instructor --</option>
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="assignSubjectSubmit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Assign
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light d-none">
                <!-- Buttons moved inside form -->
            </div>
        </div>
    </div>
</div>

<!-- Confirm Unassign Modal -->
<div class="modal fade" id="confirmUnassignModal" tabindex="-1" aria-labelledby="confirmUnassignModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title d-flex align-items-center" id="confirmUnassignModalLabel">
                    <i class="bi bi-file-earmark-x text-danger me-2 fs-4"></i>
                    <span>Confirm Unassign</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small mb-1">Target Subject</label>
                    <div class="fw-semibold" id="unassignTargetSubject">Loading...</div>
                </div>
                
                <div class="mb-3">
                    <div id="unassignSelectionCount" class="text-muted small"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small mb-2">Selected Instructors</label>
                    <div id="unassignList" class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Instructors will be listed here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmUnassignBtn">
                    <i class="bi bi-trash me-1"></i> Confirm Unassign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Assign Selection Modal -->
<div class="modal fade" id="confirmAssignSelectionModal" tabindex="-1" aria-labelledby="confirmAssignSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="confirmAssignSelectionModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Confirm Assign
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to assign the following instructor(s) to <strong id="assignModalSubjectName"></strong>?</p>
                <ul id="assignList" class="mb-0 list-unstyled small text-muted" aria-live="polite"></ul>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAssignSelectedBtn">
                    <i class="bi bi-check-lg me-1"></i> Yes, assign
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentSubjectId = null;
    let currentModalMode = 'view'; // 'view', 'unassign', or 'edit'
    let currentUnassignInstructorIds = [];
    let currentUnassignInstructorNames = [];
    let currentAssignInstructorIds = [];
    let currentAssignInstructorNames = [];
    const buttonOrigMap = {};

    // Function to show Bootstrap toasts (top-right floating) for consistency
    function showNotification(type, message) {
        const toastContainer = document.getElementById('globalToastContainer') || createGlobalToastContainer();
        const toastId = `toast-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        const toastClass = type === 'success' ? 'text-bg-success' : 'text-bg-danger';

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center ${toastClass} border-0 shadow`;
        toastEl.role = 'alert';
        toastEl.ariaLive = 'assertive';
        toastEl.ariaAtomic = 'true';
        toastEl.id = toastId;
        // Enable pointer events on individual toasts
        toastEl.style.pointerEvents = 'auto';

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toastEl);

        const bsToast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
        bsToast.show();

        // Remove toast from DOM when fully hidden
        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function createGlobalToastContainer() {
        const container = document.createElement('div');
        container.id = 'globalToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        // Ensure toasts sit below modals (Bootstrap modals are z-index ~1050)
        container.style.zIndex = 1040;
        // Allow clicks to pass through the container when it has no toasts
        container.style.pointerEvents = 'none';
        document.body.appendChild(container);
        return container;
    }

    // Open a simple read-only modal to view assigned instructors
    function openViewInstructorsModal(subjectId, subjectName) {
        const modal = new bootstrap.Modal(document.getElementById('viewInstructorsModal'));
        
        // Set subject name
        document.getElementById('viewSubjectName').textContent = subjectName;
        
        // Show loading state
        const listContainer = document.getElementById('viewInstructorList');
        listContainer.innerHTML = `
            <div class="text-center text-muted py-3">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 small">Loading instructors...</div>
            </div>
        `;
        
        modal.show();
        
        // Fetch assigned instructors
        fetch(`/gecoordinator/subjects/${subjectId}/instructors`)
            .then(response => response.json())
            .then(data => {
                const countEl = document.getElementById('viewInstructorCount');
                // The endpoint returns an array directly, not an object with 'assigned' property
                const instructors = Array.isArray(data) ? data : [];
                const count = instructors.length;
                
                if (countEl) {
                    countEl.textContent = count === 0 ? 'No instructors assigned' : 
                        `${count} instructor${count !== 1 ? 's' : ''} assigned`;
                }
                
                if (count === 0) {
                    listContainer.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            <div>No instructors assigned to this subject yet.</div>
                        </div>
                    `;
                } else {
                    listContainer.innerHTML = '';
                    instructors.forEach(instructor => {
                        const div = document.createElement('div');
                        div.className = 'd-flex align-items-center';
                        div.innerHTML = `
                            <i class="bi bi-person-circle text-success me-2"></i>
                            <span>${instructor.name}</span>
                        `;
                        listContainer.appendChild(div);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching instructors:', error);
                listContainer.innerHTML = `
                    <div class="text-center text-danger py-3">
                        <i class="bi bi-exclamation-triangle fs-4 d-block mb-2"></i>
                        <div>Failed to load instructors</div>
                    </div>
                `;
            });
    }

    function openInstructorListModal(subjectId, subjectName, mode = 'view') {
        currentSubjectId = subjectId;
        currentModalMode = mode;
        document.getElementById('instructorListSubjectName').textContent = subjectName;
        
        // Update modal title
        const modalTitle = document.getElementById('instructorListModalTitle');
        modalTitle.textContent = 'Manage Instructors';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('instructorListModal'), {
            backdrop: false
        });
        modal.show();
        
        // Fetch both assigned and available instructors
        Promise.all([
            fetch(`/gecoordinator/subjects/${subjectId}/instructors`),
            fetch('/gecoordinator/available-instructors')
        ])
        .then(([assignedResp, availableResp]) => {
            if (!assignedResp.ok) {
                return assignedResp.json().then(err => { throw new Error(err.message || 'Failed to load assigned instructors'); }).catch(() => { throw new Error('Failed to load assigned instructors'); });
            }
            if (!availableResp.ok) {
                return availableResp.json().then(err => { throw new Error(err.message || 'Failed to load available instructors'); }).catch(() => { throw new Error('Failed to load available instructors'); });
            }
            return Promise.all([assignedResp.json(), availableResp.json()]);
        })
        .then(([assignedInstructors, availableInstructors]) => {
            renderSplitPaneInstructorList(assignedInstructors, availableInstructors);
        })
        .catch(error => {
            console.error('Error loading instructors:', error);
            const message = error.message || 'Failed to load instructors. Please try again.';
            document.getElementById('assignedInstructorsList').innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${message}
                </div>`;
            document.getElementById('availableInstructorsList').innerHTML = '';
        });
    }

    // Global instructor data for search/sort
    let assignedInstructorsData = [];
    let availableInstructorsData = [];

    function renderSplitPaneInstructorList(assignedInstructors, availableInstructors) {
        // Store data globally for search/sort
        const assignedIds = assignedInstructors.map(i => i.id);
        assignedInstructorsData = assignedInstructors;
        availableInstructorsData = availableInstructors.filter(i => !assignedIds.includes(i.id));
        
        // Update counts for tab version
        document.getElementById('assignedCountTab').textContent = assignedInstructorsData.length;
        document.getElementById('availableCountTab').textContent = availableInstructorsData.length;
        
        // Render both tab lists
        renderAssignedListTab(assignedInstructorsData);
        renderAvailableListTab(availableInstructorsData);
        
        // Setup event listeners for tabs
        setupTabEventListeners();
    }

<<<<<<< HEAD
                assignedInstructors.forEach(instructor => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    const instructorInfo = `
                        <div class="d-flex align-items-center">
                            <input class="form-check-input assigned-checkbox me-2" type="checkbox" value="${instructor.id}" id="assignedCheckbox-${instructor.id}">
                            <i class="bi bi-person-fill text-success me-2"></i>
                            <label for="assignedCheckbox-${instructor.id}" class="mb-0">${instructor.name}</label>
                        </div>`;
                    item.innerHTML = instructorInfo + `
                        <div>
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="confirmUnassignInstructor(${instructor.id}, '${instructor.name.replace(/'/g, "\\'")}')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>`;
                    listGroup.appendChild(item);
                });
                instructorList.appendChild(listGroup);

                // Setup after render
                setTimeout(() => {
                    const selectAll = document.getElementById('assignedSelectAll');
                    const unassignSelectedBtn = document.getElementById('unassignSelectedBtn');
                    selectAll?.addEventListener('change', () => {
                        document.querySelectorAll('.assigned-checkbox').forEach(ch => ch.checked = selectAll.checked);
                    });
                    unassignSelectedBtn?.addEventListener('click', () => {
                        const checkedEls = Array.from(document.querySelectorAll('.assigned-checkbox:checked'));
                        const ids = checkedEls.map(el => el.value);
                        const names = checkedEls.map(el => el.nextElementSibling?.textContent?.trim() || '');
                        if (ids.length === 0) { showNotification('error', 'No instructors selected'); return; }
                        // set spinner on the unassignSelectedBtn
                        unassignSelectedBtn.dataset.origHtml = unassignSelectedBtn.innerHTML;
                        buttonOrigMap['unassignSelectedBtn'] = unassignSelectedBtn.dataset.origHtml;
                        unassignSelectedBtn.disabled = true;
                        unassignSelectedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing...';
                        // mark pending button so that if the confirmation modal is cancelled we can restore it
                        pendingFormSubmitButton = unassignSelectedBtn;
                        confirmUnassignInstructor(ids, names);
                    });
                }, 0);
            }

            // Build assign form (only show instructors that are not already assigned)
            const assignedIds = assignedInstructors.map(i => i.id);
            const filteredAvailable = availableInstructors.filter(i => !assignedIds.includes(i.id));

            const assignCard = document.createElement('div');
            assignCard.className = 'card border-0';
            const assignBody = document.createElement('div');
            assignBody.className = 'card-body p-0';

            if (filteredAvailable.length > 0) {
                const formDiv = document.createElement('div');
                formDiv.className = 'mb-3';

                // Search field for available instructors
                const searchDiv = document.createElement('div');
                searchDiv.className = 'mb-2';
                searchDiv.innerHTML = `<input class="form-control form-control-sm" id="availableSearchInput" placeholder="Search available instructors..." aria-label="Search available instructors" />`;
                formDiv.appendChild(searchDiv);

                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'd-flex justify-content-between align-items-center mb-2';
                actionsDiv.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="availableSelectAll">
                        <label class="form-check-label small ms-1" for="availableSelectAll">Select All</label>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark me-3">${filteredAvailable.length} available</span>
                        <button class="btn btn-success btn-sm" id="assignSelectedBtn">Assign Selected</button>
                    </div>`;
                formDiv.appendChild(actionsDiv);

                const listGroupAvailable = document.createElement('div');
                listGroupAvailable.className = 'list-group';
                listGroupAvailable.id = 'availableListGroup';
                filteredAvailable.forEach(instr => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex align-items-center';
                    item.innerHTML = `
                        <input class="form-check-input available-checkbox me-2" type="checkbox" value="${instr.id}" id="availableCheckbox-${instr.id}">
                        <label for="availableCheckbox-${instr.id}" class="mb-0">${instr.name}</label>`;
                    listGroupAvailable.appendChild(item);
                });
                formDiv.appendChild(listGroupAvailable);
                assignBody.appendChild(formDiv);

                // Setup events
                setTimeout(() => {
                    const selectAllAv = document.getElementById('availableSelectAll');
                    const assignSelectedBtn = document.getElementById('assignSelectedBtn');
                    selectAllAv?.addEventListener('change', () => {
                        document.querySelectorAll('.available-checkbox').forEach(ch => ch.checked = selectAllAv.checked);
                    });
                    const availableSearchInput = document.getElementById('availableSearchInput');
                    availableSearchInput?.addEventListener('input', (e) => {
                        const q = e.target.value.toLowerCase();
                        document.querySelectorAll('#availableListGroup .list-group-item').forEach(item => {
                            const t = item.textContent.trim().toLowerCase();
                            item.style.display = t.includes(q) ? '' : 'none';
                        });
                    });
                    assignSelectedBtn?.addEventListener('click', () => {
                        const checkedEls = Array.from(document.querySelectorAll('.available-checkbox:checked'));
                        const ids = checkedEls.map(el => el.value);
                        const names = checkedEls.map(el => el.nextElementSibling?.textContent?.trim() || '');
                        if (ids.length === 0) { showNotification('error', 'No instructors selected'); return; }
                        // set spinner on the assignSelected button (dataset origHtml will be used to restore)
                        assignSelectedBtn.dataset.origHtml = assignSelectedBtn.innerHTML;
                        buttonOrigMap['assignSelectedBtn'] = assignSelectedBtn.dataset.origHtml;
                        assignSelectedBtn.disabled = true;
                        // mark pending button so the confirm modal can restore this button if canceled
                        pendingFormSubmitButton = assignSelectedBtn;
                        assignSelectedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing...';
                        confirmAssignInstructor(ids, names);
                    });
                    // focus the search field for keyboard users
                    const firstSearch = document.getElementById('availableSearchInput');
                    if (firstSearch) {
                        firstSearch.focus();
                    }
                }, 0);
                assignCard.appendChild(assignBody);
                instructorList.appendChild(assignCard);
            } else {
                // No available instructors
                const noAvailableAlert = document.createElement('div');
                noAvailableAlert.className = 'alert alert-secondary';
                noAvailableAlert.innerHTML = '<i class="bi bi-info-circle me-2"></i> No available instructors to assign.';
                assignCard.appendChild(assignBody);
                assignBody.appendChild(noAvailableAlert);
                instructorList.appendChild(assignCard);
            }
=======
    function renderAssignedListTab(instructors) {
        const container = document.getElementById('assignedInstructorsListTab');
        
        if (instructors.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                    <p class="fw-semibold mb-1">No Instructors Assigned Yet</p>
                    <p class="small text-muted mb-2">This subject doesn't have any instructors teaching it</p>
                    <p class="small text-primary mb-0">
                        <i class="bi bi-arrow-left me-1"></i> 
                        <strong>Switch to "Assign Instructors" tab</strong> to add instructors
                    </p>
                </div>`;
            document.getElementById('unassignSelectedBtnTab').disabled = true;
            return;
>>>>>>> origin/TN-012
        }
        
        container.innerHTML = `
            <div class="alert alert-success border-0 py-2 px-3 mb-3" role="alert">
                <small><i class="bi bi-hand-index-thumb me-1"></i> <strong>Check boxes</strong> to select instructors, then click the button above to remove them</small>
            </div>`;
        instructors.forEach(instructor => {
            const item = document.createElement('div');
            item.className = 'form-check mb-2 p-3 rounded hover-bg';
            item.dataset.instructorId = instructor.id;
            item.dataset.instructorName = instructor.name.toLowerCase();
            item.innerHTML = `
    <div class="d-flex align-items-center">
        <input class="form-check-input assigned-checkbox-tab me-2" type="checkbox" value="${instructor.id}" id="assigned-tab-${instructor.id}" title="Check to select" style="transform: scale(1.2);">
        <label class="form-check-label d-flex align-items-center mb-0" for="assigned-tab-${instructor.id}" style="cursor: pointer;">
            <i class="bi bi-person-fill text-success me-2"></i>
            <span>${instructor.name}</span>
        </label>
    </div>`;
            container.appendChild(item);
        });
        
        // Re-enable the button if there are instructors
        document.getElementById('unassignSelectedBtnTab').disabled = false;
    }

    function renderAvailableListTab(instructors) {
        const container = document.getElementById('availableInstructorsListTab');
        
        if (instructors.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 opacity-25 text-success"></i>
                    <p class="fw-semibold mb-1">All Instructors Assigned!</p>
                    <p class="small mb-0">All available instructors are already assigned to this subject</p>
                </div>`;
            document.getElementById('assignSelectedBtnTab').disabled = true;
            return;
        }
        
        container.innerHTML = `
            <div class="alert alert-primary border-0 py-2 px-3 mb-3" role="alert">
                <small><i class="bi bi-hand-index-thumb me-1"></i> <strong>Check boxes</strong> to select instructors, then click the button above to add them</small>
            </div>`;
        instructors.forEach(instructor => {
            const item = document.createElement('div');
            item.className = 'form-check mb-2 p-3 rounded hover-bg';
            item.dataset.instructorId = instructor.id;
            item.dataset.instructorName = instructor.name.toLowerCase();
            item.innerHTML = `
    <div class="d-flex align-items-center">
        <input class="form-check-input available-checkbox-tab me-2" type="checkbox" value="${instructor.id}" id="available-tab-${instructor.id}" title="Check to select" style="transform: scale(1.2);">
        <label class="form-check-label d-flex align-items-center mb-0" for="available-tab-${instructor.id}" style="cursor: pointer;">
            <i class="bi bi-person-plus text-primary me-2"></i>
            <span>${instructor.name}</span>
        </label>
    </div>`;
            container.appendChild(item);
        });
        
        // Re-enable the button if there are instructors
        document.getElementById('assignSelectedBtnTab').disabled = false;
    }

    function setupTabEventListeners() {
        // Search assigned tab
        const searchAssignedTab = document.getElementById('searchAssignedTab');
        const newSearchAssignedTab = searchAssignedTab.cloneNode(true);
        searchAssignedTab.parentNode.replaceChild(newSearchAssignedTab, searchAssignedTab);
        
        newSearchAssignedTab.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const items = document.querySelectorAll('#assignedInstructorsListTab .form-check');
            items.forEach(item => {
                const name = item.dataset.instructorName;
                item.style.display = name.includes(query) ? '' : 'none';
            });
        });
        
        // Search available tab
        const searchAvailableTab = document.getElementById('searchAvailableTab');
        const newSearchAvailableTab = searchAvailableTab.cloneNode(true);
        searchAvailableTab.parentNode.replaceChild(newSearchAvailableTab, searchAvailableTab);
        
        newSearchAvailableTab.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const items = document.querySelectorAll('#availableInstructorsListTab .form-check');
            items.forEach(item => {
                const name = item.dataset.instructorName;
                item.style.display = name.includes(query) ? '' : 'none';
            });
        });
        
        // Sort assigned tab - replace with button handlers
        const sortAssignedAscTab = document.getElementById('sortAssignedAscTab');
        const sortAssignedDescTab = document.getElementById('sortAssignedDescTab');
        const newSortAssignedAscTab = sortAssignedAscTab.cloneNode(true);
        const newSortAssignedDescTab = sortAssignedDescTab.cloneNode(true);
        sortAssignedAscTab.parentNode.replaceChild(newSortAssignedAscTab, sortAssignedAscTab);
        sortAssignedDescTab.parentNode.replaceChild(newSortAssignedDescTab, sortAssignedDescTab);
        
        newSortAssignedAscTab.addEventListener('click', () => {
            const sorted = [...assignedInstructorsData].sort((a, b) => a.name.localeCompare(b.name));
            renderAssignedListTab(sorted);
            newSortAssignedAscTab.classList.add('active');
            newSortAssignedDescTab.classList.remove('active');
        });
        
        newSortAssignedDescTab.addEventListener('click', () => {
            const sorted = [...assignedInstructorsData].sort((a, b) => b.name.localeCompare(a.name));
            renderAssignedListTab(sorted);
            newSortAssignedDescTab.classList.add('active');
            newSortAssignedAscTab.classList.remove('active');
        });
        
        // Sort available tab - replace with button handlers
        const sortAvailableAscTab = document.getElementById('sortAvailableAscTab');
        const sortAvailableDescTab = document.getElementById('sortAvailableDescTab');
        const newSortAvailableAscTab = sortAvailableAscTab.cloneNode(true);
        const newSortAvailableDescTab = sortAvailableDescTab.cloneNode(true);
        sortAvailableAscTab.parentNode.replaceChild(newSortAvailableAscTab, sortAvailableAscTab);
        sortAvailableDescTab.parentNode.replaceChild(newSortAvailableDescTab, sortAvailableDescTab);
        
        newSortAvailableAscTab.addEventListener('click', () => {
            const sorted = [...availableInstructorsData].sort((a, b) => a.name.localeCompare(b.name));
            renderAvailableListTab(sorted);
            newSortAvailableAscTab.classList.add('active');
            newSortAvailableDescTab.classList.remove('active');
        });
        
        newSortAvailableDescTab.addEventListener('click', () => {
            const sorted = [...availableInstructorsData].sort((a, b) => b.name.localeCompare(a.name));
            renderAvailableListTab(sorted);
            newSortAvailableDescTab.classList.add('active');
            newSortAvailableAscTab.classList.remove('active');
        });
        
        // Bulk unassign button
        const unassignBtnTab = document.getElementById('unassignSelectedBtnTab');
        const newUnassignBtnTab = unassignBtnTab.cloneNode(true);
        unassignBtnTab.parentNode.replaceChild(newUnassignBtnTab, unassignBtnTab);
        
        newUnassignBtnTab.addEventListener('click', () => {
            const checkedBoxes = document.querySelectorAll('.assigned-checkbox-tab:checked');
            if (checkedBoxes.length === 0) {
                showNotification('error', 'No instructors selected');
                return;
            }
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const names = Array.from(checkedBoxes).map(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                return label ? label.textContent.trim() : '';
            });
            confirmUnassignInstructor(ids, names);
        });
        
        // Bulk assign button
        const assignBtnTab = document.getElementById('assignSelectedBtnTab');
        const newAssignBtnTab = assignBtnTab.cloneNode(true);
        assignBtnTab.parentNode.replaceChild(newAssignBtnTab, assignBtnTab);
        
        newAssignBtnTab.addEventListener('click', () => {
            const checkedBoxes = document.querySelectorAll('.available-checkbox-tab:checked');
            if (checkedBoxes.length === 0) {
                showNotification('error', 'No instructors selected');
                return;
            }
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const names = Array.from(checkedBoxes).map(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                return label ? label.textContent.trim() : '';
            });
            // show a summary modal with selected instructors to confirm bulk assignment
            showBulkAssignModal(ids, names, newAssignBtnTab);
        });
        
        // Enable/disable bulk buttons based on selection (use event delegation on document)
        document.addEventListener('change', handleCheckboxChangeTab);
    }
    
    function handleCheckboxChangeTab(e) {
        if (e.target.classList.contains('assigned-checkbox-tab')) {
            const hasChecked = document.querySelectorAll('.assigned-checkbox-tab:checked').length > 0;
            const btn = document.getElementById('unassignSelectedBtnTab');
            if (btn) btn.disabled = !hasChecked;
        }
        if (e.target.classList.contains('available-checkbox-tab')) {
            const hasChecked = document.querySelectorAll('.available-checkbox-tab:checked').length > 0;
            const btn = document.getElementById('assignSelectedBtnTab');
            if (btn) btn.disabled = !hasChecked;
        }
    }

    function renderAssignedList(instructors) {
        const container = document.getElementById('assignedInstructorsList');
        
        if (instructors.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                    <p class="fw-semibold mb-1">No Instructors Assigned Yet</p>
                    <p class="small mb-0">
                        <i class="bi bi-arrow-right"></i> Select instructors from the right panel to assign them
                    </p>
                </div>`;
            document.getElementById('unassignSelectedBtn').disabled = true;
            return;
        }
        
        container.innerHTML = `
            <div class="alert alert-success border-0 py-2 px-3 mb-3" role="alert">
                <small><i class="bi bi-hand-index-thumb me-1"></i> <strong>Hover</strong> over a name to quickly remove, or <strong>check boxes</strong> to remove multiple</small>
            </div>`;
        instructors.forEach(instructor => {
            const item = document.createElement('div');
            item.className = 'form-check mb-2 p-2 rounded hover-bg';
            item.dataset.instructorId = instructor.id;
            item.dataset.instructorName = instructor.name.toLowerCase();
            item.innerHTML = `
                <input class="form-check-input assigned-checkbox" type="checkbox" value="${instructor.id}" id="assigned-${instructor.id}" title="Check to select">
                <label class="form-check-label d-flex align-items-center w-100" for="assigned-${instructor.id}" style="cursor: pointer;">
                    <i class="bi bi-person-fill text-success me-2"></i>
                    <span class="flex-grow-1">${instructor.name}</span>
                    <button class="btn btn-sm btn-outline-danger btn-icon-only" 
                            title="Quick remove - Click the X button"
                            onclick="event.stopPropagation(); quickUnassign(${instructor.id}, '${instructor.name.replace(/'/g, "\\'")}')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </label>`;
            container.appendChild(item);
        });
        
        // Re-enable the button if there are instructors
        document.getElementById('unassignSelectedBtn').disabled = false;
    }

    function renderAvailableList(instructors) {
        const container = document.getElementById('availableInstructorsList');
        
        if (instructors.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 opacity-50 text-success"></i>
                    <p class="fw-semibold mb-1">All Instructors Assigned!</p>
                    <p class="small mb-0">
                        All available instructors are already assigned to this subject
                    </p>
                </div>`;
            document.getElementById('assignSelectedBtn').disabled = true;
            return;
        }
        
        container.innerHTML = `
            <div class="alert alert-primary border-0 py-2 px-3 mb-3" role="alert">
                <small><i class="bi bi-hand-index-thumb me-1"></i> <strong>Hover</strong> over a name to quickly add, or <strong>check boxes</strong> to add multiple</small>
            </div>`;
        instructors.forEach(instructor => {
            const item = document.createElement('div');
            item.className = 'form-check mb-2 p-2 rounded hover-bg';
            item.dataset.instructorId = instructor.id;
            item.dataset.instructorName = instructor.name.toLowerCase();
            item.innerHTML = `
                <input class="form-check-input available-checkbox" type="checkbox" value="${instructor.id}" id="available-${instructor.id}" title="Check to select">
                <label class="form-check-label d-flex align-items-center w-100" for="available-${instructor.id}" style="cursor: pointer;">
                    <i class="bi bi-person-plus text-primary me-2"></i>
                    <span class="flex-grow-1">${instructor.name}</span>
                    <button class="btn btn-sm btn-success btn-icon-only" 
                            title="Quick assign - Click + to instantly add"
                            onclick="event.stopPropagation(); quickAssign(${instructor.id}, '${instructor.name.replace(/'/g, "\\'")}')">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </label>`;
            container.appendChild(item);
        });
        
        // Re-enable the button if there are instructors
        document.getElementById('assignSelectedBtn').disabled = false;
    }

    function setupSplitPaneEventListeners() {
        // Remove old listeners by cloning and replacing elements
        const searchAssigned = document.getElementById('searchAssigned');
        const searchAvailable = document.getElementById('searchAvailable');
        const sortAssigned = document.getElementById('sortAssigned');
        const sortAvailable = document.getElementById('sortAvailable');
        const unassignBtn = document.getElementById('unassignSelectedBtn');
        const assignBtn = document.getElementById('assignSelectedBtn');
        
        // Clone to remove all event listeners
        const newSearchAssigned = searchAssigned.cloneNode(true);
        const newSearchAvailable = searchAvailable.cloneNode(true);
        const newSortAssigned = sortAssigned.cloneNode(true);
        const newSortAvailable = sortAvailable.cloneNode(true);
        const newUnassignBtn = unassignBtn.cloneNode(true);
        const newAssignBtn = assignBtn.cloneNode(true);
        
        searchAssigned.parentNode.replaceChild(newSearchAssigned, searchAssigned);
        searchAvailable.parentNode.replaceChild(newSearchAvailable, searchAvailable);
        sortAssigned.parentNode.replaceChild(newSortAssigned, sortAssigned);
        sortAvailable.parentNode.replaceChild(newSortAvailable, sortAvailable);
        unassignBtn.parentNode.replaceChild(newUnassignBtn, unassignBtn);
        assignBtn.parentNode.replaceChild(newAssignBtn, assignBtn);
        
        // Search assigned
        newSearchAssigned.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const items = document.querySelectorAll('#assignedInstructorsList .form-check');
            items.forEach(item => {
                const name = item.dataset.instructorName;
                item.style.display = name.includes(query) ? '' : 'none';
            });
        });
        
        // Search available
        newSearchAvailable.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const items = document.querySelectorAll('#availableInstructorsList .form-check');
            items.forEach(item => {
                const name = item.dataset.instructorName;
                item.style.display = name.includes(query) ? '' : 'none';
            });
        });
        
        // Sort assigned
        newSortAssigned.addEventListener('change', (e) => {
            const sortType = e.target.value;
            const sorted = [...assignedInstructorsData].sort((a, b) => {
                if (sortType === 'name-asc') return a.name.localeCompare(b.name);
                if (sortType === 'name-desc') return b.name.localeCompare(a.name);
                return 0;
            });
            renderAssignedList(sorted);
        });
        
        // Sort available
        newSortAvailable.addEventListener('change', (e) => {
            const sortType = e.target.value;
            const sorted = [...availableInstructorsData].sort((a, b) => {
                if (sortType === 'name-asc') return a.name.localeCompare(b.name);
                if (sortType === 'name-desc') return b.name.localeCompare(a.name);
                return 0;
            });
            renderAvailableList(sorted);
        });
        
        // Bulk unassign button
        newUnassignBtn.addEventListener('click', () => {
            const checkedBoxes = document.querySelectorAll('.assigned-checkbox:checked');
            if (checkedBoxes.length === 0) {
                showNotification('error', 'No instructors selected');
                return;
            }
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const names = Array.from(checkedBoxes).map(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                return label ? label.textContent.trim() : '';
            });
            confirmUnassignInstructor(ids, names);
        });
        
        // Bulk assign button
        newAssignBtn.addEventListener('click', () => {
            const checkedBoxes = document.querySelectorAll('.available-checkbox:checked');
            if (checkedBoxes.length === 0) {
                showNotification('error', 'No instructors selected');
                return;
            }
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const names = Array.from(checkedBoxes).map(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                return label ? label.textContent.trim() : '';
            });
            // show confirmation modal for bulk assign
            showBulkAssignModal(ids, names, newAssignBtn);
        });
        
        // Enable/disable bulk buttons based on selection (use event delegation on document)
        document.addEventListener('change', handleCheckboxChange);
    }
    
    function handleCheckboxChange(e) {
        if (e.target.classList.contains('assigned-checkbox')) {
            const hasChecked = document.querySelectorAll('.assigned-checkbox:checked').length > 0;
            const btn = document.getElementById('unassignSelectedBtn');
            if (btn) btn.disabled = !hasChecked;
        }
        if (e.target.classList.contains('available-checkbox')) {
            const hasChecked = document.querySelectorAll('.available-checkbox:checked').length > 0;
            const btn = document.getElementById('assignSelectedBtn');
            if (btn) btn.disabled = !hasChecked;
        }
    }

    function quickUnassign(instructorId, instructorName) {
        confirmUnassignInstructor([instructorId], [instructorName]);
    }

    function quickAssign(instructorId, instructorName) {
        const btn = event.target.closest('button');
        btn.disabled = true;
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        assignMultipleInstructors(currentSubjectId, [instructorId], btn);
    }

        function assignInstructorInline(subjectId, instructorId, button) {
            const btn = findButtonElement(button, 'assignSingleBtn');
            if (btn) {
                btn.disabled = true;
                btn.dataset.origHtml = btn.dataset.origHtml || btn.innerHTML;
                if (btn.id) buttonOrigMap[btn.id] = btn.dataset.origHtml;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Assigning...';
            }

            const formData = new FormData();
            formData.append('subject_id', subjectId);
            formData.append('instructor_id', instructorId);
            fetch('{{ route("gecoordinator.assignInstructor") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('success', data.message || 'Instructor assigned successfully!');
                    // Refresh the modal content
                    openInstructorListModal(subjectId, document.getElementById('instructorListSubjectName').textContent, 'edit');
                    // Update the view count in the table
                    refreshSubjectInstructorCount(subjectId);
                } else {
                    throw new Error(data.message || 'Failed to assign instructor');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', error.message || 'Failed to assign instructor');
            })
            .finally(() => {
                const enableBtn = findButtonElement(button, 'assignSingleBtn');
                if (enableBtn) {
                    enableBtn.disabled = false;
                    enableBtn.innerHTML = enableBtn.dataset.origHtml || buttonOrigMap['assignSingleBtn'] || enableBtn.innerHTML;
                    delete enableBtn.dataset.origHtml;
                }
            });
        }
    
    function closeInstructorListModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('instructorListModal'));
        if (modal) {
            modal.hide();
        }
        currentSubjectId = null;
    }
    
    function confirmUnassignInstructor(instructorIdOrArray, instructorNameOrArray = null) {
        // Normalize to arrays
        if (Array.isArray(instructorIdOrArray)) {
            currentUnassignInstructorIds = instructorIdOrArray;
            currentUnassignInstructorNames = Array.isArray(instructorNameOrArray) ? instructorNameOrArray : [];
        } else {
            currentUnassignInstructorIds = [instructorIdOrArray];
            currentUnassignInstructorNames = [instructorNameOrArray || ''];
        }

        // Populate subject name
        const subjectNameEl = document.getElementById('unassignTargetSubject');
        const subjectName = document.getElementById('instructorListSubjectName')?.textContent || 'Unknown Subject';
        if (subjectNameEl) subjectNameEl.textContent = subjectName;

        // Update modal content list (render as simple list items)
        const list = document.getElementById('unassignList');
        const countEl = document.getElementById('unassignSelectionCount');
        if (list) {
            list.innerHTML = '';
            currentUnassignInstructorNames.forEach(n => {
                const div = document.createElement('div');
                div.textContent = n;
                list.appendChild(div);
            });
            if (countEl) countEl.textContent = `${currentUnassignInstructorNames.length} instructor(s) will be unassigned`;
        }

        // Temporarily hide center toast if present
        const centerToast = document.getElementById('centerToastContainer');
        if (centerToast) centerToast.style.display = 'none';

        // Show the confirmation modal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmUnassignModal'), {
            backdrop: 'static',
            keyboard: false
        });
        confirmModal.show();
    }

    // Show a confirmation summary modal for bulk assign
    function showBulkAssignModal(ids, names, callingBtn) {
        // Populate subject name
        const subjectNameEl = document.getElementById('assignTargetSubject');
        const subjectName = document.getElementById('instructorListSubjectName')?.textContent || 'Unknown Subject';
        if (subjectNameEl) subjectNameEl.textContent = subjectName;
        
        // Render the selected instructors as simple list items
        const list = document.getElementById('assignList');
        const countEl = document.getElementById('assignSelectionCount');
        if (list) {
            list.innerHTML = '';
            names.forEach(n => {
                const div = document.createElement('div');
                div.textContent = n;
                list.appendChild(div);
            });
            if (countEl) countEl.textContent = `${names.length} instructor(s) will be assigned`;
        }

        // Temporarily hide center toast if present to avoid overlay
        const centerToast = document.getElementById('centerToastContainer');
        if (centerToast) centerToast.style.display = 'none';

        const confirmModal = new bootstrap.Modal(document.getElementById('confirmBulkAssignModal'), {
            backdrop: 'static',
            keyboard: false
        });
        confirmModal.show();

        window.bulkAssignInstructorIds = ids;
        window.bulkAssignCallerBtn = callingBtn;
    }
    
    // Handle the confirm unassign button click
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('confirmUnassignBtn').addEventListener('click', function() {
            if (!currentUnassignInstructorIds || currentUnassignInstructorIds.length === 0 || !currentSubjectId) {
                showNotification('error', 'Missing instructor or subject information');
                return;
            }

            // Disable the button to prevent double clicks
            this.disabled = true;
            const origHtml = this.innerHTML;
            buttonOrigMap['confirmUnassignBtn'] = origHtml;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';

            // Hide the confirmation modal
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmUnassignModal'));
            confirmModal.hide();
            
            // Perform the unassign operation for all selected instructors
            Promise.all(currentUnassignInstructorIds.map(id => {
                return fetch('{{ route("gecoordinator.unassignInstructor") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ subject_id: currentSubjectId, instructor_id: id })
                }).then(res => {
                    if (!res.ok) {
                        return res.json().then(err => { throw new Error(err.message || 'Failed to unassign instructor'); }).catch(() => { throw new Error('Failed to unassign instructor'); });
                    }
                    return res.json();
                });
            }))
            .then(results => {
                const successCount = results.filter(r => r && r.success).length;
                if (successCount === 0) throw new Error('No instructor was unassigned');
                showNotification('success', `${successCount} instructor(s) unassigned successfully.`);
                // Refresh the split-pane modal
                setTimeout(() => {
                    openInstructorListModal(currentSubjectId, document.getElementById('instructorListSubjectName').textContent, 'view');
                    refreshSubjectInstructorCount(currentSubjectId);
                }, 400);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', error.message || 'Failed to unassign instructor');
                // Reload the instructor list
                setTimeout(() => {
                    openInstructorListModal(currentSubjectId, document.getElementById('instructorListSubjectName').textContent);
                }, 1000);
            })
            .finally(() => {
                // Re-enable the button
                this.disabled = false;
                this.innerHTML = origHtml || '<i class="bi bi-person-dash me-1"></i> Yes, unassign';
                // Reset selection state
                currentUnassignInstructorIds = [];
                currentUnassignInstructorNames = [];
                // Also ensure any 'unassignSelectedBtn' is re-enabled even if DOM was re-rendered
                const unassignBtn = document.getElementById('unassignSelectedBtn');
                if (unassignBtn) {
                    unassignBtn.disabled = false;
                        unassignBtn.innerHTML = unassignBtn.dataset.origHtml || buttonOrigMap['unassignSelectedBtn'] || unassignBtn.innerHTML;
                        if (unassignBtn.dataset.origHtml) delete unassignBtn.dataset.origHtml;
                }
                // Try to restore a set of known buttons in case the modal re-rendered them
                ['assignSelectedBtn', 'confirmAssignSelectedBtn', 'assignSingleBtn', 'unassignSelectedBtn', 'confirmUnassignBtn'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.disabled = false;
                        el.innerHTML = el.dataset.origHtml || buttonOrigMap[id] || el.innerHTML;
                        if (el.dataset.origHtml) delete el.dataset.origHtml;
                    }
                });
            });
        });

<<<<<<< HEAD
        // Confirm Assign Selected button handler
        const confirmAssignBtn = document.getElementById('confirmAssignSelectedBtn');
        if (confirmAssignBtn) {
            confirmAssignBtn.addEventListener('click', function() {
                if (!currentAssignInstructorIds || currentAssignInstructorIds.length === 0 || !currentSubjectId) {
                    showNotification('error', 'Missing instructor or subject information');
                    return;
                }
                this.disabled = true;
                const origHtml = this.dataset.origHtml = this.innerHTML;
                buttonOrigMap['confirmAssignSelectedBtn'] = origHtml;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('confirmAssignSelectionModal'));
                modalInstance.hide();

                // If triggered from form submission, use pendingFormSubmitButton as the target button for spinner; otherwise just use this button
                const targetBtn = pendingFormSubmitButton || this;
                assignMultipleInstructors(currentSubjectId, currentAssignInstructorIds, targetBtn);
                // Reset pending state
                pendingFormSubmitButton = null;
                currentAssignInstructorIds = [];
                currentAssignInstructorNames = [];
                this.disabled = false;
                this.innerHTML = origHtml;
            });
        }
        // Restore pending buttons if modals close without confirmation
        const confirmAssignModalEl = document.getElementById('confirmAssignSelectionModal');
        if (confirmAssignModalEl) {
            confirmAssignModalEl.addEventListener('hidden.bs.modal', function () {
                if (pendingFormSubmitButton) {
                    const btn = findButtonElement(pendingFormSubmitButton, 'assignSelectedBtn');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.origHtml || buttonOrigMap[btn.id] || buttonOrigMap['pendingFormSubmitButton'] || buttonOrigMap['assignSelectedBtn'] || btn.innerHTML;
                        if (btn.dataset.origHtml) delete btn.dataset.origHtml;
                    }
                    pendingFormSubmitButton = null;
                }
                // Also try to restore commonly used buttons
                ['assignSelectedBtn', 'confirmAssignSelectedBtn', 'assignSingleBtn'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.disabled = false;
                        el.innerHTML = el.dataset.origHtml || buttonOrigMap[id] || el.innerHTML;
                        if (el.dataset.origHtml) delete el.dataset.origHtml;
                    }
                });
                // remove pending stored HTMLs from map after restore
                ['assignSelectedBtn','confirmAssignSelectedBtn','assignSingleBtn','pendingFormSubmitButton'].forEach(k => { if (buttonOrigMap[k]) delete buttonOrigMap[k]; });
            });
        }

        const confirmUnassignModalEl = document.getElementById('confirmUnassignModal');
        if (confirmUnassignModalEl) {
            confirmUnassignModalEl.addEventListener('hidden.bs.modal', function () {
                if (pendingFormSubmitButton) {
                    const btn = findButtonElement(pendingFormSubmitButton, 'unassignSelectedBtn');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.origHtml || buttonOrigMap[btn.id] || buttonOrigMap['pendingFormSubmitButton'] || buttonOrigMap['unassignSelectedBtn'] || btn.innerHTML;
                        if (btn.dataset.origHtml) delete btn.dataset.origHtml;
                    }
                    pendingFormSubmitButton = null;
                }
                // Also try to restore commonly used unassign buttons
                ['unassignSelectedBtn', 'confirmUnassignBtn'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.disabled = false;
                        el.innerHTML = el.dataset.origHtml || buttonOrigMap[id] || el.innerHTML;
                        if (el.dataset.origHtml) delete el.dataset.origHtml;
                    }
                });
                ['unassignSelectedBtn','confirmUnassignBtn','pendingFormSubmitButton'].forEach(k => { if (buttonOrigMap[k]) delete buttonOrigMap[k]; });
=======
        // Clear selection display when the unassign modal is closed
        const unassignModalEl = document.getElementById('confirmUnassignModal');
        if (unassignModalEl) {
            unassignModalEl.addEventListener('hidden.bs.modal', () => {
                const list = document.getElementById('unassignList');
                const countEl = document.getElementById('unassignSelectionCount');
                if (list) list.innerHTML = '';
                if (countEl) countEl.textContent = '';
                currentUnassignInstructorIds = [];
                currentUnassignInstructorNames = [];
>>>>>>> origin/TN-012
            });
        }
    });

    function prepareAssignModal(subjectId, subjectName) {
        // For backward compatibility, open the instructor list modal in split-pane view mode
        openInstructorListModal(subjectId, subjectName, 'view');
    }

    function showAssignModal(subjectId, subjectName) {
        // Utility to open the small assign modal with subject details
        document.getElementById('assign_subject_id').value = subjectId;
        document.getElementById('assignSubjectName').textContent = subjectName;
        document.getElementById('assignSubjectNameSmall').textContent = subjectName;
        const assignModal = new bootstrap.Modal(document.getElementById('confirmAssignModal'));
        assignModal.show();
    }
    
    // Handle form submission for assigning instructors
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('assignInstructorForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form submission triggered');
                
                // Find the submit button inside the form
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton ? submitButton.innerHTML : '';
                console.log('Submit button found:', submitButton);
                
                // Show loading state
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i> Assigning...';
                }
                
                // Get the form data
                const formData = new FormData(form);
                
                // Save the context so we can use the confirmation modal
                pendingFormSubmitButton = submitButton;
                // Save original text and give visual feedback
                if (pendingFormSubmitButton) {
                    pendingFormSubmitButton.dataset.origHtml = originalButtonText;
                    if (pendingFormSubmitButton.id) buttonOrigMap[pendingFormSubmitButton.id] = originalButtonText;
                    else buttonOrigMap['pendingFormSubmitButton'] = originalButtonText;
                    pendingFormSubmitButton.disabled = true;
                    pendingFormSubmitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Assigning...';
                }
                // Open confirmation modal to confirm assignment
                const instructorId = formData.get('instructor_id');
                const instructorName = document.querySelector('#instructor_select option:checked')?.text || '';
                confirmAssignInstructor(instructorId, instructorName);
                return; // wait for confirmation
            });

                // Confirm bulk assign button handler (in the same DOMContentLoaded scope)
                const confirmBulkAssignBtn = document.getElementById('confirmBulkAssignBtn');
                if (confirmBulkAssignBtn) {
                    confirmBulkAssignBtn.addEventListener('click', function() {
                        if (!window.bulkAssignInstructorIds || window.bulkAssignInstructorIds.length === 0 || !currentSubjectId) {
                            showNotification('error', 'Missing instructor or subject information');
                            return;
                        }

                        // Disable to prevent double clicks
                        this.disabled = true;
                        const orig = this.innerHTML;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Assigning...';

                        // Hide the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmBulkAssignModal'));
                        if (modal) modal.hide();

                        // Perform assignment
                        const callerBtn = window.bulkAssignCallerBtn || this;
                        assignMultipleInstructors(currentSubjectId, window.bulkAssignInstructorIds, callerBtn);

                        // restore and cleanup 
                        setTimeout(() => {
                            this.disabled = false;
                            this.innerHTML = orig;
                        }, 800);
                    });
                }

                // Clear selection display when the assign modal is closed
                const bulkAssignModalEl = document.getElementById('confirmBulkAssignModal');
                if (bulkAssignModalEl) {
                    bulkAssignModalEl.addEventListener('hidden.bs.modal', () => {
                        const list = document.getElementById('assignList');
                        const countEl = document.getElementById('assignSelectionCount');
                        if (list) list.innerHTML = '';
                        if (countEl) countEl.textContent = '';
                        // restore center toast container if hidden
                        const centerToast = document.getElementById('centerToastContainer');
                        if (centerToast) centerToast.style.display = '';
                        window.bulkAssignInstructorIds = [];
                        window.bulkAssignCallerBtn = null;
                    });
                }
        }
    });

    function toggleViewMode() {
        const mode = document.getElementById('viewMode').value;
        const yearView = document.getElementById('yearView');
        const fullView = document.getElementById('fullView');

        if (mode === 'full') {
            yearView.classList.add('d-none');
            fullView.classList.remove('d-none');
        } else {
            yearView.classList.remove('d-none');
            fullView.classList.add('d-none');
        }
    }

    let pendingFormSubmitButton = null; // used when confirm comes from the form submission

    function confirmAssignInstructor(instructorIdOrArray, instructorNameOrArray = null) {
        if (Array.isArray(instructorIdOrArray)) {
            currentAssignInstructorIds = instructorIdOrArray;
            currentAssignInstructorNames = Array.isArray(instructorNameOrArray) ? instructorNameOrArray : [];
        } else {
            currentAssignInstructorIds = [instructorIdOrArray];
            currentAssignInstructorNames = [instructorNameOrArray || ''];
        }

        // Update modal title and list
        document.getElementById('assignModalSubjectName').textContent = document.getElementById('instructorListSubjectName')?.textContent || '';
        const list = document.getElementById('assignList');
        if (list) {
            list.innerHTML = '';
            currentAssignInstructorNames.forEach(n => {
                const li = document.createElement('li');
                li.textContent = n;
                list.appendChild(li);
            });
        }

        // Show confirm assign modal
        const modal = new bootstrap.Modal(document.getElementById('confirmAssignSelectionModal'), { backdrop: false });
        modal.show();
    }

    function assignMultipleInstructors(subjectId, instructorIds, button) {
        // Use a helper to get the current DOM button for stable enable/disable
        const btn = findButtonElement(button, 'assignSelectedBtn');
        if (btn) {
            btn.disabled = true;
            btn.dataset.origHtml = btn.dataset.origHtml || btn.innerHTML;
            if (btn.id) buttonOrigMap[btn.id] = btn.dataset.origHtml;
            else buttonOrigMap['assignSelectedBtn'] = btn.dataset.origHtml;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Assigning...';
        }

        Promise.all(instructorIds.map(id => {
            const formData = new FormData();
            formData.append('subject_id', subjectId);
            formData.append('instructor_id', id);
            return fetch('{{ route("gecoordinator.assignInstructor") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            }).then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw err; });
                }
                return res.json();
            });
        }))
        .then(results => {
            const successCount = results.filter(r => r && r.success).length;
            if (successCount === 0) throw new Error('No instructors were assigned');
            showNotification('success', `${successCount} instructor(s) assigned successfully!`);
            // Refresh the split-pane modal
            setTimeout(() => {
                openInstructorListModal(subjectId, document.getElementById('instructorListSubjectName').textContent, 'view');
                refreshSubjectInstructorCount(subjectId);
            }, 400);
        })
        .catch(error => {
            console.error('Error assigning multiple instructors:', error);
            showNotification('error', error.message || 'Failed to assign selected instructors');
        })
        .finally(() => {
            // Re-enable the freshest button element by id
            const enableBtn = findButtonElement(button, 'assignSelectedBtn');
            if (enableBtn) {
                enableBtn.disabled = false;
                enableBtn.innerHTML = enableBtn.dataset.origHtml || buttonOrigMap['assignSelectedBtn'] || enableBtn.innerHTML;
                delete enableBtn.dataset.origHtml;
            }
            // Also re-enable the confirm assign button if present
            const confirmAssignBtnEl = document.getElementById('confirmAssignSelectedBtn');
            if (confirmAssignBtnEl) {
                confirmAssignBtnEl.disabled = false;
                confirmAssignBtnEl.innerHTML = confirmAssignBtnEl.dataset.origHtml || buttonOrigMap['confirmAssignSelectedBtn'] || confirmAssignBtnEl.innerHTML;
                if (confirmAssignBtnEl.dataset.origHtml) delete confirmAssignBtnEl.dataset.origHtml;
            }
            // Try to restore several common buttons in case the modal re-rendered elements
            ['assignSelectedBtn', 'confirmAssignSelectedBtn', 'assignSingleBtn', 'unassignSelectedBtn'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.disabled = false;
                    el.innerHTML = el.dataset.origHtml || buttonOrigMap[id] || el.innerHTML;
                    if (el.dataset.origHtml) delete el.dataset.origHtml;
                }
            });
        });
    }

    function findButtonElement(buttonOrElement, fallbackId) {
        if (!buttonOrElement && fallbackId) {
            return document.getElementById(fallbackId) || null;
        }
        try {
            // If it's an ID string
            if (typeof buttonOrElement === 'string') {
                const el = document.getElementById(buttonOrElement);
                if (el) return el;
            }
            // If it has an id property, find by id (fresh DOM)
            if (buttonOrElement && buttonOrElement.id) {
                const el = document.getElementById(buttonOrElement.id);
                if (el) return el;
            }
            // If it's a DOM element and still in the document, return it
            if (buttonOrElement && buttonOrElement instanceof Element && document.body.contains(buttonOrElement)) {
                return buttonOrElement;
            }
            // Fallback to common ids
            if (fallbackId && document.getElementById(fallbackId)) {
                return document.getElementById(fallbackId);
            }
        } catch (e) {
            console.error('findButtonElement error', e);
        }
        return null;
    }

    // Global helper to update the instructor count for a subject
    function refreshSubjectInstructorCount(subjectId) {
        if (!subjectId) return;
        console.log('Refreshing instructor count for subject', subjectId);
        fetch(`/gecoordinator/subjects/${subjectId}/instructors`)
            .then(resp => {
                if (!resp.ok) return resp.json().then(err => { throw new Error(err.message || 'Failed to load instructors'); }).catch(() => { throw new Error('Failed to load instructors'); });
                return resp.json();
            })
            .then(list => {
                const count = Array.isArray(list) ? list.length : (list.length || 0);
                document.querySelectorAll('button.subject-view-btn[data-subject-id="' + subjectId + '"] .view-count').forEach(el => {
                    el.textContent = count;
                });
                // Highlight the row(s) to show a change
                document.querySelectorAll(`tr[data-subject-id="${subjectId}"]`).forEach(row => {
                    row.classList.add('subject-updated-highlight');
                    setTimeout(() => row.classList.remove('subject-updated-highlight'), 1200);
                });
                console.log('Updated link view-count elements for subject', subjectId, 'to', count);
            })
            .catch(err => {
                console.error('Error updating instructor count:', err);
            });
    }

    // Render server-side flash messages as toasts (session)
    document.addEventListener('DOMContentLoaded', function () {
        @if (session('success'))
            showNotification('success', @json(session('success')));
        @endif
        @if (session('error'))
            showNotification('error', @json(session('error')));
        @endif
        
        // Initialize Bootstrap tooltips for better UX
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

@push('styles')
<style>
    .bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.1);
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    .btn-outline-success:hover, .btn-outline-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
<<<<<<< HEAD
    .subject-updated-highlight {
        animation: subject-updated 1.2s ease-in-out;
    }
    @keyframes subject-updated {
        0% { background-color: rgba(198, 255, 208, 0.8); }
        50% { background-color: rgba(255, 255, 255, 0.9); }
        100% { background-color: transparent; }
=======
    .modal-header .fs-3 {
        font-size: 1.3rem;
>>>>>>> origin/TN-012
    }
</style>
@endpush
@endsection