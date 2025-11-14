@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('vpaa.dashboard') }}" class="text-success">Home</a></li>
            <li class="breadcrumb-item active text-success" aria-current="page">Course Outcome Attainment Results</li>
        </ol>
    </nav>

    {{-- Subject List Controls --}}
    @if(isset($subjects) && count($subjects))
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                @if(isset($selectedDepartment))
                    <div class="me-auto">
                        <a href="{{ route('vpaa.course-outcome-attainment') }}" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-arrow-left"></i> Departments
                        </a>
                        <span class="fw-semibold">{{ $selectedDepartment->department_code }}</span>
                        <small class="text-muted ms-1">{{ $selectedDepartment->department_description }}</small>
                    </div>
                @else
                    <div class="flex-grow-1"></div>
                @endif
                <div class="flex-grow-1">
                    <input type="text" id="subject-search" class="form-control" placeholder="Search subjects (code or title)…" aria-label="Search subjects">
                </div>
                <div>
                    <select id="items-per-page" class="form-select">
                        <option value="9" selected>9 / page</option>
                        <option value="18">18 / page</option>
                        <option value="27">27 / page</option>
                        <option value="0">Show all</option>
                    </select>
                </div>
                <div class="text-muted" id="subjects-count" aria-live="polite"></div>
            </div>
        </div>

        <div class="row g-4 px-4 py-2" id="subject-selection" data-paginated>
            @foreach($subjects as $subjectItem)
                <div class="col-md-4">
                    <div
                        class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                        data-url="{{ route('vpaa.course-outcome-attainment.subject', ['subject' => $subjectItem->id]) }}"
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

        <nav class="mt-3" aria-label="Subjects pagination">
            <ul id="subjects-pagination" class="pagination justify-content-center mb-0"></ul>
        </nav>
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <div class="text-muted mb-3">
                    <i class="bi bi-folder-x fs-1 opacity-50"></i>
                </div>
                <h5 class="text-muted mb-2">No Subjects Found</h5>
                <p class="text-muted mb-4">
                    @if($academicYear && $semester)
                        No subjects found for the current academic period.
                        <br><strong>Academic Year:</strong> {{ $academicYear }}
                        <br><strong>Semester:</strong> {{ $semester }}
                    @else
                        No subjects found. You can still browse by subject when available.
                    @endif
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('vpaa.dashboard') }}" class="btn btn-success">
                        <i class="bi bi-house me-2"></i>Go to Dashboard
                    </a>
                    <a href="{{ route('vpaa.course-outcome-attainment') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </a>
                </div>
            </div>
        </div>
    @endif
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

    // Client-side search + pagination
    const grid = document.getElementById('subject-selection');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('.col-md-4'));
    const searchInput = document.getElementById('subject-search');
    const perPageSelect = document.getElementById('items-per-page');
    const pagination = document.getElementById('subjects-pagination');
    const countEl = document.getElementById('subjects-count');

    let filtered = cards.slice();
    let currentPage = 1;

    function getPerPage() {
        const val = parseInt(perPageSelect?.value || '9', 10);
        return val === 0 ? Number.MAX_SAFE_INTEGER : val;
    }

    function matchesSearch(card, term) {
        if (!term) return true;
        const text = card.textContent.toLowerCase();
        return text.includes(term);
    }

    function applyFilter() {
        const term = (searchInput?.value || '').trim().toLowerCase();
        filtered = cards.filter(c => matchesSearch(c, term));
        currentPage = 1;
        render();
    }

    function render() {
        const perPage = getPerPage();
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        currentPage = Math.min(currentPage, totalPages);

        // Hide all, then show slice
        cards.forEach(c => c.style.display = 'none');
        const start = (currentPage - 1) * perPage;
        const end = perPage === Number.MAX_SAFE_INTEGER ? total : start + perPage;
        filtered.slice(start, end).forEach(c => c.style.display = '');

        // Update count text
        const showing = total === 0 ? 0 : (end - start);
        if (countEl) {
            countEl.textContent = `Showing ${Math.min(total, end) === 0 ? 0 : start + 1}-${Math.min(total, end)} of ${total}`;
        }

        // Build pagination
        if (pagination) {
            pagination.innerHTML = '';
            if (perPage === Number.MAX_SAFE_INTEGER || totalPages <= 1) return;

            const createItem = (label, page, disabled = false, active = false) => {
                const li = document.createElement('li');
                li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.textContent = label;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (disabled || page === currentPage) return;
                    currentPage = page;
                    render();
                });
                li.appendChild(a);
                return li;
            };

            pagination.appendChild(createItem('«', Math.max(1, currentPage - 1), currentPage === 1));
            const maxPages = 7; // compact
            let startPage = Math.max(1, currentPage - 3);
            let endPage = Math.min(totalPages, startPage + maxPages - 1);
            if (endPage - startPage + 1 < maxPages) startPage = Math.max(1, endPage - (maxPages - 1));
            for (let p = startPage; p <= endPage; p++) {
                pagination.appendChild(createItem(String(p), p, false, p === currentPage));
            }
            pagination.appendChild(createItem('»', Math.min(totalPages, currentPage + 1), currentPage === totalPages));
        }
    }

    searchInput?.addEventListener('input', () => {
        // simple debounce
        clearTimeout(searchInput._t);
        searchInput._t = setTimeout(applyFilter, 150);
    });
    perPageSelect?.addEventListener('change', () => { currentPage = 1; render(); });

    // Initial
    applyFilter();
});
</script>
@endpush

@push('styles')
<style>
.vpaa-readonly form { display: none !important; }
.subject-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.subject-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important; }
.subject-circle { transition: all 0.3s ease; }
.subject-card:hover .subject-circle { transform: translate(-50%, -50%) scale(1.1); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
.breadcrumb-item a { color: #198754; text-decoration: none; }
.breadcrumb-item a:hover { color: #0f5132; text-decoration: underline; }
.form-label { color: #495057; font-weight: 600; }
.form-select:focus { border-color: #198754; box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25); }
.btn-primary { background-color: #198754; border-color: #198754; }
.btn-primary:hover { background-color: #0f5132; border-color: #0f5132; }
</style>
@endpush
