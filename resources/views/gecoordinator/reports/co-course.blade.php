@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-book text-success me-2"></i>{{ $course->course_code }} – Course CO Summary (GE Subjects)
            </h2>
            <p class="text-muted mb-0">{{ $course->course_description }} - GE Subjects Only</p>
        </div>
        <div>
            @if($academicYear && $semester)
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill me-2">
                    <i class="bi bi-calendar3 me-1"></i>{{ $academicYear }} – {{ $semester }}
                </span>
            @endif
            <a href="{{ route('gecoordinator.reports.co-course') }}" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i>Choose Course
            </a>
        </div>
    </div>

    {{-- CO Results Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            @if(empty($subjectCOs))
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted fs-1 d-block mb-3"></i>
                    <p class="text-muted mb-0">No GE subjects found for this course in the selected academic period.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="table-light border-bottom border-2">
                                <th style="width: 35%;" class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-2 p-2 bg-primary-subtle me-2">
                                            <i class="bi bi-book-half text-primary"></i>
                                        </div>
                                        <span class="fw-bold">Subject</span>
                                    </div>
                                </th>
                                @for($i=1; $i<=6; $i++)
                                    <th class="text-center fw-bold">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-mortarboard-fill text-success mb-1"></i>
                                            <span>CO{{ $i }}</span>
                                        </div>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjectCOs as $item)
                                @php($subject = $item['subject'])
                                @php($co = $item['co'])
                                <tr class="border-bottom">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center py-2">
                                            <div class="rounded-2 p-2 bg-light me-3">
                                                <i class="bi bi-journal-code text-primary fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $subject->subject_code }}</div>
                                                <small class="text-muted">{{ $subject->subject_description }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    @for($i=1; $i<=6; $i++)
                                        @php($val = $co[$i] ?? null)
                                        <td class="text-center py-3">
                                            @if($val)
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="badge {{ $val['percent'] >= 75 ? 'bg-success text-white' : 'bg-danger text-white' }} px-3 py-2 rounded-pill mb-1 fs-6">
                                                        {{ number_format($val['percent'], 1) }}%
                                                    </span>
                                                    <small class="text-muted">{{ $val['raw'] }}/{{ $val['max'] }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted fs-5">—</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="mt-4 p-3 bg-light rounded-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-info-circle text-primary me-2"></i>Performance Legend
                            </h6>
                            <div class="d-flex gap-3">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success text-white px-2 py-1 me-2">75%+</span>
                                    <small class="text-muted">Meeting Standards</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-danger text-white px-2 py-1 me-2">&lt;75%</span>
                                    <small class="text-muted">Below Standards</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <small class="text-muted">
                                <i class="bi bi-calculator me-1"></i>
                                Showing {{ count($subjectCOs) }} subject{{ count($subjectCOs) != 1 ? 's' : '' }} with Course Outcome data
                            </small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
