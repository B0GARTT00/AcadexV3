@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-dark fw-bold mb-0">📅 Academic Periods</h1>
        <button class="btn btn-success" onclick="showModal()">+ Generate New</button>
    </div>

    {{-- Periods Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th class="text-center">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $index => $period)
                        <tr>
                            <td>{{ $period->academic_year }}</td>
                            <td>{{ ucfirst($period->semester) }}</td>
                            <td class="text-center">{{ $period->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted fst-italic py-3">No academic periods found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to generate a new academic period based on the latest one?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('admin.academicPeriods.generate') }}">
                    @csrf
                    <button type="submit" class="btn btn-success">Yes, Generate</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
    function showModal() {
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'), {
            backdrop: false
        });
        modal.show();
    }
</script>
@endsection
