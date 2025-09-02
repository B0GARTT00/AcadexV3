@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/vpaa/dashboard">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vpaa.course-outcome-attainment') }}">Course Outcome Attainment Results</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ $selectedSubject->subject_code }} - {{ $selectedSubject->subject_description }}
            </li>
        </ol>
    </nav>

    {{-- View-only banner --}}
    <div class="alert alert-success bg-success-subtle border-0 text-dark d-flex align-items-center" role="alert">
        <i class="bi bi-eye me-2"></i>
        VPAA view is read-only. Editing is unavailable here.
    </div>

    {{-- Reuse instructor results UI (wrapped to hide non-view actions) --}}
    <div class="vpaa-readonly">
    @include('instructor.scores.course-outcome-results', [
        'students' => $students,
        'coResults' => $coResults,
        'coColumnsByTerm' => $coColumnsByTerm,
        'coDetails' => $coDetails,
        'finalCOs' => $finalCOs,
        'terms' => $terms,
        'subjectId' => $subjectId,
        'selectedSubject' => $selectedSubject,
    ])
    </div>
</div>
@endsection

@push('styles')
<style>
/* VPAA read-only: hide non-view actions coming from the instructor template */
.vpaa-readonly .notification-bell { display: none !important; }
.vpaa-readonly [data-bs-target="#printOptionsModal"],
.vpaa-readonly #printOptionsModal,
.vpaa-readonly #warningModal { display: none !important; }
.vpaa-readonly a[href*="/instructor/course-outcome-attainments"],
.vpaa-readonly a[href*="/instructor/course_outcomes"],
.vpaa-readonly a[href*="/instructor/grades"] { display: none !important; }
/* Prevent any accidental form submissions if present */
.vpaa-readonly form { display: none !important; }

/* Global fallback if included template escapes wrapper */
body.vpaa-view a[href*="/instructor/course_outcomes"],
body.vpaa-view a[href*="/instructor/course-outcome-attainments"],
body.vpaa-view a[href*="/instructor/grades"],
body.vpaa-view .notification-bell,
body.vpaa-view [data-bs-target="#printOptionsModal"],
body.vpaa-view #printOptionsModal,
body.vpaa-view #warningModal { display: none !important; }

/* Hide the academic-period note container under the empty-state buttons */
.vpaa-readonly .card-body.text-center .mt-3 { display: none !important; }
/* Extra: hide small info line regardless of wrapper */
body.vpaa-view .card-body.text-center .mt-3 small.text-muted { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark body so global CSS can target instructor links in included template
    document.body.classList.add('vpaa-view');

    // Extra hardening: remove the specific setup button by text or href
    document.querySelectorAll('a.btn').forEach(function(el){
        const txt = (el.textContent || '').trim();
        const href = el.getAttribute('href') || '';
        if (/set\s*up\s*course\s*outcomes/i.test(txt) || href.includes('/instructor/course_outcomes')) {
            el.style.display = 'none';
        }
    });

            // Remove the informational note about creating course outcomes for the current academic period (global)
            document.querySelectorAll('small.text-muted').forEach(function(el){
                const text = (el.textContent || '').replace(/\s+/g,' ').trim().toLowerCase();
                if (text.includes('course outcomes can be created')) {
                    const container = el.closest('.mt-3');
                    if (container) container.remove(); else el.remove();
                }
            });

    // If the included instructor view shows the setup guidance alert, rewrite it for VPAA context
    const infoAlerts = Array.from(document.querySelectorAll('.alert.alert-info'));
    infoAlerts.forEach(alert => {
        const heading = alert.querySelector('.alert-heading');
        const list = alert.querySelector('ul');
        if (heading && /no course outcomes found/i.test(heading.textContent || '')) {
            heading.innerHTML = '<i class="bi bi-info-circle me-2"></i>Viewing Only: No Course Outcomes Available';
            // Replace guidance content with VPAA-friendly copy
            const p = alert.querySelector('p');
            if (p) {
                p.textContent = 'This subject currently has no defined course outcomes. Results will appear once instructors set up outcomes and assessments.';
            }
            const hr = alert.querySelector('hr');
            if (hr) hr.remove();
            if (list) list.replaceChildren();
            if (list) {
                const li = document.createElement('li');
                li.textContent = 'Monitoring only: setup is managed by instructors and department staff.';
                list.appendChild(li);
            }
        }
    });
});
</script>
@endpush
