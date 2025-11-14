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
            @include('instructor.partials.activity-header', ['subject' => $subject, 'term' => $term, 'activityTypes' => $activityTypes])
            <form id="gradeForm" method="POST" action="{{ route('instructor.grades.store') }}">
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

    
    // Global function to show unsaved changes modal
    window.showUnsavedChangesModal = function(onConfirm, onCancel = null) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('unsavedChangesModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal fade" id="unsavedChangesModal" tabindex="-1" aria-labelledby="unsavedChangesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-warning text-dark border-0">
                                <h5 class="modal-title d-flex align-items-center" id="unsavedChangesModalLabel">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Unsaved Changes
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">You have unsaved changes that will be lost if you continue.</p>
                                <p class="mb-0 text-muted">Are you sure you want to leave without saving?</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-warning" id="confirmLeaveBtn">Leave Without Saving</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal.firstElementChild);
        }
        
        const modalInstance = new bootstrap.Modal(document.getElementById('unsavedChangesModal'), {
            backdrop: false
        });
        const confirmBtn = document.getElementById('confirmLeaveBtn');
        
        // Remove any existing event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Add new event listener
        newConfirmBtn.addEventListener('click', function() {
            modalInstance.hide();
            if (onConfirm) onConfirm();
        });
        
        // Handle cancel
        document.getElementById('unsavedChangesModal').addEventListener('hidden.bs.modal', function() {
            if (onCancel) onCancel();
        }, { once: true });
        
        modalInstance.show();
    };

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('fadeOverlay');
    
    // Make overlay globally accessible
    window.gradeOverlay = overlay;
    
    // Handle subject card clicks using event delegation
    const subjectSelectionArea = document.getElementById('subject-selection');
    if (subjectSelectionArea) {
        subjectSelectionArea.addEventListener('click', function(e) {
            const subjectCard = e.target.closest('.subject-card[data-url]');
            if (!subjectCard) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            const url = subjectCard.dataset.url;
            console.log('Subject card clicked, URL:', url);
            
            if (!url) {
                console.error('No URL found for subject card');
                return;
            }
            
            // Check for unsaved changes before subject navigation (only if function exists and we're in grades view)
            if (typeof checkForChanges === 'function' && document.getElementById('studentTableBody')) {
                try {
                    const { hasChanges } = checkForChanges();
                    if (hasChanges) {
                        showUnsavedChangesModal(() => {
                            // User confirmed, proceed with navigation
                            navigateToSubject(url, window.gradeOverlay || document.getElementById('fadeOverlay'));
                        });
                        return;
                    }
                } catch (error) {
                    console.error('Error checking for changes:', error);
                }
            }
            
            // No unsaved changes, proceed directly
            navigateToSubject(url, window.gradeOverlay || document.getElementById('fadeOverlay'));
        });
    }
    
    function loadGradeSection(url, overlay) {
        if (!url) {
            return Promise.reject(new Error('Missing grade section URL.'));
        }

        const overlayRef = overlay || window.gradeOverlay || document.getElementById('fadeOverlay');
        if (overlayRef) overlayRef.classList.remove('d-none');

        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`Request failed with status ${res.status}`);
            }
            return res.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGradeSection = doc.querySelector('#grade-section');
            const currentSection = document.getElementById('grade-section');

            if (!newGradeSection || !currentSection) {
                throw new Error('Grade section markup missing from response.');
            }

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

            if (overlayRef) overlayRef.classList.add('d-none');
            return true;
        })
        .catch(error => {
            if (overlayRef) overlayRef.classList.add('d-none');
            throw error;
        });
    }

    // Extract subject navigation logic to reusable function
    function navigateToSubject(url, overlay) {
        console.log('Navigating to subject URL:', url);
        loadGradeSection(url, overlay).catch(error => {
            console.error('Error loading grades:', error);
            alert('Failed to load subject grades.');
        });
    }

    // Handle term step clicks
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.term-step');
        if (!button) return;
        
        const term = button.dataset.term;
        const subjectInput = document.querySelector('input[name="subject_id"]');
        if (!term || !subjectInput) return;
        
        const subjectId = subjectInput.value;
        if (!subjectId) return;
        
        // Check for unsaved changes before term navigation (only if function exists and we're in grades view)
        if (typeof checkForChanges === 'function' && document.getElementById('studentTableBody')) {
            const { hasChanges } = checkForChanges();
            if (hasChanges) {
                showUnsavedChangesModal(() => {
                    // User confirmed, proceed with navigation
                    navigateToTerm(subjectId, term, window.gradeOverlay || document.getElementById('fadeOverlay'));
                });
                return;
            }
        }
        
        // No unsaved changes, proceed directly
        navigateToTerm(subjectId, term, window.gradeOverlay || document.getElementById('fadeOverlay'));
    });
    
    // Extract term navigation logic to reusable function
    function navigateToTerm(subjectId, term, overlay) {
        const url = `/instructor/grades/partial?subject_id=${subjectId}&term=${term}`;
        loadGradeSection(url, overlay).catch(error => {
            console.error('Error loading term data:', error);
            alert('Failed to load term data.');
        });
    }

    function refreshGradeSection() {
        const subjectInput = document.querySelector('input[name="subject_id"]');
        const termInput = document.querySelector('input[name="term"]');

        if (!subjectInput || !termInput) {
            return Promise.resolve();
        }

        const url = `/instructor/grades/partial?subject_id=${encodeURIComponent(subjectInput.value)}&term=${encodeURIComponent(termInput.value)}`;

        return loadGradeSection(url)
            .catch(error => {
                console.error('Error refreshing grade section:', error);
                throw new Error('Grades were saved, but the table could not refresh automatically. Please reload the page.');
            });
    }

    window.refreshGradeSection = refreshGradeSection;
    
});
</script>
@endpush
