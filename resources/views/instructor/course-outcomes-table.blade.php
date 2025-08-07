@extends('layouts.app')

@section('content')
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080; min-width: 350px;">
    @if(session('success'))
        <div class="toast align-items-center text-bg-success border-0 show mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" id="toast-success">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-dark" id="toast-success-bar" role="progressbar" style="width: 100%; transition: width 5s linear;"></div>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="toast align-items-center text-bg-danger border-0 show mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" id="toast-error">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-dark" id="toast-error-bar" role="progressbar" style="width: 100%; transition: width 5s linear;"></div>
            </div>
        </div>
    @endif
    @if(session('info'))
        <div class="toast align-items-center text-bg-info border-0 show mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" id="toast-info">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('info') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-dark" id="toast-info-bar" role="progressbar" style="width: 100%; transition: width 5s linear;"></div>
            </div>
        </div>
    @endif
</div>
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('instructor.course_outcomes.index') }}">Course Outcomes</a></li>
            @if(isset($selectedSubject))
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}
                </li>
            @endif
        </ol>
    </nav>

    {{-- Subject Info --}}
    @if(isset($selectedSubject))
        <div class="mb-4">
            <h4 class="fw-bold">Subject: {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}</h4>
        </div>
    @endif

    {{-- Add Course Outcome Button --}}
    <div class="mb-3 text-end">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
            + Add Course Outcome
        </button>
    </div>

    {{-- Course Outcomes Table Section --}}
    <div class="mt-4">
        @if($cos && $cos->count())
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>CO Code</th>
                                <th>Identifier</th>
                                <th>Description</th>
                                <th>Academic Period</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cos as $co)
                                <tr>
                                    <td class="fw-semibold">{{ $co->co_code }}</td>
                                    <td>{{ $co->co_identifier }}</td>
                                    <td>{{ $co->description }}</td>
                                    <td>
                                        @if($co->academicPeriod)
                                            {{ $co->academicPeriod->academic_year }} - {{ $co->academicPeriod->semester }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-success btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCourseOutcomeModal"
                                            data-id="{{ $co->id }}"
                                            data-co_code="{{ $co->co_code }}"
                                            data-co_identifier="{{ $co->co_identifier }}"
                                            data-description="{{ $co->description }}"
                                            data-academic_period_id="{{ $co->academic_period_id }}"
                                        >
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <form action="{{ route('instructor.course_outcomes.destroy', $co->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this course outcome?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="alert alert-warning bg-warning-subtle text-dark border-0 text-center">
                No course outcomes found for this subject.
            </div>
        @endif
    </div>
</div>


{{-- Add Course Outcome Modal --}}
<div class="modal fade" id="addCourseOutcomeModal" tabindex="-1" aria-labelledby="addCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('instructor.course_outcomes.store') }}">
            @csrf
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="addCourseOutcomeModalLabel">➕ Add Course Outcome</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">CO Code <span class="text-danger">*</span></label>
                        <input type="text" name="co_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identifier <span class="text-danger">*</span></label>
                        <input type="text" name="co_identifier" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Period <span class="text-danger">*</span></label>
                        <input type="hidden" name="academic_period_id" value="{{ $currentPeriod->id ?? '' }}">
                        <select class="form-select" disabled>
                            <option value="{{ $currentPeriod->id ?? '' }}">
                                {{ $currentPeriod->academic_year ?? '' }} - {{ $currentPeriod->semester ?? '' }}
                            </option>
                        </select>
                    </div>
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id ?? '' }}">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Outcome</button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- Edit Course Outcome Modal (not nested, only once at the bottom) --}}
<div class="modal fade" id="editCourseOutcomeModal" tabindex="-1" aria-labelledby="editCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="editCourseOutcomeForm">
            @csrf
            @method('PUT')
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="editCourseOutcomeModalLabel">✏️ Edit Course Outcome</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">CO Code <span class="text-danger">*</span></label>
                        <input type="text" name="co_code" id="edit_co_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identifier <span class="text-danger">*</span></label>
                        <input type="text" name="co_identifier" id="edit_co_identifier" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Period <span class="text-danger">*</span></label>
                        <input type="hidden" name="academic_period_id" id="edit_academic_period_id" value="{{ $currentPeriod->id ?? '' }}">
                        <select class="form-select" disabled>
                            <option value="{{ $currentPeriod->id ?? '' }}">
                                {{ $currentPeriod->academic_year ?? '' }} - {{ $currentPeriod->semester ?? '' }}
                            </option>
                        </select>
                    </div>
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id ?? '' }}">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Outcome</button>
                </div>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sync toast progress bar with Bootstrap toast timer   
    function syncToastBar(toastId, barId, duration = 5000) {
        var toastEl = document.getElementById(toastId);
        var barEl = document.getElementById(barId);
        if (toastEl && barEl) {
            barEl.style.width = '100%';
            barEl.style.background = '#343a40'; // Bootstrap dark
            barEl.style.opacity = '1';
            var barDuration = 4000; // 4 seconds, faster than toast
            var start = Date.now();
        var interval = setInterval(function() {
            var elapsed = Date.now() - start;
            var percent = Math.max(0, 100 - (elapsed / barDuration) * 100);
            barEl.style.width = percent + '%';
            if (elapsed >= barDuration) {
                barEl.style.width = '0%';
                barEl.style.opacity = '0.5';
                clearInterval(interval);
            }
        }, 16); // ~60fps
            // Listen for toast hidden event to clear bar immediately if closed early
            toastEl.addEventListener('hidden.bs.toast', function() {
                barEl.style.width = '0%';
                barEl.style.opacity = '0.5';
                clearInterval(interval);
            });
            // Use Bootstrap Toast API for auto-hide
            if (window.bootstrap && window.bootstrap.Toast) {
                var toastObj = bootstrap.Toast.getOrCreateInstance(toastEl);
                toastObj.show();
            }
        }
    }
    syncToastBar('toast-success', 'toast-success-bar');
    syncToastBar('toast-error', 'toast-error-bar');
    syncToastBar('toast-info', 'toast-info-bar');

    // Existing edit modal logic
    var editModal = document.getElementById('editCourseOutcomeModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var co_code = button.getAttribute('data-co_code');
        var co_identifier = button.getAttribute('data-co_identifier');
        var description = button.getAttribute('data-description');
        var academic_period_id = button.getAttribute('data-academic_period_id');

        document.getElementById('edit_co_code').value = co_code;
        document.getElementById('edit_co_identifier').value = co_identifier;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_academic_period_id').value = academic_period_id;

        // Set the form action dynamically
        var form = document.getElementById('editCourseOutcomeForm');
        form.action = '/instructor/course_outcomes/' + id;
    });
});
</script>
@endpush
@endsection