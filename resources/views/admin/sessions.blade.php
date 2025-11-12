@extends('layouts.app')

@section('content')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Session status badges */
        .session-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            gap: 0.25rem;
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
            background-color: #0d6efd;
            color: #ffffff;
        }

        /* User info styling */
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .user-name {
            font-weight: 600;
            color: #212529;
            font-size: 0.875rem;
        }

        .user-email {
            color: #6c757d;
            font-size: 0.75rem;
        }

        /* Device icon styling */
        .device-icon-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .device-icon {
            font-size: 1rem;
            color: #0f4b36;
            width: 20px;
            text-align: center;
        }

        /* Action buttons */
        .action-btn-group {
            display: inline-flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
        }

        .action-btn {
            padding: 0.4rem 0.7rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
        }

        .action-btn i {
            font-size: 0.875rem;
            margin: 0;
        }

        .btn-revoke {
            background-color: #dc3545;
            color: white;
        }

        .btn-revoke:hover {
            background-color: #bb2d3b;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }

        .btn-revoke-all {
            background-color: #fd7e14;
            color: white;
        }

        .btn-revoke-all:hover {
            background-color: #e8590c;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(253, 126, 20, 0.3);
        }

        /* Role badges */
        .role-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* IP address styling */
        .ip-address {
            font-family: 'Courier New', monospace;
            font-size: 0.8125rem;
            color: #495057;
            background-color: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }

        /* Last activity styling */
        .activity-time {
            font-size: 0.875rem;
        }

        .activity-date {
            color: #6c757d;
            font-size: 0.75rem;
        }

        /* Current session highlight */
        .current-session-row {
            background-color: #e7f3ff !important;
        }

        .your-session-badge {
            background-color: #0d6efd;
            color: white;
            padding: 0.375rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            white-space: nowrap;
        }

        /* Event type badges */
        .event-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Tab styling */
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #0f4b36;
            font-weight: 600;
        }

        /* Platform and browser text */
        .platform-text,
        .browser-text {
            font-size: 0.875rem;
            color: #495057;
        }

        /* Pagination styling */
        .pagination {
            margin-bottom: 0;
        }

        .pagination .page-link {
            color: #0f4b36;
            border-color: #dee2e6;
        }

        .pagination .page-link:hover {
            color: #0f4b36;
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: #0f4b36;
            border-color: #0f4b36;
        }

        /* Responsive table text */
        @media (max-width: 768px) {
            .user-name, .user-email {
                font-size: 0.8125rem;
            }
            
            .action-btn {
                padding: 0.25rem 0.375rem;
                font-size: 0.7rem;
            }
        }
    </style>
@endpush

<div class="container py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-dark fw-bold mb-0">
            <i class="fas fa-shield-alt text-success"></i> Session & Activity Monitor
        </h1>
        <button class="btn btn-danger btn-sm" onclick="confirmRevokeAll()">
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
            {{-- Info Alert --}}
            <div class="alert alert-info mb-3 d-flex align-items-start">
                <i class="fas fa-info-circle me-2 mt-1"></i>
                <div>
                    <small>You can revoke individual sessions or all sessions for a specific user. <strong>Your current session is protected</strong> from revocation.</small>
                </div>
            </div>

            {{-- Sessions Table --}}
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
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
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                                    <tbody>
                                @forelse($sessions as $session)
                                <tr class="{{ $session->is_current ? 'current-session-row' : '' }}">
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name">{{ $session->user_name ?? 'Unknown' }}</span>
                                            <small class="user-email">{{ $session->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge bg-secondary text-white">
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
                                                <i class="fas fa-star"></i> Current
                                            </span>
                                        @elseif($session->status === 'active')
                                            <span class="session-status-badge session-status-active">
                                                <i class="fas fa-circle"></i> Active
                                            </span>
                                        @else
                                            <span class="session-status-badge session-status-expired">
                                                <i class="fas fa-times-circle"></i> Expired
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="device-icon-wrapper">
                                            <span class="device-icon">
                                                @if($session->device_type === 'Desktop')
                                                    <i class="fas fa-desktop"></i>
                                                @elseif($session->device_type === 'Tablet')
                                                    <i class="fas fa-tablet-alt"></i>
                                                @elseif($session->device_type === 'Mobile')
                                                    <i class="fas fa-mobile-alt"></i>
                                                @else
                                                    <i class="fas fa-question-circle"></i>
                                                @endif
                                            </span>
                                            <span>{{ $session->device_type ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="browser-text">{{ $session->browser ?? 'Unknown' }}</td>
                                    <td class="platform-text">{{ $session->platform ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="ip-address">{{ $session->ip_address ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <div class="activity-time">{{ $session->last_activity_formatted }}</div>
                                        <small class="activity-date">{{ $session->last_activity_date }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if(!$session->is_current)
                                            <div class="action-btn-group">
                                                <button class="btn btn-sm btn-revoke action-btn" 
                                                        onclick="confirmRevoke('{{ $session->id }}', '{{ $session->user_name }}')"
                                                        title="Revoke this session">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                                <button class="btn btn-sm btn-revoke-all action-btn" 
                                                        onclick="confirmRevokeUser({{ $session->user_id }}, '{{ $session->user_name }}')"
                                                        title="Revoke all user sessions">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="your-session-badge">
                                                <i class="fas fa-shield-alt"></i> Protected
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted fst-italic py-4">
                                        <i class="fas fa-info-circle me-2"></i>No active sessions found.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination --}}
                @if($sessions->hasPages())
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                            <div class="text-muted small">
                                Showing <strong>{{ $sessions->firstItem() }}</strong> to <strong>{{ $sessions->lastItem() }}</strong> of <strong>{{ $sessions->total() }}</strong> sessions
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($sessions->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">&laquo;</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $sessions->previousPageUrl() }}" rel="prev">&laquo;</a>
                                        </li>
                                    @endif

                                    {{-- Page Number Links (show max 5 pages) --}}
                                    @php
                                        $currentPage = $sessions->currentPage();
                                        $lastPage = $sessions->lastPage();
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($lastPage, $currentPage + 2);
                                        
                                        // Adjust if we're near the beginning or end
                                        if ($currentPage <= 3) {
                                            $endPage = min(5, $lastPage);
                                        }
                                        if ($currentPage >= $lastPage - 2) {
                                            $startPage = max(1, $lastPage - 4);
                                        }
                                    @endphp

                                    @for ($i = $startPage; $i <= $endPage; $i++)
                                        @if ($i == $currentPage)
                                            <li class="page-item active">
                                                <span class="page-link">{{ $i }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $sessions->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endif
                                    @endfor

                                    {{-- Next Page Link --}}
                                    @if ($sessions->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $sessions->nextPageUrl() }}" rel="next">&raquo;</a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">&raquo;</span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        {{-- End Active Sessions Tab --}}

        {{-- User Logs Tab --}}
        <div class="tab-pane fade" id="logs-pane" role="tabpanel" aria-labelledby="logs-tab">
            {{-- Date Filter --}}
            <div class="d-flex justify-content-end mb-3">
                <form action="{{ route('admin.sessions') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="tab" value="logs">
                    <label for="date" class="mb-0 small fw-semibold">Filter by Date:</label>
                    <input type="date" name="date" id="date" value="{{ request('date', now()->format('Y-m-d')) }}" 
                           class="form-control form-control-sm" style="width: 180px;" 
                           onchange="this.form.submit()" />
                </form>
            </div>

            {{-- Logs Table --}}
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
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
                                            <div class="user-info">
                                                <span class="user-name">{{ $log->user->first_name }} {{ $log->user->last_name }}</span>
                                                <small class="user-email">{{ $log->user->email }}</small>
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
                                        <span class="event-badge bg-{{ $color }} text-white">
                                            {{ str_replace('_', ' ', ucwords($log->event_type, '_')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ip-address">{{ $log->ip_address ?? 'N/A' }}</span>
                                    </td>
                                    <td class="browser-text">{{ $log->browser ?? 'N/A' }}</td>
                                    <td class="platform-text">{{ $log->device ?? 'N/A' }}</td>
                                    <td class="platform-text">{{ $log->platform ?? 'N/A' }}</td>
                                    <td>{{ $log->created_at ? $log->created_at->format('M j, Y') : 'N/A' }}</td>
                                    <td>{{ $log->created_at ? $log->created_at->format('g:i A') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted fst-italic py-4">
                                        <i class="fas fa-info-circle me-2"></i>No logs found for the selected date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination --}}
                @if($userLogs->hasPages())
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                            <div class="text-muted small">
                                Showing <strong>{{ $userLogs->firstItem() }}</strong> to <strong>{{ $userLogs->lastItem() }}</strong> of <strong>{{ $userLogs->total() }}</strong> logs
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($userLogs->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">&laquo;</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $userLogs->previousPageUrl() }}" rel="prev">&laquo;</a>
                                        </li>
                                    @endif

                                    {{-- Page Number Links (show max 5 pages) --}}
                                    @php
                                        $currentPage = $userLogs->currentPage();
                                        $lastPage = $userLogs->lastPage();
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($lastPage, $currentPage + 2);
                                        
                                        // Adjust if we're near the beginning or end
                                        if ($currentPage <= 3) {
                                            $endPage = min(5, $lastPage);
                                        }
                                        if ($currentPage >= $lastPage - 2) {
                                            $startPage = max(1, $lastPage - 4);
                                        }
                                    @endphp

                                    @for ($i = $startPage; $i <= $endPage; $i++)
                                        @if ($i == $currentPage)
                                            <li class="page-item active">
                                                <span class="page-link">{{ $i }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $userLogs->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endif
                                    @endfor

                                    {{-- Next Page Link --}}
                                    @if ($userLogs->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $userLogs->nextPageUrl() }}" rel="next">&raquo;</a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">&raquo;</span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif
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

        document.addEventListener('DOMContentLoaded', function () {
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
