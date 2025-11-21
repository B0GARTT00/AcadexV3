@extends('layouts.app')

@php
    $termLabels = [
        'prelim' => 'Prelim',
        'midterm' => 'Midterm',
        'prefinal' => 'Prefinal',
        'final' => 'Final',
    ];
@endphp

@section('content')

<div class="container-fluid px-0">
    @if (!request('subject_id'))
        {{-- Card-based Subject Selection --}}
        <div class="px-4 pt-4 pb-2">
            <h1 class="h4 fw-bold mb-0 d-flex align-items-center">
                <i class="bi bi-graph-up text-success me-2" style="font-size: 1.5rem;"></i>
                <span>Final Grades</span>
            </h1>
        </div>
        @if(count($subjects) > 0)
            <div class="row g-4 px-4 py-4" id="subject-selection">
                @foreach($subjects as $subjectItem)
                    <div class="col-md-4">
                        <div
                            class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden"
                            onclick="window.location.href='{{ route('instructor.final-grades.index') }}?subject_id={{ $subjectItem->id }}'"
                            style="cursor: pointer;"
                        >
                            {{-- Top header --}}
                            <div class="position-relative" style="height: 80px;">
                                <div class="subject-circle position-absolute start-50 translate-middle"
                                    style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <h5 class="mb-0 text-white fw-bold">{{ $subjectItem->subject_code }}</h5>
                                </div>
                            </div>

                            {{-- Card body --}}
                            <div class="card-body pt-5 text-center">
                                <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $subjectItem->subject_description }}">
                                    {{ $subjectItem->subject_description }}
                                </h6>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-warning text-center mt-5 mx-4 rounded">
                No subjects have been assigned to you yet.
            </div>
        @endif
    @else
        {{-- Selected Subject View --}}
        <div class="px-4 py-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h1 class="h4 fw-bold mb-0 d-flex align-items-center">
                    <i class="bi bi-graph-up text-success me-2" style="font-size: 1.5rem;"></i>
                    <span>Final Grades</span>
                </h1>

                @if(!empty($finalData) && count($finalData) > 0)
                    <button type="button" class="btn btn-success shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#printOptionsModal">
                        <i class="bi bi-printer-fill"></i>
                        <span>Print Options</span>
                    </button>
                @endif
            </div>

            {{-- Breadcrumb Navigation --}}
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('instructor.final-grades.index') }}" class="text-success text-decoration-none border-bottom border-success">
                            Final Grades
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">
                        @php
                            $selectedSubject = $subjects->firstWhere('id', request('subject_id'));
                        @endphp
                        {{ $selectedSubject ? $selectedSubject->subject_code : 'Subject' }}
                    </li>
                </ol>
            </nav>

            {{-- Generate Final Grades --}}
            @if(empty($finalData))
                <form method="POST" action="{{ route('instructor.final-grades.generate') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <button type="submit" class="btn btn-success px-4 shadow-sm">
                        <i class="bi bi-arrow-repeat me-1"></i> Generate Final Grades
                    </button>
                </form>
            @endif

    {{-- Final Grades Table --}}
    @if(!empty($finalData) && count($finalData) > 0)
        <div class="card shadow-sm border-0" id="print-area">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="text-start">Student Name</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Prefinal</th>
                            <th>Final</th>
                            <th class="text-primary">Final Average</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($finalData as $data)
                            <tr class="hover-shadow-sm">
                                <td class="fw-semibold text-start">
                                    {{ $data['student']->last_name }}, {{ $data['student']->first_name }}
                                </td>
                                <td class="text-center">{{ isset($data['prelim']) ? (int) round($data['prelim']) : '–' }}</td>
                                <td class="text-center">{{ isset($data['midterm']) ? (int) round($data['midterm']) : '–' }}</td>
                                <td class="text-center">{{ isset($data['prefinal']) ? (int) round($data['prefinal']) : '–' }}</td>
                                <td class="text-center">{{ isset($data['final']) ? (int) round($data['final']) : '–' }}</td>
                                <td class="text-center fw-bold text-success">
                                    {{ isset($data['final_average']) ? (int) round($data['final_average']) : '–' }}
                                </td>
                                <td class="text-center">
                                    @if(isset($data['remarks']))
                                        @if(strtolower($data['remarks']) === 'passed')
                                            <span class="badge bg-success px-3 py-1">Passed</span>
                                        @elseif(strtolower($data['remarks']) === 'failed')
                                            <span class="badge bg-danger px-3 py-1">Failed</span>
                                        @else
                                            <span class="badge bg-secondary px-3 py-1">{{ $data['remarks'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="alert alert-warning text-center mt-3 rounded-3">
                No students or grades found for the selected subject.
            </div>
        @endif
        </div>
    @endif
</div>

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
                                        <i class="bi bi-printer me-2"></i>Print Prelim Term Sheet
                                    </button>
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('midterm'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Midterm Term Sheet
                                    </button>
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('prefinal'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Prefinal Term Sheet
                                    </button>
                                    <button class="btn btn-outline-success" onclick="printSpecificTable('final'); closePrintModal();">
                                        <i class="bi bi-printer me-2"></i>Print Final Term Sheet
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-table me-2"></i>Complete Report</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" onclick="printSpecificTable('summary'); closePrintModal();">
                                        <i class="bi bi-table me-2"></i>Print Final Summary
                                    </button>
                                </div>
                                <hr>
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Final Summary:</strong> Shows all term grades and final averages<br>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Term Sheets:</strong> Detailed activities and scores per term
                                </div>
                                
                                <div class="mt-2 small text-muted">
                                    ⚠️ To remove the URL or headers/footers printed by your browser, uncheck <em>Headers &amp; footers</em> in the print dialog. If you need a PDF without headers/footers, please use the "Export PDF" option or ask me to generate server-side PDFs.
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

{{-- View Notes Modal (Read-Only for Instructors) --}}
<div class="modal fade" id="viewNotesModal" tabindex="-1" aria-labelledby="viewNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewNotesModalLabel">
                    <i class="bi bi-sticky me-2"></i>
                    Chairperson/Coordinator Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Student Name:</label>
                    <p class="text-muted" id="viewStudentNameDisplay"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes/Remarks:</label>
                    <div class="border rounded p-3 bg-light" id="viewNotesContent" style="min-height: 100px; white-space: pre-wrap;"></div>
                </div>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> These notes are added by your chairperson or GE coordinator. You cannot edit them.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Enhanced Card Styling - Matching Chairperson View Grades */
.subject-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.subject-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.subject-card:hover::before {
    left: 100%;
}

.subject-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 24px rgba(77, 166, 116, 0.3) !important;
}

.subject-card .position-relative {
    background: linear-gradient(135deg, #4da674 0%, #3d8a5e 100%) !important;
}

.subject-card:hover .position-relative {
    background: linear-gradient(135deg, #3d8a5e 0%, #2d6a4e 100%) !important;
}

.subject-circle {
    transition: all 0.3s ease;
}

.subject-card:hover .subject-circle {
    transform: translate(-50%, -50%) rotate(5deg) scale(1.1) !important;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
}

.subject-card .card-body {
    background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    transition: background 0.3s;
}

.subject-card:hover .card-body {
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
}

.subject-card h6 {
    transition: color 0.3s;
}

.subject-card:hover h6 {
    color: #4da674 !important;
}
</style>
@endpush

@push('scripts')
<script>
    const termReportUrl = "{{ route('instructor.final-grades.term-report') }}";
    const bannerUrl = "{{ asset('images/banner-header.png') }}";
    const currentSubjectId = "{{ request('subject_id') }}";

    // Close print modal helper
    function closePrintModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('printOptionsModal'));
        if (modal) {
            modal.hide();
        }
    }

    // View Notes Modal Handler
    document.addEventListener('DOMContentLoaded', function() {
        const viewNotesModal = new bootstrap.Modal(document.getElementById('viewNotesModal'), {
            backdrop: false
        });
        const viewStudentNameDisplay = document.getElementById('viewStudentNameDisplay');
        const viewNotesContent = document.getElementById('viewNotesContent');

        // Handle view notes button click
        document.querySelectorAll('.view-notes-btn').forEach(button => {
            button.addEventListener('click', function() {
                const studentName = this.dataset.studentName;
                const notes = this.dataset.notes || 'No notes available.';
                
                // Populate modal
                viewStudentNameDisplay.textContent = studentName;
                viewNotesContent.textContent = notes;
                
                // Show modal
                viewNotesModal.show();
            });
        });

    });

    // Print specific table function (handles both summary and term sheets)
    function printSpecificTable(tableType) {
        if (!currentSubjectId) {
            alert('Please select a subject first.');
            return;
        }

        if (tableType === 'summary') {
            // Print the final summary table
            printFinalSummary();
        } else {
            // Print individual term sheet — fetch HTML then print via iframe to avoid about:blank footers
            const subjectId = currentSubjectId;
            const url = new URL(termReportUrl);
            url.searchParams.set('subject_id', subjectId);
            url.searchParams.set('term', tableType);

            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Unable to prepare the term sheet.');
                }
                return response.text();
            })
            .then(html => {
                printHtml(html);
            })
            .catch(error => {
                console.error(error);
                alert(error.message || 'Failed to generate the term sheet. Please try again.');
            });
        }
        }

    // Print HTML via hidden iframe (preferred) with window.open fallback
    function printHtml(html) {
        try {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.visibility = 'hidden';
            iframe.setAttribute('id', 'aca_print_iframe');
            // Use srcdoc when available
            if ('srcdoc' in iframe) {
                iframe.srcdoc = html;
            } else {
                // Fallback for older browsers — use a Blob URL to avoid printing the current page URL
                try {
                    const blob = new Blob([html], { type: 'text/html' });
                    iframe.src = URL.createObjectURL(blob);
                } catch (e) {
                    iframe.src = 'about:blank';
                }
            }
            document.body.appendChild(iframe);

            const onLoad = () => {
                try {
                    const win = iframe.contentWindow || iframe;
                    win.focus();
                    // Give browser a moment to render
                    setTimeout(() => {
                        try {
                            win.print();
                        } finally {
                            // Remove iframe after printing
                            setTimeout(() => { document.body.removeChild(iframe); }, 500);
                        }
                    }, 250);
                } catch (e) {
                    console.error('Iframe print failed, falling back to window.open', e);
                    try { document.body.removeChild(iframe); } catch (e2) {}
                    // Fallback to Blob URL opened in new window (better than opening a route)
                    try {
                        const blob2 = new Blob([html], { type: 'text/html' });
                        const blobUrl = URL.createObjectURL(blob2);
                        const w = window.open(blobUrl, '_blank', 'width=900,height=650');
                        if (!w) { alert('Please allow pop-ups to print the report.'); return; }
                        // Attempt to print once the new window loads
                        w.addEventListener('load', function(){
                            try { w.print(); } finally { setTimeout(() => URL.revokeObjectURL(blobUrl), 1000); }
                        });
                    } catch (e2) {
                        const w = window.open('', '', 'width=900,height=650');
                        if (!w) { alert('Please allow pop-ups to print the report.'); return; }
                        w.document.open(); w.document.write(html); w.document.close();
                        setTimeout(() => w.print(), 400);
                    }
                }
            };

            if ('srcdoc' in iframe) {
                iframe.onload = onLoad;
            } else {
                // Write content into iframe
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                doc.open(); doc.write(html); doc.close();
                onLoad();
            }
        } catch (e) {
            console.error('printHtml error', e);
            // Last resort fallback
            const w = window.open('', '', 'width=900,height=650');
            if (!w) { alert('Please allow pop-ups to print the report.'); return; }
            w.document.open(); w.document.write(html); w.document.close();
            setTimeout(() => w.print(), 400);
        }
    }

    // Print Final Summary Function
    function printFinalSummary() {
        const content = document.getElementById('print-area').innerHTML;
        @php
            $currentSubject = $subjects->firstWhere('id', request('subject_id'));
            $subjectCode = $currentSubject ? $currentSubject->subject_code : '';
            $subjectDesc = $currentSubject ? $currentSubject->description : '';
        @endphp
        const subjectCode = @json($subjectCode);
        const subjectDesc = @json($subjectDesc);
        const subject = `${subjectCode} - ${subjectDesc}`;
        
        // Count passed and failed students from the data
        @php
            $passedStudents = 0;
            $failedStudents = 0;
            if (!empty($finalData)) {
                foreach ($finalData as $data) {
                    if (isset($data['remarks'])) {
                        if (strtolower($data['remarks']) === 'passed') {
                            $passedStudents++;
                        } elseif (strtolower($data['remarks']) === 'failed') {
                            $failedStudents++;
                        }
                    }
                }
            }
            $totalStudents = $passedStudents + $failedStudents;
            $passRate = $totalStudents > 0 ? round(($passedStudents / $totalStudents) * 100, 1) : 0;
        @endphp
        const passedStudents = {{ $passedStudents }};
        const failedStudents = {{ $failedStudents }};
        const totalStudents = {{ $totalStudents }};
        const passRate = {{ $passRate }};

        // Get current academic period
        @php
            $activePeriod = \App\Models\AcademicPeriod::find(session('active_academic_period_id'));
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
        const academicPeriod = "{{ $activePeriod ? $activePeriod->academic_year : '' }}";
        const semester = "{{ $semesterLabel }}";
        const currentDate = new Date().toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    
        // Helper to format numeric score strings: drop trailing .00
        const formatScore = (txt) => {
            if (!txt && txt !== 0) return '';
            const raw = String(txt).trim();
            // Extract numeric portion (allow negative, decimals)
            const cleaned = raw.replace(/[^0-9.\-]/g, '');
            if (cleaned === '') return raw;
            const n = parseFloat(cleaned);
            if (isNaN(n)) return raw;
            if (Math.abs(n - Math.round(n)) < 0.0001) return String(Math.round(n));
            return String(Math.round(n * 100) / 100);
        };

        const html = `
            <html>
                <head>
                    <title>Grade Report - ${subject}</title>
                    <style>
                        @media print {
                            @page {
                                size: portrait;
                                margin: 0.5in;
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
                            font-size: 22px;
                            font-weight: bold;
                            text-align: center;
                            margin: 15px 0;
                            text-transform: uppercase;
                            letter-spacing: 2px;
                            color: #1a5f38;
                            border-bottom: 2px solid #1a5f38;
                            padding-bottom: 8px;
                        }

                        .metadata {
                            text-align: right;
                            font-size: 12px;
                            color: #666;
                            margin-bottom: 20px;
                        }

                        .header-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 25px;
                            background-color: #fff;
                            font-size: 11px;
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
                        }

                        .stats-container {
                            background-color: #f0f7f4;
                            border: 1px solid #7fb3a3;
                            border-radius: 4px;
                            margin: 0;
                            padding: 8px;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                        }

                        .stats-title {
                            font-weight: 600;
                            text-transform: uppercase;
                            margin-bottom: 6px;
                            font-size: 10px;
                            color: #1a5f38;
                            border-bottom: 1px solid #7fb3a3;
                            padding-bottom: 3px;
                        }

                        .stats-grid {
                            display: grid;
                            grid-template-columns: repeat(3, 1fr);
                            gap: 6px;
                        }

                        .stat-item {
                            background-color: #fff;
                            padding: 4px;
                            border-radius: 3px;
                            border: 1px solid #7fb3a3;
                            text-align: center;
                        }

                        .stat-label {
                            font-size: 9px;
                            color: #1a5f38;
                            margin-bottom: 1px;
                            letter-spacing: 0.5px;
                            font-weight: 600;
                        }

                        .stat-value {
                            font-size: 12px;
                            font-weight: bold;
                            color: #1a5f38;
                        }

                        .passed-count { color: #28a745; }
                        .failed-count { color: #dc3545; }
                        .total-count { color: #1a5f38; }

                        /* Print-specific table styles */
                        .print-table {
                            width: 100%;
                            border-collapse: collapse;
                            border: 2px solid #1a5f38;
                            background-color: #fff;
                            margin-top: 15px;
                            font-size: 11px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }

                        .print-table th, .print-table td {
                            border: 1px solid #7fb3a3;
                            padding: 8px;
                            font-size: 11px;
                            text-align: center;
                            vertical-align: middle;
                        }

                        /* Add specific border styling for grade columns */
                        .print-table th:nth-child(3),
                        .print-table th:nth-child(4),
                        .print-table th:nth-child(5),
                        .print-table th:nth-child(6),
                        .print-table th:nth-child(7),
                        .print-table th:nth-child(8),
                        .print-table td:nth-child(3),
                        .print-table td:nth-child(4),
                        .print-table td:nth-child(5),
                        .print-table td:nth-child(6),
                        .print-table td:nth-child(7),
                        .print-table td:nth-child(8) {
                            border-left: 1px solid #7fb3a3;
                            border-right: 1px solid #7fb3a3;
                        }

                        .print-table th {
                            background-color: #1a5f38;
                            color: #fff;
                            font-weight: bold;
                            text-transform: uppercase;
                            text-align: center;
                            white-space: nowrap;
                            border: 1px solid #1a5f38;
                            padding: 10px 8px;
                        }

                        .print-table th:first-child {
                            background-color: #0d4b2a;
                        }

                        .print-table tr:nth-child(even) {
                            background-color: #f0f7f4;
                        }

                        .print-table tr:hover {
                            background-color: #e8f3ef;
                        }

                        /* Print-specific column widths */
                        .print-table th:nth-child(1) { width: 5%; } /* Number */
                        .print-table th:nth-child(2) { width: 25%; text-align: left; } /* Student Name */
                        .print-table th:nth-child(3) { width: 12%; } /* Prelim */
                        .print-table th:nth-child(4) { width: 12%; } /* Midterm */
                        .print-table th:nth-child(5) { width: 12%; } /* Prefinal */
                        .print-table th:nth-child(6) { width: 12%; } /* Final */
                        .print-table th:nth-child(7) { width: 12%; } /* Final Average */
                        .print-table th:nth-child(8) { width: 10%; } /* Remarks */

                        .print-table td:first-child {
                            text-align: center;
                            background-color: #f0f7f4;
                            font-weight: 500;
                        }

                        .print-table td:nth-child(2) {
                            text-align: left;
                            font-weight: 500;
                        }

                        .print-table td:not(:first-child):not(:nth-child(2)) {
                            text-align: center;
                        }

                        /* Grade value styling */
                        .print-table td:nth-child(3),
                        .print-table td:nth-child(4),
                        .print-table td:nth-child(5),
                        .print-table td:nth-child(6),
                        .print-table td:nth-child(7) {
                            font-family: 'Arial', sans-serif;
                            font-weight: 500;
                        }

                        /* Final Average column special styling */
                        .print-table td:nth-child(7) {
                            font-weight: bold;
                            color: #1a5f38;
                        }

                        /* Remarks badge styling */
                        .print-badge {
                            padding: 4px 8px;
                            border-radius: 3px;
                            font-size: 10px;
                            font-weight: bold;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            display: inline-block;
                            text-align: center;
                            min-width: 60px;
                        }

                        .print-badge.passed {
                            background-color: #d4edda;
                            color: #155724;
                            border: 1px solid #c3e6cb;
                        }

                        .print-badge.failed {
                            background-color: #f8d7da;
                            color: #721c24;
                            border: 1px solid #f5c6cb;
                        }

                        /* Header table styling */
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

                        /* Stats container styling */
                        .stats-container {
                            background-color: #f0f7f4;
                            border: 1px solid #7fb3a3;
                            border-radius: 4px;
                            margin: 0;
                            padding: 8px;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                        }

                        .stats-title {
                            font-weight: 600;
                            text-transform: uppercase;
                            margin-bottom: 6px;
                            font-size: 10px;
                            color: #1a5f38;
                            border-bottom: 1px solid #7fb3a3;
                            padding-bottom: 3px;
                        }

                        .stats-grid {
                            display: grid;
                            grid-template-columns: repeat(3, 1fr);
                            gap: 6px;
                        }

                        .stat-item {
                            background-color: #fff;
                            padding: 4px;
                            border-radius: 3px;
                            border: 1px solid #7fb3a3;
                            text-align: center;
                        }

                        .stat-label {
                            font-size: 9px;
                            color: #1a5f38;
                            margin-bottom: 1px;
                            letter-spacing: 0.5px;
                            font-weight: 600;
                        }

                        .stat-value {
                            font-size: 12px;
                            font-weight: bold;
                            color: #1a5f38;
                        }

                        .passed-count { color: #28a745; }
                        .failed-count { color: #dc3545; }
                        .total-count { color: #1a5f38; }

                        .footer {
                            margin-top: 20px;
                            padding-top: 15px;
                            border-top: 1px solid #dee2e6;
                            font-size: 11px;
                            color: #666;
                            text-align: center;
                        }
                    </style>
                </head>
                <body>
                    <img src="${bannerUrl}" alt="Banner Header" class="banner">
                    
                    <div class="header-content">
                        <div class="report-title">Report of Grades</div>
                        
                        <table class="header-table">
                            <tr>
                                <td class="header-label">Course Code:</td>
                                <td class="header-value">${subjectCode}</td>
                                <td class="header-label">Units:</td>
                                <td class="header-value">{{ $currentSubject->units ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="header-label">Description:</td>
                                <td class="header-value">${subjectDesc}</td>
                                <td class="header-label">Semester:</td>
                                <td class="header-value">${semester}</td>
                            </tr>
                            <tr>
                                <td class="header-label">Course/Section:</td>
                                <td class="header-value">{{ $currentSubject->course->course_code ?? 'N/A' }}</td>
                                <td class="header-label">School Year:</td>
                                <td class="header-value">${academicPeriod}</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="padding: 0;">
                                    <div class="stats-container">
                                        <div class="stats-title">Class Performance Summary</div>
                                        <div class="stats-grid">
                                            <div class="stat-item">
                                                <div class="stat-label">PASSED STUDENTS</div>
                                                <div class="stat-value passed-count">${passedStudents}</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-label">FAILED STUDENTS</div>
                                                <div class="stat-value failed-count">${failedStudents}</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-label">PASSING RATE</div>
                                                <div class="stat-value total-count">${passRate}%</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table class="print-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Prelim</th>
                                    <th>Midterm</th>
                                    <th>Prefinal</th>
                                    <th>Final</th>
                                    <th>Final Average</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${Array.from(document.querySelectorAll('#print-area tbody tr')).map((row, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${row.cells[0].textContent.trim()}</td>
                                        <td>${formatScore(row.cells[1].textContent)}</td>
                                        <td>${formatScore(row.cells[2].textContent)}</td>
                                        <td>${formatScore(row.cells[3].textContent)}</td>
                                        <td>${formatScore(row.cells[4].textContent)}</td>
                                        <td>${formatScore(row.cells[5].textContent)}</td>
                                        <td>
                                            ${row.cells[6].textContent.trim().includes('Passed') 
                                                ? `<span class="print-badge passed">Passed</span>`
                                                : row.cells[6].textContent.trim().includes('Failed')
                                                ? `<span class="print-badge failed">Failed</span>`
                                                : row.cells[6].textContent.trim()}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div class="footer">
                        This is a computer-generated document. No signature is required.
                        <br>
                        Printed via ACADEX - Academic Grade System
                    </div>
                </body>
            </html>
        `;
        // Use iframe-based printing to avoid browser URL footers when possible
        printHtml(html);
    }
</script>
@endpush
