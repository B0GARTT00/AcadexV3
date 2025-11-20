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
</style>

<div class="page-wrapper">
    <div class="page-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1 class="text-3xl font-bold mb-2 text-gray-800 flex items-center">
                <i class="bi bi-journal-plus text-success me-3 fs-2"></i>
                Manage Courses
            </h1>
            <p class="text-muted mb-0 small">Assign subjects to instructors and manage course assignments</p>
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
                    <label for="viewMode" class="me-2 fw-semibold">View Mode:</label>
                    <select id="viewMode" class="form-select form-select-sm w-auto" onchange="toggleViewMode()">
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
                                        <th>Assigned Instructor</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectsByYear as $subject)
                                        <tr>
                                            <td>{{ $subject->subject_code }}</td>
                                            <td>{{ $subject->subject_description }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-success subject-view-btn" 
                                                    data-subject-id="{{ $subject->id }}"
                                                    onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}', 'view')">
                                                    <i class="bi bi-people-fill text-success me-1"></i>
                                                    <span>View (<span class="view-count">{{ $subject->instructors_count ?? $subject->instructors->count() }}</span>)</span>
                                                </button>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="d-flex">
                                                        <button class="btn btn-sm btn-success me-2 subject-edit-btn"
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
                                                <th class="border-0 py-3">Assigned Instructors</th>
                                                <th class="border-0 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($subjectsByYear as $subject)
                                                <tr>
                                                    <td class="fw-medium">{{ $subject->subject_code }}</td>
                                                    <td>{{ $subject->subject_description }}</td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-success subject-view-btn" 
                                                            data-subject-id="{{ $subject->id }}"
                                                            onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}', 'view')">
                                                            <i class="bi bi-people-fill text-success me-1"></i>
                                                            <span>View (<span class="view-count">{{ $subject->instructors_count ?? $subject->instructors->count() }}</span>)</span>
                                                        </button>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex gap-2 justify-content-center">
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

{{-- Instructor List Modal --}}
<div class="modal fade" id="instructorListModal" tabindex="-1" aria-labelledby="instructorListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="instructorListModalLabel">
                    <span id="instructorListModalTitle">Instructors</span>
                    <span id="instructorListSubjectName" class="text-light opacity-75 ms-2"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="instructorList">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer bg-light">
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
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmAssignModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i> Assign Instructor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Assigning instructor to: <span id="assignSubjectName" class="fw-semibold"></span></p>
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
                        <button type="submit" class="btn btn-success">
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
<div class="modal fade" id="confirmUnassignModal" tabindex="-1" aria-labelledby="confirmUnassignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="confirmUnassignModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i> Confirm Unassign
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to unassign the selected instructor(s)?</p>
                <ul id="unassignList" class="mb-3 list-unstyled small text-muted" aria-live="polite"></ul>
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <div>This action cannot be undone.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmUnassignBtn">
                    <i class="bi bi-person-dash me-1"></i> Yes, unassign
                </button>
            </div>
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
        container.style.zIndex = 1100;
        document.body.appendChild(container);
        return container;
    }

    function openInstructorListModal(subjectId, subjectName, mode = 'view') {
        currentSubjectId = subjectId;
        currentModalMode = mode; // Store the mode (view or unassign)
        document.getElementById('instructorListSubjectName').textContent = subjectName;
        
        // Update modal title and initial content based on mode
        const modalTitle = document.getElementById('instructorListModalTitle');
        if (mode === 'unassign') {
            modalTitle.innerHTML = '<i class="bi bi-person-dash me-2"></i> Unassign Instructor';
        } else if (mode === 'edit') {
            modalTitle.innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Instructors';
        } else {
            modalTitle.innerHTML = '<i class="bi bi-people-fill me-2"></i> Assigned Instructors';
        }
        
        // Show loading state
        const instructorList = document.getElementById('instructorList');
        instructorList.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading instructors...</p>
            </div>`;
        
        // Show the modal using Bootstrap
        const modal = new bootstrap.Modal(document.getElementById('instructorListModal'), {
            backdrop: false
        });
        modal.show();
        
        // Fetch instructors for this subject
        if (mode === 'edit') {
            // In edit mode, fetch both assigned and available instructors
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
            .then(([instructors, available]) => {
                renderEditInstructorList(instructors, available);
            })
            .catch(error => {
                console.error('Error loading instructors:', error);
                const message = error.message || 'Failed to load instructors. Please try again.';
                instructorList.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${message}
                    </div>`;
            });
            return;
        }

        fetch(`/gecoordinator/subjects/${subjectId}/instructors`)
            .then(response => {
                if (!response.ok) {
                    // Try to parse JSON error if available
                    return response.json().then(err => {
                        const msg = err.message || err.error || 'Failed to load instructors';
                        throw new Error(msg);
                    }).catch(() => {
                        throw new Error('Failed to load instructors');
                    });
                }
                return response.json();
            })
            .then(instructors => {
                if (instructors.length === 0) {
                    instructorList.innerHTML = `
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No instructors assigned to this subject.
                        </div>`;
                    return;
                }
                
                const listGroup = document.createElement('div');
                listGroup.className = 'list-group';
                
                instructors.forEach(instructor => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    
                    const instructorInfo = `
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-fill text-success me-2"></i>
                            <span>${instructor.name}</span>
                        </div>`;
                    
                    if (mode === 'unassign') {
                        // Add unassign button (X) only in unassign mode
                        item.innerHTML = instructorInfo + `
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="confirmUnassignInstructor(${instructor.id}, '${instructor.name.replace(/'/g, "\\'")}')">
                                <i class="bi bi-x-lg"></i>
                            </button>`;
                    } else {
                        item.innerHTML = instructorInfo;
                    }
                    
                    listGroup.appendChild(item);
                });
                
                instructorList.innerHTML = '';
                instructorList.appendChild(listGroup);
            })
            .catch(error => {
                console.error('Error loading instructors:', error);
                const message = error.message || 'Failed to load instructors. Please try again.';
                instructorList.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${message}
                    </div>`;
            });
    }

        function renderEditInstructorList(assignedInstructors, availableInstructors) {
            const instructorList = document.getElementById('instructorList');
            instructorList.innerHTML = '';

            // Assigned list with unassign buttons
            if (assignedInstructors.length === 0) {
                instructorList.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No instructors assigned to this subject.
                    </div>`;
            } else {
                const listGroup = document.createElement('div');
                listGroup.className = 'list-group mb-3';
                // Header with select all and bulk unassign
                const header = document.createElement('div');
                header.className = 'd-flex justify-content-between align-items-center mb-2 px-2';
                header.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="assignedSelectAll">
                        <label class="form-check-label small ms-1" for="assignedSelectAll">Select All</label>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark me-3">${assignedInstructors.length} assigned</span>
                        <button class="btn btn-outline-danger btn-sm" id="unassignSelectedBtn">Unassign Selected</button>
                    </div>`;
                listGroup.appendChild(header);

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
                        const ids = Array.from(document.querySelectorAll('.available-checkbox:checked')).map(el => el.value);
                        if (ids.length === 0) { showNotification('error', 'No instructors selected'); return; }
                        assignMultipleInstructors(currentSubjectId, ids, assignSelectedBtn);
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
        }

        function assignInstructorInline(subjectId, instructorId, button) {
            button.disabled = true;
            const orig = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Assigning...';

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

                    function refreshSubjectInstructorCount(subjectId) {
                        // Fetch current assigned instructors for the subject and update all matching view-count elements
                        fetch(`/gecoordinator/subjects/${subjectId}/instructors`)
                            .then(resp => {
                                if (!resp.ok) return resp.json().then(err => { throw new Error(err.message || 'Failed to load instructors'); }).catch(() => { throw new Error('Failed to load instructors'); });
                                return resp.json();
                            })
                            .then(list => {
                                const count = Array.isArray(list) ? list.length : (list.length || 0);
                                document.querySelectorAll(`button.subject-view-btn[data-subject-id="${subjectId}"] .view-count`).forEach(el => {
                                    el.textContent = count;
                                });
                            })
                            .catch(err => {
                                console.error('Error updating instructor count:', err);
                            });
                    }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', error.message || 'Failed to assign instructor');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = orig;
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

        // Update modal content list
        const list = document.getElementById('unassignList');
        if (list) {
            list.innerHTML = '';
            currentUnassignInstructorNames.forEach(n => {
                const li = document.createElement('li');
                li.textContent = n;
                list.appendChild(li);
            });
        }

        // Show the confirmation modal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmUnassignModal'), {
            backdrop: false
        });
        confirmModal.show();
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
                // Refresh the modal content and update the table count instead of reloading the page
                setTimeout(() => {
                    openInstructorListModal(currentSubjectId, document.getElementById('instructorListSubjectName').textContent, 'edit');
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
            });
        });
    });

    function prepareAssignModal(subjectId, subjectName) {
        // For backward compatibility, open the instructor list modal in 'edit' mode
        openInstructorListModal(subjectId, subjectName, 'edit');
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
                
                // Send the request
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
                        // Close the modal using Bootstrap
                        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmAssignModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Show success message using Bootstrap alert
                        showNotification('success', data.message || 'Instructor assigned successfully!');
                        
                        // Refresh the modal and the table count after a short delay to update the instructor lists
                        setTimeout(() => {
                            const sid = document.getElementById('assign_subject_id').value || '';
                            if (sid) {
                                openInstructorListModal(sid, document.getElementById('instructorListSubjectName').textContent, 'edit');
                                refreshSubjectInstructorCount(sid);
                            } else {
                                window.location.reload();
                            }
                        }, 800);
                    } else {
                        throw new Error(data.message || 'Failed to assign instructor');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show error message using Bootstrap alert
                    showNotification('error', error.message || 'Failed to assign instructor');
                })
                .finally(() => {
                    // Restore button state
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                });
            });
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

    function assignMultipleInstructors(subjectId, instructorIds, button) {
        button.disabled = true;
        const orig = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Assigning...';

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
            // Refresh modal and counts
            setTimeout(() => {
                openInstructorListModal(subjectId, document.getElementById('instructorListSubjectName').textContent, 'edit');
                refreshSubjectInstructorCount(subjectId);
            }, 400);
        })
        .catch(error => {
            console.error('Error assigning multiple instructors:', error);
            showNotification('error', error.message || 'Failed to assign selected instructors');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = orig;
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
</style>
@endpush
@endsection