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

    /* Breadcrumb styling */
    .breadcrumb {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 0.5rem;
        padding: 1rem 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
    }

    .breadcrumb-item {
        font-weight: 500;
        font-size: 0.95rem;
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
        padding: 0 0.5rem;
    }
</style>

<div class="page-wrapper">
    <div class="page-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>
                <i class="bi bi-clipboard-data"></i>
                View Grades
            </h1>
            <p class="page-subtitle">View and monitor students' final grades by instructor and subject</p>
        </div>

        <div class="content-wrapper">

    {{-- Breadcrumb Navigation --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item">
                <a href="{{ route('gecoordinator.viewGrades') }}" class="{{ empty($selectedInstructorId) && empty($selectedSubjectId) ? 'active' : '' }}">Select Instructor</a>
            </li>
            @if (!empty($selectedInstructorId) && empty($selectedSubjectId))
                <li class="breadcrumb-item active" aria-current="page">Select Subject</li>
            @elseif (!empty($selectedInstructorId) && !empty($selectedSubjectId))
                <li class="breadcrumb-item">
                    <a href="{{ route('gecoordinator.viewGrades', ['instructor_id' => $selectedInstructorId]) }}">Select Subject</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Students' Grades</li>
            @endif
        </ol>
    </nav>

    {{-- Step 1: Instructor Selection --}}
    @if (empty($selectedInstructorId) && empty($selectedSubjectId))
        @if($instructors->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block" style="font-size: 4rem;"></i>
                <h5 class="text-muted mb-2">No Data Available</h5>
                <p class="text-muted">There are currently no instructors with assigned subjects.</p>
            </div>
        @else
        <div class="row g-4 px-4 py-4">
            @foreach($instructors as $instructor)
                <div class="col-md-4">
                    <div
                        class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl hover:border-primary"
                        data-url="{{ route('gecoordinator.viewGrades', ['instructor_id' => $instructor->id]) }}"
                        style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                        onclick="window.location.href='{{ route('gecoordinator.viewGrades', ['instructor_id' => $instructor->id]) }}'"
                    >
                        {{-- Top header --}}
                        <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                            <div class="subject-circle position-absolute start-50 translate-middle"
                                style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 10%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                {{-- Person Icon for Instructor (Square Design) --}}
                                <i class="bi bi-person-circle text-white" style="font-size: 40px;"></i>
                            </div>
                        </div>

                        {{-- Card body --}}
                        <div class="card-body pt-5 text-center">
                            <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $instructor->last_name }}, {{ $instructor->first_name }}">
                                {{ $instructor->last_name }}, {{ $instructor->first_name }}
                            </h6>
                            {{-- Badge for role --}}
                            <div class="mt-2">
                                <span class="badge bg-primary text-white">Instructor</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    @elseif (empty($selectedSubjectId))
        {{-- Step 2: Subject Selection --}}
        @if (!empty($subjects))
            <div class="row g-4 px-4 py-4" id="subject-selection">
                @foreach($subjects as $subjectItem)
                    <div class="col-md-4">
                        <div
                            class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                            data-url="{{ route('gecoordinator.viewGrades', ['instructor_id' => $selectedInstructorId, 'subject_id' => $subjectItem->id]) }}"
                            style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                            onclick="window.location.href='{{ route('gecoordinator.viewGrades', ['instructor_id' => $selectedInstructorId, 'subject_id' => $subjectItem->id]) }}'"
                        >
                            {{-- Top header --}}
                            <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                                <div class="subject-circle position-absolute start-50 translate-middle"
                                    style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
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
            <div class="text-center text-muted mt-8 bg-warning bg-opacity-25 border border-warning px-6 py-4 rounded-4">
                No subjects found for this instructor.
            </div>
        @endif
    @else
        {{-- Step 3: Display Students' Grades --}}
        {{-- Students Table --}}
        @if (!empty($students) && count($students))
            <div class="bg-white shadow-lg rounded-4 overflow-x-auto">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Student Name</th>
                            <th class="text-center">Prelim</th>
                            <th class="text-center">Midterm</th>
                            <th class="text-center">Prefinal</th>
                            <th class="text-center">Final</th>
                            <th class="text-center text-success">Final Average</th>
                            <th class="text-center">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            @php
                                $termGrades = $student->termGrades->keyBy('term_id');

                                $prelim = $termGrades[1]->term_grade ?? null;
                                $midterm = $termGrades[2]->term_grade ?? null;
                                $prefinal = $termGrades[3]->term_grade ?? null;
                                $final = $termGrades[4]->term_grade ?? null;

                                $hasAll = !is_null($prelim) && !is_null($midterm) && !is_null($prefinal) && !is_null($final);
                                $average = $hasAll ? round(($prelim + $midterm + $prefinal + $final) / 4) : null;

                                $remarks = $average !== null ? ($average >= 75 ? 'Passed' : 'Failed') : null;
                            @endphp
                            <tr class="hover:bg-light">
                                <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                <td class="text-center">{{ $prelim !== null ? round($prelim) : '-' }}</td>
                                <td class="text-center">{{ $midterm !== null ? round($midterm) : '-' }}</td>
                                <td class="text-center">{{ $prefinal !== null ? round($prefinal) : '-' }}</td>
                                <td class="text-center">{{ $final !== null ? round($final) : '-' }}</td>
                                <td class="text-center fw-semibold text-success">
                                    {{ $average !== null ? $average : '-' }}
                                </td>
                                <td class="text-center">
                                    @if($remarks === 'Passed')
                                        <span class="badge bg-success-subtle text-success fw-medium px-3 py-2 rounded-pill">Passed</span>
                                    @elseif($remarks === 'Failed')
                                        <span class="badge bg-danger-subtle text-danger fw-medium px-3 py-2 rounded-pill">Failed</span>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(!empty($selectedSubjectId))
            <div class="text-center text-muted mt-8 bg-warning bg-opacity-25 border border-warning px-6 py-4 rounded-4">
                No students found for this subject.
            </div>
        @endif
    @endif
        </div>
    </div>
</div>
@endsection
