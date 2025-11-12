@extends('layouts.app')

@section('content')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .session-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .session-status-active {
            background-color: #d1f4e0;
            color: #0f4b36;
        }
        
        .session-status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .session-status-current {
            background-color: #cfe2ff;
            color: #084298;
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .swal-small {
            width: 400px !important;
        }

        .device-icon {
            font-size: 1.2rem;
            color: #0f4b36;
        }
    </style>
@endpush

<div class="container py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 text-dark fw-bold mb-1">🔐 Session Management</h1>
            <p class="text-muted small mb-0">Manage user login sessions and monitor activity logs</p>
        </div>
        <button class="btn btn-danger" onclick="confirmRevokeAll()">
            <i class="fas fa-ban me-2"></i>Revoke All Sessions
        </button>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-4" id="sessionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions-pane" 
                    type="button" role="tab" aria-controls="sessions-pane" aria-selected="true">
                <i class="fas fa-server me-2"></i>Active Sessions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs-pane" 
                    type="button" role="tab" aria-controls="logs-pane" aria-selected="false">
                <i class="fas fa-history me-2"></i>User Logs
            </button>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content" id="sessionTabContent">
        {{-- Active Sessions Tab --}}
        <div class="tab-pane fade show active" id="sessions-pane" role="tabpanel" aria-labelledby="sessions-tab">

    {{-- Sessions Table --}}

    {{-- Info Alert --}}
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        You can revoke individual sessions, all sessions for a specific user, or terminate all active sessions at once. Your current session is protected from revocation.
    </div>

    {{-- Sessions Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table id="sessionsTable" class="table table-bordered mb-0">
                <thead class="table-success">
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Device</th>
                        <th>Browser</th>
                        <th>Platform</th>
                        <th>IP Address</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr class="{{ $session->is_current ? 'table-primary' : '' }}">
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">{{ $session->user_name ?? 'Unknown' }}</span>
                                    <small class="text-muted">{{ $session->email }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    @switch($session->role)
                                        @case(0) Instructor @break
                                        @case(1) Chairperson @break
                                        @case(2) Dean @break
                                        @case(3) Admin @break
                                        @case(4) GE Coordinator @break
                                        @case(5) VPAA @break
                                        @default Unknown @break
                                    @endswitch
                                </span>
                            </td>
                            <td>
                                @if($session->is_current)
                                    <span class="session-status-badge session-status-current">
                                        <i class="fas fa-star me-1"></i>Current
                                    </span>
                                @elseif($session->status === 'active')
                                    <span class="session-status-badge session-status-active">
                                        <i class="fas fa-circle me-1"></i>Active
                                    </span>
                                @else
                                    <span class="session-status-badge session-status-expired">
                                        <i class="fas fa-times-circle me-1"></i>Expired
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="device-icon">
                                    @if($session->device_type === 'Desktop')
                                        <i class="fas fa-desktop" title="Desktop"></i>
                                    @elseif($session->device_type === 'Tablet')
                                        <i class="fas fa-tablet-alt" title="Tablet"></i>
                                    @elseif($session->device_type === 'Mobile')
                                        <i class="fas fa-mobile-alt" title="Mobile"></i>
                                    @else
                                        <i class="fas fa-question-circle" title="Unknown"></i>
                                    @endif
                                </span>
                                <span class="ms-1">{{ $session->device_type ?? 'Unknown' }}</span>
                            </td>
                            <td>{{ $session->browser ?? 'Unknown' }}</td>
                            <td>{{ $session->platform ?? 'Unknown' }}</td>
                            <td><code>{{ $session->ip_address ?? 'N/A' }}</code></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span>{{ $session->last_activity_formatted }}</span>
                                    <small class="text-muted">{{ $session->last_activity_date }}</small>
                                </div>
                            </td>
                            <td>
                                @if(!$session->is_current)
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-danger action-btn" 
                                                onclick="confirmRevoke('{{ $session->id }}', '{{ $session->user_name }}')">
                                            <i class="fas fa-ban me-1"></i>Revoke
                                        </button>
                                        <button class="btn btn-sm btn-warning action-btn" 
                                                onclick="confirmRevokeUser({{ $session->user_id }}, '{{ $session->user_name }}')">
                                            <i class="fas fa-user-times me-1"></i>All
                                        </button>
                                    </div>
                                @else
                                    <span class="badge bg-info">Your Session</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted fst-italic py-3">
                                No active sessions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
        </div>
        {{-- End Active Sessions Tab --}}

        {{-- User Logs Tab --}}
        <div class="tab-pane fade" id="logs-pane" role="tabpanel" aria-labelledby="logs-tab">
            {{-- Date Filter --}}
            <div class="d-flex justify-content-end mb-3">
                <form id="dateFilterForm" action="{{ route('admin.sessions') }}" method="GET" class="d-flex align-items-center">
                    <input type="hidden" name="tab" value="logs">
                    <label for="date" class="me-2 mb-0">Select Date:</label>
                    <input type="date" name="date" id="date" value="{{ request('date', now()->format('Y-m-d')) }}" 
                           class="form-control" style="width: 200px;" />
                </form>
            </div>

            {{-- Logs Table --}}
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table id="userLogsTable" class="table table-bordered mb-0">
                        <thead class="table-success">
                            <tr>
                                <th>User</th>
                                <th>Event Type</th>
                                <th>IP Address</th>
                                <th>Browser</th>
                                <th>Device</th>
                                <th>Platform</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userLogs as $log)
                                <tr>
                                    <td>
                                        @if ($log->user)
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $log->user->first_name }} {{ $log->user->last_name }}</span>
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            </div>
                                        @else
                                            <em class="text-muted">Unknown</em>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $eventColors = [
                                                'login' => 'success',
                                                'logout' => 'secondary',
                                                'failed_login' => 'danger',
                                                'session_revoked' => 'warning',
                                                'all_sessions_revoked' => 'warning',
                                                'bulk_sessions_revoked' => 'danger',
                                            ];
                                            $color = $eventColors[$log->event_type] ?? 'info';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                            {{ str_replace('_', ' ', ucwords($log->event_type, '_')) }}
                                        </span>
                                    </td>
                                    <td><code>{{ $log->ip_address ?? 'N/A' }}</code></td>
                                    <td>{{ $log->browser ?? 'N/A' }}</td>
                                    <td>{{ $log->device ?? 'N/A' }}</td>
                                    <td>{{ $log->platform ?? 'N/A' }}</td>
                                    <td data-sort="{{ $log->created_at ? $log->created_at->format('Y-m-d') : '' }}">
                                        {{ $log->created_at ? $log->created_at->format('F j, Y') : 'N/A' }}
                                    </td>
                                    <td data-sort="{{ $log->created_at ? $log->created_at->format('His') : '' }}">
                                        {{ $log->created_at ? $log->created_at->format('g:i A') : 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted fst-italic py-3">
                                        No logs found for the selected date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- End User Logs Tab --}}
    </div>
</div>

{{-- Revoke Single Session Modal --}}
<div class="modal fade" id="revokeModal" tabindex="-1" aria-labelledby="revokeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="revokeModalLabel">Revoke Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="revoke-form" action="{{ route('admin.sessions.revoke') }}" method="POST">
                @csrf
                <input type="hidden" name="session_id" id="revoke-session-id">
                <div class="modal-body">
                    <p>You are about to revoke the session for <strong id="revoke-user-name"></strong>.</p>
                    <p class="text-danger mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This will immediately log out the user from their current session.
                    </p>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Confirm Your Password</label>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="Enter your admin password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Revoke Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Revoke User Sessions Modal --}}
<div class="modal fade" id="revokeUserModal" tabindex="-1" aria-labelledby="revokeUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="revokeUserModalLabel">Revoke All User Sessions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="revoke-user-form" action="{{ route('admin.sessions.revokeUser') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" id="revoke-user-id">
                <div class="modal-body">
                    <p>You are about to revoke <strong>all sessions</strong> for <strong id="revoke-all-user-name"></strong>.</p>
                    <p class="text-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This will immediately log out the user from all their active devices and browsers.
                    </p>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Confirm Your Password</label>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="Enter your admin password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Revoke All Sessions</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Revoke All Sessions Modal --}}
<div class="modal fade" id="revokeAllModal" tabindex="-1" aria-labelledby="revokeAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="revokeAllModalLabel">Revoke All Sessions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="revoke-all-form" action="{{ route('admin.sessions.revokeAll') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="fw-bold text-danger">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        WARNING: This is a critical action!
                    </p>
                    <p>You are about to revoke <strong>ALL active user sessions</strong> in the system, except your current session.</p>
                    <p class="text-muted">
                        This will immediately log out all users from all their devices. Only use this in emergency situations.
                    </p>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Confirm Your Password</label>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="Enter your admin password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Revoke All Sessions</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js" defer></script>
    <script defer>
        function confirmRevoke(sessionId, userName) {
            document.getElementById('revoke-session-id').value = sessionId;
            document.getElementById('revoke-user-name').textContent = userName;
            const modal = new bootstrap.Modal(document.getElementById('revokeModal'));
            modal.show();
        }

        function confirmRevokeUser(userId, userName) {
            document.getElementById('revoke-user-id').value = userId;
            document.getElementById('revoke-all-user-name').textContent = userName;
            const modal = new bootstrap.Modal(document.getElementById('revokeUserModal'));
            modal.show();
        }

        function confirmRevokeAll() {
            const modal = new bootstrap.Modal(document.getElementById('revokeAllModal'));
            modal.show();
        }

        $(document).ready(function () {
            // Initialize Sessions DataTable with custom styling
            const sessionsTable = $('#sessionsTable').DataTable({
                pageLength: 25,
                responsive: true,
                dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search sessions...",
                    lengthMenu: "_MENU_ sessions per page",
                    emptyTable: "No active sessions found.",
                    zeroRecords: "No sessions match your search.",
                    info: "Showing _START_ to _END_ of _TOTAL_ sessions",
                    infoEmpty: "Showing 0 sessions",
                    infoFiltered: "(filtered from _MAX_ total sessions)"
                },
                order: [[7, 'desc']], // Sort by last activity
                columnDefs: [
                    { orderable: false, targets: [8] } // Disable sorting on actions column
                ],
                initComplete: function () {
                    styleDataTable(this);
                }
            });

            // Initialize User Logs DataTable with custom styling
            const logsTable = $('#userLogsTable').DataTable({
                pageLength: 25,
                responsive: true,
                dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search logs...",
                    lengthMenu: "_MENU_ logs per page",
                    emptyTable: "No logs found.",
                    zeroRecords: "No logs match your search.",
                    info: "Showing _START_ to _END_ of _TOTAL_ logs",
                    infoEmpty: "Showing 0 logs",
                    infoFiltered: "(filtered from _MAX_ total logs)"
                },
                order: [[6, 'desc'], [7, 'desc']], // Sort by date and time
                initComplete: function () {
                    styleDataTable(this);
                }
            });

            // Common styling function for DataTables
            function styleDataTable(table) {
                // Add Bootstrap classes to controls
                $('.dataTables_filter input').addClass('form-control-sm');
                $('.dataTables_length select').addClass('form-select-sm');
                
                // Style the top container row
                $('.dataTables_wrapper .row.mb-3').css({
                    'background-color': '#EAF8E7',
                    'padding': '1rem',
                    'border-radius': '0.5rem',
                    'border': '1px solid rgba(15, 75, 54, 0.1)',
                    'margin': '0 0 1rem 0'
                });

                // Style the search input container
                $('.dataTables_filter').css({
                    'margin-bottom': '0'
                });

                // Style the length menu container
                $('.dataTables_length').css({
                    'margin-bottom': '0'
                });

                // Style both input and select elements
                $('.dataTables_filter input, .dataTables_length select').css({
                    'border-color': '#0F4B36',
                    'color': '#0F4B36'
                });

                // Style the labels
                $('.dataTables_filter label, .dataTables_length label').css({
                    'color': '#0F4B36',
                    'font-weight': '500'
                });
            }

            // Submit form when date changes
            $('#date').on('change', function () {
                $('#dateFilterForm').submit();
            });

            // Handle tab switching from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab === 'logs') {
                const logsTabButton = document.getElementById('logs-tab');
                const logsTab = new bootstrap.Tab(logsTabButton);
                logsTab.show();
            }

            // Update URL when tab is clicked
            document.querySelectorAll('#sessionTabs button[data-bs-toggle="tab"]').forEach(button => {
                button.addEventListener('shown.bs.tab', event => {
                    const tabId = event.target.getAttribute('id');
                    const tabName = tabId.replace('-tab', '');
                    const url = new URL(window.location);
                    if (tabName !== 'sessions') {
                        url.searchParams.set('tab', tabName);
                    } else {
                        url.searchParams.delete('tab');
                    }
                    window.history.pushState({}, '', url);
                });
            });
        });

        // Show success/error messages
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                confirmButtonColor: '#0f4b36',
                timer: 3000
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: '<ul class="text-start mb-0">' +
                    @foreach($errors->all() as $error)
                        '<li>{{ $error }}</li>' +
                    @endforeach
                    '</ul>',
                confirmButtonColor: '#0f4b36'
            });
        @endif
    </script>
@endpush
@endsection
