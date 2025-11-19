@extends('layouts.app')

@section('content')
<style>
    /* Container wrapper for consistent styling */
    .page-wrapper {
        background-color: #EAF8E7;
        min-height: 100vh;
        padding: 0;
        margin: 0;
    }

    .page-container {
        max-width: 100%;
        margin: 0;
        padding: 1.5rem 1rem;
    }

    /* Page title styling */
    .page-title {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid rgba(77, 166, 116, 0.2);
    }

    .page-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
        display: flex;
        align-items: center;
    }

    .page-title h1 i {
        color: #198754;
        font-size: 2rem;
        margin-right: 0.75rem;
    }

    .page-subtitle {
        color: #6c757d;
        font-size: 0.875rem;
        margin: 0;
    }

    /* Content wrapper */
    .content-wrapper {
        background-color: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-top: 1.5rem;
    }

    /* Filter section */
    .filter-section {
        background-color: #f8f9fa;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }

    /* Table improvements */
    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem;
    }

    .table tbody tr {
        transition: background-color 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .table tbody td {
        padding: 0.875rem 1rem;
        vertical-align: middle;
    }

    /* Breadcrumb styling */
    .breadcrumb {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 0.5rem;
        padding: 1rem 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .breadcrumb-item a {
        color: #4da674;
        text-decoration: none;
        transition: all 0.2s;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }

    .breadcrumb-item a:hover {
        color: #3d8a5e;
        background-color: rgba(77, 166, 116, 0.1);
    }

    .breadcrumb-item.active {
        color: #6c757d;
        font-weight: 600;
    }

    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: #6c757d;
        font-weight: bold;
        font-size: 1.2rem;
    }
</style>

<div class="page-wrapper">
    <div class="page-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>
                <i class="bi bi-people-fill"></i>
                View Students
            </h1>
            <p class="page-subtitle">View all students enrolled in GE subjects</p>
        </div>

        <div class="content-wrapper">

    @if(empty($selectedSubjectId))
        {{-- Subject Selection Cards --}}
        @if($subjects->isEmpty())
            <div class="alert alert-warning shadow-sm">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No GE courses found with assigned instructors.
            </div>
        @else
            <div class="row g-4 px-4 py-4">
                @foreach($subjects as $subject)
                    <div class="col-md-4">
                        <div
                            class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                            style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                            onclick="window.location.href='{{ route('gecoordinator.studentsByYear', ['subject_id' => $subject->id]) }}'"
                        >
                            {{-- Top header --}}
                            <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                                <div class="subject-circle position-absolute start-50 translate-middle"
                                    style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                    <h5 class="mb-0 text-white fw-bold">{{ $subject->subject_code }}</h5>
                                </div>
                            </div>

                            {{-- Card body --}}
                            <div class="card-body pt-5 text-center">
                                <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $subject->subject_description }}">
                                    {{ $subject->subject_description }}
                                </h6>
                                <div class="mt-2">
                                    @foreach($subject->instructors as $instructor)
                                        <span class="badge bg-success text-white small">{{ $instructor->last_name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- Breadcrumb Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('gecoordinator.studentsByYear') }}">Select Course</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Students List</li>
            </ol>
        </nav>

    @if($students->isEmpty())
        <div class="alert alert-info shadow-sm text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block" style="font-size: 4rem;"></i>
            <h5 class="text-muted mb-2">No Students Enrolled</h5>
            <p class="text-muted mb-0">There are currently no students enrolled in this course.</p>
        </div>
    @else
        <div class="filter-section">
            <div class="d-flex align-items-center gap-3">
                <label for="yearFilter" class="form-label mb-0 fw-semibold">
                    <i class="bi bi-funnel me-1"></i>
                    Filter by Year Level:
                </label>
                <select id="yearFilter" class="form-select shadow-sm" style="width: 200px;">
                    <option value="">All Years</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
        </div>

        <div class="shadow-sm rounded-4 overflow-hidden">
            <table class="table table-hover align-middle mb-0" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th class="text-center">Year Level</th>
                        <th>GE Subject(s)</th>
                        <th>Instructor(s)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr data-year="{{ $student->year_level }}">
                            <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                            <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge bg-success fw-semibold px-3 py-2 rounded-pill">
                                    {{ $student->formatted_year_level }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $geSubjects = $student->subjects->filter(function($subject) {
                                        return str_starts_with($subject->subject_code, 'GE') || 
                                               str_starts_with($subject->subject_code, 'PE') || 
                                               str_starts_with($subject->subject_code, 'RS') || 
                                               str_starts_with($subject->subject_code, 'NSTP');
                                    });
                                @endphp
                                @if($geSubjects->count() > 0)
                                    @foreach($geSubjects as $subject)
                                        <div class="mb-1">
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ $subject->subject_code }} - {{ $subject->subject_description }}
                                            </span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted">No GE subjects</span>
                                @endif
                            </td>
                            <td>
                                @if($geSubjects->count() > 0)
                                    @foreach($geSubjects as $subject)
                                        @if($subject->instructors && $subject->instructors->count() > 0)
                                            @foreach($subject->instructors as $instructor)
                                                <div class="mb-1">
                                                    <span class="badge bg-success-subtle text-success">
                                                        {{ $instructor->first_name }} {{ $instructor->last_name }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="mb-1">
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    No instructor assigned
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="text-muted">No instructors</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearFilter = document.getElementById('yearFilter');
    const table = document.getElementById('studentsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    yearFilter.addEventListener('change', function() {
        const selectedYear = this.value;
        
        for (let row of rows) {
            if (!selectedYear || row.getAttribute('data-year') === selectedYear) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});
</script>
@endpush
@endsection
