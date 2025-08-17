@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="bi bi-building me-2"></i>
            Departments Overview
        </h1>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row g-4">
        @foreach($departments as $department)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-building me-2"></i>
                            {{ $department->department_code }}
                        </h5>
                        <p class="text-muted mb-3">
                            {{ $department->department_description ?? 'No description available' }}
                        </p>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Instructors:</span>
                                <span class="fw-bold">{{ $department->instructor_count ?? 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Students:</span>
                                <span class="fw-bold">{{ $department->student_count ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('vpaa.instructors', $department->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-people me-1"></i> View Instructors
                            </a>
                            <a href="{{ route('vpaa.students', ['department_id' => $department->id]) }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-mortarboard me-1"></i> View Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
