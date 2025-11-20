@php
    function ordinalSuffix($n) {
        $suffixes = ['th', 'st', 'nd', 'rd'];
        $remainder = $n % 100;
        return $n . ($suffixes[($remainder - 20) % 10] ?? $suffixes[$remainder] ?? $suffixes[0]);
    }
@endphp

@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="bi bi-person-badge text-success me-2"></i>
            Assign Courses to Instructors
        </h1>

        <!-- View Mode Switcher -->
        <div class="d-flex align-items-center">
            <label for="viewMode" class="me-2 fw-semibold">View Mode:</label>
            <select id="viewMode" class="form-select form-select-sm w-auto" onchange="toggleViewMode()">
                <option value="year" selected>Year View</option>
                <option value="full">Full View</option>
            </select>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- YEAR VIEW (Tabbed) -->
    <div id="yearView">
        <!-- Year Level Tabs -->
        <ul class="nav nav-tabs" id="yearTabs" role="tablist">
            @for ($level = 1; $level <= 4; $level++)
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $level === 1 ? 'active' : '' }}"
                       id="year-level-{{ $level }}"
                       data-bs-toggle="tab"
                       href="#level-{{ $level }}"
                       role="tab"
                       aria-controls="level-{{ $level }}"
                       aria-selected="{{ $level === 1 ? 'true' : 'false' }}">
                       {{ ordinalSuffix($level) }} Year
                    </a>
                </li>
            @endfor
        </ul>

        <div class="tab-content" id="yearTabsContent">
            @for ($level = 1; $level <= 4; $level++)
                @php
                    $subjectsByYear = $yearLevels[$level] ?? collect();
                @endphp

                <div class="tab-pane fade {{ $level === 1 ? 'show active' : '' }}"
                     id="level-{{ $level }}"
                     role="tabpanel"
                     aria-labelledby="year-level-{{ $level }}">
                    <div class="bg-white shadow rounded-4 overflow-x-auto mt-3">
                        @if ($subjectsByYear->isNotEmpty())
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-success">
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Description</th>
                                        <th>Assigned Instructor</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectsByYear as $subject)
                                        <tr>
                                            <td>{{ $subject->subject_code }}</td>
                                            <td>{{ $subject->subject_description }}</td>
                                            <td>{{ $subject->instructor ? $subject->instructor->name : '—' }}</td>
                                            <td class="text-center">
                                                @if ($subject->instructor)
                                                    <button
                                                        onclick="openConfirmUnassignModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')"
                                                        class="btn btn-danger btn-sm">
                                                        <i class="bi bi-x-circle me-1"></i> Unassign
                                                    </button>
                                                @else
                                                    <button
                                                        onclick="openConfirmAssignModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')"
                                                        class="btn btn-success shadow-sm btn-sm">
                                                        <i class="bi bi-person-plus me-1"></i> Assign
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                        <div class="bg-warning bg-opacity-25 text-warning border border-warning px-4 py-3 rounded-4 shadow-sm">
                            No subjects available for {{ ordinalSuffix($level) }} Year.
                        </div>
                        @endif
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <!-- FULL VIEW (All Years) -->
    <div id="fullView" class="d-none">
        <div class="row g-4">
            @for ($level = 1; $level <= 4; $level++)
                @php
                    $subjectsByYear = $yearLevels[$level] ?? collect();
                @endphp
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 fw-semibold text-success">
                                    {{ ordinalSuffix($level) }} Year
                                </h5>
                                <span class="badge bg-success-subtle text-success ms-3">
                                    {{ $subjectsByYear->count() }} {{ Str::plural('subject', $subjectsByYear->count()) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if ($subjectsByYear->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-success">
                                            <tr>
                                                <th class="border-0 py-3">Subject Code</th>
                                                <th class="border-0 py-3">Description</th>
                                                <th class="border-0 py-3">Assigned Instructor</th>
                                                <th class="border-0 py-3 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($subjectsByYear as $subject)
                                                <tr>
                                                    <td class="fw-medium">{{ $subject->subject_code }}</td>
                                                    <td>{{ $subject->subject_description }}</td>
                                                    <td>
                                                        @if($subject->instructor)
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-person-check-fill text-success me-2"></i>
                                                                <span>{{ $subject->instructor->name }}</span>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($subject->instructor)
                                                            <button
                                                                onclick="openConfirmUnassignModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')"
                                                                class="btn btn-outline-danger btn-sm" 
                                                                title="Unassign Instructor">
                                                                <i class="bi bi-x-circle me-1"></i> Unassign
                                                            </button>
                                                        @else
                                                            <button
                                                                onclick="openConfirmAssignModal({{ $subject->id }}, '{{ addslashes($subject->subject_code . ' - ' . $subject->subject_description) }}')"
                                                                class="btn btn-success shadow-sm btn-sm" 
                                                                title="Assign Instructor">
                                                                <i class="bi bi-person-plus me-1"></i> Assign
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="bi bi-journal-x display-6"></i>
                                    </div>
                                    <p class="text-muted mb-0">No subjects available for {{ ordinalSuffix($level) }} Year.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>

{{-- Confirm Unassign Modal --}}
<div id="confirmUnassignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-lg rounded-4 shadow-lg overflow-hidden flex flex-col">
        <div class="bg-danger text-white px-4 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Unassign
            </h5>
            <button onclick="closeConfirmUnassignModal()" class="btn-close btn-close-white" aria-label="Close"></button>
        </div>
        <div class="p-4">
            <p>Are you sure you want to unassign this subject? This action cannot be undone.</p>
            <form id="unassignForm" action="{{ route('chairperson.toggleAssignedSubject') }}" method="POST">
                @csrf
                <input type="hidden" name="subject_id" id="unassign_subject_id">
                <input type="hidden" name="instructor_id" value="">
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Unassign
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmUnassignModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Confirm Assign Modal --}}
<div id="confirmAssignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-lg rounded-4 shadow-lg overflow-hidden flex flex-col">
        <div class="bg-success text-white px-4 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-check-circle-fill me-2"></i> Confirm Assign
            </h5>
            <button onclick="closeConfirmAssignModal()" class="btn-close btn-close-white" aria-label="Close"></button>
        </div>
        <div class="p-4">
            <p>Select the instructor to assign this subject to:</p>
            <form id="assignForm" method="POST" action="{{ route('chairperson.storeAssignedSubject') }}" class="vstack gap-3">
                @csrf
                <input type="hidden" name="subject_id" id="assign_subject_id">
                <div>
                    <label class="form-label">Instructor</label>
                    <select name="instructor_id" class="form-select" required>
                        <option value="">-- Choose Instructor --</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Assign
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmAssignModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openConfirmUnassignModal(subjectId, subjectName) {
        document.getElementById('unassign_subject_id').value = subjectId;
        const modal = document.getElementById('confirmUnassignModal');
        modal.classList.remove('hidden');
        modal.classList.add('d-flex');
    }

    function closeConfirmUnassignModal() {
        const modal = document.getElementById('confirmUnassignModal');
        modal.classList.add('hidden');
        modal.classList.remove('d-flex');
    }

    function openConfirmAssignModal(subjectId, subjectName) {
        document.getElementById('assign_subject_id').value = subjectId;
        const modal = document.getElementById('confirmAssignModal');
        modal.classList.remove('hidden');
        modal.classList.add('d-flex');
    }

    function closeConfirmAssignModal() {
        const modal = document.getElementById('confirmAssignModal');
        modal.classList.add('hidden');
        modal.classList.remove('d-flex');
    }

    function toggleViewMode() {
        const mode = document.getElementById('viewMode').value;
        const yearView = document.getElementById('yearView');
        const fullView = document.getElementById('fullView');

        if (mode === 'full') {
            yearView.classList.add('d-none');
            fullView.classList.remove('d-none');
        } else {
            yearView.classList.remove('d-none');
            fullView.classList.add('d-none');
        }
    }
</script>
@endpush

@push('styles')
<style>
    .bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.1);
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    .btn-outline-success:hover, .btn-outline-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .table-success {
        background-color: #198754 !important;
    }
    .table-success th {
        color: #000000 !important;
        font-weight: 500;
    }
</style>
@endpush
@endsection