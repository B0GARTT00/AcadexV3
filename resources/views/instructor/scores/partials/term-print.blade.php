<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} {{ $termLabel }} Term Sheet</title>
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
            word-wrap: break-word;
            word-break: break-word;
        }

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
            text-align: center;
            vertical-align: middle;
        }

        .print-table th {
            background-color: #1a5f38;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap;
            border: 1px solid #1a5f38;
            padding: 10px 8px;
            font-size: 10px;
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
            background-color: #f0f7f4;
            font-weight: 500;
        }

        .print-table td {
            font-size: 11px;
        }

        .activity-header {
            font-size: 9px;
            display: block;
            font-weight: 400;
            opacity: 0.9;
        }

        .score-text {
            font-weight: 600;
            color: #1a5f38;
        }

        .score-muted {
            color: #6c757d;
            font-size: 9px;
        }

        .term-grade-cell {
            font-weight: bold;
            color: #1a5f38;
        }

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
    <img src="{{ asset('images/banner-header.png') }}" alt="Banner Header" class="banner">
    
    <div class="header-content">
        <div class="report-title">Report of Grades - {{ $termLabel }} Term</div>
        
        <table class="header-table">
            <tr>
                <td class="header-label">Course Code:</td>
                <td class="header-value">{{ $subject->subject_code }}</td>
                <td class="header-label">Units:</td>
                <td class="header-value">{{ $subject->units ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="header-label">Description:</td>
                <td class="header-value">{{ $subject->subject_description }}</td>
                <td class="header-label">Semester:</td>
                <td class="header-value">{{ $semesterLabel ?? '—' }}</td>
            </tr>
            <tr>
                <td class="header-label">Course/Section:</td>
                <td class="header-value">{{ $subject->course->course_code ?? 'N/A' }}</td>
                <td class="header-label">School Year:</td>
                <td class="header-value">{{ $academicYear ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <table class="print-table">
    <table class="print-table">
        <thead>
            <tr>
                <th>STUDENT NAME</th>
                @foreach($activities as $activity)
                        <th>
                            {{ strtoupper($activity->type) }}
                            <div class="activity-header">Max: {{ $activity->number_of_items }}</div>
                        </th>
                    @endforeach
                <th>TERM GRADE</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['student']->last_name }}, {{ $row['student']->first_name }}</td>
                    @foreach($activities as $activity)
                        @php
                            $score = $row['scores'][$activity->id] ?? null;
                        @endphp
                        <td>
                            @if($score !== null)
                                <span class="score-text">{{ (int) round($score) }}</span>
                                <span class="score-muted"> / {{ $activity->number_of_items }}</span>
                            @else
                                <span class="score-muted">—</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="term-grade-cell">
                        {{ $row['term_grade'] !== null ? (int) round($row['term_grade']) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $activities->count() + 2 }}" style="text-align:center; font-style:italic;">
                        No student data available for this term.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This is a computer-generated document. No signature is required.
        <br>
        Printed via ACADEX - Academic Grade System
    </div>
</body>
</html>
