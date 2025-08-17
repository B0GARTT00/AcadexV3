
@php
    $finalCOs = isset($coColumnsByTerm) && is_array($coColumnsByTerm) ? array_unique(array_merge(...array_values($coColumnsByTerm))) : [];
@endphp
@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <h1 class="h4 fw-bold mb-4">📊 Course Outcome Attainment Results</h1>

    {{-- Print Button Only --}}
    <div class="mb-4">
        <button onclick="printTable()" class="btn btn-success">Print</button>
    </div>

    {{-- Option to toggle between raw score and percentage --}}
    <div class="mb-3">
        <label for="scoreType" class="form-label fw-bold">Display Type:</label>
        <select id="scoreType" class="form-select" style="max-width:220px;display:inline-block;" onchange="toggleScoreType()">
            <option value="score">Raw Score</option>
            <option value="percentage">Percentage</option>
            <option value="passfail">Pass/Fail</option>
            <option value="copasssummary">Course Outcome Pass Summary</option>
        </select>
    </div>

    {{-- Course Outcome Pass Summary --}}
    @if(is_countable($finalCOs) && count($finalCOs))
    <div id="copasssummary-table" style="display:none;">
        <div id="print-area">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-gradient text-white fw-bold text-center" style="background: linear-gradient(90deg,#d32f2f 60%,#f44336 100%); font-size:1.3em; letter-spacing:1px;">Course Outcome Summary</div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 text-center" style="background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                        <thead style="background:#b71c1c; color:#fff;">
                            <tr>
                                <th style="background:#fff; color:#b71c1c; font-weight:700;">Course Outcomes</th>
                                @foreach($finalCOs as $coId)
                                    <th style="font-weight:700;">
                                        {{ $coDetails[$coId]['co_code'] ?? '' }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#f5f5f5;">
                                <td class="fw-bold text-start" style="padding-left:8px; padding-top:4px; padding-bottom:4px;"><span style="color:#1976d2;"><i class="bi bi-person-lines-fill"></i> Number of students attempted</span></td>
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
                                    <td class="fw-bold text-primary">{{ $attempted }}</td>
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-start" style="padding-left:8px; padding-top:4px; padding-bottom:4px;"><span style="color:#388e3c;"><i class="bi bi-check2-circle"></i> Students who passed the required percentage</span></td>
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
                            <tr style="background:#f5f5f5;">
                                <td class="fw-bold text-start" style="padding-left:8px; padding-top:4px; padding-bottom:4px;"><span style="color:#0288d1;"><i class="bi bi-percent"></i> Percentage of students who passed the required percentage</span></td>
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
                                    <td class="fw-bold text-info">{{ $percentPassed }}%</td>
                                @endforeach
                            </tr>
                            <tr style="background:#fff;">
                                <td class="fw-bold text-start" style="padding-left:8px; padding-top:4px; padding-bottom:4px;"><span style="color:#d32f2f;"><i class="bi bi-flag-fill"></i> Target Level Achieved</span></td>
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
                                        // Example logic: target achieved if > 75% of students passed
                                        $targetAchieved = ($attempted > 0 && ($passed / $attempted) * 100 > 75) ? 'Yes' : 'No';
                                    @endphp
                                    <td class="fw-bold text-danger">{{ $targetAchieved }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif


    <div id="print-area">
        @foreach($terms as $term)
            @if(!empty($coColumnsByTerm[$term]))
            <div class="card shadow-sm border-0 mb-4 main-table">
                <div class="card-header bg-success text-white fw-bold">{{ strtoupper($term) }} Term</div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-success">
                            <tr>
                                <th>Students</th>
                                @foreach($coColumnsByTerm[$term] as $coId)
                                    <th class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#e3f2fd;">
                                <td><strong>Total number of items</strong></td>
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
        @php $finalCOs = array_unique(array_merge(...array_values($coColumnsByTerm))); @endphp
        @if(count($finalCOs))
    <div class="card shadow-sm border-0 mb-4 main-table">
            <div class="card-header bg-primary text-white fw-bold">TOTAL (All Terms)</div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Students</th>
                            @foreach($finalCOs as $coId)
                                <th class="text-center">{{ $coDetails[$coId]->co_code ?? 'CO'.$coId }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Total number of items</strong></td>
                            @foreach($finalCOs as $coId)
                                @php
                                    $totalMax = 0;
                                    $percent = $coDetails[$coId]->percent ?? null;
                                    foreach($terms as $term) {
                                        foreach(\App\Models\Activity::where('term', $term)->where('course_outcome_id', $coId)->get() as $activity) {
                                            $totalMax += $activity->number_of_items;
                                        }
                                    }
                                @endphp
                                <td>
                                    <span class="score-value" data-score="{{ $totalMax }}" data-percentage="{{ $percent ?? '' }}">
                                        {{ $totalMax }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                        @foreach($students as $student)
                            <tr>
                                <td>{{ $student->getFullNameAttribute() }}</td>
                                @foreach($finalCOs as $coId)
                                    @php
                                        $raw = $coResults[$student->id]['semester_raw'][$coId] ?? '';
                                        $max = $coResults[$student->id]['semester_max'][$coId] ?? '';
                                        $percent = ($max > 0 && $raw !== '') ? ($raw / $max) * 100 : 0;
                                    @endphp
                                    <td>
                                        <span class="score-value" data-score="{{ $raw !== '' ? $raw : '-' }}" data-percentage="{{ $raw !== '' ? ceil($percent) : '-' }}">
                                            {{ $raw !== '' ? $raw : '-' }} / {{ $max !== '' ? $max : '-' }}
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

        {{-- Pass/Fail Table --}}
        @if(count($finalCOs))
    <div id="passfail-table" class="card shadow-sm border-0 mb-4" style="display:none;">
            <div class="card-header bg-info text-white fw-bold">PASS/FAIL SUMMARY</div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-info">
                        <tr>
                            <th>Students</th>
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
    function toggleScoreType() {
        var type = document.getElementById('scoreType').value;
        var passfailTable = document.getElementById('passfail-table');
        var copasssummaryTable = document.getElementById('copasssummary-table');
        var mainTables = document.querySelectorAll('.main-table');
        if(type === 'passfail') {
            passfailTable && (passfailTable.style.display = 'block');
            copasssummaryTable && (copasssummaryTable.style.display = 'none');
            mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        } else if(type === 'copasssummary') {
            passfailTable && (passfailTable.style.display = 'none');
            copasssummaryTable && (copasssummaryTable.style.display = 'block');
            mainTables.forEach(function(tbl) { tbl.style.display = 'none'; });
        } else {
            passfailTable && (passfailTable.style.display = 'none');
            copasssummaryTable && (copasssummaryTable.style.display = 'none');
            mainTables.forEach(function(tbl) { tbl.style.display = 'block'; });
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
    }
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleScoreType();
    });
    function printTable() {
        const content = document.getElementById('print-area').innerHTML;
        const currentDate = new Date().toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        const printWindow = window.open('', '', 'width=900,height=650');
        printWindow.document.write(`
            <html>
            <head>
                <title>Print CO Attainment Results</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            </head>
            <body>
                <h2>Course Outcome Attainment Results</h2>
                <p><strong>Date:</strong> ${currentDate}</p>
                <div>${content}</div>
            </body>
            </html>
        `);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }
</script>
@endpush
