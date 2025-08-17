@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Course Outcomes</li>
            @if(request('subject_id') && isset($subjects))
                @php
                    $selectedSubject = $subjects->firstWhere('id', request('subject_id'));
                @endphp
                @if($selectedSubject)
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}
                    </li>
                @endif
            @endif
        </ol>
    </nav>

    {{-- Subject Wild Cards --}}
    @if(isset($subjects) && count($subjects))
        <div class="row g-4 px-4 py-4" id="subject-selection">
            @foreach($subjects as $subjectItem)
                <div class="col-md-4">
                    <div
                        class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                        data-url="{{ route('instructor.course_outcomes.index', ['subject_id' => $subjectItem->id]) }}"
                        style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                    >
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
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Course Outcome Button --}}
    <div class="mb-3 text-end">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseOutcomeModal">
            + Add Course Outcome
        </button>
    </div>

    {{-- Course Outcomes Table Section --}}
    <div class="mt-4">
        @if(request('subject_id'))
            @if($cos && $cos->count())
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>CO Code</th>
                                    <th>Identifier</th>
                                    <th>Description</th>
                                    <th>Academic Period</th>
                                    <th>Percentage</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cos as $co)
                                    <tr>
                                        <td class="fw-semibold">{{ $co->co_code }}</td>
                                        <td>{{ $co->co_identifier }}</td>
                                        <td>{{ $co->description }}</td>
                                        <td>
                                            @if($co->academicPeriod)
                                                {{ $co->academicPeriod->academic_year }} - {{ $co->academicPeriod->semester }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(!is_null($co->percent))
                                                {{ $co->percent }}%
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('instructor.course_outcomes.edit', $co->id) }}" class="btn btn-success btn-sm">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <form action="{{ route('instructor.course_outcomes.destroy', $co->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this course outcome?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="alert alert-warning bg-warning-subtle text-dark border-0 text-center">
                    No course outcomes found for this subject.
                </div>
            @endif
        @else
            <div class="alert alert-info bg-info-subtle text-dark border-0 text-center">
                Please select a subject to view its course outcomes.
            </div>
        @endif
    </div>
</div>

{{-- Add Course Outcome Modal --}}
<div class="modal fade" id="addCourseOutcomeModal" tabindex="-1" aria-labelledby="addCourseOutcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('instructor.course_outcomes.store') }}">
            @csrf
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="addCourseOutcomeModalLabel">➕ Add Course Outcome</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">CO Code <span class="text-danger">*</span></label>
                        <input type="text" name="co_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identifier <span class="text-danger">*</span></label>
                        <input type="text" name="co_identifier" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Period <span class="text-danger">*</span></label>
                        <select name="academic_period_id" class="form-select" required>
                            <option value="">-- Select Academic Period --</option>
                            @foreach($periods ?? [] as $period)
                                <option value="{{ $period->id }}">{{ $period->academic_year }} - {{ $period->semester }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percentage <span class="text-danger">*</span></label>
                        <input type="number" name="percent" class="form-control" min="0" max="100" step="0.01" required placeholder="Enter percentage">
                    </div>
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Outcome</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.subject-card[data-url]').forEach(card => {
        card.addEventListener('click', function() {
            window.location.href = this.dataset.url;
        });
    });
});
</script>
@endpush
