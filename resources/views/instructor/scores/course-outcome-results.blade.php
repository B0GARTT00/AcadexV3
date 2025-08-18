@php
    $finalCOs = isset($coColumnsByTerm) && is_array($coColumnsByTerm) ? array_unique(array_merge(...array_values($coColumnsByTerm))) : [];
@endphp
@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
<style>
    /* Print Settings - Default A4 Portrait */
    @media print {
        @page {
            size: A4 portrait;
            margin: 0.75in 0.5in;
        }
        
        body {
            font-size: 12px;
            line-height: 1.4;
        }
        
        .no-print {
            display: none !important;
        }
        
        .header-section, .controls-panel, .stepper-container {
            display: none !important;
        }
        
        .results-card {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
            page-break-inside: avoid;
        }
        
        table {
            font-size: 10px;
            page-break-inside: auto;
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        thead {
            display: table-header-group;
        }
        
        tfoot {
            display: table-footer-group;
        }
    }

    /* Enhanced UI Styles for Course Outcome Results */
    .co-results-container {
        background: linear-gradient(135deg, #f8fffe 0%, #e8f5f0 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .header-section {
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(25, 135, 84, 0.15);
        color: white;
    }
    
    .header-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    .header-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    .controls-panel {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    
    .control-group {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .control-label {
        font-weight: 600;
        color: #2c3e50;
        min-width: 120px;
        font-size: 0.95rem;
    }
    
    .control-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.6rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .control-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }
    
    .print-btn {
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        border: none;
        border-radius: 12px;
        padding: 0.7rem 1.5rem;
        font-weight: 600;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.2);
    }
    
    .print-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(25, 135, 84, 0.3);
    }

    /* System-wide table style for CO Results */
    .co-table {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 6px 30px rgba(25,135,84,0.08);
        background: #fff;
        border: none;
    }
    .co-table th, .co-table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        font-size: 0.95rem;
        border-color: #e9ecef;
    }
    .co-table thead th {
        background: linear-gradient(135deg, #198754 0%, #157347 100%) !important;
        color: white !important;
        border-bottom: none !important;
        position: sticky;
        top: 0;
        z-index: 10;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
    }
    .co-table tbody tr:hover td {
        background-color: rgba(25,135,84,0.05);
        transition: background-color 0.2s;
    }
    .co-table tbody td {
        background-color: #fff;
        transition: background-color 0.2s;
    }
    .co-table th:not(:last-child) {
        border-right: 1px solid rgba(255,255,255,0.2);
    }
    .co-table .fw-bold {
        color: #198754;
    }
    .co-table .text-success {
        color: #388e3c !important;
    }
    .co-table .text-danger {
        color: #d32f2f !important;
    }
    .co-table .text-info {
        color: #20c997 !important;
    }
    .co-table .text-primary {
        color: #198754 !important;
    }
    .co-table .score-value {
        font-weight: 600;
        font-size: 1.05em;
        padding: 0.3rem 0.6rem;
        border-radius: 8px;
        background: rgba(25,135,84,0.05);
        display: inline-block;
        min-width: 40px;
        text-align: center;
    }
    .co-table .badge {
        font-size: 0.85em;
        padding: 0.5em 1em;
        border-radius: 20px;
        font-weight: 600;
    }
    .co-table .bi {
        font-size: 1.1em;
        vertical-align: middle;
        margin-right: 0.3em;
    }
    
    .results-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 40px rgba(0,0,0,0.06);
        border: none;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }
    
    .results-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 50px rgba(0,0,0,0.08);
    }
    
    .card-header-custom {
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
        padding: 1.5rem 2rem;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .card-header-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .card-header-primary {
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
    }
    
    .card-header-info {
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    }
    
    /* Term Stepper Enhanced Styles */
    .stepper-container {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    
    .stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0;
        position: relative;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        flex: 1;
        text-decoration: none;
        transition: all 0.3s ease;
        background: none;
        border: none;
        cursor: pointer;
        padding: 1rem;
        border-radius: 12px;
    }
    
    .step:hover {
        background: rgba(25,135,84,0.05);
        transform: translateY(-2px);
    }

    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 40px;
        left: calc(50% + 40px);
        width: calc(100% - 80px);
        height: 3px;
        background: linear-gradient(90deg, #d1e7db 0%, #a7d4c1 100%);
        border-radius: 2px;
        z-index: 0;
        transition: all 0.3s ease;
    }

    .step.highlight-line:not(:last-child)::after {
        background: linear-gradient(90deg, #198754 0%, #20c997 100%);
        box-shadow: 0 2px 8px rgba(25,135,84,0.3);
    }

    .circle-wrapper {
        position: relative;
        width: 70px;
        height: 70px;
    }

    .circle {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        z-index: 2;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .step:hover .circle {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .completed .circle {
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        animation: completedPulse 2s ease-in-out;
    }

    .active .circle {
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        animation: activePulse 1.5s infinite;
    }

    .upcoming .circle {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        color: #6c757d;
        border: 2px solid #dee2e6;
    }

    .step-label {
        font-size: 14px;
        margin-top: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .completed .step-label {
        color: #198754;
        font-weight: 700;
    }

    .active .step-label {
        color: #198754;
        font-weight: 700;
    }

    .upcoming .step-label {
        color: #6c757d;
    }

    @keyframes activePulse {
        0% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
        }
        70% {
            box-shadow: 0 0 0 15px rgba(25, 135, 84, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
        }
    }
    
    @keyframes completedPulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }
    
    .stepper-hint {
        text-align: center;
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 1rem;
        font-style: italic;
    }
    
    .view-indicator {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        color: white;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-left: 1rem;
        box-shadow: 0 2px 8px rgba(25,135,84,0.2);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .header-title {
            font-size: 1.8rem;
        }
        
        .control-group {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .control-label {
            min-width: auto;
        }
        
        .step {
            padding: 0.5rem;
        }
        
        .circle {
            width: 44px;
            height: 44px;
            font-size: 16px;
        }
        
        .step-label {
            font-size: 12px;
        }
    }
</style>

<div class="co-results-container">
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

    {{-- Header Section --}}
    <div class="header-section">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="header-title">📊 Course Outcome Attainment Results</h1>
                <p class="header-subtitle">Comprehensive analysis of student performance across all terms and course outcomes</p>
            </div>
            <div class="d-flex align-items-center no-print">
                @if(isset($coDetails) && count($coDetails) > 0)
                    <!-- Print Options Dropdown -->
                    <div class="dropdown me-2">
                        <button class="btn btn-success dropdown-toggle" type="button" id="printDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            🖨️ Print Options
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="printDropdown">
                            <li><h6 class="dropdown-header">🔄 Individual Terms</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="printSpecificTable('prelim')">
                                <i class="bi bi-printer me-2"></i>Print Prelim Only
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printSpecificTable('midterm')">
                                <i class="bi bi-printer me-2"></i>Print Midterm Only
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printSpecificTable('prefinal')">
                                <i class="bi bi-printer me-2"></i>Print Prefinal Only
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printSpecificTable('final')">
                                <i class="bi bi-printer me-2"></i>Print Final Only
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">📋 Complete Reports</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="printSpecificTable('combined')">
                                <i class="bi bi-table me-2"></i>Print Combined Table
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printSpecificTable('all')">
                                <i class="bi bi-collection me-2"></i>Print Everything
                            </a></li>
                        </ul>
                    </div>
                @endif
                <button onclick="printTable()" class="print-btn">
                    <i class="bi bi-printer me-2"></i>Print Results
                </button>
            </div>
        </div>
    </div>

    {{-- Controls Panel --}}
    <div class="controls-panel no-print">
        <div class="control-group">
            <label class="control-label">Display Type:</label>
            <select id="scoreType" class="control-select" onchange="toggleScoreType()">
                <option value="score">📝 Raw Score</option>
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
                @endphp
                <button type="button"
                        class="step term-step {{ $class }}"
                        data-term="{{ $termSlug }}"
                        onclick="switchTerm('{{ $termSlug }}', {{ $index }})">
                    <div class="circle-wrapper">
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
                                        $threshold = $coDetails[$coId]->percent ?? 0;
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
                                        $threshold = $coDetails[$coId]->percent ?? 0;
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
                                        $threshold = $coDetails[$coId]->percent ?? 75;
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
                                        $targetAchieved = ($attempted > 0 && ($passed / $attempted) * 100 > 75) ? 'Yes' : 'No';
                                        $badgeClass = $targetAchieved === 'Yes' ? 'bg-success' : 'bg-danger';
                                    @endphp
                                    <td>
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $targetAchieved === 'Yes' ? '✓ Yes' : '✗ No' }}
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

    {{-- Warning System for Incomplete CO Records --}}
    @php
        $incompleteActivities = [];
        $incompleteCOs = [];
        $zeroScoreCounts = [];
        
        // Check for incomplete records across all terms and COs
        foreach($terms as $term) {
            if(!empty($coColumnsByTerm[$term])) {
                foreach($coColumnsByTerm[$term] as $coId) {
                    $totalZeroScores = 0;
                    $totalStudents = count($students);
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
    @endphp

    @if(count($incompleteCOs) > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 5px solid #f39c12 !important;">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0 me-3">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2rem;"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-3">
                    <i class="bi bi-clipboard-data me-2"></i>
                    Incomplete Course Outcome Records Detected
                </h5>
                <p class="mb-3">
                    <strong>{{ count($incompleteCOs) }}</strong> Course Outcome(s) have incomplete or missing score records. 
                    This may affect the accuracy of your Course Outcome Attainment analysis.
                </p>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-3" style="background: rgba(255,255,255,0.8);">
                                <thead class="table-warning">
                                    <tr>
                                        <th style="font-size: 0.85rem;">Course Outcome</th>
                                        <th style="font-size: 0.85rem;">Term</th>
                                        <th style="font-size: 0.85rem;">Missing/Zero Scores</th>
                                        <th style="font-size: 0.85rem;">Completion Status</th>
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
                        <div class="bg-light p-3 rounded" style="border-left: 4px solid #f39c12;">
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
                
                <hr class="my-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Tip:</strong> Complete all activity scores to ensure accurate Course Outcome analysis.
                        </small>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="dismissWarning()">
                        <i class="bi bi-x-lg me-1"></i>Dismiss Warning
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div id="print-area">
        @php $finalCOs = array_unique(array_merge(...array_values($coColumnsByTerm))); @endphp
        
        {{-- Combined Table for All Terms (shown by default) --}}
        @if(count($finalCOs))
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
                                        $percent = $coDetails[$coId]->percent ?? null;
                                    @endphp
                                    <td>
                                        <span class="score-value" data-score="{{ $max }}" data-percentage="{{ $percent ?? '' }}">
                                            {{ $max }}
                                        </span>
                                    </td>
                                @endforeach
                                @php
                                    $totalMax = 0;
                                    $percent = $coDetails[$coId]->percent ?? null;
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
                                        $percent = $coDetails[$coId]->percent ?? null;
                                    @endphp
                                    <td>
                                        <span class="score-value" data-score="{{ $max }}" data-percentage="{{ $percent ?? '' }}">
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
        @if(count($finalCOs))
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
                                        $threshold = $coDetails[$coId]->percent ?? 75;
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
    </div>
</div>
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
        
        // Update stepper appearance
        var steps = document.querySelectorAll('.term-step');
        steps.forEach(function(step, i) {
            step.classList.remove('active', 'completed', 'upcoming', 'highlight-line');
            
            if (i < index) {
                step.classList.add('completed', 'highlight-line');
            } else if (i === index) {
                step.classList.add('active');
            } else {
                step.classList.add('upcoming');
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
                                font-size: 11px;
                            }
                            
                            table {
                                font-size: 10px;
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
                            color: #1a5f38;
                            border-bottom: 2px solid #1a5f38;
                            padding-bottom: 8px;
                        }

                        .header-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 25px;
                            background-color: #fff;
                            font-size: 11px;
                            border: 2px solid #1a5f38;
                        }

                        .header-table td {
                            padding: 8px 12px;
                            border: 1px solid #7fb3a3;
                        }

                        .header-label {
                            font-weight: bold;
                            width: 120px;
                            background-color: #1a5f38;
                            color: #fff;
                        }

                        .header-value {
                            font-family: 'Arial', sans-serif;
                            font-weight: 500;
                        }

                        .print-table {
                            width: 100%;
                            border-collapse: collapse;
                            border: 2px solid #1a5f38;
                            background-color: #fff;
                            margin-top: 15px;
                            font-size: 10px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }

                        .print-table th, .print-table td {
                            border: 1px solid #7fb3a3;
                            padding: 6px 4px;
                            text-align: center;
                            vertical-align: middle;
                        }

                        .print-table th {
                            background-color: #1a5f38;
                            color: #fff;
                            font-weight: bold;
                            text-transform: uppercase;
                            white-space: nowrap;
                            font-size: 9px;
                        }

                        .print-table th:first-child {
                            background-color: #0d4b2a;
                            text-align: left;
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
        
        // Copy table content
        const rows = table.querySelectorAll('tr');
        rows.forEach((row, index) => {
            const isHeader = row.closest('thead') !== null;
            const tag = isHeader ? 'th' : 'td';
            
            tableHTML += '<tr>';
            const cells = row.querySelectorAll(isHeader ? 'th' : 'td');
            cells.forEach(cell => {
                let cellContent = cell.textContent.trim();
                let cellClass = '';
                
                // Add special styling for different cell types
                if (cell.classList.contains('score-value') || cellContent.match(/^\d+$/)) {
                    cellClass = 'score-value';
                } else if (cellContent.includes('%')) {
                    cellClass = 'percentage-value';
                } else if (cell.textContent.includes('Average') || cell.classList.contains('average-cell')) {
                    cellClass = 'average-cell';
                }
                
                tableHTML += `<${tag} class="${cellClass}">${cellContent}</${tag}>`;
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
</script>
@endpush
