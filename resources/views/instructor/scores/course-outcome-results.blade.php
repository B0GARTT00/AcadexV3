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
@endpush

@section('content')
<div>
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

    {{-- Header Section - Only show when course outcomes exist --}}
    @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
    <div class="header-section">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="header-title">📊 Course Outcome Attainment Results</h1>
                <p class="header-subtitle">Comprehensive analysis of student performance across all terms and course outcomes</p>
            </div>
            <div class="d-flex align-items-center gap-3 no-print">
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
                        🖨️ Print Options
                    </button>
                @endif
            </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Controls Panel - Only show when course outcomes exist --}}
    @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
    <div class="controls-panel no-print">
        <div class="control-group">
            <label class="control-label">Display Type:</label>
            <select id="scoreType" class="control-select" onchange="toggleScoreType()">
                <option value="score">📝 Scores</option>
                <option value="percentage" selected>📊 Percentage</option>
                <option value="passfail">✅ Pass/Fail Analysis</option>
                <option value="copasssummary">📈 Course Outcome Summary</option>
            </select>
            <span id="current-view" class="view-indicator">All Terms View</span>
        </div>
    </div>

    {{-- Term Stepper for Raw Score and Percentage views - Only show when course outcomes exist --}}
    <div id="term-stepper-container" class="stepper-container no-print" style="display:none;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0 fw-bold text-dark">📅 Navigate by Terms</h5>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="showAllTerms()">
                <i class="bi bi-grid-3x3-gap me-1"></i>Show All Terms
            </button>
        </div>
        <div class="stepper">
            @foreach($terms as $index => $termSlug)
                @php
                    $step = $index + 1;
                    $isActive = $index === 0; // Default to first term
                    $class = $isActive ? 'active' : 'upcoming';
                    $highlightLine = false; // Will be managed by JavaScript
                    
                    // Progress ring calculations
                    $radius = 36;
                    $circumference = 2 * pi() * $radius;
                @endphp
                <button type="button"
                        class="step term-step {{ $class }}"
                        data-term="{{ $termSlug }}"
                        onclick="switchTerm('{{ $termSlug }}', {{ $index }})">
                    <div class="circle-wrapper">
                        <svg class="progress-ring" width="80" height="80">
                            <circle class="progress-ring-bg" cx="40" cy="40" r="{{ $radius }}" />
                            <circle class="progress-ring-bar" cx="40" cy="40" r="{{ $radius }}"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $isActive ? 0 : $circumference }}" />
                        </svg>
                        <div class="circle">{{ $step }}</div>
                    </div>
                    <div class="step-label">{{ ucfirst($termSlug) }}</div>
                </button>
            @endforeach
        </div>
        <div class="stepper-hint text-center mt-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Click on any term above to view specific results, or use the "Show All Terms" button to view the combined view
            </small>
        </div>
    </div>
    @endif

    {{-- Fade Overlay for Loading States --}}
    <div id="fadeOverlay" class="fade-overlay">
        <div class="spinner"></div>
    </div>

    {{-- Main Container for Stepper and Results --}}
    <div class="main-results-container">
        {{-- Term Stepper for Raw Score and Percentage views - Only show when course outcomes exist --}}
        @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
        <div id="term-stepper-container" class="stepper-container no-print" style="display:none;">
            <div class="results-card">
                <div class="card-header-custom">
                    <i class="bi bi-list-ol me-2"></i>Term Navigation
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0 fw-bold text-dark">📅 Navigate by Terms</h6>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="showAllTerms()">
                            <i class="bi bi-grid-3x3-gap me-1"></i>Show All Terms
                        </button>
                    </div>
                    <div class="stepper">
                        @foreach($terms as $index => $termSlug)
                            @php
                                $step = $index + 1;
                                $isActive = $index === 0; // Default to first term
                                $class = $isActive ? 'active' : 'upcoming';
                                $highlightLine = false; // Will be managed by JavaScript
                                
                                // Progress ring calculations
                                $radius = 36;
                                $circumference = 2 * pi() * $radius;
                            @endphp
                            <button type="button"
                                    class="step term-step {{ $class }}"
                                    data-term="{{ $termSlug }}"
                                    onclick="switchTerm('{{ $termSlug }}', {{ $index }})">
                                <div class="circle-wrapper">
                                    <svg class="progress-ring" width="80" height="80">
                                        <circle class="progress-ring-bg" cx="40" cy="40" r="{{ $radius }}" />
                                        <circle class="progress-ring-bar" cx="40" cy="40" r="{{ $radius }}"
                                                stroke-dasharray="{{ $circumference }}"
                                                stroke-dashoffset="{{ $isActive ? 0 : $circumference }}" />
                                    </svg>
                                    <div class="circle">{{ $step }}</div>
                                </div>
                                <div class="step-label">{{ ucfirst($termSlug) }}</div>
                            </button>
                        @endforeach
                    </div>
                    <div class="stepper-hint text-center mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Click on any term above to view specific results, or use the "Show All Terms" button to view the combined view
                        </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

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
                                    <th>{{ $coDetails[$coId]['co_code'] ?? '' }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#f8f9fa;">
                                <td class="fw-bold text-dark text-start">👥 Students Attempted</td>
                                @foreach($finalCOs as $coId)
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
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-dark text-start">✅ Students Passed</td>
                                @foreach($finalCOs as $coId)
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
                                @endforeach
                            </tr>
                            <tr style="background:#f8f9fa;">
                                <td class="fw-bold text-dark text-start">📊 Pass Percentage</td>
                                @foreach($finalCOs as $coId)
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
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-dark text-start">❌ Failed Percentage</td>
                                @foreach($finalCOs as $coId)
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
                                                <div class="progress" style="height: 20px; width: 100px;">
                                                    <div class="progress-bar bg-{{ $completion >= 80 ? 'success' : ($completion >= 50 ? 'warning' : 'danger') }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $completion }}%"
                                                         aria-valuenow="{{ $completion }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <small class="fw-bold">{{ $completion }}%</small>
                                                    </div>
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
        @if(isset($finalCOs) && is_countable(value: $finalCOs) && count($finalCOs))
        <div class="results-card main-table" id="combined-table">
            <div class="card-header-custom" style="background: #198754;">
                <i class="bi bi-table me-2"></i>Course Outcome Results - All Terms Combined
            </div>
            <div class="table-responsive p-3">
                <table class="table co-table table-bordered align-middle mb-0">
                    <thead class="table-success">
                        <tr>
                            <th rowspan="2" class="align-middle">Students</th>
                            @foreach($finalCOs as $coId)
                                <th colspan="{{ count($terms) + 1 }}" class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($finalCOs as $coId)
                                @foreach($terms as $term)
                                    <th class="text-center" style="font-size:0.85em;">{{ ucfirst($term) }}</th>
                                @endforeach
                                <th class="text-center text-white" style="font-size:0.85em; background: linear-gradient(135deg, #0F4B36 0%, #023336 100%);" style="color: #198754">Total</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#e8f5e8;">
                            <td id="summaryLabel" class="fw-bold text-dark text-start">Total number of items</td>
                            @foreach($finalCOs as $coId)
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
                            @endforeach
                        </tr>
                        @foreach($students as $student)
                            <tr>
                                <td>{{ $student->getFullNameAttribute() }}</td>
                                @foreach($finalCOs as $coId)
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
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Individual Term Tables (shown when stepper is used) --}}
        @foreach($terms as $term)
            @if(!empty($coColumnsByTerm[$term]))
            <div class="results-card term-table" id="term-{{ $term }}" style="display:none;">
                <div class="card-header-custom card-header-primary">
                    <i class="bi bi-calendar-event me-2"></i>{{ strtoupper($term) }} Term Results
                </div>
                <div class="table-responsive p-3">
                    <table class="table co-table table-bordered align-middle mb-0">
                        <thead class="table-success">
                            <tr>
                                <th>Students</th>
                                @foreach($coColumnsByTerm[$term] as $coId)
                                    <th class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#e8f5e8;">
                                <td class="fw-bold text-dark text-start term-summary-label">Total number of items</td>
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
                </div>
            </div>
            @endif
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
                                <th class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>{{ $student->getFullNameAttribute() }}</td>
                                @foreach($finalCOs as $coId)
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
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Individual Term Pass/Fail Tables --}}
        @foreach($terms as $term)
            @if(!empty($coColumnsByTerm[$term]))
            <div class="results-card passfail-term-table" id="passfail-term-{{ $term }}" style="display:none;">
                <div class="card-header-custom">
                    <i class="bi bi-check-circle me-2"></i>{{ strtoupper($term) }} Term - Pass/Fail Analysis
                </div>
                <div class="table-responsive p-3">
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
                </div>
            </div>
            @endif
        @endforeach
        
        {{-- Individual Term Course Outcome Summary Tables --}}
        @foreach($terms as $term)
            @if(!empty($coColumnsByTerm[$term]))
            <div class="results-card summary-term-table" id="summary-term-{{ $term }}" style="display:none;">
                <div class="card-header-custom card-header-info">
                    <i class="bi bi-graph-up me-2"></i>{{ strtoupper($term) }} Term - Course Outcome Summary
                </div>
                <div class="table-responsive p-3">
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
                </div>
            </div>
            @endif
        @endforeach
        @else
            {{-- Enhanced Splash Page for No Course Outcomes in Results View --}}
            <div class="splash-container">
                <div class="splash-card">
                    <div class="splash-header">
                        <div class="splash-icon-container">
                            <i class="bi bi-graph-up-arrow splash-icon"></i>
                        </div>
                        <h2 class="splash-title">No Course Outcome Results Available</h2>
                        <p class="splash-subtitle">
                            @if(isset($selectedSubject))
                                for <strong>{{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}</strong>
                            @else
                                for this subject
                            @endif
                        </p>
                    </div>
                    
                    <div class="splash-content">
                        <div class="splash-info">
                            <div class="info-section">
                                <h5 class="info-title">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                    No Course Outcomes Found
                                </h5>
                                <p class="info-text">
                                    Before viewing course outcome results, course outcomes must be created for this subject. 
                                    Course outcomes define the specific learning objectives and competencies that students 
                                    should achieve by the end of the course.
                                </p>
                            </div>
                            
                            <div class="info-section">
                                <h5 class="info-title">
                                    <i class="bi bi-list-check me-2" style="color: #0F4B36;"></i>
                                    What You Need to Do
                                </h5>
                                <ul class="info-list">
                                    <li>🎯 Define course outcomes that align with learning objectives</li>
                                    <li>📝 Create assessment activities linked to course outcomes</li>
                                    <li>👥 Input student scores for each activity</li>
                                    <li>📊 Then return here to view comprehensive results</li>
                                </ul>
                            </div>
                            
                            <div class="info-section">
                                <h5 class="info-title">
                                    <i class="bi bi-arrow-right-circle-fill me-2" style="color: #0F4B36;"></i>
                                    Next Steps
                                </h5>
                                <p class="info-text">
                                    Navigate to the Course Outcomes Management page to set up your course outcomes. 
                                    Once created and populated with student data, detailed performance analytics and 
                                    attainment reports will be available here.
                                </p>
                            </div>
                        </div>
                        
                        <div class="splash-actions">
                            <a href="{{ route('instructor.course_outcomes.index') }}" class="btn btn-success btn-lg splash-cta">
                                <i class="bi bi-plus-circle me-2"></i>Set Up Course Outcomes
                            </a>
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
                </div>
            </div>
        @endif
        </div> {{-- End of print-area --}}
    </div> {{-- End of main-results-container --}}
@endsection

@push('scripts')
<!-- Pass PHP data to JavaScript -->
<script>
    @php
        $activePeriod = \App\Models\AcademicPeriod::find(session('active_academic_period_id'));
        // Try to get academic period from subject relationship if session doesn't have it
        if (!$activePeriod && isset($selectedSubject) && $selectedSubject->academicPeriod) {
            $activePeriod = $selectedSubject->academicPeriod;
        }
        $semesterLabel = '';
        if($activePeriod) {
            switch ($activePeriod->semester) {
                case '1st':
                    $semesterLabel = 'First';
                    break;
                case '2nd':
                    $semesterLabel = 'Second';
                    break;
                case 'Summer':
                    $semesterLabel = 'Summer';
                    break;
            }
        }
    @endphp
    
    // Set global variables for JavaScript to use
    window.bannerUrl = "{{ asset('images/banner-header.png') }}";
    window.academicPeriod = "{{ $activePeriod ? $activePeriod->academic_year : 'N/A' }}";
    window.semester = "{{ $semesterLabel ?: 'N/A' }}";
    window.subjectInfo = "{{ isset($selectedSubject) ? $selectedSubject->subject_code . ' - ' . $selectedSubject->subject_description : 'Course Outcome Results' }}";
    window.courseCode = "{{ isset($selectedSubject) ? $selectedSubject->subject_code : 'N/A' }}";
    window.subjectDescription = "{{ isset($selectedSubject) ? $selectedSubject->subject_description : 'N/A' }}";
    window.units = "{{ isset($selectedSubject) && $selectedSubject->units ? $selectedSubject->units : 'N/A' }}";
    window.courseSection = "{{ isset($selectedSubject) && $selectedSubject->course ? $selectedSubject->course->course_code : 'N/A' }}";
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
<script src="{{ asset('js/course-outcome-results.js') }}"></script>

@endpush
