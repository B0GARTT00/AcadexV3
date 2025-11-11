@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none" style="color: #198754;">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.gradesFormula', ['view' => 'formulas']) }}" class="text-decoration-none" style="color: #198754;">
                            <i class="bi bi-sliders me-1"></i>Grades Formula
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.structureTemplateRequests.index') }}" class="text-decoration-none" style="color: #198754;">
                            Template Requests
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $request->label }}</li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-clipboard-check text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1" style="color: #198754;">{{ $request->label }}</h3>
                        <p class="text-muted mb-0">Template Request Details</p>
                    </div>
                </div>
                <a href="{{ route('admin.structureTemplateRequests.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Requests
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

    @php
        $statusBadge = match ($request->status) {
            'pending' => ['class' => 'bg-warning text-dark', 'icon' => 'clock-history', 'label' => 'Pending Review'],
            'approved' => ['class' => 'bg-success', 'icon' => 'check-circle', 'label' => 'Approved'],
            'rejected' => ['class' => 'bg-danger', 'icon' => 'x-circle', 'label' => 'Rejected'],
            default => ['class' => 'bg-secondary', 'icon' => 'question-circle', 'label' => 'Unknown'],
        };
        
        $structureType = $request->structure_config['type'] ?? 'unknown';
        $structureLabel = match ($structureType) {
            'lecture_only' => 'Lecture Only',
            'lecture_lab' => 'Lecture + Lab',
            'custom' => 'Custom',
            default => 'Unknown',
        };
    @endphp

    <div class="row g-4">
        <!-- Status and Info Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3" style="color: #198754;">Status</h5>
                    <div class="mb-3">
                        <span class="badge {{ $statusBadge['class'] }} fs-6 px-3 py-2">
                            <i class="bi bi-{{ $statusBadge['icon'] }} me-1"></i>{{ $statusBadge['label'] }}
                        </span>
                    </div>
                    
                    <h6 class="fw-semibold mt-4 mb-2">Submitted By</h6>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-person-circle text-muted"></i>
                        <span>{{ $request->chairperson->first_name }} {{ $request->chairperson->last_name }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-envelope"></i>
                        <span>{{ $request->chairperson->email }}</span>
                    </div>
                    
                    <h6 class="fw-semibold mt-4 mb-2">Submitted On</h6>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event text-muted"></i>
                        <span>{{ $request->created_at->format('F d, Y') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-clock"></i>
                        <span>{{ $request->created_at->format('h:i A') }}</span>
                    </div>
                    
                    @if ($request->reviewed_at)
                        <h6 class="fw-semibold mt-4 mb-2">Reviewed</h6>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-person-check text-muted"></i>
                            <span>{{ $request->reviewer ? $request->reviewer->first_name . ' ' . $request->reviewer->last_name : 'N/A' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small">
                            <i class="bi bi-calendar-check"></i>
                            <span>{{ $request->reviewed_at->format('F d, Y h:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if ($request->status === 'pending')
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3" style="color: #198754;">Actions</h5>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle me-1"></i>Approve Request
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i>Reject Request
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Template Details Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3" style="color: #198754;">Template Information</h5>
                    
                    <div class="mb-4">
                        <label class="fw-semibold text-muted small">Template Name</label>
                        <div class="fs-5">{{ $request->label }}</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="fw-semibold text-muted small">Structure Type</label>
                        <div>
                            <span class="badge bg-info text-dark">{{ $structureLabel }}</span>
                        </div>
                    </div>
                    
                    @if ($request->description)
                        <div class="mb-4">
                            <label class="fw-semibold text-muted small">Description</label>
                            <div class="text-muted">{{ $request->description }}</div>
                        </div>
                    @endif

                    @if ($request->admin_notes)
                        <div class="alert alert-info">
                            <strong><i class="bi bi-info-circle me-1"></i>Admin Notes:</strong>
                            <p class="mb-0 mt-2">{{ $request->admin_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Structure Configuration Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3" style="color: #198754;">Structure Configuration</h5>
                    
                    @php
                        $structure = $request->structure_config['structure'] ?? [];
                        $mainComponents = collect($structure)->where('is_main', true);
                        $subComponents = collect($structure)->where('is_main', false)->groupBy('parent_id');
                    @endphp
                    
                    @if ($mainComponents->isEmpty())
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>No structure components defined.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component</th>
                                        <th>Type</th>
                                        <th class="text-end">Weight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mainComponents as $component)
                                        <tr class="fw-bold">
                                            <td>{{ $component['label'] ?? 'Unnamed Component' }}</td>
                                            <td>
                                                @if (isset($component['activity_type']))
                                                    <span class="badge bg-primary">{{ strtoupper($component['activity_type']) }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Composite</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($component['weight'] ?? 0, 1) }}%</td>
                                        </tr>
                                        @if (isset($subComponents[$component['id']]))
                                            @foreach ($subComponents[$component['id']] as $sub)
                                                <tr class="text-muted">
                                                    <td class="ps-4">
                                                        <i class="bi bi-arrow-return-right me-1"></i>{{ $sub['label'] ?? 'Unnamed Sub-component' }}
                                                    </td>
                                                    <td>
                                                        @if (isset($sub['activity_type']))
                                                            <span class="badge bg-info text-dark">{{ strtoupper($sub['activity_type']) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ number_format($sub['weight'] ?? 0, 1) }}%</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2">Total</td>
                                        <td class="text-end">{{ number_format($mainComponents->sum('weight'), 1) }}%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($request->status === 'pending')
    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title" id="approveModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Approve Template Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.structureTemplateRequests.approve', $request) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-3">Are you sure you want to approve this template request? This will create a new structure template that can be used by chairpersons and instructors.</p>
                        
                        <div class="alert alert-info mb-3">
                            <strong>Template Name:</strong> {{ $request->label }}
                        </div>
                        
                        <div class="mb-3">
                            <label for="approve_admin_notes" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="approve_admin_notes" name="admin_notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="bi bi-x-circle me-2"></i>Reject Template Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.structureTemplateRequests.reject', $request) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-3">Please provide a reason for rejecting this template request. This information will be visible to the chairperson who submitted it.</p>
                        
                        <div class="alert alert-warning mb-3">
                            <strong>Template Name:</strong> {{ $request->label }}
                        </div>
                        
                        <div class="mb-3">
                            <label for="reject_admin_notes" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror" id="reject_admin_notes" name="admin_notes" rows="4" required placeholder="Explain why this request is being rejected..."></textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
