@extends('layouts.app')

@section('content')
<style>
    .import-courses-wrapper {
        min-height: 100vh;
        background-color: #EAF8E7;
        padding: 0;
        margin: 0;
    }

    .import-courses-container {
        max-width: 100%;
        padding: 2rem 2rem;
    }

    .page-title {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid rgba(77, 166, 116, 0.2);
    }

    .page-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
        display: flex;
        align-items: center;
    }

    .page-title h1 i {
        color: #198754;
        font-size: 2rem;
        margin-right: 0.75rem;
    }

    .page-subtitle {
        color: #6c757d;
        font-size: 0.875rem;
        margin: 0;
    }

    .content-wrapper {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
    }

    .curriculum-select-section {
        background: #f8f9fa;
        padding: 1.25rem;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-select {
        border: 1px solid #ced4da;
        border-radius: 0.5rem;
        padding: 0.625rem 0.875rem;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-select:focus {
        border-color: #4da674;
        box-shadow: 0 0 0 0.2rem rgba(77, 166, 116, 0.15);
    }

    .btn-load {
        background: #4da674;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-load:hover {
        background: #3d8a5e;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(77, 166, 116, 0.2);
    }

    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 500;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        transition: all 0.2s;
    }

    .nav-tabs .nav-link:hover {
        color: #4da674;
        border-bottom-color: #4da674;
    }

    .nav-tabs .nav-link.active {
        color: #4da674;
        background: transparent;
        border-bottom-color: #4da674;
    }

    .table-container {
        overflow-x: auto;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead {
        background: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #4da674;
    }

    .table tbody td {
        padding: 0.875rem 1rem;
        vertical-align: middle;
        color: #495057;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid #ced4da;
        cursor: pointer;
        margin-top: 0;
    }

    .form-check-input:checked {
        background-color: #4da674;
        border-color: #4da674;
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(77, 166, 116, 0.25);
    }

    .btn-select-all {
        background: white;
        border: 2px solid #4da674;
        color: #4da674;
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-select-all:hover,
    .btn-select-all.active {
        background: #4da674;
        color: white;
    }

    .btn-confirm {
        background: #4da674;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 500;
        font-size: 1rem;
        transition: all 0.2s;
    }

    .btn-confirm:hover {
        background: #3d8a5e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(77, 166, 116, 0.3);
    }

    .alert-custom {
        border-left: 4px solid #4da674;
        background: #f0f9f4;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    .semester-heading {
        color: #4da674;
        font-weight: 600;
        font-size: 1.125rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15rem;
    }

    .action-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }
</style>

<div class="import-courses-wrapper">
    <div class="import-courses-container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>
                <i class="bi bi-file-earmark-arrow-up"></i>
                Import Courses
            </h1>
            <p class="page-subtitle">Select a curriculum and choose courses to import into the system</p>
        </div>

        @if(Auth::user()->role === 1)
            <div class="alert alert-custom" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> As a Chairperson, you cannot import GE (General Education), PD (Professional Development), PE (Physical Education), RS (Religious Studies), and NSTP (National Service Training Program) subjects. These subjects are managed by the GE Coordinator.
            </div>
        @endif

        <!-- Main Content -->
        <div class="content-wrapper">
            <!-- Curriculum Selection -->
            <div class="curriculum-select-section">
                <label for="curriculumSelect" class="form-label">
                    <i class="bi bi-mortarboard me-2"></i>Select Curriculum
                </label>
                <div class="d-flex gap-3 align-items-end">
                    <div class="flex-grow-1">
                        <select id="curriculumSelect" class="form-select">
                            <option value="">-- Choose a Curriculum --</option>
                            @foreach($curriculums as $curriculum)
                                <option value="{{ $curriculum->id }}">
                                    {{ $curriculum->name }} ({{ $curriculum->course->course_description }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button id="loadSubjectsBtn" class="btn btn-success btn-load d-none">
                        <span id="loadBtnText">
                            <i class="bi bi-arrow-repeat me-2"></i>Load Courses
                        </span>
                        <span id="loadBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>

            <!-- Subject Selection Form -->
            <form method="POST" action="{{ route('curriculum.confirmSubjects') }}" id="confirmForm">
                @csrf
                <input type="hidden" name="curriculum_id" id="formCurriculumId">

                <div class="d-none" id="subjectsContainer">
                    <!-- Tabs and Select All Button -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <ul class="nav nav-tabs flex-grow-1" id="yearTabs" style="margin-bottom: 0;"></ul>
                        <button type="button" class="btn btn-select-all" id="selectAllBtn" data-selected="false">
                            <i class="bi bi-check2-square me-1"></i> Select All
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content" id="subjectsTableBody"></div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <div class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="selectedCount">0</span> course(s) selected
                        </div>
                        <button type="button" class="btn btn-success btn-confirm" data-bs-toggle="modal" data-bs-target="#confirmModal">
                            <i class="bi bi-check-circle me-2"></i>Confirm Selected Courses
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Submission</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to confirm and save the selected subjects for this curriculum?
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitConfirmBtn" class="btn btn-success">Yes, Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $activePeriod = \App\Models\AcademicPeriod::find(session('active_academic_period_id'));
    $userRole = Auth::user()->role;
@endphp

<script>
    const currentSemester = @json($activePeriod?->semester ?? '');
    const userRole = @json($userRole);
</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const curriculumSelect = document.getElementById('curriculumSelect');
    const loadSubjectsBtn = document.getElementById('loadSubjectsBtn');
    const subjectsContainer = document.getElementById('subjectsContainer');
    const subjectsTableBody = document.getElementById('subjectsTableBody');
    const formCurriculumId = document.getElementById('formCurriculumId');
    const loadBtnText = document.getElementById('loadBtnText');
    const loadBtnSpinner = document.getElementById('loadBtnSpinner');
    const yearTabs = document.getElementById('yearTabs');
    const selectAllBtn = document.getElementById('selectAllBtn');

    curriculumSelect.addEventListener('change', function () {
        loadSubjectsBtn.classList.toggle('d-none', !this.value);
        subjectsContainer.classList.add('d-none');
        yearTabs.innerHTML = '';
        subjectsTableBody.innerHTML = '';
    });

    loadSubjectsBtn.addEventListener('click', function () {
        const curriculumId = curriculumSelect.value;
        if (!curriculumId) return;

        formCurriculumId.value = curriculumId;
        yearTabs.innerHTML = '';
        subjectsTableBody.innerHTML = '';
        loadSubjectsBtn.disabled = true;
        loadBtnText.classList.add('d-none');
        loadBtnSpinner.classList.remove('d-none');

        fetch(`/curriculum/${curriculumId}/fetch-subjects`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.length) {
                yearTabs.innerHTML = '';
                subjectsTableBody.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="mb-0">No courses found for this curriculum.</p>
                    </div>
                `;
                subjectsContainer.classList.remove('d-none');
                return;
            }

            const grouped = {};
            data.forEach(subj => {
                // Only include subjects for the current semester
                if (subj.semester !== currentSemester) return;

                const key = `year${subj.year_level}`;
                if (!grouped[key]) grouped[key] = [];
                grouped[key].push(subj);
            });

            let tabIndex = 0;
            for (const [key, subjects] of Object.entries(grouped)) {
                const year = key.replace('year', '');
                const yearLabels = { '1': '1st Year', '2': '2nd Year', '3': '3rd Year', '4': '4th Year' };
                const isActive = tabIndex === 0 ? 'active' : '';

                yearTabs.insertAdjacentHTML('beforeend', `
                    <li class="nav-item">
                        <button class="nav-link ${isActive}" style="color: #198754; font-weight: 500;" data-bs-toggle="tab" data-bs-target="#tab-${key}" type="button" role="tab">${yearLabels[year]}</button>
                    </li>
                `);

                const rows = subjects.map(s => {
                    // For GE Coordinator, disable checkboxes for non-GE subjects
                    // For Chairperson, disable checkboxes for GE, PD, PE, RS, NSTP subjects
                    let isDisabled = false;
                    if (userRole === 4 && !s.is_universal) {
                        isDisabled = true; // GE Coordinator can only select GE subjects
                    } else if (userRole === 1 && s.is_restricted) {
                        isDisabled = true; // Chairperson cannot select restricted subjects (GE, PD, PE, RS, NSTP)
                    }
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const disabledClass = isDisabled ? 'opacity-50' : '';
                    
                    return `
                        <tr class="${disabledClass}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input subject-checkbox" name="subject_ids[]" value="${s.id}" data-year="${s.year_level}" data-semester="${s.semester}" ${disabledAttr}>
                            </td>
                            <td><strong>${s.subject_code}</strong></td>
                            <td>${s.subject_description}</td>
                            <td class="text-center">${s.year_level}</td>
                            <td class="text-center">${s.semester}</td>
                        </tr>
                    `;
                }).join('');

                const table = `
                    <h6 class="semester-heading">
                        <i class="bi bi-calendar3 me-2"></i>${currentSemester} Semester
                    </h6>
                    <div class="table-container">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">Select</th>
                                    <th style="width: 150px;">Course Code</th>
                                    <th>Description</th>
                                    <th style="width: 100px;" class="text-center">Year</th>
                                    <th style="width: 120px;" class="text-center">Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                    </div>
                `;

                subjectsTableBody.insertAdjacentHTML('beforeend', `
                    <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="tab-${key}" role="tabpanel">
                        ${table}
                    </div>
                `);

                tabIndex++;
            }

            subjectsContainer.classList.remove('d-none');
        })
        .catch(() => {
            subjectsTableBody.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="text-danger mb-0">Failed to load courses. Please try again.</p>
                </div>
            `;
            subjectsContainer.classList.remove('d-none');
        })
        .finally(() => {
            loadSubjectsBtn.disabled = false;
            loadBtnText.classList.remove('d-none');
            loadBtnSpinner.classList.add('d-none');
        });
    });

    // Select/Unselect All Handler
    document.addEventListener('click', function (e) {
        if (e.target.closest('#selectAllBtn')) {
            const btn = e.target.closest('#selectAllBtn');
            let allSelected = btn.dataset.selected === 'true';
            allSelected = !allSelected;
            btn.dataset.selected = allSelected;
            
            // For GE Coordinator and Chairperson, only select enabled checkboxes
            document.querySelectorAll('.subject-checkbox').forEach(cb => {
                if ((userRole === 4 && cb.disabled) || (userRole === 1 && cb.disabled)) {
                    cb.checked = false; // Keep disabled checkboxes unchecked
                } else {
                    cb.checked = allSelected;
                }
            });
            
            if (allSelected) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
            
            btn.innerHTML = allSelected
                ? '<i class="bi bi-x-square me-1"></i> Unselect All'
                : '<i class="bi bi-check2-square me-1"></i> Select All';
            
            updateSelectedCount();
        }
    });

    // Update selected count
    function updateSelectedCount() {
        const count = document.querySelectorAll('.subject-checkbox:checked').length;
        const countEl = document.getElementById('selectedCount');
        if (countEl) {
            countEl.textContent = count;
        }
    }

    // Listen for checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('subject-checkbox')) {
            updateSelectedCount();
        }
    });

    // Confirm Modal Submission
    document.getElementById('submitConfirmBtn')?.addEventListener('click', function () {
        document.getElementById('confirmForm')?.submit();
    });
});
</script>
@endpush

