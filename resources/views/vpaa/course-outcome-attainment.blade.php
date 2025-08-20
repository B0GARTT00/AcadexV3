@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course-outcome-results.css') }}">
@endpush

@section('content')
<div class="header-section">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="header-title">📊 Course Outcome Attainment Results</h1>
            <p class="header-subtitle text-white">Comprehensive analysis of student performance across all courses and outcomes</p>
        </div>
        @if(isset($hasData) && $hasData)
        <div class="no-print">
            <button class="btn btn-success" type="button" onclick="window.print()">
                <i class="bi bi-printer me-2"></i> Print
            </button>
        </div>
        @endif
    </div>
</div>

{{-- Controls Panel --}}
<div class="controls-panel no-print">
    <form action="{{ route('vpaa.course-outcome-attainment') }}" method="GET" class="row g-3">
        <div class="col-md-4">
            <label for="department_id" class="form-label">Department</label>
            <select name="department_id" id="department_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ $selectedDepartmentId == $department->id ? 'selected' : '' }}>
                        {{ $department->department_description }} ({{ $department->department_code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="course_id" class="form-label">Course</label>
            <select name="course_id" id="course_id" class="form-select" {{ empty($courses) ? 'disabled' : '' }} onchange="this.form.submit()">
                <option value="">All Courses</option>
                @if($courses->isNotEmpty())
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ $selectedCourseId == $course->id ? 'selected' : '' }}>
                            {{ $course->course_code }} - {{ $course->course_description }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-funnel me-1"></i> Apply Filters
            </button>
        </div>
    </form>
</div>

<div class="main-results-container">
    @if(!$hasData)
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i> No course outcome attainment data found for the selected filters. 
            @if(!$selectedDepartmentId)
                Please try selecting a department to filter the results.
            @elseif(!$selectedCourseId)
                Please try selecting a course to filter the results.
            @endif
        </div>
    @else
        <div id="print-area">
            @foreach($attainmentData as $courseCode => $courseData)
                <div class="results-card mb-4">
                    <div class="card-header-custom">
                        <i class="bi bi-table me-2"></i>{{ $courseCode }} - Course Outcome Attainment
                    </div>
                    <div class="table-responsive p-3">
                        <table class="table co-table table-bordered align-middle mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th>Students</th>
                                    @foreach($courseData['outcomes'] as $outcome)
                                        <th class="text-center">{{ $outcome->co_code ?? 'N/A' }}</th>
                                    @endforeach
                                    <th class="text-center">Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($courseData['students'] as $studentId => $studentData)
                                    <tr>
                                        <td class="fw-medium">{{ $studentData['last_name'] }}, {{ $studentData['first_name'] }}</td>
                                        @foreach($courseData['outcomes'] as $outcome)
                                            @php
                                                $attainment = $studentData['outcomes'][$outcome->id] ?? null;
                                                $attainmentLevel = $attainment ? ($attainment->score / $attainment->max) * 100 : 0;
                                                $statusClass = $attainmentLevel >= 70 ? 'success' : ($attainmentLevel >= 50 ? 'warning' : 'danger');
                                            @endphp
                                            <td class="text-center align-middle">
                                                @if($attainment)
                                                    <span class="badge bg-{{ $statusClass }}" title="{{ number_format($attainmentLevel, 1) }}%">
                                                        {{ number_format($attainmentLevel, 0) }}%
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold">
                                            @php
                                                $totalAttainment = 0;
                                                $count = 0;
                                                foreach($courseData['outcomes'] as $outcome) {
                                                    if(isset($studentData['outcomes'][$outcome->id])) {
                                                        $attainment = $studentData['outcomes'][$outcome->id];
                                                        $totalAttainment += ($attainment->score / $attainment->max) * 100;
                                                        $count++;
                                                    }
                                                }
                                                $average = $count > 0 ? $totalAttainment / $count : 0;
                                                $statusClass = $average >= 70 ? 'success' : ($average >= 50 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ number_format($average, 0) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- Class Average Row --}}
                                <tr class="table-light">
                                    <td class="fw-bold">Class Average</td>
                                    @foreach($courseData['outcomes'] as $outcome)
                                        @php
                                            $total = 0;
                                            $count = 0;
                                            foreach($courseData['students'] as $studentData) {
                                                if(isset($studentData['outcomes'][$outcome->id])) {
                                                    $attainment = $studentData['outcomes'][$outcome->id];
                                                    $total += ($attainment->score / $attainment->max) * 100;
                                                    $count++;
                                                }
                                            }
                                            $average = $count > 0 ? $total / $count : 0;
                                            $statusClass = $average >= 70 ? 'success' : ($average >= 50 ? 'warning' : 'danger');
                                        @endphp
                                        <td class="text-center fw-bold">
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ number_format($average, 0) }}%
                                            </span>
                                        </td>
                                    @endforeach
                                    {{-- Overall Class Average --}}
                                    @php
                                        $totalAverage = 0;
                                        $outcomeCount = count($courseData['outcomes']);
                                        if($outcomeCount > 0) {
                                            foreach($courseData['outcomes'] as $outcome) {
                                                $total = 0;
                                                $count = 0;
                                                foreach($courseData['students'] as $studentData) {
                                                    if(isset($studentData['outcomes'][$outcome->id])) {
                                                        $attainment = $studentData['outcomes'][$outcome->id];
                                                        $total += ($attainment->score / $attainment->max) * 100;
                                                        $count++;
                                                    }
                                                }
                                                $totalAverage += $count > 0 ? $total / $count : 0;
                                            }
                                            $overallAverage = $outcomeCount > 0 ? $totalAverage / $outcomeCount : 0;
                                            $statusClass = $overallAverage >= 70 ? 'success' : ($overallAverage >= 50 ? 'warning' : 'danger');
                                        }
                                    @endphp
                                    <td class="text-center fw-bold">
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ number_format($overallAverage, 0) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Enable/disable course select based on department selection
    document.addEventListener('DOMContentLoaded', function() {
        const departmentSelect = document.getElementById('department_id');
        const courseSelect = document.getElementById('course_id');
        
        if (departmentSelect && courseSelect) {
            departmentSelect.addEventListener('change', function() {
                if (this.value) {
                    // Enable course select and fetch courses via AJAX
                    courseSelect.disabled = false;
                    fetch(`/api/courses?department_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            // Clear existing options except the first one
                            while (courseSelect.options.length > 1) {
                                courseSelect.remove(1);
                            }
                            
                            // Add new options
                            data.forEach(course => {
                                const option = document.createElement('option');
                                option.value = course.id;
                                option.textContent = `${course.course_code} - ${course.course_description}`;
                                courseSelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Error fetching courses:', error));
                } else {
                    // Disable and reset course select if no department is selected
                    courseSelect.disabled = true;
                    courseSelect.selectedIndex = 0;
                }
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
    .header-section {
        margin-bottom: 2rem;
    }
    
    .header-title {
        font-size: 1.8rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .header-subtitle {
        color: #7f8c8d;
        margin-bottom: 0;
    }
    
    .controls-panel {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        border: 1px solid #e9ecef;
    }
    
    .results-card {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }
    
    .card-header-custom {
        background-color: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        color: #2c3e50;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.4em 0.8em;
    }
    
    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .badge-warning {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .badge-danger {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    
    .badge-secondary {
        background-color: #e5e7eb;
        color: #374151;
    }
    
    @media print {
        .no-print {
            display: none !important;
        }
        
        .results-card {
            page-break-inside: avoid;
            box-shadow: none;
            border: 1px solid #dee2e6;
        }
        
        .table {
            font-size: 0.85rem;
        }
    }
</style>
@endpush
@endsection
