@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Course Outcome Attainment Results</li>
        </ol>
    </nav>

    {{-- Subject Wild Cards --}}
    @if(isset($subjects) && count($subjects))
        <div class="row g-4 px-4 py-4" id="subject-selection">
            @foreach($subjects as $subjectItem)
                <div class="col-md-4">
                    <a href="{{ route('instructor.course-outcome-attainments.subject', ['subject' => $subjectItem->id]) }}" style="text-decoration:none;">
                        <div class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl" style="cursor: pointer;">
                            <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                                <div class="subject-circle position-absolute start-50 translate-middle"
                                    style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                    <h5 class="mb-0 text-white fw-bold">{{ $subjectItem->subject_code }}</h5>
                                </div>
                            </div>
                            <div class="card-body pt-5 text-center">
                                <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $subjectItem->subject_description }}">
                                    {{ $subjectItem->subject_description }}
                                </h6>
                                {{-- Debug: Show academic year and semester for each subject --}}
                                <div class="mt-2 small text-muted">
                                    <span>Year: {{ $subjectItem->academic_year ?? 'N/A' }}</span> |
                                    <span>Semester: {{ $subjectItem->semester ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning bg-warning-subtle text-dark border-0 text-center">
            No subjects found for the selected academic year.
        </div>
    @endif
</div>
@endsection
