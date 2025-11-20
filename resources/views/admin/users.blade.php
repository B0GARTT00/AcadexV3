@extends('layouts.app')

@section('content')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .swal-small {
            width: 360px !important;
            font-size: 0.875rem;
        }
        .swal2-html-container {
            margin: 0.5em 1em 0.5em !important;
        }
        /* Disable modal option cards */
        .disable-modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-bottom: none;
            padding: 1.5rem;
        }
        .disable-modal-body {
            padding: 1.75rem;
            background: #f8f9fa;
        }
        .disable-modal-intro {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            border-left: 4px solid #dc3545;
            margin-bottom: 1.5rem;
        }
        .disable-options-row {
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .disable-option-card {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .disable-option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd, #0dcaf0);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .disable-option-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .disable-option-card .icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .disable-option-card .meta {
            flex: 1 1 auto;
            width: 100%;
        }
        .disable-option-card .meta .fw-semibold {
            font-size: 0.875rem;
            margin-bottom: 0.15rem;
            color: #212529;
        }
        .disable-option-card.active {
            border-color: #0d6efd;
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.2);
            background: linear-gradient(135deg, #f8fbff 0%, #e7f3ff 100%);
            transform: translateY(-2px);
        }
        .disable-option-card.active::before {
            transform: scaleX(1);
        }
        .disable-option-card.active .icon {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0) !important;
            color: white !important;
            transform: scale(1.05);
        }
        .disable-option-card small {
            color: #6c757d;
            font-size: 0.75rem;
            line-height: 1.3;
        }
        .disable-option-card .check-mark {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 20px;
            height: 20px;
            background: #0d6efd;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.65rem;
        }
        .disable-option-card.active .check-mark {
            display: flex;
            animation: checkPop 0.3s ease;
        }
        @keyframes checkPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .disable-modal-footer {
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 1.25rem 1.75rem;
        }
        #customDisableDatetime {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.2s ease;
        }
        #customDisableDatetime:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }
    </style>
@endpush

@push('head')
    <script>
        // Make togglePasswordVisibility globally available
        window.togglePasswordVisibility = function(inputId) {
            const input = document.getElementById(inputId);
            const button = inputId === 'password' ? document.getElementById('togglePassword') : document.getElementById('togglePasswordConfirmation');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
@endpush

<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-dark fw-bold mb-0">👥 Users</h1>
        <button class="btn btn-success" onclick="openModal()">+ Add User</button>
    </div>

    {{-- Warning Message --}}
    @if (isset($hasDisabledUntilColumn) && ! $hasDisabledUntilColumn)
        <div class="alert alert-danger mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            The <code>disabled_until</code> column is missing from the <code>users</code> table. Please run the latest migrations to restore disable-account behavior.
        </div>
    @endif

    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        These users have higher access. Add one at your own discretion.
    </div>

    {{-- Users Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Username</th>
                            <th>User Role</th>
                            <th>Active Sessions</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $user->name }}
                                    @if ($user->is_active)
                                        <span class="ms-2 badge bg-success-subtle text-success fw-semibold">Active</span>
                                    @else
                                        <span class="ms-2 badge bg-secondary text-white fw-semibold">Disabled</span>
                                        @if (isset($hasDisabledUntilColumn) && $hasDisabledUntilColumn && $user->disabled_until)
                                                    @php $until = new \Carbon\Carbon($user->disabled_until); @endphp
                                                    @if ($until->year >= 9999)
                                                        <small class="d-block text-muted mt-1">Indefinitely</small>
                                                    @else
                                                        <small class="d-block text-muted mt-1">Until: {{ $until->format('M d, Y h:i A') }}</small>
                                                    @endif
                                                @endif
                                    @endif
                                </td>
                                <td>
                                    @switch($user->role)
                                        @case(0)
                                            <span class="badge bg-secondary">Instructor</span>
                                            @break
                                        @case(1)
                                            <span class="badge bg-primary">Chairperson</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-info text-dark">Dean</span>
                                            @break
                                        @case(3)
                                            <span class="badge bg-danger">Admin</span>
                                            @break
                                        @case(4)
                                            <span class="badge bg-warning text-dark">GE Coordinator</span>
                                            @break
                                        @case(5)
                                            <span class="badge bg-dark">VPAA</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark border">Unknown</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info session-count" data-user-id="{{ $user->id }}">
                                        <i class="bi bi-hourglass-split"></i> Loading...
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($user->is_active)
                                        @if(auth()->id() !== $user->id)
                                            <button type="button" class="btn btn-sm btn-danger" onclick="openChooseDisableModal({{ $user->id }}, '{{ addslashes($user->name) }}')" title="Disable Account">
                                                <i class="bi bi-person-slash"></i> Disable
                                            </button>
                                        @else
                                            {{-- Current user cannot disable themselves; show disabled state with tooltip --}}
                                            <button type="button" class="btn btn-sm btn-danger disabled" title="You cannot disable your own account" disabled>
                                                <i class="bi bi-person-slash"></i> Disable
                                            </button>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary px-3 py-2">Disabled</span>
                                        @if(auth()->id() !== $user->id)
                                            <button type="button" class="btn btn-sm btn-success ms-2" onclick="enableUser({{ $user->id }}, '{{ addslashes($user->name) }}')" title="Re-enable Account">
                                                <i class="bi bi-person-plus"></i> Enable
                                            </button>
                                        @else
                                            {{-- Self-enabled account: no action (user can't re-enable themself while logged out) --}}
                                        @endif
                                    @endif
                                </td>
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
    {{-- Disable Choose Modal (one instance) --}}
    <div class="modal fade" id="chooseDisableModal" tabindex="-1" aria-labelledby="chooseDisableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header disable-modal-header text-white">
                    <div>
                        <h5 class="modal-title mb-1" id="chooseDisableModalLabel">
                            <i class="bi bi-person-slash me-2"></i>Disable User Account
                        </h5>
                        <small class="opacity-75">Temporarily restrict account access</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="chooseDisableForm" method="POST" action="">
                    @csrf
                    <div class="modal-body disable-modal-body">
                        <div class="disable-modal-intro">
                            <div class="d-flex align-items-start gap-3">
                                <div class="text-danger" style="font-size: 1.5rem;">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Disabling: <span id="chooseDisableUserName" class="text-primary"></span></h6>
                                    <p class="mb-0 small text-muted">
                                        This will prevent the user from logging in or accessing the system for the selected duration. 
                                        All active sessions will be terminated immediately.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-dark mb-3">
                                <i class="bi bi-clock-history me-2"></i>Choose Duration
                            </label>
                        </div>

                        <div class="row disable-options-row row-cols-1 row-cols-md-2 row-cols-lg-4">
                            <div class="col mb-3">
                                <div class="disable-option-card active" data-value="1_week" role="button" tabindex="0">
                                    <span class="check-mark"><i class="bi bi-check-lg"></i></span>
                                    <div class="icon bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-calendar-week-fill"></i>
                                    </div>
                                    <div class="meta">
                                        <div class="fw-semibold">1 Week</div>
                                        <small>Disable for 7 days</small>
                                    </div>
                                    <input type="radio" class="d-none" name="duration_option" value="1_week" checked>
                                </div>
                            </div>

                            <div class="col mb-3">
                                <div class="disable-option-card" data-value="1_month" role="button" tabindex="0">
                                    <span class="check-mark"><i class="bi bi-check-lg"></i></span>
                                    <div class="icon bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-calendar-month-fill"></i>
                                    </div>
                                    <div class="meta">
                                        <div class="fw-semibold">1 Month</div>
                                        <small>Disable for ~30 days</small>
                                    </div>
                                    <input type="radio" class="d-none" name="duration_option" value="1_month">
                                </div>
                            </div>

                            <div class="col mb-3">
                                <div class="disable-option-card" data-value="indefinite" role="button" tabindex="0">
                                    <span class="check-mark"><i class="bi bi-check-lg"></i></span>
                                    <div class="icon bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-slash-circle-fill"></i>
                                    </div>
                                    <div class="meta">
                                        <div class="fw-semibold">Indefinite</div>
                                        <small>Until manually re-enabled</small>
                                    </div>
                                    <input type="radio" class="d-none" name="duration_option" value="indefinite">
                                </div>
                            </div>

                            <div class="col mb-3">
                                <div class="disable-option-card" data-value="custom" role="button" tabindex="0">
                                    <span class="check-mark"><i class="bi bi-check-lg"></i></span>
                                    <div class="icon bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-clock-fill"></i>
                                    </div>
                                    <div class="meta">
                                        <div class="fw-semibold">Custom</div>
                                        <small>Pick exact date &amp; time</small>
                                    </div>
                                    <input type="radio" class="d-none" name="duration_option" value="custom">
                                </div>
                            </div>
                        </div>

                        <div id="customDatetimeWrapper" style="display: none;" class="mt-3">
                            <div class="bg-white p-3 rounded-3 border">
                                <label for="customDisableDatetime" class="form-label fw-semibold small mb-2">
                                    <i class="bi bi-calendar-event me-1"></i>Select Re-enable Date & Time
                                </label>
                                <input 
                                    type="datetime-local" 
                                    id="customDisableDatetime" 
                                    name="custom_disable_datetime" 
                                    class="form-control" 
                                    min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                >
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>Account will be automatically re-enabled at this time
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer disable-modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <input type="hidden" name="duration" id="chooseDisableDuration" value="1_week">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-person-slash me-2"></i>Disable Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add User Modal --}}
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="courseModalLabel">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="user-form" action="{{ route('admin.storeVerifiedUser') }}" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- Name Section --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Juan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" placeholder="(optional)">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Dela Cruz" required>
                        </div>
                    </div>

                    {{-- Email Username --}}
                    <div class="mt-3">
                        <label class="form-label">Email Username</label>
                        <div class="input-group">
                            <input type="text" name="email" class="form-control" placeholder="jdelacruz" required
                                pattern="^[^@]+$" title="Do not include '@' or domain — just the username.">
                            <span class="input-group-text">@brokenshire.edu.ph</span>
                        </div>
                        <div id="email-warning" class="text-danger small mt-1 d-none">
                            Please enter only your username — do not include '@' or email domain.
                        </div>
                    </div>

                    {{-- User Role --}}
                    <div class="mt-3">
                        <label class="form-label">User Role</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Choose Role --</option>
                            <option value="1">Chairperson</option>
                            <option value="2">Dean</option>
                            <option value="3">Admin</option>
                            <option value="5">VPAA</option>
                        </select>
                    </div>

                    {{-- Department --}}
                    <div class="mt-3" id="department-wrapper">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">-- Choose Department --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->department_description }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Course --}}
                    <div class="mt-3" id="course-wrapper">
                        <label class="form-label">Course</label>
                        <select name="course_id" class="form-select">
                            <option value="">-- Choose Course --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->course_description }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Password --}}
                    <div class="mt-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Min. 8 characters" autocomplete="new-password"
                                   oninput="checkPassword(this.value)" id="password">
                            <button type="button" id="togglePassword" 
                                    class="btn btn-outline-secondary border-start-0 text-dark" 
                                    style="background-color: #f8f9fa;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        
                        {{-- Password Requirements --}}
                        <div id="password-requirements" class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div id="circle-length" class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                                        <small>Minimum 8 characters</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div id="circle-case" class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                                        <small>Upper & lowercase</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div id="circle-number" class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                                        <small>At least 1 number</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div id="circle-special" class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                                        <small>Special character</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mt-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" class="form-control" required id="password_confirmation">
                            <button type="button" id="togglePasswordConfirmation" 
                                    class="btn btn-outline-secondary border-start-0 text-dark" 
                                    style="background-color: #f8f9fa;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="openConfirmModal()">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function enableUser(userId, userName) {
        Swal.fire({
            title: 'Re-enable Account?',
            html: `Are you sure you want to re-enable <strong>${userName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Re-enable'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch(`/admin/users/${userId}/enable`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Success', text: data.message, confirmButtonColor: '#198754' });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to re-enable user', confirmButtonColor: '#198754' });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to re-enable user', confirmButtonColor: '#198754' });
            });
        })
    }
    function openChooseDisableModal(userId, userName) {
        document.getElementById('chooseDisableUserName').textContent = userName;
        const form = document.getElementById('chooseDisableForm');
        form.action = '/admin/users/' + userId + '/disable';
        form.dataset.userId = userId;
        form.dataset.userName = userName;
        // default radio value
        const defaultVal = document.querySelector('input[name="duration_option"]:checked')?.value || '1_week';
        document.getElementById('chooseDisableDuration').value = defaultVal;
        // show modal
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('chooseDisableModal'));
        modal.show();
    }


    // Update hidden input and show/hide custom datetime when radio changes
    document.addEventListener('change', function (e) {
        if (e.target && e.target.name === 'duration_option') {
            document.getElementById('chooseDisableDuration').value = e.target.value;
            const customWrapper = document.getElementById('customDatetimeWrapper');
            const customInput = document.getElementById('customDisableDatetime');
            if (e.target.value === 'custom') {
                customWrapper.style.display = '';
                customInput.required = true;
            } else {
                customWrapper.style.display = 'none';
                customInput.required = false;
            }
        }
    });

    // Card click handler: make the whole card selectable and reflect selection visually
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.disable-option-card');
        if (!card) return;

        const value = card.dataset.value;
        const radios = document.getElementsByName('duration_option');
        Array.from(radios).forEach(r => r.checked = (r.value === value));

        // Toggle active class
        document.querySelectorAll('.disable-option-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        // Trigger change event for radio to run existing handlers
        const event = new Event('change', { bubbles: true });
        const selectedRadio = Array.from(radios).find(r => r.value === value);
        if (selectedRadio) selectedRadio.dispatchEvent(event);
    });

    // AJAX submission
    document.getElementById('chooseDisableForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const form = e.target;
        const userId = form.dataset.userId;
        const userName = form.dataset.userName;
        const selected = document.querySelector('input[name="duration_option"]:checked');
        
        if (!selected) {
            alert('Please select a duration.');
            return;
        }
        
        const duration = selected.value;
        const formData = new FormData();
        formData.append('duration', duration);
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        // Disable submit button early so we can safely restore it on early returns
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : null;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Disabling...';
        }

        if (duration === 'custom') {
            const customVal = document.getElementById('customDisableDatetime').value;
            if (!customVal) {
                alert('Please select a custom date and time.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                return;
            }
            formData.append('custom_disable_datetime', customVal);
        }
        // submitBtn already disabled above
        fetch(form.action, {
            method: 'POST',
            body: formData,
        })
        .then(async response => {
            let parsed = null;
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                parsed = await response.json();
            }
            if (!response.ok) {
                const errMsg = parsed?.message || parsed || (await response.text().catch(() => null)) || response.statusText;
                throw errMsg;
            }
            return parsed;
        })
        .then(data => {
            if (data && data.success) {
                // Hide modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseDisableModal'));
                modal.hide();
                // Show success message
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success alert-dismissible fade show';
                successAlert.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                // Find a container to insert the alert into. The page uses 'container-fluid' but search both to be safe.
                const container = document.querySelector('.container-fluid.py-4') || document.querySelector('.container.py-4') || document.body;
                const headerRef = document.querySelector('.d-flex.justify-content-between');
                if (container) {
                    if (headerRef && container.contains(headerRef)) {
                        container.insertBefore(successAlert, headerRef);
                    } else if (container.firstChild) {
                        container.insertBefore(successAlert, container.firstChild);
                    } else {
                        container.appendChild(successAlert);
                    }
                } else {
                    document.body.appendChild(successAlert);
                }
                // Optionally reload page after 2 seconds
                setTimeout(() => location.reload(), 2000);
            } else {
                // When response is OK but data.success is false, show its message
                throw (data?.message || 'Failed to disable user.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const message = typeof error === 'string' ? error : (error?.message || 'An error occurred while disabling the user.');
            alert(message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
</script>
@endpush

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Your Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="confirm-form" action="#" method="POST">
                @csrf
                <div class="modal-body">
                    <p>To make sure this is you, you will need to re-enter your password for safety purposes.</p>
                    <div class="mt-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="confirm_password" class="form-control" required 
                               placeholder="Re-enter your password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js" defer></script>
    <script defer>
        // Add this at the start of your scripts
        const swalCustomClass = {
            popup: 'swal-small',
            icon: 'text-danger',
            title: 'fs-5',
            htmlContainer: 'text-start'
        };

        function validateForm() {
            const form = document.getElementById('user-form');
            const password = form.querySelector('input[name="password"]').value;
            const confirmPassword = form.querySelector('input[name="password_confirmation"]').value;
            const firstName = form.querySelector('input[name="first_name"]').value;
            const lastName = form.querySelector('input[name="last_name"]').value;
            const email = form.querySelector('input[name="email"]').value;
            const role = form.querySelector('select[name="role"]').value;
            const departmentId = form.querySelector('select[name="department_id"]').value;
            const courseId = form.querySelector('select[name="course_id"]').value;

            // Check if required fields are filled
            const missingFields = [];
            if (!firstName) missingFields.push('First Name');
            if (!lastName) missingFields.push('Last Name');
            if (!email) missingFields.push('Email Username');
            if (!role) missingFields.push('User Role');
            
            // Only validate department and course if not Admin or VPAA
            if (role !== "3" && role !== "5") {
                if (!departmentId) missingFields.push('Department');
                // Only require course for Chairperson role
                if (role === "1" && !courseId) missingFields.push('Course');
            }
            
            if (!password) missingFields.push('Password');
            if (!confirmPassword) missingFields.push('Confirm Password');

            if (missingFields.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    html: `<div class="text-start small">
                        <p class="mb-2">Please fill in the following required fields:</p>
                        ${missingFields.map(field => `<span class="d-block">• ${field}</span>`).join('')}
                    </div>`,
                    confirmButtonColor: '#198754',
                    customClass: {
                        popup: 'swal-small',
                        title: 'fs-5',
                        htmlContainer: 'text-start'
                    }
                });
                return false;
            }

            // Validate email format (no @ or domain)
            if (email.includes('@')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email Format',
                    text: 'Please enter only your username without @ or domain.',
                    confirmButtonColor: '#198754'
                });
                return false;
            }

            // Check password requirements
            const hasMinLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            if (!(hasMinLength && hasUpperCase && hasLowerCase && hasNumber && hasSpecial)) {
                let missingRequirements = [];
                if (!hasMinLength) missingRequirements.push('Minimum 8 characters');
                if (!hasUpperCase || !hasLowerCase) missingRequirements.push('Both uppercase and lowercase letters');
                if (!hasNumber) missingRequirements.push('At least one number');
                if (!hasSpecial) missingRequirements.push('At least one special character');

                Swal.fire({
                    icon: 'error',
                    title: 'Password Requirements Not Met',
                    html: `Your password must include:<br><br>` +
                          missingRequirements.map(req => `• ${req}`).join('<br>'),
                    confirmButtonColor: '#198754'
                });
                return false;
            }

            // Check if passwords match
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords Do Not Match',
                    text: 'Please make sure your passwords match.',
                    confirmButtonColor: '#198754'
                });
                return false;
            }

            return true;
        }

        function openModal() {
            const modal = new bootstrap.Modal(document.getElementById('courseModal'), {
                backdrop: false
            });
            modal.show();
        }

        function closeModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('courseModal'));
            modal.hide();
        }

        function openConfirmModal() {
            if (validateForm()) {
                // Check for duplicate user
                const firstName = document.querySelector('input[name="first_name"]').value;
                const lastName = document.querySelector('input[name="last_name"]').value;
                const email = document.querySelector('input[name="email"]').value;
                
                fetch(`/api/check-duplicate-name?first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}&email=${encodeURIComponent(email)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            Swal.fire({
                                icon: 'error',
                                title: 'User Already Exists',
                                text: 'A user with this name or email already exists in the system.',
                                confirmButtonColor: '#198754',
                                customClass: {
                                    popup: 'swal-small',
                                    icon: 'text-danger'
                                }
                            });
                        } else {
                            // Proceed with confirmation modal if no duplicate
                            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'), {
                                backdrop: false
                            });
                            confirmModal.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Proceed with confirmation modal if check fails
                        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'), {
                            backdrop: false
                        });
                        confirmModal.show();
                    });
            }
        }

        function closeConfirmModal() {
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            confirmModal.hide();
        }

        // Password validation
        function checkPassword(password) {
            const checks = {
                length: password.length >= 8,
                number: /[0-9]/.test(password),
                case: /[a-z]/.test(password) && /[A-Z]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            const update = (id, valid) => {
                const el = document.getElementById(`circle-${id}`);
                el.classList.remove('bg-danger', 'bg-success', 'bg-secondary');
                el.classList.add(valid ? 'bg-success' : 'bg-danger');
            };

            update('length', checks.length);
            update('number', checks.number);
            update('case', checks.case);
            update('special', checks.special);

            const requirementsBox = document.getElementById('password-requirements');
            const allValid = Object.values(checks).every(Boolean);
            requirementsBox.classList.toggle('d-none', allValid);
        }

        // Form submission
        document.getElementById('confirm-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
        
            fetch("{{ route('admin.confirmUserCreationWithPassword') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeConfirmModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Verified',
                        text: 'Creating new user account...',
                        timer: 1500,
                        showConfirmButton: false,
                        willClose: () => {
                            submitUserForm();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: data.message || 'Invalid password. Please try again.',
                        confirmButtonColor: '#198754'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was an error processing your request. Please try again.',
                    confirmButtonColor: '#198754'
                });
            });
        });

        function submitUserForm() {
            document.getElementById('user-form').submit();
        }

        // Role change handler
        document.addEventListener('DOMContentLoaded', function () {
            const roleInput = document.querySelector('select[name="role"]');
            const departmentInput = document.querySelector('select[name="department_id"]');
            const courseInput = document.querySelector('select[name="course_id"]');
            const courseWrapper = document.getElementById('course-wrapper');
            const departmentWrapper = document.getElementById('department-wrapper');

            // Initially hide course wrapper
            courseWrapper.classList.add('d-none');

            // Role change handler
            roleInput.addEventListener('change', function () {
                if (roleInput.value == "3" || roleInput.value == "5") {  // Admin or VPAA role
                    // Clear and hide department and course selections
                    departmentInput.value = "";
                    courseInput.value = "";
                    courseWrapper.classList.add('d-none');
                    departmentWrapper.classList.add('d-none');
                    
                    // Make course optional
                    courseInput.removeAttribute('required');
                } else if (roleInput.value == "2") {  // Dean role
                    // Show only department, hide course
                    departmentInput.value = "";
                    courseInput.value = "";
                    courseWrapper.classList.add('d-none');
                    departmentWrapper.classList.remove('d-none');
                    
                    // Make course optional for Dean
                    courseInput.removeAttribute('required');
                } else if (roleInput.value == "1") {  // Chairperson role
                    // Show both department and course
                    departmentInput.value = "";
                    courseInput.value = "";
                    courseWrapper.classList.remove('d-none');
                    departmentWrapper.classList.remove('d-none');
                    
                    // Make course required for chairperson
                    courseInput.setAttribute('required', 'required');
                }
                
                // Trigger department change to reset course selection
                departmentInput.dispatchEvent(new Event('change'));
            });

            // Department change handler
            departmentInput.addEventListener('change', function() {
                const deptId = this.value;
                const courseSelect = courseInput;
                
                // If role is Admin, VPAA, or Dean, keep course wrapper hidden
                if (roleInput.value == "3" || roleInput.value == "5" || roleInput.value == "2") {
                    courseWrapper.classList.add('d-none');
                    if (roleInput.value == "3" || roleInput.value == "5") {
                        departmentWrapper.classList.add('d-none');
                    }
                    return;
                }
                
                // Reset and hide course selection if no department selected
                if (!deptId) {
                    courseWrapper.classList.add('d-none');
                    courseSelect.innerHTML = '<option value="">-- Choose Course --</option>';
                    return;
                }

                // Show loading state
                courseWrapper.classList.remove('d-none');
                courseSelect.innerHTML = '<option value="">Loading...</option>';

                // Fetch courses for selected department
                fetch(`/api/department/${deptId}/courses`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            courseSelect.innerHTML = '<option value="">No courses available</option>';
                            return;
                        }

                        if (data.length === 1) {
                            // If department has only one course, auto-select it but keep the input visible
                            courseSelect.innerHTML = `<option value="${data[0].id}" selected>${data[0].name}</option>`;
                            courseWrapper.classList.remove('d-none');
                        } else {
                            // If department has multiple courses, show the dropdown
                            courseSelect.innerHTML = '<option value="">-- Choose Course --</option>';
                            data.forEach(course => {
                                courseSelect.innerHTML += `<option value="${course.id}">${course.name}</option>`;
                            });
                            courseWrapper.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                    });
            });

            // Add input validation for email
            const emailInput = document.querySelector('input[name="email"]');
            const emailWarning = document.getElementById('email-warning');
            
            emailInput.addEventListener('input', function() {
                if (this.value.includes('@')) {
                    emailWarning.classList.remove('d-none');
                    this.classList.add('is-invalid');
                } else {
                    emailWarning.classList.add('d-none');
                    this.classList.remove('is-invalid');
                }
            });

            // Initialize course wrapper visibility if department is pre-selected
            if (departmentInput.value) {
                departmentInput.dispatchEvent(new Event('change'));
            }

            // Password visibility toggle functionality
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('password_confirmation');
            const togglePassword = document.getElementById('togglePassword');
            const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');

            // Add click event listeners for password toggles
            togglePassword.addEventListener('click', function() {
                const input = passwordField;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });

            togglePasswordConfirmation.addEventListener('click', function() {
                const input = confirmPasswordField;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Load session counts for all users on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sessionBadges = document.querySelectorAll('.session-count');
            
            sessionBadges.forEach(badge => {
                const userId = badge.dataset.userId;
                
                fetch(`/admin/users/${userId}/session-count`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const count = data.count;
                            badge.innerHTML = `<i class="bi bi-circle-fill"></i> ${count} active`;
                            
                            // Change badge color based on session count
                            badge.classList.remove('bg-info', 'bg-success', 'bg-warning');
                            if (count === 0) {
                                badge.classList.add('bg-secondary');
                            } else if (count === 1) {
                                badge.classList.add('bg-success');
                            } else {
                                badge.classList.add('bg-warning');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching session count:', error);
                        badge.innerHTML = '<i class="bi bi-x-circle"></i> Error';
                        badge.classList.remove('bg-info');
                        badge.classList.add('bg-danger');
                    });
            });

            // Force logout functionality
            const forceLogoutButtons = document.querySelectorAll('.force-logout-btn');
            
            forceLogoutButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const userName = this.dataset.userName;
                    
                    Swal.fire({
                        title: 'Force Logout User?',
                        html: `Are you sure you want to log out <strong>${userName}</strong> from all devices?<br><br>This will end all their active sessions immediately.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Force Logout',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            button.disabled = true;
                            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Logging out...';
                            
                            fetch(`/admin/users/${userId}/force-logout`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: data.message,
                                        confirmButtonColor: '#198754'
                                    });
                                    
                                    // Update session count badge
                                    const sessionBadge = document.querySelector(`.session-count[data-user-id="${userId}"]`);
                                    if (sessionBadge) {
                                        sessionBadge.innerHTML = '<i class="bi bi-circle-fill"></i> 0 active';
                                        sessionBadge.classList.remove('bg-info', 'bg-success', 'bg-warning');
                                        sessionBadge.classList.add('bg-secondary');
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message,
                                        confirmButtonColor: '#198754'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to force logout user. Please try again.',
                                    confirmButtonColor: '#198754'
                                });
                            })
                            .finally(() => {
                                // Reset button state
                                button.disabled = false;
                                button.innerHTML = '<i class="bi bi-door-open"></i> Force Logout';
                            });
                        }
                    });
                });
            });
        });
    </script>
@endpush
@push('scripts')
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            order: [[1, 'asc'], [0, 'asc']], // Sort by Role then Name
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                emptyTable: "No users found"
            },
            columnDefs: [
                { orderable: false, targets: 3 } // Disable sorting on Actions column
            ]
        });
    });
</script>
@endpush
@endsection
