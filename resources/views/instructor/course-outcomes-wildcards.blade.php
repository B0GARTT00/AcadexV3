@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-2 course-outcomes-page" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    {{-- Compact Header Section --}}
    <div class="row mb-2">
        <div class="col">
            {{-- Compact Breadcrumbs --}}
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="/" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        <i class="bi bi-target me-1"></i>Course Outcomes
                    </li>
                </ol>
            </nav>

            {{-- Compact Page Title --}}
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-bullseye text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">Course Outcome Management</h4>
                        <small class="text-muted">Manage course learning outcomes by year</small>
                    </div>
                </div>
                
                {{-- Generate CO Button (Chairperson and GE Coordinator Only) --}}
                @if(Auth::user()->role === 1 || Auth::user()->role === 4)
                <div>
                    <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#generateCOModal" style="font-weight: 600;">
                        <i class="bi bi-magic me-1"></i>Generate COs
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Compact Academic Period Card --}}
    @if(isset($currentPeriod))
    <div class="card border-0 shadow-sm mb-2" style="background: linear-gradient(135deg, #198754, #20c997); color: white;">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="p-1 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                            <i class="bi bi-mortarboard text-white" style="font-size: 1rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $currentPeriod->academic_year }} - {{ $currentPeriod->semester }}</h6>
                            @if((Auth::user()->role === 1 || Auth::user()->role === 4) && Auth::user()->course)
                                <small class="opacity-90">
                                    <i class="bi bi-mortarboard me-1"></i>{{ Auth::user()->course->course_code }} Program
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    @if(isset($subjects) && count($subjects) > 0)
                        <div class="bg-white bg-opacity-20 rounded-pill px-2 py-1 d-inline-block">
                            <small style="color: #111; font-weight: bold;">{{ count($subjects) }} subjects</small>
                            @if(isset($subjectsByYear))
                                <small style="color: #111;"> • {{ count($subjectsByYear) }} years</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Compact Year Level Navigation --}}
    @if(isset($subjectsByYear) && count($subjectsByYear) > 1)
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h6 class="mb-0 fw-semibold" style="color: #198754; font-size: 0.9rem;">
                        <i class="bi bi-filter me-1"></i>Filter by Year
                    </h6>
                </div>
                <div class="col-md-9">
                    <div class="d-flex flex-wrap gap-1 justify-content-md-end">
                        {{-- Show All Button --}}
                        <button class="btn btn-success btn-sm rounded-pill px-2 py-1 year-filter-btn active" data-year="all" style="font-size: 0.8rem;">
                            <i class="bi bi-grid-3x3-gap me-1"></i>All
                            <span class="badge bg-white text-dark ms-1" style="font-size: 0.7rem;">{{ array_sum($subjectsByYear->map(fn($subjects) => count($subjects))->toArray()) }}</span>
                        </button>
                        
                        @foreach($subjectsByYear->keys()->sort() as $year)
                            <button class="btn btn-outline-success btn-sm rounded-pill px-2 py-1 year-filter-btn" data-year="{{ $year }}" style="font-size: 0.8rem;">
                                <i class="bi bi-mortarboard me-1"></i>
                                @php
                                    $yearLabels = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'];
                                @endphp
                                {{ $yearLabels[$year] ?? $year }}
                                <span class="badge bg-success text-white ms-1" style="font-size: 0.7rem;">{{ count($subjectsByYear[$year]) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Compact Subject Cards Grouped by Year Level --}}
    @if(isset($subjectsByYear) && count($subjectsByYear))
        @foreach($subjectsByYear as $yearLevel => $subjects)
            <div class="mb-3 year-section" id="year-{{ $yearLevel }}" data-year="{{ $yearLevel }}">
                {{-- Compact Year Level Header --}}
                <div class="year-level-section">
                    <div class="row align-items-center mb-2">
                        <div class="col">
                            <div class="d-flex align-items-center">
                                @php
                                    $iconColors = 'success';
                                @endphp
                                <div class="p-2 rounded-circle me-2 bg-{{ $iconColors }} bg-opacity-10">
                                    <i class="bi bi-award text-{{ $iconColors }}" style="font-size: 1.1rem;"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0" style="color: #198754;">
                                        @php
                                            $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                                        @endphp
                                        {{ $yearLabels[$yearLevel] ?? ($yearLevel ? 'Year ' . $yearLevel : 'Unspecified Year') }}
                                    </h5>
                                    <small class="text-muted">
                                        <i class="bi bi-book me-1"></i>{{ count($subjects) }} {{ count($subjects) == 1 ? 'subject' : 'subjects' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            @php
                                $headerBadgeClass = 'bg-success';
                            @endphp
                            <span class="badge {{ $headerBadgeClass }} px-2 py-1 rounded-pill">
                                Level {{ $yearLevel ?: '?' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Subject Cards Grid --}}
                <div class="row g-4 px-4 py-4" id="subject-selection-year-{{ $yearLevel }}">
                    @foreach($subjects as $subjectItem)
                        <div class="col-md-4">
                            <div
                                class="subject-card card h-100 border-0 shadow-lg rounded-4 overflow-hidden transform transition hover:scale-105 hover:shadow-xl"
                                data-url="{{ route($routePrefix . '.course_outcomes.index', ['subject_id' => $subjectItem->id]) }}"
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
            </div>
        @endforeach
    @else
        {{-- Enhanced Empty State --}}
        <div class="text-center py-5">
            <div class="card border-0 shadow-sm mx-auto" style="max-width: 500px;">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <div class="p-4 rounded-circle mx-auto d-inline-flex" style="background: linear-gradient(135deg, #198754, #20c997);">
                            <i class="bi bi-search text-white" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #198754;">No Subjects Found</h4>
                    @if(Auth::user()->role === 1 || Auth::user()->role === 4)
                        <p class="text-muted mb-4">
                            No subjects are currently available for your program 
                            <strong style="color: #198754;">{{ Auth::user()->course->course_code ?? 'Unknown' }}</strong> 
                            in the current academic period.
                        </p>
                        <div class="alert alert-light border border-warning">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle text-warning me-3 mt-1"></i>
                                <div>
                                    <strong>What to do:</strong>
                                    <ul class="mb-0 mt-2 text-start">
                                        <li>Contact the administrator to assign subjects to your program</li>
                                        <li>Ensure subjects are properly configured for this academic period</li>
                                        <li>Check if the academic period is correctly set</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-4">
                            No subjects have been assigned to you for the current academic period.
                        </p>
                        <div class="alert alert-light border border-info">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <span>Please contact your department chairperson for subject assignments.</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Generate Course Outcomes Modal --}}
@if(Auth::user()->role === 1 || Auth::user()->role === 4)
<div class="modal fade" id="generateCOModal" tabindex="-1" aria-labelledby="generateCOModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #198754, #20c997); border-radius: 1rem 1rem 0 0;">
                <h5 class="modal-title text-white fw-bold" id="generateCOModalLabel">
                    <i class="bi bi-magic me-2"></i>Generate Course Outcomes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Display validation errors --}}
                @if($errors->any())
                    <div class="alert alert-danger border-0 mb-3" style="background: rgba(220, 53, 69, 0.1);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill text-danger me-3 mt-1"></i>
                            <div>
                                <h6 class="text-danger fw-bold mb-2">Validation Error</h6>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li class="text-danger">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="alert alert-info border-0" style="background: rgba(13, 202, 240, 0.1);">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-info-circle text-info me-3 mt-1"></i>
                        <div>
                            <h6 class="text-info fw-bold mb-1">Auto-Generate Course Outcomes</h6>
                            <p class="mb-0 text-muted small">
                                This will generate course outcomes for subjects based on their current CO status. 
                                Each CO will have the description: <strong>"Students have achieved 75% of the course outcomes"</strong><br>
                                <strong>Identifiers:</strong> Generated as SubjectCode.1, SubjectCode.2, etc. (e.g., IT102.1, IT102.2)<br>
                                <strong>Maximum limit:</strong> 6 course outcomes per subject (CO1 through CO6)
                            </p>
                        </div>
                    </div>
                </div>

                <form id="generateCOForm" action="{{ route($routePrefix . '.course_outcomes.generate') }}" method="POST">
                    @csrf
                    
                    {{-- Subject Selection --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="color: #198754;">
                            <i class="bi bi-list-check me-1"></i>Select Generation Mode
                        </label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="generation_mode" id="mode_missing" value="missing_only" checked>
                                    <label class="form-check-label" for="mode_missing">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-plus-circle text-success me-2 mt-1"></i>
                                            <div>
                                                <strong class="text-success">Add to subjects without COs</strong>
                                                <br><small class="text-muted">Only generate for subjects that have 0 course outcomes</small>
                                                <br><small class="text-success"><i class="bi bi-shield-check me-1"></i>Safe option - no data loss</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="generation_mode" id="mode_override" value="override_all">
                                    <label class="form-check-label" for="mode_override">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-exclamation-triangle text-danger me-2 mt-1"></i>
                                            <div>
                                                <strong class="text-danger">Override all existing COs</strong>
                                                <br><small class="text-muted">Replace all existing course outcomes with fresh set of 6 COs (CO1-CO6)</small>
                                                <br><small class="text-danger"><i class="bi bi-shield-exclamation me-1"></i>⚠️ This will permanently delete existing COs!</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Danger Warning for Override Mode --}}
                    <div class="alert alert-danger border-0 mb-4" id="overrideWarning" style="display: none; background: rgba(220, 53, 69, 0.1);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill text-danger me-3 mt-1" style="font-size: 1.2rem;"></i>
                            <div>
                                <h6 class="text-danger fw-bold mb-2">
                                    <i class="bi bi-shield-exclamation me-1"></i>DANGER: This action cannot be undone!
                                </h6>
                                <p class="mb-2 text-danger">
                                    <strong>Override mode will permanently delete ALL existing course outcomes</strong> from the selected subjects and replace them with the standard template.
                                </p>
                                <ul class="mb-3 text-danger small">
                                    <li>All custom course outcome descriptions will be lost</li>
                                    <li>Any associated course outcome attainment data may be affected</li>
                                    <li>Student progress tracking linked to specific COs will be disrupted</li>
                                    <li>This action cannot be reversed</li>
                                </ul>
                                <div class="bg-white p-3 rounded border border-danger">
                                    <label class="form-label fw-bold text-danger mb-2">
                                        <i class="bi bi-key-fill me-1"></i>Confirm your password to proceed:
                                    </label>
                                    <input type="password" class="form-control border-danger" name="password_confirmation" id="passwordConfirmation" 
                                           placeholder="Enter your password to confirm this dangerous action" required disabled>
                                    <div class="form-text text-danger">
                                        <i class="bi bi-info-circle me-1"></i>Password confirmation is required for destructive operations
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Year Level Filter --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="color: #198754;">
                            <i class="bi bi-mortarboard me-1"></i>Select Year Levels (Optional)
                        </label>
                        <div class="row g-2">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="year_levels[]" value="all" id="year_all" checked>
                                    <label class="form-check-label" for="year_all">All Years</label>
                                </div>
                            </div>
                            @if(isset($subjectsByYear))
                                @foreach($subjectsByYear->keys()->sort() as $year)
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input year-specific" type="checkbox" name="year_levels[]" value="{{ $year }}" id="year_{{ $year }}">
                                        <label class="form-check-label" for="year_{{ $year }}">
                                            @php
                                                $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                                            @endphp
                                            {{ $yearLabels[$year] ?? 'Year ' . $year }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Preview Section --}}
                    <div class="card border-0" style="background: rgba(248, 249, 250, 0.8);">
                        <div class="card-body p-3">
                            <h6 class="fw-semibold mb-2" style="color: #198754;">
                                <i class="bi bi-eye me-1"></i>Preview: Course Outcomes to be Generated
                            </h6>
                            <div class="mb-2">
                                <small class="text-muted">Course outcomes will be generated to fill missing CO positions (maximum 6 per subject):</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item border-0 px-0 py-1">
                                            <small><strong>SubjectCode.1:</strong> Students have achieved 75% of the course outcomes</small>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-1">
                                            <small><strong>SubjectCode.2:</strong> Students have achieved 75% of the course outcomes</small>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-1">
                                            <small><strong>SubjectCode.3:</strong> Students have achieved 75% of the course outcomes</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item border-0 px-0 py-1">
                                            <small><strong>SubjectCode.4:</strong> Students have achieved 75% of the course outcomes</small>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-1">
                                            <small><strong>SubjectCode.5:</strong> Students have achieved 75% of the course outcomes</small>
                                        </div>
                                        <div class="list-group-item border-0 px-0 py-1">
                                            <small><strong>SubjectCode.6:</strong> Students have achieved 75% of the course outcomes</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info border-0 mt-3 mb-0" style="background: rgba(13, 202, 240, 0.1);">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-info-circle text-info me-2 mt-1"></i>
                                    <div>
                                        <small class="text-info">
                                            <strong>Smart Generation:</strong> The system will automatically identify which CO numbers (1-6) are missing for each subject and generate only those COs. Subjects that already have 6 COs will be skipped.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 1rem 1rem;">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success rounded-pill fw-semibold" onclick="submitGenerateForm()" id="generateSubmitBtn">
                    <i class="bi bi-magic me-1"></i>Generate Course Outcomes
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@if(Auth::user()->role === 1 || Auth::user()->role === 4)
@push('scripts')
<script>
// Generate CO Form Functions
function submitGenerateForm() {
    const form = document.getElementById('generateCOForm');
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    const generationMode = document.querySelector('input[name="generation_mode"]:checked').value;
    const passwordField = document.getElementById('passwordConfirmation');
    
    // Validate override mode requirements
    if (generationMode === 'override_all') {
        if (!passwordField.value.trim()) {
            // Show error for missing password
            passwordField.classList.add('is-invalid');
            passwordField.focus();
            
            // Create or update error message
            let errorDiv = passwordField.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                passwordField.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = 'Password confirmation is required for override operations';
            
            return false;
        }
        
        // Validate password via AJAX before proceeding
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Verifying password...';
        
        // Create AJAX request to validate password
        fetch('{{ route($routePrefix . ".course_outcomes.validate_password") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                password: passwordField.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                // Password is valid, show final confirmation
                const confirmed = confirm(
                    '⚠️ FINAL WARNING ⚠️\n\n' +
                    'This will PERMANENTLY DELETE all existing course outcomes and replace them with standard templates.\n\n' +
                    'Are you absolutely sure you want to proceed?\n\n' +
                    'Click OK to continue with this destructive action, or Cancel to abort.'
                );
                
                if (confirmed) {
                    // Update button and submit
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Overriding COs...';
                    form.submit();
                } else {
                    // Reset button if user cancels
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } else {
                // Password is invalid
                passwordField.classList.add('is-invalid');
                passwordField.focus();
                passwordField.select();
                
                let errorDiv = passwordField.parentNode.querySelector('.invalid-feedback');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    passwordField.parentNode.appendChild(errorDiv);
                }
                errorDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Incorrect password. Please try again.';
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Password validation error:', error);
            
            // Show error message
            passwordField.classList.add('is-invalid');
            let errorDiv = passwordField.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                passwordField.parentNode.appendChild(errorDiv);
            }
            errorDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Error validating password. Please try again.';
            
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
        
        return false; // Prevent form submission until password is validated
    } else {
        // For missing_only mode, proceed normally
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Generating...';
        form.submit();
    }
}
</script>
@endpush
@endif

@push('scripts')
<script>
// Year filtering and general functionality (available to all users)
document.addEventListener('DOMContentLoaded', function() {
    @if(Auth::user()->role === 1 || Auth::user()->role === 4)
    // Generation-specific functionality (only for chairpersons and GE coordinators)
    const allYearsCheckbox = document.getElementById('year_all');
    const yearSpecificCheckboxes = document.querySelectorAll('.year-specific');
    const overrideWarning = document.getElementById('overrideWarning');
    const passwordField = document.getElementById('passwordConfirmation');
    const modeRadios = document.querySelectorAll('input[name="generation_mode"]');
    const generateBtn = document.getElementById('generateSubmitBtn');
    
    // Handle generation mode changes
    modeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'override_all') {
                overrideWarning.style.display = 'block';
                passwordField.disabled = false;
                passwordField.required = true;
                generateBtn.className = 'btn btn-danger rounded-pill fw-semibold';
                generateBtn.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Override Course Outcomes';
            } else {
                overrideWarning.style.display = 'none';
                passwordField.disabled = true;
                passwordField.required = false;
                passwordField.value = '';
                passwordField.classList.remove('is-invalid');
                generateBtn.className = 'btn btn-success rounded-pill fw-semibold';
                generateBtn.innerHTML = '<i class="bi bi-magic me-1"></i>Generate Course Outcomes';
                
                // Remove any error messages
                const errorDiv = passwordField.parentNode.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        });
    });
    
    // Password field validation
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                const errorDiv = this.parentNode.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        });
    }
    
    // Auto-show modal if there are validation errors (form was submitted with errors)
    @if($errors->any() && old('generation_mode'))
        const modal = new bootstrap.Modal(document.getElementById('generateCOModal'), {
            backdrop: false
        });
        modal.show();
        
        // Restore form state
        const oldMode = '{{ old('generation_mode') }}';
        if (oldMode) {
            document.querySelector(`input[name="generation_mode"][value="${oldMode}"]`).checked = true;
            document.querySelector(`input[name="generation_mode"][value="${oldMode}"]`).dispatchEvent(new Event('change'));
        }
        
        // Restore year level selections
        @if(old('year_levels'))
            const oldYearLevels = @json(old('year_levels'));
            if (oldYearLevels) {
                oldYearLevels.forEach(year => {
                    const checkbox = document.querySelector(`input[name="year_levels[]"][value="${year}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        @endif
    @endif
    
    if (allYearsCheckbox) {
        allYearsCheckbox.addEventListener('change', function() {
            yearSpecificCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.disabled = this.checked;
            });
        });
        
        yearSpecificCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    allYearsCheckbox.checked = false;
                }
                
                // If no specific years are selected, check "All Years"
                const anyChecked = Array.from(yearSpecificCheckboxes).some(cb => cb.checked);
                if (!anyChecked) {
                    allYearsCheckbox.checked = true;
                    yearSpecificCheckboxes.forEach(cb => cb.disabled = true);
                } else {
                    yearSpecificCheckboxes.forEach(cb => cb.disabled = false);
                }
            });
        });
    }
    @endif

    // Year filtering functionality (available to all users)
    function filterByYear(selectedYear) {
        const yearSections = document.querySelectorAll('.year-section');
        
        yearSections.forEach(section => {
            if (selectedYear === 'all' || section.dataset.year == selectedYear) {
                section.style.display = 'block';
                section.style.animation = 'fadeInUp 0.6s ease-out';
            } else {
                section.style.display = 'none';
            }
        });
        
        // Update active filter button
        updateActiveFilterButton(selectedYear);
    }
    
    // Update active filter button
    function updateActiveFilterButton(activeFilter) {
        document.querySelectorAll('.year-filter-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
            
            if (btn.dataset.year == activeFilter) {
                btn.classList.add('active');
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success');
            }
        });
    }
    
    // Enhanced smooth scrolling for year navigation
    function scrollToYear(year) {
        const target = document.getElementById(`year-${year}`);
        if (target) {
            const header = document.querySelector('.year-navigation');
            const headerHeight = header ? header.offsetHeight + 20 : 120;
            
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
            
            // Add visual feedback
            target.style.transform = 'scale(1.02)';
            setTimeout(() => {
                target.style.transform = 'scale(1)';
            }, 300);
        }
    }
    
    // Filter button event listeners
    document.querySelectorAll('.year-filter-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedYear = this.dataset.year;
            filterByYear(selectedYear);
            
            // If filtering to a specific year, scroll to it
            if (selectedYear !== 'all') {
                setTimeout(() => scrollToYear(selectedYear), 100);
            }
        });
    });
    
    // Modern subject card interactions
    document.querySelectorAll('.subject-card[data-url]').forEach(card => {
        card.addEventListener('click', function() {
            window.location.href = this.dataset.url;
        });
        
        // Add accessibility support
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 'Select subject');
        
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Keyboard navigation support for filtering
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            const currentActive = document.querySelector('.year-filter-btn.active');
            if (!currentActive) return;
            
            const allButtons = Array.from(document.querySelectorAll('.year-filter-btn'));
            const currentIndex = allButtons.indexOf(currentActive);
            
            let nextIndex;
            if (e.key === 'ArrowLeft') {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : allButtons.length - 1;
            } else {
                nextIndex = currentIndex < allButtons.length - 1 ? currentIndex + 1 : 0;
            }
            
            const nextButton = allButtons[nextIndex];
            if (nextButton) {
                nextButton.click();
                e.preventDefault();
            }
        }
    });
    
    // Add enhanced CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0);
                opacity: 0.6;
            }
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .year-level-section {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .year-section {
            transition: all 0.3s ease;
        }
        
        .year-filter-btn {
            transition: all 0.3s ease;
        }
        
        .year-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .year-filter-btn.active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 135, 84, 0.3);
        }
    `;
    document.head.appendChild(style);
    
    // Initialize "Show All" as active
    const showAllButton = document.querySelector('.year-filter-btn[data-year="all"]');
    if (showAllButton) {
        showAllButton.classList.add('active');
    }
    
    // Global functions
    window.scrollToYear = scrollToYear;
    window.filterByYear = filterByYear;
});
</script>
@endpush

@push('styles')
<style>
/* Primary color variables */
:root {
    --primary-color: #198754;
    --primary-light: #20c997;
    --primary-dark: #146c43;
    --primary-subtle: rgba(25, 135, 84, 0.1);
}

/* Global styles */
    .course-outcomes-page {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Smooth animations */
* {
    transition: all 0.3s ease;
}

/* Year level sections */
.year-level-section {
    scroll-margin-top: 120px;
}

/* Modern card effects */
.subject-card .card {
    border-radius: 1rem;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    background: #4ecd85 !important;
    border: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.subject-card:hover .card {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

/* Subject code circle animation */
.subject-code-circle {
    transition: all 0.3s ease;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4da674, #023336);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 18px;
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.subject-card:hover .subject-code-circle {
    transform: translateX(-50%) scale(1.1);
}

.card-header {
    background: transparent !important;
    border-bottom: none;
    padding-top: 50px;
    position: relative;
}

/* Navigation buttons */
.year-nav-btn {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.year-nav-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.year-nav-btn.active {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.3);
}

/* Breadcrumb styling */
.breadcrumb {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: var(--primary-color);
    font-weight: bold;
}

/* Card hover states */
.card {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Loading animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.loading {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Responsive design improvements */
@media (max-width: 768px) {
    .year-nav-btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .subject-card .card {
        margin-bottom: 1rem;
    }
    
    .subject-code-circle {
        width: 60px;
        height: 60px;
        top: -30px;
        font-size: 14px;
    }
    
    .card-header {
        padding-top: 40px;
    }
    
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

/* Focus states for accessibility */
.subject-card:focus-within .card,
.year-nav-btn:focus {
    outline: 3px solid var(--primary-subtle);
    outline-offset: 2px;
}

/* Empty state styling */
.alert-light {
    background-color: rgba(248, 249, 250, 0.8);
    backdrop-filter: blur(10px);
}

/* Gradient text effect */
.gradient-text {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Smooth scroll behavior */
html {
    scroll-behavior: smooth;
}

/* Enhanced card shadows */
.shadow-sm {
    box-shadow: 0 0.125rem 0.75rem rgba(25, 135, 84, 0.075) !important;
}

.shadow {
    box-shadow: 0 0.5rem 1.5rem rgba(25, 135, 84, 0.15) !important;
}

/* Button improvements */
.btn {
    border-radius: 0.75rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Badge improvements */
.badge {
    font-weight: 600;
    letter-spacing: 0.025em;
}

/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.year-level-section {
    animation: fadeInUp 0.6s ease-out;
}

/* Icon enhancements */
.bi {
    vertical-align: -0.125em;
}
</style>
@endpush