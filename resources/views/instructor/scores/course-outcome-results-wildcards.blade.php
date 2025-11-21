@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Course Outcome Attainment Results</li>
        </ol>
    </nav>



    {{-- Subject Wild Cards --}}
    @if(isset($subjects) && count($subjects))
        <div class="row g-4 px-4 py-4" id="subject-selection">
            @foreach($subjects as $subjectItem)
                <div class="col-md-4">
                    <div
                        class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden"
                        data-url="{{ route('instructor.course-outcome-attainments.subject', ['subject' => $subjectItem->id]) }}"
                        style="cursor: pointer;"
                    >
                        <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                            <div class="subject-circle position-absolute start-50"
                                style="top: 100%; width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translate(-50%, -50%);">
                                <h5 class="mb-0 text-white fw-bold">{{ $subjectItem->subject_code }}</h5>
                            </div>
                        </div>
                        <div class="card-body pt-5 text-center">
                            <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $subjectItem->subject_description }}">
                                {{ $subjectItem->subject_description }}
                            </h6>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <div class="text-muted mb-3">
                    <i class="bi bi-folder-x fs-1 opacity-50"></i>
                </div>
                <h5 class="text-muted mb-2">No Subjects Found</h5>
                <p class="text-muted mb-4">
                    @if($academicYear && $semester)
                        No subjects found for the current academic period.
                        <br><strong>Academic Year:</strong> {{ $academicYear }}
                        <br><strong>Semester:</strong> {{ $semester }}
                    @else
                        No active academic period is currently set. Please contact your administrator to set up an active academic period.
                    @endif
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('instructor.dashboard') }}" class="btn btn-success">
                        <i class="bi bi-house me-2"></i>Go to Dashboard
                    </a>
                    @if($academicYear && $semester)
                        <a href="{{ route('instructor.course-outcome-attainments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Subject card click handlers
    document.querySelectorAll('.subject-card[data-url]').forEach(card => {
        card.addEventListener('click', function() {
            window.location.href = this.dataset.url;
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.subject-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.subject-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s ease;
    z-index: 1;
}

.subject-card:hover::before {
    left: 100%;
}

.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 28px rgba(77, 166, 116, 0.3) !important;
}

.subject-card .position-relative {
    transition: background-color 0.3s ease;
}

.subject-card:hover .position-relative {
    background: linear-gradient(135deg, #4da674, #3d8a5e) !important;
}

.subject-circle {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.subject-card:hover .subject-circle {
    transform: translate(-50%, -50%) rotate(5deg) scale(1.05) !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
}

.breadcrumb-item a {
    color: #198754;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #0f5132;
    text-decoration: underline;
}

.form-label {
    color: #495057;
    font-weight: 600;
}

.form-select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.btn-primary {
    background-color: #198754;
    border-color: #198754;
}

.btn-primary:hover {
    background-color: #0f5132;
    border-color: #0f5132;
}
</style>
@endpush
