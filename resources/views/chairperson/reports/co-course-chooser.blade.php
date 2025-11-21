@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-book text-success me-2"></i>Course Outcomes Summary
            </h2>
            <p class="text-muted mb-0">Select a course to view detailed Course Outcome compliance</p>
        </div>
        <div>
            @if($academicYear && $semester)
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill me-2">
                    <i class="bi bi-calendar3 me-1"></i>{{ $academicYear }} – {{ $semester }}
                </span>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4 px-4 py-2">
        @forelse($courses as $c)
            <div class="col-md-4">
                <div class="course-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" 
                     data-url="{{ route('chairperson.reports.co-course') }}?course_id={{ $c->id }}"
                     style="cursor: pointer;">
                    <div class="position-relative" style="height: 80px;">
                        <div class="course-circle position-absolute start-50 translate-middle"
                            style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <h5 class="mb-0 text-white fw-bold">{{ $c->course_code }}</h5>
                        </div>
                    </div>
                    <div class="card-body pt-5 text-center">
                        <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $c->course_description }}">
                            {{ $c->course_description }}
                        </h6>
                        <div class="mt-3">
                            <span class="btn btn-success">
                                <i class="bi bi-arrow-right-circle me-1"></i> View Courses
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="bi bi-journal-x fs-1 opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No Courses Found</h5>
                        <p class="text-muted mb-0">No courses available at this time.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
/* Enhanced Card Styling - Matching Chairperson View Grades */
.course-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.course-card * {
    pointer-events: none;
}

.course-card {
    pointer-events: auto;
}

.course-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 24px rgba(77, 166, 116, 0.3) !important;
}

.course-card .position-relative {
    background: linear-gradient(135deg, #4da674 0%, #3d8a5e 100%) !important;
}

.course-card:hover .position-relative {
    background: linear-gradient(135deg, #3d8a5e 0%, #2d6a4e 100%) !important;
}

.course-circle {
    transition: all 0.3s ease;
}

.course-card:hover .course-circle {
    transform: translate(-50%, -50%) rotate(5deg) scale(1.1) !important;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
}

.course-card .card-body {
    background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    transition: background 0.3s;
}

.course-card:hover .card-body {
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
}

.course-card h6 {
    transition: color 0.3s;
}

.course-card:hover h6 {
    color: #4da674 !important;
}

.course-card .btn {
    transition: all 0.3s;
}

.course-card:hover .btn {
    transform: scale(1.05);
}

.course-circle h5 {
  font-size: 1.1rem;
  white-space: normal;
  word-break: break-word;
  text-align: center;
  line-height: 1.1;
  max-width: 70px;
  margin: 0 auto;
}

.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1);
}

@media (max-width: 600px) {
  .course-circle h5 { 
    font-size: 0.95rem; 
  }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.course-card[data-url]').forEach(card => {
        card.addEventListener('click', function(e) {
            const url = this.dataset.url;
            if (url) {
                window.location.href = url;
            }
        });
        
        // Add keyboard support
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const url = this.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            }
        });
    });
});
</script>
@endpush
@endsection
