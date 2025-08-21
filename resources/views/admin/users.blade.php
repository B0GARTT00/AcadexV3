@extends('layouts.app')

@section('content')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .swal-small {
            width: 360px !important;
            font-size: 0.875rem;
        }
        .swal2-html-container {
            margin: 0.5em 1em 0.5em !important;
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

<div class="container py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-dark fw-bold mb-0">👥 Users</h1>
        <button class="btn btn-success" onclick="openModal()">+ Add User</button>
    </div>

    {{-- Warning Message --}}
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        These users have higher access. Add one at your own discretion.
    </div>

    {{-- Users Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Username</th>
                        <th>User Role</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>
                                {{ $user->role == 1 ? 'Chairperson' : ($user->role == 2 ? 'Dean' : ($user->role == 3 ? 'Admin' : ($user->role == 5 ? 'VPAA' : 'Unknown'))) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted fst-italic py-3">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
            const modal = new bootstrap.Modal(document.getElementById('courseModal'));
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
                            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
                            confirmModal.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Proceed with confirmation modal if check fails
                        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
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
    </script>
@endpush
@endsection
