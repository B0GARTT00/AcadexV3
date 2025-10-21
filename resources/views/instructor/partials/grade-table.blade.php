@php
    $hasData = count($students) > 0 && count($activities) > 0;
    if (!isset($courseOutcomes) || empty($courseOutcomes)) {
        $courseOutcomes = collect();
    }
    // Determine passing grade threshold (fallback to 75 if not provided)
    $threshold = isset($passingGrade) && is_numeric($passingGrade)
        ? (float) $passingGrade
        : 75.0;
@endphp

@if ($hasData)
    @if (!empty($formulaMeta))
        <div class="alert alert-secondary shadow-sm d-flex align-items-center gap-3 rounded-4 py-2 px-3 mb-3" style="transition: all 0.3s ease;">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(25, 135, 84, 0.12); color: #198754;">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold text-dark mb-1" style="font-size: 0.95rem;">
                    Using <span class="text-success">{{ $formulaMeta['label'] ?? 'ASBME Default' }}</span>
                </div>
                <div class="text-muted small">
                    Scope: {{ strtoupper($formulaMeta['scope'] ?? 'GLOBAL') }}
                    @if(!empty($formulaMeta['department']))
                        · Department: {{ $formulaMeta['department'] }}
                    @endif
                    @if(!empty($formulaMeta['course']))
                        · Course: {{ $formulaMeta['course'] }}
                    @endif
                    @if(!empty($formulaMeta['subject']))
                        · Subject Formula Active
                    @endif
                </div>
            </div>
        </div>
    @endif
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
                                        <select name="course_outcomes[{{ $activity->id }}]" 
                                            class="form-select form-select-sm course-outcome-select" 
                                            data-activity-id="{{ $activity->id }}"
                                            title="Select course outcome for this activity"
                                            style="font-size: 0.8rem; border-color: #198754; color: #000;">
                                            <option value="" 
                                                {{ !$activity->course_outcome_id ? 'selected' : '' }} 
                                                disabled>Select Course Outcome</option>
                                            @foreach ($courseOutcomes->sortBy(function($co) {
                                                // Extract the numeric part after the last space or dot
                                                preg_match('/([\d\.]+)$/', $co->co_identifier, $matches);
                                                return isset($matches[1]) ? floatval($matches[1]) : $co->co_identifier;
                                            }) as $co)
                                                <option value="{{ $co->id }}" 
                                                    {{ $activity->course_outcome_id == $co->id ? 'selected' : '' }}
                                                    @if($co->is_deleted)
                                                        style="color: #ffc107; background-color: #fff8e1;"
                                                    @else
                                                        style="color: #000;"
                                                    @endif>
                                                    {{ $co->co_code }} - {{ $co->co_identifier }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($activity->courseOutcome && $activity->courseOutcome->is_deleted)
                                            <div class="mt-1 alert alert-warning py-1 px-2 mb-0 d-flex align-items-center" style="font-size: 0.75rem; border-radius: 4px;">
                                                <i class="bi bi-exclamation-triangle-fill me-1" style="font-size: 0.8rem;"></i>
                                                <div class="text-danger small fw-bold">⚠️ Selected course outcome has been deleted</div>
                                            </div>
                                        @endif
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
                                    // Raw term grade (to 2 decimals), and separate integer for display
                                    $rawGrade = $termGrades[$student->id] ?? null; // e.g., 89.67
                                    $displayGrade = $rawGrade !== null && is_numeric($rawGrade) ? (int) round($rawGrade) : null;

                                    // Enhanced grade styling based on dynamic passing grade threshold
                                    if ($rawGrade !== null) {
                                        if ($rawGrade >= $threshold) {
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
                                            {{ $displayGrade !== null ? $displayGrade : '–' }}
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
