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
            @include('instructor.partials.activity-header', [
                'subject' => $subject,
                'term' => $term,
                'activityTypes' => $activityTypes,
                'componentStatus' => $componentStatus ?? null,
            ])
            <form id="gradeForm" method="POST" action="{{ route('instructor.grades.store') }}" data-no-page-loader="true">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="term" value="{{ $term }}">
                @include('instructor.partials.grade-table')
            </form>
        @endif
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
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('#subject-selection .subject-card[data-url]');
    if (!cards.length) {
        return;
    }

    cards.forEach(card => {
        if (card.dataset.clickBound === 'true') {
            return;
        }

        card.dataset.clickBound = 'true';
        card.setAttribute('role', 'button');
        card.tabIndex = 0;

        const navigate = () => {
            const url = card.dataset.url;
            if (url) {
                window.location.href = url;
            }
        };

        card.addEventListener('click', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            if (event.target.closest('a, button, input, label, select, textarea')) {
                return;
            }

            navigate();
        });

        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                navigate();
            }
        });
    });
});

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

// The rest of the navigation/partial loading functions are implemented inside the included grade-script partial.
</script>
@endpush
