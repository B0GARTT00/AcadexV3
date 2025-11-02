@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-person-lines-fill text-success me-2"></i>Student CO Report
            </h2>
                <p class="text-dark fw-bold fs-4 mb-0">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('dean.reports.co-student') }}" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i>Choose Student
            </a>
        </div>
    </div>

    {{-- Subject Info Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-2 bg-primary-subtle me-3">
                            <i class="bi bi-journal-text text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Subject</h6>
                            <h5 class="fw-semibold mb-0">{{ $selectedSubject->subject_code }}</h5>
                            <small class="text-muted">{{ $selectedSubject->subject_name }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-2 bg-info-subtle me-3">
                            <i class="bi bi-mortarboard text-info fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Course</h6>
                            <h5 class="fw-semibold mb-0">{{ $selectedSubject->course->course_code ?? 'N/A' }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-2 bg-success-subtle me-3">
                            <i class="bi bi-calendar3 text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Period</h6>
                            <h5 class="fw-semibold mb-0">{{ $selectedSubject->academicPeriod->semester ?? 'N/A' }}</h5>
                            <small class="text-muted">{{ $selectedSubject->academicPeriod->academic_year ?? '' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CO Results Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Term</th>
                            @foreach($coColumnsByTerm as $coCode)
                                <th class="text-center">{{ $coCode }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($terms as $term)
                            <tr class="hover:bg-light">
                                <td class="fw-semibold">{{ ucfirst($term) }}</td>
                                @foreach($coColumnsByTerm as $coCode)
                                    @php($val = $coResults[$term][$coCode] ?? null)
                                    <td class="text-center">
                                        @if($val)
                                            <span class="badge {{ $val['percent'] >= 75 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} px-3 py-2 rounded-pill">
                                                {{ number_format($val['percent'], 2) }}%
                                            </span>
                                            <div><small class="text-muted">{{ $val['raw'] }}/{{ $val['max'] }}</small></div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Overall Row --}}
                        <tr class="table-success fw-bold">
                            <td>Overall</td>
                            @foreach($coColumnsByTerm as $coCode)
                                @php($val = $finalCOs[$coCode] ?? null)
                                <td class="text-center">
                                    @if($val)
                                        <span class="badge {{ $val['percent'] >= 75 ? 'bg-success' : 'bg-danger' }} px-3 py-2 rounded-pill text-white">
                                            {{ number_format($val['percent'], 2) }}%
                                        </span>
                                        <div><small class="text-muted">{{ $val['raw'] }}/{{ $val['max'] }}</small></div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill me-2">75%+</span>
                    <small class="text-muted">Passing</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill me-2">&lt;75%</span>
                    <small class="text-muted">Below Standard</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-2">—</span>
                    <small class="text-muted">No Data</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
