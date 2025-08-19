@php
    $finalCOs = isset($coColumnsByTerm) && is_array($coColumnsByTerm) ? array_unique(array_merge(...array_values($coColumnsByTerm))) : [];
@endphp
@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course-outcome-results.css') }}">
@endpush

@section('content')
<div>
    {{-- Debug Subject Data (remove in production) --}}
    @if(config('app.debug'))
        <div class="alert alert-info" style="display: none;" id="debug-info">
            <strong>Debug Info:</strong><br>
            Subject ID: {{ $subjectId ?? 'N/A' }}<br>
            Subject Code: {{ isset($selectedSubject) ? $selectedSubject->subject_code : 'N/A' }}<br>
            Subject Description: {{ isset($selectedSubject) ? $selectedSubject->subject_description : 'N/A' }}<br>
            Units: {{ isset($selectedSubject) ? $selectedSubject->units : 'N/A' }}<br>
            Course: {{ isset($selectedSubject) && isset($selectedSubject->course) ? $selectedSubject->course->course_code : 'N/A' }}<br>
            Academic Period: {{ isset($selectedSubject) && isset($selectedSubject->academicPeriod) ? $selectedSubject->academicPeriod->academic_year . ' - ' . $selectedSubject->academicPeriod->semester : 'N/A' }}
        </div>
    @endif

    {{-- Warning System for Incomplete CO Records --}}
    @php
        $incompleteActivities = [];
        $incompleteCOs = [];
        $zeroScoreCounts = [];
        
        // Check for incomplete records across all terms and COs
        if(isset($terms) && is_array($terms) && isset($coColumnsByTerm) && isset($students)) {
            foreach($terms as $term) {
                if(!empty($coColumnsByTerm[$term])) {
                    foreach($coColumnsByTerm[$term] as $coId) {
                        $totalZeroScores = 0;
                        $totalStudents = is_countable($students) ? count($students) : 0;
                        $activities = \App\Models\Activity::where('term', $term)
                            ->where('course_outcome_id', $coId)
                            ->where('subject_id', $subjectId)
                            ->get();
                    
                    foreach($students as $student) {
                        foreach($activities as $activity) {
                            $score = \App\Models\Score::where('student_id', $student->id)
                                ->where('activity_id', $activity->id)
                                ->first();
                            if(!$score || $score->score == 0) {
                                $totalZeroScores++;
                                if(!in_array($activity->id, $incompleteActivities)) {
                                    $incompleteActivities[] = $activity->id;
                                }
                            }
                        }
                    }
                    
                    if($totalZeroScores > 0) {
                        $incompleteCOs[] = [
                            'co_id' => $coId,
                            'co_code' => $coDetails[$coId]->co_code ?? 'CO'.$coId,
                            'term' => $term,
                            'zero_scores' => $totalZeroScores,
                            'total_possible' => $totalStudents * count($activities),
                            'percentage_incomplete' => $totalStudents > 0 && count($activities) > 0 ? round(($totalZeroScores / ($totalStudents * count($activities))) * 100, 1) : 0
                        ];
                    }
                    }
                }
            }
        }
    @endphp

    {{-- Header Section --}}
    <div class="header-section">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="header-title">📊 Course Outcome Attainment Results</h1>
                <p class="header-subtitle">Comprehensive analysis of student performance across all terms and course outcomes</p>
            </div>
            <div class="d-flex align-items-center gap-3 no-print">
                @if(isset($incompleteCOs) && is_array($incompleteCOs) && count($incompleteCOs) > 0)
                    <!-- Incomplete Records Bell Notification -->
                    <button class="notification-bell" type="button" data-bs-toggle="modal" data-bs-target="#warningModal" title="Incomplete Course Outcome Records Detected">
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

    {{-- Controls Panel --}}
    <div class="controls-panel no-print">
        <div class="control-group">
            <label class="control-label">Display Type:</label>
            <select id="scoreType" class="control-select" onchange="toggleScoreType()">
                <option value="score">📝 Scores</option>
                <option value="percentage">📊 Percentage</option>
                <option value="passfail">✅ Pass/Fail Analysis</option>
                <option value="copasssummary">📈 Course Outcome Summary</option>
            </select>
            <span id="current-view" class="view-indicator">All Terms View</span>
        </div>
    </div>

    {{-- Term Stepper for Raw Score and Percentage views --}}
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

    {{-- Fade Overlay for Loading States --}}
    <div id="fadeOverlay" class="fade-overlay">
        <div class="spinner"></div>
    </div>

    {{-- Main Container for Stepper and Results --}}
    <div class="main-results-container">
        {{-- Term Stepper for Raw Score and Percentage views --}}
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
                                    @endphp
                                    <td class="fw-bold text-success">{{ $percentPassed }}%</td>
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-dark text-start">🎯 Target Achieved</td>
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
                                        $targetPercentage = $attempted > 0 ? round(($passed / $attempted) * 100, 1) : 0;
                                        $badgeClass = $targetPercentage >= 75 ? 'bg-success' : 'bg-danger';
                                    @endphp
                                    <td>
                                        <span class="badge {{ $badgeClass }}">
                                            75%
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
                        Incomplete Course Outcome Records Detected
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0 mb-4">
                        <p class="mb-3">
                            <strong>{{ count($incompleteCOs) }}</strong> Course Outcome(s) have incomplete or missing score records. 
                            This may affect the accuracy of your Course Outcome Attainment analysis.
                        </p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover incomplete-co-table">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Course Outcome</th>
                                            <th>Term</th>
                                            <th>Missing/Zero Scores</th>
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
                                                <span class="text-danger fw-bold">{{ $incomplete['zero_scores'] }}</span>
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
                            <strong>Tip:</strong> Complete all activity scores to ensure accurate Course Outcome analysis.
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
            @php $finalCOs = array_unique(array_merge(...array_values($coColumnsByTerm))); @endphp
        
        {{-- Combined Table for All Terms (shown by default) --}}
        @if(isset($finalCOs) && is_countable($finalCOs) && count($finalCOs))
        <div class="results-card main-table" id="combined-table">
            <div class="card-header-custom">
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
                                <th class="text-center bg-primary text-white" style="font-size:0.85em;">Total</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#e8f5e8;">
                            <td id="summaryLabel" class="fw-bold text-dark text-start">Total number of items</td>
                            @foreach($finalCOs as $coId)
                                @foreach($terms as $term)
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
        @endif
        </div> {{-- End of print-area --}}
    </div> {{-- End of main-results-container --}}
@endsection

@push('scripts')
<script>
    let currentTerm = null;
    
    function toggleScoreType() {
        var type = document.getElementById('scoreType').value;
        var passfailTable = document.getElementById('passfail-table');
        var copasssummaryTable = document.getElementById('copasssummary-table');
        var mainTables = document.querySelectorAll('.main-table');
        var termTables = document.querySelectorAll('.term-table');
        var summaryLabel = document.getElementById('summaryLabel');
        var termSummaryLabels = document.querySelectorAll('.term-summary-label');
        var termStepperContainer = document.getElementById('term-stepper-container');
        
        if(type === 'passfail') {
            passfailTable && (passfailTable.style.display = 'block');
            copasssummaryTable && (copasssummaryTable.style.display = 'none');
            mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            termStepperContainer && (termStepperContainer.style.display = 'none');
        } else if(type === 'copasssummary') {
            passfailTable && (passfailTable.style.display = 'none');
            copasssummaryTable && (copasssummaryTable.style.display = 'block');
            mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            termStepperContainer && (termStepperContainer.style.display = 'none');
        } else {
            passfailTable && (passfailTable.style.display = 'none');
            copasssummaryTable && (copasssummaryTable.style.display = 'none');
            termStepperContainer && (termStepperContainer.style.display = 'block');
            
            // Show combined table by default, hide term tables
            if (!currentTerm) {
                mainTables.forEach(function(tbl) { tbl.style.display = 'block'; });
                termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
            } else {
                mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
                termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
                var activeTerm = document.getElementById('term-' + currentTerm);
                if (activeTerm) activeTerm.style.display = 'block';
            }
            
            document.querySelectorAll('.score-value').forEach(function(el) {
                el.style.display = 'inline';
                var score = el.getAttribute('data-score');
                var percent = el.getAttribute('data-percentage');
                if(type === 'score') {
                    el.textContent = score;
                } else {
                    el.textContent = percent !== '' && percent !== null ? percent + '%' : '-';
                }
            });
        }
        
        if(type === 'percentage') {
            if(summaryLabel) summaryLabel.textContent = 'Percentage Required';
            termSummaryLabels.forEach(function(label) {
                label.textContent = 'Percentage Required';
            });
        } else {
            if(summaryLabel) summaryLabel.textContent = 'Total number of items';
            termSummaryLabels.forEach(function(label) {
                label.textContent = 'Total number of items';
            });
        }
    }
    
    function switchTerm(term, index) {
        currentTerm = term;
        
        // Hide combined table and all term tables
        var combinedTable = document.getElementById('combined-table');
        var termTables = document.querySelectorAll('.term-table');
        
        if (combinedTable) combinedTable.style.display = 'none';
        termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        
        // Show selected term table
        var activeTable = document.getElementById('term-' + term);
        if (activeTable) activeTable.style.display = 'block';
        
        // Update stepper appearance with progress ring animations
        var steps = document.querySelectorAll('.term-step');
        var radius = 36;
        var circumference = 2 * Math.PI * radius;
        
        steps.forEach(function(step, i) {
            var progressBar = step.querySelector('.progress-ring-bar');
            
            step.classList.remove('active', 'completed', 'upcoming', 'highlight-line');
            
            if (i < index) {
                // Completed terms - 100% progress
                step.classList.add('completed', 'highlight-line');
                if (progressBar) {
                    progressBar.style.strokeDashoffset = '0'; // 100% completion
                }
            } else if (i === index) {
                // Active term (clicked) - 100% progress
                step.classList.add('active');
                if (progressBar) {
                    progressBar.style.strokeDashoffset = '0'; // 100% completion when clicked
                }
            } else {
                // Upcoming terms - no progress
                step.classList.add('upcoming');
                if (progressBar) {
                    progressBar.style.strokeDashoffset = circumference; // 0% completion
                }
            }
        });
        
        // Update score display based on current type
        var type = document.getElementById('scoreType').value;
        document.querySelectorAll('.score-value').forEach(function(el) {
            var score = el.getAttribute('data-score');
            var percent = el.getAttribute('data-percentage');
            if(type === 'score') {
                el.textContent = score;
            } else {
                el.textContent = percent !== '' && percent !== null ? percent + '%' : '-';
            }
        });
    }
    
    function showAllTerms() {
        currentTerm = null;
        
        // Hide all term tables
        var termTables = document.querySelectorAll('.term-table');
        termTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        
        // Show combined table
        var combinedTable = document.getElementById('combined-table');
        if (combinedTable) combinedTable.style.display = 'block';
        
        // Reset stepper to default state
        var steps = document.querySelectorAll('.term-step');
        steps.forEach(function(step, i) {
            step.classList.remove('active', 'completed', 'upcoming', 'highlight-line');
            if (i === 0) {
                step.classList.add('active');
            } else {
                step.classList.add('upcoming');
            }
        });
        
        toggleScoreType();
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleScoreType();
        
        // Add smooth scrolling to table when switching
        document.querySelectorAll('.term-step').forEach(function(step) {
            step.addEventListener('click', function() {
                setTimeout(function() {
                    const tableContainer = document.querySelector('.results-card:not([style*="display: none"])');
                    if (tableContainer) {
                        tableContainer.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                    }
                }, 100);
            });
        });
    });
    
    function printTable() {
        printSpecificTable('combined');
    }
    
    function printSpecificTable(tableType) {
        const bannerUrl = "{{ asset('images/banner-header.png') }}";
        
        // Get current academic period and subject info
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
        const academicPeriod = "{{ $activePeriod ? $activePeriod->academic_year : 'N/A' }}";
        const semester = "{{ $semesterLabel ?: 'N/A' }}";
        const currentDate = new Date().toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Get subject information
        const subjectInfo = "{{ isset($selectedSubject) ? $selectedSubject->subject_code . ' - ' . $selectedSubject->subject_description : 'Course Outcome Results' }}";
        const courseCode = "{{ isset($selectedSubject) ? $selectedSubject->subject_code : 'N/A' }}";
        const subjectDescription = "{{ isset($selectedSubject) ? $selectedSubject->subject_description : 'N/A' }}";
        const units = "{{ isset($selectedSubject) && $selectedSubject->units ? $selectedSubject->units : 'N/A' }}";
        const courseSection = "{{ isset($selectedSubject) && $selectedSubject->course ? $selectedSubject->course->course_code : 'N/A' }}";
        
        let content = '';
        let reportTitle = '';
        
        switch(tableType) {
            case 'prelim':
                content = getPrintTableContent('prelim');
                reportTitle = 'Course Outcome Attainment Results - Prelim Term';
                break;
            case 'midterm':
                content = getPrintTableContent('midterm');
                reportTitle = 'Course Outcome Attainment Results - Midterm';
                break;
            case 'prefinal':
                content = getPrintTableContent('prefinal');
                reportTitle = 'Course Outcome Attainment Results - Prefinal Term';
                break;
            case 'final':
                content = getPrintTableContent('final');
                reportTitle = 'Course Outcome Attainment Results - Final Term';
                break;
            case 'combined':
                content = getPrintTableContent('combined');
                reportTitle = 'Course Outcome Attainment Results - All Terms Combined';
                break;
            case 'all':
                content = getAllTablesContent();
                reportTitle = 'Complete Course Outcome Attainment Report';
                break;
            default:
                content = getPrintTableContent('combined');
                reportTitle = 'Course Outcome Attainment Results';
        }
        
        const printWindow = window.open('', '', 'width=900,height=650');
        printWindow.document.write(`
            <html>
                <head>
                    <title>${reportTitle}</title>
                    <style>
                        @media print {
                            @page {
                                size: A4 portrait;
                                margin: 0.75in 0.5in;
                            }
                            
                            body {
                                font-size: 10px;
                            }
                            
                            table {
                                font-size: 9px;
                            }
                            
                            .banner {
                                max-height: 100px;
                            }
                            
                            .report-title {
                                font-size: 16px;
                            }
                        }
                        
                        body {
                            font-family: 'Arial', sans-serif;
                            margin: 0;
                            padding: 20px;
                            color: #333;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                            line-height: 1.6;
                        }

                        .banner {
                            width: 100%;
                            max-height: 130px;
                            object-fit: contain;
                            margin-bottom: 15px;
                        }

                        .header-content {
                            margin-bottom: 20px;
                        }

                        .report-title {
                            font-size: 20px;
                            font-weight: bold;
                            text-align: center;
                            margin: 15px 0;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            color: #4a7c59;
                            border-bottom: 2px solid #4a7c59;
                            padding-bottom: 8px;
                        }

                        .header-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 25px;
                            background-color: #fff;
                            font-size: 11px;
                            border: 2px solid #4a7c59;
                        }

                        .header-table td {
                            padding: 8px 12px;
                            border: 1px solid #2d4a35;
                        }

                        .header-label {
                            font-weight: bold;
                            width: 120px;
                            background-color: #4a7c59;
                            color: #fff;
                        }

                        .header-value {
                            font-family: 'Arial', sans-serif;
                            font-weight: 500;
                        }

                        .print-table {
                            width: 100%;
                            border-collapse: collapse;
                            border: 2px solid #4a7c59;
                            background-color: #fff;
                            margin-top: 15px;
                            font-size: 10px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }

                        .print-table th, .print-table td {
                            border: 1px solid #2d4a35;
                            padding: 6px 4px;
                            text-align: center;
                            vertical-align: middle;
                        }

                        .print-table th {
                            background-color: #4a7c59;
                            color: #fff;
                            font-weight: bold;
                            text-transform: uppercase;
                            white-space: nowrap;
                            font-size: 9px;
                        }

                        .print-table th:first-child {
                            background-color: #2d4a35;
                            text-align: left;
                        }

                        /* Multi-level header styles for All Terms Combined */
                        .print-table .table-success th,
                        .print-table th.table-success {
                            background-color: #4a7c59 !important;
                            color: white !important;
                        }

                        /* TOTAL columns - darker green */
                        .print-table .bg-primary,
                        .print-table th.bg-primary,
                        .print-table td.bg-primary {
                            background-color: #2d4a35 !important;
                            color: white !important;
                            font-weight: bold !important;
                        }

                        /* First header row - Students and CO headers */
                        .print-table thead tr:first-child th {
                            background-color: #4a7c59 !important;
                            color: white !important;
                            font-size: 10px !important;
                            padding: 8px 4px !important;
                            text-align: center !important;
                            font-weight: bold !important;
                        }

                        /* Second header row - term columns */
                        .print-table thead tr:nth-child(2) th {
                            background-color: #4a7c59 !important;
                            color: white !important;
                            font-size: 8px !important;
                            padding: 6px 2px !important;
                        }

                        /* Total columns in second row */
                        .print-table thead tr:nth-child(2) th.bg-primary {
                            background-color: #2d4a35 !important;
                        }

                        /* Data cells styling */
                        .print-table tbody td {
                            background-color: white !important;
                            font-size: 8px !important;
                            padding: 4px 2px !important;
                        }

                        /* Students column */
                        .print-table tbody td:first-child {
                            text-align: left !important;
                            background-color: #f8f9fa !important;
                            font-weight: normal !important;
                            padding-left: 6px !important;
                        }

                        /* Summary row styling */
                        .print-table tbody tr:first-child td {
                            background-color: #e8f5e8 !important;
                            font-weight: bold !important;
                        }

                        /* Score values */
                        .print-table .score-value {
                            font-weight: bold !important;
                            color: #000 !important;
                        }

                        /* Light background cells */
                        .print-table .bg-light {
                            background-color: #f8f9fa !important;
                        }

                        .print-table tr:nth-child(even) {
                            background-color: #f0f7f4;
                        }

                        .print-table td:first-child {
                            text-align: left;
                            font-weight: 500;
                            background-color: #f8f9fa;
                        }

                        .score-value {
                            font-weight: bold;
                            color: #1a5f38;
                        }

                        .percentage-value {
                            color: #0066cc;
                            font-weight: 500;
                        }

                        .average-cell {
                            background-color: #e8f5e8 !important;
                            font-weight: bold;
                            color: #1a5f38;
                        }

                        .term-section {
                            margin-bottom: 30px;
                            page-break-inside: avoid;
                        }

                        .term-title {
                            font-size: 16px;
                            font-weight: bold;
                            color: #1a5f38;
                            margin: 20px 0 10px 0;
                            padding: 8px 12px;
                            background-color: #f0f7f4;
                            border-left: 4px solid #1a5f38;
                        }

                        .footer {
                            margin-top: 20px;
                            padding-top: 15px;
                            border-top: 1px solid #dee2e6;
                            font-size: 11px;
                            color: #666;
                            text-align: center;
                        }

                        .page-break {
                            page-break-before: always;
                        }
                    </style>
                </head>
                <body>
                    <img src="${bannerUrl}" alt="Banner Header" class="banner">
                    
                    <div class="header-content">
                        <div class="report-title">${reportTitle}</div>
                        
                        <table class="header-table">
                            <tr>
                                <td class="header-label">Course Code:</td>
                                <td class="header-value">${courseCode}</td>
                                <td class="header-label">Units:</td>
                                <td class="header-value">${units}</td>
                            </tr>
                            <tr>
                                <td class="header-label">Description:</td>
                                <td class="header-value">${subjectDescription}</td>
                                <td class="header-label">Semester:</td>
                                <td class="header-value">${semester}</td>
                            </tr>
                            <tr>
                                <td class="header-label">Course/Section:</td>
                                <td class="header-value">${courseSection}</td>
                                <td class="header-label">School Year:</td>
                                <td class="header-value">${academicPeriod}</td>
                            </tr>
                        </table>
                    </div>

                    ${content}

                    <div class="footer">
                        This is a computer-generated document. No signature is required.
                        <br>
                        Printed via ACADEX - Academic Grade System on ${currentDate}
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        
        // Wait for resources to load then print
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }
    
    function getPrintTableContent(termType) {
        let tableSelector = '';
        let termTitle = '';
        
        switch(termType) {
            case 'prelim':
                tableSelector = '#term-prelim table';
                termTitle = 'Prelim Term Results';
                break;
            case 'midterm':
                tableSelector = '#term-midterm table';
                termTitle = 'Midterm Results';
                break;
            case 'prefinal':
                tableSelector = '#term-prefinal table';
                termTitle = 'Prefinal Term Results';
                break;
            case 'final':
                tableSelector = '#term-final table';
                termTitle = 'Final Term Results';
                break;
            case 'combined':
                tableSelector = '#combined-table table';
                termTitle = 'All Terms Combined';
                break;
        }
        
        const table = document.querySelector(tableSelector);
        if (!table) {
            return '<p>No data available for the selected term.</p>';
        }
        
        let tableHTML = `<div class="term-section">`;
        if (termType !== 'combined') {
            tableHTML += `<h3 class="term-title">${termTitle}</h3>`;
        }
        
        tableHTML += `<table class="print-table">`;
        
        // Copy table content with proper attributes
        const rows = table.querySelectorAll('tr');
        rows.forEach((row, index) => {
            const isHeader = row.closest('thead') !== null;
            const tag = isHeader ? 'th' : 'td';
            
            tableHTML += '<tr>';
            const cells = row.querySelectorAll(isHeader ? 'th' : 'td');
            cells.forEach(cell => {
                let cellContent = cell.textContent.trim();
                let cellClass = '';
                let cellAttrs = '';
                
                // Preserve colspan and rowspan attributes
                if (cell.hasAttribute('colspan')) {
                    cellAttrs += ` colspan="${cell.getAttribute('colspan')}"`;
                }
                if (cell.hasAttribute('rowspan')) {
                    cellAttrs += ` rowspan="${cell.getAttribute('rowspan')}"`;
                }
                
                // Preserve important CSS classes
                if (cell.classList.contains('bg-primary') || cell.classList.contains('text-white')) {
                    cellClass += ' bg-primary text-white';
                }
                if (cell.classList.contains('table-success')) {
                    cellClass += ' table-success';
                }
                if (cell.classList.contains('align-middle')) {
                    cellClass += ' align-middle';
                }
                if (cell.classList.contains('text-center')) {
                    cellClass += ' text-center';
                }
                if (cell.classList.contains('fw-bold')) {
                    cellClass += ' fw-bold';
                }
                if (cell.classList.contains('bg-light')) {
                    cellClass += ' bg-light';
                }
                
                // Add special styling for different cell types
                if (cell.classList.contains('score-value') || cellContent.match(/^\d+$/)) {
                    cellClass += ' score-value';
                } else if (cellContent.includes('%')) {
                    cellClass += ' percentage-value';
                } else if (cell.textContent.includes('Average') || cell.classList.contains('average-cell')) {
                    cellClass += ' average-cell';
                }
                
                // Check for inline styles (like the bg-primary style)
                if (cell.style && cell.style.cssText) {
                    cellAttrs += ` style="${cell.style.cssText}"`;
                }
                
                tableHTML += `<${tag}${cellAttrs} class="${cellClass.trim()}">${cellContent}</${tag}>`;
            });
            tableHTML += '</tr>';
        });
        
        tableHTML += '</table></div>';
        return tableHTML;
    }
    
    function getAllTablesContent() {
        let content = '';
        const terms = ['prelim', 'midterm', 'prefinal', 'final'];
        
        terms.forEach((term, index) => {
            if (index > 0) {
                content += '<div class="page-break"></div>';
            }
            content += getPrintTableContent(term);
        });
        
        // Add combined table
        content += '<div class="page-break"></div>';
        content += getPrintTableContent('combined');
        
        return content;
    }
    
    // Warning system functions
    function dismissWarning() {
        const warningAlert = document.querySelector('.alert-warning');
        if (warningAlert) {
            warningAlert.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            warningAlert.style.opacity = '0';
            warningAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                warningAlert.style.display = 'none';
            }, 300);
        }
    }
    
    function refreshData() {
        // Show loading state
        const refreshButton = document.querySelector('button[onclick="refreshData()"]');
        if (refreshButton) {
            const originalHTML = refreshButton.innerHTML;
            refreshButton.innerHTML = '<i class="bi bi-arrow-clockwise me-2 spin"></i>Refreshing...';
            refreshButton.disabled = true;
            
            // Add spinning animation
            const style = document.createElement('style');
            style.textContent = `
                .spin {
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }
    
    // Function to close the print modal
    function closePrintModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('printOptionsModal'));
        if (modal) {
            modal.hide();
        }
    }
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
                                    <button class="btn btn-success" onclick="printSpecificTable('all'); closePrintModal();">
                                        <i class="bi bi-grid-3x3 me-2"></i>Print Everything
                                    </button>
                                </div>
                                <hr>
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Combined Table:</strong> Shows all terms in one view<br>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Print Everything:</strong> Includes summary dashboard
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

@endpush
