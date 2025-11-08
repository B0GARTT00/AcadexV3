<script>
    // Define hasUnsavedChanges globally
    let hasUnsavedChanges = false;
    let checkForChanges; // Declare the function variable globally
    let form; // Declare form globally

    function bindGradeInputEvents() {
        console.log("Binding grade input events...");
        
        // Add a small delay to ensure DOM is ready
        setTimeout(() => {
            // Safely query elements with null checks
            const tableBody = document.getElementById('studentTableBody');
            if (!tableBody) {
                console.log("Table body not found, skipping grade input binding");
                return;
            }

        const inputs = Array.from(tableBody.querySelectorAll('.grade-input') || []);
        const itemsInputs = Array.from(document.querySelectorAll('.items-input') || []);
        const courseOutcomeInputs = Array.from(document.querySelectorAll('.course-outcome-select') || []);
        const saveButton = document.getElementById('saveGradesBtn');
    form = document.getElementById('gradeForm'); // Target grade form explicitly
        const studentSearch = document.getElementById('studentSearch');

        // Set up form submission tracking
        if (form) {
            form.submitting = false;
            form.addEventListener('submit', function(e) {
                console.log('Form submit event fired');
                this.submitting = true;
                hasUnsavedChanges = false; // Clear the global flag on form submit
            });
        }

        // Track changes
        const originalValues = new Map();
        const originalCourseOutcomes = new Map();

        // Store original values
        inputs.forEach(input => {
            originalValues.set(input, input.value);
        });
        courseOutcomeInputs.forEach(input => {
            originalCourseOutcomes.set(input, input.value);
        });

        // Define checkForChanges function
        checkForChanges = function() {
            let hasChanges = false;
            let hasInvalidInputs = false;

            inputs.forEach(input => {
                // Check for changes from original value
                if (input.value !== originalValues.get(input)) {
                    hasChanges = true;
                }
                // Check for invalid inputs
                if (input.classList.contains('is-invalid')) {
                    hasInvalidInputs = true;
                }
            });

            courseOutcomeInputs.forEach(input => {
                if (input.value !== originalCourseOutcomes.get(input)) {
                    hasChanges = true;
                }
            });

            return {
                hasChanges,
                hasInvalidInputs
            };
        };

        // Track changes for course outcome inputs (moved outside checkForChanges)
        if (courseOutcomeInputs.length > 0) {
            courseOutcomeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    updateSaveButtonState();
                });
            });
        }

        // Function to update save button state
        function updateSaveButtonState() {
            if (!saveButton) return;

            const { hasChanges, hasInvalidInputs } = checkForChanges();
            hasUnsavedChanges = hasChanges;

            // Update button state
            saveButton.disabled = !hasChanges || hasInvalidInputs;
            saveButton.classList.toggle('has-changes', hasChanges);

            // Update notification
            const container = document.getElementById('unsavedNotificationContainer');
            
            if (hasChanges) {
                let message = 'You have unsaved changes';
                if (hasInvalidInputs) {
                    message = 'Please correct invalid grades before saving';
                }
                
                // Compact notification for beside save button
                const compactNotificationHTML = `
                    <div class="unsaved-notification-compact">
                        <i class="bi ${hasInvalidInputs ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-warning'}"></i>
                        <span class="small">${message}</span>
                    </div>
                `;
                
                // Show beside save button only
                if (container) {
                    container.innerHTML = compactNotificationHTML;
                }
            } else {
                // Clear notification
                if (container) {
                    container.innerHTML = '';
                }
            }

            // Update save button tooltip
            if (hasInvalidInputs) {
                saveButton.title = 'Please correct invalid grades before saving';
            } else if (!hasChanges) {
                saveButton.title = 'No changes to save';
            } else {
                saveButton.title = 'Save changes';
            }
        }

        console.log("Found items inputs:", itemsInputs.length);

        // Initialize data structures
        const inputGrid = {};
        const activityIds = new Set();
        const studentIds = new Set();

        // Create and append modal to body
        let warningModalElement = document.getElementById('gradeWarningModal');
        if (!warningModalElement) {
            const modalHtml = `
                <div class="modal fade" id="gradeWarningModal" tabindex="-1" aria-labelledby="gradeWarningModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="gradeWarningModalLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Invalid Grades Detected
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3">The following grades exceed the new maximum score:</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered" id="invalidGradesTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Student</th>
                                                <th>Current Grade</th>
                                                <th>New Maximum</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <p class="text-danger mt-3 mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Please adjust these grades before changing the number of items.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            warningModalElement = document.getElementById('gradeWarningModal');
        }

        const warningModal = new bootstrap.Modal(warningModalElement);

        // Function to show warning modal
        function showWarningModal(invalidGrades, newMax) {
            const tbody = document.querySelector('#invalidGradesTable tbody');
            tbody.innerHTML = invalidGrades.map(grade => `
                <tr>
                    <td>${grade.student}</td>
                    <td class="text-danger">${grade.grade}</td>
                    <td>${newMax}</td>
                </tr>
            `).join('');
            warningModal.show();
        }

        // Validation function
        function validateInput(input) {
            const value = input.value.trim();
            const max = parseInt(input.getAttribute('max'));
            
            // Remove existing tooltip
            const existingTooltip = input.parentNode.querySelector('.invalid-tooltip');
            if (existingTooltip) {
                existingTooltip.remove();
            }
            
            // Reset validation state
            input.classList.remove('is-invalid');
            
            if (value !== '') {
                const numValue = parseInt(value);
                // Check if the value is a valid number and within range (0 to max, inclusive)
                if (isNaN(numValue) || numValue < 0 || numValue > max) {
                    input.classList.add('is-invalid');
                    
                    // Create error message with icon
                    let errorHTML = '';
                    if (isNaN(numValue)) {
                        errorHTML = '<i class="bi bi-x-circle-fill"></i> Please enter a valid number';
                    } else if (numValue < 0) {
                        errorHTML = '<i class="bi bi-dash-circle-fill"></i> Score cannot be negative';
                    } else if (numValue > max) {
                        errorHTML = `<i class="bi bi-exclamation-circle-fill"></i> Maximum allowed score is ${max}`;
                    }
                    
                    // Create and show tooltip
                    const tooltip = document.createElement('div');
                    tooltip.className = 'invalid-tooltip';
                    tooltip.innerHTML = `<div class="error-message">${errorHTML}</div>`;
                    input.parentNode.appendChild(tooltip);
                    
                    return false;
                }
            }
            return true;
        }

        // Validate all inputs
        function validateAllInputs() {
            let isValid = true;
            inputs.forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            return isValid;
        }

        // New function to check if any grades exceed the new maximum
        function checkGradesAgainstNewMax(activityId, newMax) {
            const invalidGrades = [];
            document.querySelectorAll(`.grade-input[data-activity="${activityId}"]`).forEach(gradeInput => {
                const value = parseInt(gradeInput.value);
                if (!isNaN(value) && value > newMax) {
                    const studentName = gradeInput.closest('tr')?.querySelector('td')?.textContent.trim() || 'Unknown Student';
                    invalidGrades.push({
                        student: studentName,
                        grade: value
                    });
                }
            });
            return invalidGrades;
        }

        // Handle student search if element exists
        if (studentSearch) {
            studentSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = tableBody.querySelectorAll('.student-row');
                
                rows.forEach(function(row) {
                    const studentName = row.querySelector('td')?.textContent.toLowerCase() || '';
                    row.style.display = studentName.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        // Handle sorting
        const sortFilter = document.getElementById('sortFilter');
        if (sortFilter) {
            // Function to sort the table
            function sortTable(order) {
                const rows = Array.from(tableBody.querySelectorAll('.student-row'));
                
                rows.sort((a, b) => {
                    const nameA = a.querySelector('td')?.textContent.trim().toLowerCase() || '';
                    const nameB = b.querySelector('td')?.textContent.trim().toLowerCase() || '';
                    
                    if (order === 'asc') {
                        return nameA.localeCompare(nameB);
                    } else if (order === 'desc') {
                        return nameB.localeCompare(nameA);
                    }
                    return 0;
                });
                
                // Clear the table body
                tableBody.innerHTML = '';
                // Append sorted rows
                rows.forEach(row => tableBody.appendChild(row));
            }

            // Sort initially in A to Z order
            sortTable('asc');

            // Handle sort filter changes
            sortFilter.addEventListener('change', function() {
                sortTable(this.value);
            });
        }

        // Handle items inputs
        if (itemsInputs.length > 0) {
            itemsInputs.forEach(input => {
                if (!input) return; // Skip if input is null
                
                // Prevent form submission on enter
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        this.blur(); // Remove focus to trigger change event
                    }
                });
                
                input.addEventListener('change', function(e) {
                    // Prevent any form submission
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const activityId = this.dataset.activityId;
                    const newValue = parseInt(this.value);
                    const oldValue = parseInt(this.defaultValue);

                    if (isNaN(newValue) || newValue < 1) {
                        showWarningModal([{ student: 'Error', grade: 'Invalid input' }], 1);
                        this.value = this.defaultValue;
                        return;
                    }

                    // Check if new maximum would invalidate existing grades
                    const invalidGrades = checkGradesAgainstNewMax(activityId, newValue);
                    if (newValue < oldValue && invalidGrades.length > 0) {
                        showWarningModal(invalidGrades, newValue);
                        this.value = oldValue;
                        return;
                    }

                    // Disable save button during the update
                    if (saveButton) {
                        saveButton.disabled = true;
                    }

                    fetch('/instructor/activities/' + activityId, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            number_of_items: newValue,
                            type: this.closest('th')?.querySelector('.fw-semibold')?.textContent.toLowerCase() || '',
                            title: this.closest('th')?.querySelector('.text-muted')?.textContent.trim() || ''
                        })
                    })
                    .then(async response => {
                        if (!response.ok) {
                            const errorData = await response.json().catch(() => null);
                            console.error('Server response:', {
                                status: response.status,
                                statusText: response.statusText,
                                data: errorData
                            });
                            throw new Error(
                                errorData?.message || 
                                `Server returned ${response.status}: ${response.statusText}`
                            );
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            this.defaultValue = newValue;
                            let hasInvalidGrades = false;
                            
                            document.querySelectorAll(`.grade-input[data-activity="${activityId}"]`).forEach(gradeInput => {
                                if (gradeInput) {
                                    const currentValue = parseInt(gradeInput.value);
                                    gradeInput.max = newValue;
                                    gradeInput.title = `Max: ${newValue}`;
                                    
                                    // Check if current value exceeds new max
                                    if (!isNaN(currentValue) && currentValue > newValue) {
                                        hasInvalidGrades = true;
                                        gradeInput.classList.add('is-invalid');
                                    } else {
                                        gradeInput.classList.remove('is-invalid');
                                    }
                                }
                            });

                            // Update save button state
                            if (saveButton) {
                                const isValid = validateAllInputs();
                                saveButton.disabled = !isValid;
                                saveButton.title = isValid ? '' : 'Please correct invalid grades before saving';
                            }

                            // Show warning if any grades became invalid
                            if (hasInvalidGrades) {
                                showWarningModal(checkGradesAgainstNewMax(activityId, newValue), newValue);
                            }
                        } else {
                            throw new Error(data.message || 'Failed to update number of items');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update number of items: ' + error.message);
                        this.value = oldValue;
                        
                        // Re-enable save button on error
                        if (saveButton) {
                            const isValid = validateAllInputs();
                            saveButton.disabled = !isValid;
                        }
                    });
                });
            });
        }

        // Handle grade inputs
        if (inputs.length > 0) {
            inputs.forEach(input => {
                if (!input) return; // Skip if input is null
                
                const student = input.dataset.student;
                const activity = input.dataset.activity;
                
                if (student && activity) {
                    activityIds.add(activity);
                    studentIds.add(student);
                    
                    if (!inputGrid[activity]) inputGrid[activity] = {};
                    inputGrid[activity][student] = input;
                }

                // Real-time validation
                input.addEventListener('input', () => {
                    validateInput(input);
                    updateSaveButtonState();
                });
                
                // Handle keyboard input
                input.addEventListener('keypress', function(e) {
                    // Allow only numbers and control keys
                    if (!/^\d$/.test(e.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key)) {
                        e.preventDefault();
                        return;
                    }
                    
                    const currentValue = this.value;
                    const max = parseInt(this.getAttribute('max'));
                    
                    // Check if new value would exceed max
                    const newValue = parseInt(currentValue + e.key);
                    if (newValue > max) {
                        e.preventDefault();
                        showError(this, `Cannot exceed maximum score of ${max}`);
                    }

                    updateSaveButtonState();
                });

                // Handle up/down arrow keys for increment/decrement
                input.addEventListener('keydown', function(e) {
                    const max = parseInt(this.getAttribute('max'));
                    const currentValue = parseInt(this.value) || 0;

                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (currentValue < max) {
                            this.value = currentValue + 1;
                            this.dispatchEvent(new Event('input'));
                        } else {
                            showError(this, `Cannot exceed maximum score of ${max}`);
                        }
                    } else if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (currentValue > 0) {
                            this.value = currentValue - 1;
                            this.dispatchEvent(new Event('input'));
                        }
                    }
                });

                // Format number on blur
                input.addEventListener('blur', function() {
                    const value = this.value.trim();
                    if (value !== '') {
                        const numValue = parseInt(value);
                        if (!isNaN(numValue)) {
                            this.value = numValue; // Remove leading zeros
                        }
                    }
                });

                // Handle paste event
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').trim();
                    const max = parseInt(this.getAttribute('max'));
                    
                    if (!/^\d+$/.test(pastedData)) {
                        showError(this, 'Only numbers can be pasted');
                        return;
                    }
                    
                    const numValue = parseInt(pastedData);
                    if (numValue > max) {
                        showError(this, `Cannot paste value greater than ${max}`);
                        return;
                    }
                    
                    this.value = numValue;
                    this.dispatchEvent(new Event('input'));
                });

                // Helper function to show errors
                function showError(input, message) {
                    // Remove any existing tooltip first
                    const existingTooltip = input.parentNode.querySelector('.invalid-tooltip');
                    if (existingTooltip) {
                        existingTooltip.remove();
                    }

                    const tooltip = document.createElement('div');
                    tooltip.className = 'invalid-tooltip';
                    tooltip.innerHTML = `
                        <div class="error-message">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>${message}</span>
                        </div>
                    `;
                    input.parentNode.appendChild(tooltip);
                    input.classList.add('is-invalid');
                    
                    setTimeout(() => {
                        tooltip.remove();
                        const currentValue = parseInt(input.value);
                        const max = parseInt(input.getAttribute('max'));
                        if (!isNaN(currentValue) && currentValue >= 0 && currentValue <= max) {
                            input.classList.remove('is-invalid');
                        }
                    }, 2000);
                }
                
                // Clear error on focus
                input.addEventListener('focus', function() {
                    if (this.value.trim() === '') {
                        this.classList.remove('is-invalid');
                        const tooltip = this.parentNode.querySelector('.invalid-tooltip');
                        if (tooltip) tooltip.remove();
                    }
                });
            });
        }

        // Setup form validation if form exists
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                const { hasInvalidInputs } = checkForChanges();
                
                if (hasInvalidInputs) {
                    alert('Please correct all invalid grades before submitting.');
                    return;
                }

                // Show loading state
                if (saveButton) {
                    saveButton.disabled = true;
                    saveButton.querySelector('.spinner-border')?.classList.remove('d-none');
                }

                // Clear unsaved changes before submitting
                hasUnsavedChanges = false;
                
                // Get form data
                const formData = new FormData(form);
                
                // Submit form using fetch
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async response => {
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => null);
                        const message = errorData?.message || 'Failed to save grades. Please try again.';
                        throw new Error(message);
                    }

                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        return response.json();
                    }

                    throw new Error('Unexpected server response format.');
                })
                .then(data => {
                    if (!data || data.status !== 'success') {
                        throw new Error(data?.message || 'Failed to save grades.');
                    }

                    // Update original values after successful save
                    inputs.forEach(input => {
                        originalValues.set(input, input.value);
                    });
                    updateSaveButtonState();

                    // Hide loading state on the current button instance
                    if (saveButton) {
                        saveButton.disabled = true;
                        saveButton.querySelector('.spinner-border')?.classList.add('d-none');
                    }

                    const refreshPromise = typeof window.refreshGradeSection === 'function'
                        ? window.refreshGradeSection()
                        : Promise.resolve();

                    return refreshPromise.then(() => data);
                })
                .then(data => {
                    const message = data?.message || 'Grades have been saved successfully.';

                    const container = document.querySelector('.container-fluid');
                    if (container) {
                        const successMessage = document.createElement('div');
                        successMessage.className = 'alert alert-success alert-dismissible fade show';
                        successMessage.innerHTML = `
                            <strong>Success!</strong> ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        container.insertBefore(successMessage, container.firstChild);

                        // Remove success message after 3 seconds
                        setTimeout(() => {
                            successMessage.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error?.message || 'Failed to save grades. Please try again.');
                    
                    // Reset button state
                    if (saveButton) {
                        saveButton.disabled = false;
                        saveButton.querySelector('.spinner-border')?.classList.add('d-none');
                    }
                });
            });
        }

        // Setup navigation sequence
        const sequence = [];
        const rows = Array.from(tableBody.querySelectorAll('.student-row'));
        const activityColumns = Array.from(document.querySelectorAll('th')).slice(1, -1); // Skip student name column and final grade column

        // Create sequence by going down each column first
        activityColumns.forEach((_, colIndex) => {
            rows.forEach((row) => {
                const input = row.querySelectorAll('.grade-input')[colIndex];
                if (input) sequence.push(input);
            });
        });

        // Enhanced keyboard navigation with column-based flow
        sequence.forEach((input, idx) => {
            if (!input) return; // Skip if input is null
            
            input.addEventListener('keydown', e => {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    e.preventDefault();
                    
                    // Get the next input in sequence
                    const next = sequence[idx + 1];
                    if (next) {
                        // Add visual feedback for current row
                        const currentRow = input.closest('tr');
                        if (currentRow) {
                            currentRow.classList.add('navigating');
                            setTimeout(() => currentRow.classList.remove('navigating'), 500);
                        }
                        
                        // Focus and select next input
                        next.focus();
                        next.select();
                        
                        // Add visual feedback for next row
                        const nextRow = next.closest('tr');
                        if (nextRow) {
                            nextRow.classList.add('navigating');
                            setTimeout(() => nextRow.classList.remove('navigating'), 500);
                        }

                        // Scroll the next input into view if needed
                        next.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });

        // Add styles for navigation feedback
        let navigationStyle = document.getElementById('gradeNavigationStyle');
        if (!navigationStyle) {
            navigationStyle = document.createElement('style');
            navigationStyle.id = 'gradeNavigationStyle';
            navigationStyle.textContent = `
            .student-row.navigating {
                background-color: rgba(25, 135, 84, 0.1) !important;
                transition: background-color 0.3s ease;
            }
            
            .grade-input:focus {
                border-color: var(--primary-green);
                box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
                background-color: #fff;
                position: relative;
            }

            /* Add visual indicator for current column */
            .grade-input:focus::after {
                content: '';
                position: absolute;
                top: 0;
                left: -2px;
                height: 100%;
                width: 3px;
                background-color: var(--primary-green);
                border-radius: 2px;
            }

            /* Add visual indicator for current row */
            .student-row:has(.grade-input:focus) {
                background-color: rgba(25, 135, 84, 0.05) !important;
            }

            /* Add visual indicator for current column */
            .grade-input:focus {
                position: relative;
            }

            .grade-input:focus::before {
                content: '';
                position: absolute;
                top: -2px;
                left: 0;
                right: 0;
                height: 3px;
                background-color: var(--primary-green);
                border-radius: 2px;
            }
        `;
            document.head.appendChild(navigationStyle);
        }

        // Initial state check
        updateSaveButtonState();
        
        // Add click handler for save button to prevent beforeunload warning
        if (saveButton) {
            saveButton.addEventListener('click', function(e) {
                console.log('Save button clicked');
                if (form) {
                    form.submitting = true;
                }
                hasUnsavedChanges = false; // Clear the global flag
                
                // Clear the notification immediately
                const container = document.getElementById('unsavedNotificationContainer');
                if (container) {
                    container.innerHTML = '';
                }
            });
        }
        
        // Initialize course outcome dropdowns
        if (typeof initializeCourseOutcomeDropdowns === 'function') {
            initializeCourseOutcomeDropdowns();
        }
        }, 100); // End setTimeout
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Grade script loaded");
        bindGradeInputEvents();
        
        // Also initialize course outcome dropdowns on initial load
        if (typeof initializeCourseOutcomeDropdowns === 'function') {
            initializeCourseOutcomeDropdowns();
        }
    });
    // Modify the beforeunload event handler
    window.addEventListener('beforeunload', function(e) {
        // Don't show warning if form is being submitted or no unsaved changes
        if (form && form.submitting) {
            console.log('Form is being submitted, allowing navigation');
            return;
        }
        
        if (!hasUnsavedChanges) {
            console.log('No unsaved changes, allowing navigation');
            return;
        }
        
        if (typeof checkForChanges === 'function') {
            const { hasChanges } = checkForChanges();
            if (hasChanges) {
                console.log('Unsaved changes detected, showing warning');
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        }
    });

    // Modify the click event handler for links
    document.addEventListener('click', function(e) {
        if (typeof checkForChanges === 'function') {
            const { hasChanges } = checkForChanges();
            if (hasChanges && (!form || !form.submitting)) {
                const link = e.target.closest('a');
                if (link && !link.hasAttribute('data-bs-toggle')) {
                    e.preventDefault();
                    
                    // Use custom modal if available, otherwise fallback to confirm
                    if (typeof window.showUnsavedChangesModal === 'function') {
                        window.showUnsavedChangesModal(() => {
                            hasUnsavedChanges = false; // Clear the flag before navigation
                            window.location.href = link.href;
                        });
                    } else {
                        if (confirm('You have unsaved changes. Are you sure you want to leave this page?')) {
                            hasUnsavedChanges = false; // Clear the flag before navigation
                            window.location.href = link.href;
                        }
                    }
                }
            }
        }
    });

    // Add styles for notifications
    const style = document.createElement('style');
    style.textContent = `
        .btn-pulse {
            animation: pulse 2s infinite;
            box-shadow: 0 0 0 rgba(40, 167, 69, 0.4);
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        #unsavedNotification {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .unsaved-notification {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            padding: 12px 20px;
            border-radius: 8px;
            border-left: 4px solid #f57c00;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            margin: 10px 0;
            animation: slideIn 0.3s ease-out;
        }

        .unsaved-notification i {
            font-size: 1.1em;
            color: #e65100;
        }

        .unsaved-notification span {
            flex: 1;
        }

        .unsaved-notification-compact {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid rgba(255, 193, 7, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            animation: slideInRight 0.3s ease-out;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15);
        }

        .unsaved-notification-compact i {
            font-size: 1em;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .invalid-tooltip {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 5;
            display: none;
            max-width: 100%;
            padding: 0.25rem 0.5rem;
            margin-top: 0.1rem;
            font-size: 0.875rem;
            color: #fff;
            background-color: rgba(220, 53, 69, 0.9);
            border-radius: 0.25rem;
        }

        .grade-input.is-invalid:hover + .invalid-tooltip,
        .grade-input.is-invalid:focus + .invalid-tooltip {
            display: block;
        }

        .grade-input::-webkit-inner-spin-button,
        .grade-input::-webkit-outer-spin-button,
        .items-input::-webkit-inner-spin-button,
        .items-input::-webkit-outer-spin-button {
            opacity: 1;
            background: transparent;
        }

        .grade-input::placeholder,
        .items-input::placeholder {
            color: #adb5bd;
            opacity: 1;
        }
    `;
    document.head.appendChild(style);

    // Export for external use
    window.bindGradeInputEvents = bindGradeInputEvents;

    // Course Outcome Dropdown JavaScript
    function initializeCourseOutcomeDropdowns() {
        console.log('Initializing course outcome dropdowns...');
        
        // Find all dropdown elements
        const dropdowns = document.querySelectorAll('.course-outcome-select');
        
        console.log('Dropdowns found:', dropdowns.length);
        
        // No need to add change handlers here as they're already handled by the main script
        // The main script already tracks these elements and updates the save button state
    }

    // Export for external use
    window.initializeCourseOutcomeDropdowns = initializeCourseOutcomeDropdowns;

        // Function to update course outcome dropdowns after term change
        window.updateCourseOutcomeDropdowns = function(subjectId, term) {
            // Fetch course outcomes for the selected subject and term
            fetch(`/instructor/course-outcomes?subject_id=${subjectId}&term=${term}`)
                .then(response => response.json())
                .then(data => {
                    const dropdowns = document.querySelectorAll('.course-outcome-select');
                    
                    dropdowns.forEach(dropdown => {
                        // Store current selection
                        const currentValue = dropdown.value;
                        
                        // Clear existing options except the first one
                        dropdown.innerHTML = '<option value="">Select Course Outcome</option>';
                        
                        if (Array.isArray(data) && data.length > 0) {
                            // Add course outcome options
                            data.forEach(outcome => {
                                const option = document.createElement('option');
                                option.value = outcome.id;
                                option.textContent = `${outcome.code} - ${outcome.identifier}`;
                                if (outcome.is_deleted) {
                                    option.classList.add('text-warning');
                                }
                                dropdown.appendChild(option);
                            });
                            
                            // Restore previous selection if it still exists
                            if (currentValue && dropdown.querySelector(`option[value="${currentValue}"]`)) {
                                dropdown.value = currentValue;
                            }
                        }
                    });
                })
                .catch(() => {
                    console.error('Error loading course outcomes for dropdowns');
                });
        };
</script>
