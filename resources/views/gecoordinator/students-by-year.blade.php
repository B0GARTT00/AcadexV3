@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold mb-6">
        <i class="bi bi-people-fill text-success me-2"></i>
        Students List
    </h1>

    @if($students->isEmpty())
        <div class="bg-warning bg-opacity-25 text-warning border border-warning px-4 py-3 rounded-4 shadow-sm">
            No students found under your department and course.
        </div>
    @else
        <div class="mb-4">
            <div class="d-flex align-items-center gap-3">
                <label for="yearFilter" class="form-label mb-0">Filter by Year Level:</label>
                <select id="yearFilter" class="form-select" style="width: auto;">
                    <option value="">All Years</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-4 overflow-x-auto">
            <table class="table table-bordered align-middle mb-0" id="studentsTable">
                <thead class="table-light">
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th class="text-center">Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr class="hover:bg-light" data-year="{{ $student->year_level }}">
                            <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                            <td>{{ $student->course->course_code ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                    {{ $student->formatted_year_level }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearFilter = document.getElementById('yearFilter');
    const table = document.getElementById('studentsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    yearFilter.addEventListener('change', function() {
        const selectedYear = this.value;
        
        for (let row of rows) {
            if (!selectedYear || row.getAttribute('data-year') === selectedYear) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});
</script>
@endpush
@endsection
