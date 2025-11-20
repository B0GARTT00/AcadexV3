@extends('layouts.app')

@push('styles')
<style>
:root {
    --theme-green: #198754;
    --theme-green-rgb: 25, 135, 84;
    --theme-green-light: #EAF8E7;
    --theme-green-light-rgb: 234, 248, 231;
}

/* Smooth transitions */
.table-row-transition {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-highlight {
    animation: highlightFade 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Smooth highlight animations */
.highlight-success {
    animation: successPulse 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.highlight-danger {
    animation: dangerPulse 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes highlightFade {
    0% { background-color: rgba(var(--theme-green-rgb), 0.15); }
    100% { background-color: transparent; }
}

@keyframes successPulse {
    0% { background-color: rgba(var(--theme-green-rgb), 0.1); }
    50% { background-color: rgba(var(--theme-green-rgb), 0.15); }
    100% { background-color: rgba(var(--theme-green-rgb), 0.1); }
}

@keyframes dangerPulse {
    0% { background-color: rgba(220, 53, 69, 0.1); }
    50% { background-color: rgba(220, 53, 69, 0.15); }
    100% { background-color: rgba(220, 53, 69, 0.1); }
}

/* Custom scrollbar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(var(--theme-green-rgb), 0.2) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(var(--theme-green-rgb), 0.2);
    border-radius: 20px;
}

/* Hover effects */
.hover-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important;
}

.table-hover tbody tr {
    transition: all 0.2s ease;
}

.btn {
    transition: all 0.2s ease;
}

/* Loading indicator */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.loading-overlay.show {
    opacity: 1;
    visibility: visible;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid var(--theme-green);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Toast notification */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
}

.custom-toast {
    min-width: 250px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.custom-toast.show {
    opacity: 1;
    transform: translateX(0);
}

/* File upload enhancement */
.file-upload-wrapper {
    position: relative;
    overflow: hidden;
}

.file-upload-wrapper input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.upload-trigger {
    display: flex;
    align-items: center;
    padding: 8px 16px;
    border: 2px dashed var(--theme-green);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: var(--theme-green-light);
}

.upload-trigger:hover {
    background-color: rgba(var(--theme-green-rgb), 0.1);
}

/* Progress bar */
.progress-thin {
    height: 2px;
    background-color: rgba(var(--theme-green-rgb), 0.1);
}

.progress-thin .progress-bar {
    background-color: var(--theme-green);
}

/* Custom theme overrides */
.btn-primary {
    background-color: var(--theme-green);
    border-color: var(--theme-green);
}

.btn-primary:hover, .btn-primary:focus {
    background-color: #157347;
    border-color: #146c43;
}

.btn-primary:disabled {
    background-color: var(--theme-green);
    border-color: var(--theme-green);
}

.text-primary {
    color: var(--theme-green) !important;
}

.bg-primary {
    background-color: var(--theme-green) !important;
}

.border-primary {
    border-color: var(--theme-green) !important;
}

.form-check-input:checked {
    background-color: var(--theme-green);
    border-color: var(--theme-green);
}

.table-active {
    background-color: var(--theme-green-light) !important;
}

/* Enhanced Alert Styling */
.alert-floating {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 300px;
    max-width: 400px;
    z-index: 1050;
    margin: 0;
    padding: 1rem;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
}

.alert-floating.show {
    transform: translateX(0);
}

.alert-success {
    background-color: rgba(var(--theme-green-rgb), 0.1);
    border-left: 4px solid var(--theme-green);
    color: var(--theme-green);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    border-left: 4px solid #dc3545;
    color: #dc3545;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border-left: 4px solid #ffc107;
    color: #856404;
}

.alert-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.alert-success .alert-icon {
    background-color: var(--theme-green);
    color: white;
}

.alert-danger .alert-icon {
    background-color: #dc3545;
    color: white;
}

.alert-warning .alert-icon {
    background-color: #ffc107;
    color: #856404;
}

.alert-floating .btn-close {
    padding: 1rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
}

/* Progress bar for alert auto-dismiss */
.alert-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background-color: rgba(255, 255, 255, 0.5);
    width: 100%;
}

.alert-progress-bar {
    height: 100%;
    background-color: currentColor;
    transition: width linear;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    <h1 class="text-2xl font-bold mb-4 d-flex align-items-center">
        <i class="bi bi-people-fill text-success me-2" style="font-size: 2rem; line-height: 1; vertical-align: middle;"></i>
        <span>Manage Students</span>
    </h1>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4" id="studentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab" aria-controls="list" aria-selected="true">Student List</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button" role="tab" aria-controls="import" aria-selected="false">Import Students</button>
        </li>
    </ul>

    <div class="tab-content" id="studentTabsContent">
        {{-- Tab 1: Student List --}}
        <div class="tab-pane fade show active" id="list" role="tabpanel" aria-labelledby="list-tab">
            {{-- Subject Selection --}}
            <form method="GET" action="{{ route('instructor.students.index') }}" class="mb-4">
                <label class="form-label fw-medium mb-1">Select Course</label>
                <select name="subject_id" class="form-select" onchange="handleSubjectChange(this)">
                    <option value="">-- Select Course --</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->subject_code }} - {{ $subject->subject_description }}
                        </option>
                    @endforeach
                </select>
            </form>

            {{-- Add Student Button (only shows when a subject is selected) --}}
            @if(request('subject_id'))
                <div class="mb-3 text-end">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enrollStudentModal">
                        + Enroll Student
                    </button>
                </div>
            @endif

            {{-- Students Table --}}
            @if($students && $students->count())
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student Name</th>
                                    <th class="text-center">Year Level</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $student)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $student->last_name }}, {{ $student->first_name }}
                                        </td>
                                        <td class="text-center">{{ $student->year_level == 1 ? '1st' : ($student->year_level == 2 ? '2nd' : ($student->year_level == 3 ? '3rd' : '4th')) }} Year</td>
                                        <td class="text-center">
                                            @if($student->pivot->is_deleted)
                                                <span class="badge bg-danger">Dropped</span>
                                            @else
                                                <span class="badge bg-success">Enrolled</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#manageStudentModal"
                                                    data-student-id="{{ $student->id }}"
                                                    data-update-url="{{ route('instructor.students.update', $student->id) }}"
                                                    data-student-first-name="{{ $student->first_name }}"
                                                    data-student-last-name="{{ $student->last_name }}"
                                                    data-student-year-level="{{ $student->year_level }}"
                                                    data-student-status="{{ $student->pivot->is_deleted ? 'dropped' : 'enrolled' }}">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#confirmDropModal"
                                                    data-student-id="{{ $student->id }}"
                                                    data-drop-url="{{ route('instructor.students.drop', $student->id) }}"
                                                    data-student-name="{{ $student->first_name }} {{ $student->last_name }}">
                                                Drop
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif(request('subject_id'))
                <div class="alert alert-warning bg-warning-subtle text-dark border-0 text-center">
                    No students found for the selected subject.
                </div> 
            @endif
        </div>

        {{-- Tab 2: Import Students --}}
        <div class="tab-pane fade" id="import" role="tabpanel" aria-labelledby="import-tab">
            <div class="p-4 rounded-3" style="background-color: var(--theme-green-light);">
                <!-- Alert Container -->
                <div id="alertContainer"></div>

                <div class="row">
                    <div class="col-12">
                        @if(session('status'))
                            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                                @foreach($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Main Card Container -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                            <h5 class="mb-0 d-flex align-items-center">
                            </h5>
                            
                            <form method="POST" 
                                  action="{{ route('instructor.students.import.upload') }}" 
                                  enctype="multipart/form-data" 
                                  id="uploadForm" 
                                  class="d-flex align-items-center gap-2">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="file" 
                                           name="file" 
                                           id="file" 
                                           class="form-control form-control-sm border-success" 
                                           accept=".xlsx,.xls"
                                           required>
                                    <button type="submit" 
                                            class="btn btn-success btn-sm d-flex align-items-center gap-2">
                                        <i class="bi bi-upload"></i>
                                        <span>Upload Excel</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="card shadow border-0 rounded-4 hover-card">
                            <div class="card-body p-0">
                                <!-- Persistent Info Message -->
                                <div class="px-4 py-3 bg-success bg-opacity-10 border-bottom">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="d-flex align-items-center text-success">
                                                <i class="bi bi-clipboard-check fs-5 me-2"></i>
                                                <span class="fw-semibold">Import Status</span>
                                            </div>
                                            <div class="vr text-success opacity-25" style="height: 20px;"></div>
                                            <div class="text-success small">
                                                Ready to import new students to your subject roster
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success rounded-pill" id="selectedCount">0 Selected</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter Controls -->
                                <div class="bg-light border-top border-bottom px-4 py-3">
                                    <div class="row align-items-end g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1">
                                                <i class="bi bi-funnel-fill text-success me-1"></i>
                                                Filter Uploaded List
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <select id="listFilter" 
                                                        class="form-select form-select-sm border-success" 
                                                        name="list_name" 
                                                        onchange="filterList(this.value)">
                                                    <option value="">All Uploaded Lists</option>
                                                    @foreach ($reviewStudents->unique('list_name')->pluck('list_name') as $name)
                                                        <option value="{{ $name }}" {{ request('list_name') === $name ? 'selected' : '' }}>
                                                            {{ $name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="input-group-text bg-success text-white border-success">
                                                    <i class="bi bi-list-check"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1">
                                                <i class="bi bi-book-fill text-success me-1"></i>
                                                Compare with Subject
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <select id="compareSubjectSelect" 
                                                        class="form-select form-select-sm border-success">
                                                    <option value="">Select Subject</option>
                                                    @foreach ($subjects as $subject)
                                                        <option value="{{ $subject->id }}" 
                                                                {{ request('compare_subject_id') == $subject->id ? 'selected' : '' }}>
                                                            {{ $subject->subject_code }} - {{ $subject->subject_description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="input-group-text bg-success text-white border-success">
                                                    <i class="bi bi-journal-text"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" 
                                                    class="btn btn-success btn-sm w-100 d-flex align-items-center justify-content-center gap-2" 
                                                    onclick="runCrossCheck()"
                                                    id="crossCheckBtn"
                                                    style="height: 31px;">
                                                <i class="bi bi-search"></i>
                                                <span>Cross Check Data</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add a divider with cross-check status -->
                                <div class="px-4 py-2 bg-success bg-opacity-10 border-bottom d-none" id="crossCheckStatus">
                                    <div class="d-flex align-items-center justify-content-between small">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-info-circle text-success"></i>
                                            <span class="text-success">Cross-check in progress...</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-success" id="matchStatus"></span>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $listName = request('list_name');
                                    $compareSubjectId = request('compare_subject_id');
                                    $filteredReviewStudents = $listName ? $reviewStudents->where('list_name', $listName) : collect();
                                    $existingStudents = $compareSubjectId ? \App\Models\Subject::find($compareSubjectId)?->students()->where('students.is_deleted', 0)->get() : collect();
                                @endphp

                                <!-- Data Tables Container -->
                                <div class="row g-0">
                                    <!-- Uploaded Students -->
                                    <div class="col-md-6 border-end position-relative">
                                        <div class="loading-overlay" id="uploadedLoading">
                                            <div class="loading-spinner"></div>
                                        </div>
                                        <div class="p-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">
                                                    <i class="bi bi-file-earmark-excel text-success me-2"></i>
                                                    Uploaded Students
                                                </h6>
                                                <div class="badge bg-success rounded-pill">
                                                    {{ $filteredReviewStudents->count() }} students
                                                </div>
                                            </div>
                                            <div class="table-responsive custom-scrollbar" style="max-height: 600px;">
                                                <table class="table table-sm table-hover mb-0 border">
                                                    <thead class="bg-light sticky-top">
                                                        <tr>
                                                            <th class="text-center checkbox-column" style="width: 40px; display: none;">
                                                                <div class="form-check">
                                                                    <input type="checkbox" 
                                                                           id="selectAll" 
                                                                           class="form-check-input"
                                                                           data-bs-toggle="tooltip"
                                                                           title="Select All">
                                                                </div>
                                                            </th>
                                                            <th>Full Name</th>
                                                            <th class="text-center">Course</th>
                                                            <th class="text-end">Year</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($filteredReviewStudents as $student)
                                                            <tr class="uploaded-row table-row-transition"
                                                                data-full-name="{{ strtolower(trim($student->full_name)) }}"
                                                                data-course="{{ trim($student->course->course_code ?? '') }}"
                                                                data-year="{{ trim($student->formatted_year_level) }}">
                                                                <td class="text-center checkbox-column" style="display: none;">
                                                                    <div class="form-check">
                                                                        <input type="checkbox" 
                                                                               name="selected_students[]" 
                                                                               value="{{ $student->id }}" 
                                                                               class="form-check-input student-checkbox">
                                                                    </div>
                                                                </td>
                                                                <td class="student-name">{{ $student->full_name }}</td>
                                                                <td class="text-center student-course">
                                                                    {{ $student->course->course_code ?? 'N/A' }}
                                                                </td>
                                                                <td class="text-end student-year">
                                                                    {{ $student->formatted_year_level }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted py-4">
                                                                    <i class="bi bi-inbox-fill fs-2 d-block mb-2"></i>
                                                                    No uploaded list selected
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Existing Students -->
                                    <div class="col-md-6 position-relative">
                                        <div class="loading-overlay" id="existingLoading">
                                            <div class="loading-spinner"></div>
                                        </div>
                                        <div class="p-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">
                                                    <i class="bi bi-people-fill text-secondary me-2"></i>
                                                    Existing Enrolled Students
                                                </h6>
                                                <div class="badge bg-secondary rounded-pill">
                                                    {{ $existingStudents->count() }} students
                                                </div>
                                            </div>
                                            <div class="table-responsive custom-scrollbar" style="max-height: 600px;">
                                                <table class="table table-sm table-hover mb-0 border">
                                                    <thead class="bg-light sticky-top">
                                                        <tr>
                                                            <th>Full Name</th>
                                                            <th class="text-center">Course</th>
                                                            <th class="text-end">Year</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($existingStudents as $student)
                                                            <tr class="enrolled-row table-row-transition"
                                                                data-full-name="{{ strtolower(trim($student->full_name)) }}"
                                                                data-course="{{ trim($student->course->course_code ?? '') }}"
                                                                data-year="{{ trim($student->formatted_year_level) }}">
                                                                <td class="student-name">{{ $student->full_name }}</td>
                                                                <td class="text-center student-course">
                                                                    {{ $student->course->course_code ?? 'N/A' }}
                                                                </td>
                                                                <td class="text-end student-year">
                                                                    {{ $student->formatted_year_level }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="3" class="text-center text-muted py-4">
                                                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                                                    No subject selected
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success">
                                    <i class="bi bi-check-circle-fill me-1"></i> New Student
                                </span>
                                <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i> Already Enrolled
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('instructor.students.index', ['tab' => 'import']) }}" 
                                   class="btn btn-light btn-sm">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </a>
                                    <button type="button" 
                                        class="btn btn-success btn-sm d-flex align-items-center gap-2" 
                                        id="importBtn"
                                        disabled>
                                    <i class="bi bi-check-circle"></i>
                                    <span>Import Selected</span>
                                    <span class="badge bg-white text-success" id="importBtnCount">0</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Enroll Student Modal --}}
<div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-labelledby="enrollStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('instructor.students.store') }}">
            @csrf
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="enrollStudentModalLabel">
                         Enroll student to 
                        @if(request('subject_id'))
                            @php
                                $selectedSubject = $subjects->firstWhere('id', request('subject_id'));
                            @endphp
                            {{ $selectedSubject ? $selectedSubject->subject_code . ' - ' . $selectedSubject->subject_description : '' }}
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <input type="hidden" name="course_id" value="{{ Auth::user()->course_id }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" class="form-select" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'] as $level => $label)
                                    <option value="{{ $level }}">{{ $label }} Year</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control bg-light" value="{{ Auth::user()->course->course_code }} - {{ Auth::user()->course->course_description }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">+ Confirm Enroll</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Manage Student Modal --}}
<div class="modal fade" id="manageStudentModal" tabindex="-1" aria-labelledby="manageStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="manageStudentForm" action="#" onsubmit="return ensureManageFormActionSet(this)">
            @csrf
            @method('PUT')
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="manageStudentModalLabel">👤 Manage Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="manage_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="manage_last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" id="manage_year_level" class="form-select" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'] as $level => $label)
                                    <option value="{{ $level }}">{{ $label }} Year</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control bg-light" value="{{ Auth::user()->course->course_code }} - {{ Auth::user()->course->course_description }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Drop Confirmation Modal --}}
<div class="modal fade" id="confirmDropModal" tabindex="-1" aria-labelledby="confirmDropModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="dropStudentForm" action="" onsubmit="return ensureDropFormActionSet(this)">
            @csrf
            @method('DELETE')
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDropModalLabel">⚠ Confirm Drop</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to drop <strong id="studentNamePlaceholder">this student</strong> from the subject?</p>
                    <p class="text-danger mb-2">This action cannot be undone.</p>
                    <div class="mb-3">
                        <label class="form-label">Type "drop" to confirm</label>
                        <input type="text" class="form-control" id="dropConfirmation" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDropBtn" disabled>Drop Student</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Import Confirm Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" 
              action="{{ route('instructor.students.import.confirm') }}" 
              class="modal-content"
              id="confirmForm">
            @csrf
            <input type="hidden" name="list_name" value="{{ $listName }}">
            <input type="hidden" name="selected_student_ids" id="selectedStudentIds">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-check text-success me-2"></i>
                    Confirm Import
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-0">
                    <label class="form-label">Target Subject</label>
                    <p class="form-control-plaintext fw-semibold" id="confirmSubjectLabel">-</p>
                    <p class="small text-muted mt-1" id="confirmStudentCount">-</p>
                    <input type="hidden" name="subject_id" id="confirmSubjectId" value="">
                </div>
                <div class="mt-3">
                    <label class="form-label small">Selected Students</label>
                    <div id="confirmSelectedList" class="list-group list-group-flush small" style="max-height: 180px; overflow:auto;">
                        <div class="list-group-item px-0 text-muted">No students selected</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success d-flex align-items-center gap-2">
                    <i class="bi bi-check2-all"></i>
                    <span>Confirm Import</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Toast Message --}}
@if(session('success') || session('dropped'))
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div class="toast show align-items-center text-bg-{{ session('success') ? 'success' : 'danger' }} border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') ?? session('dropped') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function handleSubjectChange(select) {
    if (select.value === "") {
        window.location.href = "{{ route('instructor.students.index') }}";
    } else {
        select.form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const dropModal = document.getElementById('confirmDropModal');
    const dropConfirmation = document.getElementById('dropConfirmation');
    const confirmDropBtn = document.getElementById('confirmDropBtn');

    // Also make sure clicking Drop buttons sets the action immediately (robust fallback)
    document.querySelectorAll('button[data-bs-target="#confirmDropModal"]').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            const dropUrl = btn.getAttribute('data-drop-url');
            const studentId = btn.getAttribute('data-student-id');
            const form = document.getElementById('dropStudentForm');
            if (dropUrl) {
                form.action = dropUrl;
            } else if (studentId) {
                form.action = `/instructor/students/${studentId}/drop`;
            }
        });
    });

    dropModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget || {};
        const studentId = button.getAttribute ? button.getAttribute('data-student-id') : null;
        const dropUrl = button.getAttribute ? button.getAttribute('data-drop-url') : null;
        const studentName = button.getAttribute ? button.getAttribute('data-student-name') : '';
        const form = dropModal.querySelector('#dropStudentForm');
        const placeholder = dropModal.querySelector('#studentNamePlaceholder');

        // Set the form action server-side if possible
        if (dropUrl) {
            form.setAttribute('action', dropUrl);
        } else if (studentId) {
            form.setAttribute('action', `/instructor/students/${studentId}/drop`);
        } else {
            form.setAttribute('action', '');
        }
        placeholder.textContent = studentName;
        dropConfirmation.value = '';
        confirmDropBtn.disabled = true;
    });

    dropConfirmation.addEventListener('input', function() {
        confirmDropBtn.disabled = this.value.toLowerCase() !== 'drop';
    });

    const manageModal = document.getElementById('manageStudentModal');
    manageModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        if (!button) {
            console.error('No button found as relatedTarget');
            return;
        }
        
        const studentId = button.getAttribute('data-student-id');
        const updateUrl = button.getAttribute('data-update-url');
        const firstName = button.getAttribute('data-student-first-name');
        const lastName = button.getAttribute('data-student-last-name');
        const yearLevel = button.getAttribute('data-student-year-level');
        
        console.log('Student data:', { studentId, firstName, lastName, yearLevel });
        
        const form = manageModal.querySelector('#manageStudentForm');
        if (updateUrl) {
            form.action = updateUrl;
        } else if (studentId) {
            form.action = `/instructor/students/${studentId}/update`;
        }
        
        const firstNameInput = document.getElementById('manage_first_name');
        const lastNameInput = document.getElementById('manage_last_name');
        const yearLevelSelect = document.getElementById('manage_year_level');
        
        if (firstNameInput) firstNameInput.value = firstName || '';
        if (lastNameInput) lastNameInput.value = lastName || '';
        if (yearLevelSelect) yearLevelSelect.value = yearLevel || '';
    });

    // Also make sure clicking Manage buttons sets the action immediately (robust fallback)
    document.querySelectorAll('button[data-bs-target="#manageStudentModal"]').forEach(function(btn) {
        btn.addEventListener('click', function (e) {
            const updateUrl = btn.getAttribute('data-update-url');
            const studentId = btn.getAttribute('data-student-id');
            const firstName = btn.getAttribute('data-student-first-name');
            const lastName = btn.getAttribute('data-student-last-name');
            const yearLevel = btn.getAttribute('data-student-year-level');
            
            const form = document.getElementById('manageStudentForm');
            if (updateUrl) {
                form.action = updateUrl;
            } else if (studentId) {
                form.action = `/instructor/students/${studentId}/update`;
            }
            
            // Populate fields here too as a fallback
            const firstNameInput = document.getElementById('manage_first_name');
            const lastNameInput = document.getElementById('manage_last_name');
            const yearLevelSelect = document.getElementById('manage_year_level');
            
            if (firstNameInput) firstNameInput.value = firstName || '';
            if (lastNameInput) lastNameInput.value = lastName || '';
            if (yearLevelSelect) yearLevelSelect.value = yearLevel || '';
        });
    });
});
</script>
<script>
function ensureDropFormActionSet(form) {
    // Ensure the form action points to the expected drop endpoint
    const action = form.getAttribute('action') || '';
    // Expect a URL like /instructor/students/{id}/drop
    const match = action.match(/\/instructor\/students\/([^\/]+)\/drop$/);
    if (!match) {
        console.warn('drop form action invalid on submit:', action);
        alert('Unable to determine the student to drop. Please re-open the Drop dialog and try again.');
        return false;
    }
    return true;
}
</script>
<script>
function ensureManageFormActionSet(form) {
    // Ensure the form action points to the expected update endpoint
    const action = form.getAttribute('action') || '';
    // Expect a URL like /instructor/students/{id}/update
    const match = action.match(/\/instructor\/students\/([^\/]+)\/update$/);
    if (!match) {
        console.warn('manage form action invalid on submit:', action);
        alert('Unable to determine the student to update. Please re-open the Manage dialog and try again.');
        return false;
    }
    return true;
}
</script>
<script>
// Enhanced Alert System
function showAlert(message, type = 'success', duration = 3000) {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return; // Guard clause if tab is not active or element missing
    
    const alertId = 'alert-' + Date.now();
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert-floating alert alert-${type} alert-dismissible fade`;
    alert.id = alertId;
    
    // Set icon based on type
    let icon = '';
    switch(type) {
        case 'success':
            icon = 'bi-check-circle-fill';
            break;
        case 'danger':
            icon = 'bi-x-circle-fill';
            break;
        case 'warning':
            icon = 'bi-exclamation-circle-fill';
            break;
        default:
            icon = 'bi-info-circle-fill';
    }
    
    // Create alert content
    alert.innerHTML = `
        <div class="d-flex align-items-center">
            <span class="alert-icon">
                <i class="bi ${icon}"></i>
            </span>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="alert-progress">
            <div class="alert-progress-bar"></div>
        </div>
    `;
    
    // Add to container
    alertContainer.appendChild(alert);
    
    // Show alert with animation
    setTimeout(() => {
        alert.classList.add('show');
        const progressBar = alert.querySelector('.alert-progress-bar');
        progressBar.style.width = '100%';
        progressBar.style.transitionDuration = duration + 'ms';
        setTimeout(() => {
            progressBar.style.width = '0%';
        }, 50);
    }, 10);
    
    // Auto dismiss
    const dismissTimeout = setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 300);
    }, duration);
    
    // Clear timeout if manually closed
    alert.querySelector('.btn-close').addEventListener('click', () => {
        clearTimeout(dismissTimeout);
    });
}

// Replace the old showToast function with showAlert
function showToast(message, type = 'success') {
    showAlert(message, type);
}

// File upload handling
document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('file');
    if (!fileInput.files.length) {
        showAlert('Please select an Excel file to upload', 'warning');
        return;
    }

    const file = fileInput.files[0];
    if (!file.name.match(/\.(xlsx|xls)$/i)) {
        showAlert('Please select a valid Excel file (.xlsx or .xls)', 'warning');
        return;
    }

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm"></span>
        <span>Uploading...</span>
    `;

    // Submit the form
    this.submit();
});

function filterList(selected) {
    hideCheckboxes(); // Hide checkboxes when changing list
    const url = new URL(window.location.href);
    url.searchParams.set('list_name', selected);
    // Keep the tab active
    url.searchParams.set('tab', 'import');
    window.location.href = url.toString();
}

document.getElementById('compareSubjectSelect')?.addEventListener('change', function () {
    hideCheckboxes(); // Hide checkboxes when changing subject
    const url = new URL(window.location.href);
    url.searchParams.set('compare_subject_id', this.value);
    // Keep the tab active
    url.searchParams.set('tab', 'import');
    window.location.href = url.toString();
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function () {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = selectAll.checked;
                    cb.closest('tr').classList.toggle('table-active', selectAll.checked);
                }
            });
        });
    }

    // Individual checkbox handling
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('tr').classList.toggle('table-active', this.checked);
        });
    });
});

function extractNameParts(fullName) {
    const parts = fullName.split(' ').filter(Boolean);
    const first = parts[0] ?? '';
    const last = parts[parts.length - 1] ?? '';
    return (first + last).toLowerCase();
}

function showCheckboxes() {
    document.querySelectorAll('.checkbox-column').forEach(col => {
        col.style.display = '';
    });
}

function hideCheckboxes() {
    document.querySelectorAll('.checkbox-column').forEach(col => {
        col.style.display = 'none';
    });
    // Reset all checkboxes
    document.querySelectorAll('.student-checkbox, #selectAll').forEach(checkbox => {
        checkbox.checked = false;
    });
    // Update counts
    updateSelectedCount();
}

function runCrossCheck() {
    const listFilter = document.getElementById('listFilter');
    const compareSubject = document.getElementById('compareSubjectSelect');
    const crossCheckBtn = document.getElementById('crossCheckBtn');
    
    // Validate both selections
    if (!listFilter.value) {
        showAlert('Please select an uploaded list to compare', 'warning');
        listFilter.focus();
        hideCheckboxes();
        return;
    }
    
    if (!compareSubject.value) {
        showAlert('Please select a subject to compare with', 'warning');
        compareSubject.focus();
        hideCheckboxes();
        return;
    }
    
    // Show checkboxes when starting cross-check
    showCheckboxes();

    // Show status bar
    const statusBar = document.getElementById('crossCheckStatus');
    statusBar.classList.remove('d-none');
    
    // Show loading overlays
    document.getElementById('uploadedLoading').classList.add('show');
    document.getElementById('existingLoading').classList.add('show');
    
    // Disable cross check button and show spinner
    const originalBtnContent = crossCheckBtn.innerHTML;
    crossCheckBtn.disabled = true;
    crossCheckBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm"></span>
        <span>Checking...</span>
    `;

    setTimeout(() => {
        const uploadedRows = document.querySelectorAll('.uploaded-row');
        const enrolledRows = document.querySelectorAll('.enrolled-row');

        if (uploadedRows.length === 0) {
            showAlert('No students found in the selected list', 'warning');
            crossCheckBtn.disabled = false;
            crossCheckBtn.innerHTML = originalBtnContent;
            document.getElementById('uploadedLoading').classList.remove('show');
            document.getElementById('existingLoading').classList.remove('show');
            statusBar.classList.add('d-none');
            return;
        }

        const enrolledData = [...enrolledRows].map(row => ({
            row,
            nameKey: extractNameParts(row.dataset.fullName || ''),
            course: row.dataset.course?.trim(),
            year: row.dataset.year?.trim(),
            nameCell: row.querySelector('.student-name'),
            courseCell: row.querySelector('.student-course'),
            yearCell: row.querySelector('.student-year')
        }));

        // Reset all styling while keeping rows visible
        [...uploadedRows, ...enrolledRows].forEach(row => {
            // Remove all highlight classes
            row.classList.remove(
                'highlight-success', 'highlight-danger',
                'table-row-transition'
            );
            row.style.display = ''; // Ensure row is visible
            
            // Reset cell styling while maintaining visibility
            row.querySelectorAll('td').forEach(cell => {
                cell.classList.remove('text-danger', 'text-success');
                cell.style.opacity = '1';
                cell.style.display = ''; // Ensure cell is visible
            });
            
            // Reset checkbox state
            const checkbox = row.querySelector('.student-checkbox');
            if (checkbox) {
                checkbox.disabled = false;
                checkbox.checked = false; // Uncheck the checkbox
                checkbox.style.display = ''; // Ensure checkbox is visible
            }
            
            // Update the selected count
            updateSelectedCount();
        });

        let matchCount = 0;
        let newCount = 0;

        uploadedRows.forEach(row => {
            const nameKey = extractNameParts(row.dataset.fullName || '');
            const course = row.dataset.course?.trim();
            const year = row.dataset.year?.trim();

            const nameCell = row.querySelector('.student-name');
            const courseCell = row.querySelector('.student-course');
            const yearCell = row.querySelector('.student-year');
            const checkbox = row.querySelector('.student-checkbox');

            let matched = false;

            enrolledData.forEach(e => {
                if (e.nameKey === nameKey && e.course === course && e.year === year) {
                    // Style for duplicate entries with smooth animation
                    row.classList.add('highlight-danger', 'table-row-transition');
                    [nameCell, courseCell, yearCell].forEach(el => {
                        el.classList.add('text-danger');
                        el.style.opacity = '1';
                    });
                    if (checkbox) checkbox.disabled = true;

                    // Style matching row in existing students table
                    e.row.classList.add('highlight-danger', 'table-row-transition');
                    [e.nameCell, e.courseCell, e.yearCell].forEach(el => {
                        el.classList.add('text-danger');
                        el.style.opacity = '1';
                    });
                    matched = true;
                    matchCount++;
                }
            });

            if (!matched) {
                // Style for new entries with smooth animation
                row.classList.add('highlight-success', 'table-row-transition');
                [nameCell, courseCell, yearCell].forEach(el => el.classList.add('text-success'));
                newCount++;
            }
        });

        // Update status bar
        document.getElementById('matchStatus').textContent = 
            `Found ${newCount} new students and ${matchCount} existing students`;

        // Hide loading overlays
        document.getElementById('uploadedLoading').classList.remove('show');
        document.getElementById('existingLoading').classList.remove('show');

        // Reset cross check button
        crossCheckBtn.disabled = false;
        crossCheckBtn.innerHTML = originalBtnContent;

        // Hide status bar after a delay
        setTimeout(() => {
            statusBar.classList.add('d-none');
        }, 3000);
    }, 500);
}

// Add event listeners to update cross-check button state
function updateCrossCheckButton() {
    const listFilter = document.getElementById('listFilter');
    const compareSubject = document.getElementById('compareSubjectSelect');
    const crossCheckBtn = document.getElementById('crossCheckBtn');
    
    if (crossCheckBtn) {
        const isEnabled = listFilter && listFilter.value && compareSubject && compareSubject.value;
        crossCheckBtn.disabled = !isEnabled;
        
        // Update button appearance
        if (isEnabled) {
            crossCheckBtn.classList.remove('btn-secondary');
            crossCheckBtn.classList.add('btn-success');
        } else {
            crossCheckBtn.classList.remove('btn-success');
            crossCheckBtn.classList.add('btn-secondary');
            crossCheckBtn.innerHTML = `
                <i class="bi bi-search"></i>
                <span>Cross Check Data</span>
            `;
        }
    }
}

// Add event listeners for the filters
document.getElementById('listFilter')?.addEventListener('change', updateCrossCheckButton);
document.getElementById('compareSubjectSelect')?.addEventListener('change', updateCrossCheckButton);

function updateImportButtonState() {
    const compareSubject = document.getElementById('compareSubjectSelect');
    const importBtn = document.getElementById('importBtn');
    const selectedCount = document.querySelectorAll('.student-checkbox:not(:disabled):checked').length;
    const enabled = selectedCount > 0 && compareSubject && compareSubject.value;
    if (importBtn) {
        importBtn.disabled = !enabled;
        if (!enabled) {
            importBtn.classList.remove('btn-success');
            importBtn.classList.add('btn-secondary');
        } else {
            importBtn.classList.add('btn-success');
            importBtn.classList.remove('btn-secondary');
        }
    }
}

// When subject changes, update import button state
document.getElementById('compareSubjectSelect')?.addEventListener('change', updateImportButtonState);

// Initial button state
document.addEventListener('DOMContentLoaded', function() {
    updateCrossCheckButton();
    updateImportButtonState();
});

// Form submission handling
// Confirm form submit uses the hidden subject_id set when showing the modal
document.getElementById('confirmForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get all enabled and checked checkboxes
    const selected = [...document.querySelectorAll('.student-checkbox:not(:disabled):checked')].map(cb => cb.value);
    
    if (selected.length === 0) {
        showAlert('Please select at least one student to import', 'warning');
        return;
    }

    // Ensure confirm subject ID is set (comes from Compare with Subject dropdown)
    const confirmSubjectId = this.querySelector('input[name="subject_id"]');
    if (!confirmSubjectId || !confirmSubjectId.value) {
        showAlert('Please select a target subject via the "Compare with Subject" dropdown before importing.', 'warning');
        return;
    }
    
    // Set the selected student IDs and submit
    document.getElementById('selectedStudentIds').value = selected.join(',');
    this.submit();
});

    // Import button click handler: set selected IDs and confirm subject from Compare with Subject
document.getElementById('importBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    const selected = [...document.querySelectorAll('.student-checkbox:not(:disabled):checked')].map(cb => cb.value);
    if (selected.length === 0) {
        showAlert('Please select at least one student to import', 'warning');
        return;
    }
    const compareSubject = document.getElementById('compareSubjectSelect');
    if (!compareSubject || !compareSubject.value) {
        showAlert('Please select a target subject using the "Compare with Subject" dropdown before importing', 'warning');
        compareSubject?.focus();
        return;
    }

    // Populate hidden inputs in the confirm form
    document.getElementById('selectedStudentIds').value = selected.join(',');
    document.getElementById('confirmSubjectId').value = compareSubject.value;
    // Show subject label in modal
    const selectedOption = compareSubject.options[compareSubject.selectedIndex];
    document.getElementById('confirmSubjectLabel').textContent = selectedOption ? selectedOption.text : '';
    // Show selected count in modal
    document.getElementById('confirmStudentCount').textContent = `${selected.length} student(s) will be imported`;

    // Populate preview list (show first 10 names)
    const preview = document.getElementById('confirmSelectedList');
    preview.innerHTML = '';
    const maxPreview = 10;
    selected.slice(0, maxPreview).forEach(id => {
        const cb = document.querySelector(`.student-checkbox[value="${id}"]`);
        const tr = cb ? cb.closest('tr') : null;
        const name = tr ? tr.querySelector('.student-name')?.textContent.trim() : id;
        const li = document.createElement('div');
        li.className = 'list-group-item px-0';
        li.textContent = name || id;
        preview.appendChild(li);
    });
    if (selected.length === 0) {
        const li = document.createElement('div');
        li.className = 'list-group-item px-0 text-muted';
        li.textContent = 'No students selected';
        preview.appendChild(li);
    } else if (selected.length > maxPreview) {
        const more = document.createElement('div');
        more.className = 'list-group-item px-0 text-muted';
        more.textContent = `+ ${selected.length - maxPreview} more...`;
        preview.appendChild(more);
    }

    // Show the modal programmatically
    const confirmModalEl = document.getElementById('confirmModal');
    const bsModal = new bootstrap.Modal(confirmModalEl, { backdrop: 'static' });
    bsModal.show();
});

// Clear preview list when modal hides
document.getElementById('confirmModal')?.addEventListener('hidden.bs.modal', function () {
    const preview = document.getElementById('confirmSelectedList');
    if (preview) {
        preview.innerHTML = '<div class="list-group-item px-0 text-muted">No students selected</div>';
    }
    document.getElementById('confirmStudentCount').textContent = '-';
    document.getElementById('confirmSubjectLabel').textContent = '-';
    document.getElementById('confirmSubjectId').value = '';
});

// Update selected count
function updateSelectedCount() {
    // Count only enabled and checked checkboxes
    const selectedCount = document.querySelectorAll('.student-checkbox:not(:disabled):checked').length;
    const countBadge = document.getElementById('selectedCount');
    const importBtnCount = document.getElementById('importBtnCount');
    const modalSelectedCount = document.getElementById('modalSelectedCount');
    const importBtn = document.getElementById('importBtn');
    
    // Update counts
    if (countBadge) countBadge.textContent = `${selectedCount} Selected`;
    if (importBtnCount) importBtnCount.textContent = selectedCount;
    if (modalSelectedCount) modalSelectedCount.textContent = selectedCount;
    
    // Update import button state
    if (importBtn) {
        importBtn.disabled = selectedCount === 0;
        
        // Update button appearance
        if (selectedCount === 0) {
            importBtn.classList.add('btn-secondary');
            importBtn.classList.remove('btn-success');
        } else {
            importBtn.classList.add('btn-success');
            importBtn.classList.remove('btn-secondary');
        }
    }
    // Update import button enabled state whenever selected count changes
    updateImportButtonState();
}

// Add event listeners for checkboxes
document.addEventListener('change', function(e) {
    if (e.target.matches('.student-checkbox') || e.target.matches('#selectAll')) {
        updateSelectedCount();
    }
});

// Initialize page state
document.addEventListener('DOMContentLoaded', function() {
    hideCheckboxes(); // Ensure checkboxes are hidden on page load
    updateSelectedCount();
});

// Handle server-side alerts on page load
document.addEventListener('DOMContentLoaded', function() {
    // Convert PHP alerts to our new alert system
    const serverAlerts = document.querySelectorAll('.alert');
    serverAlerts.forEach(alert => {
        const message = alert.innerText.trim();
        const type = alert.classList.contains('alert-success') ? 'success' : 'danger';
        if (message) {
            showAlert(message, type);
        }
    });
});

// Tab persistence
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab === 'import') {
        const tabTrigger = new bootstrap.Tab(document.querySelector('#import-tab'));
        tabTrigger.show();
    }
});
</script>
@endpush
