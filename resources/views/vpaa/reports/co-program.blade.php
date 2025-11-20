@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-diagram-3 text-success me-2"></i>Program Outcomes Summary
            </h2>
            <p class="text-muted mb-0">Course Outcome compliance across all courses in {{ $department->department_description ?? 'your department' }}</p>
        </div>
        <div>
            @if($academicYear && $semester)
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill me-2">
                    <i class="bi bi-calendar3 me-1"></i>{{ $academicYear }} – {{ $semester }}
                </span>
            @endif
            <a href="{{ route('vpaa.dashboard') }}" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if(!$department)
        <div class="alert alert-warning border-0 rounded-4 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>Your account has no department assigned. Please contact admin.
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start" style="width: 30%;">
                                <i class="bi bi-journal-text text-primary me-2"></i>Course
                            </th>
                            @for($i=1; $i<=6; $i++)
                                <th class="text-center">CO{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byCourse as $courseId => $row)
                            <tr class="hover:bg-light">
                                <td class="text-start">
                                    <div class="fw-semibold">{{ $row['course']->course_code ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $row['course']->course_description ?? '' }}</small>
                                </td>
                                @for($i=1; $i<=6; $i++)
                                    @php($val = $row['co'][$i] ?? null)
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
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted fs-1 d-block mb-2"></i>
                                    <p class="text-muted mb-0">No courses or assessed COs found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
