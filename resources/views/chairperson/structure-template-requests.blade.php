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
</style>

<div class="import-courses-wrapper">
    <div class="import-courses-container">
        <!-- Page Title -->
        <div class="page-title">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1>
                        <i class="bi bi-diagram-3"></i>
                        Structure Formula Requests
                    </h1>
                    <p class="page-subtitle">Create and manage your custom grading structure formula requests</p>
                </div>
                <a href="{{ route('chairperson.structureTemplates.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i>New Formula Request
                </a>
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
        $pendingRequests = $requests->where('status', 'pending');
        $approvedRequests = $requests->where('status', 'approved');
        $rejectedRequests = $requests->where('status', 'rejected');
    @endphp

    <div class="card border-0 shadow-sm mb-0">
        <div class="card-body py-3" style="border-radius: 0.75rem 0.75rem 0 0;">
            <div class="d-flex gap-3 align-items-center justify-content-center flex-wrap">
                <div class="text-center">
                    <div class="fw-bold text-warning" style="font-size: 1.5rem;">{{ $pendingRequests->count() }}</div>
                    <small class="text-muted">Pending</small>
                </div>
                <div class="text-center">
                    <div class="fw-bold text-success" style="font-size: 1.5rem;">{{ $approvedRequests->count() }}</div>
                    <small class="text-muted">Approved</small>
                </div>
                <div class="text-center">
                    <div class="fw-bold text-danger" style="font-size: 1.5rem;">{{ $rejectedRequests->count() }}</div>
                    <small class="text-muted">Rejected</small>
                </div>
            </div>
        </div>

    @if ($requests->isEmpty())
        <div class="card-body text-center py-5" style="border-top: 2px solid #e9ecef;">
            <div class="mb-3">
                <i class="bi bi-diagram-3" style="font-size: 4rem; color: #dee2e6;"></i>
            </div>
            <h5 class="text-muted mb-2">No Formula Requests Yet</h5>
            <p class="text-muted mb-3">Create your first custom grading structure formula request.</p>
            <a href="{{ route('chairperson.structureTemplates.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>Create New Request
            </a>
        </div>
    @else
        <div class="card-body p-3" style="border-top: 2px solid #e9ecef;">
            <div class="row g-4">
            @foreach ($requests as $request)
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
                        'custom' => 'Custom Structure',
                        default => 'Unknown',
                    };
                @endphp
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 request-card" data-status="{{ $request->status }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge {{ $statusBadge['class'] }} px-3 py-2">
                                    <i class="bi bi-{{ $statusBadge['icon'] }} me-1"></i>{{ $statusBadge['label'] }}
                                </span>
                                @if ($request->status === 'pending')
                                    <form method="POST" action="{{ route('chairperson.structureTemplates.destroy', $request) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this pending request?');" 
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <h5 class="card-title fw-bold mb-2">{{ $request->label }}</h5>
                            
                            @if ($request->description)
                                <p class="text-muted small mb-3">{{ Str::limit($request->description, 100) }}</p>
                            @endif

                            <div class="mb-3">
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-diagram-2 me-1"></i>{{ $structureLabel }}
                                </span>
                            </div>

                            <div class="text-muted small mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-calendar"></i>
                                    <span>Submitted: {{ $request->created_at->format('M d, Y') }}</span>
                                </div>
                                @if ($request->reviewed_at)
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-person-check"></i>
                                        <span>Reviewed: {{ $request->reviewed_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>

                            @if ($request->admin_notes && in_array($request->status, ['approved', 'rejected']))
                                <div class="alert alert-{{ $request->status === 'approved' ? 'success' : 'danger' }} alert-sm mb-3">
                                    <strong>Admin Notes:</strong>
                                    <p class="mb-0 mt-1 small">{{ $request->admin_notes }}</p>
                                </div>
                            @endif

                            <a href="{{ route('chairperson.structureTemplates.show', $request) }}" class="btn btn-outline-success btn-sm w-100">
                                <i class="bi bi-eye me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    @endif
    </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.request-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.request-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
</style>
@endpush
