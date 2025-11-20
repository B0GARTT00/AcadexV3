@extends('layouts.app')

@section('content')
<style>
    .import-courses-wrapper {
        min-height: 100vh;
        background-color: #EAF8E7;
        padding: 0;
        margin: 0;
    }

    .import-courses-container {
        max-width: 100%;
        padding: 2rem 2rem;
    }

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

    .nav-tabs {
        background: #f8f9fa;
        border-radius: 0.75rem 0.75rem 0 0;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 0;
        padding: 0.5rem 1rem 0 1rem;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 500;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem 0.75rem 0 0;
        margin-right: 0.25rem;
        background: transparent;
        transition: all 0.2s;
    }
    .nav-tabs .nav-link:hover {
        color: #4da674;
        border-bottom-color: #4da674;
        background: #eaf8e7;
    }
    .nav-tabs .nav-link.active {
        color: #4da674;
        background: #fff;
        border-bottom-color: #4da674;
        box-shadow: 0 -2px 8px rgba(77,166,116,0.06);
    }

    .tab-content .tab-pane .table-responsive {
        border-radius: 0 0 0.75rem 0.75rem !important;
        border-top: none;
    }
</style>

<div class="import-courses-wrapper">
    <div class="import-courses-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>
                <i class="bi bi-people-fill"></i>
                Students List
            </h1>
            <p class="page-subtitle">View all students under your department and filter by year level</p>
        </div>

    @if($students->isEmpty())
        <div class="bg-warning bg-opacity-25 text-warning border border-warning px-4 py-3 rounded-4 shadow-sm">
            No students found under your department and course.
        </div>
    @else
        {{-- Year Level Tabs --}}
        <ul class="nav nav-tabs" id="yearTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="all-years-tab" data-bs-toggle="tab" href="#all-years" role="tab" aria-controls="all-years" aria-selected="true">All Years</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="first-year-tab" data-bs-toggle="tab" href="#first-year" role="tab" aria-controls="first-year" aria-selected="false">1st Year</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="second-year-tab" data-bs-toggle="tab" href="#second-year" role="tab" aria-controls="second-year" aria-selected="false">2nd Year</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="third-year-tab" data-bs-toggle="tab" href="#third-year" role="tab" aria-controls="third-year" aria-selected="false">3rd Year</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="fourth-year-tab" data-bs-toggle="tab" href="#fourth-year" role="tab" aria-controls="fourth-year" aria-selected="false">4th Year</a>
            </li>
        </ul>

        <div class="tab-content" id="yearTabsContent">
            <div class="tab-pane fade show active" id="all-years" role="tabpanel" aria-labelledby="all-years-tab">
                <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th class="text-center">Year Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr class="hover:bg-light">
                                    <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                    <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            {{ $student->formatted_year_level }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="first-year" role="tabpanel" aria-labelledby="first-year-tab">
                <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th class="text-center">Year Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students->where('year_level', 1) as $student)
                                <tr class="hover:bg-light">
                                    <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                    <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            {{ $student->formatted_year_level }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="second-year" role="tabpanel" aria-labelledby="second-year-tab">
                <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th class="text-center">Year Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students->where('year_level', 2) as $student)
                                <tr class="hover:bg-light">
                                    <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                    <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            {{ $student->formatted_year_level }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="third-year" role="tabpanel" aria-labelledby="third-year-tab">
                <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th class="text-center">Year Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students->where('year_level', 3) as $student)
                                <tr class="hover:bg-light">
                                    <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                    <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            {{ $student->formatted_year_level }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="fourth-year" role="tabpanel" aria-labelledby="fourth-year-tab">
                <div class="table-responsive bg-white shadow-sm rounded-4 p-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th class="text-center">Year Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students->where('year_level', 4) as $student)
                                <tr class="hover:bg-light">
                                    <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                    <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            {{ $student->formatted_year_level }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>


    </div>
</div>
@endsection
