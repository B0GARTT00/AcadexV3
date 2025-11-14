@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-dark fw-bold mb-0">🏢 Departments</h1>
        <button class="btn btn-success" onclick="showModal()">+ Add Department</button>
    </div>

    {{-- Departments Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-success">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th class="text-center">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->id }}</td>
                            <td>{{ $department->department_description }}</td>
                            <td class="text-center">{{ $department->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted fst-italic py-3">No departments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Department Modal --}}
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="departmentModalLabel">Add New Department</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.storeDepartment') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Code</label>
                        <input type="text" name="department_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Description</label>
                        <input type="text" name="department_description" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
    function showModal() {
        const modal = new bootstrap.Modal(document.getElementById('departmentModal'), {
            backdrop: false
        });
        modal.show();
    }
</script>
@endsection
