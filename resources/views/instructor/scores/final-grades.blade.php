@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <h1 class="h4 fw-bold mb-4">📈 Final Grades</h1>

    {{-- Subject Selection + Print Button --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <form method="GET" action="{{ route('instructor.final-grades.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label fw-medium mb-0">Select Subject:</label>
            <select name="subject_id" class="form-select w-auto" onchange="this.form.submit()">
                <option value="">-- Choose Subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                        {{ $subject->subject_code }} - {{ $subject->subject_description }}
                    </option>
                @endforeach
            </select>
        </form>

        @if(!empty($finalData) && count($finalData) > 0)
            <button onclick="printTable()" class="btn btn-success">
                🖨️ Print Table
            </button>
        @endif
    </div>

    {{-- Generate Final Grades --}}
    @if(request('subject_id') && empty($finalData))
        <form method="POST" action="{{ route('instructor.final-grades.generate') }}" class="mb-4">
            @csrf
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
            <button type="submit" class="btn btn-success px-4 shadow-sm">
                🔄 Generate Final Grades
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
                            <th>Notes</th>
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
                                <td class="text-center">
                                    @if($data['has_notes'])
                                        <button 
                                            class="btn btn-sm btn-outline-info view-notes-btn"
                                            data-student-name="{{ $data['student']->last_name }}, {{ $data['student']->first_name }}"
                                            data-notes="{{ $data['notes'] }}"
                                            title="View notes from chairperson/coordinator"
                                        >
                                            <i class="bi bi-eye"></i> View Notes
                                        </button>
                                    @else
                                        <span class="text-muted fst-italic">No notes</span>
                                    @endif
                                </td>                                
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @if(request('subject_id'))
            <div class="alert alert-warning text-center mt-5 rounded-3">
                No students or grades found for the selected subject.
            </div>
        @endif
    @endif
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

@push('scripts')
<script>
    // View Notes Modal Handler
    document.addEventListener('DOMContentLoaded', function() {
        const viewNotesModal = new bootstrap.Modal(document.getElementById('viewNotesModal'));
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

    // Print Table Function
    function printTable() {
        const content = document.getElementById('print-area').innerHTML;
        const subject = document.querySelector("select[name='subject_id']").selectedOptions[0].text;
        const bannerUrl = "{{ asset('images/banner-header.png') }}";
        
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
    
        const printWindow = window.open('', '', 'width=900,height=650');
        printWindow.document.write(`
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
                                <td class="header-value">${subject.split(' - ')[0]}</td>
                                <td class="header-label">Units:</td>
                                <td class="header-value">{{ $subjects->first()->units ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="header-label">Description:</td>
                                <td class="header-value">${subject.split(' - ')[1]}</td>
                                <td class="header-label">Semester:</td>
                                <td class="header-value">${semester}</td>
                            </tr>
                            <tr>
                                <td class="header-label">Course/Section:</td>
                                <td class="header-value">{{ $subjects->first()->course->course_code ?? 'N/A' }}</td>
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
                                        <td>${row.cells[1].textContent.trim()}</td>
                                        <td>${row.cells[2].textContent.trim()}</td>
                                        <td>${row.cells[3].textContent.trim()}</td>
                                        <td>${row.cells[4].textContent.trim()}</td>
                                        <td>${row.cells[5].textContent.trim()}</td>
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
        `);
        printWindow.document.close();
        
        // Wait for resources to load then print
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }
</script>
@endpush
