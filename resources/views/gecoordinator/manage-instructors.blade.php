@extends('layouts.app')

@section('content')

<style>
    /* Change Active Tab Color */
    .nav-tabs .nav-link.inactive {
        color: #4da674 !important;  /* Green color */
        border-color: #4da674 !important;  /* Green border for active tab */
    }

    /* Optionally, change the hover color for non-active tabs */
    .nav-tabs .nav-link:hover {
        color: #4da674 !important;  /* Green color for hover state */
    }

    /* Main container improvements */
    .instructor-management-wrapper {
        background-color: #EAF8E7;
        min-height: 100vh;
        padding: 0;
        margin: 0;
    }

    .instructor-management-container {
        max-width: 100%;
        margin: 0;
        padding: 1.5rem 1rem;
    }

    /* Tab improvements */
    .nav-tabs {
        border-bottom: 2px solid #d0d0d0 !important;
        margin-bottom: 1.5rem;
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
        background-color: white !important;
        color: #4da674 !important;
        border-bottom: 3px solid #4da674 !important;
        box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
    }

    /* Content styling */
    .tab-content {
        background-color: transparent;
        border-radius: 0;
        box-shadow: none;
        padding: 0;
    }

    /* Table improvements */
    .table-responsive {
        border-radius: 0.5rem !important;
        background-color: transparent;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #333;
        border-color: #e0e0e0;
        padding: 1rem !important;
        border-bottom: 2px solid #d0d0d0;
    }

    .table tbody td {
        padding: 1rem !important;
        border-color: #e8e8e8;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: background-color 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f9f9f9;
    }

    /* Alert improvements */
    .alert {
        border-radius: 0.5rem !important;
        border: 0 !important;
        margin-bottom: 1.5rem;
    }

    .alert-warning {
        background-color: #fef3cd !important;
        color: #664d03 !important;
    }

    .alert-info {
        background-color: #d1ecf1 !important;
        color: #0c5460 !important;
    }

    .alert-success {
        background-color: #d4edda !important;
        color: #155724 !important;
    }

    /* Button improvements */
    .btn-sm {
        padding: 0.45rem 0.9rem !important;
        font-size: 0.875rem !important;
        border-radius: 0.375rem !important;
    }

    /* Page title */
    .page-title {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid rgba(77, 166, 116, 0.2);
    }

    /* Content wrapper with white background */
    .content-wrapper {
        background-color: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Tabs section */
    .tabs-section {
        width: 100%;
    }

    /* Pending approvals section - flexible and responsive */
    .pending-approvals-section {
        width: 100%;
        margin: 0;
        padding: 0;
        flex-grow: 1;
        margin-top: 1.5rem;
    }

    /* Responsive grid layout */
    @media (max-width: 768px) {
        .content-wrapper {
            padding: 1rem;
            gap: 1rem;
        }

        .page-title {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="instructor-management-wrapper">
    <div class="instructor-management-container">
        <div class="page-title">
            <h1 class="text-3xl font-bold mb-2 text-gray-800 flex items-center">
                <i class="bi bi-person-lines-fill text-success me-3 fs-2"></i>
                Instructor Account Management
            </h1>
            <p class="text-muted mb-0 small">Manage instructor accounts, requests, and GE courses assignments</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success shadow-sm rounded">
                {{ session('status') }}
            </div>
        @endif

        <div class="content-wrapper">
            <div class="tabs-section">
                {{-- Bootstrap Tabs --}}
                <ul class="nav nav-tabs" id="instructorTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="active-instructors-tab" data-bs-toggle="tab" href="#active-instructors" role="tab" aria-controls="active-instructors" aria-selected="true">
                            Active Instructors
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="inactive-instructors-tab" data-bs-toggle="tab" href="#inactive-instructors" role="tab" aria-controls="inactive-instructors" aria-selected="false">
                            Inactive Instructors
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="ge-requests-tab" data-bs-toggle="tab" href="#ge-requests" role="tab" aria-controls="ge-requests" aria-selected="false">
                            GE Courses Requests
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="instructorTabsContent">
                    {{-- Active Instructors Tab --}}
                    <div class="tab-pane fade show active" id="active-instructors" role="tabpanel" aria-labelledby="active-instructors-tab">
                        <h2 class="text-xl font-semibold mb-3 text-gray-700 flex items-center">
                            <i class="bi bi-people-fill text-primary me-2 fs-5"></i>
                            Active Instructors
                        </h2>

                        @if($instructors->isEmpty())
                            <div class="alert alert-warning shadow-sm rounded">No active instructors.</div>
                        @else
                            <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Instructor Name</th>
                                            <th>Email Address</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($instructors as $instructor)
                                            @if($instructor->is_active)
                                                <tr>
                                                    <td>{{ $instructor->last_name }}, {{ $instructor->first_name }} {{ $instructor->middle_name }}</td>
                                                    <td>{{ $instructor->email }}</td>
                                                    <td class="text-center">
                                                        <span class="badge border border-success text-success px-3 py-2 rounded-pill">
                                                            Active
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#confirmDeactivateModal"
                                                            data-instructor-id="{{ $instructor->id }}"
                                                            data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
                                                            <i class="bi bi-person-x-fill"></i> Deactivate
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>        
                        @endif

                        {{-- Pending Account Approvals --}}
                        <section>
                            <h2 class="text-lg font-semibold mb-2 text-gray-700 flex items-center">
                                <i class="bi bi-person-check-fill text-warning me-2 fs-6"></i>
                                Pending For Approvals
                            </h2>

                            @if($pendingAccounts->isEmpty())
                                <div class="alert alert-info shadow-sm rounded">No pending instructor applications.</div>
                            @else
                                <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Applicant Name</th>
                                                <th>Email Address</th>
                                                <th>Department</th>
                                                <th>Course</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingAccounts as $account)
                                                <tr>
                                                    <td>{{ $account->last_name }}, {{ $account->first_name }} {{ $account->middle_name }}</td>
                                                    <td>{{ $account->email }}</td>
                                                    <td>{{ $account->department?->department_code ?? 'N/A' }}</td>
                                                    <td>{{ $account->course?->course_code ?? 'N/A' }}</td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-success btn-sm d-inline-flex align-items-center gap-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#confirmApproveModal"
                                                            data-id="{{ $account->id }}"
                                                            data-name="{{ $account->last_name }}, {{ $account->first_name }}">
                                                            <i class="bi bi-check-circle-fill"></i> Approve
                                                        </button>

                                                        <button type="button"
                                                            class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1 ms-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#confirmRejectModal"
                                                            data-id="{{ $account->id }}"
                                                            data-name="{{ $account->last_name }}, {{ $account->first_name }}">
                                                            <i class="bi bi-x-circle-fill"></i> Reject
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>
                    </div>

                    {{-- Inactive Instructors Tab --}}
                    <div class="tab-pane fade" id="inactive-instructors" role="tabpanel" aria-labelledby="inactive-instructors-tab">
                        <h2 class="text-xl font-semibold mb-3 text-gray-700 flex items-center">
                            <i class="bi bi-person-x-fill text-secondary me-2 fs-5"></i>
                            Inactive Instructors
                        </h2>

                        @php
                            $inactiveInstructors = $instructors->filter(fn($i) => !$i->is_active);
                        @endphp

                        @if($inactiveInstructors->isEmpty())
                            <div class="alert alert-warning shadow-sm rounded">No inactive instructors.</div>
                        @else
                            <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Instructor Name</th>
                                            <th>Email Address</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inactiveInstructors as $instructor)
                                            <tr>
                                                <td>{{ $instructor->last_name }}, {{ $instructor->first_name }} {{ $instructor->middle_name }}</td>
                                                <td>{{ $instructor->email }}</td>
                                                <td class="text-center">
                                                    <span class="badge border border-secondary text-secondary px-3 py-2 rounded-pill">
                                                        Inactive
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-success btn-sm d-inline-flex align-items-center gap-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#confirmActivateModal"
                                                        data-id="{{ $instructor->id }}"
                                                        data-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
                                                        <i class="bi bi-person-check-fill"></i>
                                                        Activate
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>        
                        @endif
                    </div>

                    {{-- GE Courses Requests Tab --}}
                    <div class="tab-pane fade" id="ge-requests" role="tabpanel" aria-labelledby="ge-requests-tab">
                        <h2 class="text-xl font-semibold mb-3 text-gray-700 flex items-center">
                            <i class="bi bi-journal-plus text-warning me-2 fs-5"></i>
            GE Courses Requests
        </h2>        @php
            $geRequests = \App\Models\GESubjectRequest::with(['instructor', 'requestedBy'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();
        @endphp

        @if($geRequests->isEmpty())
            <div class="alert alert-warning shadow-sm rounded">No pending GE courses requests.</div>
        @else
            <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Instructor Name</th>
                            <th>Department</th>
                            <th>Requested By</th>
                            <th>Request Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($geRequests as $request)
                            <tr>
                                <td>{{ $request->instructor->last_name }}, {{ $request->instructor->first_name }} {{ $request->instructor->middle_name }}</td>
                                <td>{{ $request->instructor->department->department_code ?? 'N/A' }}</td>
                                <td>{{ $request->requestedBy->last_name }}, {{ $request->requestedBy->first_name }}</td>
                                <td>{{ $request->created_at->format('M d, Y h:i A') }}</td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-success btn-sm d-inline-flex align-items-center gap-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#approveGERequestModal"
                                        data-request-id="{{ $request->id }}"
                                        data-instructor-name="{{ $request->instructor->last_name }}, {{ $request->instructor->first_name }}">
                                        <i class="bi bi-check-circle-fill"></i> Approve
                                    </button>

                                    <button type="button"
                                        class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1 ms-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectGERequestModal"
                                        data-request-id="{{ $request->id }}"
                                        data-instructor-name="{{ $request->instructor->last_name }}, {{ $request->instructor->first_name }}">
                                        <i class="bi bi-x-circle-fill"></i> Reject
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pending Account Approvals section removed from here as it's now inside each tab --}}
        </div>
    </div>
</div>

{{-- Modals --}}
<div class="modal fade" id="confirmDeactivateModal" tabindex="-1" aria-labelledby="confirmDeactivateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="deactivateForm" method="POST">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeactivateModalLabel">Confirm Account Deactivation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to deactivate <strong id="instructorName"></strong>'s account?
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Deactivate</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="confirmApproveModal" tabindex="-1" aria-labelledby="confirmApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="approveForm">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="confirmApproveModalLabel">Confirm Approval</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to approve <strong id="approveName"></strong>'s account?
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="confirmRejectModal" tabindex="-1" aria-labelledby="confirmRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectForm">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmRejectModalLabel">Confirm Rejection</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to reject <strong id="rejectName"></strong>'s account?
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="confirmActivateModal" tabindex="-1" aria-labelledby="confirmActivateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="activateForm">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="confirmActivateModalLabel">Confirm Activation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to activate <strong id="activateName"></strong>'s account?
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Activate</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Approve GE Subject Request Modal --}}
<div class="modal fade" id="approveGERequestModal" tabindex="-1" aria-labelledby="approveGERequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="approveGERequestForm">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveGERequestModalLabel">Approve GE Subject Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to approve the GE subject request for <strong id="approveGERequestName"></strong>?
                    <p class="text-muted small mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        This will allow the instructor to be assigned to GE subjects.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Reject GE Subject Request Modal --}}
<div class="modal fade" id="rejectGERequestModal" tabindex="-1" aria-labelledby="rejectGERequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectGERequestForm">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectGERequestModalLabel">Reject GE Subject Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to reject the GE subject request for <strong id="rejectGERequestName"></strong>?
                    <p class="text-muted small mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        This will deny the instructor from being assigned to GE subjects.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </div>
        </form>
    </div>
</div>


@push('scripts')
<script>
    const approveModal = document.getElementById('confirmApproveModal');
    const rejectModal = document.getElementById('confirmRejectModal');
    const deactivateModal = document.getElementById('confirmDeactivateModal');
    const activateModal = document.getElementById('confirmActivateModal'); // New activate modal
    const approveGERequestModal = document.getElementById('approveGERequestModal');
    const rejectGERequestModal = document.getElementById('rejectGERequestModal');

    // Handling the approve modal
    if (approveModal) {
        approveModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            const id = button.getAttribute('data-id') || button.getAttribute('data-instructor-id');
            const name = button.getAttribute('data-name') || button.getAttribute('data-instructor-name');
            if (id) document.getElementById('approveForm').action = `/gecoordinator/approvals/${id}/approve`;
            if (name) document.getElementById('approveName').textContent = name;
        });

        // Fallback click listeners for triggers
        document.querySelectorAll('[data-bs-target="#confirmApproveModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id') || btn.getAttribute('data-instructor-id');
                const name = btn.getAttribute('data-name') || btn.getAttribute('data-instructor-name');
                if (id) document.getElementById('approveForm').action = `/gecoordinator/approvals/${id}/approve`;
                if (name) document.getElementById('approveName').textContent = name;
            });
        });
    }

    // Handling the reject modal
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            const id = button.getAttribute('data-id') || button.getAttribute('data-instructor-id');
            const name = button.getAttribute('data-name') || button.getAttribute('data-instructor-name');
            if (id) document.getElementById('rejectForm').action = `/gecoordinator/approvals/${id}/reject`;
            if (name) document.getElementById('rejectName').textContent = name;
        });

        // Fallback click listeners for triggers
        document.querySelectorAll('[data-bs-target="#confirmRejectModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id') || btn.getAttribute('data-instructor-id');
                const name = btn.getAttribute('data-name') || btn.getAttribute('data-instructor-name');
                if (id) document.getElementById('rejectForm').action = `/gecoordinator/approvals/${id}/reject`;
                if (name) document.getElementById('rejectName').textContent = name;
            });
        });
    }

    // Handling the deactivate modal
    if (deactivateModal) {
        deactivateModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            const id = button.getAttribute('data-instructor-id') || button.getAttribute('data-id');
            const name = button.getAttribute('data-instructor-name') || button.getAttribute('data-name');
            if (id) document.getElementById('deactivateForm').action = `/gecoordinator/instructors/${id}/deactivate`;
            if (name) document.getElementById('instructorName').textContent = name;
        });

        // Fallback click listeners for triggers
        document.querySelectorAll('[data-bs-target="#confirmDeactivateModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-instructor-id') || btn.getAttribute('data-id');
                const name = btn.getAttribute('data-instructor-name') || btn.getAttribute('data-name');
                if (id) document.getElementById('deactivateForm').action = `/gecoordinator/instructors/${id}/deactivate`;
                if (name) document.getElementById('instructorName').textContent = name;
            });
        });
    }

    // Handling the activate modal (new modal)
    if (activateModal) {
        activateModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            const id = button.getAttribute('data-id') || button.getAttribute('data-instructor-id');
            const name = button.getAttribute('data-name') || button.getAttribute('data-instructor-name');
            if (id) document.getElementById('activateForm').action = `/gecoordinator/instructors/${id}/activate`;
            if (name) document.getElementById('activateName').textContent = name;
        });

        // Fallback click listeners for triggers
        document.querySelectorAll('[data-bs-target="#confirmActivateModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id') || btn.getAttribute('data-instructor-id');
                const name = btn.getAttribute('data-name') || btn.getAttribute('data-instructor-name');
                if (id) document.getElementById('activateForm').action = `/gecoordinator/instructors/${id}/activate`;
                if (name) document.getElementById('activateName').textContent = name;
            });
        });
    }

    // Handling the approve GE request modal - prefer `show.bs.modal` but also attach a click handler for reliability
    if (approveGERequestModal) {
        // show.bs.modal handler (Bootstrap provides the relatedTarget)
        approveGERequestModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            const requestId = button.getAttribute('data-request-id') || button.dataset.requestId;
            const instructorName = button.getAttribute('data-instructor-name') || button.dataset.instructorName;
            if (requestId) {
                document.getElementById('approveGERequestForm').action = `/gecoordinator/ge-requests/${requestId}/approve`;
            }
            if (instructorName) {
                document.getElementById('approveGERequestName').textContent = instructorName;
            }
        });

        // Fallback: attach click listeners to all triggers that open the modal
        document.querySelectorAll('[data-bs-target="#approveGERequestModal"]').forEach(btn => {
            btn.addEventListener('click', e => {
                const requestId = btn.getAttribute('data-request-id') || btn.dataset.requestId;
                const instructorName = btn.getAttribute('data-instructor-name') || btn.dataset.instructorName;
                if (requestId) document.getElementById('approveGERequestForm').action = `/gecoordinator/ge-requests/${requestId}/approve`;
                if (instructorName) document.getElementById('approveGERequestName').textContent = instructorName;
            });
        });
    }

    // Handling the reject GE request modal - prefer `show.bs.modal` but also attach a click handler for reliability
    if (rejectGERequestModal) {
        rejectGERequestModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;
            const requestId = button.getAttribute('data-request-id') || button.dataset.requestId;
            const instructorName = button.getAttribute('data-instructor-name') || button.dataset.instructorName;
            if (requestId) {
                document.getElementById('rejectGERequestForm').action = `/gecoordinator/ge-requests/${requestId}/reject`;
            }
            if (instructorName) {
                document.getElementById('rejectGERequestName').textContent = instructorName;
            }
        });

        // Fallback: attach click listeners to all triggers that open the modal
        document.querySelectorAll('[data-bs-target="#rejectGERequestModal"]').forEach(btn => {
            btn.addEventListener('click', e => {
                const requestId = btn.getAttribute('data-request-id') || btn.dataset.requestId;
                const instructorName = btn.getAttribute('data-instructor-name') || btn.dataset.instructorName;
                if (requestId) document.getElementById('rejectGERequestForm').action = `/gecoordinator/ge-requests/${requestId}/reject`;
                if (instructorName) document.getElementById('rejectGERequestName').textContent = instructorName;
            });
        });
    }
</script>
@endpush
@endsection
