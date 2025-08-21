@php
    $hasData = count($students) > 0 && count($activities) > 0;
    if (!isset($courseOutcomes) || empty($courseOutcomes)) {
        $courseOutcomes = collect();
    }
@endphp

@if ($hasData)
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="input-group shadow-sm" style="width: 300px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" 
                    id="studentSearch" 
                    class="form-control border-start-0 ps-0" 
                    placeholder="Search student name..."
                    aria-label="Search student">
            </div>
            <select id="sortFilter" class="form-select shadow-sm" style="width: 140px;">
                <option value="asc" selected>A to Z</option>
                <option value="desc">Z to A</option>
            </select>
        </div>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            <span id="studentCount">{{ count($students) }}</span> students
        </div>
    </div>
@endif

<div class="shadow-lg rounded-4 overflow-hidden border">
    @if ($hasData)
        <div class="table-responsive">
            <div style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 200px; width: 200px;">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-badge me-2"></i>
                                    <span class="fw-semibold">Student</span>
                                </div>
                            </th>
                            @foreach ($activities as $activity)
                                <th class="text-center" style="min-width: 120px; width: 120px;">
                                    <div class="fw-semibold">{{ ucfirst($activity->type) }}</div>
                                    <div class="text-muted">{{ $activity->title }}</div>
                                    <div class="mt-2">
                                        <input type="number"
                                            class="form-control form-control-sm text-center items-input"
                                            value="{{ $activity->number_of_items }}"
                                            min="1"
                                            data-activity-id="{{ $activity->id }}"
                                            style="width: 75px; margin: 0 auto; font-size: 0.95rem;"
                                            title="Number of Items"
                                            placeholder="Items">
                                    </div>
                                    <div class="mt-2">
                                        <input type="hidden" name="course_outcomes[{{ $activity->id }}]" value="{{ $activity->course_outcome_id }}" class="course-outcome-input" data-activity-id="{{ $activity->id }}">
                                        <button type="button" 
                                            class="btn btn-outline-success btn-sm course-outcome-selector w-100" 
                                            data-activity-id="{{ $activity->id }}"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#courseOutcomeModal"
                                            title="Click to select or change course outcome"
                                            style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                            <i class="bi bi-target me-1"></i>
                                            <span class="course-outcome-display">
                                                @if($activity->courseOutcome)
                                                    {{ $activity->courseOutcome->co_code }}
                                                @else
                                                    Select CO
                                                @endif
                                            </span>
                                        </button>
                                        <div class="mt-1 text-muted small course-outcome-description">
                                            @if($activity->courseOutcome)
                                                <span><strong>{{ $activity->courseOutcome->co_code }}</strong>: {{ $activity->courseOutcome->co_identifier }}</span>
                                            @else
                                                <span>No Course Outcome Selected</span>
                                            @endif
                                        </div>
                                    </div>
                                </th>
                            @endforeach
                            <th class="text-center" style="min-width: 100px; width: 100px;">
                                <div class="fw-semibold">{{ ucfirst($term) }} Grade</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @foreach ($students as $student)
                            <tr class="student-row">
                                <td class="px-3 py-2 fw-medium text-dark" style="width: 200px;">
                                    <div class="text-truncate" title="{{ $student->last_name }}, {{ $student->first_name }} @if($student->middle_name) {{ strtoupper(substr($student->middle_name, 0, 1)) }}. @endif">
                                        {{ $student->last_name }}, {{ $student->first_name }} 
                                        @if($student->middle_name)
                                            {{ strtoupper(substr($student->middle_name, 0, 1)) }}.
                                        @endif
                                    </div>
                                </td>

                                @foreach ($activities as $activity)
                                    @php
                                        $score = $scores[$student->id][$activity->id] ?? null;
                                    @endphp
                                    <td class="px-2 py-2 text-center" style="width: 120px;">
                                        <input
                                            type="number"
                                            class="form-control text-center grade-input"
                                            name="scores[{{ $student->id }}][{{ $activity->id }}]"
                                            value="{{ $score !== null ? (int) $score : '' }}"
                                            min="0"
                                            max="{{ $activity->number_of_items }}"
                                            step="1"
                                            placeholder="–"
                                            title="Max: {{ $activity->number_of_items }}"
                                            data-student="{{ $student->id }}"
                                            data-activity="{{ $activity->id }}"
                                            style="width: 75px; margin: 0 auto; font-size: 0.95rem; height: 36px;"
                                        >
                                    </td>
                                @endforeach
                                @php
                                    $grade = $termGrades[$student->id] ?? null;
                                    if ($grade !== null && is_numeric($grade)) {
                                        $grade = (int) round($grade);
                                    } else {
                                        $grade = null;
                                    }
                                    
                                    // Enhanced grade styling
                                    if ($grade !== null) {
                                        if ($grade >= 75) {
                                            $gradeClass = 'bg-success-subtle border-success';
                                            $textClass = 'text-success';
                                            $icon = 'bi-check-circle-fill';
                                        } else {
                                            $gradeClass = 'bg-danger-subtle border-danger';
                                            $textClass = 'text-danger';
                                            $icon = 'bi-x-circle-fill';
                                        }
                                    } else {
                                        $gradeClass = 'bg-secondary-subtle border-secondary';
                                        $textClass = 'text-secondary';
                                        $icon = 'bi-dash-circle';
                                    }
                                @endphp
                                
                                <td class="px-2 py-2 text-center align-middle" style="width: 100px;">
                                    <div class="d-inline-block border rounded-2 {{ $gradeClass }} position-relative" 
                                         style="min-width: 75px; padding: 8px 12px;">
                                        <div class="position-absolute top-50 start-0 translate-middle-y {{ $textClass }}" 
                                             style="margin-left: 8px;">
                                            <i class="bi {{ $icon }}"></i>
                                        </div>
                                        <span class="fw-medium {{ $textClass }}" style="font-size: 1rem; margin-left: 8px;">
                                            {{ $grade !== null ? $grade : '–' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-warning text-center rounded-4 m-0 py-4">
            No students or activities found for <strong>{{ ucfirst($term) }}</strong>.
        </div>
    @endif
</div>

@if ($hasData)
    <div class="text-end mt-4 d-flex justify-content-end align-items-center">
        <div id="unsavedNotificationContainer" class="me-3"></div>
        <button type="submit" id="saveGradesBtn" class="btn btn-success px-4 py-2 d-flex align-items-center gap-2 position-relative" disabled>
            <i class="bi bi-save"></i>
            <span>Save Grades</span>
            <div class="spinner-border spinner-border-sm ms-1 d-none" role="status">
                <span class="visually-hidden">Saving...</span>
            </div>
        </button>
    </div>
@endif

<!-- Course Outcome Selection Modal -->
<div class="modal fade" id="courseOutcomeModal" tabindex="-1" aria-labelledby="courseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #198754;">
                <h5 class="modal-title" id="courseOutcomeModalLabel">
                    <i class="bi bi-target me-2"></i>
                    Select Course Outcome
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" id="courseOutcomeSearch" placeholder="Search course outcomes...">
                    </div>
                </div>
                
                <div class="row" id="courseOutcomeGrid">
                    @if($courseOutcomes->isEmpty())
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                No course outcomes available for this subject.
                                <br>
                                <small class="text-muted">Please create course outcomes first.</small>
                            </div>
                        </div>
                    @else
                        @foreach ($courseOutcomes as $co)
                            <div class="col-md-6 col-lg-4 mb-3 course-outcome-item" data-search="{{ strtolower($co->co_code . ' ' . $co->co_identifier . ' ' . $co->description) }}">
                                <div class="card course-outcome-card h-100" data-co-id="{{ $co->id }}" data-co-code="{{ $co->co_code }}" data-co-identifier="{{ $co->co_identifier }}" data-co-description="{{ $co->description }}" style="cursor: pointer; transition: all 0.2s ease;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0 text-primary fw-bold">{{ $co->co_code }}</h6>
                                            <span class="badge bg-secondary">{{ $co->co_identifier }}</span>
                                        </div>
                                        <p class="card-text small text-muted mb-0" style="line-height: 1.4;">
                                            {{ Str::limit($co->description, 80) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Client-Side Filtering -->
<script>
    // Student search functionality
    function initializeStudentSearch() {
        const studentSearch = document.getElementById('studentSearch');
        if (studentSearch) {
            studentSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.student-row');
                let visibleCount = 0;
                
                rows.forEach(function(row) {
                    const studentName = row.querySelector('td').textContent.toLowerCase();
                    const isVisible = studentName.includes(searchTerm);
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });

                // Update student count
                const studentCount = document.getElementById('studentCount');
                if (studentCount) {
                    studentCount.textContent = visibleCount;
                }
            });
        }
    }
    
    // Initialize student search on page load
    document.addEventListener('DOMContentLoaded', initializeStudentSearch);
    
    // Export for external use
    window.initializeStudentSearch = initializeStudentSearch;
</script>
