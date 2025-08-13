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
                                        <select name="course_outcomes[{{ $activity->id }}]" class="form-select form-select-sm course-outcome-dropdown" data-activity-id="{{ $activity->id }}">
                                            <option value="">-- Select Course Outcome --</option>
                                            @foreach ($courseOutcomes as $co)
                                                <option value="{{ $co->id }}" @if($activity->course_outcome_id == $co->id) selected @endif>
                                                    {{ $co->co_code }} - {{ $co->co_identifier }}
                                                </option>
                                            @endforeach
                                            @if($courseOutcomes->isEmpty())
                                                <option value="">No Course Outcome</option>
                                            @endif
                                        </select>
                                        <div class="mt-1 text-muted small">
                                            @if($activity->courseOutcome)
                                                <span><strong>{{ $activity->courseOutcome->co_code }}</strong>: {{ $activity->courseOutcome->description }}</span>
                                            @else
                                                <span>No Course Outcome</span>
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

<!-- Add custom styles -->
<style>
    .grade-input, .items-input {
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        font-size: 1rem !important;
        font-weight: 500;
        padding: 0.5rem !important;
        text-align: center;
        border: 2px solid transparent;
    }

    /* Enhanced Table Header Styling */
    .table thead th {
        background: linear-gradient(to bottom, #ffffff, #f8f9fa) !important;
        border-bottom: 3px solid #198754 !important;
        padding: 1rem 0.75rem !important;
        position: relative;
        vertical-align: middle;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.2s ease-in-out;
    }

    .table thead th::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: rgba(25, 135, 84, 0.1);
        transition: all 0.2s ease-in-out;
    }

    .table thead th:hover {
        background: linear-gradient(to bottom, #ffffff, #e9ecef) !important;
    }

    .table thead th:hover::after {
        height: 4px;
        background: rgba(25, 135, 84, 0.2);
    }

    .table thead th .bi {
        color: #198754;
        font-size: 1.1rem;
    }

    .table thead th .text-muted {
        font-size: 0.85rem;
        font-weight: normal;
        margin-top: 0.25rem;
        color: #6c757d !important;
    }

    .table thead th .fw-semibold {
        color: #198754;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    /* Sticky header */
    .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    /* Add subtle column dividers */
    .table thead th:not(:last-child) {
        border-right: 1px solid #e9ecef;
    }

    /* Improve readability of the table */
    .table tbody td {
        background-color: #ffffff;
        transition: background-color 0.2s ease-in-out;
    }

    .table tbody tr:hover td {
        background-color: rgba(25, 135, 84, 0.02);
    }

    /* Remove spinner/arrows for all number inputs */
    .grade-input::-webkit-inner-spin-button,
    .grade-input::-webkit-outer-spin-button,
    .items-input::-webkit-inner-spin-button,
    .items-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* For Firefox */
    .grade-input[type=number],
    .items-input[type=number] {
        -moz-appearance: textfield;
    }

    /* Error state styling */
    .grade-input.is-invalid {
        background-color: #fff2f2 !important;
        border: 2px solid #dc3545 !important;
        color: #dc3545 !important;
        font-weight: 600;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    /* Error tooltip */
    .invalid-tooltip {
        position: absolute;
        top: calc(100% + 5px);
        left: 50%;
        transform: translateX(-50%);
        z-index: 1070;
        display: none;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: #fff;
        background-color: #dc3545;
        border-radius: 0.375rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        white-space: nowrap;
        pointer-events: none;
        max-width: none;
        text-align: center;
    }

    /* Error tooltip arrow */
    .invalid-tooltip::before {
        content: '';
        position: absolute;
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-bottom: 6px solid #dc3545;
    }

    /* Show tooltip on invalid input */
    .grade-input.is-invalid + .invalid-tooltip {
        display: block;
        animation: fadeIn 0.2s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translate(-50%, -10px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }

    /* Error message styling */
    .error-message {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .error-message i {
        font-size: 1rem;
    }

    /* Error highlight */
    td:has(.grade-input.is-invalid) {
        position: relative;
    }

    /* Error shake animation */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .grade-input.is-invalid {
        animation: shake 0.2s ease-in-out;
    }

    /* Error background pulse */
    @keyframes errorPulse {
        0% { background-color: #fff2f2; }
        50% { background-color: #ffe6e6; }
        100% { background-color: #fff2f2; }
    }

    .grade-input.is-invalid {
        animation: errorPulse 1s ease-in-out infinite;
    }

    /* Improved error visibility */
    .grade-input.is-invalid {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23dc3545' viewBox='0 0 16 16'%3E%3Cpath d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        background-size: 1.25rem;
        padding-right: 2.5rem !important;
    }

    /* Valid state */
    .grade-input:not(.is-invalid):focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }

    /* Table styling improvements */
    .table td, .table th {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    thead th {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6 !important;
    }

    .student-row:hover {
        background-color: rgba(25, 135, 84, 0.04);
    }

    .student-row:focus-within {
        background-color: rgba(25, 135, 84, 0.08) !important;
    }

    /* Custom scrollbar */
    .table-responsive::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #adb5bd;
        border-radius: 6px;
        border: 2px solid #f8f9fa;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #6c757d;
    }

    /* Save button styles */
    #saveGradesBtn {
        transition: all 0.3s ease;
        font-weight: 500;
        min-width: 140px;
        border-radius: 6px;
    }

    #saveGradesBtn:not(:disabled) {
        box-shadow: 0 2px 4px rgba(25, 135, 84, 0.2);
    }

    #saveGradesBtn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        background-color: #75b798;
        border-color: #75b798;
    }

    /* Unsaved changes notification */
    .unsaved-notification {
        background-color: #fff3cd;
        color: #664d03;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        border: 1px solid #ffecb5;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(-20px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Save button pulse animation */
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
        }
    }

    #saveGradesBtn.has-changes:not(:disabled) {
        animation: pulse 2s infinite;
    }

    /* Hover effect */
    #saveGradesBtn:not(:disabled):hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(25, 135, 84, 0.2);
    }

    /* Active state */
    #saveGradesBtn:not(:disabled):active {
        transform: translateY(1px);
    }
</style>

<!-- JavaScript for Client-Side Filtering -->
<script>
    document.getElementById('studentSearch').addEventListener('input', function() {
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
        document.getElementById('studentCount').textContent = visibleCount;
    });
</script>
