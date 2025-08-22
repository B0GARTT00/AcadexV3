@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course-outcome-results.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-semibold text-gray-800 mb-0">
                <i class="bi bi-graph-up me-2"></i>
                Course Outcome Attainment Results
            </h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('vpaa.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Course Outcome Attainment</li>
                </ol>
            </nav>
        </div>
        @if(isset($hasData) && $hasData)
        <div>
            <button class="btn btn-success" type="button" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Print Report
            </button>
        </div>
        @endif
    </div>

    {{-- Filter Controls Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form action="{{ route('vpaa.course-outcome-attainment') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="department_id" class="form-label fw-semibold">Department</label>
                    <select name="department_id" id="department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ $selectedDepartmentId == $department->id ? 'selected' : '' }}>
                                {{ $department->department_description }} ({{ $department->department_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="course_id" class="form-label fw-semibold">Course</label>
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
            </form>
        </div>
    </div>

    @if(!$hasData)
        <!-- No Data Message -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <div class="text-muted mb-3">
                    <i class="bi bi-graph-up fs-1 opacity-50"></i>
                </div>
                <h5 class="text-muted mb-2">No Course Outcome Data Found</h5>
                <p class="text-muted mb-0">
                    @if(!$selectedDepartmentId)
                        Please select a department to view course outcome attainment results.
                    @elseif(!$selectedCourseId)
                        Please select a course to view specific outcome data.
                    @else
                        No data available for the selected filters.
                    @endif
                </p>
            </div>
        </div>
    @else
        <!-- Results Container -->
        <div class="card border-0 shadow-sm rounded-4" id="print-area">
            <div class="card-body p-4">
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
                                <tr>
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
                                {{-- End student rows --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

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
        background: linear-gradient(90deg, #198754 0%, #16a34a 100%);
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        font-weight: 700;
        color: #fff !important;
        font-size: 1.25rem;
        letter-spacing: 0.5px;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-header-custom .bi {
        font-size: 1.5rem;
        color: #fff !important;
        margin-right: 0.5rem;
    }

    .results-card {
        /* Ensure logo and header are visible */
        position: relative;
    }

    .acadex-logo-header {
        height: 32px;
        margin-right: 0.75rem;
        vertical-align: middle;
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
        background: linear-gradient(135deg, #1bce8f 0%, #023336 100%) !important;
        color: #fff !important;
        border: none;
        font-weight: 700;
        text-shadow: none;
    }
        /* Remove global .badge-success override and target only table badges */
        .co-table .badge.bg-success {
            background: linear-gradient(135deg, #1bce8f 0%, #023336 100%) !important;
            color: #fff !important;
            font-weight: 700;
            border: none;
            letter-spacing: 0.5px;
        }
    
    .badge-warning {
        background-color: #f59e42 !important;
        color: #fff !important;
    }
    
    .badge-danger {
        background-color: #dc2626 !important;
        color: #fff !important;
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
