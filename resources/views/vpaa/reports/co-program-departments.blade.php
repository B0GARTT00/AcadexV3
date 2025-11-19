@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-diagram-3 text-primary me-2"></i>Program CO Summary
            </h2>
            <p class="text-muted mb-0">Select a department to view Course Outcome compliance</p>
        </div>
        <div>
            @if($academicYear && $semester)
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill me-2">
                    <i class="bi bi-calendar3 me-1"></i>{{ $academicYear }} – {{ $semester }}
                </span>
            @endif
        </div>
    </div>

    <div class="row g-4 px-4 py-2">
        @forelse($departments as $dept)
            <div class="col-md-4">
                <div class="dept-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden" style="cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="position-relative" style="height: 80px; background-color: #4ecd85;">
                        <div class="dept-circle position-absolute start-50 translate-middle"
                            style="top: 100%; transform: translate(-50%, -50%); width: 80px; height: 80px; background: linear-gradient(135deg, #4da674, #023336); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                            <h5 class="mb-0 text-white fw-bold">{{ $dept->department_code }}</h5>
                        </div>
                    </div>
                    <div class="card-body pt-5 text-center">
                        <h6 class="fw-semibold mt-4 text-dark text-truncate" title="{{ $dept->department_description }}">
                            {{ $dept->department_description }}
                        </h6>
                        <div class="mt-3">
                            <a class="btn btn-success" href="{{ route('vpaa.reports.co-program') }}?department_id={{ $dept->id }}">
                                <i class="bi bi-arrow-right-circle me-1"></i> View Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="bi bi-journal-x fs-1 opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No Departments Found</h5>
                        <p class="text-muted mb-0">Departments data is required to browse reports.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
.dept-card { 
    transition: transform 0.3s ease, box-shadow 0.3s ease; 
}
.dept-card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important; 
}
.dept-circle { 
    transition: all 0.3s ease; 
}
.dept-card:hover .dept-circle { 
    transform: translate(-50%, -50%) scale(1.1); 
    box-shadow: 0 6px 20px rgba(0,0,0,0.2); 
}
.dept-circle h5 {
  font-size: 1.1rem;
  white-space: normal;
  word-break: break-word;
  text-align: center;
  line-height: 1.1;
  max-width: 70px;
  margin: 0 auto;
}
.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1);
}
@media (max-width: 600px) {
  .dept-circle h5 { 
    font-size: 0.95rem; 
  }
}
</style>
@endpush
@endsection
