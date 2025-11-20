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

    .nav-tabs {
        background: #f8f9fa;
        border-radius: 0.75rem 0.75rem 0 0;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 0;
        padding: 0.5rem 1rem 0 1rem;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 500;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem 0.75rem 0 0;
        margin-right: 0.25rem;
        background: transparent;
        transition: all 0.2s;
    }
    .nav-tabs .nav-link:hover {
        color: #4da674;
        border-bottom-color: #4da674;
        background: #eaf8e7;
    }
    .nav-tabs .nav-link.active {
        color: #4da674;
        background: #fff;
        border-bottom-color: #4da674;
        box-shadow: 0 -2px 8px rgba(77,166,116,0.06);
    }

    .tab-content .tab-pane .table-responsive {
        border-radius: 0 0 0.75rem 0.75rem !important;
        border-top: none;
    }
</style>

<div class="import-courses-wrapper">
    <div class="import-courses-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>
                <i class="bi bi-person-lines-fill"></i>
                Instructor Account Management
            </h1>
            <p class="page-subtitle">Manage instructor accounts, view status, and assign GE subject permissions</p>
        </div>

    @if(session('status'))
        <div class="alert alert-success shadow-sm rounded">
            {{ session('status') }}
        </div>
    @endif

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
    </ul>

    <div class="tab-content" id="instructorTabsContent">
        {{-- Active Instructors Tab --}}
        <div class="tab-pane fade show active" id="active-instructors" role="tabpanel" aria-labelledby="active-instructors-tab">

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
                                        <div class="btn-group" role="group">
                                            @php
                                                $geRequest = $geRequests->get($instructor->id);
                                                $hasRequest = $geRequest !== null;
                                                $requestStatus = $geRequest?->status ?? null;
                                                $canTeachGE = $instructor->can_teach_ge ?? false;
                                            @endphp
                                            
                                            @if($hasRequest && $canTeachGE)
                                                {{-- Instructor has a request AND can currently teach GE --}}
                                                @if($requestStatus === 'pending')
                                                    <button type="button"
                                                        class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1"
                                                        disabled>
                                                        <i class="bi bi-clock"></i> Pending
                                                    </button>
                                                @elseif($requestStatus === 'approved')
                                                    <button type="button"
                                                        class="btn btn-success btn-sm d-inline-flex align-items-center gap-1"
                                                        disabled>
                                                        <i class="bi bi-check-circle"></i> Assigned
                                                    </button>
                                                @endif
                                            @elseif($hasRequest && !$canTeachGE && $requestStatus === 'pending')
                                                {{-- Has pending request but can't teach GE (shouldn't happen, but handle it) --}}
                                                <button type="button"
                                                    class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1"
                                                    disabled>
                                                    <i class="bi bi-clock"></i> Pending
                                                </button>
                                            @elseif($hasRequest && !$canTeachGE && in_array($requestStatus, ['rejected', 'revoked']))
                                                {{-- Request was rejected or revoked, allow new request --}}
                                                <button type="button"
                                                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestGEAssignmentModal"
                                                    data-instructor-id="{{ $instructor->id }}"
                                                    data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}"
                                                    data-request-ge-url="{{ route('chairperson.requestGEAssignment', $instructor->id) }}">
                                                    <i class="bi bi-journal-plus"></i> Request GE
                                                </button>
                                            @elseif($hasRequest && !$canTeachGE && $requestStatus === 'approved')
                                                {{-- Was approved but later removed by GE coordinator, allow new request --}}
                                                <button type="button"
                                                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestGEAssignmentModal"
                                                    data-instructor-id="{{ $instructor->id }}"
                                                    data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}"
                                                    data-request-ge-url="{{ route('chairperson.requestGEAssignment', $instructor->id) }}">
                                                    <i class="bi bi-journal-plus"></i> Request GE Again
                                                </button>
                                            @else
                                                {{-- No request exists, allow new request --}}
                                                <button type="button"
                                                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestGEAssignmentModal"
                                                    data-instructor-id="{{ $instructor->id }}"
                                                    data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}"
                                                    data-request-ge-url="{{ route('chairperson.requestGEAssignment', $instructor->id) }}">
                                                    <i class="bi bi-journal-plus"></i> Request GE
                                                </button>
                                            @endif
                                            
                                            <button type="button"
                                                class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmDeactivateModal"
                                                data-instructor-id="{{ $instructor->id }}"
                                                data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}"
                                                data-deactivate-url="{{ route('chairperson.deactivateInstructor', $instructor->id) }}">
                                                <i class="bi bi-person-x-fill"></i> Deactivate
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>        
            @endif
        </div>

        {{-- Inactive Instructors Tab --}}
        <div class="tab-pane fade" id="inactive-instructors" role="tabpanel" aria-labelledby="inactive-instructors-tab">

            @if($instructors->isEmpty())
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
                        @foreach($instructors as $instructor)
                            @if(!$instructor->is_active)
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
                                                data-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}"
                                                data-activate-url="{{ route('chairperson.activateInstructor', $instructor->id) }}">
                                            <i class="bi bi-person-check-fill"></i>
                                            Activate
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>        
            @endif
        </div>
    </div>

    {{-- Pending Account Approvals --}}
    <section class="mt-4">
        <h2 class="text-xl font-semibold mb-3 text-gray-700 flex items-center">
            <i class="bi bi-person-check-fill text-warning me-2 fs-5"></i>
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

{{-- Request GE Assignment Modal --}}
<div class="modal fade" id="requestGEAssignmentModal" tabindex="-1" aria-labelledby="requestGEAssignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="requestGEForm">
            @csrf
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="requestGEAssignmentModalLabel">Request GE Subject Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to request GE subject assignment for <strong id="requestGEName"></strong>?</p>
                    <p class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        This request will be sent to the GE Coordinator for approval. The instructor will remain visible in your list.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Request Assignment</button>
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
    const requestGEModal = document.getElementById('requestGEAssignmentModal'); // New GE request modal

    // Handling the approve modal
    if (approveModal) {
        approveModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('approveForm').action = `/chairperson/approvals/${button.getAttribute('data-id')}/approve`;
            document.getElementById('approveName').textContent = button.getAttribute('data-name');
        });
    }

    // Handling the reject modal
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('rejectForm').action = `/chairperson/approvals/${button.getAttribute('data-id')}/reject`;
            document.getElementById('rejectName').textContent = button.getAttribute('data-name');
        });
    }

    // Handling the deactivate modal
    if (deactivateModal) {
        deactivateModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            // Prefer server-generated URL from data attribute (safer). Fallback to building the URL.
            const deactivateUrl = button.getAttribute('data-deactivate-url') || `/chairperson/instructors/${button.getAttribute('data-instructor-id')}/deactivate`;
            document.getElementById('deactivateForm').action = deactivateUrl;
            document.getElementById('instructorName').textContent = button.getAttribute('data-instructor-name');
        });
    }
    // Guard deactivate form submit to ensure action is set
    const deactivateFormEl = document.getElementById('deactivateForm');
    if (deactivateFormEl) {
        deactivateFormEl.addEventListener('submit', function(e) {
            const action = this.getAttribute('action') || '';
            if (!action || !action.includes('/deactivate')) {
                e.preventDefault();
                console.warn('Deactivate form action invalid:', action);
                alert('Unable to determine the instructor to deactivate. Please re-open the dialog and try again.');
                return false;
            }
        });
    }

    // Also make sure clicking Deactivate buttons sets the action immediately (robust fallback)
    document.querySelectorAll('button[data-bs-target="#confirmDeactivateModal"]').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            const form = document.getElementById('deactivateForm');
            const url = btn.getAttribute('data-deactivate-url') || (`/chairperson/instructors/${btn.getAttribute('data-instructor-id')}/deactivate`);
            if (form) {
                form.action = url;
                // Also populate the modal name immediately (fallback if modal show event doesn't supply relatedTarget)
                const name = btn.getAttribute('data-instructor-name') || '';
                const nameEl = document.getElementById('instructorName');
                if (nameEl) nameEl.textContent = name;
            }
        });
    });

    // Handling the activate modal (new modal)
    if (activateModal) {
        activateModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const activateUrl = button.getAttribute('data-activate-url') || `/chairperson/instructors/${button.getAttribute('data-id')}/activate`;
            document.getElementById('activateForm').action = activateUrl;
            document.getElementById('activateName').textContent = button.getAttribute('data-name');
        });
    }
    // Guard activate form submit
    const activateFormEl = document.getElementById('activateForm');
    if (activateFormEl) {
        activateFormEl.addEventListener('submit', function(e) {
            const action = this.getAttribute('action') || '';
            if (!action || !action.includes('/activate')) {
                e.preventDefault();
                console.warn('Activate form action invalid:', action);
                alert('Unable to determine the instructor to activate. Please re-open the dialog and try again.');
                return false;
            }
        });
    }

    // Also make sure clicking Activate buttons sets the action immediately (robust fallback)
    document.querySelectorAll('button[data-bs-target="#confirmActivateModal"]').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            const form = document.getElementById('activateForm');
            const url = btn.getAttribute('data-activate-url') || (`/chairperson/instructors/${btn.getAttribute('data-id')}/activate`);
            if (form) {
                form.action = url;
                const nameEl = document.getElementById('activateName');
                if (nameEl) nameEl.textContent = btn.getAttribute('data-name') || '';
            }
        });
    });

    // Handling the GE request modal
    if (requestGEModal) {
        requestGEModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const reqUrl = button.getAttribute('data-request-ge-url') || `/chairperson/instructors/${button.getAttribute('data-instructor-id')}/request-ge-assignment`;
            document.getElementById('requestGEForm').action = reqUrl;
            document.getElementById('requestGEName').textContent = button.getAttribute('data-instructor-name');
        });
    }
    // Guard request ge form submit to ensure action is set
    const requestGEFormEl = document.getElementById('requestGEForm');
    if (requestGEFormEl) {
        requestGEFormEl.addEventListener('submit', function(e) {
            const action = this.getAttribute('action') || '';
            if (!action || action.indexOf('/request-ge-assignment') === -1) {
                e.preventDefault();
                console.warn('Request GE form action invalid:', action);
                alert('Unable to determine the instructor to request GE assignment for. Please re-open the dialog and try again.');
                return false;
            }
        });
    }
    // Click fallback to set request form action and name
    document.querySelectorAll('button[data-bs-target="#requestGEAssignmentModal"]').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            const form = document.getElementById('requestGEForm');
            const url = btn.getAttribute('data-request-ge-url') || (`/chairperson/instructors/${btn.getAttribute('data-instructor-id')}/request-ge-assignment`);
            if (form) {
                form.action = url;
                const nameEl = document.getElementById('requestGEName');
                if (nameEl) nameEl.textContent = btn.getAttribute('data-instructor-name') || '';
            }
        });
    });
</script>
@endpush

    </div>
</div>
@endsection
