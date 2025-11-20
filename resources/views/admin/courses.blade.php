@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-dark fw-bold mb-0">🎓 Programs</h1>
            <p class="text-muted mb-0">Manage academic programs and courses</p>
        </div>
        <button class="btn btn-success" onclick="showModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Program
        </button>
    </div>

    {{-- Courses Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="coursesTable" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Department</th>
                            <th class="text-center">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courses as $course)
                            <tr>
                                <td class="fw-semibold">{{ $course->course_code }}</td>
                                <td>{{ $course->course_description }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $course->department->department_code ?? 'N/A' }}
                                    </span>
                                    <small class="text-muted d-block">{{ $course->department->department_description ?? '' }}</small>
                                </td>
                                <td class="text-center">{{ $course->created_at->format('Y-m-d') }}</td>
                                {{-- Actions removed as requested --}}
                            </tr>
                        @empty
                            {{-- DataTables will handle empty state --}}
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Course Modal --}}
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="courseModalLabel">Add New Program</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.storeCourse') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Code</label>
                        <input type="text" name="course_code" class="form-control" placeholder="e.g. BSIT" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Description</label>
                        <input type="text" name="course_description" class="form-control" placeholder="e.g. Bachelor of Science in Information Technology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->department_description }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#coursesTable').DataTable({
            order: [[2, 'asc'], [0, 'asc']], // Sort by Department then Code
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search programs...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ programs",
                emptyTable: "No programs found"
            },
            // Keep default ordering behavior. No explicit columnDefs needed after removing Actions column.
        });
    });

    function showModal() {
        const modal = new bootstrap.Modal(document.getElementById('courseModal'), {
            backdrop: false
        });
        modal.show();
    }
</script>
@endpush
@endsection
