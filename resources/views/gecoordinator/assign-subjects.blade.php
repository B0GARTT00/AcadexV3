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

        @if (session('success'))
            <div class="alert alert-success shadow-sm rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger shadow-sm rounded">
                {{ session('error') }}
            </div>
        @endif

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
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}', 'view')">
                                                    <i class="bi bi-people-fill me-1"></i>
                                                    <span>View ({{ $subject->instructors_count ?? $subject->instructors->count() }})</span>
                                                </button>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="d-flex">
                                                    <button class="btn btn-sm btn-success me-2" 
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#confirmAssignModal"
                                                            onclick="prepareAssignModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}')"
                                                            title="Assign new instructor">
                                                        <i class="bi bi-person-plus"></i> Assign
                                                    </button>
                                                    @if($subject->instructors->isNotEmpty())
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}', 'unassign')"
                                                                title="Unassign instructors">
                                                            <i class="bi bi-person-dash"></i> Unassign
                                                        </button>
                                                    @endif
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
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}', 'view')">
                                                            <i class="bi bi-people-fill me-1"></i>
                                                            <span>View ({{ $subject->instructors_count ?? $subject->instructors->count() }})</span>
                                                        </button>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex gap-2 justify-content-center">
                                                            <button
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#confirmAssignModal"
                                                                onclick="prepareAssignModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')"
                                                                class="btn btn-success btn-sm" 
                                                                title="Assign Instructor">
                                                                <i class="bi bi-person-plus me-1"></i> Assign
                                                            </button>
                                                            @if($subject->instructors->count() > 0)
                                                                <button
                                                                    onclick="openInstructorListModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}', 'unassign')"
                                                                    class="btn btn-danger btn-sm" 
                                                                    title="Unassign Instructor">
                                                                    <i class="bi bi-x-circle me-1"></i> Unassign
                                                                </button>
                                                            @endif
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
            <div class="modal-header bg-primary text-white">
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
                <p class="mb-3">Are you sure you want to unassign <strong id="unassignInstructorName"></strong>?</p>
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
    let currentModalMode = 'view'; // 'view' or 'unassign'
    let currentUnassignInstructorId = null;
    let currentUnassignInstructorName = null;

    // Function to show Bootstrap notifications
    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="bi ${iconClass} me-2"></i>
                <div>${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insert the alert at the top of the page content
        const container = document.querySelector('.container-fluid');
        if (container) {
            const pageHeader = container.querySelector('.d-flex.justify-content-between.align-items-center.mb-5');
            if (pageHeader) {
                pageHeader.insertAdjacentHTML('afterend', alertHtml);
            } else {
                container.insertAdjacentHTML('afterbegin', alertHtml);
            }
        }
        
        // Auto-dismiss the alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector(`.alert.${alertClass}`);
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }

    function openInstructorListModal(subjectId, subjectName, mode = 'view') {
        currentSubjectId = subjectId;
        currentModalMode = mode; // Store the mode (view or unassign)
        document.getElementById('instructorListSubjectName').textContent = subjectName;
        
        // Update modal title based on mode
        const modalTitle = document.getElementById('instructorListModalTitle');
        if (mode === 'unassign') {
            modalTitle.innerHTML = '<i class="bi bi-person-dash me-2"></i> Unassign Instructor';
        } else {
            modalTitle.innerHTML = '<i class="bi bi-people-fill me-2"></i> Assigned Instructors';
        }
        
        // Show loading state
        const instructorList = document.getElementById('instructorList');
        instructorList.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
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
                            <i class="bi bi-person-fill text-primary me-2"></i>
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
    
    function closeInstructorListModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('instructorListModal'));
        if (modal) {
            modal.hide();
        }
        currentSubjectId = null;
    }
    
    function confirmUnassignInstructor(instructorId, instructorName) {
        // Store the instructor data
        currentUnassignInstructorId = instructorId;
        currentUnassignInstructorName = instructorName;
        
        // Update modal content
        document.getElementById('unassignInstructorName').textContent = instructorName;
        
        // Show the confirmation modal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmUnassignModal'), {
            backdrop: false
        });
        confirmModal.show();
    }
    
    // Handle the confirm unassign button click
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('confirmUnassignBtn').addEventListener('click', function() {
            if (!currentUnassignInstructorId || !currentSubjectId) {
                showNotification('error', 'Missing instructor or subject information');
                return;
            }
            
            // Disable the button to prevent double clicks
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            // Hide the confirmation modal
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmUnassignModal'));
            confirmModal.hide();
            
            // Perform the unassign operation
            fetch('{{ route("gecoordinator.unassignInstructor") }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    subject_id: currentSubjectId,
                    instructor_id: currentUnassignInstructorId
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Failed to unassign instructor');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to unassign instructor');
                }
                showNotification('success', 'Instructor has been unassigned successfully.');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
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
                this.innerHTML = '<i class="bi bi-person-dash me-1"></i> Yes, unassign';
            });
        });
    });

    function prepareAssignModal(subjectId, subjectName) {
        console.log('prepareAssignModal called with:', subjectId, subjectName);
        currentSubjectId = subjectId;
        document.getElementById('assignSubjectName').textContent = subjectName;
        document.getElementById('assign_subject_id').value = subjectId;
        
        // Reset the select first
        const select = document.getElementById('instructor_select');
        console.log('Select element found:', select);
        const defaultOption = select.options[0];
        select.innerHTML = '';
        select.appendChild(defaultOption);
        
        // Show loading state
        const submitBtn = document.querySelector('#assignInstructorForm button[type="submit"]');
        console.log('Submit button found:', submitBtn);
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        select.disabled = true;
        
        console.log('Fetching instructors...');
        // Fetch all instructors
        fetch('/gecoordinator/available-instructors')
            .then(response => {
                console.log('Available instructors response:', response.status);
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || err.error || 'Failed to load available instructors'); }).catch(() => { throw new Error('Failed to load available instructors'); });
                }
                return response.json();
            })
            .then(instructors => {
                console.log('Available instructors:', instructors);
                // Fetch assigned instructors for this subject
                    return fetch(`/gecoordinator/subjects/${subjectId}/instructors`)
                    .then(response => {
                        console.log('Assigned instructors response:', response.status);
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.message || err.error || 'Failed to load assigned instructors'); }).catch(() => { throw new Error('Failed to load assigned instructors'); });
                        }
                        return response.json();
                    })
                    .then(assignedInstructors => {
                        console.log('Assigned instructors:', assignedInstructors);
                        const assignedIds = assignedInstructors.map(i => i.id);
                        
                        // Add available instructors to select
                        instructors.forEach(instructor => {
                            if (!assignedIds.includes(instructor.id)) {
                                const option = new Option(instructor.name, instructor.id);
                                select.add(option);
                            }
                        });
                        
                        console.log('Final select options count:', select.options.length);
                        
                        // Enable controls if there are available instructors
                        if (select.options.length > 1) {
                            select.disabled = false;
                            if (submitBtn) {
                                submitBtn.disabled = false;
                            }
                        } else {
                            const option = new Option('No available instructors', '');
                            option.disabled = true;
                            option.selected = true;
                            select.add(option);
                        }
                    });
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', error.message || 'Error loading instructors. Please try again.');
                // Bootstrap modal will handle closing automatically
            });
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
                        
                        // Refresh the page after a short delay to update the instructor lists
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
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