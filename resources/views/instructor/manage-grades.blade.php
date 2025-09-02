@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div id="grade-section">
        @if (!$subject)
            @if(count($subjects))
                <div class="row g-4 px-4 py-4" id="subject-selection">
                    @foreach($subjects as $subjectItem)
                        <div class="col-md-4">
                            <div
                                class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                                data-url="{{ route('instructor.grades.index') }}?subject_id={{ $subjectItem->id }}&term=prelim"
                                style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                            >
                                {{-- Top header --}}
                                <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                                    <div class="subject-circle position-absolute start-50 translate-middle"
                                        style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                        <h5 class="mb-0 text-white fw-bold">{{ $subjectItem->subject_code }}</h5>
                                    </div>
                                </div>

                                {{-- Card body --}}
                                <div class="card-body pt-5 text-center">
                                    <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $subjectItem->subject_description }}">
                                        {{ $subjectItem->subject_description }}
                                    </h6>

                                    {{-- Footer badges --}}
                                    <div class="d-flex justify-content-between align-items-center mt-4 px-2">
                                        <span class="badge bg-light border text-secondary px-3 py-2 rounded-pill">
                                            👥 {{ $subjectItem->students_count }} Students
                                        </span>
                                        <span class="badge px-3 py-2 fw-semibold text-uppercase rounded-pill
                                            @if($subjectItem->grade_status === 'completed') bg-success
                                            @elseif($subjectItem->grade_status === 'pending') bg-warning text-dark
                                            @else bg-secondary
                                            @endif">
                                            @if($subjectItem->grade_status === 'completed')
                                                ✔ Completed
                                            @elseif($subjectItem->grade_status === 'pending')
                                                ⏳ Pending
                                            @else
                                                ⭕ Not Started
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center mt-5 rounded">
                    No subjects have been assigned to you yet.
                </div>
            @endif
        @else
            @include('instructor.partials.term-stepper')
            @include('instructor.partials.activity-header', ['subject' => $subject, 'term' => $term])
            <form method="POST" action="{{ route('instructor.grades.store') }}">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="term" value="{{ $term }}">
                @include('instructor.partials.grade-table')
            </form>
        @endif
    </div>

    <div id="fadeOverlay" class="fade-overlay d-none">
        <div class="spinner"></div>
    </div>
</div>

@if(session('success'))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
        <div class="toast show align-items-center text-bg-success border-0 shadow" role="alert">
            <div class="d-flex">
                <div class="toast-body">{{ session('success') }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/grade-table.css') }}">
<style>
.fade-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.75);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    transition: opacity 0.3s ease-in-out;
}
.fade-overlay.d-none {
    display: none !important;
}
.spinner {
    border: 4px solid #e5e7eb;
    border-top: 4px solid #4da674;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 0.6s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.subject-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.subject-card:hover {
    transform: scale(1.05);
    box-shadow: 0 20px 30px rgba(0,0,0,0.1);
}
.subject-circle {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}
.subject-card:hover .subject-circle {
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    transform: translate(-50%, -55%) scale(1.05);
}
</style>
@endpush

@push('scripts')
@include('instructor.partials.grade-script')

<script>
// Make function globally available
window.initializeCourseOutcomeDropdowns = initializeCourseOutcomeDropdowns;

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('fadeOverlay');
    
    // Handle subject card clicks
    document.querySelectorAll('.subject-card[data-url]').forEach(card => {
        if (!card) return;
        
        card.addEventListener('click', function() {
            const url = this.dataset.url;
            if (!url) return;
            
            if (overlay) overlay.classList.remove('d-none');

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newGradeSection = doc.querySelector('#grade-section');
                const currentSection = document.getElementById('grade-section');
                
                if (newGradeSection && currentSection) {
                    currentSection.replaceWith(newGradeSection);
                    if (typeof bindGradeInputEvents === 'function') {
                        bindGradeInputEvents();
                    }
                    if (typeof initializeCourseOutcomeDropdowns === 'function') {
                        initializeCourseOutcomeDropdowns();
                    }
                    if (typeof initializeStudentSearch === 'function') {
                        initializeStudentSearch();
                    }
                }
                
                if (overlay) overlay.classList.add('d-none');
            })
            .catch(error => {
                console.error('Error loading grades:', error);
                if (overlay) overlay.classList.add('d-none');
                alert('Failed to load subject grades.');
            });
        });
    });

    // Handle term step clicks
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.term-step');
        if (!button) return;
        
        const term = button.dataset.term;
        const subjectInput = document.querySelector('input[name="subject_id"]');
        if (!term || !subjectInput) return;
        
        const subjectId = subjectInput.value;
        if (!subjectId) return;
        
        if (overlay) overlay.classList.remove('d-none');

        fetch(`/instructor/grades/partial?subject_id=${subjectId}&term=${term}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGradeSection = doc.querySelector('#grade-section');
            const currentSection = document.getElementById('grade-section');
            
            if (newGradeSection && currentSection) {
                currentSection.replaceWith(newGradeSection);
                if (typeof bindGradeInputEvents === 'function') {
                    bindGradeInputEvents();
                }
                if (typeof initializeCourseOutcomeDropdowns === 'function') {
                    initializeCourseOutcomeDropdowns();
                }
                if (typeof initializeStudentSearch === 'function') {
                    initializeStudentSearch();
                }
            }
            
            if (overlay) overlay.classList.add('d-none');
        })
        .catch(error => {
            console.error('Error loading term data:', error);
            if (overlay) overlay.classList.add('d-none');
            alert('Failed to load term data.');
        });
    });
});
</script>
@endpush
