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
</style>

<div class="max-w-6xl mx-auto py-10 px-4" style="background-color: #EAF8E7; border-radius: 1rem;">
    <h1 class="text-3xl font-bold mb-8 text-gray-800 flex items-center">
        <i class="bi bi-person-lines-fill text-success me-3 fs-2"></i>
        Instructor Account Management
    </h1>

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
                                        <div class="btn-group" role="group">
                                            @php
                                                $hasRequest = $geRequests->has($instructor->id);
                                            @endphp
                                            
                                            @if($hasRequest)
                                                <button type="button"
                                                    class="btn btn-secondary btn-sm d-inline-flex align-items-center gap-1"
                                                    disabled>
                                                    <i class="bi bi-check-circle"></i> Requested GE
                                                </button>
                                            @else
                                                <button type="button"
                                                    class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestGEAssignmentModal"
                                                    data-instructor-id="{{ $instructor->id }}"
                                                    data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
                                                    <i class="bi bi-journal-plus"></i> Request GE
                                                </button>
                                            @endif
                                            
                                            <button type="button"
                                                class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirmDeactivateModal"
                                                data-instructor-id="{{ $instructor->id }}"
                                                data-instructor-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
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
            <h2 class="text-xl font-semibold mb-3 text-gray-700 flex items-center">
                <i class="bi bi-person-x-fill text-secondary me-2 fs-5"></i>
                 Inactive Instructors
            </h2>

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
                                            data-name="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
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
            document.getElementById('deactivateForm').action = `/chairperson/instructors/${button.getAttribute('data-instructor-id')}/deactivate`;
            document.getElementById('instructorName').textContent = button.getAttribute('data-instructor-name');
        });
    }

    // Handling the activate modal (new modal)
    if (activateModal) {
        activateModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('activateForm').action = `/chairperson/instructors/${button.getAttribute('data-id')}/activate`;
            document.getElementById('activateName').textContent = button.getAttribute('data-name');
        });
    }

    // Handling the GE request modal
    if (requestGEModal) {
        requestGEModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('requestGEForm').action = `/chairperson/instructors/${button.getAttribute('data-instructor-id')}/request-ge-assignment`;
            document.getElementById('requestGEName').textContent = button.getAttribute('data-instructor-name');
        });
    }
</script>
@endpush
@endsection
