@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-3">
        <div class="col">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-clipboard-check text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1" style="color: #198754;">Structure Template Requests</h3>
                        <p class="text-muted mb-0">Review and approve chairperson template submissions</p>
                    </div>
                </div>
                <a href="{{ route('admin.gradesFormula', ['view' => 'formulas']) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Grades Formula
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.structureTemplateRequests.index', ['status' => 'all']) }}" 
                   class="btn btn-sm {{ $status === 'all' ? 'btn-success' : 'btn-outline-success' }}">
                    All Requests
                </a>
                <a href="{{ route('admin.structureTemplateRequests.index', ['status' => 'pending']) }}" 
                   class="btn btn-sm {{ $status === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
                    Pending @if($pendingCount > 0)<span class="badge bg-dark ms-1">{{ $pendingCount }}</span>@endif
                </a>
                <a href="{{ route('admin.structureTemplateRequests.index', ['status' => 'approved']) }}" 
                   class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                    Approved
                </a>
                <a href="{{ route('admin.structureTemplateRequests.index', ['status' => 'rejected']) }}" 
                   class="btn btn-sm {{ $status === 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
                    Rejected
                </a>
            </div>
        </div>
    </div>

    @if ($requests->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                </div>
                <h5 class="text-muted mb-2">No Requests Found</h5>
                <p class="text-muted mb-0">
                    @if ($status === 'pending')
                        There are no pending template requests at the moment.
                    @elseif ($status === 'approved')
                        No approved template requests found.
                    @elseif ($status === 'rejected')
                        No rejected template requests found.
                    @else
                        No template requests have been submitted yet.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover bg-white shadow-sm">
                <thead class="table-success">
                    <tr>
                        <th>Template Name</th>
                        <th>Submitted By</th>
                        <th>Structure Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        @php
                            $statusBadge = match ($request->status) {
                                'pending' => ['class' => 'bg-warning text-dark', 'icon' => 'clock-history'],
                                'approved' => ['class' => 'bg-success', 'icon' => 'check-circle'],
                                'rejected' => ['class' => 'bg-danger', 'icon' => 'x-circle'],
                                default => ['class' => 'bg-secondary', 'icon' => 'question-circle'],
                            };
                            
                            $structureType = $request->structure_config['type'] ?? 'unknown';
                            $structureLabel = match ($structureType) {
                                'lecture_only' => 'Lecture Only',
                                'lecture_lab' => 'Lecture + Lab',
                                'custom' => 'Custom',
                                default => 'Unknown',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $request->label }}</div>
                                @if ($request->description)
                                    <small class="text-muted">{{ Str::limit($request->description, 60) }}</small>
                                @endif
                            </td>
                            <td>
                                <div>{{ $request->chairperson->first_name }} {{ $request->chairperson->last_name }}</div>
                                <small class="text-muted">{{ $request->chairperson->email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $structureLabel }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge['class'] }}">
                                    <i class="bi bi-{{ $statusBadge['icon'] }} me-1"></i>{{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $request->created_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $request->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="viewRequest({{ $request->id }})"
                                            data-request-id="{{ $request->id }}"
                                            data-label="{{ $request->label }}"
                                            data-description="{{ $request->description }}"
                                            data-structure="{{ json_encode($request->structure_config) }}"
                                            data-chairperson="{{ $request->chairperson->first_name }} {{ $request->chairperson->last_name }}"
                                            data-status="{{ $request->status }}"
                                            data-admin-notes="{{ $request->admin_notes }}"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewRequestModal">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if ($request->status === 'pending')
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="approveRequest({{ $request->id }}, '{{ $request->label }}')"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#approveModal">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="rejectRequest({{ $request->id }}, '{{ $request->label }}')"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#rejectModal">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="viewRequestModalLabel">Template Request Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewRequestBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="approveForm">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">Approve Template Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve <strong id="approveTemplateName"></strong>?</p>
                    <p class="text-muted small">This will create a new structure template that can be used by instructors.</p>
                    
                    <div class="mb-3">
                        <label for="approveAdminNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="approveAdminNotes" name="admin_notes" rows="3" placeholder="Add any notes for the chairperson..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectForm">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Template Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong id="rejectTemplateName"></strong>?</p>
                    
                    <div class="mb-3">
                        <label for="rejectAdminNotes" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectAdminNotes" name="admin_notes" rows="3" required placeholder="Explain why this template request is being rejected..."></textarea>
                        <small class="text-muted">This message will be visible to the chairperson.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewRequest(requestId) {
    const button = document.querySelector(`[data-request-id="${requestId}"]`);
    const label = button.dataset.label;
    const description = button.dataset.description;
    const structure = JSON.parse(button.dataset.structure);
    const chairperson = button.dataset.chairperson;
    const status = button.dataset.status;
    const adminNotes = button.dataset.adminNotes;
    
    const structureType = structure.type || 'unknown';
    const structureData = structure.structure || [];
    
    let html = `
        <div class="mb-3">
            <label class="fw-bold text-muted small">Template Name</label>
            <p>${label}</p>
        </div>
        ${description ? `
        <div class="mb-3">
            <label class="fw-bold text-muted small">Description</label>
            <p>${description}</p>
        </div>
        ` : ''}
        <div class="mb-3">
            <label class="fw-bold text-muted small">Submitted By</label>
            <p>${chairperson}</p>
        </div>
        <div class="mb-3">
            <label class="fw-bold text-muted small">Structure Type</label>
            <p><span class="badge bg-info text-dark">${structureType}</span></p>
        </div>
        <div class="mb-3">
            <label class="fw-bold text-muted small">Grading Components</label>
            <div class="mt-2">
    `;
    
    const mainComponents = structureData.filter(c => c.is_main);
    const subComponents = structureData.filter(c => !c.is_main);
    
    mainComponents.forEach(main => {
        html += `
            <div class="card mb-2 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${main.label || 'Unnamed'}</strong>
                            <span class="badge bg-success-subtle text-success ms-2">${main.activity_type || 'other'}</span>
                        </div>
                        <strong class="text-success">${main.weight.toFixed(2)}%</strong>
                    </div>
        `;
        
        const subs = subComponents.filter(s => s.parent_id === main.parent_id);
        if (subs.length > 0) {
            html += '<div class="mt-2 ps-3 border-start border-success">';
            subs.forEach(sub => {
                html += `
                    <div class="d-flex justify-content-between small mb-1">
                        <span><i class="bi bi-arrow-return-right me-1"></i>${sub.label || 'Unnamed'}</span>
                        <span class="text-success">${sub.weight.toFixed(2)}%</span>
                    </div>
                `;
            });
            html += '</div>';
        }
        
        html += '</div></div>';
    });
    
    html += '</div></div>';
    
    if (adminNotes && (status === 'approved' || status === 'rejected')) {
        html += `
            <div class="mb-3">
                <label class="fw-bold text-muted small">Admin Notes</label>
                <div class="alert alert-${status === 'approved' ? 'success' : 'danger'} mb-0">
                    ${adminNotes}
                </div>
            </div>
        `;
    }
    
    document.getElementById('viewRequestBody').innerHTML = html;
}

function approveRequest(requestId, templateName) {
    document.getElementById('approveTemplateName').textContent = templateName;
    document.getElementById('approveForm').action = `/admin/structure-template-requests/${requestId}/approve`;
}

function rejectRequest(requestId, templateName) {
    document.getElementById('rejectTemplateName').textContent = templateName;
    document.getElementById('rejectForm').action = `/admin/structure-template-requests/${requestId}/reject`;
}
</script>
@endpush
