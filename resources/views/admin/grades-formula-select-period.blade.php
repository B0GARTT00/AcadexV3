@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xxl-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #198754, #20c997);">
                            <i class="bi bi-calendar2-week text-white" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1" style="color: #198754;">Select Academic Period</h3>
                            <p class="text-muted mb-0">Choose which academic period you want to manage formulas for. You can switch periods at any time.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.gradesFormula') }}" id="grades-formula-period-form" class="d-grid gap-4">
                        <div>
                            <label for="academic-period-select" class="form-label fw-semibold text-success">Academic Period</label>
                            <select class="form-select form-select-lg" id="academic-period-select" required>
                                <option value="">-- Select Academic Period --</option>
                                <option value="all">All Academic Periods</option>
                                @foreach ($academicPeriods as $period)
                                    <option
                                        value="{{ $period->id }}"
                                        data-year="{{ $period->academic_year }}"
                                        data-semester="{{ $period->semester }}"
                                    >
                                        {{ $period->academic_year }} - {{ $period->semester }} Semester
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="academic_period_id" id="selected-academic-period">
                            <input type="hidden" name="academic_year" id="selected-academic-year">
                            <input type="hidden" name="semester" id="selected-semester">
                            <small class="text-muted d-block mt-2">Only periods with active data appear here. Contact the registrar if the period you need is missing.</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('admin.departments') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                            <button type="submit" class="btn btn-success btn-lg px-4" id="continue-button" disabled>
                                Continue
                                <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('academic-period-select');
        const periodInput = document.getElementById('selected-academic-period');
        const yearInput = document.getElementById('selected-academic-year');
        const semesterInput = document.getElementById('selected-semester');
        const continueButton = document.getElementById('continue-button');

        const syncSelection = () => {
            const option = select.options[select.selectedIndex];
            const hasSelection = option && option.value !== '';

            continueButton.disabled = ! hasSelection;

            if (! hasSelection) {
                periodInput.value = '';
                yearInput.value = '';
                semesterInput.value = '';
                return;
            }

            if (option.value === 'all') {
                periodInput.value = 'all';
                yearInput.value = '';
                semesterInput.value = '';
            } else {
                periodInput.value = option.value;
                yearInput.value = option.dataset.year ?? '';
                semesterInput.value = option.dataset.semester ?? '';
            }
        };

        select.addEventListener('change', syncSelection);
        syncSelection();
    });
</script>
@endpush
