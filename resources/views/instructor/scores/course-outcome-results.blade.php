@php
    // Fallback to create finalCOs if not provided by controller (for backward compatibility)
    if (!isset($finalCOs)) {
        $finalCOs = isset($coColumnsByTerm) && is_array($coColumnsByTerm) ? array_unique(array_merge(...array_values($coColumnsByTerm))) : [];
        
        // Sort finalCOs by co_code to ensure proper ordering (CO1, CO2, CO3, CO4)
        if (!empty($finalCOs) && isset($coDetails)) {
            usort($finalCOs, function($a, $b) use ($coDetails) {
                $codeA = $coDetails[$a]->co_code ?? '';
                $codeB = $coDetails[$b]->co_code ?? '';
                return strcmp($codeA, $codeB);
            });
        }
    }
@endphp
@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course-outcome-results.css') }}">
<style>
/* Enhanced Table Styling */
.student-row:hover {
    background-color: #f8f9fa !important;
    transition: background-color 0.2s ease;
}

.co-table th {
    border-top: 2px solid #198754;
    vertical-align: middle;
}

.co-table .small {
    font-size: 0.75rem;
    line-height: 1.2;
}

.score-value {
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    min-width: 30px;
    text-align: center;
}

.score-value[data-percentage]:not([data-percentage=""]):not([data-percentage="-"]) {
    background-color: #e8f5e8;
    border: 1px solid #c3e6cb;
}

.table-success th {
    background-color: #d1e7dd !important;
    border-color: #a3cfbb !important;
}

/* Compact Circular Term Navigation Styles */
.compact-stepper {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    position: relative;
    padding: 4px 0;
}

.compact-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none;
    border: none;
    padding: 2px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #6c757d;
    position: relative;
    z-index: 2;
}

.compact-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px;
    left: 100%;
    transform: translateY(-50%);
    width: 12px;
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.compact-step.active:not(:last-child)::after {
    background: #198754;
}

.compact-step.completed:not(:last-child)::after {
    background: #28a745;
}

/* Ensure All Terms (when completed) always has green line to next step */
.compact-step.completed:first-child:not(:last-child)::after {
    background: #28a745 !important;
}

/* Special case: when All Terms is upcoming but next term is completed, make the line green */
.compact-step.upcoming:not(:last-child)::after {
    background: #dee2e6;
}

.compact-step.upcoming + .compact-step.completed::before {
    content: '';
    position: absolute;
    top: 16px;
    right: 100%;
    transform: translateY(-50%);
    width: 12px;
    height: 2px;
    background: #28a745;
    z-index: 1;
}

.compact-step:hover {
    /* Removed transform to prevent layout shift */
}

.compact-step.active {
    color: #198754;
}

.compact-step.active .compact-circle {
    background: linear-gradient(135deg, #198754, #0f5132);
    border-color: #198754;
    color: white;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.compact-step.completed .compact-circle {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    border-color: #28a745;
    color: white;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.compact-step.completed {
    color: #28a745;
}

.compact-step.upcoming .compact-circle {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    color: #6c757d;
}

.compact-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 4px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
    z-index: 3;
}

.compact-label {
    font-size: 0.7rem;
    font-weight: 500;
    text-align: center;
    line-height: 1;
    min-width: 60px;
    white-space: nowrap;
    overflow: visible;
}

.compact-all-btn {
    margin-left: 8px;
    height: 32px;
    width: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

/* Compact Form Controls */
.form-select-sm {
    font-size: 0.875rem !important;
    padding: 0.25rem 0.5rem !important;
    height: auto !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .compact-stepper {
        gap: 10px !important;
    }
    
    .compact-circle {
        width: 28px !important;
        height: 28px !important;
        font-size: 0.75rem !important;
    }
    
    .compact-label {
        font-size: 0.65rem !important;
        min-width: 50px !important;
    }
    
    .compact-step:not(:last-child)::after {
        width: 10px !important;
        top: 14px !important;
    }
    
    .compact-all-btn {
        width: 28px !important;
        height: 28px !important;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('instructor.course-outcome-attainments.index') }}">Course Outcome Attainment Results</a></li>
            @if(isset($selectedSubject))
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}
                </li>
            @endif
        </ol>
    </nav>

    {{-- Warning System for Incomplete CO Records --}}
    @php
        $incompleteActivities = [];
        $incompleteCOs = [];
        $missingScoreCounts = [];
        
        // Check for incomplete records across all terms and COs by examining the scores table
        if(isset($terms) && is_array($terms) && isset($coColumnsByTerm) && isset($students)) {
            foreach($terms as $term) {
                if(!empty($coColumnsByTerm[$term])) {
                    foreach($coColumnsByTerm[$term] as $coId) {
                        $totalMissingScores = 0;
                        $totalStudents = is_countable($students) ? count($students) : 0;
                        $activities = \App\Models\Activity::where('term', $term)
                            ->where('course_outcome_id', $coId)
                            ->where('subject_id', $subjectId)
                            ->get();
                    
                    foreach($students as $student) {
                        foreach($activities as $activity) {
                            // Check if a score record exists in the scores table for this student and activity
                            $scoreRecord = \App\Models\Score::where('student_id', $student->id)
                                ->where('activity_id', $activity->id)
                                ->where('is_deleted', false)
                                ->first();
                            
                            // Flag as incomplete if:
                            // 1. No score record exists in the scores table, OR
                            // 2. Score record exists but the score field is NULL
                            // Note: A score of 0 is valid and should NOT be flagged as incomplete
                            if(!$scoreRecord || $scoreRecord->score === null) {
                                $totalMissingScores++;
                                if(!in_array($activity->id, $incompleteActivities)) {
                                    $incompleteActivities[] = $activity->id;
                                }
                            }
                        }
                    }
                    
                    if($totalMissingScores > 0) {
                        // Get CO details for this specific CO ID
                        $coDetail = isset($coDetails[$coId]) ? $coDetails[$coId] : \App\Models\CourseOutcomes::find($coId);
                        
                        $incompleteCOs[] = [
                            'co_id' => $coId,
                            'co_code' => $coDetail ? $coDetail->co_code : 'CO'.$coId,
                            'term' => $term,
                            'missing_scores' => $totalMissingScores,
                            'total_possible' => $totalStudents * count($activities),
                            'percentage_incomplete' => $totalStudents > 0 && count($activities) > 0 ? round(($totalMissingScores / ($totalStudents * count($activities))) * 100, 1) : 0
                        ];
                    }
                }
            }
        }
    }
    @endphp

    {{-- Course Outcome Attainment Results Management Section --}}
    @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-bar-chart-fill text-success fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Course Outcome Attainment Results</h5>
                                <p class="text-muted mb-0">
                                    Subject: {{ $selectedSubject->subject_code ?? 'N/A' }} - {{ $selectedSubject->subject_description ?? 'N/A' }}
                                    @if(isset($selectedSubject) && $selectedSubject->academicPeriod)
                                        | {{ $selectedSubject->academicPeriod->academic_year }} - {{ $selectedSubject->academicPeriod->semester }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 no-print">
                            <a href="{{ route('instructor.course-outcome-attainments.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Subjects
                            </a>
                            @if(isset($incompleteCOs) && is_array($incompleteCOs) && count($incompleteCOs) > 0)
                                <!-- Incomplete Records Bell Notification -->
                                <button class="notification-bell" type="button" data-bs-toggle="modal" data-bs-target="#warningModal" title="Some student scores are missing">
                                    <i class="bi bi-bell-fill bell-icon"></i>
                                    <span class="badge">{{ count($incompleteCOs) }}</span>
                                </button>
                            @endif
                            
                            @if(isset($coDetails) && is_countable($coDetails) && count($coDetails) > 0)
                                <!-- Print Options Modal Trigger -->
                                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#printOptionsModal">
                                    <i class="bi bi-printer me-2"></i>Print Options
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Integrated Controls Section --}}
                    <div class="border-top pt-3 mt-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-success btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" id="displayTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 150px;">
                                                <span id="currentIcon">📊</span>
                                                <span id="currentText">Percentage</span>
                                            </button>
                                            <ul class="dropdown-menu shadow-sm" aria-labelledby="displayTypeDropdown" style="min-width: 200px;">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" onclick="setDisplayType('score', '📝', 'Scores'); return false;">
                                                        <span>📝</span>
                                                        <span>Raw Scores</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 active" href="#" onclick="setDisplayType('percentage', '📊', 'Percentage'); return false;">
                                                        <span>📊</span>
                                                        <span>Percentage View</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" onclick="setDisplayType('passfail', '✅', 'Pass/Fail'); return false;">
                                                        <span>✅</span>
                                                        <span>Pass/Fail Analysis</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" onclick="setDisplayType('copasssummary', '📈', 'Summary'); return false;">
                                                        <span>📈</span>
                                                        <span>Summary Dashboard</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <!-- Hidden select for functionality -->
                                        <select id="scoreType" class="d-none" onchange="toggleScoreType()">
                                            <option value="score">Scores</option>
                                            <option value="percentage" selected>Percentage</option>
                                            <option value="passfail">Pass/Fail</option>
                                            <option value="copasssummary">Summary</option>
                                        </select>
                                    </div>
                                    <span id="current-view" class="badge bg-success">All Terms</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div id="term-navigation-container" class="d-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                    <small class="text-muted text-nowrap">Term Navigation:</small>
                                    <div class="compact-stepper">
                                        {{-- All Terms Button First --}}
                                        <button type="button"
                                                class="compact-step active"
                                                onclick="showAllTerms()" 
                                                title="Show All Terms Combined">
                                            <div class="compact-circle">
                                                <i class="bi bi-grid-3x3-gap"></i>
                                            </div>
                                            <div class="compact-label">All</div>
                                        </button>
                                        
                                        {{-- Individual Terms --}}
                                        @foreach($terms as $index => $termSlug)
                                            @php
                                                $step = $index + 1;
                                            @endphp
                                            <button type="button"
                                                    class="compact-step upcoming"
                                                    data-term="{{ $termSlug }}"
                                                    onclick="switchTerm('{{ $termSlug }}', {{ $index }})"
                                                    title="{{ ucfirst($termSlug) }} Term">
                                                <div class="compact-circle">{{ $step }}</div>
                                                <div class="compact-label">{{ ucfirst($termSlug) }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif





    {{-- Fade Overlay for Loading States --}}
    <div id="fadeOverlay" class="fade-overlay">
        <div class="spinner"></div>
    </div>

    {{-- Main Container for Stepper and Results --}}
    <div class="main-results-container">

        {{-- Course Outcome Pass Summary --}}
        @if(is_countable($finalCOs) && count($finalCOs))
        <div id="copasssummary-table" style="display:none;">
            <div id="print-area">
                <div class="results-card">
                    <div class="card-header-custom card-header-info">
                        <i class="bi bi-graph-up me-2"></i>Course Outcome Summary Dashboard
                    </div>
                <div class="table-responsive p-3">
                    <table class="table co-table table-bordered align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="text-start">📋 Analysis Metrics</th>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        <th>{{ $coDetails[$coId]['co_code'] ?? '' }}</th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#f8f9fa;">
                                <td class="fw-bold text-dark text-start">👥 Students Attempted</td>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        @php
                                            $attempted = 0;
                                            foreach($students as $student) {
                                                $raw = $coResults[$student->id]['semester_raw'][$coId] ?? null;
                                                $max = $coResults[$student->id]['semester_max'][$coId] ?? null;
                                                $percent = ($max > 0 && $raw !== null) ? ($raw / $max) * 100 : null;
                                                if($percent !== null) $attempted++;
                                            }
                                        @endphp
                                        <td class="fw-bold text-success">{{ $attempted }}</td>
                                    @endif
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-dark text-start">✅ Students Passed</td>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        @php
                                            $threshold = 75; // Fixed threshold
                                            $passed = 0;
                                            foreach($students as $student) {
                                                $raw = $coResults[$student->id]['semester_raw'][$coId] ?? null;
                                                $max = $coResults[$student->id]['semester_max'][$coId] ?? null;
                                                $percent = ($max > 0 && $raw !== null) ? ($raw / $max) * 100 : null;
                                                if($percent !== null && $percent > $threshold) {
                                                    $passed++;
                                                }
                                            }
                                        @endphp
                                        <td class="fw-bold text-success">{{ $passed }}</td>
                                    @endif
                                @endforeach
                            </tr>
                            <tr style="background:#f8f9fa;">
                                <td class="fw-bold text-dark text-start">📊 Pass Percentage</td>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        @php
                                            $threshold = 75; // Fixed threshold
                                            $attempted = 0;
                                            $passed = 0;
                                            foreach($students as $student) {
                                                $raw = $coResults[$student->id]['semester_raw'][$coId] ?? null;
                                                $max = $coResults[$student->id]['semester_max'][$coId] ?? null;
                                                $percent = ($max > 0 && $raw !== null) ? ($raw / $max) * 100 : null;
                                                if($percent !== null) {
                                                    $attempted++;
                                                    if($percent > $threshold) $passed++;
                                                }
                                            }
                                            $percentPassed = $attempted > 0 ? round(($passed / $attempted) * 100, 2) : 0;
                                            $textClass = $percentPassed >= 75 ? 'text-success' : 'text-danger';
                                        @endphp
                                        <td class="fw-bold {{ $textClass }}">{{ $percentPassed }}%</td>
                                    @endif
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-dark text-start">❌ Failed Percentage</td>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        @php
                                            $threshold = 75; // Fixed threshold
                                            $attempted = 0;
                                            $passed = 0;
                                            foreach($students as $student) {
                                                $raw = $coResults[$student->id]['semester_raw'][$coId] ?? null;
                                                $max = $coResults[$student->id]['semester_max'][$coId] ?? null;
                                                $percent = ($max > 0 && $raw !== null) ? ($raw / $max) * 100 : null;
                                                if($percent !== null) {
                                                    $attempted++;
                                                    if($percent > $threshold) $passed++;
                                                }
                                            }
                                            $failed = $attempted - $passed;
                                            $failedPercentage = $attempted > 0 ? round(($failed / $attempted) * 100, 1) : 0;
                                            $textClass = $failedPercentage >= 75 ? 'text-danger' : 'text-success';
                                        @endphp
                                        <td>
                                            <span class="fw-bold {{ $textClass }}">
                                                {{ $failedPercentage }}%
                                            </span>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($incompleteCOs) && is_array($incompleteCOs) && count($incompleteCOs) > 0)
    <!-- Warning Modal -->
    <div class="modal fade warning-modal" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center" id="warningModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Missing Student Scores Found
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0 mb-4">
                        <p class="mb-3">
                            <strong>{{ count($incompleteCOs) }}</strong> Course Outcome(s) have missing student scores. 
                            Some students don't have scores entered for certain activities yet.
                            You'll need to enter these scores to see complete results.
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Note: Students who earned a score of 0 are not considered missing.
                        </small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover incomplete-co-table">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Course Outcome</th>
                                            <th>Term</th>
                                            <th>Missing Scores</th>
                                            <th>Completion Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($incompleteCOs as $incomplete)
                                        <tr>
                                            <td>
                                                <span class="badge bg-warning text-dark fw-bold">
                                                    {{ $incomplete['co_code'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-capitalize fw-medium">{{ $incomplete['term'] }}</span>
                                            </td>
                                            <td>
                                                <span class="text-danger fw-bold">{{ $incomplete['missing_scores'] }}</span>
                                                <small class="text-muted">/ {{ $incomplete['total_possible'] }}</small>
                                            </td>
                                            <td>
                                                @php $completion = 100 - $incomplete['percentage_incomplete']; @endphp
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress" style="height: 20px; width: 80px;">
                                                        <div class="progress-bar bg-{{ $completion >= 80 ? 'success' : ($completion >= 50 ? 'warning' : 'danger') }}" 
                                                             role="progressbar" 
                                                             style="width: {{ max($completion, 5) }}%"
                                                             aria-valuenow="{{ $completion }}" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            @if($completion >= 15)
                                                                <small class="fw-bold text-white">{{ round($completion, 1) }}%</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($completion < 15)
                                                        <small class="fw-bold text-{{ $completion >= 50 ? 'warning' : 'danger' }}">{{ round($completion, 1) }}%</small>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="quick-actions p-3">
                                <h6 class="fw-bold mb-3">
                                    <i class="bi bi-tools me-1"></i>Quick Actions
                                </h6>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('instructor.activities.index') }}" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-plus-circle me-2"></i>Manage Activities
                                    </a>
                                    <a href="{{ route('instructor.grades.index') }}" class="btn btn-success btn-sm">
                                        <i class="bi bi-pencil-square me-2"></i>Manage Grades
                                    </a>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="refreshData()">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Tip:</strong> Enter all missing student scores to see complete results.
                        </small>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>
        @endif

        <div id="print-area">
        
        {{-- Combined Table for All Terms (shown by default) --}}
        @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
        <div class="results-card main-table" id="combined-table">
            <div class="card-header-custom card-header-primary">
                <i class="bi bi-table me-2"></i>Course Outcome Results - All Terms Combined
            </div>
            <div class="table-responsive p-3">
                <table class="table co-table table-bordered align-middle mb-0">
                    <thead class="table-success">
                        <tr>
                            <th rowspan="2" class="align-middle fw-semibold" style="font-size: 0.9rem;">
                                <i class="bi bi-person-fill me-1"></i>Students
                            </th>
                            @foreach($finalCOs as $coId)
                                @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                    <th colspan="{{ count($terms) + 1 }}" class="text-center fw-semibold" style="font-size: 0.9rem;">
                                        <i class="bi bi-mortarboard me-1"></i>{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($finalCOs as $coId)
                                @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                    @foreach($terms as $term)
                                        <th class="text-center fw-semibold" style="font-size: 0.8rem;">
                                            <i class="bi bi-calendar-event me-1"></i>{{ ucfirst($term) }}
                                        </th>
                                    @endforeach
                                    <th class="text-center text-white fw-bold" style="font-size: 0.8rem; background: linear-gradient(135deg, #198754 0%, #0f5132 100%);">
                                        <i class="bi bi-calculator me-1"></i>Total
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#e8f5e8;">
                            <td id="summaryLabel" class="fw-bold text-dark text-start">
                                <i class="bi bi-list-ol me-2"></i>Total number of items
                            </td>
                            @foreach($finalCOs as $coId)
                                @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                    @foreach($terms as $term)
                                        @php
                                            // Check if this CO exists in this term
                                            $coExistsInTerm = isset($coColumnsByTerm[$term]) && in_array($coId, $coColumnsByTerm[$term]);
                                            
                                            if ($coExistsInTerm) {
                                                $max = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                ->get() as $activity) {
                                                $max += $activity->number_of_items;
                                            }
                                        }
                                    @endphp
                                    <td>
                                        @if ($coExistsInTerm)
                                            <span class="score-value" data-score="{{ $max }}" data-percentage="75">
                                                {{ $max }}
                                            </span>
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                @endforeach
                                @php
                                    $totalMax = 0;
                                    foreach($terms as $term) {
                                        foreach(\App\Models\Activity::where('term', $term)->where('course_outcome_id', $coId)->where('subject_id', $subjectId)->get() as $activity) {
                                            $totalMax += $activity->number_of_items;
                                        }
                                    }
                                @endphp
                                <td class="bg-light">
                                    <span class="score-value fw-bold" data-score="{{ $totalMax }}" data-percentage="{{ $percent ?? '' }}">
                                        {{ $totalMax }}
                                    </span>
                                </td>
                                @endif
                            @endforeach
                        </tr>
                        @foreach($students as $student)
                            <tr class="student-row">
                                <td class="fw-semibold">
                                    <i class="bi bi-person-circle me-2 text-success"></i>{{ $student->getFullNameAttribute() }}
                                </td>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        @foreach($terms as $term)
                                            @php
                                                // Check if this CO exists in this term
                                                $coExistsInTerm = isset($coColumnsByTerm[$term]) && in_array($coId, $coColumnsByTerm[$term]);
                                                
                                                if ($coExistsInTerm) {
                                                    // Calculate raw score for this student, term, CO
                                                    $rawScore = 0;
                                                    $maxScore = 0;
                                                    foreach(\App\Models\Activity::where('term', $term)
                                                        ->where('course_outcome_id', $coId)
                                                        ->where('subject_id', $subjectId)
                                                        ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    if($score) $rawScore += $score->score;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                $percent = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                                            }
                                        @endphp
                                        <td>
                                            @if ($coExistsInTerm)
                                                <span class="score-value" data-score="{{ $rawScore }}" data-percentage="{{ ceil($percent) }}">
                                                    {{ $rawScore }}
                                                </span>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @php
                                        $raw = $coResults[$student->id]['semester_raw'][$coId] ?? '';
                                        $max = $coResults[$student->id]['semester_max'][$coId] ?? '';
                                        $percent = ($max > 0 && $raw !== '') ? ($raw / $max) * 100 : 0;
                                    @endphp
                                    <td class="bg-light">
                                        <span class="score-value fw-bold" data-score="{{ $raw !== '' ? $raw : '-' }}" data-percentage="{{ $raw !== '' ? ceil($percent) : '-' }}">
                                            {{ $raw !== '' ? $raw : '-' }}
                                        </span>
                                    </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Individual Term Tables (shown when stepper is used) --}}
        @foreach($terms as $term)
            <div class="results-card term-table" id="term-{{ $term }}" style="display:none;">
                <div class="card-header-custom card-header-primary">
                    <i class="bi bi-calendar-event me-2"></i>{{ strtoupper($term) }} Term Results
                </div>
                <div class="table-responsive p-3">
                    @if(!empty($coColumnsByTerm[$term]))
                        <table class="table co-table table-bordered align-middle mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th class="fw-semibold" style="font-size: 0.9rem;">
                                        <i class="bi bi-person-fill me-1"></i>Students
                                    </th>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        <th class="text-center fw-semibold" style="font-size: 0.9rem;">
                                            <i class="bi bi-mortarboard me-1"></i>{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="background:#e8f5e8;">
                                    <td class="fw-bold text-dark text-start term-summary-label">
                                        <i class="bi bi-list-ol me-2"></i>Total number of items
                                    </td>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        @php
                                            $max = 0;
                                            foreach(\App\Models\Activity::where('term', $term)
                                                ->where('course_outcome_id', $coId)
                                                ->where('subject_id', $subjectId)
                                                ->get() as $activity) {
                                                $max += $activity->number_of_items;
                                            }
                                        @endphp
                                        <td>
                                            <span class="score-value" data-score="{{ $max }}" data-percentage="75">
                                                {{ $max }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                                @foreach($students as $student)
                                    <tr>
                                        <td>{{ $student->getFullNameAttribute() }}</td>
                                        @foreach($coColumnsByTerm[$term] as $coId)
                                            @php
                                                // Calculate raw score for this student, term, CO
                                                $rawScore = 0;
                                                $maxScore = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                    ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    if($score) $rawScore += $score->score;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                $percent = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                                            @endphp
                                            <td>
                                                <span class="score-value" data-score="{{ $rawScore }}" data-percentage="{{ ceil($percent) }}">
                                                    {{ $rawScore }}
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-muted mb-2">No Data Available</h5>
                            <p class="text-muted mb-0">No course outcomes or activities have been set up for the {{ strtoupper($term) }} term yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        @endif

        {{-- Pass/Fail Table --}}
        @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
    <div id="passfail-table" class="results-card" style="display:none;">
            <div class="card-header-custom">
                <i class="bi bi-check-circle me-2"></i>Pass/Fail Analysis Summary
            </div>
            <div class="table-responsive p-3">
                <table class="table co-table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-start">👤 Students</th>
                            @foreach($finalCOs as $coId)
                                @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                    <th class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>{{ $student->getFullNameAttribute() }}</td>
                                @foreach($finalCOs as $coId)
                                    @if(isset($coDetails[$coId]) && empty($coDetails[$coId]->is_deleted))
                                        @php
                                            $raw = $coResults[$student->id]['semester_raw'][$coId] ?? 0;
                                            $max = $coResults[$student->id]['semester_max'][$coId] ?? 0;
                                            $percent = ($max > 0) ? ($raw / $max) * 100 : 0;
                                            $threshold = 75; // Fixed threshold
                                        @endphp
                                        <td class="fw-bold text-{{ $percent >= $threshold ? 'success' : 'danger' }}">
                                            {{ $percent >= $threshold ? 'Passed' : 'Failed' }}
                                            <br>
                                            <small>({{ ceil($percent) }}%)</small>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Individual Term Pass/Fail Tables --}}
        @foreach($terms as $term)
            <div class="results-card passfail-term-table" id="passfail-term-{{ $term }}" style="display:none;">
                <div class="card-header-custom">
                    <i class="bi bi-check-circle me-2"></i>{{ strtoupper($term) }} Term - Pass/Fail Analysis
                </div>
                <div class="table-responsive p-3">
                    @if(!empty($coColumnsByTerm[$term]))
                        <table class="table co-table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-start">👤 Students</th>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        <th class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr>
                                        <td>{{ $student->getFullNameAttribute() }}</td>
                                        @foreach($coColumnsByTerm[$term] as $coId)
                                            @php
                                                // Calculate score for this specific term
                                                $rawScore = 0;
                                                $maxScore = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                    ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    $rawScore += $score ? $score->score : 0;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                $percent = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                                                $threshold = 75;
                                            @endphp
                                            <td class="fw-bold text-{{ $percent >= $threshold ? 'success' : 'danger' }}">
                                                {{ $percent >= $threshold ? 'Passed' : 'Failed' }}
                                                <br>
                                                <small>({{ ceil($percent) }}%)</small>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-muted mb-2">No Data Available</h5>
                            <p class="text-muted mb-0">No course outcomes or activities have been set up for the {{ strtoupper($term) }} term yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        
        {{-- Individual Term Course Outcome Summary Tables --}}
        @foreach($terms as $term)
            <div class="results-card summary-term-table" id="summary-term-{{ $term }}" style="display:none;">
                <div class="card-header-custom card-header-info">
                    <i class="bi bi-graph-up me-2"></i>{{ strtoupper($term) }} Term - Course Outcome Summary
                </div>
                <div class="table-responsive p-3">
                    @if(!empty($coColumnsByTerm[$term]))
                        <table class="table co-table table-bordered align-middle mb-0 text-center">
                            <thead>
                                <tr>
                                    <th class="text-start">📋 Analysis Metrics</th>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        <th>{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="background:#f8f9fa;">
                                    <td class="fw-bold text-dark text-start">👥 Students Attempted</td>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        @php
                                            $attempted = 0;
                                            foreach($students as $student) {
                                                $rawScore = 0;
                                                $maxScore = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                    ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    $rawScore += $score ? $score->score : 0;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                if($maxScore > 0) $attempted++;
                                            }
                                        @endphp
                                        <td class="fw-bold text-success">{{ $attempted }}</td>
                                    @endforeach
                                </tr>
                                <tr style="background:#fff;">
                                    <td class="fw-bold text-dark text-start">✅ Students Passed</td>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        @php
                                            $threshold = 75;
                                            $passed = 0;
                                            foreach($students as $student) {
                                                $rawScore = 0;
                                                $maxScore = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                    ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    $rawScore += $score ? $score->score : 0;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                $percent = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
                                                if($percent >= $threshold) $passed++;
                                            }
                                        @endphp
                                        <td class="fw-bold text-success">{{ $passed }}</td>
                                    @endforeach
                                </tr>
                                <tr style="background:#f8f9fa;">
                                    <td class="fw-bold text-dark text-start">📊 Pass Percentage</td>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        @php
                                            $attempted = 0;
                                            $passed = 0;
                                            foreach($students as $student) {
                                                $rawScore = 0;
                                                $maxScore = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                    ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    $rawScore += $score ? $score->score : 0;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                if($maxScore > 0) {
                                                    $attempted++;
                                                    $percent = ($rawScore / $maxScore) * 100;
                                                    if($percent >= 75) $passed++;
                                                }
                                            }
                                            $percentPassed = $attempted > 0 ? round(($passed / $attempted) * 100, 1) : 0;
                                            $textClass = $percentPassed >= 75 ? 'text-success' : 'text-danger';
                                        @endphp
                                        <td class="fw-bold {{ $textClass }}">{{ $percentPassed }}%</td>
                                    @endforeach
                                </tr>
                                <tr style="background:#fff;">
                                    <td class="fw-bold text-dark text-start">❌ Failed Percentage</td>
                                    @foreach($coColumnsByTerm[$term] as $coId)
                                        @php
                                            $attempted = 0;
                                            $failed = 0;
                                            foreach($students as $student) {
                                                $rawScore = 0;
                                                $maxScore = 0;
                                                foreach(\App\Models\Activity::where('term', $term)
                                                    ->where('course_outcome_id', $coId)
                                                    ->where('subject_id', $subjectId)
                                                    ->get() as $activity) {
                                                    $score = \App\Models\Score::where('student_id', $student->id)
                                                        ->where('activity_id', $activity->id)
                                                        ->first();
                                                    $rawScore += $score ? $score->score : 0;
                                                    $maxScore += $activity->number_of_items;
                                                }
                                                if($maxScore > 0) {
                                                    $attempted++;
                                                    $percent = ($rawScore / $maxScore) * 100;
                                                    if($percent < 75) $failed++;
                                                }
                                            }
                                            $failedPercentage = $attempted > 0 ? round(($failed / $attempted) * 100, 1) : 0;
                                            $textClass = $failedPercentage >= 75 ? 'text-danger' : 'text-success';
                                        @endphp
                                        <td class="fw-bold {{ $textClass }}">{{ $failedPercentage }}%</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-muted mb-2">No Data Available</h5>
                            <p class="text-muted mb-0">No course outcomes or activities have been set up for the {{ strtoupper($term) }} term yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        @else
            {{-- Enhanced Splash Page for No Course Outcomes in Results View --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="text-muted mb-3">
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                    <h5 class="text-muted mb-2">No Course Outcome Results Available</h5>
                    <p class="text-muted mb-4">
                        @if(isset($selectedSubject))
                            for <strong>{{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}</strong>
                        @else
                            for this subject
                        @endif
                    </p>
                    
                    <div class="alert alert-info bg-info-subtle text-dark border-0 mb-4">
                        <h6 class="alert-heading">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                            No Course Outcomes Found
                        </h6>
                        <p class="mb-3">
                            Before viewing course outcome results, course outcomes must be created for this subject. 
                            Course outcomes define the specific learning objectives and competencies that students 
                            should achieve by the end of the course.
                        </p>
                        <hr>
                        <h6 class="mb-2">
                            <i class="bi bi-list-check me-2" style="color: #0F4B36;"></i>
                            What You Need to Do:
                        </h6>
                        <ul class="text-start mb-0">
                            <li>🎯 Define course outcomes that align with learning objectives</li>
                            <li>📝 Create assessment activities linked to course outcomes</li>
                            <li>👥 Input student scores for each activity</li>
                            <li>📊 Then return here to view comprehensive results</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('instructor.course_outcomes.index') }}" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Set Up Course Outcomes
                        </a>
                        <a href="{{ route('instructor.course-outcome-attainments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Subjects
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Course outcomes can be created for 
                            @if(isset($activePeriod))
                                <strong>{{ $activePeriod->academic_year }} - {{ $activePeriod->semester }}</strong>
                            @else
                                the current academic period
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        @endif
        </div> {{-- End of print-area --}}
    </div> {{-- End of main-results-container --}}
@endsection

@push('scripts')
</script>
{{-- Print Options Modal --}}
<div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="printOptionsModalLabel">
                    <i class="bi bi-printer me-2"></i>Print Options
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Individual Terms</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('prelim'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Prelim Only
                                    </button>
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('midterm'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Midterm Only
                                    </button>
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('prefinal'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Prefinal Only
                                    </button>
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('final'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Final Only
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-collection me-2"></i>Complete Reports</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" onclick="printSpecificTable('combined'); closePrintModal();">
                                        <i class="bi bi-table me-2"></i>Print Combined Table
                                    </button>
                                    <button class="btn btn-success" onclick="printSpecificTable('passfail'); closePrintModal();">
                                        <i class="bi bi-check-circle me-2"></i>Print Pass/Fail Analysis
                                    </button>
                                    <button class="btn btn-success" onclick="printSpecificTable('copasssummary'); closePrintModal();">
                                        <i class="bi bi-graph-up me-2"></i>Print Course Outcomes Summary
                                    </button>
                                    <button class="btn btn-success" onclick="printSpecificTable('all'); closePrintModal();">
                                        <i class="bi bi-grid-3x3 me-2"></i>Print Everything
                                    </button>
                                </div>
                                <hr>
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Combined Table:</strong> Shows all terms in one view<br>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Pass/Fail Analysis:</strong> Student performance analysis<br>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Course Outcomes Summary:</strong> Dashboard overview<br>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Print Everything:</strong> Includes all tables and analysis
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info border-0 bg-light">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-printer text-info" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Print Settings</h6>
                            <p class="mb-1">All printouts are optimized for <strong>A4 portrait</strong> format with professional styling.</p>
                            <small class="text-muted">Make sure your printer is set to A4 paper size for best results.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include external JavaScript file -->
<script>
    // Set global variables for print header information
    @if(isset($selectedSubject))
        window.courseCode = "{{ $selectedSubject->subject_code ?? 'N/A' }}";
        window.subjectDescription = "{{ $selectedSubject->subject_description ?? 'N/A' }}";
        window.units = "{{ $selectedSubject->units ?? 'N/A' }}";
        window.courseSection = "{{ $selectedSubject->course->course_code ?? 'N/A' }}";
        @if(isset($selectedSubject->academicPeriod))
            window.semester = "{{ $selectedSubject->academicPeriod->semester ?? 'N/A' }}";
            window.academicPeriod = "{{ $selectedSubject->academicPeriod->academic_year ?? 'N/A' }}";
        @else
            window.semester = 'N/A';
            window.academicPeriod = 'N/A';
        @endif
    @else
        window.courseCode = 'N/A';
        window.subjectDescription = 'N/A';
        window.units = 'N/A';
        window.courseSection = 'N/A';
        window.semester = 'N/A';
        window.academicPeriod = 'N/A';
    @endif
    window.subjectInfo = "{{ isset($selectedSubject) ? $selectedSubject->subject_code . ' - ' . $selectedSubject->subject_description : 'Course Outcome Results' }}";
    window.bannerUrl = "{{ asset('images/banner-header.png') }}";
</script>
<script src="{{ asset('js/course-outcome-results.js') }}"></script>

@endpush
