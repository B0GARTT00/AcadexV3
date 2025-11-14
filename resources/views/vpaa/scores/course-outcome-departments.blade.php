@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('vpaa.dashboard') }}" class="text-success">Home</a></li>
            <li class="breadcrumb-item active text-success" aria-current="page">Course Outcome Attainment</li>
        </ol>
    </nav>

    @if(isset($departments) && count($departments))
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                <div class="text-muted" aria-live="polite">
                    Select a department to view its subjects
                    @if($academicYear && $semester)
                        <span class="ms-2 small">(Academic Year: {{ $academicYear }}, Semester: {{ $semester }})</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4 px-4 py-2" id="department-selection">
            @foreach($departments as $dept)
                <div class="col-md-4">
                    <div
                        class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                        data-url="{{ route('vpaa.course-outcome-attainment') }}?department_id={{ $dept->id }}"
                        style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                    >
                        <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                            <div class="subject-circle position-absolute start-50 translate-middle"
                                style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                <h5 class="mb-0 text-white fw-bold">{{ $dept->department_code }}</h5>
                            </div>
                        </div>
                        <div class="card-body pt-5 text-center">
                            <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $dept->department_description }}">
                                {{ $dept->department_description }}
                            </h6>
                            <div class="mt-3">
                                <a class="btn btn-success" href="{{ route('vpaa.course-outcome-attainment') }}?department_id={{ $dept->id }}">
                                    <i class="bi bi-arrow-right-circle me-1"></i> View Subjects
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <div class="text-muted mb-3">
                    <i class="bi bi-journal-x fs-1 opacity-50"></i>
                </div>
                <h5 class="text-muted mb-2">No Departments Found</h5>
                <p class="text-muted mb-0">Departments data is required to browse subjects for course outcome attainment.</p>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
 document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#department-selection .subject-card[data-url]').forEach(card => {
        card.addEventListener('click', () => {
            window.location.href = card.dataset.url;
        });
    });
 });
</script>
@endpush

@push('styles')
<style>
.subject-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.subject-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important; }
.subject-circle { transition: all 0.3s ease; }
.subject-card:hover .subject-circle { transform: translate(-50%, -50%) scale(1.1); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
.subject-circle h5 {
  font-size: 1.1rem;
  white-space: normal;
  word-break: break-word;
  text-align: center;
  line-height: 1.1;
  max-width: 70px;
  margin: 0 auto;
}
@media (max-width: 600px) {
  .subject-circle h5 { font-size: 0.95rem; }
}
</style>
@endpush
